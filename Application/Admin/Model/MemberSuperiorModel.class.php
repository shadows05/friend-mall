<?php
/**
 * Created by PhpStorm.
 * User: bean
 * Date: 2015/12/9
 * Time: 22:15
 */

namespace Admin\Model;


use Think\Model;

class MemberSuperiorModel extends Model
{
    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, self::MODEL_UPDATE)
    );

    protected $pk  = 'uid';

    public function initMemberSuperior($puid,$uid,$reaches){
        if($puid==0) return;
        $child_uid = $uid;
        $data_superior["uid"] = $uid;
        $j = 1;
        for($i=$reaches;$i>0,$j<=9;$i--,$j++){
            $parent_uid = M("Member")->where("uid=$child_uid")->getField('puid');
            if($parent_uid == 0) break;
            switch ($j)
            {
                case 1:
                    $data_superior["first_superior"] = $parent_uid;break;
                case 2:
                    $data_superior["second_superior"] = $parent_uid;break;
                case 3:
                    ;$data_superior["third_superior"] = $parent_uid;break;
                case 4:
                    $data_superior["fouth_superior"] = $parent_uid;break;
                case 5:
                    $data_superior["fifth_superior"] = $parent_uid;break;
                case 6:
                    $data_superior["sixth_superior"] = $parent_uid;break;
                case 7:
                    $data_superior["seventh_superior"] = $parent_uid;break;
                case 8:
                    $data_superior["eighth_superior"] = $parent_uid;break;
                case 9:
                    $data_superior["ninth_superior"] = $parent_uid;break;
                default:
                    break;
            }
            $child_uid = $parent_uid;
        }
        $data_superior["create_time"] = NOW_TIME;
        $data_superior["update_time"] = NOW_TIME;
        $this->add($data_superior);
    }

}