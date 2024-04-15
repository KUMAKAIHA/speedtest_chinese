![LibreSpeed Logo](https://github.com/librespeed/speedtest/blob/master/.logo/logo3.png?raw=true)

# LibreSpeed

无需Flash、Java、WebSocket，没有废话。

这是一个用Javascript实现的非常轻量级的网速测试工具，使用XMLHttpRequest和Web Workers。

## 试试吧
[进行网速测试](https://librespeed.org)

## 兼容性
支持所有现代浏览器：IE11、最新版Edge、最新版Chrome、最新版Firefox、最新版Safari。
同样适用于移动版本。

## 特性
* 下载速度
* 上传速度
* 延迟（Ping）
* 抖动（Jitter）
* IP地址、ISP、与服务器的距离（可选）
* 遥测数据（可选）
* 结果分享（可选）
* 多点测试（可选）

![正在进行的Speedtest屏幕录像](https://speedtest.fdossena.com/mpot_v6.gif)

## 服务器要求
* 一个配置合理的快速Web服务器，支持Apache 2（也支持nginx、IIS）
* PHP 5.4（也支持其他后端）
* MySQL数据库来存储测试结果（可选，也支持Microsoft SQL Server、PostgreSQL和SQLite）
* 一个快速的互联网连接

## 安装
假设你已经安装了PHP，安装步骤相当简单。
我在QNAP上设置了这个。
以此次为例，我在网络共享区域使用了一个名为**speedtest**的文件夹。

1. 选择example-xxx.html文件中的一个作为你speedtest文件夹中的新index.html。我使用的是：example-singleServer-full.html
2. 将speedtest.js、speedtest_worker.js和favicon.ico添加到你的speedtest文件夹中。
3. 将整个backend文件夹下载到speedtest/backend中。
4. 将整个results文件夹下载到speedtest/results中。
5. 确保你的权限允许执行（755）。
6. 访问YOURSITE/speedtest/index.html，就这样！

### 安装视频
这里有一个更深入的安装视频：
* [Ubuntu Server 19.04快速开始安装指南](https://fdossena.com/?p=speedtest/quickstart_v5_ubuntu.frag)

## Android应用
为你的LibreSpeed安装构建Android客户端的模板可以在[这里](https://github.com/librespeed/speedtest-android)找到。

## Docker
Docker镜像可以在[Docker Hub](https://hub.docker.com/repository/docker/kumakaiha/speedtest/general)上找到，查看我们的[docker文档](doc_docker.md)了解更多信息。

## Go后端
一个Go实现版本可以在[`speedtest-go`](https://github.com/librespeed/speedtest-go)仓库中找到，由[Maddie Zhan](https://github.com/maddie)维护。

## Node.js后端
`node`分支中有一个部分实现的Node.js版本，由[dunklesToast](https://github.com/dunklesToast)开发。目前不推荐使用。

## 捐赠
[![通过Liberapay捐赠](https://liberapay.com/assets/widgets/donate.svg)](https://liberapay.com/fdossena/donate)
[通过PayPal捐赠](https://www.paypal.me/sineisochronic)

## 许可证
版权所有 (C) 2016-2022 Federico Dossena

这个程序是自由软件：你可以在GNU Lesser General Public License的条款下重新分发和/或修改它，该许可证由自由软件基金会发布，要么是许可证的第3版，或者（根据你的选择）任何后续版本。

这个程序希望它是有用的，
但没有任何保证；甚至没有暗示的保证MERCHANTABILITY或特定目的的适用性。详情请参见GNU通用公共许可证。

你应该已经收到了GNU Lesser General Public License的副本，
和这个程序一起。如果没有，参见<https://www.gnu.org/licenses/lgpl>。