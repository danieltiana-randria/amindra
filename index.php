<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$dossier_config = 'config/';
$dossier_uploads = 'fichiers/';
$dossier_codes = 'codes/';
$fichier_config = $dossier_config . 'config.json';

if (!file_exists($dossier_config)) mkdir($dossier_config, 0755, true);
if (!file_exists($dossier_uploads)) mkdir($dossier_uploads, 0755, true);
if (!file_exists($dossier_codes)) mkdir($dossier_codes, 0755, true);

if (file_exists($fichier_config)) {
    $config_content = file_get_contents($fichier_config);
    $config = json_decode($config_content, true) ?: [];
} else {
    $config = [
        'admin_nom' => 'DANIEL TIANA',
        'message_bienvenue' => '2Amindra - simulation transfert de fichier',
        'codes_acces' => [],
        'code_admin' => 'ADMIN12345' 
    ];
    file_put_contents($fichier_config, json_encode($config, JSON_PRETTY_PRINT));
}

$adresse_serveur = $_SERVER['REMOTE_ADDR'];
$code_admin_special = $config['code_admin'] ?? 'ADMIN12345';


$est_admin = false;
if (isset($_SESSION['admin_connecte']) && $_SESSION['admin_connecte'] === true) {
    $est_admin = true;
}

if (isset($_POST['action']) && $_POST['action'] === 'connexion_visiteur' && isset($_POST['code_acces'])) {
    $code_saisi = trim($_POST['code_acces']);
    
    
    if ($code_saisi === $code_admin_special) {
        $_SESSION['admin_connecte'] = true;
        $_SESSION['code_utilise'] = $code_saisi;
        header('Location: index.php');
        exit;
    }
    
    
    $fichier_code = $dossier_codes . $code_saisi . '.json';
    if (file_exists($fichier_code)) {
        $infos_code = json_decode(file_get_contents($fichier_code), true);
        if (is_array($infos_code) && isset($infos_code['actif']) && $infos_code['actif']) {
            $expiration = $infos_code['date_expiration'] ?? date('Y-m-d H:i:s', strtotime('+30 days'));
            if (strtotime($expiration) > time()) {
                $_SESSION['visiteur_connecte'] = true;
                $_SESSION['code_utilise'] = $code_saisi;
                $_SESSION['expiration'] = time() + (30 * 24 * 60 * 60);
                $infos_code['derniere_utilisation'] = date('Y-m-d H:i:s');
                file_put_contents($fichier_code, json_encode($infos_code, JSON_PRETTY_PRINT));
                header('Location: index.php');
                exit;
            }
        }
    }
}

