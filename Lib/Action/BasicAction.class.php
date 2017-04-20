<?php

class BasicAction extends ApiAction {
	public function __construct() {
		parent::__construct();
		$this->assign('user_id', $_SESSION['user_id']);
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
			if($_SESSION['operator_type'] == 'MERCHANT'){
				$data['sq']=true;
				$data['data']['sq']=true;//兼容下前端
			}else{
				$data['sq']=false;
				$data['data']['sq']=false;//兼容下前端
			}
        	$data['info']='商家授权超时，需要商家主账号重新授权后再继续操作！';
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
	
	//检查是否ajax访问
	protected function checkAjax() {
		if (!$this->isAjax()) {
			echo "禁止直接访问！";exit;
		}
	}
   
}