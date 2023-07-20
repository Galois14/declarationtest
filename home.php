<?php
session_start();
require_once 'vendor/autoload.php';
use setasign\Fpdi\Fpdi;

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Connexion à la base de données
$serveur = 'localhost';
$utilisateurDB = 'root';
$motDePasseDB = 'root';
$nomBaseDeDonnees = 'goldexport';

$connexion = new mysqli($serveur, $utilisateurDB, $motDePasseDB, $nomBaseDeDonnees);
if ($connexion->connect_error) {
    die('Erreur de connexion à la base de données : ' . $connexion->connect_error);
}

// Récupération de l'ID_Groupe
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

// Traitement du formulaire d'ajout de dossier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   

    // Récupérer l'ID de la société de l'utilisateur connecté
    $userID = $_SESSION['ID_user'];
    $requeteSociete = $connexion->prepare('SELECT ID_Societe FROM Users WHERE ID_user = ?');
    $requeteSociete->bind_param('i', $userID);
    $requeteSociete->execute();
    $resultatSociete = $requeteSociete->get_result();
    $rowSociete = $resultatSociete->fetch_assoc();
    $societeID = $rowSociete['ID_Societe'];

    // Récupérer les données du formulaire
    $numDeclaration = $_POST['num_declaration'];
    $dateDeclaration = $_POST['date_declaration'];
    $nombreColis = $_POST['nombre_colis'];
    $dateEmbarquement = $_POST['date_embarquement'];
    $quantiteExport = $_POST['quantite_export'];
    $valeurExport = $_POST['valeur_export'];
    $Unite = $_POST['unite'];
    $nomAcheteurImportateur = $_POST['nom_acheteur_importateur'];
    $adresseDestination = $_POST['adresse_destination'];
    $paysDestination = $_POST['pays_destination'];
    $numFactureExport = $_POST['num_facture_export'];
    $numLPIIIE = $_POST['num_lpiii_e'];

    // Insérer le dossier dans la table "Dossier"

    


    $requeteDossier = $connexion->prepare('INSERT INTO dossier (Num_Declaration, Date_Declaration, Nombre_Colis, Date_Embarquement, Quantite_Export, Valeur_Export, Unite, Nom_Acheteur_ou_Importateur, Adresse_Destination, Pays_Destination, Num_Facture_Export, Num_LPIIIE, Validation_DGAM_Dossier, ID_Societe) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)');
    if (!$requeteDossier) {
        die('Erreur de préparation de la requête : ' . $connexion->error);
    }

    $requeteDossier->bind_param('ssssssssssssi' , $numDeclaration, $dateDeclaration, $nombreColis, $dateEmbarquement, $quantiteExport, $valeurExport, $Unite, $nomAcheteurImportateur, $adresseDestination, $paysDestination, $numFactureExport, $numLPIIIE, $societeID);
    $resultatDossier = $requeteDossier->execute();
    if (!$resultatDossier) {
        die('Erreur lors de l\'exécution de la requête : ' . $requeteDossier->error);
    }

    // Récupérer l'ID du dossier inséré
    $idDossier = $requeteDossier->insert_id;

    $requeteDossier->close();

    // Récupérer la date d'aujourd'hui
    date_default_timezone_set('Europe/Moscow'); // Définir le fuseau horaire à Moscou
    $dateEnvoie = date('Y-m-d');

    // Remplir la table Envoyer
    $requeteEnvoyer = $connexion->prepare('INSERT INTO Envoyer (ID_user, ID_dossier, Date_Envoie) VALUES (?, ?, ?)');
    if (!$requeteEnvoyer) {
        die('Erreur de préparation de la requête : ' . $connexion->error);
    }

    $requeteEnvoyer->bind_param('sis', $userID, $idDossier, $dateEnvoie);
    $resultatEnvoyer = $requeteEnvoyer->execute();
    if (!$resultatEnvoyer) {
        die('Erreur lors de l\'exécution de la requête : ' . $requeteEnvoyer->error);
    }

    $requeteEnvoyer->close();


