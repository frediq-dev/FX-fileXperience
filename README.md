# ◈ FX - fileXperience

**FX - fileXperience** ist ein kleines PHP-Tool, mit dem du schnell eine Datei vom Smartphone auf einen PC übertragen kannst.
Der PC zeigt einen QR-Code an, das Smartphone scannt ihn, lädt die Datei hoch und der PC bekommt sofort einen Download-Button.

Das Ganze funktioniert ohne Cloud-Konto, ohne Benutzerverwaltung und ohne dauerhafte Dateiablage.

---

## Was macht FX - fileXperience?

Typischer Ablauf:

1. Du öffnest FX - fileXperience am PC.
2. Die Seite erzeugt einen kurzlebigen Einmal-Token.
3. Aus diesem Token wird ein QR-Code erzeugt.
4. Du scannst den QR-Code mit dem Smartphone.
5. Das Smartphone öffnet die Upload-Seite.
6. Du wählst eine Datei aus und lädst sie hoch.
7. Der PC erkennt den Upload automatisch.
8. Der alte QR-Code verschwindet, weil der Token nicht mehr für einen neuen Upload gedacht ist.
9. Du lädst die Datei am PC herunter.
10. Nach dem Download werden Datei und Token gelöscht.
11. Danach startet automatisch eine neue Session mit neuem QR-Code.

---

## Wofür ist das gedacht?

FX - fileXperience ist praktisch, wenn du schnell etwas vom Handy auf den Rechner bekommen möchtest, zum Beispiel:

- Fotos
- Screenshots
- PDFs
- kurze Videos
- Dokumente
- Dateien aus Messenger-Apps oder E-Mail-Anhängen

Es ist **kein Cloud-Speicher**, kein Dateiarchiv und keine dauerhafte Upload-Plattform.
Die Dateien sind nur kurzfristig vorhanden und werden nach dem Download oder durch den Cron-Cleanup wieder entfernt.

---

## Voraussetzungen

Du brauchst:

- Webspace oder Server mit PHP 8.0 oder neuer
- Schreibrechte für den Ordner `uploads/`
- Composer oder alternativ eine Composer-freie Installation über vorbereitete Dateien
- einen Cronjob oder Web-Cron-Dienst für die automatische Bereinigung

Optional, aber empfohlen:

- HTTPS
- Apache/LiteSpeed mit `.htaccess` oder eine passende Nginx-Konfiguration

---

## Schnellstart mit Composer

Diese Variante ist die einfachste, wenn Composer auf deinem Webspace oder Server verfügbar ist.

### 1. Dateien hochladen

Lade alle Projektdateien in ein Verzeichnis auf deinem Webspace, zum Beispiel:

```text
/fx-filexperience/
```

Die URL könnte dann zum Beispiel so aussehen:

```text
https://example.com/fx-filexperience/
```

### 2. Composer ausführen

Wechsle per SSH in den Projektordner:

```bash
cd /pfad/zu/fx-filexperience
composer install
```

Composer lädt die PHP-Abhängigkeit für die QR-Code-Erzeugung herunter und legt den Ordner `vendor/` an.

### 3. Upload-Ordner beschreibbar machen

```bash
chmod 755 uploads/
```

Falls dein Hoster restriktiver arbeitet, kann auch `775` nötig sein. `777` solltest du nur verwenden, wenn dein Hoster es ausdrücklich verlangt und du keine andere Möglichkeit hast.

### 4. Setup im Browser starten

Öffne im Browser:

```text
https://example.com/fx-filexperience/index.php
```

Beim ersten Aufruf startet der Setup-Assistent.

Im Setup legst du fest:

- `BASE_URL`
- `TOKEN_TTL`
- `CRON_SECRET`
- Sprache
- optional Benutzer/Passwörter
- optional IP-/DNS-Whitelist

Nach dem Setup wird automatisch `config.inc.php` erzeugt.

---

## Installation ohne Composer auf dem Webspace

Viele günstige Webspace-Pakete haben keinen SSH-Zugang oder erlauben Composer nicht direkt auf dem Server.
Das ist kein Problem. Du kannst Composer auf einem anderen Rechner ausführen und danach den fertigen Ordner hochladen.

### Variante A: Composer lokal auf deinem PC ausführen

Diese Variante ist meistens am saubersten.

#### 1. Projekt lokal entpacken

Entpacke das Projekt auf deinem PC, zum Beispiel nach:

```text
C:\Projekte\fx-filexperience
```

oder unter macOS/Linux:

```text
~/Projekte/fx-filexperience
```

#### 2. Composer lokal installieren

