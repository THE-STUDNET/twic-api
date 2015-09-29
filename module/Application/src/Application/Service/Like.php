<?php
namespace Application\Service;

use Dal\Service\AbstractService;

class Like extends AbstractService
{

    /**
     * @invokable
     *
     * @param integer $event            
     */
    public function add($event)
    {
        $res = null;
        $me = $this->getServiceUser()->getIdentity()['id'];
        
        $m_like = $this->getModel()
            ->setEventId($event)
            ->setUserId($me);
        
        if ($this->getMapper()
            ->select($m_like)
            ->count() > 0) {
            $m_like->setIsLike(true);
            $res = $this->getMapper()->update($m_like, ['event_id' => $event,'user_id' => $me]);
        } else {
            $m_like->setIsLike(true)->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
            
            if ($this->getMapper()->insert($m_like) <= 0) {
                throw new \Exception('error add like');
            }
            
            $this->getServiceEvent()->userLike($event);
            
            $res = $this->getMapper()->getLastInsertValue();
        }
        
        return $res;
    }

    /**
     * @invokable
     *
     * @param integer $event            
     */
    public function delete($event)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];
        
        return $this->getMapper()->update($this->getModel()
            ->setIsLike(false), ['event_id' => $event,'user_id' => $me]);
    }

    /**
     * @invokable
     *
     * @param integer $feed            
     */
    public function getList($feed)
    {
        return $this->getServiceUser()->getList(null, null, null, null, null, null, null, null, null, null, null, $feed);
    }

    /**
     *
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->serviceLocator->get('app_service_user');
    }

    /**
     *
     * @return \Application\Service\Feed
     */
    public function getServiceFeed()
    {
        return $this->serviceLocator->get('app_service_feed');
    }

    /**
     *
     * @return \Application\Service\Contact
     */
    public function getServiceContact()
    {
        return $this->serviceLocator->get('app_service_contact');
    }

    /**
     *
     * @return \Application\Service\Event
     */
    public function getServiceEvent()
    {
        return $this->serviceLocator->get('app_service_event');
    }
}