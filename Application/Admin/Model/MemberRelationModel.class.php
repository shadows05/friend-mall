<?php
/**
 * Created by PhpStorm.
 * User: bean
 * Date: 2015/12/12
 * Time: 20:00
 */

namespace Admin\Model;

use Think\Model;

class MemberRelationModel extends Model
{

    protected $pk  =  array("puid","cuid");

    public function initMemberRelation($puid,$uid,$reaches,$check_status){
        if($puid==0) return;
        $child_uid = $uid;
        //$data_relation["uid"] = $uid;
        $j = 1;
        for($i=$reaches,$j=1;$i>0;$i--,$j++){
            $parent_id = M("Member")->where("uid=$child_uid")->getField('puid');
            $parent_reaches = M("Member")->where("uid=$parent_id")->getField("reaches");

            $data["puid"] = $parent_id;
            $data["preaches"] = $parent_reaches;
            $data["cuid"] = $uid;
            $data["creaches"] = $reaches;
            $data["check_status"] = $check_status;
            $this->add($data);

            $child_uid = $parent_id;
        }
    }

    public function getUserChildList($uid){
        $list = $this->query("select * from ".C("DB_PREFIX")."member where uid in (select cuid from ".C("DB_PREFIX")."member_relation where puid = $uid) order by uid asc");
        return $list;
    }
}