Falls Composer auf deinem PC noch nicht installiert ist, installiere ihn von der offiziellen Composer-Webseite.

Danach im Projektordner ausführen:

```bash
composer install --no-dev
```

Dadurch entsteht lokal der Ordner:

```text
vendor/
```

#### 3. Alles per FTP/SFTP hochladen

Lade anschließend den gesamten Projektordner auf deinen Webspace hoch, inklusive:

```text
vendor/
assets/
uploads/
index.php
upload.php
poll.php
download.php
cron.php
config.php
auth.php
tokens.php
lang.php
```

Wichtig: Der Ordner `vendor/` muss mit hochgeladen werden, sonst kann `index.php` keinen QR-Code erzeugen.

#### 4. Setup im Browser ausführen

Rufe danach wie gewohnt auf:

```text
https://example.com/fx-filexperience/index.php
```

### Variante B: Fertiges Release-Paket verwenden

Wenn du ein Release-Paket verwendest, in dem der Ordner `vendor/` bereits enthalten ist, brauchst du Composer gar nicht selbst auszuführen.

Dann reicht:

1. ZIP entpacken
2. alle Dateien auf den Webspace laden
3. `uploads/` beschreibbar machen
4. `index.php` im Browser öffnen
5. Setup ausfüllen

Hinweis für GitHub: Der normale Quellcode eines Repositories enthält oft **nicht** den Ordner `vendor/`. Für Nutzer ohne Composer ist deshalb ein zusätzliches Release-ZIP mit bereits installierten Abhängigkeiten hilfreich.

### Variante C: Composer auf einem anderen Server ausführen

Du kannst Composer auch auf einem anderen Server oder in einer lokalen Entwicklungsumgebung ausführen und danach nur das Ergebnis hochladen.

Wichtig ist nur: Am Ende muss der Ordner `vendor/` auf deinem Webspace liegen.

---

## Setup-Assistent

Beim ersten Aufruf von `index.php` erscheint der Setup-Assistent.

Die dort gemachten Angaben werden gespeichert in:

```text
config.inc.php
```

Diese Datei wird automatisch erzeugt und sollte **nicht öffentlich zugänglich** sein.
Die mitgelieferten Apache-/LiteSpeed-Regeln schützen diese Datei über `.htaccess`.
Für Nginx musst du die Regeln aus `nginx.example.conf` in deine Serverkonfiguration übernehmen.

### BASE_URL

`BASE_URL` ist die vollständige URL zum Projektordner, ohne Slash am Ende.

Beispiel:

```text
https://example.com/fx-filexperience
```

Diese URL ist wichtig, weil sie für QR-Code, Upload, Polling und Download verwendet wird.

### TOKEN_TTL

`TOKEN_TTL` legt fest, wie lange ein QR-Code bzw. Upload-Token gültig ist.

Beispiel:

```text
300
```

Das bedeutet: 300 Sekunden, also 5 Minuten.

### CRON_SECRET

`CRON_SECRET` schützt den Web-Aufruf von `cron.php`.

Beispiel:

```text
meine-lange-zufaellige-geheime-zeichenfolge
```

Der Cronjob wird dann so aufgerufen:

```text
https://example.com/fx-filexperience/cron.php?secret=meine-lange-zufaellige-geheime-zeichenfolge
```

---

## Zugriffskonzept

FX - fileXperience unterscheidet zwischen zwei Dingen:

1. Wer darf am PC neue QR-Sessions erzeugen?
2. Wer darf mit einem gültigen QR-Token eine Datei hochladen?

### `index.php`

`index.php` ist die PC-Seite. Hier entstehen neue QR-Codes.
Diese Seite wird durch Passwort, Whitelist oder bewusst öffentlichen Betrieb geschützt.

### `upload.php`, `poll.php`, `download.php`

Diese Dateien werden über den kurzlebigen Token geschützt.
Sie werden **nicht zusätzlich über die IP-/DNS-Whitelist blockiert**.

Das ist Absicht.

Beispiel: Dein PC ist im Büro-WLAN und darf über die Whitelist neue QR-Codes erzeugen. Dein Smartphone ist aber gerade im Mobilfunknetz. Wenn `upload.php` ebenfalls hart auf die Whitelist prüfen würde, könnte dein Smartphone die Datei nicht hochladen.

Darum gilt:

```text
Whitelist schützt das Erzeugen neuer QR-Codes.
Der QR-Link selbst wird über den kurzlebigen Einmal-Token geschützt.
```

### Mögliche Setup-Varianten

