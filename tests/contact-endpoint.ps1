param(
    [Parameter(Mandatory = $true)]
    [string]$BaseUrl
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Net.Http
$BaseUrl = $BaseUrl.TrimEnd('/')
$rateDirectory = $env:ECERT_RATE_LIMIT_DIR
if ([string]::IsNullOrWhiteSpace($rateDirectory)) {
    throw 'Set ECERT_RATE_LIMIT_DIR to an isolated temporary directory before starting the PHP server and this test.'
}

$failures = [System.Collections.Generic.List[string]]::new()
$handler = [System.Net.Http.HttpClientHandler]::new()
$handler.AllowAutoRedirect = $false
$client = [System.Net.Http.HttpClient]::new($handler)
$client.Timeout = [TimeSpan]::FromSeconds(10)

function Assert-Result([bool]$Condition, [string]$Message) {
    if ($Condition) {
        Write-Host "PASS: $Message"
    } else {
        Write-Error "FAIL: $Message" -ErrorAction Continue
        $script:failures.Add($Message)
    }
}

function Send-Request(
    [string]$Method,
    [string]$Body = '',
    [string]$ContentType = 'application/x-www-form-urlencoded',
    [string]$Origin = $BaseUrl,
    [string]$EndpointBaseUrl = $BaseUrl
) {
    $request = [System.Net.Http.HttpRequestMessage]::new([System.Net.Http.HttpMethod]::new($Method), "$EndpointBaseUrl/api/contact.php")
    if ($Method -ne 'GET') {
        $request.Content = [System.Net.Http.StringContent]::new($Body, [System.Text.Encoding]::UTF8)
        $request.Content.Headers.ContentType = [System.Net.Http.Headers.MediaTypeHeaderValue]::Parse($ContentType)
    }
    if ($Origin -ne '') {
        [void]$request.Headers.TryAddWithoutValidation('Origin', $Origin)
    }
    $response = $client.SendAsync($request).GetAwaiter().GetResult()
    $content = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
    $result = [pscustomobject]@{
        Status = [int]$response.StatusCode
        Body = $content
        Allow = if ($response.Content.Headers.Allow) { [string]::Join(',', $response.Content.Headers.Allow) } else { '' }
    }
    $request.Dispose()
    $response.Dispose()
    return $result
}

function Form-Body([hashtable]$Overrides = @{}) {
    $fields = [ordered]@{
        full_name = 'Endpoint Test User'
        email = 'endpoint.test@example.com'
        organisation = 'Example Organisation'
        phone = '+60 12-345 6789'
        enquiry_type = 'plan'
        type = 'government'
        duration = '12'
        message = 'This is a valid endpoint integration test message.'
        consent = '1'
        website = ''
    }
    foreach ($key in $Overrides.Keys) {
        if ($null -eq $Overrides[$key]) { $fields.Remove($key) } else { $fields[$key] = [string]$Overrides[$key] }
    }
    return (($fields.GetEnumerator() | ForEach-Object {
        '{0}={1}' -f [Uri]::EscapeDataString($_.Key), [Uri]::EscapeDataString($_.Value)
    }) -join '&')
}

function Assert-SafeBody($Response, [string]$Message) {
    $forbidden = '(?i)(smtp_password|phpmailer|exception|stack trace|vendor[\\/]|contact\.php on line|<SMTP_PASSWORD>|indigo\.herosite\.pro)'
    Assert-Result ($Response.Body -notmatch $forbidden) $Message
}

function Get-FreeTcpPort {
    $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, 0)
    $listener.Start()
    try {
        return ([System.Net.IPEndPoint]$listener.LocalEndpoint).Port
    } finally {
        $listener.Stop()
    }
}

