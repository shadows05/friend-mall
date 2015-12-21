<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Admin\Controller;

/**
 * Description of MemberCheckController
 *
 * @author bean
 */
class MembercheckController extends MemberAdminController {
    //put your code here
    
    // 我收到的申请
    public function index(){
        $list = D("MemberApply")->where("examine_uid=".is_login()." and apply_type in (1,2)")->select();
        foreach($list as $k=>$v){
            if($v["apply_type"] == 1){
                // 我收到的推荐人正式会员申请，后续还需要向五级会员发起审核
                // 查找五级会员申请状态
                $new_apply = D("MemberApply")->where("uid=".is_login()." and cuid = ".$v["uid"]." and apply_type = 2")->find();
                if(empty($new_apply)){
                    $list[$k]["new_apply_status"] = -1; // 还没有发起申请
                }else{
                    if($new_apply["status"] == 0){
                        $list[$k]["new_apply_status"] = 0; // 正在等待五级别会员审核
                    }elseif($new_apply["status"] == 1){
                        $list[$k]["new_apply_status"] = 1; // 五级会员已经审核通过
                    }
                }
            }
        }
        $this->assign("_list",$list);
        $this->display();
    }
    
    // 我发出的申请
    public function out(){
        $list = D("MemberApply")->where("(uid=".is_login()." or cuid=".is_login(). ") and apply_type in (1,2)")->select();
        $this->assign("_list",$list);
        $this->display();
    }

    public function changeStatus(){
        $id = I("id");
        $data["id"] = $id;
        $data["status"] = 1;
        $data["update_time"] = NOW_TIME;
        D("MemberApply")->save($data);
        if(I("type") == 1){
            D("Member")->save(array("uid"=>I("cuid"),"check_status"=>1));
            // 修改用户关系状态
            D("MemberRelation")->save(array("cuid"=>I("cuid"),"check_status"=>1));
        }elseif(I("type") ==2){
            // 修改用户状态
            D("Member")->save(array("uid"=>I("cuid"),"check_status"=>2));
            // 修改用户关系状态
            D("MemberRelation")->save(array("cuid"=>I("cuid"),"check_status"=>2));
            // 增加用户的积分
            D("MemberPointEarn")->add(array(
                                        "uid"=>I("cuid"),
                                        "point"=>500,
                                        "type"=>1,
                                        "status"=>1,
                                        "create_time"=>NOW_TIME,"update_time"=>NOW_TIME));
            D("Member")->where("uid = ". I("cuid"))->setInc('upoint',500);
        }

        $this->success('审核成功！');
    }
    
    // 称为正式会员申请
    public function memberSelf($examine_uid=""){
        if(IS_POST){
            $apply = array("uid"=>is_login(),
                            "examine_uid"=>$examine_uid,
                            "cuid"=>is_login(),
                            "apply_type"=>1,
                            "status"=>0,
                            "apply_conten" => "我申请开通正式会员",
                            "create_time"=>NOW_TIME,"update_time"=>NOW_TIME);
            if(D("MemberApply")->add($apply)){

                $this->success('申请成功！', U("Membercheck/out"));
            }

        }else{
            $pUserInfo = D("Member")->findPuserInfoByUid(is_login());
            $this->assign('data',$pUserInfo);
            $this->display();
        }
    }
        
    // 称为正式会员推荐人申请
    public function memberPuid($examine_uid="", $cuid=""){
        if(IS_POST){
            $apply = array("uid"=>is_login(),"examine_uid"=>$examine_uid,"cuid"=>$cuid,"apply_type"=>2,"status"=>0,
                "apply_conten" => "我推荐的用户申请开通正式会员，请审核",
                "create_time"=>NOW_TIME,"update_time"=>NOW_TIME);
            if(D("MemberApply")->add($apply)){

                $this->success('申请成功！', U("Membercheck/out"));
            }

        }else{
            $cuid = I("cuid");
            // 获取被推荐人上层5级报单会员
            $five_level_user = $this->getFiveLevelCust($cuid);
            $this->assign("_five_user", $five_level_user);
            $this->assign("_cuser", D("Member")->find($cuid));
            // 获取会员的5级报单会员
            $this->display();
        }
    }
    
}
