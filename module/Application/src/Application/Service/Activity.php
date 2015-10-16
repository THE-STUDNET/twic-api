<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use Zend\Db\Sql\Predicate\Between;

class Activity extends AbstractService
{
    /**
     * @invokable
     * 
     * @param array $activities
     * 
     * @return array
     */
    public function add($activities)
    {
        $ret = [];
        $user = $this->getServiceUser()->getIdentity()['id'];
        foreach ($activities as $activity) {
            $date = (isset($activity['date']))   ? $activity['date']  : null;
            $event = (isset($activity['event']))  ? $activity['event'] : null;
            $object = (isset($activity['object'])) ? $activity['object'] : null;
            $target = (isset($activity['target'])) ? $activity['target'] : null;

            $ret[] = $this->_add($date, $event, $object, $target, $user);
        }

        return $ret;
    }

    /**
     * @param string $date
     * @param string $event
     * @param array  $object
     * @param array  $target
     *
     * @throws \Exception
     *
     * @return int
     */
    public function _add($date = null, $event = null, $object = null, $target = null, $user = null)
    {
        $m_activity = $this->getModel();
        $m_activity->setEvent($event);
        $m_activity->setDate($date);
        $m_activity->setUserId($user);

        if (null !== $object) {
            if (isset($object['id'])) {
                $m_activity->setObjectId($object['id']);
            }
            if (isset($object['value'])) {
                $m_activity->setObjectValue($object['value']);
            }
            if (isset($object['name'])) {
                $m_activity->setObjectName($object['name']);
            }
            if (isset($object['data'])) {
                $m_activity->setObjectData(json_encode($object['data']));
            }
        }
        if (null !== $target) {
            if (isset($target['id'])) {
                $m_activity->setTargetId($target['id']);
            }
            if (isset($target['name'])) {
                $m_activity->setTargetName($target['name']);
            }
            if (isset($target['data'])) {
                $m_activity->setTargetData(json_encode($target['data']));
            }
        }

        if ($this->getMapper()->insert($m_activity) <= 0) {
            throw new \Exception('error insert ativity');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * @invokable
     * 
     * @param string $date
     * @param string $event
     * @param array  $object
     * @param array  $target
     * @param array  $user
     * 
     * @throws \Exception
     * 
     * @return int
     */
    public function getList($date = null, $event = null, $object = null, $target = null, $user = null, $start_date = null, $end_date = null)
    {
        $m_activity = $this->getModel();
        $m_activity->setEvent($event)
                   ->setDate($date)
                   ->setUserId($user);

        if (null !== $start_date && null !== $end_date) {
            $m_activity->setDate(new Between('date', $start_date, $end_date));
        }
        if (null !== $object) {
            if (isset($object['id'])) {
                $m_activity->setObjectId($object['id']);
            }
            if (isset($object['name'])) {
                $m_activity->setObjectName($object['name']);
            }
            if (isset($object['data'])) {
                $m_activity->setObjectData($object['data']);
            }
        }
        if (null !== $target) {
            if (isset($target['id'])) {
                $m_activity->setTargetId($target['id']);
            }
            if (isset($target['name'])) {
                $m_activity->setTargetName($target['name']);
            }
            if (isset($target['data'])) {
                $m_activity->setTargetData($target['data']);
            }
        }

        $res_activity = $this->getMapper()->select($m_activity, array('date' => 'ASC'));

        foreach ($res_activity as $m_activity) {
            $m_activity->setDate((new \DateTime($m_activity->getDate()))->format('Y-m-d\TH:i:s\Z'));
            $o_data = $m_activity->getObjectData();
            if (is_string($o_data)) {
                $m_activity->setObjectData(json_decode($o_data, true));
            }
            $o_target = $m_activity->getTargetData();
            if (is_string($o_target)) {
                $m_activity->setTargetData(json_decode($o_target, true));
            }
        }

        return $res_activity;
    }

    /**
     * @invokable
     * 
     * @param array|string $event
     * @param int          $user
     * @param int          $object_id
     * @param string       $object_name
     */
    public function aggregate($event, $user, $object_id = null, $object_name = null, $target_id = null, $target_name = null)
    {
        $ret = [];
        if (!is_array($event)) {
            $event = array($event);
        }

        foreach ($event as $e) {
            $ret[$e] = $this->getMapper()->aggregate($e, $user, $object_id, $object_name, $target_id, $target_name)->current();
        }

        return $ret;
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
