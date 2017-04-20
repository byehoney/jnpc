import React from 'react';
import { Link,browserHistory  } from 'react-router';
import { Form, Input,InputNumber,Spin, Switch,Slider, Select,Upload, Checkbox, DatePicker,Row, Col, Radio, Button, Modal, message, Tooltip,Icon } from 'antd';
import { Router, Route,hashHistory} from 'react-router';
import moment from 'moment';
import UseTime from './useTime.js';
import Phone from './phone.js';
import CreateModalSelectShopForm from './ShopCheck.js';
import UseShopCheck from './UseShopCheck.js';
import DisabledDate from './DisabledDate';
const FormItem = Form.Item;
const Option = Select.Option;
const RadioButton = Radio.Button;
const RadioGroup = Radio.Group;

function getBase64(img, callback) {
  const reader = new FileReader();
  reader.addEventListener('load', () => callback(reader.result));
  reader.readAsDataURL(img);
}
class myForm extends React.Component {
  constructor(props) {
        super(props)
        this.state = {
        		createing:false,
            imageUrl: '',
            uploading:false,//判断是否在图片上传中
            start_time:moment(new Date(), 'YYYY-MM-DD'),
            start_date:moment(new Date(), 'YYYY-MM-DD'),
            end_tip:null,
            cType:'代金券',//券的类型
            cName:'',//兑换券名称
            cMoney:1,//代金券的面额
            couponShow:'block',//代金券的显示
            coinShow:'none',//兑换券的显示
            pMoney:0,//消费满多少元可用代金券
            startDay:1,
            endDay:30,
            dateType:'相对日期',
            startDate:'',
            endDate:'',
            dayShow:'block',
            dateShow:'none',
            start_day:1,
            end_day_tip:null,
            end_date_tip:null,
            pay_tip:null,
            moreSet:'none',
            setMoreText:"点击进行高级设置",
            visible:false,//设置使用店铺的Modal
            hxVisible:false,//设置核销店铺的Modal
            
            
            voucher_brand_name:'',
			    	responseId:'',
			    	logoUrl:'',

             /*UseTime*/
			  		useTimeVisible:false,//设置可用时间的Modal
			    	useWeekList:[],
			    	arrTimeDate:[],
			    	timeKey:new Date().getTime(),
			    	user_time_txt:'',
			    	useTimeType:'0',
			    	 /*UseTime*/
			    	
			    	/*DisabledDate*/
			    	disdate_visible:false,
			    	arrDate:[],
			  		disabledType:'0',
			  		time_txt:'',
			  		/*DisabledDate*/
			  		
			  		/*Shop*/
			    	shopkey:new Date().getTime(),
			    	shop_visible:false,
				  	checkedshopIds:[],
				  	/*useShop*/
			    	useShopkey:new Date().getTime(),
			    	use_shop_visible:false,
				  	useCheckedshopIds:[]
			  		
        }
  }
  componentDidMount (){
    window.scrollTo(0,0)
    this.props.form.setFieldsValue({
        couponType:"1",
        couponDateType:'1',
        couponCash:1,
        payNeed:0,
        actName:'消费送',
        couponName:'1元代金券',
        start_time:moment(new Date(), 'YYYY-MM-DD'),
        end_time:moment(new Date(new Date().getTime() + 86400000*7), 'YYYY-MM-DD'),
        startDate:moment(new Date(), 'YYYY-MM-DD'),
        endDate:moment(new Date(new Date().getTime() + 86400000*7), 'YYYY-MM-DD'),
        des:'下次到店消费可用，每次限用1张，不与店内其他优惠同享'
    })
  }
  // 验证时间是否合格
  disabledStartDate(current) {
    return (current && (current.valueOf()+86400000) <= moment(new Date(), 'YYYY-MM-DD'))
  }
  disabledEndDate(endValue){
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
  // 券切换过程的状态控制
  handleCouponChange (value){
    if(value=='1'){
      this.setState({
        couponShow:'block',
        coinShow:'none',
        cType:'代金券'
      })
    }else{
       this.setState({
        couponShow:'none',
        coinShow:'block',
        cType:'兑换券'
      })
    }
  }
  //兑换券名字
  handleCoinChange = (e) =>{
    this.setState({
      cName:e.target.value
    })
  }
  //券有效期状态切换控制
  handleCouponDateChagne (value){
    if(value=='1'){
      this.setState({
        dayShow:'block',
        dateShow:'none',
        dateType:'相对日期'
      })
    }else{
      this.setState({
        dayShow:'none',
        dateShow:'block',
        dateType:'固定日期'
      })
    }
  }
 //相对时间验证
  changeStartDay (value){
    var endDay=this.props.form.getFieldValue("endDay");
    var that = this;
    if(value){
      if(value>=endDay){
        this.setState({
          end_day_tip:"活动结束时间需大于开始时间"
        })
      }else{
        this.setState({
          startDay:value
        })
      }
    }else{
      this.setState({
          end_day_tip:""
      })
    }
    setTimeout(function(){
      that.props.form.validateFields(['endDay'], { force: true });
    },100)
  }
  validEndDay (rule, value, callback) {
    var startDay = this.props.form.getFieldValue("startDay");
    var endDay = Number(value);
    if( endDay ){
      if( startDay >= endDay ){
        var that = this;
        callback('结束时间须大于开始时间');
        return;
      }
    }else{
      this.setState({
          end_day_tip:null
      })
      callback('');
      return;
    }
    callback();
  }
  onEndDayChange(value) {
    var startDay = this.props.form.getFieldValue("startDay");
    var that=this;
    if(value){
      if(value<=startDay){
        this.setState({
          end_day_tip:'结束时间须大于开始时间'
        })
      }else{
        this.setState({
          end_day_tip:null,
          endDay:value
        })
      }
    setTimeout(function(){
      that.props.form.validateFields(['endDay'], { force: true });
    },100)
    }else{
      this.setState({
          end_day_tip:null
      })
    }
  }  
  //固定时间验证
  disabledStartDate(current) {
    return (current && (current.valueOf()+86400000) <= moment(new Date(), 'YYYY-MM-DD'))
  }
  disabledEndDate(endValue){
      const start_date = this.state.start_date;
      return endValue.valueOf() <= start_date.valueOf();
  }
  startDateChange(dates, value) {
      var start_date = moment(new Date(value), 'YYYY-MM-DD');
      var end_date = moment(this.props.form.getFieldValue('endDate'), 'YYYY-MM-DD');
      
      this.setState({
        ...this.setState,
        start_date: start_date,
        startDate:start_date
      });
      
        var that = this;
        this.setState({
        ...this.setState,
        end_date_tip:'活动结束时间须大于等于开始时间'
      })
        setTimeout(function(){
        that.props.form.validateFields(['endDate'], { force: true });
        },100)
  }
  endDateChange(dates, value) {
      var end_date=moment(new Date(value), 'YYYY-MM-DD');
      this.setState({
        endDate:end_date,
      ...this.setState,
      end_date_tip:null
    })
  }
  validEndDate = (rule, value, callback) => {
    var start_date = moment(this.state.start_date, 'YYYY-MM-DD');
    var end_date = moment(new Date(value), 'YYYY-MM-DD');
    if( end_date ){
      if( start_date > end_date ){
        var that = this;
      callback('');
          return;
      }
    }else{
      this.setState({
      ...this.setState,
      end_date_tip:null
    })
      callback('');
        return;
    }
    callback();
  }
  // 验证输入的券面额是否合格
  handleCouponCashChange = (value) =>{
    this.setState({
      cMoney:value
    })
    this.props.form.setFieldsValue({
    	couponName:value+'元代金券'
    })
  }
  //验证输入的消费门槛时候合格
  payNeedChange = (value) =>{
    this.setState({
      pMoney:value
    })
  }
  //最多获得券数量的验证
  maxGetNum =(rule, value, callback) =>{
    const perNum = this.props.form.getFieldValue('num')
    if(perNum){
      if(value<perNum){
        callback('最多券数量不能小于单位券数量')
      }
      if(!this.props.form.getFieldValue('getNum')){
        callback('输入最多获券数量')
      }
    }else{
      callback('输入最多获券数量')
    }
    callback()
  }
  getNumChange (value){
    var that=this;
    if(!value){
      setTimeout(function(){
        that.props.form.validateFields(['getNum'], { force: true });
      },100)
    }
  }
  //控制更多设置的显示隐藏
  handleMoreSet (event){
    if(this.state.moreSet=="none"){
      this.setState({
        moreSet:"block",
        setMoreText:"收起高级设置"
      })
    }else{
      this.setState({
        moreSet:'none',
        setMoreText:'点击进行高级设置'
      })
    }
  }


    // 上传图片
  handleImgChange = (info) => {
  	console.log( info.file )
  	if (info.file.status === 'done') {
	    if ( Number(info.file.response.status)) {
	      // Get this url from response in real world.
	      getBase64(info.file.originFileObj, imageUrl => this.setState({ imageUrl }));
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
  }

  getBase64 =(img, callback) =>{
    const reader = new FileReader();
    reader.addEventListener('load', () => callback(reader.result));
    reader.readAsDataURL(img);
  }

  beforeUpload= (file) =>{
      this.setState({
          uploading:true
      })
    const isJPG = (file.type === 'image/png'||'image/jpg'||'image/jpeg');
    if (!isJPG) {
      message.error('只能上传png,jpg,jpeg格式的图片!');
    }
    const isLt2M = file.size / 1024 / 1024 < 2;
    if (!isLt2M) {
      message.error('图片大小必须小于2M!');
    }
    return isJPG && isLt2M;
  }
  handleSubmit = (e) => {
    e.preventDefault();
    this.props.form.validateFields({},(err, values) => {
      if (!err) {
        if(this.state.cType=="代金券"){
          var promo_tools_voucher_type="1";
          var worth_value=values.couponCash;
          var voucher_name=values.couponName;
          var user_min_consume=values.payNeed;
        }else{
          var promo_tools_voucher_type="2";
          var worth_value="";
          var voucher_name=values.coinName;
          var user_min_consume='';
        }
        if(this.state.dateType=="相对日期"){
          var validate_type="2";
          var voucher_relative_delay=values.startDay;
          var voucher_relative_time=values.endDay;
          var voucher_start_time="";
          var voucher_end_time="";
        }else{
          var validate_type="1";
          var voucher_start_time=moment(values.startDate).format('YYYY-MM-DD');
          var voucher_end_time=moment(values.endDate).format('YYYY-MM-DD');
          var voucher_relative_delay="";
          var voucher_relative_time="";
        }
        if(values.divide=="1"){
          var user_win_frequency_date="W";
        }else if(values.divide=='2'){
          var user_win_frequency_date="D";
        }else{
          var user_win_frequency_date="M";
        }
        if(values.des){
          var use_rule_desc=values.des;
        }else{
          var use_rule_desc="";
        }
        var arrDate = this.state.arrDate;
	    		if( arrDate.length ){
		    		arrDate.map((val,key)=>{
		    			return val.join(',');
		    		})
	    		}
	    		
    		var arrDate = this.state.arrDate;
    		var newArrDate = [];
    		if( arrDate.length ){
    			for( let x=0;x<arrDate.length;x++ ){
    				newArrDate.push( arrDate[x].join(',') )
    			}
    		}
        let weekList=[];
        let timeList=[];
		    if( this.state.useWeekList.length ){
				    this.state.useWeekList.map ((item)=>{
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
				  if( this.state.arrTimeDate.length ){
						this.state.arrTimeDate.map ((item)=>{
					    return timeList.push(item[0]+':00,'+item[1]+':00');
				    })
					}
		    }
		    console.log(' values.couponNote ')
		    console.log( values.couponNote )
        
        var formData={
          act_obj:'所有在支付宝口碑消费过的人群',
          name:values.actName,
          start_time:moment(values.start_time).format('YYYY-MM-DD'),
          end_time:moment(values.end_time).format('YYYY-MM-DD'),
          voucher_brand_name:values.actBrand,
          logo:this.state.responseId,
          promo_tools_voucher_type:promo_tools_voucher_type,
          worth_value:worth_value,
          voucher_name:voucher_name,
          voucher_note:values.couponNote,
          validate_type:validate_type,
          voucher_start_time:voucher_start_time,
          voucher_end_time:voucher_end_time,
          voucher_relative_delay:voucher_relative_delay,
          voucher_relative_time:voucher_relative_time,
          user_win_frequency_date:user_win_frequency_date,
          user_win_frequency_num:values.num,
          user_win_count:values.getNum,
          user_min_consume:user_min_consume,
          constraint_suit_shops:this.state.checkedshopIds,//核销门店
             voucher_suit_shops:this.state.useCheckedshopIds,//适用门店
          use_time_values:weekList,
          use_forbidden_day:newArrDate,
          use_rule_desc:use_rule_desc,
          use_time_values_time:timeList
        }
        console.log(formData)
        
        let that = this;
        this.setState({
        	createing:true
        })
        $.ajax({
          type:'POST',
          dataType:'json',
          url:AJAX_URL+'/Coupon/ajaxCreateSend'+token,
          data:formData,
          success:function(data){
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
        })
      }
    });
  }
  normFile = (e) => {
    if (Array.isArray(e)) {
      return e;
    }
    return e && e.fileList;
  }
  
    /*useShop*/
 onUseShopOk=(x)=> {
			this.setState({
	    	useShopkey:new Date().getTime(),
	    	use_shop_visible: false,
	    	useCheckedshopIds:x
			})
	}
	onUseFirstLoadShop=( firstData )=>{
		this.setState({
    	useCheckedshopIds:firstData
		})
	}
	// 隐藏弹框
	onUseShopCancel=( shopThis )=> {
		this.setState({
    	useShopkey:new Date().getTime(),
    	use_shop_visible: false
		})
		shopThis.setState({
			checkedShops:this.state.useCheckedshopIds
		})
	}
	useSelectShop=()=> {
  	this.setState({
  		useShopkey:new Date().getTime(),
  		use_shop_visible:true
  	})
  }
  
  
  
  /*shop*/
 onShopOk=(x)=> {
			this.setState({
	    	shopkey:new Date().getTime(),
	    	shop_visible: false,
	    	checkedshopIds:x
			})
	}
	onFirstLoadShop=( firstData,shopData )=>{
		this.setState({
    	checkedshopIds:firstData,
    	voucher_brand_name:shopData.shoplist[0].main_shop_name,
    	responseId:shopData.logo.id,
    	imageUrl:shopData.logo.url
		})
	}
	// 隐藏弹框
	onShopCancel=( shopThis )=> {
		this.setState({
    	shopkey:new Date().getTime(),
    	shop_visible: false
		})
		shopThis.setState({
			checkedShops:this.state.checkedshopIds
		})
	}
	selectShop=()=> {
  	this.setState({
  		...this.state,
  		shopkey:new Date().getTime(),
  		shop_visible:true
  	})
  }
	  /*DisabledDate*/
  openDisDate=()=>{
  	this.setState({
  		disdate_visible:true
  	})
  }
  onDateCancel=(dateThis)=>{
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
  }
  onDateOk=(arrDate,disabledType)=>{
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
  }
	/*DisabledDate*/
  /*UseTime*/
  
  showUseTimeModal (){
    this.setState({
      useTimeVisible:true
    })
  }
  handleUseTimeOk =(arrTimeDate,useTimeType,checkedList)=> {
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
  }
  handleUseTimeCancel=(checkedList,timethis)=> {
  	
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
  }
  
  /*UseTime*/

  render() {
    const { getFieldDecorator } = this.props.form;
    const formItemLayout = {
      labelCol: { span: 3 },
      wrapperCol: { span: 8 },
    };
    const rangeConfig = {
      rules: [{ type: 'array', required: true, message: '请选择活动时间范围',whitespace:true}],
    };
    const RangePicker = DatePicker.RangePicker;
    const imageUrl = this.state.imageUrl;
    return (
      <div>
      <Spin spinning={this.state.createing} size="large">
      <p className="ctitle" style={{paddingLeft:'30px',marginBottom:20}}>场景营销-消费送 活动设置</p>
      <Phone cType={this.state.cType} imageUrl={this.state.imageUrl} cMoney={this.state.cMoney} pMoney={this.state.pMoney} cName={this.state.cName} startDay={this.state.startDay} endDay={this.state.endDay} dateType={this.state.dateType} startDate={this.state.startDate} endDate={this.state.endDate}/>
      <Form className="giveForm" onSubmit={this.handleSubmit}>
      <div className="giveFormDiv" >
        <FormItem
          {...formItemLayout}
          label="活动名称"
        >
          {getFieldDecorator("actName",{
            rules: [{ required: true, message: '请输入活动名称!',whitespace: true }],
          })
            (<Input  placeholder="消费送" />)
          }
        </FormItem>
        <FormItem
          {...formItemLayout}
          label="活动对象"
          wrapperCol={{ span: 10 }}
        >
          <span className="ant-form-text">所有使用支付宝支付的会员</span>
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
                        rules: [{required: true, message: this.state.end_tip||'请选择结束时间', validator:this.validEndTime }]
                      })(
                        <DatePicker allowClear={false} showToday={false} disabledDate={this.disabledEndDate.bind(this)} onChange={this.onEndChange.bind(this)}/>
                      )}
                    </FormItem>
                </Col>
        </FormItem>




        <FormItem
          {...formItemLayout}
          label="参与品牌"
        >
          {getFieldDecorator("actBrand",{
          	initialValue:this.state.voucher_brand_name,
            rules: [{ required: true, message: '请输入参与活动的品牌名称!',whitespace:true }],
          })
            (<Input placeholder="请输入参与活动的品牌名称!" />)
          }
        </FormItem>

        <FormItem
          {...formItemLayout}
          labelCol={{ span: 3 }}
  				wrapperCol={{ span: 10 }}
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
            action={AJAX_URL+"/Coupon/ajaxUploadImages"+token}
//          action="http://1koujia.com/cx.php/Coupon/ajaxUploadImages"
            beforeUpload={this.beforeUpload}
            onChange={this.handleImgChange}
          >
            {
              imageUrl ?<img src={imageUrl} alt="" className="avatar" /> :<Icon type="plus" className="avatar-uploader-trigger" />
            }
            {this.state.uploading ?<Spin className="upState" />:""}
          </Upload>
          )}
        </FormItem>

        <FormItem
          {...formItemLayout}
          label="券类型"
          defaultActiveFirstOption={true}
        >
        {getFieldDecorator('couponType',{
            rules: [{ required: true, message: '请选择活动券类型!' }]
        })
          (<Select onChange={this.handleCouponChange.bind(this)}>
              <Option value="1" >代金券</Option>
              <Option value="2">兑换券</Option>
          </Select>)
        }
        </FormItem>

        <FormItem
          className="couponCash"
          style={{display:this.state.couponShow}}
          {...formItemLayout}
          label="券面额"
        >
          {getFieldDecorator("couponCash",{
            rules: [
                    { required: true,type:'number',message:"请输入优惠券面额 !",whitespace:true,pattern:/^(?!0+(?:\.0+)?$)(?:[1-9]\d*|0)(?:\.\d{1,2})?$/}
                ]
          })
            (<InputNumber  onChange={this.handleCouponCashChange} min={0.01} placeholder="请输入代金券面额!" />)
          }
        </FormItem>

        <FormItem
          style={{display:this.state.couponShow}}
          {...formItemLayout}
          label="券名称"
        >
          {getFieldDecorator("couponName",{
            rules: [{ required: true, message: '请输入活动名称!',whitespace:true }],
          })
            (<Input placeholder="输入券面额后自动显示" disabled/>)
          }
        </FormItem>

        <FormItem
          style={{display:this.state.coinShow}}
          {...formItemLayout}
          label="券名称"
        >
          {getFieldDecorator("coinName",{
            rules: [{ required: this.state.coinShow=='block'?true:false, message: '输入兑换券名称!',whitespace:true }],
          })
            (<Input onChange={this.handleCoinChange} placeholder="输入兑换券名称" />)
          }
        </FormItem>

        <FormItem
          className="couponNote"
          {...formItemLayout}
          label="券备注"
        >
        	{getFieldDecorator("couponNote")
            (<Input placeholder="券备注"/>
         			)
          }
         <span>备注内容仅在收银端可见!</span>
        </FormItem>

        <FormItem
          style={{display:this.state.couponShow}}
          className="payNeed"
          {...formItemLayout}
          label="消费门槛"
        >
         <span>消费满</span>
          {getFieldDecorator('payNeed',{
            rules:[
                    { required: this.state.coinShow=='none'?true:false,type:'number',message:"请输入正确的消费门槛",whitespace:true,pattern:/^(?!0+(?:\.0+)?$)(?:[1-9]\d*|0)(?:\.\d{1,2})?$/}
                ]
          })(
            <InputNumber min={0.01} onChange={this.payNeedChange} placeholder=""/>
          )}
         <span>元可用!</span>
        </FormItem>

        <FormItem
          {...formItemLayout}
          label="券有效期"
          defaultActiveFirstOption={true}
        >
        {getFieldDecorator('couponDateType',{
            rules: [{ required: true, message: '请选择券有效期!' }]
        })
          (<Select onChange={this.handleCouponDateChagne.bind(this)}>
              <Option value="1" >相对日期</Option>
              <Option value="2">固定日期</Option>
          </Select>)
        }
        </FormItem>

        <FormItem
              style={{display:this.state.dayShow}}
              label=" "
              labelCol={{ span: 3 }}
              wrapperCol={{ span: 18 }}
              required={false}
              colon={false}
              >
              <Col span="6" style={{marginRight:-10}}>
                  <FormItem>
                    从发券第
                    {getFieldDecorator('startDay', {
                        initialValue:1,
                        rules: [{pattern:/^\d+$/, required: true, message: '请填写正确的起始天数' }]
                    })(
                        <InputNumber onChange={this.changeStartDay.bind(this)} min={1} max={98} style={{width:50,marginLeft:10}}  className="quan_num" placeholder="" />
                    )}
                  </FormItem>
              </Col>
              <Col span="2" className="ant-form-splitCol">
                  <p className="ant-form-split">天至第</p>
              </Col>
              <Col span="5">
                  <FormItem>
                    {getFieldDecorator('endDay', {
                      initialValue:30,
                      rules: [{pattern:/^\d+$/, required: true, message: this.state.end_day_tip||'请填写正确的到期天数', validator: this.validEndDay.bind(this) }]
                    })(
                        <InputNumber onChange={this.onEndDayChange.bind(this)} min={Number(this.state.start_day)+1} max={99} style={{width:50,marginLeft:8}} className="quan_num" placeholder="" />
                    )}
                    天
                  </FormItem>
              </Col>
        </FormItem>

        <FormItem
                style={{display:this.state.dateShow}}
                label=" "
                labelCol={{ span: 3 }}
                required={false}
                colon={false}
                >
                <Col span="4">
                    <FormItem>
                      {getFieldDecorator('startDate', {
                        rules: [{required: true, message: '请选择开始时间' }],
                      })(
                        <DatePicker allowClear={false} showToday={false} disabledDate={this.disabledStartDate} onChange={this.startDateChange.bind(this)}/>
                      )}
                    </FormItem>
                </Col>
                <Col span="1" className="ant-form-splitCol">
                    <p className="ant-form-split">至</p>
                </Col>
                <Col span="4">
                    <FormItem>
                      {getFieldDecorator('endDate', {
                        rules: [{required: true, message: this.state.end_date_tip||'请选择结束时间', validator:this.validEndDate }]
                      })(
                        <DatePicker allowClear={false} showToday={false} disabledDate={this.disabledEndDate.bind(this)} onChange={this.endDateChange.bind(this)}/>
                      )}
                    </FormItem>
                </Col>
        </FormItem>
        
        <FormItem
          label=" "
          labelCol={{ span: 1 }}
          colon={false}
        >
          <Col span="12" onClick={this.handleMoreSet.bind(this)} style={{marginTop:15,textAlign: 'center',backgroundColor: '#eee',cursor:'pointer'}}>
            <span className="ant-form-text">{this.state.setMoreText}<Icon type="down" /></span>
          </Col>
        </FormItem>
        <Row gutter={16} style={{display:this.state.moreSet}}>
    
            <FormItem
              label="领券限制"
              labelCol={{ span: 3 }}
              wrapperCol={{ span: 18 }}
              colon={false}
              required={true}
              >
                    <Col span="3">每人每</Col>
                    <Col span="3" style={{marginLeft:-10}}>
                        <FormItem>
                        {getFieldDecorator('divide',{
                              initialValue:'1',
                              rules: [{ required: true, message: '请选择券有效期!' }]
                          })
                            (<Select>
                                <Option value="1" >周</Option>
                                <Option value="2">天</Option>
                                <Option value="3">月</Option>
                            </Select>)
                          }
                        </FormItem>
                    </Col>
                    <Col span="3" className="ant-form-splitCol">
                        <p className="ant-form-split">最多可得</p>
                    </Col>
                    <Col span="5">
                        <FormItem>
                          {getFieldDecorator('num', {
                            initialValue:1,
                            rules: [{pattern:/^\d+$/, required: true, message: '请填写券量' }]
                          })(
                              <InputNumber  min={1} max={99} style={{width:50}}  placeholder="" />
                          )}
                          张
                          <Tooltip  placement="topLeft" title="限制您的会员在活动期间,每人单位时间内可以领券的数量">
                            <a href="#"><Icon className="tip0" type="exclamation-circle" /></a>
                          </Tooltip>
                        </FormItem>
                    </Col>
            </FormItem>
         
            <FormItem
                label=" "
                labelCol={{ span: 3 }}
                wrapperCol={{ span: 18 }}
                colon={false}
                required={false}
              >
               <Col span="5" style={{marginRight:10}}>每人最多可获得</Col>
                {getFieldDecorator('getNum',{
                  initialValue:1,
                  rules:[
                          {pattern:/^\d+$/, required: true, message:"最多券数量不能小于单位券数量!",validator: this.maxGetNum},
                      ]
                })(
                  <InputNumber onChange={this.getNumChange.bind(this)}  placeholder=""/>
                )}
                张
            </FormItem>

           <FormItem
	            label="适用门店"
	            labelCol={{ span: 3 }}
          		wrapperCol={{ span: 18 }}
	            required>
	            
	            <span className="select-shop" onClick={this.useSelectShop}>选择适用门店</span>
	            <span className="select-shop-info">已选  {this.state.useCheckedshopIds.length} 家门店</span>
	            <UseShopCheck shopkey={this.state.useShopkey}  onCancel={this.onUseShopCancel}   checkedshopIds={this.state.useCheckedshopIds} onFirstLoadShop={this.onUseFirstLoadShop} onOk={this.onUseShopOk} shop_visible={this.state.use_shop_visible}/>
	        	</FormItem>
            
            <FormItem
	            label="核销门店"
	            labelCol={{ span: 3 }}
          		wrapperCol={{ span: 18 }}
	            required>
	            
	            <span className="select-shop" onClick={this.selectShop}>选择核销门店</span>
	            <span className="select-shop-info">已选  {this.state.checkedshopIds.length} 家门店</span>
	            <CreateModalSelectShopForm shopkey={this.state.shopkey}  onCancel={this.onShopCancel}   checkedshopIds={this.state.checkedshopIds} onFirstLoadShop={this.onFirstLoadShop} onOk={this.onShopOk} shop_visible={this.state.shop_visible}/>
	        	</FormItem>


            <FormItem
              {...formItemLayout}
              label="优惠券设置"
              className="couponSet"
              labelCol={{ span: 3 }}
              colon={false}
              wrapperCol={{ span: 10 }}
            >
              <p></p>
            </FormItem>

            <FormItem
                label="券可用时间"
                labelCol={{ span: 4 }}
                wrapperCol={{ span: 16 }}
                required
              >
               <Col span="6" onClick={this.showUseTimeModal.bind(this)} style={{marginRight:5,color:'#2db7f5',cursor:'pointer'}}>选择券可用时间</Col>
               <Col span="14" >{this.state.useWeekList.length?`${this.state.useWeekList+this.state.user_time_txt}`:"未选择"}</Col>
            </FormItem>
            <UseTime useWeekList={this.state.useWeekList}  visible={this.state.useTimeVisible} timeKey={this.state.timeKey} handleUseTimeOk={this.handleUseTimeOk} handleUseTimeCancel={this.handleUseTimeCancel} arrTimeDate={this.state.arrTimeDate} useTimeType={this.state.useTimeType}/>
            
            <FormItem
	            label="券不可用日期"
	            labelCol={{ span: 4 }}
          		wrapperCol={{ span: 18 }}
	            required>
	            
	            <span className="select-shop" onClick={this.openDisDate}>选择券不可用日期</span>
	            <span style={{marginLeft: '5px'}}>{this.state.disabledType=='0'?'不限制':this.state.time_txt}</span>
	            <DisabledDate disdate_visible={this.state.disdate_visible} onDateCancel={this.onDateCancel} onDateOk={this.onDateOk} arrDate={this.state.arrDate} disabledType={this.state.disabledType}/>
	        	</FormItem>
            
            <FormItem
              labelCol={{ span: 3 }}
              wrapperCol={{ span: 12}}
              label="券说明">
              {getFieldDecorator('des')(<Input type="textarea" style={{height:80}}/>)}
            </FormItem>
            
        </Row>
        
        </div>
        <FormItem className="create_btns" style={{ marginTop: 50 }}
          wrapperCol={{ span: 12, offset: 5}}
        >
          <Button type="primary" htmlType="submit">创建</Button>
          <Link to="/">
            <Button style={{marginLeft:20}} type="default" onClick={this.handleReset}>取消</Button>
          </Link>
        </FormItem>
      </Form>
      </Spin>
      </div>
    );
  }
}

myForm = Form.create()(myForm)


export default myForm;


