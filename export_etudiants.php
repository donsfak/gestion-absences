<?php
// export_etudiants.php
session_start();
require 'db.php';

// Sécurité : On vérifie que la personne est bien connectée
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// On dit au navigateur de forcer le téléchargement d'un fichier CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Liste_Etudiants_ESATIC_' . date('Y_m_d') . '.csv');

// Ouvrir la sortie standard de PHP
$output = fopen('php://output', 'w');

// 💡 ASTUCE PRO : Ajouter le "BOM UTF-8"
// Cela force Microsoft Excel à lire correctement les accents (é, à, ç) !
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 1. Écrire la ligne d'en-tête du tableau (Le séparateur est le point-virgule ';' pour Excel en français)
fputcsv($output, array('Matricule', 'Nom', 'Prénom', 'Sexe', 'Classe / Filière'), ';');

// 2. Récupérer les étudiants avec une jointure pour avoir le nom complet de la filière
$query = "SELECT e.id_etudiant, e.Nom, e.Prenom, e.Sexe, f.Libele_filiere 
          FROM ETUDIANT e 
          JOIN FILIERE f ON e.code_filiere = f.code_filiere 
          ORDER BY f.Libele_filiere ASC, e.Nom ASC";
$stmt = $pdo->query($query);

// 3. Boucler sur les résultats et écrire chaque ligne dans le fichier Excel
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // On met en majuscule le nom pour faire plus propre dans Excel
    $row['Nom'] = strtoupper($row['Nom']);
    fputcsv($output, $row, ';');
}

// Fermer le fichier
fclose($output);
exit();
?>