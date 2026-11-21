# e-Certificate marketing site

Standalone marketing site for e-Certificate. The site uses PHP pages with shared partials, shared CSS, vanilla JavaScript, and two small PHP JSON endpoints. The Contact endpoint sends mail through PHPMailer; no frontend build is required.

## Pages and routes

Pages are served at clean URLs without a file extension:

- `/` (`index.php`): Home, including the three-slide hero and static workflow.
- `/features` (`features.php`): verified product capabilities.
- `/pricing` (`pricing.php`): Normal and Government pricing tabs.
- `/contact` (`contact.php`): enquiry form and optional plan prefill.
- `api/plan.php`: resolves an allowlisted plan and returns its server-derived price.
- `api/contact.php`: validates and sends same-origin contact submissions.

The pages share one copy of the document head, header and footer in `partials/head.php`, `partials/header.php` and `partials/footer.php`. Each page defines `ECERT_PAGE` and a `$ecertPage` array (title, meta description, optional Open Graph data, active navigation key, clean `path`, body class and page scripts) before including them. The head prints `<link rel="canonical">` and `og:url` as `https://www.e-certificate.com.my` plus the page path. The `partials/` directory is denied with `403`; a partial executed directly without `ECERT_PAGE` also returns an empty `404`. Partials escape every printed value and the pages never echo query-string input.

`.htaccess` serves `/<page>` from `<page>.php` internally and redirects with `301`, keeping the query string: `/index`, `/index.php`, `/index.html` and `/index/` to `/`; `/<page>.php`, the former `/<page>.html` and `/<page>/` to `/<page>`; and the apex `e-certificate.com.my` to `https://www.e-certificate.com.my`. Every link, asset and endpoint uses a root-absolute URL (`/pricing`, `/assets/css/styles.css`, `/api/plan.php`). The JSON endpoints under `/api/` keep their `.php` names.

Primary navigation uses standalone clean page routes. Public verification points to `https://apps.e-certificate.com.my/verify`, and Sign In points to `https://apps.e-certificate.com.my/`.

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

Open `http://127.0.0.1:4173/`. Use `Ctrl+C` to stop the server.

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
   for page in / /features /pricing /contact; do
     printf '%s ' "$page"; curl -s -o /dev/null -w '%{http_code}\n' "$origin$page"
   done
   for path in index.php index.html features.php pricing.html contact.php contact/; do
     curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' "$origin/$path?type=government"
   done
   curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' "https://e-certificate.com.my/pricing?type=government"
   curl -s -o /dev/null -w '%{http_code}\n' "$origin/api/plan.php?type=government&duration=12"
   ```

   The four clean pages and the last command must return `200`: `/api/` stays public and keeps its `.php` names.

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
php -l router.php
php -l index.php
php -l features.php
php -l pricing.php
php -l contact.php
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

Browser QA uses installed Chrome at widths 1440, 1024, 768, 390, and 320. Check all four pages (`/`, `/features`, `/pricing`, `/contact`) for horizontal overflow, a 12px rendered-text floor, keyboard focus visibility, primary destinations, one `aria-current="page"`, and a header with only the single Sign In button. The site audit also checks canonical and `og:url`, root-absolute assets, the `301` redirects, that private paths, partials and `router.php` return `403`, and that an unknown path returns `404`. Also check:

- Home: three slides, Pause/Play, Left/Right keys when the carousel itself has focus, horizontal swipe, autoplay pause after manual input, reduced-motion behavior, and the static middle workflow.
- Pricing: pointer selection, Left/Right/Home/End, `type=normal|government` deep links, rejected extra query values, exact prices, and both groups visible with JavaScript disabled.
- Contact: valid plan prefill with a server-derived price, tampered query rejection, validation focus/errors, loading, generic error, rate-limit and success live-region markup, and mobile menu Escape/focus restoration.

The required evidence files are under `previews/`:

- `home-desktop.png`, `home-mobile.png`
- `features-desktop.png`, `features-mobile.png`
- `pricing-normal-desktop.png`, `pricing-government-desktop.png`, `pricing-mobile.png`
- `contact-desktop.png`, `contact-mobile.png`

## Owner-only production smoke test

An authenticated SMTP send cannot be tested from this repository because no private credential is available, and no live send is claimed.

After deployment, the site owner must configure the private password and perform one production smoke test:

1. Open the deployed Contact page over HTTPS and submit non-sensitive test details.
2. Confirm the loading state appears and the success state appears only after the request completes.
3. Confirm the message arrives at `support@e-certificate.com.my` with the fixed subject prefix and the visitor address as Reply-To.
4. Reply to the test message to confirm Reply-To behavior, then remove the test message according to the organisation's normal mailbox practice.
5. If the form returns a generic error, verify the selected TLS mode, hosting environment, private file permissions, Composer deployment, exact allowed origin, and outside-root rate directory. Do not enable public SMTP debug output or log the password or submitted form body.
