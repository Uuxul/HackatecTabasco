<?php
session_start();

header('Cache-Control: no-store');

/* =========================================
   CONEXIÓN: USA LOS DATOS DE REGISTRO.PHP
========================================= */

$host = 'sql105.infinityfree.com';
$puerto = 3306;
$baseDatos = 'if0_43107497_kanan';
$usuarioBD = 'if0_43107497';
$contrasenaBD = 'GkhU6dxmVPFL';


/* =========================================
   SESIÓN
========================================= */

if (!empty($_SESSION['usuario_id'])) {
    header('Location: perfil.php');
    exit;
}

if (empty($_SESSION['login_token'])) {
    $_SESSION['login_token'] = bin2hex(random_bytes(24));
}

$error = '';
$correoFormulario = '';

/* =========================================
   INICIAR SESIÓN
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';

    if (is_string($correo)) {
        $correoFormulario = trim($correo);
    }

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['login_token'], $token)
    ) {
        $error = 'Recarga la página e inténtalo nuevamente.';

    } elseif (
        !is_string($correo) ||
        !is_string($password) ||
        strlen($correoFormulario) > 190 ||
        !filter_var($correoFormulario, FILTER_VALIDATE_EMAIL) ||
        $password === '' ||
        strlen($password) > 72
    ) {
        $error = 'Revisa tu correo y contraseña.';

    } elseif (
        ($_SESSION['login_intentos'] ?? 0) >= 5 &&
        time() - ($_SESSION['login_ultimo'] ?? 0) < 60
    ) {
        $error = 'Espera un minuto antes de volver a intentarlo.';

    } else {

        if (time() - ($_SESSION['login_ultimo'] ?? 0) >= 60) {
            $_SESSION['login_intentos'] = 0;
        }

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$puerto};dbname={$baseDatos};charset=utf8mb4",
                $usuarioBD,
                $contrasenaBD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            $consulta = $pdo->prepare(
                'SELECT id, password_hash
                 FROM usuarios
                 WHERE correo = ?
                 LIMIT 1'
            );

            $consulta->execute([$correoFormulario]);
            $usuario = $consulta->fetch();

            if (
                $usuario &&
                password_verify(
                    $password,
                    $usuario['password_hash']
                )
            ) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuario['id'];

                unset(
                    $_SESSION['login_intentos'],
                    $_SESSION['login_ultimo'],
                    $_SESSION['login_token']
                );

                session_write_close();

                header('Location: perfil.php');
                exit;

            } else {
                $_SESSION['login_intentos'] =
                    ($_SESSION['login_intentos'] ?? 0) + 1;

                $_SESSION['login_ultimo'] = time();

                $error = 'Correo o contraseña incorrectos.';
            }

        } catch (Throwable $e) {
            error_log(
                'KANAN: fallo de acceso. Código: ' . $e->getCode()
            );

            $error = 'No se pudo iniciar sesión. Revisa la conexión a MySQL.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >

    <meta name="theme-color" content="#062b50">

    <title>Iniciar sesión | KANAN</title>

    <!-- ESTILOS COMPARTIDOS -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- ICONOS DE LA BARRA DE NAVEGACIÓN -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        body.login-page {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            background: #f1f5fa;
            color: #173453;
            font-family: Arial, sans-serif;
            overflow-y: auto;
        }

        .login-page .app {
            width: 100%;
            max-width: 480px;
            min-height: 100vh;
            min-height: 100dvh;
            height: auto;
            margin: 0 auto;
            position: relative;
            overflow: visible;
        }

        .login-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
            padding-top: max(16px, env(safe-area-inset-top));
            background: #062b50;
            color: white;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .login-brand img {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .login-brand strong {
            font-size: 21px;
            letter-spacing: 2px;
        }

        .login-back {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            min-height: 44px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            flex-shrink: 0;
        }

        .login-main {
            width: 100%;
            padding: 24px 16px;
            padding-bottom: calc(
                var(--login-nav-height, 80px) + 24px
            );
        }

        .login-card {
            width: 100%;
            min-width: 0;
            background: white;
            padding: clamp(18px, 5vw, 26px);
            border: 1px solid #dce5ef;
            border-radius: 20px;
            box-shadow: 0 6px 24px rgba(6, 43, 80, 0.04);
        }

        .login-logo {
            display: block;
            width: 76px;
            height: 76px;
            margin: 0 auto 18px;
            border-radius: 18px;
            object-fit: contain;
        }

        .login-card h1 {
            margin: 0;
            text-align: center;
            font-size: clamp(23px, 6vw, 28px);
            line-height: 1.3;
        }

        .login-intro {
            margin: 12px 0 24px;
            text-align: center;
            color: #657b90;
            font-size: 14px;
            line-height: 1.6;
        }

        .login-form label {
            display: block;
            margin: 18px 0 9px;
            font-size: 14px;
            font-weight: bold;
        }

        .login-form input:not([type="hidden"]) {
            display: block;
            width: 100%;
            min-width: 0;
            min-height: 52px;
            padding: 14px;
            border: 1px solid #cbd8e5;
            border-radius: 11px;
            background: #fbfdff;
            color: #173453;
            font: 16px Arial, sans-serif;
        }

        .login-form input:focus {
            outline: 2px solid #0877c9;
            outline-offset: 2px;
        }

        .login-password {
            position: relative;
        }

        .login-password input {
            padding-right: 82px !important;
        }

        .login-toggle {
            position: absolute;
            top: 4px;
            right: 4px;
            width: auto;
            min-width: 70px;
            min-height: 44px;
            margin: 0;
            padding: 8px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #0877c9;
            font: 13px Arial, sans-serif;
            cursor: pointer;
        }

        .login-submit {
            display: block;
            width: 100%;
            min-height: 56px;
            margin-top: 24px;
            padding: 16px;
            border: 0;
            border-radius: 12px;
            background: #062b50;
            color: white;
            font: bold 16px Arial, sans-serif;
            cursor: pointer;
            touch-action: manipulation;
        }

        .login-error {
            margin: 18px 0 0;
            padding: 14px;
            border-radius: 10px;
            background: #fdecec;
            color: #a13535;
            font-size: 13px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .login-register {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #edf1f6;
            text-align: center;
        }

        .login-register p {
            margin: 0 0 8px;
            color: #657b90;
            font-size: 14px;
        }

        .login-register a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 48px;
            padding: 12px;
            border-radius: 10px;
            background: #e7f2ff;
            color: #17649f;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        /* BARRA INFERIOR ADAPTABLE */

        .login-page .bottom-nav {
            position: fixed;
            top: auto;
            bottom: 0;
            left: 50%;
            right: auto;
            transform: translateX(-50%);
            display: flex;
            align-items: stretch;
            justify-content: center;
            width: 100%;
            max-width: 480px;
            height: auto;
            margin: 0;
            padding: 8px 0;
            padding-bottom: max(8px, env(safe-area-inset-bottom));
            background: white;
            border-top: 1px solid #dce5ef;
            z-index: 1000;
        }

        .login-page .bottom-nav .nav-item {
            flex: 1;
            min-width: 0;
            min-height: 52px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 6px 4px;
            text-align: center;
            text-decoration: none;
            color: #657b90;
        }

        .login-page .bottom-nav .nav-item i {
            font-size: 20px;
        }

        .login-page .bottom-nav .nav-item span {
            font-size: 12px;
        }

        .login-page .bottom-nav .nav-item.active {
            color: #0877c9;
        }

        .login-page a:focus-visible,
        .login-page button:focus-visible {
            outline: 3px solid #e7ad45;
            outline-offset: 3px;
        }

        @media (max-width: 340px) {
            .login-main {
                padding-left: 12px;
                padding-right: 12px;
            }

            .login-card {
                padding: 18px 14px;
            }

            .login-brand strong {
                font-size: 19px;
            }
        }

        @media (max-height: 500px) and (orientation: landscape) {
            .login-main {
                padding-top: 14px;
            }

            .login-logo {
                width: 54px;
                height: 54px;
                margin-bottom: 12px;
            }
        }
    </style>
