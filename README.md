# FX - fileXperience

**A tiny, shared-hosting-friendly file transfer tool for quick phone-to-computer uploads via QR code.**

FX - fileXperience is intentionally simple. It is not trying to replace peer-to-peer tools like PairDrop, Snapdrop or AirDrop. Instead, it is designed for ordinary PHP shared hosting environments where Node.js, Docker, WebSockets, databases or TURN servers are not available.

Open the page on your computer, scan the QR code with your phone, upload a file, download it on your computer — and the temporary file is removed afterwards.

No cloud account.  
No database.  
No Node.js.  
No Docker.  
No WebRTC or TURN server required.  
Just PHP, Composer dependencies and a small setup wizard.

---

## What FX - fileXperience is good for

FX - fileXperience is useful when you want a quick, temporary, one-way transfer from your phone to your computer and you already have a simple PHP webspace.

Typical examples:

- send a photo from your phone to your computer
- transfer a screenshot quickly
- move a PDF, image or short video without using a messenger or cloud drive
- provide yourself with a private browser-based upload drop-off page
- run a small transfer tool on shared hosting

---

## What FX - fileXperience is not

FX - fileXperience is not a full peer-to-peer file sharing platform.

It does not try to provide:

- local device discovery
- browser-to-browser WebRTC transfer
- AirDrop-like nearby sharing
- multi-user file sharing rooms
- permanent file storage
- end-to-end encrypted peer-to-peer transfer

Uploaded files are temporarily stored on your server until they are downloaded or removed by the cleanup job.

If you need real peer-to-peer browser transfer, tools like PairDrop or Snapdrop may be a better fit. If you need a small PHP-based tool that works on common shared hosting, FX - fileXperience is built for that use case.

---

## How it works

1. Open FX - fileXperience on your computer.
2. A short-lived token is created.
3. A QR code is shown.
4. Scan the QR code with your phone.
5. Upload a file from your phone.
6. The computer detects the upload.
7. Download the file.
8. The file and token are deleted.
9. A new QR code/session is created.

```text
Computer opens index.php
        ↓
Short-lived token is created
        ↓
QR code with upload.php?token=... is shown
        ↓
Phone scans the QR code
        ↓
Phone uploads a file using the token
        ↓
Computer detects the upload via polling
        ↓
Computer downloads the file
        ↓
File and token are deleted
        ↓
New QR code/session is created
Features
QR-code based phone-to-computer upload
Designed for ordinary PHP shared hosting
No database required
No Node.js, Docker, WebSocket or TURN server required
Short-lived one-time upload tokens
Automatic deletion after download
Cron cleanup for abandoned uploads
Optional password protection
Optional IP / DNS whitelist for creating new sessions
Setup wizard on first launch
Local language file for interface texts
Local QR scanner library, no external CDN required
Apache / LiteSpeed .htaccess protection
Example Nginx configuration included
MIT licensed project code

Den Abschnitt würde ich **ganz oben** einsetzen und danach mit `## Requirements` bzw. Installation weitermachen.
die features fehlen

Stimmt — dann sollte der obere README-Teil so aussehen, inklusive Features:

# FX - fileXperience

**A tiny, shared-hosting-friendly file transfer tool for quick phone-to-computer uploads via QR code.**

FX - fileXperience is intentionally simple. It is not trying to replace peer-to-peer tools like PairDrop, Snapdrop or AirDrop. Instead, it is designed for ordinary PHP shared hosting environments where Node.js, Docker, WebSockets, databases or TURN servers are not available.

Open the page on your computer, scan the QR code with your phone, upload a file, download it on your computer — and the temporary file is removed afterwards.

No cloud account.  
No database.  
No Node.js.  
No Docker.  
No WebRTC or TURN server required.  
Just PHP, Composer dependencies and a small setup wizard.

---

## Features

- QR-code based phone-to-computer upload
- Designed for ordinary PHP shared hosting
- No database required
- No Node.js, Docker, WebSocket or TURN server required
- Short-lived one-time upload tokens
- Automatic deletion after download
- Automatic new QR/session after download
- Cron cleanup for abandoned uploads
- Optional password protection
- Optional IP / DNS whitelist for creating new sessions
- Upload links work via short-lived tokens, even if the phone is on mobile data
- Setup wizard on first launch
- Base URL, token lifetime, cron secret and language configurable during setup
- Local language file for interface texts
- Local QR scanner library, no external CDN required
- Apache / LiteSpeed `.htaccess` protection
- Example Nginx configuration included
- MIT licensed project code

---

## What FX - fileXperience is good for

FX - fileXperience is useful when you want a quick, temporary, one-way transfer from your phone to your computer and you already have a simple PHP webspace.

