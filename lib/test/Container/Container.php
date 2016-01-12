<?php
namespace Test;

use Test\ClassInspector;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

class Container
{
    protected $hints = [];

	protected $aliases = [];

	protected $instances = [];

	protected function parseHint($hint)
	{
		if(is_array($hint))
		{
			list($name, $alias) = $hint;

			$this->aliases[$alias] = $name;
		}
		else
		{
			$name = $hint;

			$alias = null;
		}

		return compact('name', 'alias');
	}

	public function register($hint, $class, $singleton = false)
	{
		$hint = $this->parseHint($hint);

		$this->hints[$hint['name']] = ['class' => $class, 'singleton' => $singleton, 'alias' => $hint['alias']];
	}

	public function registerSingleton($hint, $class)
	{
		$this->register($hint, $class, true);
	}

    public function registerInstance($hint, $instance)
	{
		$hint = $this->parseHint($hint);

		$this->instances[$hint['name']] = $instance;
	}

	protected function resolveAlias($alias)
	{
		$alias = ltrim($alias, '\\');

		return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
	}

	protected function resolveHint($hint)
	{
		return isset($this->hints[$hint]) ? $this->hints[$hint]['class'] : $hint;
	}

	protected function mergeParameters(array $reflectionParameters, array $providedParameters)
	{
		// Make the provided parameter array associative

		$associativeProvidedParameters = [];

		foreach($providedParameters as $key => $value)
		{
			if(is_numeric($key))
			{
				$associativeProvidedParameters[$reflectionParameters[$key]->getName()] = $value;
			}
			else
			{
				$associativeProvidedParameters[$key] = $value;
			}
		}

		// Make reflection parameter array associative

		$associativeReflectionParameters = [];

		foreach($reflectionParameters as $key => $value)
		{
			$associativeReflectionParameters[$value->getName()] = $value;
		}

		// Return merged parameters

		return array_replace($associativeReflectionParameters, $associativeProvidedParameters);
	}

	protected function getDeclaringFunction(ReflectionParameter $parameter)
	{
		$declaringFunction = $parameter->getDeclaringFunction();

		if($declaringFunction->isClosure())
		{
			return 'Closure';
		}

		return $parameter->getDeclaringClass()->getName() . '::' . $declaringFunction->getName();
	}

	protected function resolveParameter(ReflectionParameter $parameter)
	{
		if(($parameterClass = $parameter->getClass()) !== null)
		{
			// The parameter should be a class instance. Try to resolve it though the container

			return $this->get($parameterClass->getName());
		}

		if($parameter->isDefaultValueAvailable())
		{
			// The parameter has a default value so we'll use that

			return $parameter->getDefaultValue();
		}

		// We have exhausted all our options. All we can do now is throw an exception

		//throw new RuntimeException(vsprintf("%s(): Unable to resolve the [ $%s ] parameter of [ %s ].", [__METHOD__, $parameter->getName(), $this->getDeclaringFunction($parameter)]));
	}

	protected function resolveParameters(array $reflectionParameters, array $providedParameters)
	{
		if(empty($reflectionParameters))
		{
			return $providedParameters;
		}

		// Merge provided parameters with the ones we got using reflection

		$parameters = $this->mergeParameters($reflectionParameters, $providedParameters);

		// Loop through the parameters and resolve the ones that need resolving

		foreach($parameters as $key => $parameter)
		{
			if($parameter instanceof ReflectionParameter)
			{
				$parameters[$key] = $this->resolveParameter($parameter);
			}
		}

		// Return resolved parameters

		return $parameters;
	}

	protected function isContainerAware($class)
	{
		$traits = ClassInspector::getTraits($class);

		return isset($traits['Test\ContainerAwareTrait']);
	}

	protected function closureFactory(Closure $factory, array $parameters)
	{
		// Pass the container as the first parameter followed by the the provided parameters

		$instance = call_user_func_array($factory, array_merge([$this], $parameters));

		// Check that the factory closure returned an object

		if(is_object($instance) === false)
		{
			throw new RuntimeException(vsprintf("%s(): The factory closure must return an object.", [__METHOD__]));
		}

		return $instance;
	}

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

	protected function factory($class, array $parameters = [])
	{
		// Instantiate class
        
		if($class instanceof Closure)
		{
			$instance = $this->closureFactory($class, $parameters);
		}
		else
		{
			$instance = $this->reflectionFactory($class, $parameters);
		}

		// Inject container using setter if the class is container aware

		if($this->isContainerAware($instance))
		{
			$instance->setContainer($this);
		}

		// Return the instance
		return $instance;
	}

    public function has($class)
	{
		$class = $this->resolveAlias($class);

		return (isset($this->hints[$class]) || isset($this->instances[$class]));
	}

	public function get($class, array $parameters = [], $reuseInstance = true)
	{
        
		$class = $this->resolveAlias($class);

		// If a singleton instance exists then we'll just return it

		if($reuseInstance && isset($this->instances[$class]))
		{
			return $this->instances[$class];
		}

		// Create new instance

		$instance = $this->factory($this->resolveHint($class), $parameters);

		// Store the instance if its registered as a singleton

		if($reuseInstance && isset($this->hints[$class]) && $this->hints[$class]['singleton'])
		{
			$this->instances[$class] = $instance;
		}

		// Return the instance

		return $instance;
	}

    public function getFresh($class, array $parameters = [])
	{
		return $this->get($class, $parameters, false);
	}

    public function call($callable, array $parameters = [])
	{
		if($callable instanceof Closure)
		{
			$reflection = new ReflectionFunction($callable);
		}
		else
		{
			$reflection = new ReflectionMethod($callable[0], $callable[1]);
		}

		return call_user_func_array($callable, $this->resolveParameters($reflection->getParameters(), $parameters));
	}

}