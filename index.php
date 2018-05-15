<?php

//updated 20180427

require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/func.inc.php');
require(APPDIR.'/include/http.inc.php');
require(APPDIR.'/include/coding.inc.php');
require(APPDIR.'/include/db.inc.php');

// ================================================================================================
// 初始化
// ================================================================================================

//获取网站根路径
$app_path = str_replace('/index.php', '/', ($_SERVER['SCRIPT_NAME']?$_SERVER['SCRIPT_NAME']:$_SERVER['PHP_SELF']));

//检查是否有必需的设置项
if(empty($config) || empty($address)){
	header('Location: '.$app_path.'install.php');
	exit;
}

//建立必需的目录
if(!is_dir(DATADIR)) mkdirs(DATADIR) or die('无法建立data目录，请检查权限！');
if(!is_dir(TEMPDIR)) mkdirs(TEMPDIR) or die('无法建立temp目录，请检查权限！');

//初始化设置
init_config();

//首页内容样式(显示为空白页或错误页)
if(!empty($config['homepage_style']) && empty($config['mirror_site']) && in_array($_SERVER['REQUEST_URI'],array('/','/?','/index.php','/index.php?'))){
	$style=$config['homepage_style'];
	$file=DATADIR."/error/{$style}.txt";
	$content=file_exists($file)?file_get_contents($file):'';
	if(!is_numeric($style)){
		echo $content;
		exit;
	}else{
		show_error($style, $content);
	}
}

//移除在微信里浏览时自动添加的一大堆后缀
if( preg_match('#(\?|&)nsukey=[\w\%\-]{50,}$#', $_SERVER['REQUEST_URI'], $match)){
	unset($_GET['nsukey']);
	$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'],0,-strlen($match[0]));
}

//删除请求网址里的危险字符
if(preg_match('#%0[0ad]#i', $_SERVER['REQUEST_URI'])){
	show_error(404);
}

//初始化其他几个变量
strip_gpc_slashes();
$currentUrl = Url::getCurrentUrl();
$urlCoding = new UrlCoding($currentUrl);
set_error_handler("myErrorHandler");
$error_messages = null;

if(!empty($config['encrypt_seed']) || function_exists('my_encodeUrl')){
	$s = '';
}else{
	$s = $currentUrl->home;
}
$config['cache_salt_for_supported'] = $s;

//镜像站点功能
if(!empty($config['mirror_site'])){
	require(APPDIR."/include/mirror.inc.php");
}

//载入自定义插件
if($config['plugin'] && file_exists(APPDIR."/plugin/{$config['plugin']}.php")){
	require(APPDIR."/plugin/{$config['plugin']}.php");
}

//连接数据库（开启短网址后需要）
$db=null;
if ($config['enable_shorturl']) {
	$dbfile = DATADIR.'/shorturl.db';
	if(!file_exists($dbfile) && file_exists(DATADIR.'/~data.db')){
		if(!rename(DATADIR.'/~data.db', $dbfile)){
			$dbfile = DATADIR.'/~data.db';
		}
	}
	$db = new Db('sqlite', $dbfile);
	if($db->connected() && filesize($dbfile)==0) {
		install_database($db);
	}
	if($db->error()){
		exit($db->error());
	}
}

// ================================================================================================
// 默认的一些页面动作
// ================================================================================================

//crossdomain.xml
if($_SERVER['REQUEST_URI']=='/crossdomain.xml'){
	header('Content-Type: application/xml');
	echo '<?xml version="1.0"?><cross-domain-policy><allow-access-from domain="*" /></cross-domain-policy>';
	exit;
}

//提取参数
$isIndexPhp = !isset($_GET['__nonematch__']);
$builtInAction = $builtIn = '';
if($isIndexPhp && !empty($_GET[$config['built_in_name']])){
	$builtIn = $_GET[$config['built_in_name']];
}elseif(!$isIndexPhp && strpos($_SERVER['REQUEST_URI'],"/{$config['built_in_name']}_")===0){
	$x = strlen("/{$config['built_in_name']}_");
	$builtIn = substr($_SERVER['REQUEST_URI'],$x);
}
if($builtIn){
    $builtIn = decrypt_builtin($builtIn);
	if(preg_match('#^js|nav|tj$#', $builtIn)){
		$builtInAction = $builtIn;
	}
	if(preg_match('#^_ytb(l|img)?_([\w\-\.]+?)(\.\w{2,4})?$#', $builtIn, $match)){
	    $_GET["_ytb{$match[1]}"] = $match[2];
	}
}

//js解密函数的网址参数和函数名
if($builtInAction=='js'){
	header('Content-Type: text/javascript');
	header('Cache-Control: public, max-age=86400');
	header('Expires: '.gmtDate("+1 day"));

	$cacheFile = TEMPDIR.'/~js'.APP_VER.'.~tmp';
	$jsSrcFile = APPDIR.'/images/enc.js';
	if(file_exists($cacheFile) && time()-filemtime($cacheFile)<86400 && filemtime($jsSrcFile)<filemtime($cacheFile)){
		//输出1天内的缓存
		$script = file_get_contents($cacheFile);
	}else{
		$script = file_get_contents($jsSrcFile);
		//加入一些随机变化因素
		$placeholder = '/*rand*/';
		for($i=1; $i<50; $i++){
			$pos = strpos($script, $placeholder);
			if($pos === false) break;
			if(mt_rand(0,1)==0){
				$randCode = 'var _'.rand_string(7,10,RANDSTR_BASE62,true).'='.mt_rand(1,9999).';';
			}else{
				$randCode = 'function _'.rand_string(7,10,RANDSTR_BASE62,true).'('.rand_string(1,5,RANDSTR_BASE62,true).'){return '.mt_rand(1,9999).'}';
			}
			$script = substr_replace($script, $randCode, $pos, strlen($placeholder));
		}
		//随机变化函数名和一些变量
		if(preg_match_all('#\bvar_\w+#', $script, $matches, PREG_SET_ORDER)){
			$pair = $search = $replace = array();
			foreach($matches as $match){
				$pair[$match[0]] = '';
			}
			$search = array_keys($pair);
			$count = count($search);
			$prefix = rand_string(1,1,RANDSTR_LU) . rand_string(1,1,RANDSTR_N,false);
			for($i=0; $i<$count; $i++){
				$replace[] = $prefix.'_'.base_convert_62($i, 10, 62);
			}
			shuffle($replace);
			$script = str_replace($search, $replace, $script);
		}
		//压缩
		require(APPDIR.'/include/jspacker.inc.php');
		$packer = new JavaScriptPacker($script, 62, true, false);
		$script = $packer->pack();
		//保存缓存
		file_put_contents($cacheFile, $script, LOCK_EX);
	}
	//替换函数名后输出
	echo str_replace('jsFuncName', HtmlCoding::getFuncName(), $script);
	exit;
}

//转向到手机页面
if(empty($_GET) && Http::isMobile() && file_exists(APPDIR.'/mobile/') && empty($_COOKIE['display_pc']) && empty($config['display_pc'])){
	header('Location: '.$app_path.'mobile/');
	exit;
}

//被禁止页面返回空白页
if(strpos($_SERVER['REQUEST_URI'], $app_path.'blank/')===0){
	header('Cache-Control: public, max-age=3600');
	header('Expires: '.gmtDate("+1 hour"));
	exit;
}

