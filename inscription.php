<?php
// Vérifier si le formulaire d'inscription a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $motDePasse = $_POST['mot_de_passe'];
    $societeID = $_POST['societe'];
    $passCode = $_POST['pass_code'];
    $contact = $_POST['contact'];

    // Valider les données (vous pouvez ajouter des validations supplémentaires ici)

    // Connexion à la base de données
    $connexion = new mysqli('localhost', 'root', 'root', 'goldexport');
    if ($connexion->connect_error) {
        die('Erreur de connexion à la base de données : ' . $connexion->connect_error);
    }

    // Vérifier si le pass_code est valide en le comparant avec la valeur de la table Societe
    $requetePassCode = $connexion->prepare('SELECT Pass_Code FROM Societe WHERE ID_Societe = ?');
    $requetePassCode->bind_param('s', $societeID);
    $requetePassCode->execute();
    $resultatPassCode = $requetePassCode->get_result();

    if ($resultatPassCode->num_rows > 0) {
        $rowPassCode = $resultatPassCode->fetch_assoc();
        $passCodeSociete = $rowPassCode['Pass_Code'];

        // Comparer les valeurs de pass_code
        if ($passCode !== $passCodeSociete) {
            $error_message = 'Le pass code de la société est incorrect. Veuillez réessayer.';
        } else {
            // Préparer et exécuter la requête d'insertion des données
            $requete = $connexion->prepare('INSERT INTO users (Nom_user, mail_user, Mots_de_passe, Contact_User, ID_Groupe, ID_Societe ) VALUES (?, ?, ?, ?, 1 , ?)');
            $requete->bind_param('sssss', $nom, $email, $motDePasse, $contact, $societeID);
            if ($requete->execute()) {
                // L'inscription a réussi
                echo 'Inscription réussie !';
                // Rediriger vers la page de connexion ou faire d'autres actions nécessaires
                header("Location: login.php");
            } else {
                // Une erreur s'est produite lors de l'inscription
                echo 'Erreur lors de l\'inscription : ' . $requete->error;
            }
            // Fermer la requête d'insertion des données
            $requete->close();
        }
    } else {
        echo 'Erreur : Société introuvable.';
        exit; // Arrêter l'exécution du script si la société n'est pas trouvée
    }

    // Fermer la requête pour vérifier le pass_code
    $requetePassCode->close();
    // Fermer la connexion à la base de données
    $connexion->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inscription</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">

    <style>
        .success-message {
            color: green;
        }

        .error-message {
            color: red;
        }
    </style>

    <script>
        function validatePassword() {
            var password = document.getElementById("mot_de_passe").value;
            var confirmPassword = document.getElementById("confirmation_mot_de_passe").value;
            if (password != confirmPassword) {
                alert("Le mot de passe et la confirmation du mot de passe ne correspondent pas.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <h1 class="text-center mb-4">
                    <img src="logo/mmrs.jfif" alt="Logo" height="100" class="mr-3">
                    Inscription
                </h1>
                <form method="POST" action="" onsubmit="return validatePassword();">
                    <div class="form-group">
                        <label for="nom">Nom et prénom(s) :</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email :</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="contact">Téléphone :</label>
                        <input type="tel" class="form-control" name="contact" required pattern="[0-9]{10}">
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe :</label>
                        <input type="password" class="form-control" name="mot_de_passe" id="mot_de_passe" required>
                    </div>

                    <div class="form-group">
                        <label for="confirmation_mot_de_passe">Confirmation du mot de passe :</label>
                        <input type="password" class="form-control" id="confirmation_mot_de_passe" required>
                    </div>

                    <div class="form-group">
                        <label for="societe">Société :</label>
                        <select class="form-control" name="societe" required>
                            <?php
                            // Connexion à la base de données
                            $connexion = new mysqli('localhost', 'root', 'root', 'goldexport');
                            if ($connexion->connect_error) {
                                die('Erreur de connexion à la base de données : ' . $connexion->connect_error);
                            }

                            $requete = $connexion->prepare('SELECT ID_Societe, Nom_Societe, Pass_Code FROM Societe');
                            $requete->execute();
                            $resultat = $requete->get_result();

                            // Parcourir les résultats et afficher les options du menu déroulant
                            while ($row = $resultat->fetch_assoc()) {
                                echo '<option value="' . $row['ID_Societe'] . '">' . $row['Nom_Societe'] . '</option>';
                            }

                            $requete->close();
                            $connexion->close();
                            ?>
                        </select>
                    </div>
                    <?php
                    // Afficher le message d'erreur s'il existe
                    if (isset($error_message)) {
                        echo '<p class="error-message">' . $error_message . '</p>';
                    }
                    ?>
                    <div class="form-group">
                        <label for="pass_code">Pass code de la société :</label>
                        <input type="password" class="form-control" name="pass_code" id="pass_code">
                    </div>
                    <p class="text-center mt-3">Votre société n'est pas dans la liste ? <a href="add_societe.php">Ajouter votre société ici</a></p>

                    <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
                </form>
                <p class="text-center mt-3">Déjà inscrit ? <a href="login.php">Connectez-vous</a></p>
            </div>
        </div>
    </div>

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
