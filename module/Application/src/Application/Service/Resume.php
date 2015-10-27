<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\IsNull;

class Resume extends AbstractService
{
    /**
     * Add experience.
     *
     * @invokable
     *
     * @param string $start_date
     * @param string $end_date
     * @param string $address
     * @param string $logo
     * @param string $title
     * @param string $subtitle
     * @param string $description
     * @param int    $type
     *
     * @throws \Exception
     */
    public function add($start_date = null, $end_date = null, $address = null, $logo = null, $title = null, $subtitle = null, $description = null, $type = null, $publisher = null, $url = null, $cause = null, $study = null, $grade = null, $note = null)
    {
        $m_education = $this->getModel();

        if ($end_date === 'null') {
            $end_date = new IsNull();
        }
        if ($start_date === 'null') {
            $start_date = new IsNull();
        }

        $m_education->setAddress($address)
            ->setLogo($logo)
            ->setStartDate($start_date)
            ->setEndDate($end_date)
            ->setTitle($title)
            ->setSubtitle($subtitle)
            ->setDescription($description)
            ->setType($type)
            ->setPublisher($publisher)
            ->setUrl($url)
            ->setCause($cause)
            ->setStudy($study)
            ->setGrade($grade)
            ->setNote($note)
            ->setUserId($this->getServiceUser()
            ->getIdentity()['id']);

        if ($this->getMapper()->insert($m_education) <= 0) {
            throw new \Exception('error insert experience');
        }

        $resume = $this->getMapper()->getLastInsertValue();

        $this->getServiceEvent()->profileNewresume($resume);

        return $resume;
    }

    /**
     * Update experience.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $start_date
     * @param string $end_date
     * @param string $address
     * @param string $logo
     * @param string $title
     * @param string $subtitle
     * @param string $description
     * @param string $type
     *
     * @return int
     */
    public function update($id, $start_date = null, $end_date = null, $address = null, $logo = null, $title = null, $subtitle = null, $description = null, $type = null, $publisher = null, $url = null, $cause = null, $study = null, $grade = null, $note = null)
    {
        $m_education = $this->getModel();

        if ($end_date === 'null') {
            $end_date = new IsNull();
        }
        if ($start_date === 'null') {
            $start_date = new IsNull();
        }

        $m_education->setAddress($address)
            ->setLogo($logo)
            ->setStartDate($start_date)
            ->setEndDate($end_date)
            ->setTitle($title)
            ->setSubtitle($subtitle)
            ->setDescription($description)
            ->setType($type)
            ->setPublisher($publisher)
            ->setUrl($url)
            ->setCause($cause)
            ->setStudy($study)
            ->setGrade($grade)
            ->setNote($note)
            ->setUserId($this->getServiceUser()
            ->getIdentity()['id']);

        $ret = $this->getMapper()->update($m_education, array('id' => $id, 'user_id' => $this->getServiceUser()
            ->getIdentity()['id'], ));

        if ($ret > 0) {
            $this->getServiceEvent()->profileNewresume($id);
        }

        return $ret;
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

        $m_education->setId($id)->setUserId($this->getServiceUser()
            ->getIdentity()['id']);

        return $this->getMapper()->delete($m_education);
    }

    /**
     * Get Resume.
     *
     * @param int $id
     *
     * @return \Application\Model\Resume
     */
    public function getById($id)
    {
        $m_education = $this->getModel();

        $m_education->setId($id);

        return $this->getMapper()
            ->select($m_education)
            ->current();
    }

    /**
     * Get Resume.
     *
     * @invokable
     *
     * @param int $user
     */
    public function get($user)
    {
        $m_education = $this->getModel();

        $m_education->setUserId($user);

        return $this->getMapper()->select($m_education, array(new Expression('ISNULL(end_date) DESC'), 'end_date DESC'));
    }

    /**
     * @return \Application\Service\Event
     */
    public function getServiceEvent()
    {
        return $this->getServiceLocator()->get('app_service_event');
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
