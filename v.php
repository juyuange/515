<?php

/*
 *
===========================================================================================

v.php 主要是提供给app進行调用，存在的调用参数为：
1. api，接口名称，支持的列表如下：
　　　id  【缺省值】从id或id2参数获取影音文件地址，必需id或id2参数
　　　szzd  从github获取神州正道变形域名，可选id或id2参数
　　　url  从url或url2参数获取要处理的域名或网址，必需url或url2参数
　　　og  获取网门的动态ip
　　　or  获取几大媒体网站的新闻页的网门加密地址，如果没有url和url2参数就默认获取网门的实时网址
　　　dns  解析指定域名的ip，必需url或url2参数
　　　isats 当前域名是否是ATS
     ntd1 指定新唐人直播频道的地址，必需id、url和key这三个参数，id和url的可选值请看下边$urllist变量里的新唐人直播部分，key是固定的值 wleU5_Il474HD_l1kEll
     ntd2 指定新唐人直播是否只能从中国大陆播放，可选onlycn参数，onlycn的值为on或off（默认为off），key是固定的值 wleU5_Il474HD_l1kEll
2. id或id2，指定影音文件编号
　　　使用id参数指定影音文件的编号
　　　使用id2参数指定经过加密的编号
3. url或url2，指定要处理的域名或网址
　　　使用url参数指定包含http://或https://的完整网址，网址需要先進行urlencode编码
　　　使用url2参数指定经过加密的域名或网址
4. action，内容输出方式，支持的列表如下（当api=id时缺省值是show否则就是encrypt）：
　　　show  显示在线播放器影音页面，如果是多个网址将会显示播放列表，也可以使用index参数（以1开始）指定第几条
　　　text  显示未加密内容，如果是多个网址将会用换行符间隔
　　　encrypt 如果是多个网址将会用换行符间隔
　　　redirect  跳转到指定网址，如果是多个网址将会随机选择一个
　　　xhr.so  把网址在xhr.so网站生成短网址，如果是多个网址将会随机选择一个
　　　xhr.so.qrcode  把网址在xhr.so网站生成短网址并生成二维码，如果是多个网址将会随机选择一个
　　　qrcode  生成网址二维码，如果是多个网址将会随机选择一个

上述几个参数可以理解为：api指定数据获取方式，id、id2、url和url2按照api指定的方式提供数据，最后由action参数指定内容显示方式

另外，还有几个辅助参数，会在各接口的描述里说明：
1. random=on
2. onlycn=on或off (默认是off)
3. index=数字
4. uri=网址路径部分，当action为redirect、xhr.so、xhr.so.qrcode、qrcode 这几个时，支持使用 uri 参数添加网址路径，例如 uri=/1/
5. ifisats=0或者1，当action为redirect时，如果 即将跳转的域名/v.php?api=isats 返回的内容跟ifisats参数值相同，就跳转，否则就返回403错误

最后，为了加强保密性，再提供一个更为隐秘的调用方式，把未加密的调用参数组合好之后，再加密，然后作为code参数的参数值
例如，想调用 /v.php?api=szzd&action=xhr.so.qrcode
就把 api=szzd&action=xhr.so.qrcode 加密然后组合为 /v.php?code=【加密结果】

===========================================================================================

主要调用场景如下：
1. 播放影音，调用方式 /v.php?id=【影音编号】或 /v.php?id=【加密的影音编号】，支持的影音编号如下：
　　1). 内置影音列表：
　　　　ntd: 新唐人中国频道直播
　　　　ntdmd: 新唐人美东频道直播
　　　　ntdmx: 新唐人美西频道直播
       ntd2: 新唐人亚太台
　　　　其他有效列表如下：
			新唐人电视 中国台
			ntd-cnlive150  http://cnhls.ntdtv.com/cn/live150/playlist.m3u8
			ntd-cnlive400  http://cnhls.ntdtv.com/cn/live400/playlist.m3u8
			ntd-cnlive800  http://cnhls.ntdtv.com/cn/live800/playlist.m3u8
			新唐人电视 亚太台
			ntd-mlt        http://intd.ntdtv.com.tw/mlt/playlist.m3u8
			ntd-aplive200  http://live.ntdimg.com/aplive200/playlist.m3u8
			ntd-aplive400  http://live.ntdimg.com/aplive400/playlist.m3u8
			新唐人电视 美东台
			ntd-live200    http://live.ntdimg.com/live200/playlist.m3u8
			ntd-live400    http://live.ntdimg.com/live400/playlist.m3u8
			新唐人电视 美西台
			ntd-uwlive280  http://live.ntdimg.com/uwlive280/playlist.m3u8
			ntd-uwlive520  http://live.ntdimg.com/uwlive520/playlist.m3u8
			新唐人电视 旧金山台
			ntd-sflive220  http://live.ntdimg.com/sflive220/playlist.m3u8
			ntd-sflive440  http://live.ntdimg.com/sflive440/playlist.m3u8
			新唐人电视 加东台
			ntd-mllive220  http://live.ntdimg.com/mllive220/playlist.m3u8
			新唐人电视 加西台
			ntd-cwlive220  http://live.ntdimg.com/cwlive220/playlist.m3u8
			新唐人电视 休斯顿台
			ntd-htlive480  http://live.ntdimg.com/htlive480/playlist.m3u8
　　　　1400: 1400例真相
　　　　zf: 天安门自焚真相
　　　　jp: 九评（以半角逗号分隔的9个视频地址）
　　　　fy: 风雨天地行（以半角逗号分隔的6个视频地址）
　　2). 网门视频地址（单集），比如 3EC/WH.mp4 或 4EC/JP1.mp4，当前播放的是神州正道资源站
　　3). 网门视频地址（多集），比如 4EC/JP.mp4|9，当前播放的是神州正道资源站
　　4). youtube视频的ID，比如 g0S0_7bjHCc
　　对于1)和3)，如果是多个影音，将会显示播放列表，也可以通过指定 &index=【以1开始的序号】只播放某一集
　　控制是否限制只能大陆用户播放，onlycn=on限制，onlycn=off不限制（默认）
2. 播放影音文件的完整网址，调用方式 /v.php?id2=【加密的影音文件网址】
3. 获取github上的神州正道的变形域名（调用方式 /v.php?api=szzd）或者长效域名（调用方式 /v.php?api=szzd&id=longacting.value），
      也可以通过指定random=on参数，把每个域名的最后一节变为随机内容，比如把 aaa.ogdata.bid 随机变为 kdheihd.ogdata.bid
      也可以通过指定index=以1起始的数字，指定某一条记录
4. 获取github上的神州正道的破网信息，调用方式 /v.php?api=szzd&id=【json路径】或 /v.php?api=szzd&id=【加密的json路径】，json路径里的字段间用.分隔
5. 显示指定的群发网址的各种形式 /v.php?api=url&url2=加密(【群发网址】)&action=【action参数】
6. 查询网门动态服务器地址，调用方式 /v.php?api=og
7. 把几大媒体网站的新闻页转换为网门用于群发的在大陆网站生成的二维码，调用方式 /v.php?api=or&url2=【加密后的新闻页网址】
8. 获取网门的实时网址，调用方式 /v.php?api=or

===========================================================================================

调用举例：
1. 新唐人中国频道，播放页 /v.php?id=ntd ，m3u8播放列表网址 /v.php?id=ntd&action=text
2. 播放youtube神韵介绍 /v.php?id=g0S0_7bjHCc
3. 播放风雨天地行列表 /v.php?id=fy
4. 播放九评第2集 /v.php?id=jp&index=2
5. 其他实例
　　/v.php?api=szzd                       显示神州正道变形域名列表的加密结果
　　/v.php?api=szzd&action=text           显示神州正道变形域名列表的明文
　　/v.php?api=szzd&action=redirect       随机跳转到某一个神州正道变形域名
　　/v.php?api=szzd&action=qrcode         生成神州正道某一个变形域名的二维码
　　/v.php?api=szzd&action=xhr.so         生成神州正道某一个变形域名的短网址
　　/v.php?api=szzd&action=xhr.so.qrcode  生成神州正道某一个变形域名的短网址的二维码
　　/v.php?api=szzd&id=shorturl.value     获取神州正道存储在github的变形域名的短网址
　　/v.php?code=2fd5e73f0a8786ec6b6912bd5c7d487e8fd304b86fc47ad5a4c846774100138b8a 上行（生成神州正道某一个变形域名的短网址的二维码）的更加隐秘的调用方式
　　/v.php?api=dns&url2=加密(fo04.ogdata.bid)  解析fo04.ogdata.bid域名的ip地址
　　/v.php?api=url&url2=加密(http://【群发域名】/1/)&action=xhr.so.qrcode  生成此群发域名的动态网首页的短网址的二维码

===========================================================================================

其他说明：
1. 在线影音播放页面在部分手机浏览器里存在一些缺陷：
　　1) 无法自动开始播放
　　2) 无法自动進入全屏模式（全屏时才会自动横屏显示），暂时只能做到整页显示，点击播放器工具栏的全屏按钮才能全屏
3. 对于类似 3EC/WH.mp4 这样的网门视频，网门的视频文件基本都已经不存在了，现在播放的都是神州正道资源站的影音，资源站里没有的影音就无法播放了
4. 每隔30秒会在当前网页里记录播放位置，如果遇到网络故障中断时，刷新网页将会自动从最近位置开始播放
5. 如果要补充其他视频，只需要修改 v.php 里边的视频列表就行，不过要注意格式别错了
6. 当指定了id或id2参数时，如果没指定action参数就显示在线播放页面，也可以指定 action 参数显示为别的方式，比如
　　如果 &action=text 就只显示影音文件地址，是不包含域名部分的地址，如果多集就返回换行符分割的多行
　　如果 &action=redirect 就会跳转到影音文件地址

*/

