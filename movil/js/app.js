"use strict";


/* ==========================================================
   CONFIGURACIÓN
========================================================== */

const NUMERO_EMERGENCIAS = "9971557245";

const API_CREAR_EMERGENCIA =
    "../api/emergencias/crear.php";

const API_ACTUALIZAR_EMERGENCIA =
    "../api/emergencias/actualizar.php";


/* ==========================================================
   VARIABLES
========================================================== */

let map = null;

let ubicacionActual = null;

let marcadorUsuario = null;

let circuloPrecision = null;

let emergenciaActiva = false;

let idEmergenciaActiva = null;

let primeraUbicacion = true;

let watchId = null;

let actualizandoUbicacion = false;


/* ==========================================================
   MAPA
========================================================== */

function iniciarMapa() {

    const mapElement =
        document.getElementById("map");


    if (!mapElement) {

        console.error(
            "No se encontró el elemento #map"
        );

        return;

    }


    map = L.map(
        "map",
        {
            zoomControl: false,
            attributionControl: false
        }
    );


    /*
        POSICIÓN TEMPORAL.

        Se reemplaza cuando el GPS
        obtiene la posición real.
    */

    map.setView(
        [
            20.9754,
            -89.6169
        ],
        15
    );


    /*
        MAPA BASE
    */

    L.tileLayer(

        "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",

        {

            maxZoom: 20,

            subdomains: [
                "mt0",
                "mt1",
                "mt2",
                "mt3"
            ]

        }

    ).addTo(map);


    console.log(
        "Mapa inicializado correctamente."
    );

}


/* ==========================================================
   ICONO USUARIO
========================================================== */

function crearIconoUsuario() {

    return L.divIcon({

        className: "",

        html: `

            <div class="user-marker">

                <div
                    class="user-pulse"
                ></div>

                <div
                    class="user-dot"
                ></div>

            </div>

        `,

        iconSize: [
            44,
            44
        ],

        iconAnchor: [
            22,
            22
        ]

    });

}


/* ==========================================================
   GPS AUTOMÁTICO
========================================================== */

function iniciarGPS() {

    if (!navigator.geolocation) {

        actualizarTextoGPS(
            "GPS no disponible"
        );


        console.error(
            "Geolocation no está disponible."
        );


        return;

    }


    actualizarTextoGPS(
        "Buscando ubicación..."
    );


    watchId =
        navigator.geolocation.watchPosition(

            posicionRecibida,

            errorGPS,

            {

                enableHighAccuracy:
                    true,

                maximumAge:
                    0,

                timeout:
                    15000

            }

        );


    console.log(
        "Seguimiento GPS iniciado."
    );

}


/* ==========================================================
   POSICIÓN RECIBIDA
========================================================== */

function posicionRecibida(position) {

    ubicacionActual = {

        lat:
            position.coords.latitude,

        lng:
            position.coords.longitude,

        accuracy:
            position.coords.accuracy,

        speed:
            position.coords.speed,

        heading:
            position.coords.heading,

        timestamp:
            Date.now()

    };


    console.log(
        "📍 GPS recibido:",
        ubicacionActual
    );


    /*
        ACTUALIZAMOS EL MAPA
        DEL CIUDADANO.
    */

    actualizarMapaUsuario();


    /*
        SI YA EXISTE UNA EMERGENCIA,
        ACTUALIZAMOS LA POSICIÓN
        EN MYSQL.

        NO CREAMOS OTRA EMERGENCIA.
    */

    if (
        emergenciaActiva &&
        idEmergenciaActiva
    ) {

        actualizarUbicacionDashboard();

    }

}


/* ==========================================================
   ACTUALIZAR MAPA USUARIO
========================================================== */

