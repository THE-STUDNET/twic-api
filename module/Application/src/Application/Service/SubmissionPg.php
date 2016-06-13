<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class SubmissionPg extends AbstractService
{
    
    public function add($submission, $users)
    {
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        return  $this->getMapper()->insert($this->getModel()->setUserId($users)->setSubmissionId($submission)->setDate($date));
    }
    
    public function delete($submission, $users)
    {
        return  $this->getMapper()->delete($this->getModel()->setUserId($user)->setSubmissionId($submission));
    }
    
    public function deleteByItem($item_id)
    {
        $res_submission = $this->getServiceSubmission()->getList($item_id);
        
        foreach ($res_submission as $m_submission) {
            $this->getMapper()->delete($this->getModel()->setSubmissionId($m_submission->getId()));
        }
        
        return true;
    }
    
    public function checkGraded($submission, $user)
    {
        return  $this->getMapper()->checkGraded($submission, $user);
    }
    
    public function replace($submission, $users)
    {
        $this->getMapper()->deleteNotIn($submission, $users);
        foreach($users as $u){
            $this->add($submission, $u);
        }
        return 1;
    }
    
    /**
     * @invokable
     * 
     * @param integer $item_id
     */
    public function autoAssign($item_id)
    {
        $m_opt_grading = $this->getServiceOptGrading()->get($item_id);
        if($m_opt_grading === false && $m_opt_grading->getHasPg() == 0 && $m_opt_grading->getPgAuto() == 0) {
            return false;
        }
        
        $this->deleteByItem($item_id);
        
        $ar_s = []; 
        $ar_u = [];
        $res_submission = $this->getServiceSubmission()->getList($item_id);
        foreach ($res_submission as $m_submission) {
            $ar_s[$m_submission->getId()]=[];
            foreach ($m_submission->getSubmissionUser() as $m_su) {
                $u = $m_su->getUserId();
                $ar_s[$m_submission->getId()][] = $u;
                $ar_u[] = $u;
            }
        }
        $nb = $m_opt_grading->getPgNb();
        while (($final = $this->_autoAssign($ar_u, $ar_s, $nb)) === false);
        
        foreach ($final as $s => $u) {
            $this->replace($s, $u);
        }
    }
    
    public function _autoAssign($ar_u, $ar_s, $nb)
    {
        $nbu = count($ar_u);
        $start = $ar_u;
        $final = [];
        foreach ($ar_s as $s_id => $s_user) {
            if(count($ar_u) === 0) {
                $ar_u = $start;
            }
            $tmp = $ar_u;
            foreach ($s_user as $uu) {
                $search = array_search($uu, $tmp);
                if($search !== false) {
                    unset($tmp[$search]);
                }
            }
            if(count($tmp) >= $nb) {
                $keys = array_rand($tmp, $nb);
                if(!is_array($keys)) {$keys = [$keys];}
                foreach ($keys as $k) {
                    $final[$s_id][] = $ar_u[$k];
                    unset($ar_u[$k]);
                }
            } elseif (count($ar_s) === count($start)){
                return false;
            } else {
                $nbmin = count($tmp);
                $ar_u = $start;
                foreach ($tmp as $k => $t) {
                    $final[$s_id][] = $ar_u[$k];
                    unset($ar_u[$k]);
                }
                $tmp = $ar_u;
                foreach ($s_user as $uu) {
                    $search = array_search($uu, $tmp);
                    if($search !== false) {
                        unset($tmp[$search]);
                    }
                }
                if(count($tmp) >= $nb) {
                    $keys = array_rand($tmp, $nb - $nbmin);
                    if(!is_array($keys)) {$keys = [$keys];}
                    foreach ($keys as $k) {
                        $final[$s_id][] = $ar_u[$k];
                        unset($ar_u[$k]);
                    }
                } else {
                    foreach ($tmp as $k => $t) {
                        $final[$s_id][] = $ar_u[$k];
                        unset($ar_u[$k]);
                    }
                }
            }
        }
    
        return $final;
    }
    
    /**
     * @return \Application\Service\Submission
     */
    public function getServiceSubmission()
    {
        return $this->getServiceLocator()->get('app_service_submission');
    }
    
    /**
     * @return \Application\Service\OptGrading
     */
    public function getServiceOptGrading()
    {
        return $this->getServiceLocator()->get('app_service_opt_grading');
    }
    
}