Typical examples:

- send a photo from your phone to your computer
- transfer a screenshot quickly
- move a PDF, image or short video without using a messenger or cloud drive
- provide yourself with a private browser-based upload drop-off page
- run a small transfer tool on shared hosting

---

## What FX - fileXperience is not

FX - fileXperience is not a full peer-to-peer file sharing platform.

It does not try to provide:

- local device discovery
- browser-to-browser WebRTC transfer
- AirDrop-like nearby sharing
- multi-user file sharing rooms
- permanent file storage
- end-to-end encrypted peer-to-peer transfer

Uploaded files are temporarily stored on your server until they are downloaded or removed by the cleanup job.

If you need real peer-to-peer browser transfer, tools like PairDrop or Snapdrop may be a better fit. If you need a small PHP-based tool that works on common shared hosting, FX - fileXperience is built for that use case.

---

## How it works

1. Open FX - fileXperience on your computer.
2. A short-lived token is created.
3. A QR code is shown.
4. Scan the QR code with your phone.
5. Upload a file from your phone.
6. The computer detects the upload.
7. Download the file.
8. The file and token are deleted.
9. A new QR code/session is created.

```text
Computer opens index.php
        ↓
Short-lived token is created
        ↓
QR code with upload.php?token=... is shown
        ↓
Phone scans the QR code
        ↓
Phone uploads a file using the token
        ↓
Computer detects the upload via polling
        ↓
Computer downloads the file
        ↓
File and token are deleted
        ↓
New QR code/session is created
```

---

## Requirements

- PHP 8.0 or newer
- Composer, or a prepared `vendor/` directory
- Webspace with PHP support
- Write permissions for:
  - `uploads/`
  - `tokens.json`, if the file exists
  - the project directory during first setup, so `config.inc.php` can be created
- HTTPS is strongly recommended, especially for camera access and password-protected usage

---

## Installation with Composer

This is the recommended installation method if you have SSH or terminal access on your server.

### 1. Upload the project

Upload all project files to a directory on your webspace, for example:

```text
/fx-filexperience/
```

Example public URL:

```text
https://example.com/fx-filexperience
```

### 2. Install dependencies

Run Composer in the project directory:

```bash
cd /path/to/fx-filexperience
composer install
```

Composer installs the PHP QR code generation library into the `vendor/` directory.

### 3. Make uploads writable

The `uploads/` directory must be writable by PHP.

Example:

```bash
chmod 755 uploads
```

Depending on your hosting provider, different permissions may be required.

### 4. Open the setup wizard

Open the project URL in your browser:

```text
https://example.com/fx-filexperience
```

The setup wizard appears automatically if `config.inc.php` does not exist yet.

During setup you can configure:

- Base URL
- Token lifetime
- Cron secret
- Interface language
- Password users
- Optional IP / DNS whitelist

After setup, the configuration is written to:

```text
config.inc.php
```

Do not publish this file.

---

## Installation without Composer on the webspace

Some hosting providers do not allow Composer on the server. That is not a problem.

### Option A: Run Composer locally and upload `vendor/`

If you have Composer installed on your own computer:

1. Download or clone the project to your computer.
2. Open a terminal in the project folder.
3. Run:

```bash
composer install
```

4. Upload the complete project folder to your webspace, including the generated `vendor/` directory.

Your server does not need Composer in this case.

### Option B: Use a prepared release package

For users without Composer, the easiest option is a release ZIP that already contains all required dependencies.

A release package should include the folder:

```text
vendor/
```

Then installation is simply:

1. Download the release ZIP.
2. Extract it.
3. Upload all files to your webspace.
4. Open the URL and complete setup.

---

## First setup

When FX - fileXperience is opened for the first time, it starts a setup wizard.

The setup stores your configuration in:

```text
config.inc.php
```

This file contains:

- `BASE_URL`
- `TOKEN_TTL`
- `CRON_SECRET`
- `APP_LANGUAGE`
- password hashes
- whitelist entries

Do not commit or publish `config.inc.php`.

---

## Base URL

The Base URL is the public URL of your installation without a trailing slash.

Example:

```text
https://example.com/fx-filexperience
```

Do not use:

```text
https://example.com/fx-filexperience/
```

The Base URL is used to generate QR codes and internal requests.

---

## Token lifetime

The token lifetime controls how long a QR upload link remains valid.

Example:

```text
300
```

means:

```text
300 seconds = 5 minutes
```

After the token expires, the QR link can no longer be used.

The cleanup cron also uses this value to remove expired tokens and abandoned files.

---

## Cron secret

The cron secret protects the cleanup URL.

Example:

