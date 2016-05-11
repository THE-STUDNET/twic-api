<?php

namespace Application\Mapper;

use Dal\Mapper\AbstractMapper;
use Zend\Db\Sql\Expression;
use Dal\Db\Sql\Select;
use Application\Model\Role as ModelRole;
use Zend\Db\Sql\Predicate\Predicate;

class Submission extends AbstractMapper
{
    
    
      /**
     * 
     * @param integer $id
     * 
     * @return \Application\Model\Submission
     */
    public function checkGraded($id)
    {
        
        
        $select = new Select('submission_user');
        $select->columns(['has_graded' => new Expression('SUM(IF(submission_user.grade IS NULL,1,0)) = 0')])
               ->where(['submission_user.submission_id' => $id])
               ->group('submission_user.submission_id');
        
        
        $update = $this->tableGateway->getSql()->update();
        $update->set(['is_graded' => $select])
               ->where(['id'=>$id]);
        return $this->updateWith($update);
    }
    
   
    
    /**
     * 
     * @param integer $user
     * @param integer $questionnaire
     * 
     * @return \Application\Model\Submission
     */
    public function getByUserAndQuestionnaire($user, $questionnaire)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array('id'))
            ->join('questionnaire', 'questionnaire.item_id=submission.item_id', array())
            ->join('submission_user', 'submission_user.submission_id=submission.id', array())
            ->where(array('submission_user.user_id' => $user))
            ->where(array('questionnaire.id' => $questionnaire));
    
        return $this->selectWith($select);
    }
    
    public function getListRecord($item, $user, $is_student = false)
    {
        $select = $this->tableGateway->getSql()->select();
    
        $select->columns(array('id'))
        ->join('videoconf', 'submission.id=videoconf.submission_id', array(), $select::JOIN_INNER)
        ->join('videoconf_archive', 'videoconf.id=videoconf_archive.videoconf_id', array(), $select::JOIN_INNER)
        ->where(array('videoconf_archive.archive_link IS NOT NULL'))
        ->where(array('submission.item_id' => $item));
    
        if ($is_student !== false) {
            $select->join('submission_user', 'submission.id=submission_user.submission_id', array(), $select::JOIN_INNER)
                ->where(array('submission_user.user_id' => $user));
        }
    
        return $this->selectWith($select);
    }
    
    /**
     * 
     * @param integer $item_id
     * @param integer $user_id
     * @param integer $submission_id
     * @param integer $group_id
     * 
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function get($item_id = null, $user_id = null, $submission_id = null, $group_id = null)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array('id', 'item_id', 'submission$submit_date' => new Expression('DATE_FORMAT(submission.submit_date, "%Y-%m-%dT%TZ")')))
            ->join('submission_user', 'submission_user.submission_id=submission.id', array());
            
            if(null !== $submission_id) {
                $select->where(array('submission.id' => $submission_id));
            } else {
                if(null !== $group_id && null !== $item_id) {
                    $select->where(array('submission.group_id' => $group_id))
                        ->where(array('submission.item_id' => $item_id));
                } elseif(null !== $user_id && null !== $item_id) {
                    $select->where(array('submission_user.user_id' => $user_id))
                        ->where(array('submission.item_id' => $item_id));
                } elseif(null !== $item_id) {
                    $select->where(array('submission.item_id' => $item_id));
                }
            }

        return $this->selectWith($select);
    }
    
    public function getList($item_id, $user_id)
    {
        $sql = 'SELECT  
                    `submission`.`submit_date` AS `submission$submit_date`,
                     `submission`.`id` AS `submission$id`,
                     `group`.`id` AS `submission$group_id`,
                     `group`.`name` AS `submission$group_name`,
                     `submission`.`id` AS `submission$id`,
                     `course_user_relation`.`user_id` as `submission_user$user_id`,
                     `submission_user`.`grade` as `submission_user$grade`,
                     `submission_user`.`submit_date` as `submission_user$submit_date`,
                     `user`.`firstname` as `user$firstname`,
                     `user`.`lastname` as `user$lastname`,
                     `user`.`avatar` as `user$avatar`,
                     `user`.`id` as `user$id`
                FROM
                    `item` 
                        LEFT JOIN
                    `ct_group` ON `ct_group`.`item_id` = `item`.`id`
                        LEFT JOIN
                    `group_user` ON `group_user`.`group_id` = `ct_group`.`group_id`
                        LEFT JOIN
                    `course_user_relation` ON `item`.`course_id` = `course_user_relation`.`course_id`
                        AND `item`.`set_id` IS NULL
                        AND ((`group_user`.`user_id` = `course_user_relation`.`user_id` AND `ct_group`.`item_id` IS NOT NULL) OR `ct_group`.`item_id` IS NULL)
                        AND `course_user_relation`.`user_id` IN (SELECT `user_id` FROM `user_role` WHERE `role_id`='.ModelRole::ROLE_STUDENT_ID.' )
                        LEFT JOIN
                    `user_role` ON `user_role`.`user_id`=`course_user_relation`.`user_id`
                        LEFT JOIN
                    `set_group` ON `item`.`set_id` = `set_group`.`set_id`
                        AND ((`ct_group`.`group_id` = `set_group`.`group_id`)
                        OR `ct_group`.`item_id` IS NULL)
                        LEFT JOIN
                    `group` ON `group`.`id` = `set_group`.`group_id`
                         LEFT JOIN 
                    `submission_user` ON `submission_user`.`user_id` = `course_user_relation`.`user_id`
                        AND `submission_user`.`submission_id` IN (SELECT `id` FROM `submission` WHERE `submission`.`item_id`=:item )
                        LEFT JOIN
                    `submission` ON `submission`.`item_id`=`item`.`id` AND (`submission`.`id` = `submission_user`.`submission_id`)
                        OR (`submission`.`group_id` = `set_group`.`group_id`) 
						LEFT JOIN 
					`user` ON `course_user_relation`.`user_id`=`user`.`id`
                        LEFT JOIN
	                `submission_comments` ON `submission_comments`.`submission_id`=`submission`.`id`
                WHERE item.id = :item2   
                GROUP BY `submission`.`id`, `submission_comments`.`submission_id`, `group`.`id`, `course_user_relation`.`user_id`';
        
        return $this->selectPdo($sql,[':item' => $item_id, ':item2' => $item_id]);
    }
    
    /**
     * @param integer $user
     * @return \Zend\Db\Sql\Select
     */
    private function getSelectContactState($user)
    {
        $select = new Select('user');
        $select->columns(array('user$contact_state' =>  new Expression(
            'IF(contact.accepted_date IS NOT NULL, 3,
	         IF(contact.request_date IS NOT  NULL AND contact.requested <> 1, 2,
		     IF(contact.request_date IS NOT  NULL AND contact.requested = 1, 1,0)))')))
    		     ->join('contact', 'contact.contact_id = user.id', array())
    		     ->where(array('user.id=`user$id`'))
    		     ->where(['contact.user_id' => $user ]);
    
    		     return $select;
    }
}