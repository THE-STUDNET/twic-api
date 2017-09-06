<?php
/**
 * TheStudnet (http://thestudnet.com).
 *
 * User
 */
namespace Application\Service;

use Dal\Service\AbstractService;
use DateTimeZone;
use DateTime;
use JRpc\Json\Server\Exception\JrpcException;
use Application\Model\Role as ModelRole;
use Firebase\JWT\JWT;
use Zend\Db\Sql\Predicate\IsNull;
use Application\Model\PageUser as ModelPageUser;

/**
 * Class User.
 */
class User extends AbstractService
{

    public function loginLinkedIn($linkedin_id)
    {
        $auth = $this->getServiceAuth();
        $auth->getAdapter()->setLinkedinId(trim($linkedin_id));
        
        $result = $auth->authenticate();
        if (! $result->isValid()) {
            switch ($result->getCode()) {
                case - 3:
                    $code = - 32030;
                    break;
                case - 5:
                    $code = - 32031;
                    break;
                case - 6:
                    $code = - 32032;
                    break;
                case - 7:
                    $code = - 32033;
                    break;
                default:
                    $code = - 32000;
                    break;
            }
            
            throw new JrpcException($result->getMessages()[0], $code);
        }
        
        $identity = $this->getIdentity(true);
        
        // ici on check que le role externe ne ce connect pas avec login
        if (in_array(ModelRole::ROLE_EXTERNAL_STR, $identity['roles']) && count($identity['roles']) === 1) {
            $this->logout();
            throw new \Exception("Error: unauthorized Role");
        }
        
        return $identity;
    }

    /**
     * Log user
     *
     * @invokable
     *
     * @param string $user
     * @param string $password
     * @throws JrpcException
     * @return array
     */
    public function login($user, $password)
    {
        $auth = $this->getServiceAuth();
        $auth->getAdapter()->setIdentity(trim($user));
        $auth->getAdapter()->setCredential(trim($password));
        
        $result = $auth->authenticate();
        if (! $result->isValid()) {
            switch ($result->getCode()) {
                case - 3:
                    $code = - 32030;
                    break;
                case - 5:
                    $code = - 32031;
                    break;
                case - 6:
                    $code = - 32032;
                    break;
                case - 7:
                    $code = - 32033;
                    break;
                default:
                    $code = - 32000;
                    break;
            }
            
            throw new JrpcException($result->getMessages()[0], $code);
        }
        
        $identity = $this->getIdentity(true);
        
        // ici on check que le role externe ne ce connect pas avec login
        if (in_array(ModelRole::ROLE_EXTERNAL_STR, $identity['roles']) && count($identity['roles']) === 1) {
            $this->logout();
            throw new \Exception("Error: unauthorized Role");
        }
        
        return $identity;
    }

    // //////////////// EXTERNAL METHODE ///////////////////
    
    /**
     * Log In User.
     *
     * @invokable
     *
     * @param int $user
     * @param int $password
     * @throws JrpcException
     * @return array
     */
    public function auth($user, $password)
    {
        $auth = $this->getServiceAuth();
        $auth->getAdapter()->setIdentity($user);
        $auth->getAdapter()->setCredential($password);
        
        $result = $auth->authenticate();
        if (! $result->isValid()) {
            throw new JrpcException($result->getMessages()[0], $result->getCode()['code']);
        }
        
        return $this->getIdentity(true, true);
    }

    /**
     * Add User
     *
     * @invokable
     *
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     * @param string $uid
     * @param string $role
     * @return int
     */
    public function create($email, $firstname, $lastname, $uid, $role = null)
    {
        $id = $this->add($firstname, $lastname, $email, null, null, null, $uid, null, null, null, null, null, null, $role);
        
        return $this->get($id);
    }

    // //////////////// EXTERNAL METHODE ///////////////////
    
