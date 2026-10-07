<?php
session_start();

/* =========================================
   CONFIGURACIÓN
========================================= */

if (empty($_SESSION['reporte_token'])) {
    $_SESSION['reporte_token'] = bin2hex(random_bytes(24));
}

$categorias = [
    'Personas sospechosas',
    'Vehículos sospechosos',
    'Carreras clandestinas de motos',
    'Carreras clandestinas de autos',
    'Alteración del orden público',
    'Vandalismo',
    'Otro incidente'
];

/* =========================================
   GUARDAR REPORTE
   No requiere MySQL.
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    function responderError($mensaje, $codigo = 400)
    {
        http_response_code($codigo);

        echo json_encode(
            ['error' => $mensaje],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

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
        !hash_equals($_SESSION['reporte_token'], $token)
    ) {
        responderError(
            'La sesión expiró. Recarga la página.',
            403
        );
    }

    $categoria = $datos['categoria'] ?? '';

    if (!in_array($categoria, $categorias, true)) {
        responderError('Selecciona el tipo de reporte.');
    }

    if (
        !is_string($datos['descripcion'] ?? null) ||
        !is_string($datos['direccion'] ?? null)
    ) {
        responderError('Completa la descripción y dirección.');
    }

    $descripcion = trim($datos['descripcion']);
    $direccion = trim($datos['direccion']);

    if (
        strlen($descripcion) < 20 ||
        strlen($descripcion) > 8000
    ) {
        responderError(
            'La descripción debe tener entre 20 y 2000 caracteres.'
        );
    }

    if (
        strlen($direccion) < 5 ||
        strlen($direccion) > 1200
    ) {
        responderError('Escribe una dirección válida.');
    }

    $ubicacion = $datos['ubicacion'] ?? null;

    if ($ubicacion !== null) {
        if (
            !is_array($ubicacion) ||
            !is_numeric($ubicacion['lat'] ?? null) ||
            !is_numeric($ubicacion['lng'] ?? null)
        ) {
            responderError('La ubicación es inválida.');
        }

        $latitud = (float) $ubicacion['lat'];
        $longitud = (float) $ubicacion['lng'];

        if (
            !is_finite($latitud) ||
            !is_finite($longitud) ||
            $latitud < -90 ||
            $latitud > 90 ||
            $longitud < -180 ||
            $longitud > 180
        ) {
            responderError('Las coordenadas son inválidas.');
        }

        $ubicacion = [
            'lat' => $latitud,
            'lng' => $longitud
        ];
    }

    if (
        time() - ($_SESSION['ultimo_reporte'] ?? 0) < 15
    ) {
        responderError(
            'Espera unos segundos antes de enviar otro reporte.',
            429
        );
    }

    $folio = 'KAN-' .
        gmdate('Ymd') . '-' .
        strtoupper(bin2hex(random_bytes(4)));

    $reporte = [
        'folio' => $folio,
        'fecha' => gmdate('c'),
        'categoria' => $categoria,
        'descripcion' => $descripcion,
        'direccion' => $direccion,
        'ubicacion' => $ubicacion,
        'estado' => 'Registrado'
    ];

    /*
     * Se guardan líneas JSON después de un encabezado PHP.
     * Ese encabezado impide leer los datos desde el navegador
     * cuando Apache está configurado para ejecutar PHP.
     */
    $ruta = __DIR__ . '/reportes-datos.php';

    $archivo = @fopen($ruta, 'c+');

    if (!$archivo) {
        responderError(
            'No se pudo guardar. Revisa los permisos de la carpeta.',
            500
        );
    }

    if (!flock($archivo, LOCK_EX)) {
        fclose($archivo);

        responderError(
            'No se pudo guardar. Inténtalo nuevamente.',
            500
        );
    }

    $guardado = true;

    if (fstat($archivo)['size'] === 0) {
        $encabezado = "<?php exit; ?>\n";

        $guardado = fwrite(
            $archivo,
            $encabezado
        ) === strlen($encabezado);
    }

    if ($guardado) {
        fseek($archivo, 0, SEEK_END);

        $linea = json_encode(
            $reporte,
            JSON_UNESCAPED_UNICODE
        ) . "\n";

        $guardado = fwrite(
            $archivo,
            $linea
        ) === strlen($linea);
    }

    fflush($archivo);
    flock($archivo, LOCK_UN);
    fclose($archivo);

    if (!$guardado) {
        responderError(
            'No se pudo completar el guardado.',
            500
        );
    }

    $_SESSION['ultimo_reporte'] = time();

    echo json_encode(
        [
            'ok' => true,
            'folio' => $folio
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
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

    <title>Crear reporte | KANAN</title>

    <!-- LEAFLET -->
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>
        :root {
            --azul-oscuro: #062b50;
            --azul: #0877c9;
            --fondo: #f1f5fa;
            --texto: #173453;
            --secundario: #64788e;
            --borde: #dce5ef;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--fondo);
            color: var(--texto);
            font-family: Arial, sans-serif;
        }

        button,
        input,
        textarea {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        .header {
            background: var(--azul-oscuro);
            padding: 18px 20px;
        }

        .header-content {
            max-width: 650px;
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
        }

        .brand img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            border-radius: 14px;
        }

        .brand strong {
            display: block;
            color: white;
            font-size: 23px;
            letter-spacing: 3px;
        }

        .brand small {
            display: block;
            color: #bad8f2;
            font-size: 10px;
            letter-spacing: 1.5px;
            margin-top: 5px;
        }

        .back-button {
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.09);
        }

        .content {
            max-width: 650px;
            margin: auto;
            padding: 28px 18px;
        }

        .eyebrow {
            color: var(--azul);
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.5px;
        }

        h1 {
            font-size: 29px;
            margin: 10px 0;
        }

        .intro {
            color: var(--secundario);
            font-size: 14px;
            line-height: 1.6;
        }

        .notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #e7eff9;
            border-left: 4px solid var(--azul);
            border-radius: 12px;
            padding: 16px;
            margin: 22px 0;
        }

        .notice i {
            color: var(--azul);
            margin-top: 3px;
        }

        .notice strong {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .notice p {
            margin: 0;
            color: #526b83;
            font-size: 12px;
            line-height: 1.6;
        }

        .card {
            background: white;
            border: 1px solid var(--borde);
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 5px 18px rgba(6, 43, 80, 0.03);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .step {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 29px;
            height: 29px;
            flex-shrink: 0;
            background: #e7f2ff;
            color: var(--azul);
            border-radius: 50%;
            font-size: 13px;
            font-weight: bold;
        }

        h2 {
            font-size: 16px;
            margin: 0;
        }

        .categories {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .category {
            position: relative;
        }

        .category input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
        }

        .category-box {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 76px;
            padding: 14px;
            border: 1px solid var(--borde);
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }

        .category-box i {
            color: #5480aa;
            font-size: 20px;
            flex-shrink: 0;
        }

        .category-box span {
            font-size: 12px;
            font-weight: bold;
            line-height: 1.5;
        }

        .category input:checked + .category-box {
            background: #eaf4ff;
            border-color: var(--azul);
            box-shadow: inset 0 0 0 1px var(--azul);
        }

        .category input:checked + .category-box i {
            color: var(--azul);
        }

        .category input:focus-visible + .category-box {
            outline: 3px solid #e7ad45;
            outline-offset: 3px;
        }

        .field-label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin: 15px 0 9px;
        }

        textarea,
        input[type="text"] {
            width: 100%;
            color: var(--texto);
            background: #fbfdff;
            border: 1px solid #ccd9e6;
            border-radius: 12px;
            padding: 14px;
            font-size: 14px;
        }

        textarea {
            min-height: 145px;
            resize: vertical;
            line-height: 1.6;
        }

        textarea:focus,
        input[type="text"]:focus {
            outline: 2px solid var(--azul);
            outline-offset: 2px;
        }

        .counter {
            text-align: right;
            margin-top: 7px;
            font-size: 11px;
            color: var(--secundario);
        }

        .hint {
            color: var(--secundario);
            font-size: 12px;
            line-height: 1.6;
        }

        .location-button,
        .remove-location {
            width: 100%;
            min-height: 48px;
            padding: 14px;
            border: 0;
            border-radius: 12px;
            background: #eaf3fc;
            color: #17649f;
            font-size: 13px;
            font-weight: bold;
        }

        .location-button i {
            margin-right: 8px;
        }

        .remove-location {
            background: #fff0f0;
            color: #a83c3c;
            margin-bottom: 12px;
        }

        #map {
            height: 210px;
            border-radius: 12px;
            background: #edf2f7;
            z-index: 0;
        }

        .consent {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 4px;
            margin: 18px 0;
            color: #5a7087;
            font-size: 12px;
            line-height: 1.6;
        }

        .consent input {
            margin-top: 4px;
            flex-shrink: 0;
        }

        .submit-button {
            width: 100%;
            min-height: 58px;
            padding: 18px;
            background: var(--azul-oscuro);
            color: white;
            border: 0;
            border-radius: 14px;
            font-weight: bold;
            font-size: 15px;
        }

        .submit-button i {
            margin-right: 9px;
        }

        button:disabled {
            opacity: 0.55;
            cursor: wait;
        }

        button:focus-visible,
        a:focus-visible {
            outline: 3px solid #e7ad45;
            outline-offset: 3px;
        }

        .result {
            margin-top: 18px;
            padding: 18px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .result.success {
            background: #e4f5ec;
            color: #176747;
        }

        .result.error {
            background: #fdecec;
            color: #a13535;
        }

        .result.loading {
            background: #e8f1fb;
            color: #185e97;
        }

        .result:empty {
            display: none;
        }

        footer {
            text-align: center;
            color: #75889b;
            font-size: 11px;
            padding: 12px 18px 28px;
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 360px) {
            .categories {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 17px;
            }

            h1 {
                font-size: 25px;
            }

            .brand img {
                width: 46px;
                height: 46px;
            }

            .brand strong {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

<!-- HEADER -->
<header class="header">
    <div class="header-content">

        <div class="brand">
            <img
                src="logo.jpeg"
                alt="Logo de KANAN"
                width="56"
                height="56"
            >

            <div>
                <strong>KANAN</strong>
                <small>REPORTE CIUDADANO</small>
            </div>
        </div>

        <a
            href="index.php"
            class="back-button"
            aria-label="Volver al inicio"
        >
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        </a>

    </div>
</header>

<main class="content">

    <span class="eyebrow">
        AYÚDANOS A CUIDAR TU COMUNIDAD
    </span>

    <h1>Crear reporte</h1>

    <p class="intro">
        Describe lo que observaste y señala dónde ocurrió.
    </p>

    <div class="notice">
        <i
            class="fa-solid fa-circle-info"
            aria-hidden="true"
        ></i>

        <div>
            <strong>¿Hay peligro inmediato?</strong>

            <p>
                Usa la opción de emergencia de KANAN.
                Este formulario es de demostración y no está
                conectado con la policía.
            </p>
        </div>
    </div>

    <form id="reportForm">

        <!-- CATEGORÍAS -->
        <section class="card">

            <div class="section-title">
                <span class="step">1</span>
                <h2>¿Qué deseas reportar?</h2>
            </div>

            <div class="categories">

                <?php
                $iconos = [
                    'fa-user',
                    'fa-truck',
                    'fa-motorcycle',
                    'fa-car',
                    'fa-volume-high',
                    'fa-hammer',
                    'fa-circle-exclamation'
                ];

                foreach ($categorias as $indice => $categoria):
                ?>

                    <label class="category">
                        <input
                            type="radio"
                            name="categoria"
                            value="<?= htmlspecialchars(
                                $categoria,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >

                        <span class="category-box">
                            <i
                                class="fa-solid <?= $iconos[$indice] ?>"
                                aria-hidden="true"
                            ></i>

                            <span>
                                <?= htmlspecialchars(
                                    $categoria,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>
                        </span>
                    </label>

                <?php endforeach; ?>

            </div>

        </section>

        <!-- DESCRIPCIÓN -->
        <section class="card">

            <div class="section-title">
                <span class="step">2</span>
                <h2>Describe el incidente</h2>
            </div>

            <label
                for="descripcion"
                class="field-label"
            >
                ¿Qué observaste?
            </label>

            <textarea
                id="descripcion"
                name="descripcion"
                minlength="20"
                maxlength="2000"
                required
                placeholder="Ejemplo: varias motos compiten a alta velocidad en la avenida y bloquean el tránsito. Indica la hora, dirección de marcha y detalles observables."
            ></textarea>

            <div class="counter">
                <span id="characterCount">0</span>/2000
            </div>

            <p class="hint">
                Describe acciones concretas, características del
                vehículo o placas si las observaste. Evita atribuir
                delitos por la apariencia de una persona.
            </p>

        </section>

        <!-- UBICACIÓN -->
        <section class="card">

            <div class="section-title">
                <span class="step">3</span>
                <h2>Ubicación del incidente</h2>
            </div>

            <label
                for="direccion"
                class="field-label"
            >
                Dirección o punto de referencia
            </label>

            <input
                type="text"
                id="direccion"
                name="direccion"
                minlength="5"
                maxlength="300"
                required
                placeholder="Calle, colonia, cruce o lugar cercano"
            >

            <p class="hint">
                Tu ubicación actual puede ser diferente del lugar
                donde ocurrió el incidente.
            </p>

            <button
                type="button"
                class="location-button"
                id="locationButton"
            >
                <i
                    class="fa-solid fa-location-crosshairs"
                    aria-hidden="true"
                ></i>

                Agregar mi ubicación actual
            </button>

            <p
                id="locationStatus"
                class="hint"
                aria-live="polite"
            >
                El GPS es opcional. Puedes enviar únicamente
                la dirección escrita.
            </p>

            <button
                type="button"
                class="remove-location"
                id="removeLocation"
                hidden
            >
                Quitar ubicación GPS
            </button>

            <div
                id="map"
                aria-label="Mapa de la ubicación adjunta"
                hidden
            ></div>

        </section>

        <!-- CONFIRMACIÓN -->
        <label class="consent">
            <input
                type="checkbox"
                id="confirmation"
                required
            >

            <span>
                Confirmo que describo lo que observé y acepto
                guardar este reporte y la ubicación adjunta,
                si la agregué, en el sistema de demostración.
            </span>
        </label>

        <!-- GUARDAR -->
        <button
            type="submit"
            class="submit-button"
            id="submitButton"
        >
            <i
                class="fa-solid fa-paper-plane"
                aria-hidden="true"
            ></i>

            Guardar reporte
        </button>

        <div
            id="result"
            class="result"
            role="status"
            aria-live="polite"
        ></div>

    </form>

</main>

<footer>
    KANAN · Tu seguridad, nuestra prioridad
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    /* =====================================
       ELEMENTOS
    ====================================== */

    const token = <?= json_encode(
        $_SESSION['reporte_token']
    ) ?>;

    const form = document.getElementById('reportForm');
    const descripcion = document.getElementById('descripcion');
    const direccion = document.getElementById('direccion');

    const characterCount = document.getElementById(
        'characterCount'
    );

    const locationButton = document.getElementById(
        'locationButton'
    );

    const locationStatus = document.getElementById(
        'locationStatus'
    );

    const removeLocation = document.getElementById(
        'removeLocation'
    );

    const submitButton = document.getElementById(
        'submitButton'
    );

    const result = document.getElementById('result');
    const mapElement = document.getElementById('map');

    let ubicacion = null;
    let mapa = null;
    let marcador = null;

    /* =====================================
       CONTADOR
    ====================================== */

    descripcion.addEventListener('input', function () {
        characterCount.textContent = descripcion.value.length;
    });

    /* =====================================
       MOSTRAR MENSAJES
    ====================================== */

    function mostrarResultado(mensaje, tipo) {
        result.className = 'result ' + tipo;
        result.textContent = mensaje;
    }

    /* =====================================
       OBTENER UBICACIÓN
    ====================================== */

    locationButton.addEventListener('click', function () {
        if (!navigator.geolocation) {
            locationStatus.textContent =
                'Este navegador no admite GPS. Escribe la dirección.';

            return;
        }

        locationButton.disabled = true;

        locationStatus.textContent =
            'Obteniendo tu ubicación...';

        navigator.geolocation.getCurrentPosition(
            function (position) {
                ubicacion = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                locationStatus.textContent =
                    'Ubicación adjunta: ' +
                    ubicacion.lat.toFixed(6) + ', ' +
                    ubicacion.lng.toFixed(6) +
                    ' · Precisión aproximada: ±' +
                    Math.round(position.coords.accuracy) +
                    ' metros.';

                removeLocation.hidden = false;
                locationButton.disabled = false;

                if (!window.L) {
                    locationStatus.textContent +=
                        ' El mapa no cargó, pero las coordenadas sí.';

                    return;
                }

                mapElement.hidden = false;

                if (!mapa) {
                    mapa = L.map('map');

                    L.tileLayer(
                        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                        {
                            attribution:
                                '© OpenStreetMap contributors'
                        }
                    ).addTo(mapa);

                    marcador = L.marker([
                        ubicacion.lat,
                        ubicacion.lng
                    ]).addTo(mapa);
                }

                marcador.setLatLng([
                    ubicacion.lat,
                    ubicacion.lng
                ]);

                mapa.invalidateSize();

                mapa.setView(
                    [ubicacion.lat, ubicacion.lng],
                    16
                );
            },

            function (error) {
                locationButton.disabled = false;

                if (error.code === 1) {
                    locationStatus.textContent =
                        'No autorizaste la ubicación. ' +
                        'Puedes escribir la dirección.';
                } else {
                    locationStatus.textContent =
                        'No se pudo obtener el GPS. ' +
                        'Puedes escribir la dirección o reintentar.';
                }
            },

            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    });

    /* =====================================
       QUITAR UBICACIÓN
    ====================================== */

    removeLocation.addEventListener('click', function () {
        ubicacion = null;
        mapElement.hidden = true;
        removeLocation.hidden = true;

        locationStatus.textContent =
            'No se adjuntará ubicación GPS.';
    });

    /* =====================================
       GUARDAR REPORTE
    ====================================== */

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const categoria = form.querySelector(
            'input[name="categoria"]:checked'
        );

        if (!categoria) {
            mostrarResultado(
                'Selecciona el tipo de reporte.',
                'error'
            );

            return;
        }

        const texto = descripcion.value.trim();
        const lugar = direccion.value.trim();

        if (texto.length < 20 || lugar.length < 5) {
            mostrarResultado(
                'Completa la descripción y la dirección.',
                'error'
            );

            return;
        }

        submitButton.disabled = true;

        mostrarResultado(
            'Guardando tu reporte...',
            'loading'
        );

        const datos = {
            token: token,
            categoria: categoria.value,
            descripcion: texto,
            direccion: lugar,
            ubicacion: ubicacion
        };

        try {
            const respuesta = await fetch('reporte.php', {
                method: 'POST',

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
                    'Abre esta página desde XAMPP.'
                );
            }

            if (!respuesta.ok) {
                throw new Error(
                    contenido.error || 'No se pudo guardar.'
                );
            }

            mostrarResultado(
                'Reporte guardado. Folio: ' +
                contenido.folio +
                '. Este registro es de demostración; ' +
                'no confirma recepción policial.',
                'success'
            );

            form.reset();
            characterCount.textContent = '0';
            removeLocation.click();

        } catch (error) {
            mostrarResultado(
                'No se guardó el reporte: ' + error.message,
                'error'
            );

        } finally {
            submitButton.disabled = false;
        }
    });
</script>

</body>
</html>