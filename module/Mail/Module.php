<?php

namespace Mail;

use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
                'Zend\Loader\StandardAutoloader' => array(
                        'namespaces' => array(
                                __NAMESPACE__ => __DIR__.'/src/'.__NAMESPACE__,
                        ),
                ),
                  'Zend\Loader\ClassMapAutoloader' => array(
                 __DIR__.'/autoload_classmap.php',
                ),
        );
    }

    public function getConfig()
    {
        return include __DIR__.'/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'aliases' => array(
                'mail.service' => 'Mail\Service\Mail',
            ),
            'invokables' => array(
                'Mail\Service\Mail' => 'Mail\Service\Mail',
                'Mail\Template\Storage\FsStorage' => 'Mail\Template\Storage\FsStorage',
                'Mail\Mail\Message' => 'Mail\Mail\Message',
            ),
        );
    }
}
