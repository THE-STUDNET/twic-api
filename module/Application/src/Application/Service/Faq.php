<?php
/**
 * TheStudnet (http://thestudnet.com).
 *
 * Faq
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class Faq.
 */
class Faq extends AbstractService
{
    /**
     * Add ask to faq.
     *
     * @invokable
     *
     * @param string $ask
     * @param string $answer
     * @param int    $course
     *
     * @return int
     */
    public function add($ask, $answer, $course)
    {
        $res = $this->getMapper()->insert(
            $this->getModel()
                ->setAsk($ask)
                ->setAnswer($answer)
                ->setCourseId($course)
                ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
        );

        if ($res <= 0) {
            throw new \Exception('error insert faq');
        }

        return  $this->getMapper()->getLastInsertValue();
    }

    /**
     * Update Faq.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $ask
     * @param string $answer
     *
     * @return int
     */
    public function update($id, $ask = null, $answer = null)
    {
        return $this->getMapper()->update(
            $this->getModel()
                ->setId($id)
                ->setAsk($ask)
                ->setAnswer($answer)
        );
    }

    /**
     * Get list.
     *
     * @invokable
     *
     * @param int $course
     *
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getList($course)
    {
        return $this->getMapper()->select($this->getModel()->setCourseId($course));
    }

    /**
     * Delete ask.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id)
    {
        return $this->getMapper()->delete($this->getModel()->setId($id));
    }
}
