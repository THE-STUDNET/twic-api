<?php

namespace Application\Mapper;

use Dal\Mapper\AbstractMapper;
use Zend\Db\Sql\Predicate\Expression;

class Message extends AbstractMapper
{
  public function getList($user_id, $conversation_id = null, $message_id = null)
  {
      $select = $this->tableGateway->getSql()->select();
      $select->columns(['id', 'text', 'user_id','message$created_date' => new Expression('DATE_FORMAT(message.created_date, "%Y-%m-%dT%TZ")')])
        ->join(['message_library' => 'library'], 'message_library.id=message.library_id', ['message_library!id' => 'id', 'name', 'link', 'token', 'type', 'box_id'], $select::JOIN_LEFT)
        ->join(['message_message_user' => 'message_user'], new Expression('message.id=message_message_user.message_id AND message_message_user.user_id = ?', [$user_id]) ,  ['message_message_user$read_date' => new Expression('DATE_FORMAT(message_message_user.read_date, "%Y-%m-%dT%TZ")')], $select::JOIN_LEFT)
        ->where(['message_message_user.deleted_date IS NULL'])
        ->order(['message.id DESC']);

      if(null !== $message_id) {
        $select->where(['message.id' => $message_id]);
      }
      if(null !== $conversation_id) {
        $select->where(['message.conversation_id' => $conversation_id]);
      }

      return $this->selectWith($select);
  }

}
