<?php

//updated 20180427

require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/func.inc.php');
require(APPDIR.'/include/http.inc.php');
require(APPDIR.'/include/coding.inc.php');
require(APPDIR.'/include/db.inc.php');

// ================================================================================================
// ��ʼ��
// ================================================================================================

//��ȡ��վ��·��
$app_path = str_replace('/index.php', '/', ($_SERVER['SCRIPT_NAME']?$_SERVER['SCRIPT_NAME']:$_SERVER['PHP_SELF']));

//����Ƿ��б����������
if(empty($config) || empty($address)){
	header('Location: '.$app_path.'install.php');
	exit;
}

//���������Ŀ¼
if(!is_dir(DATADIR)) mkdirs(DATADIR) or die('�޷�����dataĿ¼������Ȩ�ޣ�');
if(!is_dir(TEMPDIR)) mkdirs(TEMPDIR) or die('�޷�����tempĿ¼������Ȩ�ޣ�');

//��ʼ������
init_config();

//��ҳ������ʽ(��ʾΪ�հ�ҳ�����ҳ)
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

//�Ƴ���΢�������ʱ�Զ���ӵ�һ��Ѻ�׺
if( preg_match('#(\?|&)nsukey=[\w\%\-]{50,}$#', $_SERVER['REQUEST_URI'], $match)){
	unset($_GET['nsukey']);
	$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'],0,-strlen($match[0]));
}

//ɾ��������ַ���Σ���ַ�
if(preg_match('#%0[0ad]#i', $_SERVER['REQUEST_URI'])){
	show_error(404);
}

//��ʼ��������������
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

//����վ�㹦��
if(!empty($config['mirror_site'])){
	require(APPDIR."/include/mirror.inc.php");
}

//�����Զ�����
if($config['plugin'] && file_exists(APPDIR."/plugin/{$config['plugin']}.php")){
	require(APPDIR."/plugin/{$config['plugin']}.php");
}

//�������ݿ⣨��������ַ����Ҫ��
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
// Ĭ�ϵ�һЩҳ�涯��
// ================================================================================================

//crossdomain.xml
if($_SERVER['REQUEST_URI']=='/crossdomain.xml'){
	header('Content-Type: application/xml');
	echo '<?xml version="1.0"?><cross-domain-policy><allow-access-from domain="*" /></cross-domain-policy>';
	exit;
}

//��ȡ����
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

//js���ܺ�������ַ�����ͺ�����
if($builtInAction=='js'){
	header('Content-Type: text/javascript');
	header('Cache-Control: public, max-age=86400');
	header('Expires: '.gmtDate("+1 day"));

	$cacheFile = TEMPDIR.'/~js'.APP_VER.'.~tmp';
	$jsSrcFile = APPDIR.'/images/enc.js';
	if(file_exists($cacheFile) && time()-filemtime($cacheFile)<86400 && filemtime($jsSrcFile)<filemtime($cacheFile)){
		//���1���ڵĻ���
		$script = file_get_contents($cacheFile);
	}else{
		$script = file_get_contents($jsSrcFile);
		//����һЩ����仯����
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
		//����仯��������һЩ����
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
		//ѹ��
		require(APPDIR.'/include/jspacker.inc.php');
		$packer = new JavaScriptPacker($script, 62, true, false);
		$script = $packer->pack();
		//���滺��
		file_put_contents($cacheFile, $script, LOCK_EX);
	}
	//�滻�����������
	echo str_replace('jsFuncName', HtmlCoding::getFuncName(), $script);
	exit;
}

//ת���ֻ�ҳ��
if(empty($_GET) && Http::isMobile() && file_exists(APPDIR.'/mobile/') && empty($_COOKIE['display_pc']) && empty($config['display_pc'])){
	header('Location: '.$app_path.'mobile/');
	exit;
}

//����ֹҳ�淵�ؿհ�ҳ
if(strpos($_SERVER['REQUEST_URI'], $app_path.'blank/')===0){
	header('Cache-Control: public, max-age=3600');
	header('Expires: '.gmtDate("+1 hour"));
	exit;
}

//������֩����ת����
if($_SERVER['REQUEST_METHOD']=='HEAD' && Http::isSpider()=='jump'){
	//�����ɡ�������ת��վ�����ύ
	exit;
}

//�ײ�������javascript����ʾ�����
if($builtInAction=='nav') {
	if(!isset($_SERVER['HTTP_X_NAV_VISIBLE'])) show_navigation_js();
	exit;
}

//����youtubeα��̬
if(substr($_SERVER['REQUEST_URI'],0,5)=='/_ytb'){
	if(preg_match('#^/_ytbl/([\w\-\.]+)\.rss$#', $_SERVER['REQUEST_URI'], $match)){
		$_GET['_ytbl'] = $match[1];
	}elseif(preg_match('#^/_ytb/([\w\-\.]+)\.mp4$#', $_SERVER['REQUEST_URI'], $match)){
		$_GET['_ytb'] = $match[1];
	}
}
//����youtube��Ƶ�����б�
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
//����youtube��Ƶ��ַ
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

