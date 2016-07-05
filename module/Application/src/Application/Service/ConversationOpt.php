<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class ConversationOpt extends AbstractService
{
    /**
     * @param int  $item_id
     * @param bool $record
     * @param int  $nb_user_autorecord
     * @param bool $allow_intructor
     */
    public function addOrUpdate($item_id, $record = null, $nb_user_autorecord = null, $allow_intructor = null)
    {
        return (null !== $this->getByItem($item_id)) ?
        $this->update($item_id, $record, $nb_user_autorecord, $allow_intructor) :
        $this->add($item_id, $record, $nb_user_autorecord, $allow_intructor);
    }
    
    /**
     * @param int  $item_id
     * @param bool $record
     * @param int  $nb_user_autorecord
     * @param bool $allow_intructor
     *
     * @return int
     */
    public function add($item_id, $record = null, $nb_user_autorecord = null, $allow_intructor = null)
    {
        $m_opt_videoconf = $this->getModel()
        ->setItemId($item_id)
        ->setRecord($record)      ->setNbUserAutorecord($nb_user_autorecord)
        ->setAllowIntructor($allow_intructor);
    
        return $this->getMapper()->insert($m_opt_videoconf);
    }
    
    /**
     * @invokable
     * 
     * @param int  $item_id
     * @param bool $record
     * @param int  $nb_user_autorecord
     * @param bool $allow_intructor
     * 
     * @return int
     */
    public function update($item_id, $record = null, $nb_user_autorecord = null, $allow_intructor = null)
    {
        if (null === $record && null === $nb_user_autorecord && null === $allow_intructor) {
            return 0;
        }
    
        $m_opt_videoconf = $this->getModel()
            ->setRecord($record)
            ->setNbUserAutorecord($nb_user_autorecord)
            ->setAllowIntructor($allow_intructor);
    
        return $this->getMapper()->update($m_opt_videoconf, ['item_id' => $item_id]);
    }
    
    /**
     * @param int $id
     *
     * @return \Application\Model\ConversationOpt
     */
    public function get($id)
    {
        $res_opt_videoconf = $this->getMapper()->select($this->getModel()->setId($id));
    
        return ($res_opt_videoconf->count() > 0) ? $res_opt_videoconf->current() : null;
    }
    
    /**
     * @param int $item_id
     *
     * @return \Application\Model\ConversationOpt
     */
    public function getByItem($item_id)
    {
        $m_opt_videoconf = $this->getModel()->setItemId($item_id);
    
        $res_opt_videoconf = $this->getMapper()->select($m_opt_videoconf);
    
        return ($res_opt_videoconf->count() > 0) ? $res_opt_videoconf->current() : null;
    }
}