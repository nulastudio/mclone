<h1 align="center">mclone</h1>
<h3 align="center">体验飞一般的clone速度</h3>

## 对比

### git clone

![git clone](git_clone.gif)

### git mclone

![git mclone](git_mclone.gif)

## 限制
1. 仓库大小（仅Proxy）：`<500M`
2. LFS支持：`未知`

## 使用
`git clone`换成`git mclone`，完事

## 安装

### Windows
cmd
```shell
powershell Invoke-Expression (New-Object Net.WebClient).DownloadString(\"https://gitee.com/liesauer/mclone/raw/v1.5.2/script/install.ps1\")
```
powershell
```shell
Invoke-Expression (New-Object Net.WebClient).DownloadString("https://gitee.com/liesauer/mclone/raw/v1.5.2/script/install.ps1")
```

### Linux/MacOS
curl
```shell
sudo bash -c "$(curl -fsSL https://gitee.com/liesauer/mclone/raw/v1.5.2/script/install.sh)"
```
wget
```shell
sudo bash -c "$(wget https://gitee.com/liesauer/mclone/raw/v1.5.2/script/install.sh -O -)"
```

## 卸载

### Windows
cmd
```shell
powershell Invoke-Expression (New-Object Net.WebClient).DownloadString(\"https://gitee.com/liesauer/mclone/raw/v1.5.2/script/uninstall.ps1\")
```
powershell
```shell
Invoke-Expression (New-Object Net.WebClient).DownloadString("https://gitee.com/liesauer/mclone/raw/v1.5.2/script/uninstall.ps1")
```

### Linux/MacOS
curl
```shell
sudo bash -c "$(curl -fsSL https://gitee.com/liesauer/mclone/raw/v1.5.2/script/uninstall.sh)"
```
wget
```shell
sudo bash -c "$(wget https://gitee.com/liesauer/mclone/raw/v1.5.2/script/uninstall.sh -O -)"
```

## Mirror
使用第三方的代理直接拉取代码

**注意：`Mirror`不支持以SSH的方式拉取代码，`mlcone`会自动将SSH替换为HTTPS**

**`Mirror`指第三方代理，`Proxy Server`指自部署的mclone后台。当同时设置`Mirror`和`Proxy Server`时，优先使用`Mirror`的方式拉取代码。**

### `--mirror`
`--mirror`参数仅对当前次mclone生效。
```shell
git mclone --mirror cnpm https://github.com/username/repo.git
```

### `mirror list`
列举mclone支持的第三方代理


```shell
git mclone mirror list
```

目前已支持：
```
cnpm        github.com.cnpmjs.org
fastgit     hub.fastgit.org
gitclone    gitclone.com
```

### `mirror set <mirror>`
设置mclone的第三方代理，一次设置永久生效。
```shell
git mclone mirror set cnpm
```

### `mirror del`
删除mclone的第三方代理。
```shell
git mclone mirror del
```

## 保留镜像仓库地址
将镜像仓库保存至名叫`mirror`的`remote`里，由于mclone的特殊性，仅使用Mirror时才能保留镜像仓库。

### `--save`
```shell
git mclone --save https://github.com/username/repo.git
```

保留后的`remote`
```
E:\Projects\mclone\mclone\nocode>git remote -v
mirror  https://gitclone.com/github.com/kelseyhightower/nocode.git (fetch)
mirror  https://gitclone.com/github.com/kelseyhightower/nocode.git (push)
origin  git@github.com:kelseyhightower/nocode.git (fetch)
origin  git@github.com:kelseyhightower/nocode.git (push)
```

## Safe Clone
SafeClone功能将尝试将预mclone的仓库设为私有镜像仓库，避免仓库泄露风险。

**注意：需要部署服务器支持并启用SafeClone，使用SafeClone后，未经认证的客户端将无法拉取mclone镜像仓库。**

### `--safe`
若服务器支持SafeClone，使用`--safe`参数后，服务器将尝试进行SafeClone。
```shell
git mclone --safe https://github.com/username/repo.git
```

### `--unsafe`
若服务器支持SafeClone且默认启用SafeClone，使用`--unsafe`参数后，服务器将尝试进行UnSafeClone。若服务器强制启用SafeClone，`--unsafe`设置将无效。
```shell
git mclone --unsafe https://github.com/username/repo.git
```

