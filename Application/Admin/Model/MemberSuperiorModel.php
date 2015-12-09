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
}