</head>

<body class="login-page">

<div class="app">

    <header class="login-header">
        <div class="login-brand">
            <img src="logo.jpeg" alt="Logo KANAN">
            <strong>KANAN</strong>
        </div>

        <a
            href="index.php"
            class="login-back"
            aria-label="Volver al inicio"
        >
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        </a>
    </header>

    <main class="login-main">
        <section class="login-card">

            <img
                src="logo.jpeg"
                alt=""
                class="login-logo"
                width="76"
                height="76"
            >

            <h1>Iniciar sesión</h1>

            <p class="login-intro">
                Entra para consultar tu perfil y tu ficha médica.
            </p>

            <form method="POST" class="login-form">

                <input
                    type="hidden"
                    name="token"
                    value="<?= htmlspecialchars(
                        $_SESSION['login_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <label for="correo">Correo electrónico</label>

                <input
                    id="correo"
                    name="correo"
                    type="email"
                    maxlength="190"
                    autocomplete="username"
                    inputmode="email"
                    autocapitalize="none"
                    spellcheck="false"
                    enterkeyhint="next"
                    placeholder="ejemplo@correo.com"
                    value="<?= htmlspecialchars(
                        $correoFormulario,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

                <label for="password">Contraseña</label>

                <div class="login-password">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        maxlength="72"
                        autocomplete="current-password"
                        enterkeyhint="go"
                        placeholder="Tu contraseña"
                        required
                    >

                    <button
                        type="button"
                        id="togglePassword"
                        class="login-toggle"
                        aria-controls="password"
                        aria-pressed="false"
                    >
                        Mostrar
                    </button>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="login-error" role="alert">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="login-submit">
                    Entrar a mi cuenta
                </button>

            </form>

            <div class="login-register">
                <p>¿No tienes cuenta?</p>
                <a href="registro.php">Crear mi cuenta</a>
            </div>

        </section>
    </main>

    <!-- NAVBAR -->
    <?php
    include __DIR__ . '/includes/navbar.php';
    ?>

</div>

<!-- SIDEBAR -->
<?php
include __DIR__ . '/includes/sidebar.php';
?>

<!-- MODAL -->
<?php
include __DIR__ . '/includes/modal.php';
?>

<script>
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', function () {
        const mostrar = password.type === 'password';

        password.type = mostrar ? 'text' : 'password';
        togglePassword.textContent = mostrar ? 'Ocultar' : 'Mostrar';
        togglePassword.setAttribute('aria-pressed', String(mostrar));
    });

    /* RESERVAR EL ESPACIO REAL DE LA BARRA INFERIOR */

    const navbar = document.querySelector('.bottom-nav');

    if (navbar) {
        function ajustarEspacioNavbar() {
            document.documentElement.style.setProperty(
                '--login-nav-height',
                navbar.getBoundingClientRect().height + 'px'
            );
        }

        ajustarEspacioNavbar();

        window.addEventListener('resize', ajustarEspacioNavbar);

        if ('ResizeObserver' in window) {
            new ResizeObserver(ajustarEspacioNavbar).observe(navbar);
        }

        /* MARCAR PERFIL COMO SECCIÓN ACTIVA */

        navbar.querySelectorAll('.nav-item').forEach(function (enlace) {
            const ruta = new URL(enlace.href, window.location.href).pathname;
            const esPerfil = ruta.endsWith('/perfil.php');

            enlace.classList.toggle('active', esPerfil);

            if (esPerfil) {
                enlace.setAttribute('aria-current', 'page');
            } else {
                enlace.removeAttribute('aria-current');
            }
        });
    }
</script>

</body>
</html>