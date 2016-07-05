<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class ConversationDoc extends AbstractService
{
    public function add($conversation_id, $library_id) 
    {
        return $this->getMapper()->insert($this->getModel()
            ->setConversationId($conversation_id)
            ->setLibraryId($library_id));
    }
    
    public function delete($library_id)
    {
        $res_conversation_doc = $this->getMapper()->select($this->getModel()
            ->setLibraryId($library_id));
    
        foreach ($res_conversation_doc as $m_conversation_doc) {
            $this->getMapper()->delete($this->getModel()
                ->setLibraryId($m_conversation_doc->getLibraryId()));
            
            $this->getServiceLibrary()->delete($m_conversation_doc->getLibraryId());
        }
    }
    
    /**
     *
     * @return \Application\Service\Library
     */
    public function getServiceLibrary()
    {
        return $this->getServiceLocator()->get('app_service_library');
    }
}