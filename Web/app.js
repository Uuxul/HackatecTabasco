// ==========================================
// 1. RELOJ Y FECHA EN TIEMPO REAL
// ==========================================
function actualizarReloj() {
    const ahora = new Date();

    const opcionesFecha = { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' };
    const fechaFormateada = ahora.toLocaleDateString('es-MX', opcionesFecha);

    const opcionesHora = { hour: '2-digit', minute: '2-digit', hour12: true };
    const horaFormateada = ahora.toLocaleTimeString('es-MX', opcionesHora);

    document.getElementById('fecha-actual').textContent = fechaFormateada;
    document.getElementById('hora-actual').textContent = horaFormateada;
}

actualizarReloj();
setInterval(actualizarReloj, 1000);


// ==========================================
// 2. INICIALIZACIÓN DEL MAPA
// ==========================================
const map = L.map('map').setView([20.9754, -89.6169], 14);

L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
    maxZoom: 20,
    subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
    attribution: '&copy; <a href="https://maps.google.com">Google Maps</a>'
}).addTo(map);


// ==========================================
// 3. FUNCIÓN PARA CREAR MARCADORES PERSONALIZADOS
// ==========================================
function createCustomIcon(className, label, iconHtml = '') {
    return L.divIcon({
        className: 'custom-marker-wrapper',
        html: `<div class="custom-marker ${className}">
                    ${iconHtml}
                    <div class="marker-label">${label}</div>
               </div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
}


// ==========================================
// 4. GEOLOCALIZACIÓN AUTOMÁTICA AL CARGAR
// ==========================================
let marcadorUsuario = null;
let circuloUsuario = null;

// Se dispara solo al abrir la página
map.locate({
    setView: true,
    maxZoom: 17,
    enableHighAccuracy: true,
    timeout: 10000,
    watch: false
});

map.on('locationfound', function (e) {
    if (marcadorUsuario) map.removeLayer(marcadorUsuario);
    if (circuloUsuario) map.removeLayer(circuloUsuario);

    // Círculo de precisión
    circuloUsuario = L.circle(e.latlng, {
        radius: e.accuracy / 2,
        color: '#3B82F6',
        fillColor: '#DBEAFE',
        fillOpacity: 0.25,
        weight: 1
    }).addTo(map);

    // Marcador
    marcadorUsuario = L.marker(e.latlng, {
        icon: createCustomIcon(
            'marker-location',
            'Tú',
            '<i class="fa-solid fa-location-crosshairs"></i>'
        )
    }).addTo(map)
      .bindPopup(
          `<b>Tu ubicación actual</b><br>
           Lat: ${e.latlng.lat.toFixed(6)}<br>
           Lng: ${e.latlng.lng.toFixed(6)}<br>
           Precisión: ±${Math.round(e.accuracy)} m`
      );
});

map.on('locationerror', function (e) {
    console.warn('Geolocalización fallida:', e.message);
    // Si falla, el mapa queda en la vista por defecto (Mérida)
});