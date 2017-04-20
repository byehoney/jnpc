import React from 'react';
import { Form, Input, InputNumber, Checkbox, DatePicker, Col, Button, message, Tooltip,Icon,Spin,Modal} from 'antd';
import { Router, Route, hashHistory} from 'react-router';
import moment from 'moment';
import { Link } from 'react-router';
import CollectionForm from './CollectionForm';


const FormItem = Form.Item;




class myForm extends React.Component {
    constructor(props) {
        super(props)
        this.state = {
        	checkedShops:[],
        	createing:false,
        	son_worth_value:'1',
        	son_user_min_consume:'3',
        	sonState:null,
        	sonFormData:null,
	        visible: false,
	        arrDate:[],
			type:'0',
	        start_time:moment(new Date(), 'YYYY-MM-DD'),
	        end_tip:null,
	        modal:{
	        	key:new Date().getTime()
	        }
        }
    }
    componentDidMount() {
    	this.ajaxShopInfo();
	    this.props.form.setFieldsValue({
	    	actName:'招募新会员',
	    	start_time:moment(new Date(), 'YYYY-MM-DD'),
	    	quantity :'999999',
	    	end_time:moment(new Date(new Date().getTime() + 86400000*7), 'YYYY-MM-DD')
	    	
	    })
    }
    
    ajaxShopInfo(){
	  	var that = this;
		$.ajax({
	      	type:"POST",
	      	url:AJAX_URL+"/Coupon/ajaxGetShopInfos"+token,
	      	dataType:'json',
	      	data:{},
	      	success:function( data ){
	      		if( Number(data.status) ){
					const shopCheckedArr = [];
					if( data.data.shoplist.length ){
						shopCheckedArr.push( data.data.shoplist[0].shop_id );
					}
				    that.setState({
				    	checkedShops:shopCheckedArr,
				    	voucher_brand_name:data.data.shoplist[0].main_shop_name,
				    	responseId:data.data.logo.id,
				    	logoUrl:data.data.logo.url
				    });
	      		}else{
	      			message.error(data.info);
	      		}
	      	}
	    });
    }
    
    
    // 选择select
    handleSelectChange(value) {
    }

    // 提交表单
    handleSubmit(e) {
        e.preventDefault();
        this.setState({
        	createing:true
        })

		let sonFormData = this.state.sonFormData;
		this.props.form.validateFields((err, values) => {
		    	if (!err) {
		    		var arrDate = this.state.sonState.arrDate;
		    		var newArrDate = [];
		    		if( arrDate.length ){
		    			for( let x=0;x<arrDate.length;x++ ){
		    				newArrDate.push( arrDate[x].join(',') )
		    			}
		    		}
    		    let weekList=[];
    		    let timeList=[];
    		    if( this.state.sonState.useWeekList.length ){
					    this.state.sonState.useWeekList.map ((item)=>{
					      if(item=="周一"){
					        item='1'
					      }else if(item=='周二'){
					        item='2'
					      }else if(item=='周三'){
					        item='3'
					      }else if(item=='周四'){
					        item='4'
					      }
					      else if(item=='周五'){
					        item='5'
					      }
					      else if(item=='周六'){
					        item='6'
					      }else{
					        item='7'
					      }
					      return weekList.push(item);
					    })
					if( this.state.sonState.arrTimeDate.length ){
						this.state.sonState.arrTimeDate.map ((item)=>{
						    return timeList.push(item[0]+':00,'+item[1]+':00');
					    })
					}
    		    }
		    		var formatData = {
		    			name:values.actName,
		    			act_obj:"从未到店支付宝买单的顾客",
		    			start_time:values.start_time.format('YYYY-MM-DD'),
		    			end_time:values.end_time.format('YYYY-MM-DD'),
		    			voucher_brand_name:sonFormData.voucher_brand_name?sonFormData.voucher_brand_name:this.state.voucher_brand_name,
		    			auto_delay_flag:values.auto_delay_flag?'Y':'N',
		    			quantity:values.quantity,
		    			logo:this.state.sonState.responseId?this.state.sonState.responseId:this.state.responseId,
		    			promo_tools_voucher_type:sonFormData.promo_tools_voucher_type,
		    			worth_value:sonFormData.worth_value,
		    			voucher_name:sonFormData.worth_value+'元代金券',
		    			voucher_note:sonFormData.voucher_note,
		    			validate_type:sonFormData.validate_type,
		    			voucher_relative_time:sonFormData.end_day,
		    			voucher_relative_delay:sonFormData.start_day,
		    			user_min_consume:sonFormData.user_min_consume,
		    			constraint_suit_shops:this.state.sonState.checkedshopIds.length?this.state.sonState.checkedshopIds:this.state.checkedShops,
		    			voucher_suit_shops:this.state.sonState.checkedshopIds.length?this.state.sonState.checkedshopIds:this.state.checkedShops,
		    			use_time_values:weekList,
		    			use_forbidden_day:newArrDate,
		    			use_rule_desc:sonFormData.use_rule_desc,
		    			use_time_values_time:timeList
		    		};
		    		console.log(formatData)
		    		
		    		let that = this;
		     		$.ajax({
			      	type:"POST",
			      	url:AJAX_URL+"/Coupon/ajaxCreateExclusive"+token,
			      	dataType:'json',
			      	data:formatData,
			      	success:function( data ){
			      		that.setState({
				        	createing:false
				        })
			      		if( Number(data.status) ){
				      		Modal.success({
							    title: data.info,
							    onOk() {
							    	// window.location.href = AJAX_URL+'#/manage';
							    	hashHistory.push("manage")
							    }
							  });
			      		}else{
			      			if( data.data.sq ){
				    			Modal.warning({
								    title: data.info,
								    onOk() {
								    	window.location.href = data.data.url;
								    }
								  });
				    		}else{
				    			Modal.warning({'content':data.info});  
				    		}
			      		}
			      	}
			      });
		    		
		    	}
		    });
        //this.props.form.resetFields()
    }
    