function actualizarMapaUsuario() {

    if (
        !ubicacionActual ||
        !map
    ) {

        return;

    }


    const posicion = [

        ubicacionActual.lat,

        ubicacionActual.lng

    ];


    /*
        TEXTO GPS
    */

    actualizarTextoGPS(

        `GPS activo · ±${Math.round(
            ubicacionActual.accuracy
        )} m`

    );


    /*
        ACTUALIZAR INFORMACIÓN
        DEL MODAL.
    */

    const gpsPreview =
        document.getElementById(
            "gpsPreview"
        );


    if (gpsPreview) {

        gpsPreview.innerHTML = `

            <i class="fa-solid fa-location-dot"></i>

            <strong>
                Ubicación encontrada
            </strong>

            <br><br>

            Latitud:
            ${ubicacionActual.lat.toFixed(6)}

            <br>

            Longitud:
            ${ubicacionActual.lng.toFixed(6)}

            <br>

            Precisión:
            ±${Math.round(
                ubicacionActual.accuracy
            )} m

        `;

    }


    /*
        MARCADOR DEL USUARIO
    */

    if (!marcadorUsuario) {

        marcadorUsuario =
            L.marker(

                posicion,

                {

                    icon:
                        crearIconoUsuario(),

                    zIndexOffset:
                        1000

                }

            )
            .addTo(map);

    }

    else {

        marcadorUsuario
            .setLatLng(
                posicion
            );

    }


    /*
        CÍRCULO DE PRECISIÓN
    */

    if (!circuloPrecision) {

        circuloPrecision =
            L.circle(

                posicion,

                {

                    radius:
                        ubicacionActual.accuracy,

                    color:
                        "#4285f4",

                    fillColor:
                        "#4285f4",

                    fillOpacity:
                        0.08,

                    opacity:
                        0.25,

                    weight:
                        1

                }

            )
            .addTo(map);

    }

    else {

        circuloPrecision
            .setLatLng(
                posicion
            );


        circuloPrecision
            .setRadius(
                ubicacionActual.accuracy
            );

    }


    /*
        CENTRAR AUTOMÁTICAMENTE
        LA PRIMERA VEZ.
    */

    if (primeraUbicacion) {

        map.flyTo(

            posicion,

            17,

            {
                duration:
                    1.2
            }

        );


        primeraUbicacion =
            false;

    }

}


/* ==========================================================
   TEXTO GPS
========================================================== */

function actualizarTextoGPS(texto) {

    const elemento =
        document.getElementById(
            "locationText"
        );


    if (elemento) {

        elemento.textContent =
            texto;

    }

}


/* ==========================================================
   ERROR GPS
========================================================== */

function errorGPS(error) {

    console.error(
        "Error GPS:",
        error
    );


    let mensaje =
        "No pudimos obtener tu ubicación";


    if (error.code === 1) {

        mensaje =
            "Permite el acceso a tu ubicación";

    }

    else if (error.code === 2) {

        mensaje =
            "Ubicación no disponible";

    }

    else if (error.code === 3) {

        mensaje =
            "Buscando señal GPS...";

    }


    actualizarTextoGPS(
        mensaje
    );

}


/* ==========================================================
   CONTROLES DEL MAPA
========================================================== */

function configurarControlesMapa() {

    const centerLocation =
        document.getElementById(
            "centerLocation"
        );


    const zoomLocation =
        document.getElementById(
            "zoomLocation"
        );


    centerLocation
        ?.addEventListener(

            "click",

            function() {

                if (
                    !ubicacionActual ||
                    !map
                ) {

                    alert(
                        "Todavía estamos obteniendo tu ubicación."
                    );

                    return;

                }


                map.flyTo(

                    [

                        ubicacionActual.lat,

                        ubicacionActual.lng

                    ],

                    17,

                    {

                        duration:
                            0.8

                    }

                );

            }

        );


    zoomLocation
        ?.addEventListener(

            "click",

            function() {

                if (
                    !ubicacionActual ||
                    !map
                ) {

                    return;

                }


                map.flyTo(

                    [

                        ubicacionActual.lat,

                        ubicacionActual.lng

                    ],

                    19,

                    {

                        duration:
                            0.8

                    }

                );

            }

        );

}


/* ==========================================================
   SIDEBAR
========================================================== */

