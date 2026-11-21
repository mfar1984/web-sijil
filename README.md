# e-Certificate marketing site

Standalone marketing site for e-Certificate. The site uses PHP pages with shared partials, shared CSS, vanilla JavaScript, and two small PHP JSON endpoints. The Contact endpoint sends mail through PHPMailer; no frontend build is required.

## Pages and routes

Pages are served at clean URLs without a file extension:

- `/` (`index.php`): Home, including the three-slide hero and static workflow.
- `/features` (`features.php`): verified product capabilities.
- `/pricing` (`pricing.php`): Normal and Government pricing tabs.
- `/contact` (`contact.php`): enquiry form and optional plan prefill.
- `/verify` (`verify.php`): Verify Certificate, with QR scanning and certificate number entry.
- `api/plan.php`: resolves an allowlisted plan and returns its server-derived price.
- `api/contact.php`: validates and sends same-origin contact submissions.

The pages share one copy of the document head, header and footer in `partials/head.php`, `partials/header.php` and `partials/footer.php`. Each page defines `ECERT_PAGE` and a `$ecertPage` array (title, meta description, optional Open Graph data, active navigation key, clean `path`, body class and page scripts) before including them. The head prints `<link rel="canonical">` and `og:url` as `https://www.e-certificate.com.my` plus the page path. The `partials/` directory is denied with `403`; a partial executed directly without `ECERT_PAGE` also returns an empty `404`. Partials escape every printed value and the pages never echo query-string input.

`.htaccess` serves `/<page>` from `<page>.php` internally and redirects with `301`, keeping the query string: `/index`, `/index.php`, `/index.html` and `/index/` to `/`; `/<page>.php`, the former `/<page>.html` and `/<page>/` to `/<page>`; and the apex `e-certificate.com.my` to `https://www.e-certificate.com.my`. Every link, asset and endpoint uses a root-absolute URL (`/pricing`, `/assets/css/styles.css`, `/api/plan.php`). The JSON endpoints under `/api/` keep their `.php` names.

Primary navigation uses standalone clean page routes. Verify Certificate points to `/verify` on this site, and Sign In points to `https://apps.e-certificate.com.my/`.

## Verify Certificate page

`/verify` checks a certificate without leaving www. It offers two equally prominent modes:

- **Scan QR code**: the live camera (`getUserMedia` with the rear camera preferred) starts only when the visitor presses Start camera. Frames are decoded with the native `BarcodeDetector` when it supports QR codes, otherwise with the vendored jsQR, at most every 120 ms. Every camera track is stopped after a successful read, on Stop camera, on a switch to the other mode, on `pagehide` and when the tab is hidden. An **Upload a QR image** fallback decodes one photo or screenshot (images only, up to 10 MB) for devices without camera access.
- **Enter certificate number**: a form that accepts `CERT-YYYYMMDDHHMMSS-XXXXXX` (6 hexadecimal characters), case-insensitive and trimmed.

Accepted QR payloads (`assets/js/verify-payload.js`); anything else shows "This QR code is not an e-Certificate verification code.":

