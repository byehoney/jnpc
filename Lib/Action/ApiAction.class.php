<?php
/*
 * 该控制器为公共API调用控制器,请将所有子类中可能共同使用到的API调用方法放置在此控制器中
 * 所有的方法使用protected类型,只允许子类进行调用,方法名必须以api_开头
 * 禁止在此控制器中添加index方法
 * $_SESSION['HTTPHEADER']请求的header 格式: array("Location:www.taobao.com")
 */
class ApiAction extends CommonAction {
	
	private $retry = 3; //如果请求失败重新请求次数
	private $sleepTime = 1; //重试延迟时间(秒)
	private $retryCode = array(); //需要重试的错误码列表
	protected $_checkErrors=false;
	protected $_needSessionkey=true;
	protected $_nocheckSellerPermisson = array('order','message','share','base');
	
	public function __construct() {
		parent::__construct();
		//自动检查卖家是否授权
		if(ACTION_NAME =='ajaxUploadImages'){ //上传表单模拟成ajax请求
			$this->_isAjax=true;
		}
		$MODULE_NAME = MODULE_NAME;
		$MODULE_NAME = strtolower($MODULE_NAME);
		if(!in_array($MODULE_NAME,$this->_nocheckSellerPermisson)){
			$this->checkSellerPermisson();
		}
		
	}
	
	//缓存有效期
	protected function genCacheExpire($tag) {
		switch($tag) {
			case 'TODAY'://到今天为止
				$time = dateToTime(date('Y-m-d').' 00:00:00');
				$time = strtotime('+1 day', $time);
				$expire = $time - time();
				break;
			case '30SEC':
				$expire = 30;
				break;
			case '5MIN':
				$expire = 300;
				break;
			case '10MIN':
				$expire = 600;
				break;
			case 'HALFHOUR':
				$expire = 1800;
				break;
			case '1HOUR':
				$expire = 3600;
				break;
			case '3HOUR':
				$expire = 3 * 3600;
				break;
			case '6HOUR':
				$expire = 6 * 3600;
				break;
			case 'HALFDAY':
				$expire = 12 * 3600;
				break;
			case '1DAY':
				$expire = 86400;
				break;
			case '3DAY':
				$expire = 3 * 86400;
				break;
			case '1WEEK':
				$expire = 7 * 86400;
				break;
			case 'HALFMONTH':
				$expire = 15 * 86400;
				break;
			case '1MONTH':
				$expire = 30 * 86400;
				break;
			default: $expire = $tag;
		}
		
		return (int)$expire;
	}
	
	//api请求预处理方法
	private function _init($requestName, &$c, &$req) {
		$this->loadTopSdk($requestName);
		$c = new AopClient();
		$c->appId = C('appID');
		$c->rsaPrivateKeyFilePath = C('private_key');
		$c->alipayPublicKey = C('alipay_public_key');
		$req = new $requestName();
	}
	//api执行方法
	private function _exec(&$c, &$req, $type = false, $cacheName = null, $expire = 'HALFHOUR') {

		if($type == false || C('NO_API_CACHE')) {
			//无缓存操作
			$cacheName = null;
			$expire = 0;
			$resp = $this->_apiRequest($c, $req);
			
			$dataSource = 'api';
		} elseif($type > 0) {
			//缓存写入
			if(!$cacheName) $this->error('未指定缓存名称!缓存写入失败', $this->isAjax());//检测缓存名称是否传入
			if($type == 1) {
				$getCacheFuncName = 'getCache';
				$setCacheFuncName = 'setCache';
			} elseif($type == 2) {
				$getCacheFuncName = 'getPublicCache';
				$setCacheFuncName = 'setPublicCache';
			} else {
				$this->error('指定的写入缓存类型不可用,仅支持[1|2]', $this->isAjax());
			}
			//尝试获取缓存
			$resp = $this->$getCacheFuncName($cacheName);
			if(!$resp) {
				$resp = $this->_apiRequest($c, $req);
				//数据无误则写入缓存,否则跳过缓存写入
				if($resp->code === null) $this->$setCacheFuncName($cacheName, $resp, $this->genCacheExpire($expire));
				$dataSource = 'api';
			} else {
				$dataSource = 'cache';
			}
		} elseif($type < 0) {
			$expire = -1;
			//缓存清除
			if(!$cacheName) $this->error('未指定缓存名称!缓存清除失败', $this->isAjax());//检测缓存名称是否传入
			if($type == -1) {
				$deleteCacheFuncName = 'deleteCache';
			} elseif($type == -2) {
				$deleteCacheFuncName = 'deletePublicCache';
			} else {
				$this->error('指定的清除缓存类型不可用,仅支持[-1|-2]', $this->isAjax());
			}
			$this->$deleteCacheFuncName($cacheName);
			$resp = $this->_apiRequest($c, $req);
			$dataSource = 'api';
		}
		$resp = objectToArray($resp);
		$apiName = $req->getApiMethodName();
		return $this->_checkError($resp, $apiName, $this->_genSdkClassName($apiName));
	}

