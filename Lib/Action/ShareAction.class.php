<?php

class ShareAction extends BaseAction {
	public function __construct()
	{
		parent::__construct();
		$this->assign("app_url",APP_URL);
		$this->assign("app_self",PHP_SELF);
	}
	public function errorShare($msg){
		$this->assign("error",$msg);
		$this->display("errorshare");
	}
	/**
	 * 从店铺进入时
	 */
	public function shop(){
		$seller_id = $_GET['merchant_pid'];
		$act = D("Activity")->findTableByShopId($seller_id);
		if(empty($act) || $act['end_time']<time() || empty($_GET['shop_id'])){
			$flog = false;
		}else{
			$flog = true;
			$this->assign("activity",json_encode($act));
		}
		$this->assign("isHaveAct",$flog);//isHaveAct = true 正常 isHaveAct=false 不能分享
//		$this->assign("seller_id",$seller_id);//卖家ID
		$this->assign("shop_id",$_GET['shop_id']);
		$this->display("shop");
	}
	/**
	 * 老用户进入分享页面
	 */
	public function share(){
		if(empty($_SESSION['buyer_id'])){
			$msg="未登录，请先登录";
			$this->errorShare($msg);exit();
		}

		$seller_id= $_REQUEST['seller_id'];
		if(!empty($seller_id)){
			$act = D("Activity")->findTableByShopId($seller_id);
			if(empty($act)){
				$msg="暂未查找到相关活动";
				$this->errorShare($msg);exit();
			}
		}else{
			$msg="请刷新重试";
			$this->errorShare($msg);exit();
		}
		$order = M("Order")->where(array("id"=>$_REQUEST['order_id']))->find();
		if($_SESSION['buyer_id']!=$order['buyer_account']){
			$this->index();exit();
		}
		if($act['reStatus']==2){
			$num = $this->getRefundNum($act['reNum']);
			$act['reNum'] = $num;
		}
		if(empty($order) || $act['end_time']<time() || $order['endtime']<(time()-$act['limit_day']*86400)){
			$flog = false;
		}else{
			$flog = true;
		}
		$this->assign("isHaveAct",$flog);//isHaveAct = true 正常 isHaveAct=false 不能分享
//		$this->assign("out_trade_no",$order['out_biz_no']);
		$this->assign("order_id",$order['id']);
		$this->assign("seller_id",$order['seller_id']);//卖家ID
		$this->assign("activity",json_encode($act));
		$this->display('share');
	}
	private function getRefundNum($num){
		$arr = explode(".",$num);
		if(intval($arr[0])>0){
			$first = $arr[0];
		}else{
			$first = '0';
		}
		if ($arr[1]!='00'){
			$str1 = substr($arr[1],1,1);
			if (intval($str1) != 0){
				$secord = ".".$arr[1];
			}else{
				$str2 = substr($arr[1],0,1);
				if(intval($str2) !=0 ){
					$secord = ".".$str2;
				}else{
					$secord = '';
				}
			}
		}else{
			$secord = "";
		}
		return $first.$secord;
	}
	/**
	 * 新用户点击分享链接后显示页面
	 */
	public function index(){
		if(empty($_REQUEST['order_id'])){
			$msg="系统繁忙，请刷新后重试";
			$this->errorShare($msg);exit();
		}
		$orderMap['id'] = $_REQUEST['order_id'];
		$order = M("Order")->where($orderMap)->find();
		if($_SESSION['buyer_id'] == $order['buyer_account']){
			$this->share();
			exit;
		}

		if(empty($_SESSION['buyer_id'])){
			$msg="用户ID为空";
			$this->errorShare($msg);exit();
		}
		$act = D("Activity")->findTableBySellerId($order['seller_id']);
		if($act['end_time']<time()){
			$flog = false;
		}else{
			$flog = true;
		}
		if($act['reStatus']==2){
			$num = $this->getRefundNum($act['reNum']);
			$act['reNum'] = $num;
		}
		$this->assign("isHaveAct",$flog);//isHaveAct = true 活动正常 isHaveAct=false 活动已结束
		if($act['status'] == 2){//只允许新用户参加此有奖活动
			$resOrder = M("Order")->where(array("buyer_account"=>$_SESSION['buyer_id']))->select();
			$shareMap['_string'] = "buyer_id = ".$_SESSION['buyer_id']." or share_id=".$_SESSION['buyer_id'];
			$resShare = M("Share")->where($shareMap)->select();
			if(!empty($resOrder) || !empty($resShare)){
				$msg="您已参加过该店的拉单返现活动";
				$this->errorShare($msg);exit();
			}
		}
		$shareData = $this->saveShareMsg($act,$order);
		$Map['buyer_id'] = $_SESSION['buyer_id'];
		$Map['share_id'] = $order['buyer_account'];
		$Map['out_trade_no'] = $order['out_biz_no'];
		$res = D("Share")->where($Map)->find();
		if(!$res){
			if(!$shareData['status'] || empty($shareData['data'])){
				$msg=$shareData['info'];
				$this->errorShare($msg);exit();
			}
			$res = D("Share")->addTable($shareData['data']);
		}
		if($res){
			$this->assign("shop_id",$order['shop_id']);
			$this->assign("activity",json_encode($act));
			$this->display("index");
		}else{
			$msg="数据存储错误";
			$this->errorShare($msg);exit();
		}
	}
	/**
	 * 获取分享信息
	 */
	private function saveShareMsg($act,$order){
		$res['status'] = true;
		if(!empty($_SESSION['buyer_id'])){
			$data['buyer_id'] = $_SESSION['buyer_id'];
		}else{
			$res['status'] = false;
			$res['info'] = "用户ID为空";
			return $res;
		}

		if(!empty($order['buyer_account'])){
			$data['share_id'] = $order['buyer_account'];
		}else{
			$res['status'] = false;
			$res['info'] = "没有查找到分享者ID";
			return $res;
		}
		if(!empty($act['id'])){
			$data['act_id'] = $act['id'];
		}else{
			$res['status'] = false;
			$res['info'] = "活动Id错误";
			return $res;
		}
		if(!empty($order['out_biz_no'])){
			$data['out_trade_no'] = $order['out_biz_no'];
		}else{
			$res['status'] = false;
			$res['info'] = "支付宝交易单号不能为空";
			return $res;
		}
		if(!empty($act)){
			$data['seller_id'] = $act['seller_id'];
		}else{
			$res['status'] = false;
			$res['info'] = "没有相关活动";
			return $res;
		}
		if($res['status']){
			$data['shop_id'] = $order['shop_id']?$order['shop_id']:$act['shop_id'];
			$data['createtime'] = time();
			$res['data'] = $data;
			$res['activity'] = $act;
		}else{
			$res['data'] = array();
			$res['info'] = "系统错误，用户数据记录错误";
		}
		return $res;
	}

