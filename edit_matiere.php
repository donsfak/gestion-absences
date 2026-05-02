<?php
require 'db.php';
include 'menu.php';
$id = $_GET['id'];

// 1. Récupérer les infos actuelles de la matière
$stmt = $pdo->prepare("SELECT * FROM MATIERE WHERE code_matiere = ?");
$stmt->execute([$id]);
$matiere = $stmt->fetch();

// 2. Gérer la mise à jour quand on clique sur "Enregistrer"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nouveau_nom = htmlspecialchars($_POST['nom_matiere']);
    try {
        $stmt = $pdo->prepare("UPDATE MATIERE SET Nom_matiere = ? WHERE code_matiere = ?");
        $stmt->execute([$nouveau_nom, $id]);
        header("Location: matiere.php?msg=Modifié avec succès");
        exit();
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Modifier Matière</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h3>Modifier la matière</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label>Code (non modifiable)</label>
                        <input type="text" class="form-control" value="<?= $matiere['code_matiere'] ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label>Nouveau nom</label>
                        <input type="text" class="form-control" name="nom_matiere" value="<?= $matiere['Nom_matiere'] ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="matiere.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>