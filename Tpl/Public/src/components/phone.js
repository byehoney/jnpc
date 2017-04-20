import React from 'react';
import {Button,Row,Col} from 'antd';
import moment from 'moment';
import phone from '../images/phone1.png';
class Phone extends React.Component{
	constructor (props) {
		super(props)
		this.state={

		}
	}

	

	render () {
		return (
			<div className="phone" style={{width:240,float:'left',textAlign:'center'}}>
				<p style={{textAlign:'center',marginTop:75,color:'#fff'}}>券详情</p>
				<img style={{width:40,height:40,marginTop:3}} src={this.props.imageUrl}  alt="" />
				{
					this.props.cType=="代金券"?<p>{this.props.cMoney}元{this.props.cType}</p>:<p>{this.props.cName}</p>
				}
				<p style={{margin:'3px 0'}}><Button type="primary" >去买单</Button></p>
				{
					this.props.dateType=="相对日期"?<p>有效期第{this.props.startDay}天 至 第{this.props.endDay}天</p>:<p style={{width:'90%',margin:'0 auto',fontSize:'12px'}}>有效期{moment(this.props.startDate).format('YYYY-MM-DD')} 至 {moment(this.props.endDate).format('YYYY-MM-DD')}</p>
				}
				
				{
					this.props.cType=='代金券'?<p>满{this.props.pMoney}元可用</p>:""
				}
			</div>
		)
	}
}
export default Phone;