// Fonction pour fusionner les fichiers PDF
function mergePDFs($files) {
    $pdf = new \setasign\Fpdi\Fpdi();

    foreach ($files['tmp_name'] as $key => $tmpName) {
        if ($files['error'][$key] === UPLOAD_ERR_OK && $files['type'][$key] === 'application/pdf') {
            $pdf->setSourceFile($tmpName);
            $pageCount = $pdf->setSourceFile($tmpName);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplIdx = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($tplIdx); // Récupérer les dimensions de la page source
                $pdf->AddPage($size['orientation'], array($size['width'], $size['height'])); // Créer une nouvelle page avec les mêmes dimensions
                $pdf->useTemplate($tplIdx);
            }
        }
    }

    ob_start();
    $pdf->Output('F');
    $mergedPdf = ob_get_clean();

    return $mergedPdf;
}


// Traitement des fichiers joints
// Vérifier si le formulaire a été soumis
// if ((isset($_FILES['fichiers']) && !empty($_FILES['fichiers']['name'][0])) ) {

    $nomsPieceJointe2 = ['Dossier correspondant au numéro de déclaration', 
                        'Attestation de régularité fiscale', 
                        'Attestation de rapatriement de devise', 
                        'Attestation de situation contentieuse', 
                        'LPI à jour', 
                        'Facture d\'achats', 
                        'Facture proforma'];
    for ($i = 0; $i < count($nomsPieceJointe2); $i++) {
        // Fusionner les fichiers PDF
        $fileKey = "pdf_files{$i}";
        $dateKey = "date_pj{$i}";
        $nomsPieceJointe=$nomsPieceJointe2[$i];

        if (!empty($_FILES[$fileKey]['name'])) {
            $mergedPdf = mergePDFs($_FILES[$fileKey]);

            // Insérer les détails du fichier dans la table "Piece_jointe"
            $datePJ = $_POST[$dateKey];
            $sourceFile = 'doc.pdf';
            $nomFichier = "{$nomsPieceJointe}";
            $nouveauNomFichier = $nomFichier . '_' . $idDossier . '.pdf';
            $destinationFolder = 'uploads/';

            // Chemin complet vers le fichier de destination
            $destinationFile = $destinationFolder . $nouveauNomFichier;

            // Enregistrer le fichier fusionné dans la base de données
            $sql = 'INSERT INTO piece_jointe (Nom_piece_jointe, Date_PJ, PJ_Upload, ID_dossier) VALUES (?, ?, ?, ?)';
            $stmt = $connexion->prepare($sql);
            $stmt->bind_param("ssss", $nouveauNomFichier, $datePJ, $destinationFile, $idDossier);

            $result = $stmt->execute();

            if ($result) {
                // echo "Le fichier PDF fusionné a été enregistré dans la base de données.<br>";
            } else {
                // echo "Une erreur s'est produite lors de l'enregistrement du fichier dans la base de données.<br>";
            }
            $stmt->close();

            // Déplacer le fichier
            if (rename($sourceFile, $destinationFile)) {
                // echo "Le fichier a été déplacé avec succès vers $destinationFile.<br>";
            } else {
                // echo "Une erreur s'est produite lors du déplacement du fichier.<br>";
            }

            // echo "Le fichier PDF fusionné a été enregistré dans la base de données.<br>";
        }
    }
// }






}



$connexion->close();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Page d'accueil</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        /* .container {
            margin-top: 50px;
        } */

        .card {
            background-color: #fff;
            padding: 20px;
        }

        .card-title {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: bold;
        }

        .file-input-label {
            cursor: pointer;
        }

        .text-center {
            margin-top: 20px;
        }
    </style>
    <script>
    function afficherFichiersSelectionnes(input) {
        var files = input.files;
        var fileList = document.getElementById(input.id + '-list');
        var labelList = document.getElementById(input.id + '-label');
        

        fileList.innerHTML = '';

        for (var i = 0; i < files.length; i++) {
            var listItem = document.createElement('li');
            listItem.textContent = files[i].name;
            fileList.appendChild(listItem);

        }
        if (input.files && input.files.length > 0) {
            labelList.textContent = input.files.length + " fichier(s) sélectionné(s)";
        } else {
            labelList.textContent = "Choisir plusieur fichiers";
        }
        

    }
</script>
    
