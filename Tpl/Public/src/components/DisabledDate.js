import React from 'react';
import {Form,Input,Modal,Icon, Button,Select,DatePicker,Col,Message} from 'antd';
import moment from 'moment';

const FormItem = Form.Item;
const Option = Select.Option;
let uuid = 1;

class DisabledDate extends React.Component {
	constructor(props){
		super(props);
		
		this.state={
			dateKey:new Date().getTime(),
			disabledType:'0',
			arrNum:[1],
			end_tip:'',
			arrDate:[],
			d_start_time:moment(new Date(), 'YYYY-MM-DD'),
	    	d_end_time:moment(new Date(new Date().getTime() + 86400000*7), 'YYYY-MM-DD')
		}
	}
	onDateCancel = () =>{
		
		
		this.props.onDateCancel(this);
	}
	chunk = (array, size) => {
        var result = [];
        for (var x = 0; x < Math.ceil(array.length / size); x++) {
            var start = x * size;
            var end = start + size;
            result.push(array.slice(start, end));
        }
        return result;
    }
	onDateOk = () => {
		if( this.state.disabledType == '1'  ){
			var data_arrs = this.props.form.getFieldsValue();
			var needValids = [];
			for( let line in data_arrs ){
				if( line != 'keys' ){
					needValids.push(line);
				}
			}
			
			this.props.form.validateFields(needValids, { force: true },(err, values) => {
		    	if (!err) {
		    		var allArr = [];
		    		let dateData = [];
		    		let date_vaild = true;
		    		
		    		for( let jkey in values ){
			    		allArr.push(values[jkey]);
		    		}
		    		var dateArr = this.chunk(allArr,2);
		    		
		    		for( let i=0;i<dateArr.length;i++ ){
			    		let start = dateArr[i][0];
			    		let end = dateArr[i][1];
			    		
	//		    		let linedata = ];
			    		dateData.push([start.format('YYYY-MM-DD'),end.format('YYYY-MM-DD')]);
			    		
			    		for( let o=0;o<dateArr.length;o++ ){
			    			if( i != o ){
				    			let otherStart = dateArr[o][0];
				    			let otherEnd = dateArr[o][1];
	
								if(  moment(start).isBetween(otherStart, otherEnd) || start.isSame(otherStart)  ){
									date_vaild = false;
									break;
								}
			    			}
			    		}
			    		if( !date_vaild ){
			    			break;
			    		}
		    		}
		    		if( date_vaild ){
		    			this.props.onDateOk(dateData,this.state.disabledType);
		    		}else{
		    			Message.error('日期重复!请重新选择');
		    		}
		    	}
		   	});
		}else{
			this.props.onDateOk([],this.state.disabledType);
		}
		
	}
	handleChange = (value) => {
		this.setState({
			disabledType:value
		})
	}
  	remove = (k) => {
	    const { form } = this.props;
	    const keys = form.getFieldValue('keys');
	    // We need at least one passenger
	    if (keys.length === 1) {
	      return;
	    }
	
	    // can use data-binding to set
	    form.setFieldsValue({
	      keys: keys.filter(key => key !== k),
	    });
	}
  	add = () => {
	    uuid++;
	    const { form } = this.props;
	    // can use data-binding to get
	    const keys = form.getFieldValue('keys');
	    const nextKeys = keys.concat(uuid);
	    if( keys.length > 4 ){
	    	return;
	    }
	    form.setFieldsValue({
	      keys: nextKeys,
	    });
	}
	disabledStartDate =(current) => {
		return (current && (current.valueOf()+86400000) <= moment(new Date(), 'YYYY-MM-DD'))
	}
	disabledEndDate = (current) =>{
		return (current && (current.valueOf()+86400000) <= moment(new Date(), 'YYYY-MM-DD'))
	    
	}
	onStartChange(){
		
	}
	onEndChange(){
		
	}
	 validEndTime = (rule, value, callback) => {
	 	var str = rule.field.split('-')[1];
	 	let getStart = this.props.form.getFieldValue(['start-'+str]);
    	var start_time = moment(getStart, 'YYYY-MM-DD');
    	var end_time = moment(new Date(value), 'YYYY-MM-DD');
    	if( end_time ){
    		if( start_time > end_time ){
				callback('结束时间不能大于开始时间');
	      		return;
		    }
    	}else{
    		this.setState({
				end_tip:null
			})
    		callback('结束时间不能为空');
      		return;
    	}
	    callback();
	}
	    
