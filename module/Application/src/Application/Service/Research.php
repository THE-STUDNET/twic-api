<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class Research extends AbstractService
{
    /**
     * @invokable
     *            
     * @param string $string
     * 
     * @return array
     */
    public function getList($string, $filter = null)
    {
        $mapper = $this->getMapper();
        $res = $mapper->usePaginator($filter)->getList($string);

        return array('list' => $res,
                    'count' => $mapper->count(), );
    }
}
