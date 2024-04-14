LibreSpeed的Docker版本可以在这里找到：[GitHub Packages](https://github.com/librespeed/speedtest/pkgs/container/speedtest)

## 下载 Docker 镜像
要从 Docker 仓库下载 LibreSpeed，请使用以下命令：

```
docker pull ghcr.io/kumakaiha/speedtest
```

现在您将拥有一个名为 `kumakaiha/speedtest` 的新 Docker 镜像。

## Docker Compose
要使用 [docker compose](https://docs.docker.com/compose/) 启动容器，可以使用以下配置：

```yml
version: '3.7'
services:
  speedtest:
    container_name: speedtest
    image: ghcr.io/kumakaiha/speedtest:latest
    restart: always
    environment:
      MODE: standalone
      #TITLE: "LibreSpeed"
      #TELEMETRY: "false"
      #ENABLE_ID_OBFUSCATION: "false"
      #REDACT_IP_ADDRESSES: "false"
      #PASSWORD:
      #EMAIL:
      #DISABLE_IPINFO: "false"
      #DISTANCE: "km"
      #WEBPORT: 80
    ports:
      - "80:80" # webport mapping (host:container)
```

请根据预期的操作模式调整环境变量。

## 独立模式
如果您想在单个服务器上安装 LibreSpeed，则需要将其配置为独立模式。为此，请将 `MODE` 环境变量设置为 `standalone`。

测试可以通过端口 80 访问。

以下是在此模式下可用的其他环境变量列表：
* __`TITLE`__: 您的速度测试标题。默认值：`LibreSpeed`
* __`TELEMETRY`__: 是否启用遥测。如果启用，您可能希望持久保存数据。请参见下文。默认值：`false`
* __`ENABLE_ID_OBFUSCATION`__: 当启用遥测并设置为 true 时，测试 ID 将被模糊化，以避免暴露数据库内部的顺序 ID。默认值：`false`
* __`REDACT_IP_ADDRESSES`__: 当启用遥测并设置为 true 时，IP 地址和主机名将从收集的遥测数据中删除，以提高隐私性。默认值：`false`
* __`PASSWORD`__: 访问统计页面的密码。如果未设置，统计页面将不允许访问。
* __`EMAIL`__: GDPR 请求的电子邮件地址。在启用遥测时必须指定。
* __`IPINFO_APIKEY`__: ipinfo.io 的 API 密钥。可选，但如果您希望提供大量测试，则需要。
* __`DISABLE_IPINFO`__: 如果设置为 true，则不会从 ipinfo.io 获取 ISP 信息和距离。默认值：`false`
* __`DISTANCE`__: 当 `DISABLE_IPINFO` 设置为 false 时，指定从服务器测量的距离方式。可以是 `km` 表示千米，`mi` 表示英里，或空字符串表示禁用距离测量。默认值：`km`
* __`WEBPORT`__: 允许选择包含的 Web 服务器的自定义端口。默认值：`80`。请注意，您将需要通过 -p 参数在 Docker 中公开它。
* __`TZ`__: 设定容器时区，并使得遥测数据符合当地时间。默认值：`Asia/Shanghai`。注意：仅在x86设备使用SQLITE的Docker环境中测试。

如果启用了遥测，将在 `http://your.server/results/stats.php` 上提供统计页面，但必须指定密码。

### 持久化 sqlite 数据库

默认的数据库驱动程序是 sqlite。数据库文件写入 `/database/db.sql`。

因此，如果希望数据在镜像更新后保持不变，必须通过 `-v $PWD/db-dir:/database` 挂载卷。

###### 示例
此命令在端口 80 上使用默认设置以独立模式启动 LibreSpeed：

```
docker run -e MODE=standalone -p 80:80 -it ghcr.io/kumakaiha/speedtest
```

此命令在端口 86 上使用独立模式启动 LibreSpeed，包括遥测、ID 模糊化和统计密码：

```
docker run -e MODE=standalone -e TELEMETRY=true -e ENABLE_ID_OBFUSCATION=true -e PASSWORD="yourPasswordHere" -e WEBPORT=86 -p 86:86 -v $PWD/db-dir/:/database -it ghcr.io/kumakaiha/speedtest
```

## 多个测试点
对于多个服务器，您需要设置 1 个或多个 LibreSpeed 后端和 1 个 LibreSpeed 前端。

### 后端模式
在后端模式下，LibreSpeed 仅提供一个测试点，没有用户界面。为此，请将 `MODE` 环境变量设置为 `backend`。

可以通过端口 80 访问以下后端文件：`garbage.php`、`empty.php`、`getIP.php`

以下是在此模式下可用的其他环境变量列表：
* __`IPINFO_APIKEY`__: ipinfo.io 的 API 密钥。可选，但如果您希望提供大量测试，则需要。

###### 示例：
此命令在端口 80 上使用默认设置以后端模式启动 LibreSpeed：

```
docker run -e MODE=backend -p 80:80 -it ghcr.io/librespeed/speedtest
```

### 前端模式
在前端模式下，LibreSpeed 为客户端提供 Web 用户界面和服务器列表。为此：
* 将 `MODE` 环境变量设置为 `frontend`
* 使用您的测试点创建一个 servers.json 文件。语法如下：
    ```
    [
        {
            "name": "服务器 1 的友好名称",
            "server" :"//server1.mydomain.com/",
            "dlURL" :"garbage.php",
            "ulURL" :"empty.php",
            "pingURL" :"empty.php",
            "getIpURL" :"getIP.php"
        },
        {
            "name": "服务器 2 的友好名称",
            "server" :"https://server2.mydomain.com/",
            "dlURL" :"garbage.php",
            "ulURL" :"empty.php",
            "pingURL" :"empty.php",
            "getIpURL" :"getIP.php"
        },
        ...更多服务器...
    ]
    ```
    注意：如果服务器仅支持 HTTP 或 HTTPS，请在服务器字段中指定协议。如果两者都支持，请使用 `//`。
* 将此文件挂载到容器中的 `/servers.json`（在本文件末尾有示例）

测试可以通过端口 80 访问。

以下是在此模式下可用的其他环境变量列表：
* __`TITLE`__: 您的速度测试标题。默认值：`LibreSpeed`
* __`TELEMETRY`__: 是否启用遥测。默认值：`false`
* __`ENABLE_ID_OBFUSCATION`__: 当启用遥测并设置为 true 时，测试 ID 将被模糊化，以避免暴露数据库内部的顺序 ID。默认值：`false`
* __`REDACT_IP_ADDRESSES`__: 当启用遥测并设置为 true 时，IP 地址和主机名将从收集的遥测数据中删除，以提高隐私性。默认值：`false`
* __`PASSWORD`__: 访问统计页面的密码。如果未设置，统计页面将不允许访问。
* __`EMAIL`__: GDPR 请求的电子邮件地址。在启用遥测时必须指定。
* __`DISABLE_IPINFO`__: 如果设置为 true，则不会从 ipinfo.io 获取 ISP 信息和距离。默认值：`false`
* __`DISTANCE`__: 当 `DISABLE_IPINFO` 设置为 false 时，指定从服务器测量的距禂方式。可以是 `km` 表示千米，`mi` 表示英里，或空字符串表示禁用距离测量。默认值：`km`
* __`WEBPORT`__: 允许选择包含的 Web 服务器的自定义端口。默认值：`80`

###### 示例
此命令在前端模式下启动 LibreSpeed，使用给定的 `servers.json` 文件，以及遥测、ID 模糊化和统计密码：

```
docker run -e MODE=frontend -e TELEMETRY=true -e ENABLE_ID_OBFUSCATION=true -e PASSWORD="yourPasswordHere" -v $(pwd)/servers.json:/servers.json -p 80:80 -it ghcr.io/kumakaiha/speedtest
```