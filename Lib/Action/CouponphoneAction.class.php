<?php
header("Access-Control-Allow-Origin:*");
class CouponphoneAction extends BasicAction {
	private  $image_path;
	private  $webimage_path="/Public/logo/";
	public function __construct()
	{
		parent::__construct();
		$this->image_path="/Public/logo/";
	}
	public function ajaxGetShopInfos(){
		$shop = array();
		$imageId = $_SESSION['shoplist'][0]['brand_logo'];
		$image_url = APP_ROOT.$this->image_path.substr($_SESSION["seller_id"],0,7)."/".$_SESSION["seller_id"].'/';
		$this->webimage_path = $this->webimage_path.substr($_SESSION["seller_id"],0,7)."/".$_SESSION["seller_id"].'/';
		$file_name = md5($_SESSION['seller_id']);
		if(!file_exists($image_url.$file_name.".png")){
			if(!is_dir($image_url)){
			 	mkdir($image_url)?"true":"false";
			}
			$str = file_get_contents("https://dl.django.t.taobao.com/rest/1.0/image?fileIds=".$imageId);
			file_put_contents($image_url.$file_name.".png",$str);
			file_put_contents($image_url.$file_name.".txt",$imageId);
		}else{
			$imageId = file_get_contents($image_url.$file_name.".txt");
		}
		$logo=array(
			"url" => $this->webimage_path.$file_name.".png",
			"id" =>$imageId,
		);
		$shop['shoplist'] = array();
		$shopInfo = array();
		foreach ($_SESSION['shoplist'] as $k=>$v){
			$shopInfo['main_shop_name']  = $v['main_shop_name'];
			$shopInfo['branch_shop_name']= $v['branch_shop_name'];
			$shopInfo['shop_id'] = $v['shop_id'];
			$shop['shoplist'][] = $shopInfo;
		}
		$shop['logo'] = $logo;
		$this->ajaxReturn($shop,"获取数据成功",1);
	}
	/**
	 * 测试
	 */
	public function tesLQt(){
		return;
//		$_POST['camp_id'] = "20170331000000000792221000151696";
//		$this->ajaxGetActTongji();
//		$data = array(
//			"camp_id"=>'20170330000000000806377000151692'
//		);
//		$resp = $this->api_ActivityFind(json_encode($data));
//		$this->debug($resp);
	}
	/**
	 * 创建消费送
	 */
	public function ajaxCreateSend(){
		$cate = "send";
		$result = $this->getPostDataByCate($cate);
		if($result && !$result['data_state']){
			$this->ajaxReturn(array(),$result['data_info'],0);
		}
		$this->createAct($result,1);
	}
	/**
	 * 创建满减
	 */
	public function ajaxCreateMinus(){
		$cate = "minus";
		$result = $this->getPostDataByCate($cate);
		if($result && !$result['data_state']){
			$this->ajaxReturn(array(),$result['data_info'],0);
		}
		$this->createAct($result,2);
	}
	/**
	 * 创建新人专享
	 */
	public function ajaxCreateExclusive(){
		$cate = "exclusive";
		$result = $this->getPostDataByCate($cate);
		if($result && !$result['data_state']){
			$this->ajaxReturn(array(),$result['data_info'],0);
		}
		$this->createAct($result,3);
	}
	private function createAct($result,$cate){
		$data = $result['data'];
		$cxActData = $this->getcxActivityData($data);
		if($cxActData){
			$cxActData['act_type'] = $cate;
			$cxActData['seller_id'] =$_SESSION['seller_id'];
			$cxActData['operator_id'] = $_SESSION['operator_id']?$_SESSION['operator_id']:$_SESSION['seller_id'];
		}
		$model = new Model();
		$model->startTrans();
		$resAdd = M("Cxactivity")->add($cxActData);
		file_put_contents("./log/addData.txt", M("Cxactivity")->_sql()."\t\r\n".json_encode($data)."\t\r\n".json_encode($cxActData)."\t\r\n"."\t\r\n",FILE_APPEND);
		if (!$resAdd){
			$model->rollback();
			$this->ajaxReturn(array(),"系统繁忙，请刷新再试",0);
		}
		$resApi = $this->api_ActivityCreate(json_encode($data));
		if ($resApi['code']!=0){
			$model->rollback();
//			$this->debug($data,0,0);
//			$this->debug($resApi);
			$this->ajaxReturn(array(),$resApi['sub_msg'],0);
		}
		M("Cxactivity")->where("id=".$resAdd)->save(array("camp_id"=>$resApi['camp_id']));
		$model->commit();
		$this->ajaxReturn(array(),"创建成功",1);
	}
	/**
	 * 设置post数据 用于测试输出输出
	 */
	private function setPostDataTest(){
		return;
		/*************创建消费送***************/
		/*$_POST = array(
			"act_obj"=>"所有在支付宝口碑消费过的人群",
			"name"=>"活动名称".$this->GetRandStr(4),
			"start_time"=>date("Y-m-d H:i:s"),//活动开始时间
			"end_time"=>date("Y-m-d H:i:s",(time()+3*86400-10)),//活动结束时间
			"voucher_brand_name"=>"参与品牌",//参与品牌
			"logo"=>"acbqGOVPS-eB_9ZPKJklmQAAACMAAQED",//品牌LOGO
			"promo_tools_voucher_type"=>"1",//1 代金券 ; 2 兑换券
			"worth_value"=>"0.2",//单位元
			"min_cost"=>"0.02",//消费送
			"voucher_name"=>"代金券测试用",//券名称
			"voucher_note"=>"券备注",//券备注
			"validate_type"=>"2",//1、FIXED（绝对有效期），2、 RELATIVE（相对有效期）
			"voucher_start_time"=>date("Y-m-d H:i:s"),//券使用开始时间 validate_type=1时必填
			"voucher_end_time"=>date("Y-m-d H:i:s",(time()+3*86400)),//券使用结束时间 validate_type=1时必填
			"voucher_relative_time"=>"5",//券使用领取后第几天失效 validate_type=2时必填
			"voucher_relative_delay"=>"2",//券使用领取后第几天开始有效 validate_type=2时必填
			//"user_win_count"=>"1",//活动期间用户能够参与的次数限制如果不设置则不限制参与次数
			"user_win_frequency_date"=>"D",//D、每天，W、每周，M，每月
			"user_win_frequency_num"=>"10",//次数 与user_win_frequency_num配合使用
			"user_min_consume"=>"0.11",//消费门槛（代金券不能为空）
			"constraint_suit_shops"=>array("2016112600077000000023306679","2016102500077000000019431622"),//活动门店(核销门店)
			"voucher_suit_shops"=>array("2016112600077000000023306679"),//券的适用门店(适用门店)
			//voucher_suit_shops 券适用门店列表仅品牌商发起的招商活动可为空直发奖类型活动必须与活动适用门店一致最多支持10w家门店
			"use_time_values"=>array("1","3","5","2","4"),//如"1,3,5"，对应周一，周三，周五可用
			"use_forbidden_day"=>array("2017-03-25,2017-03-26","2017-04-01,2017-04-02"),
			"use_rule_desc"=>"券说明",//券说明
		);*/
		//$result = $this->getPostDataByCate("send");

		/*$_POST = array(
			"act_obj"=>"所有在支付宝口碑消费过的人群",
			"name"=>"活动名称",
			"start_time"=>date("Y-m-d H:i:s"),//活动开始时间
			"end_time"=>date("Y-m-d H:i:s",(time()+3*86400-10)),//活动结束时间
			"voucher_brand_name"=>"参与品牌",//参与品牌
			"logo"=>"acbqGOVPS-eB_9ZPKJklmQAAACMAAQED",//品牌LOGO
			"promo_tools_voucher_type"=>"1",//1 代金券 ; 2 兑换券
			"worth_value"=>"1",//单位元
			"voucher_name"=>"1元代金券",//券名称
			"voucher_note"=>"券备注",//券备注
			"min_cost"=>"0.6",//发券的门槛
			"validate_type"=>"1",//1、FIXED（绝对有效期），2、 RELATIVE（相对有效期）
			"voucher_start_time"=>date("Y-m-d H:i:s"),//券使用开始时间 validate_type=1时必填
			"voucher_end_time"=>date("Y-m-d H:i:s",(time()+3*86400)),//券使用结束时间 validate_type=1时必填
			"voucher_relative_time"=>"3",//券使用领取后第几天失效 validate_type=2时必填
			"voucher_relative_delay"=>"1",//券使用领取后第几天开始有效 validate_type=2时必填
			"user_win_count"=>"1",//活动期间用户能够参与的次数限制如果不设置则不限制参与次数
			"user_win_frequency_date"=>"D",//D、每天，W、每周，M，每月
			"user_win_frequency_num"=>"2",//次数 与user_win_frequency_num配合使用
			"user_min_consume"=>"2",//消费门槛（代金券不能为空）
			"constraint_suit_shops"=>array("2016112600077000000023306679","2016102500077000000019431622"),//活动门店(核销门店)
			"voucher_suit_shops"=>array("2016112600077000000023306679","2016102500077000000019431622"),//券的适用门店(适用门店)
			//voucher_suit_shops 券适用门店列表仅品牌商发起的招商活动可为空直发奖类型活动必须与活动适用门店一致最多支持10w家门店
			"use_time_values"=>array("1","2","3","4","5"),//如"1,3,5"，对应周一，周三，周五可用
			"use_forbidden_day"=>array("2017-03-25,2017-03-26","2017-04-01,2017-04-02"),
			"use_rule_desc"=>"券说明",//券说明
		);
	//	$result = $this->getPostDataByCate("minus");*/

		$_POST = array(
			"name"=>"活动名称",
			"start_time"=>date("Y-m-d H:i:s"),//活动开始时间
			"end_time"=>date("Y-m-d H:i:s",(time()+3*86400-10)),//活动结束时间
			"voucher_brand_name"=>"参与品牌",//参与品牌
			"auto_delay_flag"=>"Y", //Y代表自动续期 N代表不自动续期
			"quantity"=>"123456",//数量
			"logo"=>"acbqGOVPS-eB_9ZPKJklmQAAACMAAQED",//品牌LOGO
			"promo_tools_voucher_type"=>"1",//1 代金券 ; 2 兑换券
			"worth_value"=>"1",//单位元
			"voucher_name"=>"1元代金券",//券名称
			"voucher_note"=>"券备注",//券备注
			"validate_type"=>"2",//1、FIXED（绝对有效期），2、 RELATIVE（相对有效期）
			"voucher_relative_time"=>"3",//券使用领取后第几天失效 validate_type=2时必填
			"voucher_relative_delay"=>"1",//券使用领取后第几天开始有效 validate_type=2时必填
			"user_win_count"=>"1",//活动期间用户能够参与的次数限制如果不设置则不限制参与次数
			"user_win_frequency_date"=>"D",//D、每天，W、每周，M，每月
			"user_win_frequency_num"=>"2",//次数 与user_win_frequency_num配合使用
			"user_min_consume"=>"2",//消费门槛（代金券不能为空）
			"constraint_suit_shops"=>array("2016112600077000000023306679"),//活动门店(核销门店)
			"voucher_suit_shops"=>array("2016112600077000000023306679","2016102500077000000019431622"),//券的适用门店(适用门店)
			//voucher_suit_shops 券适用门店列表仅品牌商发起的招商活动可为空直发奖类型活动必须与活动适用门店一致最多支持10w家门店
			"use_time_values"=>array("1","3","5"),//如"1,3,5"，对应周一，周三，周五可用
			"use_forbidden_day"=>array("2017-03-25,2017-03-26","2017-04-01,2017-04-02"),
			"use_rule_desc"=>"券说明",//券说明
		);

//		$result = $this->getPostDataByCate("exclusive");
//		dump($result);
//		echo json_encode($result['data']);


	}
	/**
	 * 获取即将存入数据库的数据
	 */
	private function getcxActivityData($data){
		if($data['promo_tools']){
			$data['promo_tools'] = $data['promo_tools'][0];
		}
		if($data['promo_tools']['voucher']['use_rule']['use_time']){
			$data['promo_tools']['voucher']['use_rule']['use_time'] = $data['promo_tools']['voucher']['use_rule']['use_time'][0];
		}
		$actData = array();
		if ($data['out_biz_no']){
			$actData['out_biz_no'] = $data['out_biz_no'];
			$actData['camp_id'] = $data['camp_id']?$data['camp_id']:$data['out_biz_no'];
		}
		if($data['name']){//活动名称
			$actData['act_name'] = $data['name'];
		}
		if($_POST['act_obj']){//活动对象
			$actData['act_obj'] = $_POST['act_obj'];
		}
		if($data['start_time']){//开始时间
			$actData['start_time'] = strtotime($data['start_time']);
		}
		if($data['end_time']){//结束时间
			$actData['end_time'] = strtotime($data['end_time']);
		}
		if($data['promo_tools']["voucher"]["brand_name"]){//参与品牌
			$actData['part_brands'] = $data['promo_tools']["voucher"]["brand_name"];
		}
		if($data['promo_tools']["voucher"]["logo"]){//品牌LOGO
			$actData['brands_logo'] = $data['promo_tools']["voucher"]["logo"];
		}
		if($_POST['promo_tools_voucher_type']){//券类型
			$actData['coupon_type'] = $_POST['promo_tools_voucher_type'];
		}
		if($data['promo_tools']["voucher"]["worth_value"]){//券面额
			$actData['coupon_deno'] = $data['promo_tools']["voucher"]["worth_value"];
		}
		if($data['promo_tools']["voucher"]['name']){//券名称
			$actData['coupon_name'] = $data['promo_tools']["voucher"]['name'];
		}
		if($data['promo_tools']["voucher"]['voucher_note']){//券备注
			$actData['coupon_remarks'] = $data['promo_tools']["voucher"]['voucher_note'];
		}
		if($data['promo_tools']["voucher"]['use_rule']["min_consume"]){//券门栏
			$actData['con_threshold'] = $data['promo_tools']["voucher"]['use_rule']["min_consume"];
		}
		if($_POST['validate_type']){//时间类型
			$actData['time_type'] = $_POST['validate_type'];
		}
		if($_POST['voucher_relative_time']){//相对时间结束时间
			$actData['coupon_reletime'] = $_POST['voucher_relative_time'];
		}
		if($_POST['voucher_relative_delay']){//相对时间开始时间
			$actData['coupon_relstime'] = $_POST['voucher_relative_delay']?$_POST['voucher_relative_delay']:0;
		}
		if($data['promo_tools']['voucher']['start_time']){//固定开始时间
			$actData['coupon_stime'] = strtotime($data['promo_tools']['voucher']['start_time']);
		}
		if($data['promo_tools']['voucher']['end_time']){//固定结束时间
			$actData['coupon_etime'] = strtotime($data['promo_tools']['voucher']['end_time']);
		}
		if($data['constraint_info']['user_win_frequency']){//领券频次
			$actData['rec_restrictions'] = $data['constraint_info']['user_win_frequency'];
		}
		if($data['constraint_info']['user_win_count']){//最大用券数量
			$actData['max_restrictions'] = $data['constraint_info']['user_win_count'];
		}
		if($data['promo_tools']['voucher']['use_rule']['suit_shops']){//消费门店
			$actData['applicable_store'] = json_encode($data['promo_tools']['voucher']['use_rule']['suit_shops']);
		}
		if($data['constraint_info']["suit_shops"]){//核销门店
			$actData['writeoff_store'] = json_encode($data['constraint_info']["suit_shops"]);
		}
		if($data['promo_tools']['voucher']['use_rule']['use_time']['values']){//适用时间Day "1,2,3"
			$actData['coupon_availtime'] =$data['promo_tools']['voucher']['use_rule']['use_time']['values'];
		}
		if($data['promo_tools']['voucher']['use_rule']['use_time']['times']){//适用时间time 默认全天
			$actData['sele_availtime'] = $data['promo_tools']['voucher']['use_rule']['use_time']['times'];
		}
		if($data['promo_tools']['voucher']['use_rule']['forbidden_time']['days']){//禁用日期
			$actData['sele_unavailtime'] = $data['promo_tools']['voucher']['use_rule']['forbidden_time']['days'];
		}
		if($data["promo_tools"]["voucher"]['desc']){//券说明
			$actData['coupon_des'] = $data["promo_tools"]["voucher"]['desc'];
		}
		if($data['auto_delay_flag']){//是否自动续期"Y"是 "N"否
			$actData['auto_delay_flag'] =$data['auto_delay_flag'];
		}else{
			$actData['auto_delay_flag'] ="N";
		}
		if($data['budget_info']['budget_total']){//券说明
			$actData['budget_total'] = $data['budget_info']['budget_total'];
		}
		if($actData['camp_id']){
			$actData['create_time'] =time();
		}
		return $actData;
	}
	/**
	 * 获取并整理上传数据
	 */
	private function getPostDataByCate($cate){
		$res = array(
			"data"=>array(),
			"data_state" => true,
			"data_info"=>"数据加载成功"
		);
		$data =array();
		if($_POST['name']){//TODO 用来计数sum
			$data['name'] = $_POST['name'];
		}else{
			$res['data_info']="活动名称不能为空";
			$res['data_state']=false;
			return $res;
		}

		if($_POST['start_time']){//TODO 用来计数sum
			$data['start_time'] = $_POST['start_time']." 00:00:00";
		}else{
			$res['data_info']="活动开始时间不能为空";
			$res['data_state']=false;
			return $res;
		}

		if($_POST['end_time']){//TODO 用来计数sum
			$data['end_time'] = $_POST['end_time']." 00:00:00";
		}else{
			$res['data_info']="活动结束时间不能为空";
			$res['data_state']=false;
			return $res;
		}
		/***时间验证**/
		if(strtotime($data['end_time'])<strtotime($data['start_time'])){
			$res['data_info']="活动结束时间不能小于活动开始时间";
			$res['data_state']=false;
			return $res;
		}
		/**活动类型，目前支持以下类型： CONSUME_SEND：消费送活动 DIRECT_SEND：直发奖活动 REAL_TIME_SEND：实时立减类活动 GUESS_SEND：口令送 RECHARGE_SEND：充值送 POINT_SEND：集点卡活动**/
		if($cate=="send"){//消费送//TODO 用来计数sum
			$data["type"]="CONSUME_SEND";
			$data['constraint_info'] =array();
		}
		if($cate=="minus"){//消费减
			$data["type"]="CONSUME_SEND";
			$data['constraint_info'] =array();
		}
		if($cate=="exclusive"){//新人专享
			$data["type"]="DIRECT_SEND";
			if($_POST['auto_delay_flag']){
				$data['auto_delay_flag']=$_POST['auto_delay_flag'];
			}else{
				$data['auto_delay_flag']="N";
			}
			if($_POST['quantity']){
				$data['budget_info']['budget_type'] = "QUANTITY";
				$data['budget_info']['budget_total']=$_POST['quantity'];
			}else{
				$res['data_info']="请填写发券数量";
				$res['data_state']=false;
				return $res;
			}
			$data['constraint_info'] =array(
				"crowd_restriction"=>"NEW_MEMBER_PROMO"
			);
		}
		/**crowd_restriction中选填的条件**/
		if($_POST['user_win_count']){
			$data['constraint_info']['user_win_count'] = $_POST['user_win_count'];
		}
		//活动期间用户能够参与的频率限制 如果不设置则不限制参与频率 每日中奖1次: D||1 每周中奖2次: W||2 每月中奖3次: M||3
		if($_POST['user_win_frequency_num'] && $_POST['user_win_frequency_date']){
			$data['constraint_info']['user_win_frequency'] = $_POST['user_win_frequency_date']."||".$_POST['user_win_frequency_num'];
		}

		if($_POST['constraint_suit_shops']){
			if(is_array($_POST['constraint_suit_shops'])){
				$data['constraint_info']['suit_shops'] = $_POST['constraint_suit_shops'];
			}else{
				$data['constraint_info']['suit_shops'] = array($_POST['constraint_suit_shops']);
			}
		}else{
			$res['data_info']="请选择门店信息,门店信息不能为空";
			$res['data_state']=false;
			return $res;
		}
		/***constraint_info信息结束，之间没有填写item_ids，crowd_group_id***之下是券的信息**/
		//promo_tools_voucher_type=1 代金券 ; 2 兑换券//TODO 用来计数sum
		//promo_tools_voucher_verify_mode=1 用户自己点击券上的按钮核销 2商户通过APP扫码核销//TODO 用来计数sum
		//voucher_note POST数据
		if($cate=="send"){
			$data['promo_tools'] = array();
			if($_POST['promo_tools_voucher_type'] == 1){
				$data['promo_tools']['voucher'] =array(
					"type"=>'MONEY',
				);
			}elseif ($_POST['promo_tools_voucher_type'] == 2){
				$data['promo_tools']['voucher'] =array(
					"type"=>'EXCHANGE',
				);
				$data['promo_tools']['voucher']['verify_mode'] ="MERCHANT_SCAN";
			}else{
				$res['data_info']="请选择正确的券类型";
				$res['data_state']=false;
				return $res;
			}
		}
		if($cate=="minus"){
			$data['promo_tools'] = array();
			if($_POST['promo_tools_voucher_type'] == 1){
				$data['promo_tools']['voucher'] =array(
					"type"=>'MONEY',
				);
			}elseif ($_POST['promo_tools_voucher_type'] == 2){
				$data['promo_tools']['voucher'] =array(
					"type"=>'EXCHANGE',
				);
				$data['promo_tools']['voucher']['verify_mode'] ="MERCHANT_SCAN";
			}else{
				$res['data_info']="请选择正确的券类型";
				$res['data_state']=false;
				return $res;
			}
		}
		if($cate=="exclusive"){
			$data['promo_tools'] = array();
			$data['promo_tools']['voucher'] =array(
				"type"=>'MONEY',
			);
		}
		if($_POST['voucher_note']){//券的备注
			$data['promo_tools']['voucher']['voucher_note'] =$_POST['voucher_note'];
		}
		/***券类型结束*****券的描述开始***/
		//voucher_name	券名
		//voucher_brand_name	券副标题 （参与品牌）
		//use_instructions 券备注（券说明）
		//logo
		//worth_value 券面额
		//validate_type 1、FIXED（绝对有效期），2、 RELATIVE（相对有效期）
		//voucher_start_time 券使用开始时间
		//voucher_end_time 券使用结束时间
		//voucher_relative_delay 券使用领取后第几天开始有效
		//voucher_relative_time 券使用领取后第几天失效
		//min_consume 消费门槛
		//use_rule_desc 券使用说明
		if ($_POST['voucher_name']){
			$data['promo_tools']['voucher']['name'] =$_POST['voucher_name'];
		}else{
			$res['data_info']="券名称不能为空";
			$res['data_state']=false;
			return $res;
		}
		if ($_POST['voucher_brand_name']){
			$data['promo_tools']['voucher']['brand_name'] =$_POST['voucher_brand_name'];
		}else{
			$res['data_info']="参与品牌不能为空";
			$res['data_state']=false;
			return $res;
		}
		if ($_POST['voucher_note']){
			$data['promo_tools']['voucher']['voucher_note'] =$_POST['voucher_note'];
		}
		if($_POST['logo']){
			$data['promo_tools']['voucher']['logo'] = $_POST['logo'];
		}else{
			$res['data_info']="请先上传LOGO";
			$res['data_state']=false;
			return $res;
		}
		if($_POST['promo_tools_voucher_type'] == 1){//代金券
			if($_POST['worth_value']){
				$data['promo_tools']['voucher']['worth_value'] = $_POST['worth_value'];
			}else{
				$res['data_info']="代金券:券面额不能为空";
				$res['data_state']=false;
				return $res;
			}
		}
		$data['promo_tools']['voucher']['effect_type'] = "IMMEDIATELY";//默认不可以转赠
		if($_POST['validate_type']==1){
			$data['promo_tools']['voucher']['validate_type']= "FIXED";
			if($_POST['voucher_start_time'] && $_POST['voucher_end_time'] && strtotime($_POST['voucher_end_time'])>strtotime($_POST['voucher_start_time'])){
				$data['promo_tools']['voucher']['start_time']=$_POST['voucher_start_time']." 00:00:00";
				$data['promo_tools']['voucher']['end_time']= $_POST['voucher_end_time']." 00:00:00";
			}else{
				$res['data_info']="请输入有效的券使用时间";
				$res['data_state']=false;
				return $res;
			}
		}elseif($_POST['validate_type']==2){
			if(!$_POST['voucher_relative_delay']){
				$_POST['voucher_relative_delay']=0;
			}
			$data['promo_tools']['voucher']['validate_type']= "RELATIVE";
			$eff_time = $_POST['voucher_relative_time']-$_POST['voucher_relative_delay']+1;
			if($_POST['voucher_relative_time']){
				$data['promo_tools']['voucher']['relative_time']= $eff_time;
			}else{
				$res['data_info']="请输入有效的券使用时间";
				$res['data_state']=false;
				return $res;
			}
			if($_POST['voucher_relative_delay'] == 1){
				$data['promo_tools']['voucher']['effect_type'] = "IMMEDIATELY";//默认不可以转赠
			}elseif($_POST['voucher_relative_delay'] > 1){
				$data['promo_tools']['voucher']['effect_type'] ="DELAY";
				$data['promo_tools']['voucher']['delay_info']['type']="BYDAY";
				$data['promo_tools']['voucher']['delay_info']['value']=24*60*intval($_POST['voucher_relative_delay']);
			}else{
				$res['data_info']="请填写正确的使用时间";
				$res['data_state']=false;
				return $res;
			}
		}else{
			$res['data_info']="请选择券有效期";
			$res['data_state']=false;
			return $res;
		}
		if($_POST['voucher_suit_shops']){
			if(is_array($_POST['voucher_suit_shops'])){
				$data['promo_tools']['voucher']['use_rule']['suit_shops'] = $_POST['voucher_suit_shops'];
			}else{
				$data['promo_tools']['voucher']['use_rule']['suit_shops'] = array($_POST['voucher_suit_shops']);
			}
		}else{
			$res['data_info']="请选择券的适用门店";
			$res['data_state']=false;
			return $res;
		}
		if($_POST['user_min_consume']){
			$data['promo_tools']['voucher']['use_rule']['min_consume'] = $_POST['user_min_consume'];
		}
		if($_POST['use_rule_desc']){
			$data['promo_tools']['voucher']['desc'] = $_POST['use_rule_desc'];
		}
		$data['promo_tools']['voucher']['donate_flag'] = "false";//默认不可以转赠
		// 券的使用时间，禁止时间
		//券的使用时间 use_time_values 券可用时间维度值周维度的取值范围1-7(周一至周日)，多个可用时段用逗号分隔如"1,3,5"，对应周一，周三，周五可用
		//（暂时无用）use_time_times券可用时间段可用时间段起止时间用逗号分隔，多个时间段之间用^分隔如, "16:00:00,20:00:00^21:00:00,22:00:00"表示16点至20点，21点至22点可用时间段不可重叠
		if($_POST['use_time_values']){
			$data['promo_tools']['voucher']['use_rule']['use_time']['dimension'] = "W";
			if(is_array($_POST['use_time_values'])){
				$data['promo_tools']['voucher']['use_rule']['use_time']['values'] = implode(",",$_POST['use_time_values']);
			}else{
				$data['promo_tools']['voucher']['use_rule']['use_time']['values'] = $_POST['use_time_values'];
			}
			$data['promo_tools']['voucher']['use_rule']['use_time']['times'] = "00:00:00,23:59:59";
		}
		if($_POST['use_time_values_time']){
			if(is_array($_POST['use_time_values_time'])){
				$data['promo_tools']['voucher']['use_rule']['use_time']['times'] = implode("^",$_POST['use_time_values_time']);
			}else{
				$data['promo_tools']['voucher']['use_rule']['use_time']['times'] = $_POST['use_time_values_time'];
			}

		}
		//use_forbidden_day 不可用日期区间，仅支持到天不可用区间起止日期用逗号分隔，多个区间之间用分隔如"2016-05-01,2016-05-03^2016-10-01,2016-10-07"表示2016年5月1日至5月3日，10月1日至10月7日券不可用
		if($_POST['use_forbidden_day']){
			if(is_array($_POST['use_forbidden_day'])){
				$data['promo_tools']['voucher']['use_rule']['forbidden_time']['days'] = implode("^",$_POST['use_forbidden_day']);
			}else{
				$data['promo_tools']['voucher']['use_rule']['forbidden_time']['days'] = $_POST['use_forbidden_day'];
			}
		}
		//发券最低消费金额，单位元活动类型为消费送且不是消费送礼包时设置
		if($cate=="minus"){
			$data['promo_tools']['send_rule']['min_cost'] = $_POST['min_cost'];
			$data['promo_tools']['send_rule']['send_num'] = 1;
		}
		if($cate=="send"){
			$data['promo_tools']['send_rule']['min_cost'] = 0.01;

		}
		if($cate=="exclusive"){
			$data['promo_tools']['send_rule']['allow_repeat_send'] = "false";
			$data['promo_tools']['send_rule']['send_num'] = 1;
			$data['publish_channels'] =array(
				array("type"=>"SHOP_DETAIL","name"=>"店铺页投放"),
				array("type"=>"QR_CODE","name"=>"二维码投放")
			) ;
		}
		if($res['data_state']){
			if(!empty($data['promo_tools']['voucher']['use_rule']['use_time'])){
				$data['promo_tools']['voucher']['use_rule']['use_time']=array($data['promo_tools']['voucher']['use_rule']['use_time']);
			}
			$data['promo_tools'] = array($data['promo_tools']);
			$data['out_biz_no'] = $this->getOutBizNo();
			$res['data'] = $data;
			return $res;
		}
	}
	/**
	 * 活动下架
	 */
	public function ajaxSetActShop(){
		if(empty($_POST['camp_id'])){
			$this->ajaxReturn(array(),"请选择要停止的活动",0);
		}
		$where = array(
			"camp_id" =>$_POST['camp_id'],
			"seller_id"=>$_SESSION['seller_id'],
		);
		$act = M("Cxactivity")->where($where)->field("id,camp_id,seller_id,operator_id,act_name")->find();
		if (!$act){
			$this->ajaxReturn(array(),"系统繁忙，请刷新重试",0);
		}
		if($_SESSION['seller_id']!=$act['seller_id']){
			$this->ajaxReturn(array(),"您无权下架该活动",0);
		}
		$model = new Model();
		$model->startTrans();
		M("Cxactivity")->where($where)->save(array("act_state"=>0,"stop_time"=>time()));
		$arrayData = array(
			"out_biz_no"=>$this->getOutBizNo(),
			"camp_id"=>$_POST['camp_id'],
		);
		$resShop = $this->api_ActivityOffline(json_encode($arrayData));
		if ($resShop['code']!=0){
			$model->rollback();
			$this->ajaxReturn(array(),$resShop['sub_msg'],0);
		}
		$model->commit();
		$this->ajaxReturn(array(),"下架成功",1);
	}
	/**
	 * 获取活动列表
	 */
	public function ajaxGetActList(){
		$page_num = $_POST['page_num']?$_POST['page_num']:1;
		$page_size = $_POST['page_size']?$_POST['page_size']:20;
		$where=array(
			"seller_id"=>$_SESSION["seller_id"],
		);
		if ($_POST['actName']){
			$where['act_name'] = array("like","%".$_POST['actName']."%");
		}
		if($_POST['act_state']){
			if($_POST['act_state']==2){
				$where['act_state'] = 1;
				$where['start_time'] = array("lt",time());
				$where['end_time'] = array("gt",time());
			}elseif ($_POST['act_state']==3){
				$where['act_state'] = 1;
				$where['start_time'] = array("gt",time());
			}elseif ($_POST['act_state']==1){
				$where['_string'] = " act_state=0 or end_time<".time();
			}
		}
		$count = M("Cxactivity")->where($where)->count();
		$page = $count;
		$list = M("Cxactivity")->where($where)->order("act_state DESC,id DESC")->page($page_num,$page_size)
				->field("camp_id,act_name name,act_state,act_type,start_time,end_time,stop_time,coupon_etime voucher_end_time,time_type validate_type")
				->select();
		if($list===false){
			$this->ajaxReturn(array(),"系统繁忙，请刷新重试",0);
		}
		if(empty($list)){
			$res['page'] = 0;
			$res['actlist'] = array();
			$this->ajaxReturn($res,"加载完成",1);
		}
		foreach ($list as $k=>$v){
			//数据处理
			if($v['act_state']==1){//0、已结束，1、进行中，2、未开始
				$list[$k]['stop_time']="--";
				if($v['end_time']<time()){//时间已过
					$list[$k]['act_state']=0;
					$list[$k]['stop_time']= date("Y-m-d",($v['end_time']+86400));
				}
				if($v['start_time']>time()){
					$list[$k]['act_state']=2;
				}
			}else{
				$list[$k]['stop_time']= date("Y-m-d",$v['stop_time']);
			}
//			$list[$k]['end_time'] = date("Y-m-d",$v['end_time']);
			$list[$k]['start_time'] = date("Y-m-d",$v['start_time'])."~".date("Y-m-d",$v['end_time']);
			$list[$k]['act_info_but'] = "1";//查看方案：1、显示，0、隐藏
			$list[$k]['act_report']="1";//查看报告：1、显示，0、隐藏

			$begin_date = $v['start_time'];
			$time = strtotime(date("Ymd"));
			if($v['validate_type'] == 2){//相对时间
				if($v['act_state'] == 0){
					$coupon_endTime = $v['stop_time']+86400*$v['coupon_reletime'];
					$end_date = $time<$coupon_endTime?$time:$coupon_endTime;
				}else{
					$coupon_endTime = $v['end_time']+86400*$v['coupon_reletime'];
					$end_date = $time<$coupon_endTime?$time:$coupon_endTime;
				}
			}else{
					$coupon_endTime = $v['voucher_end_time'];
					$end_date = $time<$coupon_endTime?$time:$coupon_endTime;
			}
			/**查找相关活动报告*/
			$data = array(
				"begin_date"=>date("Ymd",$begin_date-864000),
				"end_date"=>date("Ymd",$end_date),
				"biz_type"=> "CampaignQuery",
				"ext_info"=>array("camp_id"=>$v['camp_id'])
			);
			$result = $this->api_indicatorQuery(json_encode($data));
			if($result["code"]!=0){
				$this->ajaxReturn('',$result['sub_msg'],0);
			}
			$countArray = json_decode($result['indicator_infos'],1);
			/***整理活动相关数据**/
			if(empty($countArray)){
				$str = '[{
						"biz_date": "'.date("Ymd").'",
					   "today_campaign_new_user_cnt": "0",
					   "today_campaign_trade_amt": "0",
					   "today_taken_cnt": "0",
					   "today_taken_user_cnt": "0",
					   "today_used_cnt": "0",
					   "today_used_user_cnt": "0",
					   "total_campaign_new_user_cnt": "0",
					   "total_campaign_order_amt": "0",
					   "total_campaign_trade_amt": "0",
					   "total_campaign_trade_cnt": "0",
					   "total_taken_cnt": "0",
					   "total_taken_user_cnt": "0",
					   "total_used_cnt": "0",
					   "total_used_user_cnt": "0"
				   }]';
				$countArray = json_decode($str,1);
			}
			/**总领券总数*/
			$total_taken_cnt = 0;
			/**总核券数*/
			$total_used_cnt = 0;
			foreach ($countArray as $ka=>$va){
				/**总领券总数*/
				$total_taken_cnt = $total_taken_cnt+$va['today_taken_cnt'];
				/**总核券数*/
				$total_used_cnt = $total_used_cnt+$va['today_used_cnt'];
				unset($countArray[$ka]['total_taken_cnt']);
				unset($countArray[$ka]['total_used_cnt']);
			}
			$list[$k]['total_taken_cnt']=$total_taken_cnt;
			$list[$k]['total_used_cnt']=$total_used_cnt;
		}
		$res['page'] = $page;
		$res['actlist'] = $list;
		$this->ajaxReturn($res,"加载完成",1);
	}
	
	/**
	 * 各种统计
	 */
	public function ajaxGetVariousTongji(){
		$data = array(//最近7天的数据
			"begin_date"=>date("Ymd",(time()-86400*7)),
			"end_date"=>date("Ymd"),
			"biz_type"=> "CampaignQuery"
		);
		$resConsumption7days = $this->api_indicatorQuery(json_encode($data));
		if($resConsumption7days['code']==0){
			$countArray = json_decode($resConsumption7days['indicator_infos'],1);
		}else{
			$this->ajaxReturn(array(),$resConsumption7days['sub_msg'],0);
		}
		$totalTakenCnt=0;//领券
		$totalAmt=0;//收益总数
		$totalUsedCnt=0;//核销券
		foreach($countArray as $key=>$val){
			$totalTakenCnt=$totalTakenCnt+$val['today_taken_cnt'];
			$totalAmt=$totalAmt+$val['today_campaign_trade_amt']/100.00;;
			$totalUsedCnt=$totalUsedCnt+$val['today_used_cnt'];
		}
		$res['total7days']=array(
			'totalTakenCnt'=>$totalTakenCnt,
			'totalAmt'=>$totalAmt,
			'totalUsedCnt'=>$totalUsedCnt
		);
		//todo 查找会员数
		$merNumTotal = 0;//会员总数
		$merNumNew = 0;//新增会员数
		$tradeTwiceUser = 0;
		$data = array(//今日数据
			"begin_date"=>date("Ymd"),
			"end_date"=>date("Ymd"),
			"biz_type"=> "MemberQuery"
		);
		$resCount = $this->api_indicatorQuery(json_encode($data));
		if($resCount['code']==0){
			$countArray = json_decode($resCount['indicator_infos'],1);
			$merNumNew = $countArray[0]['today_campaign_new_user_cnt'];
		}
		$merNumTotalCon = array(
			"conditions"=>array(
				array(
					"op"=>"IN",
					"tagCode"=>"pam_gender",
					"value"=>array(1,2,0)
				)
			)
		);
		$merNumTotalConResult = $this->api_MemberQuery(json_encode($merNumTotalCon));
		if($merNumTotalConResult["code"]==0){
			$countArray = json_decode($merNumTotalConResult['summary_values'],1);
			$merNumTotal = $countArray['total'];
		}else{
			$this->ajaxReturn(array(),$merNumTotalConResult['sub_msg'],0);
		}
		$tradeTwiceUserCon = array(
			"conditions"=>array(
				array(
					"op"=>"GTEQ",
					"tagCode"=>"pam_trade_cnt",
					"value"=>2
				)
			)
		);
		$tradeTwiceUserResult = $this->api_MemberQuery(json_encode($tradeTwiceUserCon));
		if($tradeTwiceUserResult["code"]==0){
			$countArray = json_decode($tradeTwiceUserResult['summary_values'],1);
			$tradeTwiceUser = $countArray['total'];
		}
		$res['memberCount'] = array(//会员数
			"merNumNew" =>$merNumNew,
			"merNumTotal"=>$merNumTotal,
			"tradeTwiceUser"=>$tradeTwiceUser
		);

		$data = array(//今日金额数据
			"begin_date"=>date("Ymd"),
			"end_date"=>date("Ymd"),
			"biz_type"=> "TradeQuery"
		);
		$resConsumptionTotal = $this->api_indicatorQuery(json_encode($data));
		if($resConsumptionTotal["code"]==0){
			$countArray = json_decode($resConsumptionTotal['indicator_infos'],1);
			$couponProfit = $countArray[0]['today_trade_amt']/100.00;
			$consumptionTotal = $countArray[0]['total_trade_amt']/100.00;
			$consumptionavg = ceil($countArray[0]['total_trade_amt']/$countArray[0]['total_trade_cnt'])/100.00;
		}else{
			$this->ajaxReturn(array(),$resConsumptionTotal['sub_msg'],0);
		}
		$res['ConsumptionTotal'] = array(//消费金额
			"couponProfit" =>$couponProfit,
			"consumptionTotal"=>$consumptionTotal,
			"consumptionavg"=>$consumptionavg
		);
		$this->ajaxReturn($res,'加载成功',1);

	}
	/**
	 * 获取二维码地址
	 */
	public function ajaxGetQrUrl(){
		if(empty($_POST['camp_id'])){
			$this->ajaxReturn($_POST,"请选择要查询的二维码地址",0);
		}
		$data = array(
			"camp_id"=>$_POST['camp_id']
		);
		$resp = $this->api_ActivityFind(json_encode($data));
		if ($resp['code']!=0){
			$this->ajaxReturn("",$resp['sub_msg'],0);
		}
		$QR_CODE = $resp['camp_detail']['publish_channels'][1]['ext_info'];
		$QR_CODE = json_decode($QR_CODE,1);
		$this->ajaxReturn(array("qr_url"=>$QR_CODE['QR_CODE']),"获取成功",1);
	}
	/**
	 * 获取活动详细信息
	 */
	public function ajaxGetActInfo(){
		if(empty($_POST['camp_id'])){
			$this->ajaxReturn(array(),"请选择正确的活动",0);
		}
		$cxActInfo = M("Cxactivity")->where(array("camp_id"=>$_POST['camp_id']))->find();
		if(empty($cxActInfo)){$this->ajaxReturn(array(),"没有查到相关数据",0);}
		if($cxActInfo['seller_id']!=$_SESSION['seller_id']){
			$this->ajaxReturn(array(),"您无权查看该条活动",0);
		}
		$info=$this->setClientData($cxActInfo);
		$image_url = $this->image_path.substr($_SESSION["seller_id"],0,7)."/".$_SESSION["seller_id"].'/';
		$absimage_url = APP_ROOT.$image_url;
		$imageId = $info['logo'];
		$file_name = $_POST['camp_id'];
		//$this->debug($image_url.$file_name.".png");
		if(!file_exists($absimage_url.$file_name.".png")){
			if(!is_dir($absimage_url)){
				mkdir($absimage_url)?"true":"false";
			}
			$str = file_get_contents("https://dl.django.t.taobao.com/rest/1.0/image?fileIds=".$imageId);
			file_put_contents($absimage_url.$file_name.".png",$str);
		}
		$info['logo'] =$image_url.$file_name.".png";
		$this->ajaxReturn($info,"加载成功",1);
	}
	/**
	 * 数据整理（将数据库数据，与前端入参数据对照整理方便前端处理）
	 */
	private function setClientData($cxActInfo){
		$info = array();
		if($cxActInfo['act_name']){
			$info['name'] = $cxActInfo['act_name'];
		}
		if($cxActInfo['start_time']){
			$info['start_time'] = date("Y-m-d",$cxActInfo['start_time']);
		}
		if($cxActInfo['end_time']){
			$info['end_time'] = date("Y-m-d",$cxActInfo['end_time']);
		}
		if($cxActInfo['part_brands']){
			$info['voucher_brand_name'] = $cxActInfo['part_brands'];
		}
		if($cxActInfo['brands_logo']){
			$info['logo'] = $cxActInfo['brands_logo'];
		}
		if($cxActInfo['coupon_type']){//代金券类型
			$info['promo_tools_voucher_type'] = $cxActInfo['coupon_type'];
		}
		if($cxActInfo['coupon_deno']){//券面额
			$info['worth_value'] = $cxActInfo['coupon_deno'];
		}
		if($cxActInfo['coupon_name']){//券名称
			$info['voucher_name'] = $cxActInfo['coupon_name'];
		}
		if($cxActInfo['coupon_remarks']){//券备注
			$info['voucher_note'] = $cxActInfo['coupon_remarks'];
		}
		if($cxActInfo['time_type']){//1、FIXED（绝对有效期），2、 RELATIVE（相对有效期）
			$info['validate_type'] = $cxActInfo['time_type'];
		}
		if($cxActInfo['con_threshold']){//券使用消费门槛
			$info['user_min_consume'] = $cxActInfo['con_threshold'];
		}
		if($cxActInfo['coupon_stime']){
			$info['voucher_start_time'] = date("Y-m-d",$cxActInfo['coupon_stime']);
		}
		if($cxActInfo['coupon_etime']){
			$info['voucher_end_time'] =  date("Y-m-d",$cxActInfo['coupon_etime']);
		}
		if($cxActInfo['coupon_reletime']){
			$info['voucher_relative_time'] = $cxActInfo['coupon_reletime'];
		}
		if($cxActInfo['coupon_relstime']){
			$info['voucher_relative_delay'] = $cxActInfo['coupon_relstime'];
		}
		if($cxActInfo['max_restrictions']){//消费次数
			$info['user_win_count'] = $cxActInfo['max_restrictions'];
		}
		if($cxActInfo['rec_restrictions']){//消费频次 D||2
			$arr = explode("||",$cxActInfo['rec_restrictions']);
			$info['user_win_frequency_date'] = $arr[0];
			$info['user_win_frequency_num'] = $arr[1];
		}
		if($cxActInfo['writeoff_store']){
			$info['constraint_suit_shops'] = json_decode($cxActInfo['writeoff_store'],1);
		}
		if($cxActInfo['applicable_store']){
			$info['voucher_suit_shops'] = json_decode($cxActInfo['applicable_store'],1);
		}
		if($cxActInfo['coupon_availtime']){//使用时间一周内1、2、3
			$info['use_time_values'] = explode(",",$cxActInfo['coupon_availtime']);
		}
		if($cxActInfo['sele_availtime']){//时间
			$arr = explode("^", $cxActInfo['sele_availtime']);
			if(count($arr)==1){
				$arr1 = explode(",", $arr[0]);
				if($arr1[0]=="00-00-00" && $arr1[1]=="23:59:59"){
					$info['use_time_values_time']=array();
				}else{
					$info['use_time_values_time']=$arr;
				}
			}else{
				$info['use_time_values_time'] =$arr;
			}
		}
		if($cxActInfo['sele_unavailtime']){
			$info['use_forbidden_day'] = explode("^",$cxActInfo['sele_unavailtime']);
		}
		if($cxActInfo['coupon_des']){
			$info['use_rule_desc'] = $cxActInfo['coupon_des'];
		}
		if($cxActInfo['act_obj']){
			$info['act_obj'] = $cxActInfo['act_obj'];
		}
		if($cxActInfo['act_type']){
			$info['act_type'] = $cxActInfo['act_type'];
		}
		if($cxActInfo['auto_delay_flag']){//是否自动续期"Y"是 "N"否
			$info['auto_delay_flag'] =$cxActInfo['auto_delay_flag'];
		}
		if($cxActInfo['budget_total']){//券说明
			$info['quantity'] = $cxActInfo['budget_total'];
		}
		if($cxActInfo['act_state']==1){
			$info['stop_time']="--";
			$info['act_state']=$cxActInfo['act_state'];
			if($cxActInfo['end_time']<time()){//时间已过
				$info['act_state']=0;
				$info['stop_time']= date("Y-m-d",($cxActInfo['end_time']+86400));
			}
		}else{
			$info['act_state']=$cxActInfo['act_state'];
			$info['stop_time'] = date("Y-m-d",$cxActInfo['end_time']);
		}
		$info['act_type'] = $cxActInfo['act_type'];
		return $info;
	}
	/*获取首页统计数据*/
	public function ajaxGetTongji(){
		$seller_id = $_SESSION['seller_id'];
		if($_SESSION[$seller_id]['indextg']){
			$this->ajaxReturn($_SESSION[$seller_id]['indextg'],"数据加载成功",1);
		}
		//todo 查找会员数
		$merNumTotal = 0;//会员总数
		$merNumNew = 0;//新增会员数
		$tradeTwiceUser = 0;
		$data = array(//今日数据
			"begin_date"=>date("Ymd"),
			"end_date"=>date("Ymd"),
			"biz_type"=> "MemberQuery"
		);
		$resCount = $this->api_indicatorQuery(json_encode($data));
		if($resCount['code']==0){
			$countArray = json_decode($resCount['indicator_infos'],1);
			$merNumNew = $countArray[0]['today_campaign_new_user_cnt'];
		}
		$merNumTotalCon = array(
			"conditions"=>array(
				array(
					"op"=>"IN",
					"tagCode"=>"pam_gender",
					"value"=>array(1,2,0)
				)
			)
		);
		$merNumTotalConResult = $this->api_MemberQuery(json_encode($merNumTotalCon));
		if($merNumTotalConResult["code"]==0){
			$countArray = json_decode($merNumTotalConResult['summary_values'],1);
			$merNumTotal = $countArray['total'];
		}
		$tradeTwiceUserCon = array(
			"conditions"=>array(
				array(
					"op"=>"GTEQ",
					"tagCode"=>"pam_trade_cnt",
					"value"=>2
				)
			)
		);
		$tradeTwiceUserResult = $this->api_MemberQuery(json_encode($tradeTwiceUserCon));
		if($tradeTwiceUserResult["code"]==0){
			$countArray = json_decode($tradeTwiceUserResult['summary_values'],1);
			$tradeTwiceUser = $countArray['total'];
		}
		$res['memberCount'] = array(//会员数
			"merNumNew" =>$merNumNew,
			"merNumTotal"=>$merNumTotal,
			"tradeTwiceUser"=>$tradeTwiceUser
		);
	/******************消费数据开始*********************/
		$consumptionToday = 0;//今日收益
		$consumptionTotal = 0;//累计收益金额(元)
		$consumptionavg = 0;//平均笔单价
		$couponProfit =0;//昨日收益
		$couponTaken =0;//今日领券量(张)
		$data = array(//今日数据
			"begin_date"=>date("Ymd"),
			"end_date"=>date("Ymd"),
			"biz_type"=> "TradeQuery"
		);
		$resConsumptionToday = $this->api_indicatorQuery(json_encode($data));
		if($resConsumptionToday["code"]==0){
			$countArray = json_decode($resConsumptionToday['indicator_infos'],1);
			$consumptionToday = $countArray[0]['today_trade_amt']/100.00;
		}else{
			$this->ajaxReturn(array(),$resConsumptionToday['sub_msg'],0);
		}

		$data = array(//昨日数据
			"begin_date"=>date("Ymd",(time()-86400)),
			"end_date"=>date("Ymd",(time()-86400)),
			"biz_type"=> "TradeQuery"
		);
		$resConsumptionTotal = $this->api_indicatorQuery(json_encode($data));
		if($resConsumptionTotal["code"]==0){
			$countArray = json_decode($resConsumptionTotal['indicator_infos'],1);
			$couponProfit = $countArray[0]['today_trade_amt']/100.00;
			$consumptionTotal = $countArray[0]['total_trade_amt']/100.00;
			$consumptionavg = ceil($countArray[0]['total_trade_amt']/$countArray[0]['total_trade_cnt'])/100.00;
		}else{
			$this->ajaxReturn(array(),$resConsumptionTotal['sub_msg'],0);
		}

		/******************收益数据开始*********************/
		$data = array(//今日数据
			"begin_date"=>date("Ymd",(time()-86400)),
			"end_date"=>date("Ymd"),
			"biz_type"=> "CampaignQuery"
		);
		$couponTotal = $this->api_indicatorQuery(json_encode($data));
		if($couponTotal["code"]==0){
			$countArray = json_decode($couponTotal['indicator_infos'],1);
			$couponTaken = $countArray[0]['today_taken_cnt'];
		}else{
			$this->ajaxReturn(array(),$couponTotal['sub_msg'],0);
		}
		$where = array(
			"end_time"=>array("gt",strtotime(date("Y-m-d",(time()+86400)))),
			"act_state"=>1
		);
		$couponUse = M("Cxactivity")->where($where)->count();
		$res['consumption'] = array(//消费额
			"consumptionToday"=>$consumptionToday,
			"couponTaken"=>$couponTaken,
			"couponUse"=>$couponUse
		);
		$res['coupon'] = array(//券统计
			"couponProfit"=>$couponProfit,//昨日收益
			"consumptionTotal"=>$consumptionTotal,
			"consumptionavg"=>$consumptionavg
		);
		$_SESSION[$seller_id]['indextg'] = $res;
		$this->ajaxReturn($res,"数据加载成功",1);
	}
	/**活动管理数据统计**/
	public function ajaxGetActTongji(){
//		$this->ajaxReturn(json_decode($str,1),"加载成功",1);
		if(empty($_POST['camp_id'])){
			$this->ajaxReturn(array(),"请选择要查询的活动",0);
		}
		$cxActInfo = M("Cxactivity")->where(array("camp_id"=>$_POST['camp_id']))
			->field("applicable_store shop_ids,act_name,act_type,act_state,stop_time,start_time,end_time,time_type validate_type,coupon_stime voucher_start_time,coupon_etime voucher_end_time,coupon_relstime,coupon_reletime")
			->find();
		if(empty($cxActInfo)){
			$this->ajaxReturn(array(),"暂无该条活动报告",0);
		}
		/***计算开始和结束时间**/
		$begin_date = $cxActInfo['start_time'];
		$time = strtotime(date("Ymd"));
		if($cxActInfo['validate_type'] == 2){//相对时间
			if($cxActInfo['act_state'] == 0){
				$coupon_endTime = $cxActInfo['stop_time']+86400*$cxActInfo['coupon_reletime'];
				$end_date = $time<$coupon_endTime?$time:$coupon_endTime;
			}else{
				$coupon_endTime = $cxActInfo['end_time']+86400*$cxActInfo['coupon_reletime'];
				$end_date = $time<$coupon_endTime?$time:$coupon_endTime;
			}
		}else{
			$coupon_endTime = $cxActInfo['voucher_end_time'];
			$end_date = $time<$coupon_endTime?$time:$coupon_endTime;
		}
		/***输出title和活动简介**/
		$title = $cxActInfo['act_name']."活动报告";//活动报告名称
		if($cxActInfo['act_state'] == 1){
			if($cxActInfo['validate_type'] ==1 ){
				$warm = "您的“".$cxActInfo['act_name']."”活动正在进行中（ ".date("Y年m月d日",$begin_date)." 至 ".date("Y年m月d日",$end_date)
					." ），本活动的优惠券有效期为 ".date("Y年m月d日",$cxActInfo['voucher_start_time'])." 至 ".date("Y年m月d日",$cxActInfo['voucher_end_time'])."。以下是活动报告。";
			}elseif($cxActInfo['validate_type'] ==2){
				$warm = "您的“".$cxActInfo['act_name']."”活动正在进行中（ ".date("Y年m月d日",$cxActInfo['start_time'])." 至 ".date("Y年m月d日",$cxActInfo['start_time'])
					." ），本活动的优惠券有效期为领券后第".$cxActInfo['coupon_relstime']."天到领券后第".$cxActInfo['coupon_reletime']."天 。以下是活动报告。";
			}
		}else{
			if($cxActInfo['validate_type'] ==1 ){
				$warm = "您的“".$cxActInfo['act_name']."”已结束（ ".date("Y年m月d日",$begin_date)." 至 ".date("Y年m月d日",$end_date)
					." ），本活动的优惠券有效期为 ".date("Y年m月d日",$cxActInfo['voucher_start_time'])." 至 ".date("Y年m月d日",$cxActInfo['voucher_end_time'])."。以下是活动报告。";
			}elseif($cxActInfo['validate_type'] ==2){
				$warm = "您的“".$cxActInfo['act_name']."”已结束（ ".date("Y年m月d日",$begin_date)." 至 ".date("Y年m月d日",$end_date)
					." ），本活动的优惠券有效期为领券后第".$cxActInfo['coupon_relstime']."天到领券后第".$cxActInfo['coupon_reletime']."天 。以下是活动报告。";
			}
		}
		/**查找相关活动报告*/
		$data = array(
			"begin_date"=>date("Ymd",$begin_date-864000),
			"end_date"=>date("Ymd",$end_date),
			"biz_type"=> "CampaignQuery",
			"ext_info"=>array("camp_id"=>$_POST['camp_id'])
		);
		$result = $this->api_indicatorQuery(json_encode($data));
		if($result["code"]!=0){
			$this->ajaxReturn('',$result['sub_msg'],0);
		}
		$countArray = json_decode($result['indicator_infos'],1);
		/***整理活动相关数据**/
		if(empty($countArray)){
			$str = '[{
						"biz_date": "'.date("Ymd").'",
					   "today_campaign_new_user_cnt": "0",
					   "today_campaign_trade_amt": "0",
					   "today_taken_cnt": "0",
					   "today_taken_user_cnt": "0",
					   "today_used_cnt": "0",
					   "today_used_user_cnt": "0",
					   "total_campaign_new_user_cnt": "0",
					   "total_campaign_order_amt": "0",
					   "total_campaign_trade_amt": "0",
					   "total_campaign_trade_cnt": "0",
					   "total_taken_cnt": "0",
					   "total_taken_user_cnt": "0",
					   "total_used_cnt": "0",
					   "total_used_user_cnt": "0"
				   }]';
			$countArray = json_decode($str,1);
		}
		/**参与活动总人数*/
		$total_taken_user_cnt = 0;
		/**总领券总数*/
		$total_taken_cnt = 0;
		/**总核券人数*/
		$total_used_user_cnt = 0;
		/**总核券数*/
		$total_used_cnt = 0;
		/**活动总交易笔数*/
		$total_campaign_trade_cnt = 0;
		/**活动总累计收益金额*/
		$total_campaign_trade_amt = 0;
		/**截止查找结束时间为止活动新增actAddNums人数**/
		$actAddNums = 0;
		/**数据**/
		foreach ($countArray as $ka=>$va){
			$countArray[$ka]['today_campaign_trade_amt'] = $va['today_campaign_trade_amt']/100;
			$countArray[$ka]['biz_date'] = date("m-d",strtotime($va['biz_date']));
			/**参与活动总人数*/
			$total_taken_user_cnt = $total_taken_user_cnt+$va['today_taken_user_cnt'];
			/**总领券总数*/
			$total_taken_cnt = $total_taken_cnt+$va['today_taken_cnt'];
			/**总核券人数*/
			$total_used_user_cnt = $total_used_user_cnt+$va['today_used_user_cnt'];
			/**总核券数*/
			$total_used_cnt = $total_used_cnt+$va['today_used_cnt'];
			/**活动总交易笔数*/
			$total_campaign_trade_cnt = $total_campaign_trade_cnt+$va['today_used_cnt'];
			/**活动总累计收益金额*/
			$total_campaign_trade_amt = $total_campaign_trade_amt+$va['today_campaign_trade_amt'];
			/**截止查找结束时间为止活动新增actAddNums人数**/
			$actAddNums = $actAddNums+$va['today_campaign_new_user_cnt'];
			unset($countArray[$ka]['total_campaign_new_user_cnt']);
			unset($countArray[$ka]['total_campaign_order_amt']);
			unset($countArray[$ka]['total_campaign_trade_amt']);
			unset($countArray[$ka]['total_campaign_trade_cnt']);
			unset($countArray[$ka]['total_taken_cnt']);
			unset($countArray[$ka]['total_taken_user_cnt']);
			unset($countArray[$ka]['total_used_cnt']);
			unset($countArray[$ka]['total_used_user_cnt']);
		}
		$total_campaign_trade_amt = $total_campaign_trade_amt/100;//转化为元
		$countArray = array_reverse($countArray);
