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

        fileList.innerHTML = '';

        for (var i = 0; i < files.length; i++) {
            var listItem = document.createElement('li');
            listItem.textContent = files[i].name;
            fileList.appendChild(listItem);
        }
    }
    </script>
</head>
<body>
<h3>Ajouter des fichiers joints</h3>
    <div class="row">
    <?php
        $nomsPieceJointe = [
            'Document représentant le STAT de la société',
            'Document représentant le NIF de la société',
            'Attestation de déclaration de la société',
            'Document représentant l\'agrément de la société',
            'CIN du gérant de la société',
            'Registre ES', 
            'LPIIIE', 
            'LPIIIC', 
            'Bordereaux d\'achats', 
            'Facture'
        ];

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
                            <label class="custom-file-label file-input-label">Choisir un fichier</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
        if ($i % 2 != 0 || $i == count($nomsPieceJointe) - 1) {
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
