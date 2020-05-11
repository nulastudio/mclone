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

## FAQ

### mclone是如何实现的？
先将仓库镜像到码云，再从码云拉取镜像仓库。

### 后端代码为什么不开源？
mclone项目后端代码使用了大量的模拟请求到码云来实现自动代理下载，如果后端代码开源，一旦码云进行了限制（应该不会这么小气8？但谁知道呢），如验证码等手段，将会对mclone造成致命打击，甚至可能会威胁到mclone的存在意义（如果每mclone一次都需要破解好几个验证码，这谁顶得住？）。但若有大量请求需要开源，可能会考虑开源。

### 我能自己部署mclone后端程序吗？应该怎么部署？
开源后就能。开源后必定有部署教程。

### 如何拉取私有仓库？
提前声明：请不要使用mclone拉取私有仓库，如确有需要，也请不要拉取特别重要的私有仓库，因为有可能面临仓库泄露的风险！

1. 进入[GitHub->Setting->Developer settings->Personal access tokens](https://github.com/settings/tokens)页面，点击[Generate new token](https://github.com/settings/tokens/new)新增一个Token，Note填写`mclone`，勾选`repo`，千万别勾别的！点击`Generate token`即可添加一条Token。Token只显示一次，如有多次需要，可复制保存下来方便以后使用。

2. 修改clone地址（仅支持HTTPS、不支持SSH），比如原地址为`https://github.com/username/private-repo.git`则修改为`https://username:token@github.com/username/private-repo.git`，将地址中的token替换为你实际的token。示例：`https://liesauer:xxxxxxxx@github.com/liesauer/mclone-private-demo.git`

3. 使用新地址进行mclone。

### 拉取私有仓库存在哪些风险？
1. Token泄露（间接导致私有仓库泄露）
2. 私有仓库泄露

当然这些风险都是将近不可能的，但仍需引起注意。

### 拉取私有仓库为什么存在这些风险？
1. 开发者盗用了Token
2. 码云盗用了Token
3. mclone后端服务器遭到攻击
4. 接入了不安全的网络，导致被监控窃听
5. mclone过程中发生异常（如不正常退出、码云服务器出现异常），导致无法清理仓库，从而导致码云上的镜像仓库处于持续公开状态。

### 我的账号出现异常行为或者怀疑我的仓库已泄露，应该怎么处理？
1. 如果你的账号在mclone之后出现异常行为，进入[GitHub->Setting->Developer settings->Personal access tokens](https://github.com/settings/tokens)页面，并点击`Delete`将之前添加的mclone Token删除，并请及时修改密码。

2. 如果你怀疑你的仓库已泄露，请及时联系开发者沟通。
