<?php
/**
 * TheStudnet (http://thestudnet.com)
 *
 * User
 */
namespace Application\Mapper;

use Dal\Mapper\AbstractMapper;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\NotIn;
use Zend\Db\Sql\Where;

/**
 * Class  User
 */
class User extends AbstractMapper
{

    public function get($user_id, $me, $is_sadmin_admin = false)
    {
        $columns = [
            'user$id' => new Expression('user.id'),
            'firstname',
            'gender',
            'lastname',
            'nickname',
            'email',
            'background',
            'has_email_notifier',
            'user$birth_date' => new Expression('DATE_FORMAT(user.birth_date, "%Y-%m-%dT%TZ")'),
            'position',
            'interest',
            'avatar',
            'organization_id',
            'ambassador',
            'user$contacts_count' => $this->getSelectContactCount(),
            'user$contact_state' => $this->getSelectContactState($me)
        ];

        $select = $this->tableGateway->getSql()->select();
        $select->columns($columns)
            ->join(array('nationality' => 'country'), 'nationality.id=user.nationality', ['nationality!id' => 'id', 'short_name'], $select::JOIN_LEFT)
            ->join(array('origin' => 'country'), 'origin.id=user.origin', ['origin!id' => 'id', 'short_name'], $select::JOIN_LEFT)
            ->join(['user_address' => 'address'], 'user.address_id = user_address.id', ['user_address!id' => 'id','street_no','street_type','street_name','floor','door','apartment','building','longitude','latitude','timezone'], $select::JOIN_LEFT)
            ->join(['user_address_division' => 'division'], 'user_address_division.id=user_address.division_id', ['user_address_division!id' => 'id','name'], $select::JOIN_LEFT)
            ->join(['user_address_city' => 'city'], 'user_address_city.id=user_address.city_id', ['school_address_city!id' => 'id','name'], $select::JOIN_LEFT)
            ->join(['user_address_country' => 'country'], 'user_address_country.id=user_address.country_id', ['user_address_country!id' => 'id','short_name','name'], $select::JOIN_LEFT)
            ->where(['user.id' => $user_id])
            ->quantifier('DISTINCT');

        /*if ($is_sadmin_admin === false && $user_id !== $me) {
            $select->join(['co' => 'circle_organization'], 'co.organization_id=user.organization_id', [])
                ->join('circle_organization', 'circle_organization.circle_id=co.circle_id', [])
                ->join(['circle_page_user' => 'page_user'], 'circle_page_user.page_id=circle_organization.organization_id', [])
                ->where(['circle_page_user.user_id' => $user_id]);
        }*/
        return $this->selectWith($select);
    }

