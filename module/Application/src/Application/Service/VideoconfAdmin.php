<?php
namespace Application\Service;

use Dal\Service\AbstractService;
use DateTimeZone;
use DateTime;
use OpenTok\Role as OpenTokRole;

class VideoconfAdmin extends AbstractService
{

    /**
     * Create admin for video conf.
     *
     * @param int $videoconf_id            
     *
     * @throws \Exception
     *
     * @return \Application\Model\VideoconfAdmin
     */
    public function add($videoconf_id, $role = OpenTokRole::MODERATOR)
    {
        $m_identity = $this->getServiceAuth()->getIdentity();
        $token = $this->getServiceZOpenTok()->createToken(
            $this->getServiceVideoconf()->get($videoconf_id)->getToken(),
            '{"id":'.$m_identity->getId() .',"firstname":"'.urlencode($m_identity->getFirstname()).'","lastname":"'.urlencode($m_identity->getLastname()).'","avatar":"'.$m_identity->getAvatar().'"}', $role, null);
        $m_videoconf_admin = $this->getModel();
        $m_videoconf_admin->setVideoconfId($videoconf_id)
            ->setUserId($m_identity->getId())
            ->setToken($token)
            ->setCreatedDate((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
        if ($this->getMapper()->insert($m_videoconf_admin) === 0) {
            throw new \Exception('Error insert');
        }
        
        return $m_videoconf_admin->setId($this->getMapper()
            ->getLastInsertValue());
    }

    /**
     *
     * @return \Application\Service\Videoconf
     */
    public function getServiceVideoconf()
    {
        return $this->getServiceLocator()->get('app_service_videoconf');
    }

    /**
     *
     * @return \ZOpenTok\Service\OpenTok
     */
    public function getServiceZOpenTok()
    {
        return $this->getServiceLocator()->get('opentok.service');
    }

    /**
     *
     * @return \Auth\Service\AuthService
     */
    public function getServiceAuth()
    {
        return $this->getServiceLocator()->get('auth.service');
    }
}
