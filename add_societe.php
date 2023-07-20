<?php

require_once 'vendor/autoload.php';
use setasign\Fpdi\Fpdi;
// Vérifier si le formulaire d'ajout de société a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    // Récupérer les données du formulaire
    $nomSociete = $_POST['nom_societe'];
    $typeSociete = $_POST['type_societe'];
    $statSociete = $_POST['stat_societe'];
    $nifSociete = $_POST['nif_societe'];
    $numAttest = $_POST['num_attest_declaration'];
    $dateAttest = $_POST['date_attestation_declaration'];
    $numAgrement = $_POST['num_agrement'];
    $dateAgrement = $_POST['date_agrement'];
    $nomGerant = $_POST['nom_gerant_societe'];
    $contactSociete = $_POST['contact_societe'];
    $mailSociete = $_POST['mail_societe'];
    $adresseSociete = $_POST['adresse_societe'];
    $numCompte = $_POST['num_compte_bancaire'];
    //$validationANOR = $_POST['validation_anor'];
    $caracteres = '0123456789ABCDEFGHJKLMNOPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $passCode = substr(str_shuffle($caracteres), 0, 4);


    // Connexion à la base de données
    $connexion = new mysqli('localhost', 'root', 'root', 'goldexport');
    if ($connexion->connect_error) {
        die('Erreur de connexion à la base de données : ' . $connexion->connect_error);
    }

    // Préparer et exécuter la requête d'insertion des données dans la table "societe"
    $requeteSociete = $connexion->prepare('INSERT INTO societe (Nom_Societe, Type_Societe, STAT_Societe, NIF_Societe, Num_Attest_de_Declaration, Date_Attestation_Declaration, Num_Agrement, Date_Agrement, Nom_gerant_societe, Contacte_Societe, Mail_Societe, Adresse_Societe, Num_Compte_Bancaire, Pass_Code, Validation_ANOR_Societe) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
    $requeteSociete->bind_param('ssssssssssssss', $nomSociete, $typeSociete, $statSociete, $nifSociete, $numAttest, $dateAttest, $numAgrement, $dateAgrement, $nomGerant, $contactSociete, $mailSociete, $adresseSociete, $numCompte, $passCode);
    if ($requeteSociete->execute()) {
        // L'ajout de la société a réussi
        $societeID = $requeteSociete->insert_id; // Récupérer l'ID de la société ajoutée

        // Fermer la requête d'ajout de société
        $requeteSociete->close();

        $passCode2 = $connexion->query("SELECT Pass_Code FROM societe WHERE ID_Societe = $societeID");
        if ($passCode2) {
            $row = $passCode2->fetch_assoc(); // Récupère la ligne de résultat
        
            if ($row) {
                $passCode = $row['Pass_Code']; // Récupère la valeur de la colonne Pass_Code
                // Affiche un message de succès avec le Pass Code
                echo "La société a été ajoutée avec succès. Votre Pass Code est  " . htmlspecialchars($passCode);
            } else {
                echo "La société n'a pas été trouvée.";
            }
        } else {
            echo "Erreur lors de l'exécution de la requête SQL : " . $connexion->error;
        }
        

        
        
        // Traitement des fichiers joints

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


        $nomsPieceJointe2 = ['Document representant le STAT de la société',
        'Docuement representant le NIF de la société',
        'Attestation de declacation de la société',
        'Document representant l\'agrement de la société',
        'CIN du gerant de la société',
        'Registre ES',
        'LPIIIE', 
        'LPIIIC', 
        'bordereaux d\'achats', 
        'facture'];

        for ($i = 0; $i < count($nomsPieceJointe2); $i++) {
        // Fusionner les fichiers PDF
            $fileKey = "pdf_files{$i}";
            $nomsPieceJointe=$nomsPieceJointe2[$i];

            if (!empty($_FILES[$fileKey]['name'])) {
                $mergedPdf = mergePDFs($_FILES[$fileKey]);

                // Insérer les détails du fichier dans la table "Piece_jointe"
                // $datePJ = $_POST[$dateKey];
                $sourceFile = 'doc.pdf';
                $nomFichier = "{$nomsPieceJointe}";
                $nouveauNomFichier = $nomFichier . '_' . $societeID . '.pdf';
                $destinationFolder = 'files_societe/';

                // Chemin complet vers le fichier de destination
                $destinationFile = $destinationFolder . $nouveauNomFichier;

                // Enregistrer le fichier fusionné dans la base de données
                $sql = 'INSERT INTO Piece_jointe_societe (Nom_PJ_Societe, PJ_Upload_Societe, ID_Societe) VALUES (?, ?, ?)';
                $stmt = $connexion->prepare($sql);
                $stmt->bind_param("sss", $nouveauNomFichier, $destinationFile, $societeID);

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
        

    } else {
        // Une erreur s'est produite lors de l'ajout de la société
        echo 'Erreur lors de l\'ajout de la société : ' . $requeteSociete->error;
    }

    // Fermer la connexion à la base de données
    $connexion->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ajouter une société et des pièces jointes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
<header class="p-3 text-bg-dark">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <a href="/" class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none">
                <svg class="bi me-2" width="40" height="32" role="img" aria-label="Bootstrap"><use xlink:href="#bootstrap"></use></svg>
            </a>

            <div class="text-end">
            
                    <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="inscription.php" class="btn btn-warning">Sign-up</a>
            </div>
        </div>
    </div>
</header>

<header class="py-3 mb-4 border-bottom">
        <div class="container d-flex flex-wrap justify-content-between">
    <a href="/" class="d-flex align-items-center mb-3 mb-lg-0 link-body-emphasis text-decoration-none">
        <svg class="bi me-2" width="40" height="32"><use xlink:href="#bootstrap"></use></svg>
        <span class="fs-4"><img src="logo/mada.jfif" alt="Logo" height="100" class="mr-3"></span>
    </a>
    <a href="/" class="d-flex align-items-center mb-3 mb-lg-0 link-body-emphasis text-decoration-none">
    <span class="fs-4"><img src="logo/mmrs.jfif" alt="Logo" height="100" class="mr-3"></span>
    </a>
</div>

</header>
    <div class="container">
        <h1 class="mt-4">Ajouter une société et des pièces jointes</h1>
        <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <div class="row">
                <div class="col-md-8">
                    <label for="nom_societe">Nom de la société :</label>
                    <input type="text" name="nom_societe" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="type_societe">Type de la société :</label>
                    <input type="text" name="type_societe" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="form-row">
                <div class="col">
                    <label for="nif_societe">NIF de la société :</label>
                    <input type="text" name="nif_societe" class="form-control" required>
                </div>
                <div class="col">
                    <label for="stat_societe">STAT de la société :</label>
                    <input type="text" name="stat_societe" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="form-row">
                <div class="col-md-8">
                    <label for="num_attest_declaration">Numéro de l'attestation de la déclaration de la société :</label>
                    <input type="text" name="num_attest_declaration" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="date_attestation_declaration">Date de l'attestation de la déclaration de la société :</label>
                    <input type="date" name="date_attestation_declaration" class="form-control" required>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="num_agrement">Numero de l'agrement de la société:</label>
                    <input type="text" name="num_agrement" class="form-control" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="date_agrement">Date de l'agrement de la société :</label>
                    <input type="date" name="date_agrement" class="form-control" required>
                </div>
            </div>
        </div>
            <div class="form-group">
                <label for="nom_gerant_societe">Nom et prenoms du gerant de la société :</label>
                <input type="text" name="nom_gerant_societe" class="form-control" required>
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="contact_societe">Téléphone :</label>
                        <input type="tel" name="contact_societe" class="form-control" required>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label for="mail_societe">Email de la société :</label>
                        <input type="mail" name="mail_societe" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="adresse_societe">Adresse de la société :</label>
                <input type="text" name="adresse_societe" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="num_compte_bancaire">Numero du compte bancaire de la société :</label>
                <input type="text" name="num_compte_bancaire" class="form-control" required>
            </div>
            <div class="form-group">
    <h3>Ajouter des fichiers joints</h3>
    <div class="row">
    <?php
        $nomsPieceJointe = ['Document representant le STAT de la société',
                            'Docuement representant le NIF de la société',
                            'Attestation de declacation de la société',
                            'Document representant l\'agrement de la société',
                            'CIN du gerant de la société',
                            'Registre ES', 
                            'LPIIIE', 
                            'LPIIIC', 
                            'bordereaux d\'achats', 
                            'facture'];


        for ($i = 0; $i < count($nomsPieceJointe); $i++) {
        $fileInputName = "pdf_files{$i}[]";

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
                </div>
            </div>
        </div>
        <?php
        }
        ?>
        <?php
            if ($i % 2 != 0 || $i == count($nomsPieceJointe) - 1) {
                echo '</div>';
            }
        
        ?>
    </div>
</div>


            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
</body>
<?php include_once('footer.php'); ?>
</html>

