<?php
session_start();

// Vérifier accès admin
$adresse_serveur = $_SERVER['REMOTE_ADDR'];
if (!($adresse_serveur === '127.0.0.1' || $adresse_serveur === '::1')) {
    header('Location: index.php');
    exit;
}

// Configuration
$dossier_config = 'config/';
$dossier_uploads = 'fichiers/';
$dossier_codes = 'codes/';
$fichier_config = $dossier_config . 'config.json';

// Charger config
$config = json_decode(file_get_contents($fichier_config), true);

// Statistiques
$fichiers = array_diff(scandir($dossier_uploads), ['.', '..']);
$total_fichiers = count($fichiers);
$taille_totale = 0;
foreach ($fichiers as $fichier) {
    $taille_totale += filesize($dossier_uploads . $fichier);
}

// Codes actifs
$codes_actifs = 0;
$codes_fichiers = glob($dossier_codes . '*.json');
foreach ($codes_fichiers as $fichier_code) {
    $infos = json_decode(file_get_contents($fichier_code), true);
    if ($infos['actif'] && strtotime($infos['expiration']) > time()) {
        $codes_actifs++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            color: #2c3e50;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .btn {
            display: block;
            padding: 20px;
            background: white;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background: #3498db;
            color: white;
            transform: translateY(-3px);
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Tableau de bord Administrateur</h1>
            <p>Gestion complète du système de transfert</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Fichiers stockés</h3>
                <div class="stat-number"><?php echo $total_fichiers; ?></div>
                <p>Total : <?php echo number_format($taille_totale / 1048576, 2); ?> Mo</p>
            </div>

            <div class="stat-card">
                <h3>Codes actifs</h3>
                <div class="stat-number"><?php echo $codes_actifs; ?></div>
                <p>Sur <?php echo count($codes_fichiers); ?> codes créés</p>
            </div>

            <div class="stat-card">
                <h3>Espace utilisé</h3>
                <div class="stat-number"><?php echo number_format($taille_totale / 1048576, 1); ?> Mo</div>
                <p>Stockage physique</p>
            </div>
        </div>

        <div class="actions">
            <a href="gestion.php?action=codes" class="btn">🔑 Gérer les codes d'accès</a>
            <a href="gestion.php?action=parametres" class="btn">⚙️ Paramètres du système</a>
            <a href="index.php" class="btn">📁 Interface fichiers</a>
            <a href="deconnexion.php" class="btn">🚪 Déconnexion admin</a>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="back-btn">← Retour à l'accueil</a>
        </div>
    </div>
</body>

</html>