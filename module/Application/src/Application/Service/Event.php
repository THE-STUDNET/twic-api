<?php
/**
 * TheStudnet (http://thestudnet.com).
 *
 * Event
 */
namespace Application\Service;

use Dal\Service\AbstractService;
use Zend\Json\Server\Request;
use Zend\Http\Client;

/**
 * Class Event
 */
class Event extends AbstractService
{
    /**
     * Identification request.
     *
     * @var int
     */
    private static $id = 0;

    const TARGET_TYPE_USER = 'user';
    const TARGET_TYPE_GLOBAL = 'global';
    const TARGET_TYPE_SCHOOL = 'school';

    /**
     * create event
     *
     * @param  string $event
     * @param  mixed  $source
     * @param  mixed  $object
     * @param  array  $user
     * @param  mixed  $target
     * @param  mixed  $src
     * @throws \Exception
     * @return int
     */
    public function create($event, $source, $object, $libelle, $target, $src = null)
    {
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $m_event = $this->getModel()
            ->setUserId($src)
            ->setEvent($event)
            ->setSource(json_encode($source))
            ->setObject(json_encode($object))
            ->setTarget($target)
            ->setDate($date);

        if ($this->getMapper()->insert($m_event) <= 0) {
            throw new \Exception('error insert event');// @codeCoverageIgnore
        }
        $event_id = $this->getMapper()->getLastInsertValue();
        $this->getServiceEventSubscription()->add($libelle, $event_id);
        $user = $this->getServiceSubscription()->getListUserId($libelle);
        if (count($user) > 0) {
            $this->sendRequest(
                array_values($user),
                [
                'id' => $event_id,
                'event' => $event,
                'source' => $source,
                'date' => (new \DateTime($date))->format('Y-m-d\TH:i:s\Z'),
                'object' => $object],
                $target
            );
        }

        return $event_id;
    }

    /**
     * create notif
     *
     * @param mixed  $data
     * @param string $type
     * @param array  $libelle
     *
     * @return bool
     */
    public function sendData($data, $type, $libelle)
    {
        $users = $this->getServiceSubscription()->getListUserId($libelle);
        if (count($users) > 0) {
            $this->sendRequest(
                array_values($users),
                ['data' => $data,'event' => $type],
                self::TARGET_TYPE_USER
            );
        }

        return true;
    }

    /**
     * Send Request Event.
     *
     * @param array $users
     * @param array $notification
     * @param mixed $target
     *
     * @throws \Exception
     *
     * @return \Zend\Json\Server\Response
     */
    public function sendRequest($users, $notification, $target)
    {
        $authorization = $this->container->get('config')['node']['authorization'];
        $rep = false;
        $request = new Request();
        $request->setMethod('notification.publish')
            ->setParams(['notification' => $notification,'users' => $users,'type' => $target])
            ->setId(++ self::$id)
            ->setVersion('2.0');

        $client = new \Zend\Json\Server\Client($this->container->get('config')['node']['addr'], $this->getClient());
        $client->getHttpClient()->setHeaders([ 'Authorization' => $authorization]);

        try {
            $rep = $client->doRequest($request);
            if ($rep->isError()) {// @codeCoverageIgnore
                throw new \Exception('Error jrpc nodeJs: ' . $rep->getError()->getMessage(), $rep->getError()->getCode());// @codeCoverageIgnore
            }
        } catch (\Exception $e) {
            syslog(1, 'Request: ' . $request->toJson());
            syslog(1, $e->getMessage());
        }

        return $rep;
    }

    /**
     * Get Client Http.
     *
     * @return \Zend\Http\Client
     */
    private function getClient()
    {
        $client = new Client();
        $client->setOptions($this->container->get('config')['http-adapter']);

        return $client;
    }


    // /////////////// EVENT //////////////////////

    /**
     * Event user.publication
     *
     * @param  int   $post_id
     * @param  array $sub
     * @return number
     */
    public function userPublication($sub, $post_id, $type = 'user', $ev = 'publication')
    {
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        $data_post = $this->getDataPost($post_id);

        $event = $type;
        if (is_string($ev)) {
            $event .= '.'.$ev;
        }

        return $this->create($event, $this->getDataUser(), $data_post, $sub, self::TARGET_TYPE_USER, $user_id);
    }


    // ------------- DATA OBJECT -------------------

    /**
     * Get Data Post
     *
     * @param  int $post_id
     * @return array
     */
    private function getDataPost($post_id)
    {
        $ar_post = $this->getServicePost()->getLite($post_id)->toArray();

        return [
            'id' => $ar_post['id'],
            'name' => 'post',
            'data' => [
                'id' =>  $ar_post['id'],
                'content' => $ar_post['content'],
                'picture' => $ar_post['picture'],
                'name_picture' => $ar_post['name_picture'],
                'link' => $ar_post['link'],
                't_page_id' => $ar_post['t_page_id'],
                't_user_id' => $ar_post['t_user_id'],
                'parent_id' => $ar_post['parent_id'],
                'origin_id' => $ar_post['origin_id'],
                'type' => $ar_post['type'],
            ]
        ];
    }

    /**
     * Get Data User.
     *
     * @param int $user_id
     *
     * @return array
     */
    private function getDataUser($user_id = null)
    {
        if (null == $user_id) {
            $identity = $this->getServiceUser()->getIdentity();
            if ($identity === null) {
                return [];// @codeCoverageIgnore
            }
            $user_id = $identity['id'];
        }

        $m_user = $this->getServiceUser()->get($user_id);

        return ['id' => $user_id,
            'name' => 'user','data' =>
            ['firstname' => $m_user['firstname'],'email' => $m_user['email'],'lastname' => $m_user['lastname'],'nickname' => $m_user['nickname'],'gender' =>
                $m_user['gender'],
                'has_email_notifier' => $m_user['has_email_notifier'],
                'avatar' => $m_user['avatar'],
                'organization' => $m_user['organization_id'],
                'user_roles' => $m_user['roles']]];
    }

    // ----------------------------- Service
    /**
     * Get Service Event Comment.
     *
     * @return \Application\Service\EventSubscription
     */
    private function getServiceEventSubscription()
    {
        return $this->container->get('app_service_event_subscription');
    }

    /**
     * Get Service User.
     *
     * @return \Application\Service\User
     */
    private function getServiceUser()
    {
        return $this->container->get('app_service_user');
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
     * Get Service Subscription
     *
     * @return \Application\Service\Subscription
     */
    private function getServiceSubscription()
    {
        return $this->container->get('app_service_subscription');
    }
}
