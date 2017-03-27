<?php

namespace Application\Model\Base;

use Dal\Model\AbstractModel;

class MessageDoc extends AbstractModel
{
 	protected $id;
	protected $token;
	protected $name;
	protected $message_id;
	protected $library_id;
	protected $type;
	protected $created_date;

	protected $prefix = 'message_doc';

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	public function getToken()
	{
		return $this->token;
	}

	public function setToken($token)
	{
		$this->token = $token;

		return $this;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	public function getMessageId()
	{
		return $this->message_id;
	}

	public function setMessageId($message_id)
	{
		$this->message_id = $message_id;

		return $this;
	}

	public function getLibraryId()
	{
		return $this->library_id;
	}

	public function setLibraryId($library_id)
	{
		$this->library_id = $library_id;

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