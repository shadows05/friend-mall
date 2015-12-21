<?php

/**
 * Created by PhpStorm.
 * User: bean
 * Date: 2015/12/16
 * Time: 23:04
 */

namespace Admin\Controller;

class MemberupController extends MemberAdminController
{
    // 我收到的申请
    public function index(){
        $list = D("MemberApply")->where("examine_uid=".is_login()." and apply_type = 3")->select();
        $this->assign("_list",$list);
        $this->display();
    }

    // 我发出的申请
    public function out(){
        $list = D("MemberApply")->where("uid=".is_login()." and apply_type = 3")->select();
        $this->assign("_list",$list);
        $this->display();
    }

    public function changeStatus(){
        $apply_id = I("id");
        // 更新用户的等级
        D("Member")->where("uid = ". I("cuid"))->setInc('level',1);
        D("MemberApply")->save(array("id" => I("id"),"status" => 1, "update_time" => NOW_TIME));
        $this->success("审核通过",U("Memberup/index"));
    }

    // 我要申请
    public function memberup($examine_uid="", $apply_conten=""){
        if(IS_POST){
            $apply = array(
                "uid" => is_login(),
                "examine_uid" => $examine_uid,
                "cuid" => is_login(),
                "apply_type" => 3,
                "apply_conten" => $apply_conten,
                "status" => 0,
                "create_time" => NOW_TIME,
                "update_time" => NOW_TIME
            );
            if(D("MemberApply")->add($apply)){
                $this->success('申请成功！', U('Memberup/out'));
            }
        }else{
            // 获取我发出的未审核升级申请
            $list = D("MemberApply")->where("uid=".is_login()." and apply_type = 3 and status = 0")->find();
            $this->assign("_already_confirm",empty($list) ? 0 : 1);
            $this->display();
        }
    }

}