	handleSubmit = (e) => {
	    e.preventDefault();
	    this.props.form.validateFields((err, values) => {
	      if (!err) {
	        console.log('Received values of form: ', values);
	      }
	    });
	}
	render(){
		
		   const { getFieldDecorator, getFieldValue } = this.props.form;
		    const formItemLayout = {
		      labelCol: {
		        xs: { span: 24 },
		        sm: { span: 5 },
		      },
		      wrapperCol: {
		        xs: { span: 24 },
		        sm: { span: 18 },
		      },
		    };
		    const selectLayout = {
	            labelCol: { span: 5 },
	            wrapperCol: { offset: 5}
	        }
		    const formItemLayoutWithOutLabel = {
		      wrapperCol: {
		        xs: { span: 16, offset: 0 },
		        sm: { span: 18, offset: 5 },
		      },
		    };
		    var arrDate = this.props.arrDate;
		    
		    getFieldDecorator('keys', { initialValue: this.state.arrNum });
		    
		    const keys = getFieldValue('keys');
		    const formItems = keys.map((k, index) => {
		    	var default_start = this.state.d_start_time;
		    	var default_end = this.state.d_end_time;
		    	if( arrDate.length && arrDate.length >= k ){
			    	default_start = moment( arrDate[k-1][0], 'YYYY-MM-DD')
			    	default_end = moment( arrDate[k-1][1], 'YYYY-MM-DD')
		    	}
		      return (
		        <FormItem
		          {...(index === 0 ? formItemLayout : formItemLayoutWithOutLabel)}
		          label={index === 0 ? '选择日期' : ''}
		          required={false}
		          key={k}
		        >
		          
		        	<Col span="8">
                        <FormItem>
				        	{getFieldDecorator(`start-${k}`, {
				        		
				        		initialValue:default_start,
				        		rules: [{required: true, message: '请选择开始时间' }],
				        	})(
				        		<DatePicker allowClear={false}  showToday={false} disabledDate={this.disabledStartDate} onChange={this.onStartChange.bind(this)}/>
				        	)}
				        </FormItem>
                    </Col>
                    <Col span="2" className="ant-form-splitCol">
                        <p className="ant-form-split">至</p>
                    </Col>
                    <Col span="8">
                        <FormItem>
				        	{getFieldDecorator(`end-${k}`, {
				        		initialValue:default_end,
				        		rules: [{required: true , validator: this.validEndTime }]
				        	})(
				        		<DatePicker allowClear={false}  showToday={false} disabledDate={this.disabledEndDate} onChange={this.onEndChange.bind(this)}/>
				        	)}
				        </FormItem>
                    </Col>
                    <Col span="4" className="ant-form-splitCol">
                        <Icon
				            className="dynamic-delete-button"
				            type="minus-circle-o"
				            disabled={keys.length === 1}
				            onClick={() => this.remove(k)}
				          />
                    </Col>
                    
		        </FormItem>
		      );
		    });
		return (
			<Modal
		        title="全不可用日期"
		        okText="确定"
		        visible={this.props.disdate_visible}
		        cancelText="取消"
		        onCancel={this.onDateCancel}
		        onOk={this.onDateOk}
		        key={this.state.dateKey}
		    >
				<FormItem 
		          	{...selectLayout}
		          	required
		          	label="券可用范围">
		            <Select value={this.state.disabledType} style={{ width: 120 }} onChange={this.handleChange}>
				    <Option value="0">不限制</Option>
				    <Option value="1">指定日期</Option>
					</Select>
		        </FormItem>
		          
	        	<div style={{ display: this.state.disabledType=='1'?'block':'none' }}>
	        		{formItems}
					<FormItem {...formItemLayoutWithOutLabel}>
			          <Button type="dashed" style={{ width: '60%',display:keys.length>4?'none':'block'}} onClick={this.add}>
			            <Icon type="plus" />增加一个
			          </Button>
			        </FormItem>
	        	</div>
	        	
			</Modal>
		)
	}
	
	
	
	
}




const DisabledDateForm = Form.create()(DisabledDate);

export default DisabledDateForm;
