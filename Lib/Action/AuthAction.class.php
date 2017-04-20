<?php

class AuthAction extends CommonAction {
	public $MongoDb;
	public function index() {
		$this->redirect('getAuth');
	}
	public function getAuth() {
		$jumpUrl = C('onlineip').C('appID').'&scope=p&redirect_uri='.C('REDIRECT_URI');
		header('Location: '.$jumpUrl);
	}
	public function logout() {
		$this->_destroyS();
		$jumpUrl = C('onlineip').C('appID').'&scope=p&redirect_uri='.C('REDIRECT_URI');
		header('Location: '.$jumpUrl);
	}
	private function getNotifyData(){
		$str = '{"contactor":"\u65b9\u5c0f\u59d0","commodity_order_id":"2016112300000004521724","notify_time":"2016-11-23 05:04:58","phone":"15921025911","biz_type":"\u5546\u54c1\u7ba1\u7406","order_item_num":"2","sign_type":"RSA","charset":"UTF-8","notify_type":"servicemarket_order_notify","total_price":"0.00","merchant_pid":"2088102044367672","version":"1.0","sign":"wYqoEy4jBcCm33BHURSYQ9rNWLR8PyS6Rq4vVd\/nWo3nVARre7JdQvryhwzW1w5TQYOZundTI71VdQ57QF8zDb5jehyFB67l5w+RuxHbASqVcKl2OL3nrG2YqlFxt\/Z\/QNrWFiMV6TAHb3WA5gBu2yNfElL9z+HscimJESOuP4s=","timestamp":"2016-11-23 05:04:58","title":"\u91d1\u725b\u5e97\u957f","order_time":"2016-11-23 05:04:58","name":"\u738b\u51ef\u7f8e","app_id":"2016102702351581","method":"alipay.open.servicemarket.order.notify","notify_id":"6f61935a48b5ab0ab1ad31cf898a95agru","_URL_":["Auth","notify"]}';
		return json_decode($str,true);
	}
	public function notify(){
		$data = $_REQUEST;
		unset($data['_URL_']);
		ksort($data);
		$string = '';
		foreach($data as $key => $val){
			if(!in_array($key,array('sign_type','sign'))){
				$string.=$key.'='.urldecode($val).'&';
			}
		}
		$string = substr($string,0,-1);
		$sign = str_replace(' ','+',$_GET['sign']);
		$sign = base64_decode($sign);
		$pubKey = file_get_contents(C('alipay_public_key'));
		$res = openssl_get_publickey($pubKey);
		$result = (bool)openssl_verify($string, $sign, $res);
		openssl_free_key($res);
		echo "success";
		if($result){
			file_put_contents("./cxchencksuccess.txt","验签成功\t\r\n",FILE_APPEND);
		}else{
			file_put_contents("./cxchenckfail.txt","验签失败\t\r\n",FILE_APPEND);
		}
		file_put_contents("./cxnotify.txt",json_encode($_REQUEST)."\t\r\n",FILE_APPEND);
		if($data['notify_type'] =='servicemarket_order_notify'){//卖家给服务商的订单
			$commodity_order_id = $data['commodity_order_id'];
			//TODO 1、先查询数据库判断该订单是否已经记录
			$this->setMongoObj();
			$this->MongoDb = $this->mongo->Serviceorderhycx;
			$where = array("commodity_order_id"=>$commodity_order_id);
			$serviceorderData = $this->MongoDb->find($where);//TODO 疑问 语句是否正确
			while ($serviceorderData->hasNext()){
				$items[] = $serviceorderData->getNext();
			}
			if(!empty($items)){
				return true;
			}
			//TODO 2、进行接单记录操作
			$res = $this->api_SerMarAccept($commodity_order_id);
			if($res['alipay_open_servicemarket_order_accept_response']['code'] == 10000){
				$orderData = $this->acceptOrder($commodity_order_id);
				if($orderData){
					//TODO 3、先存储mongo再进行接单自动同意
					$shop_ids = explode(",",$orderData['shop_ids']);
					foreach ($shop_ids as $vsi){
						$resComplete = $this->api_SerMarComplete($commodity_order_id,$vsi);
						if($resComplete['alipay_open_servicemarket_order_item_complete_response']['code'] == 10000){
							$newShop_ids[] = $vsi;
						}
					}
					if($newShop_ids){
						$orderData['shop_ids'] = implode(",",$newShop_ids);
					}else{
						$orderData['status'] =3;
					}
					$this->MongoDb->insert($orderData);
				}else{
					return false;
				}
			} else {
				return false;
			}
		}
	}
	/**
	 * 获取接单数据
	 */
	private function acceptOrder($commodity_order_id){
		$orderData['commodity_order_id'] = $commodity_order_id;
		$orderData['status'] =1;
		$orderData['createtime'] = time();
		$page_num  = 1;
		do{
			$flog = true;
			$res = $this->api_SerMarInfoQuery($commodity_order_id,$page_num);
			if(!empty($res['alipay_open_servicemarket_order_query_response']['order_items'])){
				$items = $res['alipay_open_servicemarket_order_query_response']['order_items'];
				foreach ($items as $ki=>$vi){
					$shop_ids[] =$vi['shop_id'];
				}
			}
			$orderData['commodity_id'] = $res['alipay_open_servicemarket_order_query_response']['commodity_id'];
			if($res['total_size'] <= ($res['current_page']*100)){
				$flog = false;
			}
			$page_num++;
		}while($flog);
		$orderData['shop_ids'] = implode(",",$shop_ids);
		return $orderData;
	}
	/**
	使用工具创建的优惠券创建订单或者核销时会发送通知到此处
	http://[ISV_NOTIFY_URL]?
	name=消费送&
	id=20161118000000000256856000151249
	&order_type=CREATE
	&sign=IuOu2dS5OfHomv1DBY4t1r//8yzGU/nJchdKJXQr8/AzMW6MmMg20jcudW6Q3eo9G/Urt7bii3Dmo4AJvMug0Uvge12WPwE9rWxkN473wn6vCa8bNLXcXJnui46LvwqPKYTghVt+43cL6y5VBWd9r1FjPhh/mcO0vaT7fyGlU6c=
	&order_process_time=2016-11-18 11:18:43
	&status=STARTED
	&event_code=EC-CAMPAIGN_PROCESS-CAMPAIGN_SYNCH
	&order_status=SUCCESS
	&order_id=20161118000000000932714000155246
	*/
	public function orderNotify(){
		file_put_contents("./cxorderNotify.txt",json_encode($_REQUEST)."\t\r\n",FILE_APPEND);
		$data = $_REQUEST;
		unset($data['_URL_']);
		ksort($data);
		$string = '';
		foreach($data as $key => $val){
			if(!in_array($key,array('sign_type','sign'))){
				$string.=$key.'='.urldecode($val).'&';
			}
		}
		$string = substr($string,0,-1);
		$sign = str_replace(' ','+',$_GET['sign']);
		$sign = base64_decode($sign);
		$pubKey = file_get_contents(C('alipay_public_key'));
		$res = openssl_get_publickey($pubKey);
		$result = (bool)openssl_verify($string, $sign, $res);
		openssl_free_key($res);
		echo "success";
		if($result){
			file_put_contents("./cxchencksuccess.txt","验签成功\t\r\n",FILE_APPEND);
		}else{
			file_put_contents("./cxchenckfail.txt","验签失败\t\r\n",FILE_APPEND);
		}
		if($data['notify_type'] =='koubei_trade_ext_notify'){
			if(!empty($data['order_operator_type'])){
				file_put_contents("./notify_order_operator_type.txt",date("Y-m-d H:i:s",time())."==>".json_encode($_REQUEST)."\t\r\n",FILE_APPEND);
			}
			$order_principal_id = $data['order_principal_id'];
			$map['seller_id'] = $order_principal_id;
			$user = M("User")->where($map)->field("sessionkey,seller_id")->find();
			if(empty($user['sessionkey'])){
				return;
			}
			$order_no = $data['order_no'];
			$orderData = $this->ajaxGetOrderInfo($order_no,$user['sessionkey']);
			if(!$orderData){
				return;
			}
			foreach ($orderData['vouchers'] as $itk=>$itv){
				$item_id[] = $itv['item_id'];
			}
			$shops = $orderData['shop'];
			if(is_array($shops[0])){
				foreach ($shops as $ks=>$vs){
					$shop_id[] = $vs['shop_id'];
				}
				$orderData['shop_id'] = implode(",",$shop_id);
			}else{
				$orderData['shop_id'] = $shops['shop_id'];
			}
			$orderData['shop'] = json_encode($orderData['shop']);
			$funds_vouchers = $orderData['funds_vouchers'];
			$len = count($funds_vouchers);
			$orderData['funds_vouchers'] = json_encode($funds_vouchers);
			$orderData['vouchers'] = json_encode($orderData['vouchers']);
			$orderData['activity_infos'] = json_encode($orderData['activity_infos']);
			$orderData['contact'] = json_encode($orderData['contact']);
			$orderData['gmt_create'] = strtotime($orderData['gmt_create']);
			$orderData['gmt_modified'] = strtotime($funds_vouchers[$len-1]['gmt_create'])?strtotime($funds_vouchers[$len-1]['gmt_create']):strtotime($orderData['gmt_create']);
			if(!empty($data['order_operator_type'])){
				$orderData['settle_type'] = $data['order_operator_type'];
			}
			$res = M("Order")->where(array("order_no"=>$order_no))->find();
			if($res){
				$res =M("Order")->where(array("order_no"=>$order_no))->save($orderData);
			}else{
				$res =M("Order")->add($orderData);
			}
			return $res;
		}
		if($data['notify_type'] =='servicemarket_order_notify'){//卖家给服务商的订单
			$commodity_order_id = $data['commodity_order_id'];
			//TODO 2、进行接单记录操作
			$res = $this->api_SerMarAccept($commodity_order_id);
			if($res['alipay_open_servicemarket_order_accept_response']['code'] == 10000){
				$orderData = $this->acceptOrder($commodity_order_id);
				if($orderData){
					$shop_ids = explode(",",$orderData['shop_ids']);
					foreach ($shop_ids as $vsi){
						$resComplete = $this->api_SerMarComplete($commodity_order_id,$vsi);
						if($resComplete['alipay_open_servicemarket_order_item_complete_response']['code'] == 10000){
							$newShop_ids[] = $vsi;
						}
					}
					if($newShop_ids){
						$orderData['shop_ids'] = implode(",",$newShop_ids);
					}else{
						$orderData['status'] =3;
					}
				}else{
					return false;
				}
			} else {
				return false;
			}
		}
		

	}

