<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class PollAnswer extends AbstractService
{
    public function add($poll, $poll_question, $items)
    {
        $m_poll_answer = $this->getModel()
            ->setPollId($poll)
            ->setPollQuestionId($poll_question)
            ->setUserId($this->getServiceUser()->getIdentity()['id']);

        if ($this->getMapper()->insert($m_poll_answer) <= 0) {
            throw new \Exception('error insertion answer');
        }

        $id = $this->getMapper()->getLastInsertValue();

        foreach ($items as $i) {
            $item = isset($i['item'])  ? $i['item']  : null;
            $answer = isset($i['answer']) ? $i['answer'] : null;
            $date = isset($i['date'])  ? $i['date']  : null;
            $time = isset($i['time'])  ? $i['time']  : null;

            $this->getServicePollAnswerItems()->add($id, $item, $answer, $date,  $time);
        }

        return $id;
    }

    /**
     * @return \Application\Service\PollAnswerItems
     */
    public function getServicePollAnswerItems()
    {
        return $this->getServiceLocator()->get('app_service_poll_answer_items');
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
