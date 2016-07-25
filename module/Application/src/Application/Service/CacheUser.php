<?php
/**
 * 
 * TheStudnet (http://thestudnet.com)
 *
 * Cache User
 *
 */
namespace Application\Service;

/**
 * Class CacheUser
 */
class CacheUser implements ServiceLocatorAwareInterface
{

    protected $serviceLocator;

    protected $prefix = 'identity_';

    protected $key_local = 'token';

    protected $key_global = 'id';

    /**
     * Save data Local user session
     *
     * @param mixed $data            
     * @return bool
     */
    public function saveLocal($data)
    {
        $identity = $this->getServiceAuth()->getIdentity();
        
        $this->getCache()->setItem($this->prefix . $identity[$this->key_local], $data);
    }

    /**
     * Save data Global user
     *
     * @param mixed $data            
     * @return bool
     */
    public function save($data)
    {
        $identity = $this->getServiceAuth()->getIdentity();
        
        $this->getCache()->setItem($this->prefix . $identity[$this->key_global], $data);
    }

    /**
     * Get data Local user
     *
     * @return mixed
     */
    public function getLocal()
    {
        $identity = $this->getServiceAuth()->getIdentity();
        
        return ($this->getCache()->hasItem($this->prefix . $identity[$this->key_local])) ? $this->getCache()->getItem($this->prefix . $identity[$this->key_local]) : false;
    }

    /**
     * Get data Global user
     *
     * @return mixed
     */
    public function get()
    {
        $identity = $this->getServiceAuth()->getIdentity();
        
        return ($this->getCache()->hasItem($this->prefix . $identity[$this->key_global])) ? $this->getCache()->getItem($this->prefix . $identity[$this->key_global]) : false;
    }

    /**
     * Get Storage if define.
     *
     * @return \Zend\Cache\Storage\StorageInterface
     */
    public function getCache()
    {
        $config = $this->getServiceLocator()->get('config')['app-conf'];
        
        return $this->getServiceLocator()->get($config['cache']);
    }

    /**
     * Set Service Locator
     *
     * @param ServiceLocatorInterface $serviceLocator            
     * @return \Application\Service\CacheUser
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        
        return $this;
    }

    /**
     * Get ServiceLocator
     *
     * @return \Application\Service\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Get Service AuthenticationService
     *
     * @return \Zend\Authentication\AuthenticationService
     */
    private function getServiceAuth()
    {
        return $this->getServiceLocator()->get('auth.service');
    }
}