//不允许蜘蛛跳转访问
if($_SERVER['REQUEST_METHOD']=='HEAD' && Http::isSpider()=='jump'){
	//请求由“智能跳转网站程序”提交
	exit;
}

//底部导航条javascript的显示与控制
if($builtInAction=='nav') {
	if(!isset($_SERVER['HTTP_X_NAV_VISIBLE'])) show_navigation_js();
	exit;
}

//处理youtube伪静态
if(substr($_SERVER['REQUEST_URI'],0,5)=='/_ytb'){
	if(preg_match('#^/_ytbl/([\w\-\.]+)\.rss$#', $_SERVER['REQUEST_URI'], $match)){
		$_GET['_ytbl'] = $match[1];
	}elseif(preg_match('#^/_ytb/([\w\-\.]+)\.mp4$#', $_SERVER['REQUEST_URI'], $match)){
		$_GET['_ytb'] = $match[1];
	}
}
//请求youtube视频播放列表
if(!empty($_GET['_ytbl']) && preg_match('#^[\w\-\.]+$#', $_GET['_ytbl'])){
	$code = getYoutubePlaylist($_GET['_ytbl'], $currentUrl->home.$currentUrl->path);
	if($code) {
		header('Content-Type: application/rss+xml; charset=UTF-8');
		echo($code);
	}else{
		show_error(404);
	}
	exit;
}
//请求youtube视频地址
if(!empty($_GET['_ytb']) && preg_match('#^[\w\-\.]+$#', $_GET['_ytb'])){
	$videourl = getYoutubeVideoUrl($_GET['_ytb']);
	if(!$videourl){
		show_error(404);
	}elseif(substr($videourl,0,7)=='/blank/'){
		show_error(404);
	}else{
		header('Location: ' . $urlCoding->encodeUrl($videourl,'video',null,true));
	}
	exit;
}

//访问/?home时，如果改变了homepage_style，就需要转向到第一个网址
if($isIndexPhp && isset($_GET['home']) && empty($_GET['home']) && !empty($config['homepage_style'])){
	$keys=array_keys($address);
	$id=isset($address[0])?0:$keys[0];
	header("Location: /{$id}/");
	exit;
}

//记录统计信息
if($builtInAction=='tj'){
    $lastVisit = isset($_COOKIE[$config['cookie_counter']]) ? intval($_COOKIE[$config['cookie_counter']]) : 0;
    $passedSeconds = time()-$lastVisit;

    //无上次来访时间，认定为新访客，添加访问记录，并用cookie记录访问时间
    if(!$lastVisit && record_counter('visit')){
        setcookie($config['cookie_counter'], time(), time()+7200, '/');
    }

    //有上次来访时间，每隔30分钟更新一次上次访问时间，连续2小时没有更新则会导致此cookie失效，然后再访问就算是新的访客了
    if($lastVisit && $passedSeconds>1800){
        setcookie($config['cookie_counter'], time(), time()+7200, '/');
    }

    if($passedSeconds % 2 == 1){
        //单数秒，检查同步设置
        if(!empty($config['sync_server'])){
    		include(APPDIR.'/include/sync.inc.php');
    	}
    }else{
    	//双数秒，检查是否可以清除缓存
    	$subdir = rand_string(1, 1, RANDSTR_HEX, false);
    	if(CacheHttp::canClearOverdueCache($subdir)){
    	    CacheHttp::clearOverdueCache($subdir);
    	}
    }

	exit;
}

//返回是否为中国大陆用户
if($isIndexPhp && isset($_GET['check_cn']) && $_GET['check_cn']=='1'){
	header('Content-Type: text/javascript');
	echo 'var _u_cn_="'. (in_array(get_user_country(),array('CN','LOCAL'))?'Y':'N') .'";';
	exit;
}elseif($isIndexPhp && isset($_GET['cn'],$_GET['callback']) && $_GET['cn']=='' && preg_match('#^jsonp\d+$#',$_GET['callback'])){
	header('Content-Type: text/javascript');
	$x = in_array(get_user_country(),array('CN','LOCAL'))?'1':'0';
	echo "{$_GET['callback']}({$x});";
	exit;
}

//检查是否支持备用域名插件，并判断当前网址是不是manifest文件
if( DEBUGING==0 &&
	empty($_GET[$config['ctype_var_name']]) &&
	(isset($_GET['appcache']) || (!empty($config['spare_domains']) || !empty($config['spare_domains_json'])))
  ) {
	$ext=fileext($_SERVER['REQUEST_URI']);
	if(empty($ext) || strpos(" $image_file_exts $download_file_exts $media_file_exts "," $ext ")===false){
		require(APPDIR.'/include/spare.inc.php');
	}
}

// ================================================================================================
// 临时设置参数（仅对本次请求有效）
// ================================================================================================

//通过请求参数暂时在本页禁用cache，经常用于测试
$dontReadCache = isset($_GET['_no_cache_']);
if($dontReadCache){
	unset($_GET['_no_cache_']);
	$_SERVER['REQUEST_URI']=preg_replace('#[\?&]_no_cache_=\w*$#', '', $_SERVER['REQUEST_URI']);
}

// ================================================================================================
// 解析真实的远端url
// ================================================================================================

$ctype = isset($_GET[$config['ctype_var_name']]) && preg_match('#^[\w\-\.]+$#', $_GET[$config['ctype_var_name']]) ? $_GET[$config['ctype_var_name']] : '';
$isframe = $ctype=='frame';
$isImport = $ctype=='import';
$accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : 'text/html';
$isajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest';

