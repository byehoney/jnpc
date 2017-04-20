<?php
header("Content-type:text/html;charset=utf8");
class OrderAction extends BaseAction {
	public function __construct()
	{
		parent::__construct();
	}

	private function getUserSessionKeyList(){
		if($_GET['trade_no']){
			$act = M("Activity")->where(array("seller_id"=>'2088711005018696'))->field("sessionkey,seller_id")->select();
		}else{
			$act = M("Activity")->field("sessionkey,seller_id")->select();
		}
		return $act;
	}
	/**
	 * 定时任务
	 */
	public function timedTaskCSV(){
		$sessionKeyList = $this->getUserSessionKeyList();
		if(empty($sessionKeyList)){
			return;
		}
		foreach ($sessionKeyList as $k=>$v){
			$_SESSION['sessionkey'] = $v['sessionkey'];
			if($_GET['trade_no']){
				$this->SaveCsvData(null,$v['seller_id']);
			}else{
				$this->todoGetCsvUrl($v['seller_id']);
			}
		}
	}
//	public function test(){
//		$_SESSION['sessionkey'] = '201611BB8710411966694b55b539e0e1a9a91X69';
//		$json='{"page_no":1}';
//		$res = $this->api_getshoplist($json);
//		$this->debug($res);
//	}
	private function todoGetCsvUrl($seller_id){
		$condition['bill_type'] = "trade";
		$condition['bill_date'] = date("Y-m-d",(time()-86400));
		$json = json_encode($condition);
		$res = $this->api_getBillDownUrl($json);
		if($res['code'] != 0){
			return ;
		}
		$url = $res['bill_download_url'];
		$save_dir = APP_ROOT."/Public/Csv/zip";
		$filename = date("YmdHis").".zip";
		$files = $this->getFile($url,$save_dir,$filename,0);
		$file_path = APP_ROOT."/Public/Csv/csv/".substr($seller_id,0,7)."/".$seller_id;
		$file_path_name = $this->unzip($files['save_path'],$file_path);
		if(!$file_path_name){
			return;
		}
		if(strlen($file_path_name[0])<strlen($file_path_name[1])){
			$file_name = $file_path_name[0];
		}else{
			$file_name = $file_path_name[1];
		}
		$this->SaveCsvData($file_name,$seller_id);
	}
	public function SaveCsvData($file_name,$seller_id){
		$csv_file =$file_name;
		$lines = 3000;//导出行数
		$offset = 0;//起始行数
		if($_GET['trade_no']){
			$csvData[] =array($_GET['trade_no']);
		}else{
			$csvData = $this->read_csv_lines($csv_file,$lines,$offset);
			if(empty($csvData)){
				return;
			}
		}
		$orderModel = M("Order");
		foreach ($csvData as $k=>$v){
			$jsonData['trade_no'] = trim($v[0]);
			$res = $this->api_TradeQuery(json_encode($jsonData));
			if($res['code']){
				continue;
			}
			$orderData['order_no'] = $jsonData['trade_no'];
			$orderData['out_biz_no'] = trim($res['out_trade_no']);
			$orderData['createtime'] = strtotime($res['send_pay_date']);
			$orderData['endtime'] = strtotime($res['send_pay_date']);
			$orderData['order_fee'] = trim($res['total_amount']);
			$orderData['real_fee'] = trim($res['buyer_pay_amount']);
			$orderData['shop_id'] = trim($v[6]);
			$orderData['seller_id'] = $seller_id;
			$orderData['buyer_account'] =$res['buyer_user_id'];
			$listData[] = $orderData;
		}
		if(empty($listData)){
			echo "The data has add All";
			return;
		}
//		dump($listData);
		$res = $orderModel->addAll($listData);
		if($res){
			echo "success";
			return;
		}else{
			echo "error";
			return;
		}
	}
	//每天八点定时退款
	public function checkRefundParamStatus(){
		/*$json = '{"trade_no":"2016112021001004900287309340"}';
		$ress = $this->api_TradeQuery($json);
		$this->debug($ress);*/
		$m = M("User");
		$where = array("seller_id"=>'2088711005018696');
		$datares = $m->where($where)->find();
		$this->debug($datares,0,0);
		$_SESSION['sessionkey'] = $datares['sessionkey'];
		$outnum = date("YmdHis").mt_rand(10000,99999);
		$json = '{"store_id":"2016102500077000000019431622","terminal_id":"0001","operator_id":"2088711005018696","out_trade_no":"mbuyer002_14724522728879202297838631332186384411","trade_no":"2016112021001004900287309340","refund_amount":0.01,"refund_reason":"测试退款功能123456789","out_request_no":"123"}';
		$json = json_decode($json,1);
		$json['out_request_no'] = $outnum;
		$this->debug($json,0,0);
		$json = json_encode($json);
		$res = $this->api_TradeRefund($json);
		$this->debug($res);
		/*$res = $this->api_SendActMsg('2088801815408902',"https://taobao.com","参加活动测试","2016年11月21日雪","这是标题1","这是备注","bced2b719216484b9f090df5c840b558");
		$this->debug($res);*/
	}
}

?>
