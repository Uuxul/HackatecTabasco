"use strict";


/* ==========================================================
   CONFIGURACIÓN
========================================================== */

const NUMERO_EMERGENCIAS = "9971557245";


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



/* ==========================================================
   CARGAR INCLUDE
========================================================== */

async function cargarInclude(
    contenedor,
    archivo
) {

    try {

        const respuesta =
            await fetch(archivo);


        if (!respuesta.ok) {

            throw new Error(
                `No se pudo cargar ${archivo}`
            );

        }


        const html =
            await respuesta.text();


        document.getElementById(
            contenedor
        ).innerHTML = html;


        return true;

    }

    catch (error) {

        console.error(
            error
        );


        return false;

    }

}



/* ==========================================================
   CARGAR COMPONENTES
========================================================== */

async function cargarComponentes() {

    await Promise.all([

        cargarInclude(
            "sidebar-container",
            "include/sidebar.html"
        ),

        cargarInclude(
            "navbar-container",
            "include/navbar.html"
        ),

        cargarInclude(
            "modal-container",
            "include/modal.html"
        )

    ]);

}



/* ==========================================================
   MAPA
========================================================== */

function iniciarMapa() {

    map = L.map(
        "map",
        {

            zoomControl:
                false,

            attributionControl:
                false

        }
    );


    /*
        Esta posición solamente aparece
        mientras obtenemos el GPS.

        Después el mapa se mueve automáticamente
        a la ubicación REAL del teléfono.
    */

    map.setView(
        [
            20.9754,
            -89.6169
        ],
        15
    );


    /*
        MISMO MAPA DEL DASHBOARD
    */

    L.tileLayer(

        "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",

        {

            maxZoom:
                20,

            subdomains: [

                "mt0",
                "mt1",
                "mt2",
                "mt3"

            ]

        }

    ).addTo(map);

}



/* ==========================================================
   ICONO USUARIO
========================================================== */

