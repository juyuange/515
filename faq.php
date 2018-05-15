<?php
require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/db.inc.php');
require(APPDIR.'/include/func.inc.php');
require(APPDIR.'/include/http.inc.php');
require(APPDIR.'/include/coding.inc.php');
header('Content-Type: text/html; charset='.APP_CHARSET);

forbid_spider();
init_config();
$types = array(0=>'�����Ҵ�', 1=>'������', 2=>'ͼƬ');
$config['enable_shorturl']=false;
$currentUrl=Url::getCurrentUrl();
$homepageUrl=Url::create($currentUrl->home.$currentUrl->path);
$urlCoding=new UrlCoding($homepageUrl);
$is_mobile = Http::isMobile();

/**
 * �����ļ��������ʽΪ��
 * array(
 * 	'last_id'=>���һ����ʹ�õ�id����,
 * 	'{id}'=>array('title'=>'����', 'answer'=>'�ش�����', ),
 * )
 *
 */
$faq_dbfile=DATADIR.'/faq.dat';
if(file_exists($faq_dbfile))
$list=file_exists($faq_dbfile) ? unserialize(file_get_contents($faq_dbfile)) : null;
if(empty($list)){
	$list=array('last_id'=>0);
}
$item=null;

$video_enabled = file_exists(APPDIR.'/video/index.php');

$is_admin=false;
$id=isset($_GET['id'])?intval($_GET['id']):null;
if(is_null($id)) $id=isset($_POST['id'])?$_POST['id']:null;
$type=isset($_GET['type'])?intval($_GET['type']):0;
if(isset($_GET['act']) && $_GET['act']=='admin'){
	//������֤
	$is_admin=check_authentication($config['password']);

	$formAction=isset($_POST['form_action']) ? $_POST['form_action'] : null;
	//����
	if($formAction=='save'){
		strip_gpc_slashes();
		$type=isset($_POST['type'])?intval($_POST['type']):0;
		$title=isset($_POST['title'])?htmlspecialchars(strip_tags($_POST['title']), ENT_QUOTES, 'GB2312'):null;
		$external=isset($_POST['external'])?htmlspecialchars(trim(strip_tags($_POST['external']))):null;
		$answer=isset($_POST['answer'])?get_safe_html($_POST['answer']):null;
		$list[$id?$id:++$list['last_id']]=array('type'=>$type, 'title'=>$title, 'external'=>$external, 'answer'=>$answer);
		file_put_contents_bak($faq_dbfile, serialize($list));
	}
	//ɾ��
	else if($formAction=='del'){
		if(isset($id) && isset($list[$id])){
			unset($list[$id]);
			file_put_contents_bak($faq_dbfile, serialize($list));
		}
		$id=null;
	}
	//����
	else if($formAction=='moveup'){
		if($id && isset($list[$id]) && isset($_POST['moveto'])){
			$moveto=intval($_POST['moveto']);
			if($moveto==0){
				//�Ƶ�����
				$item=$list[$id];
				unset($list[$id]);
				$list=array($id=>$item)+$list;
			}else if($moveto==-1){
				//����һλ
				$newList=array();
				$prevKey=null;
				foreach($list as $k=>$v){
					if($k!=$id && $k!='last_id'){
						$prevKey=$k;
					}
					if($k==$id && $prevKey){
						unset($newList[$prevKey]);
						$newList[$id]=$list[$id];
						$newList[$prevKey]=$list[$prevKey];
					}else{
						$newList[$k]=$v;
					}
				}
				$list=$newList;
			}
			file_put_contents_bak($faq_dbfile, serialize($list));
		}
		$id=null;
	}
}else{
	//���ʼ�¼
	if(!isset($_COOKIE['cookie_faq'])){
		record_counter('visit_faq');
		//д��cookie�������ظ�����
		setcookie('cookie_faq', 1, 0, '/');
	}
}

if($id && isset($list[$id])){
	$item=$list[$id];
	if(!empty($item)) $type=intval($item['type']);
}

