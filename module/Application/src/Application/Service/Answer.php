<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Answer extends AbstractService
{
    /**
     * @param int $question
     * @param int $questionnaire_user
     * @param int $questionnaire_question
     * @param int $peer
     * @param int $scale
     *
     * @throws \Exception
     *
     * @return int
     */
    public function add($question, $questionnaire_user, $questionnaire_question, $peer, $scale)
    {
        $m_answer = $this->getModel()
            ->setQuestionId($question)
            ->setQuestionnaireQuestionId($questionnaire_question)
            ->setQuestionnaireUserId($questionnaire_user)
            ->setScaleId($scale)
            ->setPeerId($peer)
            ->setType((($peer == $this->getServiceUser()->getIdentity()['id']) ? 'SELF' : 'PEER'))
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        if ($this->getMapper()->insert($m_answer) <= 0) {
            throw new \Exception('Error insert add answer');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * @invokable
     * 
     * @param int $item
     * @param int $peer
     */
    public function getList($item = null, $peer = null)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];

        return $this->getMapper()->getList($item, $peer, $me);
    }

    /**
     * @param int $questionnaire_user
     * 
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getByQuestionnaireUser($questionnaire_user)
    {
        $m_answer = $this->getModel()->setQuestionnaireUserId($questionnaire_user);

        return $this->getMapper()->select($m_answer);
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
