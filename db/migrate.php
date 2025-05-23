<?php
require_once __DIR__ . '/../service/db_utils.php';

class Migration {
    private $link;
    private $migrations_dir;
    private $migrations_table = 'migrations';

    public function __construct($link) {
        $this->link = $link;
        $this->migrations_dir = __DIR__ . '/migrations';
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrations_table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$this->link->query($sql)) {
            die("Erreur lors de la création de la table migrations: " . $this->link->error);
        }
    }

    public function create($description) {
        $timestamp = date('Ymd_His');
        $filename = sprintf("%s_%s.sql", $timestamp, $this->sanitizeFilename($description));
        $filepath = $this->migrations_dir . '/' . $filename;

        $content = "-- Migration: {$description}\n";
        $content .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "-- UP Migration\n";
        $content .= "-- TODO: Ajouter vos commandes SQL ici\n\n";
        $content .= "-- DOWN Migration\n";
        $content .= "-- TODO: Ajouter vos commandes de rollback ici\n";

        if (file_put_contents($filepath, $content)) {
            echo "Migration créée : {$filename}\n";
        } else {
            echo "Erreur lors de la création de la migration\n";
        }
    }

    public function up() {
        $files = $this->getPendingMigrations();
        if (empty($files)) {
            echo "Aucune migration en attente\n";
            return;
        }

        $batch = $this->getNextBatch();
        foreach ($files as $file) {
            $this->executeMigration($file, $batch, 'up');
        }
    }

    public function down() {
        $lastBatch = $this->getLastBatch();
        if (!$lastBatch) {
            echo "Aucune migration à annuler\n";
            return;
        }

        $migrations = $this->getMigrationsByBatch($lastBatch);
        foreach (array_reverse($migrations) as $migration) {
            $this->executeMigration($migration['migration'], $lastBatch, 'down');
        }
    }

    public function status() {
        $executed = $this->getExecutedMigrations();
        $files = $this->getAllMigrationFiles();
        
        echo "Statut des migrations :\n";
        echo str_repeat('-', 80) . "\n";
        echo sprintf("%-40s %-20s %s\n", "Migration", "Statut", "Date d'exécution");
        echo str_repeat('-', 80) . "\n";

        foreach ($files as $file) {
            $status = isset($executed[$file]) ? "Exécutée" : "En attente";
            $date = isset($executed[$file]) ? $executed[$file] : "-";
            echo sprintf("%-40s %-20s %s\n", $file, $status, $date);
        }
    }

    private function executeMigration($file, $batch, $direction) {
        $filepath = $this->migrations_dir . '/' . $file;
        if (!file_exists($filepath)) {
            echo "Fichier de migration non trouvé : {$file}\n";
            return;
        }

        $content = file_get_contents($filepath);
        $parts = explode("-- {$direction} Migration", $content);
        
        if (count($parts) < 2) {
            echo "Format de migration invalide dans {$file}\n";
            return;
        }

        $sql = trim($parts[1]);
        if (empty($sql)) {
            echo "Aucune commande SQL trouvée pour {$direction} dans {$file}\n";
            return;
        }

        $this->link->begin_transaction();
        try {
            if ($this->link->multi_query($sql)) {
                do {
                    if ($result = $this->link->store_result()) {
                        $result->free();
                    }
                } while ($this->link->more_results() && $this->link->next_result());
            }

            if ($direction === 'up') {
                $stmt = $this->link->prepare("INSERT INTO {$this->migrations_table} (migration, batch) VALUES (?, ?)");
                $stmt->bind_param("si", $file, $batch);
                $stmt->execute();
            } else {
                $stmt = $this->link->prepare("DELETE FROM {$this->migrations_table} WHERE migration = ? AND batch = ?");
                $stmt->bind_param("si", $file, $batch);
                $stmt->execute();
            }

            $this->link->commit();
            echo "Migration {$direction} exécutée avec succès : {$file}\n";
        } catch (Exception $e) {
            $this->link->rollback();
            echo "Erreur lors de l'exécution de la migration {$file} : " . $e->getMessage() . "\n";
        }
    }

    private function getPendingMigrations() {
        $executed = $this->getExecutedMigrations();
        $files = $this->getAllMigrationFiles();
        return array_diff($files, array_keys($executed));
    }

    private function getExecutedMigrations() {
        $migrations = [];
        $result = $this->link->query("SELECT migration, executed_at FROM {$this->migrations_table} ORDER BY id");
        while ($row = $result->fetch_assoc()) {
            $migrations[$row['migration']] = $row['executed_at'];
        }
        return $migrations;
    }

    private function getAllMigrationFiles() {
        $files = glob($this->migrations_dir . '/*.sql');
        return array_map('basename', $files);
    }

    private function getNextBatch() {
        $result = $this->link->query("SELECT MAX(batch) as max_batch FROM {$this->migrations_table}");
        $row = $result->fetch_assoc();
        return ($row['max_batch'] ?? 0) + 1;
    }

    private function getLastBatch() {
        $result = $this->link->query("SELECT MAX(batch) as last_batch FROM {$this->migrations_table}");
        $row = $result->fetch_assoc();
        return $row['last_batch'] ?? null;
    }

    private function getMigrationsByBatch($batch) {
        $migrations = [];
        $stmt = $this->link->prepare("SELECT migration FROM {$this->migrations_table} WHERE batch = ? ORDER BY id");
        $stmt->bind_param("i", $batch);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $migrations[] = $row;
        }
        return $migrations;
    }

    private function sanitizeFilename($filename) {
        return preg_replace('/[^a-z0-9_]/', '_', strtolower($filename));
    }
}

// Gestion des arguments de ligne de commande
if ($argc < 2) {
    die("Usage: php migrate.php [create|up|down|status] [description]\n");
}

$command = $argv[1];
$migration = new Migration($link);

switch ($command) {
    case 'create':
        if ($argc < 3) {
            die("Usage: php migrate.php create \"description de la migration\"\n");
        }
        $migration->create($argv[2]);
        break;
    case 'up':
        $migration->up();
        break;
    case 'down':
        $migration->down();
        break;
    case 'status':
        $migration->status();
        break;
    default:
        die("Commande non reconnue : {$command}\n");
} 