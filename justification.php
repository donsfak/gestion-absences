<?php
// justification.php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ADMIN') {
    header("Location: edition.php");
    exit();
}

$message = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// --- 1. TRAITEMENT DES ACTIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // ACTION A : AJOUTER
    if ($_POST['action'] == 'ajouter') {
        $id_etu = $_POST['id_etudiant'];
        $date_debut = $_POST['date_debut'];
        $date_fin = $_POST['date_fin'];
        $motif = htmlspecialchars($_POST['motif']);
        $chemin_fichier = null;

        if (isset($_FILES['piece_jointe']) && $_FILES['piece_jointe']['error'] == 0) {
            $dossier_cible = "uploads/";
            if (!is_dir($dossier_cible)) mkdir($dossier_cible, 0777, true);
            $chemin_fichier = $dossier_cible . time() . '_' . basename($_FILES['piece_jointe']['name']);
            move_uploaded_file($_FILES['piece_jointe']['tmp_name'], $chemin_fichier);
        }

        try {
            $pdo->prepare("INSERT INTO JUSTIFICATIF (id_etudiant, date_debut, date_fin, motif, fichier) VALUES (?, ?, ?, ?, ?)")->execute([$id_etu, $date_debut, $date_fin, $motif, $chemin_fichier]);
            
            $pdo->prepare("UPDATE Assister a JOIN ENSEIGNEMENT ens ON a.id_enseignement = ens.id_enseignement SET a.Est_Justifie = 1 WHERE a.id_etudiant = ? AND a.Statut = 'Absent' AND ens.Date_enseignement BETWEEN ? AND ?")->execute([$id_etu, $date_debut, $date_fin]);
            
            $_SESSION['flash_message'] = "<div class='alert alert-success fw-bold'><i class='bi bi-check-circle me-2'></i> Document enregistré et absences mises à jour.</div>";
            header("Location: justification.php"); exit();
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
            header("Location: justification.php"); exit();
        }
    }

    // ACTION B : MODIFIER (Le petit nouveau !)
    if ($_POST['action'] == 'modifier') {
        $id_justif = $_POST['id_justificatif'];
        $id_etu = $_POST['id_etudiant'];
        $new_debut = $_POST['date_debut'];
        $new_fin = $_POST['date_fin'];
        $new_motif = htmlspecialchars($_POST['motif']);

        try {
            // 1. On "désactive" l'ancien périmètre
            $old = $pdo->prepare("SELECT date_debut, date_fin FROM JUSTIFICATIF WHERE id_justificatif = ?");
            $old->execute([$id_justif]);
            $old_data = $old->fetch();
            if ($old_data) {
                $pdo->prepare("UPDATE Assister a JOIN ENSEIGNEMENT ens ON a.id_enseignement = ens.id_enseignement SET a.Est_Justifie = 0 WHERE a.id_etudiant = ? AND ens.Date_enseignement BETWEEN ? AND ?")->execute([$id_etu, $old_data['date_debut'], $old_data['date_fin']]);
            }

            // 2. On met à jour le justificatif
            $pdo->prepare("UPDATE JUSTIFICATIF SET date_debut = ?, date_fin = ?, motif = ? WHERE id_justificatif = ?")->execute([$new_debut, $new_fin, $new_motif, $id_justif]);

            // 3. On "réactive" sur le nouveau périmètre
            $pdo->prepare("UPDATE Assister a JOIN ENSEIGNEMENT ens ON a.id_enseignement = ens.id_enseignement SET a.Est_Justifie = 1 WHERE a.id_etudiant = ? AND a.Statut = 'Absent' AND ens.Date_enseignement BETWEEN ? AND ?")->execute([$id_etu, $new_debut, $new_fin]);

            $_SESSION['flash_message'] = "<div class='alert alert-info fw-bold'><i class='bi bi-pencil-square me-2'></i> Justificatif modifié avec succès !</div>";
            header("Location: justification.php"); exit();
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
            header("Location: justification.php"); exit();
        }
    }

    // ACTION C : SUPPRIMER
    if ($_POST['action'] == 'supprimer') {
        $id_justif = $_POST['id_justificatif'];
        try {
            $doc = $pdo->prepare("SELECT * FROM JUSTIFICATIF WHERE id_justificatif = ?");
            $doc->execute([$id_justif]);
            $j = $doc->fetch();

            if ($j) {
                $pdo->prepare("UPDATE Assister a JOIN ENSEIGNEMENT ens ON a.id_enseignement = ens.id_enseignement SET a.Est_Justifie = 0 WHERE a.id_etudiant = ? AND ens.Date_enseignement BETWEEN ? AND ?")->execute([$j['id_etudiant'], $j['date_debut'], $j['date_fin']]);
                $pdo->prepare("DELETE FROM JUSTIFICATIF WHERE id_justificatif = ?")->execute([$id_justif]);
                
                $_SESSION['flash_message'] = "<div class='alert alert-warning fw-bold'><i class='bi bi-trash me-2'></i> Justificatif supprimé.</div>";
                header("Location: justification.php"); exit();
            }
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
            header("Location: justification.php"); exit();
        }
    }
}

