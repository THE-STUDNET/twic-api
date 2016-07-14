<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Course User Relation
 *
 */

namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class CourseUserRelation
 */
class CourseUserRelation extends AbstractService
{
    public function add($user, $course)
    {
        $ret = array();

        foreach ($user as $u) {
            foreach ($course as $c) {
                $ret[$u][$c] = $this->getMapper()->insertUserCourse($c, $u);
            }
        }

        return $ret;
    }

    /**
     * @param array $user
     * @param array $course
     *
     * @return int
     */
    public function deleteCourse($user, $course)
    {
        $ret = array();

        if (!is_array($user)) {
            $user = array($user);
        }

        if (!is_array($course)) {
            $course = array($course);
        }

        foreach ($user as $u) {
            foreach ($course as $c) {
                $ret[$u][$c] = $this->getMapper()->delete($this->getModel()->setCourseId($c)->setUserId($u));
            }
        }

        return $ret;
    }

    public function deleteByUser($user)
    {
        return $this->getMapper()->delete($this->getModel()->setUserId($user));
    }
}