function configurarSidebar() {

    const menuButton =
        document.getElementById(
            "menuButton"
        );


    const sidebar =
        document.getElementById(
            "mobileSidebar"
        );


    const overlay =
        document.getElementById(
            "sidebarOverlay"
        );


    const closeButton =
        document.getElementById(
            "closeSidebar"
        );


    function abrirSidebar() {

        sidebar
            ?.classList
            .add(
                "show"
            );


        overlay
            ?.classList
            .add(
                "show"
            );


        document.body.style.overflow =
            "hidden";

    }


    function cerrarSidebar() {

        sidebar
            ?.classList
            .remove(
                "show"
            );


        overlay
            ?.classList
            .remove(
                "show"
            );


        document.body.style.overflow =
            "";

    }


    menuButton
        ?.addEventListener(
            "click",
            abrirSidebar
        );


    closeButton
        ?.addEventListener(
            "click",
            cerrarSidebar
        );


    overlay
        ?.addEventListener(
            "click",
            cerrarSidebar
        );


    document.addEventListener(

        "keydown",

        function(event) {

            if (
                event.key === "Escape"
            ) {

                cerrarSidebar();

            }

        }

    );

}


/* ==========================================================
   MODAL DE EMERGENCIA
========================================================== */

function configurarModal() {

    const modal =
        document.getElementById(
            "emergencyModal"
        );


    const helpButton =
        document.getElementById(
            "helpButton"
        );


    const cancelButton =
        document.getElementById(
            "cancelEmergency"
        );


    const confirmButton =
        document.getElementById(
            "confirmEmergency"
        );


    /*
        ABRIR MODAL
    */

    helpButton
        ?.addEventListener(

            "click",

            function() {

                modal
                    ?.classList
                    .add(
                        "show"
                    );


                document.body.style.overflow =
                    "hidden";

            }

        );


    /*
        CANCELAR
    */

    cancelButton
        ?.addEventListener(

            "click",

            function() {

                cerrarModalEmergencia();

            }

        );


    /*
        CERRAR AL TOCAR FONDO
    */

    modal
        ?.addEventListener(

            "click",

            function(event) {

                if (
                    event.target === modal
                ) {

                    cerrarModalEmergencia();

                }

            }

        );


    /*
        CONFIRMAR EMERGENCIA
    */

    confirmButton
        ?.addEventListener(

            "click",

            async function() {

                if (!ubicacionActual) {

                    alert(
                        "Todavía estamos obteniendo tu ubicación."
                    );

                    return;

                }


                /*
                    EVITAR DOBLE CLIC
                */

                confirmButton.disabled =
                    true;


                const textoAnterior =
                    confirmButton.innerHTML;


                confirmButton.innerHTML = `

                    <i class="fa-solid fa-spinner fa-spin"></i>

                    ENVIANDO...

                `;


                const enviado =
                    await enviarEmergenciaAlDashboard(
                        "BOTON_AYUDA"
                    );


                confirmButton.disabled =
                    false;


                confirmButton.innerHTML =
                    textoAnterior;


                if (enviado) {

                    cerrarModalEmergencia();


                    alert(
                        "Emergencia enviada al centro de monitoreo."
                    );

                }

                else {

                    alert(
                        "No fue posible enviar la emergencia. Verifica tu conexión."
                    );

                }

            }

        );

}


/* ==========================================================
   CERRAR MODAL
========================================================== */

function cerrarModalEmergencia() {

    const modal =
        document.getElementById(
            "emergencyModal"
        );


    modal
        ?.classList
        .remove(
            "show"
        );


    document.body.style.overflow =
        "";

}


/* ==========================================================
   BOTÓN LLAMAR
========================================================== */

function configurarLlamada() {

    const boton =
        document.getElementById(
            "callButton"
        );


    const numero =
        document.getElementById(
            "emergencyNumberText"
        );


    /*
        MOSTRAR NÚMERO
    */

    if (numero) {

        numero.textContent =
            NUMERO_EMERGENCIAS;

    }


    /*
        BOTÓN LLAMADA
    */

    boton
        ?.addEventListener(

            "click",

            async function() {

                if (!ubicacionActual) {

                    alert(
                        "Esperando tu ubicación GPS."
                    );

                    return;

                }


                /*
                ==========================================
                PRIMERO ENVIAMOS LA UBICACIÓN
                ==========================================
                */

                console.log(
                    "📤 Enviando ubicación antes de llamar..."
                );


                const enviado =
                    await enviarEmergenciaAlDashboard(
                        "LLAMADA"
                    );


                if (enviado) {

                    console.log(
                        "✅ Ubicación enviada al centro de monitoreo."
                    );

                }

                else {

                    /*
                        NO BLOQUEAMOS LA LLAMADA.

                        Aunque Internet falle,
                        el usuario debe poder llamar.
                    */

                    console.warn(
                        "⚠️ No se pudo enviar la ubicación, pero se continuará con la llamada."
                    );

                }


                /*
                ==========================================
                ABRIR TELÉFONO
                ==========================================
                */

                window.location.href =
                    "tel:" +
                    NUMERO_EMERGENCIAS;

            }

        );

}


