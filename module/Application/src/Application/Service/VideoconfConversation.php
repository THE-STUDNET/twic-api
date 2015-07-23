<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class VideoconfConversation extends AbstractService
{
    /**
     * @param int $conversation
     * @param int $videoconf
     *
     * @throws \Exception
     *
     * @return int
     */
    public function add($conversation, $videoconf)
    {
        if ($this->getMapper()->insert($this->getModel()->setConversationId($conversation)->setVideoconfId($videoconf)) <= 0) {
            throw new \Exception('error insert videoconf conversation');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * @param int $conversation
     * @param int $videoconf
     */
    public function delete($conversation, $videoconf)
    {
        return $this->getMapper()->delete($this->getModel()->setConversationId($conversation)->setVideoconfId($videoconf));
    }
    
    /**
     * 
     * @param integer $videoconf
     * @param integer $user
     */
    public function getByVideoconfUser($videoconf, $user)
    {
        return $this->getMapper()->getByVideoconfUser($videoconf, $user);
    }
}
