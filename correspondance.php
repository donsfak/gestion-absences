<?php
// correspondance.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: index.php");
    exit();
}

$message = '';

// 1. Gérer l'insertion du lien
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code_filiere = $_POST['code_filiere'];
    $code_matiere = $_POST['code_matiere'];
    $volume = $_POST['volume'];

    try {
        $stmt = $pdo->prepare("INSERT INTO Correspondre (code_filiere, code_matiere, Volume_horaire) VALUES (?, ?, ?)");
        $stmt->execute([$code_filiere, $code_matiere, $volume]);
        $message = "<div class='alert alert-success fw-bold'>Lien établi avec succès !</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur : Ce lien existe peut-être déjà ou les données sont invalides.</div>";
    }
}

// 2. Récupérer les données pour les menus déroulants
$filieres = $pdo->query("SELECT * FROM FILIERE ORDER BY Libele_filiere")->fetchAll();
$matieres = $pdo->query("SELECT * FROM MATIERE ORDER BY Nom_matiere")->fetchAll();

// 3. Récupérer les liens existants pour affichage
$liens = $pdo->query("
    SELECT f.Libele_filiere, m.Nom_matiere, c.Volume_horaire 
    FROM Correspondre c
    JOIN FILIERE f ON c.code_filiere = f.code_filiere
    JOIN MATIERE m ON c.code_matiere = m.code_matiere
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Maquette Pédagogique - ESATIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container mt-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-link-45deg text-primary me-2"></i>Maquette : Matières par Filière</h2>
    
    <?= $message ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">Associer une Matière</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Filière</label>
                            <select name="code_filiere" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach($filieres as $f): ?>
                                    <option value="<?= $f['code_filiere'] ?>"><?= $f['Libele_filiere'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Matière</label>
                            <select name="code_matiere" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach($matieres as $m): ?>
                                    <option value="<?= $m['code_matiere'] ?>"><?= $m['Nom_matiere'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Volume Horaire (H)</label>
                            <input type="number" name="volume" class="form-control" placeholder="ex: 30" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Enregistrer le lien</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Filière</th>
                                <th>Matière</th>
                                <th>Volume H.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($liens as $l): ?>
                            <tr>
                                <td><span class="badge bg-info text-dark"><?= $l['Libele_filiere'] ?></span></td>
                                <td class="fw-bold"><?= $l['Nom_matiere'] ?></td>
                                <td><?= $l['Volume_horaire'] ?> H</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>