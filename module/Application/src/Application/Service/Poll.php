<?php
namespace Application\Service;

use Dal\Service\AbstractService;

class Poll extends AbstractService
{

    /**
     *
     * @param integer $item_id
     * @param bool $record
     * @param integer $nb_user_autorecord
     * @param bool $allow_intructor
     */
    public function addOrUpdate($item_id, $title = null, $poll_item = null, $expiration = null, $time_limit = null)
    {
        return (null !== ($m_poll = $this->getByItem($item_id))) ?
            $this->update($m_poll->getId(), $title, $poll_item, $expiration, $time_limit):
            $this->add($title, $poll_item, $expiration, $time_limit, $item_id);
    }
    
    /**
     * Add poll for message.
     * 
     * @invokable
     * 
     * @param string $title
     * @param integer $poll_item
     * @param integer $expiration
     * @param integer $time_limit
     * @param integer $item_id
     * @throws \Exception
     */
    public function add($title, $poll_item, $expiration = null, $time_limit = null, $item_id = null)
    {
        $m_poll = $this->getModel();
        $m_poll->setExpirationDate($expiration)
               ->setTitle($title)
               ->setTimeLimit($time_limit)
               ->setItemId($item_id);
        
        if ($this->getMapper()->insert($m_poll) < 1) {
            throw new \Exception('Insert poll error');
        }
        
        $poll_id = $this->getMapper()->getLastInsertValue();
        $this->getServicePollItem()->add($poll_id, $poll_item);
            
        return $this->get($poll_id);
    }
    
    /**
     * update poll
     *
     * @invokable
     *
     * @param intger $id
     * @param string $title
     * @param integer $poll_item
     * @param integer $expiration
     * @param integer $time_limit
     * @param integer $item_id
     */
    public function update($id, $title = null, $poll_item = null, $expiration = null, $time_limit = null, $item_id = null)
    {
        $m_poll = $this->getModel();
        $m_poll->setId($id)
            ->setExpirationDate($expiration)
            ->setTitle($title)
            ->setTimeLimit($time_limit)
            ->setItemId($item_id);
    
        if(null !== $poll_item) {
            $this->getServicePollItem()->replace($id,$poll_item);
        }
        
        return $this->getMapper()->update($m_poll);
    }
    
    /**
     * @invokable
     * 
     * @param integer $id
     * 
     * @throws \Exception
     */
    public function get($id)
    {
        $res_poll = $this->getMapper()->select($this->getModel()->setId($id));
        
        if ($res_poll->count() !== 1) {
            throw new \Exception('poll not exist');
        }
        
        $m_poll = $res_poll->current();
        $m_poll->setPollItem($this->getServicePollItem()->getList($m_poll->getId()));
        
        return $m_poll;
    }
    
    /**
     * @param integer $id
     *
     * @return NULL|\Application\Model\Poll
     */
    public function getLite($id)
    {
        $res_poll = $this->getMapper()->select($this->getModel()->setId($id));
        if ($res_poll->count() !== 1) {
            throw new \Exception('poll not exist');
        }
    
        return $res_poll->current();
    }

    public function getByItem($item_id)
    {
        $res_poll = $this->getMapper()->select($this->getModel()->setItemId($item_id));
        
        if($res_poll->count() <= 0 ) {
            return null;
        }
        
        $m_poll = $res_poll->current();
        $m_poll->setPollItem($this->getServicePollItem()->getList($m_poll->getId()));
        
        return $m_poll;
    }
    
    /**
     * @param integer $item_id
     * 
     * @return NULL|\Application\Model\Poll
     */
    public function getLiteByItem($item_id)
    {
        return $this->getMapper()->select($this->getModel()->setItemId($item_id))->current();
    }

    /**
     * @invokable
     * 
     * @param integer $poll
     * @param integer $poll_question
     * @param array $items
     */
    public function vote($poll, $poll_question, $items)
    {       
        return $this->getServicePollAnswer()->add($poll, $poll_question, $items);
    }
    
    /**
     * @invokable
     *
     * @param integer $id
     */
    public function delete($id)
    {
        return $this->getMapper()->delete($this->getModel()->setId($id));
    }
    
    /**
     *
     * @return \Application\Service\PollAnswer
     */
    public function getServicePollAnswer()
    {
        return $this->getServiceLocator()->get('app_service_poll_answer');
    }
    
    /**
     *
     * @return \Application\Service\PollItem
     */
    public function getServicePollItem()
    {
        return $this->getServiceLocator()->get('app_service_poll_item');
    }
}