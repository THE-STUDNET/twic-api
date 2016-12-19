<?php

namespace Application\Model\Base;

use Dal\Model\AbstractModel;

class PostLike extends AbstractModel
{
    protected $id;
    protected $is_like;
    protected $post_id;
    protected $user_id;
    protected $organization_id;
    protected $page_id;
    protected $created_date;

    protected $prefix = 'post_like';

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

    public function getPostId()
    {
        return $this->post_id;
    }

    public function setPostId($post_id)
    {
        $this->post_id = $post_id;

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

    public function getOrganizationId()
    {
        return $this->organization_id;
    }

    public function setOrganizationId($organization_id)
    {
        $this->organization_id = $organization_id;

        return $this;
    }

    public function getPageId()
    {
        return $this->page_id;
    }

    public function setPageId($page_id)
    {
        $this->page_id = $page_id;

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
