<?php
// appel.php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';

// --- GESTION DU JETON CSRF ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- TRAITEMENT AJAX DE L'APPEL ET SIMULATION SMS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_appel') {
    header('Content-Type: application/json');

    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => "Erreur de sécurité CSRF."]);
        exit();
    }

    $id_enseignement = $_POST['id_enseignement'];
    $absents_notifies = [];

    if (isset($_POST['statut']) && is_array($_POST['statut'])) {
        try {
            // On utilise une transaction pour sécuriser l'insertion de masse
            $pdo->beginTransaction(); 
            
            $stmt = $pdo->prepare("INSERT INTO Assister (id_etudiant, id_enseignement, Statut) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Statut=?");

            foreach ($_POST['statut'] as $id_etudiant => $statut) {
                $stmt->execute([$id_etudiant, $id_enseignement, $statut, $statut]);
                
                // SIMULATION ENVOI SMS (Option B)
                if ($statut == 'Absent') {
                    // On récupère le nom pour le rapport de notification
                    $stmt_etu = $pdo->prepare("SELECT Nom, Prenom FROM ETUDIANT WHERE id_etudiant = ?");
                    $stmt_etu->execute([$id_etudiant]);
                    $etudiant = $stmt_etu->fetch();
                    $nom_complet = $etudiant['Nom'] . ' ' . $etudiant['Prenom'];
                    
                    // --- ZONE PRÊTE POUR LA PRODUCTION ---
                    // C'est ici que tu mettras le code de l'API (ex: Twilio) plus tard.
                    // Exemple : sendSMS($numero_parent, "L'étudiant $nom_complet est absent au cours aujourd'hui.");
                    // ------------------------------------
                    
                    $absents_notifies[] = $nom_complet;
                }
            }
            $pdo->commit(); // On valide toutes les insertions d'un coup
            
            // Préparation de la réponse AJAX
            $alert_title = "Appel enregistré !";
            $alert_text = "L'appel a été validé avec succès.";
            $icon = "success";

            if (count($absents_notifies) > 0) {
                $alert_text = "Appel enregistré.<br><br><strong>🔔 Système d'alerte :</strong> " . count($absents_notifies) . " SMS automatique(s) envoyé(s) aux parents des étudiants :<br><br><small class='text-danger'>" . implode("<br>", $absents_notifies) . "</small>";
                $icon = "warning"; // On met en warning pour bien montrer l'action SMS
            }

            echo json_encode(['success' => true, 'title' => $alert_title, 'text' => $alert_text, 'icon' => $icon]);
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'title' => 'Erreur', 'text' => "Erreur base de données : " . $e->getMessage(), 'icon' => 'error']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'title' => 'Attention', 'text' => "Aucune donnée reçue.", 'icon' => 'info']);
        exit();
    }
}

