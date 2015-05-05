<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class UserRole extends AbstractService
{
    public function add($role, $user)
    {
        $m_user_role = $this->getModel();
        $m_user_role->setRoleId($role)
                    ->setUserId($user);

        if ($this->getMapper()->insert($m_user_role) <= 0) {
            throw new \Exception('error insert');
        }

        return true;
    }

    public function deleteByUser($id)
    {
        $m_user_role = $this->getModel()->setUserId($id);

        return $this->getMapper()->delete($m_user_role);
    }
}
