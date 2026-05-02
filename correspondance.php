<?php
// correspondance.php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: index.php");
    exit();
}

// --- GESTION DU JETON CSRF (Niveau Expert) ---
if (empty($_SESSION['csrf_token'])) {
    // Génère un jeton cryptographique ultra-sécurisé de 64 caractères
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- GESTION DES MESSAGES FLASH (PRG Pattern) ---
$message = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// --- 1. TRAITEMENT DES FORMULAIRES ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // VÉRIFICATION DE SÉCURITÉ CSRF OBLIGATOIRE
    // hash_equals empêche les attaques temporelles (timing attacks)
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "<div class='alert alert-danger fw-bold'><i class='bi bi-shield-x me-2'></i>Erreur de sécurité : Action non autorisée (Jeton invalide).</div>";
        header("Location: correspondance.php");
        exit();
    }

    // ACTION A : AJOUTER UN LIEN
    if ($_POST['action'] == 'ajouter') {
        $code_filiere = $_POST['code_filiere'];
        $code_matiere = $_POST['code_matiere'];
        $volume = $_POST['volume'];

        try {
            $stmt = $pdo->prepare("INSERT INTO Correspondre (code_filiere, code_matiere, Volume_horaire) VALUES (?, ?, ?)");
            $stmt->execute([$code_filiere, $code_matiere, $volume]);
            $_SESSION['flash_message'] = "<div class='alert alert-success fw-bold'><i class='bi bi-check-circle me-2'></i>Lien établi avec succès !</div>";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "<div class='alert alert-danger'>Erreur : Ce lien existe peut-être déjà ou les données sont invalides.</div>";
        }
        header("Location: correspondance.php"); exit();
    }

    // ACTION B : MODIFIER LE VOLUME HORAIRE
    if ($_POST['action'] == 'modifier') {
        $filiere = $_POST['code_filiere'];
        $matiere = $_POST['code_matiere'];
        $nouveau_volume = $_POST['volume'];

        try {
            $stmt = $pdo->prepare("UPDATE Correspondre SET Volume_horaire = ? WHERE code_filiere = ? AND code_matiere = ?");
            $stmt->execute([$nouveau_volume, $filiere, $matiere]);
            $_SESSION['flash_message'] = "<div class='alert alert-info fw-bold'><i class='bi bi-pencil-square me-2'></i>Volume horaire mis à jour avec succès.</div>";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "<div class='alert alert-danger'>Erreur lors de la modification : " . $e->getMessage() . "</div>";
        }
        header("Location: correspondance.php"); exit();
    }

    // ACTION C : SUPPRIMER LE LIEN
    if ($_POST['action'] == 'supprimer') {
        $filiere = $_POST['code_filiere'];
        $matiere = $_POST['code_matiere'];

        try {
            $stmt = $pdo->prepare("DELETE FROM Correspondre WHERE code_filiere = ? AND code_matiere = ?");
            $stmt->execute([$filiere, $matiere]);
            $_SESSION['flash_message'] = "<div class='alert alert-warning fw-bold'><i class='bi bi-trash me-2'></i>Association supprimée de la maquette.</div>";
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "<div class='alert alert-danger'>Erreur : Impossible de supprimer, cette matière est peut-être déjà utilisée dans les absences.</div>";
        }
        header("Location: correspondance.php"); exit();
    }
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$filieres = $pdo->query("SELECT * FROM FILIERE ORDER BY Libele_filiere")->fetchAll();
$matieres = $pdo->query("SELECT * FROM MATIERE ORDER BY Nom_matiere")->fetchAll();

$liens = $pdo->query("
    SELECT c.code_filiere, c.code_matiere, f.Libele_filiere, m.Nom_matiere, c.Volume_horaire 
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container mt-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-link-45deg text-primary me-2"></i>Maquette : Matières par Filière</h2>
    
    <?= $message ?>

    <div class="row">
        <!-- FORMULAIRE D'AJOUT -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white fw-bold">Associer une Matière</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="ajouter">
                        
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
                            <input type="number" name="volume" class="form-control" placeholder="ex: 30" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Enregistrer le lien</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTE DES ASSOCIATIONS -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Filière</th>
                                <th>Matière</th>
                                <th>Volume H.</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($liens as $l): 
                                $modal_id = md5($l['code_filiere'] . $l['code_matiere']);
                            ?>
                            <tr>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($l['Libele_filiere']) ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($l['Nom_matiere']) ?></td>
                                <td><?= htmlspecialchars($l['Volume_horaire']) ?> H</td>
                                <td>
                                    <div class="d-flex">
                                        <!-- BOUTON MODIFIER -->
                                        <button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $modal_id ?>" title="Modifier le volume horaire">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- BOUTON SUPPRIMER -->
                                        <form method="POST" onsubmit="return confirm('Voulez-vous vraiment retirer cette matière de la filière ?');">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="code_filiere" value="<?= $l['code_filiere'] ?>">
                                            <input type="hidden" name="code_matiere" value="<?= $l['code_matiere'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer le lien"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </div>

                                    <!-- FENÊTRE MODALE DE MODIFICATION -->
                                    <div class="modal fade" id="editModal<?= $modal_id ?>" tabindex="-1">
                                      <div class="modal-dialog">
                                        <div class="modal-content">
                                          <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Modifier le Volume Horaire</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                          </div>
                                          <form method="POST">
                                              <div class="modal-body text-start">
                                                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                  <input type="hidden" name="action" value="modifier">
                                                  <input type="hidden" name="code_filiere" value="<?= $l['code_filiere'] ?>">
                                                  <input type="hidden" name="code_matiere" value="<?= $l['code_matiere'] ?>">
                                                  
                                                  <div class="mb-3">
                                                      <p class="mb-1"><strong>Filière :</strong> <?= htmlspecialchars($l['Libele_filiere']) ?></p>
                                                      <p class="mb-0"><strong>Matière :</strong> <?= htmlspecialchars($l['Nom_matiere']) ?></p>
                                                  </div>
                                                  <div class="mb-3">
                                                      <label class="form-label small fw-bold">Nouveau Volume Horaire (H)</label>
                                                      <input type="number" name="volume" class="form-control" value="<?= $l['Volume_horaire'] ?>" min="1" required>
                                                  </div>
                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                                              </div>
                                          </form>
                                        </div>
                                      </div>
                                    </div>
                                    <!-- FIN DU MODAL -->

                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($liens)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Aucune matière n'est associée à une filière pour le moment.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- JS indispensable pour faire fonctionner les fenêtres modales Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>