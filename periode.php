<?php
// periode.php
require 'db.php';
$message = '';

// 1. SUPPRESSION
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM PERIODE WHERE id_periode = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "<div class='alert alert-success'>Période supprimée avec succès.</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur : Cette période est liée à un cours.</div>";
    }
}

// 2. AJOUT (Maintenant avec Début et Fin)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_periode'])) {
    $id = htmlspecialchars($_POST['id_periode']);
    $debut = date('Y-m-d H:i:s', strtotime($_POST['date_debut']));
    $fin = date('Y-m-d H:i:s', strtotime($_POST['date_fin']));
    
    // Petite vérification logique : la fin doit être après le début !
    if ($fin <= $debut) {
        $message = "<div class='alert alert-warning'>Erreur : La date de fin doit être ultérieure à la date de début.</div>";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO PERIODE (id_periode, date_debut, date_fin) VALUES (?, ?, ?)");
            $stmt->execute([$id, $debut, $fin]);
            $message = "<div class='alert alert-success'>Période ajoutée avec succès !</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}

// 3. AFFICHAGE
$periodes = $pdo->query("SELECT * FROM PERIODE ORDER BY date_debut DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramétrage - Périodes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container">
    <h2 class="mb-4">Paramétrage : Périodes</h2>
    <?= $message ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Nouvelle période</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">ID Période (Ex: 1, 2...)</label>
                            <input type="number" class="form-control" name="id_periode" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-success fw-bold">Date de Début</label>
                            <input type="datetime-local" class="form-control border-success" name="date_debut" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-danger fw-bold">Date de Fin</label>
                            <input type="datetime-local" class="form-control border-danger" name="date_fin" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($periodes as $p): ?>
                            <tr>
                                <td><span class="badge bg-secondary">Période <?= htmlspecialchars($p['id_periode']) ?></span></td>
                                <td class="text-success"><?= date('d/m/Y H:i', strtotime($p['date_debut'])) ?></td>
                                <td class="text-danger">
                                    <?= $p['date_fin'] ? date('d/m/Y H:i', strtotime($p['date_fin'])) : 'Non définie' ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit_periode.php?id=<?= $p['id_periode'] ?>" class="btn btn-sm btn-warning text-dark fw-bold me-1 shadow-sm">
        <i class="bi bi-pencil-square me-1"></i> Modifier
    </a>
    
    <a href="periode.php?delete=<?= $p['id_periode'] ?>" class="btn btn-sm btn-danger text-white fw-bold shadow-sm" onclick="return confirm('Confirmer la suppression définitive ?')">
        <i class="bi bi-trash3 me-1"></i> Supprimer
    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($periodes)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Aucune période enregistrée.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</div>
</body></html>