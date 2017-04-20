import React from 'react'
import { Card , Icon,Button,Modal} from 'antd';
import { Link } from 'react-router';

import ww from '../images/ww.jpg';
import dd from '../images/dd.png';
import wx from '../images/wxx.jpg';
import xs from '../images/xs.png';
import mf from '../images/mf.png';
import lx from '../images/lx.png';
import $  from 'jQuery';

var YunyingList = React.createClass({
  render: function() {
    var commentNodes = this.props.data.map(function(comment) {
    	return (
	        <li key={comment.d}>
	            <div className="box_top">
	                <p className="top_data">0</p>
	                <p className="top_title">今日新增会员(人)</p>
	            </div>
	            <div className="box_left">
	                <p className="top_data">0</p>
	                <p className="top_title">总会员</p>
	            </div>
	            <div className="box_right">
	                <p className="top_data">0 (0%)</p>
	                <p className="top_title">交易二次以上的会员</p>
	            </div>
	        </li>
    	);
    });
    return (
       <ul className="data_info">
        {commentNodes}
      </ul>
    );
  }
});



















export default class myHome extends React.Component {
    constructor(props) {
        super(props)
        this.state = {
            merNumNew:0,
            merNumTotal:0,
            tradeTwiceUser:0,
            consumptionToday:0,
            couponTaken:0,
            couponUse:0,
            couponProfit:0,
            consumptionTotal:0,
            consumptionavg:0
        }
    }

    componentDidMount =() => {
        window.scrollTo(0,0)
        var that=this;
        $.ajax({
            type:"POST",
            dataType:'json',
            url:AJAX_URL+'/Coupon/ajaxGetTongji'+token,
            success:function(res){
                console.log(res)
                if(res.status){
                		that.setState({
                        merNumNew:res.data.memberCount.merNumNew,
                        merNumTotal:res.data.memberCount.merNumTotal,
                        tradeTwiceUser:res.data.memberCount.tradeTwiceUser,
                        consumptionToday:res.data.consumption.consumptionToday,
                        couponTaken:res.data.consumption.couponTaken,
                        couponUse:res.data.consumption.couponUse,
                        couponProfit:res.data.coupon.couponProfit,
                        consumptionTotal:res.data.coupon.consumptionTotal,
                        consumptionavg:res.data.coupon.consumptionavg
                    })
                    
                }else{
                	if( res.sq ){
									    	window.location.href = res.data.url;
					    		}else{
					    			Modal.warning({'content':res.info});  
					    		}
                }
            }
        })
    }

