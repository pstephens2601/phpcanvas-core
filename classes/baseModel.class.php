<?php
	namespace canvas;
	use PDO;

	/**
	 *  Parent class for all models within the Canvas framework.
	 *	
	 *	The baseModel class forms the framework for all of your models.
	 *	When creating a new model it is required that it is a child of the baseModel 
	 *	class.
	 *
	 *	@author Patrick Stephens <pstephens2601@gmail.com>
	 *	@version 1.0
	 * 	@package Models
	*/

	class baseModel extends canvasObject 
	{
		/** @var string $database_type Holds the database type for the model. */
		private $database_type;
		/** @var boolean $database_object Set to true if the modle is to use the static database object. */
		private $database_object = true;
		/** @var boolean $auto_connect Set to false if class should not automatically initiate db connection. */
		private $auto_connect = true;
		/** @var boolean $auto_id Set to true if an id number is to be automatically assigned to the model. */
		private $auto_id = true;
		/** @var integer $id Holds the unique id number for the model. */
		public $id;
		/** @var database $db Used to hold the database object that is shared by all instantiated models. */
		protected static $db;
		/** @var string $form Holds the name of the form that has been submitted if one has. */
		protected $form;
		/** @var boolean $is_valid Used to mark whether or not the model's current data validates according to any validators set. */
		protected $is_valid = true;
		/** @var string[] validation_messages Used to store the list of validation messages declared in the child model. */
		private $validation_messages = array();
		/** @var string[] $validation_errors Used to store the list of validation messages for validation errors that have occured. */
		protected $validation_errors = array();
		/** @var validator[] $validators Holds a the active validators for this model. */
		protected $validators = array();
		/** @var string[] $save_exclusions List of public properties that will not be saved to the database when the save() method is called. */
		protected $save_exclusions = array();
		private $safe_submit_overrides = array();
		/** @var boolean $in_db Used for non-auto_id models to determine if they have been previously saved in the database. */
		private $in_db = false;
		/** @var string[] $object_references List of public properties in the child class that are object used to store object references rather than data.  */
		private $object_references = array();
		protected $snap_shot;

		/**
		 *	Constructor that creates a database object and checks for form submission.
		 *
		 *	Upon loading the baseModel class automatically checks for form submissions and
		 *	creates a new database object to establish a connection to the database.
		 */
		function __construct()
		{
			if (HOST != '' && USER != '' && PASSWORD != '' && DATABASE != '' && $this->auto_connect != false && $this->database_object != false)
			{
				if (self::$db == null)
				{
					//database constant values set in config/db_config.php
					self::$db = new database($this);

					try
					{
						self::$db->connect();
					}
					catch (canvasException $e)
					{
						if (LOG_EXCEPTIONS)
						{
							if (VERBOSE_LOGGING)
							{
								$e->set_verbose();
							}

							$e->log();
						}
						
						throw $e;
					}
				}
				else
				{
					try
					{
						self::$db->verify_table($this);
					}
					catch (canvasException $e)
					{
						if (LOG_EXCEPTIONS)
						{
							if (VERBOSE_LOGGING)
							{
								$e->set_verbose();
							}

							$e->log();
						}

						throw $e;
					}
				}
			}
			else if ($this->auto_connect == false && $this->database_object != false)
			{
				//database constant values set in config/db_config.php
				self::$db = new database($this);
			}

			if (func_num_args() == 1)
			{
				$test = func_get_arg(0);

				if ($test)
				{
					$this->test = true;
				}
			}
		}

		/**
		 *	Passes a list of properties to be serialized when the model is serialized.
		 *
		 *	This method returns a list of all of the model's public properties that is then
		 *	used to determine what properties are available when the model is passed to a
		 *	view using a controller's provide() method.
		 *	
		 *	@return string A list of the model's public properties.
		 */
		function __sleep() {
			return $this->get_public_properties();
		}

		/**
		 *	Closes the connection to the database.
		 *
		 *	This method closes the database connection and sets the $db property to null.
		 */
		function close_connection()
		{
			self::$db = null;
		}

		/**
		 *	Used to set the database type.
		 *
		 *	This method is used to set the type of database that the model will be connecting to.
		 *	If not used the database type defualts to the type defined in the db_config file.
		 *	
		 *	@param string $type This is a string that indicates the type of database that is being used.
		 */
		protected function set_database_type($type)
		{
			$this->database = $type;
		}

		/**
		 *	Used to turn the Auto ID feature on and off.
		 *
		 *	This method is used to switch the Auto ID feature on and off.
		 *	by default it is switched on.
		 *	
		 *	@param boolean $value If true Auto ID is turned on, if false it is turned off.
		 *	@throws canvasException Throws a canvasException if the parameter provided is not a boolean value.
		 */
		protected function set_auto_id($value)
		{
			if (gettype($value) == 'boolean')
			{
				$this->auto_id = $value;
			}
			else
			{
				$error = new error;
				$error->set_error(3, 'set_auto_connect() requires a boolean value, it was passed a ' . gettype($value));
				$error->output_message();
			}
		}

		/**
		 *	Returns true if Auto ID is turned on.
		 *
		 *	This method returns true if Auto ID is turned on, and false if it is not.
		 *	
		 *	@return boolean Returns true if Auto ID is on, and false if it is not.
		 */
		function get_auto_id()
		{
			return $this->auto_id;
		}

		/**
		 *	Used to set whether or not the model will automatically connect to the database.
		 *
		 *	This method takes a boolean value that it then uses to determine if the model should,
		 *	or should not automatically connect to the database.
		 *	
		 *	@param boolean $value Should be set to true to turn on the automatic connection to the database, and false to turn it off.
		 *	@throws canvasException Throws a canvasException if the parameter provided is not a boolean value.
		 */
		protected function set_auto_connect($value)
		{
			if (gettype($value) == 'boolean')
			{
				$this->auto_connect = $value;
			}
			else
			{
				throw new canvasException('CANVAS ERROR[6]: set_auto_connect() requires a boolean value, it was passed a ' . gettype($value), 6);
			}
		}

		function set_database_object($value)
		{
			if (gettype($value) == 'boolean')
			{
				$this->database_object = $value;
			}
			else
			{
				throw new canvasException('CANVAS ERROR[6]: set_database_object() requires a boolean value, it was passed a ' . gettype($value), 6);
			}
		}

		/**
		 *	Used to exclude a public property from being saved to the database.
		 *
		 *	This method is used to exclude a public property of the model from
		 *	from automatically being saved to the database.
		 *	
		 *	@param string var_name The name of the property that is to be excluded from bein gsaved.
		 */
		function set_save_exclusion($var_name)
		{
			$save_exclusions[] = $var_name;
		}

		/**
		 *	Marks a public class member as being an object reference.
		 *
		 *	This method declares a public class member as an object reference
		 *	thereby excluding it from being saved in the database.
		 *	
		 */
		function set_object_reference($var_name)
		{
			$this->object_references[] = $var_name;
		}

		/**
		 *	Returns an array of the model's public properties.
		 *
		 *	This method returns an array that contains the names and values of all the
		 *	model's public properties.  The name of the property is set as the key,
		 *	and the value is set as the value.
		 *
		 *	@return string[string] Associative array containing the names and values of all of the model's public properties.
		 */
		function to_array()
		{
			$reflection = new \ReflectionObject($this);
			$properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

			$public_properties = array();

			foreach ($properties as $property) {
				$public_properties[] = $property->getName();
			}

			$object_array = array();
	
			foreach ($public_properties as $property)
			{
				$object_array[$property] = $this->$property;
			}

			return $object_array;
		}

		function hash($value)
		{	
			$value = $value . 'bn894rn34b';
			return md5($value);
		}

		/**
		 *	Finds a database record associated with the model by it's ID number and loads the data into the model.
		 *	
		 *	This method take a unique ID number as a parameter and uses it to find the record associated with that ID
		 *	number and model.  It then loads the record's data into the model.  If passed an array of IDs the method
		 *	will find all of the records in the array and return an array of models.  If no reocords are found by the
		 *	method it will return false.
		 *	
		 *	@param integer|integer[] $id The id, or ids, of the record being searched for. By default this is an int.
		 *	@return boolean|self|self[] The find() method returns a boolean value of false if no record is found, and an object of the same model if a record is found.  If multiple objects are found then an array of objects is returned.
		 */
		function find($id)
		{
			self::$db->set_model($this);

			if (is_array($id))
			{	
				$object_array = array();
				$array_length = count($id);
				$current_class = get_class($this);

				$statement = "SELECT * FROM " . $this->pluralize(get_class($this)) . " WHERE id =";

				for ($i = 0; $i < $array_length; $i++)
				{
					$statement .= ' ?';

					if ($i < $array_length - 1)
					{
						$statement .= ' OR id = ';
					}
				}

				try
				{
					self::$db->prepare($statement);
					$rows = self::$db->execute($id);
				}
				catch (canvasException $e)
				{
					$e->log();

					if (ENVIRONMENT == 'development')
					{
						$this->print_exception($e);
					}

					return false;
				}

				foreach ($rows as $row) {
					$new_object = new $current_class;
					$new_object->map($row);

					$object_array[] = $new_object;
				}

				return $object_array;
			}
			else
			{
				$num_args = func_num_args();

				if ($num_args > 1)
				{
					$id_list = func_get_arg(1);

					$object_array = array();
					$array_length = count($id_list);
					$current_class = get_class($this);

					$statement = "SELECT * FROM " . $this->pluralize(get_class($this)) . " WHERE " . $id . " =";

					for ($i = 0; $i < $array_length; $i++)
					{
						$statement .= ' ?';

						if ($i < $array_length - 1)
						{
							$statement .= ' OR ' . $id . ' = ';
						}
					}

					//if a an additional column is to be looked at
					if ($num_args == 3)
					{
						$restrictions = func_get_arg(2);

						foreach ($restrictions as $col => $value) {
							$statement .= ' AND ' . $col .' = ?';
							$id_list[] = $value;
						}
					}
					
					try
					{
						self::$db->prepare($statement);
						$rows = self::$db->execute($id_list);
					}
					catch (canvasException $e)
					{
						$e->log();

						if (ENVIRONMENT == 'development')
						{
							$this->print_exception($e);
						}

						return false;
					}

					foreach ($rows as $row) {
						$new_object = new $current_class;
						$new_object->map($row);

						$object_array[] = $new_object;
					}

					return $object_array;
				}
				else
				{
					$statement = "SELECT * FROM " . $this->pluralize(get_class($this)) . " WHERE id = ?";

					try
					{
						self::$db->prepare($statement);
						$rows = self::$db->execute(array($id));
					} 
					catch (canvasException $e)
					{
						$e->log();

						if (ENVIRONMENT == 'development')
						{
							$this->print_exception($e);
						}

						return false;
					}

					if (empty($rows))
					{
						return false;
					}
					else
					{
						$this->map($rows[0]);
						$this->in_db = true;
						return $rows[0];
					}
				}
			}
		}

		/**
		 *	Finds a specific number of records matching a set of given parameters.
		 *
		 *	The find_range() method can be used to find a given number of records
		 *	matching specific parameters.  This method is perfect for getting a
		 *	result set that is limited to a specific number of records.
		 *	
		 *	USAGE: find_range(*array of contitions*, *starting index*, *number of records*, *index (optional)*)
		 *
		 *	@param string[] $conditions Array of conditions used to filter results.
		 *	@param integer $start The starting index of the result set to be returned.
		 *	@param integer $stop The number of results to be returned.  If left blank only 1 result wil be returned.
		 *	@param string $index This is the column to be used as the index. If left blank the id column will be used.
		 */
		function find_range($conditions, $start, $stop = 0, $index = 'id')
		{
			self::$db->set_model($this);
			$values = array();
			$array_length = count($conditions);
			$count = 1;

			$statement = "SELECT * FROM " . $this->pluralize(get_class($this));

			if ($array_length > 0)
			{
				$statement .= " WHERE ";
			}

			foreach ($conditions as $col => $value)
			{
				$statement .= $col . ' = ?';
				$values[] = $value;

				if ($count < $array_length)
				{
					$statement .= ' AND ';
				}

				$count++;
			}

			if (strlen($stop) > 1)
			{
				$statement .= ' ORDER BY ' . $index . ' DESC';
			}

			$statement .= ' LIMIT ' . $start;

			if ($stop > 0)
			{
				$statement .= ', ' . $stop;
			}

			try
			{
				self::$db->prepare($statement);
				$rows = self::$db->execute($values);
			}
			catch (canvasException $e)
			{
				$e->log();

				if (ENVIRONMENT == 'development')
				{
					$this->print_exception($e);
				}

				return false;
			}

			if (empty($rows))
			{
				return false;
			}
			else
			{
				$current_class = get_class($this);

				foreach ($rows as $row) {
					$new_object = new $current_class;
					$new_object->map($row);

					$object_array[] = $new_object;
				}

				return $object_array;
			}
		}

		/**
		 *	Used to find the number of records in the model's database table that match a specific set of conditions.
		 *
		 *	The find count method is used to find the number of records that match the given set of conditions
		 *	in the model's database table.\
		 *	
		 *	@param string $col Name of the column that will be counted.  This should typically be the id column
		 *	@param string[] $conditions Associative array of conditions that are to be met, the key represents the column name and the value is the required value.
		 *	
		 *	@return integer This method returns an integer that represents the number of records found.
		 */
		function find_count($col, $conditions)
		{
			self::$db->set_model($this);

			$statement = "SELECT COUNT(" . $col . ") FROM " . $this->pluralize(get_class($this)) . " WHERE ";
			$values = array();
			$array_length = count($conditions);
			$count = 1;

			foreach ($conditions as $col => $value)
			{
				$statement .= $col . ' = ?';
				$values[] = $value;

				if ($count < $array_length)
				{
					$statement .= ' AND ';
				}

				$count++;
			}

			try
			{
				self::$db->prepare($statement);
				$rows = self::$db->execute($values);
			}
			catch (canvasException $e)
			{
				$e->log();

				if (ENVIRONMENT == 'development')
				{
					$this->print_exception($e);
				}

				return false;
			}

			if (empty($rows))
			{
				return false;
			}
			else
			{
				return $rows[0][0];
			}
		}

		/**
		 *	This method finds the first record that matches a given set of conditions and loads the data into the model.
		 *
		 *	This method finds the first record matching some given conditions and loads its data into the model. It takes
		 *	one, or more column names, followed by an array of values as a parameter.
		 *	ex. format: $this->find_by(col_1, col_2, array(data_1, data_2))
		 *	
		 *	@param string $column_names The name of one or more columns that are to be looked at.  Each column name should be entered as
		 *	a seperate argument.
		 *	@param string[] $values A list of the values that are to be searched for in the list of given columns.  Values are assigned to
		 *	column names in the same order as the columns were added.
		 *	@todo This method needs to be rewritten and deprecated.
		 */
		function find_by()
		{
			self::$db->set_model($this);

			$num_args = func_num_args();

			$statement = "SELECT * FROM " . $this->pluralize(get_class($this)) . " WHERE ";

			for ($i = 0; $i < $num_args - 1; $i++)
			{
				if ($i > 0)
				{
					$statement .= ' AND ';
				}

				$statement .= func_get_arg($i) . ' = ?';
			}

			try
			{
				self::$db->prepare($statement);
				$rows = self::$db->execute(func_get_arg($num_args - 1));
			}
			catch (canvasException $e)
			{
				$e->log();

				if (ENVIRONMENT == 'development')
				{
					$this->print_exception($e);
				}

				return false;
			}

			if (empty($rows))
			{
				return false;
			}
			else
			{
				$this->map($rows[0]);
				$this->in_db = true;
				return $rows[0];
			}
		}

		/**
		 *	Finds the last record matching a given set of conditions.
		 *
		 *	This method searches for the last record that matches a given set of conditions and loads
		 *	its data into the model if found. ex. format: $this->find_by(col_1, col_2, array(data_1, data_2))
		 *	
		 *	@param string $column_names The name of one or more columns that are to be looked at.  Each column name should be entered as
		 *	a seperate argument.
		 *	@param string[] $values A list of the values that are to be searched for in the list of given columns.  Values are assigned to
		 *	column names in the same order as the columns were added.
		 *	@todo This method needs to be rewritten and deprecated.
		 */
		function find_last()
		{
			self::$db->set_model($this);

			$num_args = func_num_args();

			$statement = "SELECT * FROM " . $this->pluralize(get_class($this));

			if ($num_args > 0)
			{
				$statement .= " WHERE ";
			}

			for ($i = 0; $i < $num_args - 1; $i++)
			{
				if ($i > 0)
				{
					$statement .= ' AND ';
				}

				$statement .= func_get_arg($i) . ' = ?';
			}

			try
			{
				self::$db->prepare($statement);

				if ($num_args > 0)
				{
					$rows = self::$db->execute(func_get_arg($num_args - 1));
				}
				else
				{
					$rows = self::$db->execute();
				}
			}
			catch (canvasException $e)
			{
				$e->log();

				if (ENVIRONMENT == 'development')
				{
					$this->print_exception($e);
				}

				return false;
			}

			if (empty($rows))
			{
				return false;
			}
			else
			{
				$row = array_pop($rows);
				$this->map($row);
				$this->in_db = true;
				return $row;
			}
		}

		/**
		 *	Finds the maximum value for a given column.
		 *
		 *	This method takes a column name and returns the maximum value contained within that column.  It also maps the
		 *	data from that record into the model.
		 *	
		 *	@param string $column String used to identify the name of the column that the max value is to be found in.
		 *	@return integer|string Returns the value for the field that contains the maximum value in that column.
		 */
		function find_max($column)
		{
			$statement = "SELECT * FROM " . $this->pluralize(get_class($this)) . " WHERE $column = (SELECT MAX($column) FROM " . $this->pluralize(get_class($this)) . ")";

			try
			{
				self::$db->prepare($statement);
				$rows = self::$db->execute();
			}
			catch (canvasException $e)
			{
				$e->log();

				if (ENVIRONMENT == 'development')
				{
					$this->print_exception($e);
				}

				return false;
			}

			if (empty($rows))
			{
				return false;
			}
			else
			{
				$row = array_pop($rows);
				$this->map($row);
				$this->in_db = true;
				return $row->rank;
			}
		}

		/**
		 *	Locates a group of records in the database and returns them as an array of models.
		 *
		 *	This method locates a group of records in the database. It may be passed one optional arguement.
		 *	If it is not passed an argument it will return an array of models containing all the records in 
		 *	the database table.  If the number of records return is to be limited then it should be passed
		 *	an array containing a list of column names and required values.  For eample find_group(array(col_name => value))
		 *	This method replaces find_all().
		 *	
		 *	@param string[] $conditions Optional parameter that is an associative array containing a list of column names and required values for each column.
		 *	@return boolean|self[] Returns either the boolean value of false if no records are found, or an array of models.
		 */
		function find_group($conditions = null)
		{
			$statement = "SELECT * FROM " . $this->pluralize(get_class($this));

			if ($conditions != null)
			{
				$values = array();
				$counter = 1;
				$statement .= " WHERE ";

				foreach ($conditions as $col => $value) {

					if ($counter > 1)
					{
						$statement .= ' AND ';
					}

					$statement .= $col . ' = ?';
					$values[] = $value;
					$counter++;
				}
			}

			try 
			{
				self::$db->prepare($statement);
				$rows = self::$db->execute($values);
			}
			catch (canvasException $e)
			{	
				throw new canvasException("CANVAS ERROR[10]: Find failed. Database query failed. SQL: " . $statement, 10, $e);	
			}

			$result_set = array();

			if (count($rows) > 0)
			{
				foreach ($rows as $row)
				{
					$class = get_class($this);
					$obj = new $class;
					$obj->map($row);
					$result_set[] = $obj;
				}

				return $result_set;
			}
			else
			{
				return false;
			}
		}

		/**
		 *	Locates a group of records in the database and returns them as an array of models.
		 *
		 *	This method locates a group of records in the database. It may be passed either 0 or 2 
		 *	arguments. If it is passed 0 arguments it will return all rows in the table.  If it is 
		 *	passed 2 it will return all rows where the value contained in the column given in 
		 *	argument 0 is equal to argument 1.
		 *	
		 *	@deprecated 1.0	This method has been deprecated as of version 1.0 and should no longer be used. Replaced by find_group().
		 *	@param string $col Optional parameter that contains the name of the column that contains a required value.
		 *	@param string $val An optional parameter containing a required value.
		 *	@return self[] Returns an array of models.
		 */
		function find_all()
		{
			self::$db->set_model($this);

			$num_args = func_num_args();

			if ($num_args == 2)
			{
				$col = func_get_arg(0);
				$val = func_get_arg(1);

				$query = "SELECT * FROM " . $this->pluralize(get_class($this)) . " WHERE $col ='$val'";

				if ($results = self::$db->query($query))
				{
					$result_set = array();

					while($result = $results->fetch(PDO::FETCH_ASSOC))
					{
						$result_set[] = $result;
					}

					return $result_set;
				}
				else
				{
					if (ENVIRONMENT == 'development')
					{
						$message = "Canvas Error (Stay Calm!): ";
						$message .= "There was an error when executing " . __METHOD__ . "() on line " . __LINE__;
						$message .= "- the database query failed for the following reason (" . self::$db->error . ").";

						$this->print_error($message);
					}
					else
					{
						die(PRODUCTION_ERROR_MESSAGE);
					}
				}
			}
			elseif ($num_args == 0)
			{
				$query = "SELECT * FROM " . $this->pluralize(get_class($this));

				if ($results = self::$db->query($query))
				{
					$result_set = array();

					while($result = $results->fetch(PDO::FETCH_ASSOC))
					{
						$result_set[] = $result;
					}

					return $result_set;
				}
				else
				{
					if (ENVIRONMENT == 'development')
					{
						$message = "Canvas Error (Stay Calm!): ";
						$message .= "There was an error when executing " . __METHOD__ . "() on line " . __LINE__;
						$message .= "- the database query failed for the following reason (" . self::$db->error . ").";

						$this->print_error($message);
					}
					else
					{
						//die(PRODUCTION_ERROR_MESSAGE);
					}
				}
			}
			else if ($num_args == 1)
			{
				$arg = func_get_arg(0);

				if (is_array($arg))
				{
					return $this->find_all_assoc_array($arg);	
				}
			}
			else
			{
				if (ENVIRONMENT == 'development')
				{
					$this->print_error("Canvas Error (Stay Calm!): invalid number of arguments passed to " . __METHOD__ . "() on line " . __LINE__);
				}
				else
				{
					
				}
			}
		}

		/**
		 *	Finds all records before, or after, a given date.
		 *
		 *	This method is used to find all occurences of a record that contain a date before,
		 *	or after, a given date. The name of the column that contains the date must be provided
		 *	along with a properly formatted date.
		 *	
		 *	 
		 */
		function find_by_date($column, $datetime, $time_period = 'after', $conditions = null)
		{
			$statement = "SELECT * FROM " . get_class($this) . "s WHERE ";
			$values = array($datetime);

			// Add the time condition to the query
			if (strtolower($time_period) == 'after')
			{
				$statement .= $column . " >= ?";
			}
			elseif (strtolower($time_period) == 'before')
			{
				$statement .= $column . " <= ?";
			}
			else
			{
				// IF: no time period was given, but conditions were given swap the values.
				if (is_array($time_period) && $conditions == null)
				{
					$conditions = $time_period;
					$time_period = 'after';
					$statement .= $column . " >= ?";
				}
				else
				{
					throw new canvasException('Model [' . get_class($this)  . '] -> method [' . __FUNCTION__ . '()] has generated the following exception [Invalid value given for argument 3, value must be either "before" or "after" to indicate whether you are looking for records before, or after, the given date.]');
				}
			}

			// Add any additional conditions to the query
			if ($conditions != null)
			{
				if (is_array($conditions) && $this->is_assoc($conditions))
				{
					foreach ($conditions as $col => $value) {
						$statement .= " AND " . $col . " = ?";
						$values[] = $value;
					}
				}
				else
				{
					throw new canvasException('Model [' . get_class($this)  . '] -> method [' . __FUNCTION__ . '()] has generated the following exception');
				}
			}

			try 
			{
				self::$db->prepare($statement);
				$rows = self::$db->execute($values);
			}
			catch (canvasException $e)
			{	
				throw new canvasException("CANVAS ERROR[10]: " . __FUNCTION__ . "() failed. Database query failed. SQL: " . $statement, 10, $e);	
			}

			$result_set = array();

			if (count($rows) > 0)
			{
				foreach ($rows as $row)
				{
					$class = get_class($this);
					$obj = new $class;
					$obj->map($row);
					$result_set[] = $obj;
				}

				return $result_set;
			}
			else
			{
				return false;
			}

		}

		 /**
		  *	This method finds the records close to matching some conditions.
		  *
		  *	This method finds the records close to matching some conditions.
		  *	ex. format: $this->find_like(col_1, col_2, array(data_1, data_2), limit)
		  */
		function find_like()
		{
			self::$db->set_model($this);

			$num_args = func_num_args();
			$last_argument = func_get_arg($num_args - 1);
			$add_or = false;

			if (!is_array($last_argument) && is_int($last_argument))
			{
				$previous_arg = func_get_arg($num_args - 2);

				//If the statement has a limit and an OR
				if (!is_array($previous_arg) && $previous_arg == 'OR')
				{
					$add_or = true;
				}

				$limit = $last_argument;
			}
			elseif (!is_array($last_argument) && $last_argument == 'OR')
			{
				$add_or = true;
			}

			$statement = "SELECT * FROM " . $this->pluralize(get_class($this)) . " WHERE ";

			if ($add_or)
			{
				$statement .= '(';
			}

			//Get the number of arguments to loop through
			if (isset($limit) && $add_or)
			{
				$num_data_args = $num_args - 2;
			}
			else
			{
				$num_data_args = $num_args - 1;
			}

			// Build query statement
			for ($i = 0; $i < $num_data_args; $i++)
			{
				$arg = func_get_arg($i);

				if (!is_array($arg))
				{
					if ($i > 0)
					{
						$statement .= ' AND ';
					}

					// See if column has custom operator
					$arg = explode(':', $arg);

					if (count($arg) > 1)
					{
						$statement .= $arg[0] . ' ' . $arg[1] . ' ?';
					}
					else
					{
						$statement .= $arg[0] . ' LIKE ?';
					}	
				}
			}

			if ($add_or)
			{
				$statement .= ')';

				$or_values = func_get_arg($num_data_args - 1);
				$num_cols = count($or_values);
				$statement .= ' OR (';

				for ($i = 0; $i < $num_cols; $i++)
				{
					if (!is_array($or_values[$i]))
					{
						if ($i > 0)
						{
							$statement .= ' AND ';
						}

						// See if column has custom operator
						$or_column = explode(':', $or_values[$i]);

						if (count($or_column) > 1)
						{
							$statement .= $or_column[0] . ' ' . $or_column[1] . ' ?';
						}
						else
						{
							$statement .= $or_column[0] . ' LIKE ?';
						}
					}
				}

				$statement .= ')';
			}

			if (isset($limit))
			{
				$statement .= ' LIMIT ' . $limit;
			}

			// Prepare data for LIKE query
			if (isset($limit) && !$add_or)
			{
				$data_array = func_get_arg($num_args - 2);
			}
			else
			{
				if (!$add_or && !isset($limit))
				{
					$data_array = func_get_arg($num_args - 1);
				}
				elseif ($add_or && !isset($limit))
				{
					$or_values = func_get_arg($num_args - 2);
					$num_or_values = count($or_values);
					$data_array = func_get_arg($num_args - 3);
					$data_array = array_merge($data_array, $or_values[$num_or_values - 1]);
				}
				else
				{
					$or_values = func_get_arg($num_args - 3);
					$num_or_values = count($or_values);
					$data_array = func_get_arg($num_args - 4);
					$data_array = array_merge($data_array, $or_values[$num_or_values - 1]);
				}
			}
			
			foreach ($data_array as &$data_object)
			{
				$data_object = '%' . $data_object . '%';
			}

			try
			{
				self::$db->prepare($statement);
				$rows = self::$db->execute($data_array);
			}
			catch (canvasException $e)
			{
				$e->log();

				if (ENVIRONMENT == 'development')
				{
					$this->print_exception($e);
				}

				return false;
			}

			if (empty($rows))
			{
				return false;
			}
			else
			{
				$result_set = array();

				if (count($rows) == 1)
				{
					$row = array_pop($rows);
					$this->map($row);
					$this->in_db = true;

					$result_set[] = $this;
				}
				else
				{
					foreach ($rows as $row)
					{
						$current_class = get_class($this);
						$new_object = new $current_class;
						$new_object->map($row);
						$result_set[] = $new_object;
					}	
				}

				return $result_set;
			}
		}

		/**
		 *	Returns a new duplicate object of the same class. 
		 *
		 *	The copy method creates a new object from the same model
		 *	and copies into it all of values contain in the copied object's
		 *	public properties.
		 *	
		 *	@return self Returns a copy of the model that is used to call this method.
		 */
		function copy()
		{
			$class = get_class($this);
			$copy = new $class;

			$public_properties = $this->get_public_properties();

			foreach ($this as $key => $value)
			{
				if (in_array($key, $public_properties) && $key != 'id')
				{
					$copy->$key = $value;
				}
			}

			return $copy;
		}

		/**
		 *	Takes an associative array representing a database record and copies the data into the object's properties.
		 *
		 *	This method takes an associative array representing a database record and copies the data into the model's
		 *	corresponding public properties.  Array keys that do not match one of the objects public properties will
		 *	be ignored.
		 *	
		 *	@param string[] $row Associative array representing a returned row from a database query. Key's represent 
		 *	column names and values represent the data.
		 *	@return null This method does not return anything.
		 */
		function map($row)
		{
			foreach ($row as $key => $value) {
				if (property_exists($this, $key))
				{
					$this->$key = $value;
				}
			}

			$this->snap_shot = $this->to_array();
		}


		/**
		 *	Used to see if a matching model already exists in the database.
		 *
		 *	This method is used to determine if the current model already has a record in
		 *	in the database.
		 *	
		 *	@todo Figure out if this method is actually being used, or if it even works.
		 */
		function is_unique($var) {

			$statement = "SELECT " . $var . " FROM " . get_class($this) . "s WHERE " . $var . " = ?";

			try 
			{
				self::$db->prepare($statement);
				$rows = self::$db->execute(array($this->$var));
			}
			catch (canvasException $e)
			{
				$e->log();

				if (ENVIRONMENT == 'development')
				{
					$this->print_exception($e);
				}

				throw $e;
			}
			

			if (empty($rows))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 *	Used to save the model's public properties to the database.
		 *
		 *	This method is used to save a model object to the database.  Only the model object's public properties
		 *	will be saved. If the model object is being saved for the first time a new record will be created,
		 *	if the model object has been saved before the existeing record will be updated. $is_valid must be 
		 *	set to true for this method to work.
		 *
		 *	@param integer|string $this->id Optional parameter that contains the unique id of the model object.
		 *	@return boolean Returns true if the model saves without any errors.
		 *	@throws canvasException Throws a canvasExcepton upon a failed save. 
		 */
		function save() 
		{
			self::$db->set_model($this);

			if (func_num_args() == 1)
			{
				$this->id = func_get_arg(0); // item id number

				// Check information stored in object properties against validators.
				$this->run_validation();

				// Insert data into the database.
				if ($this->is_valid)
				{
					$update_query = $this->build_query();
			
					try 
					{
						self::$db->prepare($update_query['statement']);
						self::$db->execute($update_query['values'], false);
						$this->snap_shot = $this->to_array();
						return true;
					}
					catch (canvasException $e)
					{
						throw new canvasException("CANVAS ERROR[7]: Save failed. Database query failed.", 7, $e);	
					}
				}
				else
				{
					throw new canvasException("CANVAS ERROR[8]: Save failed. Model did not validate.", 8);
				}
			}
			else if (func_num_args() == 0)
			{
				// Check information stored in object properties againts validators.
				$this->run_validation();

				// Insert data into the database.
				if ($this->is_valid)
				{
					//if not already in the database save as an insert, else save as an update.
					$query = $this->build_query();

					try
					{
						self::$db->prepare($query['statement']);
						self::$db->execute($query['values'], false);
					}
					catch (canvasException $e)
					{
						throw new canvasException("CANVAS ERROR[7]: Save failed. Database query failed.", 7, $e);
					}
					
					//if this model has not been saved previously.
					if ($this->id < 1)
					{
						$this->id = self::$db->get_insert_id();
					}

					$this->snap_shot = $this->to_array();

					return true;	
				}
				else
				{
					throw new canvasException("CANVAS ERROR[8]: Save failed. Model did not validate.", 8);
				}
			}
			else
			{
				throw new canvasException("CANVAS ERROR[9]: Invalid number of arguments passed to save().", 9);
			}
		}

		/**
		 *	Saves the current model object if there have been any changes to the data.
		 *
		 *	This method saves the current model object if the data has changed since find()
		 *	was called.
		 *	
		 *	@return boolean Returns true if the model object is saved, false if it is not.
		 */
		function save_on_update()
		{
			//IF: the data in the object has been updated.
			if ($this->updated())
			{
				$this->save();
				return true;
			}
			else
			{
				return false;
			}
		}

 		/**
 		 *	Deletes the model object's record from the database.
 		 *
 		 *	This method deletes a model object's record from the database completely.
 		 *	WARNING: this method will completely delete the record.  If you do not wish for the
		 *	record to be removed from the database use a deleted field to indicate deletion.
 		 *	
 		 *	@return boolean Returns true if the record is successfully deleted, false if it is not.
 		 */
		function delete()
		{
			self::$db->set_model($this);

			$statement = 'DELETE FROM ' . $this->pluralize(get_class($this)) . ' WHERE id = ?';

			self::$db->prepare($statement);
			if (self::$db->execute(array($this->id), false))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		 *	Returns true if the object has been changed since instantiated.
		 *
		 *	This method is used to determine if the object's data has been changed since
		 *	it was instantiated.
		 *	
		 *	@return boolean Returns false if the object has not been changed, true if it has.
		 */
		function updated()
		{
			$changed = false;

			foreach ($this->snap_shot as $var => $value) 
			{
				if ($this->$var != $value)
				{
					$changed = true;
				}
			}

			return $changed;
		}

		/**
		 *	Gets the id of the object immediately after it has been saved for the first time.
		 *
		 *	This method gets the id of the object after it has been saved for the first time.
		 *	This is useful when the id is generated automatically using auto-increment, as it
		 *	is with the default method.
		 *	
		 *	@return integer Returns the unique id of the object.
		 */
		function get_id()
		{
			return self::$db->get_insert_id();
		}

		/**
		 *	Gets any messages stored in the object.
		 *
		 *	This method returns an array containing any messages stored in the object.
		 *	
		 *	@return string[] Returns an array containing any messages stored in the object.
		 */
		function messages()
		{
			return $this->messages;
		}

		/**
		 *	Gets a list of the error messages from failed validation rules.
		 *
		 *	This method returns an array of validation error messages that are associated with validation
		 *	rules that failed to pass when validation was run.
		 *	
		 *	@return string[] Array of validation error messages.
		 */
		function get_validation_errors()
		{
			return $this->validation_errors;
		}

		/**
		 *	Gets a list of the error messages from failed validation rules.
		 *
		 *	This method returns an array of validation error messages that are associated with validation
		 *	rules that failed to pass when validation was run.
		 *	
		 *	@deprecated	Deprecated in version 1.0, and replaced by get_validation_errors().
		 *	@return string[] Array of validation error messages.
		 */
		function get_validation_messages()
		{
			return $this->validation_errors;
		}

		/**
		 *	Returns the contents of the form property.
		 *
		 *	Returns the contents of the form property.
		 *	
		 *	@todo Find out what this is being used for, if anything.
		 */
		function form()
		{
			return $this->form;
		}

		/**
		 *	Used to log a user out of the website.
		 *
		 *	This method destroys the current session and returns the user to the ROOT location.
		 *
		 *	@todo Find a way to deprecate this method and replace it with something less generic.
		 */	
		function logout()
		{
			session_destroy();
			header('Location: ' . ROOT);
		}

		/**
		 *	?
		 *
		 *	?
		 *	
		 *	@todo Find out what this is being used for, if anything.
		 */
		function get_associations($table, $value, $return)
		{
			$table_data = explode(':', $table);
			$table = $table_data[1];

			$where_data = explode(':',$value);
			$where_data = explode('=', $where_data[1]);
			$col = $where_data[0];
			$val = $where_data[1];

			$return_data = explode(':', $return);
			$return = explode(',', $return_data[1]);

			$query = "SELECT ";

			$num_returns = count($return);
			$counter = 0;

			foreach ($return as $key => $value) {
				$query .= $value;
				if ($counter < $num_returns - 1) { $query .= ', '; }
				$counter ++;
			}

			$query .= ' FROM ' . $table . ' WHERE ' . $col . " = '" . $val . "'";

			if ($result = self::$db->query($query))
			{
				while ($line = $result->fetch_assoc())
				{
					$associations[] = $line;
				}
				if (isset($associations))
				{
					return $associations;
				}
			}
			else
			{
				echo $query;
				die(self::$db->error);
			}
		}

		/**
		 *	Used to override safe submission of a varialbe.
		 *
		 *	This method is used to turn off safety features for a given object property.
		 *	
		 *	@param string $var_name Name of the object property that is having safety features turned off.
		 *	@todo Look into this method and see if it is being used.
		 */
		protected function override_safe_submit($var_name)
		{
			$this->safe_submit_overrides[] = $var_name;
		}

		/**
		 *	Used to build a unique session varible key for the object.
		 *
		 *	This method is used to build a unique session variable key for the
		 *	model. It should be used whenever data from the model is being saved to a
		 *	session to prevent it from being overwritten.
		 *	
		 *	@param string $extension Unique name of the key. Should be descriptive of the data being stored.
		 *	@return string Returns the unique name of the session key.
		 */
		protected function build_session_key($extension)
		{
			$class = get_class($this);
			$key = 'Canvas:' . $class . ':' . $extension;
			return $key;
		}

		/**
		 *	Adds validation rules to be checked before the object can be saved.
		 *
		 *	This method adds validation rules, or validators, that must be passed before the object
		 *	can be saved to an object property.
		 *	
		 *	@param string $validators List of validation rules to be added to the object.
		 */
		protected function validate()
		{

			$count = func_num_args();
			$validator = array();

			for ($i=0; $i < $count; $i++) { 
				$validator[] = func_get_arg($i);
			}

			$this->validators[] = $validator;
		}

		/**
		 *	Used to add validation messages that can be displayed when a field does not pass validation.
		 *
		 *	This method is used to add validation messages that can be displayed when a field does 
		 *	not pass validation.
		 *	
		 *	@param string $variable The name of the variable that is being validated.
		 *	@param string $validator The type of validation being run on that variable.
		 *	@param string $message The message to be displayed when that validation fails.
		 */
		protected function add_validation_message($variable, $validator, $message)
		{
			$validation_message = array($variable, $validator, $message);

			$this->validation_messages[] = $validation_message;
		}

		/**
		 *	Checks to see if the data in the object validates.
		 *
		 *	Public method that runs the form validations declared in $validators and 
		 *	returns a boolean value set to true if the form validates andset to 
		 *	false if it does not.
		 *	
		 *	@return boolean Returns true if validation passes, false if it does not.
		 */
		public function form_validates() {
			return $this->run_validation();
		}

		/**
		 *	Private method that runs the form validations declared in $validators.
		 *
		 *	This method runs all of the form validations declared in $validators and
		 *	returns true if all of the validators pass, or false if they do not.
		 *	
		 *	@return boolean Returns true if validation passes, false if it does not.
		 */
		protected function run_validation()
		{
			$var;

			foreach ($this->validators as $validator)
			{
				foreach ($validator as $current_action)
				{
					$action_pair = explode(':', $current_action);

					//if the action pair is the variable name, or the keyword is_present
					if (count($action_pair) == 1)
					{
						if ($action_pair[0] == 'unique')
						{
							if (!$this->is_unique($var, $this->$var))
							{
								$this->check_for_validation_message($var, 'unique');
								$this->is_valid = false;
							}
						}
						else
						{
							//set the name of the variable being checked
							$var = $action_pair[0];
						}
					}
					else
					{
						switch ($action_pair[0])
						{
							case 'min-length':
								if (!validate::length($this->$var, $action_pair[1], 'min'))
								{
									$this->check_for_validation_message($var, 'min-length');
									$this->is_valid = false;
								}
								break;
							case 'max-length':
								if (!validate::length($this->$var, $action_pair[1], 'max'))
								{
									$this->check_for_validation_message($var, 'max-length');
									$this->is_valid = false;
								}
								break;
							case 'min-value':
								if (!validate::value($this->$var, $action_pair[1], 'min'))
								{
									$this->check_for_validation_message($var, 'min-value');
									$this->is_valid = false;
								}
								break;
							case 'max-value':
								if (!validate::value($this->$var, $action_pair[1], 'max'))
								{
									$this->check_for_validation_message($var, 'max-value');
									$this->is_valid = false;
								}
								break;
							case 'format':
								if (!validate::has_form($this->$var, $action_pair[1]))
								{
									$this->check_for_validation_message($var, 'format');
									$this->is_valid = false;
								}
								break;
							case 'match':
								if (!validate::match($this->$var, $action_pair[1]))
								{
									$this->check_for_validation_message($var, 'match');
									$this->is_valid = false;
								}
								break;
							default:
								if (ENVIRONMENT == 'development')
								{
									$message = "Canvas Error (Stay Calm!): ";
									$message .= "There was an error when executing " . __METHOD__ . "() on line " . __LINE__;
									$message .= "- " . $action_pair[0] . " is an invalid validation method.";

									$this->print_error($message);
								}
								else
								{
									die(PRODUCTION_ERROR_MESSAGE);
								}	
						}
					}
				}
			}

			return $this->is_valid;
		}

		/**
		 *	Used by the save method to build database queries.
		 *
		 *	This method is used by the save method to build its database queries.
		 *	
		 *	@return string Returns a complete PHP PDO SQL statement that can be used to save the model to the database.
		 */
		private function build_query()
		{
			$query = array();

			$statement = 'INSERT INTO ' . $this->pluralize(get_class($this)) . ' (';
			$values = array();

			$public_properties = $this->get_public_properties();

			$is_first = true;

			foreach ($this as $key => $value) 
			{
				if ((!$this->auto_id && $key == 'id') || (($this->id != null && $key == 'id') && ($this->id != 0 && $key == 'id')))
				{
					if ($is_first == false) { $statement .= ', '; }
					$statement .= $key;
				}

				if (($key != 'id') && ($key != 'db') && (in_array($key, $public_properties) && !in_array($key, $this->object_references)))
				{ 
					if ($is_first == false) { $statement .= ', '; }
					$statement .= $key;
				}

				$is_first = false;
			}

			$statement .= ') VALUES (';

			$is_first = true;
			foreach ($this as $key => $value)
			{
				if ((!$this->auto_id && $key == 'id') || (($this->id != null && $key == 'id') && ($this->id != 0 && $key == 'id')))
				{
					if ($is_first == false) { $statement .= ', '; }

					if ($value == 'now')
					{
						$statement .= 'NOW()';
					}
					else
					{
						$statement .= '?';
						$values[] = $value;
					}
				}
				
				if (($key != 'id') && ($key != 'db') && (in_array($key, $public_properties) && !in_array($key, $this->object_references)))
				{ 
					if ($is_first == false) { $statement .= ', '; }

					if ($value === 'now')
					{
						$statement .= 'NOW()';
					}
					else
					{
						$statement .= '?';
						$values[] = $value;
					}
				}

				$is_first = false;
			}

			$statement .= ') ON DUPLICATE KEY UPDATE ';

			$is_first = true;

			foreach ($this as $column => $value) {
				if (($column != 'id') && ($column != 'db') && (in_array($column, $public_properties) && !in_array($column, $this->object_references)))
				{ 
					if ($is_first == false) { $statement .= ', '; }
					$statement .= $column . "= ?";
					$values[] = $value;
					$is_first = false;
				}
			}

			$query['statement'] = $statement;
			$query['values'] = $values;
			return $query;
		}

		/**
		 *	Used to get a list of the public properties contained in the object.
		 *
		 *	This method is used to get a list of the public properties contained in the object.
		 *	The list of public properties can be used to determine, which columns exist in the database as
		 *	properties that do not corespond to a database column should be protected or private.
		 *	
		 *	@return string[] Array containing a list of the object's pupblic properties.
		 */
		private function get_public_properties()
		{
			$reflection = new \ReflectionObject($this);
			$properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

			$public_properties = array();

			foreach ($properties as $property) {
				$public_properties[] = $property->getName();
			}
	
			return $public_properties;
		}

		/**
		 *	Returns the database table name for this class.
		 *
		 *	Returns the database table name for this class.
		 *	
		 *	@return string The name of the table that corresponds to the current model.
		 */
		function get_table_name()
		{
			return get_class($this) . 's';
		}

		private function base_model_get_all($query)
		{
			if ($result = self::$db->query($query))
			{
				if ($result->num_rows > 0)
				{
					$array = $result->fetch_assoc();

					foreach ($array as $key => $value) {
						$this->$key = $value;
					}

					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				echo "Attempted query = " . $query;
				die(self::$db->error);
			}
		}

		/**
		 *	Checks to see if a validation error message has already been added.
		 *
		 *	Searches through all of the model's validation messages to see if there is one 
		 *	that applies, and has not already been added, and puts it into messages if there is.
		 *	
		 *	@param string $variable Name of the variable that is being validated.
		 *	@param string $validation The type of validation being run.
		 */
		private function check_for_validation_message($variable, $validation)
		{
			foreach($this->validation_messages as $validation_message)
			{
				
				if ($validation_message[0] == $variable && $validation_message[1] == $validation)
				{
					//Search messages to see if the message already exists.
					$message_exists = false;

					foreach($this->validation_errors as $message)
					{
						if ($message == $validation_message[2])
						{
							$message_exists = true;
						}
					}

					if ($message_exists == false)
					{
						$this->validation_errors[] = $validation_message[2];
					}
				}
			}
		}

		/**
		 *	Used by find_all() to build a database query and conduct query.
		 *
		 *	This method is used by the now deprecated find_all method to build a database query and
		 *	then carry out the search.
		 *	
		 *	@return string[] Returns an associative array containing the results of the query.
		 *	@deprecated	This method has been deprecated as of version 1.0
		 *	
		 */
		private function find_all_assoc_array($assoc_array)
		{
			$statement = 'SELECT * FROM ' . get_class($this) . 's WHERE ';
			$counter = 0;
			$values = array();

			foreach ($assoc_array as $col => $value)
			{
				if ($counter > 0)
				{	
					$statement .= ' AND ';
				}

				$statement .= $col . ' = ?';
				$values[] = $value;
				$counter++;
			}

			self::$db->prepare($statement);
			$result = self::$db->execute($values);

			return $result;
		}
	}
?>