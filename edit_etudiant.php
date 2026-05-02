<?php
require 'db.php';
$id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $pdo->prepare("UPDATE ETUDIANT SET Nom = ?, Prenom = ?, Sexe = ?, code_filiere = ? WHERE id_etudiant = ?");
    $stmt->execute([$_POST['nom'], $_POST['prenom'], $_POST['sexe'], $_POST['filiere'], $id]);
    header("Location: etudiant.php");
}

$etu = $pdo->query("SELECT * FROM ETUDIANT WHERE id_etudiant = $id")->fetch();
$filieres = $pdo->query("SELECT * FROM FILIERE")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Modifier Étudiant</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <h3>Modifier l'étudiant : <?= $etu['Nom'] ?></h3>
                <form method="POST">
                    <input type="text" name="nom" class="form-control mb-3" value="<?= $etu['Nom'] ?>" required>
                    <input type="text" name="prenom" class="form-control mb-3" value="<?= $etu['Prenom'] ?>" required>
                    <select name="sexe" class="form-select mb-3">
                        <option value="M" <?= $etu['Sexe']=='M'?'selected':'' ?>>Masculin</option>
                        <option value="F" <?= $etu['Sexe']=='F'?'selected':'' ?>>Féminin</option>
                    </select>
                    <select name="filiere" class="form-select mb-3">
                        <?php foreach($filieres as $f): ?>
                            <option value="<?= $f['code_filiere'] ?>" <?= $f['code_filiere']==$etu['code_filiere']?'selected':'' ?>>
                                <?= $f['Libele_filiere'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-success w-100">Mettre à jour</button>
                    <a href="etudiant.php" class="btn btn-link w-100">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>