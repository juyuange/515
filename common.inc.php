<?php
/*
 * ����ѡ�
 * 0 ����ʾ������ʾ���ʺ�������������
 * 1 ��ʾ������Ϣ���ʺ��ڵ��Ի�����
 * 2 ��ʾ������Ϣ��������ҳ��ײ���ʾPHPִ��ʱ��
 * �����ò���ʾ������Ϣʱ��������Ϣ�����¼�� /temp/~errors_����.txt �ļ���
 */
define('DEBUGING', isset($_COOKIE['__debug__'])?intval($_COOKIE['__debug__']):0);
//define('DEBUGING', 2);

define('APPDIR', dirname(__FILE__));
define('TEMPDIR', APPDIR.'/temp');
define('DATADIR', APPDIR.'/data');
define('TIME', time());
define('APP_CHARSET', 'GBK');
define('APP_VER', '11'); //����ͻ��˻����ļ��������������г�ͻ������Ҫ�ڴ˸ı��ֵ

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

//��Ҫ������ļ����ͣ��������͵��ļ������ı�����ֱ����ʾ
$supported_content_type = array (
	//��ͨ��ҳ
	'text/html'	=>	'html',
	'text/plain'=>	'html',
	'text/xhtml'=>	'html',
	'text/shtml'=>	'html',
	//��ʽ��
	'text/css'	=>	'css',
	//�ű�
	'text/javascript'			=>	'js',
	'application/x-javascript'	=>	'js',
	'application/javascript'	=>	'js',
	'application/json'			=>	'js',
	'application/json-p'		=>	'js',
	'application/jsonp'			=>	'js',
	//�ֻ�ҳ���xmlҳ�棨����ʹ��javascript���ܣ�
	'text/vnd.wap.wml'			=>	'xml',
	'application/xml'			=>	'xml',
	'application/xml+xhtml'		=>	'xml',
	'application/xhtml+xml'		=>	'xml',
	'application/rss+xml'		=>	'xml',
	'text/xml'					=>	'xml',
);

//imgͼƬ�ļ�
$image_file_exts = ' .jpg .png .gif .ico ';
//������Ӧ����ʾΪ���ص���չ��
$download_file_exts = ' .zip .rar .exe .msi .cab .iso .dll .7z .7zip .bak .pdf .psd .doc .chm .rtf .xls .ppt .apk .asc .jad .jar .xpi .key .bmp ';
//��������Ƶ��������Ƶ������ص��ļ���չ������Щ��չ����url��ת��ʱ����ת��Ϊ��ԭʼ��չ����α��̬��ַ����Ϊ��̬��ַ�����޷���������
$media_file_exts = ' .swf .flv .f4v .mp3 .mkv .mp4 .mpg .mpeg .vob .avi .mov .asf .wmv .wma .rm .ra .ram .rmvb .qt .3gp .aac .m4a .webm .ogv .ogg .m3u .m3u8 .ts .wav ';

//��ȫ�����б���������url����ת����
$safe_domains = array(
	isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'],
	//��ҳ��׼����
	'www.w3.org',
	//���㷺ʹ�õ���������Ĺٷ���վ
	'macromedia.com',
	'adobe.com',
	'jquery.org',
	'jquery.com',
	'jqueryui.com',
	//���㷺ʹ�õĲ��ᱻ��İ�ȫ��վ
	'microsoft.com',
	'paypal.com',
	'github.com',
	'github.io',
	'raw.githubusercontent.com',
	'git.io',
	//���ձ�ʹ�õ�������Ƶ�����б�ı�׼����
	'xspf.org',
);

//��������������Ҫ�ǹ�桢����ͳ�ƺ������޹ؽ�Ҫ�Ĺ��ܣ���Щ��������׶�̬����һЩ�޷���׼ȷת�����ⲿ���ӣ�
$block_domains = array(
	'ad' => array(
		//google������
		'doubleclick.net',
		'googlesyndication.com',
		'google-analytics.com',
		'googletagservices.com',
		'googletagmanager.com',
		'googleadsensetvsite.com',

		//�������
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

	//���������Ĺ��
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

//js�ű���ַ����������Ҫ�ǹ��վ���һЩ�޹ؽ�Ҫ���������ܣ�script��ǩ�����������Щʱ����Щjavascript���ᱻɾ����
$block_javascript = array(
	'ad' => array(
		//google������
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

//�ֻ���վ���ձ�ԭʼ��վ����Ѿ�ʵ�����Զ���ת����վ�����ڴ����� (����û�а���mobile�����ʱ�Ż�������������)
//������pc��վurl�������β��������·����������/��β������ֵ���ֻ���վurl��
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

//��������������ʽ�����������������ڱ��ر����棨js��css���⣩
//���ﲻ�����ù��࣬����������뻺�棬��ֱ�ӹرձ��ػ��湦�ܣ�
$nocache_domains = '(minghui\.org|falundafa\.org|^localhost|^192\.168\.\d+\.\d+)$';
