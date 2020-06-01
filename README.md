## 环境要求
1. Apache
2. PHP >= 5.6

## 部署

### 1. 初始化
进入后台程序代码目录，并执行以下命令
```shell
composer install
```

### 2. 创建数据库
将`data/database.example.db`重命名为`data/database.db`，并使用数据库管理工具在`mclone_accounts`添加你的mclone码云账号，可添加多个。

`username`、`password`、`token`三个字段必填，`cookie`字段可忽略。

`username`：mclone码云账号名字
`password`：mclone码云账号密码
`token`：mclone码云账号Private Access Token，必须将`repo`下的权限勾选。

### 3. 拷贝程序
将后台程序拷贝至网站根目录下，若服务器支持修改网站根目录，请将网站根目录修改为`public`目录

### 4. 配置
修改`config/config.php`即可

### 5. 检查data目录权限
程序已默认配置Apache访问权限，`data`目录将无法被访问，以保护数据泄露，若使用Nginx、IIS等程序部署，请自行进行访问权限配置

### 6. 设置Proxy Server
将mclone程序的Proxy Server设为自部署服务器
