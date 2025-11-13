<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

// Vérifier accès admin
$adresse_serveur = $_SERVER['REMOTE_ADDR'];
if (!($adresse_serveur === '127.0.0.1' || $adresse_serveur === '::1')) {
    header('Location: index.php');
    exit;
}

// Configuration
$dossier_config = 'config/';
$dossier_codes = 'codes/';
$fichier_config = $dossier_config . 'config.json';

if (file_exists($fichier_config)) {
    $config_content = file_get_contents($fichier_config);
    $config = json_decode($config_content, true) ?: [];
} else {
    $config = [
        'admin_nom' => 'Administrateur',
        'message_bienvenue' => 'sendeeeoooo',
        'codes_acces' => []
    ];
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'sauvegarder_parametres') {
            $config['admin_nom'] = trim($_POST['admin_nom'] ?? 'Administrateur');
            $config['message_bienvenue'] = trim($_POST['message_bienvenue'] ?? 'Bienvenue');
            file_put_contents($fichier_config, json_encode($config, JSON_PRETTY_PRINT));
            header('Location: gestion.php?action=parametres&message=sauvegarde_ok');
            exit;
        } elseif ($_POST['action'] === 'creer_code_auto') {
            // Génération automatique
            $nouveau_code = substr(md5(uniqid() . time()), 0, 8);
            $fichier_code = $dossier_codes . $nouveau_code . '.json';

            $infos_code = [
                'code' => $nouveau_code,
                'type' => 'auto',
                'date_creation' => date('Y-m-d H:i:s'),
                'date_expiration' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'derniere_utilisation' => null,
                'actif' => true
            ];

            file_put_contents($fichier_code, json_encode($infos_code, JSON_PRETTY_PRINT));
            header('Location: gestion.php?action=codes&message=code_auto_cree&code=' . $nouveau_code);
            exit;
        } elseif ($_POST['action'] === 'creer_code_manuel') {
            // Création manuelle
            $code_manuel = trim($_POST['code_manuel'] ?? '');
            $code_manuel = preg_replace('/[^a-zA-Z0-9]/', '', $code_manuel); // Nettoyer le code

            if (empty($code_manuel)) {
                header('Location: gestion.php?action=codes&message=code_vide');
                exit;
            }

            if (strlen($code_manuel) < 4) {
                header('Location: gestion.php?action=codes&message=code_trop_court');
                exit;
            }

            $fichier_code = $dossier_codes . $code_manuel . '.json';

            if (file_exists($fichier_code)) {
                header('Location: gestion.php?action=codes&message=code_existe');
                exit;
            }

            $infos_code = [
                'code' => $code_manuel,
                'type' => 'manuel',
                'date_creation' => date('Y-m-d H:i:s'),
                'date_expiration' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'derniere_utilisation' => null,
                'actif' => true
            ];

            file_put_contents($fichier_code, json_encode($infos_code, JSON_PRETTY_PRINT));
            header('Location: gestion.php?action=codes&message=code_manuel_cree&code=' . $code_manuel);
            exit;
        }
    }
}

// Actions GET pour codes
if (isset($_GET['action_code'])) {
    $code = $_GET['code'] ?? '';
    if ($code) {
        $fichier_code = $dossier_codes . $code . '.json';
        if (file_exists($fichier_code)) {
            $infos = json_decode(file_get_contents($fichier_code), true);

            if ($_GET['action_code'] === 'desactiver') {
                $infos['actif'] = false;
                file_put_contents($fichier_code, json_encode($infos, JSON_PRETTY_PRINT));
                header('Location: gestion.php?action=codes&message=code_desactive');
                exit;
            } elseif ($_GET['action_code'] === 'reactiver') {
                $infos['actif'] = true;
                $infos['date_expiration'] = date('Y-m-d H:i:s', strtotime('+30 days'));
                file_put_contents($fichier_code, json_encode($infos, JSON_PRETTY_PRINT));
                header('Location: gestion.php?action=codes&message=code_reactive');
                exit;
            } elseif ($_GET['action_code'] === 'supprimer') {
                unlink($fichier_code);
                header('Location: gestion.php?action=codes&message=code_supprime');
                exit;
            }
        }
    }
}