if (isset($_SESSION['expiration']) && $_SESSION['expiration'] < time()) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$fichiers = [];
if (file_exists($dossier_uploads)) {
    $fichiers = array_diff(scandir($dossier_uploads), ['.', '..']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amindra</title>
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
            transition: all 0.3s;
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

        .sidebar-header p {
            color: var(--gray);
            font-size: 0.9em;
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

        .status-badge.admin {
            background: var(--warning);
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

        .nav-item i {
            margin-right: 12px;
            font-size: 1.2em;
            width: 20px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: all 0.3s;
        }

        .content-header {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .content-header h2 {
            color: var(--secondary);
            margin-bottom: 10px;
            font-size: 1.8em;
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
        input[type="password"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        input:focus,
        textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn {
            padding: 2px 10px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #d35400;
            transform: translateY(-2px);
        }

        .btn-light {
            background: var(--light);
            color: var(--dark);
        }

        .btn-light:hover {
            background: #dde4e6;
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

        tr:hover {
            background: #f8f9fa;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }

        .upload-container {
            margin: 20px 0;
        }

        .progress-container {
            width: 100%;
            background: #f0f0f0;
            border-radius: 10px;
            height: 20px;
            margin: 10px 0;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(45deg, var(--primary), #2980b9);
            border-radius: 10px;
            width: 0%;
            transition: width 0.3s ease;
            position: relative;
        }

        .progress-text {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: bold;
            font-size: 12px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .upload-status {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 14px;
            color: var(--gray);
        }

        .upload-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid var(--primary);
        }

        .drop-zone {
            border: 2px dashed #bdc3c7;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            margin: 20px 0;
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: var(--primary);
            background: #f8f9fa;
        }

        .drop-zone i {
            font-size: 3em;
            color: #bdc3c7;
            margin-bottom: 15px;
        }

        .drop-zone:hover i {
            color: var(--primary);
        }

        .file-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: none;
        }

        .file-info.active {
            display: block;
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
            }
        }

        .menu-toggle {
            display: none;
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
    </style>
</head>

<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>

    <div class="sidebar">
        <div class="sidebar-header">
            <h1>Bienvenue</h1>
            <?php if ($est_admin): ?>
                <p><?php echo htmlspecialchars($config['admin_nom']); ?></p>
                <span class="status-badge admin">Administrateur</span>
            <?php elseif (isset($_SESSION['visiteur_connecte'])): ?>
                <span class="status-badge">Visiteur</span>
            <?php endif; ?>
        </div>

        <nav class="sidebar-nav">
            <?php if ($est_admin): ?>
                <a href="index.php" class="nav-item active">
                    Tableau de bord
                </a>
                <a href="gestion.php?action=codes" class="nav-item">
                    Codes
                </a>
                <a href="gestion.php?action=parametres" class="nav-item">
                    Paramètres
                </a>
                <a href="deconnexion_admin.php" class="nav-item">
                    Déconnexion Admin
                </a>
            <?php elseif (isset($_SESSION['visiteur_connecte'])): ?>
                <a href="index.php" class="nav-item active">
                    <i>📁</i> Lisitra
                </a>
                <a href="deconnexion.php" class="nav-item">
                    <i>🚪</i> Déconnexion
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h2>
                <?php 
                echo htmlspecialchars($config['message_bienvenue']);
                if(isset($_SESSION['visiteur_connecte'])){
                    echo " - Vous pouvez seulement ajouter";
                }
                ?>
            </h2>
            <p>Transferer</p>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <?php if ($_GET['message'] === 'upload_ok'): ?>
                <div class="alert alert-success">✅ vita eee</div>
            <?php elseif ($_GET['message'] === 'upload_erreur'): ?>
                <div class="alert alert-error">❌ tsy tafiditra</div>
            <?php elseif ($_GET['message'] === 'fichier_introuvable'): ?>
                <div class="alert alert-error">❌ Fichier tsita</div>
            <?php elseif ($_GET['message'] === 'supprime_ok'): ?>
                <div class="alert alert-success">✅ vofafa</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($est_admin): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Fichiers</h3>
                    <div class="stat-number"><?php echo count($fichiers); ?></div>
                    <?php
                    $taille_totale = 0;
                    foreach ($fichiers as $fichier) {
                        $chemin = $dossier_uploads . $fichier;
                        if (file_exists($chemin)) {
                            $taille_totale += filesize($chemin);
                        }
                    }
                    ?>
                    <p>Total : <?php echo number_format($taille_totale / 1048576, 2); ?> Mo</p>
                </div>

                <div class="stat-card">
                    <h3>Codes en marche</h3>
                    <?php
                    $codes_actifs = 0;
                    $codes_fichiers = glob($dossier_codes . '*.json');
                    foreach ($codes_fichiers as $fichier_code) {
                        $infos = json_decode(file_get_contents($fichier_code), true);
                        if (is_array($infos) && isset($infos['actif']) && $infos['actif']) {
                            $expiration = $infos['date_expiration'] ?? '';
                            if (strtotime($expiration) > time()) {
                                $codes_actifs++;
                            }
                        }
                    }
                    ?>
                    <div class="stat-number"><?php echo $codes_actifs; ?></div>
                    <p>Sur <?php echo count($codes_fichiers); ?> codes</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['visiteur_connecte']) && !$est_admin): ?>
            <!-- Formulaire de connexion -->
            <div class="content-card">
                <h3>Entrer le code d'abord</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="connexion_visiteur">
                    <div class="form-group">
                        <label>Entrer le code</label>
                        <input type="password" name="code_acces" required placeholder="Entrez votre code...">
                    </div>
                    <button type="submit" class="btn btn-primary">=> Entre la connexion</button>
                </form>
                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <small>💡 <strong>Admin :</strong> Utilisez le code spécial pour accéder au panel administrateur</small>
                </div>
            </div>
        <?php else: ?>
            <!-- Le reste de votre code pour l'interface utilisateur -->
            <div class="content-card">
                <h3>Ajout d'un fichier</h3>
                <div class="drop-zone" id="dropZone">
                    <i>📁</i>
                    <h4>Glisser ici</h4>
                    <p>OU clicker</p>
                    <input type="file" id="fileInput" style="display: none;" name="fichier">
                </div>

                <div class="file-info" id="fileInfo">
                    <strong>infos :</strong>
                    <span id="fileName"></span>
                    <span id="fileSize"></span>
                </div>

                <div class="upload-container" id="uploadContainer" style="display: none;">
                    <div class="upload-info">
                        <strong>Ajout ...</strong>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" id="progressBar">
                            <div class="progress-text" id="progressText">0%</div>
                        </div>
                    </div>
                    <div class="upload-status">
                        <span id="statusText">En preparation</span>
                        <span id="speedText"></span>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" action="upload.php" id="uploadForm">
                    <input type="file" name="fichier" id="hiddenFileInput" style="display: none;">
                    <button type="submit" class="btn btn-success" id="uploadButton" style="display: none;">
                        Envoyer
                    </button>
                </form>
            </div>

            <div class="content-card">
                <?php if (!empty($fichiers)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Taille</th>
                                    <th>Dates</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fichiers as $fichier):
                                    $chemin = $dossier_uploads . $fichier;
                                    if (!file_exists($chemin)) continue;

                                    $taille = filesize($chemin);
                                    $date_modif = filemtime($chemin);
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fichier); ?></td>
                                        <td>
                                            <?php
                                            if ($taille < 1024) echo $taille . ' o';
                                            elseif ($taille < 1048576) echo number_format($taille / 1024, 1) . ' Ko';
                                            else echo number_format($taille / 1048576, 1) . ' Mo';
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', $date_modif); ?></td>
                                        <td>
                                            <a href="download.php?fichier=<?php echo urlencode($fichier); ?>" class="btn btn-primary">
                                                telecharger
                                            </a>
                                            <?php if ($est_admin): ?>
                                                <a href="supprimer.php?fichier=<?php echo urlencode($fichier); ?>" class="btn btn-danger" onclick="return confirm('Supprimer ce fichier ?')">
                                                    supprimer
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; padding: 30px; color: var(--gray);">
                        Aucun fichiers
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        document.querySelector('input[name="code_acces"]')?.focus();

        function confirmerSuppression() {
            return confirm('tena sure amzany aa???');
        }

        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.menu-toggle');
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && e.target !== toggle) {
                sidebar.classList.remove('active');
            }
        });

        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const hiddenFileInput = document.getElementById('hiddenFileInput');
        const uploadButton = document.getElementById('uploadButton');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadContainer = document.getElementById('uploadContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const statusText = document.getElementById('statusText');
        const speedText = document.getElementById('speedText');
        const uploadForm = document.getElementById('uploadForm');

        let uploadStartTime;

        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', handleFileSelect);
        hiddenFileInput.addEventListener('change', handleFileSelect);

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropZone.classList.add('dragover');
        }

        function unhighlight() {
            dropZone.classList.remove('dragover');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFileSelect(e) {
            const files = e.target.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                displayFileInfo(file);
                hiddenFileInput.files = files;
                uploadButton.style.display = 'inline-flex';
            }
        }

        function displayFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = ` (${formatFileSize(file.size)})`;
            fileInfo.classList.add('active');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const file = hiddenFileInput.files[0];
            if (!file) return;

            uploadContainer.style.display = 'block';
            uploadButton.disabled = true;
            uploadStartTime = Date.now();

            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('fichier', file);

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    updateProgress(percentComplete, e.loaded, e.total);
                }
            }, false);

            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    updateProgress(100);
                    statusText.textContent = 'Action terminé';
                    setTimeout(() => {
                        window.location.href = 'index.php?message=upload_ok';
                    }, 1000);
                } else {
                    statusText.textContent = 'Erreur upload';
                    progressBar.style.background = 'var(--danger)';
                }
            });

            xhr.addEventListener('error', function() {
                statusText.textContent = 'Erreur de connection ou fichier trop volumeux';
                progressBar.style.background = 'var(--danger)';
            });

            xhr.open('POST', 'upload.php');
            xhr.send(formData);
        });

        function updateProgress(percent, loaded, total) {
            const percentRounded = Math.round(percent);
            progressBar.style.width = percent + '%';
            progressText.textContent = percentRounded + '%';

            if (percentRounded < 100) {
                statusText.textContent = `envoyer ${percentRounded}%`;
                if (loaded && total) {
                    const currentTime = Date.now();
                    const timeElapsed = (currentTime - uploadStartTime) / 1000;
                    const speed = loaded / timeElapsed;
                    speedText.textContent = `${formatFileSize(speed)}/s`;
                }
            } else {
                statusText.textContent = 'Finalisation';
                speedText.textContent = '';
            }
        }

        document.getElementById('classicForm')?.addEventListener('submit', function() {
            document.getElementById('uploadContainer').style.display = 'block';
        });
    </script>
</body>
</html>