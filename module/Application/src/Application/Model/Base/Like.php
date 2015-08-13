<?php

namespace Application\Model\Base;

use Dal\Model\AbstractModel;

class Like extends AbstractModel
{
 	protected $id;
	protected $is_like;
	protected $user_id;
	protected $feed_id;
	protected $created_date;

	protected $prefix = 'like';

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	public function getIsLike()
	{
		return $this->is_like;
	}

	public function setIsLike($is_like)
	{
		$this->is_like = $is_like;

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

	public function getFeedId()
	{
		return $this->feed_id;
	}

	public function setFeedId($feed_id)
	{
		$this->feed_id = $feed_id;

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