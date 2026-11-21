<?php

declare(strict_types=1);

// Private settings for https://www.e-certificate.com.my (api/contact.php).
// Template only: copy it to /home/kflegacy/private/e-certificate-web.php, which
// lives outside every document root. Permissions 600 for the file and 700 for
// /home/kflegacy/private. Never commit, upload or copy the filled-in file.
return [
    'smtp_password'   => '<SMTP_PASSWORD>',   // mailbox password of support@e-certificate.com.my
    'allowed_origins' => [
        'https://www.e-certificate.com.my',
        'https://e-certificate.com.my',
    ],
    'rate_limit_dir'  => '/home/kflegacy/private/ecert-contact-rate-limit',
    'smtp_mode'       => 'starttls',           // 'smtps' switches to port 465
    // Optional; defaults shown. Leave them out on production.
    // 'smtp_host'    => 'indigo.herosite.pro',
    // 'smtp_port'    => 587,
];
