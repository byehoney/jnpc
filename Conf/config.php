<?php
$arr = array(
	'TMPL_L_DELIM' => '{#',
	'TMPL_R_DELIM' => '#}',
	'DATA_CACHE_SUBDIR'=>true,
	'TMPL_ACTION_ERROR' => 'Public:tip',
	'TMPL_ACTION_SUCCESS' => 'Public:tip',
	'DATA_PATH_LEVEL'=>2,
	'URL_HTML_SUFFIX'=>'', 
	//top应用配置
	'REDIRECT_URI' => urlencode(APP_URL.PHP_SELF.'/Auth/urlReturn'),
	/**********正式*************/
	'appID' => '2016102502331121',
	'onlineip'=>'https://openauth.alipay.com/oauth2/appToAppAuth.htm?app_id=',
	'private_key'=>APP_ROOT.'/key/rsa_private_key1024.pem',
	'public_key_file' => APP_ROOT. "/key/rsa_public_key1024.pem",
	//'alipay_public_key' => APP_ROOT. "/key/alipay_rsa_public_key1024.pem",*/
	//'alipay_public_key' => APP_ROOT. "/key/alipay_public_key_sha1_2016102502331121.txt",
	/*'private_key'=>APP_ROOT.'/key/private2048.txt',
	'public_key_file' => APP_ROOT. "/key/public2048.txt",
	'alipay_public_key' => APP_ROOT. "/key/alipaypublic2048.txt",
	/**********正式end*************/
	
	/**********沙箱*************/
	/*'appID' => '2016072900120717',	
	'onlineip'=>'https://openauth.alipaydev.com/oauth2/appToAppAuth.htm?app_id=',	
	'private_key'=>APP_ROOT.'/key/rsa_private_key1024.pem',
	'public_key_file' => APP_ROOT. "/key/rsa_public_key1024.pem",
	//'alipay_public_key' => APP_ROOT. "/key/alipay_rsa_public_key1024test.pem",
	/*
	'private_key'=>APP_ROOT.'/key/rsa_private_key1.pem',
	'public_key_file' => APP_ROOT. "/key/rsa_public_key1.pem",
	'alipay_public_key' => APP_ROOT. "/key/alipay_rsa_public_key1.pem"*/
	/**********沙箱end*************/
);
$dbconfig = require('./dbconfig.php');
return array_merge($arr,$dbconfig);
?>