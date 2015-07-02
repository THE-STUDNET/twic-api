<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class MailReceiver extends AbstractService
{
	public function add($mail, $receiver)
	{
		foreach ($receiver as $mr) {
			$this->_add($mr['type'], $mr['user'], $mail);
		}
	
		return true;
	}
	
	/**
	 * Add receiver.
	 *
	 * @param string $type
	 * @param integer    $user_id
	 * @param integer    $mail
	 *
	 * @return int
	 */
	public function _add($type, $user_id, $mail)
	{
		if ($this->getMapper()->insert($this->getModel()->setType($type)
				->setMailId($mail)
				->setUserId($user_id)) <= 0) {
					throw new \Exception('error insert receiver');
				}
	
				return $this->getMapper()->getLastInsertValue();
	}
	
	public function delete($receiver, $mail)
	{
		return $this->getMapper()->delete($this->getModel()->setMailId($mail)->setId($receiver));
	}
	
	public function replace($receiver, $mail)
	{
		$this->getMapper()->delete($this->getModel()->setMailId($mail));
		$this->add($mail, $receiver);
	
		return true;
	}
	
	public function getByMail($mail)
	{
		return $this->getMapper()->select($this->getModel()->setMailId($mail));
	}
}