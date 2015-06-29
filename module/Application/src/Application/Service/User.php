<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use DateTimeZone;
use DateTime;
use JRpc\Json\Server\Exception\JrpcException;
use Application\Model\Role as ModelRole;

class User extends AbstractService
{
    /**
     * Log user.
     *
     * @invokable
     *
     * @param string $user
     * @param string $password
     */
    public function login($user, $password)
    {
        $auth = $this->getServiceAuth();
        $auth->getAdapter()->setIdentity($user);
        $auth->getAdapter()->setCredential($password);

        $result = $auth->authenticate();

        if (!$result->isValid()) {
            throw new JrpcException($result->getMessages()[0], $result->getCode()['code']);
        }

        return $this->getIdentity(true);
    }

    public function _getCacheIdentity($init = false)
    {
        $user = array();
        $id = $this->getServiceAuth()->getIdentity()->getId();

        if ($init === false && $this->getCache()->hasItem('identity_'.$id)) {
            $user = $this->getCache()->getItem('identity_'.$id);
        } else {
            $user = $this->getServiceAuth()->getIdentity()->toArray();
            $user['roles'] = array();
            foreach ($this->getServiceRole()->getRoleByUser() as $role) {
                $user['roles'][] = $role->getName();
            }
            $user['school'] = $this->get($id)['school'];
            $secret_key = $this->getServiceLocator()->get('config')['app-conf']['secret_key'];
            $user['wstoken'] = sha1($secret_key . $id);
            $this->getCache()->setItem('identity_'.$id, $user);
        }

        return $user;
    }

    /**
     * @invokable
     *
     * @return \Auth\Authentication\Storage\Model\Identity|false
     */
    public function getIdentity($init = false)
    {
        return $this->_getCacheIdentity($init);
    }

    /**
     * @invokable
     */
    public function getListSession()
    {
        $auth = $this->getServiceAuth();

        return $auth->getStorage()->getListSession($auth->getIdentity()->getId());
    }

    /**
     * @invokable
     *
     * @return true
     */
    public function logout()
    {
        $this->getServiceAuth()->clearIdentity();

        return true;
    }

    /**
     * Add User.
     *
     * @invokable
     *
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $password
     * @param string $birth_date
     * @param string $position
     * @param int    $school_id
     * @param string $interest
     * @param string $avatar
     *
     * @throws \Exception
     *
     * @return int
     */
    public function add($firstname, $lastname, $email, $sis = null, $password = null, $birth_date = null, $position = null, $school_id = null, $interest = null, $avatar = null, $roles = null)
    {
        $m_user = $this->getModel();

        $m_user->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setSis($sis)
            ->setPassword(md5($password))
            ->setBirthDate($birth_date)
            ->setPosition($position)
            ->setSchoolId($school_id)
            ->setInterest($interest)
            ->setAvatar($avatar);

        /*
         * @TODO schoolid vérifier que si il n'est pas admin le school id est automatiquement celui de la personne qui add le user.
         */
        if ($school_id === null) {
            $user = $this->get();
            $m_user->setSchoolId($user['school_id']);
        }

        if ($password !== null) {
            $m_user->setPassword(md5($password));
        }

        if ($this->getMapper()->insert($m_user) <= 0) {
            throw new \Exception('error insert');
        }

        $id = $this->getMapper()->getLastInsertValue();

        if ($roles === null) {
            $roles = array(
                ModelRole::ROLE_STUDENT_STR,
            );
        }
        if (!is_array($roles)) {
            $roles = array(
                $roles,
            );
        }

        foreach ($roles as $r) {
            $this->getServiceUserRole()->add($this->getServiceRole()
                ->getIdByName($r), $id);
        }

        return $id;
    }

    /**
     * @invokable
     *
     * @param string $filter
     * @param string $type
     * @param string $level
     * @param string $course
     * @param string $program
     * @param string $search
     * @param int    $noprogram
     * @param int    $nocourse
     *
     * @return array
     */
    public function getList($filter = null, $type = null, $level = null, $course = null, $program = null, $search = null, $noprogram = null, $nocourse = null)
    {
        $mapper = $this->getMapper();
        $res = $mapper->usePaginator($filter)->getList(
                $filter,
                null,
                $this->getServiceAuth()->getIdentity()->getId(),
                $type,
                $level,
                $course,
                $program,
                $search,
                $noprogram,
                $nocourse);

        $res = $res->toArray();

        foreach ($res as &$user) {
            $user['roles'] = array();
            $user['program'] = array();

            foreach ($this->getServiceRole()->getRoleByUser($user['id']) as $role) {
                $user['roles'][] = $role->getName();
            }
            $user['program'] = $this->getServiceProgram()->getListByUser(null, $user['id'])['list'];
        }

        return array(
            'list' => $res,
            'count' => $mapper->count(),
        );
    }

    public function getListOnly($type, $course)
    {
        return $this->getMapper()->getList(null, null, null, $type, null, $course);
    }

    /**
     * @invokable
     *
     * @param array $user
     * @param array $program
     */
    public function addProgram($user, $program)
    {
        if (!is_array($user)) {
            $user = array(
                $user,
            );
        }

        if (!is_array($program)) {
            $program = array(
                $program,
            );
        }

        return $this->getServiceProgramUserRelation()->add($user, $program);
    }

    /**
     * @invokable
     *
     * @param array $user
     * @param array $course
     */
    public function addCourse($user, $course)
    {
        if (!is_array($user)) {
            $user = array(
                    $user,
            );
        }

        if (!is_array($course)) {
            $course = array(
                    $course,
            );
        }

        return $this->getServiceCourseUserRelation()->add($user, $course);
    }

