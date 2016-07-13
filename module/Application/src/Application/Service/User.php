<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use DateTimeZone;
use DateTime;
use JRpc\Json\Server\Exception\JrpcException;
use Application\Model\Role as ModelRole;
use Firebase\Token\TokenGenerator;
use Zend\Db\Sql\Predicate\IsNull;
use Application\Model\Item as ModelItem;

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

    public function getListUsersByGroup($group_id)
    {
        return $this->getMapper()->getListUsersByGroup($group_id);
    }

    public function _getCacheIdentity($init = false)
    {
        $user = [];
        $identity = $this->getServiceAuth()
            ->getIdentity();
         $id = $identity->getId();
            
        if ($init === false && $this->getCache()->hasItem('identity_'.$id)) {
            $user = $this->getCache()->getItem('identity_'.$id);
        } else {
            $user = $identity->toArray();
            
            
            $user['roles'] = [];
            foreach ($this->getServiceRole()->getRoleByUser() as $role) {
                $user['roles'][$role->getId()] = $role->getName();
            }

            $res_user = $this->getMapper()->get($id, $id);
            $user['school'] = ($res_user->count() > 0) ? $res_user->current()->toArray()['school'] : null;
            $secret_key = $this->getServiceLocator()->get('config')['app-conf']['secret_key'];
            $user['wstoken'] = sha1($secret_key.$id);

            $secret_key_fb = $this->getServiceLocator()->get('config')['app-conf']['secret_key_fb'];
            $secret_key_fb_debug = $this->getServiceLocator()->get('config')['app-conf']['secret_key_fb_debug'];

            $generator = new TokenGenerator($secret_key_fb);
            $user['fbtoken'] = $generator->setData(array('uid' => (string) $id))
                ->setOption('debug', $secret_key_fb_debug)
                ->setOption('expires', 1506096687)
                ->create();

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

        return $auth->getStorage()->getListSession($auth->getIdentity()
            ->getId());
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
     * Add User
     *
     * @invokable
     *
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $gender
     * @param string $origin
     * @param string $nationality
     * @param string $sis
     * @param string $password
     * @param string $birth_date
     * @param string $position
     * @param integer $school_id
     * @param string $interest
     * @param string $avatar
     * @param array $roles
     * @param string $timezone
     * @param string $background
     * 
     * @return integer
     */
    public function add(
        $firstname, 
        $lastname, 
        $email, 
        $gender = null, 
        $origin = null, 
        $nationality = null, 
        $sis = null, 
        $password = null, 
        $birth_date = null, 
        $position = null, 
        $school_id = null, 
        $interest = null, 
        $avatar = null, 
        $roles = null,
        $timezone = null,
        $background = null)
    {
        if ($birth_date !== null && \DateTime::createFromFormat('Y-m-d H:i:s', $birth_date) === false) {
            $birth_date = null;
        }

        if ($this->getNbrEmailUnique($email) > 0) {
            throw new JrpcException('duplicate email', -38001);
        }
        
        if (!empty($sis)) {
            if ($this->getNbrSisUnique($sis) > 0) {
                throw new JrpcException('uid email', -38002);
            }
        }

        $m_user = $this->getModel();
        $m_user->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setSis($sis)
            ->setOrigin($origin)
            ->setGender($gender)
            ->setNationality($nationality)
            ->setBirthDate($birth_date)
            ->setPosition($position)
            ->setSchoolId($school_id)
            ->setInterest($interest)
            ->setAvatar($avatar)
            ->setTimezone($timezone)
            ->setBackground($background)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
        /*
         * @TODO schoolid vérifier que si il n'est pas admin le school id est
         * automatiquement celui de la personne qui add le user.
         */
        if (!in_array(ModelRole::ROLE_SADMIN_STR, $this->getIdentity()['roles'])) {
            $user = $this->get();
            $m_user->setSchoolId($user['school_id']);

            $school_id = $user['school_id'];
        }

        if (empty($password)) {
            $cars = 'azertyiopqsdfghjklmwxcvbn0123456789/*.!:;,....';
            $long = strlen($cars);
            srand((double) microtime() * 1000000);
            $password = '';
            for ($i = 0; $i < 8; ++$i) {
                $password .= substr($cars, rand(0, $long - 1), 1);
            }
        }

        $m_user->setPassword(md5($password));

        if ($this->getMapper()->insert($m_user) <= 0) {
            throw new \Exception('error insert');
        }

        if ($school_id !== null) {
            $this->getServiceContact()->addBySchool($school_id);
        }

        try {
            $this->getServiceMail()->sendTpl('tpl_createuser', $email, array('password' => $password, 'email' => $email, 'lastname' => $m_user->getLastname(), 'firstname' => $m_user->getFirstname()));
        } catch (\Exception $e) {
            syslog(1, 'Model name does not exist <> password is : '.$password.' <> '.$e->getMessage());
        }

        $id = $this->getMapper()->getLastInsertValue();
        if ($roles === null) {
            $roles = array(ModelRole::ROLE_STUDENT_STR);
        }
        if (!is_array($roles)) {
            $roles = array($roles);
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
     * @param array $data
     * @return array
     */
    public function import($data)
    {
        $error = [];
        foreach ($data as $u) {
            try {
                $id = $this->add($u['firstname'], $u['lastname'], $u['email'], null,  null,  null,  $u['uid'],  null,  null,  null,  null,  null,  null, [$u['role']]);
            } catch (JrpcException $e) {
                $error[] = [
                    'field' => $u,
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $error;
    }
    
    /**
     * @param int $item_id
     * @param int $user
     */
    public function getListUsersGroupByItemAndUser($item_id, $user = null)
    {
        if (null === $user) {
            $user = $this->getIdentity()['id'];
        }

        return $this->getMapper()->getListUsersGroupByItemAndUser($item_id, $user);
    }

    /**
     * @param int $submission_id
     */
    public function getListUsersBySubmission($submission_id)
    {
        return $this->getMapper()->getListUsersBySubmission($submission_id);
    }

    /**
     * @invokable
     * 
     * @param integer $item_id
     */
    public function getListByItem($item_id)
    {
        return  $this->getMapper()->getListByItem($item_id);
    }

    /**
     * @param int $item_id
     * @param int $user_id
     */
    public function doBelongs($item_id, $user_id)
    {
        return $this->getMapper()->doBelongsByItemOfCourseUser($item_id, $user_id) &&
            $this->getMapper()->doBelongsByItemHaveSubmission($item_id, $user_id);
    }

    public function getListBySchool($school)
    {
        return $this->getMapper()->getListBySchool($school);
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
     * @param string $noprogram
     * @param string $nocourse
     * @param string $schools
     * @param string $order
     * @param array  $exclude
     * @param string $event
     * @param string $message
     */
    public function getList($filter = null, $type = null, $level = null, $course = null, $program = null, $search = null, $noprogram = null, $nocourse = null, $schools = null, $order = null, array $exclude = null, $event = null, $message = null)
    {
        $identity = $this->getIdentity();

        if (in_array(ModelRole::ROLE_SADMIN_STR, $identity['roles']) && $schools === null) {
            $schools = false;
        }

        $mapper = $this->getMapper();
        $res = $mapper->usePaginator($filter)->getList($filter, $event, $identity['id'], $type, $level, $course, $program, $search, $noprogram, $nocourse, $schools, $order, $exclude, $message);

        $res = $res->toArray();

        foreach ($res as &$user) {
            $user['roles'] = [];
            $user['program'] = [];

            foreach ($this->getServiceRole()->getRoleByUser($user['id']) as $role) {
                $user['roles'][] = $role->getName();
            }
            $user['program'] = $this->getServiceProgram()->getListUser($user['id']);
        }

        return ['list' => $res, 'count' => $mapper->count()];
    }

    public function getListOnly($type, $course)
    {
        return $this->getMapper()->getList(null, null, $this->getServiceAuth()
            ->getIdentity()
            ->getId(), $type, null, $course, null, null, null, null, false);
    }

    /**
     * @invokable
     */
    public function getListRequest()
    {
        $me = $this->getServiceAuth()->getIdentity()->getId();

        return $this->getMapper()->getList(null, null, $me, null, null, null, null, null, null, null, false);
    }

    public function getInstructorByItem($item_id)
    {
        return $this->getMapper()->getInstructorByItem($item_id);
    }
    
    /**
     * @invokable
     * 
     * @param integer $type
     * @param string $date
     * @param integer $user
     */
    public function getListContact($type = 5, $date = null, $user = null)
    {
        if(null === $user) {
            $user = $this->getIdentity()['id'];
        }
        
        return $this->getMapper()->getListContact($user, $type, $date);
    }

    public function getListUserBycourse($course)
    {
        return $this->getMapper()->getList(null, null, $this->getServiceAuth()
            ->getIdentity()
            ->getId(), null, null, $course, null, null, null, null, false);
    }

    public function getListUserBycourseWithStudentAndInstructorAndAcademic($course)
    {
        return $this->getMapper()->getListUserBycourseWithStudentAndInstructorAndAcademic($course);
    }

    public function getListUserBycourseWithInstructorAndAcademic($course)
    {
        return $this->getMapper()->getListUserBycourseWithInstructorAndAcademic($course);
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
            $user = array($user);
        }
        if (!is_array($program)) {
            $program = array($program);
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
            $user = array($user);
        }
        if (!is_array($course)) {
            $course = array($course);
        }

        foreach ($user as $u) {
            foreach ($course as $c) {
                $r = $this->getRoleIds($u);
                if (in_array(ModelRole::ROLE_STUDENT_ID, $r)) {
                    $res_item = $this->getServiceItem()->getListByCourse($c);
                    foreach ($res_item as $m_item) {
                        // Si item est différent de Txt Document et Module 
                        if ($m_item->getType() !== ModelItem::TYPE_TXT       &&
                            $m_item->getType() !== ModelItem::TYPE_DOCUMENT &&
                            $m_item->getType() !== ModelItem::TYPE_MODULE   &&
                            $m_item->getHasAllStudent() === 1) {
                            $this->getServiceSubmission()->addSubmissionUser($u, $m_item->getId());
                        }
                    }
                }
            }
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
     * @param integer $id
     * @param string $gender
     * @param string $origin
     * @param string $nationality
     * @param string $firstname
     * @param string $lastname
     * @param string $sis
     * @param string $email
     * @param string $birth_date
     * @param string $position
     * @param integer $school_id
     * @param string $interest
     * @param string $avatar
     * @param array $roles
     * @param array $programs
     * @param string $resetpassword
     * @param boolean $has_email_notifier
     * @param string $timezone
     * @param string $background
     * 
     * @return integer
     */
    public function update(
        $id = null, 
        $gender = null, 
        $origin = null, 
        $nationality = null, 
        $firstname = null, 
        $lastname = null, 
        $sis = null, 
        $email = null, 
        $birth_date = null, 
        $position = null, 
        $school_id = null, 
        $interest = null, 
        $avatar = null, 
        $roles = null, 
        $programs = null, 
        $resetpassword = null, 
        $has_email_notifier = null,
        $timezone = null,
        $background = null)
    {
        if ($birth_date !== null
            && \DateTime::createFromFormat('Y-m-d', $birth_date) === false
            && \DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $birth_date) === false) {
            $birth_date = null;
        }

        if ($this->getNbrEmailUnique($email, $id) > 0) {
            throw new JrpcException('duplicate email', -38001);
        }

        $m_user = $this->getModel();

        if ($id === null) {
            $id = $this->getIdentity()['id'];
        }

        $m_user->setId($id)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setOrigin($origin)
            ->setGender($gender)
            ->setNationality($nationality)
            ->setSis($sis)
            ->setBirthDate($birth_date)
            ->setPosition($position)
            ->setInterest($interest)
            ->setAvatar($avatar)
            ->setHasEmailNotifier($has_email_notifier)
            ->setTimezone($timezone)    
            ->setBackground($background)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        if ($school_id !== null) {
            if ($school_id === 'null') {
                $school_id = new IsNull('school_id');
            }
            $m_user->setSchoolId($school_id);
        }

        if ($roles !== null) {
            if (!is_array($roles)) {
                $roles = [$roles];
            }
            $this->getServiceUserRole()->deleteByUser($id);
            foreach ($roles as $r) {
                $this->getServiceUserRole()->add($this->getServiceRole()
                    ->getIdByName($r), $id);
            }
        }

        if ($programs !== null) {
            $this->getServiceProgramUserRelation()->deleteByUser($id);
            $this->addProgram($id, $programs);
        }

        $ret = $this->getMapper()->update($m_user);

        if ($ret > 0 && $id === $this->getIdentity()['id']) {
            $this->getServiceEvent()->profileUpdated($id, $m_user->toArray());
        }

        if ($resetpassword) {
            $this->lostPassword($this->get($id)['email']);
        }

        return $ret;
    }

    /**
     * @param string $email
     * @param int    $user
     * 
     * @return int
     */
    public function getNbrEmailUnique($email, $user = null)
    {
        $res_user = $this->getMapper()->getEmailUnique($email, $user);

        return ($res_user->count() > 0) ? $res_user->current()->getNbUser() : 0;
    }
    
    /**
     * @param string $sis
     * @param int    $user
     *
     * @return int
     */
    public function getNbrSisUnique($sis, $user = null)
    {
        $res_user = $this->getMapper()->getNbrSisUnique($sis, $user);
    
        return ($res_user->count() > 0) ? $res_user->current()->getNbUser() : 0;
    }

    /**
     * Lost Password.
     *
     * @invokable
     *
     * @param string $email
     */
    public function lostPassword($email)
    {
        $cars = 'azertyiopqsdfghjklmwxcvbn0123456789/*.!:;,....';
        $long = strlen($cars);
        srand((double) microtime() * 1000000);
        $password = '';
        for ($i = 0; $i < 8; ++$i) {
            $password .= substr($cars, rand(0, $long - 1), 1);
        }

        $ret = $this->getMapper()->update($this->getModel()
            ->setNewPassword(md5($password)), array('email' => $email));

        if ($ret > 0) {
            $user = $this->getMapper()
                ->select($this->getModel()
                ->setEmail($email))
                ->current();

            try {
                $this->getServiceMail()->sendTpl('tpl_forgotpasswd', $email, array('password' => $password, 'email' => $email, 'lastname' => $user->getLastname(), 'firstname' => $user->getFirstname()));
            } catch (\Exception $e) {
                syslog(1, 'Model name does not exist <> password is : '.$password.' <> '.$e->getMessage());
            }
        }

        return $ret;
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
            ->setPassword(md5($password)), array('id' => $this->getServiceAuth()
            ->getIdentity()
            ->getId(), 'password' => md5($oldpassword), ));
    }

    /**
     * @invokable
     *
     * @param int $id
     */
    public function get($id = null)
    {
        $me = $this->getServiceAuth()
            ->getIdentity()
            ->getId();

        if ($id === null) {
            $id = $me;
        }

        $res_user = $this->getMapper()->get($id, $me);

        if ($res_user->count() <= 0) {
            throw new \Exception('error get user:'.$id);
        }

        $users = $res_user->toArray();
        foreach ($users as &$user) {
            $user['roles'] = [];
            $user['program'] = [];
            $user['program'] = $this->getServiceProgram()->getListByUser(null, $user['id'])['list'];
            foreach ($this->getServiceRole()->getRoleByUser($user['id']) as $role) {
                $user['roles'][] = $role->getName();
            }
        }

        return (count($users) > 1) ? $users : reset($users);
    }

    /**
     * @param int $id
     * 
     * @return array
     */
    public function getRoleIds($id)
    {
        $ids = [];
        foreach ($this->getServiceRole()->getRoleByUser($id) as $m_role) {
            $ids[] = $m_role->getId();
        }

        return $ids;
    }

    /**
     * @invokable
     *
     * @param int $id
     */
    public function getListLite($id)
    {
        return $this->getMapper()->getListLite($id);
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
        $ret = [];
        if (!is_array($id)) {
            $id = array($id);
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
     * @invokable
     *
     * @param int $submission_id
     */
    public function getListPairGraders($submission_id)
    {
        return $this->getMapper()->getListPairGraders($submission_id);
    }

    /**
     * @param int $submission_id
     */
    public function getListBySubmissionWithInstrutorAndAcademic($submission_id)
    {
        return $this->getMapper()->getListBySubmissionWithInstrutorAndAcademic($submission_id);
    }
    
    /**
     * @param int $submission_id
     */
    public function getListBySubmission($submission_id)
    {
        return $this->getMapper()->getListBySubmission($submission_id);
    }

    /**
     * @invokable
     *
     * @param int $submission
     */
    public function getListByItemProgWithInstrutor($submission)
    {
        return $this->getMapper()->getListByItemProgWithInstrutor($submission);
    }

    /**
     * 
     * Get user list for submission and those available.
     * 
     * @param int $submission
     *
     * @return array
     */
    public function getListForSubmission($submission)
    {
        return $this->getMapper()->getListForSubmission($submission);
    }

    /**
     * Get all students for the instructor.
     *
     * @invokable
     *
     * @return array
     */
    public function getStudentList()
    {
        $ret = [];
        $instructor = $this->getIdentity();
        if (in_array(ModelRole::ROLE_INSTRUCTOR_STR, $instructor['roles'])) {
            $ret = $this->getMapper()->getStudentList($instructor['id']);
        }

        return $ret;
    }

    /**
     * @param int $conversation
     *
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getListByConversation($conversation)
    {
        $res_user = $this->getMapper()->getListByConversation($conversation);
        
        foreach ($res_user as $m_user) {
            $roles = [];
            foreach ($this->getServiceRole()->getRoleByUser($m_user->getId()) as $role) {
                $roles[] = $role->getName();
            }
            $m_user->setRoles($roles);
        }
        
        return $res_user;
    }

    /**
     * Get user list from item assignment.
     *
     * @invokable
     *
     * @param int $item_assignment
     *
     * @return \Application\Service\User
     */
    public function getListByItemAssignment($item_assignment)
    {
        $res_user = $this->getMapper()->getListByItemAssignment($item_assignment);

        foreach ($res_user as $m_user) {
            $roles = [];
            foreach ($this->getServiceRole()->getRoleByUser($m_user->getId()) as $role) {
                $roles[] = $role->getName();
            }
            $m_user->setRoles($roles);
        }

        return $res_user;
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
     * @param int $school
     */
    public function nbrBySchool($school)
    {
        return $this->getMapper()->nbrBySchool($school);
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

    /**
     * @return \Application\Service\Event
     */
    public function getServiceEvent()
    {
        return $this->getServiceLocator()->get('app_service_event');
    }

    /**
     * @return \Application\Service\Item
     */
    public function getServiceItem()
    {
        return $this->getServiceLocator()->get('app_service_item');
    }

    /**
     * @return \Application\Service\CtGroup
     */
    public function getServiceCtGroup()
    {
        return $this->getServiceLocator()->get('app_service_ct_group');
    }

    /**
     * @return \Application\Service\Submission
     */
    public function getServiceSubmission()
    {
        return $this->getServiceLocator()->get('app_service_submission');
    }

    /**
     * @return \Application\Service\Contact
     */
    public function getServiceContact()
    {
        return $this->getServiceLocator()->get('app_service_contact');
    }

    /**
     * @return \Mail\Service\Mail
     */
    public function getServiceMail()
    {
        return $this->getServiceLocator()->get('mail.service');
    }
}
