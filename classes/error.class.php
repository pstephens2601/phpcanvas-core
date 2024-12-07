<?php
	namespace canvas;

	class error extends canvasObject
	{
		private $error_code;
		private $message;

		function set_error($error_code)
		{
			switch ($error_code)
			{
				case 1:
					$this->error_code = 1;
					$this->set_message('Error Code (' . $this->error_code . '), Unable to locate file that corresponds to required class [' . func_get_arg(1) . '].');
					break;
				case 2:
					$this->error_code = 2;
					$this->set_message('Error Code (' . $this->error_code . '), Database Error --- ' . func_get_arg(1) . ' ---.');
					break;
				case 3:
					$this->error_code = 3;
					$this->set_message('Error Code (' . $this->error_code . '), Invalid parameter error. [' . func_get_arg(1) . '].');
					break;
			}
		}

		function output_message()
		{
			$this->print_error($this->message);
		}

		private function set_message($message) {
			$complete_message = 'Serene Error (Stay Calm!): ';
			$complete_message .= $message;
			$this->message = $complete_message;
		}
	}
?>