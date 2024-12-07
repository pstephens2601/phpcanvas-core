<?php
	namespace canvas;

	/**
	 *	Parent class for all controllers within the Canvas framework.
	 *
	 *	The baseController class is used to build all other controllers when using the canvas framework.  If not extending the
	 *	the baseController directly a controller must extend a child of the baseController class.
	 *
	 *	@author Patrick Stephens
	 *	@version 0.1.0
	 *	@package Canvas
	 */

	class baseController extends canvasObject 
	{
		/**
		 * @var bool $form_submit Set to true if a form has been submitted, false if one has not.
		 */
		public $form_submit = false;

		/**
		 * @var mixed[] $params Array containing all of the $_GET and $_POST variables passed to the server when the controller was called.
		 */
		public $params = array();

		protected $provides = array();
		protected $view;
		protected $layout = 'layout';
		protected $get_id;
		protected $ajax_request = false;
		protected $script_only = false;
		protected $messages = array();

		private $form_name = null;
		private $download = false;
		private $listening_for = null;
		private $js_adds = array();
		private $meta_tags = array();
		private $action;

		/**
		 *	Prepares the controller for use and calls the action.
		 *	
		 *	Readies the controller for use by getting the name of the action called, declaring a view to use, checking for form submission,
		 *	and gathering $_POST and $_GET variables and passing them to the params array.  Once the controller is ready it then calls the
		 *	method representing the action.
		 *	
		 *	@internal
		 *	@param string $action The name of the action being called.
		 *	@return null
		 *	@throws canvasException Catches any exceptions thrown by the action and passes them along to the front-end-controller
		 */
		function __construct($action)
		{
			$this->check_form_submission();
			$this->get_params();
			
			if (is_bool($action) && $action == true)
			{
				$this->test = true;
			}
			else
			{
				try
				{
					$this->action = $action;
					$this->view = 'app/views/' . get_class($this) . '/' . $action . '.php';
					$this->$action();
				}
				catch (\Exception $e)
				{
					throw $e;	
				}
			}
			
			$this->defineController();
		}

		/**
		 *	Listens for form submissions from other classes.
		 *
		 *	Takes a class name as an argument and forces the controller to listen for form submissions from forms that may be on pages
		 *	managed by another controller. Unless listen_for is used a controller will not 
		 *
		 *  @param string $class The class name of the controller submitting the form.
		 */
		function listen_for($class)
		{
			$this->check_form_submission($class);
		}

		/**
		 *	Notifies the controller that it is being called by an AJAX request.
		 *
		 *	Calling the ajax() method in an action will configure the controller to respond to an AJAX request.
		 *	This will prevent the controller from displaying a view.
		 *	
		 *	@api
		 *	@return null
		 */
		function ajax()
		{
			$this->ajax_request = true;

			echo "Canvas: Your ajax request has been processed. To customize the response create an ajax function in your ";
			echo get_class($this) . " controller. Don't forget to set the ajax_request param to true.";
		}

		/**
		 *	Inserts a normally excluded javascript file into the view being displayed.
		 *
		 *	If called this method will include a javascript file that has been excluded in the App config files.  This is perfect for including
		 *	javascript files that are only needed on a specific page.
		 *	
		 *	@api
		 *	@param string $file_name This is the name of the javascript file to be included.
		 *	@return null
		 */
		function add_javascript($file_name)
		{
			$num_args = func_num_args();

			if ($num_args == 2)
			{
				$this->js_adds[] = array($file_name, func_get_arg(1));
			}
			else
			{
				$this->js_adds[] = $file_name;
			}

			if ($this->test)
			{
				if (!defined('TEST_' . strtoupper(get_class($this)) . '_' . $this->action . '_JS_ADDS'))
				{
					define('TEST_' . strtoupper(get_class($this)) . '_' . $this->action . '_JS_ADDS', $js_files);
				}
			}
		}

		/**
		 *	Adds a meta tag to the displayed view.
		 *	
		 *	this function is used to add additional meta tags to the page being displayed. Tags should be in
		 *	the form of an associative array with the properties forming name value pairs. ex. array('title' => 'tag title').
		 */
		function add_meta_tag($tag)
		{
			$this->meta_tags[] = $tag;
		}

		// Used to redirect user to another page.
		function go_to($location)
		{
			if (!$this->test)
			{
				if ($location == 'root')
				{
					$location == '';
				}

				header('Location:' . ROOT . $location);
				exit();
			}
			else
			{
				if ($location == '' || $location == null)
				{
					$location = 'ROOT';
				}

				$this->redirect = $location;
			}
		}

		function set_ajax()
		{
			$this->ajax_request = true;
			$this->script_only = true;
		}

		function set_script_only()
		{
			$this->script_only = true;
		}

		function display_view($display)
		{
			if (!$display)
			{
				$this->script_only = true;
			}
		}

		function get_redirect()
		{
			return $this->redirect;
		}

		function call($action)
		{
			if ($this->test)
			{
				try
				{
					$this->action = $action;
					$this->$action();
					$_SESSION[get_class($this) . '_provides'] = $this->provides;
					$this->provides = array();
				}
				catch (\Exception $e)
				{
					throw $e;
				}
			}
		}

		function pass_form_data()
		{
			return $this->post;
		}

		function form_name()
		{
			return $this->form_name;
		}

		/*-------------------------------------------------------------------
			Redirects the user to another page.  Can only be used before
			any output is written to the browser.
		--------------------------------------------------------------------*/
		function to_page($url)
		{
			header("Location: " . ROOT . $url);
		}

		protected function provide($key, $value)
		{
			$this->provides[$key] = $value;
		}

		protected function set_layout($layout_name)
		{
			$this->layout = $layout_name;
		}

		protected function set_view($view)
		{
			$this->view = 'app/views/' . get_class($this) . '/' . $view . '.php';
		}

		private function get_params()
		{
			if (!$this->test)
			{
				foreach ($_GET as $key => $value) 
				{
					if (($key != 'action') && ($key != 'controller'))
					{
						$this->params[$key] = $value;
					}
				}
			}
		}

		/*-------------------------------------------------------------------
			Parses a nested associative array into a comma seperated value
			and returns list as a string. Takes either one or two arguments.

			Arguments:
				1. array to be parsed
				2. array of keys to be included if not all keys are to be
				   included in the list
		--------------------------------------------------------------------*/
		protected function to_csv($list)
		{
			$csv_string = '';

			foreach ($list as $line)
			{
				end($line);
				$last_index = key($line);
				reset($line);

				foreach ($line as $key => $item)
				{
					if (func_num_args() > 1)
					{
						if (in_array($key, func_get_arg(1)))
						{
							if (strripos($item, ',') !== false)
							{
								$csv_string .= '"' . trim($item) . '"';
							}
							else
							{
								$csv_string .= trim($item);
							}
							
							if ($key != $last_index)
							{
								$csv_string .= ",";
							}
							else
							{
								$csv_string .= "\n";
							}
						}
					}
					else
					{
						$csv_string .= "\"" . $item . "\"";

						if ($key != $last_index)
						{
							$csv_string .= ", ";
						}
						else
						{
							$csv_string .= "\n";
						}
					}
				}
			}
			return $csv_string;
		}

		//displays a pdf file in the browser without navaigating to that file
		protected function deliver_file($file, $delivery_method)
		{
			$filepath = '';

			if (func_num_args() == 3) {
				if (func_get_arg(2) == true) {
					$filepath .= $_SERVER['DOCUMENT_ROOT'] . ROOT;
				}
			}

			$filepath .= $file;

			if (file_exists($filepath))
			{

				header('Pragma: public'); 	// required
				header('Expires: 0');		// no cache
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Last-Modified: ' . gmdate ('D, d M Y H:i:s', filemtime ($filepath)).' GMT');
				header('Cache-Control: private',false);
				header('Content-Type: application/pdf');

				if ($delivery_method == 0) //inline
				{
					//force download
					header('Content-Disposition: attachment; filename="'. basename($filepath) . '"');
				}
				elseif ($delivery_method == 1) //download
				{
					//display in browser
					header('Content-Disposition: inline; filename="'. basename($filepath) . '"');
				}

				header('Content-Transfer-Encoding: binary');
				header('Content-Length: '. filesize($filepath));	// provide file size
				header('Connection: close');
				readfile($filepath);	// push it out
				$this->download = true;

				return true;
			}
			else
			{
				$this->download = true;
				return false;
			}

			$this->download = true;
		}

		protected function download($file_name, $content)
		{
			$file = new file;

			if (!$file->exists($file_name))
			{
				$file->create($file_name);
				$file->write($content);
			}
			else
			{
				$file->open($file_name);
				$file->write($content);
			}
			
			if ($file->exists($file_name))
			{
				if (headers_sent())
				{
					echo "WTF!?!";
				}
				else
				{
					$this->download = true;

					$filepath = $_SERVER['DOCUMENT_ROOT'] . ROOT . "/" . $file_name;

					header('Pragma: public'); 	// required
					header('Expires: 0');		// no cache
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Last-Modified: '. gmdate ('D, d M Y H:i:s', filemtime ($filepath)).' GMT');
					header('Cache-Control: private',false);
					header("Content-type: application/vnd.ms-excel");
					header('Content-Disposition: attachment; filename="'. basename($filepath) . '"');
					header('Content-Transfer-Encoding: binary');
					//header('Content-Length: '. filesize($filepath));	// provide file size
					header('Connection: close');
					readfile($filepath); // push it out
					exit();
				}
			}
		}	

		private function defineController()
		{
			if (!$this->test)
			{
				define('CONTROLLER', get_class($this));
			}
		}

		private function check_form_submission()
		{
			if (func_num_args() == 0)
			{
				if (isset($_POST[ get_class($this) . ':submit']))
				{
					if ($this->form_tolken_valid())
					{
						//put each element into the $post_data array
						foreach ( $_POST as $key => $value)
						{
							$fields = explode( ':', $key );

							if (count($fields) < 2)
							{
								$this->params[$fields[0]] = $value;
							}
							elseif (isset($fields[1]) && $fields[1] != 'submit')
							{
								$this->params[$key] = $value;
							}
						}

						$this->form_submit = true;
						$this->form_name = $_POST['form'];
					}
				}
			}
			else if (func_num_args() == 1)
			{
				if (isset($_POST[ func_get_arg(0) . ':submit']))
				{
					if ($this->form_tolken_valid())
					{
						//put each element into the $post_data array
						foreach ( $_POST as $key => $value)
						{
							$fields = explode( ':', $key );

							if (count($fields) < 2)
							{
								$this->params[$fields[0]] = $value;
							}
						}

						$this->form_submit = true;
						$this->form_name = $_POST['form'];
					}
				}
			}
		}

		/*-------------------------------------------------------------------------------
			This funtion checks to ensure that the CSFR Tolken sent with the form 
			matches the tolken stored in $_SESSION['canvas_CSRF_tolken']. Canvas
			will not recognize that a form has been submitted unless a CSFR tolken is
			included in the form.

			$_SESSION['canvas_CSRF_tolken'] is set in the form class in
			libs/classes/form.class.php
		---------------------------------------------------------------------------------*/
		private function form_tolken_valid()
		{
			if (isset($_POST['CSFR_Tolken']))
			{
				if (isset($_SESSION['canvas_CSRF_tolken']))
				{
					if ($_POST['CSFR_Tolken'] == $_SESSION['canvas_CSRF_tolken'])
					{
						return true;
					}
					else
					{
						return false;
					}
				}
				else {
					return false;
				}
			}
			else
			{
				return false;
			}
		}


		function dump_data()
		{
			try
			{
				if (!$this->test)
				{
					define('JS_ADDS', serialize($this->js_adds));
					define("META_TAGS", serialize($this->meta_tags));
					define("VIEW", $this->view);
					define("PROVIDES", serialize($this->provides));
				}
			}
			catch (\Exception $e)
			{
				if (ENVIRONMENT == 'development')
				{
					$this->print_exception($e);
				}
			}
		}

		function __destruct()
		{
			if ($this->script_only == false && $this->download == false)
			{
				$this->dump_data();

				if (!$this->test)
				{
					include('app/views/layouts/' . $this->layout .'.php');
				}
			}
		}
	}
?>