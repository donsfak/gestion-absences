<?php
// edit_periode.php
require 'db.php';
include 'menu.php';

$id = $_GET['id'];

// 1. Récupérer la période actuelle
$stmt = $pdo->prepare("SELECT * FROM PERIODE WHERE id_periode = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

// Convertir au format HTML pour les champs datetime-local
$debut_format = date('Y-m-d\TH:i', strtotime($p['date_debut']));
$fin_format = $p['date_fin'] ? date('Y-m-d\TH:i', strtotime($p['date_fin'])) : '';

$message = '';

// 2. Gérer la mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nouveau_debut = date('Y-m-d H:i:s', strtotime($_POST['date_debut']));
    $nouvelle_fin = date('Y-m-d H:i:s', strtotime($_POST['date_fin']));
    
    if ($nouvelle_fin <= $nouveau_debut) {
        $message = "<div class='alert alert-warning'>Erreur : La fin doit être après le début !</div>";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE PERIODE SET date_debut = ?, date_fin = ? WHERE id_periode = ?");
            $stmt->execute([$nouveau_debut, $nouvelle_fin, $id]);
            header("Location: periode.php?msg=Modifié");
            exit();
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Modifier Période</title>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h3>Modifier la période</h3>
                <?= $message ?>
                <form method="POST">
                    <div class="mb-3">
                        <label>ID Période (fixe)</label>
                        <input type="text" class="form-control" value="<?= $p['id_periode'] ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="text-success fw-bold">Date et Heure de Début</label>
                        <input type="datetime-local" class="form-control border-success" name="date_debut" value="<?= $debut_format ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="text-danger fw-bold">Date et Heure de Fin</label>
                        <input type="datetime-local" class="form-control border-danger" name="date_fin" value="<?= $fin_format ?>" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Enregistrer les modifications</button>
                    <a href="periode.php" class="btn btn-link text-secondary w-100 mt-2 text-center d-block">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>