//被block的链接
if ($isIndexPhp && isset($_GET[$config['block_var_name']])) {
	if(!isset($_SERVER['HTTP_ACCEPT']) || substr($_SERVER['HTTP_ACCEPT'],0,5)=='text/'){
		show_error(403);
	}else{
		$s = preg_replace('#[\s\,].+#', '', $_SERVER['HTTP_ACCEPT']);
		if($s && preg_match('#^\w+/\w+$#',$s)){
			header('Content-Type: ' . $s);
		}
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
}

//在响应前先判断是否可能是顶层的网页
if($ctype || strpos($accept,'text/html')!==0 || $isajax){
	$maybeTopPage = false;
}elseif(($ext=fileext($_SERVER['REQUEST_URI']))==''){
	$maybeTopPage = true;
}else{
	$contentType = get_content_type($ext);
	$maybeTopPage = $contentType && isset($supported_content_type[$contentType]) && $supported_content_type[$contentType]=='html';
}

//解析远端地址
if(!empty($_GET['_ytbimg']) && preg_match('#^[\w\-\.]+$#', $_GET['_ytbimg'])){
	//请求youtube缩略图
	$remoteUrl = Url::create("http://i.ytimg.com/vi/{$_GET['_ytbimg']}/0.jpg");
	$_GET[$config['ctype_var_name']] = $ctype = 'img';
}else{
	//解析远端地址
	$remoteUrl = $urlCoding->getRemoteUrl(null,true,true);
	if($remoteUrl===false && !empty($config['tui_url']) && strpos($config['tui_url'],"/{$currentUrl->site}/")===false &&
		!file_exists(APPDIR.'/tui/') && preg_match('#^/tui/(.*)#',$_SERVER['REQUEST_URI'],$match)){
		//重定向到站外的三退表单系统
		$remoteUrl = Url::create($config['tui_url'] . $match[1]);
	}elseif($remoteUrl===false){
		show_error(404);
	}elseif(!preg_match('#^[\w\-\.]+$#', $remoteUrl->host)){
		show_error(400);
	}
}

$urlCoding->remoteUrl = $remoteUrl;
$_SERVER['REMOTE_URL'] = $remoteUrl->url;
$redirect_original = !empty($config['redirect_original']) && preg_match("#{$config['redirect_original']}#", $remoteUrl->url);

//根据黑名单白名单检查是否被block (不阻止常见的在线资源文件)
$ext = fileext($remoteUrl->file);
if($urlCoding->isBlockDomain($remoteUrl->host)){
	show_error(403);
}elseif(($ext=='.js' || $accept=='*/*' || strpos($accept,'javascript')!==false) && $urlCoding->isBlockScript($remoteUrl->url)){
	show_error(403);
}elseif($isajax || substr($accept,0,9)!='text/html' || strpos(" .js .css .xml .jpg .png .gif .ico .swf .flv .mp3 .mp4 .m3u8 .ts ", " $ext ")!==false){
	//不是网页请求，继续
}elseif($urlCoding->isSafeDomain($remoteUrl->host)){
	//安全域名，继续
}elseif(!$urlCoding->isBlockedByWhiteDomain($remoteUrl->host)){
	//未被白名单阻止，继续
}else{
	//被白名单阻止
	show_error(403);
}

//禁止蜘蛛访问
if(!preg_match('#(feed|\.xml$|\.rss$)#', $remoteUrl->url)){ //避免把rss reader网站阻止
	forbid_spider();
}

//检查只允许中国大陆访问的url
if(!empty($config['only_allow_cn']) && preg_match('#'.$config['only_allow_cn'].'#', $remoteUrl->url) && !in_array(get_user_country(),array('CN','LOCAL'))){
	show_error(403);
}
//检查是否只允许中国大陆播放新唐人直播
if( strpos($remoteUrl->file,'.m3u8')!==false &&
    preg_match('#\.(ntdtv\.com|ntdtv\.com\.tw|ntdimg\.com)/.+?\.m3u8#', $remoteUrl->url) &&
    file_exists(DATADIR.'/ntd_onlycn.dat') &&
    !in_array(get_user_country(),array('CN','LOCAL'))){
    show_error(403);
}

//请求youtube嵌入视频页
if($remoteUrl->host=='www.youtube.com' && preg_match('#^/embed/([\w\-\.]+)(?:\?list=([\w\-\.]+)|\?.*|$)#', $remoteUrl->uri, $match)){
	$listId = !empty($match[2]) ? $match[2]: null;
	$videoId = $match[1];
	$html = '<html><head><style type="text/css">html,body{margin:0;padding:0;width:100%;height:100%;}</style>
		<script type="text/javascript" src="/images/jwplayer.js"></script></head><body>
		<script type="text/javascript">';
	if($listId){
	    $url = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytbl_{$listId}");
		$html .= "playYtbList('{$listId}','{$videoId}',false,'100%','100%','','{$url}');";
	}else{
	    $videoUrl = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytb_{$videoId}");
	    $thumbUrl = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytbimg_{$videoId}");
	    $html .= "playYtb('{$videoId}',false,'100%','100%','','{$videoUrl}','{$thumbUrl}');";
	}
	$html .= '</script></body></html>';
	exit($html);
}

//转到对应的手机页面
if(Http::isMobile() && isset($config['redirect_to_mobile']) && $config['redirect_to_mobile'] && isset($mobile_domains[$remoteUrl->url])){
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $urlCoding->encodeUrl($mobile_domains[$remoteUrl->url], null, null, true));
	exit;
}

//cookie
$requestCookieCoding = new CookieCoding($remoteUrl);
if($config['enable_cookie']){
	$requestCookieCoding->readCookies();
	if (isset($_POST[$config['basic_auth_var_name']], $_POST['username'], $_POST['password'])) {
		$_SERVER["REQUEST_METHOD"] = 'GET';
		$requestCookieCoding->remoteAuth = base64_encode(trim($_POST['username']) . ':' . $_POST['password'] );
		unset($_POST);
		$requestCookieCoding->writeCookies(array());
	}
}

// ================================================================================================
// 本次请求和响应结果变量
// ================================================================================================
$page = array (
	//请求是否是动态载入的（不缓存）
	'isajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) && stripos($_SERVER['HTTP_X_REQUESTED_WITH'],'XMLHttpRequest')!==false,
	//此网页或资源的类型(html, css, js, xml, media, content-type里的类型)
	'ctype' => $ctype && !$isframe ? $ctype : '',
	//是否frame或iframe页面
	'isframe' => $isframe,
	//是否是通过 link rel="import" 导入的页面
	'isimport' => $isImport,
	//是否是const.ini.php的$supported_content_type里设置的需要处理的类型，这些类型需要处理后一次性返回，除此之外的其他类型会分块儿返回
	'supported' => false,
	'pageandjs' => false,
	//文本类型(包含supported=true的和其他的文本类型)
	'istext' => false,
	//是否优先读取缓存，没有缓存时才向远端服务器请求（在下边确定了远端URL之后才能确定）
	'readcache' => false,
	//远端服务器返回的结果是否应该写入缓存（在远端服务器返回HTTP头之后才能确定）
	'writecache' => false,
	//被缓存对象的额外属性
	'cachesalt' => $config['cache_salt_for_supported'],
	//本地缓存扩展名，只有不带查询参数的资源文件才使用实际的扩展名，其他的都统一使用 .~tmp
	'cacheext' => null,
	//负责把远端服务器返回的结果写入缓存的缓存对象（在下边接收到http头时判断）
	'cache' => null,
	//网页字符集
	'charset' => null,
	//临时存储完整的文本形式的HTTP响应体
	'data' => '',
	//是否已经输出响应头
	'responsed' => false,
	//当前为此用户分配的cnd的编号列表
	'cdn' => isset($_COOKIE['_cdn_']) && preg_match('#^[\d,]+$#', $_COOKIE['_cdn_']) ? $_COOKIE['_cdn_'] : '',
	//未启用本地缓存时，使用1小时的客户端缓存
	'browser_cache_etag' => null,
);

if($config['enable_cache'] && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],'.swf')>0){
	//当被flash下载时，很有可能是在线播放，此时不限制文件大小
	$config['max_file_size']=0;
	//当flash里要下载的文件包含动态参数时，禁用缓存机制
	if(!empty($remoteUrl->query)){
		$config['enable_cache']=false;
	}
}

//某些域名禁止在本地被缓存
if($config['enable_cache'] && !empty($nocache_domains) && preg_match("#{$nocache_domains}#",$remoteUrl->host)){
	$config['enable_cache']=false;
}

//伪静态化资源文件
$ext=null;
if( $config['enable_cache'] && $config['enable_rewrite'] &&
	strpos($_SERVER['REQUEST_URI'],$currentUrl->path.'files/')===0 &&
	preg_match('#^/files/(?:\w{16}-\w-[\w\-]+|'. $config['url_var_name'][0] .'/\w{32}/[\w/]+)(\.\w{2,4})(\?[^=]*)?$#',$_SERVER['REQUEST_URI'],$match))
{
	//记录缓存选项
	$ext=$match[1];
	//todo:下一行为了兼容上版，以后可简化为=null
	$page['cachesalt']=strpos(' .js .css .xml '," {$ext} ")!==false ? $config['cache_salt_for_supported'] : null;
	$page['cacheext']=$ext;
	$page['readcache']=true;
	//ctype
	if(!$page['ctype'] && strpos("$image_file_exts $download_file_exts $media_file_exts", $ext)!==false){
		$page['ctype']='resource';
	}
}

