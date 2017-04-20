import React from 'react';
import {Table, Icon, Input, Select, Button, Form , Modal,Row,Col,Spin } from 'antd';
import { Link,Route, Redirect } from 'react-router';
import phone from '../images/phone1.png';

const FormItem = Form.Item;
const Option = Select.Option;

class NewModal extends React.Component {

  handleOk = ()=>{
    this.props.ok();
  }

  handleCancel =() =>{
    this.props.cancle()
  }
  
  render() {
  	let moData = this.props.modalData;
  	let qname = '代金券';
  	let useTime = '1';
  	let auto = '';
    let usedetail = ',消费满'+moData.user_min_consume+'元可用'
  	let qvalid_txt = ',券有效期'+(Number(moData.voucher_relative_time)- Number(moData.voucher_relative_delay))+'天';
  	if( moData.act_type == '1' || moData.act_type == '2' ){
  		if( moData.promo_tools_voucher_type == '1' ){
	  		qname = '代金券';
  		}else{
  			qname = '兑换券';
        usedetail = '';
  		}
  		useTime = moData.user_win_count;
  		if( moData.validate_type == 1 ){
  			qvalid_txt = ',券有效期'+moData.voucher_start_time+'至'+moData.voucher_end_time+'日';
  		}
  	}else{
  		if( moData.auto_delay_flag == 'Y' ){
  			auto = '您已设置本活动自动续期';
  		}
  	}
  	let weekTxt = '全时段可用';
  	if( moData.use_time_values && moData.use_time_values.length ){
  		let weekList = [];
	    moData.use_time_values.map ((item)=>{
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
	      return weekList.push(item);
	    })
	    weekTxt = weekList.join(',');
	    
	    if( moData.use_time_values_time && moData.use_time_values_time.length ){
	    	let timeDate = moData.use_time_values_time;
	  		let newTimeDate = [];
	  		for( let x=0;x<timeDate.length;x++ ){
					newTimeDate.push( timeDate[x].split(',').join('至') )
				}
	  		weekTxt += ',的'+newTimeDate.join(',');
	    }
	    
	    
  	}
  	
  	let use_day = '无限制';
  	if( moData.use_forbidden_day && moData.use_forbidden_day.length ){
  		let arrDate = moData.use_forbidden_day;
  		let newArrDate = [];
  		for( let x=0;x<arrDate.length;x++ ){
				newArrDate.push( arrDate[x].split(',').join('至') )
			}
  		use_day = newArrDate.join(',');
  	}
  	
    		    
  	
  	
  	
    return (
      <div>
        <Modal  width={600} title={moData.name+'活动方案'} visible={this.props.visible}
          onOk={this.handleOk} onCancel={this.handleCancel}
        >
        <Spin spinning={this.props.modLoading} size="large">
          <p style={{color:'#333'}}>{moData.start_time} 至 {moData.end_time} 期间，{moData.act_obj}可领取优惠券，每人可参加 {useTime}次{ moData.act_type=='3'?'，本活动共可发出'+moData.quantity+'张优惠券。':'。' }<span style={{color:'#f85800'}} >{auto}</span></p>
          <div style={{border:'1px solid #caeabf',padding:10,marginTop:20,position:'relative'}}>
            <p style={{color:'#f85800'}}>{moData.act_obj}，可领取{moData.voucher_name}{moData.usedetail}{qvalid_txt}</p>
            <div style={{color:'#333',lineHeight:'25px',overflow:'hidden'}}>
              <Col style={{textAlign:'right',marginRight:5}} span={4}>券可用范围:</Col>
              <Col span={18}>全场通用</Col>
              <Col style={{textAlign:'right',marginRight:5}} span={4}>券可用时间:</Col>
              <Col span={18}>{weekTxt}</Col>
              <Col style={{textAlign:'right',marginRight:5}} span={4}>券不可用日期:</Col>
              <Col span={18}>{use_day}</Col>
              <Col style={{textAlign:'right',marginRight:5}} span={4}>券使用说明:</Col>
              <Col span={18}>{moData.use_rule_desc}</Col>
            </div>
            <div style={{textAlign:'center',position:'absolute',top:32,right:30}}>
              <img style={{width:70,height:70,borderRadius:'50%'}} src={moData.logo}/>
              <p style={{width:70,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'noWrap'}}>{moData.voucher_brand_name}</p>
            </div>
          </div>
          </Spin>
        </Modal>
      </div>
    );
  }
}

class CodeModal extends React.Component {

  handleCodeOk = ()=>{
    this.props.ok();
  }

  handleCodeCancel =() =>{
    this.props.cancle()
  }
  render() {
    return (
      <div>
        <Modal style={{textAlign:'center'}} footer={null}   width={400} title="二维码" visible={this.props.visible}
          okText="下载" 
          onOk={this.handleCodeOk} onCancel={this.handleCodeCancel}
        >
          <img style={{width:200,height:200}} src={this.props.qr_url}/>
          <div className="ant-modal-footer" style={{paddingTop:15,marginTop:25,marginBottom:'-10px',marginLeft:'-10px',marginRight:'-10px'}}>
            <a style={{marginRight:15}} target='_blank' href={this.props.qr_url}><button type="button" className="ant-btn ant-btn-primary ant-btn-lg"><span>下 载</span></button></a>
            <button  type="button" className="ant-btn ant-btn-lg" onClick={this.handleCodeCancel}><span>取 消</span></button>
          </div>

        </Modal>
      </div>
    );
  }
}



