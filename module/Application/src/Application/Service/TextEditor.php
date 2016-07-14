<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Text Editor
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class TextEditor
 */
class TextEditor extends AbstractService
{
    /**
     * @param int $submission_id
     *
     * @return \Dal\Db\ResultSet\ResultSet
     */
    public function getListOrCreate($submission_id)
    {
        $res_text_editor = $this->getListBySubmission($submission_id);
        if ($res_text_editor->count() <= 0) {
            $this->add($submission_id);
            $res_text_editor = $this->getListBySubmission($submission_id);
        }

        return $res_text_editor;
    }

    public function getListBySubmission($submission_id)
    {
        return $this->getMapper()->select($this->getModel()->setSubmissionId($submission_id));
    }

    public function getListBy($submission_id)
    {
        return $this->getMapper()->select($this->getModel()->setSubmissionId($submission_id));
    }
    
    public function getListByConversation($conversation_id)
    {
        return $this->getMapper()->getListByConversation($conversation_id);
    }

    /**
     * @invokable
     * 
     * @param int    $submission_id
     * @param string $name
     * @param string $text
     * @param string $submit_date
     * @param integer $conversation_id
     */
    public function add($submission_id, $name = null, $text = null, $submit_date = null, $conversation_id = null)
    {
        $m_text_editor = $this->getModel()
            ->setSubmissionId($submission_id)
            ->setName($name)
            ->setText($text)
            ->setSubmitDate($submit_date);

        if ($this->getMapper()->insert($m_text_editor) <= 0) {
            //@TODO error
        }

        return $this->getMapper()->getLastInsertValue();
    }
    
    public function _add($data)
    {
        $submission_id = ((isset($data['submission_id']))? $data['submission_id']:null);
        $name = ((isset($data['name']))? $data['name']:null);
        $text = ((isset($data['text']))? $data['text']:null);
        $submit_date = ((isset($data['submit_date']))? $data['submit_date']:null);
        $conversation_id = ((isset($data['conversation_id']))? $data['conversation_id']:null);
        
        return $this->add($submission_id, $name, $text, $submit_date, $conversation_id);
    }

    /**
     * @invokable
     *
     * @param integer $id
     *
     * @return integer
     */
    public function delete($id)
    {
        return $this->getMapper()->delete($this->getModel()->setId($id));
    }

    /**
     * @invokable
     * 
     * @param int    $id
     * @param int    $submission_id
     * @param string $name
     * @param string $text
     * @param string $submit_date
     */
    public function update($id, $submission_id = null, $name = null, $text = null, $submit_date = null)
    {
        $m_text_editor = $this->getModel()
            ->setId($id)
            ->setSubmissionId($submission_id)
            ->setName($name)
            ->setSubmitDate($submit_date)
            ->setText($text);

        return $this->getMapper()->update($m_text_editor);
    }
}
