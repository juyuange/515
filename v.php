<?php

/*
 *
===========================================================================================

v.php ��Ҫ���ṩ��app�M�е��ã����ڵĵ��ò���Ϊ��
1. api���ӿ����ƣ�֧�ֵ��б����£�
������id  ��ȱʡֵ����id��id2������ȡӰ���ļ���ַ������id��id2����
������szzd  ��github��ȡ��������������������ѡid��id2����
������url  ��url��url2������ȡҪ�������������ַ������url��url2����
������og  ��ȡ���ŵĶ�̬ip
������or  ��ȡ����ý����վ������ҳ�����ż��ܵ�ַ�����û��url��url2������Ĭ�ϻ�ȡ���ŵ�ʵʱ��ַ
������dns  ����ָ��������ip������url��url2����
������isats ��ǰ�����Ƿ���ATS
     ntd1 ָ��������ֱ��Ƶ���ĵ�ַ������id��url��key������������id��url�Ŀ�ѡֵ�뿴�±�$urllist�������������ֱ�����֣�key�ǹ̶���ֵ wleU5_Il474HD_l1kEll
     ntd2 ָ��������ֱ���Ƿ�ֻ�ܴ��й���½���ţ���ѡonlycn������onlycn��ֵΪon��off��Ĭ��Ϊoff����key�ǹ̶���ֵ wleU5_Il474HD_l1kEll
2. id��id2��ָ��Ӱ���ļ����
������ʹ��id����ָ��Ӱ���ļ��ı��
������ʹ��id2����ָ���������ܵı��
3. url��url2��ָ��Ҫ�������������ַ
������ʹ��url����ָ������http://��https://��������ַ����ַ��Ҫ���M��urlencode����
������ʹ��url2����ָ���������ܵ���������ַ
4. action�����������ʽ��֧�ֵ��б����£���api=idʱȱʡֵ��show�������encrypt����
������show  ��ʾ���߲�����Ӱ��ҳ�棬����Ƕ����ַ������ʾ�����б�Ҳ����ʹ��index��������1��ʼ��ָ���ڼ���
������text  ��ʾδ�������ݣ�����Ƕ����ַ�����û��з����
������encrypt ����Ƕ����ַ�����û��з����
������redirect  ��ת��ָ����ַ������Ƕ����ַ�������ѡ��һ��
������xhr.so  ����ַ��xhr.so��վ���ɶ���ַ������Ƕ����ַ�������ѡ��һ��
������xhr.so.qrcode  ����ַ��xhr.so��վ���ɶ���ַ�����ɶ�ά�룬����Ƕ����ַ�������ѡ��һ��
������qrcode  ������ַ��ά�룬����Ƕ����ַ�������ѡ��һ��

�������������������Ϊ��apiָ�����ݻ�ȡ��ʽ��id��id2��url��url2����apiָ���ķ�ʽ�ṩ���ݣ������action����ָ��������ʾ��ʽ

���⣬���м����������������ڸ��ӿڵ�������˵����
1. random=on
2. onlycn=on��off (Ĭ����off)
3. index=����
4. uri=��ַ·�����֣���actionΪredirect��xhr.so��xhr.so.qrcode��qrcode �⼸��ʱ��֧��ʹ�� uri ���������ַ·�������� uri=/1/
5. ifisats=0����1����actionΪredirectʱ����� ������ת������/v.php?api=isats ���ص����ݸ�ifisats����ֵ��ͬ������ת������ͷ���403����

���Ϊ�˼�ǿ�����ԣ����ṩһ����Ϊ���صĵ��÷�ʽ����δ���ܵĵ��ò�����Ϻ�֮���ټ��ܣ�Ȼ����Ϊcode�����Ĳ���ֵ
���磬����� /v.php?api=szzd&action=xhr.so.qrcode
�Ͱ� api=szzd&action=xhr.so.qrcode ����Ȼ�����Ϊ /v.php?code=�����ܽ����

===========================================================================================

��Ҫ���ó������£�
1. ����Ӱ�������÷�ʽ /v.php?id=��Ӱ����š��� /v.php?id=�����ܵ�Ӱ����š���֧�ֵ�Ӱ��������£�
����1). ����Ӱ���б�
��������ntd: �������й�Ƶ��ֱ��
��������ntdmd: ����������Ƶ��ֱ��
��������ntdmx: ����������Ƶ��ֱ��
       ntd2: ��������̨̫
��������������Ч�б����£�
			�����˵��� �й�̨
			ntd-cnlive150  http://cnhls.ntdtv.com/cn/live150/playlist.m3u8
			ntd-cnlive400  http://cnhls.ntdtv.com/cn/live400/playlist.m3u8
			ntd-cnlive800  http://cnhls.ntdtv.com/cn/live800/playlist.m3u8
			�����˵��� ��̨̫
			ntd-mlt        http://intd.ntdtv.com.tw/mlt/playlist.m3u8
			ntd-aplive200  http://live.ntdimg.com/aplive200/playlist.m3u8
			ntd-aplive400  http://live.ntdimg.com/aplive400/playlist.m3u8
			�����˵��� ����̨
			ntd-live200    http://live.ntdimg.com/live200/playlist.m3u8
			ntd-live400    http://live.ntdimg.com/live400/playlist.m3u8
			�����˵��� ����̨
			ntd-uwlive280  http://live.ntdimg.com/uwlive280/playlist.m3u8
			ntd-uwlive520  http://live.ntdimg.com/uwlive520/playlist.m3u8
			�����˵��� �ɽ�ɽ̨
			ntd-sflive220  http://live.ntdimg.com/sflive220/playlist.m3u8
			ntd-sflive440  http://live.ntdimg.com/sflive440/playlist.m3u8
			�����˵��� �Ӷ�̨
			ntd-mllive220  http://live.ntdimg.com/mllive220/playlist.m3u8
			�����˵��� ����̨
			ntd-cwlive220  http://live.ntdimg.com/cwlive220/playlist.m3u8
			�����˵��� ��˹��̨
			ntd-htlive480  http://live.ntdimg.com/htlive480/playlist.m3u8
��������1400: 1400������
��������zf: �찲���Է�����
��������jp: �������԰�Ƕ��ŷָ���9����Ƶ��ַ��
��������fy: ��������У��԰�Ƕ��ŷָ���6����Ƶ��ַ��
����2). ������Ƶ��ַ�������������� 3EC/WH.mp4 �� 4EC/JP1.mp4����ǰ���ŵ�������������Դվ
����3). ������Ƶ��ַ���༯�������� 4EC/JP.mp4|9����ǰ���ŵ�������������Դվ
����4). youtube��Ƶ��ID������ g0S0_7bjHCc
��������1)��3)������Ƕ��Ӱ����������ʾ�����б�Ҳ����ͨ��ָ�� &index=����1��ʼ����š�ֻ����ĳһ��
���������Ƿ�����ֻ�ܴ�½�û����ţ�onlycn=on���ƣ�onlycn=off�����ƣ�Ĭ�ϣ�
2. ����Ӱ���ļ���������ַ�����÷�ʽ /v.php?id2=�����ܵ�Ӱ���ļ���ַ��
3. ��ȡgithub�ϵ����������ı������������÷�ʽ /v.php?api=szzd�����߳�Ч���������÷�ʽ /v.php?api=szzd&id=longacting.value����
      Ҳ����ͨ��ָ��random=on��������ÿ�����������һ�ڱ�Ϊ������ݣ������ aaa.ogdata.bid �����Ϊ kdheihd.ogdata.bid
      Ҳ����ͨ��ָ��index=��1��ʼ�����֣�ָ��ĳһ����¼
4. ��ȡgithub�ϵ�����������������Ϣ�����÷�ʽ /v.php?api=szzd&id=��json·������ /v.php?api=szzd&id=�����ܵ�json·������json·������ֶμ���.�ָ�
5. ��ʾָ����Ⱥ����ַ�ĸ�����ʽ /v.php?api=url&url2=����(��Ⱥ����ַ��)&action=��action������
6. ��ѯ���Ŷ�̬��������ַ�����÷�ʽ /v.php?api=og
7. �Ѽ���ý����վ������ҳת��Ϊ��������Ⱥ�����ڴ�½��վ���ɵĶ�ά�룬���÷�ʽ /v.php?api=or&url2=�����ܺ������ҳ��ַ��
8. ��ȡ���ŵ�ʵʱ��ַ�����÷�ʽ /v.php?api=or

===========================================================================================

���þ�����
1. �������й�Ƶ��������ҳ /v.php?id=ntd ��m3u8�����б���ַ /v.php?id=ntd&action=text
2. ����youtube���Ͻ��� /v.php?id=g0S0_7bjHCc
3. ���ŷ���������б� /v.php?id=fy
4. ���ž�����2�� /v.php?id=jp&index=2
5. ����ʵ��
����/v.php?api=szzd                       ��ʾ�����������������б�ļ��ܽ��
����/v.php?api=szzd&action=text           ��ʾ�����������������б������
����/v.php?api=szzd&action=redirect       �����ת��ĳһ������������������
����/v.php?api=szzd&action=qrcode         ������������ĳһ�����������Ķ�ά��
����/v.php?api=szzd&action=xhr.so         ������������ĳһ�����������Ķ���ַ
����/v.php?api=szzd&action=xhr.so.qrcode  ������������ĳһ�����������Ķ���ַ�Ķ�ά��
����/v.php?api=szzd&id=shorturl.value     ��ȡ���������洢��github�ı��������Ķ���ַ
����/v.php?code=2fd5e73f0a8786ec6b6912bd5c7d487e8fd304b86fc47ad5a4c846774100138b8a ���У�������������ĳһ�����������Ķ���ַ�Ķ�ά�룩�ĸ������صĵ��÷�ʽ
����/v.php?api=dns&url2=����(fo04.ogdata.bid)  ����fo04.ogdata.bid������ip��ַ
����/v.php?api=url&url2=����(http://��Ⱥ��������/1/)&action=xhr.so.qrcode  ���ɴ�Ⱥ�������Ķ�̬����ҳ�Ķ���ַ�Ķ�ά��

===========================================================================================

����˵����
1. ����Ӱ������ҳ���ڲ����ֻ�����������һЩȱ�ݣ�
����1) �޷��Զ���ʼ����
����2) �޷��Զ��M��ȫ��ģʽ��ȫ��ʱ�Ż��Զ�������ʾ������ʱֻ��������ҳ��ʾ�������������������ȫ����ť����ȫ��
3. �������� 3EC/WH.mp4 ������������Ƶ�����ŵ���Ƶ�ļ��������Ѿ��������ˣ����ڲ��ŵĶ�������������Դվ��Ӱ������Դվ��û�е�Ӱ�����޷�������
4. ÿ��30����ڵ�ǰ��ҳ���¼����λ�ã����������������ж�ʱ��ˢ����ҳ�����Զ������λ�ÿ�ʼ����
5. ���Ҫ����������Ƶ��ֻ��Ҫ�޸� v.php ��ߵ���Ƶ�б���У�����Ҫע���ʽ�����
6. ��ָ����id��id2����ʱ�����ûָ��action��������ʾ���߲���ҳ�棬Ҳ����ָ�� action ������ʾΪ��ķ�ʽ������
������� &action=text ��ֻ��ʾӰ���ļ���ַ���ǲ������������ֵĵ�ַ������༯�ͷ��ػ��з��ָ�Ķ���
������� &action=redirect �ͻ���ת��Ӱ���ļ���ַ

*/