</head>
<body>
<?php include_once('header.php'); ?>
    <div class="container">
        
        <h1>Bienvenue, <?php echo $_SESSION['nom_utilisateur']; ?>!</h1>
        <?php

        // Récupérer l'attribut Validation_ANOR_Societe de la société de l'utilisateur (remplacez 'ID_UTILISATEUR' par la colonne appropriée de votre table utilisateur)
        // Remplacez par l'ID de l'utilisateur actuel
        $query = "SELECT s.Validation_ANOR_Societe
                  FROM societe AS s
                  JOIN users AS u ON s.ID_Societe = u.ID_Societe
                  WHERE u.ID_user = $userID";

        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $validationANOR = $row["Validation_ANOR_Societe"];
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
                echo '<div class="alert alert-warning" role="alert">Votre société est en attente de validation par ANOR.
                Une fois valider, vous pouvez envoyer votre dossier pour exportation ... </div>';
                break;
        }
    }

        ?>

        <div class="card">
            <div class="card-body">
                <h2 class="card-title">Ajouter un dossier</h2>
                <form method="POST" action="" id="monFormulaire" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="num_declaration">Numero de la declaration :</label>
                            <input type="text" name="num_declaration" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_declaration">Date du declaration :</label>
                            <input type="date" name="date_declaration" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="nombre_colis">Nombre du colis :</label>
                            <input type="number" name="nombre_colis" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_embarquement">Date d'embarquement :</label>
                            <input type="date" name="date_embarquement" class="form-control" required>
                        </div>
                    </div>
                </div>
                    <div class="form-group">
                        <label for="quantite_export">Quantite a export en gramme:</label>
                        <input type="text" name="quantite_export" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="valeur_export">Valeur de l'OR à exporter :</label>
                                <input type="number" name="valeur_export" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                            <label for="unite">Unité :</label>
                            <select name="unite" class="form-control" required>
                                <option value=""></option>
                                <option value="dollar">Dollar</option>
                                <option value="euro">Euro</option>
                            </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nom_acheteur_importateur">Nom et Prenom de l'acheteur ou de l'importateur :</label>
                        <input type="text" name="nom_acheteur_importateur" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="adresse_destination">Adresse du destination :</label>
                                <input type="text" name="adresse_destination" class="form-control" required>
                            </div>
                        </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pays_destination">Pays destination :</label>
                                    <input type="text" name="pays_destination" class="form-control" required>
                                </div>
                            </div>
                    </div>
                    <div class="form-group">
                        <label for="num_facture_export">Numero du facture de l'exportation :</label>
                        <input type="text" name="num_facture_export" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="onum_lpiii_e">Numero du LPIII E :</label>
                        <input type="text" name="num_lpiii_e" class="form-control" required>
                    </div>

                    <h3>Ajouter des fichiers joints</h3>
                    <div class="row">
                    <?php
                        $nomsPieceJointe = ['Dossier correspondant au numéro de déclaration', 
                                            'Attestation de régularité fiscale',
                                            'Attestation de rapatriement de devise',
                                            'Attestation de situation contentieuse',
                                            'LPI à jour',
                                            'Facture d\'achats',
                                            'Facture proforma'];

                        for ($i = 0; $i < count($nomsPieceJointe); $i++) {
                            $fileInputName = "pdf_files{$i}[]";
                            $dateInputName = "date_pj{$i}";

                            ?>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label><?php echo $nomsPieceJointe[$i]; ?></label>
                                            <div class="custom-file">
                                                <input type="file" id="<?php echo $fileInputName; ?>" class="custom-file-input" name="<?php echo $fileInputName; ?>" multiple onchange="afficherFichiersSelectionnes(this)" accept=".pdf, .jpg" required>
                                                <ul id="<?php echo $fileInputName; ?>-list"></ul>
                                                <label class="custom-file-label file-input-label" id="<?php echo $fileInputName; ?>-label">Choisir plusieur fichiers</label>
                     
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Date de la pièce jointe :</label>
                                            <input type="date" name="<?php echo $dateInputName; ?>" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>


                    <?php $disabled = ($validationANOR != 1) ? 'disabled' : ''; 
                    if ($disabled) {
                    echo '<div class="text-left">
                            <input type="submit" value="Ajouter le dossier" class="btn btn-primary" ' . $disabled . '>
                        </div>';
                } else {
                    echo '<div class="text-left">
                            <input type="submit" value="Ajouter le dossier" class="btn btn-primary">
                        </div>';
                }
                  ?>
                    <!-- <div class="text-left">
                        
                        <input type="submit" value="Ajouter le dossier" class="btn btn-primary">
                    </div> -->
                </form>
            </div>
        </div>

    </div>
    <?php include_once('footer.php'); ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script>
        // Custom file input label
        $(document).on('change', '.custom-file-input', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.file-input-label').text(fileName);
        });
    </script>

</body>
</html>