	/**
	 * 查找单个订单
	 */
	public function ajaxGetOrderInfo($order_no,$usertokey){
		if(empty($order_no)){
			$this->ajaxReturn("","请选择订单",0);
		}
		$data['order_no'] = $order_no;
		$this->loadTopSdk('KoubeiTradeOrderQueryRequest');
		$c = new AopClient();
		$c->appId = C('appID');
		$c->rsaPrivateKeyFilePath = C('private_key');
		$c->alipayPublicKey = C('alipay_public_key');
		$req = new KoubeiTradeOrderQueryRequest();
		$req->setBizContent(json_encode($data));
		$resp = $c->execute($req,"",$usertokey);
		$resp = objecttoarray($resp);
		return $resp['koubei_trade_order_query_response'];
	}
	private function _getISVsession(){
		$m = M('User');
		$condition['seller_id'] = '2088711005018696';//这是泰岳兴洋的
		//$condition['seller_id'] = '2088521139596252';//这是扫货郎商城的
		$condition['operator_id'] = '2088711005018696';	
		$data = array();
		$res = $m->where($condition)->find();
		
		if (!empty($res)){
			$this->_skey = $res['sessionkey'];
		}
	}
	/**
	获取下登陆者的授权信息  看看以后要不要
	如果expr_in_end过期了还要重新授权呢；
	 */
	private function _getUserOauthInfo(){
		$m = M('User');
		$condition['seller_id'] = $_SESSION['seller_id'];
		//$condition['operator_id'] = $_SESSION['seller_id'];		
		$res = $m->where($condition)->find();
		if (!empty($res)){
			$_SESSION['sessionkey'] = $res['sessionkey'];
			$data = $finaldata = array();
			//$data['operator_type'] = $_SESSION['operator_type'];
			//$data['auth_code'] = $_SESSION['auth_code'];
			$data['time'] = time();
			$finaldata['auth_code'] = json_encode($data);
			$m->where($condition)->save($finaldata);
			return true;
		}else{
			//$_sql = $m->_sql();
			//file_put_contents("./_getUserOauthInfo.txt",date("Y-m-d H:i:s").'--'.json_encode($_SESSION).$_sql."\t\r\n",FILE_APPEND);
			if($_SESSION['operator_type']=='MERCHANT'){
				$this->redirect('getAuth');
			}else{
				$this->error('请让商户登录授权金牛会员促销，再由你进行活动的管理等操作。');
			}
			return false;
		}
	}
	private function _setToken(){
		$token = md5($_SESSION['seller_id'].'-'.time());
		$_SESSION['tokenarray'][] = $token;//登录过后加入session 防止复制链接访问 
		$data = array();
		$this->setMongoObj();
		$user = $this->mongo->user;
		$data['token'] = $token;
		$data['seller_id'] = $_SESSION['seller_id'];
		$data['sessionkey'] = $_SESSION['sessionkey'];
		$data['operator_type'] = $_SESSION['operator_type'];
		$data['operator_id'] = $_SESSION['operator_id'];
		$data['create_time'] = time();
		$user->insert($data);
		return $token;
	}
	/*
	Array
(
    [app_id] => 2016072900120717
    [app_auth_code] => a2550e2071f24704a33856ab0585dD40
    [expires_in] => 31536000
    [sessionkey] => 201610BB109345c3ec3d4a1f8f976661ff0b1X40
    [re_expires_in] => 32140800
    [refresh_token] => 201610BB0e18068979d34934858a1e88f0c82X40
    [user_id] => 2088102169058406
    [seller_id] => 2088102169058406
    [auth_app_id] => 2016072900120717
    [sign] => iuvcRAht6mcLL1qp7gUYbzPI4ABwL8kyCJseBiCuYjALxyYXxZUMsWgchjeei8dqmsnJToP9D6w6wz0/vm2fKNvPUSB9MxlHWkbRic1139O/7Q8GvoL4XnaL5DFJ8H29WcsPAa0tyiwfe8WK7RuJ/+ADYdn5rhGxumPOqCDF43w=
    [expires_time] => 1477532937
)
	*/
	public function urlReturn() {
		$agent = $_SERVER['HTTP_USER_AGENT'];
		if(isset($_GET['app_id']) && isset($_GET['auth_code'])){
			$_SESSION['app_id'] = $_GET['app_id'];
			$_SESSION['auth_code'] = $_GET['auth_code'];
			setcookie("auth_code",$_GET['auth_code'], time()+3600*24,'/');
			$_SESSION['source'] = $_GET['source'];
			$_SESSION['scope'] = $_GET['scope'];
			$_SESSION['auth_type'] = $_GET['auth_type'];

			$this->_getISVsession();//使用咱们自己的授权码就能查到所有人的基本信息。

			/*[app_id] => 2016102702351581
				[scope] => auth_base
				[source] => koubei
				[auth_type] => pay_member
				[auth_code] => 186731a6b65f4d5e85fd07ce81d93X69*/
			$this->loadTopSdk('KoubeiMemberDataOauthQueryRequest');
			$client = new AopClient();
			$client->appId = C('appID');
			$client->rsaPrivateKeyFilePath = C('private_key');
			$req=new KoubeiMemberDataOauthQueryRequest();
			$authdata['auth_type'] = $_GET['auth_type'];
			$authdata['code'] = $_GET['auth_code'];
			$jsonstr = json_encode($authdata);
			$req->setBizContent($jsonstr);
			/*Array
			(
				[koubei_member_data_oauth_query_response] => Array
					(
						[code] => 10000
						[msg] => Success
						[operator_type] => MER
						[operator_id] => 2088711005018696
						[operator_partner_id] => 2088711005018696
					)
			)*/
			$res = $client->execute($req,'',$this->_skey);
			$data = objectToArray($res);
			if(!isset($data['koubei_member_data_oauth_query_response']['operator_type'])){
				$this->redirect('getAuth');
				exit;
			}
			$_SESSION['operator_type']=$data['koubei_member_data_oauth_query_response']['operator_type']=='MER'?'MERCHANT':$data['koubei_member_data_oauth_query_response']['operator_type'];;
			$_SESSION['user_id']=$_SESSION['operator_id']=$data['koubei_member_data_oauth_query_response']['operator_id'];
			$_SESSION['seller_id']=$_SESSION['partner_id']=$_SESSION['operator_partner_id']=$data['koubei_member_data_oauth_query_response']['operator_partner_id'];
			if($_SESSION['operator_type']!='MERCHANT' && !empty($_SESSION['operator_type'])){
				$_SESSION['operator_seller'][] = $_SESSION['seller_id'];
				$_SESSION['operator_seller'] = array_unique($_SESSION['operator_seller']);
			}
			$res = $this->_getUserOauthInfo();
			if(!$res){
				if($_SESSION['operator_type']=='MERCHANT'){
					$this->redirect('getAuth');
				}else{
					$this->error('请让商户登录授权金牛会员促销，再由你进行活动的管理等操作。');
				}				
			}
			$token = $this->_setToken();
			if($_GET['merchantType']=='ALIPAY' || strpos($agent,'AlipayClient')!== false){//通过移动端过来的
				header('Location: '.APP_URL.'/mobile.php/Index/index?token='.$token);exit;
			}else{
				$this->redirect('Index/index?token='.$token);exit;
			}
		}elseif(isset($_GET['app_id']) && isset($_GET['app_auth_code'])){
			$_SESSION['app_id'] = $_GET['app_id'];
			$_SESSION['app_auth_code'] = $_GET['app_auth_code'];
			
			$this->loadTopSdk('AlipayOpenAuthTokenAppRequest');
			$client = new AopClient();
			
			$client->appId = $_GET['app_id'];
			$client->rsaPrivateKeyFilePath = C('private_key');
			$client->alipayPublicKey = C('alipay_public_key');
			$req=new AlipayOpenAuthTokenAppRequest();
			// 根据授权码取授权令牌
			$biz = array();
			$biz['grant_type'] = 'authorization_code';
			$biz['code'] = $_GET['app_auth_code'];
			$req->setBizContent(json_encode($biz));
			$res = $client->execute($req);
			$data = objectToArray($res);
			$_SESSION['expires_in'] = $data['alipay_open_auth_token_app_response']['expires_in'];
			$_SESSION['sessionkey'] = $data['alipay_open_auth_token_app_response']['app_auth_token'];
			$_SESSION['re_expires_in'] = $data['alipay_open_auth_token_app_response']['re_expires_in'];
			$_SESSION['refresh_token'] = $data['alipay_open_auth_token_app_response']['app_refresh_token'];
			$_SESSION['user_id'] = $data['alipay_open_auth_token_app_response']['user_id'];
			$_SESSION['operator_id'] = $_SESSION['seller_id']=$_SESSION['user_id'];
			$_SESSION['auth_app_id'] = $data['alipay_open_auth_token_app_response']['auth_app_id'];
			$_SESSION['operator_type'] = 'MERCHANT';
			$_SESSION['sign'] = $data['sign'];
			$_SESSION['expires_time']=time();
			$this->_updateUserInfo();
			$token = $this->_setToken();
			if($_GET['merchantType']=='ALIPAY' || strpos($agent,'AlipayClient')!== false){//通过移动端过来的
				header('Location: '.APP_URL.'/mobile.php/Index/index?token='.$token);exit;
			}
			$this->redirect('Index/index?token='.$token);
			exit;
		} elseif(isset($_GET['error'])) {
			if($_GET['error_description'] == 'authorize reject') {
				$this->assign('jumpUrl', APP_URL);
				$otherMsg = '['.$_GET['error'].']'.$_GET['error_description'];
			}
			
			$this->assign('waitSecond', 10);
			$this->error('授权发生错误:'.$otherMsg);
		} else {
			$jumpUrl = C('onlineip').C('appID').'&redirect_uri='.C('REDIRECT_URI');
			$this->assign('jumpUrl', $jumpUrl);
			$this->assign('waitSecond', 3);
			$this->error('淘宝授权错误,为了您的账户安全,需要您重新授权.');
//			header('Location: '.$jumpUrl);
		}
		
	}
}
