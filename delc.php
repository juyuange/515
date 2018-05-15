<?php

require('common.inc.php');
require(APPDIR.'/config.inc.php');
require(APPDIR.'/include/func.inc.php');
require(APPDIR.'/include/http.inc.php');

define('DISPLAY_CLEARCACHE_LOG', 1);
header('Content-Type: text/html; charset=GBK');
CacheHttp::clearOverdueCache();
