<?php
	namespace canvas;

	class critique extends canvasObject
	{
		protected static $number_of_tests = 0;
		protected static $errors = array();
		protected $subject;
		protected $if;
		protected $elseif;
		protected $tests;

		function __construct()
		{	
			$subject_name = substr(get_class($this), 0, -9);

			if (class_exists($subject_name))
			{
				$this->subject = new $subject_name(true);
			}
			else
			{
				self::$errors[] = array($subject_name, 'Class Not Found');
				self::$number_of_tests ++;
			}
			
			$child_methods = get_class_methods($this);
			$parent_methods = get_class_methods(get_parent_class($this));
			$this->tests = array_diff($child_methods, $parent_methods);

			foreach ($this->tests as $test) {
				try
				{
					$this->$test();
					$this->subject->clear_test_data();
				}
				catch (\Exception $e)
				{
					if (ENVIRONMENT == 'development')
					{
						$this->print_exception($e);
					}
				}
			}
		}

		function __call($method, $args)
		{
			throw new \Exception('Invalid method [ ' . $method . ' ] called by [ '  . get_class($this) . ' ]');	
		}

		function get_results()
		{
			return array(self::$number_of_tests, self::$errors);
		}

		protected function when($property)
		{
			$this->subject->clear_test_data();

			if (func_num_args() === 3)
			{
				$this->subject->set($property, func_get_arg(1), func_get_arg(2));
			}
			else
			{
				$this->subject->add_mock_object($property);
			}
		}

		protected function and_also($property)
		{
			if (func_num_args() === 3)
			{
				$this->subject->set($property, func_get_arg(1), func_get_arg(2));
			}
			else
			{
				$this->subject->add_mock_object($property);
			}
		}

		protected function assume_no_exceptions()
		{
			if (is_object($this->subject))
			{
				$method = $this->get_method_name();

				if (method_exists($this->subject, $method))
				{
					try
					{
						$this->subject->$method();
					}
					catch (\Exception $e)
					{
						self::$errors[] = array(get_class($this->subject), $method, 'Assume No Exceptions: Exception Caught => ' . $e->getMessage() . ' on [ ' . $e->getFile() . ' > Line ' . $e->getLine() . ' ]');
					}

					self::$number_of_tests ++;
				}
				else
				{
					throw new \Exception('Invalid test name [ ' . $method . ' ] declared in critique [ ' . get_class($this) . ' ]');
				}
			}
		}

		protected function assume_provides($test_provides)
		{
			if (is_object($this->subject))
			{
				if (is_array($test_provides))
				{
					$method = $this->get_method_name();

					self::$number_of_tests += count($test_provides);

					try
					{
						$this->subject->call($method);
					}
					catch (\Exception $e)
					{
						self::$errors[] = array(get_class($this->subject), $method, 'Assume Provides : Exception Thrown << ' . $e->getMessage() . ' >> ');
					}

					if (isset($_SESSION[get_class($this->subject) . '_provides']))
					{
						$provides = $_SESSION[get_class($this->subject) . '_provides'];

						$provide_names = array();

						foreach ($provides as $key => $value) {
							$provide_names[] = $key;
						}

						$missing_provides = array();
						$missing_provides = array_diff($test_provides, $provide_names);

						foreach ($missing_provides as $provide) {
							self::$errors[] = array(get_class($this->subject), $method, 'Assume Provides: Missing Provide - "' . $provide . '"');
						}
					}
					else
					{
						self::$errors[] = array(get_class($this->subject), $method, 'Assume Provides : NO PROVIDES FOUND!');
					}
				}
				else
				{
					throw new \Exception('assume_provides() expects Argument # 1 to be an array');
				}
			}
		}

		protected function assume_return_type($data_type)
		{
			if (is_object($this->subject))
			{
				$method = $this->get_method_name();

				switch ($data_type)
				{ 
					case 'boolean':
						if (!is_bool($value))
						{
							self::$errors[] = array(get_class($this->subject), $value, 'Assume Returns ( ' . ucwords($data_type) . ' ): Returned ( ' . gettype($value) . ' )');
						}
						break;
					case 'int':
						if (!is_int($value))
						{
							self::$errors[] = array(get_class($this->subject, $action), 'Assume Returns Type ( ' . ucwords($data_type) . ' ): Returned ( ' . gettype($value) . ' )');
						}
						break;
				}

				self::$number_of_tests ++;
			}
		}

		protected function assume_redirect($location)
		{	
			if (is_object($this->subject))
			{
				$method = $this->get_method_name();
				$this->subject->$method();

				$redirect_location = $this->subject->get_redirect();

				if ($redirect_location != $location)
				{
					if ($redirect_location != null)
					{	
						self::$errors[] = array(get_class($this->subject), $method, 'Assume Redirect: ( ' . $location . ' ) > Redirected To: ( ' . $redirect_location . ' )');
					}
					else
					{
						self::$errors[] = array(get_class($this->subject), $method, 'Assume Redirect: ( ' . $location . ' ) > No Redirect Found');
					}
				}
			}
		}

		protected function assume_return_value($switch, $expected_value)
		{
			if (is_object($this->subject))
			{
				$method = $this->get_method_name();
				$value = $this->subject->$method();

				if ($value != $expected_value)
				{
					if ($value === true || $value === false)
					{
						$value = ($value) ? 'true' : 'false';
					}

					if ($expected_value === true || $expected_value === false)
					{
						$expected_value = ($expected_value) ? 'true' : 'false';
					}

					self::$errors[] = array(get_class($this->subject), $method, 'Assume Returns Value ( ' . $expected_value . ' ): Returned ( ' . $value . ' )');
				}

				self::$number_of_tests ++;
			}
		}

		protected function object($class_name, $methods) 
		{
			$object = new mockObject($class_name, $methods);
			return $object;
		}

		protected function method($method_name, $action, $value)
		{
			$method = new mockMethod($method_name, $action, $value);
			return $method;
		}

		protected function property($property_name, $construct, $value)
		{
			$property = new mockProperty($property_name, $construct, $value);
			return $property;
		}

		private function get_method_name()
		{
			$call_stack = debug_backtrace();

			$method = explode('_', $call_stack[2]['function']);

			if ($method[0] == 'analyze')
			{
				array_shift($method);
				$method = implode('_', $method);
				return $method;
			}
			else
			{
				throw new \Exception('Invalid test name [ ' . implode('_', $method) . ' ] declared in critique [ ' . get_class($this) . ' ]');
			}
		}
	}
?>