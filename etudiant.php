<?php
// etudiant.php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';

// Sécurité : Seul l'Admin gère les inscriptions
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: edition.php");
    exit();
}

// --- GESTION DU JETON CSRF ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- 1. MOTEUR AJAX (AJOUT, MODIFICATION, SUPPRESSION) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Vérification de sécurité CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => "Erreur de sécurité CSRF."]);
        exit();
    }

    $action = $_POST['action'];

    try {
        // A. AJOUTER UN ÉTUDIANT
        if ($action == 'add') {
            $id_etudiant = htmlspecialchars($_POST['id_etudiant']);
            $nom = htmlspecialchars(strtoupper($_POST['nom']));
            $prenom = htmlspecialchars($_POST['prenom']);
            $sexe = $_POST['sexe'];
            $code_filiere = $_POST['code_filiere'];

            $stmt = $pdo->prepare("INSERT INTO ETUDIANT (id_etudiant, Nom, Prenom, Sexe, code_filiere) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_etudiant, $nom, $prenom, $sexe, $code_filiere]);
            
            // Récupérer le libellé de la filière
            $stmt_filiere = $pdo->prepare("SELECT Libele_filiere FROM FILIERE WHERE code_filiere = ?");
            $stmt_filiere->execute([$code_filiere]);
            $libelle_filiere = $stmt_filiere->fetchColumn();

            echo json_encode(['success' => true, 'message' => "Étudiant inscrit avec succès.", 'etudiant' => [
                'id_etudiant' => $id_etudiant, 'nom' => $nom, 'prenom' => $prenom, 'sexe' => $sexe, 'code_filiere' => $code_filiere, 'libelle_filiere' => $libelle_filiere
            ]]);
            exit();
        }

        // B. MODIFIER UN ÉTUDIANT
        if ($action == 'edit') {
            $id_etudiant = htmlspecialchars($_POST['id_etudiant']);
            $nom = htmlspecialchars(strtoupper($_POST['nom']));
            $prenom = htmlspecialchars($_POST['prenom']);
            $sexe = $_POST['sexe'];
            $code_filiere = $_POST['code_filiere'];

            $stmt = $pdo->prepare("UPDATE ETUDIANT SET Nom=?, Prenom=?, Sexe=?, code_filiere=? WHERE id_etudiant=?");
            $stmt->execute([$nom, $prenom, $sexe, $code_filiere, $id_etudiant]);
            
            $stmt_filiere = $pdo->prepare("SELECT Libele_filiere FROM FILIERE WHERE code_filiere = ?");
            $stmt_filiere->execute([$code_filiere]);
            $libelle_filiere = $stmt_filiere->fetchColumn();

            echo json_encode(['success' => true, 'message' => "Informations mises à jour.", 'etudiant' => [
                'id_etudiant' => $id_etudiant, 'nom' => $nom, 'prenom' => $prenom, 'sexe' => $sexe, 'code_filiere' => $code_filiere, 'libelle_filiere' => $libelle_filiere
            ]]);
            exit();
        }

        // C. SUPPRIMER UN ÉTUDIANT
        if ($action == 'delete') {
            $id_etudiant = htmlspecialchars($_POST['id_etudiant']);
            $stmt = $pdo->prepare("DELETE FROM ETUDIANT WHERE id_etudiant=?");
            $stmt->execute([$id_etudiant]);
            echo json_encode(['success' => true, 'message' => "L'étudiant a été retiré du système."]);
            exit();
        }

    } catch (PDOException $e) {
        // Gestion de l'erreur si l'étudiant a déjà des absences (Clé étrangère)
        if ($e->getCode() == '23000') {
            echo json_encode(['success' => false, 'message' => "Impossible de supprimer cet étudiant car il possède des absences enregistrées."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Erreur base de données : " . $e->getMessage()]);
        }
        exit();
    }
}

// --- 2. RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE ---
$hommes = $pdo->query("SELECT COUNT(*) FROM ETUDIANT WHERE Sexe = 'M'")->fetchColumn();
$femmes = $pdo->query("SELECT COUNT(*) FROM ETUDIANT WHERE Sexe = 'F'")->fetchColumn();

$filieres_filtre = $pdo->query("SELECT * FROM FILIERE ORDER BY Libele_filiere")->fetchAll();
$filiere_selectionnee = isset($_GET['filtre_filiere']) ? $_GET['filtre_filiere'] : '';

if (!empty($filiere_selectionnee)) {
    $stmt = $pdo->prepare("SELECT e.*, f.Libele_filiere FROM ETUDIANT e JOIN FILIERE f ON e.code_filiere = f.code_filiere WHERE e.code_filiere = ? ORDER BY e.Nom ASC");
    $stmt->execute([$filiere_selectionnee]);
    $etudiants = $stmt->fetchAll();
} else {
    $etudiants = $pdo->query("SELECT e.*, f.Libele_filiere FROM ETUDIANT e JOIN FILIERE f ON e.code_filiere = f.code_filiere ORDER BY f.Libele_filiere ASC, e.Nom ASC")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Étudiants - ESATIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
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
            <span class="badge bg-white text-dark shadow-sm p-2 me-2 border"><i class="bi bi-gender-male text-info fs-6 me-1"></i> <span id="compteur-hommes"><?= $hommes ?></span> Hommes</span>
            <span class="badge bg-white text-dark shadow-sm p-2 border"><i class="bi bi-gender-female text-danger fs-6 me-1"></i> <span id="compteur-femmes"><?= $femmes ?></span> Femmes</span>
        </div>
    </div>

    <div class="row">
        <!-- FORMULAIRE D'AJOUT -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-person-plus me-2"></i>Nouvel Étudiant
                </div>
                <div class="card-body">
                    <form id="form-ajout-etudiant">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                        
                        <button type="submit" id="btn-submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                            <span id="spinner" class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                            <span id="btn-text">Inscrire l'étudiant</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTE DES ÉTUDIANTS -->
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
                    <i class="bi bi-people-fill text-secondary me-1"></i> <span id="compteur-total"><?= count($etudiants) ?></span> affiché(s)
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
                        <tbody id="tableau-etudiants">
                            <?php foreach($etudiants as $e): ?>
                            <tr id="ligne-<?= $e['id_etudiant'] ?>">
                                <td class="ps-3 fw-bold text-secondary">#<?= $e['id_etudiant'] ?></td>
                                <td id="nom-complet-<?= $e['id_etudiant'] ?>"><strong><?= htmlspecialchars($e['Nom']) ?></strong> <?= htmlspecialchars($e['Prenom']) ?></td>
                                <td id="sexe-<?= $e['id_etudiant'] ?>">
                                    <?php if($e['Sexe'] == 'M'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info">M</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">F</span>
                                    <?php endif; ?>
                                </td>
                                <td id="filiere-<?= $e['id_etudiant'] ?>"><span class="badge bg-secondary"><?= htmlspecialchars($e['Libele_filiere']) ?></span></td>
                                <td class="text-end pe-3">
                                    <div class="btn-group shadow-sm">
                                        <!-- Actions -->
                                        <a href="recu_etudiant.php?id=<?= $e['id_etudiant'] ?>" class="btn btn-sm btn-outline-secondary" title="Reçu"><i class="bi bi-printer"></i></a>
                                        <a href="notifier.php?id=<?= $e['id_etudiant'] ?>" class="btn btn-sm btn-outline-primary" title="SMS"><i class="bi bi-chat-text"></i></a>
                                        <!-- Modification AJAX -->
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-edit" 
                                            data-id="<?= $e['id_etudiant'] ?>" data-nom="<?= htmlspecialchars($e['Nom']) ?>" 
                                            data-prenom="<?= htmlspecialchars($e['Prenom']) ?>" data-sexe="<?= $e['Sexe'] ?>" 
                                            data-filiere="<?= $e['code_filiere'] ?>" title="Modifier">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <!-- Suppression AJAX -->
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $e['id_etudiant'] ?>" title="Supprimer">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($etudiants)): ?>
                                <tr id="ligne-vide"><td colspan="5" class="text-center py-4 text-muted">Aucun étudiant trouvé pour cette sélection.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FENÊTRE MODALE GLOBALE POUR LA MODIFICATION -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Modifier l'étudiant</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-edit-etudiant">
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_etudiant" id="edit_id"> <!-- ID invisible mais envoyé -->

            <div class="mb-3">
                <label class="form-label small fw-bold">Nom</label>
                <input type="text" name="nom" id="edit_nom" class="form-control text-uppercase" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Prénoms</label>
                <input type="text" name="prenom" id="edit_prenom" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Sexe</label>
                <select name="sexe" id="edit_sexe" class="form-select" required>
                    <option value="M">Masculin (M)</option>
                    <option value="F">Féminin (F)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Filière</label>
                <select name="code_filiere" id="edit_filiere" class="form-select" required>
                    <?php foreach($filieres_filtre as $f): ?>
                        <option value="<?= $f['code_filiere'] ?>"><?= htmlspecialchars($f['Libele_filiere']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-warning fw-bold">Enregistrer les modifications</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- LE MOTEUR JAVASCRIPT / AJAX -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('tableau-etudiants');
    let modalEditInstance = new bootstrap.Modal(document.getElementById('modalEdit'));

    // --- 1. AJOUTER UN ETUDIANT (AJAX) ---
    const formAdd = document.getElementById('form-ajout-etudiant');
    formAdd.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-submit');
        const spinner = document.getElementById('spinner');
        
        btn.disabled = true; spinner.classList.remove('d-none');
        
        fetch('etudiant.php', { method: 'POST', body: new FormData(formAdd) })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Succès', text: data.message, timer: 1500, showConfirmButton: false });
                
                const e = data.etudiant;
                const badgeSexe = e.sexe === 'M' ? '<span class="badge bg-info bg-opacity-10 text-info border border-info">M</span>' : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">F</span>';
                
                // Construction dynamique de la ligne HTML
                const nouvelleLigne = `
                    <tr id="ligne-${e.id_etudiant}" class="table-success fade-in">
                        <td class="ps-3 fw-bold text-secondary">#${e.id_etudiant}</td>
                        <td id="nom-complet-${e.id_etudiant}"><strong>${e.nom}</strong> ${e.prenom}</td>
                        <td id="sexe-${e.id_etudiant}">${badgeSexe}</td>
                        <td id="filiere-${e.id_etudiant}"><span class="badge bg-secondary">${e.libelle_filiere}</span></td>
                        <td class="text-end pe-3">
                            <div class="btn-group shadow-sm">
                                <a href="recu_etudiant.php?id=${e.id_etudiant}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></a>
                                <a href="notifier.php?id=${e.id_etudiant}" class="btn btn-sm btn-outline-primary"><i class="bi bi-chat-text"></i></a>
                                <button type="button" class="btn btn-sm btn-outline-warning btn-edit" data-id="${e.id_etudiant}" data-nom="${e.nom}" data-prenom="${e.prenom}" data-sexe="${e.sexe}" data-filiere="${e.code_filiere}"><i class="bi bi-pencil-square"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${e.id_etudiant}"><i class="bi bi-trash-fill"></i></button>
                            </div>
                        </td>
                    </tr>`;

                if(document.getElementById('ligne-vide')) document.getElementById('ligne-vide').remove();
                tbody.insertAdjacentHTML('afterbegin', nouvelleLigne);
                formAdd.reset(); // Vider le formulaire
            } else {
                Swal.fire({ icon: 'error', title: 'Erreur', text: data.message });
            }
        })
        .finally(() => { btn.disabled = false; spinner.classList.add('d-none'); });
    });

    // --- DÉLÉGATION D'ÉVÉNEMENTS POUR MODIFIER/SUPPRIMER (Fonctionne même sur les nouvelles lignes) ---
    tbody.addEventListener('click', function(e) {
        
        // --- 2. SUPPRIMER UN ÉTUDIANT (AJAX) ---
        if (e.target.closest('.btn-delete')) {
            const btn = e.target.closest('.btn-delete');
            const id = btn.getAttribute('data-id');

            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette suppression est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer !',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id_etudiant', id);
                    formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

                    fetch('etudiant.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Supprimé !', data.message, 'success');
                            document.getElementById(`ligne-${id}`).remove(); // Disparition visuelle instantanée
                        } else {
                            Swal.fire('Erreur', data.message, 'error');
                        }
                    });
                }
            });
        }

        // --- 3. PRÉPARER LA MODIFICATION (Ouverture Modal) ---
        if (e.target.closest('.btn-edit')) {
            const btn = e.target.closest('.btn-edit');
            // Remplissage du formulaire de la fenêtre modale
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_nom').value = btn.getAttribute('data-nom');
            document.getElementById('edit_prenom').value = btn.getAttribute('data-prenom');
            document.getElementById('edit_sexe').value = btn.getAttribute('data-sexe');
            document.getElementById('edit_filiere').value = btn.getAttribute('data-filiere');
            
            modalEditInstance.show();
        }
    });

    // --- 4. ENREGISTRER LA MODIFICATION (AJAX) ---
    const formEdit = document.getElementById('form-edit-etudiant');
    formEdit.addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch('etudiant.php', { method: 'POST', body: new FormData(formEdit) })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalEditInstance.hide();
                Swal.fire({ icon: 'success', title: 'Mis à jour', text: data.message, timer: 1500, showConfirmButton: false });
                
                // Mise à jour visuelle instantanée de la ligne
                const etu = data.etudiant;
                const badgeSexe = etu.sexe === 'M' ? '<span class="badge bg-info bg-opacity-10 text-info border border-info">M</span>' : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">F</span>';
                
                document.getElementById(`nom-complet-${etu.id_etudiant}`).innerHTML = `<strong>${etu.nom}</strong> ${etu.prenom}`;
                document.getElementById(`sexe-${etu.id_etudiant}`).innerHTML = badgeSexe;
                document.getElementById(`filiere-${etu.id_etudiant}`).innerHTML = `<span class="badge bg-secondary">${etu.libelle_filiere}</span>`;
                
                // Mettre à jour les données cachées du bouton Edit pour de futures modifs
                const btnEdit = document.querySelector(`.btn-edit[data-id="${etu.id_etudiant}"]`);
                btnEdit.setAttribute('data-nom', etu.nom);
                btnEdit.setAttribute('data-prenom', etu.prenom);
                btnEdit.setAttribute('data-sexe', etu.sexe);
                btnEdit.setAttribute('data-filiere', etu.code_filiere);
                
            } else {
                Swal.fire({ icon: 'error', title: 'Erreur', text: data.message });
            }
        });
    });
});
</script>

<style>
/* Petite animation CSS pour l'apparition en douceur de la nouvelle ligne */
@keyframes fadeIn {
    from { opacity: 0; background-color: #d1e7dd; }
    to { opacity: 1; background-color: transparent; }
}
.fade-in {
    animation: fadeIn 2s ease-in-out forwards;
}
</style>

</body>
</html>