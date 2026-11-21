'use strict';

// Run: node tests/verify-payload-test.js
// Loads assets/js/verify-payload.js in an isolated context and checks every
// accepted and rejected QR payload and deep-link form.
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'verify-payload.js'), 'utf8');
const sandbox = { window: {}, URL, URLSearchParams };
vm.runInNewContext(source, sandbox, { filename: 'verify-payload.js' });
const payload = sandbox.window.EcertVerifyPayload;

let failures = 0;
const check = (condition, message) => {
  if (condition) {
    console.log(`PASS: ${message}`);
  } else {
    failures += 1;
    console.error(`FAIL: ${message}`);
  }
};
// Objects come from another realm, so compare their JSON form.
const same = (actual, expected) => JSON.stringify(actual) === JSON.stringify(expected);

const N = 'CERT-20260313090000-A1B2C3';
const QR = Buffer.from(JSON.stringify({ iv: 'aXZpdml2aXZpdml2aXZpdg==', value: 'c2FtcGxlK3ZhbHVlLw==', mac: 'abc123', tag: '' })).toString('base64');
const numberResult = { kind: 'number', number: N };

check(typeof payload === 'object' && payload !== null, 'window.EcertVerifyPayload is defined');
check(payload.NUMBER_PATTERN.test(N) && !payload.NUMBER_PATTERN.test(N.toLowerCase()), 'NUMBER_PATTERN matches the generator format only');
check(QR.startsWith('eyJpdiI6') && /[+/=]/.test(QR), 'sample encrypted payload has the Laravel shape and base64 symbols');

check(payload.normaliseNumber(N) === N, 'normaliseNumber keeps an exact number');
check(payload.normaliseNumber(`  ${N.toLowerCase()}\n`) === N, 'normaliseNumber trims and uppercases');
check(payload.normaliseNumber('CERT-123') === null, 'normaliseNumber rejects a short number');
check(payload.normaliseNumber(42) === null, 'normaliseNumber rejects a non-string');

const accepted = {
  'bare uppercase number': [N, numberResult],
  'bare lowercase number': [N.toLowerCase(), numberResult],
  'padded number': [`  ${N}  `, numberResult],
  'encrypted payload': [QR, { kind: 'qr', qr: QR }],
  'padded encrypted payload': [`\n${QR}\n`, { kind: 'qr', qr: QR }],
  'apps verify URL': [`https://apps.e-certificate.com.my/verify/${N}`, numberResult],
  'apps verify URL with lowercase number': [`https://apps.e-certificate.com.my/verify/${N.toLowerCase()}`, numberResult],
  'www verify URL with number': [`https://www.e-certificate.com.my/verify?number=${N}`, numberResult],
  'www verify URL with certificate_number': [`https://www.e-certificate.com.my/verify?certificate_number=${N}`, numberResult],
  'apex verify URL with number': [`https://e-certificate.com.my/verify?number=${N}`, numberResult],
  'apex verify URL with certificate_number': [`https://e-certificate.com.my/verify?certificate_number=${N}`, numberResult],
};
for (const [name, [input, expected]] of Object.entries(accepted)) {
  check(same(payload.parseQrPayload(input), expected), `accepts ${name}`);
}

