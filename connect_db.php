<?php 
//preparation du telechargement
try {
    $db = new PDO('mysql:host=localhost; dbname=goldexport', 'root','root');
} catch(PDOException $e) {
    die('Erreur:' .$e ->getMessage());
}
?>