1. A bare certificate number.
2. The encrypted payload the app prints on certificate PDFs (a Laravel encrypted string starting `eyJpdiI6`, at most 4096 characters). The browser cannot decrypt it; it is sent to the API as `qr`.
3. `https://apps.e-certificate.com.my/verify/<number>` (the app's share link): one path segment, no query, fragment, userinfo or port.
4. `https://www.e-certificate.com.my/verify` or `https://e-certificate.com.my/verify` with exactly one `number` or `certificate_number` parameter and no fragment.

The parser only extracts a number or the encrypted payload. Decoded text is never navigated to, opened or fetched, and only the extracted value is sent, as a query parameter, to the configured API.

Deep links verify on load: `/verify?number=<number>`, plus the legacy field names `?certificate_number=<number>` and `?qr_data=<encrypted payload>`. The first present name wins in that order; a repeated or malformed value shows the format message with the input prefilled. After a lookup by number the address becomes `/verify?number=<NUMBER>`; after an encrypted-payload lookup it becomes `/verify`. Legacy `/verify.php?…` redirects to `/verify?…` with the query kept.

The page calls the app's public API from the browser: `GET {ECERT_APP_URL}/api/public/v1/certificates/verify?number=…` or `?qr=…`, with `credentials: 'omit'`, `cache: 'no-store'`, `referrerPolicy: 'no-referrer'` and a 10 second timeout. Responses: `200` `valid` (with the certificate fields) or `not_found`, `422` malformed input, `429` rate limited (the `Retry-After` seconds are shown), anything else is treated as the service being unavailable. The app allows CORS only for the www and apex origins (`PUBLIC_API_ALLOWED_ORIGINS` in the Laravel app).

`ECERT_APP_URL` sets the app origin, for example `http://127.0.0.1:8000` for local testing. It must be a bare `http(s)://host[:port]` with no path, query, fragment or userinfo; any other value falls back to the default `https://apps.e-certificate.com.my`. `verify.php` prints it escaped in the `data-api-base` attribute.

There is deliberately no server-side proxy on www (no `api/verify.php`): a proxy would send every visitor's lookup from the web server's single IP and defeat the app's per-visitor rate limit (20 per minute per IP).

Privacy: camera frames are processed on the device and never uploaded. Only the certificate number, or the encrypted code printed on the certificate, is sent to e-Certificate. The result shows exactly the public fields of the app's own `/verify` page (awarded to, event, held on, location, issued by, issued on, certificate number), rendered with `textContent` only. Without JavaScript the number form still renders, with a notice that verification needs JavaScript.

### Vendored QR decoder

`assets/vendor/jsqr/jsQR.js` is jsQR 1.4.0 (`dist/jsQR.js`, 256 885 bytes, Apache-2.0, licence in `assets/vendor/jsqr/LICENSE`), copied unmodified from the npm tarball `jsqr-1.4.0.tgz` with integrity `sha512-dxLob7q65Xg2DvstYkRpkYtmKm2sPJ9oFhrhmudT1dZvNFFTlroai3AWSpLey/w5vMcLBXRgOJsbXpdN9HzU/A==`. File SHA-256: `bc40c8a15196236b2314db0856f72ca0b49980cd5413b8c852a7349f5fee0859`. It loads from `/assets/vendor/jsqr/jsQR.js` (the `data-jsqr-src` attribute) only when a decode is needed and `BarcodeDetector` is unavailable. Nothing loads from a CDN.

## Plan catalog and current flow

`api/plan-catalog.php` is the canonical server-side catalog. Its stable records are:

| Plan ID | Type | Duration | Price |
| --- | --- | ---: | ---: |
| `normal-01m` | Normal | 1 month | RM40 |
| `normal-03m` | Normal | 3 months | RM65 |
| `normal-06m` | Normal | 6 months | RM125 |
| `normal-12m` | Normal | 1 year | RM250 |
| `government-01m` | Government | 1 month | RM30 |
| `government-03m` | Government | 3 months | RM50 |
| `government-06m` | Government | 6 months | RM100 |
| `government-12m` | Government | 1 year | RM200 |

Choose Plan currently routes to `/contact` with only `type` and `duration`. The browser and Contact endpoint resolve the price from the catalog; they never accept a client-supplied price.

### Future CHIP integration boundary

Payment integration is intentionally absent. A later CHIP integration should replace the Choose Plan destination at the plan-selection boundary. The server must create a purchase from an allowlisted stable plan ID and derive the amount from `api/plan-catalog.php`. Keep CHIP credentials server-side, verify signed webhook requests, and make fulfilment idempotent so a repeated webhook cannot fulfil the same purchase twice. Do not derive amounts from card text, query-string prices, or other client input.

## Local setup

Requirements: PHP 8.1 or later and Composer 2.

```powershell
cd f:\Programming\certificate\web2
composer install --no-dev --optimize-autoloader
$env:ECERT_RATE_LIMIT_DIR = Join-Path $env:TEMP 'ecert-contact-rate-limit'
$env:ECERT_ALLOWED_ORIGIN = 'http://127.0.0.1:4173'
php -S 127.0.0.1:4173 -t f:\Programming\certificate\web2 f:\Programming\certificate\web2\router.php
```

Open `http://127.0.0.1:4173/`. Use `Ctrl+C` to stop the server. To point the Verify page at a local app, also set `$env:ECERT_APP_URL = 'http://127.0.0.1:8000'` before starting the server; the camera works on `http://127.0.0.1` because browsers treat it as a secure context.

The pages cannot be previewed by opening `file:///…` in a browser: the browser would show raw PHP, and root-absolute links such as `/pricing` would point at the filesystem root. Always preview through a PHP server such as the command above. PHP's built-in server ignores `.htaccess`, so `router.php` mirrors its rules in the same order (denied paths, apex redirect, `301`s, clean URLs, `404` for unknown paths). Pass `router.php` as the last argument, otherwise clean URLs do not resolve locally. `router.php` returns `404` when it runs under anything other than the `cli-server` SAPI, and `.htaccess` denies it with `403` in production. A PHP-capable server is also required for plan prefills and contact responses. Keep `ECERT_ALLOWED_ORIGIN` equal to the exact local origin used in the browser and endpoint test; setting it explicitly also keeps local verification consistent across PHP platforms.

Without a private SMTP credential, a valid Contact submission intentionally returns a generic `503` response. This is the expected safe local behavior and is not evidence of live email delivery.

## Production deployment on cPanel

1. Set the domain document root and upload the site contents to it, including the hidden `.htaccess` file (enable "Show Hidden Files" in cPanel File Manager). Keep `composer.json` and `composer.lock` together.
2. In cPanel Terminal, change to the deployed site directory and install the locked production dependency:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   If the hosting account has no Composer command, run the same command in a compatible local PHP environment and upload the generated `vendor/` directory. Do not edit files under `vendor/`.
3. Create a writable rate-limit directory outside the public document root, for example `/home/account/private/ecert-contact-rate-limit`. Restrict it to the hosting account and configure `ECERT_RATE_LIMIT_DIR` with that absolute path. The endpoint rejects a rate directory inside the document root.
4. Configure `ECERT_ALLOWED_ORIGIN` as the exact public origin, including `https://` and any intended `www` hostname. Do not use a wildcard.
5. Configure the SMTP password through the hosting environment as `ECERT_SMTP_PASSWORD`. Do not put a real credential in this repository, `.env.example`, public files, deployment archives, or logs.
6. If cPanel cannot provide a private process environment value, create a PHP file outside the public document root, restrict its permissions, and point `ECERT_CONTACT_CONFIG` to it through server configuration:

   ```php
   <?php
   return ['smtp_password' => '<SMTP_PASSWORD>'];
   ```

   Example protected location: `/home/account/private/contact.php`. Never place this file under `public_html` or another public document root.
7. Select the transport with `ECERT_SMTP_MODE`:
   - `starttls` uses explicit STARTTLS on port 587 and is the default.
   - `smtps` uses implicit TLS on port 465.
8. Confirm that PHP can read `vendor/autoload.php`, the private credential source, and the rate directory. Keep the private credential file unreadable to other hosting accounts.
9. Confirm the `.htaccess` rules from a terminal, replacing the origin with the deployed one. Each denied path, including `router.php`, must return `403`. Each `.php`, `.html` and trailing-slash URL must return `301` with a `Location` ending in the clean URL and the same query string. The apex host must return `301` to `www`:

   ```bash
   origin=https://www.e-certificate.com.my
   for path in tests/site-audit.php vendor/autoload.php config/contact.example.php partials/header.php previews/home-desktop.png README.md composer.json composer.lock .env.example .gitignore .htaccess router.php; do
     printf '%s ' "$path"; curl -s -o /dev/null -w '%{http_code}\n' "$origin/$path"
   done
   for page in / /features /pricing /contact /verify; do
     printf '%s ' "$page"; curl -s -o /dev/null -w '%{http_code}\n' "$origin$page"
   done
   for path in index.php index.html features.php pricing.html contact.php contact/ verify.php verify/; do
     curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' "$origin/$path?type=government"
   done
   curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' "https://e-certificate.com.my/pricing?type=government"
   curl -s -o /dev/null -w '%{http_code}\n' "$origin/api/plan.php?type=government&duration=12"
   ```

   The five clean pages and the last command must return `200`: `/api/` stays public and keeps its `.php` names.

   The Verify page uses the production app by default. Only set `ECERT_APP_URL` on cPanel if the app moves to another origin.

The endpoint uses the fixed SMTP host `indigo.herosite.pro` and the fixed username, From address, and destination `support@e-certificate.com.my`. PHPMailer runs with production debug output off, verifies the TLS peer and hostname, disallows self-signed certificates, and reports no exception or configuration detail to the browser.

`.env.example` is a placeholder-only checklist. The endpoint does not load it automatically, and a real `.env` file must not be deployed inside the public document root.

## Contact endpoint behavior

- Accepts only same-origin `POST` requests with `application/x-www-form-urlencoded` content.
- Enforces request size, field allowlists, independent field lengths, enquiry types, plan values, CRLF rejection, required consent, and a honeypot.
- Builds a fixed-prefix subject, validated Reply-To, plain-text body, and escaped HTML body.
- Returns `422` for invalid submissions, `429` after the bounded rate limit, `405` for other methods, and `503` for unavailable mail or private configuration.
- Stores only a SHA-256 hash of `REMOTE_ADDR` and timestamps in one locked, bounded rate file. It ignores forwarding headers and retains no form body.
- Reports success only after PHPMailer reports a successful send.

## Verification

Start one server in PowerShell and keep the rate directory outside the site:

```powershell
cd f:\Programming\certificate\web2
$env:ECERT_RATE_LIMIT_DIR = Join-Path $env:TEMP 'ecert-contact-rate-limit'
$env:ECERT_ALLOWED_ORIGIN = 'http://127.0.0.1:4173'
$server = Start-Process php -ArgumentList '-S','127.0.0.1:4173','-t','f:\Programming\certificate\web2','f:\Programming\certificate\web2\router.php' -PassThru
```

Run the non-live checks once:

```powershell
php tests/site-audit.php http://127.0.0.1:4173
php tests/plan-catalog-test.php
powershell -ExecutionPolicy Bypass -File tests/contact-endpoint.ps1 -BaseUrl http://127.0.0.1:4173
node tests/verify-payload-test.js
php -l router.php
php -l index.php
php -l features.php
php -l pricing.php
php -l contact.php
php -l verify.php
php -l partials/head.php
php -l partials/header.php
php -l partials/footer.php
php -l api/contact.php
php -l api/plan.php
php -l api/plan-catalog.php
php -l tests/site-audit.php
php -l tests/plan-catalog-test.php
composer validate --strict
composer audit --locked --no-dev
```

Always stop the local server and remove the temporary rate state:

```powershell
Stop-Process -Id $server.Id
Remove-Item $env:ECERT_RATE_LIMIT_DIR -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item Env:ECERT_RATE_LIMIT_DIR
Remove-Item Env:ECERT_ALLOWED_ORIGIN
```

Browser QA uses installed Chrome at widths 1440, 1024, 768, 390, and 320. Check all five pages (`/`, `/features`, `/pricing`, `/contact`, `/verify`) for horizontal overflow, a 12px rendered-text floor, keyboard focus visibility, primary destinations, one `aria-current="page"`, and a header with only the single Sign In button. The site audit also checks canonical and `og:url`, root-absolute assets, the `301` redirects, that private paths, partials and `router.php` return `403`, and that an unknown path returns `404`. Also check:

- Home: three slides, Pause/Play, Left/Right keys when the carousel itself has focus, horizontal swipe, autoplay pause after manual input, reduced-motion behavior, and the static middle workflow.
- Pricing: pointer selection, Left/Right/Home/End, `type=normal|government` deep links, rejected extra query values, exact prices, and both groups visible with JavaScript disabled.
- Contact: valid plan prefill with a server-derived price, tampered query rejection, validation focus/errors, loading, generic error, rate-limit and success live-region markup, and mobile menu Escape/focus restoration.
- Verify: mode switch with pointer and Left/Right/Home/End, manual valid, not found, malformed, rate-limited and unavailable results, every deep-link form, QR upload of an app payload, a bare number, a hostile URL (rejected, no request to that host) and a non-QR image, the fake-camera scan with the camera stopped afterwards, focus on the result heading, and the no-JavaScript notice.

The required evidence files are under `previews/`:

- `home-desktop.png`, `home-mobile.png`
- `features-desktop.png`, `features-mobile.png`
- `pricing-normal-desktop.png`, `pricing-government-desktop.png`, `pricing-mobile.png`
- `contact-desktop.png`, `contact-mobile.png`
- `verify-desktop.png`, `verify-mobile.png`, `verify-valid-desktop.png`, `verify-valid-mobile.png`, `verify-not-found-mobile.png`, `verify-scanning-mobile.png`

## Owner-only production smoke test

An authenticated SMTP send cannot be tested from this repository because no private credential is available, and no live send is claimed.

After deployment, the site owner must configure the private password and perform one production smoke test:

1. Open the deployed Contact page over HTTPS and submit non-sensitive test details.
2. Confirm the loading state appears and the success state appears only after the request completes.
3. Confirm the message arrives at `support@e-certificate.com.my` with the fixed subject prefix and the visitor address as Reply-To.
4. Reply to the test message to confirm Reply-To behavior, then remove the test message according to the organisation's normal mailbox practice.
5. If the form returns a generic error, verify the selected TLS mode, hosting environment, private file permissions, Composer deployment, exact allowed origin, and outside-root rate directory. Do not enable public SMTP debug output or log the password or submitted form body.