| Passwörter | Whitelist | Ergebnis |
|---|---|---|
| ja | nein | Jeder mit URL sieht Login und braucht ein Passwort. |
| ja | ja | Whitelist-Nutzer kommen direkt rein, alle anderen brauchen ein Passwort. |
| nein | nein | Die Installation ist bewusst öffentlich nutzbar. |
| nein | ja | Nur Whitelist-Nutzer dürfen neue QR-Sessions erzeugen. |

Wichtig: Wenn du **kein Passwort** und **keine Whitelist** einrichtest, kann jeder, der die URL kennt oder findet, FX - fileXperience benutzen.

---

## Cronjob einrichten

Der Cronjob löscht:

- abgelaufene Tokens aus `tokens.json`
- hochgeladene Dateien, die nicht mehr zu einem aktiven Token gehören
- verwaiste Upload-Dateien

Der Cronjob verwendet ausschließlich `TOKEN_TTL`.
Es gibt keinen separaten Cron-TTL-Wert.

### Empfohlenes Intervall

Ein Aufruf alle 2 bis 5 Minuten ist sinnvoll.

Beispiel:

```text
TOKEN_TTL = 300 Sekunden
Cronjob alle 2 Minuten
```

Dann ist ein Token 5 Minuten gültig, und abgelaufene Dateien werden spätestens ungefähr nach 5 bis 7 Minuten bereinigt.

### Variante A: Shell-Cronjob

Wenn du SSH oder echten Cronzugriff hast:

```cron
*/2 * * * * php /pfad/zu/fx-filexperience/cron.php cli
```

Oder alle 5 Minuten:

```cron
*/5 * * * * php /pfad/zu/fx-filexperience/cron.php cli
```

### Variante B: URL-Cronjob beim Hoster

Viele Webhoster bieten im Kundenmenü sogenannte URL-Cronjobs oder Web-Cronjobs an.

Dann trägst du diese URL ein:

```text
https://example.com/fx-filexperience/cron.php?secret=DEIN_CRON_SECRET
```

Intervall zum Beispiel:

```text
alle 2 Minuten
```

### Variante C: cron-job.org verwenden

Wenn dein Webspace-Anbieter keine Cronjobs erlaubt, kannst du einen externen Web-Cron-Dienst verwenden, zum Beispiel **cron-job.org**.
Der Dienst bietet kostenlose Web-Cronjobs an und kann URLs zeitgesteuert aufrufen. Die offizielle Seite beschreibt Ausführungen von minütlich bis jährlich. 

Grundidee:

1. Konto bei cron-job.org erstellen.
2. Neuen Cronjob anlegen.
3. Als URL eintragen:

```text
https://example.com/fx-filexperience/cron.php?secret=DEIN_CRON_SECRET
```

4. HTTP-Methode: `GET`
5. Intervall: zum Beispiel alle 2 oder 5 Minuten
6. Speichern und testen

Wenn der Cronjob erfolgreich läuft, sollte `cron.php` eine kurze Textausgabe mit der Anzahl gelöschter Tokens und Dateien zurückgeben.

---

## Webserver-Schutz

### Apache und LiteSpeed

Apache und LiteSpeed lesen `.htaccess`-Dateien.
Das Projekt enthält bereits passende Regeln für:

- Apache 2.4+
- Apache 2.2-Fallback
- LiteSpeed

Geschützt werden unter anderem:

```text
config.php
config.inc.php
auth.php
tokens.php
tokens.json
lang.php
composer.json
composer.lock
uploads/
```

### Nginx

Nginx ignoriert `.htaccess` vollständig.
Wenn du Nginx verwendest, musst du die Regeln aus folgender Datei in deine Serverkonfiguration übernehmen:

```text
nginx.example.conf
```

Danach Nginx-Konfiguration testen und neu laden.

Beispiel:

```bash
nginx -t
systemctl reload nginx
```

---

## Ordnerstruktur

```text
fx-filexperience/
├── index.php              # Setup, Login und PC-Hauptseite
├── upload.php             # Smartphone-Upload und QR-Scanner
├── poll.php               # Long-Polling-Endpunkt
├── download.php           # Download und sofortige Löschung
├── cron.php               # Cleanup-Script
├── config.php             # Basis-Konfiguration und Hilfsfunktionen
├── config.inc.php         # Wird vom Setup erzeugt, nicht committen
├── lang.php               # Sprachdatei
├── auth.php               # Authentifizierung und Setup-Logik
├── tokens.php             # Token-Speicher mit flock()
├── tokens.json            # Temporäre Token-Datenbank
├── composer.json
├── LICENSE                # MIT-Lizenz für dieses Projekt
├── .htaccess              # Apache-/LiteSpeed-Schutzregeln
├── nginx.example.conf     # Beispielregeln für Nginx
├── assets/
│   ├── css/
│   │   └── app.css        # Zentrale CSS-Datei
│   └── vendor/
│       └── jsqr/
│           ├── jsQR.js    # Lokale QR-Scanner-Bibliothek
│           └── LICENSE    # Apache-2.0-Lizenz für jsQR
├── uploads/
│   └── .htaccess          # Blockiert direkten Zugriff auf Uploads
└── vendor/                # Wird von Composer erzeugt
```

