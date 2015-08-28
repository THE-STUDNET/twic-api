<?php
namespace Application\Service;

use Dal\Service\AbstractService;

class QuestionnaireQuestion extends AbstractService
{
    public function create($questionnaire)
    {
        $m_questionnaire_question = $this->getModel()->setQuestionnaireId($questionnaire);
        
        $res_component = $this->getServiceComponent()->getList();
        foreach ($res_component as $m_component) {
            $m_question = $this->getServiceQuestion()->getRand($m_component->getId());
            $m_questionnaire_question->setQuestionId($m_question->getId());
            
            $this->getMapper()->insert($m_questionnaire_question);
        }
        
        return true;
    }
    
    /**
     * 
     * @param integer $questionnaire
     * @param integer $question
     * 
     * @return \Application\Model\QuestionnaireQuestion
     */
    public function getByQuestion($questionnaire, $question)
    {
        $m_questionnaire_question = $this->getModel()->setQuestionnaireId($questionnaire)->setQuestionId($question);

        return $this->getMapper()->select($m_questionnaire_question)->current();
    }
   
    /**
     * @return \Application\Service\Component
     */
    public function getServiceComponent()
    {
        return $this->getServiceLocator()->get('app_service_component');
    }
    
    /**
     *
     * @return \Application\Service\Question
     */
    public function getServiceQuestion()
    {
        return $this->getServiceLocator()->get('app_service_question');
    }
}