class FormLayoutDemo extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      formLayout: 'inline',
      actName:'',
      act_state:'0'
    };

	  
  }

  handleSearch = () => {
    if(this.state.actName&&this.state.actName.trim()!=""){
      this.props.handleUpdate({actName:this.state.actName,act_state:this.state.act_state,current:1})
    }else{

      Modal.warning({
        title: '',
        content: '活动名称不能为空',
        maskClosable:true
      })
    }
  }

  handleChange = (value) => {
  	this.setState({
  		...this.state,
  		act_state:value
  	})

  }
  
  onChangeActName =(e) =>{
    this.setState({
      actName:e.target.value
    })
  }

  handleClear = () =>{
    this.setState({
      act_state:'0',
      actName:''
    })
    if(this.state.actName&&this.state.actName.trim()!=""){
      this.props.handleUpdate({actName:'',act_state:'0',current:1})
    }
  }
  
  render() {
    const { formLayout } = this.state;
    const formItemLayout = formLayout === 'horizontal' ? {
      labelCol: { span: 4 },
      wrapperCol: { span: 14 },
    } : null;
    const buttonItemLayout = formLayout === 'horizontal' ? {
      wrapperCol: { span: 14, offset: 4 },
    } : null;

    const { getFieldDecorator } = this.props.form;
    return (
      <div>
        <Form layout='inline' style={{marginBottom:20}}>
          <FormItem
            label="活动名称"
            labelCol={{ span: 8}}
          	wrapperCol={{ span: 14 }}
          >
            <Input value={this.state.actName} onChange={this.onChangeActName} placeholder="请输入活动名称" />
          </FormItem>
          
          
          <FormItem
	          label="活动状态"
	          
	        >
	          
	            <Select placeholder="请选择活动状态"
	            	style={{ width: 120 }}
	            	value={this.state.act_state}
	            	onChange={this.handleChange}
	            >
	            	<Option value="0">全部</Option>
	              <Option value="1">已结束</Option>
	              <Option value="2">进行中</Option>
	              <Option value="3">未开始</Option>
	            </Select>
	         
	        </FormItem>
          
          <FormItem style={{float:'right'}} {...buttonItemLayout}>
            <Button type="default" size="large" onClick={this.handleClear}>清除</Button>
          </FormItem>
          <FormItem style={{float:'right'}} {...buttonItemLayout}>
            <Button onClick={this.handleSearch} type="primary" size="large">查询</Button>
          </FormItem>
        </Form>
      </div>
    );
  }
}


const WrappedApp = Form.create()(FormLayoutDemo);




export default class myTable extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            data: [],
            pagination: {},
            visible:false,
            modalData:{},
            modLoading:false,
            codeVisible:false,//二维码弹框
            loading: false,
            actName:'',
            act_state:'0',
            act_id:'',
            qr_url:''
        };
    }
    showModal = (e) =>{
      this.setState({
      	modLoading:true,
        visible:true
      })
      var that=this;
      
      $.ajax({
        type:"POST",
        dataType:'json',
        url:AJAX_URL+'/Coupon/ajaxGetActInfo'+token,
        data:{camp_id:e.target.getAttribute("data-cid")},
        success:function(data){
          if(data.status){
          	that.setState({
          		modLoading:false,
			        modalData:data.data
			      })
          }else{
          	if( data.data.sq ){
		    			Modal.warning({
						    title: data.info,
						    onOk() {
						    	window.location.href = data.data.url;
						    }
						  });
		    		}else{
		    			this.setState({
				        visible:false
				      })
		    			Modal.warning({'content':data.info});  
		    		}
          }
        }
      })
    }

    handleOk = (e) => {
      this.setState({
        visible: false,
      });
    }
    handleCancel = (e) => {
      this.setState({
        visible: false,
      });
    }
//二维码弹框
    showCodeModal = (e) =>{
      this.setState({
        codeVisible:true
      })
      var that=this;
      $.ajax({
        type:"POST",
        dataType:'json',
        url:AJAX_URL+'/Coupon/ajaxGetQrUrl'+token,
        data:{camp_id:e.target.getAttribute("data-sid")},
        success:function(res){
          if(res.status){
            that.setState({
              qr_url:res.data.qr_url
            })
          }else{
            if( res.data.sq ){
              Modal.warning({
                title: res.info,
                onOk() {
                  window.location.href = res.data.url;
                }
              });
            }else{
              Modal.warning({'content':res.info});  
            }
          }
        }
      })
    }

    handleCodeOk = (e) => {
      this.setState({
        codeVisible: false,
      });
    }
    handleCodeCancel = (e) => {
      this.setState({
        codeVisible: false,
      });
    }
