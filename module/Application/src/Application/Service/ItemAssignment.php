<?php

namespace Application\Service;

use Dal\Service\AbstractService;
use Application\Model\Item as CItem;
use \DateTime;
use \DateTimeZone;

class ItemAssignment extends AbstractService
{
	/**
	 * @invokable
	 * 
	 * @param integer $item_prog
	 * @param string $response
	 * @param array $documents
	 */
	public function add($item_prog, $response = null, $documents = null)
	{
		$m_item_assignment = $this->getModel()->setItemProgId($item_prog)->setResponse($response);

		if($this->getMapper()->insert($m_item_assignment) <= 0) {
			throw new \Exception('error insert item prog');
		}
		$m_item = $this->getServiceItem()->getByItemProg($item_prog);
		$item_assigment_id = $this->getMapper()->getLastInsertValue();
		
		if(is_array($documents)) {
			foreach ($documents as $d) {
				$type = isset($d['type']) ? $d['type'] : null;
				$title = isset($d['title']) ? $d['title'] : null;
				$author = isset($d['author']) ? $d['author'] : null;
				$link = isset($d['link']) ? $d['link'] : null;
				$source = isset($d['source']) ? $d['source'] : null;
				$token = isset($d['token']) ? $d['token'] : null;
				$date = isset($d['date']) ? $d['date'] : null;
				
				$this->getServiceItemAssignmentDocument()->add($item_assigment_id, $type, $title, $author, $link, $source, $token, $date);
			}
		}
		
		switch ($m_item->getType()) {
			case CItem::TYPE_WORKGROUP:
				$res_item_prog = $this->getServiceItemProgUser()->getListByItemProg($item_prog);
				foreach ($res_item_prog as $m_item_prog) {
					$this->getServiceItemAssignmentUser()->add(
							$m_item_prog->getUserId(),
							$item_assigment_id);
				}
			break;
			
			case (CItem::TYPE_INDIVIDUAL_ASSIGMENT || CItem::TYPE_CAPSTONE_PROJECT):
				$this->getServiceItemAssignmentUser()->add(
						$this->getServiceAuth()->getIdentity()->getId(),
						$item_assigment_id);
			break;
		}
		
		return $item_assigment_id;
	}
	
	/**
	 * @invokable
	 * 
	 * @param string $text
	 * @param integer $item_assignment
	 */
	public function addComment($text, $item_assignment)
	{
		return $this->getServiceItemAssignmentComment()->add($item_assignment, $text);
	}
	
	/**
	 * @invokable
	 *
	 * @param integer $item_assignment
	 * @param integer $score
	 */
	public function setGrade($item_assignment, $score)
	{
		$item_prog_id = $this->getMapper()->select($this->getModel()->setId($item_assignment))->current()->getItemProgId();
		
		$res_item_assignment_user = $this->getServiceItemAssignmentUser()->getByItemAssignment($item_assignment);
		foreach ($res_item_assignment_user as $m_item_assignment_user) {
			$item_prog_user_id = $this->getServiceItemProgUser()->get($item_prog_id, $m_item_assignment_user->getUserId())->current()->getId();
			$this->getServiceItemGrading()->add($item_prog_user_id, $score);
		}
		
		return true;
	}
	
	/**
	 * @invokable
	 * 
	 * @param integer $item_assignment
	 * @return integer
	 */
	public function submit($id)
	{
		return $this->getMapper()->update($this->getModel()->setId($id)->setSubmitDate((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s')));
	}
	
	public function deleteByItemProg($item_prog)
	{
		$res_item_assignment = $this->getMapper()->select($this->getModel()->setItemProgId($item_prog));
		
		foreach ($res_item_assignment as $m_item_assignment) {
			$this->getServiceItemAssignmentDocument()->deleteByItemAssignment($m_item_assignment->getId());
			$this->getServiceItemAssignmentComment()->deleteByItemAssignment($m_item_assignment->getId());
			$this->getServiceItemAssignmentUser()->deleteByItemAssignment($m_item_assignment->getId());
		}
		
		return $this->getMapper()->delete($this->getModel()->setItemProgId($item_prog));
	}
	
	/**
	 * @return \Application\Service\ItemAssigmentDocument
	 */
	public function getServiceItemAssignmentDocument()
	{
		return $this->getServiceLocator()->get('app_service_item_assigment_document');
	}
	
	/**
	 * @return \Application\Service\Item
	 */
	public function getServiceItem()
	{
		return $this->getServiceLocator()->get('app_service_item');
	}
	
	/**
	 * @return \Application\Service\ItemGrading
	 */
	public function getServiceItemGrading()
	{
		return $this->getServiceLocator()->get('app_service_item_grading');
	}
	
	/**
	 * @return \Application\Service\ItemProgUser
	 */
	public function getServiceItemProgUser()
	{
		return $this->getServiceLocator()->get('app_service_item_prog_user');
	}
	
	/**
	 * @return \Application\Service\ItemAssignmentComment
	 */
	public function getServiceItemAssignmentComment()
	{
		return $this->getServiceLocator()->get('app_service_item_assignment_comment');
	}
	
	/**
	 * @return \Application\Service\ItemAssignmentUser
	 */
	public function getServiceItemAssignmentUser()
	{
		return $this->getServiceLocator()->get('app_service_item_assignment_user');
	}
	
	/**
	 * @return \Zend\Authentication\AuthenticationService
	 */
	public function getServiceAuth()
	{
		return $this->getServiceLocator()->get('auth.service');
	}
}