//=== 直播或mp4视频的网址 ========================================================================================
$urllist = array(
	//新唐人（ntd: 中国台，ntdmd: 美东台，ntdmx: 美西台，ntd2: 亚太台）（只能有1个网址，不能是逗号分隔的多个网址）
	//中国台
    'ntd' =>           'http://cnhls.ntdtv.com/cn/live400/playlist.m3u8',
    'ntd-cnlive150' => 'http://cnhls.ntdtv.com/cn/live150/playlist.m3u8',
    'ntd-cnlive400' => 'http://cnhls.ntdtv.com/cn/live400/playlist.m3u8',
    'ntd-cnlive800' => 'http://cnhls.ntdtv.com/cn/live800/playlist.m3u8',
    'ntd-cnlive150_bak' => 'http://cnhls.ntdtv.com/cn/live150/first.m3u8',
    'ntd-cnlive400_bak' => 'http://cnhls.ntdtv.com/cn/live400/first.m3u8',
    'ntd-cnlive800_bak' => 'http://cnhls.ntdtv.com/cn/live800/first.m3u8',
    //亚太台
    'ntd2' =>          'http://intd.ntdtv.com.tw/mlt/playlist.m3u8',
    'ntd-mlt' =>       'http://intd.ntdtv.com.tw/mlt/playlist.m3u8',
    'ntd-aplive200' => 'http://live.ntdimg.com/aplive200/playlist.m3u8',
    'ntd-aplive400' => 'http://live.ntdimg.com/aplive400/playlist.m3u8',
    //美东台
    'ntdmd' =>         'http://live2.ntdimg.com/live330/playlist.m3u8',
    'ntd-live200' =>   'http://live.ntdimg.com/live200/playlist.m3u8',
    'ntd-live400' =>   'http://live.ntdimg.com/live400/playlist.m3u8',
    //美西台
    'ntdmx' =>         'http://live.ntdimg.com/uwlive520/playlist.m3u8',
    'ntd-uwlive280' => 'http://live.ntdimg.com/uwlive280/playlist.m3u8',
    'ntd-uwlive520' => 'http://live.ntdimg.com/uwlive520/playlist.m3u8',
    //旧金山台
    'ntd-sflive220' => 'http://live.ntdimg.com/sflive220/playlist.m3u8',
    'ntd-sflive440' => 'http://live.ntdimg.com/sflive440/playlist.m3u8',
    //加东台
    'ntd-mllive220' => 'http://live.ntdimg.com/mllive220/playlist.m3u8',
    //加西台
    'ntd-cwlive220' => 'http://live.ntdimg.com/cwlive220/playlist.m3u8',
    //休斯顿台
    'ntd-htlive480' => 'http://live.ntdimg.com/htlive480/playlist.m3u8',

	//1400例真相
	'1400' => 'http://inews3.ntdtv.com/data/media/2012/5-15/N__NTDSpecial-5089_JieXiLi_P637960.mp4',
	//天安门自焚真相
	'zf' => 'http://inews3.ntdtv.com/data/media/2012/12-9/F_RV0000061_A_F_RV_Other-1_WeiHuoZhongWenBan_P609220.mp4', //'3EC/WH.mp4',
	//九评（半角逗号分隔的9个视频地址）
	'jp' => 'http://inews3.ntdtv.com/data/media2/2015/08-23/JLP_s0_e1_v1_i0-JPGCD_1-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-04/JLP_s0_e2_v1_i0-JPGCD_2-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-06/JLP_s0_e3_v1_i0-JPGCD_3-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-09/JLP_s0_e4_v1_i0-JPGCD_4-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/07-21/JLP_s0_e5_v1_i0-JPGCD_5-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/03-19/JLP_s0_e6_v1_i0-JPGCD_6-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/02-08/JLP_s0_e7_v1_i0-JPGCD_7-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/03-03/JLP_s0_e8_v1_i0-JPGCD_8-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/07-30/JLP_s0_e9_v1_i0-JPGCD_9-video.mp4', //'4EC/JP.mp4|9',
	//风雨天地行（半角逗号分隔的6个视频地址）
	'fy' => 'http://inews3.ntdtv.com/data/media2/2015/08-12/ZXJM_s2_e1_v1_i0-FYTDX_1-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-19/ZXJM_s2_e2_v1_i0-FYTDX_2-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-19/ZXJM_s2_e3_v1_i0-FYTDX_3-video.mp4,
		http://inews3.ntdtv.com/data/media/2012/3-26/F_RV0000130_A_F_RV_Other-4-4_FengYuTianDiXin_P637505.mp4,
		http://inews3.ntdtv.com/data/media2/2015/09-02/ZXJM_s2_e5_v1_i0-FYTDX_5-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/09-02/ZXJM_s2_e6_v1_i0-FYTDX_6-video.mp4', //'3EC/TDX.mp4|6',
	//第2种视频地址的举例
	'demo' => '/video/video/demo.mp4',
);

