<?php

class ActivityAction extends BasicAction {
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 活动首页
	 */
	public function index(){
		$this->display();
	}
	/**
	 * 活动首页
	 */
	public function details(){
		$time = $this->getSelectTime();
		$this->assign("start_time",date("Y-m-d",$time[0]));
		$this->assign("end_time",date("Y-m-d",$time[1]));
		$this->display();
	}
	/**
	 * 获取详情
	 */
	public function ajaxGetDetailsDataList(){
		$this->checkAjax();
		$page_size = $_POST['page_size']?$_POST['page_size']:10;
		$page_num = $_POST['page_num']?$_POST['page_num']:1;
		$shopList = $_SESSION['shoplist'];
		foreach ($shopList as $k=>$v){
			$shop_ids[] = $v['shop_id'];
		}
		$time = $this->getSelectTime();
		$map['endtime'] = array("between",$time);
		$map['shop_id'] = array("in",$shop_ids);
		$count = M("Order")->where($map)->count();
		$page = ceil($count/$page_size);
		$data = M("Order")->where($map)->page($page_num,$page_size)->select();
		foreach ($data as $k=>$v){
			$data[$k]['endtime'] =  date("Y-m-d",$v['endtime']);
			if ($v['refundtime']){
				$data[$k]['refundtime'] =  date("Y-m-d",$v['refundtime']);
			} else{
				$data[$k]['refundtime'] = "";
			}
		}
		if($data){
			$res['page'] = $page;
			$res['dataList'] = $data;
			$this->ajaxReturn($res,"数据加载成功",1);
		}else{
			$res['page'] = 0;
			$res['dataList'] = array();
			$this->ajaxReturn($res,"暂无数据",1);
		}
	}
	/**
	 * 获取线状态 数据 计算返现数据
	 */
	public function ajaxGetLineList(){
		$this->checkAjax();
		$shopList = $_SESSION['shoplist'];
		foreach ($shopList as $k=>$v){
			$shop_ids[] = $v['shop_id'];
		}
		$map['status'] = 2 ;
		$map['shop_id'] = array("in",$shop_ids);
		$time = $this->getSelectTime();
		$map['refundtime'] = array("between",$time);
		$data = M("Order")->where($map)
			->field('FROM_UNIXTIME(refundtime,"%Y-%m-%d") up_date,FROM_UNIXTIME(refundtime,"%m-%d") refundtime ,count("id") count,sum(refund_fee) refund_fee')
			->group("up_date")
			->order('refundtime DESC')
			->select();
		if($data){
			$res['datalist'] = $data;
			$this->ajaxReturn($res,"数据加载成功",1);
		}else{
			$res['datalist'] = array();
			$this->ajaxReturn($res,"暂无数据",1);
		}
	}
	/**
	 * 活动创建
	 */
	public function create(){
		$shopList = $_SESSION['shoplist'];
		$this->assign("shopList",json_encode($shopList));
		$this->display();
	}
	/**
	 * 活动修改
	 */
	public function modify(){
		$act_id = $_REQUEST['act_id'];
		$res = D("Activity")->findTableByID($act_id);
		if(empty($res)){
			$this->error("未查找到相关活动记录");
			return;
		}
		$res['start_time'] = date("Y-m-d H:i",$res['start_time']);
		$res['end_time'] = date("Y-m-d H:i",$res['end_time']);
		$this->assign("activity",json_encode($res));
		$shopList = $_SESSION['shoplist'];
		$this->assign("shopList",json_encode($shopList));
		$this->display();
	}

