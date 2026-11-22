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

// Page => [current navigation label, its href]. Checkout and payment status
// belong to the Pricing section of the navigation.
$pages = [
    '/' => ['Home', '/'],
    '/features' => ['Features', '/features'],
    '/pricing' => ['Pricing', '/pricing'],
    '/verify' => ['Verify Certificate', '/verify'],
    '/contact' => ['Contact Us', '/contact'],
    '/checkout' => ['Pricing', '/pricing'],
    '/payment/status' => ['Pricing', '/pricing'],
];
// Structured data each page must publish, in order. The Organization is
// site-wide; /checkout and /payment/status are noindex and carry nothing else.
$expectedJsonLd = [
    '/' => ['Organization', 'WebSite'],
    '/features' => ['Organization', 'BreadcrumbList'],
    '/pricing' => ['Organization', 'BreadcrumbList', 'SoftwareApplication'],
    '/verify' => ['Organization', 'BreadcrumbList'],
    '/contact' => ['Organization', 'BreadcrumbList'],
    '/checkout' => ['Organization'],
    '/payment/status' => ['Organization'],
];
$documents = [];
$jsonLd = [];

foreach ($pages as $page => [$navLabel, $navHref]) {
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
        && $current->item(0)->getAttribute('href') === $navHref, "{$page} marks only {$navLabel} as the current navigation item");
    $robots = $xpath->query('//head/meta[@name="robots"]');
    $noindex = in_array($page, ['/checkout', '/payment/status'], true);
    $assert($noindex ? ($robots->length === 1 && $robots->item(0)->getAttribute('content') === 'noindex') : $robots->length === 0,
        "{$page} " . ($noindex ? 'is noindex' : 'has no robots meta'));
    $headerActions = $xpath->query('//header//*[contains(concat(" ", normalize-space(@class), " "), " nav-actions ")]/*');
    $assert($headerActions->length === 1
        && $headerActions->item(0)->getAttribute('class') === 'button button-small'
        && $headerActions->item(0)->getAttribute('href') === 'https://apps.e-certificate.com.my/'
        && trim($headerActions->item(0)->textContent) === 'Sign In', "{$page} header has only the single Sign In button");
    $assert(!str_contains($xpath->query('//header')->item(0)?->textContent ?? '', 'Get Started'), "{$page} header has no Get Started link");
    $assert($xpath->query('//a[contains(@href, ".html") or contains(@href, ".php")]')->length === 0, "{$page} links to no .php or .html page");

    $social = [
        'og:site_name' => 'e-Certificate',
        'og:image' => 'https://www.e-certificate.com.my/assets/images/og-cover.png',
        'og:image:width' => '1200',
        'og:image:height' => '630',
    ];
    foreach ($social as $property => $content) {
        $nodes = $xpath->query('//head/meta[@property="' . $property . '"]');
        $assert($nodes->length === 1 && $nodes->item(0)->getAttribute('content') === $content, "{$page} {$property} is {$content}");
    }
    $twitterCards = $xpath->query('//head/meta[@name="twitter:card"]');
    $twitterImages = $xpath->query('//head/meta[@name="twitter:image"]');
    $assert($twitterCards->length === 1 && $twitterCards->item(0)->getAttribute('content') === 'summary_large_image'
        && $twitterImages->length === 1 && $twitterImages->item(0)->getAttribute('content') === $social['og:image'],
        "{$page} declares a large Twitter card with the share cover");
    foreach (['og:title', 'og:description', 'og:image:alt'] as $property) {
        $nodes = $xpath->query('//head/meta[@property="' . $property . '"]');
        $assert($nodes->length === 1 && trim($nodes->item(0)->getAttribute('content')) !== '', "{$page} {$property} is present");
    }

    // Structured data: one JSON-LD block, valid JSON, the expected @type values
    // and no fabricated rating, review or award.
    $scripts = $xpath->query('//script[@type="application/ld+json"]');
    $assert($scripts->length === 1, "{$page} has exactly one JSON-LD block");
    $raw = $scripts->item(0)?->textContent ?? '';
    $decoded = json_decode($raw, true);
    $jsonLd[$page] = is_array($decoded) ? $decoded : [];
    $types = [];
    foreach ((array) ($jsonLd[$page]['@graph'] ?? []) as $node) {
        $types[] = is_array($node) ? (string) ($node['@type'] ?? '') : '';
    }
    $assert(is_array($decoded) && ($decoded['@context'] ?? null) === 'https://schema.org', "{$page} JSON-LD is valid JSON on the schema.org context");
    $assert($types === $expectedJsonLd[$page], "{$page} JSON-LD publishes " . implode(', ', $expectedJsonLd[$page]));
    $assert(!str_contains($raw, '</script'), "{$page} JSON-LD cannot close its own script element");
    $assert(preg_match('/aggregateRating|ratingValue|reviewCount|"review"|award/i', $raw) !== 1, "{$page} JSON-LD claims no rating, review or award");

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
    '.git', '.git/', '.git/config', '.git/HEAD', '.gitattributes', 'error_log', 'api/error_log',
];
foreach ($denied as $deniedPath) {
    [$status] = $fetchNoFollow("{$baseUrl}/{$deniedPath}");
    $assert($status === 403, "{$deniedPath} is denied with 403");
}

