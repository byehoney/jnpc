<?php

class BaseAction extends ApiAction {
	public function __construct() {
		parent::__construct();
		$MODULE_NAME = MODULE_NAME;
		$MODULE_NAME = strtolower($MODULE_NAME);
		$this->_nocheckSellerPermisson = array('message','order');
		if(!(in_array($MODULE_NAME,$this->_nocheckSellerPermisson) || $MODULE_NAME=='share' && ACTION_NAME=='shop')){
			$this->checkBuyerLogin();
		}
	}
	
	/**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @return void
     */
    protected function ajaxReturn($data,$type='') {
    	if (!isset($data['sq']) && is_array($data) || empty($data)) $data['sq'] = false;//2013-11-5 lht change
        if(func_num_args()>2) {// 兼容3.0之前用法
            $args           =   func_get_args();
            array_shift($args);
            $info           =   array();
            $info['data']   =   $data;
            $info['info']   =   array_shift($args);
            $info['status'] =   array_shift($args);
            $data           =   $info;
            $type           =   $args?array_shift($args):'';
        }
		if(is_numeric(strpos($data['info'],'授权令牌已过期')) || is_numeric(strpos(strtolower($data['info']),'无效的应用授权令牌'))){
        	$jumpUrl = $this->_authUrl;
        	$data['data']['url']=$jumpUrl;
        	$data['url']=$jumpUrl;//兼容下前端
        	$data['sq']=true;
        	$data['data']['sq']=true;//兼容下前端
        	$data['info']='授权超时，请重新授权！';
			$data['status']=false;
        }
        //$this->_setLogDetail($data);
        if(empty($type)) $type  =   C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)){
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('content-type:application/json;charset=utf-8');  
                exit(json_encode($data));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler  =   isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
                exit($handler.'('.json_encode($data).');');  
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);            
            default     :
                // 用于扩展其他返回格式数据
                tag('ajax_return',$data);
        }
    }
	protected function checkBuyerLogin(){
		if(empty($_SESSION['buyer_id'])){
			$_SESSION['order_id']=$_GET['order_id'];
			$_SESSION['seller_id']=$_GET['seller_id'];
//			$_SESSION['share_id']=$_GET['share_id'];
//			$_SESSION['out_trade_no']=$_GET['out_trade_no'];
			$jumpUrl = $this->_buyerAuthUrl;
			if (IS_AJAX || $this->_isAjax){
				$data['url']=$jumpUrl;
				$data['sq']=true;
				$this->ajaxReturn($data, '授权超时，请重新授权！', false, 'json');
			}else{
				header('Location: '.$jumpUrl);
			}
		}
	}
	//检查是否ajax访问
	protected function checkAjax() {
		if (!$this->isAjax()) {
			echo "禁止直接访问！";exit;
		}
	}
}