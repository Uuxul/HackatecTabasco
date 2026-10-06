/* =========================================================
   CityFix Admin — Monitoreo de Patrullas
   ========================================================= */

// Centro del mapa (Mérida, Yucatán — como en tu screenshot)
const CENTER = { lat: 20.9674, lng: -89.6237 };

// Estado global
const state = {
  patrols: [],       // {id, zone, status, coords, marker, target}
  markerLayer: null,
  routeLayer: null,
  simInterval: null,
  nextId: 1
};

/* ---------- MAPA ---------- */
function initMap() {
  const map = L.map('map', { zoomControl: false }).setView([CENTER.lat, CENTER.lng], 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map);

  L.control.zoom({ position: 'topright' }).addTo(map);

  state.markerLayer = L.layerGroup().addTo(map);
  state.routeLayer = L.layerGroup().addTo(map);
  state.map = map;
}

/* ---------- UTILIDADES ---------- */
function randomOffset(km = 1.5) {
  // ~1 grado lat = 111 km
  const dLat = (Math.random() - 0.5) * (km / 111) * 2;
  const dLng = (Math.random() - 0.5) * (km / 111) * 2 / Math.cos(CENTER.lat * Math.PI / 180);
  return { lat: CENTER.lat + dLat, lng: CENTER.lng + dLng };
}

function markerIcon(status) {
  const colors = {
    available: '#10b981',
    en_route: '#f59e0b',
    on_scene: '#f97316',
    busy: '#ef4444'
  };
  return L.divIcon({
    html: `<div class="patrol-marker" style="color:${colors[status] || '#38bdf8'}">🚓</div>`,
    className: '',
    iconSize: [30, 30],
    iconAnchor: [15, 15]
  });
}

/* ---------- GESTIÓN DE PATRULLAS ---------- */
function addPatrol(id = null, zone = null, status = 'available') {
  const patrolId = id || `P-${String(state.nextId++).padStart(2, '0')}`;
  if (state.patrols.find(p => p.id === patrolId)) {
    alert('Ya existe una patrulla con ese ID');
    return null;
  }

  const coords = randomOffset(1.5);
  const patrol = {
    id: patrolId,
    zone: zone || 'Centro',
    status,
    coords,
    target: null
  };

  const marker = L.marker([coords.lat, coords.lng], { icon: markerIcon(status) })
    .addTo(state.markerLayer)
    .bindPopup(`<b>🚓 ${patrol.id}</b><br>${patrol.zone}<br>Estado: ${patrol.status}`);

  patrol.marker = marker;
  state.patrols.push(patrol);
  renderList();
  renderStats();
  return patrol;
}

function removePatrol(id) {
  const idx = state.patrols.findIndex(p => p.id === id);
  if (idx === -1) return;
  const p = state.patrols[idx];
  state.markerLayer.removeLayer(p.marker);
  state.patrols.splice(idx, 1);
  renderList();
  renderStats();
}

function setStatus(patrol, status) {
  patrol.status = status;
  patrol.marker.setIcon(markerIcon(status));
  patrol.marker.setPopupContent(
    `<b>🚓 ${patrol.id}</b><br>${patrol.zone}<br>Estado: ${status}`
  );
  renderList();
  renderStats();
}

/* ---------- RENDER UI ---------- */
function renderList() {
  const container = document.getElementById('list-container');
  if (state.patrols.length === 0) {
    container.innerHTML = '<p style="color:#64748b;font-size:12px">Sin patrullas registradas.</p>';
    return;
  }
  container.innerHTML = state.patrols.map(p => `
    <div class="patrol-card ${p.status}">
      <div>
        <div class="id">🚓 ${p.id}</div>
        <div style="color:#64748b;font-size:10px">${p.zone}</div>
        <div class="status ${p.status}">${p.status.replace('_', ' ')}</div>
      </div>
      <button class="danger" onclick="removePatrol('${p.id}')">✕</button>
    </div>
  `).join('');
}

