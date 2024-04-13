<!DOCTYPE html>
<html>
<head>
<link rel="shortcut icon" href="favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no" />
<link rel="apple-touch-icon" href="favicon.ico">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta charset="UTF-8" />
<script type="text/javascript" src="speedtest.js"></script>
<script type="text/javascript">
function I(i){return document.getElementById(i);}

//LIST OF TEST SERVERS. See documentation for details if needed
var SPEEDTEST_SERVERS= <?= file_get_contents('/servers.json') ?: '[]' ?>;

//INITIALIZE SPEED TEST
var s=new Speedtest(); //create speed test object
<?php if(getenv("TELEMETRY")=="true"){ ?>
s.setParameter("telemetry_level","basic");
<?php } ?>
<?php if(getenv("DISABLE_IPINFO")=="true"){ ?>
s.setParameter("getIp_ispInfo",false);
<?php } ?>
<?php if(getenv("DISTANCE")){ ?>
s.setParameter("getIp_ispInfo_distance","<?=getenv("DISTANCE") ?>");
<?php } ?>

//SERVER AUTO SELECTION
function initServers(){
    var noServersAvailable=function(){
        I("message").innerHTML="No servers available";
    }
    var runServerSelect=function(){
        s.selectServer(function(server){
            if(server!=null){ //at least 1 server is available
                I("loading").className="hidden"; //hide loading message
                //populate server list for manual selection
                for(var i=0;i<SPEEDTEST_SERVERS.length;i++){
                    if(SPEEDTEST_SERVERS[i].pingT==-1) continue;
                    var option=document.createElement("option");
                    option.value=i;
                    option.textContent=SPEEDTEST_SERVERS[i].name;
                    if(SPEEDTEST_SERVERS[i]===server) option.selected=true;
                    I("server").appendChild(option);
                }
                //show test UI
                I("testWrapper").className="visible";
                initUI();
            }else{ //no servers are available, the test cannot proceed
                noServersAvailable();
            }
        });
    }
    if(typeof SPEEDTEST_SERVERS === "string"){
        //need to fetch list of servers from specified URL
        s.loadServerList(SPEEDTEST_SERVERS,function(servers){
            if(servers==null){ //failed to load server list
                noServersAvailable();
            }else{ //server list loaded
                SPEEDTEST_SERVERS=servers;
                runServerSelect();
            }
        });
    }else{
        //hardcoded server list
        s.addTestPoints(SPEEDTEST_SERVERS);
        runServerSelect();
    }
}

var meterBk=/Trident.*rv:(\d+\.\d+)/i.test(navigator.userAgent)?"#EAEAEA":"#80808040";
var dlColor="#6060AA",
	ulColor="#616161";
var progColor=meterBk;

//CODE FOR GAUGES
function drawMeter(c,amount,bk,fg,progress,prog){
	var ctx=c.getContext("2d");
	var dp=window.devicePixelRatio||1;
	var cw=c.clientWidth*dp, ch=c.clientHeight*dp;
	var sizScale=ch*0.0055;
	if(c.width==cw&&c.height==ch){
		ctx.clearRect(0,0,cw,ch);
	}else{
		c.width=cw;
		c.height=ch;
	}
	ctx.beginPath();
	ctx.strokeStyle=bk;
	ctx.lineWidth=12*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,Math.PI*0.1);
	ctx.stroke();
	ctx.beginPath();
	ctx.strokeStyle=fg;
	ctx.lineWidth=12*sizScale;
	ctx.arc(c.width/2,c.height-58*sizScale,c.height/1.8-ctx.lineWidth,-Math.PI*1.1,amount*Math.PI*1.2-Math.PI*1.1);
	ctx.stroke();
	if(typeof progress !== "undefined"){
		ctx.fillStyle=prog;
		ctx.fillRect(c.width*0.3,c.height-16*sizScale,c.width*0.4*progress,4*sizScale);
	}
}
function mbpsToAmount(s){
	return 1-(1/(Math.pow(1.3,Math.sqrt(s))));
}
function format(d){
    d=Number(d);
    if(d<10) return d.toFixed(2);
    if(d<100) return d.toFixed(1);
    return d.toFixed(0);
}

