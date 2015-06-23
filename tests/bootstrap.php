<?php

use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
ini_set('date.timezone',"Europe/Paris");
error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

/**
 * Test bootstrap, for setting up autoloading
*/
class bootstrap
{
    protected static $serviceManager;

    public static function init()
    {
    	
    	
    	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    	 
    	if (session_status() == PHP_SESSION_NONE) {
    		session_start();
    	}
    	
    	static::initAutoloader();
    }

    public static function getServiceManager()
    {
        return static::$serviceManager;
    }

    protected static function initAutoloader()
    {
        $vendorPath = static::findParentPath('vendor');
        $zf2Path = $vendorPath . '/zendframework/zendframework/library/';
	
        require $vendorPath . "/autoload.php";

        include $zf2Path . '/Zend/Loader/AutoloaderFactory.php';
        Zend\Loader\AutoloaderFactory::factory(array(
            'Zend\Loader\StandardAutoloader' => array(
                'autoregister_zf' => true,
                'namespaces' => array(
                        'ModuleTest' => __DIR__ . '/Module',
                        'JsonRpcTest' => __DIR__ . '/JsonRpcClient',
                ),
            )
        ));

       $autolader = new Zend\Loader\StandardAutoloader(array(
                'namespaces' => array(
                        'Zend' => __DIR__ . '/ZendMock'
                )
        ));
        spl_autoload_register(array($autolader, 'autoload'),true,true);

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', include __DIR__ . '/config/application.config.php');
        $serviceManager->get('ModuleManager')->loadModules();
        static::$serviceManager = $serviceManager;
    }

    protected static function findParentPath($path)
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) return false;
            $previousDir = $dir;
        }

        return $dir . '/' . $path;
    }
}

Bootstrap::init();
