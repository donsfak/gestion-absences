<?php
// appel.php
require 'db.php';
$message = '';

// Si le formulaire d'appel est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['statut'])) {
    $id_enseignement = $_POST['id_enseignement'];
    $absents_notifies = [];

    foreach ($_POST['statut'] as $id_etudiant => $statut) {
        try {
            // Insertion dans la table Assister
            $stmt = $pdo->prepare("INSERT INTO Assister (id_etudiant, id_enseignement, Statut) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Statut=?");
            $stmt->execute([$id_etudiant, $id_enseignement, $statut, $statut]);
            
            // Simulation envoi message
            if ($statut == 'Absent') {
                $absents_notifies[] = $id_etudiant;
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
    
    if (empty($message)) {
        $alert_msg = "L'appel a été enregistré avec succès.";
        if (count($absents_notifies) > 0) {
            $alert_msg .= "<br><strong>🔔 Système d'alerte :</strong> Un email/SMS automatique a été envoyé aux parents des " . count($absents_notifies) . " étudiant(s) absent(s).";
        }
        $message = "<div class='alert alert-success'>$alert_msg</div>";
    }
}

// Récupérer la liste des cours programmés pour les afficher
$cours = $pdo->query("
    SELECT e.id_enseignement, m.Nom_matiere, f.Libele_filiere, e.Date_enseignement, e.code_filiere
    FROM ENSEIGNEMENT e
    JOIN MATIERE m ON e.code_matiere = m.code_matiere
    JOIN FILIERE f ON e.code_filiere = f.code_filiere
    ORDER BY e.Date_enseignement DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Si un cours est sélectionné, on récupère les étudiants de cette filière
$etudiants = [];
$cours_selectionne = null;
if (isset($_GET['id_cours'])) {
    $cours_selectionne = $_GET['id_cours'];
    // On trouve le code_filiere de ce cours
    $stmt = $pdo->prepare("SELECT code_filiere FROM ENSEIGNEMENT WHERE id_enseignement = ?");
    $stmt->execute([$cours_selectionne]);
    $filiere_cours = $stmt->fetchColumn();

    if ($filiere_cours) {
        $stmt_etu = $pdo->prepare("SELECT * FROM ETUDIANT WHERE code_filiere = ?");
        $stmt_etu->execute([$filiere_cours]);
        $etudiants = $stmt_etu->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Faire l'appel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container">
    <h2 class="mb-4">Saisie des Présences / Absences</h2>
    <?= $message ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">1. Sélectionner un cours</h5>
                    <form method="GET" action="">
                        <div class="mb-3">
                            <select class="form-select" name="id_cours" required onchange="this.form.submit()">
                                <option value="">Choisir un cours...</option>
                                <?php foreach ($cours as $c): ?>
                                    <option value="<?= $c['id_enseignement'] ?>" <?= ($cours_selectionne == $c['id_enseignement']) ? 'selected' : '' ?>>
                                        <?= date('d/m/Y', strtotime($c['Date_enseignement'])) ?> - <?= htmlspecialchars($c['Nom_matiere']) ?> (<?= htmlspecialchars($c['Libele_filiere']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if ($cours_selectionne && !empty($etudiants)): ?>
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark fw-bold">
                    2. Liste d'appel
                </div>
                <div class="card-body">
                    <form method="POST" action="appel.php?id_cours=<?= $cours_selectionne ?>">
                        <input type="hidden" name="id_enseignement" value="<?= htmlspecialchars($cours_selectionne) ?>">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom & Prénom</th>
                                    <th class="text-center">Présent</th>
                                    <th class="text-center">Absent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($etudiants as $e): ?>
                                <tr>
                                    <td><?= htmlspecialchars($e['id_etudiant']) ?></td>
                                    <td><strong><?= htmlspecialchars($e['Nom']) ?></strong> <?= htmlspecialchars($e['Prenom']) ?></td>
                                    <td class="text-center">
                                        <input class="form-check-input bg-success" type="radio" name="statut[<?= $e['id_etudiant'] ?>]" value="Présent" checked>
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input bg-danger" type="radio" name="statut[<?= $e['id_etudiant'] ?>]" value="Absent">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-warning w-100 fw-bold">Enregistrer l'appel et Notifier</button>
                    </form>
                </div>
                <a href="notifier.php?id=<?= $e['id_etudiant'] ?>" class="btn btn-sm btn-outline-primary fw-bold me-1 shadow-sm" title="Envoyer un SMS aux parents">
    <i class="bi bi-chat-text-fill"></i> Alerte
</a>
            </div>
            <?php elseif ($cours_selectionne && empty($etudiants)): ?>
                <div class="alert alert-info">Aucun étudiant inscrit dans cette filière.</div>
            <?php else: ?>
                <div class="alert alert-secondary">Veuillez sélectionner un cours à gauche pour afficher la liste d'appel.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body></html>