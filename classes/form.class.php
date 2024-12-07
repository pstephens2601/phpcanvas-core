<?php
	/*-------------------------------------------------------------------------------
		Serenity - "Serene PHP made easy."

		Developer: Patrick Stephens
		Email: pstephens2601@gmail.com
		Github Repository: https://github.com/pstephens2601/Serenity
		Creation Date: 10-7-2013
		Last Edit Date: 4-18-2014

		Class Notes - The form class is used to quickly construct Serenity compliant
		forms.
	---------------------------------------------------------------------------------*/
	namespace canvas;

	class form extends canvasObject 
	{

		private $name;
		private $action = "";
		private $method = "post";
		private $form;
		private $on_submit;
		private $controller;
		private $CSFR_Tolken;
		private $added_params = array();

		function __construct($action, $method, $name)
		{
			$this->action = $action;
			$this->method = $method;
			$this->name = $name;

			if (isset($_SESSION['canvas_CSRF_tolken']))
			{
				$this->CSFR_Tolken = $_SESSION['canvas_CSRF_tolken'];
			}
			else
			{
				$this->set_CSRF_tolken();
			}

			if ($this->action == '')
			{
				$this->action = $_SERVER['REQUEST_URI'];
			}

			if (func_num_args() > 3)
			{
				if (is_array(func_get_arg(3)))
				{
					$this->added_params = func_get_arg(3);
				}
				else
				{
					$this->on_submit = func_get_arg(3);
				}
			}

			$this->controller = CONTROLLER;
		}

		function set_CSRF_tolken() {
			$this->CSFR_Tolken = md5(microtime() . 'rka4$rb584');
			$_SESSION['canvas_CSRF_tolken'] = $this->CSFR_Tolken;
		}

		function startForm()
		{
			$html = '<form action ="' . $this->action . '" method = "' . $this->method . '"';
			if ($this->on_submit != '')
			{
				$html .= ' onsubmit="' . $this->on_submit . '" ';
			}
			foreach ($this->added_params as $param_name => $param_value) {
				$html .= ' ' . $param_name . '="' . $param_value . '"';
			}
			$html .= '>';
			$html .= '<input type="hidden" name="form" id="form" value="' . $this->name . '">';
			$html .= '<input type="hidden" name="CSFR_Tolken" value="' . $this->CSFR_Tolken . '">';
			echo $html;
		}

		function start_form()
		{
			$html = '<form action ="' . $this->action . '" method = "' . $this->method . '"';
			if ($this->on_submit != '')
			{
				$html .= ' onsubmit="' . $this->on_submit . '" ';
			}
			foreach ($this->added_params as $param_name => $param_value) {
				$html .= ' ' . $param_name . '="' . $param_value . '"';
			}
			$html .= '>';
			$html .= '<input type="hidden" name="form" id="form" value="' . $this->name . '">';
			$html .= '<input type="hidden" name="CSFR_Tolken" value="' . $this->CSFR_Tolken . '">';
			echo $html;
		}

		function setController($controller)
		{
			$this->controller = $controller;
		}

		function input($type)
		{
			$html = "";

			switch ($type)
			{
				case 'password':
				case 'text': // Argument format (type, name, id, class(optional))

					if (is_array(func_get_arg(1)))
					{
						$html .= '<input type="' . $type . '"';

						foreach (func_get_arg(1) as $param => $value) {
							$html .= ' ' . $param . '="' . $value . '"';
						}

						$html .= ">\n";
					}
					else
					{
						$html .= '<input type="' . $type . '" name="' . func_get_arg(1) .'" id="' . func_get_arg(2) . '" ';

						if (func_num_args() == 4)
						{
							$html .= 'class="' . func_get_arg(3) . '" ';
						}

						$html .= ">\n";
					}
					break;
				case 'hidden':
					$html .= '<input type="' . $type . '" name="' . func_get_arg(1) .'" id="' . func_get_arg(2) . '" ';
					$html .= 'value="' . func_get_arg(3) . '"' . ">\n";
					break;
				case 'file':
					if (is_array(func_get_arg(1)))
					{
						$html .= '<input type="' . $type . '"';

						foreach (func_get_arg(1) as $param => $value) {
							$html .= ' ' . $param . '="' . $value . '"';
						}

						$html .= ">\n";
					}
					break;
				case 'select':
					$options = func_get_arg(3);
					$args = array('name' => func_get_arg(1), 'id' => func_get_arg(2));

					if (func_num_args() >= 5)
					{
						$arg_4 = func_get_arg(4);

						if (is_array($arg_4))
						{
							foreach ($arg_4 as $property => $value) {
								$args[$property] = $value;
							}
						}
						else
						{
							$args['class'] = func_get_arg(4);
						}	
					}

					if (func_num_args() == 6)
					{
						$selected = func_get_arg(5);
					}
					else
					{
						$selected = false;
					}

					$this->select($args, $options, $selected);
					break;
				case 'button':
					$button_code = $this->build_button(func_get_arg(1));
					echo $button_code;
					break;
				case 'submit': // Argument format (type, value, class(optional))

					if (func_num_args() == 3)
					{
						$html .= '<input type="submit" value="' . func_get_arg(1) . '" name="' . $this->controller . ':submit" class="' . func_get_arg(2). '">';
					}
					else
					{
						$html .= '<input type="submit" value="' . func_get_arg(1) . '" name="' . $this->controller . ':submit" id="' . func_get_arg(3) . '" class="' . func_get_arg(2). '">';
					}

					break;
				default:
					die('Serenity Error: Invalid type given for input()');
					break;
			}

			echo $html;
		}

		function select($attributes, $options, $selected = null)
		{
			// Build select tag
			$html = '<select';

			foreach ($attributes as $name => $value) {
				$html .= ' ' . $name . '="' . $value . '"';
			}

			$html .= '>';

			// Add options
			if ($this->is_assoc($options))
			{
				foreach ($options as $title => $value) {

					if (is_array($value))
					{
						$html .= '<option';

						foreach($value as $property => $property_value)
						{
							$html .= ' ' . $property . ' = "' . $property_value . '"';
						}
					}
					else
					{
						$html .= '<option value="' . $value . '"';
					}

					if ($selected != null && $value == $selected)
					{
						$html .= ' selected';
					}

					$html .= '>' . $title . '</option>' . "\n";
				}
			}
			else
			{
				$key = 0;

				foreach ($options as $title) {

					$html .= '<option value="' . $key . '"';
					
					if ($selected != null && $key == $selected)
					{
						$html .= ' selected';
					}

					$html .= '>' . $title . '</option>' . "\n";

					$key++;
				}
			}

			// End select tag and print output
			$html .= '</select>';

			echo $html;
		}

		function radio($group, $value, $attributes = null) {
			// Build input tag.
			$html = '<input type="radio" name="' . $group . '" value="' . $value .'"';

			if (is_array($attributes) && $this->is_assoc($attributes))
			{
				foreach ($attributes as $name => $value) {
					if ($value != '' && $value != null)
					{
						$html .= ' ' . $name . '="' . $value . '"';
					}
					else
					{
						$html .= ' ' . $name;
					}
				}
			}

			$html .= '>';

			echo $html;
		}

		function us_states($attributes, $selected = null)
		{
			if (isset($attributes['name']) && isset($attributes['id']))
			{
				$this->select($attributes, $this->get_us_states(), $selected);
			}
			else
			{
				$this->print_exception(new canvasException('Invalid list of atributes passed to form->us_states.'));
			}
		}

		function endForm()
		{
			echo "</form>";
		}

		function end_form()
		{
			echo "</form>";
		}

		private function build_button($args) {
			$html = '<input type="button"';

			foreach ($args as $key => $value) {
				$html .= ' ' . $key . '=' . '"' . $value . '"';
			}

			$html .= '>';

			return $html;
		}
	}

?>