define('OGATE_OR_API_URL', 'http://ogate.org/oo.aspx?ob=or&op=geturl&from=SzzdOgate&key=j0f2j8j8fex3&ag=');
define('OGATE_OR_API_DEFAULT_AG', 'c816667');
//define('GITHUB_SZZD_JSON', 'https://raw.githubusercontent.com/shenzhouzd/update/master/data.json');
//define('GITHUB_SZZD_JSON', 'https://raw.githubusercontent.com/chuan12/shenzhouzd/master/data.js?token=AkphYbJ1DRzmaD88-sWyZNvI-OOmeY6bks5a3fd6wA%3D%3D');
define('GITHUB_SZZD_JSON', 'https://raw.githubusercontent.com/chuan12/shenzhouzd/master/data.json?token=AkphYRR2nZFtPnqHtagtY4LeTqpg_b-dks5a3l0owA%3D%3D');
define('GITHUB_SZZD1_URL', 'https://raw.githubusercontent.com/szzd1/1/master/README.md');

//=== 初始化 ========================================================================================

require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/func.inc.php');
require(APPDIR.'/include/http.inc.php');
require(APPDIR.'/include/coding.inc.php');

//初始化
init_config();
define('API_KEY', 'wleU5_Il474HD_l1kEll');
define('NTD_CUSTOM_FILE', DATADIR . '/ntd_custom.dat');
define('NTD_ONLYCN_FILE', DATADIR . '/ntd_onlycn.dat');
require(APPDIR.'/plugin/szzd.php');

//=== 获取参数 ========================================================================================

$api = $id = $url = $action = $index = $random = $uri = '';
$v_type = $v_url = $v_img = '';
$ifisats = null;
$onlycn = isset($_GET['onlycn']) && $_GET['onlycn']=='on' ? 'on' : 'off';

