import React from 'react';
import {Modal,Checkbox,Button,Form,Col,Row,Select,Icon,Input,TimePicker,Message} from 'antd';
import moment from 'moment';

const format = 'HH:mm';
const FormItem = Form.Item;
const CheckboxGroup = Checkbox.Group;
const Option = Select.Option;
let uuid = 1;
const weekOptions = [
  { label: '周一', value: '1' },
  { label: '周二', value: '2' },
  { label: '周三', value: '3' },
  { label: '周四', value: '4' },
  { label: '周五', value: '5' },
  { label: '周六', value: '6' },
  { label: '周日', value: '7' },
];
const hour=['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'];
const minute=['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50','51','52','53','54','55','56','57','58','59']

class UseTime extends React.Component  {
  constructor(props) {
        super(props)
        this.state={
        	useTimeType:'0',
          checkedList:[],
          arrTimeDate:[],
          line:[1,2],
          num:1,
          d_start_time:moment('00:00', format),
	    		d_end_time:moment('23:50', format)
        }
  }

  handleWeekChange = (checkedList) => {
    this.setState({
      checkedList:checkedList
    })
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
  onStartChange=()=>{
		
	}
	onEndChange=()=>{
		
	}

  handleUseTimeOk = () =>{
  	const list=this.state.checkedList.sort(function(a,b){
    	  return a-b
	    })
	    let weekList=[];
	    list.map ((item)=>{
	      if(item=="1"){
	        item='周一'
	      }else if(item=='2'){
	        item='周二'
	      }else if(item=='3'){
	        item='周三'
	      }else if(item=='4'){
	        item='周四'
	      }
	      else if(item=='5'){
	        item='周五'
	      }
	      else if(item=='6'){
	        item='周六'
	      }else{
	        item='周日'
	      }
	      return weekList.push(item)
	    })
  	
  	
  	if( this.state.useTimeType == '1'  ){
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
		    		dateData.push([start.format(format),end.format(format)]);
		    		
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
	    			this.props.handleUseTimeOk(dateData,this.state.useTimeType,weekList);
	    		}else{
	    			Message.error('日期重复!请重新选择');
	    		}
	    	}
	   	});
			
			
			
  	}else{
			
	    this.props.handleUseTimeOk([],this.state.useTimeType,weekList)
		}
    

  }
  
  handleUseTimeCancel =() =>{
    let weekList=[];
    this.props.useWeekList.map ((item)=>{
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
    this.setState({
      checkedList:weekList
    })
    
    const keys = this.props.form.getFieldValue('keys');
		var arrTimeDate = this.props.arrTimeDate;
		var default_start = this.state.d_start_time;
		var default_end = this.state.d_end_time;
		var vdata = {};
		keys.map((k, index) => {
			if( arrTimeDate.length && arrTimeDate.length >= k ){
	    	default_start = arrTimeDate[k-1][0];
	    	default_end = arrTimeDate[k-1][1];
			}
			vdata[`start-${k}`] =  moment(default_start,format);
			vdata[`end-${k}`] =  moment(default_end,format);
		})
		this.props.form.setFieldsValue(vdata);
  	this.props.handleUseTimeCancel(this.props.useWeekList,this)
  }

 remove = (k) => {
    const { form } = this.props;
    // can use data-binding to get
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
  validEndTime = (rule, value, callback) => {
	 	var str = rule.field.split('-')[1];
	 	let getStart = this.props.form.getFieldValue(['start-'+str]);
    	var start_time = moment(getStart, format);
    	var end_time = moment(new Date(value), format);
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
  handleChange = (value) => {
		this.setState({
			useTimeType:value
		})
	}
  render() {
    const { getFieldDecorator, getFieldValue } = this.props.form;
		const formItemLayoutWithOutLabel = {
      wrapperCol: {
        xs: { span: 16, offset: 0 },
        sm: { span: 18, offset: 5 },
      }
    };
    getFieldDecorator('keys', { initialValue: [1] });
    const keys = getFieldValue('keys');
    var arrTimeDate = this.props.arrTimeDate;
    
    
    
    const formItems = keys.map((k, index) => {
    	var default_start = this.state.d_start_time;
    	var default_end = this.state.d_end_time;
    	if( arrTimeDate.length && arrTimeDate.length >= k ){
	    	default_start = arrTimeDate[k-1][0];
	    	default_end = arrTimeDate[k-1][1];
    	}
      return (
        <FormItem
          {...formItemLayoutWithOutLabel}
          label=""
          required={false}
          key={k}
        >
        <Col span="7">
        	<FormItem>
	          {getFieldDecorator(`start-${k}`, {
	          	initialValue:default_start,
	      			rules: [{  required: true, message: '请选择时间' }],
	          })(
	          	<TimePicker onChange={this.onStartChange} format={format}/>
	          )}
          </FormItem>
      	</Col>
        <Col span="1" className="ant-form-splitCol"  style={{paddingRight:'3px'}}>
            <p className="ant-form-split">至</p>
        </Col>
        <Col span="7">
	        <FormItem>
	       		{getFieldDecorator(`end-${k}`, {
	       			initialValue:default_end,
	      			rules: [{ required: true,validator: this.validEndTime  }],
	          })(
	          	<TimePicker onChange={this.onEndChange} format={format}/>
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

      <div>
        <Modal title="券可用时间"
        	visible={this.props.visible}
        	maskClosable={false}
        	key={this.props.timeKey}
          onOk={this.handleUseTimeOk}
          onCancel={this.handleUseTimeCancel}
        >   
          <Row>
            <Col span={3}>选择周期</Col>
            <Col span={21}>
                <CheckboxGroup  value={this.state.checkedList}  className="weekCheckGroup" options={weekOptions}  onChange={this.handleWeekChange} />
            </Col>
          </Row>
          <Row style={{marginTop:10,marginBottom:10}}>
            <Col span={5} style={{textAlign:'right',paddingRight:'10px',lineHeight: '30px'}}>
               选择时间
            </Col>
            <Col span={6}>
                <Select value={this.state.useTimeType} style={{ width: 120 }} onChange={this.handleChange} >
                    <Option value="0">全天</Option>
                    <Option value="1">自定义时间</Option>
                </Select>
            </Col>
          </Row>
          <Row>
          <div style={{ display: this.state.useTimeType=='1'?'block':'none' }}>
            <Form onSubmit={this.handleSubmit}>
              {formItems}
              <FormItem {...formItemLayoutWithOutLabel}>
                <Button type="dashed" onClick={this.add} style={{ width: '60%',display:keys.length>4?'none':'block'}}>
                  <Icon type="plus" />增加一个
                </Button>
              </FormItem>
            </Form>
            </div>
          </Row>
        </Modal>
      </div>
    );
  }
}
  const UseTimeForm=Form.create()(UseTime)

 export default UseTimeForm;
