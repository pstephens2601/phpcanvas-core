<?php
	namespace canvas;

	class mockProperty extends canvasObject
	{
		private $name;
		private $value;

		function __construct($name, $construct, $value)
		{
			$this->name = $name;
			$this->value = $value;
		}

		function get_name()
		{
			return $this->name;
		}

		function get_value()
		{
			return $this->value;
		}
	}
?>		