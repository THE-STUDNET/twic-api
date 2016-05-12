<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use Application\Model\Conversation as ModelConversation;

class ConversationUser extends AbstractService
{
    /**
     * @invokable
     * 
     * @param array $users
     * @param int   $type
     */
    public function getConversationByUser(array $users, $type = null)
    {
        $conversation_id = null;
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        if (!in_array($user_id , $users)) {
            $users[] = $user_id;
        }

        $res_conversation_user = $this->getMapper()->getConversationByUser($users, $type);  
        if ($res_conversation_user->count() === 1) {
            $conversation_id = $res_conversation_user->current()->getConversationId();
        } elseif ($res_conversation_user->count() === 0) {
            $conversation_id = $this->getServiceConversation()->create($type, null, $users);
        } elseif ($res_conversation_user->count() > 1) {
            throw new \Exception('more of one conversation');
        }

        return $conversation_id;
    }
    
    /**
     * @invokable
     *
     * @param integer $submission_id
     */
    public function getListConversationBySubmission($submission_id)
    {
        return $this->getServiceConversation()->getListBySubmission($submission_id);
    }

    /**
     * @param int $conversation
     *
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getUserByConversation($conversation)
    {
        return $this->getMapper()->select($this->getModel()->setConversationId($conversation));
    }

    /**
     * @param intger $conversation_id
     * @param array $users
     * 
     * @return []
     */
    public function add($conversation_id, $users)
    {
        if(!is_array($users)) {
            $users = [$users];
        }
        
        $ret = [];
        foreach ($users as $user) {
            $ret[$user] = $this->getMapper()->add($conversation_id, $user);
        }

        return $ret;
    }

    /**
     * @param intger $conversation_id
     * @param array $users
     * @return []
     */
    public function replace($conversation_id, $users)
    {
        $this->getMapper()->deleteNotIn($conversation, $users);

        return $this->add($conversation, $users);
    }

    /**
     * @return \Application\Service\Conversation
     */
    public function getServiceConversation()
    {
        return $this->getServiceLocator()->get('app_service_conversation');
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }

}
