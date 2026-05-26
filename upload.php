<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tokens.php';

function renderError(string $title, string $msg): string {
    $titleEsc = h($title);
    $msgEsc = h($msg);
    $pageTitle = h(app_text('upload.page_title'));
    return <<<HTML
<!DOCTYPE html><html lang="{LANG}"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$pageTitle}</title>
<link rel="stylesheet" href="assets/css/app.css"></head><body class="fx-upload-error">
<div class="box"><h2>{$titleEsc}</h2><p>{$msgEsc}</p></div>
</body></html>
HTML;
}

$token = preg_replace('/[^a-f0-9]/', '', (string)($_GET['token'] ?? ''));
if (strlen($token) !== 32 || !token_valid($token)) {
    http_response_code(403);
    echo str_replace('{LANG}', h(app_language()), renderError(app_text('upload.error_invalid_link_title'), app_text('upload.error_invalid_link_msg')));
    exit;
}

$tokenData = token_get($token);
$created = (int)($tokenData['created'] ?? time());
$message = '';
$msgType = '';
$uploaded = false;
$maxMb = (string)round(MAX_FILESIZE / 1048576);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_FILES['datei'])) {
    if (!token_valid($token)) {
        $message = app_text('upload.error_token_expired');
        $msgType = 'error';
    } else {
        $file = $_FILES['datei'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            if ($file['size'] > MAX_FILESIZE) {
                $message = app_text('upload.error_too_large', ['mb' => $maxMb]);
                $msgType = 'error';
            } else {
                $origExt = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
                if (in_array($origExt, BLOCKED_EXTENSIONS, true)) {
                    $message = app_text('upload.error_blocked_ext', ['ext' => $origExt]);
                    $msgType = 'error';
                } else {
                    $origName = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename((string)$file['name']));
                    $destPath = UPLOAD_DIR . $token . '_' . $origName;
                    foreach (glob(UPLOAD_DIR . $token . '_*') ?: [] as $old) {
                        @unlink($old);
                    }
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        token_set_file($token, $origName);
                        $uploaded = true;
                    } else {
                        $message = app_text('upload.error_save');
                        $msgType = 'error';
                    }
                }
            }
        } else {
            $errors = [
                UPLOAD_ERR_INI_SIZE => app_text('upload.error_ini_size'),
                UPLOAD_ERR_FORM_SIZE => app_text('upload.error_form_size'),
                UPLOAD_ERR_PARTIAL => app_text('upload.error_partial'),
                UPLOAD_ERR_NO_FILE => app_text('upload.error_no_file'),
                UPLOAD_ERR_NO_TMP_DIR => app_text('upload.error_no_tmp_dir'),
                UPLOAD_ERR_CANT_WRITE => app_text('upload.error_cant_write'),
            ];
            $message = $errors[$file['error']] ?? app_text('upload.error_unknown', ['code' => $file['error']]);
            $msgType = 'error';
        }
    }
}

$uploadJsText = [
    'expired' => app_text('upload.expired'),
    'uploading' => app_text('upload.uploading'),
    'scannerStarting' => app_text('upload.scanner_starting'),
    'scannerHold' => app_text('upload.scanner_hold'),
    'scannerFound' => app_text('upload.scanner_found'),
    'scannerMissingLibrary' => app_text('upload.scanner_missing_library'),
    'cameraErrorPrefix' => str_replace('{message}', '', app_text('upload.scanner_camera_error', ['message' => ''])),
];
?>
<!DOCTYPE html>
<html lang="<?= h(app_language()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?= h(app_text('upload.page_title')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="fx-upload">
<div class="card">
  <div class="logo"><?= h(app_text('app.brand')) ?></div>
  <h1><?= h(app_text('upload.title')) ?></h1>
  <p class="sub"><?= h(app_text('upload.subtitle')) ?></p>

  <?php if ($msgType === 'error'): ?>
    <div class="msg error"><?= h($message) ?></div>
  <?php endif; ?>

  <?php if (!$uploaded): ?>
  <form method="POST" enctype="multipart/form-data" id="uploadForm">
    <div class="dropzone" id="dropzone">
      <input type="file" name="datei" id="fileInput" required>
      <span class="dropzone-icon">📂</span>
      <span class="dropzone-text"><?= h(app_text('upload.drop_text')) ?></span>
      <p class="dropzone-hint"><?= h(app_text('upload.drop_hint', ['mb' => $maxMb])) ?></p>
    </div>
    <div class="file-preview" id="filePreview">
      <span class="file-icon" id="fileIcon">📄</span>
      <span class="file-name" id="fileName">–</span>
      <span class="file-size" id="fileSize"></span>
    </div>
    <div class="progress-wrap" id="progressWrap"><div class="progress-bar" id="progressBar"></div></div>
    <button type="submit" class="btn-upload" id="submitBtn" disabled><?= h(app_text('upload.upload_button')) ?></button>
  </form>
  <?php endif; ?>

  <div class="success-screen <?= $uploaded ? 'visible' : '' ?>" id="successScreen">
    <div class="success-icon">✅</div>
    <div class="success-title"><?= h(app_text('upload.success_title')) ?></div>
    <p class="success-sub"><?= h(app_text('upload.success_sub')) ?></p>
    <button class="btn-newscan" onclick="openScanner()"><?= h(app_text('upload.new_scan')) ?></button>
  </div>

  <div class="scanner-overlay" id="scannerOverlay">
    <div class="scanner-title"><?= h(app_text('upload.scanner_title')) ?></div>
    <p class="scanner-sub"><?= nl2br(h(app_text('upload.scanner_sub'))) ?></p>
    <div class="scanner-box" id="scannerBox">
      <video id="scannerVideo" playsinline autoplay muted></video>
      <canvas id="scannerCanvas"></canvas>
      <div class="scanner-corners-br"></div>
      <div class="scanner-line"></div>
    </div>
    <p class="scanner-status" id="scannerStatus"><?= h(app_text('upload.scanner_starting')) ?></p>
    <button class="btn-cancel" onclick="closeScanner()"><?= h(app_text('upload.cancel')) ?></button>
  </div>

  <div class="ttl-bar">
    <span><?= h(app_text('upload.ttl_label')) ?></span>
    <span id="countdown"></span>
  </div>
