<?php
/*
 * 调试选项：
 * 0 不显示错误提示（适合于生产环境）
 * 1 显示错误信息（适合于调试环境）
 * 2 显示错误信息，并在网页最底部显示PHP执行时间
 * 当设置不显示错误信息时，错误信息将会记录到 /temp/~errors_年月.txt 文件里
 */
define('DEBUGING', isset($_COOKIE['__debug__'])?intval($_COOKIE['__debug__']):0);
//define('DEBUGING', 2);

define('APPDIR', dirname(__FILE__));
define('TEMPDIR', APPDIR.'/temp');
define('DATADIR', APPDIR.'/data');
define('TIME', time());
define('APP_CHARSET', 'GBK');
define('APP_VER', '11'); //如果客户端缓存文件与升级后内容有冲突，就需要在此改变此值

error_reporting(DEBUGING ? E_ALL : 0);
if(DEBUGING) @ini_set('display_errors', 1);
date_default_timezone_set('Asia/Shanghai');
$start_time = microtime(true);
if(version_compare(PHP_VERSION, '5.3.3', '<')){
	exit('Need PHP 5.3.3 or higher!');
}

if(!isset($_SERVER['HTTP_X_USEING_HTACCESS']) && isset($_SERVER['REDIRECT_HTTP_X_USEING_HTACCESS'])){
	$_SERVER['HTTP_X_USEING_HTACCESS'] = $_SERVER['REDIRECT_HTTP_X_USEING_HTACCESS'];
	$_SERVER['HTTP_X_WAP_PROFILE'] = $_SERVER['REDIRECT_HTTP_X_WAP_PROFILE'];
	$_SERVER['HTTP_IF_MODIFIED_SINCE'] = $_SERVER['REDIRECT_HTTP_IF_MODIFIED_SINCE'];
	$_SERVER['HTTP_IF_MATCH'] = $_SERVER['REDIRECT_HTTP_IF_MATCH'];
	$_SERVER['HTTP_IF_NONE_MATCH'] = $_SERVER['REDIRECT_HTTP_IF_NONE_MATCH'];
}

//需要处理的文件类型，其他类型的文件将不改变内容直接显示
$supported_content_type = array (
	//普通网页
	'text/html'	=>	'html',
	'text/plain'=>	'html',
	'text/xhtml'=>	'html',
	'text/shtml'=>	'html',
	//样式表
	'text/css'	=>	'css',
	//脚本
	'text/javascript'			=>	'js',
	'application/x-javascript'	=>	'js',
	'application/javascript'	=>	'js',
	'application/json'			=>	'js',
	'application/json-p'		=>	'js',
	'application/jsonp'			=>	'js',
	//手机页面和xml页面（不能使用javascript加密）
	'text/vnd.wap.wml'			=>	'xml',
	'application/xml'			=>	'xml',
	'application/xml+xhtml'		=>	'xml',
	'application/xhtml+xml'		=>	'xml',
	'application/rss+xml'		=>	'xml',
	'text/xml'					=>	'xml',
);

//img图片文件
$image_file_exts = ' .jpg .png .gif .ico ';
//常见的应该显示为下载的扩展名
$download_file_exts = ' .zip .rar .exe .msi .cab .iso .dll .7z .7zip .bak .pdf .psd .doc .chm .rtf .xls .ppt .apk .asc .jad .jar .xpi .key .bmp ';
//与在线音频或在线视频播放相关的文件扩展名，这些扩展名的url在转换时都将转换为带原始扩展名的伪静态地址，因为动态地址可能无法正常播放
$media_file_exts = ' .swf .flv .f4v .mp3 .mkv .mp4 .mpg .mpeg .vob .avi .mov .asf .wmv .wma .rm .ra .ram .rmvb .qt .3gp .aac .m4a .webm .ogv .ogg .m3u .m3u8 .ts .wav ';

//安全域名列表（此域名的url都不转换）
$safe_domains = array(
	isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'],
	//网页标准声明
	'www.w3.org',
	//被广泛使用的组件和类库的官方网站
	'macromedia.com',
	'adobe.com',
	'jquery.org',
	'jquery.com',
	'jqueryui.com',
	//被广泛使用的不会被封的安全网站
	'microsoft.com',
	'paypal.com',
	'github.com',
	'github.io',
	'raw.githubusercontent.com',
	'git.io',
	//被普遍使用的在线视频播放列表的标准声明
	'xspf.org',
);

