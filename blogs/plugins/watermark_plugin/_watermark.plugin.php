<?php
/**
 * This file implements the Watermark plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 *
 * @author sam2kb: Alex - {@link http://ru.b2evo.net/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Adds text watermark to generated thumbnails
 *
 * @package plugins
 */
class watermark_plugin extends Plugin
{
	var $code = 'evo_watermark';
	var $name = 'Watermark';
	var $priority = 10;
	var $apply_rendering = 'never';
	var $short_desc;
	var $long_desc;
	var $version = '1.0.0';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Adds text watermark to generated thumbnails');
		$this->long_desc = T_('Adds text watermark to generated thumbnails');

		$this->fonts_dir = dirname(__FILE__).'/fonts';
	}


	/**
	 * Get the settings that the plugin can use.
	 *
	 * @return array
	 */
	function GetDefaultSettings( & $params )
	{
		global $ReqHost;
		
		return array(
			'text' => array(
					'label' => 'Image text',
					'size' => 70,
					'defaultvalue' => '&copy; '.$ReqHost,
					'note' => T_('The text to write on the image.'),
				),
			'font' => array(
					'label' => 'Font file name',
					'size' => 30,
					'defaultvalue' => '',
					'note' => sprintf(T_('You can upload your own fonts to the font directory (%s).'), rel_path_to_base($this->fonts_dir)),
				),
			'font_size' => array(
					'label' => 'Font size',
					'type' => 'select',
					'options' => array_combine( range(6,68,2), range(6,68,2) ),
					'defaultvalue' => 12,
					'note' => '',
				),
			);
	}
	
	
	function PluginSettingsUpdateAction()
	{
		global $media_path, $Settings;
		
		$font = $this->Settings->get('font');
		
		if( !empty($font) && !is_readable($this->fonts_dir.'/'.$font) )
		{
			$this->msg( sprintf( T_('Unable to load font file: %s'), $this->fonts_dir.'/'.$font ), 'error' );
			return false;
		}
		else
		{	// Delete file cache
			// TODO> clear cache only if settings are changed
			//       (could use PluginSettingsValidateSet for this)
			// TODO: dh> this should use a single function and the same should get used in the Tools menu action, too.
			$dirs = get_filenames( $media_path, false );
			foreach( $dirs as $dir )
			{
				if( basename($dir) == $Settings->get( 'evocache_foldername' ) )
				{	// Delete .evocache directories recursively
					rmdir_r( $dir );
				}
			}
		}
	}
	
	
	function BeforeEnable()
	{
		if( !function_exists('imagettftext') )
		{	// The function imagettftext() is not available.
			return T_('The function imagettftext() is not available.');
		}

		return true;
	}
	
	
	/**
	 * This gets called before an image thumbnail gets created.
	 *
	 * This is useful to post-process the thumbnail image (add a watermark or change colors).
	 *
	 * @param array Associative array of parameters
	 *   - 'File': the related File (by reference)
	 *   - 'imh': image resource (by reference)
	 *   - 'size': size name (by reference)
	 *   - 'mimetype': mimetype of thumbnail (by reference)
	 *   - 'quality': JPEG image quality [0-100] (by reference)
	 */
	function BeforeThumbCreate( & $params )
	{
		global $rsc_path;
		
		if( !function_exists('imagettftext') )
		{	// The function imagettftext() is not available.
			return;
		}
		
		// Canvas width & height
		$width = imagesx( $params['imh'] ) ;
		$height = imagesy( $params['imh'] );
		
		if( $width < 161 || $height < 161 )
		{	// Skip small thumbnails
			return;
		}
		
		if( $font = $this->Settings->get('font') )
		{	// Custom font
			$font = $this->fonts_dir.'/'.$font;
		}
		else
		{	// Default font
			$font = $rsc_path.'fonts/LiberationSans-Regular.ttf';
		}
		
		if( !is_readable($font) )
		{	// Font file not found
			// TODO: debuglog
			return;
		}
		
		$text = html_entity_decode($this->Settings->get('text'));
		$font_size = $this->Settings->get('font_size');
		
		// Text margins
		$margin_left = 10;
		$margin_bottom = 10;
		
		// Create a transparent image
		$im = imagecreatetruecolor($width, $height);
		imagealphablending($im, true);
		imagefill($im, 0, 0, imagecolortransparent($im, imagecolorallocatealpha($im, 0, 0, 0, 127)));
		imagesavealpha($im, true);
		
		// Create some colors
		$white = imagecolorallocate($im, 255, 255, 255);
		$light_grey = imagecolorallocate($im, 230, 230, 230);
		$grey = imagecolorallocate($im, 60, 60, 60);
		$black = imagecolorallocate($im, 0, 0, 0);
		
		// Add text shadow
		imagettftext($im, $font_size, 0, $margin_left + 2, $height - $margin_bottom, $grey, $font, $text);

		// Add text
		imagettftext($im, $font_size, 0, $margin_left, $height - $margin_bottom, $light_grey, $font, $text);
		
		// Merge images
		imagecopyresampled($params['imh'], $im, 0, 0, 0, 0, $width, $height, $width, $height);
		
		// Low quality :(
		//imagecopymerge($params['imh'], $im, 0, 0, 0, 0, $width, $height, 90);
		
		return true;
	}
	
	
	/**
	 * Event handler: Called before an uploaded file gets saved on server.
	 *
	 * @param array Associative array of parameters
	 *   - 'File': The "File" object (by reference).
	 *   - 'name': file name (by reference).
	 *   - 'type': file mimetype (by reference).
	 *   - 'tmp_name': file location (by reference).
	 *   - 'size': file size in bytes  (by reference).
	 *
	 * @return boolean 'false' to abort file upload, otherwise return 'true'
	 */
	function AfterFileUpload( & $params )
	{
		return true;
	}

}

?>