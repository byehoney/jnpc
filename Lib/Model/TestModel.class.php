<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/26
 * Time: 16:37
 */
class TestModel extends Model
{
    /**
     * 获取支付授权索要的参数
     */
    public function getTestArray(){
        $seller_id = '2088102169058406';//卖家商铺id
        $buyer_logon_id = 'scgmkg0194@sandbox.com';
        $data['out_trade_no'] = $this->getOutTradeNo(time(),$buyer_logon_id,$seller_id);
        file_put_contents("./test_outTradeNo.txt",$data['out_trade_no']);
        $data['seller_id'] = $seller_id;
        $data['total_amount'] =10.20;//卖家支付宝用户ID。 如果该值为空，则默认为商户签约账号对应的支付宝用户ID
        $data['buyer_logon_id'] = $buyer_logon_id;//买家支付宝账号
        $data['subject'] = '这是一次测试支付';//买家支付宝账号
        $data['body'] = "本次测试主要在测试交易返回值是什么";
//        $data['discountable_amount'] = 8.20; //可打折金额. 参与优惠计算的金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000] 如果该值未传入，但传入了【订单总金额】，【不可打折金额】则该值默认为【订单总金额】-【不可打折金额】
//        $data['undiscountable_amount'] = 2;//不可打折金额；
//        $data['goods_detail'] =
//            array(
//                "goods_id"=>"apple-01",
//                "alipay_goods_id"=>"20010001",
//                "goods_name"=>"ipad",
//                "quantity"=>"1",
//                "price"=>2000,
//                "goods_category"=>34543238,
//                "body"=>"特价手机",
//                "show_url"=>"http://www.alipay.com/xxx.jpg",
//            );
//        $data['operator_id'] = 'yx_001';//商户操作员编号
//        $data['store_id'] = 'NJ_001';//商户门店编号
//        $data['terminal_id'] = 'NJ_T_001';//商户机具终端编号
//        $data['timeout_express'] = "90m";
//        $data['alipay_store_id'] = "2016052600077000000015640104";
        return $data;
    }
    
    public function getRefundData(){
        $out_trade_no = file_get_contents("./test_outTradeNo.txt");
        if($out_trade_no){
            $data['out_trade_no'] = $out_trade_no;
        }else{
            $data['out_trade_no'] = '';
        }
        $data['refund_amount'] = 1;
        $data['refund_reason'] = "无理由退款";
        $data['out_request_no'] = $out_trade_no."_1";
        return $data;
    }
    /**
     * 获取out_trade_no
     */
    private function getOutTradeNo($time,$buyer_id,$seller_id){
        $time = date("YmdHis",$time);
        return md5($seller_id.$time.$buyer_id);
    }
}