    /**
     * Get/Create Identity in cache.
     *
     * @param bool $init
     *
     * @return array
     */
    public function _getCacheIdentity($init = false)
    {
        $user = [];
        $identity = $this->getServiceAuth()->getIdentity();
        if ($identity === null) {
            return;
        }
        $id = $identity->getId();
        if ($init === false && $this->getCache()->hasItem('identity_' . $id)) {
            $user = $this->getCache()->getItem('identity_' . $id);
        } else {
            $user = $identity->toArray();
            $user['roles'] = [];
            foreach ($this->getServiceRole()->getRoleByUser() as $role) {
                $user['roles'][$role->getId()] = $role->getName();
            }
            
            $secret_key = $this->container->get('config')['app-conf']['secret_key'];
            $user['wstoken'] = sha1($secret_key . $id);
            
            // $generator = new TokenGenerator($secret_key_fb);
            // $user['fbtoken'] = $generator->setData(array('uid' => (string) $id))->setOption('debug', $secret_key_fb_debug)->setOption('expires', 1506096687)->create();
            $user['fbtoken'] = $this->create_custom_token($id);
            $this->getCache()->setItem('identity_' . $id, $user);
        }
        
        return $user;
    }

    /**
     * Crete token custom firebase
     *
     * @invokable
     *
     * @return string
     */
    public function getCustomTokenfb()
    {
        return $this->create_custom_token($this->getIdentity()['id']);
    }

    public function create_custom_token($uid, $is_premium_account = false)
    {
        $service_account_email = $this->container->get('config')['app-conf']['account_email'];
        $private_key = $this->container->get('config')['app-conf']['private_key'];
        
        $now_seconds = time();
        $payload = [
            "iss" => $service_account_email,
            "sub" => $service_account_email,
            "aud" => "https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit",
            "iat" => $now_seconds,
            "exp" => $now_seconds + (60 * 60), // Maximum expiration time is one hour
            "uid" => $uid
            // "claims" => ["premium_account" => $is_premium_account]
        ];
        
        return JWT::encode($payload, $private_key, "RS256");
    }

    /**
     * Delete Cached Identity of user.
     *
     * @param int $id
     *
     * @return bool
     */
    private function deleteCachedIdentityOfUser($id)
    {
        return $this->getCache()->removeItem('identity_' . $id);
    }

    /**
     * Get/Create Identity External in cache.
     *
     * @param bool $init
     * @return array
     */
    public function _getCacheIdentityExternal($init = false)
    {
        $user = [];
        $identity = $this->getServiceAuth()->getIdentity();
        if ($identity === null) {
            return;
        }
        $id = $identity->getId();
        
        if ($init === false && $this->getCache()->hasItem('identity_' . $id)) {
            $user = $this->getCache()->getItem('identity_' . $id);
        } else {
            $user = $identity->toArray();
            $user['roles'] = [];
            foreach ($this->getServiceRole()->getRoleByUser() as $role) {
                $user['roles'][$role->getId()] = $role->getName();
            }
            $this->getCache()->setItem('identity_' . $id, $user);
        }
        
        return $user;
    }

    /**
     * Get Identity.
     *
     * @invokable
     *
     * @param bool $init
     * @param bool $external
     *
     * @return array
     */
    public function getIdentity($init = false, $external = false)
    {
        return ($external) ? $this->_getCacheIdentityExternal($init) : $this->_getCacheIdentity($init);
    }

    /**
     * Get List Session Active.
     *
     * @invokable
     *
     * @return array
     */
    public function getListSession()
    {
        $auth = $this->getServiceAuth();
        
        return $auth->getStorage()->getListSession($auth->getIdentity()
            ->getId());
    }

    /**
     * Log out.
     *
     * @invokable
     *
     * @return bool
     */
    public function logout()
    {
        $this->getServiceAuth()->clearIdentity();
        
        return true;
    }

