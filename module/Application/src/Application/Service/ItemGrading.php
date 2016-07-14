<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Item Grading
 *
 */

namespace Application\Service;

use Dal\Service\AbstractService;
use DateTime;
use DateTimeZone;

/**
 * Class ItemGrading
 */
class ItemGrading extends AbstractService
{
    public function _getList()
    {
        return $this->getMapper()->getList();
    }

    public function deleteByItemProgUser($submission_user)
    {
        return $this->getMapper()->delete($this->getModel()->setItemProgUserId($submission_user));
    }

    public function add($submission_user, $grade)
    {
        $res_item_grading = $this->getMapper()->select($this->getModel()->setItemProgUserId($submission_user));
        if ($res_item_grading->count() > 0) {
            return $this->_update($res_item_grading->current()->getId(), $grade);
        } else {
            return $this->_add($submission_user, $grade);
        }
    }

    public function _add($submission_user, $grade)
    {
        return $this->getMapper()->insert($this->getModel()
                ->setItemProgUserId($submission_user)
                ->setGrade($grade)
                ->setCreatedDate((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
                );
    }

    public function _update($id, $grade)
    {
        return $this->getMapper()->update($this->getModel()
                ->setId($id)
                ->setGrade($grade)
                ->setCreatedDate((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
        );
    }
}
