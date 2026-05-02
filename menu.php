<?php
// menu.php

// 1. Vérification sécurisée de la session (Correction du Notice PHP)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Protection : si l'utilisateur n'est pas connecté, on le renvoie vers l'accueil
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['user_role'];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow">
    <div class="container">
        <a class="navbar-brand fw-bold" href="edition.php">
            <i class="bi bi-mortarboard-fill text-warning me-2"></i>GESTION ABSENCES
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                
                <?php if($role == 'ADMIN'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-sliders me-1"></i> Paramétrage
                    </a>
                    <ul class="dropdown-menu border-0 shadow">
                        <li><a class="dropdown-item" href="filiere.php"><i class="bi bi-diagram-3 me-2 text-primary"></i>Filières</a></li>
                        <li><a class="dropdown-item" href="matiere.php"><i class="bi bi-journal-bookmark me-2 text-success"></i>Matières</a></li>
                        <li><a class="dropdown-item" href="enseignant.php"><i class="bi bi-person-video3 me-2 text-info"></i>Enseignants</a></li>
                        <li><a class="dropdown-item" href="periode.php"><i class="bi bi-calendar-range me-2 text-warning"></i>Périodes</a></li>
<li><a class="dropdown-item" href="correspondance.php"><i class="bi bi-link-45deg me-2 text-danger"></i>Maquette Pédagogique</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-pencil-square me-1"></i> Saisie
                    </a>
                    <ul class="dropdown-menu border-0 shadow">
                        <?php if($role == 'ADMIN'): ?>
                            <li><a class="dropdown-item" href="etudiant.php"><i class="bi bi-person-plus me-2 text-primary"></i>Inscriptions Étudiants</a></li>
                            <li><a class="dropdown-item" href="enseignement.php"><i class="bi bi-calendar-plus me-2 text-success"></i>Programmer un cours</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        
                        <li><a class="dropdown-item" href="appel.php"><i class="bi bi-card-checklist me-2 text-danger"></i>Faire l'appel</a></li>
                        
                        <?php if($role == 'ADMIN'): ?>
                            <li><a class="dropdown-item" href="justification.php"><i class="bi bi-envelope-paper me-2 text-warning"></i>Justifier une absence</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link fw-bold text-white" href="edition.php">
                        <i class="bi bi-file-earmark-bar-graph-fill me-1 text-info"></i> Édition & Stats
                    </a>
                </li>
                
            </ul>

            <div class="navbar-nav ms-auto align-items-center">
                <span class="nav-link text-light me-3 small">
                    <i class="bi bi-person-circle me-1"></i> 
                    <strong><?= htmlspecialchars(strtoupper($_SESSION['user_nom'])) ?></strong> (<?= htmlspecialchars($role) ?>)
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>