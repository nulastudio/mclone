<h1 align="center">mclone</h1>
<h3 align="center">体验飞一般的clone速度</h3>

## 对比

### git clone

![git clone](git_clone.gif)

### git mclone

![git mclone](git_mclone.gif)

## 限制
1. 仓库大小：`<500M`
2. LFS支持：`未知`

## 使用
`git clone`换成`git mclone`，完事

## 安装

### Windows
cmd
```shell
powershell Invoke-Expression (New-Object Net.WebClient).DownloadString(\"https://gitee.com/liesauer/mclone/raw/v1.0.0/script/install.ps1\")
```
powershell
```shell
Invoke-Expression (New-Object Net.WebClient).DownloadString("https://gitee.com/liesauer/mclone/raw/v1.0.0/script/install.ps1")
```

### Linux/MacOS
curl
```shell
sudo bash -c "$(curl -fsSL https://gitee.com/liesauer/mclone/raw/v1.0.0/script/install.sh)"
```
wget
```shell
sudo bash -c "$(wget https://gitee.com/liesauer/mclone/raw/v1.0.0/script/install.sh -O -)"
```

## 卸载

### Windows
cmd
```shell
powershell Invoke-Expression (New-Object Net.WebClient).DownloadString(\"https://gitee.com/liesauer/mclone/raw/v1.0.0/script/uninstall.ps1\")
```
powershell
```shell
Invoke-Expression (New-Object Net.WebClient).DownloadString("https://gitee.com/liesauer/mclone/raw/v1.0.0/script/uninstall.ps1")
```

### Linux/MacOS
curl
```shell
sudo bash -c "$(curl -fsSL https://gitee.com/liesauer/mclone/raw/v1.0.0/script/uninstall.sh)"
```
wget
```shell
sudo bash -c "$(wget https://gitee.com/liesauer/mclone/raw/v1.0.0/script/uninstall.sh -O -)"
```
