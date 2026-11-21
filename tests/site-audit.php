<?php

declare(strict_types=1);

$baseUrl = rtrim($argv[1] ?? '', '/');
if (!preg_match('#^https?://127\.0\.0\.1(?::\d+)?$#', $baseUrl)) {
    fwrite(STDERR, "Usage: php tests/site-audit.php http://127.0.0.1:4173\n");
    exit(2);
}

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
        fwrite(STDERR, "FAIL: {$message}\n");
    } else {
        fwrite(STDOUT, "PASS: {$message}\n");
    }
};

$fetch = static function (string $url): array {
    $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
    $body = file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    preg_match('#\s(\d{3})\s#', $headers[0] ?? '', $match);
    return [(int) ($match[1] ?? 0), $body === false ? '' : $body];
};

// Fetch without following redirects and return the status and Location header.
$fetchNoFollow = static function (string $url): array {
    $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5, 'follow_location' => 0]]);
    file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    preg_match('#\s(\d{3})\s#', $headers[0] ?? '', $match);
    $location = null;
    foreach ($headers as $header) {
        if (stripos($header, 'Location:') === 0) {
            $location = trim(substr($header, strlen('Location:')));
        }
    }
    return [(int) ($match[1] ?? 0), $location];
};

$pages = ['/' => 'Home', '/features' => 'Features', '/pricing' => 'Pricing', '/verify' => 'Verify Certificate', '/contact' => 'Contact Us'];
$documents = [];

foreach ($pages as $page => $navLabel) {
    [$status, $html] = $fetch($baseUrl . $page);
    $assert($status === 200, "{$page} returns 200");
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
    $documents[$page] = $document;

    $xpath = new DOMXPath($document);
    $current = $xpath->query('//nav[@aria-label="Primary navigation"]//a[@aria-current="page"]');
    $active = $xpath->query('//nav[@aria-label="Primary navigation"]//a[contains(concat(" ", normalize-space(@class), " "), " active ")]');
    $assert($current->length === 1 && $active->length === 1 && $current->item(0) === $active->item(0)
        && trim($current->item(0)->textContent) === $navLabel
        && $current->item(0)->getAttribute('href') === $page, "{$page} marks only {$navLabel} as the current navigation item");
    $headerActions = $xpath->query('//header//*[contains(concat(" ", normalize-space(@class), " "), " nav-actions ")]/*');
    $assert($headerActions->length === 1
        && $headerActions->item(0)->getAttribute('class') === 'button button-small'
        && $headerActions->item(0)->getAttribute('href') === 'https://apps.e-certificate.com.my/'
        && trim($headerActions->item(0)->textContent) === 'Sign In', "{$page} header has only the single Sign In button");
    $assert(!str_contains($xpath->query('//header')->item(0)?->textContent ?? '', 'Get Started'), "{$page} header has no Get Started link");
    $assert($xpath->query('//a[contains(@href, ".html") or contains(@href, ".php")]')->length === 0, "{$page} links to no .php or .html page");

    $canonical = 'https://www.e-certificate.com.my' . $page;
    $canonicalLinks = $xpath->query('//head/link[@rel="canonical"]');
    $ogUrls = $xpath->query('//head/meta[@property="og:url"]');
    $assert($canonicalLinks->length === 1 && $canonicalLinks->item(0)->getAttribute('href') === $canonical, "{$page} canonical is {$canonical}");
    $assert($ogUrls->length === 1 && $ogUrls->item(0)->getAttribute('content') === $canonical, "{$page} og:url is {$canonical}");

    foreach ($xpath->query('//link[@href]/@href | //script[@src]/@src') as $asset) {
        $url = trim($asset->value);
        if (preg_match('#^https?:#i', $url)) {
            continue;
        }
        $assert(str_starts_with($url, '/') && !str_starts_with($url, '//'), "{$page} local asset {$url} is root-absolute");
    }
}

$denied = [
    'tests/site-audit.php', 'vendor/autoload.php', 'config/contact.example.php',
    'partials/head.php', 'partials/header.php', 'partials/footer.php', 'previews/home-desktop.png',
    'README.md', 'composer.json', 'composer.lock', '.env.example', '.gitignore', '.htaccess', 'router.php',
];
foreach ($denied as $deniedPath) {
    [$status] = $fetchNoFollow("{$baseUrl}/{$deniedPath}");
    $assert($status === 403, "{$deniedPath} is denied with 403");
}

