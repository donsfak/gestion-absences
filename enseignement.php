<?php
// enseignement.php
require 'db.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_enseignement'])) {
    $id = htmlspecialchars($_POST['id_enseignement']);
    $date_ens = date('Y-m-d H:i:s', strtotime($_POST['date_enseignement']));
    $horaire = date('Y-m-d H:i:s', strtotime($_POST['horaire'])); // Format DATETIME selon ton SQL
    $id_enseignant = htmlspecialchars($_POST['id_enseignant']);
    $code_filiere = htmlspecialchars($_POST['code_filiere']);
    $id_periode = htmlspecialchars($_POST['id_periode']);
    $code_matiere = htmlspecialchars($_POST['code_matiere']);

    try {
        $stmt = $pdo->prepare("INSERT INTO ENSEIGNEMENT (id_enseignement, Date_enseignement, Horaire, id_enseignant, code_filiere, id_periode, code_matiere) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $date_ens, $horaire, $id_enseignant, $code_filiere, $id_periode, $code_matiere]);
        $message = "<div class='alert alert-success'>Cours programmé avec succès !</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
    }
}

// Récupération des données pour les listes déroulantes
$profs = $pdo->query("SELECT * FROM ENSEIGNANT")->fetchAll(PDO::FETCH_ASSOC);
$filieres = $pdo->query("SELECT * FROM FILIERE")->fetchAll(PDO::FETCH_ASSOC);
$periodes = $pdo->query("SELECT * FROM PERIODE")->fetchAll(PDO::FETCH_ASSOC);
$matieres = $pdo->query("SELECT * FROM MATIERE")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Programmer un cours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container">
    <h2 class="mb-4">Programmer un nouveau cours</h2>
    <?= $message ?>
    <div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-4 mb-3"><label>ID Cours</label><input type="number" class="form-control" name="id_enseignement" required></div>
                <div class="col-md-4 mb-3"><label>Date du cours</label><input type="datetime-local" class="form-control" name="date_enseignement" required></div>
                <div class="col-md-4 mb-3"><label>Heure de fin (Horaire)</label><input type="datetime-local" class="form-control" name="horaire" required></div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3"><label>Matière</label><select class="form-select" name="code_matiere" required><option value="">Choisir...</option><?php foreach($matieres as $m) echo "<option value='{$m['code_matiere']}'>{$m['Nom_matiere']}</option>"; ?></select></div>
                <div class="col-md-3 mb-3"><label>Filière</label><select class="form-select" name="code_filiere" required><option value="">Choisir...</option><?php foreach($filieres as $f) echo "<option value='{$f['code_filiere']}'>{$f['Libele_filiere']}</option>"; ?></select></div>
                <div class="col-md-3 mb-3"><label>Enseignant</label><select class="form-select" name="id_enseignant" required><option value="">Choisir...</option><?php foreach($profs as $p) echo "<option value='{$p['id_enseignant']}'>{$p['Nom']} {$p['Prenom']}</option>"; ?></select></div>
                <div class="col-md-3 mb-3"><label>Période</label><select class="form-select" name="id_periode" required><option value="">Choisir...</option><?php foreach($periodes as $p) echo "<option value='{$p['id_periode']}'>ID: {$p['id_periode']}</option>"; ?></select></div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Programmer le cours</button>
        </form>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>