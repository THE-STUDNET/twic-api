<?php
namespace Application\Service;

use Dal\Service\AbstractService;
use Zend\Db\Sql\Predicate\IsNull;

class Contact extends AbstractService
{

    /**
     * @invokable
     *
     * @param int $user            
     */
    public function add($user)
    {
        $identity = $this->getServiceUser()->getIdentity();
        
        if ($user == $identity['id']) {
            throw new \Exception('error user equal myself');
        }
        
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        
        $m_contact = $this->getModel()
            ->setUserId($identity['id'])
            ->setContactId($user);
        
        $m_contact_me = $this->getModel()
            ->setRequestDate($date)
            ->setAcceptedDate(new IsNull())
            ->setDeletedDate(new IsNull())
            ->setRequested(true)
            ->setAccepted(false)
            ->setDeleted(false);
        
        $m_contact_you = $this->getModel()
            ->setRequestDate($date)
            ->setAcceptedDate(new IsNull())
            ->setDeletedDate(new IsNull())
            ->setRequested(false)
            ->setAccepted(false)
            ->setDeleted(false);
        
        if ($this->getMapper()
            ->select($m_contact)
            ->count() === 0) {
            $m_contact_me->setUserId($identity['id'])->setContactId($user);
            $m_contact_you->setUserId($user)->setContactId($identity['id']);
            $this->getMapper()->insert($m_contact_me);
            $ret = $this->getMapper()->insert($m_contact_you);
        } else {
            $this->getMapper()->update($m_contact_me, array(
                'user_id' => $identity['id'],
                'contact_id' => $user
            ));
            $ret = $this->getMapper()->update($m_contact_you, array(
                'user_id' => $user,
                'contact_id' => $identity['id']
            ));
        }
        
        $this->getServiceEvent()->userRequestconnection($user);
        
        return $ret;
    }

    /**
     * @invokable
     *
     * @param int $user            
     */
    public function accept($user)
    {
        $identity = $this->getServiceUser()->getIdentity();
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        
        $m_contact = $this->getModel()
            ->setAcceptedDate($date)
            ->setAccepted(false);
        $this->getMapper()->update($m_contact, array(
            'user_id' => $user,
            'contact_id' => $identity['id']
        ));
        
        $m_contact = $this->getModel()
            ->setAcceptedDate($date)
            ->setAccepted(true);
        $this->getMapper()->update($m_contact, array(
            'user_id' => $identity['id'],
            'contact_id' => $user
        ));
        
        $this->getServiceEvent()->userAddConnection($identity['id'], $user);
        
        return true;
    }

    /**
     * @invokable
     *
     * @param int $user            
     */
    public function remove($user)
    {
        $identity = $this->getServiceUser()->getIdentity();
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        
        $m_contact = $this->getModel()
            ->setDeletedDate($date)
            ->setDeleted(false);
        $this->getMapper()->update($m_contact, array(
            'user_id' => $user,
            'contact_id' => $identity['id']
        ));
        
        $m_contact = $this->getModel()
            ->setDeletedDate($date)
            ->setDeleted(true);
        $this->getMapper()->update($m_contact, array(
            'user_id' => $identity['id'],
            'contact_id' => $user
        ));
        
        $this->getServiceEvent()->userDeleteConnection($identity['id'], $user);
        
        return true;
    }

    /**
     * @invokable
     *
     * @param int $school            
     */
    public function addBySchool($school)
    {
        return $this->getMapper()->addBySchool($school) / 2;
    }

    /**
     * @invokable
     *
     * @param string $all            
     */
    public function getListRequest($all = false)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];
        $listRequest = $this->getMapper()->getListRequest($me);
        foreach ($listRequest as $request) {
            $request->setContact($this->getServiceUser()
                ->get($request->getContactId()));
        }
        
        return $listRequest;
    }

    /**
     * @invokable
     *
     * @param int $user            
     * @param array $exclude            
     */
    public function getList($user = null, $exclude = null)
    {
        if (null === $user) {
            $user = $this->getServiceUser()->getIdentity();
        }
        
        if (! $user['id']) {
            throw new \Exception('user parameter without id');
        }
        
        $listRequest = $this->getMapper()->getList($user['id'], $exclude);
        foreach ($listRequest as $request) {
            $request->setContact($this->getServiceUser()
                ->get($request->getContactId()));
        }
        
        return $listRequest;
    }

    public function getListId($user = null)
    {
        if (null === $user) {
            $user = $this->getServiceUser()->getIdentity()['id'];
        }
        
        $listRequest = $this->getMapper()->getList($user);
        
        $ret = [];
        
        foreach ($listRequest as $request) {
            $ret[] = $request->getContactId();
        }
        
        return $ret;
    }

    /**
     *
     * @return \Application\Service\Event
     */
    public function getServiceEvent()
    {
        return $this->getServiceLocator()->get('app_service_event');
    }

    /**
     *
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
