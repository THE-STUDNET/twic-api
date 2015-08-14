<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Resume extends AbstractService
{
    /**
     * Add education experience.
     *
     * @invokable
     *
     * @param string $date
     * @param string $address
     * @param string $logo
     * @param string $title
     * @param string $description
     *
     * @return int
     */
    public function add($start_date = null, $end_date = null, $address = null, $logo = null, $title = null, $description = null)
    {
        $m_education = $this->getModel();
    
        $m_education->setDate($date)
        ->setAddress($address)
        ->setLogo($logo)
        ->setStartDate($start_date)
        ->setEndDate($start_date)
        ->setTitle($title)
        ->setDescription($description)
        ->setUserId($this->getServiceAuth()->getIdentity()->getId());
    
        if ($this->getMapper()->insert($m_education) <= 0) {
            throw new \Exception('error insert');
        }
    
        return $this->getMapper()->getLastInsertValue();
    }
    
    /**
     * Update education experience.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $date
     * @param string $address
     * @param string $logo
     * @param string $title
     * @param string $description
     *
     * @return int
     */
    public function update($id, $date, $address, $logo, $title, $description)
    {
        $m_education = $this->getModel();
    
        $m_education->setDate($date)
        ->setAddress($address)
        ->setLogo($logo)
        ->setTitle($title)
        ->setDescription($description);
    
        return $this->getMapper()->update($m_education, array('id' => $id, 'user_id' => $this->getServiceAuth()->getIdentity()->getId()));
    }
    
    /**
     * Update education experience.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id)
    {
        $m_education = $this->getModel();
    
        $m_education->setId($id)
        ->setUserId($this->getServiceAuth()->getIdentity()->getId());
    
        return $this->getMapper()->delete($m_education);
    }
    
    /**
     * @return \Auth\Service\AuthService
     */
    public function getServiceAuth()
    {
        return $this->getServiceLocator()->get('auth.service');
    }
}