<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use Zend\Http\Client;

class Feed extends AbstractService
{
    /**
     * Add feed.
     *
     * @invokable
     * 
     * @param string $content
     * @param string $link
     * @param string $video
     * @param string $picture
     * @param string $document
     * @param string $name_picture
     * @param string $name_document
     * @param string $link_desc
     * @param string $link_title
     * 
     * @return int
     */
    public function add($content = null, $link = null, $video = null, $picture = null, $document = null, $name_picture = null, $name_document = null, $link_desc = null, $link_title = null)
    {
        $user = $this->getServiceUser()->getIdentity()['id'];

        $m_feed = $this->getModel()
            ->setContent($content)
            ->setUserId($user)
            ->setLink($link)
            ->setVideo($video)
            ->setPicture($picture)
            ->setLinkTitle($link_title)
            ->setLinkDesc($link_desc)
            ->setDocument($document)
            ->setNamePicture($name_picture)
            ->setNameDocument($name_document)
            ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        if ($this->getMapper()->insert($m_feed) <= 0) {
            new \Exception('error insert feed');
        }

        $feed_id = $this->getMapper()->getLastInsertValue();

        $this->getServiceEvent()->userPublication($feed_id);

        return $feed_id;
    }

    /**
     * Update feed.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $content
     * @param string $link
     * @param string $video
     * @param string $picture
     * @param string $document
     * @param string $name_picture
     * @param string $name_document
     * @param string $link_desc
     * @param string $link_title
     * 
     * @return int
     */
    public function update($id, $content = null, $link = null, $video = null, $picture = null, $document = null, $name_picture = null, $name_document = null, $link_desc = null, $link_title = null)
    {
        $user = $this->getServiceUser()->getIdentity()['id'];

        $m_feed = $this->getModel()
            ->setContent($content)
            ->setLink($link)
            ->setVideo($video)
            ->setPicture($picture)
            ->setLinkTitle($link_title)
            ->setLinkDesc($link_desc)
            ->setNamePicture($name_picture)
            ->setNameDocument($name_document)
            ->setDocument($document);

        return $this->getMapper()->update($m_feed, array('user_id' => $user, 'id' => $id));
    }

    /**
     * Delete Feed.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function delete($id)
    {
        $user = $this->getServiceUser()->getIdentity()['id'];

        $m_feed = $this->getModel()->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

        return $this->getMapper()->update($m_feed, array('user_id' => $user, 'id' => $id));
    }

    /**
     * Add Comment Feed.
     *
     * @invokable
     *
     * @param int    $id
     * @param string $content
     *
     * @return int
     */
    public function addComment($id, $content)
    {
        return $this->getServiceFeedComment()->add($content, $id);
    }

    /**
     * Delete Comment Feed.
     *
     * @invokable
     *
     * @param int $id
     *
     * @return int
     */
    public function deleteComment($id)
    {
        return $this->getServiceFeedComment()->delete($id);
    }

    /**
     * Get List Comment Feed.
     *
     * @invokable
     *
     * @param int $id
     */
    public function GetListComment($id)
    {
        return $this->getServiceFeedComment()->getList($id);
    }

    /**
     * GetList Feed.
     *
     * @invokable
     * 
     * @param string $filter
     * @param string $ids
     * @param int    $user
     */
    public function getList($filter = null, $ids = null, $user = null)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];
        $res_contact = $this->getServiceContact()->getList();

        $mapper = $this->getMapper();
        if (null === $user) {
            $user = [$me];
            foreach ($res_contact as $m_contact) {
                $user[] = $m_contact->getContact()['id'];
            }
        }

        //$mapper = $mapper->usePaginator($filter);

        return $mapper->getList($user, $me, $ids); //array('list' => $mapper->getList($user,$me, $ids), 'count' => $mapper->count());
    }

    /**
     * @param int $id
     *
     * @return \Application\Model\Feed
     */
    public function get($id)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];

        return $this->getMapper()->getList(null, $me, $id)->current();
    }

    /**
     * @invokable 
     * 
     * @param string $url
     */
    public function linkPreview($url)
    {
        $sm = $this->getServiceLocator();
        $client = new Client();
        $client->setOptions($sm->get('Config')['http-adapter']);

        $pc = $this->getServiceSimplePageCrawler();
        $page = $pc->setHttpClient($client)->get($url);
        $return = $page->getMeta()->toArray();
        $return['images'] = $page->getImages()->getImages();
        
        return $return;
    }

    /**
     * @return \Application\Service\FeedComment
     */
    public function getServiceFeedComment()
    {
        return $this->serviceLocator->get('app_service_feed_comment');
    }

    /**
     * @return \Application\Service\Contact
     */
    public function getServiceContact()
    {
        return $this->serviceLocator->get('app_service_contact');
    }

    /**
     * @return \Application\Service\User
     */
    public function getServiceUser()
    {
        return $this->serviceLocator->get('app_service_user');
    }

    /**
     * @return \SimplePageCrawler\PageCrawler
     */
    public function getServiceSimplePageCrawler()
    {
        return $this->serviceLocator->get('SimplePageCrawler');
    }

    /**
     * @return \Application\Service\Event
     */
    public function getServiceEvent()
    {
        return $this->serviceLocator->get('app_service_event');
    }
}
