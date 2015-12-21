<?php
/**
 * Created by PhpStorm.
 * User: bean
 * Date: 2015/12/20
 * Time: 22:50
 */

namespace Admin\Controller;


class MemberpointController extends MemberAdminController
{

    // 积分获取列表
    public function index(){
        $list = D("MemberPointEarn")->where("uid = " . is_login())->select();
        $this->assign("_point_earn", $list);
        $this->display();
    }

    // 积分消费列表
    public function consum(){
        $list = D("MemberPointConsum")->where("uid = " . is_login())->select();
        $this->assign("_point_consum", $list);
        $this->display();
    }
}