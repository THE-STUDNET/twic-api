<?php

namespace Application\Model\Base;

use Dal\Model\AbstractModel;

class Item extends AbstractModel
{
 	protected $id;
	protected $title;
	protected $describe;
	protected $duration;
	protected $type;
	protected $weight;
	protected $course_id;
	protected $set_id;
	protected $grading_policy_id;
	protected $parent_id;

	protected $prefix = 'item';

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	public function getDescribe()
	{
		return $this->describe;
	}

	public function setDescribe($describe)
	{
		$this->describe = $describe;

		return $this;
	}

	public function getDuration()
	{
		return $this->duration;
	}

	public function setDuration($duration)
	{
		$this->duration = $duration;

		return $this;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType($type)
	{
		$this->type = $type;

		return $this;
	}

	public function getWeight()
	{
		return $this->weight;
	}

	public function setWeight($weight)
	{
		$this->weight = $weight;

		return $this;
	}

	public function getCourseId()
	{
		return $this->course_id;
	}

	public function setCourseId($course_id)
	{
		$this->course_id = $course_id;

		return $this;
	}

	public function getSetId()
	{
		return $this->set_id;
	}

	public function setSetId($set_id)
	{
		$this->set_id = $set_id;

		return $this;
	}

	public function getGradingPolicyId()
	{
		return $this->grading_policy_id;
	}

	public function setGradingPolicyId($grading_policy_id)
	{
		$this->grading_policy_id = $grading_policy_id;

		return $this;
	}

	public function getParentId()
	{
		return $this->parent_id;
	}

	public function setParentId($parent_id)
	{
		$this->parent_id = $parent_id;

		return $this;
	}

}