<?php
/**
 * DEBUG LOGIN - Captura exactamente qué está llegando al servidor
 */

session_start();

// Capturar todo
$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'post_data' => $_POST,
    'get_data' => $_GET,
    'raw_post' => file_get_contents('php://input'),
    'headers' => getallheaders(),
    'session' => $_SESSION,
    'cookies' => $_COOKIE,
];

// Guardar en archivo
$logFile = dirname(__DIR__) . '/var/logs/login-debug.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

file_put_contents(
    $logFile,
    json_encode($debug, JSON_PRETTY_PRINT) . "\n\n",
    FILE_APPEND
);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug - NexoSupport</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .debug-info {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .debug-info h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
        }
        pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        .status {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Login Debug</h1>
        <p class="subtitle">Formulario de prueba para capturar datos de login</p>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="status <?= !empty($_POST['username']) && !empty($_POST['password']) ? 'success' : 'error' ?>">
                <?php if (!empty($_POST['username']) && !empty($_POST['password'])): ?>
                    ✓ Datos recibidos correctamente
                <?php else: ?>
                    ✗ Faltan datos en el POST
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario o Email:</label>
                <input type="text" id="username" name="username" required autofocus
                       value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>">
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required
                       value="<?= htmlspecialchars($_POST['password'] ?? 'admin123') ?>">
            </div>

            <button type="submit">Enviar y Capturar Datos</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="debug-info">
                <h2>📋 Datos Capturados</h2>
                <pre><?= htmlspecialchars(json_encode($debug, JSON_PRETTY_PRINT)) ?></pre>

                <h2 style="margin-top: 20px;">📝 Verificación</h2>
                <ul style="line-height: 2;">
                    <li>✓ Método: <strong><?= $debug['method'] ?></strong></li>
                    <li><?= !empty($debug['post_data']['username']) ? '✓' : '✗' ?> Username recibido:
                        <strong><?= htmlspecialchars($debug['post_data']['username'] ?? 'VACÍO') ?></strong>
                    </li>
                    <li><?= !empty($debug['post_data']['password']) ? '✓' : '✗' ?> Password recibido:
                        <strong><?= !empty($debug['post_data']['password']) ? 'SÍ (longitud: '.strlen($debug['post_data']['password']).')' : 'VACÍO' ?></strong>
                    </li>
                    <li>📄 Log guardado en: <code>var/logs/login-debug.log</code></li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="status info" style="margin-top: 20px;">
            <strong>ℹ️ Instrucciones:</strong><br>
            1. Llena el formulario con tus credenciales<br>
            2. Click en "Enviar y Capturar Datos"<br>
            3. Revisa los datos capturados abajo<br>
            4. Compara con lo que envía el formulario real de login
        </div>
    </div>
</body>
</html>
