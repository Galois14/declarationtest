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

// Récupération ID_Groupe
$userID = $_SESSION['ID_user'];
$requeteUsers = $conn->prepare('SELECT ID_Groupe, ID_Societe FROM Users WHERE ID_user = ?');
$requeteUsers->bind_param('i', $userID);
$requeteUsers->execute();
$resultatUsers = $requeteUsers->get_result();
$rowUsers = $resultatUsers->fetch_assoc();

$groupeID = $rowUsers['ID_Groupe'];
$societeID = $rowUsers['ID_Societe'];
// Récupération des pièces jointes Societe
$sqlPJS = "SELECT * FROM piece_jointe_societe WHERE ID_Societe = ?";
// $stmtPJS = $conn->prepare($sqlPJS);
// $stmtPJS->bind_param("i", $societeID);
// // echo $societeID ;

// if (!$stmtPJS->execute()) {
//     echo "Erreur lors de l'exécution de la requête : " . $stmtPJS->error;
//     exit;
// }

// $resultPJS = $stmtPJS->get_result();

// $stmtPJS->close();


// Vérification de l'ID du dossier passé en paramètre
if (!isset($_GET['dossier']) || empty($_GET['dossier'])) {
    echo "ID du dossier non spécifié.";
    exit;
}

$dossierID = $_GET['dossier'];

// Requête pour récupérer les informations du dossier, de la société et des pièces jointes
$sql = "SELECT *  FROM dossier
        INNER JOIN societe ON dossier.ID_Societe = societe.ID_Societe
        LEFT JOIN Envoyer ON dossier.ID_dossier = Envoyer.ID_dossier
        LEFT JOIN Valider ON dossier.ID_dossier = Valider.ID_dossier
        LEFT JOIN piece_jointe ON dossier.ID_dossier = piece_jointe.ID_dossier
        WHERE dossier.ID_dossier = $dossierID";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Aucun dossier trouvé.";
    exit;
}

$row = $result->fetch_assoc();
$Validation = $row['Validation_DGAM_Dossier'];

// Récupération des pièces jointes Societe
$sqlPJS = "SELECT * FROM piece_jointe_societe 
INNER JOIN dossier ON piece_jointe_societe.ID_Societe = dossier.ID_Societe
WHERE dossier.ID_dossier = $dossierID";
$stmtPJS = $conn->prepare($sqlPJS);


if (!$stmtPJS->execute()) {
    echo "Erreur lors de l'exécution de la requête : " . $stmtPJS->error;
    exit;
}

$resultPJS = $stmtPJS->get_result();

// Récupération des informations de l'utilisateur
$requeteUsers = $conn->prepare('SELECT ID_user, Nom_user, mail_user, Contact_User FROM users WHERE ID_Societe = ?');
$requeteUsers->bind_param('i', $societeID);
$requeteUsers->execute();
$resultatUsers = $requeteUsers->get_result();
$rowUsers = $resultatUsers->fetch_assoc();

// Requête pour récupérer le nom de l'utilisateur correspondant à l'ID_dossier
$sqlNomUser = "SELECT users.Nom_user FROM Valider
               INNER JOIN users ON Valider.ID_user = users.ID_user
               WHERE Valider.ID_dossier = $dossierID";

$resultNomUser = $conn->query($sqlNomUser);
$nomUser = "";
if ($resultNomUser->num_rows > 0) {
    $rowNomUser = $resultNomUser->fetch_assoc();
    $nomUser = $rowNomUser['Nom_user'];
}



?>

