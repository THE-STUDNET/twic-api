<?php

namespace Application\Model;

use Application\Model\Base\ItemProg as BaseItemProg;

class ItemProg extends BaseItemProg
{
    protected $users;
    protected $item;
    protected $editable;
    protected $item_assignment;
    protected $item_grade;
    protected $videoconf_archives;
   
    public function exchangeArray(array &$data)
    {
        if ($this->isRepeatRelational()) {
            return;
        }

        parent::exchangeArray($data);

        $this->item = new Item($this);
        $this->item_assignment = new ItemAssignment($this);
        $this->item_grade = new ItemGrading($this);

        $this->item->exchangeArray($data);
        $this->item_assignment->exchangeArray($data);
        $this->item_grade->exchangeArray($data);
    }

    public function getVideoconfArchives()
    {
        return $this->videoconf_archives;
    }
    
    public function setVideoconfArchives($videoconf_archives)
    {
        $this->videoconf_archives = $videoconf_archives;
    
        return $this;
    }

    public function setItemAssignment($item_assignment)
    {
        $this->item_assignment = $item_assignment;

        return $this;
    }

    public function getItemAssignment()
    {
        return $this->item_assignment;
    }

    public function setItemGrade($item_grade)
    {
        $this->item_grade = $item_grade;

        return $this;
    }

    public function getItemGrade()
    {
        return $this->item_grade;
    }

    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function setItem($item)
    {
        $this->item = $item;

        return $this;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function getEditable()
    {
        return $this->editable;
    }

    public function setEditable($editable)
    {
        $this->editable = $editable;

        return $this;
    }
}
