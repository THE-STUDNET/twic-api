<?php
/**
 * TheStudnet (http://thestudnet.com)
 *
 * Post
 */
namespace Application\Service;

use Application\Model\Page as ModelPage;
use Dal\Service\AbstractService;
use Application\Model\Role as ModelRole;
use Application\Model\PostSubscription as ModelPostSubscription;
use Zend\Db\Sql\Predicate\IsNull;
use Zend\Http\Client;
use ZendService\Google\Gcm\Notification as GcmNotification;

/**
 * Class Post
 */
class Post extends AbstractService
{

    /**
     * Add Post
     *
     * @invokable
     *
     * @param string $content
     * @param string $link
     * @param string $picture
     * @param string $name_picture
     * @param string $link_title
     * @param string $link_desc
     * @param int    $parent_id
     * @param int    $t_page_id
     * @param int    $t_user_id
     * @param int    $page_id
     * @param int    $lat
     * @param int    $lng
     * @param array  $docs
     * @param string $data
     * @param string $event
     * @param string $uid
     * @param array  $sub
     * @param string $type
     *
     * @return \Application\Model\Post
     */
    public function add(
      $content = null,
      $picture = null,
      $name_picture = null,
      $link = null,
      $link_title = null,
      $link_desc = null,
      $parent_id = null,
      $t_page_id = null,
      $t_user_id = null,
      $page_id = null,
      $lat =null,
      $lng = null,
      $docs = null,
      $data = null,
      $event = null,
      $uid = null,
      $sub = null,
      $type = null,
      $item_id = null
    ) {
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        $origin_id = null;
        if (null !== $parent_id) {
            $m_post = $this->getMapper()->select($this->getModel()->setId($parent_id))->current();
            $origin_id = (is_numeric($m_post->getOriginId())) ?
                $m_post->getOriginId()  :
                $m_post->getId();
            $uid = $m_post->getUid();
        }

        if (empty($type)) {
            $type = 'post';
        }
        $uid = (($uid) && is_string($uid) && !empty($uid)) ? $uid:false;
        $is_notif = !!$uid;

        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        if (!$is_notif && null === $parent_id && null === $t_page_id && null === $t_user_id) {
            $t_user_id = $user_id;
        }

        if (!empty($data) && !is_string($data)) {
            $data = json_encode($data);
        }

        if (null !== $parent_id) {
            $uid = null;
        }

        $user_id = $this->getServiceUser()->getIdentity()['id'];
        $m_post = $this->getModel()
            ->setContent($content)
            ->setPicture($picture)
            ->setNamePicture($name_picture)
            ->setLink($link)
            ->setLinkTitle($link_title)
            ->setLinkDesc($link_desc)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
            ->setPageId($page_id)
            ->setLat($lat)
            ->setItemId($item_id)
            ->setLng($lng)
            ->setParentId($parent_id)
            ->setOriginId($origin_id)
            ->setTPageId($t_page_id)
            ->setTUserId($t_user_id)
            ->setUid($uid)
            ->setType($type)
            ->setData($data);

        if (!$is_notif || null !== $parent_id) {
            $m_post->setUserId($user_id);
        }

        if ($this->getMapper()->insert($m_post) <= 0) {
            throw new \Exception('error add post');
        }
        $id = $this->getMapper()->getLastInsertValue();

        $d = ['id' => (int)$id, 'parent_id' => $parent_id, 'origin_id' => $origin_id];
        if (is_array($data)) {
            $data = array_merge($d, $data);
        } else {
            $data = $d;
        }

        if (null !== $docs) {
            $this->getServicePostDoc()->_add($id, $docs);
        }

        $base_id = ($origin_id) ? $origin_id:$id;
        $m_post_base = $this->getLite($base_id);
        $is_private_page = (is_numeric($m_post_base->getTPageId()) && ($this->getServicePage()->getLite($m_post_base->getTPageId())->getConfidentiality() === ModelPage::CONFIDENTIALITY_PRIVATE));
        $pevent = [];

        // si c pas une notification on gére les hastags
        if (!$is_notif) {
            $ar = array_filter(
                explode(' ', str_replace(["\r\n","\n","\r"], ' ', $content)),
                function ($v) {
                    return (strpos($v, '#') !== false) || (strpos($v, '@') !== false);
                }
            );

            $this->getServiceHashtag()->add($ar, $id);
            $this->getServicePostSubscription()->addHashtag($ar, $id, $date);

            $pevent = array_merge($pevent, ['M'.$m_post_base->getUserId()]);
        }

        $et = $this->getTarget($m_post_base);
        // S'IL Y A UNE CIBLE A LA BASE ET que l'on a pas definie d'abonnement ON NOTIFIE  P{target}nbr
        if (false !== $et && empty($sub) /*&& null === $parent_id*/) {
            $pevent = array_merge($pevent, ['P'.$et]);
        }

        // if ce n'est pas un page privée
        if (!$is_private_page &&  !$is_notif) {
            $pevent = array_merge($pevent, ['P'.$this->getOwner($m_post_base)]);
        }

        if ($parent_id && $origin_id) {
            // SI N'EST PAS PRIVATE ET QUE CE N'EST PAS UNE NOTIF -> ON NOTIFIE LES AMIES DES OWNER
            $m_post = $this->getLite($id);
            if (!$is_private_page &&  !$is_notif) {
                $pevent = array_merge($pevent, ['P'.$this->getOwner($m_post)]);
            }

            $pevent = array_merge($pevent, ['M'.$m_post_base->getUserId()]);
            // SI NOTIF ET QUE LE PARENT N'A PAS DE TARGET ON RECUPERE TTES LES SUBSCRIPTIONS
            if ($is_notif && null === $sub && $et === false) {
                $sub = $this->getServicePostSubscription()->getListLibelle($origin_id);
            }
        }

        if (!empty($sub)) {
            $pevent = array_merge($pevent, $sub);
        }
        $ev=((!empty($event))? $event:(($base_id!==$id) ? ModelPostSubscription::ACTION_COM : ModelPostSubscription ::ACTION_CREATE));

        $this->getServicePostSubscription()->add(
            array_unique($pevent),
            $base_id,
            $date,
            $ev,
            ((!$is_notif) ? $user_id:null),
            (($base_id!==$id) ? $id:null),
            $data
        );
        
        if($parent_id == null){
            if($t_page_id != null){
                $m_page = $this->getServicePage()->getLite($t_page_id);
                if($m_page->getType() == ModelPage::TYPE_COURSE && $type === 'post' && $m_page->getIsPublished()) {
                    $ar_pages = [];
                    $res_user = $this->getServiceUser()->getLite($this->getServicePageUser()->getListByPage($t_page_id)[$t_page_id]);
                    if($res_user !== null) {
                        foreach($res_user as $m_user){
                            if($m_user->getId() == $user_id){
                                continue;
                            }
                            $m_organization = false;
                            if($m_user->getOrganizationId()){
                                if(!array_key_exists($m_user->getOrganizationId(), $ar_pages)){
                                    $ar_pages[$m_user->getOrganizationId()] = $this->getServicePage()->getLite($m_user->getOrganizationId());
                                }
                                $m_organization = $ar_pages[$m_user->getOrganizationId()];
                            }
                            try {
                                $url = sprintf("https://gnam.%s/page/course/%s/timeline",$this->container->get('config')['app-conf']['uiurl'],$m_page->getId());
                                $this->getServiceMail()->sendTpl('tpl_coursepost', $m_user->getEmail(), [
                                    'pagename' => $m_page->getTitle(),
                                    'pageurl' => $url,
                                    'firstname' => $m_user->getFirstName(),
                                    'prefix' => ($m_organization !== false && is_string($m_organization->getLibelle()) && !empty($m_page->getLibelle())) ? $m_organization->getLibelle() : null,
                                ]);
                                
                                $gcm_notification = new GcmNotification();
                                $gcm_notification->setTitle($m_page->getTitle())
                                    ->setSound("default")
                                    ->setColor("#00A38B")
                                    ->setIcon("icon")
                                    ->setTag("PAGEPOST".$t_page_id)
                                    ->setBody("Someone posted on the course ". $m_page->getTitle());
                                
                                $this->getServiceFcm()->send($m_user->getId(),null,$gcm_notification);
                            }
                            catch (\Exception $e) {
                                syslog(1, 'Model name does not exist Post<MESSAGE> ' . $e->getMessage() . '  <CODE> ' . $e->getCode());
                            }
                        }
                    }
                } elseif($m_page->getType() == ModelPage::TYPE_ORGANIZATION &&  $type === 'post') {
                    $ar_pages = [];
                    $res_user = $this->getServiceUser()->getLite($this->getServicePageUser()->getListByPage($t_page_id)[$t_page_id]);
                    if($res_user !== null) {
                        foreach($res_user as $m_user){
                            if($m_user->getId() == $user_id){
                                continue;
                            }
                            $m_organization = false;
                            if($m_user->getOrganizationId()){
                                if(!array_key_exists($m_user->getOrganizationId(), $ar_pages)){
                                    $ar_pages[$m_user->getOrganizationId()] = $this->getServicePage()->getLite($m_user->getOrganizationId());
                                }
                                $m_organization = $ar_pages[$m_user->getOrganizationId()];
                            }
                            try {
                                $url = sprintf("https://gnam.%s/page/organization/%s/timeline",$this->container->get('config')['app-conf']['uiurl'],$m_page->getId());
                                $this->getServiceMail()->sendTpl('tpl_organizationpost', $m_user->getEmail(), [
                                    'pagename' => $m_page->getTitle(),
                                    'pageurl' => $url,
                                    'firstname' => $m_user->getFirstName(),
                                    'prefix' => ($m_organization !== false && is_string($m_organization->getLibelle()) && !empty($m_organization->getLibelle())) ? $m_organization->getLibelle() : null,
                                ]);
                                
                                $gcm_notification = new GcmNotification();
                                $gcm_notification->setTitle($m_page->getTitle())
                                ->setSound("default")
                                ->setColor("#00A38B")
                                ->setIcon("icon")
                                ->setTag("PAGEPOST".$t_page_id)
                                ->setBody("Someone posted in ". $m_page->getTitle());
                                
                                $this->getServiceFcm()->send($m_user->getId(),null,$gcm_notification);
                            }
                            catch (\Exception $e) {
                                syslog(1, 'Model name does not exist Post<MESSAGE> ' . $e->getMessage() . '  <CODE> ' . $e->getCode());
                            }
                        }
                    }
                }
            }
        } else {
            $m_post = $this->getLite($parent_id);
            if($user_id != $m_post->getUserId()){
                $m_user = $this->getServiceUser()->getLite($m_post->getUserId());
                $m_me = $this->getServiceUser()->getLite($user_id);
                $m_page = false;
                if($m_user->getOrganizationId()){
                    $m_page =  $this->getServicePage()->getLite($m_user->getOrganizationId());
                }
                try{
                    $this->getServiceMail()->sendTpl('tpl_postcomment', $m_user->getEmail(), [
                        'url' => "https://gnam.%s",
                        'firstname' => $m_user->getFirstname(),
                        'someone' => $m_me->getFirstname(),
                        'prefix' => ($m_page !== false && is_string($m_page->getLibelle()) && !empty($m_page->getLibelle())) ? $m_page->getLibelle() : null,
                    ]);
                    
                    $gcm_notification = new GcmNotification();
                    $gcm_notification->setTitle($m_page->getTitle())
                        ->setSound("default")
                        ->setColor("#00A38B")
                        ->setIcon("icon")
                        ->setTag("PAGECOMMENT".$t_page_id)
                        ->setBody("Someone commented on your post");
                    
                    $this->getServiceFcm()->send($m_user->getId(),null,$gcm_notification);
                }
                catch (\Exception $e) {
                    syslog(1, 'Model name does not exist post comment <MESSAGE> ' . $e->getMessage() . '  <CODE> ' . $e->getCode());
                }
            }

        }

        return $id;
    }

