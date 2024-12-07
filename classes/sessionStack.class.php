<?php
	/*-------------------------------------------------------------------------------
        Serenity - "Serene PHP made easy."

        Developer: Patrick Stephens
        Email: pstephens2601@gmail.com
        Github Repository: https://github.com/pstephens2601/Serenity
        Creation Date: 7-28-2014
        Last Edit Date: 7-28-2014

        Class Notes - The sessionStack class provides Serenity with a built in class
        that is persistant across pages.
    ---------------------------------------------------------------------------------*/
    namespace canvas;

    class sessionStack extends canvasObject
    {
    	private $stack_array = array();
    	private $stack_name;

    	function __construct($stack_name)
    	{
    		if (!isset($_SESSION['canvasSessionStack-' . $this->stack_name]))
    		{
    			$_SESSION['canvasSessionStack' . $this->stack_name] = array();
    		}
    		else
    		{
    			$this->stack_array = $_SESSION['canvasSessionStack-' . $this->stack_name];
    		}
    	}

    	function push($data)
    	{
    		$this->stack_array[] = $data;
    	}

    	function pop()
    	{
    		return array_pop($this->stack_array);
    	}	

    	function peek()
    	{
    		if (func_num_args() == 1)
    		{
    			end($this->stack_array);

    			for ($i = 0; $i < func_get_arg(0); $i++)
    			{
    				$item = prev($this->stack_array);
    			}

    			return $item;
    		}
    		else
    		{
    			$last_item = end($this->stack_array);
    			reset($this->stack_array);
    			return $last_item;
    		}
    		
    		
    	}

    	function is_empty()
    	{

    	}

    	function to_string()
    	{
    		print_r($this->stack_array);	
    	}

    	function __destruct()
    	{
    		$_SESSION['canvasSessionStack-' . $this->stack_name] = $this->stack_array;
    	}
    }
?>	