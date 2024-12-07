<?php
	namespace canvas;

	class canvasException extends \Exception
	{
		private $datetime;
		private $log;
		private $verbose_logging = false;

		function __construct($message, $code = 0, $previous = null)
		{
			// build file path to log
			$log_path = explode(DIRECTORY_SEPARATOR, __DIR__);

			for ($i = 0; $i < 2; $i++)
			{
				array_pop($log_path);
			}

			$log_path[] = 'logs';
			$log_path[] = date('D_M_d_Y') . '_errors.log';
			$log_path = implode(DIRECTORY_SEPARATOR, $log_path);
			$this->log = $log_path;
			parent::__construct($message, $code, $previous);
			$this->datetime = date('m-d-Y h:i:s');
		}

		function log($log_message = null)
		{
			if (file_exists($this->log))
			{
				$log = fopen($this->log, 'a');
				fwrite($log, $this->build_log_message());
				fclose($log);
			}
			else
			{
				$log = fopen($this->log, 'w');
				fwrite($log, $this->build_log_message());
				fclose($log);
			}

			if (AUTO_EMAIL_ERROR_NOTIFICATIONS)
			{
				$this->email();
			}
		}

		function email($address = null)
		{
			if ($address == null)
			{
				$address = ADMIN_EMAIL;
			}

			require_once(__DIR__ . DIRECTORY_SEPARATOR . 'mail.class.php');
			
			$email = new mail($address);
			$email->set_from(APP_EMAIL_ADDRESS);
			$email->set_content_type('text');
			$email->set_subject(APP_ERROR_EMAIL_SUBJECT);
			$email->set_message($this->build_email_message());
			
			if (!$email->send())
			{
				echo "Email not sent.";
			}
		}

		function set_verbose()
		{
			$this->verbose_logging = true;
		}

		function set_simple()
		{
			$this->verbose_logging = false;
		}

		function set_file($file_path)
		{
			$this->file = $file_path;
		}

		function set_line($line)
		{
			$this->line = $line;
		}

		function set_trace($backtrace)
		{

		}

		private function build_log_message()
		{
			$message = '[' . $this->datetime . '] > File [' . $this->getFile() . '] > Line [' . $this->getLine() . '] > Error Code [' . $this->getCode() . '] > Page [' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ']';
			
			if ($this->verbose_logging || $this->getCode() == 0)
			{
				$message .= " > \n\tMessage [" . $this->getMessage() . "]";
				
				if ($this->verbose_logging)
				{
					$message .= " >\n\tTrace [ \n";
					$backtrace = $this->getTrace();

					foreach ($backtrace as $line)
					{
						$message .= "\t\tFile [" . $line['file'] . '] > Line [' . $line['line'] . '] > Function [' . $line['function'] . '(';
						
						foreach ($line['args'] as $arg)
						{
							if (!is_array($arg) && !is_object($arg))
							{
								$message .= $arg . ',';
							}
						}

						$message .= ")]\n";
					}
					$message .= "\t]";
				}
			}

			$message .= ";\n\n";
			return $message;
		}

		private function build_email_message()
		{
			$message = '';
			$message .= $this->build_log_message();
			return $message;
		}
	}
?>