	/**
	 * 创建活动
	 */
	public function ajaxCreateActivity(){
		$this->checkAjax();
//		$this->checkLogin();
		$res = D("Activity")->addTable();
		if(!$res['status']){
			$this->ajaxReturn("",$res['info'],0);
		}else{
			$this->ajaxReturn($res['data'],$res['info'],1);
		}
	}
	/**
	 * 修改活动
	 */
	public function ajaxModifyActivity(){
		$this->checkAjax();
//		$this->checkLogin();
		$res = D("Activity")->modifyTable();
		if(!$res['status']){
			$this->ajaxReturn("",$res['info'],0);
		}else{
			$this->ajaxReturn($res['data'],$res['info'],1);
		}
	}
	/**
	 * 修改结束时间
	 * 和结束状态
	 */
	public function ajaxModifyStatus(){
		$act_id = $_REQUEST['act_id'];
		$status = $_POST['status'];
		if(empty($act_id)){
			$this->ajaxReturn("","请选择要修改的活动",0);
		}
		if(empty($status)){
			$this->ajaxReturn("","请选择要执行的操作",0);
		}
		$map['id'] = $act_id;
		$data['status'] = $status;
		$res =M("Activity")->where($map)->save($data);
		if($res){
			$this->ajaxReturn($res,"修改成功",1);
		}else{
			$this->ajaxReturn(M("Activity")->_sql(),"修改失败",0);
		}
	}
	/**
	 * 查询活动列表
	 */
	public function ajaxGetActivityList(){
		$this->checkAjax();
		$res = D("Activity")->selectTable();
		if(empty($res)){
			$data['dataList'] = array();
			file_put_contents("./log/error_ActList.txt",D("Activity")->_sql());
			$this->ajaxReturn($data,"暂无数据",1);
		}else{
			foreach ($res as $k=>$v){
				if($v['end_time']<time()){
					$map['id'] = $v['id'];
					$data['status'] = 2;
					M("Activity")->where($map)->save($data);
					$res[$k]['order_status'] = 2;
				}else{
					$res[$k]['order_status'] = $v['status'];
				}
				$res[$k]['start_time'] = date("Y-m-d",$v['start_time']);
				$res[$k]['end_time'] = date("Y-m-d",$v['end_time']);
			}
			$data['dataList'] = $res;
			$this->ajaxReturn($data,"加载成功",1);
		}
	}
	/**
	 * 查找时间 默认查找30天内的
	 */
	private function getSelectTime(){
		$start_time = $_POST['start_time'];
		$end_time= $_POST['end_time'];
		//$start_time = "2016-08-15";
		//$end_time = "2016-08-22";
		/*****************计算查找的开始时间和结束时间******************/
		if(empty($start_time) && !empty($end_time)){
			$end_time = strtotime($end_time);
			$start_time = ($end_time - 30*86400);//默认查找30天以内的
			$end_time = $end_time+86390;
		}
		if(!empty($start_time) && empty($end_time)){
			$start_time = strtotime($start_time);
			$end_time = ($start_time + 30*86400-1);//默认查找30天以内的
		}
		if(!empty($start_time) && !empty($end_time)){
			$start_time = strtotime($start_time);
			$end_time = (strtotime($end_time) + 86390);
		}
		if(empty($start_time) && empty($end_time)){
			$start_time = (time() - 30*86400) ;//默认查找30天以内的
			$end_time = time();
		}
		$arr[] = $start_time;
		$arr[] = $end_time;
		return $arr;
	}

	/**
	 * 单独点击发送请求
	 */
	public function ajaxSendMsg(){
		$order_id = $_POST['order_id'];
//		$order_id = 3;
		if(empty($order_id)){
			$this->ajaxReturn("","请选择要推送消息的订单",0);
		}
		$orderMap['id'] = $order_id;
		$v = M("Order")->where($orderMap)->find();
		if($v['status'] != 1 ){
			$this->ajaxReturn("","该订单已推送过消息，请勿重新推送",0);
		}
		$activity = D("Activity")->findTable();
		if($v['real_fee']<$activity['min_fee'] || $activity['end_time']<time()){
			//订单金额小于限定金额，活动已结束，
			$map =array();
			$map['id'] = $v['id'];
			$orderData['status'] = 4;//不符合返现条件
			M("Order")->where($map)->save($orderData);
			$this->ajaxReturn("","不符合拉单推送条件",0);
		}
//		$shareMsg = D("Share")->findTableForBuyer($v['buyer_account'],$activity['id'],$v['endtime']);//查找此活动的分享者里面是否有此账号
//		if (!empty($shareMsg)){//该用户不是参与者
//			$this->ajaxReturn("","该用户是拉单人员",0);
//		}
		$model = new Model();
		$model->startTrans();
		$map =array();
		$map['id'] = $v['id'];
		$orderData['msg_status'] = 2;
		$orderData['refund_trade_no'] = "re".$v['out_biz_no'];
		if($activity['reStatus'] == 1){
			$refund = $activity['reNum'];
		}else{
			$refund = $this->getRefundFee($v['real_fee'],$activity['reNum']);
		}
		$orderData['refund_fee'] = $refund;
		M("Order")->where($map)->save($orderData);
		/**发送消息**/
		$res = $this->sendMsg($v,$activity);//TODO 执行发送信息
		if(!$res['code']){
			$model->commit();
			$this->ajaxReturn("","消息推送成功",1);
		}else{
			$model->rollback();
			$this->ajaxReturn("",$res['code_msg'],0);
		}
	}
	/**
	 * 单独点返现
	 */
	public function ajaxRefund(){
		$order_id = $_POST['order_id'];
		if(empty($order_id)){
			$this->ajaxReturn("","请选择要推送消息的订单",0);
		}
		$orderMap['id'] = $order_id;
		$v = M("Order")->where($orderMap)->find();
		$activity = D("Activity")->findTable();

		$model = new Model();
		$model->startTrans();
		$map =array();
		$map['id'] = $v['id'];
		$orderData =array();
		$orderData['msg_status'] = 2;
		$orderData['status'] = 2;
		$orderData['refundtime'] = time();
		$orderData['refund_trade_no'] = "re".$v['out_biz_no'];
		if($activity['reStatus'] == 1){
			$refund = $activity['reNum'];
		}else{
			$refund = $this->getRefundFee($v['real_fee'],$activity['reNum']);
		}
		if($refund <= 0 || $refund <=0.00){
			$this->ajaxReturn("","退款金额不能低于0.01元",0);
		}
		$orderData['refund_fee'] = $refund;
		M("Order")->where($map)->save($orderData);
		//TODO 执行返现活动
		$res = $this->orderRefund($v,$orderData);
		if(!$res['code']){
			$model->commit();
			$this->ajaxReturn("","发放成功",1);
		}else{
			$model->rollback();
			$this->ajaxReturn("",$res['code_msg'],0);
		}
	}
}

?>
