<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include_once('header.php'); ?>
    <div class="container">
        <h1>Status de votre société :</h1>

        <?php
        // Connexion à la base de données
        $servername = "localhost";
        $username = "root";
        $password = "root";
        $dbname = "goldexport";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Erreur de connexion à la base de données : " . $conn->connect_error);
        }

        // Récupérer l'attribut Validation_ANOR_Societe de la société de l'utilisateur (remplacez 'ID_UTILISATEUR' par la colonne appropriée de votre table utilisateur)
        $userID = $_SESSION['ID_user']; // Remplacez par l'ID de l'utilisateur actuel
        $query = "SELECT s.Validation_ANOR_Societe
                    FROM societe AS s
                    JOIN users AS u ON s.ID_Societe = u.ID_Societe
                    WHERE u.ID_user = $userID";

        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $validationANOR = $row["Validation_ANOR_Societe"];

            echo $validationANOR;
            echo $userID;

            switch ($validationANOR) {
                case '1':
                    // La société est validée par l'ANOR
                    echo '<div class="alert alert-success" role="alert">Votre société a été validée par l\'ANOR.</div>';
                    break;
                case '2':
                    // La société n'a pas encore été validée par l'ANOR
                    echo '<div class="alert alert-danger" role="alert">Votre société n\'a pas encore été validée par l\'ANOR.</div>';
                    break;
                default:
                    // L'utilisateur n'a pas de société associée
                    echo '<div class="alert alert-warning" role="alert">Votre société est en attente de validation par ANOR. Une fois validée, vous pouvez envoyer votre dossier pour exportation...</div>';
                    break;
            }
        }

        // Récupérer l'ID de la société de l'utilisateur connecté
        $userID = $_SESSION['ID_user'];
        $requeteSociete = $conn->prepare('SELECT ID_Societe FROM Users WHERE ID_user = ?');
        $requeteSociete->bind_param('i', $userID);
        $requeteSociete->execute();
        $resultatSociete = $requeteSociete->get_result();
        $rowSociete = $resultatSociete->fetch_assoc();
        $societeID = $rowSociete['ID_Societe'];

        // Récupération des données de la table "societe" pour l'ID_Societe donné
        $sql = "SELECT * FROM societe WHERE ID_Societe = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $societeID);
        $stmt->execute();
        $result = $stmt->get_result();

        // Récupération des pièces jointes de la table "piece_jointe_societe" pour l'ID_Societe donné
        $sqlPJ = "SELECT * FROM piece_jointe_societe WHERE ID_Societe = ?";
        $stmtPJ = $conn->prepare($sqlPJ);
        $stmtPJ->bind_param("i", $societeID);
        $stmtPJ->execute();
        $resultPJ = $stmtPJ->get_result();

        // Récupération des données de la table "verifier" pour l'ID_Societe donné
        $sqlV = "SELECT Verifier.*, users.Nom_user FROM societe 
                INNER JOIN Verifier ON societe.ID_Societe = Verifier.ID_Societe 
                INNER JOIN users ON Verifier.ID_user = users.ID_user 
                WHERE societe.ID_Societe = ?";
        $stmtV = $conn->prepare($sqlV);
        $stmtV->bind_param("i", $societeID);
        $stmtV->execute();
        $resultV = $stmtV->get_result();

        $conn->close();
        ?>

    </div>


    <div class="container">

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
        <h3>Commentaire :</h3>

        <div class="form-group">
            <?php
            while ($rowV = $resultV->fetch_assoc()) {
                $commentaire = nl2br(htmlspecialchars_decode($rowV['Commentaire_ANOR'], ENT_QUOTES));
                $nomUser = htmlspecialchars($rowV['Nom_user'], ENT_QUOTES);
                ?>
                <div class="company-details">
                    <p><?php echo $commentaire; ?></p>
                </div>
                <p>Vérifié par <strong><?php echo $nomUser; ?></strong></p>
                <?php
            }
            ?>
        </div>
    </div>
</div>
</body>

</html>
