<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Group extends AbstractService
{
    /**
     * @invokable
     * 
     * @param string $uid
     * @param string $name
     * @param array|integer $users
     * 
     * @return integer
     */
    public function add($set, $name,$uid = null, $users = null) 
    {
        $m_group = $this->getModel()->setUId($uid)->setName($name);
        
        if($this->getMapper()->insert($m_group) <= 0) {
            new \Exception('Error insert group');
        }
        
        $group_id = $this->getMapper()->getLastInsertValue();
        
        if(null !== $users) {
            $this->addUser($group_id, $users);
        }
        
        $this->getServiceSetGroup()->add($set, $group_id);
        
        return $group_id;
    }
    
    /**
     * @invokable
     * 
     * @param integer $id
     * 
     * @return integer
     */
    public function delete($id) 
    {
        return $this->getMapper()->delete($this->getModel()->setId($id));    
    }
    
    /**
     * @invokable
     * 
     * @param integer $id
     * @param string $name
     * @return integer
     */
    public function update($id, $name)
    {
        return $this->getMapper()->update($this->getModel()->setId($id)->setName($name));
    }
    
    /**
     * @invokable
     * 
     * @param integr $id
     * @param array|integr $users
     */
    public function addUser($id, $users)
    {
        if(!is_array($users)) {
            $users = [$users];
        }
        
        $ret = [];
        foreach ($users as $user) {
            $ret[$user] = $this->getServiceGroupUser()->add($id, $user);
        }
        
        return $ret;
    }
    
    /**
     * @invokable
     * 
     * @param integer $id
     * @param integer|array $users
     * 
     * @return NULL
     */
    public function replaceUser($id, $users) 
    {
        $ret = [];
        $this->getServiceGroupUser()->delete($id);
      
        if(!is_array($users)) {
            $users = [$users];
        }
        
        
        foreach ($users as $user) {
            $ret[$user] = $this->getServiceGroupUser()->add($id, $user);
        }
        
        return $ret;
    }
    
    /**
     * @invokable
     * 
     * @param integer $set
     * @param array $filter
     */
    public function getList($set, $filter = null)
    {
    	$mapper = $this->getMapper();
        $res_group = $mapper->usePaginator($filter)->getList($set);
        
        foreach ($res_group as $m_group) {
            $m_group->setUsers($this->getServiceGroupUser()->getListUser($m_group->getId()));
        }
        
        return ($filter === null) ? $res_group:['count' => $mapper->count(),'list' => $res_group];
    }
    
    /**
     * @invokable 
     * 
     * @param integr $id
     * @param array|integr $user
     */
    public function deleteUser($id, $user = null) 
    {
        return $this->getServiceGroupUser()->delete($id, $user);
    }
    
    /**
     * @return \Application\Service\GroupUser 
     */
    public function getServiceGroupUser()
    {
        return $this->getServiceLocator()->get('app_service_group_user');
    }
    
    /**
     * @return \Application\Service\SetGroup
     */
    public function getServiceSetGroup()
    {
        return $this->getServiceLocator()->get('app_service_set_group');
    }
}