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
//  $.get('ddd', function(result) {
//    var lastGist = result[0];
//    if (this.isMounted()) {
//      this.setState({
//      	...this.state,
//      	shopList:[{},{}],
//  			loading:false
//      });
//    }
//  }.bind(this));
    var data = [
    {
        "audit_desc": "审核通过",
        "contact_number": "01068817192",
        "audit_status": "AUDIT_SUCCESS",
        "city_code": "152200",
        "brand_logo": "QexGRuhrT7-aSRMGy-TFuwAAACMAAQQD",
        "category_id": "2015063000015529",
        "main_shop_name": "测试非凡门店",
        "partner_id": "2088711005018696",
        "payment_account": null,
        "processed_qr_code": "https://t.alipayobjects.com/files/alipaygiftprodtfs/T12uhwXi0eXXXXXXXX0a50fcd78889c1ed323ee09ae038470c",
        "district_code": "152224",
        "address": "内蒙兴安突泉",
        "is_show": "T",
        "branch_shop_name": "西山路",
        "shop_id": "2016112600077000000023306679"
    },
    {
        "audit_desc": "审核通过",
        "contact_number": "13939956589",
        "audit_status": "AUDIT_SUCCESS",
        "city_code": "410400",
        "brand_logo": "WlMGD2tSQx-wvMExLag5pwAAACMAAQED",
        "category_id": "2015091000060134",
        "main_shop_name": "泰岳兴洋超市",
        "partner_id": "2088711005018696",
        "payment_account": "1833993733@qq.com",
        "processed_qr_code": "https://t.alipayobjects.com/files/alipaygiftprodtfs/T1ejhAXoJXXXXXXXXX4e40f12be9176200b80b6357ece80c6c",
        "district_code": "410402",
        "address": "西市场十二中院内家属楼3单元东户",
        "is_show": "T",
        "branch_shop_name": "新华区店",
        "shop_id": "2016102500077000000019431622"
    }];
    const shopListArr = [];
		const shopCheckedArr = [];
		for( const shopkey in data ){
			var shopLine = {};
			shopLine.label = data[shopkey].main_shop_name;
			shopLine.value = data[shopkey].shop_id;
			shopListArr.push( shopLine );
		}
		if( data.length ){
			shopCheckedArr.push( data[0].shop_id );
		}
		
		
		if (this.isMounted()) {
	    this.setState({
	    	...this.state,
	    	shopList:shopListArr,
	    	checkedShops:shopCheckedArr,
				loading:false
	    });
	    this.props.onFirstLoadShop(shopCheckedArr);
  	}
  },
	onSeletOk:function(){
		var checkedShops = this.state.checkedShops;
		this.props.onOk(checkedShops);
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