//		$this->debug($actAddNums);
		/**截止查找结束时间为止 一共活动的天数*/
		$actdays = ceil(($end_date - $begin_date)/86400)+1;
		/**查找该活动之前$actdays天的商铺报告*/
		$beforeActData = array(
			"begin_date"=>date("Ymd",($begin_date-86400*$actdays)),
			"end_date"=>date("Ymd",($begin_date-86400)),
			"biz_type"=> "CampaignQuery",
		);
		$beforeActResult = $this->api_indicatorQuery(json_encode($beforeActData));
		if($beforeActResult["code"]==0){
			$beforeActArray = json_decode($beforeActResult['indicator_infos'],1);
		}
		if(empty($beforeActArray)){
			for($i=$actdays+1;$i<$actdays+30;$i++){
				$str = '{
						"biz_date": "'.date("Ymd",$time-86400*$i).'",
					   "today_campaign_new_user_cnt": "0",
					   "today_campaign_trade_amt": "0",
					   "today_taken_cnt": "0",
					   "today_taken_user_cnt": "0",
					   "today_used_cnt": "0",
					   "today_used_user_cnt": "0",
					   "total_campaign_new_user_cnt": "0",
					   "total_campaign_order_amt": "0",
					   "total_campaign_trade_amt": "0",
					   "total_campaign_trade_cnt": "0",
					   "total_taken_cnt": "0",
					   "total_taken_user_cnt": "0",
					   "total_used_cnt": "0",
					   "total_used_user_cnt": "0"
				   }';
				$beforeActArray[] = json_decode($str,1);
			}
		}else{
			for($i=$actdays;$i<30+$actdays;$i++){
				$str = '{
						"biz_date": "'.date("Ymd",$time-86400*$i).'",
					   "today_campaign_new_user_cnt": "0",
					   "today_campaign_trade_amt": "0",
					   "today_taken_cnt": "0",
					   "today_taken_user_cnt": "0",
					   "today_used_cnt": "0",
					   "today_used_user_cnt": "0",
					   "total_campaign_new_user_cnt": "0",
					   "total_campaign_order_amt": "0",
					   "total_campaign_trade_amt": "0",
					   "total_campaign_trade_cnt": "0",
					   "total_taken_cnt": "0",
					   "total_taken_user_cnt": "0",
					   "total_used_cnt": "0",
					   "total_used_user_cnt": "0"
				   }';
				$beforeActArray[] = json_decode($str,1);
			}
		}
		/**截止查找结束时间为止活动新增beforeActAddNums人数**/
		$beforeActAddNums = 0;//活动之前$actdays天内新增会员数
		$beforeActTradeNums = 0;//活动之前$actdays天内交易的笔数
		$beforeActTradeAmt = 0;//活动之前$actdays天内交易的金额
		foreach ($beforeActArray as $kba=>$vba){
			$beforeActArray[$kba]['biz_date'] = date("m-d",strtotime($vba['biz_date']));
			$beforeActAddNums = $beforeActAddNums+$vba['today_campaign_new_user_cnt'];
			$beforeActTradeNums = $beforeActTradeNums+$vba['today_used_cnt'];
			$beforeActTradeAmt = $beforeActTradeAmt+$vba['today_campaign_trade_amt'];
			unset($beforeActArray[$kba]['total_campaign_new_user_cnt']);
			unset($beforeActArray[$kba]['total_campaign_order_amt']);
			unset($beforeActArray[$kba]['total_campaign_trade_amt']);
			unset($beforeActArray[$kba]['total_campaign_trade_cnt']);
			unset($beforeActArray[$kba]['total_taken_cnt']);
			unset($beforeActArray[$kba]['total_taken_user_cnt']);
			unset($beforeActArray[$kba]['total_used_cnt']);
			unset($beforeActArray[$kba]['total_used_user_cnt']);
		}
		$beforeActTradeAmt = $beforeActTradeAmt/100.00;
		$beforeActArray = array_reverse($beforeActArray);
		$actPercent = $actAddNums>0?(ceil($actAddNums*10000/$actdays)/100.00):0.00;
		$beforeActPercent = $beforeActAddNums>0?(ceil($beforeActAddNums*10000/$actdays)/100.00):0.00;
		$addUser = array(//新增会员信息
			"actDays"=>$actdays,//活动天数
			"endDay"=>date("Y-m-d",$end_date),//查询截止时间
			"actAddNums"=>$actAddNums,//活动新增会员数
			"beforeActAddNums"=>$beforeActAddNums,//活动前几天新增的会员数
			"percent"=>(($actPercent-$beforeActPercent)>0?($actPercent-$beforeActPercent):"0.00")."%"
		);
		if($total_taken_user_cnt>0 && $total_used_user_cnt>0){
			$percentTakenCnt = (ceil($total_used_user_cnt*10000/$total_taken_user_cnt)/100.00)."%";
		}else{
			$percentTakenCnt = "0.00%";
		}
		$actTakenCnt = array(//活动参与人数
			"total_taken_user_cnt"=>$total_taken_user_cnt,
			"total_taken_cnt"=>$total_taken_cnt,
			"total_used_user_cnt"=>$total_used_user_cnt,
			"total_used_cnt"=>$total_used_cnt,
			"percent"=>$percentTakenCnt
		);
		/******会员消费对比（与活动前）******/
		if($total_campaign_trade_cnt<=0){
			$act_before_trade = '0.00%';//交易笔数提升数
			$act_before_avag = "0.00%";//交易单笔收益提升数
			$act_avag_amt = 0;//活动平均单笔收益
			$before_act_avag_amt= $beforeActTradeAmt/$beforeActTradeNums;//活动之前$actdays天平均单笔收益
		}else{
			$act_before_trade = $beforeActTradeNums>0?(ceil($total_campaign_trade_cnt*10000/$beforeActTradeNums)/100.00)."%":$total_used_user_cnt."笔";
			$act_avag_amt = ceil($total_campaign_trade_amt*100/$total_campaign_trade_cnt)/100.00;//活动平均单笔收益
			$before_act_avag_amt = ceil($beforeActTradeAmt*100/$beforeActTradeNums)/100.00;//活动之前$actdays天平均单笔收益
			$act_before_avag = $before_act_avag_amt>0?(ceil($act_avag_amt*10000/$before_act_avag_amt)/100.00)."%":$act_avag_amt."元";
		}
