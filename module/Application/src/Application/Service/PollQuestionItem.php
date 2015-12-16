<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class PollQuestionItem extends AbstractService
{
    public function add($question, $question_item)
    {
        $m_question_item = $this->getModel();
        $m_question_item->setQuestionId($question)
        ->setLibelle((isset($question_item['libelle']) ? $question_item['libelle'] : null))
        ->setParentId($this->getMapper()->selectLastParentId($question));
    
        if ($this->getMapper()->insert($m_question_item) < 1) {
            throw new \Exception('Insert question item error');
        }
    
        $question_item_id = $this->getMapper()->getLastInsertValue();
    
        if (isset($question_item['parent'])) {
            $this->updateParentId($question, $question_item_id, $question_item['parent']);
        }
    
        return $question_item_id;
    }
    
    public function updateParentId($question, $question_item, $parent_id)
    {
        $res_question_item = $this->getMapper()->select($this->getModel()->setId($parent_id));
    
        if ($res_question_item->count() > 0 && ($res_question_item = $res_question_item->current()) && $res_question_item->getQuestionId() == $question) {
            $tmp_question_item = $this->getModel();
            $tmp_question_item->setParentId($question_item);
            $this->getMapper()->update($tmp_question_item, array('parent_id' => $parent_id, 'question_id' => $question));
    
            $m_question_item = $this->getModel();
            $m_question_item->setId($question_item);
            $m_question_item->setParentId($parent_id);
    
            $this->getMapper()->update($m_question_item);
        }
    }
    
    public function getList($question)
    {
        $m_question_item = $this->getModel();
        $m_question_item->setQuestionId($question);
    
        return $this->getMapper()->select($m_question_item)->toArrayParent('parent_id', 'id', array('id'));
    }
}