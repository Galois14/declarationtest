<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$serveur = 'localhost';
$utilisateurDB = 'root';
$motDePasseDB = 'root';
$nomBaseDeDonnees = 'goldexport';

$connexion = new mysqli($serveur, $utilisateurDB, $motDePasseDB, $nomBaseDeDonnees);
if ($connexion->connect_error) {
    die('Erreur de connexion à la base de données : ' . $connexion->connect_error);
}

$userID = $_SESSION['ID_user'];
$requeteUsers = $connexion->prepare('SELECT ID_Groupe FROM Users WHERE ID_user = ?');
$requeteUsers->bind_param('i', $userID);
$requeteUsers->execute();
$resultatUsers = $requeteUsers->get_result();
$rowUsers = $resultatUsers->fetch_assoc();
$groupeID = $rowUsers['ID_Groupe'];

if ($groupeID !== 1) {
    // Redirection de l'utilisateur ou affichage d'un message d'erreur pour accès refusé
    switch ($groupeID) {
        case 2:
            header("Location: admin.php");
            break;
        case 3:
            header("Location: anor_home.php");
            break;
        case 4:
            header("Location: consutant_home.php");


        }
    exit;
}

$userID = $_SESSION['ID_user'];
$requeteSociete = $connexion->prepare('SELECT ID_Societe FROM Users WHERE ID_user = ?');
$requeteSociete->bind_param('i', $userID);
$requeteSociete->execute();
$resultatSociete = $requeteSociete->get_result();
$rowSociete = $resultatSociete->fetch_assoc();
$societeID = $rowSociete['ID_Societe'];

$requeteDossiers = $connexion->prepare('SELECT D.ID_dossier, D.Num_Declaration, D.Adresse_Destination, D.Pays_Destination, D.Num_Facture_Export, D.Nom_Acheteur_ou_Importateur, D.Validation_DGAM_Dossier, E.Date_Envoie, V.Date_Validation FROM Dossier D
INNER JOIN Envoyer E ON D.ID_dossier = E.ID_dossier
LEFT JOIN Valider V ON D.ID_dossier = V.ID_dossier
WHERE D.ID_Societe = ?
ORDER BY D.ID_dossier'); // Ajout de l'ordre de tri par le numéro de dossier
$requeteDossiers->bind_param('i', $societeID);
$requeteDossiers->execute();
$resultatDossiers = $requeteDossiers->get_result();
;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Liste des dossiers</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
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
        <h1>Liste des dossiers</h1>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Acheteur ou Importateur</th>
                    <th>Pays de destination</th>
                    <th>Date d'envoi</th>
                    <th>Date de réponse</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($rowDossier = $resultatDossiers->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $rowDossier['ID_dossier']; ?></td>
                        <td><?php echo $rowDossier['Nom_Acheteur_ou_Importateur']; ?></td>
                        <td><?php echo $rowDossier['Pays_Destination']; ?></td>
                        <td><?php echo $rowDossier['Date_Envoie']; ?></td>
                        <td>
                            <?php
                                if (!empty($rowDossier['Date_Validation'])) {
                                    echo $rowDossier['Date_Validation'];
                                } else {
                                    echo "N/A";
                                }
                            ?>
                        </td>
                        <td>
                            <?php
                                $validation = $rowDossier['Validation_DGAM_Dossier'];
                                $statut = '';
                                $statusClass = '';

                                if ($validation === 0) {
                                    $statut = 'En attente';
                                    $statusClass = 'status-pending';
                                } elseif ($validation === 1) {
                                    $statut = 'Validé';
                                    $statusClass = 'status-approved';
                                } elseif ($validation === 2) {
                                    $statut = 'Refusé';
                                    $statusClass = 'status-rejected';
                                }

                                echo "<span class='status-label $statusClass'>$statut</span>";
                            ?>
                        </td>
                        <td>
                            <?php
                                if ($validation === 1 || $validation === 2) {
                                    // Afficher le bouton de détails si le statut est valide ou refusé
                                    echo "<a href='details_dossier.php?dossier=".$rowDossier['ID_dossier']."' class='btn btn-primary'>Voir les détails</a>";
                                }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php include_once('footer.php'); ?>
</body>
</html>

<?php
$requeteSociete->close();
$requeteDossiers->close();
$connexion->close();
?>
