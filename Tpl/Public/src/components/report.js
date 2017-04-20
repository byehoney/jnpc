import React from 'react';
import {Icon,Button,Row,Col,Modal,} from 'antd';
import Highcharts from 'highcharts';

class Report extends React.Component {
  constructor(props) {
    super(props);
    console.log(this.props)
    this.state = {
      camp_id:this.props.location.query.rid,
      boxShow:'none',//无数据时显示
      boxShowShop:'none',//商铺无数据时的显示
      title:'',//活动标题
      cxActInfo:'',
      act_end_day:'',//活动截止日期
      shopNums:0,//参加活动的门店数量
      actTakenAmt:0,//累计营销收入
      actTakenAvagAmt:0,//平均每张券拉动多少元消费
      total_taken_user_cnt:0,//总领券人数
      total_taken_cnt:0,//总领券数
      total_used_user_cnt:0,//活动总核销人数
      total_used_cnt:0,//活动总核销券数
      percent:0,//活动核销券数占总领券数比
      actDays:0,//活动天数
      actAddNums:0,//新增会员数
      addpercent:0,//会员增长速度
      act_before_avag:0,//笔单价提升
      act_before_trade:0,//参与本次活动的会员交易笔数提升
      get_total_list:[],//营销收益
      lqList:[],//领券人数
      uqList:[],//用券人数
      dateList:[],//时间段
      newAddList:[],//新增会员数
      beforeList:[],//活动前会员数
      beforeActTradeNums:[],//活动前总交易笔数
      total_campaign_trade_cnt:[],//活动中总交易笔数
      act_avag_amt:[],//活动期间笔单价
      before_act_avag_amt:[],//活动之前笔单价
      shopList:[],//门店数组
      total_campaign_trade_amt:[],//门店总营收
      shopDateList:[]//门店日期数组
    };
 }
  componentDidMount() {
    var that=this;
    $.ajax({
      type:'POST',
      dataType:'json',
      url:AJAX_URL+'/Coupon/ajaxGetActTongji'+token,
      data:{camp_id:that.state.camp_id},
      success:function(res){
        if(Number(res.status)){
          let beforeActTradeNums=[Number(res.data.userTradeCnt.beforeActTradeNums)];
          let total_campaign_trade_cnt=[Number(res.data.userTradeCnt.total_campaign_trade_cnt)];
          let before_act_avag_amt=[Number(res.data.userTradeCnt.before_act_avag_amt)];
          let act_avag_amt=[Number(res.data.userTradeCnt.act_avag_amt)];
          that.setState({
            cxActInfo:res.data.cxActInfo,
            title:res.data.title,
            act_end_day:res.data.userDate.endDay,
            actDays:res.data.userDate.actDays,
            actAddNums:res.data.userDate.actAddNums,
            addpercent:res.data.userDate.percent,
            actTakenAvagAmt:res.data.actAmt.actTakenAvagAmt,
            shopNums:res.data.actAmt.shopNums,
            actTakenAmt:res.data.actAmt.actTakenAmt,
            act_before_trade:res.data.userTradeCnt.act_before_trade,
            act_before_avag:res.data.userTradeCnt.act_before_avag,
            total_taken_user_cnt:res.data.actTakenCnt.total_taken_user_cnt,
            total_taken_cnt:res.data.actTakenCnt.total_taken_cnt,
            total_used_user_cnt:res.data.actTakenCnt.total_used_user_cnt,
            total_used_cnt:res.data.actTakenCnt.total_used_cnt,
            percent:res.data.actTakenCnt.percent,
            beforeActTradeNums:beforeActTradeNums,
            total_campaign_trade_cnt:total_campaign_trade_cnt,
            before_act_avag_amt:before_act_avag_amt,
            act_avag_amt:act_avag_amt
          })
          if(res.data.alltongji && res.data.alltongji.length){
            that.setState({
              boxShow:'none'
            })
          }else{
            that.setState({
              boxShow:'block'
            })
          }
          let get_total_list=[];
          let lqList=[];
          let uqList=[];
          let dateList=[];
          let newAddList=[];
          let beforeList=[];

          $(res.data.alltongji).each(function(i,n){
            get_total_list.push(Number(n.today_campaign_trade_amt));
            lqList.push(Number(n.today_taken_cnt));
            uqList.push(Number(n.today_used_cnt));
            dateList.push(n.biz_date);
          })
          //新增会员数和活动前会员数的获取
          $(res.data.acttongji).each(function(i,n){
            newAddList.push(Number(n.today_campaign_new_user_cnt));
          })
          $(res.data.beforeActtongji).each(function(i,n){
            beforeList.push(Number(n.today_campaign_new_user_cnt));
          })
        
          //活动开始前的数据用[""]填充到newAddList中
          let arr=[];
          for(let i=0;i<(dateList.length-newAddList.length);i++){
            arr.push("");
          }
          that.setState({
              get_total_list:get_total_list,
              lqList:lqList,
              uqList:uqList,
              dateList:dateList,
              newAddList:arr.concat(newAddList),
              beforeList:beforeList
          })
          console.log(that.state.dateList)
          that.randerChart0()
          that.randerChart()
          that.randerChart2()
          that.randerChart3()
          that.randerChart4()
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
    //请求门店排行榜
    $.ajax({
      type:'POST',
      dataType:'json',
      url:AJAX_URL+'/Coupon/ajaxGetActShopTongji'+token,
      data:{camp_id:that.state.camp_id},
      success:function(res){
        console.log(res)
        if(Number(res.status)){
          if(res.data && res.data.shopLists && res.data.shopLists.length){
            that.setState({
              boxShowShop:'none'
            })
	          that.randerChart5(res.data,res.info)
          }else{
            that.setState({
              boxShowShop:'block'
            })
          }
          
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

  randerChart0 = () => {
    var chart = new Highcharts.Chart('container0', {
      title: {
          text: '',
          x: -20
      },
      // subtitle: {
      //     text: '数据来源: WorldClimate.com',
      //     x: -20
      // },
      xAxis: {
          categories: this.state.dateList
      },
      yAxis: {
          title: {
              text: '营销收入(元)'
          },
          plotLines: [{
              value: 0,
              width: 1,
              color: '#808080'
          }]
      },
      tooltip: {
          valueSuffix: '元'
      },
      legend: {
          layout: 'horizontal',
          align: 'center',
          verticalAlign: 'bottom',
          borderWidth: 0,
          enabled:false
      },
      series: [{
          name: '营销收入',
          data: this.state.get_total_list
      }]
  });
  }

 randerChart = () => {
    var chart = new Highcharts.Chart('container1', {
	    title: {
	        text: '',
	        x: -20
	    },
	    // subtitle: {
	    //     text: '数据来源: WorldClimate.com',
	    //     x: -20
	    // },
	    xAxis: {
	        categories: this.state.dateList
	    },
	    yAxis: {
	        title: {
	            text: '券数量(张)'
	        },
	        plotLines: [{
	            value: 0,
	            width: 1,
	            color: '#808080'
	        }]
	    },
	    tooltip: {
	        valueSuffix: '张'
	    },
	    legend: {
	        layout: 'horizontal',
	        align: 'center',
	        verticalAlign: 'bottom',
	        borderWidth: 0
	    },
	    series: [{
	        name: '领券数',
	        data: this.state.lqList
	    }, {
	        name: '用券数',
	        data: this.state.uqList
	    }]
	});
  }

  randerChart2 = () => {
    var chart = new Highcharts.Chart('container2', {
	    title: {
	        text: '',
	        x: -20
	    },
	    xAxis: {
	        categories: this.state.dateList
	    },
	    yAxis: {
	        title: {
	            text: '新增会员(人)'
	        },
	        plotLines: [{
	            value: 0,
	            width: 1,
	            color: '#808080'
	        }]
	    },
	    tooltip: {
	        valueSuffix: '人'
	    },
	    legend: {
	        layout: 'horizontal',
	        align: 'center',
	        verticalAlign: 'bottom',
	        borderWidth: 0
	    },
	    series: [ {
	        name: '活动期间',
	        data: this.state.newAddList
	    },{
          name: '活动前',
          data: this.state.beforeList
      }]
	});
  }

  randerChart3 = () => {
    var chart = new Highcharts.Chart('container3', {
      chart: {
            type: 'column'
        },
        title: {
            text: ''
        },
        xAxis: {
            categories: [

            ],
            crosshair: true,
            visible:false
        },
        yAxis: {
            min: 0,
            title: {
                text: '交易笔数 (笔)'
            }
        },
        legend:{
          squareSymbol:false,
          symbolRadius:0
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px"></span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
            '<td style="padding:0"><b>{point.y} 笔</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: [{
            name: '活动前',
            data: this.state.beforeActTradeNums,
            color:"rgb(19, 182, 206)"
        }, {
            name: '活动期间',
            data: this.state.total_campaign_trade_cnt,
            color:'rgb(35, 194, 251)'
        }]
    })
  }

  randerChart4 = () => {
    var chart = new Highcharts.Chart('container4', {
      chart: {
            type: 'column'
        },
        title: {
            text: ''
        },
        xAxis: {
            categories: [
               
            ],
            crosshair: true,
            visible:false
        },
        yAxis: {
            min: 0,
            title: {
                text: '笔单价 (元)'
            }
        },
        legend:{
          squareSymbol:false,
          symbolRadius:0
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px"></span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
            '<td style="padding:0"><b>{point.y:.2f} 元</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: [{
            name: '活动前',
            data: this.state.before_act_avag_amt,
            color:"rgb(19, 182, 206)"
        }, {
            name: '活动期间',
            data: this.state.act_avag_amt,
            color:'rgb(35, 194, 251)'
        }]
    })
  }
  randerChart5 = (data,title) => {
  	console.log(data,title)
    var shopdata = data.shopLists;
    var shopSevArr = [];
    $(shopdata).each(function(key,val){
      var serLine = {};
      serLine.name = val.store_name;
      serLine.data = [val.total_campaign_trade_amt];
      shopSevArr.push(serLine);
    })
console.log(shopSevArr)
        var chart = Highcharts.chart('container5', {
        chart: {
            type: 'column'
        },
        title: {
            text: ' '
        },
        xAxis: {
            categories: ['销量']
        },
        yAxis: {
            labels: {
                x: -15
            },
            title: {
                text: ''
            }
        },
        series:shopSevArr,
        tooltip: {
            valueSuffix: ' 元'
        },
        legend:{
          squareSymbol:false,
          symbolRadius:0
        }
    });
  }
 //  componentDidMount() {
	// this.randerChart()
 //  }
  render() {
    return (
      <div className="report">
        <Row>
        	<Col>
        		{this.state.title}
        	</Col>
        	<Col span={20}>
				    {this.state.cxActInfo}
        	</Col>
        </Row>
        <div>
          <Row style={{margin:'20px 0 10px 0'}}>
              <Col span={3} style={{borderLeft:'3px solid #3089dc',paddingLeft:10,lineHeight:'25px'}}>
                营销收入
              </Col>
              <Col span={21} style={{borderTop:'1px dotted #ccc',position:'relative',top:12}}>
            
              </Col>
          </Row>
          <Row style={{margin:'20px 0 10px 0'}}>
              <Col span={24} >
                截止到{this.state.act_end_day}日,参加本次活动的全部{this.state.shopNums}家门店,<span style={{color:'#ff6602'}}>累计营销收入{this.state.actTakenAmt}元,平均每张券拉动{this.state.actTakenAvagAmt}元消费</span>
              </Col>
          </Row>
          <Row>
            <Col span={24}>
              {
                this.state.boxShow=='none'?<div id="container0" className="chart-box" style={{width:'100%',height:350}}></div>
                                          : <div style={{height:350,textAlign:'center',lineHeight:'350px',fontSize:20,border:'1px solid #e9e9e9'}}><Icon type="frown-o" /><div id="container0" className="chart-box" style={{width:'100%',height:350,display:'none'}}></div>暂无数据</div>
              }
            </Col>
          </Row>
        </div>
        <Row style={{margin:'20px 0 10px 0'}}>
        	<Col span={3} style={{borderLeft:'3px solid #3089dc',paddingLeft:10,lineHeight:'25px'}}>
        		活动参与人数
        	</Col>
        	<Col span={21} style={{borderTop:'1px dotted #ccc',position:'relative',top:12}}>
				
        	</Col>
        </Row>
        <Row>
        	<Col span={24} style={{}}>
        		<p>截止到{this.state.act_end_day}日 共有{this.state.total_taken_user_cnt}人参与活动， 领取了<span style={{color:'#ff6600'}}>{this.state.total_taken_cnt}张券</span>，其中<span style={{color:'#ff6600'}}>{this.state.total_used_user_cnt}人持券到店消费{this.state.total_used_cnt}次，占总领券人数的{this.state.percent}。</span></p>
        	</Col>
        </Row>
        <Row>
        	<Col span={24}>
            {
              this.state.boxShow=='none'?<div id="container1" className="chart-box" style={{width:'100%',height:350}}></div>
                                        : <div style={{height:350,textAlign:'center',lineHeight:'350px',fontSize:20,border:'1px solid #e9e9e9'}}><Icon type="frown-o" /><div id="container1" className="chart-box" style={{width:'100%',height:350,display:'none'}}></div>暂无数据</div>
            }
        	</Col>
        </Row>
        <Row style={{margin:'20px 0 10px 0'}}>
        	<Col span={5} style={{borderLeft:'3px solid #3089dc',paddingLeft:10,lineHeight:'25px'}}>
        		消费分布(与活动前)
        	</Col>
        	<Col span={18} style={{borderTop:'1px dotted #ccc',position:'relative',top:12}}>
				
        	</Col>
        </Row>
        <Row>
        	<Col span={24} style={{}}>
        		<p>截止到{this.state.act_end_day}日 在活动的{this.state.actDays}天里， 共有{this.state.actAddNums}位顾客成为您的会员，相比活动前的{this.state.actDays}天 ,<span style={{color:'#ff6600'}}>会员增长速度{this.state.addpercent}</span></p>
        	</Col>
        </Row>
        <Row>
        	<Col span={24}>
          {
              this.state.boxShow=='none'?<div id="container2" className="chart-box" style={{width:'100%',height:350}}></div>
                                        : <div style={{height:350,textAlign:'center',lineHeight:'350px',fontSize:20,border:'1px solid #e9e9e9'}}><Icon type="frown-o" /><div id="container2" className="chart-box" style={{width:'100%',height:350,display:'none'}}></div>暂无数据</div>
            
          }
        	</Col>
        </Row>

        <Row style={{margin:'20px 0 10px 0'}}>
        	<Col span={5} style={{borderLeft:'3px solid #3089dc',paddingLeft:10,lineHeight:'25px'}}>
        		会员消费对比(与活动前)
        	</Col>
        	<Col span={18} style={{borderTop:'1px dotted #ccc',position:'relative',top:12}}>
				
        	</Col>
        </Row>
        <Row>
        	<Col span={24} style={{}}>
        		<p>截止到{this.state.act_end_day}， 在活动的{this.state.actDays}天里， 相比活动前的{this.state.actDays}天， 参与本次活动的会员<span style={{color:'#ff6600'}}>交易笔数提升了{this.state.act_before_trade}， 笔单价提升了{this.state.act_before_avag}。</span></p>
        	</Col>
        </Row>
        <Row>
        	<Col span={12}>
        		{
              this.state.boxShow=='none'?<div id="container3" className="chart-box" style={{width:'100%',height:350}}></div>
                                        : <div style={{height:350,textAlign:'center',lineHeight:'350px',fontSize:20,border:'1px solid #e9e9e9'}}><Icon type="frown-o" /><div id="container3" className="chart-box" style={{width:'100%',height:350,display:'none'}}></div>暂无数据</div>
            
            }
        	</Col>
        	<Col span={12}>
        		{
              this.state.boxShow=='none'?<div id="container4" className="chart-box" style={{width:'100%',height:350}}></div>
                                        : <div style={{height:350,textAlign:'center',lineHeight:'350px',fontSize:20,border:'1px solid #e9e9e9'}}><Icon type="frown-o" /><div id="container4" className="chart-box" style={{width:'100%',height:350,display:'none'}}></div>暂无数据</div>
            
            }
        	</Col>
        </Row>

        <Row style={{margin:'20px 0 10px 0'}}>
          <Col span={5} style={{borderLeft:'3px solid #3089dc',paddingLeft:10,lineHeight:'25px'}}>
            门店收入对比(与活动前)
          </Col>
          <Col span={18} style={{borderTop:'1px dotted #ccc',position:'relative',top:12}}>
        
          </Col>
        </Row>
        <Row>
          <Col span={24}>
            {
              this.state.boxShowShop=='none'?<div id="container5" className="chart-box" style={{width:'100%',height:350}}></div>
                                        : <div style={{height:350,textAlign:'center',lineHeight:'350px',fontSize:20,border:'1px solid #e9e9e9'}}><Icon type="frown-o" /><div id="container5" className="chart-box" style={{width:'100%',height:350,display:'none'}}></div>暂无数据</div>
            }
          </Col>
        </Row>
      </div>
    );
  }
}


export default Report;