const rejected = {
  'evil host with number parameter': `https://evil.example/verify?number=${N}`,
  'look-alike host': `https://apps.e-certificate.com.my.evil.example/verify/${N}`,
  'apps URL nested in an evil path': `https://evil.example/https://apps.e-certificate.com.my/verify/${N}`,
  'http apps URL': `http://apps.e-certificate.com.my/verify/${N}`,
  'http www URL': `http://www.e-certificate.com.my/verify?number=${N}`,
  'userinfo before apps host': `https://user@apps.e-certificate.com.my/verify/${N}`,
  'apps host as userinfo of evil host': `https://apps.e-certificate.com.my@evil.example/verify/${N}`,
  'apps URL with a port': `https://apps.e-certificate.com.my:8443/verify/${N}`,
  'apps URL with the default port': `https://apps.e-certificate.com.my:443/verify/${N}`,
  'apps URL with an extra path segment': `https://apps.e-certificate.com.my/verify/${N}/extra`,
  'apps URL with dot segments': `https://apps.e-certificate.com.my/x/../verify/${N}`,
  'apps URL with a query': `https://apps.e-certificate.com.my/verify/${N}?ref=1`,
  'apps URL with an empty query': `https://apps.e-certificate.com.my/verify/${N}?`,
  'apps URL with a hash': `https://apps.e-certificate.com.my/verify/${N}#top`,
  'apps URL with an empty hash': `https://apps.e-certificate.com.my/verify/${N}#`,
  'apps URL with a malformed number': 'https://apps.e-certificate.com.my/verify/CERT-123',
  'apps URL with an encoded number': `https://apps.e-certificate.com.my/verify/${N.replace(/-/g, '%2D')}`,
  'apps URL with a trailing-dot host': `https://apps.e-certificate.com.my./verify/${N}`,
  'www URL with a hash': `https://www.e-certificate.com.my/verify?number=${N}#x`,
  'www URL with a repeated number': `https://www.e-certificate.com.my/verify?number=${N}&number=${N}`,
  'www URL with both parameter names': `https://www.e-certificate.com.my/verify?number=${N}&certificate_number=${N}`,
  'www URL with an extra parameter': `https://www.e-certificate.com.my/verify?number=${N}&utm=1`,
  'www URL with qr_data': `https://www.e-certificate.com.my/verify?qr_data=${QR}`,
  'www URL on a wrong path': `https://www.e-certificate.com.my/check?number=${N}`,
  'www URL on verify.php': `https://www.e-certificate.com.my/verify.php?number=${N}`,
  'www URL with a path number': `https://www.e-certificate.com.my/verify/${N}`,
  'www URL with a malformed number': 'https://www.e-certificate.com.my/verify?number=CERT-123',
  'user subdomain URL': `https://user.e-certificate.com.my/verify/${N}`,
  'backslash URL': `https:\\\\apps.e-certificate.com.my\\verify\\${N}`,
  'protocol-relative URL': `//apps.e-certificate.com.my/verify/${N}`,
  'javascript: URL': `javascript:alert('${N}')`,
  'data: URL': `data:text/html,${N}`,
  'WIFI payload': 'WIFI:T:WPA;S:office;P:secret;;',
  'short number': 'CERT-123',
  'number with extra text': `${N} extra`,
  'empty text': '',
  'whitespace only': '   ',
  '5000 characters': 'A'.repeat(5000),
  'oversized encrypted payload': `eyJpdiI6${'A'.repeat(5000)}`,
  'encrypted prefix with markup': 'eyJpdiI6<script>alert(1)</script>',
  'non-string value': null,
};
for (const [name, input] of Object.entries(rejected)) {
  check(payload.parseQrPayload(input) === null, `rejects ${name}`);
}

const deepLinks = {
  'no parameters': ['', null],
  'unrelated parameter': ['?type=normal', null],
  'number': [`?number=${N}`, numberResult],
  'lowercase padded number': [`?number=%20${N.toLowerCase()}%20`, numberResult],
  'legacy certificate_number': [`?certificate_number=${N}`, numberResult],
  'legacy qr_data, encoded': [`?qr_data=${encodeURIComponent(QR)}`, { kind: 'qr', qr: QR }],
  'legacy qr_data, unencoded plus signs': [`?qr_data=${QR}`, { kind: 'qr', qr: QR }],
  'number wins over certificate_number': [`?certificate_number=CERT-1&number=${N}`, numberResult],
  'certificate_number wins over qr_data': [`?qr_data=bad&certificate_number=${N}`, numberResult],
  'repeated number': [`?number=${N}&number=${N}`, { kind: 'invalid', raw: N }],
  'repeated certificate_number': [`?certificate_number=${N}&certificate_number=x`, { kind: 'invalid', raw: N }],
  'repeated qr_data': [`?qr_data=${encodeURIComponent(QR)}&qr_data=x`, { kind: 'invalid', raw: QR }],
  'malformed number': ['?number=CERT-123', { kind: 'invalid', raw: 'CERT-123' }],
  'empty number': ['?number=', { kind: 'invalid', raw: '' }],
  'malformed certificate_number': ['?certificate_number=abc', { kind: 'invalid', raw: 'abc' }],
  'malformed qr_data': ['?qr_data=%3Cscript%3E', { kind: 'invalid', raw: '<script>' }],
  'URL inside number': [`?number=${encodeURIComponent(`https://apps.e-certificate.com.my/verify/${N}`)}`, { kind: 'invalid', raw: `https://apps.e-certificate.com.my/verify/${N}` }],
};
for (const [name, [search, expected]] of Object.entries(deepLinks)) {
  check(same(payload.readDeepLink(search), expected), `readDeepLink handles ${name}`);
}

if (failures > 0) {
  console.error(`${failures} verify payload assertion(s) failed.`);
  process.exit(1);
}
console.log('All verify payload assertions passed.');