    render(){
        return (
            <div className="home_content">
                <div className="card_content">
                    <div className="card_box" id="ewmimg_box">
                    		<Card  bodyStyle={{ padding: 0 }}>
                            <div className="custom-card">
                              <h3>微信客服</h3>
                              <p>jinniukefu</p>
                            </div>
                            <div className="custom-image">
                              <img alt="example" width="100%" src={wx} />
                            </div>
                        </Card>
                        <Card  bodyStyle={{ padding: 0 }}>
                            <div className="custom-card">
                              <h3>钉钉客服</h3>
                              <p>13121960260</p>
                            </div>
                            <div className="custom-image">
                              <img alt="example" width="100%" src={dd} />
                            </div>
                        </Card>
                        <Card bodyStyle={{ padding: 0 }}>
                            <div className="custom-card">
                              <h3>旺旺客服</h3>
                              <p><a target="_blank" href="http://www.taobao.com/webww/ww.php?ver=3&touid=%E5%85%A8%E8%83%BD%E4%BF%83%E9%94%80%E6%8F%92%E4%BB%B6&siteid=cntaobao&status=1&charset=utf-8"><Icon type="aliwangwang"/>全能促销插件</a></p>
                            </div>
                            <div className="custom-image">
                              <img alt="example" width="100%" src={ww} />
                            </div>
                        </Card>
                        
                        
                    </div>
                </div> 
                <div className="data_contnet">
                    <div className="data_box">
                        <h3>我的运营数据</h3>
                        <ul className="data_info">
                            <li>
                                <div className="box_top">
                                    <p className="top_data">{this.state.merNumNew}</p>
                                    <p className="top_title">今日新增会员(人)</p>
                                </div>
                                <div className="box_left">
                                    <p className="top_data">{this.state.merNumTotal}</p>
                                    <p className="top_title">总会员</p>
                                </div>
                                <div className="box_right">
                                    <p className="top_data">{this.state.tradeTwiceUser} ({Number(this.state.merNumTotal)!='0'?(((this.state.tradeTwiceUser)/Number(this.state.merNumTotal)*100)).toFixed(2):0}%)</p>
                                    <p className="top_title">交易二次以上的会员</p>
                                </div>
                            </li>
                            <li>
                                <div className="box_top">
                                    <p className="top_data">{this.state.consumptionToday}</p>
                                    <p className="top_title">今日收益(元)</p>
                                </div>
                                <div className="box_left">
                                    <p className="top_data">{this.state.couponTaken}</p>
                                    <p className="top_title">今日领券量(张)</p>
                                </div>
                                <div className="box_right">
                                    <p className="top_data">{this.state.couponUse}</p>
                                    <p className="top_title">当前活动个数</p>
                                </div>
                            </li>
                            <li>
                                <div className="box_top">
                                    <p className="top_data">{this.state.couponProfit}</p>
                                    <p className="top_title">昨日收益(元)</p>
                                </div>
                                <div className="box_left">
                                    <p className="top_data">{this.state.consumptionTotal}</p>
                                    <p className="top_title">累计收益金额(元)</p>
                                </div>
                                <div className="box_right">
                                    <p className="top_data">{this.state.consumptionavg}</p>
                                    <p className="top_title">平均笔单价(元)</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div className="act_content">
                    <h3>场景营销</h3>
                    <div className="act_box">
                        <Card bodyStyle={{ padding: 0 }}>
                            <Link to="/paygive">
                                <div className="custom-card">
                                  <h3>消费送</h3>
                                  <p>支付即赠券,闪速发展会员</p>
                                </div>
                                <div className="custom-image">
                                  <img alt="example"  src={xs} />
                                </div>
                                <ul className="act_data">
                                    <li>
                                        <p className="act_info_data">36085 次</p>
                                        <p>累计使用</p>
                                    </li>
                                    <li>
                                        <p className="act_info_data">10825 元</p>
                                        <p>营销平均收益</p>
                                    </li>
                                </ul>
    							<Button type="primary">立即使用</Button>
                            </Link>
                        </Card>
                        <Card  bodyStyle={{ padding: 0 }}>
                            <Link to="/payfullgive">
                                <div className="custom-card">
                                  <h3>满返券</h3>
                                  <p>消费满额赠券,提升桌均</p>
                                </div>
                                <div className="custom-image">
                                  <img alt="example"  src={mf} />
                                </div>
                                <ul className="act_data">
                                    <li>
                                        <p className="act_info_data">4151 次</p>
                                        <p>累计使用</p>
                                    </li>
                                    <li>
                                        <p className="act_info_data">1.6万元</p>
                                        <p>营销平均收益</p>
                                    </li>
                                </ul>
                                <Button type="primary">立即使用</Button>
                            </Link>
                        </Card>
                        <Card  bodyStyle={{ padding: 0 }}>
                            <Link to="/pullnew">
                                <div className="custom-card">
                                  <h3>拉新</h3>
                                  <p>多渠道流量曝光,将未到店消费顾客变成会员</p>
                                </div>
                                <div className="custom-image">
                                  <img alt="example"  src={lx} />
                                </div>
                                <ul className="act_data">
                                    <li>
                                        <p className="act_info_data">801 次</p>
                                        <p>累计使用</p>
                                    </li>
                                    <li>
                                        <p className="act_info_data">2.36万元</p>
                                        <p>营销平均收益</p>
                                    </li>
                                </ul>
                                <Button type="primary">立即使用</Button>
                            </Link>
                        </Card>
                    </div>
                </div>
            </div>
        )
    }
}
