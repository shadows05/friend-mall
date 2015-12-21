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

    public function initMemberSuperior($puid,$uid,$reaches,$check_status){
        if($puid==0) return;
        $child_uid = $uid;
        for($i=$reaches,$j=1;$i--,$j++;$i>0){
            $data_superior["uid"] = $uid;
            $parent_uid = M("Member")->where("uid=$child_uid")->getField('puid');
            if($parent_uid == 0) return;
            switch ($j)
            {
                case 1:
                    $superior = "first_superior"; break;
                case 2:
                    $superior = "second_superior"; break;
                case 3:
                    $superior = "third_superior";break;
                case 4:
                    $superior = "fouth_superior"; break;
                case 5:
                    $superior = "fifth_superior"; break;
                case 6:
                    $superior = "sixth_superior"; break;
                case 7:
                    $superior = "seventh_superior"; break;
                case 8:
                    $superior = "eighth_superior"; break;
                case 9:
                    $superior = "ninth_superior"; break;
                default:
                    $superior = "";
            }
            if($superior !=  ""){
                $data_superior[$superior] = $parent_uid;
                $this->save($data_superior);
            }

            $child_uid = $parent_uid;
        }
    }

}