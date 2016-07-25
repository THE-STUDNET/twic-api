<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Submission
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;
use Application\Model\Item as ModelItem;
use Zend\Db\Sql\Predicate\IsNull;
use JRpc\Json\Server\Exception\JrpcException;
use Application\Model\Library as ModelLibrary;
use Application\Model\Role as ModelRole;

/**
 * Class Submission
 */
class Submission extends AbstractService
{

    protected $sub = [ModelItem::TYPE_INDIVIDUAL_ASSIGNMENT => [ModelItem::CMP_TEXT_EDITOR => true,ModelItem::CMP_CHAT => true],ModelItem::TYPE_HANGOUT => [ModelItem::CMP_VIDEOCONF => true,ModelItem::CMP_CHAT => true],ModelItem::TYPE_CHAT => [ModelItem::CMP_CHAT => true],ModelItem::TYPE_CAPSTONE_PROJECT => [ModelItem::CMP_TEXT_EDITOR => true,ModelItem::CMP_CHAT => false],ModelItem::TYPE_DISCUSSION => [ModelItem::CMP_DISCUSSION => true],ModelItem::TYPE_DOCUMENT => [ModelItem::CMP_DOCUMENT => true],ModelItem::TYPE_EQCQ => [ModelItem::CMP_EQCQ => true],ModelItem::TYPE_MODULE => [],ModelItem::TYPE_POLL => [ModelItem::CMP_POLL => true],ModelItem::TYPE_TXT => []];

    /**
     *
     * @param int $user_id            
     * @param int $questionnaire_id            
     *
     * @return \Application\Service\Submission
     */
    public function getByUserAndQuestionnaire($user_id, $questionnaire_id, $item_id)
    {
        $res_submission = $this->getMapper()->getByUserAndQuestionnaire($user_id, $questionnaire_id);
        $m_submission = ($res_submission->count() <= 0) ? $this->get($item_id) : $res_submission->current();
        
        return $m_submission;
    }

    /**
     *
     * @param int $user_id            
     * @param int $conversation_id            
     *
     * @return \Application\Model\Submission
     */
    public function getByUserAndConversation($user_id, $conversation_id)
    {
        $res_submission = $this->getMapper()->getByUserAndConversation($user_id, $conversation_id);
        return ($res_submission->count() <= 0) ? null : $res_submission->current();
    }

    /**
     *
     * @param int $item_id            
     * @param int $user_id            
     * @param int $group_id            
     *
     * @return int
     */
    public function create($item_id, $user_id = null, $group_id = null)
    {
        // / INIT VARIABLE
        $m_item = $this->getServiceItem()->get($item_id);
        if (null === $user_id) {
            $user_id = $this->getServiceUser()->getIdentity()['id'];
        }
        if (is_numeric($m_item->getSetId()) && $group_id === null) {
            $group_id = $this->getServiceGroupUser()->getGroupIdByItemUser($item_id, $user_id);
        }
        // / FIN INIT VARIABLE
        
        $submission_id = null;
        /*
         * 3 types => 1 Par group set_id, Une submission par group
         * 2 Individuel, Une submission pas éléve
         * 3 Tout le monde, Une submission pour tout le monde
         *
         * Ici pour le type 3 (Live class concerné)
         */
        if (($m_item->getType() === ModelItem::HANGOUT) && ! is_numeric($m_item->getSetId())) {
            $res_submission = $this->getMapper()->get($item_id);
            if ($res_submission->count() > 0) {
                $m_submission = $res_submission->current();
                $submission_id = $m_submission->getId();
            }
        }
        
        if (null === $submission_id) {
            $m_submission = $this->getModel()
                ->setItemId($item_id)
                ->setGroupId($group_id);
            $this->getMapper()->insert($m_submission);
            $submission_id = $this->getMapper()->getLastInsertValue();
        }
        
        $res_user = null;
        if (null !== $group_id) {
            $res_user = $this->getServiceUser()->getListUsersByGroup($group_id);
        } elseif ($m_item->getType() === ModelItem::HANGOUT) {
            $res_user = $this->getServiceUser()->getListByItem($item_id);
        }
        
        $users = [];
        if (null === $res_user || $res_user->count() <= 0) {
            $users[] = $user_id;
        } else {
            foreach ($res_user as $m_user) {
                $users[] = $m_user->getId();
            }
        }
        
        $this->getServiceSubmissionUser()->create($submission_id, $users);
        
        return $submission_id;
    }

