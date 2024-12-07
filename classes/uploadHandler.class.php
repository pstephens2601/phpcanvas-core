<?php
	/*-------------------------------------------------------------------------------
        Serenity - "Serene PHP made easy."

        Developer: Patrick Stephens
        Email: pstephens2601@gmail.com
        Github Repository: https://github.com/pstephens2601/Serenity
        Creation Date: 8-9-2014
        Last Edit Date: 3-21-2014

        Class Notes - 
    ---------------------------------------------------------------------------------*/
    namespace canvas;

    class uploadHandler extends canvasObject
    {
    	private $allowed_extensions = array();
    	private $allowed_type;
    	private $max_file_size;
    	private $input_name;
    	private $temp_name;
    	private $save_path;
    	private $file_name;
    	private $extension;
    	private $folder;
    	private $error_log = array();

    	function __construct()
    	{
    		if (func_num_args() == 1)
    		{
    			$this->input_name = func_get_arg(0);
    			$this->temp_name = $_FILES[$this->input_name]["tmp_name"];

                // Set default file name equal to file's original name minus extension
                $file_name_array = explode('.', $_FILES[$this->input_name]["name"]);
                $this->file_name = $file_name_array[0];

                // Set default file extension
                $this->extension = $file_name_array[1];
    		}
    	}

    	function set_allowed_extensions($extensions)
    	{
    		$this->allowed_extensions = $extensions;
    	}

    	function set_input_name($input_name)
    	{
    		$this->input_name = $input_name;
    		$this->temp_name = $_FILES[$this->input_name]["tmp_name"];
    	}

    	function set_file_name($name)
    	{
    		$this->file_name = $name;
    	}

    	function set_folder($folder_path)
    	{
    		$this->folder = $folder_path;
    	}

    	function set_max_size($size)
    	{
    		if ($size > 0)
    		{
    			$this->max_file_size = $size;
    		}
    	}

        function get_extension()
        {
            return $this->extension;
        }

        function get_file_name()
        {
            return $this->file_name;
        }

        function get_error_log()
        {
            return $this->error_log;
        }

    	function valid_size()
    	{
    		if ($_FILES[$this->input_name]['size'] <= $this->max_file_size)
    		{
    			return true;
    		}
    		else
    		{
    			return false;
    		}
    	}

    	function valid_extension()
    	{
    		$file = explode('.', $_FILES[$this->input_name]["name"]);
    		$this->extension = end($file);
    		foreach ($this->allowed_extensions as $extension)
    		{
    			if ($this->extension == $extension)
    			{
    				return true;
    			}
    		}

    		return false;
    	}

        function validate()
        {
            if ($this->valid_extension())
            {
                if ($this->valid_size())
                {
                    if ($this->no_errors())
                    {
                        if ($this->save_path == null)
                        {
                            $this->save_path = $this->folder . '/' . $this->file_name . '.' . $this->extension;
                        }

                        if (file_exists($this->save_path))
                        {
                            $error_message = 'The file ' . $this->file_name . '.' . $this->extension . ' already exist at this location.';
                            $this->error_log[] = $error_message;
                            return false;
                        }
                        else
                        {
                            return true;
                        }
                    }
                    else
                    {
                        $error_message = 'There was an error uploading your file.';
                        $this->error_log[] = $error_message;
                        return false;
                    }
                }
                else
                {
                    $error_message = 'This file exceeds the maximum file size for upload.';
                    $this->error_log[] = $error_message;
                    return false;
                }
            }
            else
            {

                $error_message = 'The file extension .' . $this->extension. ' is an invalid file type.';
                $this->error_log[] = $error_message;
                return false;
            }
        }

    	function no_errors()
    	{
    		if ($_FILES[$this->input_name]["error"] < 1)
    		{
    			return true;
    		}
    		else
    		{
    			return false;
    		}
    	}

        function clear_existing()
        {
            if ($this->extension == null)
            {
                $file = explode('.', $_FILES[$this->input_name]["name"]);
                $this->extension = end($file);
            }

            $this->save_path = $this->folder . '/' . $this->file_name . '.' . $this->extension;
            $file = $_SERVER['DOCUMENT_ROOT'] . ROOT . $this->save_path;

            if (file_exists($file))
            {
                unlink($file);
            }
            else
            {
               
            }
        }

        function clear($file)
        {
            $save_path = $this->folder . '/' . $file;
            $file = $_SERVER['DOCUMENT_ROOT'] . ROOT . $save_path;

            if (file_exists($file))
            {
                unlink($file);
            }
            else
            {
               
            }
        }

    	function save_file()
    	{

    		if ($this->valid_extension() && $this->valid_size() && $this->no_errors())
    		{
    			if ($this->save_path == null)
	    		{
	    			$this->save_path = $this->folder . '/' . $this->file_name . '.' . $this->extension;
	    		}

    			if (file_exists($this->save_path))
    			{
    				$error_message = 'The file ' . $this->file_name . ' already exist at the location ' . $this->folder;
    				$this->error_log[] = $error_message;

    				throw new canvasException($error_message, 1);

    				return false;
    			}
    			else
    			{
    				move_uploaded_file($this->temp_name, $_SERVER['DOCUMENT_ROOT'] . ROOT . $this->save_path);
    			}
    		}
    		else
    		{
    			throw new canvasException("File did not validate.", 1);
    		}
    	}
    }
?>