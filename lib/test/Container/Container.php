<?php
namespace Test;

use Closure;
use ReflectionClass;

class Container
{
    protected $params = [];
    
    protected $aliases = [];
    
    protected $instances = [];
    
    public function registerInstance($param, $instance)
    {
        $param = $this->parseParam($param);
        $this->instances[$param['name']] = $instance;
    }
    
    public function parseParam($param)
    {
        if(is_array($param))
        {
            list($name, $alias) = $param;
            $this->aliases[$alias] = $name;
        }
        else
        {
            $name = $param;
            $alias = null;
        }
        
        return compact('name', 'alias');
    }
    
    public function get($class, array $parameters = [], $reuseInstance = true)
    {
        $class = $this->resolveAlias($class);
        
        $instance = $this->factory($this->resolveParam($class), $parameters);
        
        $this->instances[$class] = $instance;
        
        return $instance;
    }
    
    public function resolveAlias($alias)
    {
        $alias = $this->aliases[$alias];
        return $alias;
    }
    
    /**
     * [[Description]]
     */
    public function resolveParam($param)
    {
        var_dump($this->params[$param]);
        return isset($this->params[$param]) ? $this->params[$param]['class'] : $param;
    }
    
    protected function factory($class, array $parameters = [])
    {
        //var_dump($class);
        /*if($class instanceof Closure)
		{
			$instance = $this->closureFactory($class, $parameters);
		}
		else
		{
			$instance = $this->reflectionFactory($class, $parameters);
		}*/
        //$instance = $this->closureFactory($class, $parameters);
        $instance = $this->setContainer($this);

        //$instance = $this->closureFactory($class, $parameters);
        return $instance;
    }
    
    public function setContainer(Container $container)
	{
		$this->container = $container;
	}
    
    public function register($param, $class, $singleton = false)
	{
		$paramP = $this->parseParam($param);
		$this->params[$paramP['name']] = ['class' => $class, 'singleton' => $singleton, 'alias' => $paramP['alias']];
	}
    
    public function registerSingleton($hint, $class)
	{
		$this->register($hint, $class, true);
	}
    
    protected function closureFactory(Closure $factory, array $parameters)
	{
		$instance = call_user_func_array($factory, array_merge([$this], $parameters));
		// Check that the factory closure returned an object
		/*if(is_object($instance) === false)
		{
			throw new RuntimeException(vsprintf("%s(): The factory closure must return an object.", [__METHOD__]));
		}*/
		return $instance;
	}

	/**
	 * Creates a class instance using reflection.
	 *
	 * @access  public
	 * @param   string  $class       Class name or closure
	 * @param   array   $parameters  Constructor parameters
	 * @return  object
	 */

	protected function reflectionFactory($class, array $parameters)
	{
		$class = new ReflectionClass($class);

		// Check that it's possible to instantiate the class

		if(!$class->isInstantiable())
		{
			throw new RuntimeException(vsprintf("%s(): Unable create a [ %s ] instance.", [__METHOD__, $class->getName()]));
		}

		// Get the class constructor

		$constructor = $class->getConstructor();

		if($constructor === null)
		{
			// No constructor has been defined so we'll just return a new instance

			$instance = $class->newInstance();
		}
		else
		{
			// The class has a constructor. Lets get its parameters.

			$constructorParameters = $constructor->getParameters();

			// Create and return a new instance using our resolved parameters

			$instance = $class->newInstanceArgs($this->resolveParameters($constructorParameters, $parameters));
		}

		return $instance;
	}

}