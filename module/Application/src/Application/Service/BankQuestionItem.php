<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class BankQuestionItem extends AbstractService
{
    /**
     *
     * @param integer $bank_question_id
     * @param $data
     *
     * @throws \Exception
     *
     * @return integer
     */
    public function add($bank_question_id, $data)
    {
        $ret = [];
        foreach ($data as $bqm) {
            $libelle = $bqm['libelle'];
            $answer = (isset($bqm['answer'])) ? $bqm['answer']:null;
            $percent = (isset($bqm['percent'])) ? $bqm['percent']:null;
            $order_id = (isset($bqm['order_id'])) ? $bqm['order_id']:null;
            
            $ret[] = $this->_add($bank_question_id, $libelle, $percent, $answer = null, $order_id = null);
        }
    
        return $ret;
    }
    
    public function replace($bank_question_id, $data)
    {
        $m_bank_question_item = $this->getModel()->setBankQuestionId($bank_question_id);
        
        $this->getMapper()->delete($m_bank_question_item);
    
        return $this->add($bank_question_id, $data);
    }
    
    public function copy($bank_question_id_new, $bank_question_id_old)
    {
        $res_bank_question_item = $this->getMapper()->select($this->getModel()->setBankQuestionId($bank_question_id_old));
        
        foreach ($res_bank_question_item as $m_bank_question_item) {
            $this->getMapper()->insert($m_bank_question_item->setBankQuestionId($bank_question_id_new)->setId(null));
        }
        
        return true;
    }
    
    /**
     * 
     * @param integer $bank_question_id
     * @param string $libelle
     * @param integer $percent
     * @param string $answer
     * @param integer $order_id
     * @throws \Exception
     * 
     * @return integer
     */
    public function _add($bank_question_id, $libelle, $percent = null, $answer = null, $order_id = null)
    {
        $m_bank_question_item = $this->getModel()
            ->setBankQuestionId($bank_question_id)
            ->setLibelle($libelle);
    
        if($this->getMapper()->insert($m_bank_question_item) <=0) {
            throw new \Exception('error insert tag');
        }
        
        $m_bank_question_item_id = $this->getMapper()->getLastInsertValue();
        $this->getServiceBankAnswerItem()->add($m_bank_question_item_id, $percent, $answer);
        
        return $m_bank_question_item_id;
    }
    
    public function getList($bank_question_id)
    {
        return $this->getMapper()->getList($bank_question_id);
    }
    
    /**
     *
     * @return \Application\Service\BankAnswerItem
     */
    public function getServiceBankAnswerItem()
    {
        return $this->getServiceLocator()->get('app_service_bank_answer_item');
    }
}