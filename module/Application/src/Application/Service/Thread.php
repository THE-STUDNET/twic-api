<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use Dal\Db\ResultSet\ResultSet;

class Thread extends AbstractService
{
    /**
     * Add thread.
     *
     * @invokable
     *
     * @param string $title
     * @param integer $course
     * @param string $message
     * @param integer $item_id
     * 
     * @throws \Exception
     *
     * @return int
     */
    public function add($title, $course, $message = null, $item_id = null)
    {
        $m_thread = $this->getModel()
            ->setCourseId($course)
            ->setTitle($title)
            ->setItemId($item_id)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
            ->setUserId($this->getServiceAuth()->getIdentity()->getId());

        if ($this->getMapper()->insert($m_thread) <= 0) {
            throw new \Exception('error insert thread');
        }

        $id = $this->getMapper()->getLastInsertValue();
        $this->getServiceEvent()->threadNew($id);

        if (null !== $message) {
            $id = $this->getServiceThreadMessage()->add($message, $id, true);
        }

        return $id;
    }

    /**
     * update thread.
     *
     * @invokable
     *
     * @TODO Add updated date
     *
     * @param integer $id
     * @param string $title
     * @param integer $item_id
     * 
     * @return integer
     */
    public function update($id, $title = null, $item_id = null)
    {
        if($item_id === null && $title === null) {
            return 0;
        }
        
        $m_thread = $this->getModel()
            ->setId($id)
            ->setTitle($title)
            ->setItemId($item_id);

        return $this->getMapper()->update($m_thread);
    }

    /**
     * GetList Thread by course.
     *
     * @invokable
     *
     * @param integer $course
     * @param unknown $filter
     * @param string $name
     *
     *
     * @return ResultSet
     */
    public function getList($course, $filter = null, $name = null)
    {
        $mapper = $this->getMapper();
        $res_thread = $mapper->usePaginator($filter)->getList($course, null, $name);
        foreach ($res_thread as $m_thread) {
            $m_thread->setMessage($this->getServiceThreadMessage()
                ->getLast($m_thread->getId()));
            $roles = [];
            foreach ($this->getServiceRole()->getRoleByUser($m_thread->getUser()
                ->getId()) as $role) {
                $roles[] = $role->getName();
            }
            $m_thread->getUser()->setRoles($roles);
        }

        return array('count' => $mapper->count(),'list' => $res_thread);
    }

    /**
     * @invokable
     *
     * @param int $id
     *
     * @throws \Exception
     * 
     * @return \Application\Model\Thread
     */
    public function get($id)
    {
        $mapper = $this->getMapper();

        $res_thread = $mapper->getList(null, $id);

        if ($res_thread->count() <= 0) {
            throw new \Exception('not thread with course id: '.$course);
        }

        $m_thread = $res_thread->current();
        $m_thread->setMessage($this->getServiceThreadMessage()
            ->getLast($m_thread->getId()));
        $roles = [];
        foreach ($this->getServiceRole()->getRoleByUser($m_thread->getUser()
            ->getId()) as $role) {
            $roles[] = $role->getName();
        }
        $m_thread->getUser()->setRoles($roles);

        return $m_thread;
    }

    public function getByItem($item_id)
    {
        $res_thread  = $this->getMapper()->select($this->getModel()->setItemId($item_id));
        
        return ($res_thread->count() > 0) ? $res_thread->current():null;
    }
    
    /**
     * delete thread.
     *
     * @invokable
     *
     * @param int $id
     */
    public function delete($id)
    {
        return $this->getMapper()->update($this->getModel()
            ->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')), array('user_id' => $this->getServiceAuth()
            ->getIdentity()
            ->getId(), 'id' => $id));
    }

    /**
     * @invokable
     * 
     * @param int $school
     * 
     * @return int
     */
    public function getNbrMessage($school)
    {
        return [
            'd' => $this->getMapper()->getNbrMessage($school, 1),
            'w' => $this->getMapper()->getNbrMessage($school, 7),
            'm' => $this->getMapper()->getNbrMessage($school, 30),
            'a' => $this->getMapper()->getNbrMessage($school),
        ];
    }

    /**
     * @return \Auth\Service\AuthService
     */
    public function getServiceAuth()
    {
        return $this->getServiceLocator()->get('auth.service');
    }

    /**
     * @return \Application\Service\Event
     */
    public function getServiceEvent()
    {
        return $this->getServiceLocator()->get('app_service_event');
    }

    /**
     * @return \Application\Service\Role
     */
    public function getServiceRole()
    {
        return $this->getServiceLocator()->get('app_service_role');
    }

    /**
     * @return \Application\Service\ThreadMessage
     */
    public function getServiceThreadMessage()
    {
        return $this->getServiceLocator()->get('app_service_thread_message');
    }
}
