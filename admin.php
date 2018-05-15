<?php
require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/func.inc.php');
header('Content-Type: text/html; charset='.APP_CHARSET);

//���config����������������ģ���ת��Ϊ����
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

//��֤��¼
check_authentication($config['password']);


$act=isset($_GET['act'])?$_GET['act']:'';
if(!$act){
	//��ҳ
	echo '<!DOCTYPE html><html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset='.APP_CHARSET.'" />
	<title>��վ����</title>
	</head>
	<frameset rows="32,*" cols="*" frameborder="0" border="0">
	  <frame src="?act=top" scrolling="no" name="top" id="top" />
	  <frame src="?act=counter&type=visit" scrolling="auto" name="main" id="main" />
	</frameset>
	<noframes>
		<body>��ǰ�������֧�ֿ�ܣ�������������</body>
	</noframes>
	</html>';

}elseif($act=='top'){
	//����
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
	<a href="./" target="_top">������ҳ</a>'.
	'<a href="?act=option" target="main" onclick="clicked(this);">����</a>'.
	'<a href="?act=counter&type=visit" target="main" onclick="clicked(this);" id="on">����ͳ��</a>'.
	'<a href="?act=counter&type=3tui" target="main" onclick="clicked(this);">����ͳ��</a>'.
	'<a href="./guestbook.php?act=admin" target="main" onclick="clicked(this);">����</a>';

	if(file_exists(APPDIR.'/faq.php')){
		echo '<a href="./faq.php?act=admin" target="main" onclick="clicked(this);">����</a>'.
			'<a href="?act=counter&type=visit_faq" target="main" onclick="clicked(this);" style="margin-left:0;">[ͳ��]</a>';
	}
	if(file_exists(APPDIR.'/mobile/')){
		echo '<span>|</span>'.
			'<a href="./mobile/counter/dl_count.php" target="main" onclick="clicked(this);">�ֻ�����</a>'.
			'<a href="./mobile/note.php?act=admin" target="main" onclick="clicked(this);">�ֻ�����</a>'.
			'<a href="./mobile/tui.php?act=admin" target="main" onclick="clicked(this);">�ֻ�����</a>';
	}
	if(file_exists(APPDIR.'/video/')){
		echo '<span>|</span>'.
			'<a href="./video/forme.php" target="main" onclick="clicked(this);">Ӱ��</a>'.
			'<a href="?act=counter&type=visit_video" target="main" onclick="clicked(this);" style="margin-left:0;">[ͳ��]</a>';
	}
	if(file_exists(APPDIR.'/pan/')){
		echo '<span>|</span>'.
			'<a href="./pan/forme.php" target="main" onclick="clicked(this);">����</a>'.
			'<a href="?act=counter&type=visit_pan" target="main" onclick="clicked(this);" style="margin-left:0;">[ͳ��]</a>';
	}
	if(file_exists(APPDIR.'/tui/index.php')){
	    echo '<span>|</span><a href="./tui/forme.php" target="main" onclick="clicked(this);">���˱�</a></a>';
	}
	echo "</body></html>";

}elseif($act=='counter'){
    if(!empty($config['allowed_spider']) || !empty($config['simple_for_mobile'])){
        echo '<div class="caution">Ϊ�˷����ݹ��ˣ�config.inc.php ��� allowed_spider �� simple_for_mobile �Ѿ������ã������������Ѿ�������Ч</div>';
    }
	$options=array(
		'visit' =>			array('title'=>'���ߴ������ͳ��', 'name'=>'����', 'file'=>DATADIR.'/~counter_visit.dat', 'desc'=>'����ͳ�Ƶ��Ƿ��ʴ���ҳ��Ķ����ÿ���'),
		'3tui' =>			array('title'=>'ֱ�����˴���ͳ��', 'name'=>'����', 'file'=>DATADIR.'/~counter_3tui.dat', 'desc'=>'����ͳ�Ƶ��ǳɹ�����˵��ύ�Ĵ���������������������˵�ҳ���M���ύ�ģ�����ͨ���ײ������������ύ�˵��ģ�ֻҪ������˵���վ���ύ���ҳ����ʾΪ�ύ�ɹ��ģ��������ڴ��M��ͳ�ơ�'),
		'visit_faq' =>		array('title'=>'�����ʴ����ͳ��', 'name'=>'����', 'file'=>DATADIR.'/~counter_visit_faq.dat', 'desc'=>'����ͳ�Ƶ��������ʴ�ҳ��faq.php���õĶ����ÿ���'),
		'visit_video' =>	array('title'=>'����Ӱ������ͳ��', 'name'=>'����', 'file'=>APPDIR.'/data/~counter_visit_video.dat', 'desc'=>'����ͳ�Ƶ���������Ƶҳ�棨������ҳ�Ͳ���ҳ�����õĶ����ÿ���'),
		'visit_pan' =>		array('title'=>'�������ط���ͳ��', 'name'=>'����', 'file'=>APPDIR.'/data/~counter_visit_pan.dat', 'desc'=>'����ͳ�Ƶ��ǰٶ�������������ҳ�棨������ҳ������ҳ�����õĶ����ÿ���'),
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
    //�����б�
    $fixed = '';
    foreach($address as $k=>$v){
        $fixed .= "{$k}|{$v}\n";
    }
    //����������վ�б�
    if(isset($_POST['custom'])){
        $custom = '';
        $error = '';
        $lines = explode("\n", $_POST['custom']);
        foreach($lines as $k=>$line){
            $line = trim($line);
            if($line){
                $record = explode('|', $line);
                if(count($record)!=3 || ($id=intval($record[0]))<=0){
                    $error .= "�� <b>".($k+1)."</b> �и�ʽ����{$line}<br/>";
                }elseif(isset($address[$id])){
                    $error .= "�� <b>".($k+1)."</b> �еı�� (<b>{$record[0]}</b>) ���������б����ˣ�<br/>";
                }elseif(strpos($record[1],'<')!==false || strpos($record[1],'>')!==false){
                    $error .= "�� <b>".($k+1)."</b> �б�������{$line}<br/>";
                }elseif(!preg_match('#^\*?https?://[\w\-\.:]{6,}(/|$)#', $record[2])){
                    $error .= "�� <b>".($k+1)."</b> ����ַ����{$line}<br/>";
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
            $message = '<div class="row success">�ѳɹ�����</div>';
        }
    }else{
        //�����Զ����б�
        $custom = file_exists(DATADIR.'/address.dat') ? file_get_contents(DATADIR.'/address.dat') : '';
    }
	//����
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
    <strong>�����б�</strong>
    <pre>{$fixed}</pre>
</div>
<div class="row">
    <strong>�Զ����б�</strong>
    <textarea rows="10" name="custom">{$custom}</textarea>
    <div class="tip">���������б�ÿ��һ������ʽΪ�� ���ֱ��|��ҳ����|������ַ<br>�����ַ����Ҫ���ܣ�������ַǰ������Ǻţ����� *https://git.io/fgp</div>
</div>
<div class="row">
    <button type="submit">��������</button>
</div>
</form>
{$message}
</body></html>
EOH;
}