//����/?homeʱ������ı���homepage_style������Ҫת�򵽵�һ����ַ
if($isIndexPhp && isset($_GET['home']) && empty($_GET['home']) && !empty($config['homepage_style'])){
	$keys=array_keys($address);
	$id=isset($address[0])?0:$keys[0];
	header("Location: /{$id}/");
	exit;
}

//��¼ͳ����Ϣ
if($builtInAction=='tj'){
    $lastVisit = isset($_COOKIE[$config['cookie_counter']]) ? intval($_COOKIE[$config['cookie_counter']]) : 0;
    $passedSeconds = time()-$lastVisit;

    //���ϴ�����ʱ�䣬�϶�Ϊ�·ÿͣ���ӷ��ʼ�¼������cookie��¼����ʱ��
    if(!$lastVisit && record_counter('visit')){
        setcookie($config['cookie_counter'], time(), time()+7200, '/');
    }

    //���ϴ�����ʱ�䣬ÿ��30���Ӹ���һ���ϴη���ʱ�䣬����2Сʱû�и�����ᵼ�´�cookieʧЧ��Ȼ���ٷ��ʾ������µķÿ���
    if($lastVisit && $passedSeconds>1800){
        setcookie($config['cookie_counter'], time(), time()+7200, '/');
    }

    if($passedSeconds % 2 == 1){
        //�����룬���ͬ������
        if(!empty($config['sync_server'])){
    		include(APPDIR.'/include/sync.inc.php');
    	}
    }else{
    	//˫���룬����Ƿ�����������
    	$subdir = rand_string(1, 1, RANDSTR_HEX, false);
    	if(CacheHttp::canClearOverdueCache($subdir)){
    	    CacheHttp::clearOverdueCache($subdir);
    	}
    }

	exit;
}

//�����Ƿ�Ϊ�й���½�û�
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

//����Ƿ�֧�ֱ���������������жϵ�ǰ��ַ�ǲ���manifest�ļ�
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
// ��ʱ���ò��������Ա���������Ч��
// ================================================================================================

//ͨ�����������ʱ�ڱ�ҳ����cache���������ڲ���
$dontReadCache = isset($_GET['_no_cache_']);
if($dontReadCache){
	unset($_GET['_no_cache_']);
	$_SERVER['REQUEST_URI']=preg_replace('#[\?&]_no_cache_=\w*$#', '', $_SERVER['REQUEST_URI']);
}

// ================================================================================================
// ������ʵ��Զ��url
// ================================================================================================

$ctype = isset($_GET[$config['ctype_var_name']]) && preg_match('#^[\w\-\.]+$#', $_GET[$config['ctype_var_name']]) ? $_GET[$config['ctype_var_name']] : '';
$isframe = $ctype=='frame';
$isImport = $ctype=='import';
$accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : 'text/html';
$isajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest';

//��block������
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

//����Ӧǰ���ж��Ƿ�����Ƕ������ҳ
if($ctype || strpos($accept,'text/html')!==0 || $isajax){
	$maybeTopPage = false;
}elseif(($ext=fileext($_SERVER['REQUEST_URI']))==''){
	$maybeTopPage = true;
}else{
	$contentType = get_content_type($ext);
	$maybeTopPage = $contentType && isset($supported_content_type[$contentType]) && $supported_content_type[$contentType]=='html';
}

