<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Language extends AbstractService
{
    /**
     * @invokable
     */
    public function getList() {
        return $this->getMapper()->fetchAll();
    }

    public function add($array)
    {
        if (!is_array($array)) {
            if (is_numeric($array)) {
                $array = array('id' => (int) $array);
            } elseif (is_string($array)) {
                $array = array('name' => $array);
            }
        }

        if (!empty($array['name']) && (empty($array['id']) || !isset($array['id']))) {
            $m_language = $this->getModel();

            $m_language->setLibelle($array['name']);
            $res_language = $this->getMapper()->select($m_language);

            if ($res_language->count() > 0) {
                $array['id'] = $res_language->current()->getId();
            } else {
                $this->getMapper()->insert($m_language);
                $array['id'] = $this->getMapper()->getLastInsertValue();
            }
        }

        if (empty($array['id']) && !is_numeric($array['id'])) {
            throw new \Exception(' array does not has id');
        }

        return $array['id'];
    }
}