/* ==========================================================
   ENVIAR EMERGENCIA AL SERVIDOR
========================================================== */

async function enviarEmergenciaAlDashboard(
    origen
) {

    /*
        NECESITAMOS GPS.
    */

    if (!ubicacionActual) {

        console.error(
            "No existe ubicación GPS."
        );

        return false;

    }


    /*
        SI YA EXISTE UNA EMERGENCIA ACTIVA,
        NO CREAMOS OTRA.

        SOLAMENTE ACTUALIZAMOS SU GPS.
    */

    if (
        emergenciaActiva &&
        idEmergenciaActiva
    ) {

        console.log(
            "Ya existe una emergencia activa:",
            idEmergenciaActiva
        );


        await actualizarUbicacionDashboard();


        return true;

    }


    /*
        OBJETO QUE RECIBE crear.php
    */

    const emergencia = {

        origen:
            origen,

        tipo:
            "EMERGENCIA",

        latitud:
            ubicacionActual.lat,

        longitud:
            ubicacionActual.lng,

        precision:
            ubicacionActual.accuracy

    };


    console.log(
        "📤 Enviando emergencia:",
        emergencia
    );


    try {

        const response =
            await fetch(

                API_CREAR_EMERGENCIA,

                {

                    method:
                        "POST",

                    headers: {

                        "Content-Type":
                            "application/json"

                    },

                    body:
                        JSON.stringify(
                            emergencia
                        ),

                    cache:
                        "no-store"

                }

            );


        /*
            ERROR HTTP
        */

        if (!response.ok) {

            const texto =
                await response.text();


            console.error(
                "Respuesta PHP:",
                texto
            );


            throw new Error(
                "HTTP " +
                response.status
            );

        }


        /*
            RESPUESTA JSON
        */

        const data =
            await response.json();


        console.log(
            "📥 Respuesta crear.php:",
            data
        );


        if (!data.ok) {

            throw new Error(
                data.mensaje ||
                "El servidor rechazó la emergencia."
            );

        }


        /*
        ==========================================
        GUARDAR ID DE MYSQL
        ==========================================
        */

        idEmergenciaActiva =
            Number(
                data.id
            );


        if (
            !idEmergenciaActiva ||
            Number.isNaN(
                idEmergenciaActiva
            )
        ) {

            throw new Error(
                "El servidor no devolvió un ID válido."
            );

        }


        emergenciaActiva =
            true;


        /*
            GUARDAMOS EL ID LOCALMENTE.

            Esto permite recuperarlo si la
            página se recarga.
        */

        localStorage.setItem(

            "idEmergenciaActiva",

            String(
                idEmergenciaActiva
            )

        );


        localStorage.setItem(

            "emergenciaActiva",

            "true"

        );


        console.log(
            "🚨 EMERGENCIA CREADA"
        );


        console.log(
            "ID:",
            idEmergenciaActiva
        );


        console.log(
            "Latitud:",
            ubicacionActual.lat
        );


        console.log(
            "Longitud:",
            ubicacionActual.lng
        );


        return true;

    }

    catch (error) {

        console.error(
            "❌ ERROR ENVIANDO EMERGENCIA:",
            error
        );


        return false;

    }

}


/* ==========================================================
   ACTUALIZAR UBICACIÓN EN MYSQL
========================================================== */