	//api请求函数
	private function _apiRequest($c, $req) {
		if($this->_needSessionkey){
			$resp = $c->execute($req,null, $_SESSION['sessionkey']);
		}else{
			$resp = $c->execute($req,null,$_SESSION['sessionkey']);
		}
		
		$apiName = $req->getApiMethodName();
		$apiName=str_replace('.','_',$apiName).'_response';
		$resp=$resp->$apiName;
		if(isset($resp->code) && in_array($resp->code, $this->retryCode) && $this->retry > 0) {
			sleep($this->sleepTime); //延迟
			$this->retry--;
			$resp=$this->_apiRequest($c, $req);
		} else {
			return $resp;
		}
	}
	//SDK请求类名生成器
	private function _genSdkClassName($apiName) {
		$sdkClassName = explode('.', $apiName);
		$temp = '';
		foreach($sdkClassName as $value) {
			$temp .= ucfirst($value);
		}
		$sdkClassName = $temp.'Request';
		return substr($sdkClassName, 6);
	}
	
	//api相应数据错误检查
	private function _checkError($resp, $apiName, $sdkClassName) {
		$resp['code_msg']=$resp['sub_msg']?$resp['sub_msg']:($resp['sub_code']?$resp['sub_code']:$resp['msg']);
		
		if(isset($resp['code']) && $resp['code']!=10000) {
			if(!$this->_checkErrors){
				return $resp;
			}
			$errInfo = $this->_errCodeAnalysis($resp, $apiName, $sdkClassName);
			if($this->isAjax()) {
				$this->ajaxReturn($resp, $errInfo, false, 'json');
			} else {
				if(in_array($resp->sub_msg,array('应用授权令牌已过期','无效的应用授权令牌'))){//刷新令牌
					$jumpUrl = C('onlineip').C('appID').'&redirect_uri='.C('REDIRECT_URI');//登陆授权链接
					header('Location: '.$jumpUrl);
				}
				
				$this->assign('waitSecond', 5);
				$this->error($errInfo);
			}
		} else {
			if($resp['code']==10000){
				$resp['code']=0;
			}
			return $resp;
		}
	}
	
