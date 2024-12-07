<?php
	/*-------------------------------------------------------------------------------
		Serenity - "Serene PHP made easy."

		Developer: Patrick Stephens
		Email: pstephens2601@gmail.com
		Github Repository: https://github.com/pstephens2601/Serenity
		Creation Date: 8-20-2013
		Last Edit Date: 6-24-2014

		Class Notes - Built on top of the mysqli class the database class handles 
		interaction with the database.  A database object is created in the 
		constructor of every model and can be accessed from within the model class 
		using $this->db.
	---------------------------------------------------------------------------------*/
	namespace canvas;
	use PDO;

	class database extends canvasObject
	{
		private $host;
		private $user;
		private $password;
		private $database_name;
		private $database_type;
		private $table;
		private $handle;
		private $model;
		private $query_object;
		private $query_statement;
		private $rows = array();
		public $error;

		// possible parameters  (db_type)
		function __construct($model)
		{
			$this->host = HOST;
			$this->user = USER;
			$this->password = PASSWORD;
			$this->database_name = DATABASE;

			$num_args = func_num_args();

			$this->model = $model;

			//set database type
			if ($num_args > 1)
			{
				$this->database_type = func_get_arg(1);
			}
			else
			{
				$this->database_type = DATABASE_TYPE;
			}
		}

		// kill database connection when model is destroyed
		function __destruct()
		{
			$this->handle = null;
		}

		function get_errors()
		{
			return $this->handle->errorInfo();
		}

		function verify_table($model)
		{
			$this->verify_table_exists($model);
		}

		function set_host($new_host)
		{
			$this->host = $new_host;
		}

		function set_user($new_user)
		{
			$this->user = $new_user;
		}

		function set_password($new_password)
		{
			$this->password = $new_password;
		}

		function set_model($new_model)
		{
			$this->model = $new_model;
		}

		function set_database_name($new_database_name)
		{
			$this->database = $new_database_name;
		}

		function set_database_type($new_database_type)
		{
			$this->database_type_type = $new_database_type;
		}

		function connect()
		{
			switch ($this->database_type)
			{
				case 'mysql':
					try
					{
						$database = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->database_name . ';charset=utf8', $this->user, $this->password);
						$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$this->handle = $database;
						$this->verify_table_exists($this->model);
					}
					catch (\PDOException $e)
					{
						try
						{
							throw new canvasException('CANVAS ERROR[2]: Unable to connect to database \'' . $this->database_name . '\'. ERROR MESSAGE: ' . $e->getMessage(), 2);
							//$this->create_database($this->database_name);
						}
						catch(\PDOException $e)
						{
							throw new canvasException('CANVAS ERROR[2]: Unable to connect to database \'' . $this->database_name . '\'. ERROR MESSAGE: ' . $e->getMessage(), 2);
						}	
					}
					break;
			}
		}

		function create_database($db_name)
		{
			try
			{
				$database = new PDO('mysql:host=' . $this->host, $this->user, $this->password);
				$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->handle = $database;
				$query_string = "CREATE DATABASE IF NOT EXISTS $db_name";
				$this->query($query_string);
				$this->query("use $db_name");
			}
			catch (\PDOException $e)
			{
				throw new canvasException('CANVAS ERROR[3]: Unable to create database \'' . $this->database_name . '\'.', 3);
			}
		}

		function execute($data)
		{
			$this->rows = array();

			if(func_num_args() > 1)
			{ 
				if (is_bool(func_get_arg(1)))
				{
					$return_value = func_get_arg(1);

					// if the query is a save() there will be no return value
					if (!$return_value)
					{
						try
						{
							$this->query_object->execute($data);
							return true;
						}
						catch (\PDOException $e)
						{
							// IF: the first attempt throw an exception update the columns then try again
							try
							{
								$this->update_columns();
								$this->query_object->execute($data);
								return true;
							}
							catch (\PDOException $e)
							{
								throw new canvasException('CANVAS ERROR[5]: Unable to execute SQL query \'' . $this->query_statement . '\'.', 5, $e);
							}	
						}
					}
					else
					{
						$this->query_object = $this->prepare(func_get_arg(1));

						try
						{
							$this->query_object->execute($data);

							while ($row = $this->query_object->fetch())
							{
								$this->rows[] = $row;
							}
						}
						catch (\PDOException $e)
						{
							if (ENVIRONMENT == 'development')
							{
								$this->print_exception($e);
							}
						}
					}
				}
				else
				{
					$this->query_object = $this->prepare(func_get_arg(1));

					try
					{
						$this->query_object->execute($data);

						while ($row = $this->query_object->fetch())
						{
							$this->rows[] = $row;
						}
					}
					catch (\PDOException $e)
					{
						if (ENVIRONMENT == 'development')
						{
							$this->print_exception($e);
						}
					}
				}
			}
			else
			{
				try
				{
					$this->query_object->execute($data);
					while ($row = $this->query_object->fetch())
					{
						$this->rows[] = $row;
					}
				}
				catch (\PDOException $e)
				{
					throw new canvasException('CANVAS ERROR[5]: Unable to execute SQL query \'' . $this->query_statement . '\'. Error message [' . $e->getMessage() . ']', 5);
				}
			}

			if (empty($this->rows))
			{
				return false;
			}
			else
			{
				return $this->rows;
			}
		}

		function fetch()
		{
			if (func_num_args() < 1)
			{
				return $this->rows;
			}
			else if (func_num_args() == 1)
			{

			}
		}

		function prepare($query_frame)
		{
			$this->query_statement = $query_frame;
			$this->query_object = $this->handle->prepare($query_frame);
		}

		function query($query_string)
		{
			try
			{
				$result = $this->handle->query($query_string);
				return $result;
			}
			catch(\PDOException $e)
			{
				return false;
			}
		}

		function quote($data)
		{
			return $this->handle->quote($data);
		}

		function get_insert_id()
		{
			return $this->handle->lastInsertId();
		}

		private function verify_table_exists($model)
		{
			$table_name = $this->pluralize(get_class($model));

			try
			{
				if (is_object($this->handle))
				{
					$db = $this->handle;
					$db->query("SELECT 1 FROM $table_name LIMIT 1");
				}
				else
				{
					throw new canvasException('CANVAS ERROR [4]: Unable to find database connection to \'' . $this->database_name . '\'.', 4);	
				}
			}
			catch (\PDOException $e)
			{
				if (DATABASE_AUTOBUILD)
				{
					try
					{

						$columns = $this->get_columns($model);
						$db->query("CREATE TABLE $table_name ($columns)");
					}
					catch (\PDOException $e)
					{
						throw new canvasException('CANVAS ERROR [4]: ' . $e->getMessage() . " CREATE TABLE $table_name ($columns)", 4);
					}
				}
				else
				{
					throw new canvasException('CANVAS ERROR [4]: ' . $e->getMessage(), 4);
				}
			}
		}

		private function update_columns()
		{
			$model = $this->model;
			$columns = array();
			$query = "DESCRIBE " . $this->pluralize(get_class($model));

			$sketch_name = $this->pluralize(get_class($model)) . '_sketch';
			$sketch = new $sketch_name;
			$sketch_columns = $sketch->get_structure();

			$results = $this->query($query);
			
			while ($column = $results->fetch())
			{
				$columns[] = $column['Field'];
			}
			
			if ($this->add_columns($columns, $sketch_columns) && $this->drop_columns($columns, $sketch_columns))
			{	
				return true;
			}
			else
			{
				return false;
			}
		}

		private function add_columns($columns, $sketch_columns)
		{
			// Loop through columns defined in the sketch and add any missing columns
			$missing_columns = array();
			$previous_column = null;

			foreach ($sketch_columns as $name => $attr)
			{
				if (!in_array($name, $columns))
				{
					$missing_columns[$name] = array($previous_column, $attr);
				}

				$previous_column = $name;
			}

			$update_query = "ALTER TABLE " . $this->pluralize(get_class($this->model));
			$num_columns = count($missing_columns);
			$count = 1;

			foreach ($missing_columns as $name => $attr)
			{
				if ($attr[0] != null)
				{
					$update_query .= " ADD " . $name . ' '  . $attr[1] . " AFTER " . $attr[0];
				}
				else
				{
					$update_query .= " ADD " . $name . ' '  . $attr[1] . " FIRST";
				}

				if ($count < $num_columns)
				{
					$update_query .= ',';
				}

				$count ++;
			}

			if ($num_columns > 0)
			{
				if ($this->query($update_query))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return true;
			}
		}

		private function drop_columns($columns, $sketch_columns)
		{
			// Loop through columns defined in the sketch and add any missing columns
			$extra_columns = array();

			foreach ($columns as $name)
			{
				if (!array_key_exists($name, $sketch_columns) && $name != 'id')
				{
					$extra_columns[] = $name;
				}
			}

			$update_query = "ALTER TABLE " . $this->pluralize(get_class($this->model));
			$num_columns = count($extra_columns);
			$count = 1;

			foreach ($extra_columns as $col)
			{
				$update_query .= " DROP " . $col;

				if ($count < $num_columns)
				{
					$update_query .= ',';
				}

				$count ++;
			}

			if ($num_columns > 0)
			{
				if ($this->query($update_query))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return true;
			}
		}

		private function get_columns($model)
		{
			$sketch_name = $this->pluralize(get_class($model)) . '_sketch';
			$sketch = new $sketch_name;
			$columns = $sketch->get_structure();

			if ($model->get_auto_id())
			{
				$column_string = 'id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), ';
			}
			else
			{
				$column_string = 'id INT NOT NULL, PRIMARY KEY(id), ';
			}

			$array_length = count($columns);
			$counter = 1;

			foreach($columns as $col_name => $data_type)
			{
				$column_string .= $col_name . ' ' . $data_type;

				if ($counter < $array_length)
				{
					$column_string .= ', ';
				}

				$counter++;
			}

			return $column_string;
		}
	}
?>