<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >
    <meta name="theme-color" content="#062b50">

    <title>Crear reporte | KANAN</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <!-- Estilos de tus componentes compartidos -->
    <link rel="stylesheet" href="css/styles.css">

    <style>
        .report-page {
            --azul-oscuro: #062b50;
            --azul: #0877c9;
            --fondo: #f1f5fa;
            --texto: #173453;
            --secundario: #64788e;
            --borde: #dce5ef;
            --report-nav-height: 88px;

            margin: 0;
            background: var(--fondo);
            color: var(--texto);
            font-family: Arial, sans-serif;
            min-height: 100vh;
            min-height: 100dvh;
        }

        .report-page *,
        .report-page *::before,
        .report-page *::after {
            box-sizing: border-box;
        }

        .report-page .app {
            display: block;
            width: 100%;
            max-width: 650px;
            height: auto;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0 auto;
            overflow: visible;
            background: var(--fondo);
            padding-bottom: calc(var(--report-nav-height) + 20px);
        }

        .report-page button,
        .report-page input,
        .report-page textarea {
            font: inherit;
        }

        .report-page button {
            cursor: pointer;
        }

        .report-page .report-header {
            background: var(--azul-oscuro);
            padding: 16px;
            padding-top: max(16px, env(safe-area-inset-top));
            padding-left: max(12px, env(safe-area-inset-left));
            padding-right: max(12px, env(safe-area-inset-right));
        }

        .report-page .report-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .report-page .report-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 0;
        }

        .report-page .report-brand img {
            display: block;
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .report-page .report-brand strong {
            display: block;
            color: white;
            font-size: 22px;
            letter-spacing: 2px;
        }

        .report-page .report-brand small {
            display: block;
            color: #bad8f2;
            font-size: 9px;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .report-page .report-header-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            padding: 0;
            margin: 0;
            color: white;
            border: 0;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 19px;
        }

        .report-page .report-content {
            width: 100%;
            min-width: 0;
            padding: 24px 16px;
            padding-left: max(16px, env(safe-area-inset-left));
            padding-right: max(16px, env(safe-area-inset-right));
        }

        .report-page .eyebrow {
            color: var(--azul);
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .report-page .report-content h1 {
            font-size: clamp(25px, 7vw, 30px);
            margin: 10px 0;
        }

        .report-page .intro {
            color: var(--secundario);
            font-size: 14px;
            line-height: 1.6;
        }

        .report-page .notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #e7eff9;
            border-left: 4px solid var(--azul);
            border-radius: 12px;
            padding: 16px;
            margin: 22px 0;
        }

        .report-page .notice i {
            color: var(--azul);
            margin-top: 3px;
            flex-shrink: 0;
        }

        .report-page .notice strong {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .report-page .notice p {
            margin: 0;
            color: #526b83;
            font-size: 12px;
            line-height: 1.6;
        }

        .report-page .report-card {
            min-width: 0;
            background: white;
            border: 1px solid var(--borde);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 5px 18px rgba(6, 43, 80, 0.03);
        }

        .report-page .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .report-page .step {
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

        .report-page .section-title h2 {
            font-size: 16px;
            margin: 0;
        }

        .report-page .categories {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .report-page .category {
            position: relative;
            min-width: 0;
        }

        .report-page .category input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
        }

        .report-page .category-box {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 100%;
            min-height: 78px;
            padding: 12px;
            border: 1px solid var(--borde);
            border-radius: 12px;
            cursor: pointer;
        }

        .report-page .category-box i {
            color: #5480aa;
            font-size: 20px;
            flex-shrink: 0;
        }

        .report-page .category-box span {
            font-size: 12px;
            font-weight: bold;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .report-page .category input:checked + .category-box {
            background: #eaf4ff;
            border-color: var(--azul);
            box-shadow: inset 0 0 0 1px var(--azul);
        }

        .report-page .category input:checked + .category-box i {
            color: var(--azul);
        }

        .report-page .category input:focus-visible + .category-box {
            outline: 3px solid #e7ad45;
            outline-offset: 3px;
        }

        .report-page .field-label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0 9px;
        }

        .report-page #reportForm textarea,
        .report-page #reportForm input[type="text"] {
            display: block;
            width: 100%;
            min-width: 0;
            min-height: 50px;
            color: var(--texto);
            background: #fbfdff;
            border: 1px solid #ccd9e6;
            border-radius: 12px;
            padding: 14px;
            font-size: 16px;
        }

        .report-page #reportForm textarea {
            min-height: 155px;
            resize: vertical;
            line-height: 1.6;
        }

        .report-page #reportForm textarea:focus,
        .report-page #reportForm input[type="text"]:focus {
            outline: 2px solid var(--azul);
            outline-offset: 2px;
        }

        .report-page .counter {
            text-align: right;
            margin-top: 7px;
            font-size: 12px;
            color: var(--secundario);
        }

        .report-page .hint {
            color: var(--secundario);
            font-size: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .report-page .location-button,
        .report-page .remove-location {
            width: 100%;
            min-height: 50px;
            padding: 14px;
            border: 0;
            border-radius: 12px;
            background: #eaf3fc;
            color: #17649f;
            font-size: 14px;
            font-weight: bold;
        }

        .report-page .location-button i {
            margin-right: 8px;
        }

        .report-page .remove-location {
            background: #fff0f0;
            color: #a83c3c;
            margin-bottom: 12px;
        }

        .report-page #map {
            width: 100%;
            height: clamp(210px, 55vw, 280px);
            border-radius: 12px;
            background: #edf2f7;
            z-index: 0;
        }

        .report-page .consent {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 8px 4px;
            margin: 18px 0;
            color: #5a7087;
            font-size: 13px;
            line-height: 1.6;
            cursor: pointer;
        }

        .report-page .consent input {
            width: 22px;
            height: 22px;
            margin: 2px 0 0;
            flex-shrink: 0;
            accent-color: var(--azul-oscuro);
        }

        .report-page .submit-button {
            width: 100%;
            min-height: 58px;
            padding: 18px;
            background: var(--azul-oscuro);
            color: white;
            border: 0;
            border-radius: 14px;
            font-weight: bold;
            font-size: 16px;
        }

        .report-page .submit-button i {
            margin-right: 9px;
        }

        .report-page button:disabled {
            opacity: 0.55;
            cursor: wait;
        }

        .report-page button:focus-visible,
        .report-page a:focus-visible {
            outline: 3px solid #e7ad45;
            outline-offset: 3px;
        }

        .report-page .result {
            margin-top: 18px;
            padding: 18px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .report-page .result.success {
            background: #e4f5ec;
            color: #176747;
        }

        .report-page .result.error {
            background: #fdecec;
            color: #a13535;
        }

        .report-page .result.loading {
            background: #e8f1fb;
            color: #185e97;
        }

        .report-page .result:empty {
            display: none;
        }

        .report-page .report-footer {
            text-align: center;
            color: #75889b;
            font-size: 12px;
            padding: 12px 16px 20px;
        }

        /* Navbar centrado: Inicio, Reportes y Perfil */
        .report-page .bottom-nav {
            position: fixed;
            left: 50%;
            right: auto;
            bottom: 0;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 650px;
            gap: clamp(4px, 3vw, 20px);
            padding: 10px 12px;
            padding-bottom: max(10px, env(safe-area-inset-bottom));
            background: white;
            border-top: 1px solid var(--borde);
            box-shadow: 0 -4px 18px rgba(6, 43, 80, 0.06);
            z-index: 1000;
        }

        .report-page .bottom-nav .nav-item {
            flex: 0 1 100px;
            min-width: 0;
            min-height: 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 0;
            padding: 8px 4px;
            color: var(--secundario);
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
        }

        .report-page .bottom-nav .nav-item i {
            font-size: 20px;
        }

        .report-page .bottom-nav .nav-item span {
            font-size: 12px;
        }

        .report-page .bottom-nav .nav-item.active {
            color: var(--azul);
            background: #eaf4ff;
            font-weight: bold;
        }

        .report-page [hidden] {
            display: none !important;
        }

        @media (max-width: 360px) {
            .report-page .categories {
                grid-template-columns: 1fr;
            }

            .report-page .report-card {
                padding: 16px;
            }

            .report-page .report-brand {
                gap: 6px;
            }

            .report-page .report-brand img {
                width: 38px;
                height: 38px;
            }

            .report-page .report-brand strong {
                font-size: 18px;
            }

            .report-page .report-brand small {
                font-size: 8px;
                letter-spacing: 0;
            }
        }
    </style>
</head>

<body class="report-page">

<div class="app">

    <!-- HEADER -->
    <header class="report-header">
        <div class="report-header-content">

            <button
                type="button"
                class="menu-button report-header-button"
                id="menuButton"
                aria-label="Abrir menú"
            >
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>

            <div class="report-brand">
                <img
                    src="logo.jpeg"
                    alt="Logo de KANAN"
                    width="48"
                    height="48"
                >

                <div>
                    <strong>KANAN</strong>
                    <small>REPORTE CIUDADANO</small>
                </div>
            </div>

            <button
                type="button"
                class="bell report-header-button"
                id="notificationButton"
                aria-label="Notificaciones"
            >
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
            </button>

        </div>
    </header>

    <main class="report-content">

        <span class="eyebrow">
            AYÚDANOS A CUIDAR TU COMUNIDAD
        </span>

        <h1>Crear reporte</h1>

        <p class="intro">
            Describe lo que observaste y señala dónde ocurrió.
        </p>

        <div class="notice">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>

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
            <section class="report-card">
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
                                    class="fa-solid <?= $iconos[$indice]
                                        ?? 'fa-circle-exclamation' ?>"
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
            <section class="report-card">
                <div class="section-title">
                    <span class="step">2</span>
                    <h2>Describe el incidente</h2>
                </div>

                <label for="descripcion" class="field-label">
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
            <section class="report-card">
                <div class="section-title">
                    <span class="step">3</span>
                    <h2>Ubicación del incidente</h2>
                </div>

                <label for="direccion" class="field-label">
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

            <button
                type="submit"
                class="submit-button"
                id="submitButton"
            >
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
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

    <footer class="report-footer">
        KANAN · Tu seguridad, nuestra prioridad
    </footer>

    <!-- NAVBAR -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

</div>

<!-- SIDEBAR -->
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- MODAL -->
<?php include __DIR__ . '/includes/modal.php'; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(() => {
    'use strict';

    const token = <?= json_encode(
        $_SESSION['reporte_token'],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;

    const form = document.getElementById('reportForm');
    const descripcion = document.getElementById('descripcion');
    const direccion = document.getElementById('direccion');
    const characterCount = document.getElementById('characterCount');
    const locationButton = document.getElementById('locationButton');
    const locationStatus = document.getElementById('locationStatus');
    const removeLocation = document.getElementById('removeLocation');
    const submitButton = document.getElementById('submitButton');
    const result = document.getElementById('result');
    const mapElement = document.getElementById('map');

    let ubicacion = null;
    let mapa = null;
    let marcador = null;

    descripcion.addEventListener('input', () => {
        characterCount.textContent = descripcion.value.length;
    });

    function mostrarResultado(mensaje, tipo) {
        result.className = 'result ' + tipo;
        result.textContent = mensaje;
    }

    locationButton.addEventListener('click', () => {
        if (!navigator.geolocation) {
            locationStatus.textContent =
                'Este navegador no admite GPS. Escribe la dirección.';
            return;
        }

        locationButton.disabled = true;
        locationStatus.textContent = 'Obteniendo tu ubicación...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
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
                            attribution: '© OpenStreetMap contributors'
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

                requestAnimationFrame(() => {
                    mapa.invalidateSize();
                    mapa.setView([
                        ubicacion.lat,
                        ubicacion.lng
                    ], 16);
                });
            },
            (error) => {
                locationButton.disabled = false;

                locationStatus.textContent = error.code === 1
                    ? 'No autorizaste la ubicación. Puedes escribir la dirección.'
                    : 'No se pudo obtener el GPS. Puedes escribir la dirección o reintentar.';
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    });

    removeLocation.addEventListener('click', () => {
        ubicacion = null;
        mapElement.hidden = true;
        removeLocation.hidden = true;
        locationStatus.textContent = 'No se adjuntará ubicación GPS.';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!form.reportValidity() || submitButton.disabled) {
            return;
        }

        const categoria = form.querySelector(
            'input[name="categoria"]:checked'
        );

        if (!categoria) {
            mostrarResultado('Selecciona el tipo de reporte.', 'error');
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
        mostrarResultado('Guardando tu reporte...', 'loading');

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
                    'Abre esta página desde tu servidor PHP.'
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

    // Marca Reportes como la sección activa.
    const navbar = document.querySelector('.bottom-nav');

    if (navbar) {
        navbar.querySelectorAll('.nav-item').forEach((enlace) => {
            const ruta = new URL(enlace.href, window.location.href);
            const activo = ruta.pathname === window.location.pathname;

            enlace.classList.toggle('active', activo);

            if (activo) {
                enlace.setAttribute('aria-current', 'page');
            } else {
                enlace.removeAttribute('aria-current');
            }
        });

        // Evita que el navbar tape el final del formulario.
        const ajustarEspacio = () => {
            document.body.style.setProperty(
                '--report-nav-height',
                Math.ceil(navbar.getBoundingClientRect().height) + 'px'
            );
        };

        ajustarEspacio();
        window.addEventListener('resize', ajustarEspacio);

        if ('ResizeObserver' in window) {
            const observador = new ResizeObserver(ajustarEspacio);
            observador.observe(navbar);
        }
    }
})();
</script>

<!-- Conserva aquí el JavaScript que ya abre tu sidebar y tu modal. -->

</body>
</html>