	//api错误码解析
	private function _errCodeAnalysis($err, $apiName, $sdkClassName) {
		$msg = '<span style="color:red;font-weight:bold;font-size:18px;">系统异常报告</span><br />';
		$msg .= '来源: <span style="color:orange;font-weight:bold;">淘宝开放平台</span><br />';
		if(C('APP_DEBUG')) {
			$msg .= '淘宝接口: <span style="color:orange;font-weight:bold;">'.$apiName.'</span><br />';
			$msg .= 'SDK类名: <span style="color:orange;font-weight:bold;">'.$sdkClassName.'</span><br />';
		}
		$msg .= '主错误码: <span style="color:red;">'.$err['code'].'</span><br />';
		$msg .= '子错误码: <span style="color:red;">'.$err['sub_code'].'</span><br />';
		$msg .= '错误描述[1]: <span style="color:green;">'.$err['msg'].'</span><br />';
		$msg .= '错误描述[2]: <span style="color:green;">'.$err['sub_msg'].'</span><br />';
		$msg .= '其他信息: <span style="color:blue;font-weight:bold;">';
		switch($err['code']) {
			case 0:
				$otherMsg = '无法连接到淘宝数据服务器,请检查您的网络';
				break;
			case 3:
				$otherMsg = '图片上传失败,请检查图片格式/大小';
				break;
			case 7:
				$otherMsg = '淘宝API调用限制，请稍后再尝试';
				break;
			case 9:
				$otherMsg = 'HTTP方法被禁止';//POST或GET大写，如果有图片等信息传入则一定要用POST才可以
				break;
			case 10:
				$otherMsg = '服务不可用,发生未知异常';
				break;
			case 11:
				$otherMsg = '开发者权限不足';
				break;
			case 12:
				$otherMsg = '用户权限不足';//应用没有权限调用增值权限的接口，可在淘宝合作伙伴后台提交权限申请
				break;
			case 13:
				$otherMsg = '合作伙伴权限不足';//应用没有权限调用增值权限的接口，可在淘宝合作伙伴后台提交权限申请
				break;
			case 15:
				$otherMsg = '无法获取数据,确保您已开通直通车服务,并且正确授权';//应用没有权限调用增值权限的接口，可在淘宝合作伙伴后台提交权限申请
				break;
			case 21:
				$otherMsg = '缺少方法名参数';
				break;
			case 22:
				$otherMsg = '不存在的方法名';
				break;
			case 23:
				$otherMsg = '无效数据格式';
				break;
			case 24:
				$otherMsg = '缺少签名参数';
				break;
			case 25:
				$otherMsg = '无效签名';
				break;
			case 26:
				$otherMsg = '缺少授权参数,请重新授权';
				break;
			case 27:
				$otherMsg = '无效的授权参数,可能授权已过期,请重新授权';
				break;
			case 28:
				$otherMsg = '缺少AppKey参数';
				break;
			case 29:
				$otherMsg = '无效的AppKey参数';
				break;
			case 30:
				$otherMsg = '缺少时间戳参数';//传入的参数中必需包含timestamp参数
				break;
			case 31:
				$otherMsg = '非法的时间戳参数';//格式为yyyy-mm-dd hh:mm:ss 淘宝API服务端允许客户端请求时间误差为10分钟
				break;
			case 32:
				$otherMsg = '缺少SDK版本参数';
				break;
			case 33:
				$otherMsg = '非法的SDK版本参数';
				break;
			case 34:
				$otherMsg = '不支持的SDK版本号';
				break;
			case 40:
				$otherMsg = '调用API缺少必选参数';
				break;
			case 41:
				$otherMsg = 'API请求参数类型错误';
				break;
			case 42:
				$otherMsg = '短授权不可调用高危API';
				break;
			case 43:
				$otherMsg = '传入参数非法';
				break;
			case 47:
				$otherMsg = '编码错误,请使用UTF-8编码';
				break;
		}
		$msg .= $otherMsg;
		$msg .= '</span><br />';
		$msg .= '给您带来不便尽情谅解';
		//写入错误日志
		//$apierrlog = D('Apierrlog');
		//$apierrlog->writeErrLog($_SESSION['nick'], $apiName, $sdkClassName, $err['code'], $err['sub_code'], $err['msg'], $err['sub_msg'], $otherMsg);
		
		return $msg;
	}
	//用户信息
	protected function api_getSellerInfo(){
		$this->loadTopSdk('AlipayUserUserinfoShareRequest');
		$c = new AopClient();
		$c->appId = C('appID');
		$c->rsaPrivateKeyFilePath = C('private_key');
		$c->alipayPublicKey = C('alipay_public_key');
		$req = new AlipayUserUserinfoShareRequest();
		return $result = $c->execute ( $req,'',$_SESSION['sessionkey']); 
	}
	//查询授权信息
	public function api_getApiList(){
		$this->_init('AlipayOpenAuthTokenAppQueryRequest', $c, $req);
		$req->setBizContent("{\"app_auth_token\":\"{$_SESSION['sessionkey']}\"}");
		$res = $this->_exec($c, $req);
		$this->debug($res);
	}
	//***************************************************paystart
	/**
	
	Array
	(
		[code] => 0
		[msg] => Success
		[bill_download_url] => http://dwbillcenter.alipaydev.com/downloadBillFile.resource?bizType=trade&userId=20881021690584060156&fileType=csv.zip&bizDates=20161027&downloadFileName=20881021690584060156_20161027.csv.zip&fileId=%2Ftrade%2F20881021690584060156%2F20161027.csv.zip×tamp=1478747659&token=77651b840be37a3a7c29461117099b00
		[code_msg] => Success
	)
	*/
	protected function api_getBillDownUrl($bizContent){
		$this->_init('AlipayDataDataserviceBillDownloadurlQueryRequest', $c, $req);
		$req->setBizContent($bizContent);
		return $res = $this->_exec($c, $req);
	}
	protected function api_getOrderList(){
		$this->_init('KoubeiMarketingDataIndicatorQueryRequest', $c, $req);
		$req->setBizContent("{\"begin_date\":\"20161016\",\"end_date\":\"20161110\",\"page_num\":\"1\",\"page_size\":\"20\",\"biz_type\":\"CampaignQuery\"}");
		$res = $this->_exec($c, $req);
		$this->debug($res);
	}
	//查询订单alipay.trade.query
	protected function api_TradeQuery($bizContent){
		/*$this->loadTopSdk('AlipayTradeQueryRequest');
		$c = new AopClient();
		$c->appId = C('appID');
		$c->rsaPrivateKeyFilePath = C('private_key');
		$c->alipayPublicKey = C('alipay_public_key');
		$req = new AlipayTradeQueryRequest();
		$req->setBizContent($bizContent);
		return $result = $c->execute ( $req,'',$_SESSION['sessionkey']); 
		*/
		$this->_init('AlipayTradeQueryRequest', $c, $req);
		$req->setBizContent($bizContent);
		return $this->_exec($c, $req);
	}
	
