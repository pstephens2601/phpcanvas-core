<?php
	namespace canvas;
	
	class sketch extends canvasObject
	{
		private $structure;

		function set_structure($new_structure)
		{
			$this->structure = $new_structure;
		}

		function get_structure()
		{
			return $this->structure;
		}
	}
?>