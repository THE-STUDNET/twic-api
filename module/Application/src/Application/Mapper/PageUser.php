<?php

namespace Application\Mapper;

use Dal\Mapper\AbstractMapper;
use Application\Model\PageUser as ModelPageUser;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\Predicate;

class PageUser extends AbstractMapper
{  
    
    public function get($page_id = null, $user_id = null, $state = null){
        $select = $this->tableGateway->getSql()->select();
        $select->columns(['page_id','user_id','state','role', 'page_user$created_date' => new Expression('DATE_FORMAT(page_user.created_date, "%Y-%m-%dT%TZ")')]);
        if(null !== $page_id){
            $select->where(['page_id' => $page_id]);
        }
        if(null !== $user_id){
            $select->where(['user_id' => $user_id]);
        }
        
        return $this->selectWith($select);
               
    }
    
    public function getList($page_id = null, $user_id = null, $role = null, 
        $state = null, $type = null, $me = null, $sent = null, $is_pinned = null, 
        $search = null, $order = null)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(['page_id','user_id','state','role'])
            ->join('page', 'page_user.page_id = page.id', [])
            ->join('user', 'page_user.user_id = user.id', [])
            ->where(['page.deleted_date IS NULL'])
            ->where(['user.deleted_date IS NULL'])
            ->quantifier('DISTINCT');

        if (null!==$role) {
            if ($role !== ModelPageUser::ROLE_ADMIN) {
                $select->where(['page_user.state' => ModelPageUser::STATE_MEMBER]);
            } else {
                $select->where(['page_user.state <> ?' => ModelPageUser::STATE_REJECTED]);
            }
            $select->where(['page_user.role' => $role]);
        }
        if (null!==$page_id) {
            $select->where(['page_user.page_id' => $page_id]);
        }
        if (null!==$user_id) {
            $select->where(['page_user.user_id' => $user_id]);
        }
        if (null!==$state) {
            $select->where(['page_user.state' => $state]);
        }
        if (null!==$type) {
            $select->where(['page.type' => $type]);
        }
        if(null !== $sent) {
            $select->where(['user.email_sent' => $sent]);
        }
        if(true === $is_pinned) {
            $select->where('page_user.is_pinned IS TRUE');
        }
        else if(false === $is_pinned) {
            $select->where('page_user.is_pinned IS FALSE');
        }
        if (null !== $search) {
            $select->where(['( CONCAT_WS(" ", user.firstname, user.lastname) LIKE ? ' => $search.'%'])
                ->where(['CONCAT_WS(" ", user.lastname, user.firstname) LIKE ? ' => $search.'%'], Predicate::OP_OR)
                ->where(['user.email LIKE ? ' => $search.'%'], Predicate::OP_OR)
                ->where(['user.nickname LIKE ? )' => $search.'%'], Predicate::OP_OR);
        }
        if (null !== $me) {
            $select->join(['me' => 'page_user'], new Expression('me.page_id = page.id AND me.user_id = ?',$me), [], $select::JOIN_LEFT)
                ->where(['( page_user.state NOT IN ("pending", "invited") OR me.role = "admin" OR page_user.user_id = me.user_id)'])
                ->where(['( me.role IS NOT NULL OR page.confidentiality<>2 ) '])
                ->where(['(me.role = "admin" OR user.is_active = 1)'])
                ->where(['( page.is_published IS TRUE OR page.type <> "course" OR me.role = "admin" )']);
        }
        if (null !== $order) {
            switch ($order['type']) {
            case 'name':
                $select->order(new Expression('user.is_active DESC, COALESCE(NULLIF(user.nickname,""),TRIM(CONCAT_WS(" ",user.lastname,user.firstname, user.email)))'));
                break;
            case 'firstname':
                $select->order('user.firstname ASC');
                break;
            case 'organization':
                $select
                    ->join(['organization' => 'page'], 'organization.id = user.organization_id', [])
                    ->order( new Expression('organization.title ASC, user.is_active DESC, COALESCE(NULLIF(user.nickname,""),TRIM(CONCAT_WS(" ",user.lastname,user.firstname, user.email)))'));
                break;
            case 'admin':
                $select->order([new Expression('IF(me.role = "admin", 0, 1)'), 'me.page_id DESC']);
                break;
            case 'created_date':
                $select->order(['user.created_date' => 'DESC']);
                break;
            case 'date':
                $select->order([new Expression('IF(page.type = "organization", user.invitation_date, 0) DESC,  page_user.created_date DESC')]);
                break;
            case 'random':
                $select->order(new Expression('RAND(?)', $order['seed']));
                break;
            default:
                $select->order(['user.id' => 'DESC']);
            }
        }
        return $this->selectWith($select);
    }
}
