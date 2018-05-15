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
$types = array(0=>'你问我答', 1=>'电子书', 2=>'图片');
$config['enable_shorturl']=false;
$currentUrl=Url::getCurrentUrl();
$homepageUrl=Url::create($currentUrl->home.$currentUrl->path);
$urlCoding=new UrlCoding($homepageUrl);
$is_mobile = Http::isMobile();

/**
 * 数据文件，保存格式为：
 * array(
 * 	'last_id'=>最后一个被使用的id数字,
 * 	'{id}'=>array('title'=>'标题', 'answer'=>'回答内容', ),
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
	//密码验证
	$is_admin=check_authentication($config['password']);

	$formAction=isset($_POST['form_action']) ? $_POST['form_action'] : null;
	//保存
	if($formAction=='save'){
		strip_gpc_slashes();
		$type=isset($_POST['type'])?intval($_POST['type']):0;
		$title=isset($_POST['title'])?htmlspecialchars(strip_tags($_POST['title']), ENT_QUOTES, 'GB2312'):null;
		$external=isset($_POST['external'])?htmlspecialchars(trim(strip_tags($_POST['external']))):null;
		$answer=isset($_POST['answer'])?get_safe_html($_POST['answer']):null;
		$list[$id?$id:++$list['last_id']]=array('type'=>$type, 'title'=>$title, 'external'=>$external, 'answer'=>$answer);
		file_put_contents_bak($faq_dbfile, serialize($list));
	}
	//删除
	else if($formAction=='del'){
		if(isset($id) && isset($list[$id])){
			unset($list[$id]);
			file_put_contents_bak($faq_dbfile, serialize($list));
		}
		$id=null;
	}
	//上移
	else if($formAction=='moveup'){
		if($id && isset($list[$id]) && isset($_POST['moveto'])){
			$moveto=intval($_POST['moveto']);
			if($moveto==0){
				//移到顶部
				$item=$list[$id];
				unset($list[$id]);
				$list=array($id=>$item)+$list;
			}else if($moveto==-1){
				//上移一位
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
	//访问记录
	if(!isset($_COOKIE['cookie_faq'])){
		record_counter('visit_faq');
		//写入cookie，避免重复计数
		setcookie('cookie_faq', 1, 0, '/');
	}
}

if($id && isset($list[$id])){
	$item=$list[$id];
	if(!empty($item)) $type=intval($item['type']);
}

//转向到外部链接
if(!$is_admin && $id && $item && !empty($item['external']) && is_external_url($item['external'])){
    if(strpos($item['external'], '://')){
        //加入到白名单里
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
	echo '资料管理';
}else{
	echo ($item['title']?$item['title'].' - ':'') . '真相资料_在线三退提交';
}
?></title>
<link type="text/css" rel="stylesheet" href="images/faq.css" />
<script type="text/javascript" src="images/faq.js"></script>
</head>
<body id="<?php if($is_admin) echo 'page_admin'; elseif($is_mobile) echo 'page_mobile';?>">
<div id="container">

<?php if(!$is_admin){ ?>
<div id="header"><img src="images/banner1.jpg" style="display:none;" onload="this.style.display='block';" border="0" alt="真相资料" /></div>
<?php } ?>

<div id="main">

<?php if($is_admin){
    $typeOptions='';
    foreach($types as $k=>$v){
        $typeOptions .= "<option value='{$k}'" .($type==$k?' selected':''). ">{$v}</option>";
    }
    $selectedFilters = isset($_COOKIE['filter'])?$_COOKIE['filter']:implode('',array_keys($types));
?>
	<!-- tinymce编辑器是从 tinymce.cachefly.net/4.0/tinymce.min.js 提取而来 -->
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

	<h1>资料管理
	    <a href="?act=admin&new=1" class='graybtn' onclick="return ignoreChanged();">添加资料</a>
	    <a href="faq.php" target="_blank" class='graybtn'>访问前台</a>
	</h1>

 	<?php if(!$id && isset($_GET['new'])){ ?>
 	<h1><a id="anchor_edit" name="edit"></a>添加资料</h1>
	<form action="?act=admin" method="post" class='editform' id="formNew" onsubmit="return checkForm('formNew');" style="margin-left:0;">
	<div class='line'>类型：<br/><select name='type' class='input' onchange='editChanged();'><?php echo $typeOptions; ?></select></div>
 	<div class='line'>标题：<br/><input type='text' id='formNew_title' name='title' value='' size='106' maxlength='200' class='input' onchange='editChanged();' /></div>
 	<div class='line'>外部链接（将通过代理访问此链接，需加到白名单里）：<br/><input type='text' id='formNew_external' name='external' value='' size='106' maxlength='200' class='input' onchange='editChanged();' /></div>
 	<div class='line'>正文内容（如果设置了外部链接，正文内容将不再显示）：<br/><textarea id='formNew_answer' name='answer' rows='20' cols='93' class='input' style='vertical-align:top;' onchange='editChanged();' /></textarea></div>
 	<div class='line'>
	 	<input type='hidden' name='id' value='0' />
	 	<input type='hidden' name='form_action' value='save' />
	 	<input type='submit' value='保存' class='button' /> &nbsp;
	 	<input type='reset' value='取消' class='button' onclick="if(ignoreChanged()) location='faq.php?act=admin'" />
	 	&nbsp;插入/video/里的影音，请点击插入视频按钮，然后在地址里输入播放页网址(最好不包含域名)，建议尺寸为600*400
 	</div>
 	</form>
 	<?php } ?>

	<ul id="faqList" class="underline">
	    <div id='filter'>只显示选定类型
	        <input type='checkbox' value='0' id='filter_0'>问答
	        <input type='checkbox' value='1' id='filter_1'>电子书
	        <input type='checkbox' value='2' id='filter_2'>图片
	        &nbsp;<button type="button" onclick="changeFilter()">确定</button>
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
				    <li>● {$k}. [<a href='?id={$k}' target='_blank'>预览</a> 编辑 <a href='javascript:' onclick='del({$k});'>删除</a> <a href='javascript:' onclick='moveup({$k},-1);'>上移</a> <a href='javascript:' onclick='moveup({$k},0);' title='上移到第一个'>顶部</a>] {$v['title']}</li>
				    <div>
 						<form action='?act=admin' method='post' class='editform' id='formSave' onsubmit=\"return checkForm('formSave');\">
 						    <div class='line'>类型：<select name='type' class='input' onchange='editChanged();'>{$typeOptions}</select></div>
	 						<div class='line'>标题：<br/><input type='text' id='formSave_title' name='title' value='{$item['title']}' size='106' maxlength='200' class='input' onchange='editChanged();' /></div>
	 						<div class='line'>外部链接（将通过代理访问此链接，需加到白名单里）：<br/><input type='text' id='formSave_external' name='external' value='{$item['external']}' size='106' maxlength='200' class='input' onchange='editChanged();' /></div>
	 						<div class='line'>正文内容（如果设置了外部链接，正文内容将不再显示）：<br/><textarea id='formSave_answer' name='answer' rows='10' cols='93' class='input' style='vertical-align:top;' onchange='editChanged();' />{$item['answer']}</textarea></div>
					 		<div class='line'>
						 		<input type='hidden' name='id' value='{$id}' />
						 		<input type='hidden' name='form_action' value='save' />
					 			<input type='submit' value='保存修改' class='button' /> &nbsp;
					 			<input type='reset' value='取消' class='button' onclick=\"if(ignoreChanged()) location='faq.php?act=admin'\" />
					 			&nbsp;插入/video/里的影音，请点击插入视频按钮，然后在地址里输入播放页网址(最好不包含域名)，建议尺寸为600*400
					 		</div>
 						</form>
					</div>
					<script>location.hash='#edit';</script>";
			}else{
			    $imgHtml = empty($v['external']) ? '' : "<img src='images/extlink.png'> ";
				echo "<li>● {$k}. [<a href='?id={$k}' target='_blank'>预览</a> <a href='?act=admin&id={$k}' onclick='return ignoreChanged();'>编辑</a> <a href='javascript:' onclick='del({$k});'>删除</a> <a href='javascript:' onclick='moveup({$k},-1);'>上移</a> <a href='javascript:' onclick='moveup({$k},0);' title='上移到第一个'>顶部</a>] {$imgHtml}{$v['title']}</li>";
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
							//新版播放页url
							$playUrl=preg_replace('#video/play/(\d+)\.html$#', 'video/miniplay/$1-'.$width.'-'.$height.'.html', $playUrl);
						}else{
							//旧版播放页url
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
				<a href="http://www.epochtimes.com/gb/nf5657.htm" title="请点击这里，查看有关“一亿中国人三退”的更多内容" target="_blank"><img src='images/scroll.jpg' alt='一亿中国人三退' /></a>
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
	    <a href="/video/" target="_blank" noproxy>真相影音</a>
	    <?php }else{ ?>
	    <a href="http://www.ntdtv.com/xtr/gb/prog57.html/%E4%B9%9D%E8%AF%84%E5%85%B1%E4%BA%A7%E5%85%9A.html" target="_blank">精彩视频</a>
		<?php } ?>

		<a href="javascript:" id="tab_2" rel="2" onclick="changeTab(2);">我要提问</a>

		<?php if(file_exists(APPDIR.'/mobile/tui.php')){ ?>
	    <a href="#td" id="btnTui">我要三退</a>
	    <?php }else{ ?>
	    <a href="http://tuidang.epochtimes.com/" target="_blank">我要三退</a>
		<?php } ?>

	    <span></span>
	</div>

	<div id="tab_content_1" class="tab_content" style="display:block;">
		<div class="bar">了解真相是希望！稍纵即逝的良机！</div>
		<ul id="faqList2">
			<?php
			foreach($list as $k=>$v){
			    if($type!=$v['type']){
			        continue;
			    }elseif($k==$id){
					echo "<li><i>◆</i> {$v['title']}</li>\n";
				}else{
					echo "<li><i>◆</i> <a href='".$currentUrl->script."?id={$k}' title='{$v['title']}' target='_blank' noproxy>{$v['title']}</a></li>\n";
				}
			}
			?>
			<div style="clear:both;"></div>
		</ul>
	</div>

	<div id="tab_content_2" class="tab_content">
		<div class="bar">还有其他不明白的？没关系的，欢迎向我提问。如果有其他建议什么的也可以在此提交。</div>
		<form id="form_ask" method="post" action="/guestbook.php" target="btmn_iframe" onsubmit="btmn_setvisible('');" accept-charset="GBK" onsubmit="document.charset='GBK';" noproxy>
		  	<div>提问内容（最多500字）</div>
		    <textarea rows="5" cols="50" maxlength="500" onchange="encryptI(this,'fk_content_ly');"></textarea><input type="hidden" name="fk_content" id="fk_content_ly" value=""/>
		    <div>联系方式（选填，最多100字）</div>
		    <textarea rows="2" cols="50" maxlength="100" onchange="encryptI(this,'fk_contact_ly');"></textarea><input type="hidden" name="fk_contact" id="fk_contact_ly" value=""/>
		    <div>
		    	<input type="hidden" name="fk_charset" value="GBK"/>
		    	<input type="submit" value="我要提问" style="padding:5px 10px; margin-top:10px;" />
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
	//加密
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
