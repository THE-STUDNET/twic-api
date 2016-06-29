<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Whiteboard extends AbstractService
{
    /**
     * @invokable
     * 
     * @param string $name
     */
    public function add($name = "")
    {
        $m_whiteboard = $this->getModel()
            ->setName($name)
            ->setOwnerId($this->getServiceUser()->getIdentity()['id']);
        
        if ($this->getMapper()->insert($m_whiteboard) <= 0) {
            //@TODO error
        }
    
        return $this->getMapper()->getLastInsertValue();
    }
    
    public function _add($data)
    {
        $name = ((isset($data['name']))? $data['name']:null);

        return $this->add($name);
    }
    
    public function getListByConversation($conversation_id)
    {
        return $this->getMapper()->getListByConversation($conversation_id);
    }
    
    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
        
    }
}