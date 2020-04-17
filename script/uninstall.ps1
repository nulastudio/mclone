$Path = "${Env:APPDATA}\mclone"

Remove-Item -Path $Path -Recurse -Force

git config --global --unset alias.mclone
