<?php
require 'db.php';
$message = '';

// DELETE
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM FILIERE WHERE code_filiere = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "<div class='alert alert-success'>Filière supprimée.</div>";
    } catch (Exception $e) { $message = "<div class='alert alert-danger'>Erreur : Cette filière contient sûrement des étudiants.</div>"; }
}

// CREATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['code_filiere'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO FILIERE (code_filiere, Libele_filiere, Nbre_etudiant) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['code_filiere'], $_POST['libelle'], $_POST['nbre']]);
        $message = "<div class='alert alert-success'>Filière ajoutée !</div>";
    } catch (Exception $e) { $message = "<div class='alert alert-danger'>Erreur : ".$e->getMessage()."</div>"; }
}

$filieres = $pdo->query("SELECT * FROM FILIERE")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Gestion Filières</title>
</head>
<body class="bg-light">
    <?php include 'menu.php'; ?>
    <div class="container">
        <h3>Paramétrage : Filières</h3>
        <?= $message ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card p-3 shadow-sm">
                    <h5>Nouvelle Filière</h5>
                    <form method="POST">
                        <input type="number" name="code_filiere" class="form-control mb-2" placeholder="Code" required>
                        <input type="text" name="libelle" class="form-control mb-2" placeholder="Libellé" required>
                        <input type="number" name="nbre" class="form-control mb-2" placeholder="Nb étudiants" required>
                        <button class="btn btn-primary w-100">Enregistrer</button>
                    </form>
                </div>
            </div>
            <div class="col-md-8">
                <table class="table table-white shadow-sm">
                    <thead><tr><th>Code</th><th>Libellé</th><th>Effectif</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($filieres as $f): ?>
                        <tr>
                            <td><?= $f['code_filiere'] ?></td>
                            <td><?= $f['Libele_filiere'] ?></td>
                            <td><?= $f['Nbre_etudiant'] ?></td>
                            
                            <td class="text-end pe-3">
    <a href="edit_filiere.php?id=<?= $f['code_filiere'] ?>" class="btn btn-sm btn-warning text-dark fw-bold me-1 shadow-sm">
        <i class="bi bi-pencil-square me-1"></i> Modifier
    </a>
    
    <a href="filiere.php?delete=<?= $f['code_filiere'] ?>" class="btn btn-sm btn-danger text-white fw-bold shadow-sm" onclick="return confirm('Confirmer la suppression définitive ?')">
        <i class="bi bi-trash3 me-1"></i> Supprimer
    </a>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>