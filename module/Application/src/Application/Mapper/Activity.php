<?php

namespace Application\Mapper;

use Dal\Mapper\AbstractMapper;
use Zend\Db\Sql\Predicate\Expression;
use Application\Model\Role as ModelRole;

class Activity extends AbstractMapper
{
    public function aggregate($event, $user, $object_id = null, $object_name = null, $target_id = null, $target_name = null)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array(
            'event',
            'activity$value_user' => new Expression('SUM( IF(activity.user_id='.$user.', object_value, 0))'),
            'activity$value_total' => new Expression('SUM(object_value)'),
        ))->where(array('event' => $event));
        if (null !== $object_name && null !== $object_id) {
            $select->where(array(
            'object_id' => $object_id,
            'object_name' => $object_name, ));
        }
        if (null !== $target_name && null !== $target_id) {
            $select->where(array(
            'target_id' => $target_id,
            'target_name' => $target_name, ));
        }
        
        $select->join('user_role', 'user_role.user_id=activity.user_id')
            ->where(array('user_role.role_id='.ModelRole::ROLE_STUDENT_ID.''));

        return $this->selectWith($select);
    }
}
