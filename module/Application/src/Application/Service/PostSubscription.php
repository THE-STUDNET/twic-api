<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Post Subscription
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;
use Application\Model\PostSubscription as ModelPostSubscription;

/**
 * Class PostSubscription
 */
class PostSubscription extends AbstractService
{
    /**
     * Add Post Subscription
     * 
     * @param string $libelle
     * @param int $post_id
     * @param string $last_date
     * @param string $action
     * @param int $user_id
     * @param int $sub_post_id
     * @param mixed $data
     * @return bool
     */
    public function add($libelle, $post_id, $last_date, $action, $user_id, $sub_post_id =null, $data = null)
    {
        if(!is_array($libelle)) {
            $libelle = [$libelle];
        }
        
        if(!empty($data) && !is_string($data)) {
            $data = json_encode($data);
        }
        
        $m_post_subscription = $this->getModel()
            ->setPostId($post_id)
            ->setAction($action)
            ->setUserId($user_id)
            ->setSubPostId($sub_post_id)
            ->setData($data)
            ->setLastDate($last_date);
        
        foreach ($libelle as $l) { 
            $m_post_subscription->setLibelle($l);
            $this->getMapper()->insert($m_post_subscription);
        }
        
        return true;
    }
    
    /**
     * @param string $libelle
     * @param int $post_id
     * @return int
     */
    public function delete($libelle, $post_id)
    {
        $m_post_subscription = $this->getModel()
            ->setLibelle($libelle)
            ->setPostId($post_id);
    
        return $this->getMapper()->delete($m_post_subscription);
    }
    
    public function addHashtag($ar, $id, $date)
    {
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        foreach ($ar as $n) {
            if(substr($n,0,1) === '@') {
                $tab = json_decode(str_replace("'", "\"", substr($n, 1)),true);
                // remonte le post des abonner a la personne tagé
                $this->add('U'.$tab[0].$tab[1], $id, $date, ModelPostSubscription::ACTION_TAG, $user_id);
            }
        }
    }
   
    /**
     * 
     * @param int $post_id
     */
    public function getLast($post_id)
    {
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        
        return $this->getMapper()->getLast($post_id, $user_id)->current();
    }
    
    /**
     *
     * @param int $post_id
     */
    public function getListLibelle($post_id)
    {
        $res_post_subscription = $this->getMapper()->getListLibelle($post_id);
        
        $lib = [];
        foreach ($res_post_subscription as $m_post_subscription) {
            $lib[] = $m_post_subscription->getLibelle();
        }
        
        return array_unique($lib);
    }
    
    /**
     *
     * @param int $post_id
     */
    public function getLastLite($post_id)
    {
        $user_id = $this->getServiceUser()->getIdentity()['id'];
    
        return $this->getMapper()->getLastLite($post_id, $user_id)->current();
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
     * Get Service Post Like
     *
     * @return \Application\Service\PostLike
     */
    private function getServicePostLike()
    {
        return $this->container->get('app_service_post_like');
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
    
}