//以下情况都同时满足时才会读取缓存
//1. 开启了缓存机制
//2. 没有：提交GET表单、提交POST表单、上传文件、使用了域登录
//3. 不发送cookie，或者是资源文件（只有应该被浏览器缓存的资源文件才会写入缓存）
$page['readcache'] =
	$config['enable_cache'] && !$dontReadCache &&
	(!isset($_GET[$config['get_form_name']]) && empty($_POST) && empty($_FILES) && empty($requestCookieCoding->remoteAuth)) &&
	(empty($requestCookieCoding->remoteCookies) || $page['ctype'] || $page['cacheext'] || !empty($_SERVER["HTTP_IF_NONE_MATCH"]));

//用手机访问网页时，使用针对手机的缓存（远程服务器可能会返回手机版页面）
if($page['readcache'] && !$page['ctype'] && !$page['cacheext'] && Http::isMobile() && strpos($_SERVER['HTTP_ACCEPT'],'text/html')!==false){
	$page['cachesalt'] = 'mobile://'.$page['cachesalt'];
}

/*
//客户端缓存机制（本机制即使未开启本地缓存也有效）：1小时内的重复请求，如果cookie没有变化，就返回304（禁止缓存的内容除外）
//因为只根据HTTP_IF_MODIFIED_SINCE判断是否需要缓存，会导致某些短时间的缓存无法失效，所以要禁用以下代码，此机制改为用 Cache-Control:max-age=秒 来控制
if(DEBUGING<2){
	$modifiedSince = isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) ? strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) : null;
	if($modifiedSince && TIME-$modifiedSince<=3600){
		if(isset($_SERVER["HTTP_IF_NONE_MATCH"])){
			$etag = CacheHttp::makeEtag(serialize($requestCookieCoding->remoteCookies).strval(Http::isMobile()).$page['cdn']);
			if($etag==$_SERVER["HTTP_IF_NONE_MATCH"]){
				header('HTTP/1.1 304 Not Modified');
				exit;
			}
		}else{
			header('HTTP/1.1 304 Not Modified');
			exit;
		}
	}
}
*/

// ================================================================================================
// 定义当远端服务器或者文件缓存返回内容时的应对操作
// ================================================================================================

/**
 * 完整返回HTTP头部之后的事件
 * @param Http $http
 * @param array $headers 解析后的数组格式（键名都是小写）
 * @param bool $fromCache 是否来源于缓存
 */