```text
my-very-secret-cleanup-key
```

The cleanup URL then looks like this:

```text
https://example.com/fx-filexperience/cron.php?secret=my-very-secret-cleanup-key
```

Choose a long random value.

---

## Cron cleanup

The cron job removes:

- expired tokens
- uploaded files whose token is no longer active
- abandoned files that were never downloaded

The cron uses `TOKEN_TTL`.

It does not shorten the token lifetime. It only checks regularly whether expired data should be removed.

---

## Recommended cron interval

A cron interval of 2 to 5 minutes is fine.

Example for every 2 minutes:

```cron
*/2 * * * * curl -s "https://example.com/fx-filexperience/cron.php?secret=YOUR_SECRET" > /dev/null
```

Example for every 5 minutes:

```cron
*/5 * * * * curl -s "https://example.com/fx-filexperience/cron.php?secret=YOUR_SECRET" > /dev/null
```

If `TOKEN_TTL` is 300 seconds and the cron runs every 2 minutes, abandoned files may remain for up to about 7 minutes.

That is expected:

```text
token lifetime + cron interval
```

---

## Shell cron

If your hosting provider gives you shell cron access, you can call the script directly:

```cron
*/2 * * * * php /path/to/fx-filexperience/cron.php cli
```

This avoids an HTTP request.

---

## Cron without hosting cron support

Some shared hosting providers do not allow cron jobs.

In that case, you can use an external web cron service such as:

```text
https://cron-job.org
```

Create a cron job there that calls:

```text
https://example.com/fx-filexperience/cron.php?secret=YOUR_SECRET
```

Recommended interval:

```text
every 2 to 5 minutes
```

Make sure your `CRON_SECRET` is strong and not easy to guess.

---

## Access model

FX - fileXperience separates two things:

1. Who may create new QR upload sessions
2. Who may use an already created QR upload link

This is important because your computer may be on a trusted network, while your phone may use mobile data.

---

### Access to `index.php`

`index.php` is the main page where new QR sessions are created.

This page is protected by the setup access rules.

| Passwords | Whitelist | Result |
|---|---|---|
| yes | no | Everyone with the URL sees a login form |
| yes | yes | Whitelisted clients skip login, others need a password |
| no | no | Public mode: anyone with the URL can create sessions |
| no | yes | Only whitelisted clients can create sessions |

---

### Access to `upload.php`

`upload.php` is accessed by the phone through a QR code.

It is protected by the short-lived token.

The phone does not need to be on the same IP address or network as the computer.

This allows the common workflow:

```text
Computer in office/home network
Phone on mobile data
QR scan still works
```

---

### Access to `poll.php`

`poll.php` is used by the computer page to detect whether a file has been uploaded.

It is protected by the token.

---

### Access to `download.php`

`download.php` is used to download the uploaded file.

It is protected by the token.

After the download:

- the uploaded file is deleted
- the token is deleted
- a new QR session is created

---

## Public mode warning

If you do not create a password and do not configure a whitelist, the installation is public.

That means:

```text
Anyone who knows or finds the URL can create upload sessions.
```

Use public mode only if this is intended.

For private use, configure at least one of the following:

- password user
- IP / DNS whitelist
- both

---

## IP / DNS whitelist

The whitelist can contain:

- IPv4 addresses
- IPv6 addresses
- DNS hostnames

Examples:

```text
203.0.113.10
2001:db8::1
vpn.example.com
```

A whitelisted client can access the main page without entering a password.

If no password is configured but a whitelist exists, only whitelisted clients may create new sessions.

---

## Security notes

FX - fileXperience is designed for temporary file transfer.

It is not intended as permanent cloud storage.

Security features include:

- short-lived random tokens
- token deletion after download
- file deletion after download
- cron cleanup for abandoned uploads
- blocked dangerous file extensions
- protected upload directory
- optional password protection
- optional IP / DNS whitelist
- local token file locking
- no database required

Still, you should:

- use HTTPS
- use a strong cron secret
- keep your installation private if possible
- avoid very long token lifetimes
- do not publish `config.inc.php`
- do not publish `tokens.json` with real runtime data
- do not publish uploaded files

---

## Blocked file extensions

Dangerous file types are blocked by extension.

Examples include:

```text
php, phtml, phar, exe, bat, cmd, ps1, sh, py, rb, jar, dll, docm, xlsm
```

This reduces the risk of executable files being uploaded.

The upload directory should still remain protected by server configuration.

---

## File structure

Typical project structure:

