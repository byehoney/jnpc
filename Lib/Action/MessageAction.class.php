<?php
header("Content-type:text/html;charset=utf8");
class MessageAction extends BaseAction {
	public function __construct()
	{
		parent::__construct();
		$this->_nocheckSellerPermisson = array('message','share');
	}

	/**
	 * 定时轮询
	 * 查找符合条件的订单
	 */
	public function getOrderList(){
//		if(!in_array(date("H"),array("10","15","16","17","18","19","20","21"))){
//			return;
//		}
		$orderMap['status'] = 1;//未参与返现
		$orderList = M("Order")->where($orderMap)->order("id DESC")->select();
		if(empty($orderList)){
			return;
		}
		foreach ($orderList as $k=>$v){
			//TODO 1、先判断该用户是否已经参加过该店的活动（然后改变）
			$where = array(
				"buyer_account"=>$v['buyer_account'],
				"shop_id"=>$v['shop_id'],
				"status"=>2,
			);
			$res = M("Order")->where($where)->find();
			if($res){
				$map =array();
				$map['id'] = $v['id'];
				$orderData['status'] = 3;
				M("Order")->where($map)->save($orderData);
				continue;
			}
			/*****获取店铺相关活动信息******/
			$activity = D("Activity")->findTableByShopId($v['seller_id']);
			$min_fee = $activity['min_fee'];
			if($v['real_fee']<$min_fee || $activity['end_time']<time()|| $activity['status'] == 2  || $v['endtime']<(time()-$activity['limit_day']*86400)){
				//订单金额小于限定金额，活动已结束，该单完成时间已经超过限定天
				$map =array();
				$map['id'] = $v['id'];
				$orderData['status'] = 4;//不符合返现条件
				M("Order")->where($map)->save($orderData);
				continue;
			}
			//TODO 2、若之前没有没有参加该店铺活动，也未发送消息，并且条件达标时 ，则发送消息，
			$shareMsg = D("Share")->findTableForBuyer($v['buyer_account'],$activity['id'],$v['endtime']);//查找此活动的参与者里面是否有此账号
			if($v['msg_status'] == 1 ){
					if (empty($shareMsg)){//该用户不是参与者
						$model = new Model();
						$model->startTrans();
						$map =array();
						$map['id'] = $v['id'];
						$orderData['msg_status'] = 2;
						$orderData['refund_trade_no'] = "re".$v['out_biz_no'];
						$orderData['act_trade_no'] = $v['out_biz_no'];
						if($activity['reStatus'] == 1){
							$refund = $activity['reNum'];
						}else{
							$refund = $this->getRefundFee($v['real_fee'],$activity['reNum']);
						}
						$orderData['refund_fee'] = $refund;
						M("Order")->where($map)->save($orderData);
						/**
						 * 发送消息
						 */
						$res = $this->sendMsg($v,$activity);//TODO 执行发送信息
						if(!empty($res)&&!$res['code']){
							$model->commit();
						}else{
							$model->rollback();
						}
					}else{//该用户是参与者
						if($activity['status'] == 2){//没有下过单的新用户才可以购买
							$orderBuyerMap['buyer_account'] = $v['buyer_account'];
							$orderBuyerMap['id'] = array("neq",$v['id']);
							$buyer_orders = M("Order")->where($orderBuyerMap)->select();
							if(!empty($buyer_orders)){
								$orderData =array();
								$map = array();
								$orderData['status'] = 3;
								$map['id'] = $v['id'];
								M("Order")->where($map)->save($orderData);
								continue;
							}
						}
						$model = new Model();
						$model->startTrans();
						$map =array();
						$map['id'] = $v['id'];
						$orderData =array();
						$orderData['msg_status'] = 3;
						$orderData['status'] = 2;
						$orderData['refund_trade_no'] = "re".$v['out_biz_no'];
						$orderData['act_trade_no'] =$shareMsg[0]['out_trade_no'];
						$orderData['refundtime'] = time();
						if($activity['reStatus'] == 1){
							$refund = $activity['reNum'];
						}else{
							$refund = $this->getRefundFee($v['real_fee'],$activity['reNum']);
						}
						$orderData['refund_fee'] = $refund;
						M("Order")->where($map)->save($orderData);//修改订单表结束
						$shareMap =array();
						$shareMap['buyer_id'] = $shareMsg['buyer_id'];
						$shareMap['act_id'] = $shareMsg['act_id'];
						$shareData['status'] = 2;
						M("Share")->where($shareMap)->save($shareData);
						if($orderData['refund_fee']<=0 || $orderData['refund_fee']<=0.00){
							$model->rollback();
							continue;
						}
						//TODO 执行返现活动
						$res = $this->orderRefund($v,$orderData);
						if(!$res['code']){
							$this->sendMsg($v,$activity,"Success");
							$model->commit();
						}else{
							$model->rollback();
						}
					}
			}elseif($v['msg_status'] == 2){
				//TODO 3.1说明是分享用户，判断是否有拉单是否返现
				$shareMsg = D("Share")->findTableForShare($v['buyer_account'],$activity['id'],$v['out_biz_no']);//查找此活动里是否有该分享者分享出去拉来的参与者
				if(empty($shareMsg)){
					continue;
				}else{
					//判断参与的用户是否已购买
					foreach ($shareMsg as $ks=>$vs){
						$buyer_ids[] = $vs['buyer_id'];
					}
					$orderBuyerMap['buyer_account'] = array("in",$buyer_ids);
					$orderBuyerMap['act_trade_no'] = $v['out_biz_no'];
					$buyer_orders = M("Order")->where($orderBuyerMap)->select();
					if(empty($buyer_orders)){
						continue;
					}
					if($v['refund_fee']<=0 || $v['refund_fee']<=0.00){
						continue;
					}
					//TODO 执行返现活动
					$model = new Model();
					$model->startTrans();
					$map =array();
					$map['id'] = $v['id'];
					$orderData['status'] = 2;
					$orderData['refundtime'] = time();
					M("Order")->where($map)->save($orderData);
					//TODO 执行返现活动
					$res = $this->orderRefund($v);
					if(!$res['code']){
						$this->sendMsg($v,$activity,"Success");
						$model->commit();
					}else{
						$model->rollback();
					}
				}
			}
		}
	}

}

?>
