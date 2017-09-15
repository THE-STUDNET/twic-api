<?php

namespace Application\Mapper;

use Dal\Mapper\AbstractMapper;
use Zend\Db\Sql\Expression;

class Item extends AbstractMapper
{
    public function getListId($page_id, $me, $is_admin_page, $parent_id = null)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(['id', 'parent_id', 'page_id'])
      ->join('page_user', 'page_user.page_id=item.page_id', [])
      ->join('item_user', 'item_user.item_id=item.id', [], $select::JOIN_LEFT)
      ->where(['page_user.user_id' => $me])
      ->where(['item.page_id' => $page_id])
      ->order('item.page_id ASC')
      ->order('item.order ASC')
      ->quantifier('DISTINCT');

        if (null !== $parent_id) {
            $select->where(['item.parent_id' => $parent_id]);
        } else {
            $select->where(['item.parent_id IS NULL']);
        }

        if ($is_admin_page !== true) {
            $select->where(['item.is_published IS TRUE']);
            $select->where(["( item.participants = 'all' || item_user.user_id = ? )" => $me]);
        }

        return $this->selectWith($select);
    }

    public function getListTimeline($me)
    {
        $select1 = $this->tableGateway->getSql()->select();
        $select1->columns(['id', 'parent_id', 'page_id', 'item$timeline_type' => new Expression(" 'S' "), 'item$order_date' => new Expression("item.start_date")])
        ->join('page_user', 'page_user.page_id=item.page_id', [])
        ->join('page', 'page.id=item.page_id', [])
        ->where(['page_user.user_id' => $me])
        ->where(['page_user.state' => 'member'])
        ->where(["( `item`.`type` IN ('A', 'QUIZ', 'DISC') OR `item`.`points` IS NOT NULL )"])
        ->where(['item.is_published IS TRUE AND item.start_date IS NOT NULL AND page.is_published IS TRUE']);

        $select2 = $this->tableGateway->getSql()->select();
        $select2->columns(['id', 'parent_id', 'page_id', 'item$timeline_type' => new Expression(" 'E' "), 'item$order_date' => new Expression("item.end_date")])
        ->join('page_user', 'page_user.page_id=item.page_id', [])
        ->join('page', 'page.id=item.page_id', [])
        ->where(['page_user.user_id' => $me])
        ->where(['page_user.state' => 'member'])
        ->where(["( `item`.`type` IN ('A', 'QUIZ', 'DISC') OR `item`.`points` IS NOT NULL )"])
        ->where(['item.is_published IS TRUE AND item.end_date IS NOT NULL AND page.is_published IS TRUE']);

        $select1->combine($select2);

        return $this->selectWith($select1);
    }

    public function getListAssignmentId($me, $page_id = null, $filter = null, $is_admin_page = null)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns([
      'id',
      'parent_id',
      'page_id'])
      ->join('page_user', 'page_user.page_id=item.page_id', [])
      ->join('item_user', 'item_user.item_id=item.id', [], $select::JOIN_LEFT)
      ->where(['page_user.user_id' => $me])
      ->where(['page_user.state' => 'member'])
      ->where(["( `item`.`type` IN ('A', 'QUIZ', 'DISC') OR  `item`.`points` IS NOT NULL )"])
      ->where(['item.is_published IS TRUE'])
      ->order('item.page_id ASC')
      ->order('item.order ASC')
      ->quantifier('DISTINCT');

        if (null !== $page_id) {
            $select->where(['item.page_id' => $page_id]);
        }

        if (null !== $filter) {
            if ($filter['d'] === '<') {
                $select->where(['( item.start_date <= ? ' => $filter['s']]);
                $select->where([' item.end_date <= ? )' => $filter['s']], 'OR');
            } else {
                $select->where(['( item.start_date >= ? ' => $filter['s']]);
                $select->where([' item.end_date >= ? )' => $filter['s']], 'OR');
            }
            $select->order(['item.start_date','item.end_date']);
            $select->offset((($filter['p'] - 1) * $filter['n']));
            $select->limit($filter['n']);
        }
        if ($is_admin_page !== true) {
            $select->where(["( item.participants = 'all' || item_user.user_id = ? )" => $me]);
        }

        return $this->selectWith($select);
    }

    public function get($id, $me)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns([
        'id',
        'title',
        'points',
        'description',
        'type',
        'is_available',
        'is_published',
        'order',
        'item$start_date' => new Expression('DATE_FORMAT(item.start_date, "%Y-%m-%dT%TZ")'),
        'item$end_date' => new Expression('DATE_FORMAT(item.end_date, "%Y-%m-%dT%TZ")'),
        'item$updated_date' => new Expression('DATE_FORMAT(item.updated_date, "%Y-%m-%dT%TZ")'),
        'item$created_date' => new Expression('DATE_FORMAT(item.created_date, "%Y-%m-%dT%TZ")'),
        'parent_id',
        'page_id',
        'user_id',
        'participants',
        'conversation_id',
        'is_grade_published',
        'item$library_id' => new Expression("IF(`page_user`.`role`='admin' OR `item`.`is_grade_published`=true OR (`item`.`is_available`=1 OR (`item`.`is_available` = 3 AND (( `item`.`start_date` IS NULL AND `item`.`end_date` IS NULL )OR( `item`.`start_date` < UTC_TIMESTAMP() AND `item`.`end_date` IS NULL )OR( `item`.`start_date` IS NULL AND `item`.`end_date` > UTC_TIMESTAMP()) OR (UTC_TIMESTAMP() BETWEEN `item`.`start_date` AND `item`.`end_date` )))), `item`.`library_id`, NULL)"),
        'item$post_id' =>    new Expression("IF(`page_user`.`role`='admin' OR `item`.`is_grade_published`=true OR (`item`.`is_available`=1 OR (`item`.`is_available` = 3 AND (( `item`.`start_date` IS NULL AND `item`.`end_date` IS NULL )OR( `item`.`start_date` < UTC_TIMESTAMP() AND `item`.`end_date` IS NULL )OR( `item`.`start_date` IS NULL AND `item`.`end_date` > UTC_TIMESTAMP()) OR (UTC_TIMESTAMP() BETWEEN `item`.`start_date` AND `item`.`end_date` )))), `post`.`id`, NULL)"),
        'item$quiz_id' =>    new Expression("IF(`page_user`.`role`='admin' OR `item`.`is_grade_published`=true OR (`item`.`is_available`=1 OR (`item`.`is_available` = 3 AND (( `item`.`start_date` IS NULL AND `item`.`end_date` IS NULL )OR( `item`.`start_date` < UTC_TIMESTAMP() AND `item`.`end_date` IS NULL )OR( `item`.`start_date` IS NULL AND `item`.`end_date` > UTC_TIMESTAMP()) OR (UTC_TIMESTAMP() BETWEEN `item`.`start_date` AND `item`.`end_date` )))), `quiz`.`id`, NULL)"),
        'item$text' =>       new Expression("IF(`page_user`.`role`='admin' OR `item`.`is_grade_published`=true OR (`item`.`is_available`=1 OR (`item`.`is_available` = 3 AND (( `item`.`start_date` IS NULL AND `item`.`end_date` IS NULL )OR( `item`.`start_date` < UTC_TIMESTAMP() AND `item`.`end_date` IS NULL )OR( `item`.`start_date` IS NULL AND `item`.`end_date` > UTC_TIMESTAMP()) OR (UTC_TIMESTAMP() BETWEEN `item`.`start_date` AND `item`.`end_date` )))), `item`.`text`, NULL)")
      ])
      ->join('page_user', 'page_user.page_id=item.page_id', [])
      ->join('post', 'item.id=post.item_id', [], $select::JOIN_LEFT)
      ->join('quiz', 'item.id=quiz.item_id', [], $select::JOIN_LEFT)
      ->where(['page_user.user_id' => $me])
      ->where(['item.id' => $id]);

        return $this->selectWith($select);
    }

    public function getInfo($id)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns([
      'item$nb_total' => new Expression("IF(item.participants = 'ALL', COUNT(true), IF(item.participants = 'USER', COUNT(item_user.id), COUNT(DISTINCT item_user.group_id)))"),
      'item$nb_grade' => new Expression("IF(item.participants = 'GROUP', COUNT(DISTINCT item_user.group_id , IF(item_user.rate IS NULL, NULL, true))  , COUNT(item_user.rate))"),
      'item$nb_submission' => new Expression("IF(item.participants = 'GROUP', COUNT(DISTINCT item_user.group_id , IF(submission.submit_date IS NULL, NULL, true)) , COUNT(submission.submit_date))")
    ])
      ->join('page_user', 'page_user.page_id=item.page_id', [])
      ->join('item_user', 'item.id=item_user.item_id AND page_user.user_id=item_user.user_id', [], $select::JOIN_LEFT)
      ->join('submission', 'submission.id=item_user.submission_id', [], $select::JOIN_LEFT)
      ->where(['page_user.role'=> 'user'])
      ->where(['item.id' => $id]);

        return $this->selectWith($select);
    }

    public function getListSubmission($id, $user_id = null)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(['id', 'is_grade_published'])
      ->join('page_user', 'page_user.page_id=item.page_id', ['user_id'])
      ->join('item_user', 'item.id=item_user.item_id AND page_user.user_id=item_user.user_id', ['id', 'group_id', 'rate'], $select::JOIN_LEFT)
      ->join('group', 'group.id=item_user.group_id', ['id', 'name'], $select::JOIN_LEFT)
      ->join('submission', 'submission.id=item_user.submission_id', ['id', 'post_id',
      'submission$submit_date' => new Expression('DATE_FORMAT(submission.submit_date, "%Y-%m-%dT%TZ")'),
    ], $select::JOIN_LEFT)
      ->where(['page_user.role'=> 'user'])
      ->where(['item.id' => $id]);

        if (null !== $user_id) {
            $select->where(['page_user.user_id' => $user_id]);
        }

        return $this->selectWith($select);
    }


    public function getLastOrder($id, $page_id, $parent_id = null)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(['order']);
        if (is_numeric($parent_id)) {
            $select->where(['item.parent_id' => $parent_id]);
        } else {
            $select->where(['item.parent_id IS NULL']);
        }

        $select->where(['item.page_id' => $page_id])->where(['item.id <> ?' => $id])
      ->order('order DESC')
      ->limit(1);

        return $this->selectWith($select);
    }

    public function uptOrder($page_id, $order, $parent_id)
    {
        $update = $this->tableGateway->getSql()->update();
        $update->set(['order' => new Expression('`item`.`order`+1')])
      ->where(['`item`.`order` >= ? ' => $order])
      ->where(['page_id' => $page_id]);

        if (is_numeric($parent_id)) {
            $update->where(['item.parent_id' => $parent_id]);
        } else {
            $update->where(['item.parent_id IS NULL']);
        }

        return $this->updateWith($update);
    }
}
