<?php
	/*-------------------------------------------------------------------------------
        Serenity - "Serene PHP made easy."

        Developer: Patrick Stephens
        Email: patrick@patrickstephens.dev
        Github Repo sitory: https://github.com/pstephens2601/Serenity
        Creation Date: 8-20-2013
        Last Edit Date: 5-2-2014

        File Notes - This file handles all of the file loading for the application 
        except for javascript and css files, which must be loaded from the layout. 
        The include order for loading is defined in load_files(), however, it is 
        mandatory that 'config/canvas_config.php' is always loaded first
	 	as it defines constants required by the other config files.
    ---------------------------------------------------------------------------------*/
	//use canvas\libs\classes;
	
	function load_files()
	{
		function class_loader($class) {

			$class = explode('\\', $class);

			if (count($class) > 1)
			{
				$class_name = end($class);
			}
			else
			{
				$class_name = $class[0];
			}

			if (file_exists('libs/classes/' . $class_name . '.class.php'))
			{
				require_once('libs/classes/' . $class_name . '.class.php');
			}
			elseif (file_exists('app/controllers/' . $class_name . '.php'))
			{
				require_once('app/controllers/' . $class_name . '.php');
			}
			elseif (file_exists('app/models/' . $class_name . '.php'))
			{
				require_once('app/models/' . $class_name . '.php');
			}
			elseif (file_exists('app/critiques/' . $class_name . '.php'))
			{
				require_once('app/critiques/' . $class_name . '.php');
			}
			elseif (file_exists('app/sketches/' . $class_name . '.php'))
			{
				require_once('app/sketches/' . $class_name . '.php');
			}
			elseif (file_exists('app/extendables/' . $class_name . '.php'))
			{
				require_once('app/extendables/' . $class_name . '.php');
			}
			else
			{ 
				throw new Exception('Class [' . $class_name . '] not found.');
			}
		}

		spl_autoload_register('class_loader');

		load_file('config/environment_config.php');
		load_file('config/canvas_config.php');
		load_file('config/routes.php');
		load_file('canvas_funcs.php');
		load_dir('config');
		load_file('pluggins/loader.php');
	}

	/*-------------------------------------------------------------------------------
		This helper function is used to load entire directories. If a file in the 
		directory has already been loaded this function will not include it again.
	---------------------------------------------------------------------------------*/
	 
	function load_dir($directory)
	{
		$files = scandir($directory);

		foreach ($files as $file)
		{
			if (($file != '.') && ($file != '..') && ($file != ''))
			{
				$path = $directory . '/' . $file;
				require_once($path);
			}
		}
	}

	/*-------------------------------------------------------------------------------
		This helper function is used to load a single file. If the file has already
	 	been loaded this function will not include it again.
	---------------------------------------------------------------------------------*/
	function load_file($file)
	{
		require_once($file);
	}
?>