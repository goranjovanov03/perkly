$sdk = "$env:LOCALAPPDATA\Android\Sdk"
$adb = "$sdk\platform-tools\adb.exe"
$avd = "perkly_api34"
$root = Split-Path $PSScriptRoot -Parent

function Get-PerklySerial {
    foreach ($line in (& $adb devices | Select-Object -Skip 1)) {
        if ($line -match '^(emulator-\d+)\s+device') {
            $serial = $Matches[1]
            $name = ((& $adb -s $serial emu avd name) | Select-Object -First 1).Trim()
            if ($name -eq $avd) { return $serial }
        }
    }
}

function Test-Port([int]$port) {
    [bool](Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)
}

$serial = Get-PerklySerial
if (-not $serial) {
    Start-Process "$sdk\emulator\emulator.exe" -ArgumentList "-avd", $avd -WindowStyle Hidden
    for ($i = 0; $i -lt 60 -and -not $serial; $i++) {
        Start-Sleep 5
        $serial = Get-PerklySerial
    }
    if (-not $serial) { throw "Emulator $avd did not start" }
    & $adb -s $serial wait-for-device
    while ((& $adb -s $serial shell getprop sys.boot_completed).Trim() -ne "1") { Start-Sleep 3 }
}

if (-not (Test-Port 8001)) {
    Start-Process "cmd.exe" -ArgumentList "/c", "cd /d `"$root\backend`" && php artisan serve --port=8001" -WindowStyle Hidden
}

if (-not (Test-Port 8081)) {
    $env:EXPO_PUBLIC_API_URL = "http://10.0.2.2:8001/api"
    Start-Process "cmd.exe" -ArgumentList "/c", "cd /d `"$root\mobile`" && npx expo start --port 8081" -WindowStyle Hidden
    for ($i = 0; $i -lt 60; $i++) {
        Start-Sleep 3
        try {
            if ((Invoke-WebRequest "http://localhost:8081/status" -UseBasicParsing -TimeoutSec 3).Content -match "running") { break }
        } catch {}
    }
}

& $adb -s $serial shell am force-stop host.exp.exponent
& $adb -s $serial shell am start -a android.intent.action.VIEW -d "exp://10.0.2.2:8081" host.exp.exponent
