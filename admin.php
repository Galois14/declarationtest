<?php
        session_start();

        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: login.php');
            exit;
        }
        
        // Connexion à la base de données
        $servername = "localhost";
        $username = "root";
        $password = "root";
        $dbname = "goldexport";

        $conn = new mysqli($servername, $username, $password, $dbname);

        // Vérification de la connexion
        if ($conn->connect_error) {
            die("Échec de la connexion à la base de données : " . $conn->connect_error);
        }


        
        //Recuperation ID_Groupe
        $userID = $_SESSION['ID_user'];
        $requeteUsers = $conn->prepare('SELECT ID_Groupe FROM Users WHERE ID_user = ?');
        $requeteUsers->bind_param('i', $userID);
        $requeteUsers->execute();
        $resultatUsers = $requeteUsers->get_result();
        $rowUsers = $resultatUsers->fetch_assoc();
        $groupeID = $rowUsers['ID_Groupe'];

        // Redirigez l'utilisateur ou affichez un message d'erreur pour accès refusé
        switch ($groupeID) {
            case 1:
                header("Location: home.php");
                break;
            case 3:
                header("Location: anor_home.php");
                break;
            case 4:
                header("Location: consutant_home.php");
                break;

  
            }


        // Requête pour récupérer les informations des dossiers
$sql = "SELECT D.ID_dossier, D.Num_Declaration, D.Adresse_Destination, D.Pays_Destination, D.Num_Facture_Export, D.Nom_Acheteur_ou_Importateur, D.Validation_DGAM_Dossier, E.Date_Envoie, S.Nom_Societe, V.Date_Validation
FROM dossier D
INNER JOIN societe S ON D.ID_Societe = S.ID_Societe
INNER JOIN Envoyer E ON D.ID_dossier = E.ID_dossier
LEFT JOIN Valider V ON D.ID_dossier = V.ID_dossier
ORDER BY D.ID_dossier";

$result = $conn->query($sql);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Liste des dossiers</title>
    <!-- Importation de Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include_once('header.php'); ?>
    <div class="container">
        <h1 class="mt-4 mb-4">Liste des dossiers</h1>
        
<div class="table-responsive">
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Acheteur ou Importateur</th>
            <th>Pays de destination</th>
            <th>Date d'Envoi</th>
            <th>Nom de la Société</th>
            <th>Statut du Dossier</th>
            <th>Date de Validation</th>
            <th>Consulter</th>
        </tr>
    </thead>
    <tbody>
        <?php
            if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            ?>
            <tr>
                <td><?php echo $row['ID_dossier']; ?></td>
                <td><?php echo $row['Nom_Acheteur_ou_Importateur']; ?></td>
                <td><?php echo $row['Pays_Destination']; ?></td>
                <td><?php echo date('d F Y', strtotime($row['Date_Envoie'])); ?></td>
                <td><?php echo $row['Nom_Societe']; ?></td>
                <td>
                    <?php
                    switch ($row['Validation_DGAM_Dossier']) {
                        case 0:
                            echo "En attente";
                            break;
                        case 1:
                            echo "Validé";
                            break;
                        case 2:
                            echo "Refusé";
                            break;
                        default:
                            echo "Statut inconnu";
                    }
                    ?>
                </td>
                <td><?php echo !empty($row['Date_Validation']) ? date('d F Y', strtotime($row['Date_Validation'])) : " "; ?></td>

                <td>
                    <?php if ($row['Validation_DGAM_Dossier'] != 0) : ?>
                        <a href="details_dossier.php?dossier=<?php echo $row['ID_dossier']; ?>" class="btn btn-secondary">Consulter</a>
                    <?php else : ?>
                        <a href="consulter_dossier.php?dossier=<?php echo $row['ID_dossier']; ?>" class="btn btn-primary">Consulter</a>
                    <?php endif; ?>
                </td>

            </tr>
        <?php
        }
    } else {
        echo "Aucun dossier trouvé.";
        }
        ?>
    </tbody>
</table>
</div>
<?php


$conn->close();
?>
<!-- <form action="logout.php" method="POST">
<input type="submit" value="Déconnexion" class="btn btn-danger">
    </form> -->
