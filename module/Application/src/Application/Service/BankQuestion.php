<?php
/**
 * TheStudnet (http://thestudnet.com).
 *
 * Bank Question
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class BankQuestion.
 */
class BankQuestion extends AbstractService
{
    /**
     * Add Question to bank.
     *
     * @invokable
     *
     * @param int   $course_id
     * @param array $data
     *
     * @return array
     */
    public function add($course_id, $data)
    {
        $ret = [];
        foreach ($data as $bq) {
            $question = (isset($bq['question'])) ? $bq['question'] : null;
            $bank_question_type_id = (isset($bq['bank_question_type_id'])) ? $bq['bank_question_type_id'] : null;
            $point = (isset($bq['point'])) ? $bq['point'] : null;
            $bank_question_item = (isset($bq['bank_question_item'])) ? $bq['bank_question_item'] : null;
            $bank_question_tag = (isset($bq['bank_question_tag'])) ? $bq['bank_question_tag'] : null;
            $bank_question_media = (isset($bq['bank_question_media'])) ? $bq['bank_question_media'] : null;
            $name = (isset($bq['name'])) ? $bq['name'] : null;

            $ret[] = $this->_add($course_id, $question, $bank_question_type_id, $point, $bank_question_item, $bank_question_tag, $bank_question_media, $name);
        }

        return $ret;
    }

    /**
     * Update Bank question.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $question
     * @param int    $bank_question_type_id
     * @param int    $point
     * @param array  $bank_question_item
     * @param array  $bank_question_tag
     * @param array  $bank_question_media
     * @param string $name
     *
     * @return int
     */
    public function update($id, $question = null, $bank_question_type_id = null, $point = null, $bank_question_item = null, $bank_question_tag = null, $bank_question_media = null, $name = null)
    {
        $bank_question_id = $this->copy($id);

        $m_bank_question = $this->getModel()
            ->setId($bank_question_id)
            ->setQuestion($question)
            ->setBankQuestionTypeId($bank_question_type_id)
            ->setPoint($point)
            ->setName($name);

        $ret = $this->getMapper()->update($m_bank_question);

        if (null !== $bank_question_media) {
            $this->getServiceBankQuestionMedia()->replace($bank_question_id, $bank_question_media);
        }

        if (null !== $bank_question_tag) {
            $this->getServiceBankQuestionTag()->replace($bank_question_id, $bank_question_tag);
        }

        if (null !== $bank_question_item) {
            $this->getServiceBankQuestionItem()->replace($bank_question_id, $bank_question_item);
        }

        return $ret;
    }

    /**
     * Get With Poll Exist.
     *
     * @param int $id
     *
     * @return null|\Application\Model\BankQuestion
     */
    public function getWithPollItemExist($id)
    {
        $res_bank_question = $this->getMapper()->getWithPollItemExist($id);

        return ($res_bank_question->count() > 0) ? $res_bank_question->current() : null;
    }

    /**
     * Copy Bank Question.
     *
     * Si utiliser il copy et on retour le nouvelle id,
     * si utiliser mais for_delete alors on le passe simplement a older.
     *
     * @param int  $id
     * @param bool $for_delete
     *
     * @return int
     */
    public function copy($id, $for_delete = false)
    {
        $m_bank_question = $this->getWithPollItemExist($id);
        if (null === $m_bank_question) {
            return $id;
        }

        $bank_question_id = null;
        if (!$for_delete) {
            $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $m_bank_question->setId(null)
                ->setOlder(null)
                ->setCreatedDate($date);

            $this->getMapper()->insert($m_bank_question);
            $bank_question_id = $this->getMapper()->getLastInsertValue();

            $this->getServiceBankQuestionMedia()->copy($bank_question_id, $id);
            $this->getServiceBankQuestionTag()->copy($bank_question_id, $id);
            $this->getServiceBankQuestionItem()->copy($bank_question_id, $id);
        }

        $this->getMapper()->update(
            $this->getModel()
                ->setOlder((null === $bank_question_id) ? $id : $bank_question_id), ['id' => $id]
        );

        return $bank_question_id;
    }

