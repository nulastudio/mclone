#!/bin/bash

version="v1.5.0"
url="https://liesauer.coding.net/p/mclone/d/mclone/git/raw/${version}"
name=""
path="/usr/local/bin"
save="${path}/mclone"

if [ "$(uname -s)" == "Darwin" ]; then
    name="osx-x64"
else
    name="linux-x64"
fi

if command -v "wget" > /dev/null 2>&1; then
    wget -O $save "${url}/${name}"
else
    curl –o $save "${url}/${name}"
fi

chmod 0777 ${save}

git config --global alias.mclone "!${save}"
