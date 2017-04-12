<?php
namespace Application\Service;

use Dal\Service\AbstractService;

class PageDoc extends AbstractService
{
    /**
     * Add Page Document Relation
     *
     * @param  int       $page_id
     * @param  int|array $library
     * @return int
     */
    public function add($page_id, $library)
    {
        if (is_array($library)) {
            $library = $this->getServiceLibrary()->_add($library)->getId();
        } elseif (!is_numeric($var)) {
            throw new \Exception('error add document');
        }

        $m_page_doc = $this->getModel()
            ->setPageId($page_id)
            ->setLibraryId($library);

        $this->getMapper()->insert($m_page_doc);

        return $library;
    }


    /**
     * Delete Doc
     *
     * @param int $library_id
     **/
    public function delete($library_id)
    {
        $this->getMapper()->delete($this->getModel()->setLibraryId($library_id));

        return $this->getServiceLibrary()->delete($library_id);
    }

    /**
     * Add Array
     *
     * @param  int   $page_id
     * @param  array $data
     * @return array
     */
    public function _add($page_id, $data)
    {
        $ret = [];
        foreach ($data as $d) {
            $ret[] = $this->add($page_id, $d);
        }

        return $ret;
    }

    /**
     * Replace Array
     *
     * @param  int   $page_id
     * @param  array $data
     * @return array
     */
    public function replace($page_id, $data)
    {
        $this->getMapper()->delete($this->getModel()->setPageId($page_id));

        return $this->_add($page_id, $data);
    }

    /**
     * Get Service Library
     *
     * @return \Application\Service\Library
     */
    private function getServiceLibrary()
    {
        return $this->container->get('app_service_library');
    }

    /**
     * Get Service Page User
     *
     * @return \Application\Service\User
     */
    private function getServiceUser()
    {
        return $this->container->get('app_service_user');
    }
}
