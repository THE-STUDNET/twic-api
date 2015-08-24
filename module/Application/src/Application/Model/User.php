<?php

namespace Application\Model;

use Application\Model\Base\User as BaseUser;
use Address\Model\Country;

class User extends BaseUser
{
    protected $school;
    protected $roles;
    protected $available;
    protected $selected;
    protected $contact_state;
    protected $gender;
    protected $nationality;
    protected $origin;

    public function exchangeArray(array &$data)
    {
        if ($this->isRepeatRelational()) {
            return;
        }

        parent::exchangeArray($data);

        $this->school = new School($this);
        $this->nationality = new Country($this, 'nationality');
        $this->origin = new Country($this, 'origin');
        
        $this->school->exchangeArray($data);
        $this->nationality->exchangeArray($data);
        $this->origin->exchangeArray($data);
    }
    
    public function getOrigin()
    {
        return $this->origin;
    }
    
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    
        return $this;
    }
    public function getNationality()
    {
        return $this->nationality;
    }
    
    public function setNationality($nationality)
    {
        $this->nationality = $nationality;
    
        return $this;
    }
    public function getGender()
    {
        return $this->gender;
    }
    
    public function setGender($gender)
    {
        $this->gender = $gender;
    
        return $this;
    }
    
    public function getContactState()
    {
        return $this->contact_state;
    }

    public function setContactState($contact_state)
    {
        $this->contact_state = $contact_state;
    
        return $this;
    }
    
    public function setSchool($school)
    {
        $this->school = $school;

        return $this;
    }

    public function getSchool()
    {
        return $this->school;
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    public function getAvailable()
    {
        return $this->available;
    }

    public function setSelected($selected)
    {
        $this->selected = $selected;

        return $this;
    }

    public function getSelected()
    {
        return $this->selected;
    }
}
