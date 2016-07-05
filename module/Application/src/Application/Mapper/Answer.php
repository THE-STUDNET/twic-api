<?php

namespace Application\Mapper;

use Dal\Mapper\AbstractMapper;

class Answer extends AbstractMapper
{
    public function getList($submission_id, $user_id, $peer = null)
    {
        $select = $this->tableGateway->getSql()->select();
        $select->columns(array('id', 'peer_id', 'type', 'created_date'))
            ->join('scale', 'scale.id=answer.scale_id', array('answer$scale' => 'value'))
            ->join('question', 'question.id=answer.question_id', array('answer$component' => 'component_id'))
            ->join('component', 'component.id=question.component_id', array('answer$dimension' => 'dimension_id'))
            ->join('questionnaire_user', 'questionnaire_user.id=answer.questionnaire_user_id', array())
            ->join('user', 'user.id=questionnaire_user.user_id', array('answer$gender' => 'gender'))
            ->join('country', 'country.id=user.nationality', array('answer$nationality' => 'id', 'answer$nationality_name' => 'short_name'), $select::JOIN_LEFT)
            ->join(array('origin' => 'country'), 'origin.id=user.origin', array('answer$origin' => 'id', 'answer$origin_name' => 'short_name'), $select::JOIN_LEFT)
            ->join('questionnaire', 'questionnaire.id=questionnaire_user.questionnaire_id', array())
            ->join('item', 'item.id=questionnaire.item_id', array('answer$item' => 'id', 'answer$course' => 'course_id'))
            ->where(array("( answer.type='PEER' OR (answer.type='SELF' AND user.id = ? )) " => $user_id))
            ->where(array('scale.value <> 0'));

        if (null !== $peer) {
            $select->where(array('answer.peer_id' => $peer));
        }
        if (null !== $submission_id) {
            $select->where(array('questionnaire_user.submission_id' => $submission_id));
        }

        return $this->selectWith($select);
    }
}