//=== ֱ����mp4��Ƶ����ַ ========================================================================================
$urllist = array(
	//�����ˣ�ntd: �й�̨��ntdmd: ����̨��ntdmx: ����̨��ntd2: ��̨̫����ֻ����1����ַ�������Ƕ��ŷָ��Ķ����ַ��
	//�й�̨
    'ntd' =>           'http://cnhls.ntdtv.com/cn/live400/playlist.m3u8',
    'ntd-cnlive150' => 'http://cnhls.ntdtv.com/cn/live150/playlist.m3u8',
    'ntd-cnlive400' => 'http://cnhls.ntdtv.com/cn/live400/playlist.m3u8',
    'ntd-cnlive800' => 'http://cnhls.ntdtv.com/cn/live800/playlist.m3u8',
    'ntd-cnlive150_bak' => 'http://cnhls.ntdtv.com/cn/live150/first.m3u8',
    'ntd-cnlive400_bak' => 'http://cnhls.ntdtv.com/cn/live400/first.m3u8',
    'ntd-cnlive800_bak' => 'http://cnhls.ntdtv.com/cn/live800/first.m3u8',
    //��̨̫
    'ntd2' =>          'http://intd.ntdtv.com.tw/mlt/playlist.m3u8',
    'ntd-mlt' =>       'http://intd.ntdtv.com.tw/mlt/playlist.m3u8',
    'ntd-aplive200' => 'http://live.ntdimg.com/aplive200/playlist.m3u8',
    'ntd-aplive400' => 'http://live.ntdimg.com/aplive400/playlist.m3u8',
    //����̨
    'ntdmd' =>         'http://live2.ntdimg.com/live330/playlist.m3u8',
    'ntd-live200' =>   'http://live.ntdimg.com/live200/playlist.m3u8',
    'ntd-live400' =>   'http://live.ntdimg.com/live400/playlist.m3u8',
    //����̨
    'ntdmx' =>         'http://live.ntdimg.com/uwlive520/playlist.m3u8',
    'ntd-uwlive280' => 'http://live.ntdimg.com/uwlive280/playlist.m3u8',
    'ntd-uwlive520' => 'http://live.ntdimg.com/uwlive520/playlist.m3u8',
    //�ɽ�ɽ̨
    'ntd-sflive220' => 'http://live.ntdimg.com/sflive220/playlist.m3u8',
    'ntd-sflive440' => 'http://live.ntdimg.com/sflive440/playlist.m3u8',
    //�Ӷ�̨
    'ntd-mllive220' => 'http://live.ntdimg.com/mllive220/playlist.m3u8',
    //����̨
    'ntd-cwlive220' => 'http://live.ntdimg.com/cwlive220/playlist.m3u8',
    //��˹��̨
    'ntd-htlive480' => 'http://live.ntdimg.com/htlive480/playlist.m3u8',

	//1400������
	'1400' => 'http://inews3.ntdtv.com/data/media/2012/5-15/N__NTDSpecial-5089_JieXiLi_P637960.mp4',
	//�찲���Է�����
	'zf' => 'http://inews3.ntdtv.com/data/media/2012/12-9/F_RV0000061_A_F_RV_Other-1_WeiHuoZhongWenBan_P609220.mp4', //'3EC/WH.mp4',
	//��������Ƕ��ŷָ���9����Ƶ��ַ��
	'jp' => 'http://inews3.ntdtv.com/data/media2/2015/08-23/JLP_s0_e1_v1_i0-JPGCD_1-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-04/JLP_s0_e2_v1_i0-JPGCD_2-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-06/JLP_s0_e3_v1_i0-JPGCD_3-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-09/JLP_s0_e4_v1_i0-JPGCD_4-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/07-21/JLP_s0_e5_v1_i0-JPGCD_5-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/03-19/JLP_s0_e6_v1_i0-JPGCD_6-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/02-08/JLP_s0_e7_v1_i0-JPGCD_7-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/03-03/JLP_s0_e8_v1_i0-JPGCD_8-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/07-30/JLP_s0_e9_v1_i0-JPGCD_9-video.mp4', //'4EC/JP.mp4|9',
	//��������У���Ƕ��ŷָ���6����Ƶ��ַ��
	'fy' => 'http://inews3.ntdtv.com/data/media2/2015/08-12/ZXJM_s2_e1_v1_i0-FYTDX_1-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-19/ZXJM_s2_e2_v1_i0-FYTDX_2-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/08-19/ZXJM_s2_e3_v1_i0-FYTDX_3-video.mp4,
		http://inews3.ntdtv.com/data/media/2012/3-26/F_RV0000130_A_F_RV_Other-4-4_FengYuTianDiXin_P637505.mp4,
		http://inews3.ntdtv.com/data/media2/2015/09-02/ZXJM_s2_e5_v1_i0-FYTDX_5-video.mp4,
		http://inews3.ntdtv.com/data/media2/2015/09-02/ZXJM_s2_e6_v1_i0-FYTDX_6-video.mp4', //'3EC/TDX.mp4|6',
	//��2����Ƶ��ַ�ľ���
	'demo' => '/video/video/demo.mp4',
);

