<?php
// index.php
session_start();
require 'db.php';
$erreur = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['identifiant'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM USERS WHERE identifiant = ? AND mot_de_passe = ?");
    $stmt->execute([$user, $pass]);
    $u = $stmt->fetch();

    if ($u) {
        $_SESSION['user_id'] = $u['id_user'];
        $_SESSION['user_nom'] = $u['nom_complet'];
        $_SESSION['user_role'] = $u['role'];
        header("Location: edition.php"); // On redirige vers le dashboard
        exit();
    } else {
        $erreur = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Connexion - Gestion Absences</title>
</head>
<body class="bg-primary d-flex align-items-center" style="height: 100vh;">
    <div class="container">
        <div class="card shadow-lg mx-auto border-0" style="max-width: 400px; border-radius: 15px;">
            <div class="card-body p-5 text-center">
                <i class="bi bi-shield-lock-fill text-primary display-1 mb-4"></i>
                <h3 class="fw-bold mb-4">Authentification</h3>
                <?php if($erreur): ?>
                    <div class="alert alert-danger small"><?= $erreur ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="identifiant" class="form-control mb-3 p-3" placeholder="Identifiant" required>
                    <input type="password" name="password" class="form-control mb-4 p-3" placeholder="Mot de passe" required>
                    <button type="submit" class="btn btn-primary w-100 p-3 fw-bold">SE CONNECTER</button>

<div class="text-center mt-3">
    <span class="text-muted small">Nouveau dans l'établissement ?</span><br>
    <a href="register.php" class="text-decoration-none fw-bold text-primary">Créer un compte</a>
</div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>