    /**
     * Update Post
     *
     * @invokable
     *
     * @param int    $id
     * @param string $content
     * @param string $link
     * @param string $picture
     * @param string $name_picture
     * @param string $link_title
     * @param string $link_desc
     * @param int    $lat
     * @param int    $lng
     * @param array $docs
     * @param string $data
     * @param string $event
     * @param int    $uid
     * @param array  $sub
     * @param $item_id
     *
     * @return \Application\Model\Post
     */
    public function update(
        $id = null,
        $content = null,
        $link = null,
        $picture = null,
        $name_picture = null,
        $link_title = null,
        $link_desc = null,
        $lat = null,
        $lng = null,
        $docs =null,
        $data = null,
        $event = null,
        $uid = null,
        $sub = null,
        $item_id = null
    ) {
        if ($uid === null && $id === null) {
            throw new \Exception('error update: no $id and no $uid');
        }

        $user_id = $this->getServiceUser()->getIdentity()['id'];
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        //recup id de base
        $m_post_base = ($uid !== null && $id === null) ? $this->getLite(null, $uid) : $this->getLite($id);
        $id = $m_post_base->getId();

        // check if notif
        $uid = (is_string($uid) && !empty($uid)) ? $uid:false;
        $event = (is_string($event) && !empty($event)) ? $event:false;
        $is_notif = ($uid && $event);

        // create where request
        $w = ($uid !== false) ?  ['id' => $id] : ['id' => $id, 'user_id' => $user_id];

        if (!empty($data) && !is_string($data)) {
            $data = json_encode($data);
        }

        $m_post = $this->getModel()
            ->setContent($content)
            ->setLink(($link==='')?new IsNull():$link)
            ->setPicture(($picture==='')?new IsNull():$picture)
            ->setNamePicture(($name_picture==='')?new IsNull():$name_picture)
            ->setLinkTitle(($link_title==='')?new IsNull():$link_title)
            ->setLinkDesc(($link_desc==='')?new IsNull():$link_desc)
            ->setLat($lat)
            ->setLng($lng)
            ->setItemId($item_id)
            ->setData($data)
            ->setUpdatedDate($date);

        if (null !== $docs) {
            $this->getServicePostDoc()->replace($id, $docs);
        }

        $ret = $this->getMapper()->update($m_post, $w);
        if ($ret > 0) {
            $is_private_page = (is_numeric($m_post_base->getTPageId()) && ($this->getServicePage()->getLite($m_post_base->getTPageId())->getConfidentiality() === ModelPage::CONFIDENTIALITY_PRIVATE));

            // si c pas une notification on gére les hastags
            if (!$is_notif) {
                $ar = array_filter(
                    explode(' ', str_replace(["\r\n","\n","\r"], ' ', $content)),
                    function ($v) {
                        return (strpos($v, '#') !== false) || (strpos($v, '@') !== false);
                    }
                );

                $this->getServiceHashtag()->add($ar, $id);
                $this->getServicePostSubscription()->addHashtag($ar, $id, $date, ModelPostSubscription::ACTION_UPDATE);
            }

            $pevent = [];
            // S'IL Y A UNE CIBLE A LA BASE ON NOTIFIE
            $et = $this->getTarget($m_post_base);
            if (false !== $et) {
                $pevent = array_merge($pevent, ['P'.$et]);
            }
            // if ce n'est pas un page privée
            if (!$is_private_page &&  !$is_notif) {
                $pevent = array_merge($pevent, ['P'.$this->getOwner($m_post_base)]);
            }
            if (!empty($sub)) {
                $pevent = array_merge($pevent, $sub);
            }
            $this->getServicePostSubscription()->add(
                array_unique($pevent),
                $id,
                $date,
                (!empty($event)? $event:ModelPostSubscription::ACTION_UPDATE),
                $user_id,
                null,
                $data
            );
        }

        return $ret;
    }

