<?php

namespace Application\Model\Base;

use Dal\Model\AbstractModel;

class Resume extends AbstractModel
{
 	protected $id;
	protected $start_date;
	protected $end_date;
	protected $address;
	protected $title;
	protected $subtitle;
	protected $logo;
	protected $description;
	protected $type;
	protected $user_id;

	protected $prefix = 'resume';

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	public function getStartDate()
	{
		return $this->start_date;
	}

	public function setStartDate($start_date)
	{
		$this->start_date = $start_date;

		return $this;
	}

	public function getEndDate()
	{
		return $this->end_date;
	}

	public function setEndDate($end_date)
	{
		$this->end_date = $end_date;

		return $this;
	}

	public function getAddress()
	{
		return $this->address;
	}

	public function setAddress($address)
	{
		$this->address = $address;

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

	public function getSubtitle()
	{
		return $this->subtitle;
	}

	public function setSubtitle($subtitle)
	{
		$this->subtitle = $subtitle;

		return $this;
	}

	public function getLogo()
	{
		return $this->logo;
	}

	public function setLogo($logo)
	{
		$this->logo = $logo;

		return $this;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setDescription($description)
	{
		$this->description = $description;

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

	public function getUserId()
	{
		return $this->user_id;
	}

	public function setUserId($user_id)
	{
		$this->user_id = $user_id;

		return $this;
	}

}