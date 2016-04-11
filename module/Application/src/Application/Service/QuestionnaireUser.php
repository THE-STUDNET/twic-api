<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class QuestionnaireUser extends AbstractService
{
    /**
     * @param int $questionnaire
     *
     * @throws \Exception
     * 
     * @return \Application\Model\QuestionnaireUser
     */
    public function get($questionnaire)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];

        $m_questionnaire_user = $this->getModel()
            ->setUserId($me)
            ->setQuestionnaireId($questionnaire);

        $res_questionnaire_user = $this->getMapper()->select($m_questionnaire_user);
        if ($res_questionnaire_user->count() <= 0) {
            $m_questionnaire_user
                ->setSubmissionId($this->getServiceSubmission()->getByUserAndQuestionnaire($me, $questionnaire)->getId())
                ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
            if ($this->getMapper()->insert($m_questionnaire_user) <= 0) {
                throw new \Exception('Error insert questionnaire user');
            }

            $res_questionnaire_user = $this->getMapper()->select($m_questionnaire_user);
        }

        return $res_questionnaire_user->current();
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
    
    /**
     * @return \Application\Service\Submission
     */
    public function getServiceSubmission()
    {
        return $this->getServiceLocator()->get('app_service_submission');
    }
}
