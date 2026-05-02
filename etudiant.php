<?php
// etudiant.php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';

// Sécurité : Seul l'Admin gère les inscriptions
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: edition.php");
    exit();
}

$message = '';

// 1. TRAITEMENT DE L'AJOUT D'UN ÉTUDIANT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $nom = htmlspecialchars(strtoupper($_POST['nom']));
    $prenom = htmlspecialchars($_POST['prenom']);
    $sexe = $_POST['sexe'];
    $code_filiere = $_POST['code_filiere'];

    try {
        $stmt = $pdo->prepare("INSERT INTO ETUDIANT (Nom, Prenom, Sexe, code_filiere) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $sexe, $code_filiere]);
        $message = "<div class='alert alert-success fw-bold shadow-sm'><i class='bi bi-check-circle me-2'></i>L'étudiant a été inscrit avec succès.</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger shadow-sm'><i class='bi bi-exclamation-triangle me-2'></i>Erreur lors de l'inscription : " . $e->getMessage() . "</div>";
    }
}

// 2. STATISTIQUES DES BADGES (Hommes / Femmes)
$hommes = $pdo->query("SELECT COUNT(*) FROM ETUDIANT WHERE Sexe = 'M'")->fetchColumn();
$femmes = $pdo->query("SELECT COUNT(*) FROM ETUDIANT WHERE Sexe = 'F'")->fetchColumn();

// 3. GESTION DU FILTRE PAR FILIÈRE
$filieres_filtre = $pdo->query("SELECT * FROM FILIERE ORDER BY Libele_filiere")->fetchAll();
$filiere_selectionnee = isset($_GET['filtre_filiere']) ? $_GET['filtre_filiere'] : '';

if (!empty($filiere_selectionnee)) {
    // Si une filière est choisie, on filtre
    $stmt = $pdo->prepare("
        SELECT e.*, f.Libele_filiere 
        FROM ETUDIANT e 
        JOIN FILIERE f ON e.code_filiere = f.code_filiere 
        WHERE e.code_filiere = ? 
        ORDER BY e.Nom ASC
    ");
    $stmt->execute([$filiere_selectionnee]);
    $etudiants = $stmt->fetchAll();
} else {
    // Sinon on affiche tout, trié par filière
    $etudiants = $pdo->query("
        SELECT e.*, f.Libele_filiere 
        FROM ETUDIANT e 
        JOIN FILIERE f ON e.code_filiere = f.code_filiere 
        ORDER BY f.Libele_filiere ASC, e.Nom ASC
    ")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Étudiants - ESATIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container mt-4">
    
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>Inscriptions Étudiants</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="export_etudiants.php" class="btn btn-success fw-bold shadow-sm me-3" title="Télécharger la liste au format Excel">
                <i class="bi bi-file-earmark-excel-fill me-2"></i> Exporter
            </a>
            <span class="badge bg-white text-dark shadow-sm p-2 me-2 border"><i class="bi bi-gender-male text-info fs-6 me-1"></i> <?= $hommes ?> Hommes</span>
            <span class="badge bg-white text-dark shadow-sm p-2 border"><i class="bi bi-gender-female text-danger fs-6 me-1"></i> <?= $femmes ?> Femmes</span>
        </div>
    </div>

    <?= $message ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-person-plus me-2"></i>Nouvel Étudiant
                </div>
                <div class="card-body">
                    <form method="POST" action="etudiant.php">
                        <input type="hidden" name="action" value="add">



                        <div class="mb-3">
    <label class="form-label small fw-bold">Matricule</label>
    <input type="number" name="id_etudiant" class="form-control" placeholder="Ex: 2505" required>
</div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nom</label>
                            <input type="text" name="nom" class="form-control text-uppercase" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Prénoms</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Sexe</label>
                            <select name="sexe" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <option value="M">Masculin (M)</option>
                                <option value="F">Féminin (F)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Filière / Classe</label>
                            <select name="code_filiere" class="form-select" required>
                                <option value="">-- Affecter à une classe --</option>
                                <?php foreach($filieres_filtre as $f): ?>
                                    <option value="<?= $f['code_filiere'] ?>"><?= htmlspecialchars($f['Libele_filiere']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">Inscrire l'étudiant</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            
            <div class="bg-white p-3 rounded shadow-sm border mb-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-funnel-fill text-primary fs-4 me-3"></i>
                    <h6 class="mb-0 fw-bold me-3 text-muted">Filtrer par classe :</h6>
                    
                    <form method="GET" action="etudiant.php" class="m-0">
                        <select name="filtre_filiere" class="form-select border-primary text-primary fw-bold shadow-sm" style="min-width: 250px;" onchange="this.form.submit()">
                            <option value="">-- Toutes les filières confondues --</option>
                            <?php foreach($filieres_filtre as $f): ?>
                                <option value="<?= $f['code_filiere'] ?>" <?= ($filiere_selectionnee == $f['code_filiere']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['Libele_filiere']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                
                <span class="text-muted small fw-bold bg-light p-2 rounded border">
                    <i class="bi bi-people-fill text-secondary me-1"></i> <?= count($etudiants) ?> affiché(s)
                </span>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Matricule</th>
                                <th>Étudiant</th>
                                <th>Sexe</th>
                                <th>Filière</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($etudiants as $e): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-secondary">#<?= $e['id_etudiant'] ?></td>
                                <td><strong><?= htmlspecialchars($e['Nom']) ?></strong> <?= htmlspecialchars($e['Prenom']) ?></td>
                                <td>
                                    <?php if($e['Sexe'] == 'M'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info">M</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">F</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($e['Libele_filiere']) ?></span></td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <a href="recu_etudiant.php?id=<?= $e['id_etudiant'] ?>" class="btn btn-sm btn-outline-secondary" title="Imprimer le reçu">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="notifier.php?id=<?= $e['id_etudiant'] ?>" class="btn btn-sm btn-outline-primary" title="Alerte SMS Parent">
                                            <i class="bi bi-chat-text"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($etudiants)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Aucun étudiant trouvé pour cette sélection.</td></tr>
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