    // 显示弹框
    showModal() {
	    this.setState({
	    	...this.state,
	    	visible: true
			})
    }
    // 隐藏弹框
    handleCancel( sonThis ) {
    	
    	
    	let sonData = this.state.sonFormData;
    	if( sonData ){
    		sonThis.props.form.setFieldsValue(sonData);
    	}
    	
	    
    	this.setState({
        	visible: false
    		})
    }
    
    handleCreate = (sonFormData,sonThis,type) => {
    	
	    this.setState({
	    	sonFormData:sonFormData,
	    	son_worth_value:sonFormData.worth_value,
	    	son_user_min_consume:sonFormData.user_min_consume,
	    	sonState:sonThis.state,
	    	visible: false
	    });
	    
	    
//	    qform.validateFields((err, values) => {
//	      if (err) {
//	        return;
//	      }
//	      qform.resetFields();
//	      
//	    });
	    
	  }
    
	  saveFormRef = (form) => {
	    this.qform = form;
	  }
    
    
    disabledStartDate(current) {
			return (current && (current.valueOf()+86400000) <= moment(new Date(), 'YYYY-MM-DD'))
		}
    disabledEndDate(endValue,ddd){
	    const start_time = this.state.start_time;
	    
	    return endValue.valueOf() <= start_time.valueOf();
		}
    
    onStartChange(dates, value) {
    	var start_time = moment(new Date(value), 'YYYY-MM-DD');
	    var end_time = moment(this.props.form.getFieldValue('end_time'), 'YYYY-MM-DD');
	    
			this.setState({
	    	...this.setState,
	    	start_time: start_time
	    });
	    
    	var that = this;
    	this.setState({
				...this.setState,
				end_tip:'活动结束时间须大于等于开始时间'
			})
	    	setTimeout(function(){
				that.props.form.validateFields(['end_time'], { force: true });
    	},100)
		}
    onEndChange(dates, value) {
    	this.setState({
			...this.setState,
			end_tip:null
		})
	}
    validEndTime = (rule, value, callback) => {
    	var start_time = moment(this.state.start_time, 'YYYY-MM-DD');
    	var end_time = moment(new Date(value), 'YYYY-MM-DD');
    	if( end_time ){
    		if( start_time > end_time ){
    			var that = this;
				callback('');
	      		return;
		    }
    	}else{
    		this.setState({
				...this.setState,
				end_tip:null
			})
    		callback('');
      		return;
    	}
	    callback();
	}

