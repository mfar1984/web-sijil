<?php

declare(strict_types=1);

// JSON-LD for the public pages, included by partials/head.php. Every node is a
// PHP array encoded with json_encode, so the markup cannot be broken by a
// stray quote, and the escaping flags make a literal "</script>" impossible.
// Only facts the page itself states are published here: no rating, no review,
// no award, no phone number and no postal address.
if (!defined('ECERT_PAGE')) {
    http_response_code(404);
    exit;
}

// $ecertSiteUrl, $ecertPath, $ecertCanonical and $ecertDescription come from
// partials/head.php, which is the only includer.
$ecertSdSite = $ecertSiteUrl;
$ecertSdOrganizationId = $ecertSdSite . '/#organization';
$ecertSdGraph = [[
    '@type' => 'Organization',
    '@id' => $ecertSdOrganizationId,
    'name' => 'e-Certificate',
    'url' => $ecertSdSite . '/',
    'logo' => $ecertSdSite . '/assets/images/favicon.svg',
    'contactPoint' => [[
        '@type' => 'ContactPoint',
        'contactType' => 'customer support',
        'email' => 'support@e-certificate.com.my',
    ]],
]];

if ($ecertPath === '/') {
    $ecertSdGraph[] = [
        '@type' => 'WebSite',
        '@id' => $ecertSdSite . '/#website',
        'url' => $ecertSdSite . '/',
        'name' => 'e-Certificate',
        'description' => $ecertDescription,
        'inLanguage' => 'en-MY',
        'publisher' => ['@id' => $ecertSdOrganizationId],
    ];
}

// Inner pages carry a two-level breadcrumb. The labels are the primary
// navigation labels those pages render. /checkout and /payment/status are
// noindex, so they stay out.
$ecertSdBreadcrumbs = [
    '/features' => 'Features',
    '/pricing' => 'Pricing',
    '/contact' => 'Contact Us',
    '/verify' => 'Verify Certificate',
];
if (array_key_exists($ecertPath, $ecertSdBreadcrumbs)) {
    $ecertSdGraph[] = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $ecertSdSite . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $ecertSdBreadcrumbs[$ecertPath], 'item' => $ecertCanonical],
        ],
    ];
}

// Pricing offers. pricing.php resolves its packages and its duration labels
// before including the head, so the offers below are the same prices and the
// same wording the plan cards print; nothing is hard-coded here. There is no
// FAQ section on the page, so no FAQPage is published.
if ($ecertPath === '/pricing' && is_array($ecertPackages['packages'] ?? null) && is_callable($ecertDurationLabel ?? null)) {
    $ecertSdOffers = [];
    $ecertSdPrices = [];
    foreach ($ecertPackages['packages'] as $ecertSdPackage) {
        $ecertSdCents = $ecertSdPackage['price_cents'];
        $ecertSdPrice = $ecertSdCents % 100 === 0 ? intdiv($ecertSdCents, 100) : $ecertSdCents / 100;
        $ecertSdPrices[] = $ecertSdPrice;
        $ecertSdOffers[] = [
            '@type' => 'Offer',
            'name' => ucfirst($ecertSdPackage['category']) . ' plan, ' . $ecertDurationLabel($ecertSdPackage['duration']),
            'sku' => $ecertSdPackage['code'],
            'price' => $ecertSdPrice,
            'priceCurrency' => 'MYR',
            'url' => $ecertSdSite . '/pricing',
        ];
    }

    if ($ecertSdOffers !== []) {
        $ecertSdGraph[] = [
            '@type' => 'SoftwareApplication',
            '@id' => $ecertSdSite . '/#software',
            'name' => 'e-Certificate',
            'url' => $ecertSdSite . '/',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'publisher' => ['@id' => $ecertSdOrganizationId],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'MYR',
                'lowPrice' => min($ecertSdPrices),
                'highPrice' => max($ecertSdPrices),
                'offerCount' => count($ecertSdOffers),
                'offers' => $ecertSdOffers,
            ],
        ];
    }
}

$ecertSdJson = json_encode(
    ['@context' => 'https://schema.org', '@graph' => $ecertSdGraph],
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if (is_string($ecertSdJson)):
?>
  <script type="application/ld+json"><?= $ecertSdJson ?></script>
<?php endif; ?>
