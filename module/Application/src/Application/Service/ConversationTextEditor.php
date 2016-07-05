<?php
namespace Application\Service;

use Dal\Service\AbstractService;

class ConversationTextEditor extends AbstractService
{

    public function add($conversation_id, $text_editor_id)
    {
        return $this->getMapper()->insert($this->getModel()
            ->setConversationId($conversation_id)
            ->setTextEditorId($text_editor_id));
    }

    public function delete($text_editor_id)
    {
        $res_conversation_text_editor = $this->getMapper()->select($this->getModel()
            ->setTextEditorId($text_editor_id));
        
        foreach ($res_conversation_text_editor as $m_conversation_text_editor) {
            $this->getMapper()->delete($this->getModel()
                ->setTextEditorId($m_conversation_text_editor->getTextEditorId()));
            
            $this->getServiceTextEditor()->delete($m_conversation_text_editor->getTextEditorId());
        }
    }

    /**
     *
     * @return \Application\Service\TextEditor
     */
    public function getServiceTextEditor()
    {
        return $this->getServiceLocator()->get('app_service_text_editor');
    }
}   