<?php
	namespace canvas;

	class mockMethod extends canvasObject
	{
		private $name;
		private $return_value;

		function __construct($name, $action, $value)
		{		
			$this->name = $name;

			if ($action == 'returns')
			{
				$this->return_value = $value;
			}
		}

		function get_name()
		{
			return $this->name;
		}

		function get_return_value()
		{
			return $this->return_value;
		}
	}
?>