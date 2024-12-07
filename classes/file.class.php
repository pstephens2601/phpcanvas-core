<?php
	/*-------------------------------------------------------------------------------
		Serenity - "Serene PHP made easy."

		Developer: Patrick Stephens
		Email: pstephens2601@gmail.com
		Github Repository: https://github.com/pstephens2601/Serenity
		Creation Date: 3-21-2014
		Last Edit Date: 3-21-2014

		Class Notes - The file class is used to quickly create and edit text
		files for data storage or user download.
	---------------------------------------------------------------------------------*/
	namespace canvas;

	class file extends canvasObject
	{
		private $handle;
		private $path;
		private $extension; //file extension such as .txt or .csv

		//creates a new file.
		function create($file_path) {
			if (file_exists($file_path))
			{
				if (ENVIRONMENT == 'development')
				{
					$message = "Serene Error (Stay Calm!): ";
					$message .= "There was an error when executing " . __METHOD__ . "() on line " . __LINE__;
					$message .= "- the file " . $file_path . " already exists.";

					$this->print_error($message);
				}
				else
				{
					die(PRODUCTION_ERROR_MESSAGE);
				}
			}
			else
			{
				if (!$this->handle = fopen($file_path, 'w'))
				{
					if (ENVIRONMENT == 'development')
					{
						$message = "Serene Error (Stay Calm!): ";
						$message .= "There was an error when executing " . __METHOD__ . "() on line " . __LINE__;
						$message .= "- the file " . $file_path . " could not be created.";

						$this->print_error($message);
					}
					else
					{
						die(PRODUCTION_ERROR_MESSAGE);
					}
				}
				else
				{
					$this->path = $file_path;
				}
			}
		}

		function open($file_path)
		{
			if (!$this->handle = fopen($file_path, 'w'))
			{
				if (ENVIRONMENT == 'development')
				{
					$message = "Serene Error (Stay Calm!): ";
					$message .= "There was an error when executing " . __METHOD__ . "() on line " . __LINE__;
					$message .= "- the file " . $file_path . " could not be opened.";

					$this->print_error($message);
					return false;
				}
				else
				{
					return false;
				}
			}
			else
			{
				$this->path = $file_path;
			}
		}

		function delete()
		{
			fclose($this->handle);
			unlink($this->path);
		}

		function write($data)
		{
			fwrite($this->handle, $data);
		}

		function exists($file_path)
		{
			if (file_exists($file_path))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		function __destruct()
		{
			if (get_resource_type($this->handle) == false)
			{
				fclose($this->handle);
			}
		}
	}
?>