    public function getListLite($id)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array('id', 'firstname', 'lastname', 'nickname', 'avatar'))->where(array('user.id' => $id));

        return $this->selectWith($select);
    }

    public function getList(
      $user_id,
      $is_admin,
      $post_id = null,
      $search = null,
      $page_id = null,
      $order = null,
      array $exclude = null,
      $contact_state = null)
    {
        $select = $this->tableGateway->getSql()->select();
        if ($is_admin) {
            $select->columns([
              'user$id' => new Expression('user.id'),
              'firstname', 'lastname', 'email', 'nickname', 'ambassador', 'email_sent',
              'user$birth_date' => new Expression('DATE_FORMAT(user.birth_date, "%Y-%m-%dT%TZ")'),
              'position', 'interest', 'avatar', 'suspension_date', 'suspension_reason',
              'user$contact_state' => $this->getSelectContactState($user_id),
              'user$contacts_count' => $this->getSelectContactCount()
            ]);
        } else {
            $select->columns([
              'user$id' => new Expression('user.id'),
              'firstname', 'lastname', 'email', 'nickname','ambassador',
              'user$birth_date' => new Expression('DATE_FORMAT(user.birth_date, "%Y-%m-%dT%TZ")'),
              'position', 'interest', 'avatar',
              'user$contact_state' => $this->getSelectContactState($user_id),
              'user$contacts_count' => $this->getSelectContactCount()
            ]);/*->join(['co' => 'circle_organization'], 'co.organization_id=user.organization_id', [])
             ->join('circle_organization', 'circle_organization.circle_id=co.circle_id', [])
             ->join('page_user', 'page_user.page_id=circle_organization.organization_id', [])
             ->where(['page_user.user_id' => $user_id]);*/
        }
        $select->where('user.deleted_date IS NULL')
            ->group('user.id')
            ->quantifier('DISTINCT');

        if(null !== $order) {
          switch ($order['type']) {
            case 'firstname':
                $select->order('user.firstname ASC');
                break;
            case 'random':
                $select->order(new Expression('RAND(?)', $order['seed']));
                break;
            default :
              $select->order(['user.id' => 'DESC']);
          }
        } else {
          $select->order(['user.id' => 'DESC']);
        }

        if ($exclude) {
            $select->where->notIn('user.id', $exclude);
        }
        if (!empty($post_id)) {
          $select->join('post_like', 'post_like.user_id=user.id', [])
            ->where(['post_like.post_id' => $post_id])
            ->where(['post_like.is_like IS TRUE']);
        }
        if (!empty($page_id)) {
          $select->join('page_user', 'page_user.user_id=user.id', [])
            ->join('page', 'page_user.page_id=page.id', [])
            ->where(['page_user.page_id' => $page_id]);
        }
        if (null !== $search) {
            $select->where(['( CONCAT_WS(" ", user.firstname, user.lastname) LIKE ? ' => ''.$search.'%'])
                ->where(['CONCAT_WS(" ", user.lastname, user.firstname) LIKE ? ' => ''.$search.'%'], Predicate::OP_OR)
                ->where(['user.email LIKE ? ' => '%'.$search.'%'], Predicate::OP_OR)
                ->where(['user.nickname LIKE ? )' => ''.$search.'%'], Predicate::OP_OR);
        }
        if (null !== $contact_state) {
            if (!is_array($contact_state)) {
                $contact_state = [$contact_state];
            }
            $select->having(['user$contact_state' => $contact_state]);
            if (in_array(0, $contact_state)) {
                $select->having('user$contact_state IS NULL', Predicate::OP_OR);
            }
        }

	       return $this->selectWith($select);
    }

    public function getEmailUnique($email, $user)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array('user$nb_user' => new Expression('COUNT(true)')))
            ->where(array('user.email' => $email))
            ->where(array('user.deleted_date IS NULL'));

        if (null !== $user) {
            $select->where(array('user.id <> ?' => $user));
        }

        return $this->selectWith($select);
    }

    public function getNbrSisUnique($sis, $user)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array('user$nb_user' => new Expression('COUNT(true)')))
            ->where(array('user.sis' => $sis))
            ->where(array('user.deleted_date IS NULL'))
            ->where(array('user.sis IS NOT NULL'))
            ->where(array('user.sis <> ""'));

        if (null !== $user) {
            $select->where(array('user.id <> ?' => $user));
        }

        return $this->selectWith($select);
    }

    /**
     * Get Select Objet for Contact State
     *
     * @param  int $user
     * @return \Zend\Db\Sql\Select
     */
    public function getSelectContactState($user)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(
            array('user$contact_state' => new Expression(
                'IF(contact.accepted_date IS NOT NULL AND contact.deleted_date IS NULL, 3,
	         IF(contact.request_date IS NOT  NULL AND contact.requested <> 1 AND contact.deleted_date IS NULL, 2,
		     IF(contact.request_date IS NOT  NULL AND contact.requested = 1 AND contact.deleted_date IS NULL, 1,0)))'
            ))
        )
            ->join('contact', 'contact.contact_id = user.id', array())
            ->where(array('user.id=`user$id`'))
            ->where(['contact.user_id' => $user]);

        return $select;
    }

    /**
     * Get Select Objet for Contact Count
     *
     * @return \Zend\Db\Sql\Select
     */
    public function getSelectContactCount()
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array('user$contacts_count' => new Expression('COUNT(1)')))
            ->join('contact', 'contact.contact_id = user.id', [])
            ->where(array('contact.user_id = `user$id` AND user.deleted_date IS NULL AND contact.accepted_date IS NOT NULL AND contact.deleted_date IS NULL'));

        return $select;
    }
}
