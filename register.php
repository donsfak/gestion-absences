<?php
// register.php
require 'db.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = htmlspecialchars($_POST['identifiant']);
    $password = $_POST['password']; // En production, il faudra utiliser password_hash()
    $nom_complet = htmlspecialchars($_POST['nom_complet']);
    $role = $_POST['role'];

    // On vérifie si l'identifiant existe déjà
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM USERS WHERE identifiant = ?");
    $stmt_check->execute([$identifiant]);
    
    if ($stmt_check->fetchColumn() > 0) {
        $message = "<div class='alert alert-danger small'><i class='bi bi-exclamation-triangle me-1'></i> Cet identifiant est déjà pris.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO USERS (identifiant, mot_de_passe, nom_complet, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$identifiant, $password, $nom_complet, $role]);
            $message = "<div class='alert alert-success small'><i class='bi bi-check-circle me-1'></i> Compte créé avec succès ! <br><a href='index.php' class='btn btn-sm btn-success mt-2 w-100 fw-bold'>Aller à la connexion</a></div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger small'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Inscription - Gestion Absences</title>
</head>
<body class="bg-primary d-flex align-items-center" style="height: 100vh;">
    <div class="container">
        <div class="card shadow-lg mx-auto border-0" style="max-width: 450px; border-radius: 15px;">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-person-badge-fill text-primary display-4"></i>
                    <h3 class="fw-bold mt-2">Nouveau Compte</h3>
                    <p class="text-muted small">Configurez un accès au système</p>
                </div>
                
                <?= $message ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Nom et Prénom</label>
                        <input type="text" name="nom_complet" class="form-control bg-light" placeholder="Ex: Jean Dupont" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Identifiant de connexion</label>
                        <input type="text" name="identifiant" class="form-control bg-light" placeholder="Ex: jdupont" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Mot de passe</label>
                        <input type="password" name="password" class="form-control bg-light" placeholder="Créer un mot de passe" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Rôle dans l'établissement</label>
                        <select name="role" class="form-select border-primary" required>
                            <option value="PROFESSEUR">Professeur (Accès Saisie uniquement)</option>
                            <option value="ADMIN">Administrateur (Accès Total)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 p-2 fw-bold"><i class="bi bi-person-plus-fill me-2"></i>CRÉER LE COMPTE</button>
                </form>

                <div class="mt-4 text-center">
                    <a href="index.php" class="text-decoration-none text-secondary small"><i class="bi bi-arrow-left me-1"></i>Retour à la connexion</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>