</div>
<script src="assets/vendor/jsqr/jsQR.js"></script>
<script>
const fileInput = document.getElementById('fileInput');
const dropzone = document.getElementById('dropzone');
const preview = document.getElementById('filePreview');
const submitBtn = document.getElementById('submitBtn');
const form = document.getElementById('uploadForm');
const progressWrap = document.getElementById('progressWrap');
const progressBar = document.getElementById('progressBar');
const CREATED = <?= $created ?>;
const TTL = <?= (int)TOKEN_TTL ?>;
const TXT = <?= json_encode($uploadJsText, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

(function tick() {
  const left = TTL - (Math.floor(Date.now() / 1000) - CREATED);
  const el = document.getElementById('countdown');
  if (!el) return;
  if (left <= 0) { el.textContent = TXT.expired; return; }
  const m = String(Math.floor(left / 60)).padStart(2, '0');
  const s = String(left % 60).padStart(2, '0');
  el.textContent = m + ':' + s;
  setTimeout(tick, 1000);
})();

function iconFor(name) {
  const ext = name.split('.').pop().toLowerCase();
  if (['jpg','jpeg','png','gif','webp','heic'].includes(ext)) return '🖼️';
  if (ext === 'pdf') return '📕';
  if (['mp4','mov','avi'].includes(ext)) return '🎬';
  return '📄';
}
function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}
if (fileInput) {
  fileInput.addEventListener('change', () => {
    const f = fileInput.files[0];
    if (!f) return;
    document.getElementById('fileIcon').textContent = iconFor(f.name);
    document.getElementById('fileName').textContent = f.name;
    document.getElementById('fileSize').textContent = formatSize(f.size);
    preview.classList.add('visible');
    submitBtn.disabled = false;
  });
}
if (dropzone) {
  dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
  dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
  dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
      fileInput.files = e.dataTransfer.files;
      fileInput.dispatchEvent(new Event('change'));
    }
  });
}
if (form) {
  form.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(form);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '');
    submitBtn.disabled = true;
    submitBtn.textContent = TXT.uploading;
    progressWrap.classList.add('visible');
    xhr.upload.addEventListener('progress', ev => {
      if (ev.lengthComputable) progressBar.style.width = Math.round(ev.loaded / ev.total * 100) + '%';
    });
    xhr.addEventListener('load', () => {
      if (xhr.status === 200 && xhr.responseText.includes('success-screen')) {
        document.getElementById('successScreen').classList.add('visible');
        form.style.display = 'none';
      } else {
        form.submit();
      }
    });
    xhr.addEventListener('error', () => { form.submit(); });
    xhr.send(fd);
  });
}

let scannerStream = null;
let scannerRaf = null;
let scannerActive = false;
let scannerBusy = false;
let lastScanTs = 0;
let barcodeDetector = null;
const overlay = document.getElementById('scannerOverlay');
const video = document.getElementById('scannerVideo');
const canvas = document.getElementById('scannerCanvas');
const status = document.getElementById('scannerStatus');
const ctx = canvas.getContext('2d', { willReadFrequently: true });

