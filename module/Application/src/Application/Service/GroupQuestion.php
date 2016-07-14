<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Group Question
 *
 */

namespace Application\Service;

use Dal\Service\AbstractService;
use Zend\Db\Sql\Predicate\IsNull;

/**
 * Class GroupQuestion
 */
class GroupQuestion extends AbstractService
{
    public function add($group_question, $nb)
    {
        if ($this->getMapper()->insert($this->getModel()->setNb($nb)) <= 0) {
            throw new \Exception('error insert group');
        }

        $group_question_id = $this->getMapper()->getLastInsertValue();
        foreach ($group_question as $bank_question_id) {
            $this->getServiceQuestionRelation()->add($group_question_id, $bank_question_id);
        }

        return $group_question_id;
    }

    /**
     * @param int $group_question_id
     *
     * @return \Application\Model\GroupQuestion
     */
    public function getList($group_question_id)
    {
        if (null === $group_question_id || $group_question_id instanceof IsNull) {
            return;
        }

        $res_group_question = $this->getMapper()->select($this->getModel()->setId($group_question_id));
        if ($res_group_question->count() <= 0) {
            return;
        }

        $m_group_question = $res_group_question->current();
        $res_question_relation = $this->getServiceQuestionRelation()->getList($group_question_id);
        $ret = [];
        foreach ($res_question_relation as $m_question_relation) {
            $ret[] = $m_question_relation->getBankQuestionId();
        }
        $m_group_question->setBankQuestion($ret);

        return $m_group_question;
    }

    /**
     * @return \Application\Service\QuestionRelation
     */
    public function getServiceQuestionRelation()
    {
        return $this->getServiceLocator()->get('app_service_question_relation');
    }
}
