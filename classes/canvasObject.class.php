<?php
	/*-------------------------------------------------------------------------------
        Serenity - "Serene PHP made easy."

        Developer: Patrick Stephens
        Email: pstephens2601@gmail.com
        Github Repository: https://github.com/pstephens2601/Serenity
        Creation Date: 3-17-2014
        Last Edit Date: 3-21-2014

        Class Notes - The canvasObject class is the parent class for all Serenity
        Objects except the database class.
    ---------------------------------------------------------------------------------*/
    namespace canvas;
    
	class canvasObject {

		protected $test = false;
		protected $mock_objects = array();
		private $reset_properties = array();
		protected $redirect;

		private $us_states = array(
			'Alabama' => 'AL', 'Alaska' => 'AK', 'Arizona' => 'AZ', 'Arkansas' => 'AR',
			'California' => 'CA', 'Colorado' => 'CO', 'Connecticut' => 'CT', 'Delaware' => 'DE',
			'District of Columbia' => 'DC', 'Florida' => 'FL', 'Georgia' => 'GA', 'Hawaii' => 'HI',
			'Idaho' => 'ID', 'Illinois' => 'IL', 'Indiana' => 'IN', 'Iowa' => 'IA',
			'Kansas' => 'KS', 'Kentucky' => 'KY', 'Louisiana' => 'LA', 'Maine' => 'ME',
			'Maryland' => 'MD', 'Massachusetts' => 'MA', 'Michigan' => 'MI', 'Minnesota' => 'MN',
			'Mississippi' => 'MS', 'Missouri' => 'MO', 'Montana' => 'MT', 'Nebraska' => 'NE',
			'Nevada' => 'NV', 'New Hampshire' => 'NH', 'New Jersey' => 'NJ', 'New Mexico' => 'NM',
			'New York' => 'NY', 'North Carolina' => 'NC', 'North Dakota' => 'ND', 'Ohio' => 'OH',
			'Oklahoma' => 'OK', 'Oregon' => 'OR', 'Pennsylvania' => 'PA', 'Rhode Island' => 'RI',
			'South Carolina' => 'SC', 'South Dakota' => 'SD', 'Tennessee' => 'TN', 'Texas' => 'TX',
			'Utah' => 'UT', 'Vermont' => 'VT', 'Virgina' => 'VA', 'Washington' => 'WA', 'West Virgina' => 'WV',
			'Wisconsin' => 'WI', 'Wyoming' => 'WY'
			);

		function is_assoc($arr)
		{
    		return array_keys($arr) !== range(0, count($arr) - 1);
		}

		protected function print_error($message) {
			$html = "<html>";
			$html .= "<head>";
			$html .= '<link rel="stylesheet" type="text/css" href="' . ROOT . 'app/assets/stylesheets/canvas.css">' . "\n";
			$html .= '</head>';
			$html .= '<body>';
			$html .= '<div class="Serene_Error">' . $message . '</div>';
			$html .= '</body>';

			echo $html;
		}

		function print_exception($e)
		{
			$message = '';
			
			do
			{
				$stack_trace = $e->getTrace();
				$message .= '<p>PHP CANVAS ERROR: Exception thrown by (' . $e->getFile() . ' on Line: ' . $e->getLine() . ')</p>';
				$message .= '<p>Exception Message: ' . $e->getMessage() . '</p>';
				
				if ($stack_trace[0]['function'] != 'error_logger')
				{
					$message .= '<p>Stack Trace:<ol>';

					$call_line = $e->getLine();

					foreach ($stack_trace as $call) {
						if (isset($call['class']))
						{
							$message .= '<li>Class [ ' . $call['class'] . ' ] > Method [ ' . $call['function'] . '() ] > Line [ ' . $call_line . ']</li>';
							$call_line = $call['line'];
						}	
					}

					$message .= '</ol>';
					$message .= '</p>';
				}
			}
			while ($e = $e->getPrevious());

			$this->print_error($message);

			exit();
		}

		/*------------------------------------------------------------------------
			Prints out a debugging message that has been specified in code the
			code when debugging has been turned on.
		------------------------------------------------------------------------*/
		protected function debug_message($message, $message_name)
		{
			if (DEBUGGING_ON == true)
			{
				$html = "<html>";
				$html .= "<head>";
				$html .= '<link rel="stylesheet" type="text/css" href="' . ROOT . 'app/assets/stylesheets/canvas.css">' . "\n";
				$html .= '</head>';
				$html .= '<body>';
				$html .= '<div class="Serene_Debug_Message">';
				$html .= '<h4>Serene Debug Message - ' . $message_name . '</h4><hr>';
				$html .= nl2br(htmlspecialchars($message));
				$html .= '</div>';
				$html .= '</body>';

				echo $html;
			}
		}

		protected function format_url($url)
		{
			$url_components = explode('.', $url);

			// Get the URL protocol if there is one.
			if (count($url_components) >= 2)
			{
				$protocol = explode('://', $url_components[0]);
				$protocol = $protocol[0];

				if ($protocol == 'http' || $protocol == 'https')
				{
					$url = implode('.', $url_components);
				}
				else
				{
					$url = 'http://' . implode('.', $url_components);
				}
			}

			return $url;
		}

		protected function array_insert(&$array, $index, $inserted_element)
		{
			if (is_array($array))
			{
				if (is_int($index) && $index > -1)
				{
					$temp_array = array();

					for ($i = 0; $i < count($array); $i++)
					{
						if ($i == $index)
						{
							if (is_array($inserted_element) && $this->is_assoc($inserted_element))
							{
								foreach ($inserted_element as $key => $value) {
									$temp_array[$key] = $value;
								}
							}
							elseif (is_array($inserted_element))
							{
								foreach ($inserted_element as $element) {
									$temp_array[] = $element;
								}
							}
							else
							{
								$temp_array[$i] = $inserted_element;
							}

							if ($this->is_assoc($array))
							{
								$pair = each($array);
								$temp_array[$pair['key']] = $pair['value'];
							}
							else
							{
								$temp_array[] = $array[$i];
							}
						}
						else
						{
							if ($this->is_assoc($array))
							{
								$pair = each($array);
								$temp_array[$pair['key']] = $pair['value'];
							}
							else
							{
								$temp_array[] = $array[$i];
							}	
						}
					}

					$array = $temp_array;
					return $temp_array;
				}	
				else
				{
					if (!is_int($index))
					{
						$type = gettype($index);
						$message = 'Argument 2 expected to be a positive integer, ' . $type . ' given instead.';
					}
					else
					{
						$message = 'Argument 2 expected to be a positive integer, negative value given instead.';
					}
					
					throw new canvasException($message, 0);
				}
			}
			else
			{
				$type = gettype($array);
				throw new canvasException('Argument 1 expected to be an array, ' . $type . ' given instead.', 0);
			}
		}

		//used to find the ordinal indicator of a number
		protected function get_ordinal_number($number)
		{
			$ends = array('th','st','nd','rd','th','th','th','th','th','th');

			if (($number %100) >= 11 && ($number%100) <= 13)
			{
			   $abbreviation = $number . 'th';
			}
			else
			{
			   $abbreviation = $number. $ends[$number % 10];
			}

			return $abbreviation;
		}

		protected function month_to_num($month)
		{
			$month = strtolower($month);

			switch($month)
			{
				case 'january':
					return 1;
				case 'february':
					return 2;
				case 'march':
					return 3;
				case 'april': 
					return 4;
				case 'may':
					return 5;
				case 'june':
					return 6;
				case 'july':
					return 7;
				case 'august':
					return 8;
				case 'september':
					return 9;
				case 'october':
					return 10;
				case 'november':
					return 11;
				case 'december':
					return 12;
				case 'jan':
					return 1;
				case 'feb':
					return 2;
				case 'mar':
					return 3;
				case 'apr': 
					return 4;
				case 'may':
					return 5;
				case 'jun':
					return 6;
				case 'jul':
					return 7;
				case 'aug':
					return 8;
				case 'sep':
					return 9;
				case 'oct':
					return 10;
				case 'nov':
					return 11;
				case 'dec':
					return 12;
				default:
					return 0;
			}
		}

		//converts an int between 1 and 12 to a month
		protected function num_to_month($number)
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
				throw new \Exception('num_to_month() requires a value between 1 and 12. Value provided was[' . $number . '].');
			}
		}

		// takes in a 10 digit number and returns a formatted phone number
		protected function format_phone_number($phone)
        {
            $clean_phone_num = preg_replace("/[^0-9]/", '', $phone);

            $area_code = substr($clean_phone_num, 0, 3);
            $prefix = substr($clean_phone_num, 3, 3);
            $number = substr($clean_phone_num, 6);

            return '(' . $area_code . ') ' . $prefix . '-' . $number;
        }

		protected function standard_time($time)
		{
			if (is_array($time))
			{
				$new_time = array();

				if ($time[0] > 12)
				{
					$new_time[0] = $time[0] - 12;
					$new_time[3] = 'pm';
				}
				else
				{
					$new_time[0] = $time[0] - 12;
					$new_time[3] = 'pm';
				}

				$new_time[1] = $time[1];
				$new_time[2] = $time[2];
			}
			else
			{
				$time_array = explode(':', $time);
				$new_time = array();

				if ($time[0] > 12)
				{
					$new_time[0] = $time_array[0] - 12;
					$new_time[3] = 'pm';
				}
				else
				{
					$new_time[0] = $time_array[0] - 12;
					$new_time[3] = 'pm';
				}

				$new_time[1] = $time_array[1];
				$new_time[2] = $time_array[2];
			}

			return $new_time;
		}

		protected function get_us_states_dropdown()
		{
			$dropdown = array('Select' => '');

			foreach ($this->us_states as $abbrev => $state) {
				$dropdown[$state] = $abbrev;
			}

			return $dropdown;
		}

		protected function get_us_states()
		{
			return $this->us_states;
		}

		protected function abbreviation_to_state($abbrev)
		{
			if (isset($this->us_states[strtoupper($abbrev)]))
			{
				return $this->us_states[strtoupper($abbrev)];
			}
			else
			{
				return false;
			}
		}

		protected function pluralize($class_name)
		{
			$last_letter = substr($class_name, -1);
			$second_to_last_letter = substr($class_name, -2);
			$vowels = array('a', 'e', 'i', 'o', 'u');

			switch ($last_letter)
			{
				case 's':
					return $class_name . 'es';
					break;
				case 'x':
					return $class_name . 'es';
					break;
				case 'h':
					if ($second_to_last_letter == 's' || $second_to_last_letter == 'c')
						return $class_name . 'es';
					else
						return $class_name . 's';
					break;
				case 'y':
					if (in_array($second_to_last_letter, $vowels))
						return $class_name . 's';
					else
						return substr($class_name, 0, -1)  . 'ies';
				default:
					return $class_name . 's';
			}
		}

		function create($object)
		{
			if (!$this->test)
			{
				return $object;
			}
			else
			{
				if (array_key_exists(get_class($object), $this->mock_objects))
				{
					return $this->mock_objects[get_class($object)];
				}
				else
				{
					return $object;
				}
			}
		}

		function set($property, $conditional, $new_value)
		{
			$old_value = $this->$property;
			$this->$property = $new_value;

			$this->reset_properties[$property] = $old_value;
		}

		function add_mock_object($object)
		{
			$this->mock_objects[$object->get_name()] = $object;
		}

		function clear_test_data()
		{
			$this->mock_objects = array();

			foreach ($this->reset_properties as $key => $value) {
				$this->$key = $value;
			}

			$this->reset_properties = array();
			$this->redirect = null;
		}

		protected function convert_sql_server_date($sql_server_date)
		{
			$date_array = explode(' ', $sql_server_date);

			// Eliminate empty indexes
			for ($i = 0; $i < count($date_array); $i++) {
				if (trim($date_array[$i]) == '')
				{
					unset($date_array[$i]);
				}
			}

			$date_array = array_values($date_array);

			if (count($date_array) > 0)
			{
				// Store values.  Month and day converted to two digit string.
				$month = sprintf("%02d", $this->month_to_num($date_array[0]));
				$day = sprintf("%02d", $date_array[1]);
				$year = $date_array[2];

				// Convert time portion of datetime.
				if (isset($date_array[3]))
				{
					$time_array = explode(':', $date_array[3]);
					$hours = $time_array[0];
					$minutes = $time_array[1];
					$seconds = $time_array[2];

					$am_pm = substr($sql_server_date, -2);

					if ($am_pm == 'PM')
					{
						$hours += 12;
					}
					elseif ($hours == 12)
					{
						$hours = 0;
					}
				}

				$date_time = $year . '-' . $month . '-' . $day;

				if (isset($hours))
				{
					$date_time .= ' ' . $hours . ':' . $minutes . ':' . $seconds;
				}

			}
			else
			{
				$date_time = '0000-00-00';
			}

			return $date_time;
		}
	}
?>