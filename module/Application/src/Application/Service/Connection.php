<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Connection extends AbstractService
{
    public function add()
    {
        $identity = $this->getServiceUser()->getIdentity();
        $m_connection = $this->selectLast();
        $current = new \DateTime('now', new \DateTimeZone('UTC'));

        $diff = ($m_connection) ? ($current->getTimestamp() - (new \DateTime($m_connection->getEnd(), new \DateTimeZone('UTC')))->getTimestamp()) : null;

        if ($diff > 3600 || $diff === null) {
            $m_connection = $this->getModel()
                ->setUserId($identity['id'])
                ->setToken($identity['token'])
                ->setStart($current->format('Y-m-d H:i:s'))
                ->setEnd($current->format('Y-m-d H:i:s'));

            return $this->getMapper()->insert($m_connection);
        } else {
            $m_connection->setEnd($current->format('Y-m-d H:i:s'));

            return $this->getMapper()->update($m_connection);
        }
    }

    /**
     * @return \Application\Model\Connection
     */
    public function selectLast()
    {
        $identity = $this->getServiceUser()->getIdentity();

        $m_connection = null;
        $res_connection = $this->getMapper()->selectLastConnection($identity['token'], $identity['id']);
        if ($res_connection->count() > 0) {
            $m_connection = $res_connection->current();
        }

        return $m_connection;
    }

    /**
     * @invokable
     * 
     * @param int $school
     */
    public function getAvg($school)
    {
        return [
            'd' => $this->getMapper()->getAvg($school, 1)->current(),
            'w' => $this->getMapper()->getAvg($school, 7)->current(),
            'm' => $this->getMapper()->getAvg($school, 30)->current(),
            'a' => $this->getMapper()->getAvg($school)->current(),
        ];
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
