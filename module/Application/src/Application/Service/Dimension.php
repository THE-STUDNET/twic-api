<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Dimension extends AbstractService
{
    /**
     * GetList.
     * 
     * @invokable
     * 
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getList($filter = null, $search = null)
    {
        $mapper = $this->getMapper();
        $res_dimension = $mapper->usePaginator($filter)->getList($search);

        foreach ($res_dimension as $m_dimension) {
            $res_component = $this->getServiceComponent()->getList($m_dimension->getId());
            $m_dimension->setComponent($res_component->count() ? $res_component : array());
        }

        return array('count' => $mapper->count(), 'list' => $res_dimension);
    }

    /**
     * Add Dimnsion.
     *
     * @invokable
     *
     * @param string $name
     * @param string $describe
     *
     * @throws \Eception
     *
     * @return int
     */
    public function add($name, $describe)
    {
        $m_dimension = $this->getModel()
            ->setName($name)
            ->setDescribe($describe);

        if ($this->getMapper()->insert($m_dimension) <= 0) {
            throw new \Eception('error insert dimension');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * Update Dimension.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $name
     * @param string $describe
     *
     * @return int
     */
    public function update($id, $name, $describe)
    {
        $m_dimension = $this->getModel()
            ->setId($id)
            ->setName($name)
            ->setDescribe($describe);

        return $this->getMapper()->update($m_dimension);
    }

    /**
     * Delete Dimension (update deleted date ).
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id)
    {
        $m_dimension = $this->getModel()
            ->setId($id)
            ->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        return $this->getMapper()->update($m_dimension);
    }

    /**
     * @invokable
     * 
     * @param int $school
     */
    public function getEqCq($school)
    {
        return $this->getMapper()->getEqCq($school);
    }

    /**
     * @return \Application\Service\Component
     */
    public function getServiceComponent()
    {
        return $this->getServiceLocator()->get('app_service_component');
    }
}