```text
fx-filexperience/
├── assets/
│   ├── css/
│   │   └── app.css
│   └── vendor/
│       └── jsqr/
│           ├── jsQR.js
│           └── LICENSE
├── uploads/
│   └── .htaccess
├── .gitignore
├── .htaccess
├── auth.php
├── composer.json
├── composer.lock
├── config.php
├── cron.php
├── download.php
├── index.php
├── lang.php
├── LICENSE
├── nginx.example.conf
├── poll.php
├── README.md
├── tokens.php
└── upload.php
```

After setup, this file is created:

```text
config.inc.php
```

After Composer installation, this directory is created:

```text
vendor/
```

---

## Important files

### `index.php`

Main computer page. Creates QR sessions and waits for uploads.

### `upload.php`

Phone upload page. Receives files through a valid token.

### `poll.php`

Long-polling endpoint. The computer page uses it to detect whether a file is ready.

### `download.php`

Downloads the uploaded file. Deletes the file and token after delivery.

### `cron.php`

Cleanup script. Deletes expired tokens and abandoned files.

### `config.php`

Base configuration and default constants.

### `config.inc.php`

Generated setup configuration. Do not commit this file.

### `lang.php`

Language texts. Contains arrays for supported languages, for example `en` and `de`.

### `tokens.php`

Token management. Handles loading, saving, validating and deleting tokens.

### `tokens.json`

Token storage file. Do not publish real token data.

### `assets/css/app.css`

Main stylesheet.

### `assets/vendor/jsqr/`

Local QR scanning library for the phone-side QR scanner.

---

## Apache / LiteSpeed

The project includes `.htaccess` files for Apache and LiteSpeed.

They protect sensitive files such as:

```text
config.php
config.inc.php
tokens.json
tokens.php
auth.php
lang.php
composer.json
composer.lock
```

The `uploads/` directory also contains a `.htaccess` file to prevent direct access to uploaded files.

---

## Nginx

Nginx does not use `.htaccess`.

If you run FX - fileXperience on Nginx, you must add equivalent deny rules to your server configuration.

An example file is included:

```text
nginx.example.conf
```

Review and adapt it to your server setup.

---

## Composer dependency

The project uses Composer for PHP dependencies.

The QR code displayed on the computer side is generated by:

```text
chillerlan/php-qrcode
```

Install dependencies with:

```bash
composer install
```

If you cannot run Composer on your server, install dependencies elsewhere and upload the generated `vendor/` directory.

---

## Troubleshooting

### The QR code does not appear

Check that Composer dependencies are installed:

```bash
composer install
```

Also check that the `vendor/` directory exists.

---

### The QR scanner opens but does not scan

Make sure the project includes:

```text
assets/vendor/jsqr/jsQR.js
```

Also use HTTPS if possible. Some browsers restrict camera access on non-HTTPS pages.

---

### Upload fails

Check:

- `uploads/` exists
- `uploads/` is writable by PHP
- file size is below the configured limit
- file extension is not blocked
- token is still valid

---

### Download says token expired

The token lifetime may be too short.

Increase `TOKEN_TTL` in setup or recreate the session.

---

### Cron does not clean up files

Check:

- cron URL is correct
- cron secret is correct
- `cron.php` is reachable by the cron service
- `uploads/` is writable
- token storage is writable

---

### I get “Forbidden” when calling cron

The `secret` parameter is missing or incorrect.

Correct format:

```text
https://example.com/fx-filexperience/cron.php?secret=YOUR_SECRET
```

---

## Third-party libraries

FX - fileXperience uses third-party open-source libraries.

Composer dependencies are installed into the `vendor/` directory and retain their own license files and metadata.

Included Composer packages may include, among others:

- `chillerlan/php-qrcode`
- `chillerlan/php-settings-container`
- Composer runtime files under `vendor/composer`

The local QR scanner library `jsQR` is included under:

```text
assets/vendor/jsqr/
```

`jsQR` is licensed separately under Apache-2.0. See:

```text
assets/vendor/jsqr/LICENSE
```

Third-party libraries are not relicensed under the MIT license of this project. They remain under their respective licenses.

---

## Disclaimer

FX - fileXperience is a small self-hosted file transfer tool. It is provided as-is, without warranty of any kind.

Please make sure you understand your hosting environment, access settings and server configuration before using it publicly. The project authors are not responsible for misconfiguration, unauthorized access, data loss or other issues caused by installation or usage.

The project authors are also not responsible for the existence, availability, legality, type, content or consequences of any files transferred through an installation of this software. Responsibility for transferred files remains entirely with the person or organization operating and using the installation.

Avoid using it for highly sensitive or business-critical files unless you have reviewed and secured your setup properly.

---

## License

The project code is licensed under the MIT License.

See:

```text
LICENSE
```

Third-party libraries keep their own licenses.
