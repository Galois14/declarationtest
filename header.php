<?php
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

// Récupération de l'ID_Groupe de l'utilisateur
$userID = $_SESSION['ID_user'];
$requeteUsers = $conn->prepare('SELECT ID_Groupe FROM Users WHERE ID_user = ?');
$requeteUsers->bind_param('i', $userID);
$requeteUsers->execute();
$resultatUsers = $requeteUsers->get_result();
$rowUsers = $resultatUsers->fetch_assoc();
$groupeID = $rowUsers['ID_Groupe'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Formulaire attrayant avec Bootstrap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</head>
<body>
<header class="p-3 text-bg-dark">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <a href="/" class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none">
                <svg class="bi me-2" width="40" height="32" role="img" aria-label="Bootstrap"><use xlink:href="#bootstrap"></use></svg>
            </a>

            <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                <?php switch ($groupeID) {
                    case 1:
                        echo '<li><a href="home.php" class="nav-link px-2 text-secondary">Home</a></li>';
                        echo '<li><a href="dossieruser.php" class="nav-link px-2 text-white">Mes dossiers</a></li>';
                        echo '<li><a href="status_societe.php" class="nav-link px-3 text-white">Status</a></li>';
                        break;
                    case 2:
                        echo '<li><a href="admin.php" class="nav-link px-2 text-secondary">Home</a></li>';
                        break;
                    case 3:
                        echo '<li><a href="anor_home.php" class="nav-link px-2 text-secondary">Home</a></li>';
                        break;
                    case 4:
                        echo '<li><a href="home.php" class="nav-link px-2 text-secondary">Home</a></li>';
                        echo '<li><a href="dossieruser.php" class="nav-link px-2 text-white">Mes dossiers</a></li>';
                        break;
                } ?>
            </ul>

            <div class="text-end">
                <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { ?>
                    <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                <?php } else { ?>
                    <form action="logout.php" method="POST" class="d-inline">
                        <input type="submit" value="Déconnexion" class="btn btn-danger">
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>
</header>

<header class="py-3 mb-4 border-bottom">
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) { ?>
        <div class="container d-flex flex-wrap justify-content-between">
    <a href="/" class="d-flex align-items-center mb-3 mb-lg-0 link-body-emphasis text-decoration-none">
        <svg class="bi me-2" width="40" height="32"><use xlink:href="#bootstrap"></use></svg>
        <span class="fs-4"><img src="logo/mada.jfif" alt="Logo" height="100" class="mr-3"></span>
    </a>
    
    <?php 
        switch ($groupeID) {
            case 3:
                echo '<a href="/" class="d-flex align-items-center mb-3 mb-lg-0 link-body-emphasis text-decoration-none">';
                echo '<span class="fs-4"><img src="logo/anor.jfif" alt="Logo" height="100" class="mr-3"></span>';
                echo '</a>';
                break;

            default:
                echo '<a href="/" class="d-flex align-items-center mb-3 mb-lg-0 link-body-emphasis text-decoration-none">';
                echo '<span class="fs-4"><img src="logo/mmrs.jfif" alt="Logo" height="100" class="mr-3"></span>';
                echo '</a>';
                break;
        }
    ?>


    <!-- <a href="/" class="d-flex align-items-center mb-3 mb-lg-0 link-body-emphasis text-decoration-none">
    <span class="fs-4"><img src="logo/mmrs.jfif" alt="Logo" height="100" class="mr-3"></span>
    </a> -->

</div>

    <?php } ?>
</header>

</body>
</html>
