<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Question extends AbstractService
{
    /**
     * @param int $component
     *
     * @return \Application\Model\Question
     */
    public function getRand($component)
    {
        return $this->getMapper()
            ->selectRand($component)
            ->current();
    }

    /**
     * @invokable
     *
     * @param string $text
     * @param string $component
     */
    public function add($text, $component)
    {
        $m_question = $this->getModel()
            ->setText($text)
            ->setComponentId($component);

        if ($this->getMapper()->insert($m_question) <= 0) {
            throw new \Eception('error insert question');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * @invokable
     *
     * @param int    $id
     * @param string $text
     * @param string $component
     */
    public function update($id, $text, $component)
    {
        $m_question = $this->getModel()
            ->setId($id)
            ->setText($text)
            ->setComponentId($component);

        return $this->getMapper()->update($m_question);
    }

    /**
     * @invokable
     *
     * @param int $id
     */
    public function delete($id)
    {
        $m_question = $this->getModel()
            ->setId($id)
            ->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        return $this->getMapper()->update($m_question);
    }

    /**
     * @invokable
     * 
     * @param string $filter
     * @param string $questionnaire
     * @param string $dimension
     * @param string $search
     */
    public function getList($questionnaire = null, $filter = null, $dimension = null, $search = null)
    {
        $mapper = $this->getMapper();

        $res_question = $mapper->usePaginator($filter)->getList($questionnaire, $dimension, $search);

        return (null !== $filter) ?
            array('count' => $mapper->count(),'list' => $res_question) :
            $res_question;
    }
}
