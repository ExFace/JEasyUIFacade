param([string]$Browser = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe")
$ErrorActionPreference = 'Stop'
$facade = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$vendor = Split-Path (Split-Path $facade)
$temp = Join-Path $env:TEMP ('exf-eui-display-' + [Guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $temp | Out-Null
try {
    if (-not (Test-Path $Browser)) { throw 'Pass -Browser with the path to a Chromium browser.' }
    $shared = (ConvertTo-Json -InputObject ([IO.File]::ReadAllText((Join-Path $vendor 'exface\core\Facades\AbstractAjaxFacade\Elements\JExcelDisplayTrait.php'))) -Compress).Replace('<', '\u003c')
    $eui = (ConvertTo-Json -InputObject ([IO.File]::ReadAllText((Join-Path $facade 'Facades\Elements\EuiDataSpreadSheet.php'))) -Compress).Replace('<', '\u003c')
    $tests = [IO.File]::ReadAllText((Join-Path $PSScriptRoot 'EuiSpreadsheetDisplay.test.js'))
    $gridCss = (New-Object System.Uri((Join-Path $vendor 'npm-asset\jspreadsheet-ce\dist\jspreadsheet.css'))).AbsoluteUri
    $euiCss = (New-Object System.Uri((Join-Path $facade 'Facades\js\themes\jeasyui.exface.css'))).AbsoluteUri
    $colors = (New-Object System.Uri((Join-Path $vendor 'exface\core\Facades\AbstractAjaxFacade\js\exfColorTools.js'))).AbsoluteUri
    $html = @"
<!doctype html><html><head><link rel="stylesheet" href="$gridCss"><link rel="stylesheet" href="$euiCss"></head>
<body><pre id="result">RUNNING</pre><script src="$colors"></script><script>
$tests
try {
    document.getElementById('result').textContent = runEuiSpreadsheetDisplayTests($shared, $eui);
} catch(error) {
    document.getElementById('result').textContent = 'FAILED: ' + error.stack;
}
</script></body></html>
"@
    $page = Join-Path $temp 'test.html'
    [IO.File]::WriteAllText($page, $html)
    $uri = (New-Object System.Uri($page)).AbsoluteUri
    $process = Start-Process -FilePath $Browser -ArgumentList @(
        '--headless', '--disable-gpu', '--no-first-run', '--disable-extensions',
        "--user-data-dir=`"$temp\profile`"", '--dump-dom', $uri
    ) -RedirectStandardOutput (Join-Path $temp 'output.html') -RedirectStandardError (Join-Path $temp 'stderr.txt') -Wait -PassThru
    $output = [IO.File]::ReadAllText((Join-Path $temp 'output.html'))
    $result = [regex]::Match($output, '<pre id="result">([\s\S]*?)</pre>').Groups[1].Value
    if ($process.ExitCode -ne 0 -or $result -notmatch '^\d+ JEasyUI spreadsheet checks passed$') { throw "Browser checks did not pass: $result" }
    Write-Output $result
} finally {
    Remove-Item -LiteralPath $temp -Recurse -Force -ErrorAction SilentlyContinue
}

