<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$adresse_serveur = $_SERVER['REMOTE_ADDR'];
if (!($adresse_serveur === '127.0.0.1' || $adresse_serveur === '::1')) {
    header('Location: index.php');
    exit;
}

$dossier_uploads = 'fichiers/';
$fichier_a_supprimer = isset($_GET['fichier']) ? basename($_GET['fichier']) : '';
$chemin_complet = $dossier_uploads . $fichier_a_supprimer;

if ($fichier_a_supprimer && file_exists($chemin_complet) && is_file($chemin_complet)) {
    unlink($chemin_complet);
    header('Location: index.php?message=supprime_ok');
} else {
    header('Location: index.php?message=fichier_introuvable');
}
