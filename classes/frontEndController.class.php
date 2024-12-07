<?php
	/*-------------------------------------------------------------------------------
		Serenity - "Serene PHP made easy."

		Developer: Patrick Stephens
		Email: pstephens2601@gmail.com
		Github Repository: https://github.com/pstephens2601/Serenity
		Creation Date: 8-20-2013
		Last Edit Date: 3-21-2014

		Class Notes - The front End Controller is responsible for all of the 
		application routing.
	---------------------------------------------------------------------------------*/
	namespace canvas;
	
	class frontEndController extends canvasObject
	{
		var $path;
		var $controllers;

		function __construct()
		{
			//if the controller or action are not set
			if ((!isset($_GET['controller'])) || (!isset($_GET['action'])))
			{
				//ROOT_PATH is defined in funcs.php
				$this->path = ROOT_PATH;
			}
		}

		function follow_path()
		{
			if ($this->path == ROOT_PATH)
			{
				/* array format = 
				 * Array ( [0] => Array ( [0] => controller [1] => action ) [1] =>
				 * app/views/controller/action.php )
				 */

				$path = unserialize(ROOT_PATH);

				$controller = $path[0];
				$action = $path[1];
				
				if ((class_exists($controller)) && (method_exists($controller, $action)))
				{
					try
					{
						$active_controller = new $controller($action);
						unset($active_controller);
					}
					catch (\Exception $e)
					{

						if (is_a($e, 'canvas\canvasException') && APP_ERROR_LOGGING)
						{
							if (APP_VERBOSE_LOGGING)
							{
								$e->set_verbose();
							}
							
							$e->log();
						}

						if (ENVIRONMENT == 'development')
						{
							$this->print_exception($e);
						}
						
					}
				}
				else
				{
					if (ENVIRONMENT == 'development')
					{
						$message = "Canvas Error: ROOT_PATH contains invalid values (Controller [$controller] or Action [$action] not found.  Please check to make sure both exist and are named correctly.)";
						$this->print_error($message);	
					}
					else
					{
						die();
					}
				}
			}
			else
			{
				$this->call($_GET['controller'], $_GET['action']);
			}
		}

		private function call($controller, $action)
		{
			if ((class_exists($controller)) && (method_exists($controller, $action)))
			{
				try
				{
					$active_controller = new $controller($action);
				}
				catch (\Exception $e)
				{
					if (is_a($e, 'canvas\canvasException') && APP_ERROR_LOGGING)
					{
						if (APP_VERBOSE_LOGGING)
						{
							$e->set_verbose();
						}

						$e->log();
					}

					if (ENVIRONMENT == 'development')
					{
						$this->print_exception($e);
					}
				}
			}
			else
			{
				if (ENVIRONMENT == 'development')
				{

					$message = "Canvas Error: Controller [$controller] or Action [$action] not found.  Please check to make sure both exist and are named correctly.";
					$this->print_error($message);
					
				}
				else
				{
					if ($this->path != ROOT_PATH) {
						header('Location: ../index.php');
					}
					else
					{
						die();
					}
				}
			}
		}
	}
?>