<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi;

/**
 * 后台用户控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class UserController extends AdminController {

    /**
     * 用户管理首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
        $nickname       =   I('nickname');
        $map['status']  =   array('egt',0);
        if(is_numeric($nickname)){
            $map['uid|nickname']=   array(intval($nickname),array('like','%'.$nickname.'%'),'_multi'=>true);
        }else{
            $map['nickname']    =   array('like', '%'.(string)$nickname.'%');
        }

        $list   = $this->lists('Member', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '用户信息';
        $this->display();
    }

    /**
     * 修改昵称初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updateNickname(){
        $nickname = M('Member')->getFieldByUid(UID, 'nickname');
        $this->assign('nickname', $nickname);
        $this->meta_title = '修改昵称';
        $this->display();
    }

    /**
     * 修改昵称提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitNickname(){
        //获取参数
        $nickname = I('post.nickname');
        $password = I('post.password');
        empty($nickname) && $this->error('请输入昵称');
        empty($password) && $this->error('请输入密码');

        //密码验证
        $User   =   new UserApi();
        $uid    =   $User->login(UID, $password, 4);
        ($uid == -2) && $this->error('密码不正确');

        $Member =   D('Member');
        $data   =   $Member->create(array('nickname'=>$nickname));
        if(!$data){
            $this->error($Member->getError());
        }

        $res = $Member->where(array('uid'=>$uid))->save($data);

        if($res){
            $user               =   session('user_auth');
            $user['username']   =   $data['nickname'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success('修改昵称成功！');
        }else{
            $this->error('修改昵称失败！');
        }
    }

    /**
     * 修改密码初始化
     * @author huajie <banhuajie@163.com>
     */
    public function updatePassword(){
        $this->meta_title = '修改密码';
        $this->display();
    }

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function submitPassword(){
        //获取参数
        $password   =   I('post.old');
        empty($password) && $this->error('请输入原密码');
        $data['password'] = I('post.password');
        empty($data['password']) && $this->error('请输入新密码');
        $repassword = I('post.repassword');
        empty($repassword) && $this->error('请输入确认密码');

        if($data['password'] !== $repassword){
            $this->error('您输入的新密码与确认密码不一致');
        }

        $Api    =   new UserApi();
        $res    =   $Api->updateInfo(UID, $password, $data);
        if($res['status']){
            $this->success('修改密码成功！');
        }else{
            $this->error($res['info']);
        }
    }

    /**
     * 用户行为列表
     * @author huajie <banhuajie@163.com>
     */
    public function action(){
        //获取列表数据
        $Action =   M('Action')->where(array('status'=>array('gt',-1)));
        $list   =   $this->lists($Action);
        int_to_string($list);
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);

        $this->assign('_list', $list);
        $this->meta_title = '用户行为';
        $this->display();
    }

    /**
     * 新增行为
     * @author huajie <banhuajie@163.com>
     */
    public function addAction(){
        $this->meta_title = '新增行为';
        $this->assign('data',null);
        $this->display('editaction');
    }

    /**
     * 编辑行为
     * @author huajie <banhuajie@163.com>
     */
    public function editAction(){
        $id = I('get.id');
        empty($id) && $this->error('参数不能为空！');
        $data = M('Action')->field(true)->find($id);

        $this->assign('data',$data);
        $this->meta_title = '编辑行为';
        $this->display();
    }

    /**
     * 更新行为
     * @author huajie <banhuajie@163.com>
     */
    public function saveAction(){
        $res = D('Action')->update();
        if(!$res){
            $this->error(D('Action')->getError());
        }else{
            $this->success($res['id']?'更新成功！':'新增成功！', Cookie('__forward__'));
        }
    }

    /**
     * 会员状态修改
     * @author 朱亚杰 <zhuyajie@topthink.net>
     */
    public function changeStatus($method=null){
        $id = array_unique((array)I('id',0));
        if( in_array(C('USER_ADMINISTRATOR'), $id)){
            $this->error("不允许对超级管理员执行该操作!");
        }
        $id = is_array($id) ? implode(',',$id) : $id;
        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map['uid'] =   array('in',$id);
        switch ( strtolower($method) ){
            case 'forbiduser':
                $this->forbid('Member', $map );
                break;
            case 'resumeuser':
                $this->resume('Member', $map );
                break;
            case 'deleteuser':
                $this->delete('Member', $map );
                break;
            default:
                $this->error('参数非法');
        }
    }

    public function add($username = '', $password = '', $repassword = '', $mobile = '', $idcard = '', $realname = '', $webchat = '', $address = '', $ruid = '', $puid = '', $bdirection = '', $level = '' ){

        R("MemberAdmin/checkUserStatus");

        if(IS_POST){
            /* 检测密码 */
            if($password != $repassword){
                $this->error('密码和重复密码不一致！');
            }
            if($realname == ''){
                $this->error('请填写真实姓名！');
            }
            if($webchat == ''){
                $this->error('请填写微信号！');
            }
            if($address == ''){
                $this->error('请填写常用地址！');
            }

            // 获取 puid = 0 的用户，必须只能有一个；
            $top_user = M('Member')->where("puid = 0")->find();

            // 如果 puid > 0 ,则判断是否有puid=0的顶层用户存在，如果没有，则提示，需要先创建顶层用户
            if(is_null($top_user) &&  $puid > 0){
                $this->error("系统需要先创建顶层会员，请设置顶层会员的接点人编号为0!");
            }

            // 如果puid = 0 ，则查找是有已经存在puid=0的顶层会员，如果存在，则提示，顶层会员已经存在，不可再创建顶层用户
            if(!is_null($top_user) && $puid == 0){
                $this->error("系统只能创建一个顶层会员，请重新设置顶层会员的接点人!");
            }

            if($puid == 0 && $level != 9){
                $this->error("您现在正在创建顶层会员，请设置会员等级为9!");
            }

            if($level < 0 || $level > 9){
                $this->error("会员等级请填写0-9!");
            }

            $user = array(
                "nickname"      =>          $username,
                "realname"          =>      $realname,
                "idcard"        =>          $idcard,
                "mobile"        =>          $mobile,
                "webchat"       =>          $webchat,
                "address"       =>          $address,
                "ruid"       =>             $ruid,
                "puid"      =>              $puid,
                "bdirection"        =>     $bdirection);
            // 后台创建的会员都是合格会员；需要红包审核通过，直接是合格会员
            $user["status"] = 1;
            $user["check_status"] = $ruid == 2 ? 2 : 0;
            $user["level"] = $level;

            // 获取puid的用户，获取其status and check_status,如果status != 1,提示用户状态不对；
            // 如果check_status != 2 ,提示用户还不是正式会员，不可发现会员
            // 获取用户的reaches+1赋值给新的用户 从from获取用的level等级
            if($puid == 0){
                $user["reaches"] = 0;
            }else{
                // 获取接点人信息
                $parent_user = M('Member')->where("uid = $puid")->find();
                if(is_null($parent_user)){
                    $this->error("您选择的接点人不存在，请重新选择!");
                }
                if($parent_user["status"] == 0 || $parent_user["check_status"] == 0){
                    $this->error("您选择的接点人还不是正式的会员，请重新选择!");
                }
                $user["reaches"] = $parent_user["reaches"] + 1;

                if($parent_user["b_left_uid"] != 0 && $parent_user["b_middle_uid"] != 0 & $parent_user["b_right_uid"] != 0){
                    $this->error("接点会员左中右区会员已满，请重新选择接点会员!");
                }
                if($bdirection == 0 && $parent_user["b_left_uid"] != 0){
                    $this->error("接点会员左区已有会员，请重新选择!");
                }
                if($bdirection == 1 && $parent_user["b_middle_uid"] != 0){
                    $this->error("接点会员中区已有会员，请重新选择!");
                }
                if($bdirection == 2 && $parent_user["b_right_uid"] != 0){
                    $this->error("接点会员右区已有会员，请重新选择!");
                }
            }

            /* 调用注册接口注册用户 */
            $User   =   new UserApi;
            $uid    =   $User->register($username, $password, $mobile, $idcard);
            if(0 < $uid){ //注册成功
                $user["uid"] = $uid;
                if(!M('Member')->add($user)){
                    $this->error('用户添加失败！');
                } else {
                    // 添加到用户组
                    // group=2 注册用户分组
                    $AuthGroup = D('AuthGroup');
                    $AuthGroup->addToGroup($uid,2);
                    //记录行为
                    action_log('add_user', 'member', $uid, UID);

                    D("Member")->updateParentBdirection($puid,$uid,$bdirection);

                    D("MemberSuperior")->initMemberSuperior($puid,$uid,$user["reaches"]);

                    D("MemberRelation")->initMemberRelation($puid,$uid,$user["reaches"],$user["check_status"]);

                    // 生成用户的顶层关系网络 网站管理员是可以手动设置的,这里是自动设置
                    // 生成用户顶层关系
                    // 生成用户member_relation
                    //M('MemberSuperior')->add(array("uid" => $uid,"create_time" => NOW_TIME,"update_time"=>NOW_TIME));
                    $this->success('用户添加成功！',$ruid == 2 ? U('index') : U("Membership/team"));
                }
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }
        } else {
            $this->meta_title = '新增用户';
            $data['ruid'] = is_login();
            $data['puid'] = isset($_GET["puid"])?$_GET["puid"]:0;
            $data['bdirection'] = isset($_GET["bd"])?$_GET["bd"]:1;
            $data['level'] = isset($_GET["level"])?$_GET["level"]:1;
            $this->assign('data',$data);
            $this->display();
        }
    }

    public function editsuperior(){
        if(IS_POST){
            $data = $_POST;
            $data["update_time"] = NOW_TIME;
            M("MemberSuperior")->save($data);
            $this->success('更新成功！',U('index'));
        }else{
            $uid = (int)$_GET['uid'];
            $data = M("MemberSuperior")->where("uid={$uid}")->find();
            $this->assign("user_superior",$data);
            $this->display();
        }
    }

    public function editlevel(){
        if(IS_POST){
            $data["uid"] = $_POST["uid"];
            $data["level"] = $_POST["level"];
            $data["update_time"] = NOW_TIME;
            M("Member")->save($data);
            $this->success('更新成功！',U('index'));
        }else{
            $uid = (int)$_GET['uid'];
            $data = M("Member")->find($uid);
            $this->assign("_user_info",$data);
            $this->display();
        }
    }

    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0){
        switch ($code) {
            case -1:  $error = '用户名长度必须在6-30个字符以内！'; break;
            case -2:  $error = '用户名被禁止注册！'; break;
            case -3:  $error = '用户名被占用！'; break;
            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
            case -5:  $error = '邮箱格式不正确！'; break;
            case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
            case -7:  $error = '邮箱被禁止注册！'; break;
            case -8:  $error = '邮箱被占用！'; break;
            case -9:  $error = '手机格式不正确！'; break;
            case -10: $error = '手机被禁止注册！'; break;
            case -11: $error = '手机号被占用！'; break;
            case -12: $error = '身份证号码格式不正确！'; break;
            case -13: $error = '身份证号被占用！'; break;
            default:  $error = '未知错误';
        }
        return $error;
    }

}
