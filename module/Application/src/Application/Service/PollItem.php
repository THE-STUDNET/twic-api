<?php
/**
 * TheStudnet (http://thestudnet.com).
 *
 * Poll Item
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class PollItem.
 */
class PollItem extends AbstractService
{
    /**
     * Add Poll Item.
     *
     * @param int   $poll_id
     * @param array $data
     *
     * @return array
     */
    public function add($poll_id, $data = [])
    {
        if (empty($data)) {
            return;
        }

        $ret = [];
        foreach ($data as $d) {
            $nb_point = (isset($d['nb_point'])) ? $d['nb_point'] : null;
            $bank_question_id = (isset($d['bank_question_id'])) ? $d['bank_question_id'] : null;
            $group_question = (isset($d['group_question'])) ? $d['group_question'] : null;
            $nb = (isset($d['nb'])) ? $d['nb'] : null;
            $is_mandatory = (isset($d['is_mandatory'])) ? $d['is_mandatory'] : null;
            $order_id = (isset($d['order_id'])) ? $d['order_id'] : null;

            $ret[] = $this->_add($poll_id, $nb_point, $bank_question_id, $group_question, $nb, $is_mandatory, $order_id);
        }

        return $ret;
    }

    /**
     * Add General Poll Item.
     *
     * @param int   $poll_id
     * @param int   $nb_point
     * @param int   $bank_question_id
     * @param array $group_question
     * @param int   $nb
     * @param bool  $is_mandatory
     * @param int   $order_id
     *
     * @throws \Exception
     *
     * @return int
     */
    public function _add($poll_id, $nb_point = null, $bank_question_id = null, $group_question = null, $nb = null, $is_mandatory = null, $order_id = null)
    {
        $group_question_id = ($group_question !== null) ? $this->getServiceGroupQuestion()->add($group_question, $nb) : null;

        $m_question = $this->getModel()
            ->setIsMandatory($is_mandatory)
            ->setPollId($poll_id)
            ->setBankQuestionId($bank_question_id)
            ->setNbPoint($nb_point)
            ->setGroupQuestionId($group_question_id)
            ->setOrderId($order_id);

        if ($this->getMapper()->insert($m_question) < 1) {
            throw new \Exception('Insert question error');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * replace poll item.
     *
     * @invokable
     *
     * @param int   $poll_id
     * @param array $data
     *
     * @return array
     */
    public function replace($poll_id, $data = [])
    {
        $this->delete($poll_id);

        return $this->add($poll_id, $data);
    }

    /**
     * Delete Poll Item.
     *
     * @param int $poll_id
     *
     * @return bool
     */
    public function delete($poll_id)
    {
        return $this->getMapper()->delete($this->getModel()->setPollId($poll_id));
    }

    /**
     * Get Poll Item.
     *
     * @param int $id
     *
     * @return \Application\Model\PollItem
     */
    public function get($id)
    {
        return $this->getMapper()->select($this->getModel()->setId($id))->current();
    }

    /**
     * Get List Poll Item.
     *
     * @param int $poll_id
     *
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getList($poll_id)
    {
        $res_poll_item = $this->getMapper()->select(
            $this->getModel()
                ->setPollId($poll_id)
        );

        if ($res_poll_item->count() <= 0) {
            return;
        }

        foreach ($res_poll_item as $m_poll_item) {
            $m_poll_item->setGroupQuestion($this->getServiceGroupQuestion()->getList($m_poll_item->getGroupQuestionId()));
        }

        return $res_poll_item;
    }

    /**
     * Get List Lite Poll Item.
     *
     * @param int $poll_id
     *
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getListLite($poll_id)
    {
        return $this->getMapper()->select($this->getModel()->setPollId($poll_id));
    }

    /**
     * Get Service GroupQuestion.
     *
     * @return \Application\Service\GroupQuestion
     */
    private function getServiceGroupQuestion()
    {
        return $this->container->get('app_service_group_question');
    }
}