if(!empty($_GET['code'])){
	parse_str(juyuange_decrypt($_GET['code']));
	if(isset($key)) $_GET['key'] = $key;
	if(isset($onlycn)) $_GET['onlycn'] = $onlycn;
	if(!$api) $api = 'id';
}else{
	$action = empty($_GET['action']) ? '' : $_GET['action'];
	$api = empty($_GET['api']) ? 'id' : $_GET['api'];
	$index = isset($_GET['index']) ? intval($_GET['index']) : null;
	$uri = isset($_GET['uri']) ? $_GET['uri'] : '';
	$random = !empty($_GET['random']);
	if(isset($_GET['ifisats'])){
		$ifisats = !empty($_GET['ifisats']) ? '1' : '0';
	}

	if(!empty($_GET['id'])){
		$id = $_GET['id'];
	}elseif(!empty($_GET['id2'])){
		$id = juyuange_decrypt($_GET['id2']);
	}

	if(!empty($_GET['url'])){
		if(strpos($_GET['url'],'://')!==false || preg_match('#^[\w+\.\-]+$#', $_GET['url']) || preg_match('#^[\w+\.\-]+?\.\w+$#', $_GET['url'])){
			$url = $_GET['url'];
		}else{
			$url = urlsafe_base64_decode(urlsafe_base64_decode($_GET['url']));
		}
	}elseif(!empty($_GET['url2'])){
		$url = juyuange_decrypt($_GET['url2']);
	}else{
		$url = '';
	}
}

//=== api ========================================================================================

switch($api){
	case 'og':
		//网门的动态网址
		$v_url = get_og_server();
		if(empty($v_url)){
			show_error(500, '暂无可用节点');
		}
		break;
	case 'szzd':
		if(empty($id)) $id='domain.value';
		$arr = fetch_szzd($id);
		if(!empty($arr)){
			if(in_array($id,array('domain.value','longacting.value')) && $random){
				//神州正道变形域名，随机变化每个域名的最后一节
				foreach($arr as $k=>$v){
				    if(preg_match('#^(https?://)?([\d\.:]+)$#', $v, $match)){
				        //ip地址（需要排除吗？）
				    }elseif(preg_match('#^(https?://)?([\w\-]+)(\.[\w\-\.:]+)$#', $v, $match)){
				        $arr[$k] = $match[1] . rand_string(6, 10, RANDSTR_LN, false) . $match[3];
					}
				}
			}
			$v_url = $arr;
		}
		break;
	case 'or':
		//网门新闻页
		$v_url = fetch_or($url ? $url : OGATE_OR_API_DEFAULT_AG);
		break;
	case 'id':
		//根据id参数查询视频
		if(!$action) $action='show';
		get_video_from_params();
		break;
	case 'url':
		if($url){
			$v_url = $url;
		}else{
			show_error(501, 'url参数值错误');
		}
		break;
	case 'dns':
		if($url){
			$v_url = resolve($url, 5, 2);
			if(!$v_url || $v_url==$url){
				show_error(500, 'failed');
			}
		}else{
			show_error(501, 'url参数值错误');
		}
		break;
	case 'isats':
		echo !empty($config['is_behind_ats']) ? 1 : 0;
		exit;
	case 'ntd1':
	    //指定新唐人直播频道的地址
	    //if($_GET['key']!=API_KEY || strpos($id,'ntd')!==0 || strpos($url,'.m3u8')===false || !isset($urllist[$id]) || array_search($url,$urllist)===false){
	    if($_GET['key']!=API_KEY || strpos($id,'ntd')!==0 || !preg_match('#^https?://\w+\.(ntdtv\.com|ntdtv\.com\.tw|ntdimg\.com)/[^\?]+?\.m3u8#', $url)){
	        show_error(501, '参数值错误');
	    }
	    $arr = file_exists(NTD_CUSTOM_FILE) ? unserialize(file_get_contents(NTD_CUSTOM_FILE)) : array();
	    $arr[$id] = $url;
	    file_put_contents(NTD_CUSTOM_FILE, serialize($arr));
	    //反馈
	    echo 'OK';
	    exit;
	case 'ntd2':
	    //设置是否只能大陆访问
	    if($_GET['key']!=API_KEY || !isset($_GET['onlycn']) || !in_array($_GET['onlycn'],array('on','off'))){
	        show_error(501, '参数值错误');
	    }
	    if($onlycn=='on' && !file_exists(NTD_ONLYCN_FILE)){
            file_put_contents(NTD_ONLYCN_FILE, 'ON');
	    }elseif($onlycn=='off' && file_exists(NTD_ONLYCN_FILE)){
	        unlink(NTD_ONLYCN_FILE);
	    }
	    //反馈
	    echo 'OK';
	    exit;
	default:
		show_error(501, 'api参数值错误');
}
if(empty($v_url)){
	show_error(500, 'API返回空值');
}


//=== 输出 ========================================================================================

if(is_string($v_url) && strpos($v_url,"\n")>0){
	$v_url = explode("\n",$v_url);
}
if($index && is_array($v_url) && isset($v_url[$index-1])){
	$v_url = array_slice($v_url, $index-1, 1);
}

