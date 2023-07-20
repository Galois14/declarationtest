<?php
class SocieteModel {
    private $connexion;

    public function __construct($connexion) {
        $this->connexion = $connexion;
    }

    public function ajouterSociete($nomSociete, $nifStatSociete, $dateOuvertureSociete, $directeurSociete, $passCode, $contact) {
        $requeteSociete = $this->connexion->prepare('INSERT INTO societe (Nom_Societe, NIF_STAT_Societe, Date_Ouverture_Societe, Directeur_Societe, Validation_ANOR, Pass_Code, Contacte_Societe) VALUES (?, ?, ?, ?, 0, ?, ?)');
        $requeteSociete->bind_param('ssssss', $nomSociete, $nifStatSociete, $dateOuvertureSociete, $directeurSociete, $passCode, $contact);
        if ($requeteSociete->execute()) {
            return $requeteSociete->insert_id;
        } else {
            return false;
        }
    }

    public function ajouterPieceJointe($nomFichier, $cheminDestination, $societeID) {
        $requetePJ = $this->connexion->prepare('INSERT INTO Piece_jointe_societe (Nom_PJ_Societe, PJ_Upload_Societe, ID_Societe) VALUES (?, ?, ?)');
        if (!$requetePJ) {
            die('Erreur de préparation de la requête : ' . $this->connexion->error);
        }
        $requetePJ->bind_param('ssi', $nomFichier, $cheminDestination, $societeID);
        $resultatPJ = $requetePJ->execute();
        if (!$resultatPJ) {
            die('Erreur lors de l\'exécution de la requête : ' . $requetePJ->error);
        }
        $requetePJ->close();
    }
}
?>
