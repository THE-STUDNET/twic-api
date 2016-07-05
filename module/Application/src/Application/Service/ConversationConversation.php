<?php
namespace Application\Service;

use Dal\Service\AbstractService;

class ConversationConversation extends AbstractService
{

    public function add($id, $conversation_id)
    {
        return $this->getMapper()->insert($this->getModel()
            ->setId($id)
            ->setConversationId($conversation_id));
    }

    public function getList($conversation_id, $user_id = null)
    {
        if (null === $user_id) {
            $user_id = $this->getServiceUser()->getIdentity()['id'];
        }
        
        return $this->getMapper()->getList($conversation_id, $user_id);
    }

    public function delete($conversation_id)
    {
        $res_conversation_conversation = $this->getMapper()->select($this->getModel()
            ->setConversationId($conversation_id));
        
        foreach ($res_conversation_conversation as $m_conversation_conversation) {
            $this->getMapper()->delete($this->getModel()
                ->setConversationId($m_conversation_conversation->getConversationId()));
            
            $this->getServiceConversation()->delete($m_conversation_conversation->getConversationId());
        }
    }

    /**
     *
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }

    /**
     *
     * @return \Application\Service\Conversation
     */
    public function getServiceConversation()
    {
        return $this->getServiceLocator()->get('app_service_conversation');
    }
}