function onReceivedHeader($http, $headers, $fromCache){
	global $config, $page, $supported_content_type, $requestCookieCoding, $currentUrl, $remoteUrl, $urlCoding, $redirect_original, $app_path, $media_file_exts;
	global $image_file_exts, $media_file_exts;
	//如果发生了转向
	$redirected = $http->getLastUrl()!='' && $http->getLastUrl()!=$remoteUrl->url;
	if($redirected){
		$remoteUrl=Url::create($http->getLastUrl());
		if($remoteUrl===false){
			show_error(404);
		}elseif(!preg_match('#^[\w\-\.]+$#', $remoteUrl->host)){
			show_error(400);
		}
		$urlCoding->remoteUrl=$remoteUrl;
	}
	//==判断资源类型==
	$ext = isset($headers['__ext']) ? $headers['__ext'] : fileext($remoteUrl->file);
	if(!$http->contentType && $ext){
		$http->contentType = get_content_type($ext);
	}elseif(!$http->contentType && $remoteUrl->query=='' && $ext){
		$http->contentType = get_content_type($ext);
		if(!isset($headers['content-type']) && $http->contentType) $headers['content-type']=$http->contentType;
	}
	if(isset($headers['__ctype'])){
		$ctype = $headers['__ctype'];
	}elseif(isset($supported_content_type[$http->contentType])){
		$ctype = $supported_content_type[$http->contentType];
	}elseif(in_array(substr($http->contentType,0,6), array('audio/','video/'))){
		$ctype = 'resource';
	}else{
		$ctype = $http->contentType;
	}
	if(!$page['ctype'] || in_array($ctype, $supported_content_type)){
		$page['ctype'] = $ctype;
	}
	$page['supported'] = in_array($page['ctype'], $supported_content_type);
	$page['pageandjs'] = $page['supported'] && $page['ctype']!='css';
	$page['istext'] = ($page['supported'] || strpos($page['ctype'], 'text/')!==false) && (substr($remoteUrl->file,-3,4)!='.ts');
	$page['charset'] = $http->charset;
	if($page['istext'] && substr($remoteUrl->file,-3)=='.js') $page['ctype'] = 'js';

	//返回的content-type是文本类型，实际根据扩展名可以确定为不是文本型的，要纠正过来
	if($page['istext'] && in_array($ext, array('.woff','.ttf','.font'))){
		$page['istext'] = false;
	}

	/*
	//如果转向到网页了
	if($redirected && $page['ctype']=='html'){
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $urlCoding->encodeUrl($remoteUrl->url, null, null, true));
		$http->stop();
		return;
	}
	*/

	if($fromCache){
		//==从缓存输出, 先检查客户端缓存是否依然有效==
		if(!CacheHttp::isModified($headers)){
			//客户端无需更新，直接发送304信息
			header('HTTP/1.1 304 Not Modified');
			if(isset($headers['content-type'])) header("Content-Type: {$headers['content-type']}");
			if(isset($headers['cache-control'])) header("Cache-Control: {$headers['cache-control']}");
			if(isset($headers['expires'])) header("Expires: {$headers['expires']}");
			if(isset($headers['last-modified'])) header("Last-Modified: {$headers['last-modified']}");
			if(isset($headers['etag'])) header("ETag: {$headers['etag']}");
			if(isset($headers['content-disposition'])) header("Content-Disposition: {$headers['content-disposition']}");
			$http->stop();
			exit;
		}else{
			if(!isset($headers['content-type']) && $http->contentType) $headers['content-type']=$http->contentType;
		}
	}else{
		//==从HTTP输出==
		$notModified = true;
		//转到域名认证
		if($http->getResponseStatusCode()==401 && isset($headers['www-authenticate']) && preg_match('#basic\s+(?:realm="(.*?)")?#i', $headers['www-authenticate'], $match)) {
			$http->stop();
			show_report('auth', $match[1]);
		}
		//编码HTTP头里出现的url
		if (isset($headers['p3p']) && preg_match('#policyref\s*=\s*[\'"]?([^\'"\s]*)[\'"]?#i', $headers['p3p'], $matches )) {
			$headers['p3p']=str_replace($matches[0], 'policyref="' . $urlCoding->encodeUrl($matches[1]) . '"', $headers['p3p']);
		}
		if(isset($headers['location'])) {
			$headers['location']=$redirect_original?$headers['location']:$urlCoding->encodeUrl($headers['location'], $page['ctype'], null, true);
		}
		if (isset($headers['refresh']) && preg_match('#([0-9\s]*;\s*URL\s*=)\s*(\S*)#i', $headers['refresh'], $matches )){
			$headers['refresh']=$matches[1]. $urlCoding->encodeUrl($matches[2], $page['ctype'], null, true);
		}
		//去掉可能的敏感信息
		if(isset($headers['content-disposition'])){
			$headers['content-disposition']=preg_replace('#(filename\s*=\s*["\']?)([^\."\'\s;]+)\.#',
				'filename='.rand_string(3,8,RANDSTR_HEX,false).'.',
				$headers['content-disposition']);
		}
		if(!$config['enable_cookie']){
			unset($headers['set-cookie']);
		}

		//以下情况满足时应该写入缓存
		//   不是从缓存返回的内容
		//1. 开启了缓存机制
		//2. 没有：提交GET表单、提交POST表单、上传文件、使用了域登录
		//3. HTTP状态码=200，并且不是ajax动态载入的
		//4. 未使用分块下载 (分块下载无法保存到缓存)
		//5. 没发送cookie的网页，或者是应该被浏览器缓存的资源文件才会写入缓存
		//6. 根据HTTP响应头判断是否需要缓存
		$page['writecache'] =
			$config['enable_cache'] &&
			(!isset($_GET[$config['get_form_name']]) && empty($_POST) && empty($_FILES) && empty($requestCookieCoding->remoteAuth)) &&
			$http->getResponseStatusCode()==200 && !$page['isajax'] &&
			!isset($_SERVER['HTTP_RANGE']) &&
			CacheHttp::shouldCache($headers,$page['pageandjs'],!empty($requestCookieCoding->remoteCookies) && !empty($headers['set-cookie']));
		if($page['writecache']){
			//网页和js可能包含时效性较强的内容，缓存时间为服务器声明的有效期（不超过1小时）或者默认1小时
			//其他文件的缓存时间为服务器声明的有效期（不超过1天）或者默认1天
			$expire=$page['pageandjs']?min(CacheHttp::shouldCacheSeconds($headers,3600),3600):min(CacheHttp::shouldCacheSeconds($headers,3600*24),3600*24);
			//js文件里，如果服务器没返回过期时间或者很短，需要把缓存有效时间设置的短一些，我们暂定为2小时，并且不能使用.js扩展名以避免不走PHP
			if($page['supported']) $page['cacheext']=null;
			if(!$page['supported']) $page['cachesalt']=null;
			if(!isset($headers['expires'])) $headers['expires']=gmtDate("+{$expire} seconds");
			if(!isset($headers['cache-control'])) $headers['cache-control']="public, max-age={$expire}";
			//设置缓存信息
			$headerToCache = array();
			if(!$page['cacheext']){
				$keys = array('content-type', 'expires', 'cache-control', 'etag', 'last-modified', 'content-disposition',);
				foreach ($keys as $key){
					if(isset($headers[$key])) {
						$headerToCache[$key]=$headers[$key];
					}
				}
				$headerToCache['__ctype'] = $page['ctype'];
				$headerToCache['__charset'] = $http->charset;
				if(isset($headers['last-modified'])){
					$headerToCache['__last-modified'] = $headers['last-modified'];
				}
				if(isset($headers['etag'])){
					$headerToCache['etag'] = $headerToCache['__etag'] = $headers['etag']; //原始etag
				}else{
					$arr = $headerToCache;
					unset($arr['expires']);
					$headers['etag'] = $headerToCache['etag'] = CacheHttp::makeEtag($arr);
				}
			}
			if($page['cache']){
				$page['cache']->close();
				$page['cache']=null;
			}
			$page['cache']=CacheHttp::create(TEMPDIR, $remoteUrl->url, $headerToCache, $page['cachesalt'], $expire, $page['cacheext']);
		}

		//客户端缓存机制：如果没写入缓存，除了网页外，只要未禁止缓存，就启用1小时的客户端缓存
		if(DEBUGING<2 &&
			!$page['writecache'] &&
			(!isset($headers['pragma']) || $headers['pragma']!='no-cache') &&
			(!isset($headers['cache-control']) || $headers['cache-control']!='no-cache') &&
			$http->getResponseStatusCode()==200 && strpos($_SERVER['HTTP_ACCEPT'],'text/html')===false)
		{
			unset($headers['pragma'], $headers['expires']);
			$page['browser_cache_etag'] = CacheHttp::makeEtag(serialize($requestCookieCoding->remoteCookies).strval(Http::isMobile()).$page['cdn']);
			$headers['cache-control'] = !empty($headers['cache-control']) ? $headers['cache-control'] : 'max-age=3600';
			$headers['last-modified'] = gmtDate(TIME);
			$headers['etag'] = $page['browser_cache_etag'];
		}
	}

	//==输出HTTP头==

	//仅返回以下头部
	$toForward = array('content-type', 'content-disposition', 'content-language', 'location', 'refresh', 'accept-ranges', 'content-range',
		'cache-control', 'etag', 'pragma', 'expires', 'last-modified', 'supported');
	if((!$page['supported'] && !$page['istext'] && !$http->shouldUnzip) || $_SERVER['REQUEST_METHOD']=='HEAD'){
		//后边加密内容时和压缩时会改变，会从新计算，所以需要先删除此值
		$toForward[] = 'content-length';
	}
	if($config['enable_cookie']){
		$toForward[]='p3p';
		$toForward[]='set-cookie';
	}
	foreach ($headers as $k=>$v){
		if(!in_array($k, $toForward)) unset($headers[$k]);
	}

	//对需要更新的域从新赋值
	if($config['enable_cookie']){
		//加密cookie
		if(empty($headers['set-cookie'])){
			unset($headers['set-cookie']);
		}else{
			$responseCookieCoding = clone $requestCookieCoding;
			$responseCookieCoding->writeCookies($headers['set-cookie']);
			$headers['set-cookie']=$responseCookieCoding->setCookies;
			unset($responseCookieCoding);
		}
	}
	//保证另存为时可以正确获取到文件名
	if(!$page['supported'] && !empty($ext) && strpos("$image_file_exts $media_file_exts", $ext)!==false &&
	    !isset($headers['content-disposition']) && ($filename=$http->getFilename())){
		$headers['content-disposition']='inline; filename="'.substr(md5($remoteUrl->url),0,3).fileext($filename).'"';
	}
	//其他需要更新的域
	if($page['ctype']=='html' || $page['ctype']=='xml'){
		//补充charset
		if($page['charset'] && stripos($headers['content-type'], 'charset')===false){
			$headers['content-type']="{$headers['content-type']};charset={$page['charset']}";
		}
	}elseif(!$page['supported'] && $page['writecache'] && $page['cacheext'] && $page['cache']){
		//对于以后直接返回资源缓存的url，其ETag和Last-Modified的值，本次从远端返回的与以后直接从本服务器返回的值不一样，所以要修改和去掉不一样的影响缓存的部分
		unset($headers['etag']);
		$headers['last-modified'] = gmtDate($page['cache']->mtime);
	}

	//响应码
	if($fromCache){
		if(isset($headers['content-range'])){
			header('HTTP/1.1 206 Partial Content');
		}else{
			header('HTTP/1.1 200 OK');
		}
	}else{
		header($http->getResponseStatusText());
	}

	//如果是xml或媒体文件
	if($page['ctype']=='xml' || strpos($media_file_exts, " $ext ")!==false){
		header('Access-Control-Allow-Origin: *');
	}

	//其他响应头
	$keysCase = array('p3p'=>'P3P', 'etag'=>'ETag');
	foreach($headers as $k=>$v){
		if($k && substr($k,0,2)!='__'){
			$k = isset($keysCase[$k]) ? $keysCase[$k] : strtr(ucwords(strtr($k, '-', ' ')), ' ', '-');
			if(is_array($v)){
				for($i=0; $i<count($v); $i++)
					header($k.': '.$v[$i], false);
			}else{
				header($k.': '.$v);
			}
		}
	}

	$page['responsed']=($http->getResponseStatusCode()!=404);
}