	//订单退款alipay.trade.refund
	/**
	
	{"out_trade_no":"20161027100455010","trade_no":"2016102721001004260200157377","refund_amount":1,"refund_reason":"\u4f18\u60e0\u9000\u6b3e","out_request_no":"20161115110100000001"}
	debug调试输出:
		Array
		(
			[code] => 0
			[msg] => Success
			[buyer_logon_id] => scg***@sandbox.com
			[buyer_user_id] => 2088102169224265
			[fund_change] => Y
			[gmt_refund_pay] => 2016-11-15 11:09:46
			[open_id] => 20881016869765030743795552614026
			[out_trade_no] => 20161027100455010
			[refund_fee] => 1.00
			[send_back_fee] => 0.00
			[trade_no] => 2016102721001004260200157377
			[code_msg] => Success
		)
	*/
	protected function api_TradeRefund($bizContent){
		$this->_init('AlipayTradeRefundRequest', $c, $req);
		$req->setBizContent($bizContent);
		return $this->_exec($c, $req);
	}
	//***************************************************payend
	//获取门店的列表
	protected function api_getshoplist($bizContent){
		$this->_init('AlipayOfflineMarketShopBatchqueryRequest', $c, $req);
		$bizContent?$bizContent:$req->setBizContent($bizContent);
		return $this->_exec($c, $req);
	}
	
	//AlipayOfflineMarketShopQuerydetailRequest 单个门店的信息
	protected function api_getshopdetail($bizContent){
		$this->_init('AlipayOfflineMarketShopQuerydetailRequest', $c, $req);
		$req->setBizContent($bizContent);
		return $this->_exec($c, $req);
	}
	//上传图片
	protected function api_uploadImage($imageName,$imgContent,$imageType){
		$this->_init('AlipayOfflineMaterialImageUploadRequest', $c, $req);
		$req->setImageContent($imgContent);
		$req->setImageName($imageName);
		$req->setImageType($imageType);
		return $this->_exec($c, $req);
	}
	
	//刷新令牌
	protected function api_RefreshAuthToken($bizContent){
		$this->_needSessionkey=false;
		$bizContent['grant_type'] = 'refresh_token';
		$bizContent['refresh_token'] = $bizContent['refresh_token']?$bizContent['refresh_token']:$_SESSION['refresh_token'];
		$this->_init('AlipayOpenAuthTokenAppRequest', $c, $req);
		$req->setBizContent($bizContent);
		$resp=$this->_exec($c, $req);
		if(!$resp['code']){
			$_SESSION['expires_in'] = $resp['expires_in'];
			$_SESSION['sessionkey'] = $resp['app_auth_token'];
			$_SESSION['re_expires_in'] = $resp['re_expires_in'];
			$_SESSION['refresh_token'] = $resp['app_refresh_token'];
			$_SESSION['user_id'] = $resp['user_id'];
			$_SESSION['seller_id']=$_SESSION['user_id'];
			$_SESSION['auth_app_id'] = $resp['auth_app_id'];
			$this->_updateUserInfo(false,true);
		}
		return $resp;
	}
	/**
	 * 获取requestId
	 */
	protected  function getRequestId($id_num){
		return date("YmdHis",time())."SSS".$id_num;
	}

	//实施完成--自动流程中把要实施的店铺已经放入数据库 状态是未实施；
	//卖家授权登陆后查看下实施状态如果是未实施则调用此接口，然后再调用上架接口替卖家上架门店。
	protected function api_SerMarConfirmOrder($commodity_order_id,$shop_id){
		$this->_init('AlipayOpenServicemarketOrderItemConfirmRequest', $c, $req);
		$item = array("commodity_order_id"=>$commodity_order_id,"shop_id"=>$shop_id);
		$req->setBizContent(json_encode($item));
		return $this->_exec($c, $req);
	}
	//门店上架操作
	protected function api_SerMarShopOnline($commodity_id,$shop_id){
		$this->_init('AlipayOpenServicemarketCommodityShopOnlineRequest', $c, $req);
		$item = array("commodity_id"=>$commodity_id,"shop_id"=>$shop_id);
		$req->setBizContent(json_encode($item));
		return $this->_exec($c, $req);
	}

