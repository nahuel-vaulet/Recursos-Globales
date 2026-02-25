<?php
require_once 'config/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Simple auth (Production should use password_verify)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['password'] === $password) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'comprador') {
            header('Location: views/dashboard/index.php');
        } else {
            header('Location: views/requests/my_requests.php');
        }
        exit;
    } else {
        $error = 'Credenciales incorrectas (Demo: juan@empresa.com / 1234)';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Módulo de Compras</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--primary-light);
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .demo-credentials {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            background: var(--bg-body);
            padding: 0.75rem;
            border-radius: var(--radius-sm);
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <h1>Módulo de Compras</h1>
            <p>Ingrese a su cuenta</p>
        </div>

        <?php if ($error): ?>
            <div style="color: var(--danger-color); margin-bottom: 1rem; text-align: center; font-size: 0.875rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required placeholder="correo@empresa.com">
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                Ingresar
            </button>
        </form>

        <div class="demo-credentials">
            <strong>Usuarios Demo:</strong><br>
            Comprador: ana@empresa.com / 1234<br>
            Solicitante: juan@empresa.com / 1234
        </div>
    </div>
</body>

</html>