/**
 * 返回每块儿HTTP主体时的事件
 * $finished=true 只表示与远端的http请求结束，不表示成功完成，需要在最底下的$result变量里判断是否成功完成
 */
function onReceivedBody($http, $data, $finished, $fromCache){
	global $config, $page, $http, $currentUrl, $remoteUrl, $urlCoding, $media_file_exts;
	static $isTextRam = null;
	static $isTextAsf = null;
	static $isTextM3u = null;

	//判断是否是仅包含一个视频文件url的*.ram
	if(is_null($isTextRam)){
		$isTextRam = substr($remoteUrl->file,-4)=='.ram' && preg_match('#^https?://[\w\-\./]+?\.(ra|rm|rmvb)\s*$#', $data);
	}
	if($isTextRam){
		$page['data'].=$data;
		if($finished){
			$url = $urlCoding->encodeUrl(trim($page['data']),'video',null,true);
			$url = $currentUrl->getFullUrl($url);

			if($page['cache']){
				$page['cache']->write($url);
				$page['cache']->finish();
			}

			header('Content-Length: '.strlen($url));
			echo $url;
		}
		return;
	}

	//判断是不是文本型的asf列表
	if(is_null($isTextAsf)){
		$isTextAsf = $http->contentType=='video/x-ms-asf' && preg_match('#^\s*\[reference\][\r\n]#i', $data);
	}
	if($isTextAsf){
		$page['data'].=$data;
		if($finished){
			if(preg_match_all('#[\r\n]\w+=(https?://[\w\.\-\?&/%=:]+)[\r\n]#', $page['data'], $matches, PREG_SET_ORDER)){
				for($i=0, $count=count($matches); $i<$count; ++$i) {
					$match = $matches[$i];
					//加密网址
					$url = $urlCoding->encodeUrl($match[1],'video',null,true);
					$url = $currentUrl->getFullUrl($url);
					$page['data'] = str_replace($match[1], $url, $page['data']);
				}
			}
			header('Content-Length: '.strlen($page['data']));
			echo $page['data'];
		}
		return;
	}

	//判断是不是文本型的m3u或m3u8列表
	if(is_null($isTextM3u)){
		$isTextM3u = strpos($data,'#EXTM3U')===0 && preg_match('#\.m3u8?$#', $remoteUrl->file);
	}
	if($isTextM3u){
		$page['data'].=$data;
		if($finished){
			$pattern = str_replace(array(' ','.'), array('|','\.'), trim($media_file_exts));
			if(preg_match_all('#[\r\n]([\w\.\-\?&/%=:]+?('.$pattern.'))[\r\n]#', $page['data'], $matches, PREG_SET_ORDER)){
				for($i=0, $count=count($matches); $i<$count; ++$i) {
					$match = $matches[$i];
					//加密网址
					if(substr($_SERVER['REQUEST_URI'],-14)=='/playlist.m3u8' && substr($match[1],-3)=='.ts' && strpos($match[1],'/')===false){
						$url = $match[1];
					}else{
						$url = $urlCoding->encodeUrl($match[1],'video',null,true);
						$url = $currentUrl->getFullUrl($url,false);
						$page['data'] = str_replace($match[1], $url, $page['data']);
					}
				}
			}
			header('Content-Length: '.strlen($page['data']));
			echo $page['data'];
		}
		return;
	}

	if($page['istext']){
		//需要处理的类型和可确定为文本的类型，需要下载完毕后再进行后续处理
		$page['data'].=$data;
		if($finished) {
			//如果是404页，并且内容是空的或者是默认的404页，就等到最后显示自定义的404页
			if($http->getResponseStatusCode()==404 && (!$page['data'] || stripos($page['data'], '.microsoft.com/')>0)){
				$page['responsed']=false;
			}else{
				$page['responsed']=true;
				outputHtml($page['data'], true, $fromCache);
			}
		}else{
			if(empty($page['data']) && $http->lastError){
				show_error(504);
			}
			$page['responsed']=false;
		}
	}else{
		//默认的缓冲区是4K，为了避免访客网速过慢，再在超时时间上增加15秒
		if(ENABLE_SET_TIME_LIMIT) set_time_limit($config['read_timeout']+15);

		//改变favicon.ico的特征
		if($remoteUrl->file=='favicon.ico' && strlen($data)>200){
		    $x = rand(100, strlen($data)-50);
		    $data[$x] = chr(rand(0,255));
		}

		//无需处理的类型直接输出，并保存到缓存里
		echo $data;

		if($page['cache']){
			$page['cache']->write($data);
		}
	}
	//完成缓存
	if($finished && $page['cache'] && !$http->lastError){
		$cacheFile = $page['cache']->finish();
	}
}

