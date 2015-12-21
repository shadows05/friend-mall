<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Model;
use Think\Model;

/**
 * 用户模型
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */

class MemberModel extends Model {

    protected $_validate = array(
        array('nickname', '1,16', '昵称长度为1-16个字符', self::EXISTS_VALIDATE, 'length'),
        array('nickname', '', '昵称被占用', self::EXISTS_VALIDATE, 'unique'), //用户名被占用
    );

    /* 用户模型自动完成 */
    protected $_auto = array(
        array('reg_time', NOW_TIME, self::MODEL_INSERT),
        array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1)
    );

    public function lists($status = 1, $order = 'uid DESC', $field = true){
        $map = array('status' => $status);
        return $this->field($field)->where($map)->order($order)->select();
    }

    /**
     * 登录指定用户
     * @param  integer $uid 用户ID
     * @return boolean      ture-登录成功，false-登录失败
     */
    public function login($uid){
        /* 检测是否在当前应用注册 */
        $user = $this->field(true)->find($uid);
        if(!$user || 1 != $user['status']) {
            $this->error = '用户不存在或已被禁用！'; //应用级别禁用
            return false;
        }

        //记录行为
        action_log('user_login', 'member', $uid, $uid);

        /* 登录用户 */
        $this->autoLogin($user);
        return true;
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout(){
        session('user_auth', null);
        session('user_auth_sign', null);
    }

    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    private function autoLogin($user){
        /* 更新登录信息 */
        $data = array(
            'uid'             => $user['uid'],
            'login'           => array('exp', '`login`+1'),
            'last_login_time' => NOW_TIME,
            'last_login_ip'   => get_client_ip(1),
        );
        $this->save($data);

        /* 记录登录SESSION和COOKIES */
        $auth = array(
            'uid'             => $user['uid'],
            'username'        => $user['nickname'],
            'last_login_time' => $user['last_login_time'],
        );

        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));

    }

    public function getNickName($uid){
        return $this->where(array('uid'=>(int)$uid))->getField('nickname');
    }

    public function updateParentBdirection($puid,$uid,$bdirection){
        if($puid != 0){
            $data = array("uid" => $puid);
            if($bdirection == 0){
                $data["b_left_uid"] = $uid;
            }elseif($bdirection == 1){
                $data["b_middle_uid"] = $uid;
            }elseif($bdirection ==2){
                $data["b_right_uid"] = $uid;
            }
            $this->save($data);
        }
    }

    public function generateTeamStructure($uid){
        // 获取此uid的 b_left b_middle b_right
        //$structure_info = array();
        $user_ifo = $this->find($uid);

        if($user_ifo["check_status"] != 2){
            $structure =    '<div class="strt-part">'.
                '	<span class="strt-name">'.$user_ifo["nickname"].'<span class="badge">'.$user_ifo["level"].'</span></span>'.
                '</div>';
        }else{
            $structure =    '<div class="strt-part">'.
                '	<span class="strt-name">'.$user_ifo["nickname"].'<span class="badge">'.$user_ifo["level"].'</span></span>'.
                '	<div class="line-v"><span></span></div>'.
                '	<div class="strt-block">'.
                $this->getChildStructure($uid).
                '	</div>'.
                '</div>';

        }


        return $structure;

    }

    public function getChildStructure($uid){
        $user_ifo = $this->find($uid);

        if($user_ifo["check_status"] != 2){
            return "";
        }

        $left_structure = $middle_structure = $right_structure = "";
        if($user_ifo["b_left_uid"] == 0){
            $left_structure .=  '    <div class="strt-part">'.
                           '			<span class="line-h line-h-r"></span>'.
                           '			<div class="line-v"><span></span></div>'.
                           '			<a href="'.U("User/add?puid=$uid&bd=0&level=0").'" ><span class="strt-name">(空缺)推荐</span></a>'.
                           '		</div>';
        }else{
            $left_user_info = $this->find($user_ifo["b_left_uid"]);
            $left_structure .=   '<div class="strt-part">'.
                            '    <span class="line-h line-h-r"></span>'.
                            '    <div class="line-v"><span></span></div>'.
                            '    <span class="strt-name">'.$left_user_info["nickname"].'<span class="badge">'.$left_user_info["level"].'</span></span>'.
                            '    <div class="line-v"><span></span></div>'.
                            '       <div class="strt-block">'.
                            $this->getChildStructure($user_ifo["b_left_uid"]).
                            '   </div></div>';
        }


        if($user_ifo["b_middle_uid"] == 0){
            $middle_structure .=   '    <div class="strt-part">'.
                            '			<span class="line-h line-h-c"></span>'.
                            '			<div class="line-v"><span></span></div>'.
                            '			<a href="'.U("User/add?puid=$uid&bd=1&level=0").'" ><span class="strt-name">(空缺)推荐</span></a>'.
                            '		</div>';
        }else{
            $middle_user_info = $this->find($user_ifo["b_middle_uid"]);
            $middle_structure .=   '<div class="strt-part">'.
                '    <span class="line-h line-h-c"></span>'.
                '    <div class="line-v"><span></span></div>'.
                '    <span class="strt-name user-strt-name">'.$middle_user_info["nickname"].'<span class="badge">'.$middle_user_info["level"].'</span></span>'.
                '    <div class="line-v"><span></span></div>'.
                '       <div class="strt-block">'.
                $this->getChildStructure($user_ifo["b_middle_uid"]).
                '   </div></div>';
        }


        if($user_ifo["b_right_uid"] == 0){
            $right_structure .=   '    <div class="strt-part">'.
                '			<span class="line-h line-h-l"></span>'.
                '			<div class="line-v"><span></span></div>'.
                '			<a href="'.U("User/add?puid=$uid&bd=2&level=0").'" ><span class="strt-name">(空缺)推荐</span></a>'.
                '		</div>';
        }else{
            $right_user_info = $this->find($user_ifo["b_right_uid"]);
            $right_structure .=   '<div class="strt-part">'.
                '    <span class="line-h line-h-l"></span>'.
                '    <div class="line-v"><span></span></div>'.
                '    <span class="strt-name">'.$right_user_info["nickname"].'<span class="badge">'.$right_user_info["level"].'</span></span>'.
                '    <div class="line-v"><span></span></div>'.
                '       <div class="strt-block">'.
                $this->getChildStructure($user_ifo["b_right_uid"]).
                '   </div></div>';
        }
        return $left_structure . $middle_structure . $right_structure;
    }
    
    public function findPuserInfoByUid($uid){
        $puid = $this->where("uid = $uid")->getField("ruid");
        return $this->find($puid);
    }
}
