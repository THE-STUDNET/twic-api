<?php

namespace Application\Service;

use Dal\Service\AbstractService;

class ProgramUserRelation extends AbstractService
{
    public function add($user, $program)
    {
        $ret = array();

        foreach ($user as $u) {
            foreach ($program as $p) {
                $ret[$u][$p] = $this->getMapper()->insertUserProgram($p, $u);
            }
        }

        return $ret;
    }

    /**
     * @param array $user
     * @param array $program
     *
     * @return int
     */
    public function deleteProgram($user, $program)
    {
        $ret = array();

        if (!is_array($user)) {
            $user = array($user);
        }

        if (!is_array($program)) {
            $program = array($program);
        }

        foreach ($user as $u) {
            foreach ($program as $p) {
                $ret[$u][$p] = $this->getMapper()->delete($this->getModel()->setProgramId($p)->setUserId($u));
            }
        }

        return $ret;
    }

    public function deleteByUser($user)
    {
        return $this->getMapper()->delete($this->getModel()->setUserId($user));
    }
}
