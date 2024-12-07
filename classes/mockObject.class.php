<?php
	namespace canvas;

	class mockObject extends canvasObject
	{
		private $mock_methods = array();
		private $class_name;

		function __construct($class_name)
		{		
			$this->class_name = $class_name;

			if (func_num_args() == 2)
			{
				$methods = func_get_arg(1);

				if (is_array($methods))
				{
					foreach ($methods as $method) {
						if (is_object($method) && get_class($method) == 'canvas\mockMethod')
						{	
							$this->mock_methods[$method->get_name()] = $method;
						}
						else
						{
							$property_name = $method->get_name();
							$this->$property_name = $method->get_value();
						}
					}
				}
				else
				{
					$this->mock_methods[$methods->get_name()] = $methods;
				}
			}
		}

		function add_method($method)
		{
			$mock_methods[] = array($method);
		}

		function __call($method, $arg)
		{
			if (array_key_exists($method, $this->mock_methods))
			{
				return $this->mock_methods[$method]->get_return_value();
			}
			else
			{
				throw new \Exception('Invalid mock method [ ' . $method . ' ] called.');	
			}
		}

		function get_name()
		{
			return $this->class_name;
		}
	}
?>