function crearIconoUsuario() {

    return L.divIcon({

        className:
            "",


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

    if (
        !navigator.geolocation
    ) {

        actualizarTextoGPS(
            "GPS no disponible"
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

}



/* ==========================================================
   POSICIÓN RECIBIDA
========================================================== */

function posicionRecibida(
    position
) {

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


    actualizarMapaUsuario();


    /*
        Si ya existe una emergencia,
        continuamos mandando la posición.
    */

    if (
        emergenciaActiva &&
        idEmergenciaActiva
    ) {

        actualizarUbicacionDashboard();

    }

}



/* ==========================================================
   ACTUALIZAR MAPA
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


    actualizarTextoGPS(

        `GPS activo · ±${Math.round(
            ubicacionActual.accuracy
        )} m`

    );


    /*
        ACTUALIZAR INFORMACIÓN DEL MODAL
    */

    const gpsPreview =
        document.getElementById(
            "gpsPreview"
        );


    if (
        gpsPreview
    ) {

        gpsPreview.innerHTML = `

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
        MARCADOR
    */

    if (
        !marcadorUsuario
    ) {

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

    if (
        !circuloPrecision
    ) {

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
                        .08,

                    opacity:
                        .25,

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
        LA PRIMERA VEZ
    */

    if (
        primeraUbicacion
    ) {

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

function actualizarTextoGPS(
    texto
) {

    const elemento =
        document.getElementById(
            "locationText"
        );


    if (
        elemento
    ) {

        elemento.textContent =
            texto;

    }

}



/* ==========================================================
   ERROR GPS
========================================================== */

function errorGPS(
    error
) {

    console.error(
        "Error GPS:",
        error
    );


    let mensaje =
        "No pudimos obtener tu ubicación";


    if (
        error.code === 1
    ) {

        mensaje =
            "Permite el acceso a tu ubicación";

    }


    else if (
        error.code === 2
    ) {

        mensaje =
            "Ubicación no disponible";

    }


    else if (
        error.code === 3
    ) {

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


    document.getElementById(
        "centerLocation"
    )

    ?.addEventListener(

        "click",

        function() {


            if (
                !ubicacionActual
            ) {

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
                        .8

                }

            );

        }

    );



    document.getElementById(
        "zoomLocation"
    )

    ?.addEventListener(

        "click",

        function() {


            if (
                !ubicacionActual
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
                        .8

                }

            );

        }

    );

}



/* ==========================================================
   MODAL
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



    helpButton
        ?.addEventListener(

            "click",

            function() {

                modal
                    ?.classList
                    .add(
                        "show"
                    );

            }

        );



    cancelButton
        ?.addEventListener(

            "click",

            function() {

                modal
                    ?.classList
                    .remove(
                        "show"
                    );

            }

        );



    confirmButton
        ?.addEventListener(

            "click",

            async function() {


                if (
                    !ubicacionActual
                ) {

                    alert(
                        "Todavía estamos obteniendo tu ubicación."
                    );

                    return;

                }


                const enviado =
                    await enviarEmergenciaAlDashboard(
                        "BOTON_AYUDA"
                    );


                if (
                    enviado
                ) {

                    modal
                        ?.classList
                        .remove(
                            "show"
                        );


                    alert(
                        "Emergencia enviada al centro de monitoreo."
                    );

                }

            }

        );

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


    if (
        numero
    ) {

        numero.textContent =
            "";

    }



    boton
        ?.addEventListener(

            "click",

            function() {


                if (
                    !ubicacionActual
                ) {

                    alert(
                        "Esperando tu ubicación GPS."
                    );

                    return;

                }


                /*
                    NO usamos await.

                    Intentamos enviar la ubicación
                    y abrimos inmediatamente el
                    marcador del teléfono.
                */

                enviarEmergenciaAlDashboard(
                    "LLAMADA"
                );


                window.location.href =
                    "tel:" +
                    NUMERO_EMERGENCIAS;

            }

        );

}



/* ==========================================================
   ENVIAR EMERGENCIA AL ADMIN
========================================================== */

async function enviarEmergenciaAlDashboard(
    origen
) {

    if (
        !ubicacionActual
    ) {

        return false;

    }


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
            ubicacionActual.accuracy,

        estado:
            "ACTIVA",

        fecha:
            new Date().toISOString()

    };



    /*
        PARA PRUEBAS
    */

    localStorage.setItem(

        "ultimaEmergencia",

        JSON.stringify(
            emergencia
        )

    );


    console.log(
        "Enviando emergencia:",
        emergencia
    );



    /*
        API REAL
    */

    try {


        const response =
            await fetch(

                API_EMERGENCIAS,

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
                        )

                }

            );


        if (
            !response.ok
        ) {

            throw new Error(
                "Error enviando emergencia"
            );

        }


        const data =
            await response.json();


        idEmergenciaActiva =
            data.id;


        emergenciaActiva =
            true;


        return true;

    }

    catch (
        error
    ) {


        console.warn(

            "Backend todavía no conectado:",

            error

        );


        return true;

    }

}



/* ==========================================================
   ACTUALIZAR UBICACIÓN EN EL ADMIN
========================================================== */

async function actualizarUbicacionDashboard() {

    if (
        !idEmergenciaActiva ||
        !ubicacionActual
    ) {

        return;

    }


    try {


        await fetch(

            `${API_EMERGENCIAS}/${idEmergenciaActiva}/ubicacion`,

            {

                method:
                    "PATCH",

                headers: {

                    "Content-Type":
                        "application/json"

                },

                body:
                    JSON.stringify({

                        latitud:
                            ubicacionActual.lat,

                        longitud:
                            ubicacionActual.lng,

                        precision:
                            ubicacionActual.accuracy,

                        fecha:
                            new Date().toISOString()

                    })

            }

        );

    }

    catch (
        error
    ) {

        console.warn(

            "No se pudo actualizar la ubicación:",

            error

        );

    }

}



/* ==========================================================
   INICIAR APP
========================================================== */

async function iniciarApp() {


    /*
        Primero cargamos navbar,
        sidebar y modal.
    */

    await cargarComponentes();



    /*
        Después configuramos los eventos
        que dependen de esos componentes.
    */

    configurarModal();

    configurarLlamada();

    configurarControlesMapa();



    /*
        Inicializamos mapa.
    */

    iniciarMapa();



    /*
        Ajustamos Leaflet.
    */

    setTimeout(

        function() {

            map.invalidateSize();

        },

        200

    );



    /*
        GPS AUTOMÁTICO.
    */

    iniciarGPS();

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