define('OGATE_OR_API_URL', 'http://ogate.org/oo.aspx?ob=or&op=geturl&from=SzzdOgate&key=j0f2j8j8fex3&ag=');
define('OGATE_OR_API_DEFAULT_AG', 'c816667');
//define('GITHUB_SZZD_JSON', 'https://raw.githubusercontent.com/shenzhouzd/update/master/data.json');
//define('GITHUB_SZZD_JSON', 'https://raw.githubusercontent.com/chuan12/shenzhouzd/master/data.js?token=AkphYbJ1DRzmaD88-sWyZNvI-OOmeY6bks5a3fd6wA%3D%3D');
define('GITHUB_SZZD_JSON', 'https://raw.githubusercontent.com/chuan12/shenzhouzd/master/data.json?token=AkphYRR2nZFtPnqHtagtY4LeTqpg_b-dks5a3l0owA%3D%3D');
define('GITHUB_SZZD1_URL', 'https://raw.githubusercontent.com/szzd1/1/master/README.md');

//=== ��ʼ�� ========================================================================================

require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/func.inc.php');
require(APPDIR.'/include/http.inc.php');
require(APPDIR.'/include/coding.inc.php');

//��ʼ��
init_config();
define('API_KEY', 'wleU5_Il474HD_l1kEll');
define('NTD_CUSTOM_FILE', DATADIR . '/ntd_custom.dat');
define('NTD_ONLYCN_FILE', DATADIR . '/ntd_onlycn.dat');
require(APPDIR.'/plugin/szzd.php');

