<?php
	namespace canvas;

	/**
	 *	The pdf class is used to manipulate pdf forms.
	 *
	 *	The pdf library class can be used to insert data into an existing
	 *	pdf form, or create a PDF file from scratch.
	 *
	 *	@author Patrick Stephens <pstephens2601@gmail.com>
	 *	@version 1.0
	 * 	@package Canvas Library
	 */
	class pdf extends canvasObject
	{
		/** @var string $template Filename of the PDF file that data will be inserted into. */
		private $template;
		/** @var string $output_file Filename of the final PDF file that will be saved. */
		private $output_file;
		/** @var string $destination Folder where the temporary PDF will be saved. */
		private $destination;

		function __construct($template = null, $data = null, $encoding = 'UTF-8')
		{
			$this->template = $template;
			$this->encoding = $encoding;

			if (is_array($data))
			{
				$this->data = $data;
			}
			else
			{
				throw new canvasException('Invalid parameter passed to pdf constructor.  Array required for data.');
			}
		}

		function get_template()
		{
			return $this->template;
		}

		function get_output_file()
		{
			return $this->output_file;
		}

		function get_encoding()
		{
			return $this->encoding;
		}

		function create_pdf()
		{

		}

		function view()
		{
			echo $this->generate_xml();
		}

		private function generate_xml()
		{
		    $xml = '<?xml version="1.0" encoding="' . $this->encoding . '"?>'."\n"; 
		    $xml .= '<xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve">' . "\n";
		    $xml .= '<f href="'. $this->template . '" />' . "\n";
		    $xml .= '<ids original="' . md5($this->template) . '" modified="' . time() . '" />'."\n";
		    $xml .= '<fields>' . "\n";

		    foreach($this->data as $field => $val) 
		    {
		        $xml .= '<field name="'. $field . '">' . "\n";

		        if (is_array($val))
		        { 
		            foreach($val as $opt)
		           	{ 
		                $xml .= '<value>' . htmlentities($opt) . '</value>' . "\n";
		            }
		        }
		        else
		        { 
		            $xml .= '<value>' . htmlentities($val) . '</value>' . "\n"; 
		        }

		        $xml .= '</field>' . "\n"; 
		    }

		    $xml .= '</fields>' . "\n";      
		    $xml .= '</xfdf>'."\n"; 
		    
		    return $xml; 
		}
	}
?>