async function actualizarUbicacionDashboard() {

    /*
        NO ACTUALIZAMOS SI NO EXISTE
        EMERGENCIA.
    */

    if (
        !emergenciaActiva ||
        !idEmergenciaActiva ||
        !ubicacionActual
    ) {

        return false;

    }


    /*
        EVITAR QUE watchPosition()
        MANDE VARIAS PETICIONES
        SIMULTÁNEAMENTE.
    */

    if (actualizandoUbicacion) {

        return false;

    }


    actualizandoUbicacion =
        true;


    const datos = {

        id:
            idEmergenciaActiva,

        latitud:
            ubicacionActual.lat,

        longitud:
            ubicacionActual.lng,

        precision:
            ubicacionActual.accuracy

    };


    try {

        const response =
            await fetch(

                API_ACTUALIZAR_EMERGENCIA,

                {

                    method:
                        "POST",

                    headers: {

                        "Content-Type":
                            "application/json"

                    },

                    body:
                        JSON.stringify(
                            datos
                        ),

                    cache:
                        "no-store"

                }

            );


        if (!response.ok) {

            const texto =
                await response.text();


            console.error(
                "Respuesta actualizar.php:",
                texto
            );


            throw new Error(
                "HTTP " +
                response.status
            );

        }


        const resultado =
            await response.json();


        if (!resultado.ok) {

            throw new Error(
                resultado.mensaje ||
                "No se pudo actualizar la ubicación."
            );

        }


        console.log(

            "📍 GPS actualizado en servidor:",

            {
                id:
                    idEmergenciaActiva,

                lat:
                    ubicacionActual.lat,

                lng:
                    ubicacionActual.lng
            }

        );


        return true;

    }

    catch (error) {

        console.error(
            "❌ Error actualizando ubicación:",
            error
        );


        return false;

    }

    finally {

        actualizandoUbicacion =
            false;

    }

}


/* ==========================================================
   RECUPERAR EMERGENCIA ACTIVA
========================================================== */

function recuperarEmergenciaActiva() {

    const idGuardado =
        localStorage.getItem(
            "idEmergenciaActiva"
        );


    const estadoGuardado =
        localStorage.getItem(
            "emergenciaActiva"
        );


    if (
        idGuardado &&
        estadoGuardado === "true"
    ) {

        const id =
            Number(
                idGuardado
            );


        if (
            Number.isFinite(id) &&
            id > 0
        ) {

            idEmergenciaActiva =
                id;


            emergenciaActiva =
                true;


            console.log(
                "Emergencia recuperada:",
                idEmergenciaActiva
            );

        }

    }

}


/* ==========================================================
   NOTIFICACIONES
========================================================== */

function configurarNotificaciones() {

    const button =
        document.getElementById(
            "notificationButton"
        );


    button
        ?.addEventListener(

            "click",

            function() {

                alert(
                    "No tienes notificaciones nuevas."
                );

            }

        );

}


/* ==========================================================
   INICIAR APP
========================================================== */

function iniciarApp() {

    console.log(
        "Iniciando CityFix..."
    );


    /*
    ======================================================
    IMPORTANTE

    Navbar, Sidebar y Modal ya son cargados
    directamente por PHP en index.php.

    NO usamos fetch() para cargarlos.
    ======================================================
    */


    /*
        RECUPERAR EMERGENCIA
    */

    recuperarEmergenciaActiva();


    /*
        CONFIGURAR INTERFAZ
    */

    configurarSidebar();

    configurarModal();

    configurarLlamada();

    configurarControlesMapa();

    configurarNotificaciones();


    /*
        MAPA
    */

    iniciarMapa();


    /*
        CORREGIR DIMENSIONES LEAFLET
    */

    setTimeout(

        function() {

            if (map) {

                map.invalidateSize();

            }

        },

        200

    );


    /*
        GPS
    */

    iniciarGPS();


    console.log(
        "CityFix iniciado."
    );

}


/* ==========================================================
   LIMPIAR GPS
========================================================== */

window.addEventListener(

    "beforeunload",

    function() {

        if (
            watchId !== null &&
            navigator.geolocation
        ) {

            navigator.geolocation
                .clearWatch(
                    watchId
                );

        }

    }

);


/* ==========================================================
   ARRANCAR
========================================================== */

document.addEventListener(

    "DOMContentLoaded",

    iniciarApp

);