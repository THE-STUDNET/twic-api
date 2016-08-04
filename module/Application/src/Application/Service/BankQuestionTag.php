<?php
/**
 * TheStudnet (http://thestudnet.com).
 *
 * BankQuestionTag
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class BankQuestionTag.
 */
class BankQuestionTag extends AbstractService
{
    /**
     * Add Bank Question Tag.
     * 
     * @param int   $bank_question_id
     * @param array $data
     *
     * @throws \Exception
     *
     * @return int
     */
    public function add($bank_question_id, $data)
    {
        $ret = [];
        foreach ($data as $name) {
            $ret[] = $this->_add($bank_question_id, $name);
        }

        return $ret;
    }

    /**
     * Replace Bank Question Tag.
     *
     * @param int   $bank_question_id
     * @param array $data
     *
     * @return int
     */
    public function replace($bank_question_id, $data)
    {
        $this->getMapper()->delete($this->getModel()
            ->setBankQuestionId($bank_question_id));

        return $this->add($bank_question_id, $data);
    }

    /**
     * Copy Bank Question Tag.
     *
     * @param int $bank_question_id_new
     * @param int $bank_question_id_old
     *
     * @return bool
     */
    public function copy($bank_question_id_new, $bank_question_id_old)
    {
        $res_bank_question_tag = $this->getMapper()->select($this->getModel()
            ->setBankQuestionId($bank_question_id_old));

        foreach ($res_bank_question_tag as $m_bank_question_tag) {
            $this->getMapper()->insert($m_bank_question_tag->setBankQuestionId($bank_question_id_new));
        }

        return true;
    }

    /**
     * Add Bank Question Tag.
     *
     * @param int    $bank_question_id
     * @param string $name
     *
     * @throws \Exception
     *
     * @return int
     */
    public function _add($bank_question_id, $name)
    {
        $m_bank_question_tag = $this->getModel()
            ->setBankQuestionId($bank_question_id)
            ->setName($name);

        if ($this->getMapper()->insert($m_bank_question_tag) <= 0) {
            throw new \Exception('error insert tag');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * Get List Bank Question Tag.
     *
     * @invokable
     *
     * @param int    $bank_question_id
     * @param int    $course_id
     * @param string $search
     *
     * @return array
     */
    public function getList($bank_question_id = null, $course_id = null, $search = null)
    {
        $ret = [];
        $res_bank_question_tag = $this->getMapper()->getList($bank_question_id, $course_id, $search);
        foreach ($res_bank_question_tag as $m_bank_question_tag) {
            $ret[] = $m_bank_question_tag->getName();
        }

        return $ret;
    }
}