	/**
	 * 分享者检测是否已关注了店铺
	 */
	public function ajaxCheckFollowShare(){
		$this->checkFollow(true);
	}
	/**
	 * 参与者检测是否已关注了店铺
	 */
	public function ajaxCheckFollowIndex(){
		$this->checkFollow(false);
	}
	private function checkFollow($isShare){
		$seller_id = $_POST['seller_id'];//TODO  POST
		$followMap['seller_id'] =$seller_id;
		$followMap['buyer_id'] = $_SESSION['buyer_id'];
		$res = M("Checkfollow")->where($followMap)->find();
		if($res){
			$data = array("isFollow"=>true);
			$this->ajaxReturn($data,"页面跳转",1);
		}
		M("Checkfollow")->add($followMap);
		$activityMap = array(
			"seller_id"=>$seller_id,
		);
		$act = M("Activity")->where($activityMap)->find();
		$orderMap['buyer_account'] = $_POST['share_id'];//TODO  POST
		$orderMap['out_trade_no'] = $_POST['out_trade_no'];//TODO  POST
		$order = M("Order")->where($orderMap)->find();
		if($isShare){//先判断是不是分享者 如果是
			if(empty($order)){
				$this->ajaxReturn("","请先购买商品，第二天再进行分享",0);
			}
			$url=APP_URL.PHP_SELF."/Share/share?seller_id=".$order['seller_id']."&order_id=".$order['id'];
		}else{
			$url=APP_URL.PHP_SELF."/Share/share?seller_id=".$seller_id."&order_id=".$_SESSION['order_id'];
		}
		$to_user_id = $_SESSION['buyer_id'];//只要走到这里都是有buyer_id数据的
		$userMap = array(
			"seller_id"=>$seller_id,
		);
		$user = M("User")->where($userMap)->find();
		if($user['template_id']){
			$msg_template_id = $user['template_id'];
		}else{
			$res = $this->api_getMsgTem();
			if($res['code']){
				return false;
			}
			$msg_template_id = $res['msg_template_id'];
			$userMap =array();
			$userMap['id'] = $user['id'];
			$userData['template_id'] = $msg_template_id;
			M("User")->where($userMap)->save($userData);
		}

		$keyword1=$act['actName'];//活动名称
		if($isShare){
			$first="拉来不低于".$act['min_fee']."元的订单";
			if($act['reStatus'] == 1){
				$refund = $act['reNum']."元";
				$remark="您将获得".$refund."的返现";
			}elseif($act['reStatus'] == 2){
				$refund = $act['reNum']."%";
				$remark="您将获得实付金额".$refund."的返现";
			}else{
				return false;
			}
		}else{
			$first="购买不低于".$act['min_fee']."元的订单";
			if($act['reStatus'] == 1){
				$refund = $act['reNum']."元";
				$remark="您将获得".$refund."的返现";
			}elseif($act['reStatus'] == 2){
				$refund = $act['reNum']."%";
				$remark="您将获得实付金额".$refund."的返现";
			}else{
				return false;
			}

		}
		$keyword2=date("Y-m-d H:i:s",$act['start_time'])."到".date("Y-m-d H:i:s",$act['end_time']);//活动时间
		$template_id =$msg_template_id;
//		$template_id ="bced2b719216484b9f090df5c840b558";
		$_SESSION['sessionkey'] = $act['sessionkey'];
		$res = $this->api_SendActMsg($to_user_id,$url,$keyword1,$keyword2,$first,$remark,$template_id);
		if($res['code'] == '40004'){
			$data = array("isFollow"=>false);
			$this->ajaxReturn($data,"请先关注店铺再执行操作",1);
		}elseif($res['code'] == '0'){
			$data = array("isFollow"=>true);
			$this->ajaxReturn($data,"页面跳转",1);
		}else{
			$this->ajaxReturn("",$res['sub_msg']."请刷新后重试",0);
		}
	}
}

?>
