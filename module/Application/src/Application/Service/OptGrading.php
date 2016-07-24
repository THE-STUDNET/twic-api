<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Option Grading
 *
 */
namespace Application\Service;

use Dal\Service\AbstractService;

/**
 * Class OptGrading
 */
class OptGrading extends AbstractService
{
    /**
     * Add Option Grading
     * 
     * @invokable
     *
     * @param int    $item_id
     * @param string $mode
     * @param bool   $has_pg
     * @param int    $pg_nb
     * @param bool   $pg_auto
     * @param string $pg_due_date
     * @param bool   $pg_can_view
     * @param bool   $user_can_view
     * @param bool   $pg_stars
     * @return int
     */
    public function add($item_id, $mode = null, $has_pg = null, $pg_nb = null, $pg_auto = null, $pg_due_date = null, $pg_can_view = null, $user_can_view = null, $pg_stars = null)
    {
        $this->delete($item_id);
        
        $m_opt_grading = $this->getModel()
            ->setItemId($item_id)
            ->setMode($mode)
            ->setHasPg($has_pg)
            ->setPgNb($pg_nb)
            ->setPgAuto($pg_auto)
            ->setPgDueDate($pg_due_date)
            ->setPgCanView($pg_can_view)
            ->setUserCanView($user_can_view)
            ->setPgStars($pg_stars);

        return $this->getMapper()->insert($m_opt_grading);
    }

    /**
     * Update Option Grading
     * 
     * @invokable
     *
     * @param int    $item_id
     * @param string $mode
     * @param bool   $has_pg
     * @param int    $pg_nb
     * @param bool   $pg_auto
     * @param string $pg_due_date
     * @param bool   $pg_can_view
     * @param bool   $user_can_view
     * @param bool   $pg_stars
     * @return int
     */
    public function update($item_id, $mode = null, $has_pg = null, $pg_nb = null, $pg_auto = null, $pg_due_date = null, $pg_can_view = null, $user_can_view = null, $pg_stars = null)
    {
        $m_opt_grading = $this->getModel()
            ->setItemId($item_id)
            ->setMode($mode)
            ->setHasPg($has_pg)
            ->setPgNb($pg_nb)
            ->setPgAuto($pg_auto)
            ->setPgDueDate($pg_due_date)
            ->setPgCanView($pg_can_view)
            ->setUserCanView($user_can_view)
            ->setPgStars($pg_stars);

        return $this->getMapper()->update($m_opt_grading);
    }

    /**
     * Delete Option Grading
     * 
     * @invokable
     *
     * @param int $item_id
     * @return bool
     */
    public function delete($item_id)
    {
        $m_opt_grading = $this->getModel()->setItemId($item_id);

        return $this->getMapper()->delete($m_opt_grading);
    }

    /**
     * Get Option Grading
     * 
     * @param int $item_id
     * @return \Application\Model\OptGrading
     */
    public function get($item_id)
    {
        $m_opt_grading = $this->getModel()->setItemId($item_id);

        return $this->getMapper()->select($m_opt_grading)->current();
    }
}