//域名黑名单（主要是广告、访问统计和其他无关紧要的功能，这些代码很容易动态生成一些无法被准确转换的外部链接）
$block_domains = array(
	'ad' => array(
		//google广告相关
		'doubleclick.net',
		'googlesyndication.com',
		'google-analytics.com',
		'googletagservices.com',
		'googletagmanager.com',
		'googleadsensetvsite.com',

		//其他广告
		'888media.net',
		'adnxs.com',
		'evergage.com',
		'realmedia.com',
		'openx.net',
		'tribalfusion.com',
		'exponential.com',
		'zedo.com',
		'crwdcntrl.net',
		'2mdn.net',
	),

	//被封域名的广告
	'ads.ntdtv.com',

	'fastclick.net',
	'quantserve.com',
	'rubiconproject.com',
	'scorecardresearch.com',
	'sharethis.com',
	'addthis.com',
	'statcounter.com',
	'static.chartbeat.com',
	'cpro.baidu.com',
	'media.line.me',
);

//js脚本网址黑名单（主要是广告站点和一些无关紧要的外来功能，script标签属性里出现这些时，这些javascript将会被删除）
$block_javascript = array(
	'ad' => array(
		//google广告相关
		'pagead/show_ads.js',
		'google_ad_client',
		'pagead/js/adsbygoogle.js',
		'/google_service.js',
		'www.google.com/pagead/',
	),

	'apis.google.com/js/plusone.js',
	'mhstats.php',
	'quantserve.com/quant.js',
	'statcounter.com/counter/counter.js',
	'platform.twitter.com/widgets.js',
	'cloudfront.net/atrk.js',
	'google-analytics.com/analytics.js',
	'media.line.me/js/line-button.js',
	'epochtimes.com/js/Djy/DongtaiwangHomepage.js'
);

//手机网站对照表，原始网站如果已经实现了自动跳转的网站无需在此设置 (仅当没有包含mobile版程序时才会启用下述设置)
//键名是pc网站url（如果结尾是域名或路径，必须以/结尾），键值是手机网站url，
$mobile_domains = array(
	'http://www.aboluowang.com/indext.html'		=>'http://www.aboluowang.com/wap',
	'http://www.aboluowang.com/'				=>'http://www.aboluowang.com/wap',
	'http://www.ntdtv.com/'						=>'http://m.ntdtv.com/',
	'http://www.ntdtv.com/xtr/gb/index.html'	=>'http://m.ntdtv.com/',
	'http://www.secretchina.com/'				=>'http://m.kanzhongguo.com/',
	'http://www.minghui.org/'					=>'http://m.minghui.org/',
	'http://cn.epochtimes.com/'					=>'http://m.epochtimes.com/',
	'http://cn.epochtimes.com/gb/ncnews.htm'	=>'http://m.epochtimes.com/',
	'http://www.youtube.com/'					=>'http://m.youtube.com/',
	'http://www.youmaker.com/'					=>'http://m.youmaker.com/video/index.html?code=gb',
	'http://www.youmaker.com/video/index.html?code=b5&c'=>'http://m.youmaker.com/video/index.html?code=gb',
	'http://www.youmaker.com/video/indexb5.html'		=>'http://m.youmaker.com/video/index.html?code=gb',
	'http://www.youmaker.com/video/index.html?code=gb'	=>'http://m.youmaker.com/video/index.html?code=gb',
	'http://www.youmaker.com/video/indexgb.html'		=>'http://m.youmaker.com/video/index.html?code=gb',
	'http://wujieliulan.com/download.php'				=>'http://m.wujieliulan.com/index.html#downloadurl'
// 	'http://dongtaiwang.com/loc/phome.php'				=>'http://dongtaiwang.com/loc/mobile/',
// 	'http://www.dongtaiwang.com/loc/phome.php'			=>'http://dongtaiwang.com/loc/mobile/',
// 	'http://www.dongtaiwang.com/loc/download.php'		=>'http://dongtaiwang.com/loc/mobile/download.php',
);

//符合以下正则表达式的域名，将不会在在本地被缓存（js和css除外）
//这里不宜设置过多，如果多数不想缓存，请直接关闭本地缓存功能，
$nocache_domains = '(minghui\.org|falundafa\.org|^localhost|^192\.168\.\d+\.\d+)$';
