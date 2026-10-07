<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
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

    <!-- ESTILOS EXISTENTES -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- IDENTIDAD KANAN -->
    <style>
        .header .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .header .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-width: 0;
        }

        .header .brand-logo {
            display: block;
            width: 64px;
            height: 64px;
            flex-shrink: 0;
            object-fit: contain;
            border-radius: 16px;
        }

        .header .brand-text {
            min-width: 0;
        }

        .header .brand-text h1 {
            margin: 0;
            color: #ffffff;
            font-family: "Inter", sans-serif;
            font-size: clamp(22px, 6vw, 30px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: 2px;
        }

        .header .brand-text span {
            display: block;
            margin-top: 5px;
            color: #bddcff;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 2px;
        }

        .header .slogan {
            margin-top: 14px;
            text-align: center;
            color: #e0efff;
            font-size: 13px;
        }

        .help-text,
        .call-text,
        .report-text {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        @media (max-width: 360px) {
            .header .header-top {
                gap: 8px;
            }

            .header .brand {
                gap: 8px;
            }

            .header .brand-logo {
                width: 48px;
                height: 48px;
                border-radius: 12px;
            }

            .header .brand-text h1 {
                font-size: 22px;
            }

            .header .brand-text span {
                font-size: 9px;
                letter-spacing: 1px;
            }
        }
    </style>
</head>

<body>

<div class="app">

    <!-- HEADER -->
    <header class="header">

        <div class="header-top">

            <!-- LOGO KANAN -->
            <div class="brand">

                <img
                    src="logo.jpeg"
                    alt="Logo de KANAN"
                    class="brand-logo"
                    width="64"
                    height="64"
                >

                <div class="brand-text">
                    <h1>KANAN</h1>
                    <span>EMERGENCIAS</span>
                </div>

            </div>

            <!-- NOTIFICACIONES -->
  

        </div>

        <div class="slogan">
            Tu seguridad, nuestra prioridad
        </div>

    </header>

    <!-- MAPA -->
    <section class="map-wrapper" aria-label="Tu ubicación actual">

        <div id="map"></div>

        <!-- UBICACIÓN -->
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

        <!-- CREAR REPORTE -->
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

<!-- LEAFLET -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- LÓGICA EXISTENTE -->
<script src="js/app.js"></script>

</body>
</html>