//����Զ�˵�ַ
if(!empty($_GET['_ytbimg']) && preg_match('#^[\w\-\.]+$#', $_GET['_ytbimg'])){
	//����youtube����ͼ
	$remoteUrl = Url::create("http://i.ytimg.com/vi/{$_GET['_ytbimg']}/0.jpg");
	$_GET[$config['ctype_var_name']] = $ctype = 'img';
}else{
	//����Զ�˵�ַ
	$remoteUrl = $urlCoding->getRemoteUrl(null,true,true);
	if($remoteUrl===false && !empty($config['tui_url']) && strpos($config['tui_url'],"/{$currentUrl->site}/")===false &&
		!file_exists(APPDIR.'/tui/') && preg_match('#^/tui/(.*)#',$_SERVER['REQUEST_URI'],$match)){
		//�ض���վ������˱�ϵͳ
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

//���ݺ���������������Ƿ�block (����ֹ������������Դ�ļ�)
$ext = fileext($remoteUrl->file);
if($urlCoding->isBlockDomain($remoteUrl->host)){
	show_error(403);
}elseif(($ext=='.js' || $accept=='*/*' || strpos($accept,'javascript')!==false) && $urlCoding->isBlockScript($remoteUrl->url)){
	show_error(403);
}elseif($isajax || substr($accept,0,9)!='text/html' || strpos(" .js .css .xml .jpg .png .gif .ico .swf .flv .mp3 .mp4 .m3u8 .ts ", " $ext ")!==false){
	//������ҳ���󣬼���
}elseif($urlCoding->isSafeDomain($remoteUrl->host)){
	//��ȫ����������
}elseif(!$urlCoding->isBlockedByWhiteDomain($remoteUrl->host)){
	//δ����������ֹ������
}else{
	//����������ֹ
	show_error(403);
}

//��ֹ֩�����
if(!preg_match('#(feed|\.xml$|\.rss$)#', $remoteUrl->url)){ //�����rss reader��վ��ֹ
	forbid_spider();
}

//���ֻ�����й���½���ʵ�url
if(!empty($config['only_allow_cn']) && preg_match('#'.$config['only_allow_cn'].'#', $remoteUrl->url) && !in_array(get_user_country(),array('CN','LOCAL'))){
	show_error(403);
}
//����Ƿ�ֻ�����й���½����������ֱ��
if( strpos($remoteUrl->file,'.m3u8')!==false &&
    preg_match('#\.(ntdtv\.com|ntdtv\.com\.tw|ntdimg\.com)/.+?\.m3u8#', $remoteUrl->url) &&
    file_exists(DATADIR.'/ntd_onlycn.dat') &&
    !in_array(get_user_country(),array('CN','LOCAL'))){
    show_error(403);
}

//����youtubeǶ����Ƶҳ
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

//ת����Ӧ���ֻ�ҳ��
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
// �����������Ӧ�������
// ================================================================================================
$page = array (
	//�����Ƿ��Ƕ�̬����ģ������棩
	'isajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) && stripos($_SERVER['HTTP_X_REQUESTED_WITH'],'XMLHttpRequest')!==false,
	//����ҳ����Դ������(html, css, js, xml, media, content-type�������)
	'ctype' => $ctype && !$isframe ? $ctype : '',
	//�Ƿ�frame��iframeҳ��
	'isframe' => $isframe,
	//�Ƿ���ͨ�� link rel="import" �����ҳ��
	'isimport' => $isImport,
	//�Ƿ���const.ini.php��$supported_content_type�����õ���Ҫ��������ͣ���Щ������Ҫ�����һ���Է��أ�����֮����������ͻ�ֿ������
	'supported' => false,
	'pageandjs' => false,
	//�ı�����(����supported=true�ĺ��������ı�����)
	'istext' => false,
	//�Ƿ����ȶ�ȡ���棬û�л���ʱ����Զ�˷������������±�ȷ����Զ��URL֮�����ȷ����
	'readcache' => false,
	//Զ�˷��������صĽ���Ƿ�Ӧ��д�뻺�棨��Զ�˷���������HTTPͷ֮�����ȷ����
	'writecache' => false,
	//���������Ķ�������
	'cachesalt' => $config['cache_salt_for_supported'],
	//���ػ�����չ����ֻ�в�����ѯ��������Դ�ļ���ʹ��ʵ�ʵ���չ���������Ķ�ͳһʹ�� .~tmp
	'cacheext' => null,
	//�����Զ�˷��������صĽ��д�뻺��Ļ���������±߽��յ�httpͷʱ�жϣ�
	'cache' => null,
	//��ҳ�ַ���
	'charset' => null,
	//��ʱ�洢�������ı���ʽ��HTTP��Ӧ��
	'data' => '',
	//�Ƿ��Ѿ������Ӧͷ
	'responsed' => false,
	//��ǰΪ���û������cnd�ı���б�
	'cdn' => isset($_COOKIE['_cdn_']) && preg_match('#^[\d,]+$#', $_COOKIE['_cdn_']) ? $_COOKIE['_cdn_'] : '',
	//δ���ñ��ػ���ʱ��ʹ��1Сʱ�Ŀͻ��˻���
	'browser_cache_etag' => null,
);

if($config['enable_cache'] && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],'.swf')>0){
	//����flash����ʱ�����п��������߲��ţ���ʱ�������ļ���С
	$config['max_file_size']=0;
	//��flash��Ҫ���ص��ļ�������̬����ʱ�����û������
	if(!empty($remoteUrl->query)){
		$config['enable_cache']=false;
	}
}

//ĳЩ������ֹ�ڱ��ر�����
if($config['enable_cache'] && !empty($nocache_domains) && preg_match("#{$nocache_domains}#",$remoteUrl->host)){
	$config['enable_cache']=false;
}

