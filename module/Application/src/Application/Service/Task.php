<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use DateTime;
use DateTimeZone;

class Task extends AbstractService
{
    /**
     * Get task.
     *
     * @param int $id *
     *
     * @return array
     */
    public function get($id)
    {
        return $this->getMapper()->get($id);
    }

    /**
     * Add task.
     *
     * @invokable
     *
     * @param string $title
     * @param string $start
     * @param string $end
     * @param string $content
     * @param array  $task_share
     *
     * @throws \Exception
     *
     * @return int
     */
    public function add($title, $start, $end, $content = null, $task_share = null)
    {
        $m_calendar = $this->getModel()
            ->setTitle($title)
            ->setStart($start)
            ->setEnd($end)
            ->setContent($content)
            ->setCreatorId($this->getServiceUser()->getIdentity()['id'])
            ->setCreatedDate((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        $res = $this->getMapper()->insert($m_calendar);
        $task = $this->getMapper()->getLastInsertValue();
        if ($res <= 0) {
            throw new \Exception('error insert task');
        }

        if ($task_share !== null) {
            $this->addSharing($task, $task_share);
        }

        return $task;
    }

    /**
     * add task_share to a task.
     *
     * @invokable
     *
     * @param int       $task
     * @param int|array $users
     */
    public function addSharing($task, $users)
    {
        if (!is_array($users)) {
            $users = array(
                    $users,
            );
        }

        return $this->getServiceTaskShare()->add($task, $users);
    }

    /**
     * Update task.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $title
     * @param string $start
     * @param string $end
     * @param string $content
     * @param array  $task_share
     *
     * @return int
     */
    public function update($id, $title, $start, $end, $content = null, $task_share = null)
    {
        $m_task = $this->getMapper()->select($this->getModel()->setId($id))->current();
        if ($this->getServiceUser()->getIdentity()['id'] === $m_task->getCreatorId()) {
            $m_calendar = $this->getModel()
                ->setId($id)
                ->setTitle($title)
                ->setStart($start)
                ->setEnd($end)
                ->setContent($content);

            $res = $this->getMapper()->update($m_calendar);

            $this->getServiceTaskShare()->getMapper()->delete($this->getServiceTaskShare()->getModel()->setTaskId($id));
            if ($task_share !== null) {
                $this->addSharing($id, $task_share);
            }

            return $res;
        }

        return 0;
    }

    /**
     * Delete task.
     *
     * @invokable
     *
     * @param string $id
     *
     * @return int
     */
    public function delete($id)
    {
        $m_task = $this->getMapper()->select($this->getModel()->setId($id))->current();
        if ($this->getServiceUser()->getIdentity()['id'] === $m_task->getCreatorId()) {
            $this->getServiceTaskShare()->getMapper()->delete($this->getServiceTaskShare()->getModel()->setTaskId($id));

            return $this->getMapper()->delete($this->getModel()->setId($id));
        }

        return 0;
    }

    /**
     * Get tasks of current user.
     *
     * @invokable
     *
     * @param string $start
     * @param string $end
     *
     * @return array
     */
    public function getList($start, $end)
    {
        $res_tasks = $this->getMapper()->getList($start, $end, $this->getServiceUser()->getIdentity()['id']);

        foreach ($res_tasks as $m_task) {
            $m_task->setTaskShare($this->getServiceTaskShare()->getMapper()->select($this->getServiceTaskShare()->getModel()->setTaskId($m_task->getId())));
        }

        return $res_tasks;
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }

    /**
     * @return \Application\Service\TaskShare
     */
    public function getServiceTaskShare()
    {
        return $this->getServiceLocator()->get('app_service_task_share');
    }
}