//		echo $beforeActTradeAmt;
//		echo "==>";
//		echo $beforeActAddNums;
//		$this->debug($before_act_avag_amt);
		$userTradeCnt = array( //会员消费对比（与活动前）
			"act_before_trade"=>$act_before_trade,//交易笔数提升数
			"act_before_avag"=>$act_before_avag,//笔单价提升了
			"total_campaign_trade_cnt"=>$total_campaign_trade_cnt,//活动期间交易笔数
			"beforeActTradeNums"=>$beforeActTradeNums,//活动前交易笔数
			"total_campaign_trade_amt"=>$total_campaign_trade_amt,//活动期间总收益金额
			"beforeActTradeAmt"=>$beforeActTradeAmt,//活动前几天总收益金额
			"act_avag_amt"=>$act_avag_amt,//活动期间比单价
			"before_act_avag_amt"=>$before_act_avag_amt//活动之前几天笔单价
		);
		/******营销收入****/
		$shopNums = count(json_decode($cxActInfo['shop_ids']));
		$actTakenAmt = $total_campaign_trade_amt;//活动期间总收益金额
		$actTakenAvagAmt = $act_avag_amt;//平均每单拉动消费
		$actAmt = array(//营销收入
			"shopNums"=>$shopNums,//门店数
			"actTakenAmt"=>$actTakenAmt, //累计营销收入元
			"actTakenAvagAmt"=>$actTakenAvagAmt//平均每单拉动消费
		);
		//$this->debug($addUser);
		$res['act_state'] = $cxActInfo['act_state'];
		$res['act_type'] = $cxActInfo['act_type'];
		$res['title'] = $title;
		$res['cxActInfo'] = $warm;
		$res['userDate'] = $addUser;
		$res['actTakenCnt'] = $actTakenCnt;
		$res['userTradeCnt'] = $userTradeCnt;
		$res['actAmt'] = $actAmt;
		$res['acttongji'] = $countArray;
		$res['beforeActtongji'] = $beforeActArray;
		$res['alltongji'] = array_merge($beforeActArray,$countArray);
		$this->ajaxReturn($res,"数据加载成功",1);
	}

	public function ajaxGetActShopTongji(){
		if(empty($_POST['camp_id'])){
			$this->ajaxReturn(array(),"请选择要查询的活动",0);
		}
		$cxActInfo = M("Cxactivity")->where(array("camp_id"=>$_POST['camp_id']))
			->field("writeoff_store shop_ids,act_name,act_type,act_state,stop_time,start_time,end_time,time_type validate_type,coupon_stime voucher_start_time,coupon_etime voucher_end_time,coupon_relstime,coupon_reletime")
			->find();
		if(empty($cxActInfo)){
			$this->ajaxReturn(array(),"暂无该条活动报告",0);
		}
		/***计算开始和结束时间**/
		$sort_field = "today_campaign_trade_amt";
		$data = array(
			"begin_date"=>date("Ymd",time()-86400),
			"end_date"=>date("Ymd",time()-86400),
			"biz_type"=> "CampaignQueryByStore",
			"ext_info"=>array(
				"camp_id"=>$_POST['camp_id'],
				"sort_field"=>$sort_field,
				"sort_type"=>"DESC",
				"store_Ids"=>implode(",",json_decode($cxActInfo['shop_ids'],1))
			)
		);
		//$this->debug($data);
		$result = $this->api_indicatorQuery(json_encode($data));
		//$this->debug($result);
		if($result["code"]!=0){
			if(!($result['code'] ==40004 && $result['sub_msg']=="参数排序指标暂不支持" && $result['sub_code']=="ACTIVITY_INDICATOR_QUERY_FAIL")){
				$this->ajaxReturn('',$result['sub_msg'],0);
			}
		}
		$countArray = json_decode($result['indicator_infos'],1);
		$shopnum=0;
		$shopsLists = array();
		if(empty($countArray)){
			$shop_ids =json_decode($cxActInfo['shop_ids']);
			$shops = $_SESSION['shoplist'];
			foreach ($shop_ids as $vs){
				if($shopnum>=5) break;
				foreach ($shops as $ksh=>$vsh){
					if($vs ==$vsh['shop_id']){
						$store_name = $vsh['main_shop_name'];
					}
				}
				$shopInfo = array();
				$shopInfo['biz_date'] = date("Ymd",time()-86400);
				$shopInfo['store_id'] = $vs;
				$shopInfo['store_name'] = $store_name;
				$shopInfo['today_used_cnt'] = 0;
				$shopInfo['today_used_user_cnt'] = 0;
				$shopInfo['total_campaign_trade_amt'] = 0;
				$shopInfo['total_campaign_trade_amt'] = 0;
				$shopInfo['total_used_cnt'] =0;
				$shopsLists['shopLists'][] =$shopInfo;
				$shopnum++;
			}
			$this->ajaxReturn($shopsLists,"",1);
		}
		foreach ($countArray as $k=>$v){
			if($shopnum>=5) break;
			$shopInfo = array();
			$shopInfo['biz_date'] = $v['biz_date'];
			$shopInfo['store_id'] = $v['store_id'];
			$shopInfo['store_name'] = $v['store_name'];
			$shopInfo['today_used_cnt'] = $v['today_used_cnt'];
			$shopInfo['today_used_user_cnt'] = $v['today_used_user_cnt'];
			$shopInfo['total_campaign_trade_amt'] = empty($v['total_campaign_trade_amt'])?$v['today_campaign_trade_amt']:$v['total_campaign_trade_amt'];
			$shopInfo['total_campaign_trade_amt'] = $shopInfo['total_campaign_trade_amt']/100.00;
			$shopInfo['total_used_cnt'] = empty($v['total_used_cnt'])?$v['today_used_cnt']:$v['total_used_cnt'];
			$shopsLists['shopLists'][] =$shopInfo;
			$shopnum++;
		}
		//echo json_encode($shopsLists);exit;
		$this->ajaxReturn($shopsLists,date("Y年m月d日",time()-86400)."门店收益排行",1);
	}

	/**生成外部批次ID**/
	private function getOutBizNo(){
		$str1 = substr(md5($_SESSION['seller_id']),mt_rand(1,24),8);
		$str2 = $this->GetRandStr(8);
		return date("YmdHis").$str1.$str2;
	}
	private function GetRandStr($len)
	{
		$chars = array(
			 "a", "b", "c", "d", "e", "f", "g","h", "i", "j", "k", "l", "m", "n", "o", "p", "q","r", "s", "t", "u", "v", "w", "x", "y", "z"
		);
		$charsLen = count($chars) - 1;
		shuffle($chars);
		$output = "";
		for ($i=0; $i<$len; $i++)
		{
			$output .= $chars[mt_rand(0, $charsLen)];
		}
		return $output;
	}

	private $_imgPath = './Public/Uploads/actImages/'; //上传图片临时保存路径
	//上传图片
	public function ajaxUploadImages(){
		$this->_imgPath = $this->_imgPath.substr($_SESSION['seller_id'],0,7)."/".$_SESSION['seller_id']."/";
		import('ORG.Net.UploadFile');
		$upload = new UploadFile();// 实例化上传类
		$upload->maxSize  =  4*1024*1024;// 设置上传大小
		$upload->allowExts  = array('jpg', 'png', 'jpeg','bmp','gif');// 设置上传类型
		$upload->savePath =  $this->_imgPath;// 设置上传目录
		$upload->saveRule = 'time';
		if(!is_dir($upload->savePath)){
			mkdirs($upload->savePath);
		}
		if(!$upload->upload()) {// 上传错误提示错误信息
			$this->ajaxReturn(null,$upload->getErrorMsg(),0);
		}else{// 上传成功 获取上传文件信息
			$info = $upload->getUploadFileInfo();
			$filePath = $info[0]['savepath'].$info[0]['savename'];
			if(file_exists($filePath)){ //上传到图片空间
				$filename = '@'.APP_ROOT.$filePath;
				$filename = str_replace('\\', '/', str_replace('./', '/', $filename));
				$res=$this->api_uploadImage($info[0]['savename'],$filename,$info[0]['extension']);
				if(!$res['code']){
					unlink($filePath);
					$data['image_path']=$filePath;
					$data['image_id']=$res['image_id'];
					$data['image_url']=$res['image_url'];
					$this->ajaxReturn($data,'上传成功！',1);
				}else{
					if($res['code_msg']=='应用授权令牌已过期'){
						$resData = array('sq'=>true,'url'=>$this->_authUrl);
					}else{
						$resData = array();
					}
					$this->ajaxReturn($resData,'上传失败！'.$res['code_msg'],0);
				}
			}else{
				$this->ajaxReturn(null,'上传失败！',0);
			}
		}
	}
}

?>
