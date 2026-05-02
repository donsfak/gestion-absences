<?php
// matiere.php
require 'db.php';
$message = '';

// 1. LOGIQUE DE SUPPRESSION (Delete)
if (isset($_GET['delete'])) {
    $id_a_supprimer = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM MATIERE WHERE code_matiere = ?");
        $stmt->execute([$id_a_supprimer]);
        $message = "<div class='alert alert-success'>Matière supprimée avec succès !</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Impossible de supprimer : cette matière est déjà utilisée dans un cours.</div>";
    }
}

// 2. LOGIQUE D'AJOUT (Create)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['code_matiere'])) {
    $code = htmlspecialchars($_POST['code_matiere']);
    $nom = htmlspecialchars($_POST['nom_matiere']);
    try {
        $stmt = $pdo->prepare("INSERT INTO MATIERE (code_matiere, Nom_matiere) VALUES (?, ?)");
        $stmt->execute([$code, $nom]);
        $message = "<div class='alert alert-success'>Matière ajoutée !</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
    }
}

// 3. LOGIQUE D'AFFICHAGE (Read) - Très important pour remplir le tableau !
$stmt = $pdo->query("SELECT * FROM MATIERE");
$matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramétrage - Matières</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container">
    <h2 class="mb-4">Paramétrage : Matières</h2>
    <?= $message ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Nouvelle matière</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Code Matière (Ex: 101)</label>
                            <input type="number" class="form-control" name="code_matiere" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Libellé de la Matière</label>
                            <input type="text" class="form-control" name="nom_matiere" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Nom</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matieres as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['code_matiere']) ?></td>
<td><?= htmlspecialchars($m['Nom_matiere']) ?></td>
                                <td class="text-end pe-3">
    <a href="edit_matiere.php?id=<?= $m['code_matiere'] ?>" class="btn btn-sm btn-warning text-dark fw-bold me-1 shadow-sm">
        <i class="bi bi-pencil-square me-1"></i> Modifier
    </a>
    
    <a href="matiere.php?delete=<?= $m['code_matiere'] ?>" class="btn btn-sm btn-danger text-white fw-bold shadow-sm" onclick="return confirm('Confirmer la suppression définitive ?')">
        <i class="bi bi-trash3 me-1"></i> Supprimer
    </a>
</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($matieres)): ?>
                            <tr><td colspan="3" class="text-center text-muted">Aucune matière trouvée.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>