## Proxy Server
自部署mclone后台后，需要将mclone的服务器地址切换为自己的代理服务器地址。

### `--proxy`
`--proxy`参数仅对当前次mclone生效。
```shell
git mclone --proxy https://mclone.my.com https://github.com/username/repo.git
```

### `proxy set <proxy>`
设置mclone的代理服务器地址，一次设置永久生效。
```shell
git mclone proxy set https://mclone.my.com
```

### `proxy del`
删除mclone的代理服务器地址，删除后将使用默认的`mclone.nulastudio.org`服务器。
```shell
git mclone proxy del
```

## FAQ

### mclone是如何实现的？
先将仓库镜像到码云，再从码云拉取镜像仓库。

### 后端代码为什么不开源？
~~mclone项目后端代码使用了大量的模拟请求到码云来实现自动代理下载，如果后端代码开源，一旦码云进行了限制（应该不会这么小气8？但谁知道呢），如验证码等手段，将会对mclone造成致命打击，甚至可能会威胁到mclone的存在意义（如果每mclone一次都需要破解好几个验证码，这谁顶得住？）。但若有大量请求需要开源，可能会考虑开源。~~

已开源，见[backend分支](https://github.com/nulastudio/mclone/tree/backend)。

### 我能自己部署mclone后端程序吗？应该怎么部署？
~~开源后就能。开源后必定有部署教程。~~

已开源，见[backend分支](https://github.com/nulastudio/mclone/tree/backend)。

### 为什么卡在等待代码同步完成中半天都没反应？
1. mclone的仓库较大（其实大仓库镜像也很快的说，超过15分钟一般来说都是第二种情况了）
2. 码云目前镜像仓库采用的是队列的方式，也就是说当前如果有很多待同步的仓库的话，可能会卡很久，甚至半天都没轮到你的仓库同步，这个我们没有办法解决，只能等待或者过段时间再试。

### 如何拉取私有仓库？
**提前声明：请不要使用mclone拉取私有仓库，如确有需要，也请不要拉取特别重要的私有仓库，因为有可能面临仓库泄露的风险！**

**自部署服务器并使用SafeClone请无视上面的Warning。**

1. 进入[GitHub->Setting->Developer settings->Personal access tokens](https://github.com/settings/tokens)页面，点击[Generate new token](https://github.com/settings/tokens/new)新增一个Token，Note填写`mclone`，勾选`repo`，千万别勾别的！点击`Generate token`即可添加一条Token。Token只显示一次，如有多次需要，可复制保存下来方便以后使用。

2. 修改clone地址（仅支持HTTPS、不支持SSH），比如原地址为`https://github.com/username/private-repo.git`则修改为`https://username:token@github.com/username/private-repo.git`，将地址中的token替换为你实际的token。示例：`https://liesauer:xxxxxxxx@github.com/liesauer/mclone-private-demo.git`

3. 使用新地址进行mclone。

### 拉取私有仓库存在哪些风险？
1. Token泄露（间接导致私有仓库泄露）
2. 私有仓库泄露

当然这些风险都是将近不可能的，但仍需引起注意。

**自部署服务器并使用SafeClone请无视上面的风险。**

### 拉取私有仓库为什么存在这些风险？
1. 开发者盗用了Token
2. 码云盗用了Token
3. mclone后端服务器遭到攻击
4. 接入了不安全的网络，导致被监控窃听
5. mclone过程中发生异常（如不正常退出、码云服务器出现异常），导致无法清理仓库，从而导致码云上的镜像仓库处于持续公开状态。

**自部署服务器并使用SafeClone请无视上面的第一点和第五点。**

### 我的账号出现异常行为或者怀疑我的仓库已泄露，应该怎么处理？
1. 如果你的账号在mclone之后出现异常行为，进入[GitHub->Setting->Developer settings->Personal access tokens](https://github.com/settings/tokens)页面，并点击`Delete`将之前添加的mclone Token删除，并请及时修改密码。

2. 如果你怀疑你的仓库已泄露，请及时联系开发者沟通。

**自部署服务器请自行解决。**
