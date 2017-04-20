<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/26
 * Time: 16:37
 */
class ShareModel extends Model
{
   /**
    * 添加分享表
    */
    public function addTable($data){
        $res =  $this->add($data);
        return $res;
    }
    /**
     * 查找（）参与者
     */
    public function findTableForBuyer($buyer_id,$act_id,$endtime){
        $map['buyer_id'] = $buyer_id;
        $map['act_id'] = $act_id;
        $map['status'] = 1;
        if(!empty($endtime)){
            $map['createtime'] = array("lt",$endtime);//说明 该次订单下单在分享之后
        }
        $res = $this->where($map)->order("id DESC")->limit(1)->select();
        return $res;
    }
    /**
     * 查找分享表
     */
    public function findTableForShare($share_id,$act_id,$out_trade_no){
        $map['share_id'] = $share_id;
        $map['act_id'] = $act_id;
        $map['out_trade_no'] = $out_trade_no;
        $res = $this->where($map)->select();
        return $res;
    }
}