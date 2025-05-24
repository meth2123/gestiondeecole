<?php
session_start();
require_once __DIR__ . '/db_utils.php';

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Méthode non autorisée');
}

// Vérifier si les champs requis sont présents
if (!isset($_POST['myid']) || !isset($_POST['mypassword'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Champs manquants');
}

// Nettoyer les entrées
$myid = filter_var($_POST['myid'], FILTER_SANITIZE_STRING);
$mypassword = $_POST['mypassword']; // Ne pas nettoyer le mot de passe

// Vérifier que les champs ne sont pas vides
if (empty($myid) || empty($mypassword)) {
    header('Location: ../index.php?error=empty_fields');
    exit;
}

try {
    // Récupérer le mot de passe stocké et le type d'utilisateur
    $sql = "SELECT usertype, password, status FROM users WHERE userid = ?";
    $stmt = $link->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erreur de préparation de la requête");
    }
    
    $stmt->bind_param("s", $myid);
    
    if (!$stmt->execute()) {
        throw new Exception("Erreur d'exécution de la requête");
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $stored_password = $row['password'];
        $control = $row['usertype'];
        $status = $row['status'];
        
        // Vérifier si le compte est actif
        if ($status !== 'active') {
            header('Location: ../index.php?error=inactive_account');
            exit;
        }
        
        // Vérifier le mot de passe
        if (password_verify($mypassword, $stored_password)) {
            // Connexion réussie
            $_SESSION['login_id'] = $myid;
            $_SESSION['user_type'] = $control;
            $_SESSION['last_activity'] = time();
            
            // Redirection selon le type d'utilisateur
            $redirect_paths = [
                'admin' => '../module/admin',
                'teacher' => '../module/teacher',
                'student' => '../module/student',
                'staff' => '../module/staff',
                'parent' => '../module/parent'
            ];
            
            if (isset($redirect_paths[$control])) {
                header('Location: ' . $redirect_paths[$control]);
            } else {
                header('Location: ../index.php?error=invalid_user_type');
            }
            exit;
        }
    }
    
    // Si on arrive ici, la connexion a échoué
    header('Location: ../index.php?error=invalid_credentials');
    exit;
    
} catch (Exception $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    header('Location: ../index.php?error=system_error');
    exit;
}
?>