    render() {
    	
        const { getFieldDecorator } = this.props.form
        const formItemLayout = {
            labelCol: { span: 3 },
            wrapperCol: { span: 6 }
        }
        return (
        	<Spin spinning={this.state.createing} size="large">
        	<p className="ctitle">场景营销-拉新 活动设置</p>
            <Form className="pullform" onSubmit={this.handleSubmit.bind(this)}>
                <FormItem
                    id="control-input"
                    label="活动名称"
                    {...formItemLayout}
                    required>
                    
                    {getFieldDecorator('actName', {
						            rules: [{ required: true, message: '请输入活动名称',whitespace: true }],
						        })(
						            <Input id="control-input" placeholder="招募新会员" />
						        )}
                </FormItem>

								<FormItem
                    id="control-input"
                    label="活动对象"
                    {...formItemLayout}
                    required>
                    从未到店支付宝买单的顾客
                </FormItem>

                <FormItem
                    label="活动时间"
                    labelCol={{ span: 3 }}
                    required>
                    <Col span="4">
                        <FormItem>
								        	{getFieldDecorator('start_time', {
								        		rules: [{required: true, message: '请选择开始时间' }],
								        	})(
								        		<DatePicker allowClear={false} showToday={false} disabledDate={this.disabledStartDate} onChange={this.onStartChange.bind(this)}/>
								        	)}
								        </FormItem>
                    </Col>
                    <Col span="1" className="ant-form-splitCol">
                        <p className="ant-form-split">至</p>
                    </Col>
                    <Col span="4">
                        <FormItem>
								        	{getFieldDecorator('end_time', {
								        		rules: [{required: true, message: this.state.end_tip||'请选择结束时间', validator: this.validEndTime }]
								        	})(
								        		<DatePicker allowClear={false} showToday={false} disabledDate={this.disabledEndDate.bind(this)} onChange={this.onEndChange.bind(this)}/>
								        	)}
								        </FormItem>
                    </Col>
                    <Col span="4">
                    	<FormItem className="auto_delay_flag">
		                    	{getFieldDecorator('auto_delay_flag', {
									            valuePropName: 'checked',
									            initialValue: true,
									        })(
									            <Checkbox>自动续期</Checkbox>
									        )}
	                    		<Tooltip placement="bottom" title="活动到期前3天自动延期一个月，最长一年">
													    <span><Icon className="question-circle" type="question-circle" /></span>
													</Tooltip>
	                    	</FormItem>
			                    	
	                    </Col>
	                </FormItem>
                
                <FormItem
                    id="control-input"
                    label="送券规则"
                    {...formItemLayout}
                    required>
                    <div className="gz_box">
                    	<span>从未到店支付宝买单的顾客，可领取：</span>
                    	<div className="coupon-wrapper">
                    		<div className="coupon" onClick={this.showModal.bind(this)} >{this.state.son_worth_value}元代金券，满{this.state.son_user_min_consume}元可用</div>
                    		<span className="change-coupon" onClick={this.showModal.bind(this)}>修改</span>
                    	</div>
                    </div>
                    
                    <CollectionForm
			          ref={this.saveFormRef}
			          visible={this.state.visible}
			          key={this.state.modal.key}
			          onCancel={this.handleCancel.bind(this)}
			          onCreate={this.handleCreate.bind(this)}
			          sonFormData = {this.state.sonFormData}
			          responseId={this.state.responseId}
			          logoUrl={this.state.logoUrl}
			          voucher_brand_name={this.state.voucher_brand_name}
			        />
                    
                </FormItem>
                
                <FormItem
                    id="control-input"
                    label="参与限制"
                    {...formItemLayout}
                    required>
                    每人可参加 1 次
                    <Tooltip placement="bottom" title="限制每人在活动期间可参与的次数">
									    <span><Icon className="question-circle join_tip" type="question-circle" /></span>
										</Tooltip>
                </FormItem>
                
                <FormItem
                    id="control-input"
                    label="发券总数"
                    {...formItemLayout}
                    required>
                    
                    {getFieldDecorator('quantity', {
						            rules: [{ required: true, message: '请输入正确的发券数量' }],
						        })(
						            <InputNumber min={1} max={999999} className="quan_num" id="control-input" placeholder="请输入发券数量" />
						        )}
                    	张
                </FormItem>
                
                <FormItem wrapperCol={{ span: 9, offset: 5 }} className="create_btns" style={{ marginTop: 50 }}>
                    <Button type="primary" htmlType="submit" >创建</Button>
                    &nbsp;&nbsp;&nbsp;
                    <Link to="/">
                    	<Button type="ghost">取消</Button>
                    </Link>
                    
                </FormItem>
                
                
            </Form>
       		</Spin>
        )
    }
}

myForm = Form.create()(myForm)

export default myForm;
