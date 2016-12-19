<?php
/**
 *
 * TheStudnet (http://thestudnet.com)
 *
 * PageTag
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class PageTag
 */
class PageTag extends AbstractService
{
    /**
     * Add Page Tag
     *
     * @param int $page_id
     * @param string $tag
     * @return int
     */
    public function add($page_id, $tag)
    {
        $m_page_tag = $this->getModel()
            ->setPageId($page_id)
            ->setTagId($this->getServiceTag()->add($tag));
        
        return $this->getMapper()->insert($m_page_tag);
    }
    
    /**
     * Add Array
     *
     * @param int $page_id
     * @param array $data
     * @return array
     */
    public function _add($page_id, $data)
    {
        $ret = [];
        foreach ($data as $tag) {
            $ret = $this->add($page_id, $tag);
        }
    
        return $ret;
    }
    
    /**
     * Replacec Array
     *
     * @param int $page_id
     * @param array $data
     * @return array
     */
    public function replace($page_id, $data)
    {
        $this->getMapper()->delete($this->getModel()->setPageId($page_id));
        
        return  $this->_add($page_id, $data);
    }
    
    /**
     * Get List
     *
     * @param int $page_id
     */
    public function getList($page_id)
    {
        return $this->getServiceTag()->getListByPage($page_id);
    }
    
    /**
     *
     * @return \Application\Service\Tag
     */
    private function getServiceTag()
    {
        return $this->container->get('app_service_tag');
    }
}
