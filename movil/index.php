<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    >

    <meta name="theme-color" content="#062b50">

    <title>KANAN Emergencias</title>

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

    <!-- GOOGLE FONT -->
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
    >

    <!-- ESTILOS COMPARTIDOS -->
    <link rel="stylesheet" href="css/styles.css">

    <style>
        .home-page {
            --azul-oscuro: #062b50;
            --azul: #0877c9;
            --fondo: #f1f5fa;
            --texto: #173453;
            --secundario: #64788e;
            --borde: #dce5ef;
            --home-nav-height: 88px;

            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            background: var(--fondo);
            color: var(--texto);
            font-family: "Inter", Arial, sans-serif;
        }

        .home-page *,
        .home-page *::before,
        .home-page *::after {
            box-sizing: border-box;
        }

        .home-page .app {
            display: block;
            width: 100%;
            max-width: 650px;
            height: auto;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0 auto;
            overflow: visible;
            background: var(--fondo);
            padding-bottom: calc(var(--home-nav-height) + 20px);
        }

        .home-page button {
            font: inherit;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        /* HEADER */

        .home-page .header {
            position: relative;
            width: 100%;
            background: var(--azul-oscuro);
            padding: 16px;
            padding-top: max(16px, env(safe-area-inset-top));
            padding-left: max(12px, env(safe-area-inset-left));
            padding-right: max(12px, env(safe-area-inset-right));
            border-radius: 0;
        }

        .home-page .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .home-page .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 0;
        }

        .home-page .brand-logo {
            display: block;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            object-fit: contain;
            border-radius: 12px;
        }

        .home-page .brand-text {
            min-width: 0;
        }

        .home-page .brand-text h1 {
            margin: 0;
            color: white;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: 2px;
        }

        .home-page .brand-text span {
            display: block;
            margin-top: 4px;
            color: #bad8f2;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .home-page .header-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 19px;
        }

        .home-page .slogan {
            margin-top: 14px;
            text-align: center;
            color: #e0efff;
            font-size: 12px;
            line-height: 1.5;
        }

        /* MAPA */

        .home-page .map-wrapper {
            position: relative;
            width: auto;
            height: clamp(260px, 65vw, 350px);
            margin: 18px 16px 0;
            border: 1px solid var(--borde);
            border-radius: 18px;
            overflow: hidden;
            background: #e7eff9;
            isolation: isolate;
        }

        .home-page #map {
            width: 100%;
            height: 100%;
            min-height: 0;
            border-radius: inherit;
            z-index: 0;
        }

        .home-page .location-card {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            bottom: auto;
            width: auto;
            max-width: none;
            margin: 0;
            padding: 12px 14px;
            border: 1px solid rgba(220, 229, 239, 0.9);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 4px 14px rgba(6, 43, 80, 0.08);
            pointer-events: none;
            z-index: 2;
        }

        .home-page .location-card strong {
            display: block;
            color: var(--texto);
            font-size: 13px;
            margin-bottom: 5px;
        }

        .home-page .location-card span {
            display: block;
            color: var(--secundario);
            font-size: 12px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        /* CONTENIDO */

        .home-page .content {
            display: block;
            width: 100%;
            max-width: none;
            min-width: 0;
            margin: 0;
            padding: 20px 16px;
            padding-left: max(16px, env(safe-area-inset-left));
            padding-right: max(16px, env(safe-area-inset-right));
            background: transparent;
        }

        .home-page .help-button,
        .home-page .call-button,
        .home-page .report-button {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 14px;
            width: 100%;
            min-height: 88px;
            margin: 0 0 14px;
            padding: 18px;
            border-radius: 16px;
            text-align: left;
            transition: transform 0.15s ease;
        }

        .home-page .help-button:active,
        .home-page .call-button:active,
        .home-page .report-button:active {
            transform: scale(0.99);
        }

        .home-page .help-button {
            border: 1px solid var(--azul-oscuro);
            background: var(--azul-oscuro);
            color: white;
            box-shadow: 0 5px 18px rgba(6, 43, 80, 0.12);
        }

        .home-page .call-button {
            border: 1px solid #b9d9f1;
            background: #e7f2fc;
            color: var(--azul-oscuro);
        }

        .home-page .report-button {
            border: 1px solid var(--borde);
            background: white;
            color: var(--texto);
            box-shadow: 0 5px 18px rgba(6, 43, 80, 0.03);
        }

        .home-page .help-button > i,
        .home-page .call-button > i,
        .home-page .report-button > i {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            font-size: 22px;
        }

        .home-page .help-button > i {
            background: rgba(255, 255, 255, 0.12);
        }

        .home-page .call-button > i {
            background: #d4e9fa;
            color: var(--azul);
        }

        .home-page .report-button > i {
            background: #eaf3fc;
            color: var(--azul);
        }

        .home-page .help-text,
        .home-page .call-text,
        .home-page .report-text {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
            text-align: left;
        }

        .home-page .help-text strong,
        .home-page .call-text strong,
        .home-page .report-text strong {
            font-size: clamp(13px, 3.7vw, 16px);
            font-weight: 800;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .home-page .help-text > span,
        .home-page .call-text > span,
        .home-page .report-text > span {
            font-size: 12px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .home-page .help-text > span {
            color: #d6e8f8;
        }

        .home-page .call-text > span,
        .home-page .report-text > span {
            color: var(--secundario);
        }

        .home-page .non-emergency {
            margin: 22px 0 14px;
            text-align: center;
            color: var(--secundario);
            font-size: 13px;
        }

        .home-page .home-footer {
            padding: 0 16px 20px;
            text-align: center;
            color: #75889b;
            font-size: 12px;
            line-height: 1.6;
        }

        /* NAVBAR */

        .home-page .bottom-nav {
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

        .home-page .bottom-nav .nav-item {
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

        .home-page .bottom-nav .nav-item i {
            font-size: 20px;
        }

        .home-page .bottom-nav .nav-item span {
            font-size: 12px;
        }

        .home-page .bottom-nav .nav-item.active {
            color: var(--azul);
            background: #eaf4ff;
            font-weight: bold;
        }

        .home-page button:focus-visible,
        .home-page a:focus-visible {
            outline: 3px solid #e7ad45;
            outline-offset: 3px;
        }

        .home-page button:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        @media (max-width: 360px) {
            .home-page .header-top {
                gap: 8px;
            }

            .home-page .brand {
                gap: 6px;
            }

            .home-page .brand-logo {
                width: 38px;
                height: 38px;
            }

            .home-page .brand-text h1 {
                font-size: 18px;
            }

            .home-page .brand-text span {
                font-size: 8px;
            }

            .home-page .help-button,
            .home-page .call-button,
            .home-page .report-button {
                gap: 10px;
                padding: 14px;
            }

            .home-page .help-button > i,
            .home-page .call-button > i,
            .home-page .report-button > i {
                width: 38px;
                height: 38px;
                font-size: 20px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .home-page .help-button,
            .home-page .call-button,
            .home-page .report-button {
                transition: none;
            }
        }
    </style>
</head>

<body class="home-page">

<div class="app">

    <!-- HEADER -->
    <header class="header">

        <div class="header-top">

           

            <!-- LOGO -->
            <div class="brand">

                <img
                    src="logo.jpeg"
                    alt="Logo de KANAN"
                    class="brand-logo"
                    width="48"
                    height="48"
                >

                <div class="brand-text">
                    <h1>KANAN</h1>
                    <span>EMERGENCIAS</span>
                </div>

            </div>

        </div>

        <div class="slogan">
            Tu seguridad, nuestra prioridad
        </div>

    </header>

    <!-- MAPA -->
    <section
        class="map-wrapper"
        aria-label="Tu ubicación actual"
    >

        <div id="map"></div>

        <div class="location-card">
            <strong>Tu ubicación</strong>

            <span id="locationText" aria-live="polite">
                Buscando ubicación...
            </span>
        </div>

    </section>

    <!-- CONTENIDO -->
    <main class="content">

        <!-- SOLICITAR AYUDA -->
        <button
            type="button"
            class="help-button"
            id="helpButton"
        >
            <i class="fa-solid fa-bell" aria-hidden="true"></i>

            <span class="help-text">
                <strong>SOLICITAR AYUDA PARA MÍ</strong>

                <span>
                    Envía tu ubicación al centro de monitoreo
                </span>
            </span>
        </button>

        <!-- LLAMAR -->
        <button
            type="button"
            class="call-button"
            id="callButton"
        >
            <i class="fa-solid fa-phone" aria-hidden="true"></i>

            <span class="call-text">
                <strong>LLAMAR A EMERGENCIAS</strong>

                <span
                    class="call-number"
                    id="emergencyNumberText"
                >
                    Cargando...
                </span>
            </span>
        </button>

        <div class="non-emergency">
            ¿No es una emergencia?
        </div>

        <!-- REPORTE -->
        <button
            type="button"
            class="report-button"
            onclick="window.location.href='reporte.php'"
        >
            <i
                class="fa-solid fa-clipboard-list"
                aria-hidden="true"
            ></i>

            <span class="report-text">
                <strong>CREAR REPORTE</strong>

                <span>
                    Ayúdanos a mejorar tu ciudad
                </span>
            </span>
        </button>

    </main>

    <footer class="home-footer">
        KANAN · Tu seguridad, nuestra prioridad
    </footer>

    <!-- NAVBAR: INICIO, REPORTES Y PERFIL -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

</div>

<!-- SIDEBAR -->
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- MODAL -->
<?php include __DIR__ . '/includes/modal.php'; ?>

<!-- LEAFLET -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- LÓGICA EXISTENTE -->
<script src="js/app.js"></script>

<script>
(() => {
    'use strict';

    const navbar = document.querySelector('.bottom-nav');

    if (!navbar) {
        return;
    }

    // Marca Inicio como activo.
    navbar.querySelectorAll('.nav-item').forEach((enlace) => {
        const ruta = new URL(enlace.href, window.location.href);
        const activo = ruta.pathname.endsWith('/index.php');

        enlace.classList.toggle('active', activo);

        if (activo) {
            enlace.setAttribute('aria-current', 'page');
        } else {
            enlace.removeAttribute('aria-current');
        }
    });

    // Reserva el espacio real de la barra inferior.
    const ajustarEspacio = () => {
        document.body.style.setProperty(
            '--home-nav-height',
            Math.ceil(navbar.getBoundingClientRect().height) + 'px'
        );
    };

    ajustarEspacio();

    window.addEventListener('resize', ajustarEspacio);

    if ('ResizeObserver' in window) {
        const observador = new ResizeObserver(ajustarEspacio);
        observador.observe(navbar);
    }
})();
</script>

</body>
</html>