//输出编码后的网页
function outputHtml($data, $finish, $fromCache){
    global $config, $page, $currentUrl, $remoteUrl, $urlCoding, $bottom_navigation, $error_messages, $address, $start_time;;
	if(strlen($data)==0) return;

	//网页处理超时
	if(ENABLE_SET_TIME_LIMIT) set_time_limit(15);

	$htmlCoding=new HtmlCoding($currentUrl, $remoteUrl, $urlCoding);
	if(!$page['charset']){
		$page['charset']=$htmlCoding->getCharset($data);
	}
	$htmlCoding->charset=$urlCoding->charset=$page['charset'];
	$htmlCoding->ctype=$page['ctype'];

	//记录三退提交成功的计数
	if($_SERVER['REQUEST_METHOD']=='POST' && stripos($remoteUrl->url,'http://tuidang.epochtimes.com/post/')===0){
		$temp=mb_convert_encoding($data, APP_CHARSET, $page['charset']);
		if((strpos($temp,'您的声明已经提交')!==false || strpos($temp,'查询密码是')!==false)){
			record_counter('3tui');
		}
		unset($temp);
	}

	if(!$fromCache && $data){
		//删除Unicode规范中的BOM字节序标记(UCS编码的 Big-Endian BOM, UCS编码的 Little-Endian BOM, UTF-8编码的BOM)
		if($page['istext'] && ord($data{0})>=0xEF){
			if(substr($data,0,2)=="\xFE\xFF" || substr($data,0,2)=="\xFF\xFE"){
				$s = mb_convert_encoding($data, 'utf-8', 'UTF-16');
				if($s){
					$htmlCoding->charset = 'utf-8';
					$data = $s;
				}
			}elseif(substr($data,0,3)=="\xEF\xBB\xBF"){
				$data = substr($data, 3);
				$htmlCoding->charset = 'utf-8';
			}
		}
		//根据设置去除不支持的js和多媒体
		if($page['ctype']=='html'){
			if(!$config['enable_script']) $data=$htmlCoding->stripScript($data);
			if(!$config['enable_media']) $data=$htmlCoding->stripMedia($data);
		}
		//提取base标签里的链接地址
		if($page['ctype']=='html'){
			$urlCoding->parseBaseUrl($data,true);
		}
		//链接本地化
		if($page['ctype']=='css') {
			$data=$htmlCoding->proxifyCss($data, true);
			$data=$htmlCoding->proxifyDomain($data);
		}elseif($page['ctype']=='js') {
			$data=$htmlCoding->proxifyScript($data, true);
		}elseif($page['ctype']=='html' || $page['ctype']=='xml') {
			$data=$htmlCoding->proxifyHtml($data,$page['ctype']);
		}

		//压缩空白字符
		$data=$htmlCoding->compact($data, $page['ctype']);
		//保存到缓存
		if($finish && $page['cache']){
			$page['cache']->write($data);
		}
	}

	//替换域名占位变量
	$data=$htmlCoding->replaceVar($data, $page['cdn']);

	//还原地址栏里的地址，去掉备用域名插件所添加的尾巴
	if(!empty($config['player_only_allow_cn']) && $page['ctype']=='html' && strpos($data, "/images/jwplayer.js") && !in_array(get_user_country(),array('CN','LOCAL'))){
		$data = str_replace_once('</head>', '<script type="text/javascript">var _u_cn_="N",_r_url_="'.str_rot13($remoteUrl->url).'";</script></head>', $data);
	}

	//除了html和xml，其他类型文件的内容处理都在上边实现了，下边都是对网页类型文件的進一步处理

	//根据内容再次确定是不是网页类型
	$bodyEndPos = 0;
	if($page['ctype']=='html') $bodyEndPos = strripos($data,'</body>');
	if($bodyEndPos===false) $bodyEndPos=0;
	//判断是不是顶层(不在框架内、不是ajax请求)的普通网页
	$is_top_page=($bodyEndPos>0 && !$page['isframe'] && !$page['isajax'] && !$page['isimport']);

	if($is_top_page){
		//顶部地址栏、底部导航条和访问统计
		$showaddress = @$config['enable_address_bar'];
		$shownav = $bottom_navigation['enable'];
		$code='<script type="text/javascript">if(is_top_win){document.write(\'';
		if($shownav) $code.='<scr\'+\'ipt type="text/javascript" src="/?'.$config['built_in_name'].'='.encrypt_builtin('nav').'" charset="'.APP_CHARSET.'"></scr\'+\'ipt>';
		if($showaddress) $code.='<scr\'+\'ipt type="text/javascript" src="/images/address.js" charset="GBK"></scr\'+\'ipt>';
		$code.='\');';
		$code.="async_get('/?{$config['built_in_name']}=".encrypt_builtin('tj',true)."&_='+(+new Date()));";
		$code.='}</script>';
		$data=substr_replace($data,$code,$bodyEndPos,0);

		//还原地址栏里的地址，去掉备用域名插件所添加的尾巴
		if(!empty($_SERVER['SPARE_REAL_URI'])){
			$data = str_replace_once('</head>', "<script type='text/javascript'>".
			"document.cookie='_sp_=1; expires=' + new Date(new Date().getTime()+60*1000).toGMTString();" .
			"if(history.replaceState) history.replaceState(null, null, '{$_SERVER['REQUEST_URI']}');</script></head>", $data);
		}

		//把被阻止的链接标记上特殊的样式，以方便用户识别是不能点击的
		if(strpos($data,"/blank/")>0){
			$data = str_replace_once('</head>', "<style type='text/css'>a[href*='/blank/']{color:#999 !important;font-weight:normal !important;text-decoration:line-through !important;}</style></head>", $data);
		}
	}

	//处理远端网址里的hash
	if($bodyEndPos>0 && $remoteUrl->fragment){
		$data.="<script type='text/javascript'>location.hash='{$remoteUrl->fragment}';</script>";
	}

	//添加cdn探测代码
	if($config['cdn_servers'] && $is_top_page && trim($page['cdn'],',')==''){
		$data.=$htmlCoding->getCdnTestCode();
	}

	//添加第三方统计代码
	if($is_top_page && !empty($config['analytics'])){
		$data.=$config['analytics'];
	}

	//记录快捷网址的访问记录
	if(isset($_SERVER['REQUEST_ADDRESS_ID'])){
		$s = $urlCoding->getAddressOf($_SERVER['REQUEST_ADDRESS_ID']);
		$x = strpos($s,'#');
		if($x>0) {
			$s=substr($s,0,$x);
		}
		if($s==$remoteUrl->url || "{$s}/"==$remoteUrl->url){
			file_put_contents(DATADIR . '/~counter_visit_address_temp.dat', date('Ymd').",{$_SERVER['REQUEST_ADDRESS_ID']}\n", FILE_APPEND | LOCK_EX);
		}
	}

	//如果不是蜘蛛，就需要加密内容：
	//1.xml页面简单编码汉字，因为如果用javascript方式加密会破坏原有格式
	//2.手机访问html时只简单编码汉字，因为有些手机不支持javascript脚本
	//3.普通浏览器浏览html页面时采用javascript进行加密
	if($page['ctype']) {
		if($page['ctype']=='js' && DEBUGING<2){
			$data=$htmlCoding->encodeHanzi($data, $page['charset'], $page['ctype'], 'no');
			$data=htmlentities_to_js($data);
		}elseif($page['ctype']=='xml'){
			$data=$htmlCoding->proxifyDomain($data);
			$data=$htmlCoding->encodeHanzi($data, $page['charset'], $page['ctype'], 'no');
		}elseif($page['ctype']=='html' && ($bodyEndPos==0 || DEBUGING==2 || $page['isimport'])){
			$data=$htmlCoding->encodeHanzi($data, $page['charset'], $page['ctype'], $page['isframe'] || $page['isimport'] ? 'no' : 'auto');
		}elseif($page['ctype']=='html'){
			$data=$htmlCoding->encryptHtml($data, $page['charset']);
		}
	}

	//网页输出超时（按照1KB/秒的速度再增加30秒计算）
	if(ENABLE_SET_TIME_LIMIT) set_time_limit(strlen($data)/1024 + 30);

	if(DEBUGING && $error_messages && $page['ctype']=='html'){
		$data .= "/* <div style='padding:10px; margin:10px; border:1px solid #FFB2B6; background-color:#FFE8E7; color:#333; font-size:12px; clear:both; text-align:left;'><pre>".trim($error_messages)."</pre></div> */";
	}

	//显示页面执行时间
// 	if(DEBUGING>=1 && $page['ctype']=='html'){
// 	    $data .= "/* <div style='clear:both;'>page size: " . round(strlen($data)/1024,2) . "kb, load time: " . round(microtime(true)-$start_time,3) . "s</div> */";
// 	}

	//压缩
	switch ($config['zlib_output']){
		case 0:
			//不支持压缩（指定原始长度）
			header('Content-Length: '.strlen($data));
			echo $data;
			break;
		case 1:
			//自动压缩（不指定原始长度或压缩后的长度，系统会自动设置的）
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding');
			echo $data;
			break;
		case 2:
			//手动压缩（指定压缩后的长度）
			//用header()函数给浏览器发送一些头部信息，告诉浏览器这个页面已经用GZIP压缩过了！
			/*
			 * $data .= @ob_get_clean();
			 * $data=gzencode($data,6);
			 * header('Content-Encoding: gzip');
			 * header('Vary: Accept-Encoding');
			 * header('Content-Length: '.strlen($data));
			 * echo $data;
			 * break;
			 */
			//由于已经采用zlib压缩过了，所以为了考虑性能就不重复压缩了
			header('Content-Length: '.strlen($data));
			echo $data;
			break;
	}
}

