<?php
// migration_passwords.php
require 'db.php';

echo "<h3>Démarrage de la migration des mots de passe...</h3>";

try {
    // On utilise bien "identifiant" ici
    $users = $pdo->query("SELECT id_user, identifiant, mot_de_passe FROM USERS")->fetchAll();

    foreach ($users as $user) {
        // On vérifie si le mot de passe n'est pas déjà haché
        if (substr($user['mot_de_passe'], 0, 4) !== '$2y$') {
            
            // Hachage BCRYPT
            $hashed_password = password_hash($user['mot_de_passe'], PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("UPDATE USERS SET mot_de_passe = ? WHERE id_user = ?");
            $stmt->execute([$hashed_password, $user['id_user']]);
            
            echo "Utilisateur " . htmlspecialchars($user['identifiant']) . " : Mot de passe sécurisé !<br>";
        } else {
            echo "Utilisateur " . htmlspecialchars($user['identifiant']) . " : Déjà sécurisé.<br>";
        }
    }
    echo "<br><b style='color:green;'>Migration terminée avec succès ! Tu peux supprimer ce fichier du serveur.</b>";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>