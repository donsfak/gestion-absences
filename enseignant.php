<?php
// enseignant.php
require 'db.php';
$message = '';

// 1. LOGIQUE DE SUPPRESSION (Delete)
if (isset($_GET['delete'])) {
    $id_del = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM ENSEIGNANT WHERE id_enseignant = ?");
        $stmt->execute([$id_del]);
        $message = "<div class='alert alert-success'>Enseignant supprimé avec succès.</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur : Impossible de supprimer cet enseignant car il est lié à un cours programmé.</div>";
    }
}

// 2. LOGIQUE D'AJOUT (Create)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_enseignant'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO ENSEIGNANT (id_enseignant, Nom, Prenom, Mail, Specialite, Diplome, Sexe) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['id_enseignant'], $_POST['nom'], $_POST['prenom'], 
            $_POST['mail'], $_POST['specialite'], $_POST['diplome'], $_POST['sexe']
        ]);
        $message = "<div class='alert alert-success'>Enseignant ajouté !</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
    }
}

// 3. LOGIQUE D'AFFICHAGE (Read)
$enseignants = $pdo->query("SELECT * FROM ENSEIGNANT")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Enseignants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'menu.php'; ?>

<div class="container">
    <h2 class="mb-4">Paramétrage : Enseignants</h2>
    <?= $message ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Nouvel Enseignant</h5>
                    <form method="POST">
                        <input type="number" name="id_enseignant" class="form-control mb-2" placeholder="ID / Matricule" required>
                        <input type="text" name="nom" class="form-control mb-2" placeholder="Nom" required>
                        <input type="text" name="prenom" class="form-control mb-2" placeholder="Prénom" required>
                        <input type="email" name="mail" class="form-control mb-2" placeholder="Email" required>
                        <input type="text" name="specialite" class="form-control mb-2" placeholder="Spécialité" required>
                        <input type="text" name="diplome" class="form-control mb-2" placeholder="Diplôme" required>
                        <select name="sexe" class="form-select mb-3">
                            <option value="Homme">Homme</option>
                            <option value="Femme">Femme</option>
                        </select>
                        <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nom & Prénom</th>
                                <th>Spécialité</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enseignants as $e): ?>
                            <tr>
                                <td><?= $e['id_enseignant'] ?></td>
                                <td><strong><?= htmlspecialchars($e['Nom']) ?></strong> <?= htmlspecialchars($e['Prenom']) ?></td>
                                <td><?= htmlspecialchars($e['Specialite']) ?></td>
                                <td class="text-end">
                                    <a href="edit_enseignant.php?id=<?= $e['id_enseignant'] ?>" class="btn btn-sm btn-warning text-dark fw-bold me-1 shadow-sm">
        <i class="bi bi-pencil-square me-1"></i> Modifier
    </a>
    
    <a href="filiere.php?delete=<?= $f['code_filiere'] ?>" class="btn btn-sm btn-danger text-white fw-bold shadow-sm" onclick="return confirm('Confirmer la suppression définitive ?')">
        <i class="bi bi-trash3 me-1"></i> Supprimer
    </a>
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
<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#enseignantTable tbody").rows;
    
    for (let i = 0; i < rows.length; i++) {
        let textMatricule = rows[i].cells[0].textContent.toUpperCase();
        let textNom = rows[i].cells[1].textContent.toUpperCase();
        
        if (textMatricule.indexOf(filter) > -1 || textNom.indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }      
    }
});
</script>
</body>
</html>