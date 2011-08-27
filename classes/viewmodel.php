<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;



// ------------------------------------------------------------------------

/**
 * ViewModel
 *
 * @package	    Fuel
 * @subpackage  Core
 * @category    Core
 * @author      Jelmer Schreuder
 */
abstract class ViewModel {

	/**
	 * This method is deprecated...use forge() instead.
	 *
	 * @deprecated until 1.2
	 */
	public static function factory($viewmodel, $method = 'view')
	{
		\Log::warning('This method is deprecated.  Please use a forge() instead.', __METHOD__);
		return static::forge($viewmodel, $method);
	}

	/**
	 * Factory for fetching the ViewModel
	 *
	 * @param   string  ViewModel classname without View_ prefix or full classname
	 * @param   string  Method to execute
	 * @return  ViewModel
	 */
	public static function forge($viewmodel, $method = 'view', $auto_filter = null)
	{
		$class = ucfirst(\Request::active()->module).'\\View_'.ucfirst(str_replace(DS, '_', $viewmodel));

		if ( ! class_exists($class))
		{
			if ( ! class_exists($class = $viewmodel))
			{
				throw new \OutOfBoundsException('ViewModel "View_'.ucfirst(str_replace(DS, '_', $viewmodel)).'" could not be found.');
			}
		}

		return new $class($method, $auto_filter);
	}

	/**
	 * @var  string  method to execute when rendering
	 */
	protected $_method;

	/**
	 * @var  string|View  view name, after instantiation a View object
	 */
	protected $_template;

	/**
	 * @var  bool  whether or not to use auto filtering
	 */
	protected $auto_filter;

	protected function __construct($method, $auto_filter = null)
	{
		if (empty($this->_template))
		{
			$class = get_class($this);
			$this->_template = strtolower(str_replace('_', '/', preg_replace('#^([a-z0-9_]*\\\\)?(View_)?#i', '', $class)));
		}

		$this->set_template();
		$this->_method = $method;
		$this->auto_filter = is_null($auto_filter) ? \Config::get('security.auto_filter_view_data', \Config::get('security.auto_encode_view_data', true)) : $auto_filter;

		$this->before();

		// Set this as the controller output if this is the first ViewModel loaded
		if (empty(\Request::active()->controller_instance->response->body))
		{
			\Request::active()->controller_instance->response->body = $this;
		}
	}

	/**
	 * Must return a View object or something compatible
	 *
	 * @return	Object	any object on which the template vars can be set and which has a toString method
	 */
	protected function set_template()
	{
		$this->_template = \View::forge($this->_template);
	}

	/**
	 * Change auto filter setting
	 *
	 * @param   null|bool  change setting (bool) or get the current setting (null)
	 * @return  void|bool  returns current setting or nothing when it is changed
	 */
	public function auto_filter($setting = null)
	{
		if (func_num_args() == 0)
		{
			return $this->auto_filter;
		}

		$this->auto_filter = (bool) $setting;

		return $this;
	}

	/**
	 * Executed before the view method
	 */
	public function before() {}

	/**
	 * The default view method
	 * Should set all expected variables upon itself
	 */
	public function view() {}

	/**
	 * Executed after the view method
	 */
	public function after() {}

	/**
	 * Fetches an existing value from the template
	 *
	 * @return	mixed
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * Gets a variable from the template
	 *
	 * @param	string
	 */
	public function get($name)
	{
		return $this->_template->{$name};
	}

	/**
	 * Sets and sanitizes a variable on the template
	 *
	 * @param	string
	 * @param	mixed
	 */
	public function __set($name, $val)
	{
		return $this->set($name, $val);
	}

	/**
	 * Sets a variable on the template
	 *
	 * @param	string
	 * @param	mixed
	 * @param	bool|null
	 */
	public function set($name, $val, $filter = null)
	{
		$this->_template->set($name, $val, $filter);

		return $this;
	}

	/**
	 * Add variables through method and after() and create template as a string
	 */
	public function render()
	{
		$this->{$this->_method}();
		$this->after();

		return (string) $this->_template;
	}

	/**
	 * Auto-render on toString
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (\Exception $e)
		{
			\Error::exception_handler($e);

			return '';
		}
	}
}


