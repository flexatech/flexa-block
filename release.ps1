# Release script for Windows (PowerShell)
# Builds assets and packages the plugin into flexa-block.zip
# Mirrors release.sh but uses native Windows commands (Copy-Item + Compress-Archive).
#
# Usage:  Right-click > Run with PowerShell, or in a terminal:  .\release.ps1

$ErrorActionPreference = 'Stop'

$PluginSlug  = 'flexa-block'
$ProjectPath = $PSScriptRoot
$StagePath   = Join-Path $env:TEMP "$PluginSlug-release"
$DestPath    = Join-Path $StagePath $PluginSlug
$ZipName     = "$PluginSlug.zip"
$ZipPath     = Join-Path $ProjectPath $ZipName

Write-Host 'Building assets...' -ForegroundColor Cyan
npm run build -- --stats=errors-only
if ($LASTEXITCODE -ne 0) { throw 'Build failed.' }

Write-Host 'Preparing release directory...' -ForegroundColor Cyan
if (Test-Path $StagePath) { Remove-Item $StagePath -Recurse -Force }
New-Item -ItemType Directory -Path $DestPath -Force | Out-Null

# --- Read .distignore and build exclusion rules -------------------------------
$anchored = New-Object System.Collections.Generic.List[string]  # paths anchored at root (start with /)
$globs    = New-Object System.Collections.Generic.List[string]  # name patterns that match anywhere

$distignore = Join-Path $ProjectPath '.distignore'
if (Test-Path $distignore) {
    foreach ($raw in Get-Content $distignore) {
        $line = $raw.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { continue }
        if ($line.StartsWith('/')) {
            $anchored.Add($line.TrimStart('/').TrimEnd('/'))
        } else {
            $globs.Add($line)
        }
    }
}

function Test-Excluded {
    param([string]$RelPath)

    # Normalize to forward slashes for matching
    $rel = $RelPath -replace '\\', '/'
    $name = Split-Path $rel -Leaf

    # Anchored (root-relative) entries: exact file or anything under a folder
    foreach ($a in $anchored) {
        if ($rel -eq $a -or $rel.StartsWith("$a/")) { return $true }
    }

    # Glob / name patterns matched against the file name (matches anywhere in tree)
    foreach ($g in $globs) {
        if ($name -like $g) { return $true }
        if ($rel  -like $g) { return $true }
    }

    return $false
}

Write-Host 'Syncing files...' -ForegroundColor Cyan
$prefixLen = $ProjectPath.Length + 1
Get-ChildItem -Path $ProjectPath -Recurse -File -Force | ForEach-Object {
    $rel = $_.FullName.Substring($prefixLen)
    if (Test-Excluded $rel) { return }

    $target = Join-Path $DestPath $rel
    $targetDir = Split-Path $target -Parent
    if (-not (Test-Path $targetDir)) { New-Item -ItemType Directory -Path $targetDir -Force | Out-Null }
    Copy-Item -LiteralPath $_.FullName -Destination $target -Force
}

Write-Host 'Generating zip file...' -ForegroundColor Cyan
if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }

# Build the zip manually so entry paths use forward slashes (/), which is what
# WordPress/Linux expects. Compress-Archive on Windows PowerShell 5.1 writes
# backslashes and breaks extraction ("Could not copy file. flexa-block\build\").
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$archive = [System.IO.Compression.ZipFile]::Open($ZipPath, 'Create')
try {
    Push-Location $DestPath
    try {
        Get-ChildItem -Recurse -File -Force | ForEach-Object {
            # Path relative to the plugin folder, normalized to forward slashes.
            $rel = (Resolve-Path -LiteralPath $_.FullName -Relative) -replace '^\.[\\/]', '' -replace '\\', '/'
            $entryName = "$PluginSlug/$rel"
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $archive, $_.FullName, $entryName,
                [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
        }
    } finally {
        Pop-Location
    }
} finally {
    $archive.Dispose()
}

Remove-Item $StagePath -Recurse -Force

Write-Host "$ZipName generated!" -ForegroundColor Green
