import React from 'react';
import {Modal,Checkbox,Button,Form} from 'antd';
export default class HxShop extends React.Component  {
  // constructor(props) {
  //       super(props)
        
  // }
  // handleOk = (e) => {
  //   console.log(e);
  //   var that=this;
  //   console.log(that)
  // }
  // handleCancel = (e) => {
  //   console.log(e);
  //   this.setState({
  //     visible: false,
  //   });
  // }
  render() {
    return (
      <div>
        <Modal title="Basic Modal" visible={this.props.visible}
          onOk={this.props.handleUseShopOk} onCancel={this.props.handleUseShopCancel}
        >
          <p>some contents...hexiao </p>
          <p>some contents...</p>
          <p>some contents...</p>
        </Modal>
      </div>
    );
  }
}
