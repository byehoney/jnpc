import React from 'react';
import { Form, Input, Select,InputNumber,Spin, Col,Icon, Upload, Button, Modal, message} from 'antd';
import DisabledDate from './DisabledDate';
import UseTime from './useTime.js';
import CreateModalSelectShopForm from './ShopCheck.js';


const FormItem = Form.Item;
const Option = Select.Option;


var ModalForm = React.createClass({
	componentWillReceiveProps(newProps){
		console.log('componentWillReceiveProps')
		this.setState({
			responseId:newProps.responseId,
			voucher_brand_name:newProps.voucher_brand_name
		})
		
		if( !this.state.imageUrl ){
			this.setState({
				imageUrl:newProps.logoUrl
			})
		}
		
//		this.props.form.setFieldsValue({
//	    	voucher_brand_name: newProps.voucher_brand_name
//	    });
	},
	getInitialState: function () {
    	return {
    		imageUrl:'',
    		responseId:'',
    		voucher_brand_name:'',
    		/*DisabledDate*/
	    	disdate_visible:false,
	    	arrDate:[],
	  		disabledType:'0',
	  		time_txt:'',
	  		/*DisabledDate*/
	  		
	  		/*UseTime*/
	  		useTimeVisible:false,//设置可用时间的Modal
	    	useWeekList:[],
	    	arrTimeDate:[],
	    	timeKey:new Date().getTime(),
	    	user_time_txt:'',
	    	useTimeType:'0',
	    	/*UseTime*/
	    	
	    	mform:{
		      	start_day:2,
		      	end_day_tip:null,
		      	imageUrl:null
	    	},
	      	/*Shop*/
	    	shopkey:new Date().getTime(),
	    	shop_visible:false,
		  	checkedshopIds:[]
	    };
	},
	getBase64(img, callback){
	    const reader = new FileReader();
	    reader.addEventListener('load', () => callback(reader.result));
	    reader.readAsDataURL(img);
  	},
	beforeUpload(file){
      	this.setState({
          	uploading:true
      	})
	    const isJPG = (file.type === 'image/png'||'image/jpg'||'image/jpeg');
	    if (!isJPG) {
	      message.error('只能上传png,jpg,jpeg格式的图片!');
	    }
	    console.log(file.size)
	    const isLt2M = file.size / 1024 / 1024 < 2;
	    if (!isLt2M) {
	      message.error('图片大小必须小于2M!');
	    }
	    return isJPG && isLt2M;
 	},
 	    // 上传图片
	handleImgChange(info){
		
		console.log(info)
	    if (info.file.status === 'done') {
	      // Get this url from response in real world.
	        
	        if ( Number(info.file.response.status)) {
		      // Get this url from response in real world.
		      	this.getBase64(info.file.originFileObj, imageUrl => this.setState({ imageUrl }));
		        this.setState({
		            uploading:false,
		            responseId:info.file.response.data.image_id
		        })
		    }else {
		    		if( info.file.response.sq ){
		    			
		    			Modal.warning({
						    title: info.file.response.info,
						    onOk() {
						    	window.location.href = info.file.response.url;
						    }
						  });
		    		}else{
		    			Modal.warning({'content':info.file.response.info});  
		    		}
		    }
	        
	    }else if(info.file.status === 'error'&& info.file.percent === 100){
	        Modal.warning({'content':'上传失败,请重新上传'});  
	        this.setState({
	            uploading:false
	        })
	    }
	},
	normFile(e) {
    if (Array.isArray(e)) {
      return e;
    }
    return e && e.fileList;
  },
  
  /*Shop*/
  // 显示弹框
	onShopOk(x) {
			this.setState({
	    	shopkey:new Date().getTime(),
	    	shop_visible: false,
	    	checkedshopIds:x
			})
	},
	onFirstLoadShop( firstData ){
		this.setState({
    	checkedshopIds:firstData
		})
	},
	// 隐藏弹框
	onShopCancel( shopThis ) {
		this.setState({
    	shopkey:new Date().getTime(),
    	shop_visible: false
		})
		shopThis.setState({
			checkedShops:this.state.checkedshopIds
		})
	},
	selectShop:function() {
  	this.setState({
  		...this.state,
  		shopkey:new Date().getTime(),
  		shop_visible:true
  	})
  },
	
	/*Shop*/
	
	changeStartDay:function(value){
    var end_day = this.props.form.getFieldValue('end_day');
    
		this.setState({
			...this.state,
			mform:{
				...this.state.mform,
      	start_day:Number(value)+1,
      	end_day_tip:'活动结束时间须大于等于开始时间'
      }
		})
			var that = this;
			setTimeout(function(){
				that.props.form.validateFields(['end_day'], { force: true });
    	},100)
	},
	validEndDay:function(rule, value, callback) {
		var start_day = Number(this.state.mform.start_day);
		
		var end_day = Number(value);
		if( end_day ){
			if( start_day > end_day ){
				var that = this;
				callback('');
  			return;
	    }
		}else{
			this.setState({
				...this.state,
				mform:{
					...this.state.mform,
					end_day_tip:null
				}
			})
			callback('');
	  		return;
		}
    	callback();
	},
	onEndDayChange:function(dates, value) {
		this.setState({
			...this.state,
			mform:{
				...this.state.mform,
				end_day_tip:null
			}
		})
  },  	
 
  
  /*DisabledDate*/
  openDisDate:function(){
  	this.setState({
  		disdate_visible:true
  	})
  },
  onDateCancel:function(dateThis){
  	this.setState({
  		disdate_visible:false
  	})
  	dateThis.setState({
  		arrDate:this.state.arrDate,
  		disabledType:this.state.disabledType
  	})
  	var arrNum = [1];
    if( this.state.arrDate.length ){
    	arrNum = [];
    	for( let l=0;l<this.state.arrDate.length;l++ ){
    		arrNum.push(l+1);
    	}
    }
  	dateThis.props.form.setFieldsValue({
      keys: arrNum
    });
  },
  onDateOk:function(arrDate,disabledType){
  	var time_txt = [];
  	for( let i=0; i<arrDate.length;i++ ){
  		time_txt.push(arrDate[i].join('至'))
  	}
  	this.setState({
  		disdate_visible:false,
  		arrDate,
  		time_txt:time_txt.join(','),
  		disabledType
  	})
  },
	/*DisabledDate*/
  /*UseTime*/
  
  showUseTimeModal (){
    this.setState({
      useTimeVisible:true
    })
  },
  handleUseTimeOk (arrTimeDate,useTimeType,checkedList) {
  	var time_txt = [];
  	if( useTimeType == '1' && arrTimeDate.length ){
	  	for( let i=0; i<arrTimeDate.length;i++ ){
	  		time_txt.push(arrTimeDate[i].join('-'))
	  	}
	  	time_txt = '的 '+time_txt.join(',');
  	}else{
  		time_txt = '的全天'
  	}
  	this.setState({
  		useTimeVisible:false,
      useWeekList:checkedList,
  		arrTimeDate,
  		useTimeType,
  		user_time_txt:time_txt
  	})
  },
  handleUseTimeCancel(checkedList,timethis) {
  	
    this.setState({
      useTimeVisible: false,
      arrTimeDate:this.state.arrTimeDate,
      useWeekList:checkedList
    });
    
    
    
    timethis.setState({
			arrTimeDate:this.state.arrTimeDate,
			useTimeType:this.state.useTimeType
		})
  	
  	var arrNum = [1];
    if( this.state.arrTimeDate.length ){
    	arrNum = [];
    	for( let l=0;l<this.state.arrTimeDate.length;l++ ){
    		arrNum.push(l+1);
    	}
    }
    
  	timethis.props.form.setFieldsValue({
      keys: arrNum
    });
  },
  
  /*UseTime*/
	submitSonForm:function(){
		if(this.state.uploading){
			return false;
		}
		this.props.form.validateFields((err, values) => {
	    	if (!err) {
	    		
	    		this.props.onCreate(values,this);
	    		
	    	}
	    });
	},
	onSonCancel(){
		if(this.state.uploading){
			return false;
		}
		this.props.onCancel( this );
	},
	onFirstLoad(){
		this.props.onCreate( this.props.form.getFieldsValue(),this );
	},
	componentDidMount() {
    	this.onFirstLoad()
	    
   },
 
  render: function () {
    // 从【父组件】获取的值
    var props = this.props;
    
    const imageUrl = this.state.imageUrl;
  	const { visible, onCancel, onCreate, form, key } = props;
    const { getFieldDecorator } = form;
    const maskClosable = false;
    const formItemLayout = {
      labelCol: { span: 5 },
      wrapperCol: { span: 10 }
    }
    return (
        <Modal
        	className="modalForm"
	        visible={visible}
	        title="券设置"
	        okText="确定"
	        cancelText="取消"
	        onCancel={this.onSonCancel}
	        onOk={this.submitSonForm}
	        maskClosable={maskClosable}
	        key={key}
	      >
        	<Form className="pullnewform">
	          <FormItem 
	          	{...formItemLayout}
	          	required
	          	label="券可用范围">
		            {getFieldDecorator('voucherRange', {
		            	initialValue:"1",
			            rules: [
			              { required: true, message: '请选择使用范围' },
			            ],
			          })(
			            <Select placeholder="请选择使用范围">
			              <Option value="1">全场通用</Option>
			            </Select>
			          )}
	          </FormItem>
	          <FormItem 
	          	{...formItemLayout}
	          	required
	          	label="券类型">
		            {getFieldDecorator('promo_tools_voucher_type', {
		            	initialValue:"1",
			            rules: [
			              { required: true, message: '请选择券类型' },
			            ],
			          })(
			            <Select placeholder="请选择券类型">
			              <Option value="1">代金券</Option>
			            </Select>
			          )}
	          </FormItem>
	          <FormItem
	            label="券面额"
	            labelCol={{ span: 5 }}
          		wrapperCol={{ span: 16 }}
	            extra="提示：为保障活动效果，建议填写>笔单价*10%的值"
	            required>
	            
	            {getFieldDecorator('worth_value', {
	            			initialValue:1,
				            rules: [{pattern:/^(?!0+(?:\.0+)?$)(?:[1-9]\d*|0)(?:\.\d{1,2})?$/, required: true, message: '请输入正确的券面额' }],
				        })(
				            <InputNumber min={0.01} max={999} style={{width:80}} className="quan_num" id="control-input" placeholder="请输入券面额" />
				        )}
	        			元
	        	</FormItem>
	        	
	        	<FormItem
	            label="消费门槛"
	            labelCol={{ span: 5 }}
          		wrapperCol={{ span: 16 }}
	            extra="提示：为保障活动效果，建议填写≤笔单价*0.8的值"
	            required>
	        	
	            消费满
	            {getFieldDecorator('user_min_consume', {
	            			initialValue:1,
				            rules: [{pattern:/^(?!0+(?:\.0+)?$)(?:[1-9]\d*|0)(?:\.\d{1,2})?$/, required: true, message: '请输入正确的消费门槛' }],
				        })(
				            <InputNumber min={0.01} max={99999999} style={{width:80,marginLeft:10}} className="quan_num" id="control-input" placeholder="请输入消费门槛" />
				        )}
	        			元可用
	        	</FormItem>
	        	
	        	<FormItem 
	          	{...formItemLayout}
	          	required
	          	label="券有效期">
		            {getFieldDecorator('validate_type', {
		            	initialValue:"2",
			            rules: [
			              { required: true, message: '请选择券有效期' },
			            ],
			          })(
			            <Select placeholder="请选择券有效期">
			              <Option value="2">相对日期</Option>
			            </Select>
			          )}
	          </FormItem>
	          
	        	
	        	
	        	<FormItem
	            label=" "
	            labelCol={{ span: 5 }}
          		wrapperCol={{ span: 19 }}
          		required={false}
          		colon={false}
          		>
	            <Col span="8" style={{marginRight:-10}}>
	                <FormItem>
	                	从发券第
					        	{getFieldDecorator('start_day', {
			            			initialValue:1,
						            rules: [{pattern:/^\d+$/, required: true, message: '请填写正确的起始天数' }]
						        })(
						            <InputNumber min={1} max={99} style={{width:50,marginLeft:10}} onChange={this.changeStartDay} className="quan_num" placeholder="" />
						        )}
					        </FormItem>
	            </Col>
	            <Col span="2" className="ant-form-splitCol">
	                <p className="ant-form-split">天至</p>
	            </Col>
	            <Col span="9">
	                <FormItem>
					        	{getFieldDecorator('end_day', {
					        		initialValue:30,
					            rules: [{pattern:/^\d+$/, required: true, message: this.state.mform.end_day_tip||'请填写正确的到期天数', validator: this.validEndDay }]
						        })(
						            <InputNumber min={this.state.mform.start_day} max={365} onChange={this.onEndDayChange} style={{width:50,marginLeft:8}} className="quan_num" placeholder="" />
						        )}
					        	天
					        </FormItem>
	            </Col>
            </FormItem>
	        	
	        	<FormItem
	            label="核销门店"
	            labelCol={{ span: 5 }}
          		wrapperCol={{ span: 16 }}
	            required>
	            
	            <span className="select-shop" onClick={this.selectShop}>修改</span>
	            <span className="select-shop-info">已选  {this.state.checkedshopIds.length} 家门店</span>
	            <CreateModalSelectShopForm shopkey={this.state.shopkey}  onCancel={this.onShopCancel}   checkedshopIds={this.state.checkedshopIds} onFirstLoadShop={this.onFirstLoadShop} onOk={this.onShopOk} shop_visible={this.state.shop_visible}/>
	        	</FormItem>
	        	
	        	<FormItem
	            label="参与品牌"
	            {...formItemLayout}
	            required>
	            
	            {getFieldDecorator('voucher_brand_name', {
	            		initialValue:this.state.voucher_brand_name,
			            rules: [{ required: true, message: '请输入参与品牌',whitespace: true }],
			        })(
			            <Input id="control-input" placeholder="请输入参与品牌" />
			        )}
		        </FormItem>
	        	
		        
		        <FormItem
		          {...formItemLayout}
		          label="品牌logo"
		          extra="建议：不超过500kb。格式：png，jpg。建议尺寸150px＊150px"
		          className="upLoad_img"
		        >
		          {getFieldDecorator('logo', {
		            valuePropName: 'fileList',
		            getValueFromEvent: this.normFile
		          })(
		            <Upload
		            className="avatar-uploader"
		            name="logo"
		            showUploadList={false}
//		            header("Access-Control-Allow-Origin:*");
		            action={AJAX_URL+"/Coupon/ajaxUploadImages"+token}
//		            action="//jsonplaceholder.typicode.com/posts/"
		            beforeUpload={this.beforeUpload}
		            onChange={this.handleImgChange}
		          >
		            {
		              this.state.imageUrl ?<img src={this.state.imageUrl} alt="" className="avatar" /> :<Icon type="plus" className="avatar-uploader-trigger" />
		            }
		            {this.state.uploading ?<Spin className="upState" />:""}
		          </Upload>
		          )}
		        </FormItem>
		        
            <FormItem
                label="券可用时间"
                labelCol={{ span: 5 }}
                wrapperCol={{ span: 18 }}
                required
              >
               <Col span="6" onClick={this.showUseTimeModal} style={{marginRight:5,color:'#2db7f5',cursor:'pointer'}}>选择券可用时间</Col>
               <Col span="14" >{this.state.useWeekList.length?`${this.state.useWeekList+this.state.user_time_txt}`:"未选择"}</Col>
            </FormItem>
            <UseTime useWeekList={this.state.useWeekList}  visible={this.state.useTimeVisible} timeKey={this.state.timeKey} handleUseTimeOk={this.handleUseTimeOk} handleUseTimeCancel={this.handleUseTimeCancel} arrTimeDate={this.state.arrTimeDate} useTimeType={this.state.useTimeType}/>

		        
		        
	        	
	        	<FormItem
	            label="券不可用日期"
	            labelCol={{ span: 5 }}
          		wrapperCol={{ span: 16 }}
	            required>
	            
	            <span className="select-shop" onClick={this.openDisDate}>选择券不可用日期</span>
	            <p style={{marginTop: '-5px'}}>{this.state.disabledType=='0'?'不限制':this.state.time_txt}</p>
	            <DisabledDate disdate_visible={this.state.disdate_visible} onDateCancel={this.onDateCancel} onDateOk={this.onDateOk} arrDate={this.state.arrDate} disabledType={this.state.disabledType}/>
	        	</FormItem>
	        	
	          <FormItem
	          	labelCol={{ span: 5 }}
          		wrapperCol={{ span: 16 }}
	          	extra="备注内容仅在收银端可见"
	          	label="券备注">
	            {getFieldDecorator('voucher_note',{
	            	initialValue:''
	            })(<Input type="textarea" style={{height:80}}/>)}
	          </FormItem>
	          
	          <FormItem
	          	labelCol={{ span: 5 }}
          		wrapperCol={{ span: 16 }}
	          	label="券说明">
	            {getFieldDecorator('use_rule_desc',{
	            	initialValue:'下次到店消费可用，每次限用1张，不与店内其他优惠同享'
	            })(<Input type="textarea" style={{height:80}}/>)}
	          </FormItem>
	        </Form>
        
        </Modal>
    );
  }
});
const CollectionForm = Form.create()(ModalForm);


export default CollectionForm;