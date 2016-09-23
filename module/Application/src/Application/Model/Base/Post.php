<?php

namespace Application\Model\Base;

use Dal\Model\AbstractModel;

class Post extends AbstractModel
{
 	protected $id;
	protected $key;
	protected $content;
	protected $user_id;
	protected $link;
	protected $video;
	protected $picture;
	protected $name_picture;
	protected $link_title;
	protected $link_desc;
	protected $created_date;
	protected $deleted_date;
	protected $updated_date;
	protected $parent_id;
	protected $t_page_id;
	protected $t_organization_id;
	protected $t_user_id;
	protected $t_course_id;

	protected $prefix = 'post';

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	public function getKey()
	{
		return $this->key;
	}

	public function setKey($key)
	{
		$this->key = $key;

		return $this;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function setContent($content)
	{
		$this->content = $content;

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

	public function getLink()
	{
		return $this->link;
	}

	public function setLink($link)
	{
		$this->link = $link;

		return $this;
	}

	public function getVideo()
	{
		return $this->video;
	}

	public function setVideo($video)
	{
		$this->video = $video;

		return $this;
	}

	public function getPicture()
	{
		return $this->picture;
	}

	public function setPicture($picture)
	{
		$this->picture = $picture;

		return $this;
	}

	public function getNamePicture()
	{
		return $this->name_picture;
	}

	public function setNamePicture($name_picture)
	{
		$this->name_picture = $name_picture;

		return $this;
	}

	public function getLinkTitle()
	{
		return $this->link_title;
	}

	public function setLinkTitle($link_title)
	{
		$this->link_title = $link_title;

		return $this;
	}

	public function getLinkDesc()
	{
		return $this->link_desc;
	}

	public function setLinkDesc($link_desc)
	{
		$this->link_desc = $link_desc;

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

	public function getDeletedDate()
	{
		return $this->deleted_date;
	}

	public function setDeletedDate($deleted_date)
	{
		$this->deleted_date = $deleted_date;

		return $this;
	}

	public function getUpdatedDate()
	{
		return $this->updated_date;
	}

	public function setUpdatedDate($updated_date)
	{
		$this->updated_date = $updated_date;

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

	public function getTPageId()
	{
		return $this->t_page_id;
	}

	public function setTPageId($t_page_id)
	{
		$this->t_page_id = $t_page_id;

		return $this;
	}

	public function getTOrganizationId()
	{
		return $this->t_organization_id;
	}

	public function setTOrganizationId($t_organization_id)
	{
		$this->t_organization_id = $t_organization_id;

		return $this;
	}

	public function getTUserId()
	{
		return $this->t_user_id;
	}

	public function setTUserId($t_user_id)
	{
		$this->t_user_id = $t_user_id;

		return $this;
	}

	public function getTCourseId()
	{
		return $this->t_course_id;
	}

	public function setTCourseId($t_course_id)
	{
		$this->t_course_id = $t_course_id;

		return $this;
	}

}