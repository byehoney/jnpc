<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/26
 * Time: 16:37
 */
class ActivityModel extends Model
{
   /**
    * 添加活动表
    */
    public function addTable(){
        //先检测是否有活动
        $res = $this->checkActivity();
        if(!$res['status']){//有活动
            return $res;
        }
        $actData = $this->getActivityData();
        if(!$actData['status'] || empty($actData['data'])){
            return $actData;
        }
        $data = $actData['data'];
        $data["createtime"] = time();
        $result = $this->add($data);
        if($result){
            $data['id'] = $result;
            $actData['data'] = $data;
            $actData['info'] = "存储成功";
            return $actData;
        }else{
            $actData['status'] = false;
            $actData['data'] = array();
            $actData['info'] = "系统繁忙，请重试";
            return $actData;
        }
    }
    /**
     * 修改活动列表
     */
    public function modifyTable(){
        //先检测是否有活动
        $res = $this->checkActivity();
        if($res['status']){//无活动
            $res['status'] = false;
            $res['data'] = array();
            $res['info'] = "没有查找到相关活动，请刷新重试";
            return $res;
        }
        $dataMap['id'] = $res['data']['id'];
        $actData = $this->getActivityData();
        if(!$actData['status']){
            return $actData;
        }
        $data = $actData['data'];
        $result = $this->where($dataMap)->save($data);
        if($result){
            $data['id'] = $dataMap['id'];
            $actData['data'] = $data;
            return $actData;
        }else{
            $actData['status'] = false;
            $actData['data'] = array();
            $actData['info'] = "暂无修改项";
            return $actData;
        }
    }
    /**
     * 查询订单
     */
    public function selectTable(){
        $map['seller_id'] = $_SESSION['seller_id'];
        $result = $this->where($map)->select();
        return $result;
    }
    /**
     * 通过ID查询订单
     */
    public function findTableByID($act_id){
        $map['id'] = $act_id;
        $result = $this->where($map)->find();
        return $result;
    }
    /**
     * 通过ID查询订单
     */
    public function findTableBySellerId($seller_id){
        $map['seller_id'] = $seller_id;
        $result = $this->where($map)->find();
        return $result;
    }
    /**
     * 通过ID查询订单
     */
    public function findTableByShopId($seller_id){
        $map['seller_id'] = $seller_id;
        $result = $this->where($map)->find();
        return $result;
    }
    /**
     * 通过ID查询订单
     */
    public function findTable(){
        if(!$_SESSION['seller_id']){
            return false;
        }
        $map['seller_id'] = $_SESSION['seller_id'];
        $result = $this->where($map)->find();
        return $result;
    }
    /**
     * 检测是否有活动
     */
    private function checkActivity(){
        $actMap['seller_id'] = $_SESSION['seller_id'];
        $res = $this->where($actMap)->select();
        if(empty($res)){
            $res['status'] = true;
            $res['info'] = "暂无相关活动";
            $res['data'] = array();
        }else{
            $res['status'] = false;
            $res['info'] = "存在相关活动";
            $res['data'] = $res[0];
        }
        if(count($res) >=2 ){
            file_put_contents("./log/error_Act.txt",$actMap['shop_id']."===>".json_encode($res)."\n\r",FILE_APPEND);
        }
        return $res;
    }
    /**
     * 获取数据库数据
     */
    private function getActivityData(){
        $res['status'] = true;
        if($_POST['start_time']){
            $data['start_time'] = strtotime($_POST['start_time']);
        }else{
            $res['status'] = false;
            $res['info'] = "开始时间不能为空";
            return $res;
        }

        if($_POST['end_time']){
            $data['end_time'] = strtotime($_POST['end_time']);
        }else{
            $res['status'] = false;
            $res['info'] = "结束时间不能为空";
            return $res;
        }

        if($_POST['min_fee']){
            $data['min_fee'] = $_POST['min_fee'];
        }else{
            $res['status'] = false;
            $res['info'] = "买家最低消费不能为空";
            return $res;
        }

        if($_POST['limit_day']){
            $data['limit_day'] = intval($_POST['limit_day']);
        }else{
            $res['status'] = false;
            $res['info'] = "拉单期限不能为空";
            return $res;
        }

        if($_POST['reStatus']){
            $data['reStatus'] = $_POST['reStatus'];
        }else{
            $res['status'] = false;
            $res['info'] = "请选择返现方式";
            return $res;
        }

        if($_POST['reNum']){
            $data['reNum'] = $_POST['reNum'];
        }else{
            $res['status'] = false;
            if($data['reStatus'] == 1){
                $res['info'] = "请填写返现金额(单位：元)";
            }else{
                $res['info'] = "请填写返现比例（%）";
            }
            return $res;
        }

        if($_SESSION['shoplist']){
            $data['main_shop_name'] = $_SESSION['shoplist'][0]['main_shop_name'];
        }else{
            $res['status'] = false;
            $res['info'] = "门店信息错误";
            return $res;
        }

        if($_SESSION['seller_id']){
            $data['seller_id'] = $_SESSION['seller_id'];
        }else{
            $res['status'] = false;
            $res['info'] = "卖家信息不能为空";
            return $res;
        }

        if($_SESSION['sessionkey']){
            $data['sessionkey'] = $_SESSION['sessionkey'];
        }else{
            $res['status'] = false;
            $res['info'] = "卖家信息不能为空";
            return $res;
        }

        if($_POST['shop_ids']){
            $data['shop_id'] = implode(",",$_POST['shop_ids']);
        }else{
            $res['status'] = false;
            $res['info'] = "请选择应用店铺";
            return $res;
        }
        if($res['status']){
            $data['actName'] = "拉单返现活动";
            $res['data'] = $data;
        }else{
            $res['info'] = "系统繁忙，请稍后再试（数据存储延时）";
            $res['data'] = array();
        }

        return $res;
    }


   
}