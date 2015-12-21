<?php
/**
 * Created by PhpStorm.
 * User: bean
 * Date: 2015/12/12
 * Time: 21:07
 */

namespace Admin\Controller;

use User\Api\UserApi;

class MemberAdminController extends AdminController
{
    /**
     * 普通用户后台控制器初始化
     */
    protected $userInfo;


    protected function _initialize(){

        parent::_initialize();
        // 查找用户基本信息
        $this->getUserInfo();
        // 查找会员团队统计
        $this->getUserStatisticInfo();
        // 查找会员未处理的开通会员申请
        $this->getUnsolvedApply();
        // 查找会员未处理的升级申请
        $this->getUnsolvedUpdateApply();
        // 判断用户是否可升级
        $this->getUserIfCanUpdate();
        // 查找我推荐的用户里面check_status = 1 的会员，就是通过了我的审核，但是还没有通过5级管理员审核的会员
        $this->getRecommendUnConfirmCustCount();

        //$this->userInfo["tipsCount"] = $this->userInfo["unconfirmRecommendUserCount"]
        //                                + $this->userInfo["unsolvedApplyCount"]
        //                                + $this->userInfo["unsolvedUpdateApplyCount"]
        //                                + $this->userInfo["ifCatUpdate"];
        $this->userInfo["tipsCount"] = 10;
        $this->assign("userInfo", $this->userInfo);
    }

    public function checkUserStatus(){
        // 获取用户状态 注册状态&&正式会员状态
        // 获取我的申请
        $my_apply = D("MemberApply")->where("uid=".  is_login()." and apply_type = 1")->find();
        if($this->userInfo["baseInfo"]["check_status"] == 0 && empty($my_apply)){
            $this->redirect("Membercheck/memberSelf");
        }

        if($this->userInfo["baseInfo"]["check_status"] == 0 && !empty($my_apply)){
            $this->redirect("Membercheck/out");
        }
    }

    private function getUserIfCanUpdate(){
        $now_level = $this->userInfo["baseInfo"]["level"];
        if($this->userInfo["statistic"]["teamActiveCount"] >= 512){
            $new_level = 9;
        }elseif($this->userInfo["statistic"]["teamActiveCount"] >= 256){
            $new_level = 8;
        }elseif($this->userInfo["statistic"]["teamActiveCount"] >= 128){
            $new_level = 7;
        }elseif($this->userInfo["statistic"]["teamActiveCount"] >= 64){
            $new_level = 6;
        }elseif($this->userInfo["statistic"]["teamActiveCount"] >= 32){
            $new_level = 5;
        }elseif($this->userInfo["statistic"]["teamActiveCount"] >= 16){
            $new_level = 4;
        }elseif($this->userInfo["statistic"]["teamActiveCount"] >= 8){
            $new_level = 3;
        }elseif($this->userInfo["statistic"]["teamActiveCount"] >= 4){
            $new_level = 2;
        }elseif($this->userInfo["statistic"]["teamActiveCount"] >= 2){
            $new_level = 1;
        }
        if($new_level > $now_level){
            $this->userInfo["ifCatUpdate"] = 1;
        }else{
            $this->userInfo["ifCatUpdate"] = 0;
        }
    }

    private function getRecommendUnConfirmCustCount(){
        $this->userInfo["unconfirmRecommendUserCount"] = M("Member")->where("ruid=".is_login()." and check_status = 1")->count();
    }

    private function getUnsolvedApply(){
        $this->userInfo["unsolvedApplyCount"] = M("MemberApply")->where("examine_uid=".is_login()." and status = 0 and apply_type in (1,2)")->count();
    }

    private function getUnsolvedUpdateApply(){
        $this->userInfo["unsolvedUpdateApplyCount"] = M("MemberApply")->where("examine_uid=".is_login()." and status = 0 and apply_type = 3")->count();
    }

    private function getUserInfo(){
        $user_info = M("Member")->find(is_login());
        switch ($user_info["level"])
        {
            case 0:
                $user_info["update_tips"] = "需要向上一层1级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),1);
                break;
            case 1:
                $user_info["update_tips"] = "需要向上二层2级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),2);
                break;
            case 2:
                $user_info["update_tips"] = "需要向上三层3级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),3);
                break;
            case 3:
                $user_info["update_tips"] = "需要向上四层4级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),4);
                break;
            case 4:
                $user_info["update_tips"] = "需要向上五层5级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),5);
                break;
            case 5:
                $user_info["update_tips"] = "需要向上六层6级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),6);
                break;
            case 6:
                $user_info["update_tips"] = "需要向上七层7级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),7);
                break;
            case 7:
                $user_info["update_tips"] = "需要向上八层8级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),8);
                break;
            case 8:
                $user_info["update_tips"] = "需要向上九层9级会员发200元红包";
                $user_info["update_confirm_cust"] = $this->getUpdateConfirmUser(is_login(),9);
                break;
            default:
                break;
        }
        $user_info["next_level"] = (int)$user_info["level"] + 1;
        $this->userInfo["baseInfo"] = $user_info;
    }

    private function getUserStatisticInfo(){
        $this->userInfo["statistic"] = array(
            "recommendCount" => M("Member")->where("ruid=".is_login())->count(),
            "teamCount" => M("MemberRelation")->where("puid=".is_login())->count(),
            "teamActiveCount" => M("MemberRelation")->where("puid=".is_login()." and check_status = 2")->count()
        );
    }

    public function getFiveLevelCust($uid){
        // 获取uid所有的上层用户
        $list = D("MemberRelation")->where("cuid = $uid")->order("preaches desc")->select();
        $user_info = array();
        foreach($list as $k=>$v){
            $puid = $v["puid"];
            $p_user_info = D("Member")->where("uid = $puid")->find();
            if($p_user_info["level"] >= 5){
                $user_info =  $p_user_info;
                break;
            }
        }

        return $user_info;

    }

    // 获取用户升级的上层用户
    public function getUpdateConfirmUser($uid,$next_level){
        $start = $next_level - 1;
        $user_info = array();
        $list = D("MemberRelation")->where("cuid=$uid")->order("preaches desc")->limit("$start,100")->select();
        foreach($list as $k=>$v){
            $puid = $v["puid"];
            $p_user_info = D("Member")->where("uid = $puid")->find();
            if($p_user_info["level"] >= $next_level){
                $user_info =  $p_user_info;
                break;
            }
        }
        return $user_info;
    }
}