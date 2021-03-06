<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 审核管理--AppStore审核
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class AuditAppstoreController extends ComController {
    public function index(){
        $model = M("appstore");
        $map['audit_status'] = array('in','0,1,2');
        $list = $this->_list($model,$map,'10',"audit_status asc,app_uploadtime desc");
        $this -> assign("list",$list);
        $this->display();
    }
    public function detail(){
        $ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('每次只能查询一个apk对应的详情');
            die();
        }
        $model = D("appstore");
        $map['id'] = array('in',$ids);
        $list = $model->where($map)->find();
        if ($list['maclist'] == 'all') {
            $list['maclist'] = '所有Mac';
        }
        $this->assign('list',$list);
        $this->display();
    }
    public function audit(){
        $this->changeAuditStatus(2);
    }
    public function unaudit(){
        $this->changeAuditStatus(1);
    }
    public function changeAuditStatus($audit_status){
        if(empty($_REQUEST['ids'])){
            $this->error("至少选择一条记录");
            die();
        }
        $idsstr = implode(',', $_REQUEST['ids']);
        $model = D("appstore");
        $map['id'] = array("in",$_REQUEST['ids']);
        $data = array('audit_time'=>date("Y-m-d H:i:s"),'audit_status'=>$audit_status);
        $result = $model->where($map)->setField($data);
        if($result === false){
            $this->error("修改状态失败");
        }else{
            if($audit_status == 1){
               addAuditlog("APK审核未通过,数据ID为：".$idsstr,$resouce_type=7,$type=1);
            }elseif($audit_status == 2){
               addAuditlog("APK审核通过,数据ID为：".$idsstr,$resouce_type=7,$type=1);
            }
            $this->success("修改状态成功");
        }
    }
}