//=== ��ȡ���� ========================================================================================

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
		//���ŵĶ�̬��ַ
		$v_url = get_og_server();
		if(empty($v_url)){
			show_error(500, '���޿��ýڵ�');
		}
		break;
	case 'szzd':
		if(empty($id)) $id='domain.value';
		$arr = fetch_szzd($id);
		if(!empty($arr)){
			if(in_array($id,array('domain.value','longacting.value')) && $random){
				//����������������������仯ÿ�����������һ��
				foreach($arr as $k=>$v){
				    if(preg_match('#^(https?://)?([\d\.:]+)$#', $v, $match)){
				        //ip��ַ����Ҫ�ų��𣿣�
				    }elseif(preg_match('#^(https?://)?([\w\-]+)(\.[\w\-\.:]+)$#', $v, $match)){
				        $arr[$k] = $match[1] . rand_string(6, 10, RANDSTR_LN, false) . $match[3];
					}
				}
			}
			$v_url = $arr;
		}
		break;
	case 'or':
		//��������ҳ
		$v_url = fetch_or($url ? $url : OGATE_OR_API_DEFAULT_AG);
		break;
	case 'id':
		//����id������ѯ��Ƶ
		if(!$action) $action='show';
		get_video_from_params();
		break;
	case 'url':
		if($url){
			$v_url = $url;
		}else{
			show_error(501, 'url����ֵ����');
		}
		break;
	case 'dns':
		if($url){
			$v_url = resolve($url, 5, 2);
			if(!$v_url || $v_url==$url){
				show_error(500, 'failed');
			}
		}else{
			show_error(501, 'url����ֵ����');
		}
		break;
	case 'isats':
		echo !empty($config['is_behind_ats']) ? 1 : 0;
		exit;
	case 'ntd1':
	    //ָ��������ֱ��Ƶ���ĵ�ַ
	    //if($_GET['key']!=API_KEY || strpos($id,'ntd')!==0 || strpos($url,'.m3u8')===false || !isset($urllist[$id]) || array_search($url,$urllist)===false){
	    if($_GET['key']!=API_KEY || strpos($id,'ntd')!==0 || !preg_match('#^https?://\w+\.(ntdtv\.com|ntdtv\.com\.tw|ntdimg\.com)/[^\?]+?\.m3u8#', $url)){
	        show_error(501, '����ֵ����');
	    }
	    $arr = file_exists(NTD_CUSTOM_FILE) ? unserialize(file_get_contents(NTD_CUSTOM_FILE)) : array();
	    $arr[$id] = $url;
	    file_put_contents(NTD_CUSTOM_FILE, serialize($arr));
	    //����
	    echo 'OK';
	    exit;
	case 'ntd2':
	    //�����Ƿ�ֻ�ܴ�½����
	    if($_GET['key']!=API_KEY || !isset($_GET['onlycn']) || !in_array($_GET['onlycn'],array('on','off'))){
	        show_error(501, '����ֵ����');
	    }
	    if($onlycn=='on' && !file_exists(NTD_ONLYCN_FILE)){
            file_put_contents(NTD_ONLYCN_FILE, 'ON');
	    }elseif($onlycn=='off' && file_exists(NTD_ONLYCN_FILE)){
	        unlink(NTD_ONLYCN_FILE);
	    }
	    //����
	    echo 'OK';
	    exit;
	default:
		show_error(501, 'api����ֵ����');
}
if(empty($v_url)){
	show_error(500, 'API���ؿ�ֵ');
}