    /**
     * @invokable
     *
     * @param int $item_id            
     * @param int $user_id            
     * @param int $submission_id            
     * @param int $group_id            
     *
     * @return null|\Application\Model\Submission
     */
    public function getSubmissionUser($item_id = null, $user_id = null, $submission_id = null, $group_id = null)
    {
        if (null === $item_id && null === $submission_id) {
            return;
        }
        
        if (null === $submission_id && null === $user_id && null !== $item_id) {
            $user_id = $this->getServiceUser()->getIdentity()['id'];
        }
        
        $res_submission = $this->getMapper()->getSubmissionUser($item_id, $user_id, $submission_id, $group_id);
        
        return ($res_submission->count() === 1) ? $res_submission->current() : null;
    }

    /**
     * Get With Item
     * 
     * @param int $submission_id            
     * @return \Application\Model\Submission
     */
    public function getWithItem($submission_id)
    {
        $res_submission = $this->getMapper()->getWithItem($submission_id);
        if ($res_submission->count() <= 0) {
            return;
        }
        
        $m_submission = $res_submission->current();
        $m_submission->setSubmissionUser($this->getServiceSubmissionUser()
            ->getListBySubmissionId($m_submission->getId()));
        
        return $m_submission;
    }

    /**
     * @invokable
     *
     * @param int $item_id            
     * @param int $submission_id            
     * @param int $group_id            
     * @param int $user_id            
     *
     * @return \Application\Model\Submission
     */
    public function get($item_id = null, $submission_id = null, $group_id = null, $user_id = null)
    {
        $identity = $this->getServiceUser()->getIdentity();
        
        // // ICI INITIALISATION DE LA RECHERCHE DE SUBMISSION
        if (null === $item_id && null === $submission_id) {
            throw new \Exception('error item and submission are null in submission.get');
        }
        if (in_array(ModelRole::ROLE_STUDENT_STR, $identity['roles'])) {
            $user_id = $identity['id'];
        }
        // // FIN ICI INITIALISATION DE LA RECHERCHE DE SUBMISSION
        $res_submission = $this->getMapper()->get($item_id, $user_id, $submission_id);
        if ($res_submission->count() <= 0) {
            return;
        }
        $m_submission = $res_submission->current();
        $m_submission->setSubmissionUser($this->getServiceSubmissionUser()
            ->getListBySubmissionId($m_submission->getId()));
        
        return $m_submission;
    }

    public function add($data, $item_id)
    {
        $has_modif = false;
        $res_submission = $this->getMapper()->select($this->getModel()
            ->setItemId($item_id));
        foreach ($res_submission as $m_submission) {
            $is_present = false;
            foreach ($data as $su) {
                if ($m_submission->getId() === $su['submission_id']) {
                    $is_present = true;
                    break;
                }
            }
            if ($is_present === false) {
                $has_modif = true;
                $this->getMapper()->delete($this->getModel()
                    ->setId($m_submission->getId()));
            }
        }
        
        foreach ($data as $su) {
            if (isset($su['submission_id']) && is_numeric($su['submission_id'])) {
                $s_id = $su['submission_id'];
            } else {
                $this->getMapper()->insert($this->getModel()
                    ->setItemId($item_id)
                    ->setGroupName((isset($su['group_name']) ? $su['group_name'] : null))
                    ->setGroupId((isset($su['group_id']) ? $su['group_id'] : null)));
                $s_id = $this->getMapper()->getLastInsertValue();
            }
            // si il y a eu une modification des submission_user je has_modif a true
            if ($this->getServiceSubmissionUser()->create($s_id, $su['submission_user'])) {
                $has_modif = true;
            }
        }
        
        if ($has_modif === true) {
            $this->getServiceSubmissionPg()->autoAssign($item_id);
        }
    }

    /**
     * @invokable
     *
     * @param int $item_id            
     */
    public function getListToGrade($item_id)
    {
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        
        $res_submission = $this->getMapper()->getListToGrade($user_id, $item_id);
        foreach ($res_submission as $m_submission) {
            $m_submission->setSubmissionUser($this->getServiceSubmissionUser()
                ->getListBySubmissionId($m_submission->getId()));
        }
        
        return $res_submission;
    }