// --- 2. RÉCUPÉRATION ---
$etudiants_en_attente = $pdo->query("SELECT DISTINCT e.id_etudiant, e.Nom, e.Prenom FROM Assister a JOIN ETUDIANT e ON a.id_etudiant = e.id_etudiant WHERE a.Statut = 'Absent' AND a.Est_Justifie = 0 ORDER BY e.Nom")->fetchAll(PDO::FETCH_ASSOC);
$documents = $pdo->query("SELECT j.*, e.Nom, e.Prenom FROM JUSTIFICATIF j JOIN ETUDIANT e ON j.id_etudiant = e.id_etudiant ORDER BY j.date_saisie DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisie - Justifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4 fw-bold"><i class="bi bi-shield-check text-success me-2"></i>Centre des Justifications</h2>
    <?= $message ?>

    <div class="row">
        <!-- FORMULAIRE -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white fw-bold"><i class="bi bi-file-medical me-2"></i>Enregistrer un document</div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">1. Étudiant</label>
                            <select name="id_etudiant" class="form-select border-success" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($etudiants_en_attente as $etu): ?>
                                    <option value="<?= $etu['id_etudiant'] ?>"><?= htmlspecialchars($etu['Nom']) ?> <?= htmlspecialchars($etu['Prenom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6"><label class="form-label small fw-bold">2. Du</label><input type="date" name="date_debut" class="form-control" required></div>
                            <div class="col-6"><label class="form-label small fw-bold">3. Au</label><input type="date" name="date_fin" class="form-control" required></div>
                        </div>
                        <div class="mb-3"><label class="form-label small fw-bold">4. Motif</label><input type="text" name="motif" class="form-control bg-light" required></div>
                        <div class="mb-4"><label class="form-label small fw-bold">5. Fichier</label><input type="file" name="piece_jointe" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png"></div>
                        <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">Valider le document</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTE DES DOCUMENTS -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white fw-bold"><i class="bi bi-folder2-open me-2"></i>Archives</div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Période couverte</th><th>Étudiant</th><th>Motif</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><span class="badge bg-secondary">Du <?= date('d/m/Y', strtotime($doc['date_debut'])) ?> au <?= date('d/m/Y', strtotime($doc['date_fin'])) ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($doc['Nom']) ?> <?= htmlspecialchars($doc['Prenom']) ?></td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($doc['motif']) ?>
                                    <?php if(!empty($doc['fichier'])): ?> <a href="<?= htmlspecialchars($doc['fichier']) ?>" target="_blank" class="text-info ms-2"><i class="bi bi-paperclip"></i></a><?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex">
                                        <!-- BOUTON MODIFIER (Ouvre le Modal) -->
                                        <button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $doc['id_justificatif'] ?>" title="Modifier">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- BOUTON SUPPRIMER -->
                                        <form method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="id_justificatif" value="<?= $doc['id_justificatif'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </div>

                                    <!-- FENÊTRE MODAL DE MODIFICATION -->
                                    <div class="modal fade" id="editModal<?= $doc['id_justificatif'] ?>" tabindex="-1">
                                      <div class="modal-dialog">
                                        <div class="modal-content">
                                          <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Modifier le justificatif</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                          </div>
                                          <form method="POST">
                                              <div class="modal-body text-start">
                                                  <input type="hidden" name="action" value="modifier">
                                                  <input type="hidden" name="id_justificatif" value="<?= $doc['id_justificatif'] ?>">
                                                  <input type="hidden" name="id_etudiant" value="<?= $doc['id_etudiant'] ?>">

                                                  <div class="row mb-3">
                                                      <div class="col-6"><label class="form-label small fw-bold">Du</label><input type="date" name="date_debut" class="form-control" value="<?= $doc['date_debut'] ?>" required></div>
                                                      <div class="col-6"><label class="form-label small fw-bold">Au</label><input type="date" name="date_fin" class="form-control" value="<?= $doc['date_fin'] ?>" required></div>
                                                  </div>
                                                  <div class="mb-3">
                                                      <label class="form-label small fw-bold">Motif</label>
                                                      <input type="text" name="motif" class="form-control" value="<?= htmlspecialchars($doc['motif']) ?>" required>
                                                  </div>
                                                  <div class="alert alert-warning small mb-0"><i class="bi bi-info-circle me-1"></i> Les statuts des absences seront resynchronisés.</div>
                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                                              </div>
                                          </form>
                                        </div>
                                      </div>
                                    </div>
                                    <!-- FIN DU MODAL -->

                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>