<?php
/**
 * TheStudnet (http://thestudnet.com).
 *
 * Bank Question Item
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class BankQuestionItem.
 */
class BankQuestionItem extends AbstractService
{
    /**
     * Add Bank Question Item.
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
        foreach ($data as $bqm) {
            $libelle = $bqm['libelle'];
            $answer = (isset($bqm['answer'])) ? $bqm['answer'] : null;
            $percent = (isset($bqm['percent'])) ? $bqm['percent'] : null;
            $order_id = (isset($bqm['order_id'])) ? $bqm['order_id'] : null;

            $ret[] = $this->_add($bank_question_id, $libelle, $percent, $answer, $order_id);
        }

        return $ret;
    }

    /**
     * replace Bank Question Item.
     *
     * @param int   $bank_question_id
     * @param array $data
     *
     * @return int
     */
    public function replace($bank_question_id, $data)
    {
        $m_bank_question_item = $this->getModel()->setBankQuestionId($bank_question_id);

        $this->getMapper()->delete($m_bank_question_item);

        return $this->add($bank_question_id, $data);
    }

    /**
     * Copy Get Bank Question Item.
     *
     * @param int $bank_question_id_new
     * @param int $bank_question_id_old
     *
     * @return bool
     */
    public function copy($bank_question_id_new, $bank_question_id_old)
    {
        $res_bank_question_item = $this->getMapper()->select($this->getModel()
            ->setBankQuestionId($bank_question_id_old));

        foreach ($res_bank_question_item as $m_bank_question_item) {
            $bank_question_item_id_old = $m_bank_question_item->getId();
            $this->getMapper()->insert($m_bank_question_item->setBankQuestionId($bank_question_id_new)
                ->setId(null));
            $bank_question_item_id_new = $this->getMapper()->getLastInsertValue();
            $this->getServiceBankAnswerItem()->copy($bank_question_item_id_new, $bank_question_item_id_old);
        }

        return true;
    }

    /**
     * General Add Bank Question Item.
     *
     * @param int    $bank_question_id
     * @param string $libelle
     * @param int    $percent
     * @param string $answer
     * @param int    $order_id
     *
     * @throws \Exception
     *
     * @return int
     */
    public function _add($bank_question_id, $libelle, $percent = null, $answer = null, $order_id = null)
    {
        $m_bank_question_item = $this->getModel()
            ->setBankQuestionId($bank_question_id)
            ->setLibelle($libelle);

        if ($this->getMapper()->insert($m_bank_question_item) <= 0) {
            throw new \Exception('error insert tag');
        }

        $m_bank_question_item_id = $this->getMapper()->getLastInsertValue();
        $this->getServiceBankAnswerItem()->add($m_bank_question_item_id, $percent, $answer);

        return $m_bank_question_item_id;
    }

    /**
     * Get List Bank Question Item.
     *
     * @param int $bank_question_id
     *
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getList($bank_question_id)
    {
        return $this->getMapper()->getList($bank_question_id);
    }

    /**
     * Get Bank Question Item.
     *
     * @param int $id
     *
     * @return \Application\Model\BankQuestionItem
     */
    public function get($id)
    {
        return $this->getMapper()
            ->select($this->getModel()
            ->setId($id))
            ->current();
    }

    /**
     * Get Service BankAnswerItem.
     *
     * @return \Application\Service\BankAnswerItem
     */
    private function getServiceBankAnswerItem()
    {
        return $this->getServiceLocator()->get('app_service_bank_answer_item');
    }
}
