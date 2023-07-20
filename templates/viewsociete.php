<!DOCTYPE html>
<html>
<head>
    <title>Ajouter une société et des pièces jointes</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-4">Ajouter une société et des pièces jointes</h1>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nom_societe">Nom de la société :</label>
                <input type="text" name="nom_societe" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="nif_stat_societe">NIF/STAT de la société :</label>
                <input type="text" name="nif_stat_societe" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="contact">Téléphone :</label>
                <input type="tel" name="contact" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="date_ouverture_societe">Date d'ouverture de la société :</label>
                <input type="date" name="date_ouverture_societe" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="directeur_societe">Directeur de la société :</label>
                <input type="text" name="directeur_societe" class="form-control" required>
            </div>
            <div class="form-group">
                <h3>Ajouter des fichiers joints</h3>
                <?php
                $nomsPieceJointe = ['Registre ES', 'LPIIIE', 'LPIIIC', 'bordereaux d\'achats', 'facture'];
                for ($i = 0; $i < 5; $i++) {
                    ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="form-group">
                                <label><?php echo $nomsPieceJointe[$i]; ?></label>
                                <input type="file" name="fichiers[]" multiple accept=".pdf, .jpg" class="form-control-file" required>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
