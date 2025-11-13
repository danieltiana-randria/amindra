<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$adresse_serveur = $_SERVER['REMOTE_ADDR'];
$est_admin = ($adresse_serveur === '127.0.0.1' || $adresse_serveur === '::1');
if (!isset($_SESSION['visiteur_connecte']) && !$est_admin) {
    header('Location: index.php');
    exit;
}

$dossier_uploads = 'fichiers/';

if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
    $nom_fichier = basename($_FILES['fichier']['name']);
    $chemin_destination = $dossier_uploads . $nom_fichier;


    if (file_exists($chemin_destination)) {
        header('Location: index.php?message=existe_deja');
        exit;
    }

    if (move_uploaded_file($_FILES['fichier']['tmp_name'], $chemin_destination)) {
        header('Location: index.php?message=upload_ok');
    } else {
        header('Location: index.php?message=upload_erreur');
    }
} else {
    header('Location: index.php?message=upload_erreur');
}