    /**
     * @invokable
     *
     * @param int|array $user
     * @param int|array $course
     *
     * @return int
     */
    public function deleteCourse($user, $course)
    {
        return $this->getServiceCourseUserRelation()->deleteCourse($user, $course);
    }

    /**
     * @invokable
     *
     * @param int|array $user
     * @param int|array $program
     *
     * @return int
     */
    public function deleteProgram($user, $program)
    {
        return $this->getServiceProgramUserRelation()->deleteProgram($user, $program);
    }

    /**
     * Update User.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $birth_date
     * @param string $position
     * @param int    $school_id
     * @param string $interest
     * @param string $avatar
     * @param array  $roles
     * @param array  $programs
     *
     * @return int
     */
    public function update($id = null, $firstname = null, $lastname = null, $sis = null, $email = null, $birth_date = null, $position = null, $school_id = null, $interest = null, $avatar = null, $roles = null, $programs = null)
    {
        $m_user = $this->getModel();

        if ($id === null) {
            $id = $this->getServiceAuth()
                ->getIdentity()
                ->getId();
        }

        $m_user->setId($id)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setSis($sis)
            ->setBirthDate($birth_date)
            ->setPosition($position)
            ->setSchoolId($school_id)
            ->setInterest($interest)
            ->setAvatar($avatar);

        if ($roles !== null) {
            foreach ($roles as $r) {
                $this->getServiceUserRole()->deleteByUser($id);
                $this->getServiceUserRole()->add($this->getServiceRole()
                    ->getIdByName($r), $id);
            }
        }

        if ($programs !== null) {
            $this->getServiceProgramUserRelation()->deleteByUser($id);
            $this->addProgram($id, $programs);
        }

        return $this->getMapper()->update($m_user);
    }

    /**
     * @invokable
     *
     * @param string $oldpassword
     * @param string $password
     *
     * @return int
     */
    public function updatePassword($oldpassword, $password)
    {
        return $this->getMapper()->update($this->getModel()
            ->setPassword(md5($password)), array(
            'id' => $this->getServiceAuth()
                ->getIdentity()
                ->getId(),
            'password' => md5($oldpassword),
        ));
    }

    /**
     * @invokable
     *
     * @param int $id
     */
    public function get($id = null)
    {
        if ($id === null) {
            $id = $this->getServiceAuth()->getIdentity()->getId();
        }

        $res_user = $this->getMapper()->get($id);
        if ($res_user->count() <= 0) {
            throw new \Exception('error get user:'.$id);
        }

        $user = $res_user->current()->toArray();

        $user['roles'] = array();
        foreach ($this->getServiceRole()->getRoleByUser($id) as $role) {
            $user['roles'][] = $role->getName();
        }

        return $user;
    }

    /**
     * Delete User.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id)
    {
        $ret = array();
        if (!is_array($id)) {
            $id = array(
                $id,
            );
        }

        foreach ($id as $i) {
            $m_user = $this->getModel();
            $m_user->setId($i)->setDeletedDate((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

            $ret[$i] = $this->getMapper()->update($m_user);
        }

        return $ret;
    }

    /**
     * Add language to user.
     *
     * @invokable
     *
     * @param array $language
     * @param int   $language_level
     *
     * @return int
     */
    public function addLanguage($language, $language_level)
    {
        $language_id = $this->getServiceLanguage()->add($language);

        return $this->getServiceUserLanguage()->add($language_id, $language_level);
    }

    /**
     * Get user list from item prog
     *
     * @invokable
     *
     * @param int   $item_prog
     *
     * @return array
     */
    public function getListByItemProg($item_prog){
        return $this->getMapper()->getListByItemProg($item_prog);
    }
    
       /**
     * Get user list from item assignment
     *
     * @invokable
     *
     * @param int   $item_assignment
     *
     * @return array
     */
    public function getListByItemAssignment($item_assignment){
        return $this->getMapper()->getListByItemAssignment($item_assignment);
    }

    /**
     * delete language to user.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function deleteLanguage($id)
    {
        return $this->getServiceUserLanguage()->delete($id);
    }

    /**
     * @return \Application\Service\Language
     */
    public function getServiceLanguage()
    {
        return $this->getServiceLocator()->get('app_service_language');
    }

    /**
     * @return \Application\Service\Program
     */
    public function getServiceProgram()
    {
        return $this->getServiceLocator()->get('app_service_program');
    }

    /**
     * @return \Application\Service\ProgramUserRelation
     */
    public function getServiceProgramUserRelation()
    {
        return $this->getServiceLocator()->get('app_service_program_user_relation');
    }

    /**
     * @return \Application\Service\CourseUserRelation
     */
    public function getServiceCourseUserRelation()
    {
        return $this->getServiceLocator()->get('app_service_course_user_relation');
    }

    /**
     * @return \Application\Service\UserLanguage
     */
    public function getServiceUserLanguage()
    {
        return $this->getServiceLocator()->get('app_service_user_language');
    }

    /**
     * @return \Zend\Authentication\AuthenticationService
     */
    public function getServiceAuth()
    {
        return $this->getServiceLocator()->get('auth.service');
    }

    /**
     * @return \Application\Service\Role
     */
    public function getServiceRole()
    {
        return $this->getServiceLocator()->get('app_service_role');
    }

    /**
     * @return \Application\Service\UserRole
     */
    public function getServiceUserRole()
    {
        return $this->getServiceLocator()->get('app_service_user_role');
    }

    /**
     * Get Storage if define in config.
     *
     * @return \Zend\Cache\Storage\StorageInterface
     */
    public function getCache()
    {
        $config = $this->getServiceLocator()->get('config')['app-conf'];

        return $this->getServiceLocator()->get($config['cache']);
    }
}
