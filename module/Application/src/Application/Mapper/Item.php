<?php

namespace Application\Mapper;

use Dal\Mapper\AbstractMapper;
use Zend\Db\Sql\Expression;

class Item extends AbstractMapper
{
  public function getListId($page_id, $me, $is_admin_page, $parent_id = null)
  {
    $select = $this->tableGateway->getSql()->select();
    $select->columns(['id', 'title', 'points', 'description', 'type', 'is_available', 'is_published', 'order', 'start_date', 'end_date', 'updated_date', 'created_date', 'parent_id', 'page_id', 'user_id'])
      ->join('page_user', 'page_user.page_id=item.page_id', [])
      ->where(['page_user.user_id' => $me])
      ->where(['item.page_id' => $page_id])
      ->order('item.page_id ASC')
      ->order('item.order ASC');

    if(null !== $parent_id) {
      $select->where(['item.parent_id' => $parent_id]);
    } else {
      $select->where(['item.parent_id IS NULL']);
    }

    if($is_admin_page !== true) {
      $select->where(['item.is_published IS TRUE']);
    }

    return $this->selectWith($select);
  }

  public function get($id, $me)
  {
    $select = $this->tableGateway->getSql()->select();
    $select->columns(['id', 'title', 'points','description', 'type', 'is_available', 'is_published', 'order', 'start_date', 'end_date', 'updated_date', 'created_date', 'parent_id', 'page_id', 'user_id'])
      ->join('page_user', 'page_user.page_id=item.page_id', [])
      ->where(['page_user.user_id' => $me])
      ->where(['id' => $id]);

    return $this->selectWith($select);
  }

  public function getLastOrder($id, $page_id, $parent_id = null)
  {
    $select = $this->tableGateway->getSql()->select();
    $select->columns(['order']);
    if(is_numeric($parent_id)) {
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

    if(is_numeric($parent_id)) {
      $update->where(['item.parent_id' => $parent_id]);
    } else {
      $update->where(['item.parent_id IS NULL']);
    }

    return $this->updateWith($update);
  }
}