    public function addSubmissionUser($user_id, $item_id)
    {
        if ($this->getByItem($item_id, $user_id) === null) {
            $this->getMapper()->insert($this->getModel()
                ->setItemId($item_id));
            $this->getServiceSubmissionUser()->add($this->getMapper()
                ->getLastInsertValue(), $user_id);
        }
        
        return true;
    }

    /**
     * @invokable
     *
     * @param int $item_id            
     */
    public function getList($item_id)
    {
        $res_submission = $this->getMapper()->get($item_id);
        $m_item = $this->getServiceItem()->get($item_id);
        foreach ($res_submission as $m_submission) {
            $m_submission->setSubmissionUser($this->getServiceSubmissionUser()
                ->getListBySubmissionId($m_submission->getId()));
        }
        
        return $res_submission;
    }

    /**
     * @invokable
     *
     * @param array $filter            
     * @param array $type            
     * @param array $course            
     * @param bool $started            
     * @param bool $submitted            
     * @param bool $graded            
     * @param bool $late            
     * @param string $search            
     * @param string $user_id            
     */
    public function getListStudent($filter = null, $type = null, $course = null, $started = null, $submitted = null, $graded = null, $late = null, $search = null, $user_id = null, $tograde = null)
    {
        $identity = $this->getServiceUser()->getIdentity();
        
        if (null === $user_id || key_exists(ModelRole::ROLE_STUDENT_ID, $identity['roles'])) {
            $user_id = $identity['id'];
        }
        
        $mapper = $this->getMapper();
        
        $res_submission = $mapper->usePaginator($filter)->getListStudent($user_id, $type, $course, $started, $submitted, $graded, $late, $search, $tograde);
        foreach ($res_submission as $m_submission) {
            $m_submission->setSubmissionUser($this->getServiceSubmissionUser()
                ->getListBySubmissionId($m_submission->getId()));
        }
        
        return ['list' => $res_submission,'count' => $mapper->count()];
    }

    /**
     * @invokable
     *
     * @param int $grade            
     * @param int $item            
     * @param int $group            
     * @param int $submission            
     * @param int $user            
     */
    public function updateSubmissionGrade($grade, $item = null, $group = null, $submission = null, $user = null)
    {
        $m_submission = $this->get($item, $submission, $group, $user);
        
        $this->getServiceSubmissionUser()->OverwrittenGrade($m_submission->getId(), $grade);
        
        $this->getMapper()->update($this->getModel()
            ->setIsGraded(true)
            ->setId($m_submission->getId()));
        
        return $m_submission->getId();
    }

    /**
     *
     * @param int $id            
     *
     * @return \Application\Model\Submission
     */
    public function getBySubmission($id)
    {
        return $this->get(null, $id);
    }

