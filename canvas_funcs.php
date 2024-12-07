<?php
	/*-------------------------------------------------------------------------------
        Serenity - "Serene PHP made easy."

        Developer: Patrick Stephens
        Email: pstephens2601@gmail.com
        Github Repository: https://github.com/pstephens2601/Serenity
        Creation Date: 8-20-2013
        Last Edit Date: 5-2-2014

        File Notes - This file contains functions required for the application.  
        Most of these function are used to insert information into the layouts 
        and views, however, some are required for application setup in index.php.
    ---------------------------------------------------------------------------------*/
	//used to render the body of the page
	function view()
	{
		$num_args = func_num_args();

		if (VIEW != null)
		{
			if(file_exists(VIEW))
			{
				require_once(VIEW);
			}
			else
			{
				if (ENVIRONMENT == 'development')
				{
					echo "<div class=\"Serene_Error\">Serene Error (Stay Calm!): the defined view [" . VIEW . "] can not be found. Did you remember to create it?</div>";
				}
			}
		}
	}

	//grabs data provided by a model for use in a view
	function get_provide($key)
	{
		$provides = unserialize(PROVIDES);

		if (isset($provides[$key]))
		{
			return $provides[$key];
		}
		else
		{
			if (defined('VIEW_PROVIDE_' . strtoupper($key)))
			{
				$view_provide = unserialize(constant('VIEW_PROVIDE_' . strtoupper($key)));
				return $view_provide;
			}
			else
			{
				return false;
			}
		}
	}

	//used to
	function render($key)
	{
		$provides = unserialize(PROVIDES);

		if (isset($provides[$key]))
		{
			if (is_array($provides[$key]))
			{
				if ((func_num_args() > 1) && (count($provides[$key]) != 0))
				{
					 $class = func_get_arg(1);
					 echo '<div class="' . $class . '">';
				}

				foreach ($provides[$key] as $value) {
					echo $value . '<br>';
				}

				if ((func_num_args() > 1) && (count($provides[$key]) != 0))
				{ 
					echo '</div>';
				}
			}
			else
			{
				echo $provides[$key];
			}
		}
	}

	function debug($message)
	{
		
	}

	//used to load all of the files in the stylesheets folder into your layout
	function load_css() {
		$css_files = scandir('app/assets/stylesheets');

		if (defined("CSS_PRELOADS"))
		{
			$load_priorities = unserialize(CSS_PRELOADS);
		
			foreach ($load_priorities as $css_file)
			{
				echo '<link rel="stylesheet" type="text/css" href="' . ROOT . 'app/assets/stylesheets/' . $css_file . "\">\n";
			}

			foreach ($css_files as $css) {
				if (($css != '.') && ($css != '..') && (!in_array($css, $load_priorities)))
				{
					$filename_split = explode(".", $css);

					if (isset($filename_split[1]) && $filename_split[1] == 'css') {
						echo '<link rel="stylesheet" type="text/css" href="' . ROOT . 'app/assets/stylesheets/' . $css . "\">\n";
					}
				}
			}
		}
		else
		{
			foreach ($css_files as $css) {
				if (($css != '.') && ($css != '..'))
				{
					echo '<link rel="stylesheet" type="text/css" href="' . ROOT . 'app/assets/stylesheets/' . $css . "\">\n";
				}
			}
		}
	}

	//used to load all of the files in the javascript folder into your layout
	function load_javascript() {
		
		$js_files = scandir('app/assets/javascript');

		if (defined("JS_PRELOADS") || defined("JS_EXCLUDES"))
		{
			
			$load_priorities = unserialize(JS_PRELOADS);
			$excludes = unserialize(JS_EXCLUDES);
			

			foreach ($load_priorities as $js_file)
			{
				echo '<script type="text/javascript" src="' . ROOT . 'app/assets/javascript/' . $js_file . "\"></script>\n";
			}

			foreach ($js_files as $js) {
				if (($js != '.') && ($js != '..') && (!in_array($js, $load_priorities)) && (!in_array($js, $excludes)))
				{
					echo '<script type="text/javascript" ';

					if (ASYNC_JS) {
						echo 'async ';
					}

					echo 'src="' . ROOT . 'app/assets/javascript/' . $js . "\"></script>\n";
				}
			}

			if (defined('JS_ADDS'))
			{
				$added_files = unserialize(JS_ADDS);

				foreach ($added_files as $js_file)
				{
					echo '<script type="text/javascript" ';

					if (ASYNC_JS) {
						echo 'async ';
					}
					
					if (is_array($js_file))
					{
						echo 'src="' . ROOT . $js_file[1] . '/' . $js_file[0] . "\"></script>\n";
					}
					else
					{
						echo 'src="' . ROOT . 'app/assets/javascript/' . $js_file . "\"></script>\n";
					}
				}
			}
		}
		else
		{
			foreach ($js_files as $js) {
				if (($js != '.') && ($js != '..'))
				{
					echo '<script type="text/javascript" src="' . ROOT . 'app/assets/javascript/' . $js . "\"></script>\n";
				}
			}
		}
	}

	function add_meta_tags()
	{
		$tags = unserialize(META_TAGS);

		if (count($tags) > 0 )
		{
			foreach ($tags as $tag)
			{
				$html = '<meta';

				foreach ($tag as $name => $value)
				{
					$html .= ' ' . $name . ' = "' . $value . '"';
				}
				$html .= ' />' . "\n\t";
				
				echo $html;
			}
		}
	}

	//Used to insert partials into a page
	function insert($partial) {
		$layouts = scandir('app/views/layouts');

		if (func_num_args() == 2)
		{
			$view_provides = func_get_arg(1);

			if (is_array($view_provides))
			{
				foreach ($view_provides as $key => $value) {
					define('VIEW_PROVIDE_' . strtoupper($partial) . ':' . strtoupper($key), serialize($value));
				}
			}
			else
			{
				throw new Exception("Invalid arguement provided to [insert()]. Function requires an array for argument 1.", 1);	
			}
		}

		if (in_array('_' . $partial . '.php', $layouts))
		{
			include('app/views/layouts/_' . $partial . '.php');
		}
		else
		{
			if (ENVIRONMENT == 'development')
			{
				echo "<div class=\"Serene_Error\">Serene Error (Stay Calm!): partial [_". $partial . ".php] not found.</div>";
			}
		}
	}

	function canvas_panel()
	{
		if (ENVIRONMENT == 'development' && ENABLE_CRITIQUES == true)
		{
			require_once('libs/phpcanvas-core/templates/canvas_panel.php');
		}
	}

	//used to create a link in a view or layout
	function link_to($path, $name)
	{
		
		$num_args = func_num_args();
		$print = true;
		$linkProtocol = substr($path, 0, 4);

		if ($num_args > 2)
		{
			if (func_get_arg(2) != false)
			{
				$arg_3 = func_get_arg(2);

				echo '<a href="';

				if ($linkProtocol != 'http')
				{
					 echo ROOT;
				}
				echo $path . '"';

				if (is_array($arg_3))
				{
					foreach ($arg_3 as $attribute => $value)
					{
						echo ' ' . $attribute . '="' . $value . '"';
					}
				}
				else
				{
					$class = func_get_arg(2);

					if (isset($class)) echo ' class="' . $class . '"';
					
				}

				echo '>' . $name . '</a>';
			}
			else
			{
				$html = '<a href="';
				if ($linkProtocol != 'http')
				{
					$html .= ROOT;
				}
				$html .= $path . '"';

				if (func_num_args() == 4 && is_array(func_get_arg(3)))
				{
					foreach (func_get_arg(3) as $param => $value) {
						$html .= ' ' . $param . '="' . $value . '"';
					}
				}
				
				$html .= '>' . $name . '</a>';

				return $html;
			}	
		}
		else
		{
			echo '<a href="';
			if ($linkProtocol != 'http')
			{
				echo ROOT;
			}
			echo $path . '">' . $name . '</a>';
		}
	}

	//converts an int between 1 and 12 to a month
	function num_to_month_abrv($number)
	{
		if ($number > 0 && $number < 13)
		{
			switch($number)
			{
				case 1:
					return 'Jan';
				case 2:
					return 'Feb';
				case 3:
					return 'Mar';
				case 4: 
					return 'Apr';
				case 5:
					return 'May';
				case 6:
					return 'Jun';
				case 7:
					return 'Jul';
				case 8:
					return 'Aug';
				case 9:
					return 'Sep';
				case 10:
					return 'Oct';
				case 11:
					return 'Nov';
				case 12:
					return 'Dec';
			}
		}
	}

	//converts an int between 1 and 12 to a month
	function num_to_month($number)
	{
		if ($number > 0 && $number < 13)
		{
			switch($number)
			{
				case 1:
					return 'January';
				case 2:
					return 'February';
				case 3:
					return 'March';
				case 4: 
					return 'April';
				case 5:
					return 'May';
				case 6:
					return 'June';
				case 7:
					return 'July';
				case 8:
					return 'August';
				case 9:
					return 'September';
				case 10:
					return 'October';
				case 11:
					return 'November';
				case 12:
					return 'December';
			}
		}
		else
		{
			throw new Exception('num_to_month() requires a value between 1 and 12');
		}
	}

	function days_in_month($number)
	{
		if ($number > 0 && $number < 13)
		{
			switch($number)
			{
				case 1:
					return 31;
				case 2:
					return 28;
				case 3:
					return 31;
				case 4: 
					return 30;
				case 5:
					return 31;
				case 6:
					return 30;
				case 7:
					return 31;
				case 8:
					return 31;
				case 9:
					return 30;
				case 10:
					return 31;
				case 11:
					return 30;
				case 12:
					return 31;
			}
		}
		else
		{
			throw new Exception('num_to_month() requires a value between 1 and 12');
		}
	}

	//used to insert an image into the page without naming the full path
	function img($file_name) {

		$num_args = func_num_args();

		if ($num_args < 2)
		{
			echo '<img src="' . ROOT . 'app/assets/images/' . $file_name . '" alt="">';
		}
		elseif ($num_args == 2)
		{
			$second_arg = func_get_arg(1);

			if (is_array($second_arg))
			{
				echo '<img src="' . ROOT . 'app/assets/images/' . $file_name . '"';

				foreach($second_arg as $key => $value)
				{
					echo ' ' . $key . '="' . $value . '"';
				}

				echo '>';
			}
			else
			{
				echo '<img src="' . ROOT . 'app/assets/images/' . $file_name . '" class="' . func_get_arg(1) . '" alt="">';
			}
		}
		elseif ($num_args == 3) {

			$second_arg = func_get_arg(1);
			$html = '';

			if (is_array($second_arg))
			{
				$html .= '<img src="' . ROOT . 'app/assets/images/' . $file_name . '"';

				foreach($second_arg as $key => $value)
				{
					$html .= ' ' . $key . '="' . $value . '"';
				}

				$html .= '>';
			}
			else
			{
				$html .= '<img src="' . ROOT . 'app/assets/images/' . $file_name . '" class="' . func_get_arg(1) . '" alt="">';
			}

			if (func_get_arg(2) == true) {
				echo $html;
			}
			else {
				return $html;
			}
		}
		else
		{	if (ENVIRONMENT == 'development')
			{
				echo "<div class=\"Serene_Error\">Serene Error (Stay Calm!): Invalid number of arguments passed to " . __METHOD__ . "() on line " . __LINE__ . "</div>";
			}
		}
	}

	//sets up the application for the current environment which can be set in config/canvas_config.php
	function setup_environment()
	{

		set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
	        if (error_reporting()) {
	            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	        }
		});

		if (ENVIRONMENT == 'development')
		{
			global $development;

			if (ERROR_REPORTING)
			{
				error_reporting(E_ALL);
				ini_set('error_reporting',E_ALL);
			}
			else
			{
				error_reporting(0);
				ini_set('error_reporting', 0);
			}
		}
		elseif (ENVIRONMENT == 'production')
		{
			error_reporting(0);
			ini_set('error_reporting', 0);
		}

		if (ERROR_LOGGING == true)
		{
			register_shutdown_function('error_logger');
		}
	}

	/*----------------------------------------------------------
		Then error_logger() function is used to define how 
		fatal errors are reported.  It does not provide a
		way to recover from fatal errors.
	-----------------------------------------------------------*/
	function error_logger()
	{
		$error = error_get_last();

		// if an error has been thrown
		if ($error !== NULL)
		{
			require_once('classes/canvasException.class.php');
			$e = new canvas\canvasException($error['message'], 0);
			$e->set_file($error['file']);
			$e->set_line($error['line']);
			
			if (ERROR_LOGGING)
			{
				if (VERBOSE_LOGGING)
				{
					$e->set_verbose();
				}

				$e->log();
			}

			if (ENVIRONMENT == 'development')
			{
				$canvas = new canvas\canvasObject;
				$canvas->print_exception($e);
			}
			else
			{
				exit();
			}
		}
	}

	//used to set load priority for javascript files that need to be loaded first.
	//should only be used in the canvas_config file.
	function js_preload($files)
	{
		$js_files = serialize($files);
		define("JS_PRELOADS", $js_files);
	}

	function js_exclude($files)
	{
		$js_files = serialize($files);
		define("JS_EXCLUDES", $js_files);
	}

	//used to set load priority for css files that need to be loaded first.
	//should only be used in the canvas_config file.
	function css_preload($files)
	{
		$css_files = serialize($files);
		define("CSS_PRELOADS", $css_files);
	}

	//Used to add third party pluggins in the pluggins' loader
	function add($file)
	{
		require('pluggins/' . $file);
	}

	function format_phone_number($phone)
    {
        $clean_phone_num = preg_replace("/[^0-9]/", '', $phone);

        if ($clean_phone_num > 0)
        {
	        $area_code = substr($clean_phone_num, 0, 3);
	        $prefix = substr($clean_phone_num, 3, 3);
	        $number = substr($clean_phone_num, 6);

	        return '(' . $area_code . ') ' . $prefix . '-' . $number;
	    }
	    else
	    {
	    	return '';
	    }  
    }

    function run_critiques()
    {
    	$tests = scandir('app/critiques');

    	foreach ($tests as $test)
    	{
    		if ($test != '.' && $test != '..')
    		{
    			$file_name = explode('.', $test);
    			$critique = new $file_name[0];
    		}
    	}
    	
    	define('CRITIQUE_RESULTS', serialize($critique->get_results()));
    }

    function get_date_time_components($date_time)
	{
		$date_array = explode(' ', $date_time);
		$date = explode('-', $date_array[0]);
		$time = explode(':', $date_array[1]);

		$date_time_array = array('month' => $date[1], 'day' => $date[2], 'year' => $date[0], 'hour' => $time[0], 'minute' => $time[1], 'second' => $time[2]);
		return $date_time_array;
	}
?>