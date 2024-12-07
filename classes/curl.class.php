<?php
	/*-------------------------------------------------------------------------------
		Canvas - "Canvas PHP made easy."

		Developer: Patrick Stephens
		Email: pstephens2601@gmail.com
		Github Repository: https://github.com/pstephens2601/Canvas
		Creation Date: 1-8-2014
		Last Edit Date: 3-21-2014

		Class Notes - The cURL class can be used for creating cURL connections to 
		other sites.
	---------------------------------------------------------------------------------*/
	namespace canvas;
	
	class curl extends canvasObject 
	{

		public $error;
		private $ch;

		function __construct($url)
		{
			$this->ch = curl_init();
			curl_setopt($this->ch, CURLOPT_URL, $url);
		}

		function set_option($option, $value)
		{
			curl_setopt($this->ch, $option, $value);
		}

		public function execute()
		{
			if ($result = curl_exec($this->ch))
			{
				return $result;
			}
			else
			{
				$this->error = curl_error($this->ch);
			}
		}

		public function print_info() {
			print_r(curl_getinfo($this->ch));
		}

		function __destruct()
		{
			curl_close($this->ch);
		}
	}
?>