    /**
     * Get Post
     *
     * @invokable
     *
     * @param  int $id
     * @return \Application\Model\Post
     */
    public function get($id)
    {
        $identity = $this->getServiceUser()->getIdentity();
        $is_sadmin = $identity && (in_array(ModelRole::ROLE_ADMIN_STR, $identity['roles']));
        $res_post = $this->getMapper()->get($identity['id'], $id, $is_sadmin);
        foreach ($res_post as $m_post) {
            $m_post->setDocs($this->getServicePostDoc()->getList($m_post->getId()));
            $m_post->setSubscription($this->getServicePostSubscription()->getLastLite($m_post->getId()));

            if (is_string($m_post->getData())) {
                $m_post->setData(json_decode($m_post->getData(), true));
            }
        }

        $res_post->rewind();

        if (is_array($id)) {
            $ar_post = $res_post->toArray(['id']);
            foreach ($id as $i) {
                if (!isset($ar_post[$i])) {
                    $ar_post[$i] = null;
                }
            }
        }

        return (is_array($id) ? $ar_post: $res_post->current());
    }

    /**
     * Get List Post
     *
     * @invokable
     *
     * @param array $filter
     * @param int   $user_id
     * @param int   $page_id
     * @param int   $parent_id
     * @param bool  $is_item
     */
    public function getListId($filter = null, $user_id = null, $page_id = null, $parent_id = null, $is_item = null)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];
        $mapper = (null !== $filter) ?
            $this->getMapper()->usePaginator($filter) :
            $this->getMapper();

        $res_posts = $mapper->getListId($me, $page_id, $user_id, $parent_id, $is_item);

        return (null !== $filter) ?
            ['count' => $mapper->count(), 'list' => $res_posts]:
            $res_posts;
    }

    /**
     * Like post
     *
     * @invokable
     *
     * @param int $id
     */
    public function like($id)
    {
        $m_post = $this->getLite($id);
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        if($user_id != $m_post->getUserId()){
            $m_user = $this->getServiceUser()->getLite($m_post->getUserId());
            $m_me = $this->getServiceUser()->getLite($user_id);
            $m_page = false;
            if($m_user->getOrganizationId()){
                $m_page =  $this->getServicePage()->getLite($m_user->getOrganizationId());
            }
            try{
                $this->getServiceMail()->sendTpl('tpl_postlike', $m_user->getEmail(), [
                    'url' => "https://gnam.%s",
                    'firstname' => $m_user->getFirstname(),
                    'someone' => $m_me->getFirstname(),
                    'prefix' => ($m_page !== false && is_string($m_page->getLibelle()) && !empty($m_page->getLibelle())) ? $m_page->getLibelle() : null,
                ]);
                
                $gcm_notification = new GcmNotification();
                $gcm_notification->setTitle($m_page->getTitle())
                    ->setSound("default")
                    ->setColor("#00A38B")
                    ->setIcon("icon")
                    ->setTag("PAGECOMMENT".$t_page_id)
                    ->setBody("Someone liked your post");
                
                $this->getServiceFcm()->send($m_user->getId(),null,$gcm_notification);
            }
            catch (\Exception $e) {
                syslog(1, 'Model name does not exist <MESSAGE> ' . $e->getMessage() . '  <CODE> ' . $e->getCode());
            }
        }
        return $this->getServicePostLike()->add($id);
    }

    /**
     * UnLike Post
     *
     * @invokable
     *
     * @param int $id
     */
    public function unlike($id)
    {
        
        return $this->getServicePostLike()->delete($id);
    }

    /**
     * Delete Post
     *
     * @invokable
     *
     * @param  int $id
     * @return int
     */
    public function delete($id)
    {
        $identity = $this->getServiceUser()->getIdentity();
        $is_sadmin = (in_array(ModelRole::ROLE_ADMIN_STR, $identity['roles']));
        $m_post = $this->getModel()->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));



        $ret =  (!$is_sadmin) ?
          $this->getMapper()->update($m_post, ['id' => $id, 'user_id' => $identity['id']]) :
          $this->getMapper()->update($m_post, ['id' => $id]);

        if ($ret) {
            $this->getServiceEvent()->sendData($id, 'post.delete', ['PU'.$this->getLite($id)->getUserId()]);
        }

        return $ret;
    }

    /**
     * Reactivate Post
     *
     * @invokable
     *
     * @param  int $id
     * @return int
     */
    public function reactivate($id)
    {
        //$this->deleteSubscription($id);

        $m_post = $this->getModel()->setDeletedDate(new \Zend\Db\Sql\Predicate\IsNull());

        return $this->getMapper()->update($m_post, ['id' => $id]);
    }

    /**
     * hard Delete
     */
    public function hardDelete($uid)
    {
        return (is_string($uid) && !empty($uid)) ?  $this->getMapper()->delete($this->getModel()->setUid($uid)) : false;
    }

    /**
     * Get Post Lite
     *
     * @param  int $id
     * @param  int $uid
     * @return \Application\Model\Post
     */
    public function getLite($id = null, $uid = null, $item_id = null)
    {
        return $this->getMapper()->select($this->getModel()->setId($id)->setUid($uid)->setItemId($item_id))->current();
    }

    public function getOwner($m_post)
    {
        switch (true) {
        case (is_numeric($m_post->getPageId())):
            $u = 'P'.$m_post->getPageId();
            break;
        default:
            $u ='U'.$m_post->getUserId();
            break;
        }

        return $u;
    }

    public function getTarget($m_post)
    {
        switch (true) {
        case (is_numeric($m_post->getTPageId())):
            $t = 'P'.$m_post->getTPageId();
            break;
        case (is_numeric($m_post->getTUserId())):
            $t = 'U'.$m_post->getTUserId();
            break;
        default:
            $t = false;
            break;
        }

        return $t;
    }

    /**
     * Add Sys
     *
     * @param string $uid
     * @param string $content
     * @param string $data
     * @param string $event
     * @param string $sub
     * @param int    $parent_id
     * @param int    $t_page_id
     * @param int    $t_user_id
     *
     * @return \Application\Model\Post
     */
    public function addSys($uid, $content, $data, $event, $sub = null, $parent_id = null, $t_page_id = null, $t_user_id = null, $type = null)
    {
        if ($sub !== null && !is_array($sub)) {
            $sub = [$sub];
        }

        $res_post = $this->getMapper()->select($this->getModel()->setUid($uid));

        return ($res_post->count() > 0) ?
            $this->update(null, $content, null, null, null, null, null, null, null, null, $data, $event, $uid, $sub) :
            $this->add(
                $content,
                null,
                null,
                null,
                null,
                null,
                $parent_id,
                $t_page_id,
                $t_user_id,
                null,
                null,
                null,
                null,
                $data,
                $event,
                $uid,
                $sub,
                $type
            );
    }

    /**
     * updateSys
     *
     * @param string $uid
     * @param string $content
     * @param string $data
     * @param string $event
     * @param array  $sub
     * @return int
     */
    public function updateSys($uid, $content, $data, $event, $sub = null)
    {
        if ($sub !== null && !is_array($sub)) {
            $sub = [$sub];
        }

        return $this->update(null, $content, null, null, null, null, null, null, null, null, $data, $event, $uid, $sub);
    }

    /**
     * Get preview Crawler.
     *
     * @invokable
     *
     * @param string $url
     *
     * @return array
     */
    public function linkPreview($url)
    {
        $client = new Client();
        $client->setOptions($this->container->get('Config')['http-adapter']);

        $pc = $this->getServiceSimplePageCrawler();
        $page = $pc->setHttpClient($client)->get($url);

        $return = $page->getMeta()->toArray();
        $return['images'] = $page->getImages()->getImages();
        if (isset($return['meta'])) {
            foreach ($return['meta'] as &$v) {
                $v = html_entity_decode(html_entity_decode($v));
            }
        }
        if (isset($return['open_graph'])) {
            foreach ($return['open_graph'] as &$v) {
                $v = html_entity_decode(html_entity_decode($v));
            }
        }

        return $return;
    }

    /**
     * Get Service User
     *
     * @return \Application\Service\User
     */
    private function getServiceUser()
    {
        return $this->container->get('app_service_user');
    }

    /**
     * Get Service Post Doc
     *
     * @return \Application\Service\PostDoc
     */
    private function getServicePostDoc()
    {
        return $this->container->get('app_service_post_doc');
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

    /**
     * Get Service Post Like
     *
     * @return \Application\Service\PostLike
     */
    private function getServicePostLike()
    {
        return $this->container->get('app_service_post_like');
    }

    /**
     * Get Service Post Like
     *
     * @return \Application\Service\PostSubscription
     */
    private function getServicePostSubscription()
    {
        return $this->container->get('app_service_post_subscription');
    }

    /**
     * Get Service Post Like
     *
     * @return \Application\Service\Hashtag
     */
    private function getServiceHashtag()
    {
        return $this->container->get('app_service_hashtag');
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
     * Get Service PageCrawler.
     *
     * @return \SimplePageCrawler\PageCrawler
     */
    private function getServiceSimplePageCrawler()
    {
        return $this->container->get('SimplePageCrawler');
    }

    /**
     * Get Service Page User
     *
     * @return \Application\Service\PageUser
     */
    private function getServicePageUser()
    {
        return $this->container->get('app_service_page_user');
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
     * Get Service Service Conversation User.
     *
     * @return \Application\Service\Fcm
     */
    private function getServiceFcm()
    {
        return $this->container->get('fcm');
    }
}
