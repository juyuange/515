<?php
require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/db.inc.php');
require(APPDIR.'/include/func.inc.php');
header('Content-Type: text/html; charset='.APP_CHARSET);

// STRIP SLASHES FROM GPC IF NECESSARY
strip_gpc_slashes();

//����
//================================================================================================================
$dbfile=DATADIR.'/~guestbook.db';
$db=new Db('sqlite', $dbfile);
if(!$db->connected()){
	exit($db->error());
}

//�������ݿ�
//================================================================================================================
if(filesize($dbfile)==0){
	$sql = <<<END
		CREATE TABLE "post" (
			"id" INTEGER PRIMARY KEY,
			"content" TEXT NOT NULL,
			"contact" TEXT NULL,
			"hash" VARCHAR(32) NOT NULL UNIQUE,
			"spam" INTEGER NOT NULL,
			"addtime" INTEGER NOT NULL
		);
END;
	$db->query($sql);
}

//����POST����
if(!empty($_POST)){
	if(isset($_POST['fk_charset'])){
		$fk_charset=$_POST['fk_charset'];
		unset($_POST['fk_charset']);
	}
	if(!empty($fk_charset)){
			foreach($_POST as $k=>$v){
				if(str_decrypt_form($k,$v,$fk_charset)){
					unset($_POST[$k]);
					$_POST[substr($k,3)]=$v;
				}
				unset($v);
			}
	}
}

