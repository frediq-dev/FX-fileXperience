<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tokens.php';
require_once __DIR__ . '/auth.php';

$authState = require_auth();

if ($authState === 'setup'):
$setupError = '';
$step = max(1, min(3, (int)($_POST['step'] ?? 1)));
$setupLang = (string)($_POST['app_language'] ?? 'en');
if (!in_array($setupLang, app_available_languages(), true)) $setupLang = 'en';

$savedBaseUrl = (string)($_POST['base_url'] ?? '');
$savedTokenTtl = (string)($_POST['token_ttl'] ?? '300');
$savedCronSecret = (string)($_POST['cron_secret'] ?? bin2hex(random_bytes(16)));
$savedLabels = $_POST['pw_label'] ?? [''];
$savedPasswords = $_POST['pw_value'] ?? [''];
$savedConfirms = $_POST['pw_confirm'] ?? [''];
$savedHosts = $_POST['wl_host'] ?? [''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $step === 3) {
    $pwEntries = [];
    foreach ($savedLabels as $i => $label) {
        if (trim((string)$label) === '' && trim((string)($savedPasswords[$i] ?? '')) === '') continue;
        $pwEntries[] = [
            'label' => $label,
            'password' => $savedPasswords[$i] ?? '',
            'confirm' => $savedConfirms[$i] ?? '',
        ];
    }

    $settings = [
        'language' => $setupLang,
        'base_url' => $savedBaseUrl,
        'token_ttl' => $savedTokenTtl,
        'cron_secret' => $savedCronSecret,
    ];

    $result = run_setup($settings, $pwEntries, $savedHosts);
    if ($result === true) {
        header('Location: index.php');
        exit;
    }
    $setupError = $result;
}