//下边这个代码，是为了保证网页结束时清除资源
function on_shutdown(){
	global $page, $http, $db;
	if($page['cache']){
		$page['cache']->close();
		$page['cache']=null;
	}
	if($http) {
		$http->close(true);
		$http=null;
	}
	if($db) {
		$db->disconnect();
		$db=null;
	}

	if(function_exists('error_get_last')){
		$e = error_get_last();
		if($e){
			myErrorHandler($e['type'], $e['message'], $e['file'], $e['line']);
		}
	}
}
if(function_exists('register_shutdown_function')) register_shutdown_function('on_shutdown');

// ================================================================================================
// 从远端或缓存读取网页等资源
// ================================================================================================

$http = Http::create($config);
if($http===false){
	show_report('server');
	exit;
}
$http->proxy=$config['proxy'];
$http->currentHome=$currentUrl->home;
$http->redirect = $config['cdn_servers']=='' && !$redirect_original;

//设置请求头
$http->setRequestHeader('Referer', $urlCoding->getRefererString());
$http->setAuth($requestCookieCoding->remoteAuth);
if($config['enable_cookie']){
	foreach($requestCookieCoding->remoteCookies as $k=>$v){
		$http->setCookie($k, $v);
	}
}
if(isset($_SERVER['HTTP_RANGE'])){
	$http->setRequestHeader('Range', $_SERVER['HTTP_RANGE']);
}
//自定义请求头
if(!empty($config['additional_http_header']) && strpos($config['additional_http_header'],'=')>0){
	$arr=explode('=', $config['additional_http_header'], 2);
	$http->setRequestHeader(trim($arr[0]), trim($arr[1]));
}
if(substr($remoteUrl->host,-12)=='.youtube.com'){
	//模拟ie8访问youtube网站，否则某些内容无法被正确处理
	$http->setRequestHeader('user-agent', 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv 11.0) like Gecko');
}

//设置要提交的表单数据
if(!empty($_POST)){
	//解密POST数据
	if(isset($_POST['fk_charset'])){
		$fk_charset=strtoupper($_POST['fk_charset']);
		unset($_POST['fk_charset']);
	}else{
		$fk_charset=null;
	}
	foreach($_POST as $k=>$v){
		if(str_decrypt_form($k,$v,$fk_charset)){
			unset($_POST[$k]);
			$_POST[substr($k,3)]=$v;
		}
		unset($v);
	}
	foreach($_POST as $k=>$v){
		$http->addPostField($k, $v);
	}
}
//设置要提交的json数据
elseif(isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'],'/json')!==false){
	$s = file_get_contents('php://input');
	if($s) $http->setPostData($s);
}

//设置上传数据
if(!empty($_FILES)){
	foreach($_FILES as $k=>$v){
		$filename=isset($v['name'])?$v['name']:$v['tmp_name'];
		$content=null;
		if(isset($v['tmp_name']) && is_uploaded_file($v['tmp_name'])){
			$content=@file_get_contents($v['tmp_name']);
			unlink($v['tmp_name']);
		}
		if($filename && isset($content{0}))
			$http->addPostFile($k, $filename, $content);
	}
}

//是否读取缓存以及缓存的设置
$http->readCache = $page['readcache'] &&
	(!isset($_SERVER['HTTP_CACHE_CONTROL']) || $_SERVER['HTTP_CACHE_CONTROL']!='no-cache') &&
	(!isset($_SERVER['HTTP_PRAGMA']) || $_SERVER['HTTP_PRAGMA']!='no-cache');
$http->cacheDir = TEMPDIR;
$http->cacheSalt = $page['cachesalt'];
$http->cacheExt = $page['cacheext'];

//有些网站的缓存有效期比较特殊，需要单独设置
if($http->readCache && $page['ctype']=='html' && strpos($remoteUrl->host,'youtube.')!==false){
	$http->cacheExpire = TIME+3600*4; //因为视频真实地址的有效期是5小时多
}

//发出请求
if(ENABLE_SET_TIME_LIMIT) set_time_limit($config['connect_timeout']+5);
switch ($_SERVER['REQUEST_METHOD']){
	case 'HEAD':
		$result = $http->head($remoteUrl, null);
		break;
	case 'GET':
		$keys=array_keys($address);
		$current_id=isset($address[0])?0:$keys[0];
		do{
			$http->maxRetry = $current_id==0 ? 2 : 1;
			$result = $http->get($remoteUrl, null, null, 'onReceivedHeader', 'onReceivedBody');
			$current_id++;
		}while(!$result && $http->lastError=='internet' && empty($_GET) && isset($address[$current_id]) && ($remoteUrl=Url::create($urlCoding->getAddressOf($current_id))));
		break;
	case 'POST':
		$result = $http->post($remoteUrl, null, null, null, 'onReceivedHeader', 'onReceivedBody');
		break;
	default:
		$http->lastError = 501;
		break;
}

//把不完整的网页内容也输出吧
if(!$result && !$page['responsed'] && $page['data']){
	outputHtml($page['data'], false, false);
}
if($page['cache']) {
	$page['cache']->close();
	$page['cache']=null;
}

if($db) {
	$db->disconnect();
	unset($db);
}

$lastError=$http->lastError;
$contentLength=$http->contentLength;
$http->close();
unset($http);

//判断结果
if(!$result && !$page['responsed']){
	switch ($lastError){
		case 204:
		case 206:
		case 'partial':
			//只从远端接收到部分数据，但是也不排除是因为服务器返回的Content-Length错误而引起，所以，遇到此问题只显示，而不做缓存。
			break;
		case 400:
			show_error(400);
		case 403:
			show_error(403);
		case 404:
		case 'missing':
			if($maybeTopPage && file_exists(APPDIR.'/404.htm')){
				header('HTTP/1.0 404 Not Found');
				header('Content-Type: text/html; charset='.APP_CHARSET);
				$html=file_get_contents(APPDIR.'/404.htm');
				$html=str_replace('{apppath}', $currentUrl->home.$currentUrl->path, $html);
				//导航
				$links = array();
				foreach($address as $v){
					$arr = explode('|',$v,2);
					if(count($arr)==2 && $arr[0] && $arr[1]){
						$arr[1]=$currentUrl->getFullUrl(trim($arr[1]," \t*"));
						$link = "<li><a href='{$arr[1]}' target='_blank'>{$arr[0]}</a></li>";
						if(!in_array($link, $links)) $links[]= $link;
					}
				}
				$links = implode('', $links);
				$htmlCoding=new HtmlCoding($currentUrl, $remoteUrl, $urlCoding);
				$links=$htmlCoding->proxifyHtml($links);
				$links=str_replace(array('{app_site}','{apppath}'), $currentUrl->home.$currentUrl->path, $links);
				$links=$htmlCoding->encryptHtml($links,APP_CHARSET);
				$html=str_replace('{link}', $links, $html);
				exit($html);
			}else{
				show_error(404);
			}
		case 500:
			show_error(500);
		case 501:
			show_error(501);
		case 502:
		case 503:
		case 504:
		case 505:
		case 'internet':
			show_error(504, 'Remote server not exists or timeout');
		case 'timeout':
			show_error(504, 'timeout');
		case 'resource':
			header('HTTP/1.1 403 Forbidden');
			if($isframe){
				exit('文件太大');
			}else{
				show_report('resource', $contentLength);
			}
			exit;
		case 'cancel':
			exit;
		default:
			if($isframe){
				exit($lastError);
			}elseif(substr($currentUrl->file,-3)=='.js' || $page['ctype']=='js' || $ext='.js'){
				header('HTTP/1.0 505 Server Error');
			}else{
				show_report('message', $lastError);
			}
			exit;
	}
}
