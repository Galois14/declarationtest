
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

        // Requête pour récupérer les informations des dossiers
        // $sql = "SELECT * FROM societe S
        //         INNER JOIN dossier D ON S.ID_Societe = D.ID_Societe
        //         INNER JOIN piece_jointe_societe P ON D.ID_dossier = P.ID_Societe
        //         ORDER BY S.ID_societe";

        $sql = "SELECT * FROM societe";

        $result = $conn->query($sql);
        ?>
<!DOCTYPE html>
<html>
<head>
    <title>Liste des Sociétés</title>
    <!-- Importation de Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include_once('header.php'); ?>
    <div class="container">
        <h1 class="mt-4 mb-4">Liste des sociétés</h1>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nom de la société</th>
                        <th>Nom du gérant</th>
                        <th>Adresse</th>
                        <th>Date d'agrément</th>
                        <th>Statut Validation</th>
                        <th>Consulter</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <tr>
                            <td><?php echo $row['Nom_Societe']; ?></td>
                            <td><?php echo $row['Nom_gerant_societe']; ?></td>
                            <td><?php echo $row['Adresse_Societe']; ?></td>
                            <td><?php echo date('d F Y', strtotime($row['Date_Agrement'])); ?></td>
                            <td>
                                <?php
                                switch ($row['Validation_ANOR_Societe']) {
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

                            <td>
                                <?php if ($row['Validation_ANOR_Societe'] != 0) : ?>
                                    <a href="details_societe.php?societe=<?php echo $row['ID_Societe']; ?>" class="btn btn-secondary">Consulter</a>
                                <?php else : ?>
                                    <a href="consulter_societe.php?societe=<?php echo $row['ID_Societe']; ?>" class="btn btn-primary">Consulter</a>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php
                    }
                } else {
                    echo "Aucun dossier trouvé.";
                }
        
                $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
        <?php

        ?>
    </div>
</body>
</html>
