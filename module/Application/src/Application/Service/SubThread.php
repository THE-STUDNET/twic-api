<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * SubThread
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class SubThread
 */
class SubThread extends AbstractService
{
    /**
     * @param int $thread_id
     * @param int $submission_id
     *
     * @return int
     */
    public function add($thread_id, $submission_id)
    {
        $m_sub_tread = $this->getModel()->setThreadId($thread_id)->setSubmissionId($submission_id);

        if ($this->getMapper()->select($m_sub_tread)->count() === 0) {
            return $this->getMapper()->insert($m_sub_tread);
        }

        return false;
    }
}