if($action!='show'){
	switch($action){
		case 'file': //【仅用于先前兼容】
		case 'text':
			//显示文本形式的结果
			if(!empty($uri)){
				$v_url = add_scheme_and_uri(is_array($v_url) ? $v_url[array_rand($v_url)] : $v_url);
			}else{
				$v_url = is_array($v_url) ? implode($v_url,"\n") : $v_url;
			}
			echo $v_url;
			break;
		case 'redirect':
			//随机跳转
			$url = add_scheme_and_uri(is_array($v_url) ? $v_url[array_rand($v_url)] : $v_url);
			if($ifisats!==null && preg_match('#^(https?://)([\w\.\-:]+)(/|$)#', $url, $match)){
				if(Url::getCurrentSite()==$match[2]){
					$match_ifisats = $ifisats==(!empty($config['is_behind_ats']) ? '1' : '0');
				}else{
					$match_ifisats = $ifisats==http_get("{$match[1]}{$match[2]}/v.php?api=isats");
				}
				if(!$match_ifisats){
					show_error(403);
				}
			}
			if(empty($config['is_behind_ats'])){
				header('Location: ' . $url);
			}else{
				echo "<!DOCTYPE html><html><head><meta http-equiv='refresh' content='0;url={$url}'></head><body><script type='text/javascript'>window.location.href='{$url}';</script></body></html>";
			}
			break;
		case 'xhr.so':
		case 'xhr.so.qrcode':
			$v_url = add_scheme_and_uri(is_array($v_url) ? $v_url[array_rand($v_url)] : $v_url);
			$v_url = shorturl_xhr_so($v_url);
			if(empty($v_url)){
				show_error(500, '获取短网址失败');
			}elseif($action=='xhr.so.qrcode'){
				require 'include/phpqrcode.inc.php';
				QRcode::png($v_url, false, QR_ECLEVEL_L, 6, 2);
				break;
			}else{
				echo $v_url;
				break;
			}
		case 'qrcode':
			//随机显示一个网址的二维码
			$v_url = add_scheme_and_uri(is_array($v_url) ? $v_url[array_rand($v_url)] : $v_url);
			require 'include/phpqrcode.inc.php';
			QRcode::png($v_url, false, QR_ECLEVEL_L, 6, 2);
			break;
		case 'encrypt':
			//继续:
		default:
			//显示加密后的结果
			$v_url = is_array($v_url) ? implode($v_url,"\n") : $v_url;
			echo juyuange_encrypt($v_url);
			break;
	}
	exit;
}

$file = $playlist = $type = '';
if($v_type=='m3u8' && is_string($v_url)){
    $file="{$v_url}";
}elseif($v_type=='mp4' && is_string($v_url)) {
    $file="{$v_url}";
    $type='mp4';
}elseif($v_type=='mp4' && is_array($v_url)){
    $playlist=array();
    foreach($v_url as $k=>$v){
        $num=$k+1;
        $playlist[]="{sources:[{file:'{$v}'}],title:'第{$num}集'}";
    }
    $playlist=implode(',',$playlist);
    $playlist="[{$playlist}]";
}

//=== 在线播放影音 ========================================================================================

header('Content-Type: text/html; charset=GBK');
?>
<!DOCTYPE html><!-- saved from url=(0019)about:trusted sites --><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=GBK" />
<meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<meta name="format-detection" content="telephone=no">
<title>视频播放</title>
<style type="text/css">
html,body{margin:0;padding:0;border:none;width:100%;height:100%;}
#myvideo{width:100%;height:100%;text-align:center;}
#tips{display:none;position:absolute;z-index:999;left:5px;top:5px;font-size:12px;color:#FF0000;color:#FF0000;background-color:rgb(238,238,236);padding:2px 5px;border:1px solid #CCC;}
</style>
</head>
<body>
<div id="tips">若无法播放，请半分钟后刷新试试 <button onclick="closetips();">知道了</button></div>
<div id="myvideo"><br/><br/><br/>正在加载播放器，请稍候...</div>
<script type="text/javascript" src="/images/jwplayer.js" noproxy></script>
<script type="text/javascript">
function closetips(){
	document.cookie='clst=1';
	document.getElementById('tips').style.display="none";
}
if(document.cookie.indexOf('clst=1')===-1) document.getElementById('tips').style.display="block";

//起始播放位置
var startIndex=startPos=currIndex=lastPos=0,
	divid=dividPlayer='myvideo',
	autostart=true,
	activeLI=null,
	hiddentips=false,
	playlist=<?php echo $playlist && $playlist!='[]' ? $playlist : 'null' ?>,
	arr=location.hash.substr(1).split(',');

if(arr.length=2){
	startIndex=parseInt(arr[0],10);
	if(startIndex<0) startIndex=0;
	startPos=parseInt(arr[1],10);
	if(startPos<0) startPos=0;
}

if(playlist){
	setPlayerMenu('', '<?php echo md5_16($playlist); ?>', '100%', '100%', divid, null);
	dividPlayer=divid+'-player';
}

