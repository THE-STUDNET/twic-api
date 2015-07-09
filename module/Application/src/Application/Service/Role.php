<?php
namespace Application\Service;

use Dal\Service\AbstractService;
use Application\Model\Role as ModelRole;

class Role extends AbstractService
{

    /**
     * add role.
     *
     * @invokable
     *
     * @param string $name            
     *
     * @throws \Exception
     *
     * @return int
     */
    public function add($name)
    {
        if ($this->getMapper()->insert($this->getModel()
            ->setName($name)) <= 0) {
            throw new \Exception('error insert');
        }
        
        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * Update Role.
     *
     * @invokable
     *
     * @param int $id            
     * @param string $name            
     *
     * @return int
     */
    public function update($id, $name)
    {
        $m_role = $this->getModel();
        
        $m_role->setId($id)->setName($name);
        
        return $this->getMapper()->update($m_role);
    }

    /**
     * Delete Role by ID.
     *
     * @invokable
     *
     * @param int $id            
     *
     * @return int
     */
    public function delete($id)
    {
        return $this->getMapper()->delete($this->getModel()
            ->setId($id));
    }

    /**
     * Add role to user.
     *
     * @invokable
     *
     * @param int $role            
     * @param int $user            
     *
     * @return bool
     */
    public function addUser($role, $user)
    {
        return $this->getServiceUserRole()->add($role, $user);
    }

    /**
     */
    public function getRoleByUser($id = null)
    {
        if ($id === null) {
            $id = $this->getServiceAuth()
                ->getIdentity()
                ->getId();
        }
        
        return $this->getMapper()->getRoleByUser($id);
    }

    public function getIdByName($namerole)
    {
        return array_search($namerole, ModelRole::$role);
    }

    /**
     *
     * @return \Application\Service\UserRole
     */
    public function getServiceUserRole()
    {
        return $this->getServiceLocator()->get('app_service_user_role');
    }

    /**
     *
     * @return \Zend\Authentication\AuthenticationService
     */
    public function getServiceAuth()
    {
        return $this->getServiceLocator()->get('auth.service');
    }
}
