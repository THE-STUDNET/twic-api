<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Post
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;

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
     * @param int $parent_id
     * @param int $t_page_id
     * @param int $t_organization_id
     * @param int $t_user_id
     * @param int $t_course_id
     * @param int $page_id
     * @param int $organization_id
     * @param int $lat
     * @param int $lng
     * @param array $docs
     */
    public function add($content, $picture = null,  $name_picture = null, $link = null, $link_title = null,  $link_desc = null, $parent_id = null,  $t_page_id = null,  $t_organization_id = null,  $t_user_id = null,  $t_course_id = null, $page_id = null, $organization_id = null, $lat =null, $lng = null ,$docs = null)
    {
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        
        $origin_id = null;
        if (null !== $parent_id) {
            $m_post = $this->getMapper()->select($this->getModel()->setId($parent_id))->current();
            $origin_id = (null !== $m_post->getOriginId()) ?
                $m_post->getOriginId():
                $m_post->getId();
        }
        
        if(null === $parent_id && null === $t_course_id && null === $t_organization_id && null === $t_page_id && null === $t_user_id) {
            $t_user_id = $user_id;
        }
        
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $m_post = $this->getModel()
            ->setContent($content)
            ->setPicture($picture)
            ->setNamePicture($name_picture)
            ->setUserId($user_id)
            ->setLink($link)
            ->setLinkTitle($link_title)
            ->setLinkDesc($link_desc)
            ->setCreatedDate($date)
            ->setOrganizationId($organization_id)
            ->setPageId($page_id)
            ->setLat($lat)
            ->setLng($lng)
            ->setParentId($parent_id)
            ->setOriginId($origin_id)
            ->setTPageId($t_page_id)
            ->setTOrganizationId($t_organization_id)
            ->setTUserId($t_user_id)
            ->setTCourseId($t_course_id);
       
        if($this->getMapper()->insert($m_post) <= 0) {
            throw new \Exception('error add post');
        }
        
        
        
        $id = $this->getMapper()->getLastInsertValue();
        $ar = array_filter(explode(' ', str_replace(array("\r\n","\n","\r"), ' ', $content)), function ($v) {
            return (strpos($v, '#') !== false) || (strpos($v, '@') !== false);
        });
        $this->getServiceHashtag()->add($ar, $id);
        $this->getServicePostSubscription()->addHashtag($ar, $id, $date);
        
        $this->getServicePostSubscription()->addOrUpdatePost($id, $date);
        
        if(null !== $docs) {
            $this->getServicePostDoc()->_add($id, $docs);
        }
        
        return $this->get($id);
    }
        
    /**
     * Update Post
     * 
     * @invokable
     * 
     * @param int $id
     * @param string $content
     * @param string $link
     * @param string $picture
     * @param string $name_picture
     * @param string $link_title
     * @param string $link_desc
     * @param int $lat
     * @param int $lng
     * @param arrray $docs
     * @return int
     */
    public function update($id, $content = null, $link = null, $picture = null, $name_picture = null, $link_title = null, $link_desc = null, $lat = null, $lng = null, $docs =null)
    {
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $m_post = $this->getModel()
            ->setContent($content)
            ->setLink($link)
            ->setPicture($picture)
            ->setNamePicture($name_picture)
            ->setLinkTitle($link_title)
            ->setLinkDesc($link_desc)
            ->setLat($lat)
            ->setLng($lng)
            ->setUpdatedDate($date);
        
        if(null !== $docs) {
            $this->getServicePostDoc()->replace($id, $docs);
        }
        
        $ar = array_filter(explode(' ', str_replace(["\r\n","\n","\r"], ' ', $content)), function ($v) {
            return (strpos($v, '#') !== false) || (strpos($v, '@') !== false);
        });
        $this->getServiceHashtag()->add($ar, $id);
        $this->getServicePostSubscription()->addHashtag($ar, $id, $date);
        
        $this->getServicePostSubscription()->addOrUpdatePost($id, $date);
        
        $this->getMapper()->update($m_post, ['id' => $id, 'user_id' => $this->getServiceUser()->getIdentity()['id']]);
        
        return $this->get($id);
    }
    
    /**
     * Delete Post
     * 
     * @invokable
     * 
     * @param int $id
     * @return int
     */
    public function delete($id)
    {
        //$this->deleteSubscription($id);
        
        $m_post = $this->getModel()
            ->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
    
        return $this->getMapper()->update($m_post, ['id' => $id, 'user_id' => $this->getServiceUser()->getIdentity()['id']]);
    }
    
    /**
     * Get Post
     * 
     * @invokable
     * 
     * @param int $id
     */
    public function get($id) 
    {
        $m_post =  $this->getMapper()->select($this->getModel()->setId($id))->current();
        $m_post->setComments($this->getMapper()->getList(null, null, null, null, null, $m_post->getId()));
        $m_post->setDocs($this->getServicePostDoc()->getList($id));
        
        return $m_post;
    }
    
    /**
     * Get List Post
     * 
     * @invokable
     */
    public function getList($user_id = null, $page_id = null, $organization_id = null, $course_id = null, $parent_id = null)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];
        $res_posts = $this->getMapper()->getList($me, $page_id, $organization_id, $user_id, $course_id, $parent_id);
        if(null === $parent_id){
            foreach ($res_posts as $m_post) {
                $m_post->setComments($this->getMapper()->getList($me, null, null, null, null, $m_post->getId()));
                $m_post->setDocs($this->getServicePostDoc()->getList($m_post->getId()));
            }            
        }
        
        return $res_posts;
    }
    
    /**
     * Get Post Lite
     * 
     * @param int $id
     * @return \Application\Model\Post
     */
    public function getLite($id)
    {
        return $this->getMapper()->select($this->getModel()->setId($id))->current();
    }
    
    /**
     * Like post 
     * 
     * @invokable
     * 
     * @param int $post_id
     */
    public function like($id)
    {
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
        $this->getServicePostLike()->delete($id);
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
    
}