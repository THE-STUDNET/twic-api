<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Item extends AbstractService
{
	/**
	 * @invokable
	 * 
	 * @param integer $course
	 * @param integer $grading_policy
	 * @param string $title
	 * @param string $describe
	 * @param string $type
	 * @param integer $weight
	 * @param integer $parent
	 * @param integer $module
	 * @throws \Exception
	 * @return integer
	 */
	public function add($course, $grading_policy, $title = null, $describe = null, $type = null, $weight = null, $parent = null, $module = null)
	{
		$m_item = $this->getModel()->setTitle($title)
		                 ->setDescribe($describe)
		                 ->setType($type)
		                 ->setWeight($weight)
		                 ->setCourse($course)
		                 ->setParent($parent)
		                 ->setGradingPolicy($grading_policy)
		                 ->setModule($module);
		
		if($this->getMapper()->insert($m_item) <= 0) {
			throw new \Exception('error insert item');
		}
		
		return $this->getMapper()->getLastInsertValue();
	}
	
	/**
	 * 
	 * Get Item by Type
	 * 
	 * @invokable
	 * 
	 * @param integer $course
	 * @param integer $type
	 * @return \Dal\Db\ResultSet\ResultSet
	 */
	public function getItemByType($course, $type)
	{
		$m_item = $this->getModel()->setType($type)->setCourse($course);
		
		return $this->getMapper()->select($m_item);
	}
}
