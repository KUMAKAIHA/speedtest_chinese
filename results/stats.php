<?php
session_start();
error_reporting(0);

require 'telemetry_settings.php';
require_once 'telemetry_db.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
    <head>
        <title>服务器测速 - 统计</title>
        <style type="text/css">
            html,body{
                margin:0;
                padding:0;
                border:none;
                width:100%; min-height:100%;
            }
            html{
                background-color: hsl(198,72%,35%);
                font-family: "Segoe UI","Roboto",sans-serif;
            }
            body{
                background-color:#FFFFFF;
                box-sizing:border-box;
                width:100%;
                max-width:70em;
                margin:4em auto;
                box-shadow:0 1em 6em #00000080;
                padding:1em 1em 4em 1em;
                border-radius:0.4em;
            }
            h1,h2,h3,h4,h5,h6{
                font-weight:300;
                margin-bottom: 0.1em;
            }
            h1{
                text-align:center;
            }
            table{
                margin:2em 0;
                width:100%;
            }
            table, tr, th, td {
                border: 1px solid #AAAAAA;
            }
            th {
                width: 6em;
            }
            td {
                word-break: break-all;
            }
            div {
                margin: 1em 0;
            }
        </style>
    </head>
    <body>
        <h1>服务器测速 - 统计</h1>
        <?php
        if (!isset($stats_password) || $stats_password === 'PASSWORD') {
            ?>
                请在telemetry_settings.php中设置$stats_password以启用访问。
            <?php
        } elseif ($_SESSION['logged'] === true) {
            if ($_GET['op'] === 'logout') {
                $_SESSION['logged'] = false;
                ?><script type="text/javascript">window.location=location.protocol+"//"+location.host+location.pathname;</script><?php
            } else {
                ?>
                <form action="stats.php" method="GET">
                    <input type="hidden" name="op" value="logout" />
                    <input type="submit" value="登出" />
                    <input type="button" value="返回" onclick="window.location.href = window.location.origin;" style="margin-left: 10px;" />
                </form>
                <form action="stats.php" method="GET">
                    <h3>搜索测速结果</h3>
                    <input type="hidden" name="op" value="id" />
                    <input type="text" name="id" id="id" placeholder="测试 ID" value=""/>
                    <input type="submit" value="查找" />
                    <input type="submit" onclick="document.getElementById('id').value=''" value="显示最近100次测试" />
                </form>
                <?php
                if ($_GET['op'] === 'id' && !empty($_GET['id'])) {
                    $speedtest = getSpeedtestUserById($_GET['id']);
                    $speedtests = [];
                    if (false === $speedtest) {
                        echo '<div>尝试获取 ID "'.htmlspecialchars($_GET['id'], ENT_HTML5, 'UTF-8').'" 的速度测试结果时出现错误。</div>';
                    } elseif (null === $speedtest) {
                        echo '<div>未找到 ID "'.htmlspecialchars($_GET['id'], ENT_HTML5, 'UTF-8').'" 的速度测试结果。</div>';
                    } else {
                        $speedtests = [$speedtest];
                    }
                } else {
                    $speedtests = getLatestSpeedtestUsers();
                    if (false === $speedtests) {
                        echo '<div>尝试获取最新速度测试结果时出现错误。</div>';
                    } elseif (empty($speedtests)) {
                        echo '<div>数据库中找不到任何速度测试结果。</div>';
                    }
                }
                
                foreach ($speedtests as $speedtest) {
                    ?>
                    <table>
                        <tr>
                            <th>测试 ID</th>
                            <td><?= htmlspecialchars($speedtest['id_formatted'], ENT_HTML5, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th>日期与时间</th>
                            <td><?= htmlspecialchars($speedtest['timestamp'], ENT_HTML5, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th>IP和ISP信息</th>
                            <td>
                                <?= htmlspecialchars($speedtest['ip'], ENT_HTML5, 'UTF-8') ?><br/>
                                <?= htmlspecialchars($speedtest['ispinfo'], ENT_HTML5, 'UTF-8') ?>
                            </td>
                        </tr>
                        <tr>
                            <th>设备信息</th>
                            <td><?= htmlspecialchars($speedtest['ua'], ENT_HTML5, 'UTF-8') ?><br/>
                                <?= htmlspecialchars($speedtest['lang'], ENT_HTML5, 'UTF-8') ?>
                            </td>
                        </tr>
                        <tr>
                            <th>下载速度</th>
                            <td><?= htmlspecialchars($speedtest['dl'], ENT_HTML5, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th>上传速度</th>
                            <td><?= htmlspecialchars($speedtest['ul'], ENT_HTML5, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th>延迟</th>
                            <td><?= htmlspecialchars($speedtest['ping'], ENT_HTML5, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th>抖动</th>
                            <td><?= htmlspecialchars($speedtest['jitter'], ENT_HTML5, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th>日志</th>
                            <td><?= htmlspecialchars($speedtest['log'], ENT_HTML5, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <th>其它信息</th>
                            <td><?= htmlspecialchars($speedtest['extra'], ENT_HTML5, 'UTF-8') ?></td>
                        </tr>
                    </table>
                    <?php
                }
            }
        } elseif ($_GET['op'] === 'login' && $_POST['password'] === $stats_password) {
            $_SESSION['logged'] = true;
            ?><script type="text/javascript">window.location=location.protocol+"//"+location.host+location.pathname;</script><?php
        } else {
            ?>
            <form action="stats.php?op=login" method="POST">
                <h3>登录</h3>
                <input type="password" name="password" placeholder="密码" value=""/>
                <input type="submit" value="登录" />
            </form>
            <?php
        }
        ?>
    </body>
</html>