//=== ��� ========================================================================================

if(is_string($v_url) && strpos($v_url,"\n")>0){
	$v_url = explode("\n",$v_url);
}
if($index && is_array($v_url) && isset($v_url[$index-1])){
	$v_url = array_slice($v_url, $index-1, 1);
}

if($action!='show'){
	switch($action){
		case 'file': //����������ǰ���ݡ�
		case 'text':
			//��ʾ�ı���ʽ�Ľ��
			if(!empty($uri)){
				$v_url = add_scheme_and_uri(is_array($v_url) ? $v_url[array_rand($v_url)] : $v_url);
			}else{
				$v_url = is_array($v_url) ? implode($v_url,"\n") : $v_url;
			}
			echo $v_url;
			break;
		case 'redirect':
			//�����ת
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
				show_error(500, '��ȡ����ַʧ��');
			}elseif($action=='xhr.so.qrcode'){
				require 'include/phpqrcode.inc.php';
				QRcode::png($v_url, false, QR_ECLEVEL_L, 6, 2);
				break;
			}else{
				echo $v_url;
				break;
			}
		case 'qrcode':
			//�����ʾһ����ַ�Ķ�ά��
			$v_url = add_scheme_and_uri(is_array($v_url) ? $v_url[array_rand($v_url)] : $v_url);
			require 'include/phpqrcode.inc.php';
			QRcode::png($v_url, false, QR_ECLEVEL_L, 6, 2);
			break;
		case 'encrypt':
			//����:
		default:
			//��ʾ���ܺ�Ľ��
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
        $playlist[]="{sources:[{file:'{$v}'}],title:'��{$num}��'}";
    }
    $playlist=implode(',',$playlist);
    $playlist="[{$playlist}]";
}