jwplayer(dividPlayer).setup({
	file:'<?php echo $file; ?>',
	type:'<?php echo $type; ?>',
	'playlist':playlist,
	image: '<?php echo $v_img; ?>',
	width: '100%',
	height: '100%',
	stretching: 'fill',
	primary: 'html5',
	fallback: false,
	onlycn: <?php echo $onlycn=='on'?'yes':'false';?>,
	fullscreen: true,
	allowscriptaccess: 'always',
	'autostart': autostart,
	analytics: {enabled:false, cookies:false},
	events: {
		onReady: function() {
			if(playlist){
				this.addButton("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAQBAMAAADkNkIoAAAAA3NCSVQICAjb4U/gAAAAIVBMVEX///////////////////////////////////////////9/gMdvAAAAC3RSTlMAETNVZneIu8zd/2eLAAkAAAAJcEhZcwAACxIAAAsSAdLdfvwAAAAWdEVYdENyZWF0aW9uIFRpbWUAMTAvMjUvMTX2GIFiAAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M26LyyjAAAAD5JREFUCJljWNXgtQoCGFY1RMGY5Qnm5RDAgATSHNTSIIBh1YQoLNoEBQShAJmJpABJG5JhSADJYmRtcEcCAMgwQm1GkmHzAAAAAElFTkSuQmCC",
						"&#26174;&#31034;&#25773;&#25918;&#21015;&#34920;",
						function() {document.getElementById(divid + '-list').style.display = 'block';},
						'menu');
			}
			if(startIndex || startPos){
				try{
					if(startIndex>0) this.playlistItem(startIndex);
					if(startPos>0) this.seek(startPos);
					this.play(true);
					this.setFullscreen(true);
				}catch(e){}
				startIndex=0;
			}else if(autostart){
				this.play(true);
			}
		},
		onPlaylist: function(a){
			var ul = document.getElementById(divid + '-ul');
			if(!ul) return false;

			var html = '',
				arr = a.playlist,
				index = 0;
			for (var k in arr) {
				var v = arr[k];
				html += '<li id="' + divid + '-li-' + k + '"><a id="' + divid + '-a-' + k + '" href="javascript:" rel="' + k + '">' + (parseInt(k) + 1) + '. ' + v.title + '</a></li>';
			}
			ul.innerHTML = html;

			for (var k in arr) {
				var a = document.getElementById(divid + '-a-' + k);
				a.player = this;
				a.onclick = function() {
					this.player.stop();
					this.player.playlistItem(this.rel);
				}
			}

			if (index > 0) this.playlistItem(index);
			if (!autostart) this.stop();
		},
		onPlaylistItem: function(index, playlist) {
			currIndex=index.index;
			var item = document.getElementById(divid + '-li-' + index.index);
			if(!item) return false;
			if (activeLI) activeLI.className = '';
			activeLI = item;
			activeLI.className = 'active';
		},
		onTime: function(e) {
			if(startPos-e.position>15){
				this.seek(startPos);
			}else if(Math.abs(e.position-lastPos)>30) {
				lastPos=Math.floor(e.position);
				location.hash=currIndex+','+lastPos;
			}
			startPos = 0;
		},
		onPlay: function(callback){
			if(!hiddentips) {
				setTimeout(function(){
					hiddentips=true;document.getElementById('tips').style.display="none";
				},5000);
			}
		}
	},
	clapprEvents: {
		//onReady: function() { },
	    onPlay: function() {
			if(!hiddentips) {
				setTimeout(function(){
					hiddentips=true;document.getElementById('tips').style.display="none";
				},5000);
			}
		}
	}
});
</script>
</body>
</html>

<?php

//=== 支撑函数 ========================================================================================

function get_video_from_params(){
	global $config, $urllist, $v_type, $v_url, $v_img, $id, $index;
	if(empty($id)) show_error(501, '调用参数错误');

	if(isset($urllist[$id])){
		//视频列表
	    $url = trim($urllist[$id]," \t\n\r\0,");
		//载入自定义直播地址
		if(file_exists(NTD_CUSTOM_FILE)){
		    $arr = unserialize(file_get_contents(NTD_CUSTOM_FILE));
		    if(is_array($arr) && isset($arr[$id])){
		        $url = trim($arr[$id]);
		    }
		}
		//去除空格
		$url = preg_replace('#\s+#', '', $url);
	}elseif(preg_match('#^[\w\-\.]{5,15}$#', $id)){
		//youtube视频地址
		$v_type = 'mp4';
		$v_url = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytb_{$id}");
		$v_img = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytbimg_{$id}");
		return;
	}elseif(preg_match('#^[\w\-\.]{20,50}$#', $id)){
		//youtube视频列表地址
		$v_type = 'mp4';
		$v_url = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytbl_{$id}");
		return;
	}elseif(preg_match('#^\w{2,5}/\w{2,10}\.\w{2,4}(\||$)#', $id)){
		//网门视频
		$url = $id;
	}elseif(strlen($id)>16){
		//【仅用于先前兼容】编码后的网址
		$url = urlsafe_base64_decode(urlsafe_base64_decode($id));
	}else{
		$url = false;
	}
	if(!$url){
		show_error(501, '调用参数错误');
	}

	//视频列表
	if($url){
	    if(preg_match('#(ntdtv|ntdimg).+?/playlist\.m3u8#', $url)){
	        $v_img = "/images/ntd.jpg";
	    }

		$url = trim($url,"\r\n\t |,");
		$v_type = preg_match('#\.(\w{2,4})(\?|\r|\n|\|\d+\|?$|$)#', $url, $match) ? $match[1] : 'mp4';

		$arr = array();
		if(preg_match('#^(.+?)(\.\w{2,4})\|(\d+)\|?$#', $url, $match)){
			for($i=1; $i<=intval($match[3]); $i++){
				$arr[$i] = "{$match[1]}{$i}{$match[2]}";
			}
		}else{
			$arr = preg_split('#[\r\n\t,]+#', $url, 0, PREG_SPLIT_NO_EMPTY);
		}
		if($index && isset($arr[$index-1])){
			$arr = array_slice($arr, $index-1, 1);
		}

		$v_url = array();
		$currentUrl = Url::getCurrentUrl();
		$remoteUrl = null;
		$urlCoding = new UrlCoding($currentUrl, $remoteUrl);
		foreach($arr as $k=>$v){
			if(preg_match('#^(\w{2,5}/\w{2,10})(\.\w{2,4})$#', $v, $match)){
				$v_url[] = '/res-' . juyuange_encrypt('ogate/'.$match[1]) . $match[2];
			}else{
				$v_url[] = $urlCoding->encodeUrl($v, 'media', null, true);
			}
		}

		if(count($v_url)==0){
			$v_url = '';
		}elseif(count($v_url)==1){
			$v_url = $v_url[0];
		}
	}

	if(!$v_type || !$v_url){
		show_error(501, '调用参数错误');
	}
}

