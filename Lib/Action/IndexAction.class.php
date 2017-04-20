<?php

class IndexAction extends BasicAction {
	private $testArray ;
	private $tradeOutTradeNoArray;
	private $getRefundData;
	public function __construct() {
		parent::__construct();
		if($_SESSION['initOrderAcceptAuto']){
			$this->assign("isGetOrder","NO");
		}else{
			$this->assign("isGetOrder","OK");
		}
		if(empty($_SESSION['shoplist'])){
			$shop = array();
			$res = $this->api_getshoplist('{"page_no":1}');
			if($res['total_pageno']>1){
				for($i=2;$i<=$res['total_pageno'];$i++){
					$array = array("page_no"=>$i);
					$itemres = $this->api_getshoplist(json_encode($array));
					$res['shop_ids'] = array_merge($res['shop_ids'],$itemres['shop_ids']);
				}
			}
			foreach($res['shop_ids'] as $k => $v){
				$shopinfo = $this->api_getshopdetail('{"shop_id":"'.$v.'"}');
				$shop[$k]["audit_desc"] = $shopinfo["audit_desc"];
				$shop[$k]["contact_number"] = $shopinfo["contact_number"];
				$shop[$k]["audit_status"] = $shopinfo["audit_status"];
				$shop[$k]["city_code"] = $shopinfo["city_code"];
				$shop[$k]["brand_logo"] = $shopinfo["brand_logo"];
				$shop[$k]["category_id"] = $shopinfo["category_id"];
				$shop[$k]["main_shop_name"] = $shopinfo["main_shop_name"];
				$shop[$k]["partner_id"] = $shopinfo["partner_id"];
				$shop[$k]["payment_account"] = $shopinfo["payment_account"];
				$shop[$k]["processed_qr_code"] = $shopinfo["processed_qr_code"];
				$shop[$k]["district_code"] = $shopinfo["district_code"];
				$shop[$k]["address"] = $shopinfo["address"];
				$shop[$k]["is_show"] = $shopinfo["is_show"];
				$shop[$k]["branch_shop_name"] = $shopinfo["branch_shop_name"];
				$shop[$k]["shop_id"] = $v;
			}
			$_SESSION['shoplist'] = $shop;
			if($_GET['token']){
				$this->setMongoObj();
				$user = $this->mongo->user;
				$map = $data = array();
				$data['shoplist'] = json_encode($shop);
				$map['token'] = $_GET['token'];
				$newdata = array('$set'=>$data);
				$user->update($map,$newdata);
			}
		}
	}
	//首页
	public function index() {
		if(!$_SESSION['initOrderAcceptAuto']){
			$this->initOrderAcceptAuto();
			$_SESSION['initOrderAcceptAuto'] = true;
		}
		$this->display();
	}
	/**
	 * 初始化自动接单程序
	 */
	public function initOrderAcceptAuto(){
		file_put_contents("./log/".date("Y-m-d")."_initOrderAcceptAuto.txt",json_encode($_SESSION)."\t\r\n",FILE_APPEND);
		if($_SESSION['initOrderAcceptAuto']){
			return;
		}else{
			$_SESSION['initOrderAcceptAuto'] = true;
		}
		$shopList = $_SESSION['shoplist'];
		foreach ($shopList as $ks=>$kv){
			$shopIds[] = $kv['shop_id'];
		}
		if(empty($shopIds)){
			return;
		}
		$this->setMongoObj();
		$this->MongoDb = $this->mongo->Serviceorderhycx;
		$this->MongoDbLog = $this->mongo->Serviceorderhycxlog;
		$where =array("status"=>1);
		$serviceorderDataList = $this->MongoDb->find($where);
		while ($serviceorderDataList->hasNext()){
			$item = $serviceorderDataList->getNext();
			$commodity_order_id = $item['commodity_order_id'];
			$shop_ids = explode(",",$item['shop_ids']);
			$commodity_id = $item['commodity_id'];
			if(in_array($shop_ids[0],$shopIds)){//循环查询结果 判断是否是当前卖家的shop_id
				$newShop_ids =array();
				foreach ($shop_ids as $vsi){
					$resConfirm = $this->api_SerMarConfirmOrder($commodity_order_id,$vsi);//授权给服务自动上架的权利
					//echo "============授权给服务自动上架的权利api_SerMarConfirmOrder=================>"."</br>";
					//$this->debug($resConfirm,0,0);
					$resConfirm['title']="授权给服务自动上架的权利";
					$resConfirm['shop_id']=$shopIds;
					$resConfirm['user_id'] = $_SESSION['seller_id'];
					$resConfirm['createtime']=date("Y-m-d H:i:s");
					$this->MongoDbLog->insert($resConfirm);
					if($resConfirm['msg']!="Success"){
						file_put_contents("./log/".date("Y-m-d")."_error_api_SerMarConfirmOrder.txt","=order_id=>".$commodity_order_id."=errorMsg=>".json_encode($resConfirm)."\t\r\n",FILE_APPEND);
						continue;
					}
					$resComplete = $this->api_SerMarShopOnline($commodity_id,$vsi);//自动上架
					$resComplete['title']="自动上架";
					$resComplete['shop_id']=$shopIds;
					$resComplete['user_id'] = $_SESSION['seller_id'];
					$resComplete['createtime']=date("Y-m-d H:i:s");
					$this->MongoDbLog->insert($resComplete);
					//echo "============上架api_SerMarShopOnline=================>"."</br>";
					//$this->debug($resComplete,0,0);
					if(!($resComplete['msg'] == "Success" || $resComplete['sub_code']=="SHOP_TYPE_ALREADY_ONLINE")){
						file_put_contents("./log/".date("Y-m-d")."_error_api_SerMarShopOnline.txt","=order_id=>".$commodity_id."=errorMsg=>".json_encode($resComplete)."\t\r\n",FILE_APPEND);
						$newShop_ids[] = $vsi;
					}
				}
				$map =array();
				$map['commodity_order_id'] = $commodity_order_id;
				$data = array();
				if($newShop_ids){
					$data['shop_ids'] = implode(",",$newShop_ids);
				}else{
					$data['status'] = 2;
				}
				$newdata = array('$set'=>$data);
				$this->MongoDb->update($map,$newdata);//Mongo更新数据库 若$data 为空说明已经全部上架
			}
		}
	}
	public function test1(){
		/*$j = array();
		$j['bill_type'] = 'trade';
		$j['bill_date'] = '2016-12-11';
		$json = json_encode($j);
		$res = $this->api_getBillDownUrl($json);
		$this->debug($res);*/
	}
}

?>
