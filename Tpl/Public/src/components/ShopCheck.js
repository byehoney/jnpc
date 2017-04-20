import React from 'react';
import {Modal,Checkbox,Form,Col,Row,Message,Spin} from 'antd';
import moment from 'moment';

const CheckboxGroup = Checkbox.Group;

var ModalShop = React.createClass({
		getInitialState: function () {
    return {
    	shopList:[],
    	loading:true,
    	checkedShops:[],
	    indeterminate: true,
	    checkAll: false,
    };
  },
	componentDidMount: function() {
	  	let that = this;
	  	$.ajax({
	      	type:"POST",
	      	url:AJAX_URL+"/Coupon/ajaxGetShopInfos"+token,
	      	dataType:'json',
	      	data:{},
	      	success:function( data ){
	      		if( Number(data.status) ){
					const shopListArr = [];
					const shopCheckedArr = [];
					const ajaxshoplist = data.data.shoplist;
					
					if( data.data.shoplist.length ){
						shopCheckedArr.push( ajaxshoplist[0].shop_id );
					}
					for( const shopkey in ajaxshoplist ){
						var shopLine = {};
						shopLine.label = ajaxshoplist[shopkey].main_shop_name;
						shopLine.value = ajaxshoplist[shopkey].shop_id;
						shopListArr.push( shopLine );
					}
				    if (that.isMounted()) {
				    	that.setState({
				    		loading:false,
					    	shopList:shopListArr,
					    	checkedShops:shopCheckedArr
					    });
					    that.props.onFirstLoadShop(shopCheckedArr,data.data);
				  	}
	      		}else{
	      			message.error(data.info);
	      		}
	      	}
	    });
	},
	onSeletOk:function(){
		var checkedShops = this.state.checkedShops;
		
		if( checkedShops.length ){
			this.props.onOk(checkedShops);
		}else{
			Message.error('请选择至少1家门店');
		}
	},
	onCancel(){
		this.props.onCancel(this);
	},
	onChangeShop:function(checkedValues){
		const shopList = this.state.shopList;
		this.setState({
			checkedShops:checkedValues,
			indeterminate: !!checkedValues.length && (checkedValues.length < shopList.length),
      checkAll: checkedValues.length === shopList.length,
		})
	},
	onCheckAllChange(e){
		const shopList = this.state.shopList;
		let shopListArr = [];
		for( const shopkey in shopList ){
			var shopLine = {};
			shopListArr.push( shopList[shopkey].value );
		}
    this.setState({
      checkedShops: e.target.checked ? shopListArr : [],
      indeterminate: false,
      checkAll: e.target.checked,
    });
	},
	render:function(){
		const shop_visible = this.props.shop_visible;
		const shopkey = this.props.shopkey;
		const onShopCancel = this.props.onCancel;
		const { getFieldDecorator } = this.props.form;
		const formItemLayout = {
      labelCol: { span: 5 },
      wrapperCol: { span: 18 }
    }
		return (
			<Modal
        title="选择门店"
        okText="确定"
        visible={shop_visible}
        cancelText="取消"
        onCancel={this.onCancel}
        onOk={this.onSeletOk}
        shopkey={this.shopkey}
      >
				<Spin spinning={this.state.loading}>
					<Form layout="inline">
						<div style={{ borderBottom: '1px solid #E9E9E9',paddingBottom:'5px' }}>
		          <Checkbox
		            indeterminate={this.state.indeterminate}
		            onChange={this.onCheckAllChange}
		            checked={this.state.checkAll}
		          >
		            全选
		          </Checkbox>
		        </div>
		        <br />
						<CheckboxGroup options={this.state.shopList}  value={this.state.checkedShops} onChange={this.onChangeShop} />
					</Form>
				</Spin>
			</Modal>
		)
		
	}
})
const CreateModalSelectShopForm = Form.create()(ModalShop);





export default CreateModalSelectShopForm;