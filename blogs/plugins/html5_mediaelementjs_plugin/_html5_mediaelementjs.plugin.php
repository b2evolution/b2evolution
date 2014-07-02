<?php
/**
 * This file implements the HTML 5 MediaElement.js Video Player plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author fplanque: Francois PLANQUE.
 *
 * @package plugins
 * @version $Id: _html5_mediaelementjs.plugin.php 198 2011-11-05 21:34:08Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class html5_mediaelementjs_plugin extends Plugin
{
	var $code = 'b2evH5M';
	var $name = 'HTML 5 MediaElement.js Video Player';
	var $priority = 80;
	var $version = '5.0.0';
	var $group = 'files';
	var $number_of_installs = 1;
	var $allow_ext = array( 'flv', 'm4v', 'f4v', 'mp4', 'ogv', 'webm' );


	function PluginInit( & $params )
	{
		$this->short_desc = sprintf( T_('Media player for the these file formats: %s.'), implode( ', ', $this->allow_ext ) );
		$this->long_desc = $this->short_desc;
	}


	/**
	 * @see Plugin::SkinBeginHtmlHead()
	 */
	function SkinBeginHtmlHead( & $params )
	{
		global $Blog;

		$relative_to = ( is_admin_page() ? 'rsc_url' : 'blog' );

		require_css( '#mediaelement_css#', $relative_to );
		require_js( '#jquery#', 'blog' );
		require_js( '#mediaelement#', $relative_to );
		$this->require_skin();

		// Set a video size in css style, because option setting cannot sets correct size
		$width = intval( $this->get_coll_setting( 'width', $Blog ) );
		$width = empty( $width ) ? '100%' : $width.'px';
		$height = intval( $this->get_coll_setting( 'height', $Blog ) );
		add_css_headline( 'video.html5_mediaelementjs_video{ width: '.$width.' !important; height: '.$height.'px !important; display: block; margin: auto; }
.mediajs_block {
	margin: 0 auto 1em;
}
.mediajs_block .mediajs_text {
	font-size: 84%;
	text-align: center;
	margin: 4px 0;
}' );

		// Initialize a player
		add_js_headline( 'jQuery( document ).ready( function() {
				jQuery( "video.html5_mediaelementjs_video" ).mediaelementplayer( {
					defaultVideoWidth: "'.$width.'",
					defaultVideoHeight: "'.$height.'",
					videoWidth: "'.$width.'",
					videoHeight: "'.$height.'",
				} );
			} );' );
		/**
		 * Plugin options:

			// if the <video width> is not specified, this is the default
			defaultVideoWidth: 480,
			// if the <video height> is not specified, this is the default
			defaultVideoHeight: 270,
			// if set, overrides <video width>
			videoWidth: -1,
			// if set, overrides <video height>
			videoHeight: -1,
			// width of audio player
			audioWidth: 400,
			// height of audio player
			audioHeight: 30,
			// initial volume when the player starts
			startVolume: 0.8,
			// useful for <audio> player loops
			loop: false,
			// enables Flash and Silverlight to resize to content size
			enableAutosize: true,
			// the order of controls you want on the control bar (and other plugins below)
			features: ['playpause','progress','current','duration','tracks','volume','fullscreen'],
			// Hide controls when playing and mouse is not over the video
			alwaysShowControls: false,
			// force iPad's native controls
			iPadUseNativeControls: false,
			// force iPhone's native controls
			iPhoneUseNativeControls: false,
			// force Android's native controls
			AndroidUseNativeControls: false,
			// forces the hour marker (##:00:00)
			alwaysShowHours: false,
			// show framecount in timecode (##:00:00:00)
			showTimecodeFrameCount: false,
			// used when showTimecodeFrameCount is set to true
			framesPerSecond: 25,
			// turns keyboard support on and off for this instance
			enableKeyboard: true,
			// when this player starts, it will pause other players
			pauseOtherPlayers: true,
			// array of keyboard commands
			keyActions: []

		 */
	}


	/**
	 * @see Plugin::AdminEndHtmlHead()
	 */
	function AdminEndHtmlHead( & $params )
	{
		$this->SkinBeginHtmlHead( $params );
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		return array_merge( parent::get_coll_setting_definitions( $params ),
			array(
				'skin' => array(
					'label' => T_('Skin'),
					'type' => 'select',
					'options' => $this->get_skins_list(),
					'defaultvalue' => 'default',
					),
				'width' => array(
					'label' => T_('Video width (px)'),
					'defaultvalue' => 425,
					'note' => T_('100% width if left empty or 0'),
					),
				'height' => array(
					'label' => T_('Video height (px)'),
					'type' => 'integer',
					'defaultvalue' => 300,
					'note' => '',
					'valid_range' => array( 'min' => 1 ),
					),
				'allow_download' => array(
					'label' => T_('Allow downloading of the video file'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
					),
				'disp_caption' => array(
					'label' => T_('Display caption'),
					'note' => T_('Check to display the video file caption under the video player.'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
					),
			)
		);
	}


	/**
	 * Check a file for correct extension
	 *
	 * @param File
	 * @return boolean true if extension of file supported by plugin
	 */
	function is_flp_video( $File )
	{
		return in_array( strtolower( $File->get_ext() ), $this->allow_ext );
	}


	/**
	 * Event handler: Called when displaying item attachment.
	 *
	 * @param array Associative array of parameters. $params['File'] - attachment, $params['data'] - output
	 * @param boolean TRUE - when render in comments
	 * @return boolean true if plugin rendered this attachment
	 */
	function RenderItemAttachment( & $params, $in_comments = false )
	{
		$File = $params['File'];

		if( ! $this->is_flp_video( $File ) )
		{
			return false;
		}

		$Item = & $params['Item'];
		$item_Blog = $Item->get_Blog();

		if( $File->exists() )
		{
			/**
			 * @var integer A number to assign each video player new id attribute
			 */
			global $html5_mediaelementjs_number;
			$html5_mediaelementjs_number++;

			if( $in_comments )
			{
				$params['data'] .= '<div style="clear: both; height: 0px; font-size: 0px"></div>';
			}

			$params['data'] .= '<div class="mediajs_block">';

			$params['data'] .= '<video class="html5_mediaelementjs_video '.$this->get_skin_class().'" id="html5_mediaelementjs_'.$html5_mediaelementjs_number.'">'.
				'<source src="'.$File->get_url().'" type="'.$this->get_video_mimetype( $File ).'" align="center" />'.
			'</video>';

			if( $File->get( 'desc' ) != '' && $this->get_coll_setting( 'disp_caption', $item_Blog ) )
			{ // Display caption
				$params['data'] .= '<div class="mediajs_text">'.$File->get( 'desc' ).'</div>';
			}

			if( $this->get_coll_setting( 'allow_download', $item_Blog ) )
			{ // Allow to download the video files
				$params['data'] .= '<div class="mediajs_text"><a href="'.$File->get_url().'">'.T_('Download this video').'</a></div>';
			}

			$params['data'] .= '</div>';

			return true;
		}

		return false;
	}


	/**
	 * Event handler: Called when displaying comment attachment.
	 *
	 * @param array Associative array of parameters. $params['File'] - attachment, $params['data'] - output
	 * @return boolean true if plugin rendered this attachment
	 */
	function RenderCommentAttachment( & $params )
	{
		$Comment = & $params['Comment'];
		$params['Item'] = & $Comment->get_Item();

		return $this->RenderItemAttachment( $params, true );
	}

	/**
	 * Get a list of the skins
	 *
	 * @return array Skins
	 */
	function get_skins_list()
	{
		$skins_path = dirname( $this->classfile_path ).'/skins';

		$skins = array();
		// Set this skin permanently, because it is a default skin
		$skins['default'] = 'default';

		$files = scandir( $skins_path );
		foreach( $files as $file )
		{
			if( $file != '.' && $file != '..' && is_dir( $skins_path.'/'.$file ) )
			{	// Use folder name as skin name
				$skins[ $file ] = $file;
			}
		}

		return $skins;
	}

	/**
	 * Get skin class name
	 *
	 * @return string Skin class name
	 */
	function get_skin_class()
	{
		global $Blog;

		$skin = $this->get_coll_setting( 'skin', $Blog );

		if( ! empty( $skin ) && $skin != 'default')
		{
			return 'mejs-'.$skin;
		}

		return ''; // Default skin
	}

	/**
	 * Require css file of current skin
	 */
	function require_skin()
	{
		global $Blog;

		$skin = $this->get_coll_setting( 'skin', $Blog );
		if( !empty( $skin ) && $skin != 'default')
		{
			$skins_path = dirname( $this->classfile_path ).'/skins';
			if( file_exists( $skins_path.'/'.$skin.'/style.css' ) )
			{	// Require css file only if it exists
				require_css( $this->get_plugin_url().'skins/'.$skin.'/style.css', 'relative' );
			}
		}
	}

	/**
	 * Get video mimetype
	 *
	 * @param object File
	 * @return string Mimetype
	 */
	function get_video_mimetype( $File )
	{
		switch( $File->get_ext() )
		{
			case 'flv':
				$mimetype = 'video/flv';
				break;

			case 'm4v':
				$mimetype = 'video/m4v';
				break;

			case 'ogv':
				$mimetype = 'video/ogg';
				break;

			case 'webm':
				$mimetype = 'video/webm';
				break;

			case 'f4v':
			case 'mp4':
			default:
				$mimetype = 'video/mp4';
				break;
		}

		return $mimetype;
	}
}
?>