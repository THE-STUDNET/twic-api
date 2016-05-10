<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use Zend\Db\Sql\Predicate\IsNull;

class Library extends AbstractService
{
	/**
	 * @invokable
	 * 
	 * @param string $name
	 * @param string $link
	 * @param string $token
	 * @param string $type
	 * @param integer $folder_id
	 * @throws \Exception
	 * 
	 * @return \Application\Model\Library
	 */
	public function add($name, $link = null, $token = null, $type = null, $folder_id = null)
	{
	    $urldms = $this->getServiceLocator()->get('config')['app-conf']['urldms'];
	    
	    $box_id = null;
	    $u = (null !== $link)?$link:$urldms.$token;
	    $m_box = $this->getServiceBox()->addFile($u, $type);
	    
	    if($m_box instanceof Document) {
	        $box_id = $m_box->getId();
	    }
	    
		$m_library = $this->getModel()
			->setName($name)
			->setLink($link)
			->setToken($token)
			->setBoxId($box_id)
			->setFolderId($folder_id)
			->setType($type)
			->setOwnerId($this->getServiceUser()->getIdentity()['id'])
			->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));

		if($this->getMapper()->insert($m_library) < 0) {
			throw new \Exception('Error insert file');
		}
		
		$id = $this->getMapper()->getLastInsertValue();
		
		return $this->get($id);
	}
	
	/**
	 * @invokable
	 *
	 * @param integer $id
	 * @param string $name
	 * @param string $link
	 * @param string $token
	 * @param integer $folder_id
	 * 
	 * @return \Application\Model\Library
	 */
	public function update($id, $name = null, $link = null, $token = null, $folder_id = null)
	{
	    if($folder_id === $id) {
	        return 0;
	    }
	    
		$m_library = $this->getModel()
			->setId($id)
			->setName($name)
			->setLink($link)
			->setToken($token)
			->setFolderId(($folder_id === 0)? new IsNull():$folder_id)
			->setUpdatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
	
	 	$this->getMapper()->update($m_library);
	 	
	 	return $this->get($id);
	}
	
	/**
	 * @invokable
	 * 
	 * @param integer $folder_id
	 */
	public function getList($folder_id = null)
	{
		$m_library = $this->getModel()
		    ->setFolderId(($folder_id == null)? new IsNull():$folder_id)
		    ->setDeletedDate(new IsNull())
		    ->setOwnerId($this->getServiceUser()->getIdentity()['id']);
		
		// If root folder: returns only documents
		if (!$folder_id) {
			return ['documents' => $this->getMapper()->select($m_library)];
		}
		
		// Requested document / folder
		$m_folder = $this->getModel()->setId($folder_id);
		$folder = $this->getMapper()->select($m_folder)->current();

		// Parent folder
		if (!$folder->getFolderId() instanceof \Zend\Db\Sql\Predicate\IsNull) {
			$m_parent = $this->getModel()->setId($folder->getFolderId());
			$parent = $this->getMapper()->select($m_parent)->current();
		} else {
			$parent = null;
		}

		return [
			'documents' => $this->getMapper()->select($m_library),
			'folder' => $folder,
			'parent' => $parent,
		];
	}
	
	/**
	 * @invokable
	 * 
	 * @param integer $item
	 */
	public function getListByItem($item)
	{
	    return $this->getMapper()->getListByItem($item);
	}
        
	/**
	 * @invokable
	 * 
	 * @param integer $item
	 */
	public function getListByParentItem($item)
	{
	    return $this->getMapper()->getListByParentItem($item);
	}
	
	public function getListByBankQuestion($bank_question_id)
	{
	    return $this->getMapper()->getListByBankQuestion($bank_question_id);
	}
	
	/**
	 * @invokable
	 *
	 * @param integer $submission_id
	 */
	public function getListBySubmission($submission_id)
	{
	    return $this->getMapper()->getListBySubmission($submission_id);
	}
	
	/**
	 * @param integer $item_id
	 * @return \Application\Model\Library
	 * 
	 */
	public function getByItem($item_id)
	{
	    $res_library = $this->getMapper()->getListByItem($item);
	    
	    return ($res_library->count() > 0) ?  $res_library->current() : null;
	}
	
	/**
	 * @invokable
	 *
	 * @param integer $item
	 */
	public function getListByCt($item)
	{
	    return $this->getMapper()->getListByCt($item);
	}
	
	/**
	 * @invokable
	 *
	 * @param integer $id
	 */
	public function delete($id)
	{
		$m_library = $this->getModel()
			->setId($id)
			->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
	 	return $this->getMapper()->update($m_library);
	}
	
	/**
	 * @invokable
	 * 
	 * @param integer $id
	 * 
	 * @return \Application\Model\Library
	 */
	public function get($id)
	{
		return $this->getMapper()->select($this->getModel()->setId($id))->current();
	}
	
	/**
	 * @invokable
	 *
	 * @param integer $id
	 * @param integer $box_id
	 */
	public function getSession($id = null, $box_id = null)
	{
	    if(null === $id && null === $box_id) {
	        return;
	    }
	    
	    if(null !== $id) {
	       $res_library = $this->getMapper()->select($this->getModel()->setId($id));
	       
	       if($res_library->count() <= 0) {
	           throw new \Exception();
	       }
	       $m_library = $res_library->current();
	       $box_id=$m_library->getBoxId();
	    }
	    
	    return $this->getServiceBox()->createSession($box_id);
	}
	
	/**
	 * @return \Application\Service\User
	 */
	public function getServiceUser()
	{
		return $this->getServiceLocator()->get('app_service_user');
	}
	
	/**
	 * @return \Box\Service\Api
	 */
	public function getServiceBox()
	{
	    return $this->getServiceLocator()->get('box.service');
	}
	
}