$action = $_GET['action'] ?? 'codes';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --white: #ffffff;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f8f9fa;
            display: flex;
            min-height: 100vh;
            color: var(--dark);
        }

        .sidebar {
            width: var(--sidebar-width);
            background: var(--white);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid var(--light);
            text-align: center;
        }

        .sidebar-header h1 {
            color: var(--primary);
            font-size: 1.4em;
            margin-bottom: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--success);
            color: white;
            border-radius: 20px;
            font-size: 0.8em;
            margin-top: 10px;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover,
        .nav-item.active {
            background: var(--light);
            border-left-color: var(--primary);
            color: var(--primary);
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
        }

        .content-header {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .content-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
        }

        .btn {
            padding: 5px 10px;
            margin-bottom: 2px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: var(--secondary);
            color: white;
            font-weight: 600;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }

        .tabs {
            display: flex;
            margin-bottom: 25px;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background: var(--light);
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab.active {
            background: var(--primary);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .code-badge {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .badge-auto {
            background: #d4edda;
            color: #155724;
        }

        .badge-manuel {
            background: #fff3cd;
            color: #856404;
        }

        .menu-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: var(--primary);
                color: white;
                border: none;
                padding: 10px;
                border-radius: 5px;
                cursor: pointer;
            }
        }
    </style>
</head>

<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>

    <div class="sidebar">
        <div class="sidebar-header">
            <h1>⚙️ Administration</h1>
            <p>Admib </p>
            <span class="status-badge">Admin</span>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item">
                Tableau de bord
            </a>
            <a href="gestion.php?action=codes" class="nav-item <?php echo $action === 'codes' ? 'active' : ''; ?>">
                codes
            </a>
            <a href="gestion.php?action=parametres" class="nav-item <?php echo $action === 'parametres' ? 'active' : ''; ?>">
                parametres
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="content-header">

            <p>ESPACES ADMIN</p>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert <?php echo strpos($_GET['message'], 'erreur') !== false ? 'alert-error' : 'alert-success'; ?>">
                <?php
                $messages = [
                    'sauvegarde_ok' => '✅ Fichier enregistré',
                    'code_auto_cree' => '✅ Code crée: <strong>' . ($_GET['code'] ?? '') . '</strong>',
                    'code_manuel_cree' => '✅ Action effectué avec success: <strong>' . ($_GET['code'] ?? '') . '</strong>',
                    'code_desactive' => '✅ Desactivé avec succes',
                    'code_reactive' => '✅ Code reactivé',
                    'code_supprime' => '✅ Supprimé avec success',
                    'code_vide' => '❌ Erreur! Code requis',
                    'code_trop_court' => '❌ Le code est trop cours',
                    'code_existe' => '❌ Code déjà existé'
                ];
                echo $messages[$_GET['message']] ?? 'Fin';
                ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <?php if ($action === 'codes'): ?>
                <h3>Fidirana</h3>

                <div class="tabs">
                    <button class="tab active" onclick="showTab('auto')">Auto</button>
                    <button class="tab" onclick="showTab('manuel')">Ampidirina</button>
                </div>

            
                <div id="tab-auto" class="tab-content active">
                    <p>Code randomisée</p>
                    <form method="POST" style="margin: 20px 0;">
                        <input type="hidden" name="action" value="creer_code_auto">
                        <button type="submit" class="btn btn-success">Generer</button>
                    </form>
                </div>

              
                <div id="tab-manuel" class="tab-content">
                    <p>Ajout nouvel code:</p>
                    <form method="POST" style="margin: 20px 0;">
                        <input type="hidden" name="action" value="creer_code_manuel">
                        <div class="form-group">
                            <label>code à ajouter:</label>
                            <input type="text" name="code_manuel" required
                                placeholder="apidiro ny kody"
                                pattern="[a-zA-Z0-9]{4,20}"
                                title="4 - 20 abc1234">
                        </div>
                        <button type="submit" class="btn btn-primary">vita</button>
                    </form>
                </div>

                <h4 style="margin-top: 40px;">Codes existants</h4>
                <?php
                $codes_fichiers = glob($dossier_codes . '*.json');
                if (!empty($codes_fichiers)):
                ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($codes_fichiers as $fichier_code):
                                    $infos = json_decode(file_get_contents($fichier_code), true);
                                    if (!is_array($infos)) continue;
                                    $code = $infos['code'] ?? 'N/A';
                                    $type = $infos['type'] ?? 'auto';
                                    $est_actif = $infos['actif'] ?? false;
                                    $expiration = $infos['date_expiration'] ?? '';
                                    $est_expire = strtotime($expiration) < time();
                                ?>
                                    <tr>
                                        <td><strong><?php echo $code; ?></strong></td>
                                        <td>
                                            <span class="code-badge <?php echo 'badge-' . $type; ?>">
                                                <?php echo $type === 'manuel' ? 'Manuel' : 'Auto'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $infos['date_creation'] ?? 'N/A'; ?></td>
                                        <td style="color: <?php echo $est_expire ? '#e74c3c' : '#27ae60'; ?>">
                                            <?php echo $expiration; ?>
                                            <?php if ($est_expire): ?> ⚠️<?php endif; ?>
                                        </td>
                                        <td><?php echo $infos['derniere_utilisation'] ?? 'Androany'; ?></td>
                                        <td>
                                            <span style="color: <?php echo $est_actif ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
                                                <?php echo $est_actif ? 'mande' : 'tsy mande'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($est_actif): ?>
                                                <a href="?action=codes&action_code=desactiver&code=<?php echo $code; ?>" class="btn btn-warning">vonoina</a>
                                            <?php else: ?>
                                                <a href="?action=codes&action_code=reactiver&code=<?php echo $code; ?>" class="btn btn-success">✅ alefa</a>
                                            <?php endif; ?>
                                            <a href="?action=codes&action_code=supprimer&code=<?php echo $code; ?>" class="btn btn-danger" onclick="return confirm('Tena fafana anN?')">fafana</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; padding: 30px; color: var(--gray);">
                        Tsisy kody
                    </p>
                <?php endif; ?>

            <?php elseif ($action === 'parametres'): ?>
                <h3>Parametres</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="sauvegarder_parametres">

                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="admin_nom" value="<?php echo htmlspecialchars($config['admin_nom']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>MEssage</label>
                        <input type="text" name="message_bienvenue" value="<?php echo htmlspecialchars($config['message_bienvenue']); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">terizina</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        function showTab(tabName) {
      
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

          
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }

        document.querySelector('input[name="code_manuel"]')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
        });
    </script>
</body>

</html>