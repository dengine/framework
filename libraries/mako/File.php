<?php

namespace mako
{
	use \mako\Mako;
	use \RuntimeException;
	
	/**
	* Collection of file related methods.
	*
	* @author     Frederic G. Østby
	* @copyright  (c) 2008-2011 Frederic G. Østby
	* @license    http://www.makoframework.com/license
	*/

	class File
	{
		//---------------------------------------------
		// Class variables
		//---------------------------------------------

		// Nothing here

		//---------------------------------------------
		// Class constructor, destructor etc ...
		//---------------------------------------------

		/**
		* Protected constructor since this is a static class.
		*
		* @access  protected
		*/

		protected function __construct()
		{
			// Nothing here
		}

		//---------------------------------------------
		// Class methods
		//---------------------------------------------

		/**
		* Will convert bytes to a more human friendly format.
		*
		* @access  public
		* @param   int      Filesize in bytes
		* @param   boolean  (optional) True to use binary prefixes and false to use decimal prefixes
		* @return  string
		*/

		public static function humanSize($bytes, $binary = true)
		{
			if($bytes > 0)
			{
				if($binary === true)
				{
					$base  = 1024;
					$terms = array('byte', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
				}
				else
				{
					$base  = 1000;
					$terms = array('byte', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
				}

				$e = floor(log($bytes, $base));

				return round($bytes / pow($base, $e), 2) . ' ' . $terms[$e];
			}
			else
			{
				return '0 byte';
			}
		}
		
		/**
		* Returns the mime type of a file. Returns false if the mime type is not found.
		*
		* @access  public
		* @param   string   Full path to the file
		* @param   boolean  (optional) Set to false to disable mime type guessing
		* @return  string
		*/
		
		public static function mime($file, $guess = true)
		{
			if(function_exists('finfo_open'))
			{
				// Get mime using the file information functions
				
				$info = finfo_open(FILEINFO_MIME_TYPE);
				
				$mime = finfo_file($info, $file);
				
				finfo_close($info);
				
				return $mime;
			}
			else
			{
				if($guess === true)
				{
					// Just guess mime by using the file extension

					static $mimeTypes;

					if(empty($mimeTypes))
					{
						$mimeTypes = Mako::config('file/mime_types');
					}

					$extension = pathinfo($file, PATHINFO_EXTENSION);

					return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : false;
				}
				else
				{
					return false;
				}
			}
		}

		/**
		* Forces a file to be downloaded.
		*
		* @access  public
		* @param   string  Full path to file
		* @param   string  (optional) Content type of the file
		* @param   string  (optional) Filename of the download
		* @param   int     (optional) Max download speed in KiB/s
		*/

		public static function download($file, $contentType = null, $filename = null, $kbps = 0)
		{
			// Check that the file exists and that its readable

			if(file_exists($file) === false || is_readable($file) === false)
			{
				throw new RuntimeException(__CLASS__ . ": Failed to open stream.");
			}

			// Empty output buffers

			while(ob_get_level() > 0) ob_end_clean();

			// Send headers
			
			if($contentType === null)
			{
				$contentType = static::mime($file);
			}

			if($filename === null)
			{
				$filename = basename($file);
			}

			header('Content-type: ' . $contentType);
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . filesize($file));

			// Read file and write it to the output

			set_time_limit(0);

			if($kbps === 0)
			{
				readfile($file);
			}
			else
			{
				$handle = fopen($file, 'r');

				while(!feof($handle) && !connection_aborted())
				{
					$s = microtime(true);

					echo fread($handle, round($kbps * 1024));

					if(($wait = 1e6 - (microtime(true) - $s)) > 0)
					{
						usleep($wait);
					}
					
				}

				fclose($handle);
			}

			exit();
		}
	}
}

/** -------------------- End of file --------------------**/