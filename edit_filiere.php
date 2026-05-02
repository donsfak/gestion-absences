<?php
// edit_filiere.php
require 'db.php';
include 'menu.php'; // On n'oublie pas le menu !

$id = $_GET['id'];
$message = '';

// 1. Gérer la mise à jour quand on clique sur "Enregistrer"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $stmt = $pdo->prepare("UPDATE FILIERE SET Libele_filiere = ?, Nbre_etudiant = ? WHERE code_filiere = ?");
        $stmt->execute([$_POST['libelle'], $_POST['nbre'], $id]);
        
        // Redirection avec le fameux exit();
        header("Location: filiere.php?msg=Modifié");
        exit(); 
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
    }
}

// 2. Récupérer les données actuelles pour pré-remplir le formulaire
// J'ai sécurisé cette requête avec un prepare() plutôt qu'un query() direct
$stmt_fetch = $pdo->prepare("SELECT * FROM FILIERE WHERE code_filiere = ?");
$stmt_fetch->execute([$id]);
$f = $stmt_fetch->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Modifier Filière</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h3>Modifier la filière</h3>
                <?= $message ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Code Filière (Fixe)</label>
                        <input type="text" class="form-control text-muted" value="<?= htmlspecialchars($f['code_filiere']) ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Libellé de la filière</label>
                        <input type="text" name="libelle" class="form-control" value="<?= htmlspecialchars($f['Libele_filiere']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre d'étudiants</label>
                        <input type="number" name="nbre" class="form-control" value="<?= htmlspecialchars($f['Nbre_etudiant']) ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Enregistrer les modifications</button>
                    <a href="filiere.php" class="btn btn-link text-secondary w-100 mt-2 text-center d-block">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>