---

## Sprachdatei

Alle sichtbaren Texte liegen in:

```text
lang.php
```

Jede Sprache hat einen eigenen Array-Bereich, zum Beispiel:

```php
'en' => [
    'language' => 'English',
    'setup' => [
        'title' => 'Setup',
    ],
]
```

Der Key `language` enthält den Namen der Sprache.

Texte können so gelesen werden:

```php
app_text('setup.title')
```

Oder mit expliziter Sprache:

```php
app_text('de.setup.title')
```

Dadurch kann das Projekt später einfach um weitere Sprachen erweitert werden.

---

## Sicherheitshinweise

FX - fileXperience ist bewusst klein und einfach gehalten, enthält aber mehrere Schutzmechanismen:

- Tokens werden mit `random_bytes()` erzeugt.
- Tokens sind nur kurz gültig.
- Dateien werden nach dem Download gelöscht.
- Der Cronjob entfernt abgelaufene Tokens und alte Dateien.
- Gefährliche Dateiendungen werden blockiert.
- `tokens.json` wird mit `flock()` gegen gleichzeitige Schreibzugriffe geschützt.
- Passwörter werden als bcrypt-Hashes gespeichert.
- `config.inc.php` enthält sensible Setup-Daten und wird durch Webserver-Regeln geschützt.
- Die Upload-Dateien sind nicht direkt öffentlich abrufbar.

Trotzdem gilt:

- Verwende HTTPS.
- Wähle ein langes `CRON_SECRET`.
- Nutze Passwortschutz oder Whitelist, wenn die Installation nicht öffentlich sein soll.
- Prüfe bei Nginx unbedingt die Serverkonfiguration.
- Lade `config.inc.php` nicht in ein öffentliches GitHub-Repository hoch.

---

## Dateien, die nicht ins GitHub-Repository gehören

Diese Dateien entstehen lokal oder enthalten installationsspezifische Daten:

```text
config.inc.php
vendor/
tokens.json mit echten Tokens
uploads/*
```

`tokes.json` kann im Repository leer oder mit `{}` vorhanden sein, sollte aber keine echten aktiven Tokens enthalten.

---

## Häufige Probleme

### Der QR-Code erscheint nicht

Wahrscheinlich fehlt der Ordner `vendor/`.

Lösung:

```bash
composer install --no-dev
```

Oder ein Release-Paket verwenden, das `vendor/` bereits enthält.

### Der Upload funktioniert, aber der PC zeigt nichts an

Prüfe:

- Ist `BASE_URL` korrekt?
- Ist `TOKEN_TTL` lang genug?
- Funktioniert `poll.php`?
- Gibt es JavaScript-Fehler im Browser?

### Dateien bleiben im Upload-Ordner liegen

Prüfe:

- Läuft der Cronjob?
- Stimmt `CRON_SECRET`?
- Ist `uploads/` beschreibbar?
- Ist `TOKEN_TTL` korrekt gesetzt?

### Ich bekomme 403-Fehler

Mögliche Ursachen:

- `.htaccess` blockiert absichtlich sensible Dateien.
- Die Whitelist erlaubt deinen Anschluss nicht.
- Bei Nginx wurden die Beispielregeln falsch übernommen.
- Der Token ist abgelaufen.

---

## Drittanbieter-Bibliotheken

### jsQR

Dieses Projekt enthält `jsQR` lokal, damit der QR-Scanner ohne CDN und ohne externe Netzwerkanfrage funktioniert.

- Projekt: jsQR
- Autor: Cosmo Wolfe
- Lizenz: Apache-2.0
- Lizenzdatei: `assets/vendor/jsqr/LICENSE`

### chillerlan/php-qrcode

Für die QR-Code-Erzeugung auf der PC-Seite wird `chillerlan/php-qrcode` über Composer verwendet.
Die Lizenzinformationen dieser Bibliothek werden über Composer bzw. den Ordner `vendor/` bereitgestellt.

---

## Lizenz

FX - fileXperience steht unter der MIT-Lizenz.

Siehe:

```text
LICENSE
```

Drittanbieter-Bibliotheken behalten ihre eigenen Lizenzen.