function renderStats() {
  document.getElementById('stat-total').textContent = state.patrols.length;
  document.getElementById('stat-available').textContent =
    state.patrols.filter(p => p.status === 'available').length;
  document.getElementById('stat-busy').textContent =
    state.patrols.filter(p => p.status === 'busy' || p.status === 'en_route').length;
}

/* ---------- MOVIMIENTO SIMULADO ---------- */
function startSimulation() {
  if (state.simInterval) clearInterval(state.simInterval);

  state.simInterval = setInterval(() => {
    state.patrols.forEach(p => {
      // Patrullas disponibles se mueven aleatoriamente (patrullaje)
      if (p.status === 'available') {
        const step = 0.0008;
        const lat = p.coords.lat + (Math.random() - 0.5) * step;
        const lng = p.coords.lng + (Math.random() - 0.5) * step;
        p.coords = { lat, lng };
        p.marker.setLatLng([lat, lng]);
      }

      // Patrullas en ruta se acercan a su objetivo
      if (p.status === 'en_route' && p.target) {
        const dLat = p.target.lat - p.coords.lat;
        const dLng = p.target.lng - p.coords.lng;
        const dist = Math.sqrt(dLat * dLat + dLng * dLng);

        if (dist < 0.0008) {
          p.coords = { ...p.target };
          p.marker.setLatLng([p.coords.lat, p.coords.lng]);
          setStatus(p, 'on_scene');
          // Liberar después de 5s
          setTimeout(() => {
            if (p.status === 'on_scene') {
              p.target = null;
              setStatus(p, 'available');
            }
          }, 5000);
        } else {
          const factor = 0.08;
          p.coords = {
            lat: p.coords.lat + dLat * factor,
            lng: p.coords.lng + dLng * factor
          };
          p.marker.setLatLng([p.coords.lat, p.coords.lng]);
        }
      }
    });
  }, 1200);
}

/* ---------- GENERAR 3 A 6 PATRULLAS ALEATORIAS ---------- */
function generateRandomPatrols() {
  // Limpiar existentes
  state.patrols.forEach(p => state.markerLayer.removeLayer(p.marker));
  state.patrols = [];
  state.nextId = 1;

  const count = Math.floor(Math.random() * 4) + 3; // 3, 4, 5 o 6
  const zones = ['Centro', 'Norte', 'Sur', 'Oriente', 'Poniente', 'Periferia'];
  const statuses = ['available', 'available', 'available', 'busy'];

  for (let i = 0; i < count; i++) {
    const status = statuses[Math.floor(Math.random() * statuses.length)];
    addPatrol(null, zones[i % zones.length], status);
  }

  // Ajustar el centro a las patrullas creadas
  if (state.patrols.length) {
    const bounds = L.latLngBounds(state.patrols.map(p => [p.coords.lat, p.coords.lng]));
    state.map.fitBounds(bounds.pad(0.3));
  }
}

/* ---------- EVENTOS ---------- */
function bindEvents() {
  document.getElementById('btn-add').onclick = () => {
    const id = document.getElementById('input-id').value.trim();
    const zone = document.getElementById('input-zone').value.trim();
    const created = addPatrol(id || null, zone || null, 'available');
    if (created) {
      document.getElementById('input-id').value = '';
      document.getElementById('input-zone').value = '';
      state.map.setView([created.coords.lat, created.coords.lng], 15);
    }
  };

  document.getElementById('btn-random').onclick = generateRandomPatrols;

  // Permitir Enter en los inputs
  ['input-id', 'input-zone'].forEach(id => {
    document.getElementById(id).addEventListener('keydown', e => {
      if (e.key === 'Enter') document.getElementById('btn-add').click();
    });
  });
}

/* ---------- INICIALIZACIÓN ---------- */
window.addEventListener('DOMContentLoaded', () => {
  initMap();
  bindEvents();

  // Generar 3-6 patrullas al iniciar
  generateRandomPatrols();

  // Iniciar simulación de movimiento
  startSimulation();
});