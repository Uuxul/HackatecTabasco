const map = L.map("map", {
    zoomControl: false
}).setView([20.9754, -89.6169], 14);

L.tileLayer(
    "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
    {
        maxZoom: 20,
        subdomains: ["mt0", "mt1", "mt2", "mt3"]
    }
).addTo(map);


let marcadorUsuario = null;
let circuloPrecision = null;
let primeraUbicacion = true;


/* ========================================
   UBICACIÓN AUTOMÁTICA
======================================== */

function iniciarUbicacion() {

    if (!navigator.geolocation) {
        mostrarErrorGPS(
            "Tu dispositivo no permite geolocalización."
        );
        return;
    }


    document.getElementById("estadoGPS").textContent =
        "Buscando tu ubicación...";


    navigator.geolocation.watchPosition(

        actualizarUbicacion,

        errorUbicacion,

        {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 15000
        }

    );
}


/* ========================================
   ACTUALIZAR POSICIÓN
======================================== */

function actualizarUbicacion(position) {

    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const precision = position.coords.accuracy;

    const posicion = [lat, lng];


    document.getElementById("estadoGPS").innerHTML =
        `<i class="fa-solid fa-location-dot"></i>
         Ubicación activa · ±${Math.round(precision)} m`;


    /* CÍRCULO DE PRECISIÓN */

    if (!circuloPrecision) {

        circuloPrecision = L.circle(posicion, {
            radius: precision,
            color: "#4285F4",
            fillColor: "#4285F4",
            fillOpacity: 0.12,
            weight: 1
        }).addTo(map);

    } else {

        circuloPrecision.setLatLng(posicion);
        circuloPrecision.setRadius(precision);

    }


    /* PUNTO AZUL */

    if (!marcadorUsuario) {

        const iconoUsuario = L.divIcon({

            className: "",

            html: `
                <div class="google-location">
                    <div class="location-pulse"></div>
                    <div class="location-dot"></div>
                </div>
            `,

            iconSize: [40, 40],

            iconAnchor: [20, 20]

        });


        marcadorUsuario = L.marker(
            posicion,
            {
                icon: iconoUsuario,
                zIndexOffset: 1000
            }
        )
        .addTo(map)
        .bindPopup(
            "<strong>Tu ubicación actual</strong>"
        );

    } else {

        marcadorUsuario.setLatLng(posicion);

    }


    /* CENTRAR SOLO LA PRIMERA VEZ */

    if (primeraUbicacion) {

        map.flyTo(
            posicion,
            18,
            {
                duration: 1.3
            }
        );

        primeraUbicacion = false;

    }
}


/* ========================================
   ERROR
======================================== */

function errorUbicacion(error) {

    let mensaje =
        "No pudimos obtener tu ubicación.";

    if (error.code === 1) {
        mensaje =
            "Permite el acceso a tu ubicación.";
    }

    if (error.code === 2) {
        mensaje =
            "Tu ubicación no está disponible.";
    }

    if (error.code === 3) {
        mensaje =
            "El GPS tardó demasiado.";
    }


    mostrarErrorGPS(mensaje);
}


function mostrarErrorGPS(mensaje) {

    document.getElementById(
        "estadoGPS"
    ).textContent = mensaje;

}


/* ========================================
   INICIAR AUTOMÁTICAMENTE
======================================== */

window.addEventListener(
    "load",
    iniciarUbicacion
);