<!DOCTYPE html>
<html>
<head>
    <title>Consulter le dossier</title>
    <!-- Importation de Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
    .company-details {
        background-color: #ffffff;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: Arial, sans-serif;
    }

    .company-details h2 {
        font-size: 20px;
        margin-bottom: 15px;
    }

    .company-details ul {
        list-style: none;
        padding: 0;
        margin-bottom: 10px;
    }

    .company-details li {
        margin-bottom: 5px;
    }

    .company-details strong {
        font-weight: bold;
    }

        .status-label {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-approved {
            background-color: #28a745;
            color: #fff;
        }

        .status-rejected {
            background-color: #dc3545;
            color: #fff;
        }

</style>

</head>
<body>
<?php include_once('header.php'); ?>
    <div class="container">
        <!-- <h1 class="mt-4 mb-4">Dossier n°<?php echo $row['ID_dossier']; ?></h1> -->
        <h3>Informations de la société <?php echo $row['Nom_Societe']; ?></h3>
        
        <div class="container">
    <table class="table table-striped">
        <tbody>
            <tr>
                <th>NIF/STAT</th>
                <td><?php echo $row['NIF_Societe']; ?> / <?php echo $row['STAT_Societe']; ?></td>
            </tr>
            <tr>
                <th>Attestation de déclaration</th>
                <td><?php echo $row['Num_Attest_de_Declaration']; ?> du <?php echo $row['Date_Attestation_Declaration']; ?></td>
            </tr>
            <tr>
                <th>Agrément</th>
                <td><?php echo $row['Num_Agrement']; ?> du <?php echo $row['Date_Agrement']; ?></td>
            </tr>
            <tr>
                <th>Gérant</th>
                <td><?php echo $row['Nom_gerant_societe']; ?></td>
            </tr>
            <tr>
                <th>Contact</th>
                <td><?php echo $row['Contacte_Societe']; ?> ou <?php echo $row['Mail_Societe']; ?></td>
            </tr>
            <tr>
                <th>Adresse</th>
                <td><?php echo $row['Adresse_Societe']; ?></td>
            </tr>
            <tr>
                <th>Compte Bancaire</th>
                <td><?php echo $row['Num_Compte_Bancaire']; ?></td>
            </tr>
            <tr>
                <th>Validation ANOR</th>
                <td>
                    <?php
                    switch ($row['Validation_ANOR_Societe']) {
                        case 0:
                            $statut = 'En attente';
                            $statusClass = 'status-pending';
                            break;
                        case 1:
                            $statut = 'Validé';
                            $statusClass = 'status-approved';
                            break;
                        case 2:
                            $statut = 'Refusé';
                            $statusClass = 'status-rejected';
                            break;
                        default:
                            echo "Statut inconnu";
                    }
                    echo "<span class='status-label badge $statusClass'>$statut</span>";
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

        <h3>Détails du dossier</h3>
        <table class="table">
            <tr>
                <th>ID Dossier</th>
                <th>Pays destination</th>
                <th>Quantite a exporter</th>
                <th>Valeur</th>
                <th>Date d'Envoi</th>
                <th>Embarquement</th>
                <th>Status</th>
                
            </tr>
            <tr>
                <td><?php echo $row['ID_dossier']; ?></td>
                <td><?php echo $row['Pays_Destination']; ?></td>
                <td><?php echo $row['Quantite_Export']; ?></td>
                <td><?php echo $row['Valeur_Export']; ?> <?php echo $row['Unite']; ?></td>
                <td><?php echo $row['Date_Envoie']; ?></td>
                <td><?php echo $row['Date_Embarquement']; ?></td>
                <td>
                    <?php
                        switch ($row['Validation_DGAM_Dossier']) {
                            case 0:
                                $statut = 'En attente';
                                $statusClass = 'status-pending';
                                break;
                            case 1:
                                $statut = 'Validé';
                                $statusClass = 'status-approved';
                                break;
                            case 2:
                                $statut = 'Refusé';
                                $statusClass = 'status-rejected';
                                
                                break;
                            default:
                                echo "Statut inconnu";
                        }
                        echo "<span class='status-label $statusClass'>$statut</span>";
                    ?>
                </td>
                
            </tr>

        </table>
        <ul>
            <li><strong>Numero et date du declaration:</strong> <?php echo $row['Num_Declaration']; ?> du <?php echo $row['Date_Declaration']; ?></li>
            <li><strong>Nombre de Clois:</strong> <?php echo $row['Nombre_Colis']; ?></li>
            <li><strong>Date d'embarquement:</strong> <?php echo $row['Date_Embarquement']; ?> </li>
            <li><strong>Acheteur ou Importateur:</strong> <?php echo $row['Nom_Acheteur_ou_Importateur']; ?></li>
            <li><strong>Adresse destination :</strong> <?php echo $row['Adresse_Destination']; ?> / <?php echo $row['Pays_Destination']; ?></li>
            <li><strong>Numero du Facture:</strong> <?php echo $row['Num_Facture_Export']; ?></li>
            <li><strong>Numero de LPIII E:</strong> <?php echo $row['Num_LPIIIE']; ?></li>
        </ul>

        <h3>Pièces jointes du dossier</h3>
        <ul>
            <?php
            while ($rowPJ = $result->fetch_assoc()) {
                if (!is_null($rowPJ['ID_piece_jointe'])) {
                    echo '<li><a href="' . $rowPJ['PJ_Upload'] . '">' . $rowPJ['Nom_piece_jointe'] . '</a></li>';
                }
            }
            ?>
        </ul>
        <h3>Pièces jointes da societe</h3>
        <ul>
            <?php
            while ($rowPJS = $resultPJS->fetch_assoc()) {
    
                    echo '<li><a href="' . $rowPJS['PJ_Upload_Societe'] . '">' . $rowPJS['Nom_PJ_Societe'] . '</a></li>';

            }
            ?>
        </ul>

        <h3>Commentaire : </h3>
            <div class="form-group">
                <div class="company-details">
                    <p><?php echo nl2br($row['Commentaire']); ?></p>
                </div>
                <p>Verifié par <strong><?php echo $nomUser; ?></strong></p>
            </div>
    </div>

<!-- Importation de JQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<!-- Importation de Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-pzjw8+ua5bq5E5n8128Ly6DbwR7dK8KGbfs5/O6A6Kl4vZ83Ri6Xf5DL5rwMWHzf" crossorigin="anonymous"></script>
<?php include_once('footer.php'); ?>
</body>
</html>
