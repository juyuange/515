<?php
require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/func.inc.php');
header('Content-Type: text/html; charset='.APP_CHARSET);

//如果config配置里的密码是明文，就转换为密文
if(strlen($config['password'])!=32 && is_writable(APPDIR.'/config.inc.php')){
    $content = file_get_contents(APPDIR.'/config.inc.php');
    if(preg_match('#\'password\'\s*=>\s*\'(.+?)\'\s*,#', $content, $match)){
        $password = md5("{$config['password']} 01 Jan 1970 00:00:00 GMT");
        $content = str_replace($match[0], "'password' => '{$password}',", $content);
        if(file_put_contents(APPDIR.'/config.inc.php', $content, LOCK_EX)>0){
            $config['password'] = $password;
        }
    }
}

//验证登录
check_authentication($config['password']);


$act=isset($_GET['act'])?$_GET['act']:'';
if(!$act){
	//首页
	echo '<!DOCTYPE html><html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset='.APP_CHARSET.'" />
	<title>网站管理</title>
	</head>
	<frameset rows="32,*" cols="*" frameborder="0" border="0">
	  <frame src="?act=top" scrolling="no" name="top" id="top" />
	  <frame src="?act=counter&type=visit" scrolling="auto" name="main" id="main" />
	</frameset>
	<noframes>
		<body>当前浏览器不支持框架，请更换浏览器！</body>
	</noframes>
	</html>';

}elseif($act=='top'){
	//顶部
	echo '<!DOCTYPE html><html>
	<head>
	<style type="text/css">
		html,body{margin:0;padding:0;height:30px;line-height:30px;background-color:#F6F6F6;border-bottom:2px solid #65748A;}
		a{margin-left:10px;color:#000;text-decoration:none;}
		a:hover{color:red;}
		a#on{color:red;font-weight:bold;}
		span{margin-left:10px;}
	</style>
	<script type="text/javascript">
		function clicked(a){
			var e=document.getElementById("on");
			if(e) e.id="";
			a.id="on";
		}
	</script>
	</head>
	<body>
	<a href="./" target="_top">返回首页</a>'.
	'<a href="?act=option" target="main" onclick="clicked(this);">设置</a>'.
	'<a href="?act=counter&type=visit" target="main" onclick="clicked(this);" id="on">访问统计</a>'.
	'<a href="?act=counter&type=3tui" target="main" onclick="clicked(this);">三退统计</a>'.
	'<a href="./guestbook.php?act=admin" target="main" onclick="clicked(this);">留言</a>';

	if(file_exists(APPDIR.'/faq.php')){
		echo '<a href="./faq.php?act=admin" target="main" onclick="clicked(this);">资料</a>'.
			'<a href="?act=counter&type=visit_faq" target="main" onclick="clicked(this);" style="margin-left:0;">[统计]</a>';
	}
	if(file_exists(APPDIR.'/mobile/')){
		echo '<span>|</span>'.
			'<a href="./mobile/counter/dl_count.php" target="main" onclick="clicked(this);">手机访问</a>'.
			'<a href="./mobile/note.php?act=admin" target="main" onclick="clicked(this);">手机留言</a>'.
			'<a href="./mobile/tui.php?act=admin" target="main" onclick="clicked(this);">手机三退</a>';
	}
	if(file_exists(APPDIR.'/video/')){
		echo '<span>|</span>'.
			'<a href="./video/forme.php" target="main" onclick="clicked(this);">影音</a>'.
			'<a href="?act=counter&type=visit_video" target="main" onclick="clicked(this);" style="margin-left:0;">[统计]</a>';
	}
	if(file_exists(APPDIR.'/pan/')){
		echo '<span>|</span>'.
			'<a href="./pan/forme.php" target="main" onclick="clicked(this);">下载</a>'.
			'<a href="?act=counter&type=visit_pan" target="main" onclick="clicked(this);" style="margin-left:0;">[统计]</a>';
	}
	if(file_exists(APPDIR.'/tui/index.php')){
	    echo '<span>|</span><a href="./tui/forme.php" target="main" onclick="clicked(this);">三退表单</a></a>';
	}
	echo "</body></html>";

}elseif($act=='counter'){
    if(!empty($config['allowed_spider']) || !empty($config['simple_for_mobile'])){
        echo '<div class="caution">为了防内容过滤，config.inc.php 里的 allowed_spider 和 simple_for_mobile 已经被禁用，这两个设置已经不再生效</div>';
    }
	$options=array(
		'visit' =>			array('title'=>'在线代理访问统计', 'name'=>'访问', 'file'=>DATADIR.'/~counter_visit.dat', 'desc'=>'这里统计的是访问代理页面的独立访客数'),
		'3tui' =>			array('title'=>'直接三退次数统计', 'name'=>'三退', 'file'=>DATADIR.'/~counter_3tui.dat', 'desc'=>'这里统计的是成功完成退党提交的次数。无论是自主浏览到退党页面進行提交的，还是通过底部导航条快速提交退党的，只要最后在退党网站的提交结果页面显示为提交成功的，都将会在此進行统计。'),
		'visit_faq' =>		array('title'=>'真相问答访问统计', 'name'=>'访问', 'file'=>DATADIR.'/~counter_visit_faq.dat', 'desc'=>'这里统计的是真相问答页面faq.php来访的独立访客数'),
		'visit_video' =>	array('title'=>'在线影音访问统计', 'name'=>'访问', 'file'=>APPDIR.'/data/~counter_visit_video.dat', 'desc'=>'这里统计的是在线视频页面（包含首页和播放页）来访的独立访客数'),
		'visit_pan' =>		array('title'=>'在线下载访问统计', 'name'=>'访问', 'file'=>APPDIR.'/data/~counter_visit_pan.dat', 'desc'=>'这里统计的是百度网盘在线下载页面（包含首页和下载页）来访的独立访客数'),
	);
	$type = !empty($_GET['type']) && isset($options[$_GET['type']]) ? $_GET['type'] : 'visit';

	if($type=='visit'){
	    $address_counter = get_address_counter();
	}

	$option = $options[$type];
	$counter_file = $option['file'];
	if(!include(APPDIR.'/include/counter.inc.php')){
		exit('File not found: counter.inc.php');
	}

}

elseif($act=='option'){
    $message = '';
    //内置列表
    $fixed = '';
    foreach($address as $k=>$v){
        $fixed .= "{$k}|{$v}\n";
    }
    //保存设置网站列表
    if(isset($_POST['custom'])){
        $custom = '';
        $error = '';
        $lines = explode("\n", $_POST['custom']);
        foreach($lines as $k=>$line){
            $line = trim($line);
            if($line){
                $record = explode('|', $line);
                if(count($record)!=3 || ($id=intval($record[0]))<=0){
                    $error .= "第 <b>".($k+1)."</b> 行格式有误：{$line}<br/>";
                }elseif(isset($address[$id])){
                    $error .= "第 <b>".($k+1)."</b> 行的编号 (<b>{$record[0]}</b>) 已在内置列表里了！<br/>";
                }elseif(strpos($record[1],'<')!==false || strpos($record[1],'>')!==false){
                    $error .= "第 <b>".($k+1)."</b> 行标题有误：{$line}<br/>";
                }elseif(!preg_match('#^\*?https?://[\w\-\.:]{6,}(/|$)#', $record[2])){
                    $error .= "第 <b>".($k+1)."</b> 行网址有误：{$line}<br/>";
                }else{
                    $custom .= "{$line}\n";
                }
            }
        }
        if($error){
            $custom = trim($_POST['custom']);
            $message = '<div class="row error">'.$error.'</div>';
        }else{
            if(empty($custom)) {
                if(file_exists(DATADIR.'/address.dat')) unlink(DATADIR.'/address.dat');
            }else{
                file_put_contents(DATADIR.'/address.dat', $custom);
            }
            $message = '<div class="row success">已成功保存</div>';
        }
    }else{
        //载入自定义列表
        $custom = file_exists(DATADIR.'/address.dat') ? file_get_contents(DATADIR.'/address.dat') : '';
    }
	//顶部
	echo <<<EOH
<!DOCTYPE html><html>
<head>
<style type="text/css">
	html,body{background-color:#FFF;}
	.row{margin-top:20px;}
	.row strong{margin-bottom:5px; display:block;}
	.success{color:green;}
	.error{color:red;}
	.tip{color:#666;}
	textarea{width:90%;}
	button{padding:2px 5px;}
	pre{line-height:20px; width:90%; border:1px solid #999; padding:2px; margin:0; background-color:#F6F6F6;}
</style>
<script type="text/javascript">
	function clicked(a){
		var e=document.getElementById("on");
		if(e) e.id="";
		a.id="on";
	}
</script>
</head>
<body>
<form action="" method="post">
<div class="row">
    <strong>内置列表</strong>
    <pre>{$fixed}</pre>
</div>
<div class="row">
    <strong>自定义列表</strong>
    <textarea rows="10" name="custom">{$custom}</textarea>
    <div class="tip">参照内置列表，每行一个，格式为： 数字编号|网页名称|完整网址<br>如果网址不需要加密，就在网址前标记上星号，例如 *https://git.io/fgp</div>
</div>
<div class="row">
    <button type="submit">保存设置</button>
</div>
</form>
{$message}
</body></html>
EOH;
}