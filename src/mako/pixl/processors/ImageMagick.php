<?php

namespace mako\pixl\processors;

use \Imagick;
use \ImagickPixel;
use \InvalidArgumentException;
use \RuntimeException;

use \mako\pixl\Image;

/**
 * ImageMagick processor.
 *
 * @author     Frederic G. Østby
 * @copyright  (c) 2008-2014 Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

class ImageMagick implements \mako\pixl\processors\ProcessorInterface
{
	//---------------------------------------------
	// Class properties
	//---------------------------------------------

	/**
	 * Imagick instance.
	 * 
	 * @var \Imagick
	 */

	protected $image;

	//---------------------------------------------
	// Class constructor, destructor etc ...
	//------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @access  public
	 * @param   string  $file  Path to the image file
	 */

	public function __construct()
	{
		if(class_exists('\Imagick', false) === false)
		{
			throw new RuntimeException(vsprintf("%s(): Imagick is not available.", [__METHOD__]));
		}
	}

	/**
	 * Destructor.
	 *
	 * @access  public
	 */

	public function __destruct()
	{
		if($this->image instanceof Imagick)
		{
			$this->image->destroy();
		}	
	}

	//---------------------------------------------
	// Class methods
	//---------------------------------------------

	/**
	 * Checks if the HEX value is valid.
	 * 
	 * @access  protected
	 * @param   string     $hex  HEX value
	 */

	protected function checkHEX($hex)
	{
		$hex = str_replace('#', '', $hex);
		
		if(preg_match('/^([a-f0-9]{3}){1,2}$/i', $hex) === 0)
		{
			throw new InvalidArgumentException(vsprintf("%s(): Invalid HEX value [ %s ].", [__METHOD__, $hex]));
		}
	}

	/**
	 * Add the hash character (#) if its missing.
	 * 
	 * @access  public
	 * @param   string  $hex  HEX value
	 * @return  string
	 */

	public function normalizeHEX($hex)
	{
		return (strpos($hex, '#') !== 0) ? '#' . $hex : $hex;
	}

	/**
	 * Sets the image we want to work with.
	 * 
	 * @access  public
	 * @param   string  $image  Path to image file
	 */

	public function setImage($image)
	{
		$this->image = new Imagick($image);
	}

	/**
	 * Rotates the image using the given angle in degrees.
	 *
	 * @access  public
	 * @param   int     $degrees  Degrees to rotate the image
	 */

	public function rotate($degrees)
	{
		$this->image->rotateImage(new ImagickPixel('none'), (360 - $degrees));
	}

	/**
	 * Resizes the image to the chosen size. 
	 *
	 * @access  public
	 * @param  int      $width        Width of the image
	 * @param  int      $height       (optional) Height of the image
	 * @param  int      $aspectRatio  (optional) Aspect ratio
	 */

	public function resize($width, $height = null, $aspectRatio = Image::RESIZE_IGNORE)
	{
		$w = $this->image->getImageWidth();
		$h = $this->image->getImageHeight();

		if($height === null)
		{				
			$newWidth  = round($w * ($width / 100));
			$newHeight = round($h * ($width / 100));
		}
		else
		{
			if($aspectRatio === Image::RESIZE_AUTO)
			{
				// Calculate smallest size based on given height and width while maintaining aspect ratio

				$percentage = min(($width / $w), ($height / $h));

				$newWidth  = round($w * $percentage);
				$newHeight = round($h * $percentage);
			}
			else if($aspectRatio === Image::RESIZE_WIDTH)
			{
				// Base new size on given width while maintaining aspect ratio

				$newWidth  = $width;
				$newHeight = round($h * ($width / $w));
			}
			else if($aspectRatio === Image::RESIZE_HEIGHT)
			{
				// Base new size on given height while maintaining aspect ratio

				$newWidth  = round($w * ($height / $h));
				$newHeight = $height;
			}
			else
			{
				// Ignone aspect ratio
				
				$newWidth  = $width;
				$newHeight = $height;
			}					
		}
		
		$this->image->scaleImage($newWidth, $newHeight);
	}

	/**
	 * Crops the image.
	 *
	 * @access  public
	 * @param   int  $width   Width of the crop
	 * @param   int  $height  Height of the crop
	 * @param   int  $x       The X coordinate of the cropped region's top left corner
	 * @param   int  $y       The Y coordinate of the cropped region's top left corner
	 */

	public function crop($width, $height, $x, $y)
	{			
		$this->image->cropImage($width, $height, $x, $y);
	}

	/**
	 * Flips the image.
	 *
	 * @access  public  
	 * @param   int     $direction  (optional) Direction to flip the image
	 */

	public function flip($direction = Image::FLIP_HORIZONTAL)
	{
		if($direction ===  Image::FLIP_VERTICAL)
		{
			// Flips the image in the vertical direction

			$this->image->flipImage();
		}
		else
		{
			// Flips the image in the horizontal direction

			$this->image->flopImage();
		}
	}

	/**
	 * Adds a watermark to the image.
	 *
	 * @access  public
	 * @param   string  $file      Path to the image file
	 * @param   int     $position  (optional) Position of the watermark
	 * @param   int     $opacity   (optional) Opacity of the watermark in percent
	 */
	
	public function watermark($file, $position = Image::WATERMARK_TOP_LEFT, $opacity = 100)
	{
		// Check if the image exists

		if(file_exists($file) === false)
		{
			throw new RuntimeException(vsprintf("%s(): The watermark image [ %s ] does not exist.", [__METHOD__, $file]));
		}
		
		$watermark = new Imagick($file);
		
		$watermarkW = $watermark->getImageWidth();
		$watermarkH = $watermark->getImageHeight();
		
		// Make sure that opacity is between 0 and 100
		
		$opacity = max(min((int) $opacity, 100), 0);
		
		if($opacity < 100)
		{				
			$watermark->evaluateImage(Imagick::EVALUATE_MULTIPLY, ($opacity / 100), Imagick::CHANNEL_ALPHA);
		}
		
		// Position the watermark.
		
		switch($position)
		{
			case Image::WATERMARK_TOP_RIGHT:
				$x = $this->image->getImageWidth() - $watermarkW;
				$y = 0;
				break;
			case Image::WATERMARK_BOTTOM_LEFT:
				$x = 0;
				$y = $this->image->getImageHeight() - $watermarkH;
				break;
			case Image::WATERMARK_BOTTOM_RIGHT:
				$x = $this->image->getImageWidth() - $watermarkW;
				$y = $this->image->getImageHeight() - $watermarkH;
				break;
			case Image::WATERMARK_CENTER:
				$x = ($this->image->getImageWidth() / 2) - ($watermarkW / 2);
				$y = ($this->image->getImageHeight() / 2) - ($watermarkH / 2);
				break;
			default:
				$x = 0;
				$y = 0;
		}
		
		$this->image->compositeImage($watermark, PHP_Imagick::COMPOSITE_OVER, $x, $y);
		
		$watermark->destroy();
	}

	/**
	 * Converts image to greyscale.
	 *
	 * @access  public
	 */
	
	public function greyscale()
	{
		$this->image->setImageType(Imagick::IMGTYPE_GRAYSCALE);
	}

	/**
	 * Colorize an image.
	 *
	 * @access  public
	 * @param   string  $color  Hex value
	 */

	public function colorize($color)
	{
		$this->checkHEX($color);
		
		$this->image->colorizeImage($this->normalizeHEX($color), 1.0);
	}

	/**
	 * Adds a border to the image.
	 *
	 * @access  public
	 * @param   string  $color      Hex code for the color
	 * @param   int     $thickness  Thickness of the frame in pixels
	 */
	
	public function border($color = '#000', $thickness = 5)
	{
		$this->checkHEX($color);

		$this->image->shaveImage($thickness, $thickness);
		
		$this->image->borderImage($this->normalizeHEX($color), $thickness, $thickness);
	}

	/**
	 * Returns a string containing the image.
	 * 
	 * @access  public
	 * @param   string  $type     (optional) Image type
	 * @param   int     $quality  (optional) Image quality 1-100
	 * @return  string
	 */

	public function getImageBlob($type = null, $quality = 95)
	{
		if($type !== null)
		{
			if(!$this->image->setImageFormat($type))
			{
				throw new RuntimeException(vsprintf("%s(): Unsupported image type [ %s ].", [__METHOD__, $type]));
			}
		}

		// Set image quality

		$this->image->setImageCompressionQuality($quality);

		// Return image blob

		return $this->image->getImageBlob();
	}

	/**
	 * Saves image to file.
	 *
	 * @access  public
	 * @param   string  $file     Path to the image file
	 * @param   int     $quality  (optional) Image quality 1-100
	 */

	public function save($file, $quality = 95)
	{	
		// Set image quality
		
		$this->image->setImageCompressionQuality($quality);

		// Save image
		
		$this->image->writeImage($file);
	}
}

/** -------------------- End of file -------------------- **/