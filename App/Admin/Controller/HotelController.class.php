<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店信息控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_POST['hotelname'])) {
            $map['hotelname']=array("like","%".$_POST['hotelname']."%");
            $this->assign('hotelname', $_POST['hotelname']);
        }
        if (!empty($_POST['name'])) {
            $map['name']=array("like","%".$_POST['name']."%");
        }
        if (!empty($_POST['hid'])) {
            $map['hid']=array("like","%".$_POST['hid']."%");
            $this->assign('hid',$_POST['hid']);
        }
        if (!empty($_POST['provinceid'])) {
            $map['provinceid'] = $_POST['provinceid'];
            $this->assign("provinceid",$_POST['provinceid']);
        }else{
            $this->assign("provinceid",'');
        }
        if (!empty($_POST['cityid'])) {
            $map['cityid'] = $_POST['cityid'];
            $this->assign("cityid",$_POST['cityid']);
        }else{
            $this->assign("cityid",'');
        }
        return $map;
    }
    //列表
    public function index(){
        $model = M(CONTROLLER_NAME);
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition){
            $map['hid'] = $hid_condition;
        }
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);
        $this -> display();
    }
    //添加
    public function add(){
        $category = M('hotel')->field('id,pid,name')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$id \$selected>\$spacer\$name</option>"; //生成的形式
        $category = $tree->get_tree(0,$str, 0);
        $this->assign('pHotel',$category);
        //皮肤
        $HotelSkin = D("HotelSkin");
        $list = $HotelSkin->where("status=1")->order("id asc")->field('id,name')->select();
        $this->assign("skinlist",$list);
        //launcher皮肤
        $launcherSkin = D("hotel_launcher_web");
        $launcherList = $launcherSkin->where('status=1')->field('id,name')->select();
        $this->assign('launcherSkin',$launcherList);
        //省份
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);

        $this->display();
    }
    //修改
    public function edit(){
        $model = M(CONTROLLER_NAME);
        $ids = $_REQUEST['ids'];//只能同时修改一条记录
        if(count($ids)<>1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        $where['zxt_hotel.id'] = array('in',$ids);
        $var = $model->field("zxt_hotel.*,zxt_hotel_user.huid")->where($where)->join('left join zxt_hotel_user ON zxt_hotel.id=zxt_hotel_user.hotel_id')->select();
        $var = $var['0'];
        if(!$var){
            $this->error('参数错误！');
        }
        $this->assign('vo',$var);

        //客户上级不能是以本身为根节点的树上的菜单
        $leafIds = $this->getIdTree($model,$ids[0]); //获取该记录的子记录的id
        $map['id']=array("not in",$leafIds);
        $category = $model->where($map)->field('id,pid,name')->order('hid asc')->select();//子集不能作其上级
        $tree = new Tree($category);
        $str = "<option value=\$id \$selected>\$spacer\$name</option>"; //生成的形式
        $category = $tree->get_tree(0,$str, $var['pid']);//生成树型结构的代码
        $this->assign('pHotel',$category);
        //皮肤
        $HotelSkin = D("HotelSkin");
        $list = $HotelSkin->where("status=1")->order("id asc")->field('id,name')->select();
        $this->assign("skinlist",$list);
        //launcher网页版皮肤
        $launcherSkin = D("hotel_launcher_web");
        $launcherList = $launcherSkin->where('status=1')->field('id,name')->select();
        $this->assign('launcherSkin',$launcherList);
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);
        $citylist = $Region->where('pid='.$var['provinceid'])->order("sort asc")->field('id,name')->select();//所有城市
        $this->assign("clist",$citylist);
        $this -> display();
    }
    //保存
    public function update(){
        $data['id'] = I('post.id','','intval');
        $data['pid'] = $_POST['pid'];
        $data['name'] = I('post.name','','strip_tags');
        $data['hotelname'] = I('post.hotelname','','strip_tags');
        $data['hid'] =strtoupper(I('post.hid','','strip_tags'));
        $data['skinid'] = I('post.skinid','','intval');
        $data['launcher_skinid'] = I('post.launcher_skinid','','intval');
        $data['provinceid'] = intval($_POST['provinceid']);
        $data['cityid'] = intval($_POST['cityid']);
        $data['manager'] = I('post.manager','','strip_tags');
        $data['mobile'] = I('post.mobile','','strip_tags');
        $data['tel'] = I('post.tel','','strip_tags');
        $data['address'] = I('post.address','','strip_tags');
        $data['space'] = I('post.space','500','strip_tags');
        $data['intro'] = I('post.intro','','strip_tags');
        $data['update_time'] = time();
        if(!$data['name'] or !$data['hotelname'] or !$data['hid'] ){
            $this->error('警告！酒店客户名称、酒店名称、酒店编号为必填项目。');
        }
        if($data['id']){//修改
            if ($data['pid']==$data['id']) {
                $this->error('警告！客户上级不能选本身！');
            }
            $data['isinsert'] = I('post.isinsert');

            
            if (empty($data['isinsert'])) {//判断是否需要新增中间表和member表 antu_group_access表
                M('hotel')->data($data)->where('id='.$data['id'])->save();
            }else{
                $userData['user'] = I('post.user','','strip_tags');
                $userData['phone'] = I('post.phone','','','intval');
                $password = I('post.password','','strip_tags');
                $userData['password'] = password($password);
                if(!$userData['user'] or !$userData['phone'] or !$userData['password']){
                    $this->error("警告 管理员账号密码电话号码为必填项目");
                }
                $data['status'] = 0;
                $data['create_time'] = time();
                $userData['t'] = time();
                $huDate['hid'] = $userData['hid'] = $data['hid'];
                $model = D();

                $model->startTrans();
                $save = M('hotel')->data($data)->where('id='.$data['id'])->save();
                $user_id = D('member')->data($userData)->add();
                $huDate['hotel_id'] = $data['id'];
                $huDate['user_id'] = $user_id;
                $hu = D('hotel_user')->data($huDate)->add();
                $access_id = M('auth_group_access')->data(array('group_id'=>3,'uid'=>$user_id))->add();
                if($save===false or !$user_id or !$hu or !$access_id){
                    $model->rollback();
                    $this->error('修改失败',U('index'));
                    die();
                }else{
                    $model->commit();
                }
            }
            addlog('修改酒店信息，酒店ID：'.$data['id']);
        }else{
            $rules = array(
                array('hid','','酒店编号已经存在！',0,'unique',1),
            );
            $userData['user'] = I('post.user','','strip_tags');
            $userData['phone'] = I('post.phone','','','intval');
            $password = I('post.password','','strip_tags');
            $userData['password'] = password($password);
            if(!$userData['user'] or !$userData['phone'] or !$userData['password']){
                $this->error("警告 管理员账号密码电话号码为必填项目");
            }
            $data['status'] = 0;
            $data['create_time'] = time();
            $userData['t'] = time();
            $userData['hid'] = $data['hid'];
            $model = D();
            $hotel_m = D('hotel');
            $model->startTrans();
            $validate = $hotel_m->validate($rules)->create();
            if(!$validate){
                $this->error($hotel_m->getError());
                die();
            }
            $hotel_id = D('hotel')->data($data)->add();
            $user_id = D('member')->data($userData)->add();
            $huDate['hotel_id'] = $hotel_id;
            $huDate['user_id'] = $user_id;
            $huDate['hid'] = $data['hid'];
            $hu = D('hotel_user')->data($huDate)->add();
            $access_id = M('auth_group_access')->data(array('group_id'=>3,'uid'=>$user_id))->add();
            //新增酒店的时候广告容量添加
            $allAdVolume = M("Ad")->field('SUM(size)')->where(array('hidlist'=>'all'))->find();
            $Volumedata['hid'] = $data['hid'];
            $Volumedata['ad_size'] = $allAdVolume['sum(size)'];
            $Volumedata['content_size'] = 0.000;
            $Volumedata['topic_size'] = 0.000;
            $Volumedata['devicebg_size'] = 0.000;
            $Volumeid = M("hotel_volume")->data($Volumedata)->add();
            if(!$hotel_id or !$user_id or !$hu or !$access_id or !$Volumeid){
                $model->rollback();
                $this->success('添加酒店失败',U('index'));
                die();
            }else{
                $model->commit();
            }
            addlog('添加酒店ID：'.$hotel_id.'  添加用户ID'.$user_id);
        }

        $this->success('恭喜，操作成功！',U('index'));
    }
    //获取城市列表
    public function get_city(){
        $pid = $_REQUEST['pid'];
        $Region = D("Region");
        $citylist = $Region->where('pid='.$pid)->order("sort asc")->field('id,name')->select();
        echo json_encode($citylist);
    }
    //获取该记录的子记录的id
    private function getIdTree($model,$pid=0,$ids=''){
        $volist = $model->where('pid='.$pid)->select();
        if(!empty($volist)){
            foreach ($volist as $value) {
                $idTree = $this->getIdTree($model,$value['id'],$value['id']);
                $ids .= ','.$idTree;
            }
        }
        return $ids;
    }
    public function copy(){
        $ids = $_REQUEST['copyid'];
        $hid = I('post.hid','','strip_tags');
        if(empty($hid)){
            $this->error('请输入酒店编号');
            die();
        }
        $model = D(CONTROLLER_NAME);
        $rules = array(
            array('hid','','酒店编号已经存在！',0,'unique',1),
        );
        $validate = $model->validate($rules)->create();
        if(!$validate){
            $this->error($model->getError());
            die();
        }
        $map['id'] = $ids;
        $list = $model->where($map)->select();
        if(!empty($list['0'])){
            $a = $list['0'];
            unset($a['id']);
            $a['hid'] = $hid;
            $a['create_time'] = $a['update_time'] = time();
            $result = $model->data($a)->add();
            if($result === false){
                $this->error('复制失败');
                die();
            }else{
                $this->success('复制成功');
                die();
            }
        }else{
            $this->error('复制失败');
            die();
        }
    }
    public function _usermap(){
        $map = array();
        $username = trim(I('post.username','','strip_tags'));
        if(!empty($username)){
            $map['user']=array("like","%".$username."%");
        }
        return $map;
    }
    public function hoteluser(){
        $prefix = C('DB_PREFIX');
        $hotelGroup = C('HOTEL_GROUP');
        $auth_group_access_model = D("auth_group_access");
        $map = $this->_usermap();
        $map["{$prefix}auth_group_access.group_id"] = array('in',$hotelGroup);
        $count = $auth_group_access_model->where($map)->count();
        $Page = new\Think\Page($count,$pagesize=10,$_GET);//实例化分页类 
        $pageshow = $Page -> show();//分页显示输出                                                 //
        $list = $auth_group_access_model->field("{$prefix}member.*")->join("{$prefix}member ON {$prefix}auth_group_access.uid = {$prefix}member.uid")->where($map)->limit($Page ->firstRow.','.$Page -> listRows)->select();
        $this -> assign("page",$pageshow);//赋值分页输出
        $this->assign('list',$list);
        $this->display();
    }

    public function searchhotel(){
        if(!empty($_POST['hid'])){
            $hid = trim(I('post.hid','','strip_tags'));
            if(empty($hid)){
                return false;
                die();
            }
            $model = D(CONTROLLER_NAME);
            $map['hid'] = array('eq',$hid);
            $list = $model->field('id,hid,hotelname')->where($map)->find();
            echo json_encode($list);
            die();
        }
    }
    public function insertHotelUser(){
        $model = D("hotel_user");
        $data['user_id'] = I('post.userid','','strip_tags');
        $data['hotel_id'] = I('post.hotelid','','strip_tags');
        $data['hid'] = I('post.inserthid','','strip_tags');
        if(empty($data['user_id'])){
            $this->error('未能获取到酒店管理员ID');
            die();
        }
        $map['hid'] = $data['hid'];
        $map['user_id'] = $data['user_id'];
        $have = $model->where($map)->find();
        if(!empty($have)){
            $this->error('该酒店已归属');
            die();
        }
        $result = $model->data($data)->add();
        if($result){
            $this->success('酒店归属成功');
        }else{
            $this->error('酒店归属失败');
        }
    }
    public function _showbelonghotel(){
        $ids = $_REQUEST['ids'];
        if(count($ids)>1){
            $this->error('只能同时查看一位管理员的所属酒店');
            die();
        }elseif(count($ids)<1){
            $this->error('请选择一名酒店管理员');
            die();
        }
        $model = D("hotel_user");
        $prefix = C('DB_PREFIX');
        $map["{$prefix}hotel_user.user_id"] = array('eq',$ids['0']);
        $field = "{$prefix}hotel_user.huid,{$prefix}hotel.id,{$prefix}hotel.hid,{$prefix}hotel.hotelname,{$prefix}hotel.manager,{$prefix}hotel.mobile,{$prefix}hotel.provinceid,{$prefix}hotel.cityid,{$prefix}hotel.status";
        $list = $model->field($field)->join("{$prefix}hotel ON {$prefix}hotel_user.hotel_id = {$prefix}hotel.id")->where($map)->select();
        return $list;
    }
    public function checkbelong(){
        $list = $this->_showbelonghotel();
        $this->assign("list",$list);
        $this->display();
    }
    public function showdelbelong(){
        $list = $this->_showbelonghotel();
        $this->assign("list",$list);
        $this->display();
    }
    public function deletebelong(){
        $ids = $_REQUEST['ids'];
        if(count($ids)<1){
            $this->error('至少要选择一条数据');
            die();
        }
        $model = D("hotel_user");
        $map['huid'] = array('in',$ids);
        if($model->where($map)->delete()){
            $this->success("删除成功","hoteluser");
        }else{
            $this->error('删除失败',"hoteluser");
        }
    }
    public function relatetopic(){
        $ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('请选择一家酒店进行专题关联');
            die();
        }
        $model = D("HotelTopic");
        $hotelList = D("Hotel")->where('id="'.$ids['0'].'"')->field('hid')->find();//HID
        $topicgroupList = D("TopicGroup")->field('id,title')->where('status=1')->select();//一级栏目
        $HotelTopicList = $model->where('hid="'.$hotelList['hid'].'"')->select();
        if(!empty($HotelTopicList)){
            $topicname='';
            foreach ($HotelTopicList as $key => $value) {
                $voo = array();
                $voo = D("TopicGroup")->where('id="'.$value['topic_id'].'"')->field('title')->find();
                $topicname = $topicname.','.$voo['title'];
                $topic_id_arr[] = $value['topic_id'];
            }
            $topic_id_str = implode(",", $topic_id_arr);
            foreach ($topicgroupList as $k => $v) {
                if(in_array($v['id'], $topic_id_arr)){
                    $topicgroupList[$k]['isselect'] = 1;
                }else{
                    $topicgroupList[$k]['isselect'] = 0;
                }
            }
            $this->assign("topicName",trim($topicname,','));
        }else{
            $this->assign("topicName","--");
        }
        $this->assign('topic_id_str',$topic_id_str);
        
        $this->assign("hid",$hotelList['hid']);
        $this->assign('topiclist', $topicgroupList);
        $this->display();
    }
    //保存关联通用栏目
    public function saverelatetopic(){
        $hid = I('post.hid','','strip_tags');
        $topic_list = I('post.ids','','strip_tags');
        $pass_topic_list = I('post.passids','','strip_tags');

        $topic_list_arr = explode(",", $topic_list);
        $pass_topic_list_arr = explode(",", $pass_topic_list);
        $add_id_arr = array_diff($topic_list_arr, $pass_topic_list_arr);
        $del_id_arr = array_diff($pass_topic_list_arr, $topic_list_arr);

        if(empty($hid)){
            $this->error('系统错误：未能获取酒店编号');
        }
        $model = D("hotel_topic");
        $model->startTrans();
        $topic_list_arr = array();

        //操作hotel_topic表
        $hoteltopicResult = true;
        $addHoteltopicResult = true;
        $delHoteltopicResult = $model->where('hid="'.$hid.'"')->delete();//删除操作
        if(!empty($topic_list)){
            $topic_list_arr = explode(",", $topic_list);
            $i = 0;
            foreach ($topic_list_arr as $key => $value) {
                $addHoteltopicDate[$i]['hid'] = $hid;
                $addHoteltopicDate[$i]['topic_id'] = $value;
                $i++;
            }
            $addHoteltopicResult = D("hotel_topic")->addAll($addHoteltopicDate);
        }
        if($addHoteltopicResult===false || $delHoteltopicResult===false){
            $hoteltopicResult = false;
        }
        //获取资源大小
        $allsize = 0;
        if(!empty($topic_list_arr)){
            foreach ($topic_list_arr as $key => $value) {
                $allsize += $this->searchTopicAndResource($value);//累加各个栏目的资源大小
            }
        }
        if(M("hotel_volume")->where('hid="'.$hid.'"')->count()){
            $updatesize = D("hotel_volume")->where('hid="'.$hid.'"')->setField("topic_size",$allsize);
        }else{
            $arrdata['hid'] = $hid;
            $arrdata['content_size'] = 0.00;
            $arrdata['topic_size'] = $allsize;
            $arrdata['ad_size'] = 0.00;
            $updatesize = M("hotel_volume")->data($arrdata)->add();
        }
        //更新资源表
        $addgetName = $this->searchTopicAndName($add_id_arr);
        $delgetName = $this->searchTopicAndName($del_id_arr);
        $allresourceResult = true;
        $addAllresourceResult = true;
        $delAllresourceResult = true;
        if(!empty($addgetName['name'])){
            $count = count($addgetName['name']);
            for ($i=0; $i < $count; $i++) { 
                $addlist[$i]['hid'] = $hid;
                $addlist[$i]['name'] = $addgetName['name'][$i];
                $addlist[$i]['type'] = $addgetName['type'][$i];
                $addlist[$i]['timeunix'] = time();
                $addlist[$i]['time'] = date("Y-m-d H:i:s");
                $addlist[$i]['web_upload_file'] = $addgetName['filepath'][$i];
            }
            $addAllresourceResult = D("hotel_allresource")->addAll($addlist);
        }
        if(!empty($delgetName['name'])){
            $delAllResourceMap['name'] = array('in',$delgetName['name']);
            $delAllresourceResult = D("hotel_allresource")->where($delAllResourceMap)->delete();
        }
        if($addAllresourceResult===false || $delAllresourceResult===false){
            $allresourceResult = false;
        }
        if($hoteltopicResult!== false && $updatesize !== false && $allresourceResult!==false){
            $model->commit();
            $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$hid.'.txt';
            $this->fileputXml(D("hotel_allresource"),$hid,$xmlFilepath);
            $this->success('关联成功','index');
        }else{
            $model->rollback();
            $this->error('关联失败','index');
        }
    }
    //酒店管理员账号
    public function checkUsername(){
        $user = I('post.user','','strip_tags');
        if(empty($user)){
            echo false;
        }
        $map['user'] = $user;
        $model = D("member");
        $result = $model->where($map)->find();
        if(!empty($result)){
            echo 0;
        }else{
            echo 1;
        }
    }
    //酒店删除 需要删除volume表
    public function delete(){
        $ids = $_REQUEST['ids'];
        if(count($ids) !=1){
            $this->error('系统提示：仅能同时进行一目标进行删除操作');
        }
        $model = D("hotel");
        $map['id'] = $ids[0];
        $hotelhid_arr = $model->field('hid')->where($map)->find();
        $hotelHid = $hotelhid_arr['hid'];
        $arrmap['hid'] = $hotelHid; //酒店HID
        $hotel_volume = D("hotel_volume")->where($arrmap)->find();
        if(!empty($hotel_volume) && $hotel_volume['content_size']>0){
            $this->error("系统提示，删除目标含有资源，请先删除资源再进行删除操作");
            die();
        }

        $model->startTrans();
        $delHotelvolumeResult = M("hotel_volume")->where($arrmap)->delete();
        if($model->where($map)->delete() && $delHotelvolumeResult!==false){
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $model->commit();
            $this->success('恭喜，删除成功！');
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！');
        }
    }

    /*新增 集团栏目关联功能  20170630*/
    //关联集团栏目
    public function relatechg(){
        $ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('请选择集团子酒店进行专题关联');
            die();
        }

        $hotelMap['id'] = $ids['0'];
        $hotel = D("hotel")->where($hotelMap)->field('id,hid,pid,space')->find();
        $hid = $hotel['hid'];
        $phidMap['id'] = $hotel['pid']; 
        $photel = D("hotel")->where($phidMap)->field('id,hid,pid')->find();
        $phid = $photel['hid'];

        //查询酒店剩余容量值(不包括已选集团栏目)
        $volumeMap['hid'] = $hid;
        $volumeList = D("hotel_volume")->where($volumeMap)->find();
        if(!empty($volumeList)){
            $residueV = $hotel['space'] - $volumeList['content_size'] - $volumeList['topic_size'] - $volumeList['ad_size'] - $volumeList['devicebg_size'];
        }else{
            $residueV = $hotel['space'];
        }
        $chgHadV = 0; //已选集团栏目所占容量

        //查询该酒店下的集团通用栏目
        $chgCategoryMap['hid'] = $phid;
        $chgCategoryMap['pid'] = 0;
        $chgCategoryMap['status'] = 1;
        $categoryList = D("hotel_chg_category")->where($chgCategoryMap)->field('id,hid,name,all_size')->select();

        //查询已关联集团通用栏目
        $hadchgReleateMap['zxt_hotel_chglist.hid'] = $hid;
        $hadchgReleateMap['pid'] = 0;
        $field = "zxt_hotel_chglist.chg_cid,zxt_hotel_chg_category.id,zxt_hotel_chg_category.name,zxt_hotel_chg_category.all_size";
        $releateList = D("hotel_chglist")->where($hadchgReleateMap)->join("zxt_hotel_chg_category ON zxt_hotel_chglist.chg_cid=zxt_hotel_chg_category.id")->field($field)->select();

        if(!empty($releateList)){ //有绑定执行赋值操作
                
            foreach ($releateList as $k => $v) {
                $showlist .= $v['name'];
                $showlist .= '('.$v['all_size'].'MB)&nbsp;&nbsp;&nbsp;&nbsp;';
                $chglist_id_arr[] = $v['chg_cid']; //已选集团栏目id数组
                $chgHadV += $v['all_size'];
            }
            $chglist_id = implode(',', $chglist_id_arr);

            foreach ($categoryList as $key => $value) {
                $isselect = 0;

                foreach ($releateList as $key2 => $value2) {
                    if($value['id'] == $value2['id']){
                        $isselect = 1;
                    }
                }

                if($isselect==1){
                    $categoryList[$key]['isselect'] = 1;
                }else{
                    $categoryList[$key]['isselect'] = 0;
                }
            }
        }

        //最终可用容量
        $residueSize = $residueV - $chgHadV;

        $this->assign('hid',$hid);  //本身（子酒店）hid
        $this->assign('phid',$phid);  //父级hid
        $this->assign('releateList',$releateList); //
        $this->assign('showlist',$showlist); //textarea显示列表
        $this->assign('selectlist',$categoryList);  //弹窗勾选列表
        $this->assign('chglist_id',$chglist_id); //已选择集团栏目id列表
        $this->assign('residueV',$residueV); //剩余容量(不包括所选栏目)
        $this->assign('chgHadV',$chgHadV); //所选栏目已用容量
        $this->assign('residueSize',$residueSize);
        $this->display();

    }

    //更新关联集团
    public function updaterelatechg(){

        $hid = I('post.hid','','strip_tags');
        $phid = I('post.phid','','strip_tags');
        $chglist_id = I('post.chglist_id','','strip_tags');
        $ids = I('post.ids','','strip_tags');
        $size = I('post.size','','strip_tags');

        if(empty($hid) || empty($phid)){
            $this->error('参数错误，请联系系统管理员',U("index"));
        }

        //查询酒店容量
        $hotelMap['hid'] = $hid;
        $hotel = D("hotel")->where($hotelMap)->field('space')->find(); 
        $hotelspace = floatval($hotel['space']); //酒店容量
        
        //查询已用容量（不包括关联集团） 
        $volumeMap['hid'] = $hid;
        $volumeVo = D("hotel_volume")->where($volumeMap)->find();  
        if(!empty($volumeVo)){
            $usedVolume = $volumeVo['content_size'] + $volumeVo['topic_size'] + $volumeVo['ad_size'] + $volumeVo['devicebg_size'];
        }else{
            $usedVolume = 0;
        }
        
        //查询选择的栏目的容量大小
        if(!empty($ids)){
            $id_arr = explode(',', $ids);
            $chgCategoryMap['id'] = array('in',$id_arr);
            $chgCategoryList = D("hotel_chg_category")->where($chgCategoryMap)->field('SUM(all_size)')->select();
            $chgVolume = floatval($chgCategoryList[0]['sum(all_size)']);
        }else{
            $chgVolume = 0;
        }

        if(($hotelspace-$usedVolume-$chgVolume)>0){ //有容量剩余 更新

            //判断关联集团栏目是否有变化 若有 找出异同
            if(!empty($chglist_id)){
                $chglistId_arr = explode(',', $chglist_id);//原关联栏目id组
                if(!empty($ids)){
                    $delId_arr = array_diff($chglistId_arr, $id_arr);
                    $addId_arr = array_diff($id_arr,$chglistId_arr);
                }else{ //为空时  全部删除
                    $delId_arr = $chglistId_arr;
                    $addId_arr = array();
                }

            }else{
                if(!empty($ids)){ //新操作有选定集团栏目
                    $delId_arr = array();
                    $addId_arr = $id_arr;
                }else{
                    $delId_arr = array();
                    $addId_arr = array();
                }
            }

            //删除（原有选定 现不选定）
            $result_del = true;
            $result_add = true;
            $model = D("hotel_chglist");
            $model->startTrans();
            $allresourceResult = true; //对allresource表的最终结果
            $delAllresourceResult = true;//对allresource表删除结果
            $addAllresourceResult = true; //对allresource表新增结果
            if(!empty($delId_arr)){
                $delMap['hid'] = $hid;
                $delMap['phid'] = $phid;
                $delMap['chg_cid'] = array('in',$delId_arr);
                $result_del = D("hotel_chglist")->where($delMap)->delete();

                //资源表allresource作删除
                $first_resource_del = $this->searchChgFcategoryAndResource($delId_arr);
                $second_resource_del = $this->searchChgScategoryAndResource($delId_arr);
                $del_name_arr = array();
                if(!empty($first_resource_del)){
                    foreach ($first_resource_del as $key => $value) {
                        $del_name_arr[] = $value['name'];
                    }
                }
                if(!empty($second_resource_del)){
                    foreach ($second_resource_del as $key => $value) {
                        $del_name_arr[] = $value['name'];
                    }
                }

                if(!empty($del_name_arr)){
                    $delAllResourceMap['name'] = array('in',$del_name_arr);
                    $delAllResourceMap['hid'] = $hid;
                    $delAllresourceResult = D("hotel_allresource")->where($delAllResourceMap)->delete();
                }
            }

            //新增 （原来没选 新选定）
            if(!empty($addId_arr)){
                $i = 0;
                foreach ($addId_arr as $key => $value) {
                    $addDate[$i]['hid'] = $hid;
                    $addDate[$i]['phid'] = $phid;
                    $addDate[$i]['chg_cid'] = intval($value);
                    $chg_cid_arr[] = intval($value);//chg_cid数组集合
                    $i++;
                }
                $result_add = D("hotel_chglist")->addAll($addDate);

                //资源表allresource做新增
                $addlist = array();
                $first_resource = $this->searchChgFcategoryAndResource($chg_cid_arr);
                $second_resource = $this->searchChgScategoryAndResource($chg_cid_arr);
                if(!empty($first_resource)){
                    $i = 0;
                    foreach ($first_resource as $fkey => $fvalue) {
                        $addlist[$i]['hid'] = $hid;
                        $addlist[$i]['name'] = $fvalue['name'];
                        $addlist[$i]['type'] = $fvalue['type'];
                        $addlist[$i]['timeunix'] = time();
                        $addlist[$i]['time'] = date("Y-m-d H:i:s");
                        $addlist[$i]['web_upload_file'] = $fvalue['web_upload_file'];
                        $i++;
                    }
                }
                if(!empty($second_resource)){
                    $j = count($addlist);
                    foreach ($second_resource as $skey => $svalue) {
                        $addlist[$j]['hid'] = $hid;
                        $addlist[$j]['name'] = $svalue['name'];
                        $addlist[$j]['type'] = $svalue['type'];
                        $addlist[$j]['timeunix'] = time();
                        $addlist[$j]['time'] = date("Y-m-d H:i:s");
                        $addlist[$j]['web_upload_file'] = $svalue['web_upload_file'];
                        $j++;
                    }
                }
                if($addlist){
                    $addAllresourceResult = D("hotel_allresource")->addAll($addlist);
                }
            }

            if($delAllresourceResult===false || $addAllresourceResult===false){
                $allresourceResult = false;
            }

            //添加到酒店容量表
            $hvMap['hid'] = $hid;
            if(M("hotel_volume")->where($hvMap)->count()){
                $sql = "UPDATE `zxt_hotel_volume` SET `chg_size`=".$size." where hid ='".$hid."'";
                $updatesize = D("hotel_volume")->execute($sql);
                // $updatesize = D("hotel_volume")->where($hvMap)->execute($sql);
                // $updatesize = D("hotel_volume")->where($hvMap)->setField("chg_size",$size);
            }else{
                $hvDate['hid'] = $data['hid'];
                $hvDate['content_size'] = 0.000;
                $hvDate['topic_size'] = 0.000;
                $hvDate['ad_size'] = 0.000;
                $hvDate['chg_size'] = $size;
                $updatesize = M("hotel_volume")->data($hvDate)->add();
            }

            if($result_del!==false && $result_add!==false && $updatesize!==false && $allresourceResult!==false){
                $model->commit();
                //allresource资源写入xml文件
                $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$hid.'.txt';
                $xmlResult = $this->fileputXml(D("hotel_allresource"),$hid,$xmlFilepath);
                $this->success('操作成功',U('index'));
            }else{
                $model->rollback();
                $this->error('操作失败',U('index'));
            }
        }
        else{
            $this->error('关联集团栏目所需容量超过剩余容量,请重新选择关联集团栏目!',U('index'));
        }
        // var_dump($_POST,$hotelspace,$usedVolume,$chgVolume,$delId_arr,$addId_arr);
    }

    //找出chg一级栏目及其资源
    public function searchChgFcategoryAndResource($chg_cid_arr = array()){
        if(empty($chg_cid_arr)){
            return array();
        }
        $list = array();
        $categoryMap['id'] = $resourceMap['cid'] = array('in',$chg_cid_arr);
        $categoryList = D("hotel_chg_category")->where($categoryMap)->field('filepath')->select();//一级栏目
        $resourceList = D("hotel_chg_resource")->where($resourceMap)->field('file_type,filepath,icon')->select();// 一级栏目对应的资源
        
        if(!empty($categoryList)){
            $i = 0;
            foreach ($categoryList as $ckey => $cvalue) {
                if(!empty($cvalue['filepath'])){
                    $list[$i]['type'] = 2; //图片类型
                    $getName = explode("/", $cvalue['filepath']);
                    $list[$i]['name'] = $getName[count($getName)-1];
                    $list[$i]['web_upload_file'] = $cvalue['filepath'];
                    $i++;
                }
            }
        }
        if(!empty($resourceList)){
            $j = count($list);
            foreach ($resourceList as $rkey => $rvalue) {
                $list[$j]['type'] = $rvalue['file_type'];
                $getName = explode("/",$rvalue['filepath']);
                $list[$j]['name'] = $getName[count($getName)-1];
                $list[$j]['web_upload_file'] = $rvalue['filepath'];
                $j++;
            }

            $k = count($list);
            foreach ($resourceList as $ikey => $ivalue) {
                if(!empty($ivalue['icon'])){
                    $list[$k]['type'] = 2;
                    $getName = explode("/", $ivalue['icon']);
                    $list[$k]['name'] = $getName[count($getName)-1];
                    $list[$k]['web_upload_file'] = $ivalue['icon'];
                }
                $k++;
            }
        }

        return $list;
    }

    //找出chg二级栏目及其资源
    public function searchChgScategoryAndResource($chg_cid_arr = array()){
        if(empty($chg_cid_arr)){
            return array();
        }

        $list = array();
        $second_id_arr = array();//二级栏目id数组集合
        $categoryMap['pid'] = array('in',$chg_cid_arr);
        $categoryList = D("hotel_chg_category")->where($categoryMap)->field('id,filepath')->select();//二级栏目

        if(!empty($categoryList)){
            $i = 0;
            foreach ($categoryList as $ckey => $cvalue) {
                $second_id_arr[] = $cvalue['id'];
                $list[$i]['type'] = 2;//图片资源
                $getName = explode("/", $cvalue['filepath']);
                $list[$i]['name'] = $getName[count($getName)-1];
                $list[$i]['web_upload_file'] = $cvalue['filepath'];
                $i++;
            }
        }

        if(!empty($second_id_arr)){
            $resourceMap['cid'] = array('in',$second_id_arr);
            $resourceList = D('hotel_chg_resource')->where($resourceMap)->field('file_type,filepath,icon')->select();//二级栏目对应的资源
            $j = count($list);
            foreach ($resourceList as $rkey => $rvalue) {
                $list[$j]['type'] = $rvalue['file_type'];
                $getName = explode("/",$rvalue['filepath']);
                $list[$j]['name'] = $getName[count($getName)-1];
                $list[$j]['web_upload_file'] = $rvalue['filepath'];
                $j++;
            }

            $k = count($list);
            foreach ($resourceList as $ikey => $ivalue) {
                if(!empty($ivalue['icon'])){
                    $list[$k]['type'] = 2;
                    $getName = explode("/", $ivalue['icon']);
                    $list[$k]['name'] = $getName[count($getName)-1];
                    $list[$k]['web_upload_file'] = $ivalue['icon'];
                }
                $k++;
            }
        }

        return $list;
    }

    //找出通用栏目下资源和栏目图标总大小
    public function searchTopicAndResource($id=false){
        //一级栏目
        $topic_group = D("topic_group")->field('size')->where('id='.$id)->find();
        $topic_group_size = !empty($topic_group['size'])?$topic_group['size']:0;
        //二级栏目
        $topic_category = D("topic_category")->field('sum(size)')->where('groupid="'.$id.'"')->select();
        $topic_category_size = !empty($topic_category[0]['sum(size)'])?$topic_category[0]['sum(size)']:0;
        //资源
        $topic_resource = D("topic_resource")->where('gid="'.$id.'"')->field('sum(size)')->select();
        $topic_resource_size = $topic_resource[0]['sum(size)'];
        return ($topic_group_size+$topic_category_size+$topic_resource_size);
    }

    //找出通用栏目下资源的名称
    public function searchTopicAndName($id_arr = array()){
        if(empty($id_arr)){
            return;
        }
        $getName = array();
        $getType = array();
        $getFilepath = array();
        foreach ($id_arr as $key => $value) {
            //查找一级
            $topic_group = D("topic_group")->field('icon')->where('id="'.$value.'"')->find();
            if(!empty($topic_group['icon'])){
                $getName[] = $this->filepathToName($topic_group['icon']);
                $getType[] = 2;
                $getFilepath[] = $topic_group['icon'];
            }
            //查找二级
            $topic_category = D("topic_category")->field('icon')->where('groupid="'.$value.'"')->select();
            if(!empty($topic_category)){
                foreach ($topic_category as $k => $v) {
                    if(!empty($v['icon'])){
                        $getName[] = $this->filepathToName($v['icon']);
                        $getType[] = 2;
                        $getFilepath[] = $v['icon'];
                    }
                }
            }
            //查找资源
            $topic_resource = D("topic_resource")->field('video,image,video_image')->where('gid="'.$value.'"')->select();
            if(!empty($topic_resource)){
                foreach ($topic_resource as $kk => $vv) {
                    if(!empty($vv['video'])){
                        $getName[] = $this->filepathToName($vv['video']);
                        $getType[] = 1;
                        $getFilepath[] = $vv['video'];
                    }
                    if(!empty($vv['image'])){
                        $getName[] = $this->filepathToName($vv['image']);
                        $getType[] = 2;
                        $getFilepath[] = $vv['image'];
                    }
                    if(!empty($vv['video_image'])){
                        $getName[] = $this->filepathToName($vv['video_image']);
                        $getType[] = 2;
                        $getFilepath[] = $vv['video_image'];
                    }
                }
            }
        }
        $result['name'] = $getName;
        $result['type'] = $getType;
        $result['filepath'] = $getFilepath;
        return $result;
    }

    //根据路径获得名字
    public function filepathToName($str){
        $Name_arr = explode("/", $str);
        $getName = $Name_arr[count($Name_arr)-1];
        return $getName;
    }
}