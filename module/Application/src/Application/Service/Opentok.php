<?php

namespace Application\Service;

use OpenTok\MediaMode;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Opentok implements ServiceLocatorAwareInterface
{
    protected $serviceLocator;

    /**
     * @invokable
     *
     * @param string $media_mode
     *
     * @return string
     */
    public function createSession($media_mode = MediaMode ::ROUTED)
    {
        return  $this->getServiceOpenTok()->createSession($media_mode);
    }

    /**
     * @return \ZOpenTok\Service\OpenTok
     */
    protected function getServiceOpenTok()
    {
        return $this->getServiceLocator()->get('opentok.service');
    }

    /**
     * Set service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        return $this;
    }

    /**
     * Get service locator.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}
