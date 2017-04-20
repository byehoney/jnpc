'use strict';

import React from 'react';
import ReactDom from 'react-dom';
import { Router, Route, Link, hashHistory, IndexRoute, Redirect, IndexLink} from 'react-router';
import { Layout } from 'antd';




const Content = Layout.Content;



// 引入Antd的导航组件
import { Menu, Icon } from 'antd';
const SubMenu = Menu.SubMenu;

// 引入单个页面（包括嵌套的子页面）
import home from './home.js';
import manage from './manage.js';
import paygive from './paygive.js';
import payfullgive from './payfullgive.js';
import pullnew from './pullnew.js';
import report from './report.js';
import plan from './plan.js';
import logo from '../images/logo3.png';
let routeMap = {
    '/home': '0',
    '/manage': '1',
    '/myForm': '2',
    '/myProgress': '3',
    '/myCarousel': '4',
    '/report': '1',
    '/plan':'1'
};
var that="";
// 配置导航
class Sider extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            current: ''
        };
    }
    that=this;
    handleClick(e) {
        this.setState({
            current: e.key
        });
    }

    componentWillMount() {
        var route = this.props.location.pathname;
        console.log(route)
        this.setState({
            current: routeMap[route] || '0'
        });
    }

    render() {
        return (
        	
            <div> 
            	<div className="nav_wrapper">	
                    <Menu 
                        onClick={this.handleClick.bind(this)}
                       
                        defaultSelectedKeys={[this.state.current]}
                        mode="horizontal"
                    >
                            <Menu.Item key="0"><Link activeClassName="active" to="/home">金牛</Link></Menu.Item>
                            <Menu.Item key="1"><Link activeClassName="active"  to="/manage">活动管理</Link></Menu.Item>
                       
                    </Menu>
                   
                    <div className="company">
                        <img className="logo" src={logo} alt="" />
                    </div>
                    
                </div>
                
                
                
                <Content>
                    <div className="">
                        { this.props.children }
                    </div>
                </Content>


            </div>
        )
    }
}

const onEnterRouter = () => {
	let appurl = window.location.href;
	let bodt_height = document.getElementById('app').offsetHeight;
	console.log(bodt_height)
	document.getElementsByTagName('body')[0].style.overflowY="hidden";
	if( appurl.indexOf('report') != -1 ){
		window.parent.postMessage('{"pageHeight":2400}','*');
	}else{
		window.parent.postMessage('{"pageHeight":1200}','*');
	}
	
}
// 配置路由
ReactDom.render((
    <Router history={hashHistory} >
        <Route path="/" component={Sider} >
            <IndexRoute component={home} />
            <Route path="home" component={home} onEnter={onEnterRouter}/>
            <Route path="manage" component={manage} onEnter={onEnterRouter}/>
			<Route path="paygive" component={paygive} onEnter={onEnterRouter}/>
			<Route path="payfullgive" component={payfullgive} onEnter={onEnterRouter}/>
			<Route path="pullnew" component={pullnew} onEnter={onEnterRouter}/>
            <Route path="plan" component={plan} onEnter={onEnterRouter}/>
            <Route path=":rid" component={report} onEnter={onEnterRouter}/>
        </Route>
    </Router>
), document.getElementById('app'));
