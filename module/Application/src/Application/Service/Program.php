<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Program
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;
use Application\Model\Role as ModelRole;
use Zend\Db\Sql\Predicate\IsNull;

/**
 * Class Program
 */
class Program extends AbstractService
{
    /**
     * add program.
     *
     * @invokable
     *
     * @param string $name
     * @param int    $school_id
     * @param string $level
     * @param string $sis
     * @param string $year
     *
     * @throws \Exception
     *
     * @return int
     */
    public function add($name, $school_id, $level = null, $sis = null, $year = null)
    {
        $m_program = $this->getModel();

        $m_program->setName($name)
                  ->setSchoolId($school_id)
                  ->setLevel($level)
                  ->setSis($sis)
                  ->setYear($year);

        if ($this->getMapper()->insert($m_program) <= 0) {
            throw new \Exception('error insert');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    /**
     * Update Program.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $name
     * @param string $school_id
     * @param string $level
     * @param string $sis
     * @param string $year
     *
     * @return int
     */
    public function update($id, $name = null, $school_id = null, $level = null, $sis = null, $year = null)
    {
        $m_program = $this->getModel();
        $m_program->setId($id)
                  ->setName($name)
                  ->setSchoolId($school_id)
                  ->setLevel($level)
                  ->setSis($sis)
                  ->setYear($year);

        return $this->getMapper()->update($m_program);
    }

    /**
     * @invokable
     *
     * @param array  $filter
     * @param string $search
     * @param string $school
     */
    public function getList($filter = null, $search = null, $school = null)
    {
        $user = $this->getServiceUser()->getIdentity();

        $res_program = $this->getListByUser($filter, $user['id'], $search, $school);

        foreach ($res_program['list'] as $m_program) {
            $m_program->setStudent($this->getServiceUser()->getList(array('n' => 1, 'p' => 1), 'student', null, null, $m_program->getId())['count']);
            $m_program->setInstructor($this->getServiceUser()->getList(array('n' => 1, 'p' => 1), 'instructor', null, null, $m_program->getId())['count']);
            $m_program->setCourse($this->getServiceCourse()->count($m_program->getId()));
        }

        return $res_program;
    }

    public function getListUser($user)
    {
        return $this->getMapper()->getListUser($user);
    }

    public function getListByUser($filter = null, $user = null, $search = null, $school = null)
    {
        $identity = $this->getServiceUser()->getIdentity();

        if ($user === null) {
            $user = $identity['id'];
        }
        $mapper = $this->getMapper();
        $is_sadmin = (in_array(ModelRole::ROLE_SADMIN_STR, $identity['roles']));
        $res = $mapper->usePaginator($filter)->getList($user, $search, $school, $is_sadmin);

        return array('list' => $res, 'count' => $mapper->count());
    }

    public function getListBySchool($school)
    {
        return $this->getMapper()->select($this->getModel()->setSchoolId($school)->setDeletedDate(new IsNull()));
    }

    /**
     * @invokable
     *
     * @param int $id
     */
    public function get($id)
    {
        $res_program = $this->getMapper()->get($id);

        if ($res_program->count() <= 0) {
            throw new \Exception('error get program');
        }

        $m_program = $res_program->current();
        $m_program->setStudent($this->getServiceUser()->getList(null, 'student', null, null, $m_program->getId()));
        $m_program->setInstructor($this->getServiceUser()->getList(null, 'instructor', null, null, $m_program->getId()));
        $m_program->setCourse($this->getServiceCourse()->getList($m_program->getId()));

        return $m_program;
    }

    /**
     * Delete Program by ID.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id)
    {
        $ret = [];
        if (!is_array($id)) {
            $id = array($id);
        }

        foreach ($id as $p) {
            $ret[$p] = $this->getMapper()->delete($this->getModel()->setId($p));
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

    /**
     * @return \Application\Service\Course
     */
    public function getServiceCourse()
    {
        return $this->getServiceLocator()->get('app_service_course');
    }

}
