<?php
/* ============================================================
   KANAN · CENTRAL DE MONITOREO VEHICULAR v2.0
   ------------------------------------------------------------
   INSTALACIÓN:
   - Guardar en C:/xampp/htdocs/central_monitoreo.php
   - Activar extension=pdo_sqlite en php.ini
   - Reiniciar Apache
   - Abrir http://localhost/central_monitoreo.php
   - Requiere Internet (TensorFlow.js, COCO-SSD, Leaflet, OSM)
   ============================================================ */

/* ------------------------------------------------------------
   1. CONFIGURACIÓN
   ------------------------------------------------------------ */
$CAMERA_LOCATION = ['label' => '', 'lat' => null, 'lon' => null];
// Ejemplo: ['label'=>'Cruce Calle 10 y Calle 20','lat'=>20.97,'lon'=>-89.62]
$DB_FOLDER = 'kanan_monitor_data';
$DB_FILE   = 'alertas.sqlite';
$TIMEZONE  = 'America/Mexico_City';

session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
header('Cache-Control: no-store');

/* ------------------------------------------------------------
   2. BACKEND API
   ------------------------------------------------------------ */
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if (!extension_loaded('pdo_sqlite'))
            throw new RuntimeException('Activa extension=pdo_sqlite en php.ini y reinicia Apache.');

        $dir = dirname($_SERVER['DOCUMENT_ROOT'] ?: __DIR__) . DIRECTORY_SEPARATOR . $DB_FOLDER;
        if (!is_dir($dir) && !mkdir($dir, 0700, true))
            throw new RuntimeException('No se pudo crear la carpeta de la base de datos.');

        $db = new PDO('sqlite:' . $dir . DIRECTORY_SEPARATOR . $DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA busy_timeout=5000');

        $db->exec('CREATE TABLE IF NOT EXISTS alerts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT UNIQUE NOT NULL,
            detected_at TEXT NOT NULL,
            source_type TEXT NOT NULL,
            source_name TEXT NOT NULL,
            video_second REAL NOT NULL,
            place TEXT NOT NULL,
            latitude REAL,
            longitude REAL,
            accuracy REAL,
            box_json TEXT,
            status TEXT NOT NULL DEFAULT "pending_review"
        )');

        $checkCsrf = function ($d) {
            if (!hash_equals($_SESSION['csrf'], (string)($d['csrf'] ?? ''))) {
                http_response_code(403);
                echo json_encode(['error' => 'Sesión no válida; recarga la página']);
                exit;
            }
        };
        $method = $_SERVER['REQUEST_METHOD'];
        $api = $_GET['api'];

        /* ---------- LIST ---------- */
        if ($api === 'list' && $method === 'GET') {
            echo json_encode(['events' => $db->query('SELECT * FROM alerts ORDER BY id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        /* ---------- SAVE ---------- */
        if ($api === 'save' && $method === 'POST') {
            $raw = file_get_contents('php://input');
            if (strlen($raw) > 20000) throw new InvalidArgumentException('Solicitud demasiado grande');
            $d = json_decode($raw, true);
            if (!is_array($d)) throw new InvalidArgumentException('JSON inválido');
            $checkCsrf($d);

            if (!in_array($d['source_type'] ?? '', ['file','camera'], true)
                || !is_numeric($d['video_second'] ?? null)
                || $d['video_second'] < 0
                || !preg_match('/^[a-zA-Z0-9:._-]{1,120}$/', (string)($d['event_id'] ?? '')))
                throw new InvalidArgumentException('Datos inválidos');

            foreach (['latitude'=>90,'longitude'=>180,'accuracy'=>1000000] as $f=>$lim)
                if (isset($d[$f]) && (!is_numeric($d[$f]) || abs($d[$f]) > $lim))
                    throw new InvalidArgumentException('Coordenadas inválidas');

            if ($d['source_type'] === 'file') { $d['latitude']=null; $d['longitude']=null; $d['accuracy']=null; }

            $now = (new DateTimeImmutable('now', new DateTimeZone($TIMEZONE)))->format('Y-m-d H:i:sP');
            $q = $db->prepare('INSERT INTO alerts
                (event_id,detected_at,source_type,source_name,video_second,place,latitude,longitude,accuracy,box_json)
                VALUES (?,?,?,?,?,?,?,?,?,?)
                ON CONFLICT(event_id) DO NOTHING');
            $q->execute([
                $d['event_id'], $now, $d['source_type'],
                substr((string)($d['source_name'] ?? ''), 0, 255),
                (float)$d['video_second'],
                substr((string)($d['place'] ?? 'No especificado'), 0, 255),
                $d['latitude'] ?? null, $d['longitude'] ?? null, $d['accuracy'] ?? null,
                json_encode($d['box'] ?? null)
            ]);
            echo json_encode(['saved' => true]); exit;
        }

        /* ---------- UPDATE ---------- */
        if ($api === 'update' && $method === 'POST') {
            $raw = file_get_contents('php://input');
            $d = json_decode($raw, true);
            if (!is_array($d)) throw new InvalidArgumentException('JSON inválido');
            $checkCsrf($d);
            $allowed = ['pending_review','reviewed','dismissed','confirmed'];
            if (!in_array($d['status'] ?? '', $allowed, true) || empty($d['event_id']))
                throw new InvalidArgumentException('Datos inválidos');
            $q = $db->prepare('UPDATE alerts SET status = ? WHERE event_id = ?');
            $q->execute([$d['status'], $d['event_id']]);
            echo json_encode(['updated' => true]); exit;
        }

        /* ---------- DELETE ---------- */
        if ($api === 'delete' && $method === 'POST') {
            $raw = file_get_contents('php://input');
            $d = json_decode($raw, true);
            if (!is_array($d)) throw new InvalidArgumentException('JSON inválido');
            $checkCsrf($d);
            if (empty($d['event_id'])) throw new InvalidArgumentException('Datos inválidos');
            $q = $db->prepare('DELETE FROM alerts WHERE event_id = ?');
            $q->execute([$d['event_id']]);
            echo json_encode(['deleted' => true]); exit;
        }

        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);

    } catch (InvalidArgumentException $e) {
        http_response_code(400); echo json_encode(['error' => $e->getMessage()]);
    } catch (Throwable $e) {
        http_response_code(500); error_log($e->getMessage());
        echo json_encode(['error' => ($e instanceof RuntimeException && !($e instanceof PDOException))
            ? $e->getMessage()
            : 'No se pudo procesar. Revisa permisos y el log de Apache.']);
    }
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>KANAN · Central de monitoreo</title>

<!-- Leaflet (mapa) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
/* ============================================================
   CSS · TEMA CLARO ESTILO DASHBOARD
   ============================================================ */
:root{
  --bg:#f1f4f9;
  --panel:#ffffff;
  --sidebar:#0f1e33;
  --sidebar-hover:#1a2f4d;
  --border:#e2e8f0;
  --text:#0f172a;
  --muted:#64748b;
  --accent:#1d4ed8;
  --accent-light:#e0e9ff;
  --danger:#dc2626;
  --danger-light:#fee2e2;
  --success:#16a34a;
  --success-light:#dcfce7;
  --warn:#d97706;
  --warn-light:#fef3c7;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);
  font:14px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}

.layout{display:grid;grid-template-columns:230px 1fr;min-height:100vh}

/* -------- SIDEBAR -------- */
.sidebar{background:var(--sidebar);color:#cbd5e1;padding:20px 0;display:flex;flex-direction:column}
.brand{display:flex;align-items:center;gap:10px;padding:0 20px 22px;border-bottom:1px solid #1e293b}
.brand .logo{width:38px;height:38px;background:#fff;border-radius:9px;display:grid;place-items:center;font-weight:900;color:var(--sidebar);font-size:18px}
.brand h2{margin:0;font-size:15px;color:#fff;letter-spacing:1px}
.brand small{font-size:10px;color:#94a3b8;letter-spacing:1px}
.nav{list-style:none;padding:14px 0;margin:0;flex:1}
.nav li{display:flex;align-items:center;gap:12px;padding:12px 20px;cursor:pointer;font-size:14px;transition:.15s;border-left:3px solid transparent}
.nav li:hover{background:var(--sidebar-hover)}
.nav li.active{background:var(--accent);color:#fff;border-left-color:#60a5fa}
.nav li .badge{background:var(--danger);color:#fff;border-radius:10px;font-size:11px;padding:1px 7px;margin-left:auto}
.sidebar footer{padding:14px 20px;font-size:11px;color:#64748b;border-top:1px solid #1e293b}

/* -------- MAIN -------- */
.main{padding:22px 26px;overflow-x:hidden}
.topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px}
.topbar h1{margin:0 0 4px;font-size:24px}
.topbar .sub{color:var(--muted);font-size:13px}
.topbar .right{text-align:right;font-size:12px;color:var(--muted)}
.topbar .right strong{display:block;color:var(--text);font-size:14px;margin-top:2px}

/* -------- CARDS -------- */
.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.card{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:16px;
  display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,.03)}
.card .icon{width:44px;height:44px;border-radius:10px;display:grid;place-items:center;font-size:20px}
.card .icon.red{background:var(--danger-light);color:var(--danger)}
.card .icon.blue{background:var(--accent-light);color:var(--accent)}
.card .icon.orange{background:var(--warn-light);color:var(--warn)}
.card .icon.green{background:var(--success-light);color:var(--success)}
.card .num{font-size:22px;font-weight:700;line-height:1.1}
.card .lbl{font-size:12px;color:var(--muted)}

/* -------- PANEL -------- */
.panel{background:var(--panel);border:1px solid var(--border);border-radius:12px;
  padding:16px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,.03)}
.panel h3{margin:0 0 14px;font-size:14px;display:flex;align-items:center;gap:8px;color:var(--text)}
.panel h3 .dot{width:8px;height:8px;border-radius:50%;background:var(--accent)}

/* -------- CONTROLES -------- */
.controls{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px}
button,.file{background:var(--accent);color:#fff;border:0;border-radius:8px;padding:9px 15px;
  font:inherit;font-size:13px;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:6px}
button:hover,.file:hover{background:#1e40af}
button:disabled{opacity:.5;cursor:default}
button.secondary{background:#eef2f7;color:var(--text)}
button.secondary:hover{background:#dde5ee}
button.danger{background:var(--danger)}
button.danger:hover{background:#b91c1c}
.file input{display:none}

.settings{display:flex;gap:18px;flex-wrap:wrap;margin-top:12px}
.settings label{display:grid;gap:6px;flex:1;min-width:180px;font-size:12px;color:var(--muted)}
input[type=range]{width:100%;accent-color:var(--accent)}
select{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:7px;
  font:inherit;font-size:13px;background:#fff;color:var(--text)}

/* -------- VISOR -------- */
.viewer{position:relative;background:#000;border-radius:10px;overflow:hidden;
  height:60vh;display:flex;align-items:center;justify-content:center;border:1px solid var(--border)}
video{width:100%;height:100%;object-fit:contain;display:block}
canvas{position:absolute;inset:0;width:100%;height:100%;pointer-events:none}
.empty{color:#94a3b8;font:15px monospace;letter-spacing:3px}
.status{font:13px monospace;color:var(--success);margin:10px 0}
.status.error{color:var(--danger)}
.alarm{position:absolute;top:16px;left:50%;transform:translateX(-50%);
  background:var(--danger);color:#fff;border-radius:8px;padding:12px 20px;
  font-weight:800;z-index:3;text-align:center;box-shadow:0 0 30px rgba(220,38,38,.5)}
.alarm span{display:block;font-size:11px;font-weight:400;margin-top:4px}

.metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;
  font:11px monospace;color:var(--muted);margin-top:12px}
.metrics strong{display:block;color:var(--text);font-size:16px;margin-top:4px;font-family:system-ui}

/* -------- MAPA -------- */
.map-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px}
.map-actions{display:flex;gap:8px}
#map{height:420px;border-radius:10px;border:1px solid var(--border);z-index:1}
.map-legend{display:flex;gap:16px;flex-wrap:wrap;margin-top:10px;font-size:12px;color:var(--muted);align-items:center;justify-content:center}
.map-legend .lg{display:flex;align-items:center;gap:6px}
.map-legend .dot{width:12px;height:12px;border-radius:50%;display:inline-block}

/* -------- TABLA -------- */
table{width:100%;border-collapse:collapse;font:12px monospace;background:#fff;border-radius:10px;overflow:hidden;border:1px solid var(--border)}
td,th{text-align:left;border-bottom:1px solid var(--border);padding:10px;vertical-align:top}
th{color:var(--muted);background:#f8fafc;font-weight:600;font-size:11px;letter-spacing:.5px}
tr:last-child td{border-bottom:0}
tr:hover td{background:#f8fafc}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}
.badge.pending{background:var(--warn-light);color:#92400e}
.badge.reviewed{background:var(--success-light);color:#166534}
.badge.confirmed{background:var(--danger-light);color:#991b1b}
.badge.dismissed{background:#e2e8f0;color:#334155}

small{color:var(--muted);font-size:11px;display:block;margin-top:14px}

@media(max-width:980px){
  .layout{grid-template-columns:1fr}
  .sidebar{display:none}
  .cards{grid-template-columns:1fr 1fr}
  .metrics{grid-template-columns:1fr 1fr}
  .viewer{height:45vh}
  #map{height:320px}
}
</style>
</head>
<body>

<div class="layout">

  <!-- ============================================================
       SIDEBAR
       ============================================================ -->
  <aside class="sidebar">
    <div class="brand">
      <div class="logo">K</div>
      <div>
        <h2>KANAN</h2>
        <small>EMERGENCIAS</small>
      </div>
    </div>
    <ul class="nav">
      <li class="active">🏠 Inicio</li>
      <li>🚨 Emergencias <span class="badge" id="sideBadge">0</span></li>
      <li>🚗 Unidades</li>
      <li>📊 Estadísticas</li>
      <li>⚙️ Configuración</li>
    </ul>
    <footer>KANAN EMERGENCIAS · v2.0</footer>
  </aside>

  <!-- ============================================================
       MAIN
       ============================================================ -->
  <main class="main">

    <!-- TOPBAR -->
    <div class="topbar">
      <div>
        <h1>Resumen operativo</h1>
        <div class="sub">Monitoreo vehicular · Canal 01</div>
      </div>
      <div class="right">
        <div id="clock">—</div>
      </div>
    </div>

    <!-- CARDS -->
    <div class="cards">
      <div class="card">
        <div class="icon red">🚨</div>
        <div>
          <div class="num" id="cardPending">0</div>
          <div class="lbl">Eventos pendientes</div>
        </div>
      </div>
      <div class="card">
        <div class="icon blue">🚗</div>
        <div>
          <div class="num" id="count">00</div>
          <div class="lbl">Vehículos detectados</div>
        </div>
      </div>
      <div class="card">
        <div class="icon orange">⚠️</div>
        <div>
          <div class="num" id="eventCount">00</div>
          <div class="lbl">Alertas en esta sesión</div>
        </div>
      </div>
      <div class="card">
        <div class="icon green">✅</div>
        <div>
          <div class="num" id="cardReviewed">0</div>
          <div class="lbl">Eventos revisados</div>
        </div>
      </div>
    </div>

    <!-- CONTROLES + VISOR -->
    <div class="panel">
      <h3><span class="dot"></span> Análisis en vivo · Canal 01</h3>
      <div id="modelStatus" style="font-size:12px;color:var(--muted);margin-bottom:10px">Cargando IA…</div>

      <div class="controls">
        <button id="camera">📹 Activar cámara</button>
        <select hidden id="devices" aria-label="Seleccionar cámara">
          <option value="">Cámara predeterminada</option>
        </select>
        <label class="file">📁 Cargar video<input id="file" type="file" accept="video/*"></label>
        <button hidden id="pause" disabled class="secondary">Pausar</button>
        <button hidden id="stop" disabled class="danger">Detener</button>
      </div>

      <div hidden class="settings">
        <label>Confianza mínima: <output id="cval">35%</output>
          <input id="confidence" type="range" min="15" max="85" value="45"></label>
        <label>Velocidad
          <select id="rate">
            <option value="1">Normal</option>
            <option value="0.5">0.5×</option>
            <option value="0.25">0.25×</option>
          </select></label>
        <label>Umbral de brusquedad: <output id="bval">12 px/s</output>
          <input id="brusqueness" type="range" min="2" max="150" value="12"></label>
      </div>

      <div id="status" class="status" style="margin-top:12px">Selecciona cámara o video para comenzar.</div>

      <div class="viewer">
        <div id="alarm" class="alarm" hidden>⚠ POSIBLE ACCIDENTE<span>Evento pendiente de revisión</span></div>
        <div id="empty" class="empty">CANAL 01 · SIN SEÑAL</div>
        <video id="video" playsinline muted></video>
        <canvas id="overlay"></canvas>
      </div>

      <div class="metrics">
        <div>REGISTRO<strong id="dbStatus">Comprobando…</strong></div>
        <div>UBICACIÓN<strong id="locationStatus">No especificada</strong></div>
        <div>MODO<strong id="modeStatus">—</strong></div>
        <div>SEGUNDO<strong id="secondStatus">0.00</strong></div>
      </div>
      <input id="seek" type="range" min="0" max="100" value="0" step="0.1" hidden style="width:100%;margin-top:10px">
    </div>

    <!-- ============================================================
         MAPA GENERAL (NUEVO MÓDULO)
         ============================================================ -->
    <div class="panel">
      <div class="map-header">
        <h3 style="margin:0"><span class="dot"></span> 🗺️ Mapa general — KANAN EMERGENCIAS</h3>
        <div class="map-actions">
          <button class="secondary" id="btnLocate">📍 Mi ubicación</button>
          <button id="btnFocus">🎯 Centrar eventos</button>
        </div>
      </div>
      <div id="map"></div>
      <div class="map-legend">
        <div class="lg"><span class="dot" style="background:#d97706"></span> Pendiente</div>
        <div class="lg"><span class="dot" style="background:#16a34a"></span> Revisado</div>
        <div class="lg"><span class="dot" style="background:#dc2626"></span> Confirmado</div>
        <div class="lg"><span class="dot" style="background:#64748b"></span> Descartado</div>
        <div class="lg"><span class="dot" style="background:#1d4ed8"></span> Tú</div>
      </div>
    </div>

    <!-- TABLA -->
    <div class="panel">
      <h3><span class="dot"></span> Eventos ocurridos · Posibles accidentes</h3>
      <div style="overflow:auto">
        <table>
          <thead>
            <tr>
              <th>FECHA / HORA</th>
              <th>FUENTE</th>
              <th>SEGUNDO</th>
              <th>LUGAR</th>
              <th>ESTADO</th>
              <th>ACCIONES</th>
            </tr>
          </thead>
          <tbody id="eventRows"></tbody>
        </table>
      </div>
      <small>Detección orientativa: requiere revisión humana. Las coordenadas del dispositivo
      solo corresponden a una cámara instalada en ese lugar.</small>
    </div>

  </main>
</div>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.22.0/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js"></script>
<script>
'use strict';

/* ---------- Referencias DOM ---------- */
const $ = id => document.getElementById(id);
const video = $('video'), overlay = $('overlay'), ctx = overlay.getContext('2d');
const sample = document.createElement('canvas');
const sc = sample.getContext('2d', { willReadFrequently: true });

/* ---------- Estado global ---------- */
const csrf = <?php echo json_encode($_SESSION['csrf']); ?>;
const cameraConfig = <?php echo json_encode($CAMERA_LOCATION); ?>;

let sourceName = '', sourceSession = '', geo = null;
let eventCount = 0, seenPairs = new Set();
let model = null, busy = false;
let vehicleTracks = [], pairs = new Map(), vehicleId = 1;
let inferenceTime = null, sceneGray = null;
let alertUntil = 0, alertBox = null, cooldown = 0;
let stream = null, url = null, running = false;
let last = 0, lastMedia = -1, mode = '', generation = 0;

/* ---------- MAPA (Leaflet) ---------- */
let map = null;
const markers = new Map();  // event_id -> marker
const eventCoords = [];     // {lat,lng,id}

function initMap() {
  map = L.map('map', { zoomControl: true, scrollWheelZoom: true })
         .setView([20.9674, -89.6237], 13); // Centro por defecto (Mérida, MX)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
  }).addTo(map);
}

function colorForStatus(st) {
  return st === 'reviewed'  ? '#16a34a'
       : st === 'confirmed' ? '#dc2626'
       : st === 'dismissed' ? '#64748b'
                            : '#d97706';
}

function buildPopup(e) {
  const dt = e.detected_at || '';
  const place = e.place || 'Sin lugar';
  const src = e.source_name || '';
  const sec = Number(e.video_second || 0).toFixed(2);
  const stMap = {
    pending_review: 'Pendiente',
    reviewed: 'Revisado',
    confirmed: 'Confirmado',
    dismissed: 'Descartado'
  };
  return `
    <div style="font:12px system-ui;min-width:200px">
      <strong style="color:#0f172a">${place}</strong><br>
      <span style="color:#64748b">${dt}</span><br>
      <span>Fuente: ${src}</span><br>
      <span>Segundo: ${sec} s</span><br>
      <span>Estado: <b style="color:${colorForStatus(e.status)}">${stMap[e.status] || e.status}</b></span>
    </div>`;
}

function upsertMarker(e) {
  if (e.latitude === null || e.longitude === null) return;
  const lat = Number(e.latitude), lng = Number(e.longitude);
  if (!isFinite(lat) || !isFinite(lng)) return;

  const color = colorForStatus(e.status);
  const icon = L.divIcon({
    className: 'kanan-pin',
    html: `<div style="width:16px;height:16px;border-radius:50%;background:${color};
                       border:2px solid #fff;box-shadow:0 0 0 2px ${color}55"></div>`,
    iconSize: [16, 16],
    iconAnchor: [8, 8]
  });

  const prev = markers.get(e.event_id);
  if (prev) {
    prev.setLatLng([lat, lng]).setIcon(icon).setPopupContent(buildPopup(e));
  } else {
    const m = L.marker([lat, lng], { icon }).addTo(map).bindPopup(buildPopup(e));
    markers.set(e.event_id, m);
    eventCoords.push({ lat, lng, id: e.event_id });
  }
}

function focusOnEvents() {
  if (!map || eventCoords.length === 0) return;
  const bounds = L.latLngBounds(eventCoords.map(c => [c.lat, c.lng]));
  map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
}

function locateMe() {
  if (!navigator.geolocation) return alert('Geolocalización no disponible');
  navigator.geolocation.getCurrentPosition(p => {
    const lat = p.coords.latitude, lng = p.coords.longitude;
    L.circleMarker([lat, lng], {
      radius: 8, color: '#1d4ed8', fillColor: '#1d4ed8', fillOpacity: 1, weight: 2
    }).addTo(map).bindPopup('📍 Tú estás aquí').openPopup();
    map.setView([lat, lng], 15);
  }, () => alert('No se pudo obtener la ubicación.'));
}

/* ---------- UI helpers ---------- */
function status(text, error = false) {
  $('status').textContent = text;
  $('status').className = 'status' + (error ? ' error' : '');
}
function reset() {
  sceneGray = null; vehicleTracks = []; pairs.clear();
  inferenceTime = null; alertUntil = 0; alertBox = null; cooldown = 0;
  $('alarm').hidden = true; lastMedia = -1;
  ctx.clearRect(0, 0, overlay.width, overlay.height);
}
function release() {
  sourceSession = crypto.randomUUID();
  seenPairs.clear(); geo = null; running = false; generation++;
  video.pause();
  if (stream) stream.getTracks().forEach(t => t.stop());
  stream = null; video.srcObject = null;
  video.removeAttribute('src'); video.load();
  if (url) URL.revokeObjectURL(url);
  url = null; reset(); mode = '';
  $('pause').disabled = true; $('stop').disabled = true; $('seek').hidden = true;
  $('empty').hidden = false;
  $('modeStatus').textContent = '—';
}
function ready() {
  overlay.width = video.videoWidth;
  overlay.height = video.videoHeight;
  sample.width = 320;
  sample.height = Math.max(1, Math.round(320 * video.videoHeight / video.videoWidth));
  reset();
  $('empty').hidden = true;
  $('pause').disabled = false; $('stop').disabled = false;
  $('pause').textContent = 'Pausar';
  running = true;
  status('Analizando · Sin movimiento');
}

/* ---------- Eventos del video ---------- */
video.addEventListener('loadedmetadata', () => {
  if (!mode) return;
  ready();
  if (mode === 'file' && Number.isFinite(video.duration)) {
    $('seek').max = video.duration; $('seek').hidden = false;
  }
});
video.addEventListener('error', () => {
  running = false;
  status('No se pudo reproducir el video. Prueba un MP4 compatible.', true);
});
video.addEventListener('ended', () => {
  running = false; reset();
  $('pause').textContent = 'Repetir';
  status('Video terminado.');
});
video.addEventListener('seeking', reset);

/* ---------- Controles ---------- */
$('confidence').oninput = () => $('cval').value = $('confidence').value + '%';
$('rate').onchange = () => video.playbackRate = Number($('rate').value);
$('brusqueness').oninput = () => $('bval').value = $('brusqueness').value + ' px/s';

/* ---------- Cámara ---------- */
$('camera').onclick = async () => {
  release();
  const token = generation;
  mode = 'camera'; sourceName = 'Cámara 01';
  $('modeStatus').textContent = 'Cámara';
  $('locationStatus').textContent = cameraConfig.label || 'Solicitando ubicación…';

  if (!cameraConfig.label && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(p => {
      if (token !== generation) return;
      geo = { lat: p.coords.latitude, lon: p.coords.longitude, accuracy: p.coords.accuracy };
      $('locationStatus').textContent = 'Dispositivo · ±' + Math.round(geo.accuracy) + ' m';
    }, () => {
      if (token === generation) $('locationStatus').textContent = 'Ubicación no disponible';
    }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
  }

  status('Solicitando acceso a la cámara…');
  try {
    if (!navigator.mediaDevices?.getUserMedia)
      throw new Error('La cámara requiere localhost o HTTPS.');
    const id = $('devices').value;
    const incoming = await navigator.mediaDevices.getUserMedia({
      video: id ? { deviceId: { exact: id } } : { width: { ideal: 1280 }, height: { ideal: 720 } },
      audio: false
    });
    if (token !== generation) { incoming.getTracks().forEach(t => t.stop()); return; }
    stream = incoming; video.srcObject = stream; await video.play();
    const devices = await navigator.mediaDevices.enumerateDevices();
    $('devices').replaceChildren(new Option('Cámara predeterminada', ''));
    for (const d of devices.filter(d => d.kind === 'videoinput'))
      $('devices').add(new Option(d.label || 'Cámara', d.deviceId));
    $('devices').value = id;
  } catch (e) {
    if (token !== generation) return;
    release();
    const messages = {
      NotAllowedError: 'Permiso denegado. Permite la cámara y vuelve a intentarlo.',
      NotFoundError: 'No se encontró una cámara conectada.',
      NotReadableError: 'La cámara está ocupada por otra aplicación.'
    };
    status(messages[e.name] || e.message, true);
  }
};

/* ---------- Cargar archivo ---------- */
$('file').onchange = async () => {
  const f = $('file').files[0];
  if (!f) return;
  release();
  mode = 'file'; sourceName = f.name;
  $('modeStatus').textContent = 'Video';
  $('locationStatus').textContent = 'Lugar del video desconocido';
  url = URL.createObjectURL(f);
  video.src = url;
  status('Cargando video…');
  try { await video.play(); }
  catch (e) {
    status('Pulsa Reproducir para iniciar el video.');
    $('pause').disabled = false;
    $('pause').textContent = 'Reproducir';
  }
  $('file').value = '';
};

/* ---------- Pausa / Stop / Seek ---------- */
$('pause').onclick = async () => {
  if (video.paused) {
    try {
      if (video.ended) video.currentTime = 0;
      await video.play();
      running = true; reset();
      $('pause').textContent = 'Pausar';
    } catch (e) { status('No se pudo iniciar: ' + e.message, true); }
  } else {
    video.pause(); running = false; reset();
    $('pause').textContent = 'Reproducir';
    status('Análisis pausado.');
  }
};
$('stop').onclick = () => { release(); status('Detenido. Selecciona cámara o video.'); };
$('seek').oninput = () => { video.currentTime = Number($('seek').value); reset(); };

/* ---------- Geometría ---------- */
function center(b) { return { x: b.x + b.w / 2, y: b.y + b.h / 2 }; }
function overlap(a, b) {
  const iw = Math.max(0, Math.min(a.x + a.w, b.x + b.w) - Math.max(a.x, b.x));
  const ih = Math.max(0, Math.min(a.y + a.h, b.y + b.h) - Math.max(a.y, b.y));
  return iw * ih / Math.min(a.w * a.h, b.w * b.h);
}

/* ---------- Tracking + colisión ---------- */
function trackVehicles(boxes, t) {
  const dt = inferenceTime === null ? 0 : t - inferenceTime;
  inferenceTime = t;
  if (dt <= 0 || dt > 1) { vehicleTracks = []; pairs.clear(); }

  const old = vehicleTracks, used = new Set();
  vehicleTracks = boxes.map(b => {
    const c = center(b);
    let match = null, cost = Infinity;
    for (const a of old) {
      if (used.has(a.id)) continue;
      const d = Math.hypot(c.x - a.x - a.vx * dt, c.y - a.y - a.vy * dt);
      if (d < cost && d < Math.max(25, Math.max(b.w, b.h) * .6)) { cost = d; match = a; }
    }
    if (!match) return { ...c, b, id: vehicleId++, vx: 0, vy: 0, age: 1 };
    used.add(match.id);
    return {
      ...c, b, id: match.id,
      vx: match.vx * .35 + (c.x - match.x) / dt * .65,
      vy: match.vy * .35 + (c.y - match.y) / dt * .65,
      age: match.age + 1
    };
  });

  let hit = null; const current = new Set();
  for (let i = 0; i < vehicleTracks.length; i++) {
    for (let j = i + 1; j < vehicleTracks.length; j++) {
      const a = vehicleTracks[i], b = vehicleTracks[j];
      const key = [a.id, b.id].sort((x, y) => x - y).join(':');
      const dist = Math.hypot(a.x - b.x, a.y - b.y);
      const contact = overlap(a.b, b.b);
      const prev = pairs.get(key); current.add(key);

      const closing = prev && dt > 0 ? (prev.dist - dist) / dt : 0;
      const relative = Math.hypot(a.vx - b.vx, a.vy - b.vy);
      const limit = Number($('brusqueness').value);
      const approaching = closing > limit * .3 ? t : (prev?.approachTime ?? -Infinity);
      const recentApproach = t - approaching < 1.5;

      const touching = contact > .12;
      const streak = touching && recentApproach ? (prev?.streak || 0) + 1 : 0;
      const duration = touching && recentApproach ? (prev?.duration || 0) + dt : 0;
      const peak = Math.max(relative, prev?.peak || 0);
      const drop = peak > limit && relative < peak * .7;
      const directionChange = prev &&
        Math.hypot((a.vx - b.vx) - prev.rvx, (a.vy - b.vy) - prev.rvy) > limit * .75;

      if (streak >= 3 && duration >= .18 && (drop || directionChange)
          && a.age >= 4 && b.age >= 4 && !seenPairs.has(key)) {
        const x = Math.min(a.b.x, b.b.x), y = Math.min(a.b.y, b.b.y);
        hit = {
          key, x, y,
          w: Math.max(a.b.x + a.b.w, b.b.x + b.b.w) - x,
          h: Math.max(a.b.y + a.b.h, b.b.y + b.b.h) - y
        };
      }
      pairs.set(key, {
        dist, relative, streak, duration, approachTime: approaching, peak,
        rvx: a.vx - b.vx, rvy: a.vy - b.vy
      });
    }
  }
  for (const key of pairs.keys()) if (!current.has(key)) pairs.delete(key);
  return hit;
}

/* ---------- Bucle principal ---------- */
async function tick(time) {
  requestAnimationFrame(tick);
  if (!model || busy || !running || video.paused || video.readyState < 2
      || time - last < 90 || video.currentTime === lastMedia) return;

  busy = true; last = time; lastMedia = video.currentTime;
  const token = generation, t = video.currentTime;
  const w = 640, h = Math.round(w * video.videoHeight / video.videoWidth);
  sample.width = w; sample.height = h;
  sc.drawImage(video, 0, 0, w, h);

  const px = sc.getImageData(0, 0, w, h).data;
  const gray = new Uint8Array(w * h);
  let delta = 0;
  for (let i = 0; i < gray.length; i++) {
    gray[i] = (px[i*4] + px[i*4+1] + px[i*4+2]) / 3;
    if (sceneGray && sceneGray.length === gray.length) delta += Math.abs(gray[i] - sceneGray[i]);
  }
  if (sceneGray && delta / gray.length > 40) {
    vehicleTracks = []; pairs.clear(); inferenceTime = null;
    alertBox = null; alertUntil = 0;
  }
  $('alarm').hidden = !(alertBox && t < alertUntil);
  sceneGray = gray;

  try {
    const predictions = await model.detect(sample, 40, Number($('confidence').value) / 100);
    if (token !== generation || !running || video.seeking
        || Math.abs(video.currentTime - t) > 1) return;

    const boxes = predictions
      .filter(p => ['car','truck','bus','motorcycle'].includes(p.class))
      .map(p => ({ x: p.bbox[0], y: p.bbox[1], w: p.bbox[2], h: p.bbox[3], score: p.score }));

    $('count').textContent = String(boxes.length).padStart(2, '0');

    const hit = trackVehicles(boxes, t);
    if (hit && t >= cooldown) {
      seenPairs.add(hit.key);
      alertBox = hit; alertUntil = t + 6; cooldown = t + 5;
      eventCount++;
      $('eventCount').textContent = String(eventCount).padStart(2, '0');
      saveEvent(hit, t, mode, sourceName, sourceSession, geo);
    }

    const active = alertBox && t < alertUntil;
    const sx = overlay.width / w, sy = overlay.height / h;
    ctx.clearRect(0, 0, overlay.width, overlay.height);
    ctx.lineWidth = Math.max(2, overlay.width / 500);
    ctx.font = `${Math.max(14, overlay.width / 70)}px monospace`;

    for (const a of vehicleTracks) {
      const red = active && overlap(a.b, alertBox) > .1;
      ctx.strokeStyle = ctx.fillStyle = red ? '#dc2626' : '#16a34a';
      ctx.strokeRect(a.b.x*sx, a.b.y*sy, a.b.w*sx, a.b.h*sy);
      ctx.fillText('VEHÍCULO ' + a.id + ' ' + Math.round(a.b.score * 100) + '%',
                   a.b.x*sx, Math.max(18, a.b.y*sy - 6));
      ctx.beginPath(); ctx.arc(a.x*sx, a.y*sy, 5, 0, Math.PI*2); ctx.fill();
    }
    if (active) {
      ctx.strokeStyle = ctx.fillStyle = '#dc2626';
      ctx.lineWidth = 4;
      ctx.strokeRect(alertBox.x*sx, alertBox.y*sy, alertBox.w*sx, alertBox.h*sy);
      ctx.fillText('POSIBLE ACCIDENTE', alertBox.x*sx, Math.max(20, alertBox.y*sy - 24));
    }

    $('alarm').hidden = !active;
    status(active ? '⚠ Posible accidente · Revisa el video'
                  : boxes.length + ' vehículo(s) detectado(s)');
    $('secondStatus').textContent = t.toFixed(2);
    if (mode === 'file') $('seek').value = video.currentTime;
  } catch (e) {
    $('modelStatus').textContent = 'Error al analizar: ' + e.message;
  } finally {
    busy = false;
  }
}

/* ---------- IA ---------- */
async function loadModel() {
  try {
    if (!window.cocoSsd) throw Error('No se descargaron las bibliotecas. Revisa tu conexión.');
    model = await cocoSsd.load({ base: 'mobilenet_v2' });
    $('modelStatus').textContent = '✅ IA lista · Reconocimiento de vehículos activo';
  } catch (e) {
    $('modelStatus').textContent = '❌ IA no disponible: ' + e.message;
    status('Se requiere Internet para descargar el modelo.', true);
  }
}
loadModel();
requestAnimationFrame(tick);

/* ---------- Overlay fit ---------- */
function fitOverlay() {
  if (!video.videoWidth) return;
  const r = video.getBoundingClientRect();
  const scale = Math.min(r.width / video.videoWidth, r.height / video.videoHeight);
  overlay.style.width = video.videoWidth * scale + 'px';
  overlay.style.height = video.videoHeight * scale + 'px';
}
window.addEventListener('resize', fitOverlay);
video.addEventListener('loadedmetadata', fitOverlay);

/* ---------- Tabla ---------- */
function cell(row, value) {
  const td = document.createElement('td');
  td.textContent = value;
  row.append(td);
}
async function refreshEvents() {
  try {
    const r = await fetch('?api=list', { cache: 'no-store' });
    const d = await r.json();
    if (!r.ok) throw Error(d.error);

    const tbody = $('eventRows');
    tbody.replaceChildren();

    let pending = 0, reviewed = 0;

    for (const e of d.events) {
      if (e.status === 'pending_review') pending++;
      if (e.status === 'reviewed') reviewed++;

      const tr = document.createElement('tr');
      cell(tr, e.detected_at);
      cell(tr, e.source_name);
      cell(tr, Number(e.video_second).toFixed(2) + ' s');
      cell(tr, e.place + (e.latitude !== null && e.longitude !== null
        ? ' · ' + Number(e.latitude).toFixed(5) + ', ' + Number(e.longitude).toFixed(5)
        : ''));

      const stTd = document.createElement('td');
      const badge = document.createElement('span');
      badge.className = 'badge ' + (e.status === 'pending_review' ? 'pending'
        : e.status === 'reviewed' ? 'reviewed'
        : e.status === 'confirmed' ? 'confirmed' : 'dismissed');
      badge.textContent = e.status === 'pending_review' ? 'Pendiente'
        : e.status === 'reviewed' ? 'Revisado'
        : e.status === 'confirmed' ? 'Confirmado' : 'Descartado';
      stTd.append(badge);
      tr.append(stTd);

      const acTd = document.createElement('td');
      const btnR = document.createElement('button');
      btnR.textContent = '✓'; btnR.title = 'Revisar';
      btnR.className = 'secondary'; btnR.style.padding = '4px 8px'; btnR.style.marginRight = '4px';
      btnR.onclick = () => updateStatus(e.event_id, 'reviewed');

      const btnD = document.createElement('button');
      btnD.textContent = '🗑'; btnD.title = 'Eliminar';
      btnD.className = 'danger'; btnD.style.padding = '4px 8px';
      btnD.onclick = () => deleteEvent(e.event_id);

      acTd.append(btnR, btnD);
      tr.append(acTd);
      tbody.append(tr);

      // --- MAPA ---
      upsertMarker(e);
    }

    $('dbStatus').textContent = 'Conectado';
    $('cardPending').textContent = pending;
    $('cardReviewed').textContent = reviewed;
    $('sideBadge').textContent = pending;
  } catch (e) {
    $('dbStatus').textContent = e.message;
  }
}

/* ---------- Guardar ---------- */
async function saveEvent(hit, t, eventMode, name, session, location) {
  const place = eventMode === 'file'
    ? 'Lugar del video no especificado'
    : cameraConfig.label || (location ? 'Ubicación del dispositivo (verificar cámara)' : 'Ubicación no disponible');
  const body = {
    csrf,
    event_id: session + ':' + hit.key,
    source_type: eventMode,
    source_name: name,
    video_second: t,
    place,
    latitude:  eventMode === 'camera' ? (cameraConfig.lat ?? location?.lat ?? null) : null,
    longitude: eventMode === 'camera' ? (cameraConfig.lon ?? location?.lon ?? null) : null,
    accuracy:  eventMode === 'camera' ? (location?.accuracy ?? null) : null,
    box: hit
  };
  $('dbStatus').textContent = 'Guardando…';
  let error;
  for (let attempt = 0; attempt < 3; attempt++) {
    try {
      const r = await fetch('?api=save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const d = await r.json();
      if (!r.ok) throw Error(d.error || 'Error de guardado');
      await refreshEvents();
      return;
    } catch (e) { error = e; }
  }
  $('dbStatus').textContent = 'NO GUARDADO: ' + error.message;
}

/* ---------- Actualizar / Eliminar ---------- */
async function updateStatus(eventId, newStatus) {
  try {
    const r = await fetch('?api=update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf, event_id: eventId, status: newStatus })
    });
    const d = await r.json();
    if (!r.ok) throw Error(d.error);
    await refreshEvents();
  } catch (e) { alert('Error: ' + e.message); }
}
async function deleteEvent(eventId) {
  if (!confirm('¿Eliminar esta alerta permanentemente?')) return;
  try {
    const r = await fetch('?api=delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf, event_id: eventId })
    });
    const d = await r.json();
    if (!r.ok) throw Error(d.error);
    markers.get(eventId)?.remove();
    markers.delete(eventId);
    const idx = eventCoords.findIndex(c => c.id === eventId);
    if (idx >= 0) eventCoords.splice(idx, 1);
    await refreshEvents();
  } catch (e) { alert('Error: ' + e.message); }
}

/* ---------- Reloj ---------- */
function updateClock() {
  $('clock').textContent = new Date().toLocaleString('es-MX', { timeZone: 'America/Mexico_City' });
}
updateClock();
setInterval(updateClock, 1000);

/* ---------- Botones del mapa ---------- */
$('btnLocate').onclick = locateMe;
$('btnFocus').onclick  = focusOnEvents;

/* ---------- Init ---------- */
initMap();
refreshEvents();
setTimeout(focusOnEvents, 800);
window.addEventListener('beforeunload', release);
</script>
</body>
</html>