<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class ThreadMessage extends AbstractService
{
    /**
     * Add message in thread.
     *
     * @invokable
     * 
     * @param string $message
     * @param int $thread
     * @param int $is_new
     * @param int $parent_id
     * 
     * @return integer
     */
    public function add($message, $thread, $is_new = false, $parent_id = null)
    {
        $m_thread_message = $this->getModel()
            ->setMessage($message)
            ->setThreadId($thread)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
            ->setUserId($this->getServiceAuth()->getIdentity()->getId())
            ->setParentId(($parent_id === 0) ? null : $parent_id);

        if ($this->getMapper()->insert($m_thread_message) <= 0) {
            throw new \Exception('error insert thread');
        }

        $thread_message_id = $this->getMapper()->getLastInsertValue();

        if (!$is_new) {
            $this->getServiceEvent()->threadMessage($thread_message_id);
        }

        return $thread_message_id;
    }

    /**
     * update message in thread.
     *
     * @invokable
     *
     * @TODO set UpdateDate value
     *
     * @param string $message
     * @param int    $id
     *
     * @throws \Exception
     *
     * @return int
     */
    public function update($message, $id, $parent_id = null)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];
        
        $m_threadmessage = $this->getModel()
            ->setMessage($message)
            ->setParentId(($parent_id === 0) ? new IsNull():$parent_id);
                
        return $this->getMapper()->update($m_threadmessage, [
            'user_id' => $me,
            'id' => $id,
        ]);
    }

    /**
     * delete message.
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
            ->getId(), 'id' => $id, ));
    }

    /**
     * Get list thread message.
     *
     * @invokable
     *
     * @param integer $thread
     * @param integer $parent_id
     * @param string $filter
     */
    public function getList($thread, $parent_id = null, $filter = null)
    {
        $mapper = ($filter !== null) ? $this->getMapper()->usePaginator($filter) : $this->getMapper();
        
        $res_thread_message = $mapper->getList($thread, null, $parent_id);

        foreach ($res_thread_message as $m_thread_message) {
            $roles = [];
            foreach ($this->getServiceRole()->getRoleByUser($m_thread_message->getUser()
                ->getId()) as $role) {
                $roles[] = $role->getName();
            }
            $m_thread_message->getUser()->setRoles($roles);
        }

        return ($filter !== null) ? ['count' => $mapper->count(),'list' => $res_thread_message]:$res_thread_message;
    }

    public function getLast($thread)
    {
        return $this->getMapper()->getLast($thread);
    }

    /**
     * @invokable
     * 
     * @param integer $thread_message
     * @return \Application\Model\ThreadMessage
     */
    public function get($thread_message)
    {
        return $this->getMapper()
            ->getList(null, $thread_message)
            ->current();
    }

    /**
     * @return \Application\Service\Event
     */
    public function getServiceEvent()
    {
        return $this->getServiceLocator()->get('app_service_event');
    }

    /**
     * @return \Auth\Service\AuthService
     */
    public function getServiceAuth()
    {
        return $this->getServiceLocator()->get('auth.service');
    }

    /**
     * @return \Application\Service\Role
     */
    public function getServiceRole()
    {
        return $this->getServiceLocator()->get('app_service_role');
    }
    
    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