function openScanner() {
  overlay.classList.add('visible');
  scannerActive = true;
  scannerBusy = false;
  lastScanTs = 0;
  status.textContent = TXT.scannerStarting;
  status.className = 'scanner-status';
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    status.textContent = TXT.cameraErrorPrefix + 'getUserMedia';
    status.className = 'scanner-status error';
    return;
  }
  if ('BarcodeDetector' in window) {
    try { barcodeDetector = new BarcodeDetector({ formats: ['qr_code'] }); } catch (e) { barcodeDetector = null; }
  }
  navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false })
    .then(stream => { scannerStream = stream; video.srcObject = stream; return video.play(); })
    .then(() => { status.textContent = TXT.scannerHold; setTimeout(startScanLoop, 350); })
    .catch(err => { status.textContent = TXT.cameraErrorPrefix + err.message; status.className = 'scanner-status error'; });
}
function startScanLoop() {
  if (!scannerActive) return;
  cancelAnimationFrame(scannerRaf);
  scannerRaf = requestAnimationFrame(scanLoop);
}
function scanLoop(ts) {
  if (!scannerActive) return;
  if (!scannerBusy && ts - lastScanTs > 220) { lastScanTs = ts; scanFrame(); }
  scannerRaf = requestAnimationFrame(scanLoop);
}
async function scanFrame() {
  if (!scannerActive || scannerBusy || video.readyState < video.HAVE_ENOUGH_DATA) return;
  if (!video.videoWidth || !video.videoHeight) return;
  scannerBusy = true;
  try {
    if (barcodeDetector) {
      try {
        const detected = await barcodeDetector.detect(video);
        for (const item of detected) {
          const value = item.rawValue || '';
          if (handleQrValue(value)) return;
        }
      } catch (e) {}
    }
    if (typeof jsQR !== 'function') {
      status.textContent = TXT.scannerMissingLibrary;
      status.className = 'scanner-status error';
      return;
    }
    const vw = video.videoWidth;
    const vh = video.videoHeight;
    const maxSide = 900;
    const scale = Math.min(1, maxSide / Math.max(vw, vh));
    const cw = Math.max(1, Math.round(vw * scale));
    const ch = Math.max(1, Math.round(vh * scale));
    canvas.width = cw;
    canvas.height = ch;
    ctx.drawImage(video, 0, 0, cw, ch);
    const minSide = Math.min(cw, ch);
    const zones = [
      [0, 0, cw, ch],
      [(cw - minSide) / 2, (ch - minSide) / 2, minSide, minSide],
      [(cw - minSide * .76) / 2, (ch - minSide * .76) / 2, minSide * .76, minSide * .76],
      [(cw - minSide * .55) / 2, (ch - minSide * .55) / 2, minSide * .55, minSide * .55],
    ];
    for (const zone of zones) {
      const code = tryDecodeZone(...zone);
      if (code && handleQrValue(code.data || '')) return;
    }
  } finally {
    scannerBusy = false;
  }
}
function tryDecodeZone(x, y, w, h) {
  x = Math.max(0, Math.round(x));
  y = Math.max(0, Math.round(y));
  w = Math.max(1, Math.min(canvas.width - x, Math.round(w)));
  h = Math.max(1, Math.min(canvas.height - y, Math.round(h)));
  let imgData;
  try { imgData = ctx.getImageData(x, y, w, h); } catch (e) { return null; }
  return jsQR(imgData.data, imgData.width, imgData.height, { inversionAttempts: 'attemptBoth' });
}
function handleQrValue(value) {
  if (!value) return false;
  let target = value.trim();
  const tokenMatch = target.match(/[?&]token=([a-f0-9]{32})/i) || target.match(/^([a-f0-9]{32})$/i);
  if (!target.includes('upload.php') && !tokenMatch) return false;
  if (tokenMatch && !target.includes('upload.php')) target = 'upload.php?token=' + tokenMatch[1].toLowerCase();
  scannerActive = false;
  status.textContent = TXT.scannerFound;
  status.className = 'scanner-status found';
  setTimeout(() => { closeScanner(); window.location.href = target; }, 150);
  return true;
}
function closeScanner() {
  scannerActive = false;
  scannerBusy = false;
  overlay.classList.remove('visible');
  if (scannerRaf) { cancelAnimationFrame(scannerRaf); scannerRaf = null; }
  if (scannerStream) { scannerStream.getTracks().forEach(t => t.stop()); scannerStream = null; }
  video.srcObject = null;
}
</script>
</body>
</html>
