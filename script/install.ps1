$Version = "v1.1.0"
$Url = "https://liesauer.coding.net/p/mclone/d/mclone/git/raw/${Version}/win-x86.exe"
$Path = "${Env:APPDATA}\mclone"
$Save = "${Path}\mclone.exe"

if (!(Test-Path -Path $Path)) {
    mkdir -p $Path | Out-Null
}

try {
    if ([Net.ServicePointManager]::SecurityProtocol -notcontains 'Tls12') {
        [Net.ServicePointManager]::SecurityProtocol += [Net.SecurityProtocolType]::Tls12
    }
} finally {}

Invoke-WebRequest -Uri $Url -OutFile $Save | Out-Null
Unblock-File $Save
$Save = $Save.Replace("\", "\\").Replace(" ", "\ ")
git config --global alias.mclone "!${Save}"