//=== ���߲���Ӱ�� ========================================================================================

header('Content-Type: text/html; charset=GBK');
?>
<!DOCTYPE html><!-- saved from url=(0019)about:trusted sites --><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=GBK" />
<meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<meta name="format-detection" content="telephone=no">
<title>��Ƶ����</title>
<style type="text/css">
html,body{margin:0;padding:0;border:none;width:100%;height:100%;}
#myvideo{width:100%;height:100%;text-align:center;}
#tips{display:none;position:absolute;z-index:999;left:5px;top:5px;font-size:12px;color:#FF0000;color:#FF0000;background-color:rgb(238,238,236);padding:2px 5px;border:1px solid #CCC;}
</style>
</head>
<body>
<div id="tips">���޷����ţ������Ӻ�ˢ������ <button onclick="closetips();">֪����</button></div>
<div id="myvideo"><br/><br/><br/>���ڼ��ز����������Ժ�...</div>
<script type="text/javascript" src="/images/jwplayer.js" noproxy></script>
<script type="text/javascript">
function closetips(){
	document.cookie='clst=1';
	document.getElementById('tips').style.display="none";
}
if(document.cookie.indexOf('clst=1')===-1) document.getElementById('tips').style.display="block";

//��ʼ����λ��
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

//=== ֧�ź��� ========================================================================================