//α��̬����Դ�ļ�
$ext=null;
if( $config['enable_cache'] && $config['enable_rewrite'] &&
	strpos($_SERVER['REQUEST_URI'],$currentUrl->path.'files/')===0 &&
	preg_match('#^/files/(?:\w{16}-\w-[\w\-]+|'. $config['url_var_name'][0] .'/\w{32}/[\w/]+)(\.\w{2,4})(\?[^=]*)?$#',$_SERVER['REQUEST_URI'],$match))
{
	//��¼����ѡ��
	$ext=$match[1];
	//todo:��һ��Ϊ�˼����ϰ棬�Ժ�ɼ�Ϊ=null
	$page['cachesalt']=strpos(' .js .css .xml '," {$ext} ")!==false ? $config['cache_salt_for_supported'] : null;
	$page['cacheext']=$ext;
	$page['readcache']=true;
	//ctype
	if(!$page['ctype'] && strpos("$image_file_exts $download_file_exts $media_file_exts", $ext)!==false){
		$page['ctype']='resource';
	}
}

//���������ͬʱ����ʱ�Ż��ȡ����
//1. �����˻������
//2. û�У��ύGET�����ύPOST�����ϴ��ļ���ʹ�������¼
//3. ������cookie����������Դ�ļ���ֻ��Ӧ�ñ�������������Դ�ļ��Ż�д�뻺�棩
$page['readcache'] =
	$config['enable_cache'] && !$dontReadCache &&
	(!isset($_GET[$config['get_form_name']]) && empty($_POST) && empty($_FILES) && empty($requestCookieCoding->remoteAuth)) &&
	(empty($requestCookieCoding->remoteCookies) || $page['ctype'] || $page['cacheext'] || !empty($_SERVER["HTTP_IF_NONE_MATCH"]));

//���ֻ�������ҳʱ��ʹ������ֻ��Ļ��棨Զ�̷��������ܻ᷵���ֻ���ҳ�棩
if($page['readcache'] && !$page['ctype'] && !$page['cacheext'] && Http::isMobile() && strpos($_SERVER['HTTP_ACCEPT'],'text/html')!==false){
	$page['cachesalt'] = 'mobile://'.$page['cachesalt'];
}

/*
//�ͻ��˻�����ƣ������Ƽ�ʹδ�������ػ���Ҳ��Ч����1Сʱ�ڵ��ظ��������cookieû�б仯���ͷ���304����ֹ��������ݳ��⣩
//��Ϊֻ����HTTP_IF_MODIFIED_SINCE�ж��Ƿ���Ҫ���棬�ᵼ��ĳЩ��ʱ��Ļ����޷�ʧЧ������Ҫ�������´��룬�˻��Ƹ�Ϊ�� Cache-Control:max-age=�� ������
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
// ���嵱Զ�˷����������ļ����淵������ʱ��Ӧ�Բ���
// ================================================================================================

/**
 * ��������HTTPͷ��֮����¼�
 * @param Http $http
 * @param array $headers ������������ʽ����������Сд��
 * @param bool $fromCache �Ƿ���Դ�ڻ���
 */
