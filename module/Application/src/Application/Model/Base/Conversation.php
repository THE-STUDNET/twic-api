<?php

namespace Application\Model\Base;

use Dal\Model\AbstractModel;

class Conversation extends AbstractModel
{
 	protected $id;
	protected $type;
	protected $item_id;
	protected $group_id;
	protected $created_date;

	protected $prefix = 'conversation';

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;

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

	public function getItemId()
	{
		return $this->item_id;
	}

	public function setItemId($item_id)
	{
		$this->item_id = $item_id;

		return $this;
	}

	public function getGroupId()
	{
		return $this->group_id;
	}

	public function setGroupId($group_id)
	{
		$this->group_id = $group_id;

		return $this;
	}

	public function getCreatedDate()
	{
		return $this->created_date;
	}

	public function setCreatedDate($created_date)
	{
		$this->created_date = $created_date;

		return $this;
	}

}