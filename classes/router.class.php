<?php
	/*-------------------------------------------------------------------------------
        Serenity - "Serene PHP made easy."

        Developer: Patrick Stephens
        Email: pstephens2601@gmail.com
        Github Repository: https://github.com/pstephens2601/Serenity
        Creation Date: 8-20-2013
        Last Edit Date: 3-21-2014

        Class Notes - The router handles reroute requests that are set in 
        config/routes.php.
    ---------------------------------------------------------------------------------*/
    namespace canvas;
    
	class router extends canvasObject
	{

		private $path_url;
		private $path_view;
		private $action;
		private $controller;
		private $root_controller;
		private $root_action;

		function root_to($view)
		{
			if (strpos($view, '#') > 0)
			{
				$path = explode('#', $view);
				$this->root_controller = $path[0];
				$this->root_action = $path[1];
				define("ROOT_PATH", serialize(array($this->root_controller, $this->root_action)));
			}
			else
			{
				define("ROOT_PATH", 'app/views/pages/' . $view . '.php');
			}
		}
	}
?>