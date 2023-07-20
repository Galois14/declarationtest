<?php
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    redirigerVersPageAccueil();
}

// Vérifier si le formulaire de connexion a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $adresseEmail = $_POST['adresse_email'];
    $motDePasse = $_POST['mots_de_passe'];

    // Valider les données (vous pouvez ajouter des validations supplémentaires ici)

    // Vérifier les informations de connexion dans la base de données
    $connexion = seConnecterBaseDeDonnees();
    $utilisateur = verifierInformationsConnexion($connexion, $adresseEmail, $motDePasse);

    if ($utilisateur !== null) {
        // Connexion réussie, créer une session pour l'utilisateur
        creerSessionUtilisateur($utilisateur);

        // Rediriger en fonction de l'ID_Groupe de l'utilisateur
        redirigerSelonIDGroupe($utilisateur['ID_Groupe']);
    } else {
        // Identifiants de connexion invalides
        $messageErreur = 'Identifiants de connexion invalides';
    }

    // Fermer la connexion à la base de données
    fermerConnexionBaseDeDonnees($connexion);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Connexion</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 100px;
            padding: 20px;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }

        .login-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #333333;
        }

        .login-form .form-group label {
            font-weight: bold;
            color: #333333;
        }

        .login-form input[type="email"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .login-form .btn-login {
            width: 100%;
            margin-top: 20px;
            padding: 10px;
            background-color: #007bff;
            color: #ffffff;
            border: none;
            border-radius: 4px;
        }

        .login-form .btn-login:hover {
            background-color: #0056b3;
        }

        .login-form .signup-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #333333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1 class="mt-5 d-flex align-items-center justify-content-center">
            <img src="logo/mmrs.jfif" alt="Logo" height="100" class="mr-3">
            Connexion
        </h1>

            <?php if (isset($messageErreur)) : ?>
                <p class="error-message"><?php echo $messageErreur; ?></p>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="adresse_email">Adresse e-mail :</label>
                    <input type="email" class="form-control" name="adresse_email" required>
                </div>

                <div class="form-group">
                    <label for="mots_de_passe">Mot de passe :</label>
                    <input type="password" class="form-control" name="mots_de_passe" required>
                </div>

                <button type="submit" class="btn btn-login">Se connecter</button>
            </form>
            <a class="signup-link" href="inscription.php">S'inscrire</a>
        </div>
    </div>

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>



<?php
function redirigerVersPageAccueil() {
    header('Location: home.php');
    exit;
}

function seConnecterBaseDeDonnees() {
    // Remplacez les valeurs suivantes par vos propres informations de connexion à la base de données
    $serveur = 'localhost';
    $utilisateurDB = 'root';
    $motDePasseDB = 'root';
    $nomBaseDeDonnees = 'goldexport';

    $connexion = new mysqli($serveur, $utilisateurDB, $motDePasseDB, $nomBaseDeDonnees);
    if ($connexion->connect_error) {
        die('Erreur de connexion à la base de données : ' . $connexion->connect_error);
    }

    return $connexion;
}

function fermerConnexionBaseDeDonnees($connexion) {
    $connexion->close();
}

function verifierInformationsConnexion($connexion, $adresseEmail, $motDePasse) {
    $requete = $connexion->prepare('SELECT ID_user, Nom_user, ID_Groupe FROM Users WHERE mail_user = ? AND Mots_de_passe = ?');
    $requete->bind_param('ss', $adresseEmail, $motDePasse);
    $requete->execute();
    $resultat = $requete->get_result();

    if ($resultat->num_rows === 1) {
        return $resultat->fetch_assoc();
    }

    return null;
}

function creerSessionUtilisateur($utilisateur) {
    $_SESSION['loggedin'] = true;
    $_SESSION['ID_user'] = $utilisateur['ID_user'];
    $_SESSION['nom_utilisateur'] = $utilisateur['Nom_user'];
}

function redirigerSelonIDGroupe($idGroupe) {
    if ($idGroupe === 3) {
        header('Location: admin.php');
    } else {
        header('Location: home.php');
    }
    exit;
}
?>