function get_video_from_params(){
	global $config, $urllist, $v_type, $v_url, $v_img, $id, $index;
	if(empty($id)) show_error(501, '���ò�������');

	if(isset($urllist[$id])){
		//��Ƶ�б�
	    $url = trim($urllist[$id]," \t\n\r\0,");
		//�����Զ���ֱ����ַ
		if(file_exists(NTD_CUSTOM_FILE)){
		    $arr = unserialize(file_get_contents(NTD_CUSTOM_FILE));
		    if(is_array($arr) && isset($arr[$id])){
		        $url = trim($arr[$id]);
		    }
		}
		//ȥ���ո�
		$url = preg_replace('#\s+#', '', $url);
	}elseif(preg_match('#^[\w\-\.]{5,15}$#', $id)){
		//youtube��Ƶ��ַ
		$v_type = 'mp4';
		$v_url = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytb_{$id}");
		$v_img = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytbimg_{$id}");
		return;
	}elseif(preg_match('#^[\w\-\.]{20,50}$#', $id)){
		//youtube��Ƶ�б��ַ
		$v_type = 'mp4';
		$v_url = "/?{$config['built_in_name']}=" . encrypt_builtin("_ytbl_{$id}");
		return;
	}elseif(preg_match('#^\w{2,5}/\w{2,10}\.\w{2,4}(\||$)#', $id)){
		//������Ƶ
		$url = $id;
	}elseif(strlen($id)>16){
		//����������ǰ���ݡ���������ַ
		$url = urlsafe_base64_decode(urlsafe_base64_decode($id));
	}else{
		$url = false;
	}
	if(!$url){
		show_error(501, '���ò�������');
	}

	//��Ƶ�б�
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
		show_error(501, '���ò�������');
	}
}

/**
 * @param string $url
 * @param function $formatFunc ����Ӧ���ݵ�Ԥ���������˺���ֻ��һ���ַ������͵Ĳ��������صĴ��������ַ���������
 * @param int $retryInterval ���Լ������
 * @param int $cacheTimeout �������ʱ�䣨�룩
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
 * ��ȡ��������ҳ��ַ
 * @param $url ʵ����ַ
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
		show_error(501, '���ò�������');
	}
}

/**
 * ��ȡ����������������
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
 * ����ַ��xhr.so
 * @return string
 * ������Դ�� http://surl.sinaapp.com/
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
		//���·��
		$url = Url::create($url)->getFullUrl($uri, true);
	}
	return $url;
}

/**
 * ��������
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