try {
    if (Test-Path -LiteralPath $rateDirectory) {
        Remove-Item -LiteralPath $rateDirectory -Recurse -Force
    }
    New-Item -ItemType Directory -Path $rateDirectory -Force | Out-Null

    $response = Send-Request -Method 'GET'
    Assert-Result ($response.Status -eq 405 -and $response.Allow -match 'POST') 'wrong method returns 405 and Allow: POST'
    Assert-SafeBody $response 'method error reveals no internal detail'

    $response = Send-Request -Method 'POST' -Body '{}' -ContentType 'application/json'
    Assert-Result ($response.Status -eq 422) 'wrong content type returns 422'

    $response = Send-Request -Method 'POST' -Body (Form-Body) -Origin 'https://cross-origin.example'
    Assert-Result ($response.Status -eq 422) 'cross-origin request returns 422'

    $response = Send-Request -Method 'POST' -Body ('message=' + ('x' * 17000))
    Assert-Result ($response.Status -eq 422) 'oversized request returns 422'

    $response = Send-Request -Method 'POST' -Body ''
    $payload = $response.Body | ConvertFrom-Json
    Assert-Result ($response.Status -eq 422 -and $payload.errors.full_name -and $payload.errors.email -and $payload.errors.organisation -and $payload.errors.enquiry_type -and $payload.errors.message -and $payload.errors.consent) 'missing required fields return safe field errors'

    $response = Send-Request -Method 'POST' -Body (Form-Body @{ email = "victim@example.com`r`nBcc:other@example.com" })
    Assert-Result ($response.Status -eq 422) 'CRLF email injection is rejected'

    $response = Send-Request -Method 'POST' -Body (Form-Body @{ website = 'bot.example' })
    Assert-Result ($response.Status -eq 422) 'filled honeypot is rejected'

    $response = Send-Request -Method 'POST' -Body ((Form-Body) + '&price=1')
    Assert-Result ($response.Status -eq 422) 'client-supplied price is rejected'

    $response = Send-Request -Method 'POST' -Body (Form-Body @{ duration = '2' })
    Assert-Result ($response.Status -eq 422) 'non-catalog plan is rejected'

    $response = Send-Request -Method 'POST' -Body (Form-Body)
    Assert-Result ($response.Status -eq 503) 'valid catalog plan reaches generic missing-configuration response'
    Assert-SafeBody $response 'missing-configuration response reveals no internals or credential data'
    $payload = $response.Body | ConvertFrom-Json
    Assert-Result ($payload.success -eq $false) 'missing configuration never reports success'

    $throwingRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('ecert-contact-config-test-' + [Guid]::NewGuid().ToString('N'))
    $throwingConfig = Join-Path $throwingRoot 'throwing-contact-config.php'
    $throwingRateDirectory = Join-Path $throwingRoot 'rate'
    $throwingServer = $null
    $previousConfig = $env:ECERT_CONTACT_CONFIG
    $previousRateDirectory = $env:ECERT_RATE_LIMIT_DIR
    $previousOrigin = $env:ECERT_ALLOWED_ORIGIN
    try {
        New-Item -ItemType Directory -Path $throwingRateDirectory -Force | Out-Null
        [System.IO.File]::WriteAllText(
            $throwingConfig,
            "<?php`nthrow new RuntimeException('test config failure marker');`n",
            [System.Text.UTF8Encoding]::new($false)
        )
        $throwingPort = Get-FreeTcpPort
        $throwingBaseUrl = "http://127.0.0.1:$throwingPort"
        $documentRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
        $env:ECERT_CONTACT_CONFIG = $throwingConfig
        $env:ECERT_RATE_LIMIT_DIR = $throwingRateDirectory
        $env:ECERT_ALLOWED_ORIGIN = $throwingBaseUrl
        $throwingServer = Start-Process -FilePath 'php' -ArgumentList @('-S', "127.0.0.1:$throwingPort", '-t', $documentRoot, (Join-Path $documentRoot 'router.php')) -PassThru -WindowStyle Hidden

        $ready = $false
        1..40 | ForEach-Object {
            if ($ready) { return }
            Start-Sleep -Milliseconds 100
            try {
                $probe = Send-Request -Method 'GET' -Origin $throwingBaseUrl -EndpointBaseUrl $throwingBaseUrl
                $ready = $probe.Status -eq 405
            } catch {
                $ready = $false
            }
        }
        Assert-Result $ready 'throwing-config regression server becomes ready'
        if ($ready) {
            $throwingResponse = Send-Request -Method 'POST' -Body (Form-Body) -Origin $throwingBaseUrl -EndpointBaseUrl $throwingBaseUrl
            $throwingPayload = $throwingResponse.Body | ConvertFrom-Json
            Assert-Result ($throwingResponse.Status -eq 503 -and $throwingPayload.success -eq $false -and $throwingPayload.message -eq 'The enquiry service is temporarily unavailable.') 'throwing protected config returns generic JSON 503'
            Assert-Result ($throwingResponse.Body -notmatch 'test config failure marker|throwing-contact-config|RuntimeException') 'throwing protected config reveals no failure detail'
            Assert-SafeBody $throwingResponse 'throwing protected config reveals no internals or credential data'
        }
    } finally {
        if ($null -ne $throwingServer -and !$throwingServer.HasExited) {
            Stop-Process -Id $throwingServer.Id -Force
            $throwingServer.WaitForExit()
        }
        $env:ECERT_CONTACT_CONFIG = $previousConfig
        $env:ECERT_RATE_LIMIT_DIR = $previousRateDirectory
        $env:ECERT_ALLOWED_ORIGIN = $previousOrigin
        if (Test-Path -LiteralPath $throwingRoot) {
            Remove-Item -LiteralPath $throwingRoot -Recurse -Force
        }
    }

    # Private settings file (cPanel): origins and rate directory come only from
    # the file named by ECERT_CONTACT_CONFIG, with the matching env vars unset.
    $privateRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('ecert-contact-private-test-' + [Guid]::NewGuid().ToString('N'))
    $privateConfig = Join-Path $privateRoot 'e-certificate-web.php'
    $privateRateDirectory = Join-Path $privateRoot 'rate'
    $privateOrigins = @('https://www.e-certificate.com.my', 'https://e-certificate.com.my')
    $privateServer = $null
    $previousConfig = $env:ECERT_CONTACT_CONFIG
    $previousRateDirectory = $env:ECERT_RATE_LIMIT_DIR
    $previousOrigin = $env:ECERT_ALLOWED_ORIGIN
    $previousPassword = $env:ECERT_SMTP_PASSWORD
    $previousMode = $env:ECERT_SMTP_MODE
    try {
        New-Item -ItemType Directory -Path $privateRateDirectory -Force | Out-Null
        $phpRateDirectory = $privateRateDirectory.Replace('\', '\\').Replace("'", "\'")
        [System.IO.File]::WriteAllText(
            $privateConfig,
            "<?php`nreturn [`n    'smtp_password' => '<SMTP_PASSWORD>',`n    'allowed_origins' => ['$($privateOrigins[0])', '$($privateOrigins[1])'],`n    'rate_limit_dir' => '$phpRateDirectory',`n    'smtp_mode' => 'starttls',`n];`n",
            [System.Text.UTF8Encoding]::new($false)
        )
        $privatePort = Get-FreeTcpPort
        $privateBaseUrl = "http://127.0.0.1:$privatePort"
        $documentRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
        $env:ECERT_CONTACT_CONFIG = $privateConfig
        $env:ECERT_RATE_LIMIT_DIR = $null
        $env:ECERT_ALLOWED_ORIGIN = $null
        $env:ECERT_SMTP_PASSWORD = $null
        $env:ECERT_SMTP_MODE = $null
        $privateServer = Start-Process -FilePath 'php' -ArgumentList @('-S', "127.0.0.1:$privatePort", '-t', $documentRoot, (Join-Path $documentRoot 'router.php')) -PassThru -WindowStyle Hidden

        $ready = $false
        1..40 | ForEach-Object {
            if ($ready) { return }
            Start-Sleep -Milliseconds 100
            try {
                $probe = Send-Request -Method 'GET' -Origin $privateOrigins[0] -EndpointBaseUrl $privateBaseUrl
                $ready = $probe.Status -eq 405
            } catch {
                $ready = $false
            }
        }
        Assert-Result $ready 'private-config server becomes ready'
        if ($ready) {
            foreach ($origin in $privateOrigins) {
                $privateResponse = Send-Request -Method 'POST' -Body ((Form-Body) + '&unsupported=1') -Origin $origin -EndpointBaseUrl $privateBaseUrl
                $privatePayload = $privateResponse.Body | ConvertFrom-Json
                Assert-Result ($privateResponse.Status -eq 422 -and $privatePayload.message -eq 'Please check the highlighted fields.') "private config allows listed origin $origin"
            }
            foreach ($origin in @('https://cross-origin.example', $privateBaseUrl)) {
                $privateResponse = Send-Request -Method 'POST' -Body ((Form-Body) + '&unsupported=1') -Origin $origin -EndpointBaseUrl $privateBaseUrl
                $privatePayload = $privateResponse.Body | ConvertFrom-Json
                Assert-Result ($privateResponse.Status -eq 422 -and $privatePayload.message -eq 'The submitted form could not be accepted.') "private config rejects unlisted origin $origin"
            }
            $privateResponse = Send-Request -Method 'POST' -Body (Form-Body) -Origin $privateOrigins[1] -EndpointBaseUrl $privateBaseUrl
            $privatePayload = $privateResponse.Body | ConvertFrom-Json
            Assert-Result ($privateResponse.Status -eq 503 -and $privatePayload.success -eq $false -and $privatePayload.message -eq 'The enquiry service is temporarily unavailable.') 'private config placeholder password returns generic JSON 503'
            Assert-SafeBody $privateResponse 'private config 503 reveals no internals or credential data'
            Assert-Result (Test-Path -LiteralPath (Join-Path $privateRateDirectory 'contact-rate.json')) 'private config rate_limit_dir holds the rate state'
        }
    } finally {
        if ($null -ne $privateServer -and !$privateServer.HasExited) {
            Stop-Process -Id $privateServer.Id -Force
            $privateServer.WaitForExit()
        }
        $env:ECERT_CONTACT_CONFIG = $previousConfig
        $env:ECERT_RATE_LIMIT_DIR = $previousRateDirectory
        $env:ECERT_ALLOWED_ORIGIN = $previousOrigin
        $env:ECERT_SMTP_PASSWORD = $previousPassword
        $env:ECERT_SMTP_MODE = $previousMode
        if (Test-Path -LiteralPath $privateRoot) {
            Remove-Item -LiteralPath $privateRoot -Recurse -Force
        }
    }

    Remove-Item -LiteralPath $rateDirectory -Recurse -Force
    New-Item -ItemType Directory -Path $rateDirectory -Force | Out-Null
    1..5 | ForEach-Object {
        $attempt = Send-Request -Method 'POST' -Body (Form-Body @{ message = "Valid rate limit test message number $_." })
        Assert-Result ($attempt.Status -eq 503) "allowed attempt $_ reaches missing-configuration response"
    }
    $response = Send-Request -Method 'POST' -Body (Form-Body @{ message = 'Valid rate limit test message number six.' })
    Assert-Result ($response.Status -eq 429) 'sixth valid attempt in the window returns 429'
    Assert-SafeBody $response 'rate-limit response reveals no internals'

    $stateFiles = @(Get-ChildItem -LiteralPath $rateDirectory -File -ErrorAction SilentlyContinue)
    Assert-Result ($stateFiles.Count -le 1) 'rate limiter keeps bounded state in one file'
    if ($stateFiles.Count -eq 1) {
        $state = Get-Content -LiteralPath $stateFiles[0].FullName -Raw
        Assert-Result ($state -notmatch '127\.0\.0\.1|Endpoint Test User|endpoint\.test@example\.com|Valid rate') 'rate state retains only a hashed address and timestamps'
    }

    if ($failures.Count -gt 0) {
        throw "$($failures.Count) contact endpoint assertion(s) failed."
    }
    Write-Host 'All contact endpoint assertions passed.'
} finally {
    $client.Dispose()
    $handler.Dispose()
    if (Test-Path -LiteralPath $rateDirectory) {
        Remove-Item -LiteralPath $rateDirectory -Recurse -Force
    }
}
