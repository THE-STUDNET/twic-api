<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Group User
 *
 */

namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class GroupUser
 */
class GroupUser extends AbstractService
{
    public function add($group, $users)
    {
        if (!is_array($users)) {
            $users = [$users];
        }
        foreach ($users as $u) {
            $this->getMapper()->insert($this->getModel()->setGroupId($group)->setUserId($u));
        }

        return true;
    }

    public function getListUser($group)
    {
        $res_group_user = $this->getMapper()->select($this->getModel()->setGroupId($group));

        $u = [];
        foreach ($res_group_user as $m_group_user) {
            $u[] = $m_group_user->getUserId();
        }

        return $u;
    }

    /**
     * @param int $item_id
     * @param int $user_id
     *
     * @return null|int
     */
    public function getGroupIdByItemUser($item_id, $user_id)
    {
        $res_group_user = $this->getMapper()->getGroupIdByItemUser($item_id, $user_id);

        return ($res_group_user->count() > 0) ?
             $res_group_user->current()->getGroupId() :
             null;
    }

    public function delete($group, $user = null)
    {
        return $this->getMapper()->delete($this->getModel()->setGroupId($group)->setUserId($user));
    }
}
