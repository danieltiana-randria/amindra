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
$fichier_demande = isset($_GET['fichier']) ? basename($_GET['fichier']) : '';
$chemin_complet = $dossier_uploads . $fichier_demande;

if ($fichier_demande && file_exists($chemin_complet) && is_file($chemin_complet)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fichier_demande . '"');
    header('Content-Length: ' . filesize($chemin_complet));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($chemin_complet);
    exit;
} else {
    header('Location: index.php?message=fichier_introuvable');
}
