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

// Récupération de l'ID_Groupe
$userID = $_SESSION['ID_user'];
$requeteUsers = $conn->prepare('SELECT ID_Groupe FROM Users WHERE ID_user = ?');
$requeteUsers->bind_param('i', $userID);
$requeteUsers->execute();
$resultatUsers = $requeteUsers->get_result();
$rowUsers = $resultatUsers->fetch_assoc();
$groupeID = $rowUsers['ID_Groupe'];

switch ($groupeID) {
    case 1:
        header("Location: home.php");
        exit;
    case 2:
        header("Location: admin.php");
        exit;
    case 4:
        header("Location: consultant_home.php");
        exit;
}


// Vérification de l'ID du societe passé en paramètre
if (!isset($_GET['societe']) || empty($_GET['societe'])) {
    echo "ID du societe non spécifié.";
    exit;
}

$societeID = $_GET['societe'];

// Récupération des données de la table "societe" pour l'ID_Societe donné
$sql = "SELECT * FROM societe WHERE ID_Societe = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $societeID);
$stmt->execute();
$result = $stmt->get_result();

// Récupération des pièces jointes de la table "piece_jointe_societe" pour l'ID_Societe = 1

$sqlPJ = "SELECT * FROM piece_jointe_societe WHERE ID_Societe = ?";
$stmtPJ = $conn->prepare($sqlPJ);
$stmtPJ->bind_param("i", $societeID);
$stmtPJ->execute();
$resultPJ = $stmtPJ->get_result();

// Récupération des données de la table "verifier pour l'ID_Societe donné
$sqlV = "SELECT * FROM societe INNER JOIN Verifier ON societe.ID_Societe = Verifier.ID_Societe WHERE societe.ID_Societe = ?";
$stmtV = $conn->prepare($sqlV);
$stmtV->bind_param("i", $societeID);
$stmtV->execute();
$resultV = $stmtV->get_result();

// Traitement du formulaire d'envoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Validation_ANOR_Societe'])) {
    $validation = $_POST['Validation_ANOR_Societe'];
    $commentaire = mysqli_real_escape_string($conn, $_POST['Commentaire_ANOR']);

    // Mettre à jour l'attribut Validation dans la table societe
    $requeteMiseAJour = $conn->prepare('UPDATE societe SET Validation_ANOR_Societe = ? WHERE ID_Societe = ?');
    $requeteMiseAJour->bind_param('ii', $validation, $societeID);
    $resultatMiseAJour = $requeteMiseAJour->execute();
    if (!$resultatMiseAJour) {
        die('Erreur lors de la mise à jour de l\'attribut Validation : ' . $requeteMiseAJour->error);
    }

    $insertSql = "INSERT INTO Verifier (ID_user, ID_Societe, Date_verification, Commentaire_ANOR) VALUES ('$userID', '$societeID', NOW(), '$commentaire')";
    if ($conn->query($insertSql) === TRUE) {
        echo "Validation mise à jour avec succès.";
        // Rediriger l'utilisateur vers la page admin.php
        header("Location: anor_home.php");
        exit;
    } else {
        echo "Erreur lors de la mise à jour de la validation : " . $conn->error;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Espace de validation des Sociétés</title>
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
        <h1 class="text-center">Espace de validation des Sociétés</h1>

        <?php

        if ($result->num_rows > 0) {
            // Affichage des éléments de la table "societe"
            $row = $result->fetch_assoc();
            ?>

            <div class="card">
                <div class="card-header">
                    Informations de la société <strong><?php echo $row['Nom_Societe']; ?></strong>
                </div>
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

            <?php
        } else {
            echo "<p>Aucune société trouvée pour l'ID_Societe = 1.</p>";
        }



        if ($resultPJ->num_rows > 0) {
            // Affichage des pièces jointes avec des liens de téléchargement
            ?>

            <div class="card">
                <div class="card-header">
                    Pièces jointes
                </div>
                <div class="card-body">
                    <ul class="file-list">
                        <?php
                        while ($rowPJ = $resultPJ->fetch_assoc()) {
                            $fileUrl = $rowPJ["PJ_Upload_Societe"];
                            $fileName = $rowPJ["Nom_PJ_Societe"];
                            ?>
                            <li>
                                <a href="<?php echo $fileUrl; ?>"><?php echo $fileName; ?></a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <?php
        } else {
            echo "<p>Aucune pièce jointe trouvée dans la table piece_jointe_societe pour l'ID_Societe = 1.</p>";
        }
        ?>

        <div class="company-details">
            <h3>Constatation du dossier de la société</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="Validation_ANOR_Societe">Validation :</label>
                    <select class="form-control" name="Validation_ANOR_Societe" id="Validation_ANOR_Societe">
                        <option value="0" <?php echo $row['Validation_ANOR_Societe'] == 0 ? 'selected' : ''; ?>>En attente</option>
                        <option value="1" <?php echo $row['Validation_ANOR_Societe'] == 1 ? 'selected' : ''; ?>>Validé</option>
                        <option value="2" <?php echo $row['Validation_ANOR_Societe'] == 2 ? 'selected' : ''; ?>>Refusé</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="Commentaire_ANOR">Commentaire :</label>
                    <textarea class="form-control" name="Commentaire_ANOR" id="Commentaire_ANOR" rows="3"><?php echo isset($row['Commentaire_ANOR']) ? $row['Commentaire_ANOR'] : ''; ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Valider</button>
            </form>
        </div>
    </div>
    <!-- Importation de jQuery et Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
