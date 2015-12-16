<?php
namespace Application\Service;

use Dal\Service\AbstractService;

class PollQuestion extends AbstractService
{

    public function add($poll, $question, $question_type, $mandatory = null, $parent = null)
    {
        $m_question = $this->getModel();
        $m_question->setIsMandatory($mandatory)
            ->setPollId($poll)
            ->setQuestion($question)
            ->setQuestionTypeId($question_type)
            ->setParentId($this->getMapper()->selectLastParentId($poll));
        
        if ($this->getMapper()->insert($m_question) < 1) {
            throw new \Exception('Insert question error');
        }
        
        $question_id = $this->getMapper()->getLastInsertValue();
        
        if (null !== $parent) {
            $this->updateParentId($poll, $question_id, $datas['parent']);
        }
        
        if ($question_type == 2 || $question_type == 3) {
            foreach ($question_items as $question_item) {
                $this->getServiceQuestionItem()->add($question_id, $question_item);
            }
        }
        
        return $question_id;
    }

    public function updateParentId($poll, $question, $parent_id)
    {
        $res_question = $this->getMapper()->select($this->getModel()
            ->setId($parent_id));
        
        if ($res_question->count() > 0 && ($res_question = $res_question->current()) && $res_question->getPollId() == $poll) {
            $tmp_question = $this->getModel();
            $tmp_question->setParentId($question);
            $this->getMapper()->update($tmp_question, array('parent_id' => $parent_id,'poll_id' => $poll));
            
            $m_question = $this->getModel();
            $m_question->setId($question);
            $m_question->setParentId($parent_id);
            
            $this->getMapper()->update($m_question);
        }
    }

    public function getList($poll)
    {
        $m_question = $this->getModel();
        $m_question->setPollId($poll);
        
        $res_question = $this->getMapper()->select($m_question);
        
        foreach ($res_question as $m_question) {
            $m_question->setPollQuestionItems($this->getServicePollQuestionItem()
                ->getList($m_question->getId()));
        }
        
        return $res_question->toArrayParent('parent_id', 'id', array('id'));
    }

    /**
     *
     * @return \Application\Service\PollQuestionItem
     */
    public function getServicePollQuestionItem()
    {
        return $this->getServiceLocator()->get('dal_service_poll_question_item');
    }
}