<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Criteria
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class Criteria
 */
class Criteria extends AbstractService
{

    /**
     * Get criteria.
     *
     * @invokable
     *
     * @param int $id            
     * @return \Application\Model\Criteria
     */
    public function get($id)
    {
        return $this->getMapper()
            ->select($this->getModel()
            ->setId($id))
            ->current();
    }

    /**
     * Get criteria list.
     *
     * @invokable
     *
     * @param int $grading_policy            
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getList($grading_policy)
    {
        return $this->getMapper()->select($this->getModel()
            ->setGradingPolicyId($grading_policy));
    }

    /**
     * Get criteria list.
     *
     * @invokable
     *
     * @param int $item            
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getListByItem($item)
    {
        return $this->getMapper()->getListByItem($item);
    }

    /**
     * Insert criteria.
     *
     * @invokable
     *
     * @param string $name            
     * @param int $points            
     * @param string $description            
     * @param int $grading_policy            
     * @return int
     */
    public function add($name, $points, $description, $grading_policy)
    {
        $m_criteria = $this->getModel()
            ->setName($name)
            ->setPoints($points)
            ->setDescription($description)
            ->setGradingPolicyId($grading_policy);
        
        $this->getMapper()->insert($m_criteria);
        
        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * Delete criteria
     *
     * @invokable
     *
     * @param int $id            
     * @return int
     */
    public function delete($id)
    {
        return $this->getMapper()->delete($this->getModel()
            ->setId($id));
    }

    /**
     * Update criteria
     *
     * @invokable
     *
     * @param int $id            
     * @param string $name            
     * @param int $points            
     * @param string $description            
     * @return int
     */
    public function _update($id, $name, $points, $description)
    {
        $m_criteria = $this->getModel()
            ->setId($id)
            ->setName($name)
            ->setPoints($points)
            ->setDescription($description);
        
        return $this->getMapper()->update($m_criteria);
    }

    /**
     * Update Criteria
     *
     * @param array $data            
     * @param int $grading_policy_id            
     * @return array
     */
    public function update($data, $grading_policy_id)
    {
        $ret = [];
        foreach ($data as $criteria) {
            $name = isset($criteria['name']) ? $criteria['name'] : null;
            $description = isset($criteria['description']) ? $criteria['description'] : null;
            $points = isset($criteria['points']) ? $criteria['points'] : null;
            if (array_key_exists('id', $criteria)) {
                $this->_update($criteria['id'], $name, $points, $description);
                $ret[] = $criteria['id'];
            } else {
                $id = $this->add($name, $points, $description, $grading_policy_id);
                $ret[] = $id;
            }
        }
        
        $this->getMapper()->deleteNotIn($ret, $grading_policy_id);
        
        return $ret;
    }
}
