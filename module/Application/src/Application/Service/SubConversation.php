<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class SubConversation extends AbstractService
{
    public function add($conversation_id, $submission_id)
    {
        return $this->getMapper()->insert($this->getModel()->setConversationId($conversation_id)->setSubmissionId($submission_id));
    }
}