$setupPacks = [
    'en' => app_language_pack('en')['setup'],
    'de' => app_language_pack('de')['setup'],
];
$languageNames = [];
foreach (app_available_languages() as $langCode) {
    $languageNames[$langCode] = app_text($langCode . '.language');
}
?>
<!DOCTYPE html>
<html lang="<?= h($setupLang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title data-i18n="page_title"><?= h(app_text($setupLang . '.setup.page_title')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="fx-setup">
<div class="card">
  <div class="logo"><?= h(app_text('app.brand')) ?></div>
  <h1 data-i18n="title"><?= h(app_text($setupLang . '.setup.title')) ?></h1>
  <p class="sub" data-i18n="subtitle"><?= h(app_text($setupLang . '.setup.subtitle')) ?></p>

  <div class="wizard-steps">
    <div class="wstep <?= $step === 1 ? 'active' : 'done' ?>" id="wstep1"><span class="wstep-num">1</span><span data-i18n="step_config"><?= h(app_text($setupLang . '.setup.step_config')) ?></span></div>
    <div class="wstep <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>" id="wstep2"><span class="wstep-num">2</span><span data-i18n="step_passwords"><?= h(app_text($setupLang . '.setup.step_passwords')) ?></span></div>
    <div class="wstep <?= $step === 3 ? 'active' : '' ?>" id="wstep3"><span class="wstep-num">3</span><span data-i18n="step_whitelist"><?= h(app_text($setupLang . '.setup.step_whitelist')) ?></span></div>
  </div>

  <?php if ($setupError): ?>
    <div class="error"><?= h($setupError) ?></div>
  <?php endif; ?>

  <form method="POST" id="setupForm">
    <input type="hidden" name="step" id="stepField" value="<?= (int)$step ?>">

    <div id="step1Panel" class="setup-panel <?= $step !== 1 ? 'hidden' : '' ?>">
      <p class="section-title" data-i18n="section_language"><?= h(app_text($setupLang . '.setup.section_language')) ?></p>
      <div class="field-group">
        <label for="appLanguage" data-i18n="language_label"><?= h(app_text($setupLang . '.setup.language_label')) ?></label>
        <select name="app_language" id="appLanguage">
          <?php foreach ($languageNames as $code => $name): ?>
            <option value="<?= h($code) ?>" <?= $code === $setupLang ? 'selected' : '' ?>><?= h($name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <p class="section-title" data-i18n="section_config"><?= h(app_text($setupLang . '.setup.section_config')) ?></p>
      <div class="field-group">
        <label for="baseUrl" data-i18n="base_url_label"><?= h(app_text($setupLang . '.setup.base_url_label')) ?></label>
        <input type="url" name="base_url" id="baseUrl" value="<?= h($savedBaseUrl) ?>" placeholder="<?= h(app_text($setupLang . '.setup.base_url_placeholder')) ?>" autocomplete="url" required>
        <p class="field-hint" data-i18n="base_url_hint"><?= h(app_text($setupLang . '.setup.base_url_hint')) ?></p>
      </div>
      <div class="field-group">
        <label for="tokenTtl" data-i18n="token_ttl_label"><?= h(app_text($setupLang . '.setup.token_ttl_label')) ?></label>
        <input type="number" name="token_ttl" id="tokenTtl" value="<?= h($savedTokenTtl) ?>" min="30" max="86400" step="1" required>
        <p class="field-hint" data-i18n="token_ttl_hint"><?= h(app_text($setupLang . '.setup.token_ttl_hint')) ?></p>
      </div>
      <div class="field-group">
        <label for="cronSecret" data-i18n="cron_secret_label"><?= h(app_text($setupLang . '.setup.cron_secret_label')) ?></label>
        <input type="text" name="cron_secret" id="cronSecret" value="<?= h($savedCronSecret) ?>" placeholder="<?= h(app_text($setupLang . '.setup.cron_secret_placeholder')) ?>" autocomplete="off" required>
        <p class="field-hint" data-i18n="cron_secret_hint"><?= h(app_text($setupLang . '.setup.cron_secret_hint')) ?></p>
      </div>
      <button type="button" class="btn-primary" onclick="goStep(2)" data-i18n="next"><?= h(app_text($setupLang . '.common.next')) ?></button>
    </div>

    <div id="step2Panel" class="setup-panel <?= $step !== 2 ? 'hidden' : '' ?>">
      <p class="section-title" data-i18n="section_credentials"><?= h(app_text($setupLang . '.setup.section_credentials')) ?></p>
      <div class="warning" data-i18n="public_access_warning"><?= h(app_text($setupLang . '.setup.public_access_warning')) ?></div>
      <div id="pwList">
        <?php foreach ($savedLabels as $i => $lbl): ?>
        <div class="pw-entry" id="pw<?= (int)$i ?>">
          <?php if ($i > 0): ?>
            <button type="button" class="remove-btn" onclick="removePw(<?= (int)$i ?>)">✕</button>
          <?php endif; ?>
          <div class="field-group compact">
            <label data-i18n="password_label_name"><?= h(app_text($setupLang . '.setup.password_label_name')) ?></label>
            <input type="text" name="pw_label[]" value="<?= h((string)$lbl) ?>" placeholder="<?= h(app_text($setupLang . '.setup.password_label_placeholder')) ?>" autocomplete="off">
          </div>
          <div class="row">
            <div class="field-group compact">
              <label data-i18n="password_value"><?= h(app_text($setupLang . '.setup.password_value')) ?></label>
              <input type="password" name="pw_value[]" autocomplete="new-password">
            </div>
            <div class="field-group compact">
              <label data-i18n="password_confirm"><?= h(app_text($setupLang . '.setup.password_confirm')) ?></label>
              <input type="password" name="pw_confirm[]" autocomplete="new-password">
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-btn" onclick="addPw()" data-i18n="add_password"><?= h(app_text($setupLang . '.setup.add_password')) ?></button>
      <div class="btn-row">
        <button type="button" class="btn-secondary" onclick="goStep(1)" data-i18n="back"><?= h(app_text($setupLang . '.common.back')) ?></button>
        <button type="button" class="btn-primary" onclick="goStep(3)" data-i18n="next"><?= h(app_text($setupLang . '.common.next')) ?></button>
      </div>
    </div>

    <div id="step3Panel" class="setup-panel <?= $step !== 3 ? 'hidden' : '' ?>">
      <p class="section-title"><span data-i18n="section_whitelist"><?= h(app_text($setupLang . '.setup.section_whitelist')) ?></span> <span class="optional">(<span data-i18n="whitelist_optional"><?= h(app_text($setupLang . '.setup.whitelist_optional')) ?></span>)</span></p>
      <p class="wl-hint" data-i18n="whitelist_hint"><?= h(app_text($setupLang . '.setup.whitelist_hint')) ?></p>
      <div id="wlList">
        <?php foreach ($savedHosts as $host): ?>
        <div class="wl-entry">
          <input type="text" name="wl_host[]" value="<?= h((string)$host) ?>" placeholder="<?= h(app_text($setupLang . '.setup.whitelist_placeholder')) ?>">
          <button type="button" class="remove-btn" onclick="this.closest('.wl-entry').remove()">✕</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="add-btn" onclick="addWl()" data-i18n="add_whitelist"><?= h(app_text($setupLang . '.setup.add_whitelist')) ?></button>
      <div class="btn-row">
        <button type="button" class="btn-secondary" onclick="goStep(2)" data-i18n="back"><?= h(app_text($setupLang . '.common.back')) ?></button>
        <button type="submit" class="btn-primary" data-i18n="finish"><?= h(app_text($setupLang . '.setup.finish')) ?></button>
      </div>
    </div>
  </form>
</div>

<script>
const SETUP_I18N = <?= json_encode($setupPacks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const COMMON_I18N = <?= json_encode(['en' => app_language_pack('en')['common'], 'de' => app_language_pack('de')['common']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let currentSetupLang = <?= json_encode($setupLang) ?>;
let pwCount = <?= count($savedLabels) ?>;

function t(key) {
  return (SETUP_I18N[currentSetupLang] && SETUP_I18N[currentSetupLang][key]) ||
         (COMMON_I18N[currentSetupLang] && COMMON_I18N[currentSetupLang][key]) || key;
}

function refreshSetupLanguage() {
  document.documentElement.lang = currentSetupLang;
  document.querySelectorAll('[data-i18n]').forEach(el => { el.textContent = t(el.dataset.i18n); });
  document.title = t('page_title');
  const placeholders = {
    baseUrl: 'base_url_placeholder',
    cronSecret: 'cron_secret_placeholder'
  };
  Object.keys(placeholders).forEach(id => {
    const el = document.getElementById(id);
    if (el) el.placeholder = t(placeholders[id]);
  });
  document.querySelectorAll('input[name="pw_label[]"]').forEach(el => { el.placeholder = t('password_label_placeholder'); });
  document.querySelectorAll('input[name="wl_host[]"]').forEach(el => { el.placeholder = t('whitelist_placeholder'); });
}

document.getElementById('appLanguage').addEventListener('change', (e) => {
  currentSetupLang = e.target.value;
  refreshSetupLanguage();
});

function setWizardStep(step) {
  document.getElementById('stepField').value = String(step);
  for (let i = 1; i <= 3; i++) {
    document.getElementById('step' + i + 'Panel').classList.toggle('hidden', i !== step);
    const nav = document.getElementById('wstep' + i);
    nav.className = 'wstep' + (i < step ? ' done' : (i === step ? ' active' : ''));
  }
}
function goStep(step) { setWizardStep(step); }

function addPw() {
  const list = document.getElementById('pwList');
  const div = document.createElement('div');
  div.className = 'pw-entry';
  div.id = 'pw' + pwCount;
  div.innerHTML = `
    <button type="button" class="remove-btn" onclick="removePw(${pwCount})">✕</button>
    <div class="field-group compact">
      <label data-i18n="password_label_name">${t('password_label_name')}</label>
      <input type="text" name="pw_label[]" placeholder="${t('js_password_label_placeholder')}" autocomplete="off">
    </div>
    <div class="row">
      <div class="field-group compact">
        <label data-i18n="password_value">${t('password_value')}</label>
        <input type="password" name="pw_value[]" autocomplete="new-password">
      </div>
      <div class="field-group compact">
        <label data-i18n="password_confirm">${t('password_confirm')}</label>
        <input type="password" name="pw_confirm[]" autocomplete="new-password">
      </div>
    </div>`;
  list.appendChild(div);
  div.querySelector('input[type=text]').focus();
  pwCount++;
}
function removePw(id) {
  const el = document.getElementById('pw' + id);
  if (el) el.remove();
}
function addWl() {
  const list = document.getElementById('wlList');
  const div = document.createElement('div');
  div.className = 'wl-entry';
  div.innerHTML = `<input type="text" name="wl_host[]" placeholder="${t('js_whitelist_placeholder')}"><button type="button" class="remove-btn" onclick="this.closest('.wl-entry').remove()">✕</button>`;
  list.appendChild(div);
  div.querySelector('input').focus();
}
</script>
</body>
</html>
<?php
elseif ($authState === 'denied'):
?>
<!DOCTYPE html>
<html lang="<?= h(app_language()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h(app_text('access_denied.page_title')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="fx-login">
<div class="card">
  <div class="logo"><?= h(app_text('app.brand')) ?></div>
  <h1><?= h(app_text('access_denied.title')) ?></h1>
  <p class="sub"><?= h(app_text('access_denied.subtitle')) ?></p>
  <div class="error"><?= h(app_text('access_denied.message')) ?></div>
</div>
</body>
</html>
<?php
elseif ($authState === 'login'):
$authError = defined('AUTH_ERROR');
?>
<!DOCTYPE html>
<html lang="<?= h(app_language()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h(app_text('login.page_title')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="fx-login">
<div class="card">
  <div class="logo"><?= h(app_text('app.brand')) ?></div>
  <h1><?= h(app_text('login.title')) ?></h1>
  <p class="sub"><?= h(app_text('login.subtitle')) ?></p>
  <?php if ($authError): ?>
    <div class="error"><?= h(app_text('login.error')) ?></div>
  <?php endif; ?>
  <form method="POST">
    <label for="pw"><?= h(app_text('login.password_label')) ?></label>
    <input type="password" name="password" id="pw" autofocus autocomplete="current-password">
    <button type="submit" class="btn"><?= h(app_text('login.button')) ?></button>
  </form>
</div>
</body>
</html>
<?php
else:
require_once __DIR__ . '/vendor/autoload.php';

if (empty($_GET['token'])) {
    $token = token_create();
    header('Location: index.php?token=' . $token);
    exit;
}
$token = preg_replace('/[^a-f0-9]/', '', (string)$_GET['token']);
if (strlen($token) !== 32 || !token_valid($token)) {
    $token = token_create();
    header('Location: index.php?token=' . $token);
    exit;
}
$tokenData = token_get($token);

try {
    $baseUrl = app_base_url();
} catch (RuntimeException $e) {
    http_response_code(500);
    $msg = h($e->getMessage());
    $title = h(app_text('main.config_missing_title'));
    $brand = h(app_text('app.brand'));
    die("<!DOCTYPE html><html lang=\"" . h(app_language()) . "\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>{$title}</title><link rel=\"stylesheet\" href=\"assets/css/app.css\"></head><body class=\"fx-config-error\"><div class=\"box\"><div class=\"logo\">{$brand}</div><h1>{$title}</h1><p class=\"msg\">{$msg}</p></div></body></html>");
}
$uploadUrl = $baseUrl . '/upload.php?token=' . $token;
$options = new \chillerlan\QRCode\QROptions([
    'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
    'eccLevel' => \chillerlan\QRCode\QRCode::ECC_H,
    'svgAddXmlHeader' => false,
    'imageBase64' => false,
    'svgViewBox' => true,
    'scale' => 5,
    'margin' => 2,
]);
try {
    $qrSvg = (new \chillerlan\QRCode\QRCode($options))->render($uploadUrl);
} catch (Throwable $e) {
    $qrSvg = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions(['outputType' => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG, 'imageBase64' => false])))->render($uploadUrl);
}
$created = (int)($tokenData['created'] ?? time());
$mainJsText = [
    'expired' => app_text('common.expired'),
    'sessionExpiredTitle' => app_text('main.session_expired_title'),
    'sessionExpiredSub' => app_text('main.session_expired_sub'),
    'fileReadyTitle' => app_text('main.file_ready_title'),
    'downloadPrefix' => '⬇ ',
    'downloadFallback' => app_text('main.download_button'),
    'downloadSuffix' => ' ' . app_text('common.download'),
    'downloadedTitle' => app_text('main.downloaded_title'),
    'downloadedSub' => app_text('main.downloaded_sub'),
    'qrHiddenTitle' => app_text('main.qr_hidden_title'),
    'qrHiddenSub' => app_text('main.qr_hidden_sub'),
    'newSessionAfterDownload' => app_text('main.new_session_after_download'),
];
?>
<!DOCTYPE html>
<html lang="<?= h(app_language()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h(app_text('main.page_title')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="fx-main">
<div class="card">
  <div class="logo"><?= h(app_text('app.brand')) ?></div>
  <h1><?= h(app_text('main.title')) ?></h1>
  <p class="subtitle"><?= h(app_text('main.subtitle')) ?></p>
  <div class="steps">
    <div class="step done" id="step1"><span class="step-num">✓</span><?= h(app_text('main.step_opened')) ?></div>
    <div class="step active" id="step2"><span class="step-num">2</span><?= h(app_text('main.step_scan')) ?></div>
    <div class="step" id="step3"><span class="step-num">3</span><?= h(app_text('main.step_download')) ?></div>
  </div>
  <div class="qr-wrap" id="qrWrap">
    <div class="qr-inner"><?= $qrSvg ?></div>
    <p class="qr-label"><?= h(app_text('main.qr_label')) ?></p>
  </div>
  <div class="status waiting" id="statusBox">
    <div class="dot"></div>
    <div class="status-text">
      <strong id="statusTitle"><?= h(app_text('main.status_waiting_title')) ?></strong>
      <span id="statusSub"><?= h(app_text('main.status_waiting_sub')) ?></span>
    </div>
  </div>
  <a href="#" class="download-btn" id="downloadBtn"><?= h(app_text('main.download_button')) ?></a>
  <a href="index.php" class="new-session-btn"><?= h(app_text('main.new_session')) ?></a>
  <div class="token-info" id="tokenInfo">
    <code><?= h(app_text('common.token')) ?>: <?= h($token) ?></code>
    <span class="countdown" id="countdown"></span>
  </div>
</div>
<script>
const TOKEN = <?= json_encode($token) ?>;
const BASE = <?= json_encode($baseUrl) ?>;
const TTL = <?= (int)TOKEN_TTL ?>;
const TXT = <?= json_encode($mainJsText, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let startTs = <?= $created ?> * 1000;
let polling = true;
function updateCountdown() {
  const left = TTL - Math.floor((Date.now() - startTs) / 1000);
  const el = document.getElementById('countdown');
  if (left <= 0) {
    el.textContent = TXT.expired;
    polling = false;
    setStatus('error', TXT.sessionExpiredTitle, TXT.sessionExpiredSub);
    return;
  }
  el.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
}
setInterval(updateCountdown, 1000); updateCountdown();
function setStatus(type, title, sub) {
  document.getElementById('statusBox').className = 'status ' + type;
  document.getElementById('statusTitle').textContent = title;
  document.getElementById('statusSub').textContent = sub;
}
function hideQrAfterUpload() {
  const qrWrap = document.getElementById('qrWrap');
  const tokenInfo = document.getElementById('tokenInfo');
  if (qrWrap) qrWrap.classList.add('hidden');
  if (tokenInfo) tokenInfo.classList.add('hidden');
  setStatus('uploaded', TXT.qrHiddenTitle, TXT.qrHiddenSub);
}
function markStepDone(n) {
  for (let i = 1; i <= 3; i++) {
    const el = document.getElementById('step' + i);
    if (!el) continue;
    el.className = 'step' + (i < n ? ' done' : i === n ? ' active' : '');
    if (i < n) el.querySelector('.step-num').textContent = '✓';
  }
}
async function poll() {
  while (polling) {
    try {
      const res = await fetch(BASE + '/poll.php?token=' + TOKEN);
      const data = await res.json();
      if (data.status === 'ready') {
        polling = false;
        setStatus('uploaded', TXT.fileReadyTitle, data.filename ?? '');
        hideQrAfterUpload();
        markStepDone(3);
        const btn = document.getElementById('downloadBtn');
        btn.style.display = 'block';
        btn.href = BASE + '/download.php?token=' + TOKEN;
        btn.textContent = data.filename ? (TXT.downloadPrefix + data.filename + TXT.downloadSuffix) : TXT.downloadFallback;
        btn.addEventListener('click', () => {
          btn.textContent = TXT.newSessionAfterDownload;
          setTimeout(() => {
            window.location.href = 'index.php';
          }, 1500);
        }, { once: true });
        break;
      } else if (data.status === 'expired') {
        polling = false;
        setStatus('error', TXT.sessionExpiredTitle, TXT.sessionExpiredSub);
        break;
      }
    } catch (e) {
      await new Promise(r => setTimeout(r, 2000));
    }
  }
}
poll();
</script>
</body>
</html>
<?php endif; ?>
