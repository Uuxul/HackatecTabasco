<?php
session_start();

/* =========================================
   SI YA TIENE SESIÓN, ABRIR EL INICIO
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    !empty($_SESSION['usuario_id'])
) {
    header('Location: index.php');
    exit;
}

/* =========================================
   DATOS DE CONEXIÓN
========================================= */

$host = 'HOST_MYSQL_DE_TU_PANEL';
$puerto = 3306;
$baseDatos = 'NOMBRE_COMPLETO_DE_TU_BASE';
$usuarioBD = 'if0_43107497';
$contrasenaBD = 'TU_CONTRASEÑA_MYSQL';

/* =========================================
   CONFIGURACIÓN
========================================= */

if (empty($_SESSION['registro_token'])) {
    $_SESSION['registro_token'] = bin2hex(random_bytes(24));
}

$gruposSanguineos = [
    'No lo sé',
    'A+', 'A-',
    'B+', 'B-',
    'AB+', 'AB-',
    'O+', 'O-'
];

function responderError($mensaje, $codigo = 400)
{
    http_response_code($codigo);

    echo json_encode([
        'ok' => false,
        'error' => $mensaje
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

function obtenerTexto($datos, $campo, $maximo, $obligatorio = false)
{
    $valor = $datos[$campo] ?? '';

    if (!is_string($valor)) {
        responderError('Revisa el campo: ' . $campo);
    }

    $valor = trim($valor);

    $longitud = function_exists('mb_strlen')
        ? mb_strlen($valor, 'UTF-8')
        : preg_match_all('/./us', $valor);

    if (
        $longitud > $maximo ||
        ($obligatorio && $valor === '')
    ) {
        responderError('Revisa el campo: ' . $campo);
    }

    return $valor;
}

/* =========================================
   GUARDAR CUENTA Y FICHA
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $datos = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($datos)) {
        responderError('Datos inválidos.');
    }

    $token = $datos['token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['registro_token'], $token)
    ) {
        responderError('Recarga la página e inténtalo nuevamente.', 403);
    }

    if (!empty($_SESSION['usuario_id'])) {
        responderError(
            'Ya tienes una sesión iniciada. Vuelve al inicio.',
            409
        );
    }

    /* DATOS PERSONALES */

    $nombre = obtenerTexto($datos, 'nombre', 120, true);
    $correo = obtenerTexto($datos, 'correo', 190, true);
    $telefono = obtenerTexto($datos, 'telefono', 20, true);

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        responderError('Escribe un correo válido.');
    }

    if (!preg_match('/^[0-9+() \-]{7,20}$/', $telefono)) {
        responderError('Escribe un teléfono válido.');
    }

    /* CONTRASEÑA */

    $password = $datos['password'] ?? '';
    $confirmacion = $datos['confirmacion'] ?? '';

    if (
        !is_string($password) ||
        !is_string($confirmacion)
    ) {
        responderError('La contraseña no es válida.');
    }

    $cantidadCaracteres = function_exists('mb_strlen')
        ? mb_strlen($password, 'UTF-8')
        : preg_match_all('/./us', $password);

    if ($cantidadCaracteres < 8 || strlen($password) > 72) {
        responderError(
            'Usa al menos 8 caracteres y no superes 72 bytes en la contraseña.'
        );
    }

    if ($password !== $confirmacion) {
        responderError('Las contraseñas no coinciden.');
    }

    /* FICHA MÉDICA */

    $fechaNacimiento = obtenerTexto(
        $datos,
        'fecha_nacimiento',
        10
    );

    if ($fechaNacimiento !== '') {
        $fecha = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $fechaNacimiento
        );

        if (
            !$fecha ||
            $fecha->format('Y-m-d') !== $fechaNacimiento ||
            $fechaNacimiento > date('Y-m-d')
        ) {
            responderError('Revisa la fecha de nacimiento.');
        }
    }

    $grupoSanguineo = obtenerTexto(
        $datos,
        'grupo_sanguineo',
        15
    );

    if (!in_array($grupoSanguineo, $gruposSanguineos, true)) {
        responderError('Selecciona un grupo sanguíneo válido.');
    }

    $alergias = obtenerTexto($datos, 'alergias', 1000);
    $enfermedades = obtenerTexto($datos, 'enfermedades', 1000);
    $medicamentos = obtenerTexto($datos, 'medicamentos', 1000);
    $observaciones = obtenerTexto($datos, 'observaciones', 1000);

    /* CONTACTO DE EMERGENCIA */

    $contactoNombre = obtenerTexto(
        $datos,
        'contacto_nombre',
        120
    );

    $contactoParentesco = obtenerTexto(
        $datos,
        'contacto_parentesco',
        60
    );

    $contactoTelefono = obtenerTexto(
        $datos,
        'contacto_telefono',
        20
    );

    if (
        $contactoTelefono !== '' &&
        !preg_match('/^[0-9+() \-]{7,20}$/', $contactoTelefono)
    ) {
        responderError('Revisa el teléfono del contacto de emergencia.');
    }

    if (
        ($contactoNombre !== '' && $contactoTelefono === '') ||
        ($contactoTelefono !== '' && $contactoNombre === '') ||
        (
            $contactoParentesco !== '' &&
            ($contactoNombre === '' || $contactoTelefono === '')
        )
    ) {
        responderError(
            'Completa el nombre y teléfono del contacto de emergencia.'
        );
    }

    /* AUTORIZACIÓN */

    $autoriza = ($datos['autoriza'] ?? false) === true;

    $tieneFicha =
        $fechaNacimiento !== '' ||
        $grupoSanguineo !== 'No lo sé' ||
        $alergias !== '' ||
        $enfermedades !== '' ||
        $medicamentos !== '' ||
        $observaciones !== '' ||
        $contactoNombre !== '' ||
        $contactoParentesco !== '' ||
        $contactoTelefono !== '';

    if ($tieneFicha && !$autoriza) {
        responderError(
            'Autoriza guardar la ficha o deja sus campos vacíos.'
        );
    }

    /* INSERTAR AMBAS TABLAS */

    $pdo = null;

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

        $pdo->beginTransaction();

        $consultaUsuario = $pdo->prepare(
            'INSERT INTO usuarios (
                nombre,
                correo,
                telefono,
                password_hash
            ) VALUES (?, ?, ?, ?)'
        );

        $consultaUsuario->execute([
            $nombre,
            $correo,
            $telefono,
            password_hash($password, PASSWORD_DEFAULT)
        ]);

        $usuarioId = $pdo->lastInsertId();

        $consultaFicha = $pdo->prepare(
            'INSERT INTO fichas_medicas (
                usuario_id,
                fecha_nacimiento,
                grupo_sanguineo,
                alergias,
                enfermedades,
                medicamentos,
                observaciones,
                contacto_nombre,
                contacto_parentesco,
                contacto_telefono,
                autoriza_uso_emergencia
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $consultaFicha->execute([
            $usuarioId,
            $fechaNacimiento === '' ? null : $fechaNacimiento,
            $grupoSanguineo === 'No lo sé' ? null : $grupoSanguineo,
            $alergias === '' ? null : $alergias,
            $enfermedades === '' ? null : $enfermedades,
            $medicamentos === '' ? null : $medicamentos,
            $observaciones === '' ? null : $observaciones,
            $contactoNombre === '' ? null : $contactoNombre,
            $contactoParentesco === '' ? null : $contactoParentesco,
            $contactoTelefono === '' ? null : $contactoTelefono,
            $autoriza ? 1 : 0
        ]);

        $pdo->commit();

        /* CONSERVAR SESIÓN PARA MI PERFIL */

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuarioId;

        session_write_close();

        echo json_encode([
            'ok' => true,
            'redirect' => 'index.php'
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (Throwable $error) {

        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if (
            $error instanceof PDOException &&
            ($error->errorInfo[1] ?? null) == 1062
        ) {
            responderError('Ese correo ya está registrado.', 409);
        }

        error_log(
            'KANAN: fallo de registro. Código: ' . $error->getCode()
        );

        responderError(
            'No se pudo guardar. Revisa la conexión y las tablas.',
            500
        );
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta name="theme-color" content="#062b50">

    <title>Crear cuenta | KANAN</title>

    <style>
        :root {
            --azul-oscuro: #062b50;
            --azul: #0877c9;
            --fondo: #f1f5fa;
            --texto: #173453;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            background: var(--fondo);
            color: var(--texto);
            font-family: Arial, sans-serif;
        }

        header {
            background: var(--azul-oscuro);
            padding: 16px;
            padding-top: max(16px, env(safe-area-inset-top));
        }

        .header-content {
            max-width: 620px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .brand strong {
            display: block;
            color: white;
            font-size: 23px;
            letter-spacing: 2px;
        }

        .brand small {
            display: block;
            color: #bad8f2;
            font-size: 10px;
            margin-top: 5px;
        }

        .back {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            min-height: 44px;
            color: white;
            text-decoration: none;
            font-size: 24px;
            border-radius: 10px;
            background: rgba(255,255,255,.1);
            flex-shrink: 0;
        }

        main {
            width: 100%;
            max-width: 620px;
            margin: auto;
            padding: 24px 16px;
        }

        h1 {
            margin: 10px 0;
            font-size: clamp(25px, 7vw, 31px);
        }

        .intro,
        .hint {
            color: #657b90;
            line-height: 1.6;
        }

        .intro {
            font-size: 14px;
        }

        .hint {
            font-size: 12px;
        }

        .card {
            min-width: 0;
            padding: 20px;
            margin: 18px 0;
            border: 1px solid #dce5ef;
            border-radius: 17px;
            background: white;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .step {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e8f3ff;
            color: var(--azul);
            font-size: 13px;
            font-weight: bold;
            flex-shrink: 0;
        }

        h2 {
            margin: 0;
            font-size: 17px;
            line-height: 1.4;
        }

        .field {
            margin-top: 17px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        input:not([type="checkbox"]),
        select,
        textarea {
            display: block;
            width: 100%;
            min-width: 0;
            min-height: 50px;
            padding: 13px;
            border: 1px solid #cbd8e5;
            border-radius: 11px;
            background: #fbfdff;
            color: var(--texto);
            font: 16px Arial, sans-serif;
        }

        textarea {
            min-height: 95px;
            resize: vertical;
            line-height: 1.5;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid var(--azul);
            outline-offset: 2px;
        }

        .consent {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 20px 0;
            color: #526b83;
            font-size: 13px;
            line-height: 1.6;
        }

        .consent input {
            width: 20px;
            height: 20px;
            margin-top: 3px;
            flex-shrink: 0;
        }

        button {
            width: 100%;
            min-height: 58px;
            padding: 17px;
            border: 0;
            border-radius: 13px;
            background: var(--azul-oscuro);
            color: white;
            font: bold 16px Arial, sans-serif;
            cursor: pointer;
            touch-action: manipulation;
        }

        button:disabled {
            opacity: .6;
            cursor: wait;
        }

        .result {
            padding: 16px;
            margin-top: 16px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .result:empty {
            display: none;
        }

        .result.loading {
            background: #e7f1fc;
            color: #185e97;
        }

        .result.error {
            background: #fdecec;
            color: #a13535;
        }

        .login-link {
            text-align: center;
            font-size: 14px;
            line-height: 1.6;
            margin-top: 22px;
        }

        .login-link a {
            color: var(--azul);
        }

        footer {
            padding: 20px 16px;
            padding-bottom: max(24px, env(safe-area-inset-bottom));
            text-align: center;
            color: #75889b;
            font-size: 12px;
        }

        @media (max-width: 340px) {
            main {
                padding: 20px 12px;
            }

            .card {
                padding: 15px;
            }

            .brand strong {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="header-content">
        <div class="brand">
            <img src="logo.jpeg" alt="Logo KANAN">

            <div>
                <strong>KANAN</strong>
                <small>REGISTRO CIUDADANO</small>
            </div>
        </div>

        <a href="index.php" class="back" aria-label="Volver al inicio">
            ←
        </a>
    </div>
</header>

<main>
    <h1>Crea tu cuenta</h1>

    <p class="intro">
        Completa tus datos. Los campos con * son obligatorios;
        la ficha médica y el contacto de emergencia son opcionales.
    </p>

    <form id="registroForm">

        <section class="card">
            <div class="section-title">
                <span class="step">1</span>
                <h2>Datos personales</h2>
            </div>

            <div class="field">
                <label for="nombre">Nombre completo *</label>
                <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    maxlength="120"
                    autocomplete="name"
                    required
                >
            </div>

            <div class="field">
                <label for="correo">Correo electrónico *</label>
                <input
                    id="correo"
                    name="correo"
                    type="email"
                    maxlength="190"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="field">
                <label for="telefono">Teléfono *</label>
                <input
                    id="telefono"
                    name="telefono"
                    type="tel"
                    maxlength="20"
                    autocomplete="tel"
                    required
                >
            </div>
        </section>

        <section class="card">
            <div class="section-title">
                <span class="step">2</span>
                <h2>Contraseña</h2>
            </div>

            <div class="field">
                <label for="password">Contraseña *</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    required
                >
            </div>

            <div class="field">
                <label for="confirmacion">Confirmar contraseña *</label>
                <input
                    id="confirmacion"
                    name="confirmacion"
                    type="password"
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    required
                >
            </div>
        </section>

        <section class="card">
            <div class="section-title">
                <span class="step">3</span>
                <h2>Ficha médica opcional</h2>
            </div>

            <p class="hint">
                Deja vacíos los datos que desconozcas.
                Esta información es declarada por ti.
            </p>

            <div class="field">
                <label for="fecha_nacimiento">Fecha de nacimiento</label>
                <input
                    id="fecha_nacimiento"
                    name="fecha_nacimiento"
                    type="date"
                    max="<?= date('Y-m-d') ?>"
                    autocomplete="bday"
                >
            </div>

            <div class="field">
                <label for="grupo_sanguineo">Grupo sanguíneo</label>

                <select id="grupo_sanguineo" name="grupo_sanguineo">
                    <?php foreach ($gruposSanguineos as $grupo): ?>
                        <option value="<?= htmlspecialchars(
                            $grupo,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">
                            <?= htmlspecialchars(
                                $grupo,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="alergias">Alergias</label>
                <textarea
                    id="alergias"
                    name="alergias"
                    maxlength="1000"
                    placeholder="Alergias conocidas"
                ></textarea>
            </div>

            <div class="field">
                <label for="enfermedades">
                    Enfermedades o condiciones relevantes
                </label>
                <textarea
                    id="enfermedades"
                    name="enfermedades"
                    maxlength="1000"
                ></textarea>
            </div>

            <div class="field">
                <label for="medicamentos">Medicamentos que utilizas</label>
                <textarea
                    id="medicamentos"
                    name="medicamentos"
                    maxlength="1000"
                    placeholder="Nombre y dosis, si los conoces"
                ></textarea>
            </div>

            <div class="field">
                <label for="observaciones">Otra información importante</label>
                <textarea
                    id="observaciones"
                    name="observaciones"
                    maxlength="1000"
                    placeholder="Necesidades de accesibilidad u otra información"
                ></textarea>
            </div>
        </section>

        <section class="card">
            <div class="section-title">
                <span class="step">4</span>
                <h2>Contacto de emergencia</h2>
            </div>

            <p class="hint">
                Si agregas un contacto, completa su nombre y teléfono.
            </p>

            <div class="field">
                <label for="contacto_nombre">Nombre del contacto</label>
                <input
                    id="contacto_nombre"
                    name="contacto_nombre"
                    type="text"
                    maxlength="120"
                >
            </div>

            <div class="field">
                <label for="contacto_parentesco">Parentesco o relación</label>
                <input
                    id="contacto_parentesco"
                    name="contacto_parentesco"
                    type="text"
                    maxlength="60"
                    placeholder="Madre, padre, pareja, amistad..."
                >
            </div>

            <div class="field">
                <label for="contacto_telefono">Teléfono del contacto</label>
                <input
                    id="contacto_telefono"
                    name="contacto_telefono"
                    type="tel"
                    maxlength="20"
                >
            </div>
        </section>

        <label class="consent">
            <input id="autoriza" name="autoriza" type="checkbox">

            <span>
                Autorizo guardar mi ficha para apoyar la atención
                de una emergencia. Puedo crear mi cuenta sin
                proporcionar estos datos.
            </span>
        </label>

        <button id="registrarButton" type="submit">
            Crear mi cuenta
        </button>

        <div
            id="resultado"
            class="result"
            role="status"
            aria-live="polite"
        ></div>
    </form>

    <p class="login-link">
        ¿Ya tienes cuenta?
        <a href="login.php">Iniciar sesión</a>
    </p>
</main>

<footer>
    KANAN · Tu seguridad, nuestra prioridad
</footer>

<script>
    const token = <?= json_encode(
        $_SESSION['registro_token']
    ) ?>;

    const form = document.getElementById('registroForm');
    const boton = document.getElementById('registrarButton');
    const resultado = document.getElementById('resultado');

    let guardado = false;

    function mostrarResultado(mensaje, tipo) {
        resultado.className = 'result ' + tipo;
        resultado.textContent = mensaje;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (guardado || boton.disabled) {
            return;
        }

        const datos = Object.fromEntries(
            new FormData(form).entries()
        );

        datos.token = token;
        datos.autoriza = document.getElementById('autoriza').checked;

        if (datos.password !== datos.confirmacion) {
            mostrarResultado(
                'Las contraseñas no coinciden.',
                'error'
            );
            return;
        }

        const camposFicha = [
            'fecha_nacimiento',
            'alergias',
            'enfermedades',
            'medicamentos',
            'observaciones',
            'contacto_nombre',
            'contacto_parentesco',
            'contacto_telefono'
        ];

        const tieneFicha =
            datos.grupo_sanguineo !== 'No lo sé' ||
            camposFicha.some(
                campo => String(datos[campo] || '').trim() !== ''
            );

        if (tieneFicha && !datos.autoriza) {
            mostrarResultado(
                'Autoriza guardar la ficha o deja sus campos vacíos.',
                'error'
            );
            return;
        }

        boton.disabled = true;
        boton.textContent = 'Guardando...';

        mostrarResultado('Creando tu cuenta...', 'loading');

        try {
            const respuesta = await fetch(window.location.pathname, {
                method: 'POST',
                credentials: 'same-origin',

                headers: {
                    'Content-Type': 'application/json'
                },

                body: JSON.stringify(datos)
            });

            let contenido;

            try {
                contenido = await respuesta.json();
            } catch {
                throw new Error(
                    'El servidor no devolvió una respuesta válida. ' +
                    'Revisa que el archivo se ejecute como PHP.'
                );
            }

            if (!respuesta.ok || !contenido.ok) {
                throw new Error(
                    contenido.error || 'No se pudo guardar.'
                );
            }

            guardado = true;

            // Abrir inicio automáticamente después del registro.
            window.location.replace('index.php');

        } catch (error) {
            mostrarResultado(error.message, 'error');

        } finally {
            boton.disabled = guardado;
            boton.textContent = guardado
                ? 'Abriendo inicio...'
                : 'Crear mi cuenta';
        }
    });
</script>

</body>
</html>