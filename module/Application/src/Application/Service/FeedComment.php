<?php
namespace Application\Service;

use Dal\Service\AbstractService;
use Zend\Db\Sql\Predicate\IsNull;

class FeedComment extends AbstractService
{

    public function add($content, $feed_id)
    {
        $user = $this->getServiceUser()->getIdentity()['id'];
        
        $m_feed = $this->getModel()
            ->setContent($content)
            ->setUserId($user)
            ->setFeedId($feed_id)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
        
        if ($this->getMapper()->insert($m_feed) <= 0) {
            new \Exception('error insert feed comment');
        }
        
        return $this->getMapper()->getLastInsertValue();
    }

    public function delete($id)
    {
        $user = $this->getServiceUser()->getIdentity()['id'];
        
        $m_feed_comment = $this->getModel()->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
        
        return $this->getMapper()->update($m_feed_comment, array('user_id' => $user, 'id' => $id));
    }

    public function getList($id)
    {
        return $this->getMapper()->getList($id);
    }
    
    /**
     *
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->serviceLocator->get('app_service_user');
    }
}
