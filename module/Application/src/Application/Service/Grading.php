<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Grading extends AbstractService
{
    /**
     * update grading policy.
     *
     * @invokable
     *
     * @param array $datas
     * @param integer   $school
     *
     * @return bool
     */
    public function update($datas, $school)
    {
        $this->getMapper()->delete($this->getModel()->setSchoolId($school));
        foreach ($datas as $gp) {
            $this->_add($gp['letter'], $gp['min'], $gp['max'], $gp['grade'], $gp['description'], $school);
        }

        return true;
    }
    
    /**
     * update grading policy by program.
     *
     * @invokable
     *
     * @param array $datas
     * @param integer   $program
     *
     * @return bool
     */
    public function updateProgram($datas, $program)
    {
        $this->getMapper()->delete($this->getModel()->setProgramId($program));
        foreach ($datas as $gp) {
            $this->_add($gp['letter'], $gp['min'], $gp['max'], $gp['grade'], $gp['description'], null, $program);
        }
    
        return true;
    }

    /**
     * Get Grading by school id.
     *
     * @invokable
     *
     * @param integer $school
     */
    public function getBySchool($school = null)
    {
        if (null === $school) {
            $school = $this->getServiceUser()->getIdentity()['school']['id'];
        }

        return $this->getMapper()->select($this->getModel()->setSchoolId($school));
    }
    
    /**
     * Get Grading by program id.
     *
     * @invokable
     *
     * @param integer $program
     */
    public function getByProgram($program)
    {
        return $this->getMapper()->select($this->getModel()->setProgramId($program));
    }

    /**
     * Get Grading by school id.
     *
     * @invokable
     *
     * @param integer $school
     *
     */
    public function getByCourse($course)
    {
        return $this->getMapper()->getByCourse($course);
    }

    public function _add($letter, $min, $max, $grade, $description, $school= null, $program= null)
    {
        $m_grading = $this->getModel()
                           ->setLetter($letter)
                           ->setMin($min)
                           ->setMax($max)
                           ->setGrade($grade)
                           ->setDescription($description)
                           ->setSchoolId($school)
                           ->setProgramId($program);

        return $this->getMapper()->insert($m_grading);
    }

    public function initTpl($school)
    {
        $res_grading = $this->getMapper()->select($this->getModel()->setTpl(true));

        foreach ($res_grading as $m_grading) {
            $m_grading->setId(null)
                      ->setSchoolId($school)
                      ->setTpl(false);

            $this->getMapper()->insert($m_grading);
        }

        return true;
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }
}
