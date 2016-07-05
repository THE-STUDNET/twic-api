<?php
namespace Application\Service;

use Dal\Service\AbstractService;

class ConversationWhiteboard extends AbstractService
{

    public function add($conversation_id, $whiteboard_id)
    {
        return $this->getMapper()->insert($this->getModel()
            ->setConversationId($conversation_id)
            ->setWhiteboardId($whiteboard_id));
    }

    public function delete($whiteboard_id)
    {
        $res_conversation_whiteboard = $this->getMapper()->select($this->getModel()
            ->setWhiteboardId($whiteboard_id));
        
        foreach ($res_conversation_whiteboard as $m_conversation_whiteboard) {
            $this->getMapper()->delete($this->getModel()
                ->setWhiteboardId($m_conversation_whiteboard->getWhiteboardId()));
            
            $this->getServiceWhiteboard()->delete($m_conversation_whiteboard->getWhiteboardId());
        }
    }

    /**
     *
     * @return \Application\Service\Whiteboard
     */
    public function getServiceWhiteboard()
    {
        return $this->getServiceLocator()->get('app_service_whiteboard');
    }
}