// Crawler files at the site root. No deny rule and no clean-URL rewrite may
// swallow them, and the sitemap lists only the public pages.
[$robotsStatus, $robots] = $fetch("{$baseUrl}/robots.txt");
$assert($robotsStatus === 200, '/robots.txt returns 200');
$assert(str_contains($robots, 'Sitemap: https://www.e-certificate.com.my/sitemap.xml'), '/robots.txt points at the sitemap');
foreach (['/checkout', '/payment/status', '/api/'] as $disallowed) {
    $assert(str_contains($robots, "Disallow: {$disallowed}"), "/robots.txt disallows {$disallowed}");
}
$assert(!str_contains($robots, 'Disallow: /assets'), '/robots.txt keeps /assets crawlable for rendering');

[$sitemapStatus, $sitemap] = $fetch("{$baseUrl}/sitemap.xml");
$assert($sitemapStatus === 200, '/sitemap.xml returns 200');
$sitemapXml = simplexml_load_string($sitemap);
$assert($sitemapXml instanceof SimpleXMLElement, '/sitemap.xml parses as XML');
$sitemapUrls = [];
if ($sitemapXml instanceof SimpleXMLElement) {
    foreach ($sitemapXml->url as $entry) {
        $sitemapUrls[] = (string) $entry->loc;
    }
}
$assert($sitemapUrls === [
    'https://www.e-certificate.com.my/',
    'https://www.e-certificate.com.my/features',
    'https://www.e-certificate.com.my/pricing',
    'https://www.e-certificate.com.my/contact',
    'https://www.e-certificate.com.my/verify',
], '/sitemap.xml lists exactly the five public pages');
foreach ($sitemapUrls as $sitemapUrl) {
    [$sitemapPageStatus] = $fetch($baseUrl . parse_url($sitemapUrl, PHP_URL_PATH));
    $assert($sitemapPageStatus === 200, "sitemap URL {$sitemapUrl} resolves");
}

[$coverStatus, $cover] = $fetch("{$baseUrl}/assets/images/og-cover.png");
$coverSize = @getimagesizefromstring($cover);
$assert($coverStatus === 200 && is_array($coverSize) && $coverSize[0] === 1200 && $coverSize[1] === 630 && strlen($cover) < 307200,
    'the share cover is a 1200x630 image under 300 KB');

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
    '/checkout.php?plan=normal-01m' => '/checkout?plan=normal-01m',
    '/checkout/' => '/checkout',
    '/payment-status.php?ref=01ARZ3NDEKTSV4RRFFQ69G5FAV' => '/payment/status?ref=01ARZ3NDEKTSV4RRFFQ69G5FAV',
    '/payment/status/' => '/payment/status',
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
    $planCode = sprintf('%s-%02dm', $type, (int) $duration);
    $assert($href === "/contact?type={$type}&duration={$duration}" || $href === "/checkout?plan={$planCode}",
        "plan card {$key} CTA is the Contact selection or the checkout for {$planCode}");
    $seen[$key] = true;
}

$assert(array_keys($seen) === array_keys($expected), 'all approved type and duration combinations appear once in order');

// The pricing offers must be the prices the plan cards print.
$software = null;
foreach ((array) ($jsonLd['/pricing']['@graph'] ?? []) as $node) {
    if (is_array($node) && ($node['@type'] ?? '') === 'SoftwareApplication') {
        $software = $node;
    }
}
$aggregate = $software['offers'] ?? [];
$assert(($aggregate['@type'] ?? '') === 'AggregateOffer' && ($aggregate['priceCurrency'] ?? '') === 'MYR'
    && ($aggregate['lowPrice'] ?? null) === min($expected) && ($aggregate['highPrice'] ?? null) === max($expected)
    && ($aggregate['offerCount'] ?? null) === count($expected),
    'pricing JSON-LD offers every plan in MYR from RM' . min($expected) . ' to RM' . max($expected));
$offerPrices = [];
foreach ((array) ($aggregate['offers'] ?? []) as $offer) {
    $offerPrices[(string) ($offer['sku'] ?? '')] = $offer['price'] ?? null;
}
foreach ($expected as $key => $approvedPrice) {
    [$type, $duration] = explode(':', $key);
    $code = sprintf('%s-%02dm', $type, (int) $duration);
    $assert(($offerPrices[$code] ?? null) === $approvedPrice, "pricing JSON-LD offer {$code} is RM{$approvedPrice}");
}
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
$assert($validStatus === 200 && ($validPayload['plan']['price'] ?? null) === 200
    && ($validPayload['plan']['price_cents'] ?? null) === 20000
    && ($validPayload['plan']['price_display'] ?? null) === 'RM200', 'plan API derives an approved price');

[$tamperStatus, $tamperBody] = $fetch("{$baseUrl}/api/plan.php?type=government&duration=12&price=1");
$tamperPayload = json_decode($tamperBody, true);
$assert($tamperStatus === 422 && ($tamperPayload['success'] ?? null) === false, 'plan API rejects a supplied price');

if ($failures !== []) {
    exit(1);
}

fwrite(STDOUT, "All site audit assertions passed.\n");
