<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Course
 *
 */

namespace Application\Service;

use Dal\Service\AbstractService;
use DateTime;
use DateTimeZone;
use Application\Model\Role as ModelRole;

/**
 * Class Course
 */
class Course extends AbstractService
{
    /**
     * Add course.
     *
     * @invokable
     *
     * @param int    $program_id
     * @param string $title
     * @param string $abstract
     * @param string $description
     * @param string $objectives
     * @param string $teaching
     * @param string $attendance
     * @param string $duration
     * @param string $notes
     * @param string $learning_outcomes
     * @param string $video_link
     * @param string $video_token
     * @param array  $material_document
     *
     * @throws \Exception
     *
     * @return int
     */
    public function add($program_id, $title = null, $picture = null, $abstract = null, $description = null, $objectives = null, $teaching = null, $attendance = null, $duration = null, $notes = null, $learning_outcomes = null, $video_link = null, $video_token = null, array $material_document = array())
    {
        $m_course = $this->getModel()
            ->setTitle($title)
            ->setCreatorId($this->getServiceUser()->getIdentity()['id'])
            ->setAbstract($abstract)
            ->setPicture($picture)
            ->setDescription($description)
            ->setObjectives($objectives)
            ->setTeaching($teaching)
            ->setAttendance($attendance)
            ->setDuration($duration)
            ->setNotes($notes)
            ->setLearningOutcomes($learning_outcomes)
            ->setVersion(1)
            ->setVideoLink($video_link)
            ->setVideoToken($video_token)
            ->setProgramId($program_id)
            ->setCreatedDate((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        $res = $this->getMapper()->insert($m_course);
        if ($res <= 0) {
            throw new \Exception('error insert course');
        }

        $course_id = $this->getMapper()->getLastInsertValue();

        // On ne crée plus les grading policy par default
        //$this->getServiceGradingPolicy()->initTpl($course_id);
       
        return $this->get($course_id);
    }

    /**
     * Update course.
     *  
     * @invokable
     *
     * @param int    $id
     * @param string $title
     * @param string $abstract
     * @param string $description
     * @param string $objectives
     * @param string $teaching
     * @param string $attendance
     * @param string $duration
     * @param string $notes
     * @param string $learning_outcomes
     * @param string $video_link
     * @param string $video_token
     *
     * @return int
     */
    public function update($id, $title = null, $picture = null, $abstract = null, $description = null, $objectives = null, $teaching = null, $attendance = null, $duration = null, $notes = null, $learning_outcomes = null, $video_link = null, $video_token = null)
    {
        $m_course = $this->getModel()
            ->setId($id)
            ->setTitle($title)
            ->setAbstract($abstract)
            ->setPicture($picture)
            ->setDescription($description)
            ->setObjectives($objectives)
            ->setTeaching($teaching)
            ->setAttendance($attendance)
            ->setDuration($duration)
            ->setNotes($notes)
            ->setLearningOutcomes($learning_outcomes)
            ->setVideoLink($video_link)
            ->setVideoToken($video_token)
            ->setUpdatedDate((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        $ret = $this->getMapper()->update($m_course);

        if ($ret > 0) {
            $ar_course = $m_course->toArray();
            unset($ar_course['updated_date']);
            $this->getServiceEvent()->courseUpdated($id, $ar_course);
        }

        return $ret;
    }

    /**
     * Delete course.
     *
     * @invokable
     *
     * @param array $id
     *
     * @return int
     */
    public function delete($id)
    {
        $ret = array();

        if (!is_array($id)) {
            $id = array($id);
        }

        foreach ($id as $idc) {
            $ret[$idc] = $this->getMapper()->delete($this->getModel()->setId($idc));
        }

        return $ret;
    }

    /**
     * @invokable
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return \Application\Model\Course
     */
    public function get($id)
    {
        $res_couse = $this->getMapper()->get($id);

        if ($res_couse->count() == 0) {
            throw new \Exception('no course with id: '.$id);
        }

        $m_course = $res_couse->current();
        $m_course->setGrading($this->getServiceGrading()
            ->getByCourse($id));
        $m_course->setGradingPolicy($this->getServiceGradingPolicy()
            ->get($id));
        $m_course->setInstructor($this->getServiceUser()
            ->getListOnly(ModelRole::ROLE_INSTRUCTOR_STR, $m_course->getId()));

        return $m_course;
    }

    /**
     * @invokable
     *
     * @param int    $program
     * @param string $search
     * @param array  $filter
     * @param int $user
     * @param int    $school
     *
     * @return array
     */
    public function getList($program = null, $search = null, $filter = null, $user = null, $school = null)
    {
        $mapper = $this->getMapper();

        $res_course = $mapper->usePaginator($filter)->getList($program, $search, $filter, $user, $school);

        foreach ($res_course as $m_course) {
            $m_course->setStudent($this->getServiceUser()
                ->getListOnly(ModelRole::ROLE_STUDENT_STR, $m_course->getId()));
            $m_course->setInstructor($this->getServiceUser()
                ->getListOnly(ModelRole::ROLE_INSTRUCTOR_STR, $m_course->getId()));
        }

        return array('count' => $mapper->count(), 'list' => $res_course);
    }

    /**
     * get Nbr Course by program.
     * 
     * @param int $program
     *
     * @return int
     */
    public function count($program)
    {
        $res_course = $this->getMapper()->getCount($program);

        return ($res_course->count() > 0) ? $res_course->current()->getNbrCourse() : 0;
    }

    /**
     * Get Service Grading
     * 
     * @return \Application\Service\Grading
     */
    private function getServiceGrading()
    {
        return $this->getServiceLocator()->get('app_service_grading');
    }

    /**
     * Get Service Item
     * 
     * @return \Application\Service\Item
     */
    private function getServiceItem()
    {
        return $this->getServiceLocator()->get('app_service_item');
    }

    /**
     * Get Service User
     * 
     * @return \Application\Service\User
     */
    private function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }

    /**
     * Get Service GradingPolicy
     * 
     * @return \Application\Service\GradingPolicy
     */
    private function getServiceGradingPolicy()
    {
        return $this->getServiceLocator()->get('app_service_grading_policy');
    }

    /**
     * Get Service Event
     * 
     * @return \Application\Service\Event
     */
    private function getServiceEvent()
    {
        return $this->getServiceLocator()->get('app_service_event');
    }
}