//ת���ⲿ����
if(!$is_admin && $id && $item && !empty($item['external']) && is_external_url($item['external'])){
    if(strpos($item['external'], '://')){
        //���뵽��������
        $url = $urlCoding->encodeUrl($item['external'],null,null,true);
    }else{
        $url = $item['external'];
    }
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: {$url}");
    exit;
}

unset($list['last_id']);

if(!$is_admin){
    ob_start();
}
?>
<!doctype html>
<html><head><meta http-equiv="Content-Type" content="text/html;charset=gb2312">
<meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<meta name="format-detection" content="telephone=no">
<title><?php
if($is_admin){
	echo '���Ϲ���';
}else{
	echo ($item['title']?$item['title'].' - ':'') . '��������_���������ύ';
}
?></title>
<link type="text/css" rel="stylesheet" href="images/faq.css" />
<script type="text/javascript" src="images/faq.js"></script>
</head>
<body id="<?php if($is_admin) echo 'page_admin'; elseif($is_mobile) echo 'page_mobile';?>">
<div id="container">

<?php if(!$is_admin){ ?>
<div id="header"><img src="images/banner1.jpg" style="display:none;" onload="this.style.display='block';" border="0" alt="��������" /></div>
<?php } ?>

<div id="main">

<?php if($is_admin){
    $typeOptions='';
    foreach($types as $k=>$v){
        $typeOptions .= "<option value='{$k}'" .($type==$k?' selected':''). ">{$v}</option>";
    }
    $selectedFilters = isset($_COOKIE['filter'])?$_COOKIE['filter']:implode('',array_keys($types));
?>
	<!-- tinymce�༭���Ǵ� tinymce.cachefly.net/4.0/tinymce.min.js ��ȡ���� -->
	<script src="images/tinymce/tinymce.min.js"></script>
	<script type="text/javascript">
	tinymce.init({
		menubar: false,
		language_url: "images/tinymce/tinymce_zh_CN.js",
		plugins: "image,link,media,fullscreen,textcolor,code",
		toolbar: "undo redo | styleselect fontsizeselect | alignleft aligncenter alignright alignjustify | bold italic underline strikethrough removeformat | forecolor backcolor numlist bullist | hr link image media code | fullscreen",
	 	selector: "textarea.input",
	 	height: 400,
	 	setup: function(editor) {
	    	editor.on('change', function(e) {
	      	editChanged();
    	});
	  }
	});
	</script>

	<h1>���Ϲ���
	    <a href="?act=admin&new=1" class='graybtn' onclick="return ignoreChanged();">�������</a>
	    <a href="faq.php" target="_blank" class='graybtn'>����ǰ̨</a>
	</h1>

 	<?php if(!$id && isset($_GET['new'])){ ?>
 	<h1><a id="anchor_edit" name="edit"></a>�������</h1>
	<form action="?act=admin" method="post" class='editform' id="formNew" onsubmit="return checkForm('formNew');" style="margin-left:0;">
	<div class='line'>���ͣ�<br/><select name='type' class='input' onchange='editChanged();'><?php echo $typeOptions; ?></select></div>
 	<div class='line'>���⣺<br/><input type='text' id='formNew_title' name='title' value='' size='106' maxlength='200' class='input' onchange='editChanged();' /></div>
 	<div class='line'>�ⲿ���ӣ���ͨ��������ʴ����ӣ���ӵ����������<br/><input type='text' id='formNew_external' name='external' value='' size='106' maxlength='200' class='input' onchange='editChanged();' /></div>
 	<div class='line'>�������ݣ�����������ⲿ���ӣ��������ݽ�������ʾ����<br/><textarea id='formNew_answer' name='answer' rows='20' cols='93' class='input' style='vertical-align:top;' onchange='editChanged();' /></textarea></div>
 	<div class='line'>
	 	<input type='hidden' name='id' value='0' />
	 	<input type='hidden' name='form_action' value='save' />
	 	<input type='submit' value='����' class='button' /> &nbsp;
	 	<input type='reset' value='ȡ��' class='button' onclick="if(ignoreChanged()) location='faq.php?act=admin'" />
	 	&nbsp;����/video/���Ӱ��������������Ƶ��ť��Ȼ���ڵ�ַ�����벥��ҳ��ַ(��ò���������)������ߴ�Ϊ600*400
 	</div>
 	</form>
 	<?php } ?>

	<ul id="faqList" class="underline">
	    <div id='filter'>ֻ��ʾѡ������
	        <input type='checkbox' value='0' id='filter_0'>�ʴ�
	        <input type='checkbox' value='1' id='filter_1'>������
	        <input type='checkbox' value='2' id='filter_2'>ͼƬ
	        &nbsp;<button type="button" onclick="changeFilter()">ȷ��</button>
	    </div>
	    <script>setFilter();</script>

		<?php
		foreach($list as $k=>$v){
		    if(strpos($selectedFilters,isset($v['type'])?strval($v['type']):'0')===false){
		        continue;
		    }elseif($k==$id){
			    if(!isset($item['external'])) $item['external']='';
			    $item['type']=intval($item['type']);
			    $typeOptions='';
			    foreach($types as $k2=>$v2){
			        $typeOptions .= "<option value='{$k2}'" .($item['type']==$k2?' selected':''). ">{$v2}</option>";
			    }
				echo "<a id='anchor_edit' name='edit'></a>
				    <li>�� {$k}. [<a href='?id={$k}' target='_blank'>Ԥ��</a> �༭ <a href='javascript:' onclick='del({$k});'>ɾ��</a> <a href='javascript:' onclick='moveup({$k},-1);'>����</a> <a href='javascript:' onclick='moveup({$k},0);' title='���Ƶ���һ��'>����</a>] {$v['title']}</li>
				    <div>
 						<form action='?act=admin' method='post' class='editform' id='formSave' onsubmit=\"return checkForm('formSave');\">
 						    <div class='line'>���ͣ�<select name='type' class='input' onchange='editChanged();'>{$typeOptions}</select></div>
	 						<div class='line'>���⣺<br/><input type='text' id='formSave_title' name='title' value='{$item['title']}' size='106' maxlength='200' class='input' onchange='editChanged();' /></div>
	 						<div class='line'>�ⲿ���ӣ���ͨ��������ʴ����ӣ���ӵ����������<br/><input type='text' id='formSave_external' name='external' value='{$item['external']}' size='106' maxlength='200' class='input' onchange='editChanged();' /></div>
	 						<div class='line'>�������ݣ�����������ⲿ���ӣ��������ݽ�������ʾ����<br/><textarea id='formSave_answer' name='answer' rows='10' cols='93' class='input' style='vertical-align:top;' onchange='editChanged();' />{$item['answer']}</textarea></div>
					 		<div class='line'>
						 		<input type='hidden' name='id' value='{$id}' />
						 		<input type='hidden' name='form_action' value='save' />
					 			<input type='submit' value='�����޸�' class='button' /> &nbsp;
					 			<input type='reset' value='ȡ��' class='button' onclick=\"if(ignoreChanged()) location='faq.php?act=admin'\" />
					 			&nbsp;����/video/���Ӱ��������������Ƶ��ť��Ȼ���ڵ�ַ�����벥��ҳ��ַ(��ò���������)������ߴ�Ϊ600*400
					 		</div>
 						</form>
					</div>
					<script>location.hash='#edit';</script>";
			}else{
			    $imgHtml = empty($v['external']) ? '' : "<img src='images/extlink.png'> ";
				echo "<li>�� {$k}. [<a href='?id={$k}' target='_blank'>Ԥ��</a> <a href='?act=admin&id={$k}' onclick='return ignoreChanged();'>�༭</a> <a href='javascript:' onclick='del({$k});'>ɾ��</a> <a href='javascript:' onclick='moveup({$k},-1);'>����</a> <a href='javascript:' onclick='moveup({$k},0);' title='���Ƶ���һ��'>����</a>] {$imgHtml}{$v['title']}</li>";
			}
		}
		?>
	</ul>

	<form action="?act=admin" method="post" id="formOther">
 	<input type='hidden' name='id' id="formOther_id" value='' />
 	<input type='hidden' name='moveto' id="formOther_moveto" value='' />
 	<input type='hidden' name='form_action' id="formOther_form_action" value='' />
 	</form>

<?php }else{

	if($id && $item){
		$answer=$item['answer'];
		if(preg_match_all('#<video\s.+?</video>#is', $answer, $matches, PREG_SET_ORDER)){
			foreach($matches as $k => $v){
				$width=0; $height=0; $style=null; $playUrl=null;
				$v=$v[0];
				if(preg_match('#width="(\d+)"#i', $v, $match)) $width=intval($match[1]);
				if(preg_match('#height="(\d+)"#i', $v, $match)) $height=intval($match[1]);
				if(preg_match('#\sstyle="(.*?)"#i', $v, $match)) $style=trim($match[1]);
				if(preg_match('#<source src="(.+?)"#i', $v, $match)) $playUrl=trim($match[1]);
				if($playUrl && strpos($playUrl,'video/play/')!==false){
					if($video_enabled){
						if(preg_match('#video/play/(\d+)\.html$#',$playUrl)){
							//�°沥��ҳurl
							$playUrl=preg_replace('#video/play/(\d+)\.html$#', 'video/miniplay/$1-'.$width.'-'.$height.'.html', $playUrl);
						}else{
							//�ɰ沥��ҳurl
							$playUrl=str_replace('/play/', "/miniplay_{$width}_{$height}/", $playUrl);
						}
						if($width<=0) $width=600; $width+=20;
						if($height<=0) $height=400; $height+=40;
						if($is_mobile) $width='100%';
						$new="<iframe src=\"{$playUrl}\" style=\"width:{$width}px;margin:0 auto; {$style}\" frameborder='0' scrolling='no' id='frm_video_{$k}' name='frm_video_{$k}' onload=\"resizeIframe('frm_video_{$k}');\"></iframe>";
						$answer=str_replace($v, $new, $answer);
					}else{
						$answer=str_replace($v, '', $answer);
					}
				}
			}
		}
	?>
		<div class="bar title"><?php echo $item['title']; ?></div>
		<div class='answer'><?php echo $answer; ?></div>
  <?php } ?>

	<div id="scroll_images">
		<div id="scroll_images_container">
			<div id="scroll_images_container_1">
				<a href="http://www.epochtimes.com/gb/nf5657.htm" title="��������鿴�йء�һ���й������ˡ��ĸ�������" target="_blank"><img src='images/scroll.jpg' alt='һ���й�������' /></a>
		    </div>
		    <div id="scroll_images_container_2"></div>
		    <div style="clear:both;"></div>
		</div>
	</div>
	<script type="text/javascript">StartRoll('scroll_images');</script>

	<div id="tab">
        <?php
        foreach($types as $k=>$v){
            if($type==$k){
                echo "<a href='javascript:' id='tab_1' rel='1' class='active' onclick='changeTab(1);'>{$v}</a>";
            }else{
                echo "<a href='faq.php?type={$k}'>{$v}</a>";
            }
        }
        ?>

		<?php if($video_enabled){ ?>
	    <a href="/video/" target="_blank" noproxy>����Ӱ��</a>
	    <?php }else{ ?>
	    <a href="http://www.ntdtv.com/xtr/gb/prog57.html/%E4%B9%9D%E8%AF%84%E5%85%B1%E4%BA%A7%E5%85%9A.html" target="_blank">������Ƶ</a>
		<?php } ?>

		<a href="javascript:" id="tab_2" rel="2" onclick="changeTab(2);">��Ҫ����</a>

		<?php if(file_exists(APPDIR.'/mobile/tui.php')){ ?>
	    <a href="#td" id="btnTui">��Ҫ����</a>
	    <?php }else{ ?>
	    <a href="http://tuidang.epochtimes.com/" target="_blank">��Ҫ����</a>
		<?php } ?>

	    <span></span>
	</div>

	<div id="tab_content_1" class="tab_content" style="display:block;">
		<div class="bar">�˽�������ϣ�������ݼ��ŵ�������</div>
		<ul id="faqList2">
			<?php
			foreach($list as $k=>$v){
			    if($type!=$v['type']){
			        continue;
			    }elseif($k==$id){
					echo "<li><i>��</i> {$v['title']}</li>\n";
				}else{
					echo "<li><i>��</i> <a href='".$currentUrl->script."?id={$k}' title='{$v['title']}' target='_blank' noproxy>{$v['title']}</a></li>\n";
				}
			}
			?>
			<div style="clear:both;"></div>
		</ul>
	</div>

	<div id="tab_content_2" class="tab_content">
		<div class="bar">�������������׵ģ�û��ϵ�ģ���ӭ�������ʡ��������������ʲô��Ҳ�����ڴ��ύ��</div>
		<form id="form_ask" method="post" action="/guestbook.php" target="btmn_iframe" onsubmit="btmn_setvisible('');" accept-charset="GBK" onsubmit="document.charset='GBK';" noproxy>
		  	<div>�������ݣ����500�֣�</div>
		    <textarea rows="5" cols="50" maxlength="500" onchange="encryptI(this,'fk_content_ly');"></textarea><input type="hidden" name="fk_content" id="fk_content_ly" value=""/>
		    <div>��ϵ��ʽ��ѡ����100�֣�</div>
		    <textarea rows="2" cols="50" maxlength="100" onchange="encryptI(this,'fk_contact_ly');"></textarea><input type="hidden" name="fk_contact" id="fk_contact_ly" value=""/>
		    <div>
		    	<input type="hidden" name="fk_charset" value="GBK"/>
		    	<input type="submit" value="��Ҫ����" style="padding:5px 10px; margin-top:10px;" />
		    	<span id="btmn_mycontact"></span>
		    </div>
		</form>
	</div>

	<?php if(!$is_mobile) echo get_ad_code(728, 90, null, 'width:728px; height:90px; margin:0 auto;'); ?>

	<?php if(file_exists(APPDIR.'/mobile/3t/add.php')){ ?>
		<a name="td"></a>
		<iframe src="mobile/3t/add.php?inframe=1" style="width:100% !important;margin-bottom:10px;" frameborder='0' scrolling='no' id="frm3t" name="frm3t" onload="resizeIframe('frm3t');"></iframe>
	<?php } ?>
<?php } ?>

	<div style="clear:both;"></div>
</div>

<?php if(!$is_admin){ ?>
<div id="footer"><img src="images/bg_footer.gif" style="display:none;" onload="this.style.display='block';" border="0" alt="" /></div>
<?php if($bottom_navigation['enable']){ ?>
<script type="text/javascript" src="/?<?php echo $config['built_in_name'] . '=' . encrypt_builtin('nav'); ?>" charset="GBK" noproxy></script>
<?php } ?>
<?php } ?>

</div>
<iframe width="0" height="0" style="display:none" name="btmn_iframe" id="btmn_iframe"></iframe>
</body>
</html>

<?php
if(!$is_admin){
	//����
	$data=ob_get_contents();
	ob_clean();
	$htmlCoding=new HtmlCoding($homepageUrl, null, $urlCoding);
	$data=$htmlCoding->proxifyHtml($data);
	$data=$htmlCoding->replaceVar($data);
	$data=$htmlCoding->compact($data, 'html');
	$data=$htmlCoding->encryptHtml($data, APP_CHARSET);
	echo $data;
}

?>
