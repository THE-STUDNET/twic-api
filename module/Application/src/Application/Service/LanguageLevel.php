<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class LanguageLevel extends AbstractService
{
    /**
     * @invokable
     */
    public function getList()
    {
        return $this->getMapper()->fetchAll();
    }
}