//�ύ
//================================================================================================================
if(isset($_POST['content'])){
	/**
	 * ������ݺϷ��ԣ�����������д��������ʾ����ִֹ��
	 */
	function checkcontent($content) {
		if(empty($GLOBALS['config_noword'])) return;
		static $noword=null;
		if($noword===null){
			$arr = explode('|',$GLOBALS['config_noword']);
			foreach($arr as $k=>$v){
				if(empty($v)){
					unset($arr[$k]);
				}else{
					$arr[$k]=preg_quote($v);
				}
			}
			$noword='/('.implode('|',$arr).')/';
		}
		if(preg_match($noword, $content)){
			exit('���������<a href="javascript:history.back();">����</a>');
		}
	}

	function add(){
		global $db;
		$mbEnabled = function_exists('mb_strlen');

		$content=isset($_POST['content'])?strip_tags($_POST['content']):'';
		if(!$content){
			return ('No content.');
		}
		$len=$mbEnabled ? mb_strlen($content,APP_CHARSET) : strlen($content);
		if($len>500){
			return('������������̫�࣬���500���ַ�');
		}
		checkcontent($content);
		$content=$db->escapeString($content);
		$content='<domain>'.(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME']).'</domain>'.$content;

		$contact=isset($_POST['contact'])?strip_tags($_POST['contact']):'';
		$len=$mbEnabled ? mb_strlen($contact,APP_CHARSET) : strlen($contact);
		if($len>100){
			return('��ϵ��ʽ����̫�࣬���100���ַ�');
		}
		checkcontent($contact);
		$contact=$db->escapeString($contact);

		$hash=md5($_SERVER["REMOTE_ADDR"].'_'.date('YmdH'));
		$query = $db->query("SELECT * FROM post WHERE hash='{$hash}'");
		if($query && ($row=$db->fetchAssoc($query))!==false){
			$spam=$row['spam'];
			if($spam>=3){
				return('spam');
			}else{
				$id=$row['id'];
				$oldContent=$row['content'];
				$contentChanged=strpos($oldContent,$content)===false;
				$oldContact=$row['contact'];
				$contactChanged=$oldContact!=$contact && strpos($oldContact,$contact)===false;
				$sql='UPDATE post SET ';
				if(!$contentChanged && !$contactChanged){
					return('ok');
				}else{
					if($contentChanged){
						if($oldContent && $content) $content="\n\n".$content;
						$sql .= 'content=' . $db->concat('content', $content) . ', ';
					}
					if($contactChanged){
						if($oldContact && $contact) $contact=' '.$contact;
						$sql .= 'contact=' . $db->concat('contact', $contact) . ', ';
					}
				}
				$sql .= 'spam=spam+1 WHERE id='. $id;
				$result = $db->query($sql);
				return $result ? 'ok' : $db->error();
			}
		}else{
			$time=time();
			$result = $db->query("INSERT INTO post(content,contact,hash,spam,addtime) VALUES('{$content}','{$contact}','{$hash}',1,{$time})");
			return $result ? 'ok' : $db->error();
		}
	}

	$ret = add();
	if($ret=='ok'){
		echo "�ύ�ɹ���<script>alert('�ύ�ɹ���');</script>";
	}else{
		$ret = preg_replace('#[\'"\r\n]+#', ' ', $ret);
		echo "ʧ�ܣ�{$ret}<script>alert('ʧ�ܣ�{$ret}');</script>";
	}
	exit;
}

//ɾ��
//================================================================================================================
else if(isset($_GET['act']) && $_GET['act']=='del' && is_numeric($_GET['id'])){
	//������֤
	check_authentication($config['password']);

	$result = $db->query("DELETE FROM post WHERE id={$_GET['id']}");
	if($result){
		echo '<script>parent.afterDel('.$_GET['id'].');</script>';
	}else{
		echo '<script>alert("'.$db->error().'");</script>';
	}
	exit;
}

//���
//================================================================================================================
else if(!isset($_GET['act']) || $_GET['act']=='add'){
	echo '
<!doctype html>
<html><head><meta http-equiv="Content-Type" content="text/html;charset=gb2312">
<title>����</title>
<meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<meta name="format-detection" content="telephone=no"/>
<!-[if AppleWebKit]>
<style type="text/css">
@media screen and (max-width:580px) {
  textarea{width:99%;}
}
@media screen and (max-height:250px) and (orientation:landscape) {
	h1{display:none;}
}
</style>
<![endif]->
</head>
<body>
<h1>����</h1>
<form method="post" action="">
<div>���ݣ�������������κ����ݣ�</div>
<textarea name="content" rows="4" cols="60"></textarea>
<div>��ϵ��ʽ��ѡ�</div>
<textarea name="contact" rows="2" cols="60"></textarea>
<div><input type="submit" value="�ύ����" style="padding:5px 10px; margin-top:5px;" /></div>
</form>
</body>
</html>
	';
}

//����
//================================================================================================================
else if(isset($_GET['act']) && $_GET['act']=='admin'){
	//������֤
	check_authentication($config['password']);
?>
<!doctype html>
<html><head><meta http-equiv="Content-Type" content="text/html;charset=gb2312">
<title>���Թ���</title>
<style type="text/css">
	#messageTable{table-layout:fixed; *table-layout:auto; empty-cells:show; border-collapse:collapse; width:90%;}
	#messageTable td{font-size: 12px;padding: 5px;}
	#pages{background-color:#EEE;color:#000;text-align:left;padding-left:5px 10px;}
	#pages a, #pages span {display:block; float:left; margin-left:2px; width:16px; height:16px; line-height:16px;text-align:center; border:1px solid #999; background-color:#FFF;}
	#pages a{text-decoration:none; border-color:blue;}
	#pages span{color:#999;}
	.tr2_ta{width:100%; border:0;}
</style>
<meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<meta name="format-detection" content="telephone=no"/>
<script type="text/javascript">
function del(id){
	if(confirm('ȷʵҪɾ����')){
		document.getElementById("hiddenFrame").src="?act=del&id="+id;
	}
}
function afterDel(id){
	var tbl=document.getElementById('messageTable');
  tbl.deleteRow(document.getElementById('tr1_'+id).rowIndex);
  tbl.deleteRow(document.getElementById('tr2_'+id).rowIndex);
	alert('ok');
}
</script>
</head>
<body>
<h1>���Թ���</h1>
<iframe id="hiddenFrame" src="about:blank" width="0" height="0" style="display:none;"></iframe>
<table id="messageTable" border="1">
<?php
	$recordCount=$db->tableRowCount('post');
	$pageSize=20;
	$page=isset($_GET['page'])?intval($_GET['page']):1;
	$offset=($page-1)*$pageSize;
	$query = $db->query("SELECT id,content,contact,addtime FROM post ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}");
	while(false!==($row=$db->fetchAssoc($query))){
		$passed = time() -	$row['addtime'];
		if($passed<60){ //1����֮��
			$time='�ո�';
		}else if($passed<6000){ //100����֮��
			$time = floor($passed / 60).'����ǰ';
		}else if($passed<3600*48){ //48Сʱ֮��
			$time = floor($passed / 3600).'Сʱǰ';
		}else if($passed<3600*24*31){ //1��֮��
			$time = floor($passed / 3600 / 24).'��ǰ';
		}else{ //����1����
			$time = date('m-d H:i:s', $row['addtime']);
		}

		$content = trim($row['content']);
		$domain = '';
		if(preg_match('#^<domain>(.+?)</domain>#',$content,$match)){
			$domain = $match[1];
			$content = substr($content,strlen($match[0]));
		}
		$content = str_ireplace('<textarea', '&lt;textarea', $content);
		$content = str_ireplace('</textarea', '&lt;/textarea', $content);
		echo "<tr id='tr1_{$row['id']}' bgcolor='#EEEEEE' height='20'><td width='30' align='center'>{$row['id']}</td><td width='100' align='center'>{$time}</td><td width='150'>{$domain}</td><td>{$row['contact']}</td></tr>";
		echo "<tr id='tr2_{$row['id']}'><td colspan='4'><textarea class='tr2_ta' id='tr2_ta{$row['id']}'>{$content}</textarea><br/><button onclick='del({$row['id']});'>ɾ��</button><br/></td></tr>";
		echo "<script>var e=document.getElementById('tr2_ta{$row['id']}'); if(e.scrollHeight>0) e.style.height=e.scrollHeight+'px'; else e.rows=5;</script>";
	}

	//��ҳ����
	$pageHtml='';
	$pageCount = ceil($recordCount / $pageSize);
	if($pageCount>1){
		for($i=1; $i<=$pageCount; $i++){
			if($i==$page){
				$pageHtml.='<span>'.$i.'</span>';
			}else{
				$pageHtml.='<a href="?act=admin&page='.$i.'">'.$i.'</a>';
			}
		}
		echo "<tr><td colspan='3' id='pages'>{$pageHtml}</td></tr>";
	}
?>
</table>
</body>
</html>
<?php
}
?>