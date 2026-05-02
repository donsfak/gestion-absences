<?php
// recu_etudiant.php
require 'db.php';
$id = $_GET['id'];

// Récupération des infos avec jointure pour la filière
$stmt = $pdo->prepare("SELECT e.*, f.Libele_filiere FROM ETUDIANT e JOIN FILIERE f ON e.code_filiere = f.code_filiere WHERE id_etudiant = ?");
$stmt->execute([$id]);
$e = $stmt->fetch();

if (!$e) die("Étudiant introuvable.");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu d'Inscription - <?= $e['id_etudiant'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eee; }
        .receipt-card { background: white; width: 210mm; min-height: 148mm; margin: 20px auto; padding: 20px; border: 1px solid #ddd; position: relative; }
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(0,0,0,0.03); font-weight: bold; pointer-events: none; }
        
        @media print {
            body { background: white; }
            .receipt-card { border: none; margin: 0; width: 100%; box-shadow: none; }
            .btn-print { display: none; } /* On cache le bouton lors de l'impression */
        }
    </style>
</head>
<body>

<div class="container text-center mt-3 no-print">
    <button onclick="window.print()" class="btn btn-primary btn-print shadow">
        <i class="bi bi-printer-fill me-2"></i>Imprimer le Reçu Officiel
    </button>
</div>

<div class="receipt-card shadow-sm">
    <div class="watermark">ESATIC - OFFICIEL</div>
    
    <div class="row align-items-center mb-4">
        <div class="col-3 text-center">
            <div class="border p-2 fw-bold text-primary">LOGO ESATIC</div>
        </div>
        <div class="col-9 text-end">
            <h4 class="fw-bold mb-0">REÇU D'INSCRIPTION</h4>
            <small class="text-muted">Année Académique 2025-2026</small><br>
            <small>N° DOC : REG-<?= date('Y') ?>-<?= $e['id_etudiant'] ?></small>
        </div>
    </div>

    <hr>

    <div class="row mt-5">
        <div class="col-6">
            <p class="mb-1 text-muted small text-uppercase fw-bold">Informations Étudiant</p>
            <h5 class="fw-bold"><?= htmlspecialchars($e['Nom']) ?> <?= htmlspecialchars($e['Prenom']) ?></h5>
            <p>Matricule : <strong><?= htmlspecialchars($e['id_etudiant']) ?></strong><br>
               Sexe : <?= $e['Sexe'] == 'M' ? 'Masculin' : 'Féminin' ?></p>
        </div>
        <div class="col-6 text-end">
            <p class="mb-1 text-muted small text-uppercase fw-bold">Affectation Académique</p>
            <h5 class="text-primary fw-bold"><?= htmlspecialchars($e['Libele_filiere']) ?></h5>
            <p>Statut : <span class="badge bg-success">Inscrit</span></p>
        </div>
    </div>

    <div class="mt-5 p-3 bg-light rounded border border-dashed">
        <p class="small mb-0 italic">Ce document atteste que l'étudiant suscité est régulièrement inscrit auprès des services académiques de l'ESATIC pour la période en cours.</p>
    </div>

    <div class="row mt-5 pt-4">
        <div class="col-6 text-center">
            <p class="small text-muted">Signature de l'Étudiant</p>
            <div style="height: 60px;"></div>
            <p>____________________</p>
        </div>
        <div class="col-6 text-center">
            <p class="small text-muted">Le Service de Scolarité (Cachet)</p>
            <div style="height: 60px;"></div>
            <p>Fait à Abidjan, le <?= date('d/m/Y') ?></p>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</body>
</html>