	/***20170320后新增**/

	/**
	 * 新活动创建
	 * 接口：koubei.marketing.campaign.activity.create
	 * 入参：$jsonstr 字符串例：
	 * $jsonstr = '{
			"out_biz_no": '.time().',
			"name":"代金券针对新人105",
			"start_time": "2017-03-19 00:00:00",
			"end_time": "2017-10-01 00:00:00",
			"type": "DIRECT_SEND",
			"constraint_info":{
			"user_win_count":"1",
			"suit_shops":["2016112600077000000023306679"],
			"crowd_restriction":"NEW_MEMBER_PROMO"
			},
			"promo_tools": [
			{
			"voucher": {
			"type": "MONEY",
			"voucher_note":"测试新人105",
			"name": "代金券105",
			"brand_name": "新人专享",
			"desc_detail_list": [
			{
			"title": "温馨提示",
			"details": [
			"周一到周五可用"
			]
			}
			],

			"use_instructions": [
			"券的使用说明"
			],
			"logo": "acbqGOVPS-eB_9ZPKJklmQAAACMAAQED",
			"validate_type": "RELATIVE",
			"relative_time": "7",
			"use_rule": {
			"suit_shops": [
			"2016112600077000000023306679"
			],
			"min_consume":"10"
			},
			"worth_value": "3",
			"effect_type": "IMMEDIATELY"
			},
			"send_rule": {
			"min_cost": "1",
			"send_num": "2",
			"allow_repeat_send": "false"
			}
			}
			],
			"publish_channels": [
			{
			"type": "SHOP_DETAIL",
			"name": "页面"
			}
			]
			}';
	 *
	 */
	protected function api_ActivityCreate($jsonstr){
		$this->_init('KoubeiMarketingCampaignActivityCreateRequest', $c, $req);
		$req->setBizContent($jsonstr);
		$req->setNotifyUrl(APP_URL.NOTIFYPHP_SELF.'/Auth/orderNotify');
		return $this->_exec($c, $req);
	}
	/**
	 * 活动查询接口
	 */
	protected function api_ActivityFind($jsonstr){
		$this->_init('KoubeiMarketingCampaignActivityQueryRequest', $c, $req);
		$req->setBizContent($jsonstr);
		return $this->_exec($c, $req);
	}
	/**
	 * 活动下架接口
	 * koubei.marketing.campaign.activity.offline
	 * $jsonstr={}
	 */
	protected function api_ActivityOffline($jsonstr){
		$this->_init('KoubeiMarketingCampaignActivityOfflineRequest', $c, $req);
		$req->setBizContent($jsonstr);
		$req->setNotifyUrl(APP_URL.NOTIFYPHP_SELF.'/Auth/orderNotify');
		return $this->_exec($c, $req);
	}
	/**
	 * 口碑查询
	 * koubei.marketing.campaign.activity.query
	 */
	protected function api_ActivityQuery($jsonstr){
		$this->_init('KoubeiMarketingCampaignActivityQueryRequest', $c, $req);
		$req->setBizContent($jsonstr);
		$req->setNotifyUrl(APP_URL.NOTIFYPHP_SELF.'/Auth/orderNotify');
		return $this->_exec($c, $req);
	}
	/**
	 * 会员查询
	 * koubei.marketing.campaign.crowd.count
	 */
	protected function api_MemberQuery($jsonstr){
		$this->_init('KoubeiMarketingCampaignCrowdCountRequest', $c, $req);
		$req->setBizContent($jsonstr);
		$req->setNotifyUrl(APP_URL.NOTIFYPHP_SELF.'/Auth/orderNotify');
		return $this->_exec($c, $req);
	}
	/**
	 * 口碑营销分析查询
	 	$data = array(
			"begin_date"=>date("Ymd",(time()-86400)),
			"end_date"=>date("Ymd",(time()-86400)),
			"biz_type"=> "CampaignQuery",
			"ext_info"=>array("camp_id"=>"20170322000000000717629000151691")
		);
	 * koubei.marketing.data.indicator.query
	 */
	protected function api_indicatorQuery($parm){
		$this->_init('KoubeiMarketingDataIndicatorQueryRequest', $c, $req);
		$req->setBizContent($parm);
		$res =  $this->_exec($c, $req);
		return $res;
	}
}













