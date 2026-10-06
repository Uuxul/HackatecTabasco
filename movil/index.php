<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    >

    <meta name="theme-color" content="#062b50">

    <title>CityFix Emergencias</title>


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


    <!-- ESTILOS -->
    <link
        rel="stylesheet"
        href="css/styles.css"
    >

</head>


<body>


<!-- =========================================
     APP
========================================= -->

<div class="app">


    <!-- =====================================
         HEADER
    ====================================== -->

    <header class="header">

        <div class="header-top">


            <!-- BOTÓN MENÚ -->

            <button
                type="button"
                class="menu-button"
                id="menuButton"
                aria-label="Abrir menú"
            >

                <i class="fa-solid fa-bars"></i>

            </button>



            <!-- LOGO -->

            <div class="brand">

                <div class="shield">

                    <i class="fa-solid fa-shield-halved"></i>

                </div>


                <div class="brand-text">

                    <h1>
                        CITYFIX
                    </h1>

                    <span>
                        EMERGENCIAS
                    </span>

                </div>

            </div>



            <!-- NOTIFICACIONES -->

            <button
                type="button"
                class="bell"
                id="notificationButton"
                aria-label="Notificaciones"
            >

                <i class="fa-regular fa-bell"></i>

            </button>


        </div>


        <div class="slogan">

            Tu seguridad, nuestra prioridad

        </div>

    </header>



    <!-- =====================================
         MAPA
    ====================================== -->

    <section class="map-wrapper">


        <div id="map"></div>



        <!-- UBICACIÓN -->

        <div class="location-card">

            <strong>
                Tu ubicación
            </strong>

            <span id="locationText">
                Buscando ubicación...
            </span>

        </div>



        <!-- CONTROLES -->

        <div class="map-controls">


            <button
                type="button"
                class="map-control"
                id="centerLocation"
                title="Mi ubicación"
            >

                <i class="fa-solid fa-location-crosshairs"></i>

            </button>


            <button
                type="button"
                class="map-control"
                id="zoomLocation"
                title="Acercar"
            >

                <i class="fa-solid fa-plus"></i>

            </button>


        </div>


    </section>



    <!-- =====================================
         CONTENIDO
    ====================================== -->

    <main class="content">


        <!-- SOLICITAR AYUDA -->

        <button
            type="button"
            class="help-button"
            id="helpButton"
        >

            <i class="fa-solid fa-bell"></i>


            <div class="help-text">

                <strong>
                    SOLICITAR AYUDA PARA MÍ
                </strong>

                <span>
                    Envía tu ubicación al centro de monitoreo
                </span>

            </div>

        </button>



        <!-- LLAMAR -->

        <button
            type="button"
            class="call-button"
            id="callButton"
        >

            <i class="fa-solid fa-phone"></i>


            <div class="call-text">

                <strong>
                    LLAMAR A EMERGENCIAS
                </strong>

                <span
                    class="call-number"
                    id="emergencyNumberText"
                >
                    Cargando...
                </span>

            </div>

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

            <i class="fa-solid fa-clipboard-list"></i>


            <div class="report-text">

                <strong>
                    CREAR REPORTE
                </strong>

                <span>
                    Ayúdanos a mejorar tu ciudad
                </span>

            </div>

        </button>


    </main>



    <!-- =====================================
         NAVBAR
    ====================================== -->

    <?php
        include __DIR__ . '/include/navbar.php';
    ?>


</div>



<!-- =========================================
     SIDEBAR
========================================= -->

<?php
    include __DIR__ . '/include/sidebar.php';
?>



<!-- =========================================
     MODAL
========================================= -->

<?php
    include __DIR__ . '/include/modal.php';
?>



<!-- =========================================
     LEAFLET JS
========================================= -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>



<!-- =========================================
     APP JS
========================================= -->

<script src="js/app.js"></script>


</body>

</html>