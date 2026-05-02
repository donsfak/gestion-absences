<?php
// edit_enseignant.php
require 'db.php';
include 'menu.php';

$id = $_GET['id'];

// 1. Récupérer les données actuelles
$stmt = $pdo->prepare("SELECT * FROM ENSEIGNANT WHERE id_enseignant = ?");
$stmt->execute([$id]);
$e = $stmt->fetch();

// 2. Traiter la modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $stmt = $pdo->prepare("UPDATE ENSEIGNANT SET Nom=?, Prenom=?, Mail=?, Specialite=?, Diplome=?, Sexe=? WHERE id_enseignant=?");
        $stmt->execute([
            $_POST['nom'], $_POST['prenom'], $_POST['mail'], 
            $_POST['specialite'], $_POST['diplome'], $_POST['sexe'], $id
        ]);
        header("Location: enseignant.php?msg=Modifié");
        exit();
    } catch (PDOException $err) {
        echo "Erreur : " . $err->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Modifier Enseignant</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <h3>Modifier l'enseignant</h3>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Nom</label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($e['Nom']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Prénom</label>
                            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($e['Prenom']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="mail" class="form-control" value="<?= htmlspecialchars($e['Mail']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Spécialité</label>
                        <input type="text" name="specialite" class="form-control" value="<?= htmlspecialchars($e['Specialite']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Diplôme</label>
                        <input type="text" name="diplome" class="form-control" value="<?= htmlspecialchars($e['Diplome']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Sexe</label>
                        <select name="sexe" class="form-select">
                            <option value="Homme" <?= $e['Sexe'] == 'Homme' ? 'selected' : '' ?>>Homme</option>
                            <option value="Femme" <?= $e['Sexe'] == 'Femme' ? 'selected' : '' ?>>Femme</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Enregistrer les changements</button>
                    <a href="enseignant.php" class="btn btn-link w-100 mt-2">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>