    /**
     * Get By Item
     *
     * @invokable
     *
     * @param int $item_id            
     * @param int $user_id            
     *
     * @return \Application\Model\Submission
     */
    public function getByItem($item_id, $user_id = null)
    {
        return $this->get($item_id, null, null, $user_id);
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     */
    public function getContent($submission_id)
    {
        if (null === ($m_submission = $this->getBySubmission($submission_id))) {
            throw new JrpcException('Error no submission', 999);
        }
        
        $ret = [];
        $item_id = $m_submission->getItemId();
        $m_item = $this->getServiceItem()->get($item_id);
        $type = (isset($this->sub[$m_item->getType()])) ? $this->sub[$m_item->getType()] : [];
        
        if (isset($type[ModelItem::CMP_TEXT_EDITOR]) && $type[ModelItem::CMP_TEXT_EDITOR] === true) {
            $ret[ModelItem::CMP_TEXT_EDITOR] = $this->getServiceTextEditor()->getListOrCreate($submission_id);
        } else {
            $ret[ModelItem::CMP_TEXT_EDITOR] = $this->getServiceTextEditor()->getListBySubmission($submission_id);
        }
        $ret[ModelItem::CMP_DOCUMENT] = $this->getServiceLibrary()->getListBySubmission($submission_id);
        if (isset($type[ModelItem::CMP_CHAT]) && $type[ModelItem::CMP_CHAT] === true) {
            if (! (! $m_item->getIsGrouped() && $m_item->getType() === ModelItem::TYPE_INDIVIDUAL_ASSIGNMENT)) {
                $ret[ModelItem::CMP_CHAT] = $this->getServiceConversation()->getListOrCreate($submission_id);
            }
        } else {
            $ret[ModelItem::CMP_CHAT] = $this->getServiceConversation()->getListBySubmission($submission_id);
        }
        if (isset($type[ModelItem::CMP_POLL]) && $type[ModelItem::CMP_POLL] === true) {
            $ret[ModelItem::CMP_POLL] = $this->getServiceSubQuiz()->getBySubmission($submission_id);
        }
        if (isset($type[ModelItem::CMP_DISCUSSION])) {
            $ret[ModelItem::CMP_DISCUSSION] = $this->getServiceThread()->getBySubmission($submission_id);
        }
        
        return $ret;
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     */
    public function getContentSg($submission_id)
    {
        if (null === ($m_submission = $this->getBySubmission($submission_id))) {
            throw new JrpcException('Error no submission', 999);
        }
        
        $ret = [];
        $item_id = $m_submission->getItemId();
        $m_item = $this->getServiceItem()->get($item_id);
        $type = (isset($this->sub[$m_item->getType()])) ? $this->sub[$m_item->getType()] : [];
        
        $ret[ModelItem::CMP_TEXT_EDITOR] = $this->getServiceTextEditor()->getListBySubmission($submission_id);
        $ret[ModelItem::CMP_WHITEBOARD] = $this->getServiceWhiteboard()->getList($submission_id);
        $ret[ModelItem::CMP_DOCUMENT] = $this->getServiceLibrary()->getListBySubmission($submission_id);
        $ret[ModelItem::CMP_CHAT] = $this->getServiceConversation()->getListBySubmission($submission_id, true);
        $ret[ModelItem::CMP_DISCUSSION] = $this->getServiceThread()->getBySubmission($submission_id);
        $ret[ModelItem::CMP_POLL] = $this->getServiceSubQuiz()->getBySubmission($submission_id);
        
        return $ret;
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     * @param int $item_id            
     *
     * @return void|bool
     */
    public function forceSubmit($submission_id = null, $item_id = null)
    {
        if ($submission_id === null && $item_id === null) {
            return;
        }
        
        return ($submission_id !== null) ? $this->forceSubmitBySubmission($submission_id) : $this->forceSubmitByItem($item_id);
    }

    /**
     * @invokable
     *
     * @param int $item_id            
     *
     * @return bool
     */
    public function forceSubmitByItem($item_id)
    {
        return $this->forceSubmitBySubmission($this->getByItem($item_id)
            ->getId());
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     *
     * @return bool
     */
    public function forceSubmitBySubmission($submission_id)
    {
        $m_submission = $this->getModel()
            ->setSubmitDate((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
            ->setId($submission_id);
        
        return $this->getMapper()->update($m_submission);
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     * @param int $item_id            
     *
     * @return void|bool
     */
    public function submit($submission_id = null, $item_id = null)
    {
        return $this->_submit($submission_id, $item_id);
    }

    public function _submit($submission_id = null, $item_id = null, $user_id = null)
    {
        $ret = null;
        
        if ($submission_id === null && $item_id === null) {
            return;
        }
        
        if ($submission_id !== null) {
            if ($user_id !== null) {
                $ret = $this->_submitBySubmission($submission_id, $user_id);
            } else {
                $ret = $this->submitBySubmission($submission_id);
            }
        } else {
            $ret = $this->submitByItem($item_id);
        }
        
        return $ret;
    }

    /**
     * toutes les oumission passe par ici
     *
     * @param integer $submission_id            
     * @param integer $user_id            
     *
     * @return integer
     */
    public function _submitBySubmission($submission_id, $user_id)
    {
        $submit = 1;
        $is_first = true;
        $is_ok = false;
        $res_submission_user = $this->getServiceSubmissionUser()->getListBySubmissionId($submission_id, $user_id);
        foreach ($res_submission_user as $m_submission_user) {
            if ($m_submission_user->getUserId() === $user_id) {
                $this->getServiceSubmissionUser()->submit($submission_id, $user_id);
                // A bien était soumis
                $is_ok = true;
            } else {
                $is_check = ($m_submission_user->getSubmitDate() !== null && (! $m_submission_user->getSubmitDate() instanceof IsNull));
                // Si c'est vrai, alors ce n'est pas la premiere soumission
                if ($is_check) {
                    $is_first = false;
                }
                $submit &= $is_check;
            }
        }
        
        $m_item = $this->getServiceItem()->getBySubmission($submission_id);
        // Si c la premiere fois que l'on soumet et quil manque des soumissions
        if ($is_ok === true && $is_first === true && $submit !== 1) {
            if ($m_item->getType() === $m_item::TYPE_INDIVIDUAL_ASSIGNMENT) {
                $user = [];
                foreach ($res_submission_user as $m_submission_user) {
                    if ($user_id !== $m_submission_user->getUserId() && $m_submission_user->getStartDate() !== null && (! $m_submission_user->getStartDate() instanceof IsNull) && ($m_submission_user->getSubmitDate() === null || ($m_submission_user->getSubmitDate() instanceof IsNull))) {
                        $user[] = $m_submission_user->getUserId();
                    }
                }
                if (count($user) > 0) {
                    $this->getServiceEvent()->requestSubmit($submission_id, $user);
                }
            }
        }
        
        if ($submit === 1) {
            $this->getServiceEvent()->endSubmit($submission_id);
            if ($this->getServiceOptGrading()
                ->get($m_item->getId())
                ->getHasPg()) {
                $this->getServiceEvent()->pgAssigned($submission_id);
            }
            $this->forceSubmitBySubmission($submission_id);
        }
        
        return $submit;
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     *
     * @return bool
     */
    public function submitBySubmission($submission_id)
    {
        $user_id = $this->getServiceUser()->getIdentity()['id'];
        
        return $this->_submitBySubmission($submission_id, $user_id);
    }

    /**
     * @invokable
     *
     * @param int $item_id            
     *
     * @return bool
     */
    public function submitByItem($item_id)
    {
        return $this->submitBySubmission($this->getByItem($item_id)
            ->getId());
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     * @param int $item_id            
     *
     * @return void|bool
     */
    public function cancelsubmit($submission_id = null, $item_id = null)
    {
        if ($submission_id === null && $item_id === null) {
            return;
        }
        
        return ($submission_id !== null) ? $this->cancelsubmitBySubmission($submission_id) : $this->cancelsubmitByItem($item_id);
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     *
     * @return bool
     */
    public function cancelsubmitBySubmission($submission_id)
    {
        $m_submission = $this->get(null, $submission_id);
        if (! ($m_submission->getSubmitDate() === null || $m_submission->getSubmitDate() instanceof IsNull)) {
            return;
        }
        
        return $this->getServiceSubmissionUser()->cancelsubmit($submission_id, $this->getServiceUser()
            ->getIdentity()['id']);
    }

    /**
     * @invokable
     *
     * @param int $item_id            
     *
     * @return bool
     */
    public function cancelsubmitByItem($item_id)
    {
        return $this->cancelsubmitBySubmission($this->getByItem($item_id)
            ->getId());
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     * @param string $name            
     * @param string $type            
     * @param string $link            
     * @param string $token            
     */
    public function addDocument($submission_id, $name = null, $type = null, $link = null, $token = null)
    {
        return $this->getServiceDocument()->add($name, $type, $link, $token, null, $submission_id, ModelLibrary::FOLDER_OTHER_INT);
    }

    /**
     * @invokable
     *
     * @param int $submission_id            
     * @param int $library_id            
     */
    public function deleteDocument($submission_id, $library_id)
    {
        return $this->getServiceDocument()->delete(null, $submission_id, $library_id);
    }

    /**
     * @invokable
     *
     * @param int $id            
     * @param array $users            
     */
    public function assignGraders($users, $id)
    {
        return $this->getServiceSubmissionPg()->replace($id, $users);
    }

    /**
     * @invokable
     *
     * @param int $id            
     */
    public function getPairGraders($id)
    {
        return $this->getServiceUser()->getListPairGraders($id);
    }

    /**
     * @invokable
     *
     * @param int $id            
     */
    public function getPGCriterias($id)
    {
        return $this->getServicePgUserCriteria()->getListBySubmission($id);
    }

    /**
     * @invokable
     *
     * @param int $id            
     */
    public function getUserCriterias($id)
    {
        return $this->getServiceSubmissionUserCriteria()->getListBySubmission($id);
    }

    /**
     * @invokable
     *
     * @param int $id            
     */
    public function getPGGrades($id)
    {
        return $this->getServicePgUserGrade()->getListBySubmission($id);
    }

    /**
     * @invokable
     *
     * @param int $id            
     * @param array $grades            
     * @param array $criterias            
     */
    public function pairRates($id, $grades = null, $criterias = null)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];
        $this->getServicePgUserCriteria()->deleteByUserAndSubmission($me, $id);
        $this->getServicePgUserGrade()->deleteByUserAndSubmission($me, $id);
        if (null !== $criterias && count($criterias) > 0) {
            foreach ($criterias as $criteria_id => $criteria) {
                foreach ($criteria as $user => $points) {
                    if (is_numeric($user) && null !== $points) {
                        $this->getServicePgUserCriteria()->add($me, $user, $id, $criteria_id, $points);
                    }
                }
            }
        } else {
            foreach ($grades as $user => $grade) {
                if ($grade !== null && is_numeric($user) && is_numeric($grade)) {
                    $this->getServicePgUserGrade()->add($me, $user, $id, $grade);
                }
            }
        }
        $this->processSubmissionPairGrade($id, $me);
        
        return 1;
    }

    /**
     * Intructor Rates
     * 
     * @invokable
     *
     * @param int $id
     * @param int $user_id
     * @param int $item
     * @param array $grades
     * @param int $criterias
     * @return int
     */
    public function instructorRates($id, $user_id = null, $item = null, $grades = null, $criterias = null)
    {
        $this->getServiceSubmissionUserCriteria()->deleteBySubmission($id);
        if (null !== $criterias && count($criterias) > 0) {
            foreach ($criterias as $criteria_id => $criteria) {
                foreach ($criteria as $user => $points) {
                    if (is_numeric($user) && null !== $points && isset($points['points'])) {
                        $this->getServiceSubmissionUserCriteria()->add($id, $user, $criteria_id, $points['points'], true);
                    }
                }
                $res_submission_user = $this->getServiceSubmissionUser()->getProcessedGrades($id);
                foreach ($res_submission_user as $m_submission_user) {
                    if(is_numeric($m_submission_user->getGrade())) {
                        $this->getServiceSubmissionUser()->setGrade($id, $m_submission_user->getUserId(), $m_submission_user->getGrade(), ! ($m_submission_user->getGrade() instanceof IsNull));
                    }
                }
            }
        }
        
        if (null !== $grades && count($grades) > 0) {
            foreach ($grades as $user => $grade) {
                if (is_numeric($user) && $grade !== null && isset($grade['grade'])) {
                    $this->getServiceSubmissionUser()->setGrade($id, $user, $grade['grade'], true);
                }
            }
        }
        $this->getMapper()->checkGraded($id);
        
        return $id;
    }

    /**
     * @invokable
     *
     * @param int $id            
     * @param int $group            
     * @param int $user            
     * @param string $file_name            
     * @param string $file_token            
     * @param string $audio            
     * @param string $text            
     */
    public function addComment($id, $group = null, $user = null, $item = null, $file_name = null, $file_token = null, $audio = null, $text = null)
    {
        $me = $this->getServiceUser()->getIdentity()['id'];
        
        $m_submission_comments = $this->getServiceSubmissionComments()->add($id, $me, $file_name, $file_token, $audio, $text);
        $this->getServiceEvent()->submissionCommented($id, $m_submission_comments->getId());
        
        return ['submission_id' => $id,'comment' => $m_submission_comments];
    }

    /**
     * @invokable
     *
     * @param int $id            
     */
    public function getComments($id = null)
    {
        return $this->getServiceSubmissionComments()->getList($id);
    }

    public function processSubmissionPairGrade($id, $user)
    {
        $res_pg_user_criteria = $this->getServicePgUserCriteria()->getProcessedGrades($id, $user);
        foreach ($res_pg_user_criteria as $m_pg_user_criteria) {
            $this->getServicePgUserGrade()->add($user, $m_pg_user_criteria->getUserId(), $id, $m_pg_user_criteria->getGrade());
        }
        $res_subm_user_criteria = $this->getServiceSubmissionUserCriteria()->getProcessedGrades($id);
        foreach ($res_subm_user_criteria as $m_sbm_user_criteria) {
            $this->getServiceSubmissionUserCriteria()->add($id, $m_sbm_user_criteria->getUserId(), $m_sbm_user_criteria->getCriteriaId(), $m_sbm_user_criteria->getPoints());
        }
        $res_pg_user_grade = $this->getServicePgUserGrade()->getProcessedGrades($id);
        foreach ($res_pg_user_grade as $m_pg_user_grade) {
            $this->getServiceSubmissionUser()->setGrade($id, $m_pg_user_grade->getUserId(), $m_pg_user_grade->getGrade());
        }
        $this->getServiceSubmissionPg()->checkGraded($id, $user);
        $this->getMapper()->checkGraded($id);
    }

    /**
     * @invokable
     *
     * @param int $id            
     *
     * @return array
     */
    public function getUserGrades($id)
    {
        return $this->getServiceSubmissionUser()->getList($id);
    }

    /**
     * Get Service GroupUser
     *
     * @return \Application\Service\GroupUser
     */
    private function getServiceGroupUser()
    {
        return $this->getServiceLocator()->get('app_service_group_user');
    }

    /**
     * Get Service Library
     *
     * @return \Application\Service\Library
     */
    private function getServiceLibrary()
    {
        return $this->getServiceLocator()->get('app_service_library');
    }

    /**
     * Get Service Document
     *
     * @return \Application\Service\Document
     */
    private function getServiceDocument()
    {
        return $this->getServiceLocator()->get('app_service_document');
    }

    /**
     * Get Service TextEditor
     *
     * @return \Application\Service\TextEditor
     */
    private function getServiceTextEditor()
    {
        return $this->getServiceLocator()->get('app_service_text_editor');
    }

    /**
     * Get Service Event
     *
     * @return \Application\Service\Event
     */
    private function getServiceEvent()
    {
        return $this->getServiceLocator()->get('app_service_event');
    }

    /**
     * Get Service Conversation
     *
     * @return \Application\Service\Conversation
     */
    private function getServiceConversation()
    {
        return $this->getServiceLocator()->get('app_service_conversation');
    }

    /**
     * Get Service Item
     *
     * @return \Application\Service\Item
     */
    private function getServiceItem()
    {
        return $this->getServiceLocator()->get('app_service_item');
    }

    /**
     * Get Service Thread
     *
     * @return \Application\Service\Thread
     */
    private function getServiceThread()
    {
        return $this->getServiceLocator()->get('app_service_thread');
    }

    /**
     * Get Service User
     *
     * @return \Application\Service\User
     */
    private function getServiceUser()
    {
        return $this->getServiceLocator()->get('app_service_user');
    }

    /**
     * Get Service OptGrading
     *
     * @return \Application\Service\OptGrading
     */
    private function getServiceOptGrading()
    {
        return $this->getServiceLocator()->get('app_service_opt_grading');
    }

    /**
     * Get Service SubmissionUser
     *
     * @return \Application\Service\SubmissionUser
     */
    private function getServiceSubmissionUser()
    {
        return $this->getServiceLocator()->get('app_service_submission_user');
    }

    /**
     * Get Service SubmissionComments
     *
     * @return \Application\Service\SubmissionComments
     */
    private function getServiceSubmissionComments()
    {
        return $this->getServiceLocator()->get('app_service_submission_comments');
    }

    /**
     * Get Service SubQuiz
     *
     * @return \Application\Service\SubQuiz
     */
    private function getServiceSubQuiz()
    {
        return $this->getServiceLocator()->get('app_service_sub_quiz');
    }

    /**
     * Get Service GradingPolicy
     *
     * @return \Application\Service\GradingPolicy
     */
    private function getServiceGradingPolicy()
    {
        return $this->getServiceLocator()->get('app_service_grading_policy');
    }

    /**
     * Get Service PgUserCriteria
     *
     * @return \Application\Service\PgUserCriteria
     */
    private function getServicePgUserCriteria()
    {
        return $this->getServiceLocator()->get('app_service_pg_user_criteria');
    }

    /**
     * Get Service SubmissionUserCriteria
     *
     * @return \Application\Service\SubmissionUserCriteria
     */
    private function getServiceSubmissionUserCriteria()
    {
        return $this->getServiceLocator()->get('app_service_submission_user_criteria');
    }

    /**
     * Get Service PgUserCriteria
     *
     * @return \Application\Service\PgUserCriteria
     */
    private function getServicePgUserGrade()
    {
        return $this->getServiceLocator()->get('app_service_pg_user_grade');
    }

    /**
     * Get Service Whiteboard
     *
     * @return \Application\Service\Whiteboard
     */
    private function getServiceWhiteboard()
    {
        return $this->getServiceLocator()->get('app_service_whiteboard');
    }
    
    /**
     *
     * Get Service SubmissionPg
     *
     * @return \Application\Service\SubmissionPg
     */
    private function getServiceSubmissionPg()
    {
        return $this->getServiceLocator()->get('app_service_submission_pg');
    }
}
