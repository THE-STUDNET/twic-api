<?php
/**
 * TheStudnet (http://thestudnet.com).
 *
 * Set
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class Set.
 */
class Set extends AbstractService
{
    /**
     * Add Group Set.
     *
     * @invokable
     *
     * @param int    $course
     * @param string $name
     * @param string $uid
     * @param array  $groups
     * @param bool   $is_used
     *
     * @throws \Exception
     *
     * @return \Application\Model\Set
     */
    public function add($course, $name, $uid = null, $groups = null, $is_used = false)
    {
        $m_set = $this->getModel()
            ->setUid($uid)
            ->setName($name)
            ->setCourseId($course)
            ->setIsUsed($is_used);

        if ($this->getMapper()->insert($m_set) <= 0) {
            throw new \Exception('error insert set group');
        }

        $set_id = $this->getMapper()->getLastInsertValue();

        if (null != $groups) {
            foreach ($groups as $group) {
                $name = (isset($group['name'])) ? $group['name'] : null;
                $uid = (isset($group['uid'])) ? $group['uid'] : null;
                $users = (isset($group['users'])) ? $group['users'] : null;

                $this->getServiceGroup()->add($set_id, $name, $uid, $users);
            }
        }

        return $this->get($set_id);
    }

    /**
     * Delete Group Set.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id)
    {
        return $this->getMapper()->delete($this->getModel()
            ->setId($id));
    }

    /**
     * Update Group Set.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $uid
     * @param string $name
     *
     * @return int
     */
    public function update($id, $uid = null, $name = null)
    {
        return $this->getMapper()->update($this->getModel()
            ->setId($id)
            ->setUid($uid)
            ->setName($name));
    }

    /**
     * Get Group Set.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return \Application\Model\Set
     */
    public function get($id)
    {
        $res_set = $this->getMapper()->select($this->getModel()
            ->setId($id));

        if ($res_set->count() <= 0) {
            throw new \Exception('error select set group');
        }

        $m_set = $res_set->current();
        $m_set->setGroups($this->getServiceGroup()
            ->getList($m_set->getCourseId(), $m_set->getId()));

        return $m_set;
    }

    /**
     * Get List Group Set.
     *
     * @invokable
     *
     * @param int    $course
     * @param string $name
     * @param array  $filter
     *
     * @return array
     */
    public function getList($course, $name = null, $filter = null)
    {
        $mapper = $this->getMapper();
        $res_set = $mapper->usePaginator($filter)->getList($course, $name);
        foreach ($res_set as $m_set) {
            $m_set->setGroups($this->getServiceGroup()
                ->getList($m_set->getCourseId(), $m_set->getId()));
        }

        return ($filter === null) ? $res_set : ['count' => $mapper->count(), 'list' => $res_set];
    }

    /**
     * Get Service Group Set.
     *
     * @return \Application\Service\Group
     */
    private function getServiceGroup()
    {
        return $this->container->get('app_service_group');
    }
}