/**
 * @param string $url
 * @param function $formatFunc 对响应内容的预处理函数，此函数只有一个字符串类型的参数，返回的处理结果是字符串或数组
 * @param int $retryInterval 重试间隔秒数
 * @param int $cacheTimeout 缓存过期时间（秒）
 */
function fetch_url($url, $formatFunc=null, $retryInterval=10, $cacheTimeout=60){
    $shouldRefetch = true;
    $oldRet = '';
    if($cacheTimeout>0){
        $cacheID = md5_16("v.php?{$url}");
        $cacheFile = TEMPDIR . "/{$cacheID[0]}/{$cacheID[1]}{$cacheID[2]}/{$cacheID}.~tmp";
        $oldRet = file_get_contents_safe($cacheFile);
        if($oldRet===false){
            $shouldRefetch = true;
        }elseif($oldRet===''){
            $shouldRefetch = time()-filemtime($cacheFile) > $retryInterval;
        }else{
            $oldRet = unserialize($oldRet);
            $shouldRefetch = time()-filemtime($cacheFile)>$retryInterval && time()-$oldRet['time']>$cacheTimeout;
            $oldRet = $oldRet['content'];
        }
    }
    if($shouldRefetch){
        global $config;
        if($cacheFile) touch($cacheFile);
        $httpConfig = array('timeout'=>15, 'proxy'=>$config['proxy']);
        $text = http_get($url, $httpConfig);
        if($text){
            $newRet = $formatFunc ? $formatFunc($text) : $text;
            if($newRet){
                if($cacheTimeout>0){
                    file_put_contents($cacheFile, serialize(array('time'=>time(),'content'=>$newRet)), LOCK_EX);
                }
                return $newRet;
            }
        }
    }
    return $oldRet;
}

/**
 * 获取网门新闻页地址
 * @param $url 实际网址
 * @return string
 */
function fetch_or($url){
	global $config;
	if(preg_match('#^[\w\.\-]+$#', $url) || preg_match('#^https?://[^/]+?\.\w+(/|/\?|/\w|$)#', $url)){
		$cacheID = md5_16("v:or:{$url}");
		$cacheFile = TEMPDIR . "/{$cacheID[0]}/{$cacheID[1]}{$cacheID[2]}/{$cacheID}.~tmp";
		$oldRet = file_get_contents_safe($cacheFile);
		$passedSeconds = time()-@filemtime($cacheFile);
		$shouldQueryAPI = ($oldRet && $passedSeconds>300) || (!$oldRet && $passedSeconds>10);
		if($shouldQueryAPI){
			mkdirs(dirname($cacheFile));
			$newRet = null;
			touch($cacheFile);
			$httpConfig = array('timeout'=>10, 'proxy'=>$config['proxy']);
			$text = http_get(OGATE_OR_API_URL . rawurlencode($url), $httpConfig);
			if($text){
				$text = preg_replace('#<br>|<br/>|<br />|<br/>|<p>|<p/>|<p />|<p/>#i', "\n", $text);
				$text = preg_replace('#\s+#i', "\n", trim($text));
				$arr = explode("\n", $text);
				$arr2 = array();
				foreach($arr as $v){
					if(preg_match('#^(https?://[^/]+?\.\w+/\??\S+)#', $v, $match)){
						$arr2[] = $match[1];
					}else{
						break;
					}
				}
				if(!empty($arr2)){
					$newRet = implode("\n", $arr2);
				}
			}
			if($newRet){
				file_put_contents($cacheFile, $newRet, LOCK_EX);
				return $newRet;
			}elseif($oldRet){
				touch($cacheFile, time()-(300-10));
				return $oldRet;
			}else{
				return '';
			}
		}else{
			return $oldRet;
		}
	}else{
		show_error(501, '调用参数错误');
	}
}

/**
 * 获取神州正道变形域名
 * @return string
 */
function fetch_szzd($id){
	global $config;
	$json = null;
	$cacheFile = TEMPDIR . "/szzd.~tmp";
	if(!file_exists($cacheFile) || time()-filemtime($cacheFile)>60){
		$httpConfig = array('timeout'=>15, 'proxy'=>$config['proxy']);
		$text = http_get(GITHUB_SZZD_JSON, $httpConfig);
		if($text){
		    if(substr($text,0,15)=='var feed_data ='){
		        $text=substr($text,15);
		    }
			$json=json_decode($text,true);
		}
		if(!empty($json)){
			file_put_contents($cacheFile, serialize($json), LOCK_EX);
		}
	}
	if(empty($json) && file_exists($cacheFile)){
		$json = unserialize(file_get_contents($cacheFile));
	}
	if(empty($json)){
		return null;
	}
	$arr = explode('.', $id);
	foreach($arr as $v){
		if(isset($json[$v])){
			$json = $json[$v];
		}else{
			return null;
		}
	}
	if(empty($json)){
		return null;
	}elseif(is_scalar($json)){
		$json = array($json);
	}elseif(is_array($json)){
		foreach($json as $v){
			if(is_array($v)){
				return null;
			}
		}
		$json = array_unique($json);
	}
	foreach($json as &$v){
		if(preg_match('#[\w\+/]+={0,2}#', $v)){
			$v2 = base64_decode(base64_decode($v));
			$v = !empty($v2) ? $v2 : $v;
		}
		unset($v);
	}
	return $json;
}

