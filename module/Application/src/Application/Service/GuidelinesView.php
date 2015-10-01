<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class GuidelinesView extends AbstractService
{
    public function add($state)
    {
        return $this->getMapper()->view($state, $this->getServiceUser()
            ->getIdentity()['id']);
    }

    public function exist($state)
    {
        return ($this->getMapper()
            ->select($this->getModel()
            ->setUserId($this->getServiceUser()
            ->getIdentity()['id'])
            ->setState($state))
            ->count() > 0) ? true : false;
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