// --- RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE ---
// Liste des cours programmés
$cours = $pdo->query("
    SELECT e.id_enseignement, m.Nom_matiere, f.Libele_filiere, e.Date_enseignement, e.code_filiere
    FROM ENSEIGNEMENT e
    JOIN MATIERE m ON e.code_matiere = m.code_matiere
    JOIN FILIERE f ON e.code_filiere = f.code_filiere
    ORDER BY e.Date_enseignement DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Liste des étudiants si un cours est sélectionné
$etudiants = [];
$cours_selectionne = null;
if (isset($_GET['id_cours'])) {
    $cours_selectionne = $_GET['id_cours'];
    $stmt = $pdo->prepare("SELECT code_filiere FROM ENSEIGNEMENT WHERE id_enseignement = ?");
    $stmt->execute([$cours_selectionne]);
    $filiere_cours = $stmt->fetchColumn();

    if ($filiere_cours) {
        $stmt_etu = $pdo->prepare("SELECT * FROM ETUDIANT WHERE code_filiere = ? ORDER BY Nom ASC");
        $stmt_etu->execute([$filiere_cours]);
        $etudiants = $stmt_etu->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Faire l'appel - ESATIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- SweetAlert2 pour les notifications stylées -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold"><i class="bi bi-clipboard-check-fill text-primary me-2"></i>Saisie des Présences</h2>

    <div class="row">
        <!-- COLONNE 1 : CHOIX DU COURS -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-1-circle me-1"></i> Sélectionner un cours
                </div>
                <div class="card-body">
                    <form method="GET" action="appel.php">
                        <div class="mb-3">
                            <select class="form-select border-primary shadow-sm" name="id_cours" required onchange="this.form.submit()">
                                <option value="">-- Choisir un cours --</option>
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

        <!-- COLONNE 2 : LISTE D'APPEL -->
        <div class="col-md-8">
            <?php if ($cours_selectionne && !empty($etudiants)): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-2-circle me-1"></i> Liste d'appel</span>
                    <span class="badge bg-dark"><?= count($etudiants) ?> Étudiants</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    <!-- Formulaire avec un ID pour l'AJAX -->
                    <form id="form-appel" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="save_appel">
                        <input type="hidden" name="id_enseignement" value="<?= htmlspecialchars($cours_selectionne) ?>">
                        
                        <table class="table table-hover align-middle mb-0 border-bottom">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Matricule</th>
                                    <th>Nom & Prénom</th>
                                    <th class="text-center text-success"><i class="bi bi-check-circle-fill me-1"></i>Présent</th>
                                    <th class="text-center text-danger"><i class="bi bi-x-circle-fill me-1"></i>Absent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($etudiants as $e): ?>
                                <tr>
                                    <td class="ps-3 text-secondary fw-bold">#<?= htmlspecialchars($e['id_etudiant']) ?></td>
                                    <td><strong><?= htmlspecialchars($e['Nom']) ?></strong> <?= htmlspecialchars($e['Prenom']) ?></td>
                                    
                                    <!-- Boutons radio personnalisés -->
                                    <td class="text-center bg-success bg-opacity-10">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input border-success" style="transform: scale(1.5);" type="radio" name="statut[<?= $e['id_etudiant'] ?>]" value="Présent" checked>
                                        </div>
                                    </td>
                                    <td class="text-center bg-danger bg-opacity-10">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input border-danger" style="transform: scale(1.5);" type="radio" name="statut[<?= $e['id_etudiant'] ?>]" value="Absent">
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="p-3 bg-light">
                            <button type="submit" id="btn-submit-appel" class="btn btn-warning w-100 fw-bold shadow-sm p-3 text-dark">
                                <span id="spinner-appel" class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                                <i class="bi bi-send-fill me-2" id="icon-appel"></i> <span id="text-appel">Enregistrer l'appel et Notifier les parents</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php elseif ($cours_selectionne && empty($etudiants)): ?>
                <div class="alert alert-info border-info shadow-sm"><i class="bi bi-info-circle-fill me-2"></i>Aucun étudiant inscrit dans la filière de ce cours.</div>
            <?php else: ?>
                <div class="alert alert-secondary border-secondary shadow-sm text-center p-5">
                    <i class="bi bi-arrow-left-circle display-4 text-muted mb-3 d-block"></i>
                    Veuillez sélectionner un cours dans le menu de gauche pour afficher la liste d'appel.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- LE MOTEUR AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formAppel = document.getElementById('form-appel');
    
    if(formAppel) {
        formAppel.addEventListener('submit', function(e) {
            e.preventDefault(); // Stoppe le rechargement de la page

            const btn = document.getElementById('btn-submit-appel');
            const spinner = document.getElementById('spinner-appel');
            const icon = document.getElementById('icon-appel');
            const text = document.getElementById('text-appel');

            // État de chargement
            btn.disabled = true;
            spinner.classList.remove('d-none');
            icon.classList.add('d-none');
            text.textContent = " Enregistrement et envoi des SMS en cours...";

            const formData = new FormData(formAppel);

            // Envoi de la requête silencieuse
            fetch('appel.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Affichage de la notification d'alerte simulée
                    Swal.fire({
                        icon: data.icon,
                        title: data.title,
                        html: data.text,
                        confirmButtonText: 'Fermer',
                        confirmButtonColor: '#0d6efd'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error("Erreur Fetch:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur technique',
                    text: 'Impossible de joindre le serveur.'
                });
            })
            .finally(() => {
                // Restauration du bouton
                btn.disabled = false;
                spinner.classList.add('d-none');
                icon.classList.remove('d-none');
                text.textContent = "Enregistrer l'appel et Notifier les parents";
            });
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>