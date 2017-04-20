import React from 'react';
import {Icon,Button,Row,Col} from 'antd';


class Plan extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      endTime:'2017-3-28',
      perNum:0,
      couponNum:0,
      aveNum:0,
      uPNum:0,
      uCNum:0,
      percent:0,
      day:0,
      hNum:0
    };
 }
  render() {
    return (
      <div className="plan">
        <Row>
          <Col span={3}> </Col>
          <Col span={8} style={{borderTop:'1px solid #ddd',position:'relative',top:16}}></Col>
          <Col span={2} style={{fontSize:'16px',textAlign:'center'}}>活动方案</Col>
          <Col span={8} style={{borderTop:'1px solid #ddd',position:'relative',top:16}}></Col>
        </Row>
        <Row style={{margin:'30px 0'}}>
        	<Col style={{fontSize:'16px'}}>
        		<Icon type="tag" style={{marginRight:20,verticalAlign:'middle'}} />活动方案设计
        	</Col>
        </Row>
        <Row style={{margin:'20px 0 10px 0',background:"#e9e9e9",padding:'10px'}}>
        	<Col span={24}>
        		<p>03月24日至04月22日期间，到店使用支付宝付款满10元的顾客，获赠1张1元的代金券，每人每天可获得1张，最多可获得1张。本活动的优惠券将直接放入顾客支付宝券包中。</p>
        	</Col>
        	<Col>
				    <p>代金券有效时间为：从发券第1天至第30天，满1元可用，限周一至周日00:00-23:59使用。</p>
        	</Col>
          <Col>
            <p>参与活动门店：全部门店</p>
          </Col>
          <Col>
            <p>可使用门店：全部门店</p>
          </Col>
          <Col>
            <p>顾客下次到店消费，使用支付宝付款自动核销，每次核销一张，优惠同时存在的情况下，优先核销力度大的一张券。</p>
          </Col>
        </Row>
      </div>
    );
  }
}


export default Plan;