function onReceivedHeader($http, $headers, $fromCache){
	global $config, $page, $supported_content_type, $requestCookieCoding, $currentUrl, $remoteUrl, $urlCoding, $redirect_original, $app_path, $media_file_exts;
	global $image_file_exts, $media_file_exts;
	//���������ת��
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
	//==�ж���Դ����==
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

	//���ص�content-type���ı����ͣ�ʵ�ʸ�����չ������ȷ��Ϊ�����ı��͵ģ�Ҫ��������
	if($page['istext'] && in_array($ext, array('.woff','.ttf','.font'))){
		$page['istext'] = false;
	}

	/*
	//���ת����ҳ��
	if($redirected && $page['ctype']=='html'){
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $urlCoding->encodeUrl($remoteUrl->url, null, null, true));
		$http->stop();
		return;
	}
	*/

	if($fromCache){
		//==�ӻ������, �ȼ��ͻ��˻����Ƿ���Ȼ��Ч==
		if(!CacheHttp::isModified($headers)){
			//�ͻ���������£�ֱ�ӷ���304��Ϣ
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
		//==��HTTP���==
		$notModified = true;
		//ת��������֤
		if($http->getResponseStatusCode()==401 && isset($headers['www-authenticate']) && preg_match('#basic\s+(?:realm="(.*?)")?#i', $headers['www-authenticate'], $match)) {
			$http->stop();
			show_report('auth', $match[1]);
		}
		//����HTTPͷ����ֵ�url
		if (isset($headers['p3p']) && preg_match('#policyref\s*=\s*[\'"]?([^\'"\s]*)[\'"]?#i', $headers['p3p'], $matches )) {
			$headers['p3p']=str_replace($matches[0], 'policyref="' . $urlCoding->encodeUrl($matches[1]) . '"', $headers['p3p']);
		}
		if(isset($headers['location'])) {
			$headers['location']=$redirect_original?$headers['location']:$urlCoding->encodeUrl($headers['location'], $page['ctype'], null, true);
		}
		if (isset($headers['refresh']) && preg_match('#([0-9\s]*;\s*URL\s*=)\s*(\S*)#i', $headers['refresh'], $matches )){
			$headers['refresh']=$matches[1]. $urlCoding->encodeUrl($matches[2], $page['ctype'], null, true);
		}
		//ȥ�����ܵ�������Ϣ
		if(isset($headers['content-disposition'])){
			$headers['content-disposition']=preg_replace('#(filename\s*=\s*["\']?)([^\."\'\s;]+)\.#',
				'filename='.rand_string(3,8,RANDSTR_HEX,false).'.',
				$headers['content-disposition']);
		}
		if(!$config['enable_cookie']){
			unset($headers['set-cookie']);
		}

		//�����������ʱӦ��д�뻺��
		//   ���Ǵӻ��淵�ص�����
		//1. �����˻������
		//2. û�У��ύGET�����ύPOST�����ϴ��ļ���ʹ�������¼
		//3. HTTP״̬��=200�����Ҳ���ajax��̬�����
		//4. δʹ�÷ֿ����� (�ֿ������޷����浽����)
		//5. û����cookie����ҳ��������Ӧ�ñ�������������Դ�ļ��Ż�д�뻺��
		//6. ����HTTP��Ӧͷ�ж��Ƿ���Ҫ����
		$page['writecache'] =
			$config['enable_cache'] &&
			(!isset($_GET[$config['get_form_name']]) && empty($_POST) && empty($_FILES) && empty($requestCookieCoding->remoteAuth)) &&
			$http->getResponseStatusCode()==200 && !$page['isajax'] &&
			!isset($_SERVER['HTTP_RANGE']) &&
			CacheHttp::shouldCache($headers,$page['pageandjs'],!empty($requestCookieCoding->remoteCookies) && !empty($headers['set-cookie']));
		if($page['writecache']){
			//��ҳ��js���ܰ���ʱЧ�Խ�ǿ�����ݣ�����ʱ��Ϊ��������������Ч�ڣ�������1Сʱ������Ĭ��1Сʱ
			//�����ļ��Ļ���ʱ��Ϊ��������������Ч�ڣ�������1�죩����Ĭ��1��
			$expire=$page['pageandjs']?min(CacheHttp::shouldCacheSeconds($headers,3600),3600):min(CacheHttp::shouldCacheSeconds($headers,3600*24),3600*24);
			//js�ļ�����������û���ع���ʱ����ߺ̣ܶ���Ҫ�ѻ�����Чʱ�����õĶ�һЩ�������ݶ�Ϊ2Сʱ�����Ҳ���ʹ��.js��չ���Ա��ⲻ��PHP
			if($page['supported']) $page['cacheext']=null;
			if(!$page['supported']) $page['cachesalt']=null;
			if(!isset($headers['expires'])) $headers['expires']=gmtDate("+{$expire} seconds");
			if(!isset($headers['cache-control'])) $headers['cache-control']="public, max-age={$expire}";
			//���û�����Ϣ
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
					$headerToCache['etag'] = $headerToCache['__etag'] = $headers['etag']; //ԭʼetag
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

		//�ͻ��˻�����ƣ����ûд�뻺�棬������ҳ�⣬ֻҪδ��ֹ���棬������1Сʱ�Ŀͻ��˻���
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

	//==���HTTPͷ==

	//����������ͷ��
	$toForward = array('content-type', 'content-disposition', 'content-language', 'location', 'refresh', 'accept-ranges', 'content-range',
		'cache-control', 'etag', 'pragma', 'expires', 'last-modified', 'supported');
	if((!$page['supported'] && !$page['istext'] && !$http->shouldUnzip) || $_SERVER['REQUEST_METHOD']=='HEAD'){
		//��߼�������ʱ��ѹ��ʱ��ı䣬����¼��㣬������Ҫ��ɾ����ֵ
		$toForward[] = 'content-length';
	}
	if($config['enable_cookie']){
		$toForward[]='p3p';
		$toForward[]='set-cookie';
	}
	foreach ($headers as $k=>$v){
		if(!in_array($k, $toForward)) unset($headers[$k]);
	}

	//����Ҫ���µ�����¸�ֵ
	if($config['enable_cookie']){
		//����cookie
		if(empty($headers['set-cookie'])){
			unset($headers['set-cookie']);
		}else{
			$responseCookieCoding = clone $requestCookieCoding;
			$responseCookieCoding->writeCookies($headers['set-cookie']);
			$headers['set-cookie']=$responseCookieCoding->setCookies;
			unset($responseCookieCoding);
		}
	}
	//��֤���Ϊʱ������ȷ��ȡ���ļ���
	if(!$page['supported'] && !empty($ext) && strpos("$image_file_exts $media_file_exts", $ext)!==false &&
	    !isset($headers['content-disposition']) && ($filename=$http->getFilename())){
		$headers['content-disposition']='inline; filename="'.substr(md5($remoteUrl->url),0,3).fileext($filename).'"';
	}
	//������Ҫ���µ���
	if($page['ctype']=='html' || $page['ctype']=='xml'){
		//����charset
		if($page['charset'] && stripos($headers['content-type'], 'charset')===false){
			$headers['content-type']="{$headers['content-type']};charset={$page['charset']}";
		}
	}elseif(!$page['supported'] && $page['writecache'] && $page['cacheext'] && $page['cache']){
		//�����Ժ�ֱ�ӷ�����Դ�����url����ETag��Last-Modified��ֵ�����δ�Զ�˷��ص����Ժ�ֱ�Ӵӱ����������ص�ֵ��һ��������Ҫ�޸ĺ�ȥ����һ����Ӱ�컺��Ĳ���
		unset($headers['etag']);
		$headers['last-modified'] = gmtDate($page['cache']->mtime);
	}

	//��Ӧ��
	if($fromCache){
		if(isset($headers['content-range'])){
			header('HTTP/1.1 206 Partial Content');
		}else{
			header('HTTP/1.1 200 OK');
		}
	}else{
		header($http->getResponseStatusText());
	}

	//�����xml��ý���ļ�
	if($page['ctype']=='xml' || strpos($media_file_exts, " $ext ")!==false){
		header('Access-Control-Allow-Origin: *');
	}

	//������Ӧͷ
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
 * ����ÿ���HTTP����ʱ���¼�
 * $finished=true ֻ��ʾ��Զ�˵�http�������������ʾ�ɹ���ɣ���Ҫ������µ�$result�������ж��Ƿ�ɹ����
 */
function onReceivedBody($http, $data, $finished, $fromCache){
	global $config, $page, $http, $currentUrl, $remoteUrl, $urlCoding, $media_file_exts;
	static $isTextRam = null;
	static $isTextAsf = null;
	static $isTextM3u = null;

	//�ж��Ƿ��ǽ�����һ����Ƶ�ļ�url��*.ram
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

	//�ж��ǲ����ı��͵�asf�б�
	if(is_null($isTextAsf)){
		$isTextAsf = $http->contentType=='video/x-ms-asf' && preg_match('#^\s*\[reference\][\r\n]#i', $data);
	}
	if($isTextAsf){
		$page['data'].=$data;
		if($finished){
			if(preg_match_all('#[\r\n]\w+=(https?://[\w\.\-\?&/%=:]+)[\r\n]#', $page['data'], $matches, PREG_SET_ORDER)){
				for($i=0, $count=count($matches); $i<$count; ++$i) {
					$match = $matches[$i];
					//������ַ
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

	//�ж��ǲ����ı��͵�m3u��m3u8�б�
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
					//������ַ
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
		//��Ҫ��������ͺͿ�ȷ��Ϊ�ı������ͣ���Ҫ������Ϻ��ٽ��к�������
		$page['data'].=$data;
		if($finished) {
			//�����404ҳ�����������ǿյĻ�����Ĭ�ϵ�404ҳ���͵ȵ������ʾ�Զ����404ҳ
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
		//Ĭ�ϵĻ�������4K��Ϊ�˱���ÿ����ٹ��������ڳ�ʱʱ��������15��
		if(ENABLE_SET_TIME_LIMIT) set_time_limit($config['read_timeout']+15);

		//�ı�favicon.ico������
		if($remoteUrl->file=='favicon.ico' && strlen($data)>200){
		    $x = rand(100, strlen($data)-50);
		    $data[$x] = chr(rand(0,255));
		}

		//���账�������ֱ������������浽������
		echo $data;

		if($page['cache']){
			$page['cache']->write($data);
		}
	}
	//��ɻ���
	if($finished && $page['cache'] && !$http->lastError){
		$cacheFile = $page['cache']->finish();
	}
}

//�����������ҳ
function outputHtml($data, $finish, $fromCache){
    global $config, $page, $currentUrl, $remoteUrl, $urlCoding, $bottom_navigation, $error_messages, $address, $start_time;;
	if(strlen($data)==0) return;

	//��ҳ����ʱ
	if(ENABLE_SET_TIME_LIMIT) set_time_limit(15);

	$htmlCoding=new HtmlCoding($currentUrl, $remoteUrl, $urlCoding);
	if(!$page['charset']){
		$page['charset']=$htmlCoding->getCharset($data);
	}
	$htmlCoding->charset=$urlCoding->charset=$page['charset'];
	$htmlCoding->ctype=$page['ctype'];

	//��¼�����ύ�ɹ��ļ���
	if($_SERVER['REQUEST_METHOD']=='POST' && stripos($remoteUrl->url,'http://tuidang.epochtimes.com/post/')===0){
		$temp=mb_convert_encoding($data, APP_CHARSET, $page['charset']);
		if((strpos($temp,'���������Ѿ��ύ')!==false || strpos($temp,'��ѯ������')!==false)){
			record_counter('3tui');
		}
		unset($temp);
	}

	if(!$fromCache && $data){
		//ɾ��Unicode�淶�е�BOM�ֽ�����(UCS����� Big-Endian BOM, UCS����� Little-Endian BOM, UTF-8�����BOM)
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
		//��������ȥ����֧�ֵ�js�Ͷ�ý��
		if($page['ctype']=='html'){
			if(!$config['enable_script']) $data=$htmlCoding->stripScript($data);
			if(!$config['enable_media']) $data=$htmlCoding->stripMedia($data);
		}
		//��ȡbase��ǩ������ӵ�ַ
		if($page['ctype']=='html'){
			$urlCoding->parseBaseUrl($data,true);
		}
		//���ӱ��ػ�
		if($page['ctype']=='css') {
			$data=$htmlCoding->proxifyCss($data, true);
			$data=$htmlCoding->proxifyDomain($data);
		}elseif($page['ctype']=='js') {
			$data=$htmlCoding->proxifyScript($data, true);
		}elseif($page['ctype']=='html' || $page['ctype']=='xml') {
			$data=$htmlCoding->proxifyHtml($data,$page['ctype']);
		}

		//ѹ���հ��ַ�
		$data=$htmlCoding->compact($data, $page['ctype']);
		//���浽����
		if($finish && $page['cache']){
			$page['cache']->write($data);
		}
	}

	//�滻����ռλ����
	$data=$htmlCoding->replaceVar($data, $page['cdn']);

	//��ԭ��ַ����ĵ�ַ��ȥ�����������������ӵ�β��
	if(!empty($config['player_only_allow_cn']) && $page['ctype']=='html' && strpos($data, "/images/jwplayer.js") && !in_array(get_user_country(),array('CN','LOCAL'))){
		$data = str_replace_once('</head>', '<script type="text/javascript">var _u_cn_="N",_r_url_="'.str_rot13($remoteUrl->url).'";</script></head>', $data);
	}

	//����html��xml�����������ļ������ݴ������ϱ�ʵ���ˣ��±߶��Ƕ���ҳ�����ļ����Mһ������

	//���������ٴ�ȷ���ǲ�����ҳ����
	$bodyEndPos = 0;
	if($page['ctype']=='html') $bodyEndPos = strripos($data,'</body>');
	if($bodyEndPos===false) $bodyEndPos=0;
	//�ж��ǲ��Ƕ���(���ڿ���ڡ�����ajax����)����ͨ��ҳ
	$is_top_page=($bodyEndPos>0 && !$page['isframe'] && !$page['isajax'] && !$page['isimport']);

	if($is_top_page){
		//������ַ�����ײ��������ͷ���ͳ��
		$showaddress = @$config['enable_address_bar'];
		$shownav = $bottom_navigation['enable'];
		$code='<script type="text/javascript">if(is_top_win){document.write(\'';
		if($shownav) $code.='<scr\'+\'ipt type="text/javascript" src="/?'.$config['built_in_name'].'='.encrypt_builtin('nav').'" charset="'.APP_CHARSET.'"></scr\'+\'ipt>';
		if($showaddress) $code.='<scr\'+\'ipt type="text/javascript" src="/images/address.js" charset="GBK"></scr\'+\'ipt>';
		$code.='\');';
		$code.="async_get('/?{$config['built_in_name']}=".encrypt_builtin('tj',true)."&_='+(+new Date()));";
		$code.='}</script>';
		$data=substr_replace($data,$code,$bodyEndPos,0);

		//��ԭ��ַ����ĵ�ַ��ȥ�����������������ӵ�β��
		if(!empty($_SERVER['SPARE_REAL_URI'])){
			$data = str_replace_once('</head>', "<script type='text/javascript'>".
			"document.cookie='_sp_=1; expires=' + new Date(new Date().getTime()+60*1000).toGMTString();" .
			"if(history.replaceState) history.replaceState(null, null, '{$_SERVER['REQUEST_URI']}');</script></head>", $data);
		}

		//�ѱ���ֹ�����ӱ�����������ʽ���Է����û�ʶ���ǲ��ܵ����
		if(strpos($data,"/blank/")>0){
			$data = str_replace_once('</head>', "<style type='text/css'>a[href*='/blank/']{color:#999 !important;font-weight:normal !important;text-decoration:line-through !important;}</style></head>", $data);
		}
	}

	//����Զ����ַ���hash
	if($bodyEndPos>0 && $remoteUrl->fragment){
		$data.="<script type='text/javascript'>location.hash='{$remoteUrl->fragment}';</script>";
	}

	//���cdn̽�����
	if($config['cdn_servers'] && $is_top_page && trim($page['cdn'],',')==''){
		$data.=$htmlCoding->getCdnTestCode();
	}

	//��ӵ�����ͳ�ƴ���
	if($is_top_page && !empty($config['analytics'])){
		$data.=$config['analytics'];
	}

	//��¼�����ַ�ķ��ʼ�¼
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

	//�������֩�룬����Ҫ�������ݣ�
	//1.xmlҳ��򵥱��뺺�֣���Ϊ�����javascript��ʽ���ܻ��ƻ�ԭ�и�ʽ
	//2.�ֻ�����htmlʱֻ�򵥱��뺺�֣���Ϊ��Щ�ֻ���֧��javascript�ű�
	//3.��ͨ��������htmlҳ��ʱ����javascript���м���
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

	//��ҳ�����ʱ������1KB/����ٶ�������30����㣩
	if(ENABLE_SET_TIME_LIMIT) set_time_limit(strlen($data)/1024 + 30);

	if(DEBUGING && $error_messages && $page['ctype']=='html'){
		$data .= "/* <div style='padding:10px; margin:10px; border:1px solid #FFB2B6; background-color:#FFE8E7; color:#333; font-size:12px; clear:both; text-align:left;'><pre>".trim($error_messages)."</pre></div> */";
	}

	//��ʾҳ��ִ��ʱ��
// 	if(DEBUGING>=1 && $page['ctype']=='html'){
// 	    $data .= "/* <div style='clear:both;'>page size: " . round(strlen($data)/1024,2) . "kb, load time: " . round(microtime(true)-$start_time,3) . "s</div> */";
// 	}

	//ѹ��
	switch ($config['zlib_output']){
		case 0:
			//��֧��ѹ����ָ��ԭʼ���ȣ�
			header('Content-Length: '.strlen($data));
			echo $data;
			break;
		case 1:
			//�Զ�ѹ������ָ��ԭʼ���Ȼ�ѹ����ĳ��ȣ�ϵͳ���Զ����õģ�
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding');
			echo $data;
			break;
		case 2:
			//�ֶ�ѹ����ָ��ѹ����ĳ��ȣ�
			//��header()���������������һЩͷ����Ϣ��������������ҳ���Ѿ���GZIPѹ�����ˣ�
			/*
			 * $data .= @ob_get_clean();
			 * $data=gzencode($data,6);
			 * header('Content-Encoding: gzip');
			 * header('Vary: Accept-Encoding');
			 * header('Content-Length: '.strlen($data));
			 * echo $data;
			 * break;
			 */
			//�����Ѿ�����zlibѹ�����ˣ�����Ϊ�˿������ܾͲ��ظ�ѹ����
			header('Content-Length: '.strlen($data));
			echo $data;
			break;
	}
}

//�±�������룬��Ϊ�˱�֤��ҳ����ʱ�����Դ
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
// ��Զ�˻򻺴��ȡ��ҳ����Դ
// ================================================================================================

$http = Http::create($config);
if($http===false){
	show_report('server');
	exit;
}
$http->proxy=$config['proxy'];
$http->currentHome=$currentUrl->home;
$http->redirect = $config['cdn_servers']=='' && !$redirect_original;

//��������ͷ
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
//�Զ�������ͷ
if(!empty($config['additional_http_header']) && strpos($config['additional_http_header'],'=')>0){
	$arr=explode('=', $config['additional_http_header'], 2);
	$http->setRequestHeader(trim($arr[0]), trim($arr[1]));
}
if(substr($remoteUrl->host,-12)=='.youtube.com'){
	//ģ��ie8����youtube��վ������ĳЩ�����޷�����ȷ����
	$http->setRequestHeader('user-agent', 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv 11.0) like Gecko');
}

//����Ҫ�ύ�ı�����
if(!empty($_POST)){
	//����POST����
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
//����Ҫ�ύ��json����
elseif(isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'],'/json')!==false){
	$s = file_get_contents('php://input');
	if($s) $http->setPostData($s);
}

//�����ϴ�����
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

//�Ƿ��ȡ�����Լ����������
$http->readCache = $page['readcache'] &&
	(!isset($_SERVER['HTTP_CACHE_CONTROL']) || $_SERVER['HTTP_CACHE_CONTROL']!='no-cache') &&
	(!isset($_SERVER['HTTP_PRAGMA']) || $_SERVER['HTTP_PRAGMA']!='no-cache');
$http->cacheDir = TEMPDIR;
$http->cacheSalt = $page['cachesalt'];
$http->cacheExt = $page['cacheext'];

//��Щ��վ�Ļ�����Ч�ڱȽ����⣬��Ҫ��������
if($http->readCache && $page['ctype']=='html' && strpos($remoteUrl->host,'youtube.')!==false){
	$http->cacheExpire = TIME+3600*4; //��Ϊ��Ƶ��ʵ��ַ����Ч����5Сʱ��
}

//��������
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

//�Ѳ���������ҳ����Ҳ�����
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

//�жϽ��
if(!$result && !$page['responsed']){
	switch ($lastError){
		case 204:
		case 206:
		case 'partial':
			//ֻ��Զ�˽��յ��������ݣ�����Ҳ���ų�����Ϊ���������ص�Content-Length������������ԣ�����������ֻ��ʾ�����������档
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
				//����
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
				exit('�ļ�̫��');
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
