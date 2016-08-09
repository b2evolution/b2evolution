<?php
/**
 * This file implements the Watermark plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 *
 * @author sam2kb: Alex - {@link http://b2evo.sonorth.com/}
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
	var $group = 'files';
	var $priority = 10;
	var $short_desc;
	var $long_desc;
	var $version = '6.7.5';
	var $number_of_installs = 1;

	var $fonts_dir = '';

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Adds text watermark to generated thumbnails');
		$this->long_desc = T_('Adds text watermark to generated thumbnails greater than 160x160 pixels');

		$this->fonts_dir = dirname(__FILE__).'/fonts';
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		global $ReqHost;

		return array(
			'text' => array(
					'label' => T_('Image text'),
					'size' => 70,
					'defaultvalue' => '&copy; '.$ReqHost,
					'note' => T_('The text to write on the image.'),
				),
			'font' => array(
					'label' => T_('Font file name'),
					'type' => 'select',
					'defaultvalue' => '',
					'options' => $this->get_font_files(),
					'note' => sprintf( T_('You can upload your own fonts to the plugin\'s font directory (%s), then select the filename here. By default "%s" is used.'),
						rel_path_to_base( $this->fonts_dir ), rel_path_to_base( $this->get_default_font() ) ),
				),
			'font_size' => array(
					'label' => T_('Font size'),
					'type' => 'select',
					'options' => array(6=>6,8=>8,10=>10,12=>12,14=>14,16=>16,18=>18,20=>20,22=>22,24=>24,
							26=>26,28=>28,30=>30,32=>32,34=>34,36=>36,38=>38,40=>40,42=>42,44=>44,46=>46,
							48=>48,50=>50,52=>52,54=>54,56=>56,58=>58,60=>60,62=>62,64=>64,66=>66,68=>68),
					'defaultvalue' => 12,
					'note' => '',
				),
			'min_dimension' => array(
					'label' => T_('Min dimension'),
					'type' => 'integer',
					'size' => 4,
					'defaultvalue' => 400,
					'note' => T_('Enter the minimum pixel dimension an image must have to get a watermark.')
				),
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$r = array(
			'coll_text' => array(
					'label' => T_('Image text'),
					'size' => 70,
					'defaultvalue' => $this->Settings->get( 'text' ),
					'note' => T_('The text to write on the image.'),
				),
			'coll_font' => array(
					'label' => T_('Font file name'),
					'type' => 'select',
					'defaultvalue' => $this->Settings->get( 'font' ),
					'options' => $this->get_font_files(),
					'note' => sprintf( T_('You can upload your own fonts to the plugin\'s font directory (%s), then select the filename here. By default "%s" is used.'),
						rel_path_to_base( $this->fonts_dir ), rel_path_to_base( $this->get_default_font() ) ),
				),
			'coll_font_size' => array(
					'label' => T_('Font size'),
					'type' => 'select',
					'options' => array(6=>6,8=>8,10=>10,12=>12,14=>14,16=>16,18=>18,20=>20,22=>22,24=>24,
							26=>26,28=>28,30=>30,32=>32,34=>34,36=>36,38=>38,40=>40,42=>42,44=>44,46=>46,
							48=>48,50=>50,52=>52,54=>54,56=>56,58=>58,60=>60,62=>62,64=>64,66=>66,68=>68),
					'defaultvalue' => $this->Settings->get( 'font_size' ),
					'note' => '',
				),
			'coll_min_dimension' => array(
					'label' => T_('Min dimension'),
					'type' => 'integer',
					'size' => 4,
					'defaultvalue' => 400,
					'note' => T_('Enter the minimum pixel dimension an image must have to get a watermark.')
				),
			);

		return $r;
	}


	function PluginSettingsUpdateAction()
	{
		$font = $this->Settings->get( 'font' );

		return $this->settings_update_action( $font );
	}


	function PluginCollSettingsUpdateAction()
	{
		global $Blog;

		$font = $this->get_coll_setting( 'coll_font', $Blog );

		return $this->settings_update_action( $font );
	}


	function BeforeEnable()
	{
		if( !function_exists('imagettftext') )
		{	// The function imagettftext() is not available.
			return 'The function imagettftext() is not available.'; // Conf error, no translation
		}

		delete_cachefolders();

		return true;
	}


	function BeforeDisable()
	{
		delete_cachefolders();
	}


	/**
	 * Get hardcoded default font.
	 * @return string
	 */
	function get_default_font()
	{
		global $rsc_path;
		return $rsc_path.'fonts/LiberationSans-Regular.ttf';
	}


	function settings_update_action( $font = '' )
	{
		if( ! empty( $font ) && ! is_readable( $this->fonts_dir.'/'.$font ) )
		{
			$this->msg( sprintf( T_('Unable to load font file: %s'), $this->fonts_dir.'/'.$font ), 'error' );
			return false;
		}

		// Delete file cache
		// TODO> clear cache only if settings have changed
		//       (could use PluginSettingsValidateSet for this)
		delete_cachefolders();
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
	 *   - 'root_type': file root type 'user', 'group', 'collection' etc. (by reference)
	 *   - 'root_type_ID': ID of user, group or collection (by reference)
	 */
	function BeforeThumbCreate( & $params )
	{
		if( $params['root_type'] == 'collection' )
		{
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $params['root_type_ID'], false, false );
		}

		if( ! empty( $Blog ) )
		{ // Use blog settings when possible
			$font = $this->get_coll_setting( 'coll_font', $Blog );
			$text = $this->get_coll_setting( 'coll_text', $Blog );
			$font_size = $this->get_coll_setting( 'coll_font_size', $Blog );
			$min_dimension = $this->get_coll_setting( 'coll_min_dimension', $Blog );
		}
		else
		{ // Use global settings
			$font = $this->Settings->get( 'font' );
			$text = $this->Settings->get( 'text' );
			$font_size = $this->Settings->get( 'font_size' );
			$min_dimension = $this->Settings->get( 'min_dimension' );
		}

		if( ! function_exists( 'imagettftext' ) )
		{ // The function imagettftext() is not available.
			return;
		}

		// Canvas width & height
		$width = imagesx( $params['imh'] ) ;
		$height = imagesy( $params['imh'] );

		if( $width < $min_dimension || $height < $min_dimension )
		{ // Skip small thumbnails
			return;
		}

		if( $font )
		{	// Custom font
			$font = $this->fonts_dir.'/'.$font;
		}
		else
		{
			$font = $this->get_default_font();
		}

		if( !is_readable($font) )
		{	// Font file not found
			$this->debug_log( sprintf( 'Font file (%s) is not readable!', $font ) );
			return;
		}

		$text = html_entity_decode( $text );

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


	/**
	 * Get all font files from folder
	 *
	 * @return array Font files
	 */
	function get_font_files()
	{
		$fonts = array( '' => T_('Default') );

		// Scan fonts folder
		$files = scandir( $this->fonts_dir );

		if( $files !== false )
		{
			foreach( $files as $file )
			{
				if( preg_match( '/\.ttf$/i', $file ) )
				{ // Allow only ttf files
					$fonts[ $file ] = $file;
				}
			}
		}

		return $fonts;
	}
}

?>