//二维码弹框结束
//停止活动
    stopAct = (e) =>{
      var act_id=e.target.getAttribute("data-sid");
      var that = this;
      Modal.confirm({
        title: '活动停止',
        content: '确定停止该活动吗?',
        onOk() {
        	
          $.ajax({
            type:'POST',
            dataType:'json',
            data:{camp_id:act_id},
            url:AJAX_URL+'/Coupon/ajaxSetActShop'+token,
            success:function(data){
                if( Number(data.status) ){
                    Modal.success({
									    title: '提示',
									    content: '停止成功',
									  });
                 		that.handleTableChange(that.state.pagination)
                }else{
                  if( data.data.sq ){
                    window.location.href =data.data.url;
                }else{
                  Modal.warning({'content':data.info});  
                }
              }
            }
          })
        },
      })
    }
//停止活动结束
    handleTableChange = (pagination) => {
      const pager = { ...this.state.pagination };
      pager.current = pagination.current;
      this.setState({
        pagination: pager,
      });
      this.ajax({
        page_size:10,
        page_num: pagination.current,
        actName:this.state.actName,
        act_state:this.state.act_state
      });
    }
    ajax = (params ={actName:this.state.actName,act_state:this.state.act_state} ) => {
      var that=this;
      const pagination = { ...this.state.pagination };
      pagination.current=1;
      if(params.current){
        this.setState({
          pagination:pagination
        })
      }
      this.setState({ loading: true,actName:params.actName,act_state:params.act_state});
      $.ajax({
        url: AJAX_URL+'/Coupon/ajaxGetActList'+token,
        method: 'POST',
        data: {
          page_size: 10,
          page_num:1,
          ...params
        },
        dataType: 'json',
        success:function(res){
          if(res.status){
              const pagination = { ...that.state.pagination };
            pagination.total = Number(res.data.page);
            that.setState({
              loading: false,
              actName:params.actName,
              act_state:params.act_state,
              data: res.data.actlist,
              pagination:pagination
            });
          }else{
            if( res.data.sq ){
              Modal.warning({
                title: res.info,
                onOk() {
                  window.location.href = res.data.url;
                }
              });
            }else{
              Modal.warning({'content':res.info});  
            }
          }
        }
      })
    }

    componentDidMount() {
      this.ajax();
    }
    

    render() {
          var that=this;
          const columns = [{
          title: '活动名称',
          width: '20%',
          dataIndex: 'name',
          render(text , record , index){
            if(record.act_type == '1'){
              return <a  data-cid={record.camp_id}  onClick={that.showModal}>{text}</a>
            }else if(record.act_type =='2'){
               return <a  data-cid={record.camp_id}  onClick={that.showModal}>{text}</a>
            }else{
              return <a  data-cid={record.camp_id}  onClick={that.showModal}>{text}</a>
            }
          }
          }, {
              title: '活动时间',
              width: '20%',
              dataIndex: 'start_time',
          }, {
              title: '活动停止时间',
              width: '20%',
              dataIndex: 'stop_time'
          },{
              title: '活动状态',
              width: '20%',
              dataIndex: 'act_state',
              render(text,record,index){
                if(text == '0'){
                  text='已结束'
                }else if(text == '1'){
                  text='进行中'
                }else{
                  text='未开始'
                }
                return text
              }
          },{
              title: '操作',
              width: '20%',
              dataIndex: 'operate',
              render(text, record, index) {
                  var str=[];
                  if(record.act_report=='1'){
                    str.push(['查看报告',record.camp_id])
                  }
                  if(record.act_state=='1'){
                    str.push(['停止',record.camp_id]) ;
                  }
                  if(record.act_type=='3'){
                    str.push(['二维码',record.camp_id]);
                  }
                  

                  str=str.map((val ,index) =>{
                    if( val[0] == '停止' ){
                      return (<a onClick={that.stopAct}  key={index} data-sid={val[1]}  style={{marginRight:5}}>{val[0]}</a>)
                    }
                    if( val[0] == '二维码' ){
                      return (<a onClick={that.showCodeModal} key={index} data-sid={val[1]}  style={{marginRight:5}}>{val[0]}</a>)
                    }
                    if( val[0] == '查看报告' ){
                      return <Link key={index} to={{pathname: '/report',search:'?rid='+val[1]}} style={{marginRight:5}}  >{val[0]}</Link>
                    }
                    
                  })
                  return str
            }
        }];

        return (
        	<div>
        		<WrappedApp handleUpdate={this.ajax}/>
            <div style={{width:'15%',background:'#2db7f5',color:"#fff",textAlign:'center',lineHeight:'30px',borderRadius:'5px 5px 0 0'}}>我的营销活动</div>
            <Table columns={columns}
              rowKey={record => record.camp_id}
              dataSource={this.state.data}
              pagination={this.state.pagination}
              loading={this.state.loading}
              onChange={this.handleTableChange}
            />
            <NewModal modalData={this.state.modalData} modLoading={this.state.modLoading} visible={this.state.visible} ok={this.handleOk} cancle={this.handleCancel}/>
            <CodeModal qr_url={this.state.qr_url} visible={this.state.codeVisible} ok={this.handleCodeOk} cancle={this.handleCodeCancel}/>
          </div>
        )
    }
}
