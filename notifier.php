<?php
// notifier.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_etudiant = $_GET['id'];
$message_status = '';

// Récupération des infos de l'étudiant
$stmt = $pdo->prepare("SELECT * FROM ETUDIANT WHERE id_etudiant = ?");
$stmt->execute([$id_etudiant]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    die("Étudiant introuvable.");
}

// Simulation de l'envoi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dans la vraie vie, c'est ici qu'on appelle l'API (Twilio, Orange, etc.)
    // Pour la démo, on simule un délai de traitement de 1 seconde
    sleep(1); 
    $message_status = "<div class='alert alert-success shadow-sm fw-bold'><i class='bi bi-check-circle-fill me-2'></i> Le SMS a été envoyé avec succès aux parents de " . $etudiant['Nom'] . ".</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <title>Notification SMS</title>
</head>
<body class="bg-light">
    
<?php include 'menu.php'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <div class="d-flex align-items-center mb-4">
                <a href="etudiant.php" class="btn btn-outline-secondary btn-sm me-3"><i class="bi bi-arrow-left"></i> Retour</a>
                <h3 class="fw-bold mb-0"><i class="bi bi-chat-dots-fill text-primary me-2"></i> Centre de Notification</h3>
            </div>

            <?= $message_status ?>

            <div class="card shadow border-0" style="border-radius: 15px;">
                <div class="card-header bg-primary text-white pt-3 pb-2" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                    <h5 class="mb-0"><i class="bi bi-phone me-2"></i> Envoyer un SMS d'alerte</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-4">Ce module permet de prévenir instantanément les tuteurs légaux d'une absence constatée.</p>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Destinataire (Parent de :)</label>
                            <input type="text" class="form-control bg-light fw-bold" value="<?= strtoupper($etudiant['Nom']) ?> <?= $etudiant['Prenom'] ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Numéro de téléphone</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">+225</span>
                                <input type="text" class="form-control" value="01 02 03 04 05" readonly>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold text-primary">Message généré automatiquement :</label>
                            <textarea class="form-control border-primary" rows="4" style="background-color: #f0f8ff;">URGENT - ESATIC : Bonjour, nous vous informons que votre enfant <?= $etudiant['Nom'] ?> <?= $etudiant['Prenom'] ?> a été marqué(e) ABSENT(E) ce jour. Merci de vous rapprocher de la scolarité pour justifier cette absence.</textarea>
                            <div class="form-text text-end small">156 caractères (1 SMS)</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow">
                            <i class="bi bi-send-fill me-2"></i> ENVOYER LE SMS MAINTENANT
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>