    /**
     * Delete Bank question.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return array
     */
    public function delete($id)
    {
        if (!is_array($id)) {
            $id = [$id];
        }

        $ret = [];
        foreach ($id as $i) {
            if ($this->copy($i, true) === $i) {
                $ret[$i] = $this->getMapper()->delete(
                    $this->getModel()
                        ->setId($i)
                );
            } else {
                $ret[$i] = true;
            }
        }

        return $ret;
    }

    /**
     * Add Bank Question (Global).
     *
     * @param int    $course_id
     * @param string $question
     * @param int    $bank_question_type_id
     * @param int    $point
     * @param array  $bank_question_item
     * @param array  $bank_question_tag
     * @param array  $bank_question_media
     * @param string $name
     *
     * @throws \Exception
     *
     * @return int
     */
    public function _add($course_id, $question, $bank_question_type_id, $point, $bank_question_item = null, $bank_question_tag = null, $bank_question_media = null, $name)
    {
        $m_bank_question = $this->getModel()
            ->setQuestion($question)
            ->setBankQuestionTypeId($bank_question_type_id)
            ->setPoint($point)
            ->setName($name)
            ->setCourseId($course_id)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        if ($this->getMapper()->insert($m_bank_question) <= 0) {
            throw new \Exception('error add bank question');
        }

        $bank_question_id = $this->getMapper()->getLastInsertValue();

        if (null !== $bank_question_tag) {
            $this->getServiceBankQuestionTag()->add($bank_question_id, $bank_question_tag);
        }

        if (null !== $bank_question_item) {
            $this->getServiceBankQuestionItem()->add($bank_question_id, $bank_question_item);
        }

        if (null !== $bank_question_media) {
            $this->getServiceBankQuestionMedia()->add($bank_question_id, $bank_question_media);
        }

        return $bank_question_id;
    }

    /**
     * Get List.
     *
     * @invokable
     *
     * @param int    $course_id
     * @param array  $filter
     * @param string $search
     * @param bool   $older
     *
     * @return \Dal\Db\ResultSet\ResultSet|array
     */
    public function getList($course_id, $filter = null, $search = null, $older = false)
    {
        $mapper = (null !== $filter) ? $this->getMapper()->usePaginator($filter) : $this->getMapper();

        $res_bank_question = $mapper->getList($course_id, $search, $older);

        foreach ($res_bank_question as $m_bank_question) {
            $bank_question_id = $m_bank_question->getId();
            $m_bank_question->setBankQuestionItem(
                $this->getServiceBankQuestionItem()
                    ->getList($bank_question_id)
            );
            $m_bank_question->setBankQuestionMedia(
                $this->getServiceBankQuestionMedia()
                    ->getList($bank_question_id)
            );
            $m_bank_question->setBankQuestionTag(
                $this->getServiceBankQuestionTag()
                    ->getList($bank_question_id)
            );
        }

        return (null !== $filter) ? ['list' => $res_bank_question, 'count' => $mapper->count()] : $res_bank_question;
    }

    /**
     * Get List Lite.
     *
     * @param int $ids
     *
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getListLite($ids)
    {
        return $this->getMapper()->select(
            $this->getModel()
                ->setId($ids)
        );
    }

    /**
     * Get Bank Question.
     *
     * @param int $id
     *
     * @return \Application\Model\BankQuestion
     */
    public function get($id)
    {
        return $this->getMapper()
            ->select(
                $this->getModel()
                    ->setId($id)
            )
            ->current();
    }

    /**
     * Get Service BankQuestionMedia.
     *
     * @return \Application\Service\BankQuestionMedia
     */
    private function getServiceBankQuestionMedia()
    {
        return $this->container->get('app_service_bank_question_media');
    }

    /**
     * Get Service BankQuestionTag.
     *
     * @return \Application\Service\BankQuestionTag
     */
    private function getServiceBankQuestionTag()
    {
        return $this->container->get('app_service_bank_question_tag');
    }

    /**
     * Get Service Bank Question Item.
     *
     * @return \Application\Service\BankQuestionItem
     */
    private function getServiceBankQuestionItem()
    {
        return $this->container->get('app_service_bank_question_item');
    }
}