$redirects = [
    '/index.php' => '/',
    '/index' => '/',
    '/index.html?x=1' => '/?x=1',
    '/features.php?a=b' => '/features?a=b',
    '/pricing.html?type=government' => '/pricing?type=government',
    '/contact.php?type=normal&duration=6' => '/contact?type=normal&duration=6',
    '/contact/' => '/contact',
    '/features/?a=1' => '/features?a=1',
    '/verify.php?number=CERT-20260313090000-A1B2C3' => '/verify?number=CERT-20260313090000-A1B2C3',
    '/verify/' => '/verify',
];
foreach ($redirects as $from => $to) {
    [$status, $location] = $fetchNoFollow($baseUrl . $from);
    $assert($status === 301 && $location === $to, "{$from} redirects with 301 to {$to}");
}

[$missingStatus] = $fetchNoFollow("{$baseUrl}/nope");
$assert($missingStatus === 404, '/nope returns 404');

$checkedTargets = [];
foreach ($documents as $page => $document) {
    $xpath = new DOMXPath($document);
    foreach ($xpath->query('//a[@href]') as $link) {
        $href = trim($link->getAttribute('href'));
        $assert($href !== '#', "{$page} has no dead href=# link");
        if ($href === '' || preg_match('#^(?:https?:|mailto:|tel:)#i', $href)) {
            continue;
        }

        if (str_starts_with($href, '#')) {
            $target = substr($href, 1);
            $assert($target !== '' && $xpath->query('//*[@id="' . $target . '"]')->length === 1, "{$page} internal anchor {$href} resolves");
            continue;
        }

        $parts = parse_url($href);
        $path = $parts['path'] ?? '';
        if ($path === '' || isset($checkedTargets[$path])) {
            continue;
        }
        $checkedTargets[$path] = true;
        [$status] = $fetch($baseUrl . $path);
        $assert($status === 200, "local link target {$path} resolves");
    }

    foreach ($xpath->query('//nav[@aria-label="Primary navigation"]//a') as $navLink) {
        $href = $navLink->getAttribute('href');
        $assert(!in_array($href, ['#features', '#pricing', '#contact'], true), "{$page} primary navigation uses page routes");
    }
}

$verifyXpath = new DOMXPath($documents['/verify']);
$verifyTools = $verifyXpath->query('//*[@data-verify-tool]');
$verifyTool = $verifyTools->item(0);
$assert($verifyTools->length === 1, 'verify page has one verification tool');
$assert($verifyTool !== null && preg_match('~^https?://[^/?#]+$~', $verifyTool->getAttribute('data-api-base')) === 1, 'verify tool API base is a bare origin');
$jsqrSrc = $verifyTool?->getAttribute('data-jsqr-src') ?? '';
$assert(str_starts_with($jsqrSrc, '/assets/vendor/'), 'verify tool decoder is vendored under /assets/vendor/');
[$jsqrStatus] = $fetch($baseUrl . $jsqrSrc);
$assert($jsqrSrc !== '' && $jsqrStatus === 200, "verify tool decoder {$jsqrSrc} resolves");
$verifyForms = $verifyXpath->query('//form[@data-verify-form]');
$verifyForm = $verifyForms->item(0);
$assert($verifyForms->length === 1 && $verifyForm->getAttribute('action') === '/verify'
    && strtolower($verifyForm->getAttribute('method')) === 'get', 'verify form submits GET to /verify');
$numberInputs = $verifyXpath->query('.//input[@name="number"]', $verifyForm);
$numberInputId = $numberInputs->item(0)?->getAttribute('id') ?? '';
$assert($numberInputs->length === 1 && $numberInputId !== ''
    && $verifyXpath->query('//label[@for="' . $numberInputId . '"]')->length === 1, 'verify form has one labelled number input');
$noscripts = $verifyXpath->query('//noscript');
$assert($noscripts->length === 1 && str_contains($noscripts->item(0)->textContent,
    'Certificate verification needs JavaScript. Turn it on to check a number or scan a QR code.'), 'verify page has the noscript notice');
