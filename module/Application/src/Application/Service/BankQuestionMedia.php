<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Bank Question Media
 *
 */

namespace Application\Service;

use Dal\Service\AbstractService;
use Application\Model\Library as ModelLibrary;

/**
 * Class BankQuestionMedia
 */
class BankQuestionMedia extends AbstractService
{
    /**
     * @param int $bank_question_id
     * @param $data
     * 
     * @throws \Exception
     *                     
     * @return int
     */
    public function add($bank_question_id, $data = [])
    {
        $ret = [];
        foreach ($data as $bqm) {
            $token = (isset($bqm['token'])) ? $bqm['token'] : null;
            $link = (isset($bqm['link'])) ? $bqm['link'] : null;
            $name = (isset($bqm['name'])) ? $bqm['name'] : null;
            $type = (isset($bqm['type'])) ? $bqm['type'] : null;

            $ret[] = $this->_add($bank_question_id, $name, $link, $token, $type);
        }

        return $ret;
    }

    public function replace($bank_question_id, $data)
    {
        $this->getMapper()->delete($this->getModel()->setBankQuestionId($bank_question_id));

        return $this->add($bank_question_id, $data);
    }

    public function copy($bank_question_id_new, $bank_question_id_old)
    {
        $res_bank_question_media = $this->getMapper()->select($this->getModel()->setBankQuestionId($bank_question_id_old));

        foreach ($res_bank_question_media as $m_bank_question_media) {
            $this->getMapper()->insert($m_bank_question_media->setBankQuestionId($bank_question_id_new)->setId(null));
        }

        return true;
    }

    /**
     * @param int    $bank_question_id
     * @param string $name
     * @param string $link
     * @param string $token
     * @param string $type
     * 
     * @return int
     */
    public function _add($bank_question_id, $name = null, $link = null, $token = null, $type = null)
    {
        $m_library = $this->getServiceLibrary()->add($name, $link, $token, $type, ModelLibrary::FOLDER_OTHER_INT);

        $m_bank_question_media = $this->getModel()
            ->setBankQuestionId($bank_question_id)
            ->setLibraryId($m_library->getId());

        if ($this->getMapper()->insert($m_bank_question_media) <= 0) {
            throw new \Exception('error insert media');
        }

        return $this->getMapper()->getLastInsertValue();
    }

    public function getList($bank_question_id)
    {
        return $this->getServiceLibrary()->getListByBankQuestion($bank_question_id);
    }

    public function getListBankQuestion($bank_question_id)
    {
        return $this->getMapper()->getListBankQuestion($bank_question_id);
    }

    /**
     * @return \Application\Service\Library
     */
    public function getServiceLibrary()
    {
        return $this->getServiceLocator()->get('app_service_library');
    }
}
