<?php
    /*-------------------------------------------------------------------------------
        Serenity - "Serene PHP made easy."

        Developer: Patrick Stephens
        Email: pstephens2601@gmail.com
        Github Repository: https://github.com/pstephens2601/Serenity
        Creation Date: 2-24-2014
        Last Edit Date: 3-21-2014

        Class Notes - The mail class can be used to build and send emails from your
        application.
    ---------------------------------------------------------------------------------*/
    namespace canvas;
    
    class mail extends canvasObject
    {
    	private $to;
    	private $from;
    	private $reply_to;
    	private $return_path;
    	private $header;
    	private $subject;
    	private $message;
        private $content_type;

    	function __construct($to)
    	{
    		$this->to = $to;
        }
        
        function set_from($address)
        {
        	$this->from = "From: $address\r\n";
        	$this->reply_to = "Reply-To: $address\r\n";
    		$this->return_path = "Return-Path: $address\r\n";
        }

        function set_content_type($type)
        {
            if (strtolower($type) == 'html')
            {
                $this->content_type = "Content-Type: text/html; charset=ISO-8859-1\r\n";
            }
            else
            {
                $this->content_type = "Content-Type: text/plain\r\n";
            }
        }

        function set_subject($subject)
        {
        	$this->subject = $subject;
        }

        function set_message($message)
        {
        	$this->message = $message;
        }

        function send()
        {
        	$this->header = $this->from . $this->reply_to . $this->return_path;
            $this->header .= "MIME-Version: 1.0\r\n";

            if ($this->content_type != '')
            {
                $this->header .= $this->content_type;
            }

        	if (mail($this->to, $this->subject, $this->message, $this->header))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }
?>