$assert($verifyXpath->query('//*[@data-verify-result][@aria-live="polite"]')->length === 1, 'verify result region is a polite live region');
$verifyScripts = $verifyXpath->query('//script[@src]');
$assert($verifyScripts->length > 0, 'verify page loads its scripts');
foreach ($verifyScripts as $script) {
    $src = $script->getAttribute('src');
    $assert(str_starts_with($src, '/assets/') && !str_contains($src, 'jsqr'), "verify page script {$src} is a local asset loaded up front");
}

$pricing = $documents['/pricing'];
$xpath = new DOMXPath($pricing);
$expected = [
    'normal:1' => 40,
    'normal:3' => 65,
    'normal:6' => 125,
    'normal:12' => 250,
    'government:1' => 30,
    'government:3' => 50,
    'government:6' => 100,
    'government:12' => 200,
];
$cards = $xpath->query('//*[@data-plan-card]');
$assert($cards->length === 8, 'pricing DOM contains exactly eight plan cards');
$seen = [];

foreach ($cards as $card) {
    $type = $card->getAttribute('data-plan-type');
    $duration = $card->getAttribute('data-plan-duration');
    $key = "{$type}:{$duration}";
    $priceNode = (new DOMXPath($pricing))->query('.//*[@data-plan-price]', $card)->item(0);
    $linkNode = (new DOMXPath($pricing))->query('.//a[contains(concat(" ", normalize-space(@class), " "), " plan-choice ")]', $card)->item(0);
    $price = $priceNode ? trim($priceNode->textContent) : '';
    $href = $linkNode ? $linkNode->getAttribute('href') : '';
    $approvedPrice = $expected[$key] ?? null;
    $assert($approvedPrice !== null, "plan card {$key} is allowlisted");
    $assert($price === "RM{$approvedPrice}", "plan card {$key} shows exact price");
    $assert($href === "/contact?type={$type}&duration={$duration}", "plan card {$key} CTA contains only type and duration");
    $seen[$key] = true;
}

$assert(array_keys($seen) === array_keys($expected), 'all approved type and duration combinations appear once in order');
$staticTablist = $xpath->query('//*[@data-pricing-tablist]');
$staticTabs = $xpath->query('//*[@data-pricing-tab]');
$staticPanels = $xpath->query('//*[@data-pricing-panel]');
$assert($staticTablist->length === 1 && !$staticTablist->item(0)->hasAttribute('role'), 'pricing fallback has no inactive tablist semantics');
$assert($staticTabs->length === 2, 'pricing fallback provides two category headings');
foreach ($staticTabs as $tab) {
    $assert(strtolower($tab->nodeName) === 'h3'
        && !$tab->hasAttribute('role')
        && !$tab->hasAttribute('aria-selected')
        && !$tab->hasAttribute('aria-controls')
        && !$tab->hasAttribute('tabindex'), 'pricing fallback category is an ordinary heading');
}
$assert($staticPanels->length === 2, 'pricing fallback provides two plan sections');
foreach ($staticPanels as $panel) {
    $assert(strtolower($panel->nodeName) === 'section'
        && !$panel->hasAttribute('role')
        && !$panel->hasAttribute('aria-labelledby')
        && !$panel->hasAttribute('hidden'), 'pricing fallback plan group is an ordinary visible section');
}
$assert(str_contains($pricing->textContent, 'Contact us to confirm.'), 'unknown pricing policy uses the approved confirmation language');

[$validStatus, $validBody] = $fetch("{$baseUrl}/api/plan.php?type=government&duration=12");
$validPayload = json_decode($validBody, true);
$assert($validStatus === 200 && ($validPayload['plan']['price'] ?? null) === 200, 'plan API derives an approved price');

[$tamperStatus, $tamperBody] = $fetch("{$baseUrl}/api/plan.php?type=government&duration=12&price=1");
$tamperPayload = json_decode($tamperBody, true);
$assert($tamperStatus === 422 && ($tamperPayload['success'] ?? null) === false, 'plan API rejects a supplied price');

if ($failures !== []) {
    exit(1);
}

fwrite(STDOUT, "All site audit assertions passed.\n");