//UI CODE
var uiData=null;
function startStop(){
    if(s.getState()==3){
		//speed test is running, abort
		s.abort();
		data=null;
		I("startStopBtn").className="";
		I("server").disabled=false;
		initUI();
	}else{
		//test is not running, begin
		I("startStopBtn").className="running";
		I("shareArea").style.display="none";
		I("server").disabled=true;
		s.onupdate=function(data){
            uiData=data;
		};
		s.onend=function(aborted){
            I("startStopBtn").className="";
            I("server").disabled=false;
            updateUI(true);
            if(!aborted){
                //if testId is present, show sharing panel, otherwise do nothing
                try{
                    var testId=uiData.testId;
                    if(testId!=null){
                        var shareURL=window.location.href.substring(0,window.location.href.lastIndexOf("/"))+"/results/?id="+testId;
                        I("resultsImg").src=shareURL;
                        I("resultsURL").value=shareURL;
                        I("testId").innerHTML=testId;
                        I("shareArea").style.display="";
                    }
                }catch(e){}
            }
		};
		s.start();
	}
}
//this function reads the data sent back by the test and updates the UI
function updateUI(forced){
	if(!forced&&s.getState()!=3) return;
	if(uiData==null) return;
	var status=uiData.testState;
	I("ip").textContent=uiData.clientIp;
	I("dlText").textContent=(status==1&&uiData.dlStatus==0)?"...":format(uiData.dlStatus);
	drawMeter(I("dlMeter"),mbpsToAmount(Number(uiData.dlStatus*(status==1?oscillate():1))),meterBk,dlColor,Number(uiData.dlProgress),progColor);
	I("ulText").textContent=(status==3&&uiData.ulStatus==0)?"...":format(uiData.ulStatus);
	drawMeter(I("ulMeter"),mbpsToAmount(Number(uiData.ulStatus*(status==3?oscillate():1))),meterBk,ulColor,Number(uiData.ulProgress),progColor);
	I("pingText").textContent=format(uiData.pingStatus);
	I("jitText").textContent=format(uiData.jitterStatus);
}
function oscillate(){
	return 1+0.02*Math.sin(Date.now()/100);
}
//update the UI every frame
window.requestAnimationFrame=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.msRequestAnimationFrame||(function(callback,element){setTimeout(callback,1000/60);});
function frame(){
	requestAnimationFrame(frame);
	updateUI();
}
frame(); //start frame loop
//function to (re)initialize UI
function initUI(){
	drawMeter(I("dlMeter"),0,meterBk,dlColor,0);
	drawMeter(I("ulMeter"),0,meterBk,ulColor,0);
	I("dlText").textContent="";
	I("ulText").textContent="";
	I("pingText").textContent="";
	I("jitText").textContent="";
	I("ip").textContent="";
}
function copyResultsURL() {
    var resultsURL = document.getElementById('resultsURL');
    resultsURL.style.display = 'block'; // 显示文本框
    resultsURL.select(); // 选中文本
    document.execCommand('copy'); // 复制文本
    resultsURL.style.display = 'none'; // 隐藏文本框
    alert('结果链接已复制');
}
</script>
<style type="text/css">
	html,body{
		border:none; padding:0; margin:0;
		background:#FFFFFF;
		color:#202020;
	}
	body{
		text-align:center;
		font-family:"Roboto",sans-serif;
	}
	h1{
		color:#404040;
	}
	#loading{
		background-color:#FFFFFF;
		color:#404040;
		text-align:center;
	}
	span.loadCircle{
		display:inline-block;
		width:2em;
		height:2em;
		vertical-align:middle;
		background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAMAAAD04JH5AAAAP1BMVEUAAAB2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZ2dnZyFzwnAAAAFHRSTlMAEvRFvX406baecwbf0casimhSHyiwmqgAAADpSURBVHja7dbJbQMxAENRahnN5lkc//5rDRAkDeRgHszXgACJoKiIiIiIiIiIiIiIiIiIiIj4HHspsrpAVhdVVguzrA4OWc10WcEqpwKbnBo0OU1Q5NSpsoJFTgOecrrdEag85DRgktNqfoEdTjnd7hrEHMEJvmRUYJbTYk5Agy6nau6Abp5Cm7mDBtRdPi9gyKdU7w4p1fsLvyqs8hl4z9/w3n/Hmr9WoQ65lAU4d7lMYOz//QboRR5jBZibLMZdAR6O/Vfa1PlxNr3XdS3HzK/HVPRu/KnLs8iAOh993VpRRERERMT/fAN60wwWaVyWwAAAAABJRU5ErkJggg==');
		background-size:2em 2em;
		margin-right:0.5em;
		animation: spin 0.6s linear infinite;
	}
	@keyframes spin{
		0%{transform:rotate(0deg);}
		100%{transform:rotate(359deg);}
	}
	#startStopBtn{
		display:inline-block;
		margin:0 auto;
		color:#6060AA;
		background-color:rgba(0,0,0,0);
		border:0.15em solid #6060FF;
		border-radius:0.3em;
		transition:all 0.3s;
		box-sizing:border-box;
		width:8em; height:3em;
		line-height:2.7em;
		cursor:pointer;
		box-shadow: 0 0 0 rgba(0,0,0,0.1), inset 0 0 0 rgba(0,0,0,0.1);
	}
	#startStopBtn:hover{
		box-shadow: 0 0 2em rgba(0,0,0,0.1), inset 0 0 1em rgba(0,0,0,0.1);
	}
	#startStopBtn.running{
		background-color:#FF3030;
		border-color:#FF6060;
		color:#FFFFFF;
	}
	#startStopBtn:before{
		content:"开始测速";
	}
	#startStopBtn.running:before{
		content:"终止";
	}
	#serverArea{
		margin-top:1em;
	}
	#server{
		font-size:1em;
		padding:0.2em;
	}
	#test{
		margin-top:2em;
		margin-bottom:10em;
	}
	div.testArea{
		display:inline-block;
		width:16em;
		height:12.5em;
		position:relative;
		box-sizing:border-box;
	}
	div.testArea2{
		display:inline-block;
		width:14em;
		height:7em;
		position:relative;
		box-sizing:border-box;
		text-align:center;
	}
	div.testArea div.testName{
		position:absolute;
		top:0.1em; left:0;
		width:100%;
		font-size:1.4em;
		z-index:9;
	}
	div.testArea2 div.testName{
        display:block;
        text-align:center;
        font-size:1.4em;
	}
	div.testArea div.meterText{
		position:absolute;
		bottom:1.55em; left:0;
		width:100%;
		font-size:2.5em;
		z-index:9;
	}
	div.testArea2 div.meterText{
        display:inline-block;
        font-size:2.5em;
	}
	div.meterText:empty:before{
		content:"0.00";
	}
	div.testArea div.unit{
		position:absolute;
		bottom:2em; left:0;
		width:100%;
		z-index:9;
	}
	div.testArea2 div.unit{
		display:inline-block;
	}
	div.testArea canvas{
		position:absolute;
		top:0; left:0; width:100%; height:100%;
		z-index:1;
	}
	div.testGroup{
		display:block;
        margin: 0 auto;
	}
	#shareArea{
		width:95%;
		max-width:40em;
		margin:0 auto;
		margin-top:2em;
	}
	#shareArea > *{
		display:block;
		width:100%;
		height:auto;
		margin: 0.25em 0;
	}
	#privacyPolicy{
        position:fixed;
        top:2em;
        bottom:2em;
        left:2em;
        right:2em;
        overflow-y:auto;
        width:auto;
        height:auto;
        box-shadow:0 0 3em 1em #000000;
        z-index:999999;
        text-align:left;
        background-color:#FFFFFF;
        padding:1em;
	}
	a.privacy{
        text-align:center;
        font-size:0.8em;
        color:#808080;
        display:block;
	}
	@media all and (max-width:40em){
		body{
			font-size:0.8em;
		}
	}
	div.visible{
		animation: fadeIn 0.4s;
		display:block;
	}
	div.hidden{
		animation: fadeOut 0.4s;
		display:none;
	}
	@keyframes fadeIn{
		0%{
			opacity:0;
		}
		100%{
			opacity:1;
		}
	}
	@keyframes fadeOut{
		0%{
			display:block;
			opacity:1;
		}
		100%{
			display:block;
			opacity:0;
		}
	}