/*
function fetch_szzd(){
	global $config;
	$cacheFile = TEMPDIR . "/v_szzd.~tmp";
	$oldRet = file_get_contents_safe($cacheFile);
	$passedSeconds = time()-@filemtime($cacheFile);
	$shouldQueryAPI = ($oldRet && $passedSeconds>60) || (!$oldRet && $passedSeconds>10);
	if($shouldQueryAPI){
		$newRet = null;
		touch($cacheFile);
		$httpConfig = array('timeout'=>15, 'proxy'=>$config['proxy']);
		$text = http_get(GITHUB_SZZD_JSON, $httpConfig);
		if($text && ($json=json_decode($text))){
			$domains = array();
			foreach($json->domain->value as $v){
				$domain = base64_decode(base64_decode($v));
				$domains[] = !preg_match('#^https?://#', $domain) ? "http://{$domain}" : $domain;
			}
			$domains = array_unique($domains);
			if(!empty($domains)){
				$newRet = implode("\n", $domains);
			}
		}
		if($newRet){
			file_put_contents($cacheFile, $newRet, LOCK_EX);
			return $newRet;
		}elseif($oldRet){
			touch($cacheFile, time()-(600-10));
			return $oldRet;
		}else{
			return '';
		}
	}else{
		return $oldRet;
	}
}
*/

/**
 * 短网址，xhr.so
 * @return string
 * 函数来源于 http://surl.sinaapp.com/
 */
/*
function shorturl_xhr_so($url){
	if(preg_match('#^http://t\.cn/#', $url)){
		return $url;
	}

	$cacheID = md5_16("v:xhr.so:{$url}");
	$cacheFile = TEMPDIR . "/{$cacheID[0]}/xhrso.~tmp";
	$content = file_get_contents_safe($cacheFile);
	if($content && (($x=strpos($content,"{$cacheID}="))!==false)){
		$x += 17;
		$x2 = strpos($content,"\n", $x);
		return $x2>0 ? substr($content, $x, $x2-$x) : '';
	}

	$ret = http_post('http://xhr.so/ShortUrl.so', 'longurl='.rawurlencode($url));

	if($ret && preg_match('#^https?://\w+\.\w+/\w+$#', $ret)){
		mkdirs(dirname($cacheFile));
		file_put_contents($cacheFile, "{$cacheID}={$ret}\n", FILE_APPEND | LOCK_EX);
		return $ret;
	}else{
		return	'';
	}
}
*/
function shorturl_xhr_so($url){
	if(preg_match('#^http://t\.cn/#', $url)){
		return $url;
	}

	$cacheID = md5_16("v:xhr.so:{$url}");
	$cacheFile = TEMPDIR . "/{$cacheID[0]}/xhrso.~tmp";
	$content = file_get_contents_safe($cacheFile);
	if($content && (($x=strpos($content,"{$cacheID}="))!==false)){
		$x += 17;
		$x2 = strpos($content,"\n", $x);
		return $x2>0 ? substr($content, $x, $x2-$x) : '';
	}

	$ret = http_get('http://gzbusnow.duapp.com/surl/surl_proxy.php?source=1681459862&url_long='.rawurlencode($url).'&callback=jsonp_'.rand_string(5,5,RANDSTR_HEX,false));
	if($ret && preg_match('#"url_short"\s*:\s*"(http://t\.cn/\w+)"#', $ret, $match)){
		mkdirs(dirname($cacheFile));
		file_put_contents($cacheFile, "{$cacheID}={$match[1]}\n", FILE_APPEND | LOCK_EX);
		return $match[1];
	}else{
		return'';
	}
}

function add_scheme_and_uri($s){
	global $uri;
	if(!$s) {
		return null;
	}elseif(preg_match('#^https?://#', $s)){
		$url = $s;
	}elseif(substr($s,0,2)=='//'){
		$url = Url::getCurrentScheme() .':'. $s;
	}elseif($s[0]=='/'){
		$url = get_current_homepage() . $s;
	}else{
		$url = Url::getCurrentScheme() .'://'. $s;
	}
	if($uri){
		//添加路径
		$url = Url::create($url)->getFullUrl($uri, true);
	}
	return $url;
}

/**
 * 域名解析
 */
function resolve($host, $timeout=5, $retry=2) {
	if(preg_match('#^\d+\.\d+\.\d+\.\d+$#', $host)) {
		return $host;
	}
	if(!preg_match('#^[\w+\.\-]+?\.\w+$#', $host)) {
		return $host;
	}

	$ip = null;
	for($i=1; $i<=$retry; $i++){
		set_time_limit($timeout+1);
		$ip = @gethostbyname($host);
		if ($ip != $host && preg_match('#^\d+\.\d+\.\d+\.\d+$#', $ip)) {
			return $ip;
		}
	}
	return $host;

	/*
	$query = 'nslookup -timeout='.$timeout.' -retry=1 '.$host;
	$query = shell_exec($query);
	if(preg_match('#\nAddress: (.*)\n#', $query, $matches))
		return trim($matches[1]);
		return $host;
	*/
}

