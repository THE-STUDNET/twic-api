<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Submission Question
 *
 */

namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class SubQuestion
 */
class SubQuestion extends AbstractService
{
    /**
     * Get List Lite Submission Question
     * 
     * @param int $sub_quiz_id
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getListLite($sub_quiz_id)
    {
        return $this->getMapper()->select($this->getModel()->setSubQuizId($sub_quiz_id));
    }

    /**
     * Get Submission Question
     * 
     * @param int $id
     * @return \Application\Model\SubQuestion
     */
    public function get($id)
    {
        return $this->getMapper()->select($this->getModel()->setId($id))->current();
    }

    /**
     * Update Answered of Submission Question
     * 
     * @param int $id
     * @return int
     */
    public function updateAnswered($id)
    {
        $m_sub_question = $this->getModel()
            ->setId($id)
            ->setAnsweredDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        return $this->getMapper()->update($m_sub_question);
    }

    /**
     * Update Point Submission Question
     * 
     * @param int $id
     * @param int $point
     * @return int
     */
    public function updatePoint($id, $point)
    {
        $m_sub_question = $this->getModel()
            ->setId($id)
            ->setPoint($point);

        return $this->getMapper()->update($m_sub_question);
    }

    /**
     * Add Submission Question
     * 
     * @param int $sub_quiz_id
     * @param int $poll_item_id
     * @param int $bank_question_id
     * @param int $group_question_id
     * @return int
     */
    public function add($sub_quiz_id, $poll_item_id, $bank_question_id, $group_question_id)
    {
        $m_sub_question = $this->getModel()
            ->setSubQuizId($sub_quiz_id)
            ->setPollItemId($poll_item_id)
            ->setBankQuestionId($bank_question_id)
            ->setGroupQuestionId($group_question_id);

        $this->getMapper()->insert($m_sub_question);

        return $this->getMapper()->getLastInsertValue();
    }
}