</style>
<title><?= getenv('TITLE') ?: '服务器测速' ?></title>
</head>
<body onload="initServers()">
<h1><?= getenv('TITLE') ?: '服务器点对点测速' ?></h1>
<div id="loading" class="visible">
	<p id="message"><span class="loadCircle"></span>选择一个服务器...</p>
</div>
<div id="testWrapper" class="hidden">
	<div id="startStopBtn" onclick="startStop()"></div>
	<?php if(getenv("TELEMETRY")=="true"){ ?>
		<a class="privacy" href="#" onclick="window.location.href = window.location.origin + '/results/stats.php';">进入统计数据页面</a>
        <a class="privacy" href="#" onclick="I('privacyPolicy').style.display=''">隐私政策</a>
	<?php } ?>
	<div id="serverArea">
		Server: <select id="server" onchange="s.setSelectedServer(SPEEDTEST_SERVERS[this.value])"></select>
	</div>
	<div id="test">
		<div class="testGroup">
            <div class="testArea2">
				<div class="testName">延迟</div>
				<div id="pingText" class="meterText" style="color:#AA6060"></div>
				<div class="unit">ms</div>
			</div>
			<div class="testArea2">
				<div class="testName">抖动</div>
				<div id="jitText" class="meterText" style="color:#AA6060"></div>
				<div class="unit">ms</div>
			</div>
		</div>
		<div class="testGroup">
			<div class="testArea">
				<div class="testName">下载</div>
				<canvas id="dlMeter" class="meter"></canvas>
				<div id="dlText" class="meterText"></div>
				<div class="unit">Mbit/s</div>
			</div>
			<div class="testArea">
				<div class="testName">上传</div>
				<canvas id="ulMeter" class="meter"></canvas>
				<div id="ulText" class="meterText"></div>
				<div class="unit">Mbit/s</div>
			</div>
		</div>
		<div id="ipArea">
			<span id="ip"></span>
		</div>
		<div id="shareArea" style="display:none">
			<h3>被共享的测速结果</h3>
			<p>测试 ID: <span id="testId"></span></p>
			<input type="text" value="" id="resultsURL" readonly="readonly" style="display: none;" />
			<img src="" id="resultsImg" onclick="copyResultsURL()" />
		</div>
	</div>
	由此获取本站 <a href="https://github.com/KUMAKAIHA/speedtest_chinese">源代码</a>
