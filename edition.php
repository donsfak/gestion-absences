<?php
// edition.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 1. Initialisation des variables par défaut (Anti-Crash)
$total_etudiants = 0;
$total_filieres = 0;
$absences_non_justifiees = 0;
$rapport_matieres = [];
$rapport_abs_filiere = [];
$rapport_justifiees = [];
$rapport_exclusion = [];
$erreur_sql = "";

try {
    // --- 2. DONNÉES POUR LES CARTES (KPIs) ---
    $total_etudiants = $pdo->query("SELECT COUNT(*) FROM ETUDIANT")->fetchColumn();
    $total_filieres = $pdo->query("SELECT COUNT(*) FROM FILIERE")->fetchColumn();
    
    // CORRECTION ICI : On utilise IFNULL au cas où le champ Est_Justifie soit NULL au lieu de 0
    $absences_non_justifiees = $pdo->query("SELECT COUNT(*) FROM Assister WHERE Statut = 'Absent' AND IFNULL(Est_Justifie, 0) = 0")->fetchColumn();

    // --- 3. RAPPORT : MATIÈRES PAR FILIÈRE ---
    $rapport_matieres = $pdo->query("
        SELECT f.Libele_filiere, m.Nom_matiere 
        FROM FILIERE f 
        JOIN Correspondre c ON f.code_filiere = c.code_filiere 
        JOIN MATIERE m ON c.code_matiere = m.code_matiere
        ORDER BY f.Libele_filiere
    ")->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. RAPPORT : ABSENCES PAR FILIÈRE ---
    $rapport_abs_filiere = $pdo->query("
        SELECT f.Libele_filiere, COUNT(a.id_etudiant) as nb_absences
        FROM FILIERE f
        JOIN ETUDIANT e ON f.code_filiere = e.code_filiere
        JOIN Assister a ON e.id_etudiant = a.id_etudiant
        WHERE a.Statut = 'Absent'
        GROUP BY f.Libele_filiere
    ")->fetchAll(PDO::FETCH_ASSOC);

    // --- 5. RAPPORT : LISTE DES ABSENCES JUSTIFIÉES (Anti-Doublons) ---
    $rapport_justifiees = $pdo->query("
        SELECT e.Nom, e.Prenom, m.Nom_matiere, c.Date_enseignement, MAX(j.motif) as Justification
        FROM Assister a
        JOIN ETUDIANT e ON a.id_etudiant = e.id_etudiant
        JOIN ENSEIGNEMENT c ON a.id_enseignement = c.id_enseignement
        JOIN MATIERE m ON c.code_matiere = m.code_matiere
        LEFT JOIN JUSTIFICATIF j ON e.id_etudiant = j.id_etudiant AND c.Date_enseignement BETWEEN j.date_debut AND j.date_fin
        WHERE a.Est_Justifie = 1
        GROUP BY a.id_etudiant, c.id_enseignement, e.Nom, e.Prenom, m.Nom_matiere, c.Date_enseignement
        ORDER BY c.Date_enseignement DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // --- 6. RAPPORT : ALERTE EXCLUSION (Point 3 : Le volume horaire) ---
    $rapport_exclusion = $pdo->query("
        SELECT e.Nom, e.Prenom, m.Nom_matiere, f.Libele_filiere, c.Volume_horaire, COUNT(a.id_enseignement) as nb_absences
        FROM Assister a
        JOIN ETUDIANT e ON a.id_etudiant = e.id_etudiant
        JOIN ENSEIGNEMENT ens ON a.id_enseignement = ens.id_enseignement
        JOIN MATIERE m ON ens.code_matiere = m.code_matiere
        JOIN Correspondre c ON e.code_filiere = c.code_filiere AND m.code_matiere = c.code_matiere
        JOIN FILIERE f ON e.code_filiere = f.code_filiere
        WHERE a.Statut = 'Absent' AND IFNULL(a.Est_Justifie, 0) = 0
        GROUP BY e.id_etudiant, m.code_matiere, e.Nom, e.Prenom, f.Libele_filiere, c.Volume_horaire
        HAVING nb_absences >= (c.Volume_horaire * 0.20)
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erreur_sql = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Module Édition - ESATIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .card-stat { border: none; border-radius: 12px; }
        .nav-tabs .nav-link { color: #6c757d; font-weight: bold; border: none; }
        .nav-tabs .nav-link.active { color: #0d6efd; border-bottom: 3px solid #0d6efd; background: none; }
        @media print { .no-print { display: none; } .container { width: 100%; } }
    </style>
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="fw-bold"><i class="bi bi-file-earmark-bar-graph text-primary me-2"></i>Module d'Édition</h2>
        <button class="btn btn-primary shadow-sm fw-bold" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimer le rapport</button>
    </div>

    <?php if(!empty($erreur_sql)): ?>
        <div class="alert alert-danger shadow-sm no-print">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Erreur Base de données :</strong> <?= $erreur_sql ?>
        </div>
    <?php endif; ?>

    <div class="row mb-5 no-print">
        <div class="col-md-4">
            <div class="card card-stat shadow-sm bg-primary text-white p-3">
                <small class="text-white-50 fw-bold">TOTAL ÉTUDIANTS</small>
                <h2 class="fw-bold mb-0"><?= $total_etudiants ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm bg-success text-white p-3">
                <small class="text-white-50 fw-bold">FILIÈRES ACTIVES</small>
                <h2 class="fw-bold mb-0"><?= $total_filieres ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm bg-danger text-white p-3">
                <small class="text-white-50 fw-bold">ABSENCES À JUSTIFIER</small>
                <h2 class="fw-bold mb-0" id="compteur-absences"><?= $absences_non_justifiees ?></h2>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 pt-3 no-print">
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-auto" style="white-space: nowrap; padding-bottom: 5px;">                
                <li class="nav-item">
                    <button class="nav-link active" id="filiere-tab" data-bs-toggle="tab" data-bs-target="#filiere" type="button" role="tab">Matières / Filière</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="abs-filiere-tab" data-bs-toggle="tab" data-bs-target="#abs-filiere" type="button" role="tab">Absences / Filière</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="justifiees-tab" data-bs-toggle="tab" data-bs-target="#justifiees" type="button" role="tab">Absences Justifiées</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link text-danger fw-bold" id="exclusion-tab" data-bs-toggle="tab" data-bs-target="#exclusion" type="button" role="tab"><i class="bi bi-exclamation-triangle-fill me-1"></i> Risque d'Exclusion</button>
                </li>
            </ul>
        </div>
        
        <div class="card-body p-4">
            <div class="tab-content" id="myTabContent">
                
                <!-- ONGLET 1 : MATIÈRES / FILIÈRE -->
                <div class="tab-pane fade show active" id="filiere" role="tabpanel">
                    <h5 class="fw-bold mb-3"><i class="bi bi-diagram-3 text-info me-2"></i>Répartition des Matières par Filière</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light"><tr><th>Filière</th><th>Matière Enseignée</th></tr></thead>
                            <tbody>
                                <?php foreach($rapport_matieres as $rm): ?>
                                <tr>
                                    <td><span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1"><?= htmlspecialchars($rm['Libele_filiere']) ?></span></td>
                                    <td class="fw-bold"><?= htmlspecialchars($rm['Nom_matiere']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($rapport_matieres)): ?>
                                    <tr><td colspan="2" class="text-center text-muted py-3">Aucune donnée disponible.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ONGLET 2 : ABSENCES / FILIÈRE -->
                <div class="tab-pane fade" id="abs-filiere" role="tabpanel">
                    <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart text-danger me-2"></i>Bilan des Absences par Filière</h5>
                    <div class="row">
                        <?php foreach($rapport_abs_filiere as $raf): ?>
                        <div class="col-md-4 mb-3">
                            <div class="border p-3 rounded bg-light border-danger border-opacity-25 shadow-sm">
                                <h6 class="text-muted small mb-1"><?= htmlspecialchars($raf['Libele_filiere']) ?></h6>
                                <h4 class="fw-bold text-danger mb-0"><i class="bi bi-person-x me-1"></i> <?= $raf['nb_absences'] ?> Absences</h4>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($rapport_abs_filiere)): ?>
                            <div class="col-12 text-center text-muted py-3">Aucune absence enregistrée.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ONGLET 3 : ABSENCES JUSTIFIÉES -->
                <div class="tab-pane fade" id="justifiees" role="tabpanel">
                    <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-success me-2"></i>Registre des Absences Justifiées</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle">
                            <thead class="table-light"><tr><th>Date</th><th>Étudiant</th><th>Matière</th><th>Motif de justification</th></tr></thead>
                            <tbody>
                                <?php foreach($rapport_justifiees as $rj): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= date('d/m/Y', strtotime($rj['Date_enseignement'])) ?></span></td>
                                    <td><strong><?= htmlspecialchars($rj['Nom']) ?></strong> <?= htmlspecialchars($rj['Prenom']) ?></td>
                                    <td><?= htmlspecialchars($rj['Nom_matiere']) ?></td>
                                <td>
                                    <span class="text-success small fw-bold">
                                        <i class="bi bi-check2-circle me-1"></i>
                                        "<?= !empty($rj['Justification']) ? htmlspecialchars($rj['Justification']) : 'Motif non précisé (Archive)' ?>"
                                    </span>
                                </td> 
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($rapport_justifiees)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">Aucune absence justifiée pour le moment.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ONGLET 4 : ALERTE EXCLUSION -->
                <div class="tab-pane fade" id="exclusion" role="tabpanel">
                    <div class="alert alert-danger border-danger shadow-sm">
                        <h5 class="fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-2"></i>Étudiants en risque d'exclusion</h5>
                        <p class="mb-0 small">Ces étudiants ont cumulé un nombre d'absences non-justifiées supérieur à <strong>20% du volume horaire</strong> de la matière. Conformément au règlement, ils doivent être convoqués en commission de discipline.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-danger"><tr><th>Étudiant</th><th>Filière</th><th>Matière</th><th>Volume Total</th><th>Absences Non Justifiées</th><th>Statut</th></tr></thead>
                            <tbody>
                                <?php foreach($rapport_exclusion as $re): ?>
                                <tr class="table-light text-danger fw-bold">
                                    <td><?= htmlspecialchars($re['Nom']) ?> <?= htmlspecialchars($re['Prenom']) ?></td>
                                    <td><?= htmlspecialchars($re['Libele_filiere']) ?></td>
                                    <td><?= htmlspecialchars($re['Nom_matiere']) ?></td>
                                    <td><?= $re['Volume_horaire'] ?> Heures</td>
                                    <td class="text-center fs-5"><?= $re['nb_absences'] ?></td>
                                    <td><span class="badge bg-danger">CONVOCATION REQUISE</span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($rapport_exclusion)): ?>
                                    <tr><td colspan="6" class="text-center text-success py-4 fw-bold"><i class="bi bi-emoji-sunglasses fs-4 d-block mb-2"></i>Aucun étudiant ne dépasse le seuil d'exclusion de 20%.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- JS indispensable pour faire fonctionner les onglets Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>