<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class MailUser extends AbstractService
{
	/**
	 * 
	 * @param integer $mail_id
	 * @param integer $user_id
	 * @param integer $mail_group_id
	 * @throws \Exception
	 * @return integer
	 */
	public function add($mail_id, $user_id, $mail_group_id)
	{
		$m_mail_user = $this->getModel()->setMailId($mail_id)
										  ->setUserId($user_id)
										  ->setMailGroupId($mail_group_id)
										  ->setCreatedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
	
		if ($this->getMapper()->insert($m_mail_user) <= 0) {
			throw new \Exception('error insert message user');
		}
	
		return $this->getMapper()->getLastInsertValue();
	}
	
	/**
	 * Delete mail.
	 *
	 * @param integer $user
	 * @param integer $id
	 *
	 * @return integer
	 */
	public function delete($user, $id)
	{
		$m_mail_user = $this->getModel()->setDeletedDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
	
		return $this->getMapper()->update($m_mail_user, array('user_id' => $user, 'mail_id' => $id));
	}
}