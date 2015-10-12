<?php
namespace Application\Model;

use Application\Model\Base\Event as BaseEvent;

class Event extends BaseEvent
{

    protected $nb_like;

    protected $is_like;

    protected $read_date;

    public function getIsLike()
    {
        return $this->is_like;
    }

    public function setIsLike($is_like)
    {
        $this->is_like = $is_like;
        
        return $this;
    }

    public function getNbLike()
    {
        if (null !== $this->id) {
            $this->nb_like = $this->getServiceLocator()->get('app_mapper_event')->nbrLike($this->id);
        }
        
        return $this->nb_like;
    }

    public function setNbLike($nb_like)
    {
        $this->nb_like = $nb_like;
        
        return $this;
    }

    public function getReadDate()
    {
        return $this->read_date;
    }

    public function setReadDate($read_date)
    {
        $this->read_date = $read_date;
        
        return $this;
    }

    public function getSource()
    {
        return (null !== $this->source) ? json_decode($this->source, true) : null;
    }

    public function getObject()
    {
        return (null !== $this->object) ? json_decode($this->object, true) : null;
    }
}