    /**
     * Suspend or reactivate user account.
     *
     * @invokable
     *
     * @param int $id
     * @param bool $suspend
     * @param string $reason
     *
     * @return bool
     */
    public function suspend($id, $suspend, $reason = null)
    {
        $m_user = $this->getModel()
            ->setId($id)
            ->setSuspensionDate(1 === $suspend ? (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s') : new IsNull())
            ->setSuspensionReason(1 === $suspend ? $reason : new IsNull());
        if (1 === $suspend) {
            $this->getServiceAuth()
                ->getStorage()
                ->clearSession($id);
        }
        return $this->getMapper()->update($m_user);
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
     * @param int $organization_id
     * @param string $interest
     * @param string $avatar
     * @param array $roles
     * @param string $timezone
     * @param string $background
     * @param string $nickname
     * @param string $ambassador
     * @param array $address
     *
     * @return int
     */
    public function add($firstname, $lastname, $email, $gender = null, $origin = null, $nationality = null, $sis = null, $password = null, $birth_date = null, $position = null, $organization_id = null, $interest = null, $avatar = null, $roles = null, $timezone = null, $background = null, $nickname = null, $ambassador = null, $address = null)
    {
        if ($this->getNbrEmailUnique($email) > 0) {
            throw new JrpcException('duplicate email', - 38001);
        }
        
        if (! empty($sis)) {
            if ($this->getNbrSisUnique($sis) > 0) {
                throw new JrpcException('uid email', - 38002);
            }
        }
        
        $m_user = $this->getModel();
        
        if ($address !== null) {
            $address = $this->getServiceAddress()->getAddress($address);
            if ($address && null !== ($address_id = $address->getId())) {
                $m_user->setAddressId($address_id);
            }
        }
        
        $m_user->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setSis($sis)
            ->setOrigin($origin)
            ->setGender($gender)
            ->setNationality($nationality)
            ->setBirthDate($birth_date)
            ->setPosition($position)
            ->setInterest($interest)
            ->setAvatar($avatar)
            ->setTimezone($timezone)
            ->setBackground($background)
            ->setNickname($nickname)
            ->setAmbassador($ambassador)
            ->setEmailSent(0)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
        
        if (empty($password)) {
            // $cars = 'azertyiopqsdfghjklmwxcvbn0123456789/*.!:;,....';
            $cars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $long = strlen($cars);
            srand((double) microtime() * 1000000);
            $password = '';
            for ($i = 0; $i < 8; ++ $i) {
                $password .= substr($cars, rand(0, $long - 1), 1);
            }
        }
        $m_user->setPassword(md5($password));
        
        if ($this->getMapper()->insert($m_user) <= 0) {
            throw new \Exception('error insert');
        }
        
        $id = (int) $this->getMapper()->getLastInsertValue();
        
        if ($organization_id !== null) {
            $this->addOrganization($organization_id, $id, true);
        }
        
        // Si il n'y a pas de role ou que ce n'est pas un admin c'est un user
        if (empty($roles) || ! in_array(ModelRole::ROLE_ADMIN_STR, $this->getIdentity()['roles'])) {
            $roles = [
                ModelRole::ROLE_USER_STR
            ];
        }
        foreach ($roles as $r) {
            $this->getServiceUserRole()->add($this->getServiceRole()
                ->getIdByName($r), $id);
        }
        
        $this->getServiceSubscription()->add('SU' . $id, $id);
        
        return $id;
    }

    /**
     * Import user data.
     *
     * @invokable
     *
     * @param array $data
     * @param int $page_id
     *
     * @return array
     */
    public function import($data, $page_id = null)
    {
        $error = [];
        foreach ($data as $u) {
            try {
                $id = $this->add($u['firstname'], /*firstname*/
                  $u['lastname'], /*lastname*/
                  $u['email'], /*email*/
                  null, /*gender*/
                  null, /*origin*/
                  null, /*$nationality*/
                  array_key_exists('uid', $u) ? $u['uid'] : null, /*$sis*/
                  null, /*password*/
                  null, /*birth_date*/
                  null, /*position*/
                  $page_id, /*organization_id*/
                  null, /*interest*/
                  null, /*avatar*/
                  null, /*roles*/
                  null, /*timezone*/
                  null, /*background*/
                  array_key_exists('nickname', $u) ? $u['nickname'] : null, /*$nickname*/
                  null, /*ambassador*/
                  null /*address*/
                );
                
                $this->getServicePageUser()->add($page_id, $id, ModelPageUser::ROLE_USER, ModelPageUser::STATE_MEMBER);
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
     * Get List user For Attendees
     *
     * @invokable
     *
     * @param array $course
     * @param array $program
     * @param array $school
     * @param array $exclude_course
     * @param array $exclude_program
     * @param array $exclude_user
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getListAttendees($course = null, $program = null, $school = null, $page = null, $exclude_course = null, $exclude_program = null, $exclude_page = null, $exclude_user = null, $roles = null)
    {
        $identity = $this->getIdentity();
        $is_sadmin_admin = (in_array(ModelRole::ROLE_SADMIN_STR, $identity['roles']) || in_array(ModelRole::ROLE_ADMIN_STR, $identity['roles']));
        
        $res_user = $this->getMapper()->getListAttendees($identity['id'], $is_sadmin_admin, $course, $program, $school, $page, $exclude_course, $exclude_program, $exclude_user, $exclude_page, $roles);
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
     * Update User
     *
     * @invokable
     *
     * @param int $id
     * @param string $gender
     * @param string $origin
     * @param string $nationality
     * @param string $firstname
     * @param string $lastname
     * @param string $sis
     * @param string $email
     * @param string $birth_date
     * @param string $position
     * @param int $organization_id
     * @param string $interest
     * @param string $avatar
     * @param array $roles
     * @param string $resetpassword
     * @param bool $has_email_notifier
     * @param string $timezone
     * @param string $background
     * @param string $nickname
     * @param bool $ambassador
     * @param string $password
     * @param array $address
     *
     * @return int
     */
    public function update($id = null, $gender = null, $origin = null, $nationality = null, $firstname = null, $lastname = null, $sis = null, $email = null, $birth_date = null, $position = null, $organization_id = null, $interest = null, $avatar = null, $roles = null, $resetpassword = null, $has_email_notifier = null, $timezone = null, $background = null, $nickname = null, $suspend = null, $suspension_reason = null, $ambassador = null, $password = null, $address = null)
    {
        if ($this->getNbrEmailUnique($email, $id) > 0) {
            throw new JrpcException('duplicate email', - 38001);
        }
        
        $m_user = $this->getModel();
        
        if ($id === null) {
            $id = $this->getIdentity()['id'];
        }
        if (! empty($password)) {
            $m_user->setPassword(md5($password));
        }
        if ($address !== null) {
            $address_id = null;
            if ($address === 'null') {
                $address_id = new IsNull('address_id');
            } else {
                $address = $this->getServiceAddress()->getAddress($address);
                if ($address) {
                    $address_id = $address->getId();
                }
            }
            if ($address_id !== null) {
                $m_user->setAddressId($address_id);
            }
        }
        
        $m_user->setId($id)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setOrigin(('null' === $origin) ? new IsNull('origin') : $origin)
            ->setGender($gender)
            ->setNationality(('null' === $nationality) ? new IsNull('nationality') : $nationality)
            ->setSis($sis)
            ->setBirthDate(('null' === $birth_date) ? new IsNull('birth_date') : $birth_date)
            ->setPosition($position)
            ->setInterest($interest)
            ->setAvatar($avatar)
            ->setHasEmailNotifier($has_email_notifier)
            ->setTimezone($timezone)
            ->setBackground($background)
            ->setNickname($nickname)
            ->setAmbassador($ambassador);
        
        // @TODO secu school_id
        if ($organization_id !== null) {
            if ($organization_id === 'null') {
                $organization_id = new IsNull('organization_id');
            }
            $this->addOrganization($organization_id, $id, true);
        }
        
        if ($roles !== null) {
            if (! is_array($roles)) {
                $roles = [
                    $roles
                ];
            }
            $this->getServiceUserRole()->deleteByUser($id);
            foreach ($roles as $r) {
                $this->getServiceUserRole()->add($this->getServiceRole()
                    ->getIdByName($r), $id);
            }
        }
        
        $ret = $this->getMapper()->update($m_user);
        if ($resetpassword) {
            $this->lostPassword($this->get($id)['email']);
        }
        
        if (null !== $suspend) {
            $this->suspend($id, $suspend, $suspension_reason);
        }
        // on supprime son cache identity pour qu'a ca prochaine cannection il el recré.
        $this->deleteCachedIdentityOfUser($id);
        $this->getServiceEvent()->sendData($id, 'user.update', [
            'PU' . $id
        ]);
        /*
         * if (null !== $avatar && $id === $this->getIdentity()['id']) {
         * $this->getServicePost()->addSys(
         * 'UU'.$id. 'A'.$avatar, 'Avatar update', [
         * 'state' => 'update',
         * 'user' => $id,
         * 'avatar' => $avatar,
         * ], 'update',
         * null/*sub/,
         * null/*parent/,
         * null/*page/,
         * $id/*user/,
         * 'user'
         * );
         * }
         */
        
        return $ret;
    }

    /**
     * Get number of email.
     *
     * @param string $email
     * @param int $user_id
     *
     * @return int
     */
    public function getNbrEmailUnique($email, $user_id = null)
    {
        $res_user = $this->getMapper()->getEmailUnique($email, $user_id);
        
        return ($res_user->count() > 0) ? $res_user->current()->getNbUser() : 0;
    }

    /**
     * Get number of sis.
     *
     * @param string $sis
     * @param int $user_id
     *
     * @return int
     */
    public function getNbrSisUnique($sis, $user_id = null)
    {
        $res_user = $this->getMapper()->getNbrSisUnique($sis, $user_id);
        
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
        $cars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
        $long = strlen($cars);
        srand((double) microtime() * 1000000);
        $password = '';
        for ($i = 0; $i < 8; ++ $i) {
            $password .= substr($cars, rand(0, $long - 1), 1);
        }
        
        $ret = $this->getMapper()->update($this->getModel()
            ->setNewPassword(md5($password)), [
            'email' => $email
        ]);
        if ($ret > 0) {
            $m_user = $this->getModel()
                ->setEmail($email)
                ->setSuspensionDate(new IsNull())
                ->setDeletedDate(new IsNull());
            
            $user = $this->getMapper()
                ->select($m_user)
                ->current();
            $m_page = $this->getServicePage()->getLite($user->getOrganizationId());
            try {
                $this->getServiceMail()->sendTpl('tpl_forgotpasswd', $email, [
                    'prefix' => ($m_page !== false && is_string($m_page->getLibelle()) && ! empty($m_page->getLibelle())) ? $m_page->getLibelle() : null,
                    'password' => $password,
                    'email' => $email,
                    'lastname' => $user->getLastname(),
                    'firstname' => $user->getFirstname()
                ]);
            } catch (\Exception $e) {
                syslog(1, 'Model name does not exist <> password is : ' . $password . ' <> ' . $e->getMessage());
            }
        } else {
            throw new JrpcException('bad email', - 32033);
        }
        
        return $ret;
    }

    /**
     * Send New Password
     *
     * @invokable
     *
     * @param array|int $id
     * @param int $page_id
     */
    public function sendPassword($id = null, $page_id = null)
    {
        if (null !== $page_id) {
            $identity = $this->getIdentity();
            $is_admin = (in_array(ModelRole::ROLE_ADMIN_STR, $identity['roles']));
            $res_user = $this->getMapper()->getList($identity['id'], $is_admin, null, null, $page_id, null, null, null, true);
            $id = [];
            foreach ($res_user as $m_user) {
                $id[] = $m_user->getId();
            }
        }
        
        if (! is_array($id)) {
            $id = [$id];
        }

        $nb = 0;
        foreach ($id as $uid) {
            $res_user = $this->getMapper()->select($this->getModel()->setId($uid));
            if ($res_user->count() <= 0) {
                continue;
            }
            
            try {
                $uniqid = uniqid();
                $m_user = $res_user->current();
                $m_page = $this->getServicePage()->getLite($m_user->getOrganizationId());

                $this->getServicePreregistration()->add($uniqid, null, null, null, $m_user->getOrganizationId(), $m_user->getId());
                $this->getServiceMail()->sendTpl('tpl_sendpasswd', $m_user->getEmail(), [
                    'prefix' => (is_string($m_page->getLibelle()) && ! empty($m_page->getLibelle())) ? $m_page->getLibelle() : null,
                    'password' => $password,
                    'uniqid' => $uniqid,
                    'email' => $m_user->getEmail(),
                    'lastname' => $m_user->getLastname(),
                    'firstname' => $m_user->getFirstname()
                ]);

                $nb ++;
            } catch (\Exception $e) {
                syslog(1, 'Model name does not exist <> uniqid is : ' . $uniqid . ' <MESSAGE> ' . $e->getMessage() . '  <CODE> ' . $e->getCode());
            }
        }
        
        return $nb;
    }

    /**
     * Update Password.
     *
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
            'password' => md5($oldpassword)
        ));
    }

    public function _updatePassword($email, $password)
    {
        return $this->getMapper()->update($this->getModel()
            ->setPassword(md5($password)), array(
            'id' => $this->getServiceAuth()
                ->getIdentity()
                ->getId(),
            'email' => md5($email)
        ));
    }

    /**
     *
     * @param int $id
     * @return \Dal\Db\ResultSet\ResultSet | \Application\Model\User
     */
    public function getLite($id)
    {
        $res_user = $this->getMapper()->select($this->getModel()
            ->setId($id));
        
        return (is_array($id)) ? $res_user : $res_user->current();
    }

    /**
     * Get User
     *
     * @invokable
     *
     * @param int|array $id
     * @return array
     */
    public function get($id = null)
    {
        $users = [];
        $identity = $this->getIdentity();
        $user_id = $identity['id'];
        if ($id === null) {
            $id = $user_id;
        }
        
        $is_admin = (in_array(ModelRole::ROLE_ADMIN_STR, $identity['roles']));
        $res_user = $this->getMapper()->get($id, $user_id, $is_admin);
        
        if ($res_user->count() <= 0) {
            throw new \Exception('error get user: ' . json_encode($id));
        }
        
        foreach ($res_user->toArray() as $user) {
            $user['roles'] = [];
            foreach ($this->getServiceRole()->getRoleByUser($user['id']) as $role) {
                $user['roles'][] = $role->getName();
            }
            $users[$user['id']] = $user;
        }
        
        if (is_array($id)) {
            foreach ($id as $i) {
                if (! isset($users[$i])) {
                    $users[$i] = null;
                }
            }
        }
        
        return (is_array($id)) ? $users : reset($users);
    }

    /**
     * Get User Id
     *
     * @invokable
     *
     * @param string $search
     * @param array $exclude
     * @param array $filter
     * @param int $contact_state
     * @param int $page_id
     * @param int $post_id
     * @param array $order
     * @param string $role
     * @param int $conversation_id
     *
     * @return array
     */
    public function getListId($search = null, $exclude = null, $filter = null, $contact_state = null, $page_id = null, $post_id = null, $order = null, $role = null, $conversation_id = null)
    {
        $identity = $this->getIdentity();
        if (null !== $exclude && ! is_array($exclude)) {
            $exclude = [
                $exclude
            ];
        }
        
        $is_admin = (in_array(ModelRole::ROLE_ADMIN_STR, $identity['roles']));
        $mapper = $this->getMapper();
        $res_user = $mapper->usePaginator($filter)->getList($identity['id'], $is_admin, $post_id, $search, $page_id, $order, $exclude, $contact_state, null, $role, $conversation_id);
        
        $users = [];
        foreach ($res_user as $m_user) {
            $users[] = $m_user->getId();
        }
        
        return (null === $filter) ? $users : [
            'list' => $users,
            'count' => $mapper->count()
        ];
    }

    /**
     * Get List Lite.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return \Dal\Db\ResultSet\ResultSet
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
     * @return array
     */
    public function delete($id)
    {
        $ret = [];
        if (! is_array($id)) {
            $id = array(
                $id
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
     * @invokable
     *
     * @param string $token
     * @param string $uuid
     */
    public function registerFcm($token, $uuid)
    {
        return $this->getServiceFcm()->register($uuid, $token);
    }

    /**
     *
     * @param string $uuid
     * @param string $token
     */
    public function unregisterGcm($token = null, $uuid = null)
    {
        $this->getServiceGcmGroup()->delete($uuid, $token);
    }

    /**
     * Add School relation
     *
     *
     * @invokable
     *
     * @param int $organization_id
     * @param int $user_id
     * @param bool $default
     * @return NULL|int
     */
    public function addOrganization($organization_id, $user_id, $default = false)
    {
        $ret = null;
        if ($default === true) {
            $ret = $this->getMapper()->update($this->getModel()
                ->setId($user_id)
                ->setOrganizationId($organization_id));
        }
        
        return $ret;
    }

    public function removeOrganizationId($organization_id)
    {
        return $this->getMapper()->update($this->getModel()
            ->setOrganizationId(new IsNull('organization_id')), [
            'organization_id' => $organization_id
        ]);
    }

    /**
     * sign In Password
     *
     * @invokable
     *
     * @param string $account_token
     * @param string $password
     */
    public function signIn($account_token, $password)
    {
        $m_registration = $this->getServicePreregistration()->get($account_token);
        if (false === $m_registration) {
            throw new \Exception('Account token not found.');
        }
        
        if (is_numeric($m_registration->getUserId())) {
            $this->getMapper()->update($this->getModel()
                ->setPassword(md5($password)), [
                'id' => $m_registration->getUserId()
            ]);
            $user_id = $m_registration->getUserId();
        } else {
            $user_id = $this->add($m_registration->getFirstname(), $m_registration->getLastname(), $m_registration->getEmail(), null, null, null, null, $password, null, null, (is_numeric($m_registration->getOrganizationId()) ? $m_registration->getOrganizationId() : null));
        }
        
        $m_user = $this->getLite($user_id);
        $login = $this->login($m_user->getEmail(), $password);
        $this->getServicePreregistration()->delete($account_token, $m_user->getId());
        
        return $login;
    }

    /**
     * @invokable
     *
     * @param string $code
     * @param string $account_token
     */
    public function linkedinSignIn($code, $account_token = null)
    {
        $identity = $this->getIdentity();
        $linkedin = $this->getServiceLinkedIn();
        $linkedin->init($code, null);
        $m_people = $linkedin->people();
        $linkedin_id = $m_people->getId();
        $login = false;
        if (! $linkedin_id || ! is_string($linkedin_id)) {
            throw new \Exception('Error LinkedIn Id');
        }
        
        $res_user = $this->getMapper()->select($this->getModel()
            ->setLinkedinId($linkedin_id));
        if ($res_user->count() > 0) { // utilisateur existe on renvoye une session
            $login = $this->loginLinkedIn($linkedin_id);
        } else { // utilisateur existe pas
            if (isset($identity['id'])) { // SI connecter alors on save id linkedin
                $this->getMapper()->update($this->getModel()
                    ->setLinkedinId($linkedin_id), [
                    'id' => $identity['id']
                ]);
                $login = true;
            } else {
                $m_registration = $this->getServicePreregistration()->get($account_token);
                if (false === $m_registration) {
                    throw new \Exception('Account token not found.');
                }
                
                if (is_numeric($m_registration->getUserId())) {
                    $this->getMapper()->update($this->getModel()
                        ->setLinkedinId($linkedin_id), [
                        'id' => $m_registration->getUserId()
                    ]);
                    $user_id = $m_registration->getUserId();
                } else {
                    $user_id = $this->add($m_registration->getFirstname(), $m_registration->getLastname(), $m_registration->getEmail(), null, null, null, null, null, null, null, (is_numeric($m_registration->getOrganizationId()) ? $m_registration->getOrganizationId() : null));
                    $this->getMapper()->update($this->getModel()
                        ->setLinkedinId($linkedin_id), [
                        'id' => $user_id
                    ]);
                }
                
                $m_user = $this->getLite($user_id);
                
                $login = $this->loginLinkedIn($linkedin_id);
                $this->getServicePreregistration()->delete($account_token, $m_user->getId());
            }
        }
        
        return $login;
    }

    /**
     * Get Service Preregistration
     *
     * @return \Application\Service\Preregistration
     */
    private function getServicePreregistration()
    {
        return $this->container->get('app_service_preregistration');
    }

    /**
     * Get Service LinkedIn
     *
     * @return \LinkedIn
     */
    private function getServiceLinkedIn()
    {
        return $this->container->get('linkedin.service');
    }

    /**
     * Get Service Post
     *
     * @return \Application\Service\Post
     */
    private function getServicePost()
    {
        return $this->container->get('app_service_post');
    }

    /**
     * Get Service Event
     *
     * @return \Application\Service\Event
     */
    private function getServiceEvent()
    {
        return $this->container->get('app_service_event');
    }

    /**
     * Get Service Auth.
     *
     * @return \Zend\Authentication\AuthenticationService
     */
    private function getServiceAuth()
    {
        return $this->container->get('auth.service');
    }

    /**
     * Get Service Role.
     *
     * @return \Application\Service\Role
     */
    private function getServiceRole()
    {
        return $this->container->get('app_service_role');
    }

    /**
     * Get Service UserRole.
     *
     * @return \Application\Service\UserRole
     */
    private function getServiceUserRole()
    {
        return $this->container->get('app_service_user_role');
    }

    /**
     * Get Storage if define in config.
     *
     * @return \Zend\Cache\Storage\StorageInterface
     */
    private function getCache()
    {
        $config = $this->container->get('config')['app-conf'];
        
        return $this->container->get($config['cache']);
    }

    /**
     * Get Service Mail.
     *
     * @return \Mail\Service\Mail
     */
    private function getServiceMail()
    {
        return $this->container->get('mail.service');
    }

    /**
     * Get Service Subscription
     *
     * @return \Application\Service\Subscription
     */
    private function getServiceSubscription()
    {
        return $this->container->get('app_service_subscription');
    }

    /**
     * Get Service Fcm
     *
     * @return \Application\Service\Fcm
     */
    private function getServiceFcm()
    {
        return $this->container->get('fcm');
    }

    /**
     * Get Service Address
     *
     * @return \Address\Service\Address
     */
    private function getServiceAddress()
    {
        return $this->container->get('addr_service_address');
    }

    /**
     * Get Service GcmGroup
     *
     * @return \Application\Service\GcmGroup
     */
    private function getServiceGcmGroup()
    {
        return $this->container->get('app_service_gcm_group');
    }

    /**
     * Get Service PageUser
     *
     * @return \Application\Service\PageUser
     */
    private function getServicePageUser()
    {
        return $this->container->get('app_service_page_user');
    }

    /**
     * Get Service Page
     *
     * @return \Application\Service\Page
     */
    private function getServicePage()
    {
        return $this->container->get('app_service_page');
    }
}
