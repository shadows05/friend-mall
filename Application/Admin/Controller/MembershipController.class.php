<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi as UserApi;

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class MembershipController extends MemberAdminController {


    public function index(){
        if($this->userInfo["baseInfo"]["check_status"] == 0){
            $my_apply = D("MemberApply")->where("uid=".  is_login()." and apply_type = 1")->find();
            if(empty($my_apply)){
                $cust_confirm_status = 1; // 非正式会员，请向推荐人发红包
            }elseif(!empty($my_apply) && $my_apply["status"] == 0){
                $cust_confirm_status=  2; // 已经向推荐人发送红包，等待审核
            }
        }elseif($this->userInfo["baseInfo"]["check_status"] == 1){
            $cust_confirm_status = 3; // 已通过推荐人的审核，耐心等待5级报单会员的审核
        }
        $this->assign("_cust_confirm_status", $cust_confirm_status);
        //print_r($this->userInfo);
        $this->display();
    }

    /**
     * 我推荐的会员
     */
    public function recommend(){
        $list = M("Member")->where("ruid=".is_login())->select();
        $this->assign("_list", $list);
        $this->display();
    }

    /**
     * 我的推荐图谱
     */
    public function team(){

        $uid = is_null($_GET['uid']) ? is_login() : (int)$_GET['uid'];

        //$team_structure =
        $structure = D("Member")->generateTeamStructure($uid);
        $this->assign("structure", $structure);
        $this->display();
    }

    /**
     * 我的线下成员列表
     */
    public function teamlist(){

        $list = D("MemberRelation")->getUserChildList(is_login());

        $this->assign("_list", $list);

        $this->display();
    }

    /**
     * 会员基本信息
     */
    public function info(){
        $uid = is_null($_GET['uid']) ? is_login() : (int)$_GET['uid'];
        $this->assign("info", M("Member")->find($uid));
        $this->display();
    }

    /**
     * 推荐会员
     */
    public function adduser($username = '', $password = '', $repassword = '', $mobile = '', $idcard = '', $realname = '', $webchat = '', $address = '', $ruid = '', $puid = '', $bdirection = '', $level = '' ){

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
            $user["check_status"] = 2;
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
            if(0 < $uid) { //注册成功
                $user["uid"] = $uid;
                if (!M('Member')->add($user)) {
                    $this->error('用户添加失败！');
                } else {
                    // 添加到用户组
                    // group=2 注册用户分组
                    $AuthGroup = D('AuthGroup');
                    $AuthGroup->addToGroup($uid, 2);
                    //记录行为
                    action_log('add_user', 'member', $uid, UID);

                    D("Member")->updateParentBdirection($puid, $uid, $bdirection);

                    D("MemberSuperior")->initMemberSuperior($puid, $uid, $user["reaches"]);

                    D("MemberRelation")->initMemberRelation($puid, $uid, $user["reaches"], $user["check_status"]);

                    // 生成用户的顶层关系网络 网站管理员是可以手动设置的,这里是自动设置
                    // 生成用户顶层关系
                    // 生成用户member_relation
                    //M('MemberSuperior')->add(array("uid" => $uid,"create_time" => NOW_TIME,"update_time"=>NOW_TIME));
                    $this->success('用户添加成功！', U("team?uid=$puid"));
                }
            }
        }else{
            $this->meta_title = '新增用户';
            $data['ruid'] = is_login();
            $data['puid'] = $_GET["puid"];
            $data['bdirection'] = $_GET["bd"];
            $data['level'] = 0;
            $this->assign('data',$data);
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