</div>
<div id="privacyPolicy" style="display:none">
<h2>隐私政策</h2>
    <p>此测速服务器已配置为启用遥测。</p>
    <h4>我们收集哪些数据</h4>
    <p>
        在测试结束时，将收集并存储以下数据：
        <ul>
            <li>测试 ID</li>
            <li>测试时间</li>
            <li>测试结果（下载速度、上传速度、ping 和抖动）</li>
            <li>IP 地址</li>
            <li>ISP 信息</li>
            <li>大致位置（从 IP 地址推断，而非 GPS）</li>
            <li>用户代理和浏览器区域设置</li>
            <li>测试日志（不包含个人信息）</li>
        </ul>
    </p>
    <h4>我们如何使用数据</h4>
    <p>
        通过此服务收集的数据用于：
        <ul>
            <li>允许分享测试结果（适用于论坛等的可共享图像）</li>
            <li>改进为您提供的服务（例如，检测我们这一侧的问题）</li>
        </ul>
        不会向第三方披露个人信息。
    </p>
    <h4>您的同意</h4>
    <p>
        通过开始测试，您同意本隐私政策条款。
    </p>
    <h4>数据删除</h4>
    <p>
        如果您希望删除您的信息，您需要提供测试 ID 或您的 IP 地址。这是唯一识别您数据的方式，没有这些信息，我们将无法满足您的请求。<br/><br/>
        请通过以下电子邮件地址联系以获取所有删除请求： <a href="mailto:<?=getenv("EMAIL") ?>"><?=getenv("EMAIL") ?></a>。
    </p>
    <br/><br/>
    <a class="privacy" href="#" onclick="I('privacyPolicy').style.display='none'">关闭</a><br/>
</div>
</body>
</html>
