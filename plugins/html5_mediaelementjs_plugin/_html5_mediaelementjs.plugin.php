<?php
/**
 * This file implements the HTML 5 MediaElement.js Video Player plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author fplanque: Francois PLANQUE.
 *
 * @package plugins
 * @version $Id: _html5_mediaelementjs.plugin.php 8373 2015-02-28 21:44:37Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class html5_mediaelementjs_plugin extends Plugin
{
	var $code = 'b2evH5MP';
	var $name = 'HTML 5 MediaElement.js Video and Audio Player';
	var $priority = 80;
	var $version = '6.7.9';
	var $group = 'files';
	var $number_of_installs = 1;
	var $allow_ext = array( 'flv', 'm4v', 'f4v', 'mp4', 'ogv', 'webm', 'mp3', 'm4a' );


	function PluginInit( & $params )
	{
		$this->short_desc = sprintf( T_('Media player for the these file formats: %s. Note: iOS supports only: %s; Android supports only: %s.'),
			implode( ', ', $this->allow_ext ), 'mp4, mp3, m4a', 'mp4, webm, mp3, m4a' );

		$this->long_desc = $this->short_desc.' '
			.sprintf( T_('This player can display a placeholder image of the same name as the video file with the following extensions: %s.'),
			'jpg, jpeg, png, gif' );
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlHead( & $params )
	{
		global $Collection, $Blog;

		require_css( '#mediaelement_css#', 'blog' );
		require_js( '#jquery#', 'blog' );
		require_js( '#mediaelement#', 'blog' );
		$this->require_skin();

		// Set a video/audio size in css style, because option setting cannot sets correct size
		$width = intval( $this->get_coll_setting( 'width', $Blog ) );
		$width = empty( $width ) ? '100%' : $width.'px';
		$height = intval( $this->get_coll_setting( 'height', $Blog ) );
		add_css_headline( 'video.html5_mediaelementjs_player{ width: '.$width.' !important; height: '.$height.'px !important; display: block; margin: auto; }
audio.html5_mediaelementjs_player{ width: '.$width.' !important; display: block; margin: auto; }
.mediajs_block {
	width: '.$width.' !important;
	margin: 0 auto 1em;
	text-align: center;
}
.mediajs_block .mediajs_text {
	font-size: 84%;
	text-align: center;
	margin: 4px 0;
}' );

		// Initialize a player
		add_js_headline(
			( $width == "100%" ?
			// Use 100% width
			'' :
			// Check to make player width <= window width
			'var html5_mediaelementjs_player_width = parseInt( "'.$width.'" );
			if( jQuery( window ).width() < html5_mediaelementjs_player_width )
			{
				html5_mediaelementjs_player_width = jQuery( window ).width();
			}'
			).'
			jQuery( document ).ready( function() {
				jQuery( ".html5_mediaelementjs_player" ).mediaelementplayer( {
					defaultVideoWidth: '.( $width == "100%" ? '"100%"' : 'html5_mediaelementjs_player_width' ).',
					defaultVideoHeight: "'.$height.'",
					videoWidth: '.( $width == "100%" ? '"100%"' : 'html5_mediaelementjs_player_width' ).',
					videoHeight: "'.$height.'",
					audioWidth: '.( $width == "100%" ? '"100%"' : 'html5_mediaelementjs_player_width' ).',
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
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
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
				'use_for_posts' => array(
					'label' => T_('Use for'),
					'note' => T_('videos attached to posts'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
					),
				'use_for_comments' => array(
					'label' => '',
					'note' => T_('videos attached to comments'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
					),
				'skin' => array(
					'label' => T_('Skin'),
					'type' => 'select',
					'options' => $this->get_skins_list(),
					'defaultvalue' => 'default',
					),
				'width' => array(
					'label' => T_('Video/Audio width (px)'),
					'defaultvalue' => 460,
					'note' => T_('100% width if left empty or 0'),
					),
				'height' => array(
					'label' => T_('Video height (px)'),
					'type' => 'integer',
					'defaultvalue' => 320,
					'note' => '',
					'valid_range' => array( 'min' => 1 ),
					),
				'allow_download' => array(
					'label' => T_('Display Download Link'),
					'note' => T_('Check to display a "Download this video" link under the video.'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
					),
				'disp_caption' => array(
					'label' => T_('Display caption'),
					'note' => T_('Check to display the file caption under the player.'),
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
	function is_file_supported( $File )
	{
		return in_array( strtolower( $File->get_ext() ), $this->allow_ext );
	}


	/**
	 * Check a file for correct extension
	 *
	 * @param File
	 * @return boolean true if extension of file supported by plugin
	 */
	function is_url_supported( $url )
	{
		if( preg_match( '#\.([a-z0-9]+)(\?.+)?$#', $url, $match ) )
		{
			$url_extenssion = strtolower( $match[1] );

			return in_array( $url_extenssion, $this->allow_ext );
		}

		return false;
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

		if( ! $this->is_file_supported( $File ) )
		{	// This file is not supported by plugin, Exit here:
			return false;
		}

		$Item = & $params['Item'];
		$item_Blog = $Item->get_Blog();

		if( ( ! $in_comments && ! $this->get_coll_setting( 'use_for_posts', $item_Blog ) ) ||
		    ( $in_comments && ! $this->get_coll_setting( 'use_for_comments', $item_Blog ) ) )
		{ // Plugin is disabled for post/comment videos on this Blog
			return false;
		}

		if( $File->exists() )
		{
			if( ! $File->is_audio() && $placeholder_File = & $Item->get_placeholder_File( $File ) )
			{	// Get placeholder/poster when image file is linked to the Item with same name as current video File:
				$video_poster_url = $placeholder_File->get_url();
			}
			else
			{	// No poster file
				$video_poster_url = '';
			}

			// Get video/audio player:
			$params['data'] .= $this->get_player( array(
					'before'           => $in_comments ? '<div style="clear: both; height: 0px; font-size: 0px"></div>' : '',
					'Blog'             => $item_Blog,
					'file_type'        => $File->is_audio() ? 'audio' : 'video',
					'file_url'         => $File->get_url(),
					'file_caption'     => ( $File->get( 'desc' ) != '' && $this->get_coll_setting( 'disp_caption', $item_Blog ) ) ? $File->get( 'desc' ) : '',
					'video_poster_url' => $video_poster_url,
				) );

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
	 * Event handler: Called when displaying item attachment.
	 *
	 * @param array Associative array of parameters. $params['File'] - attachment, $params['data'] - output
	 * @param boolean TRUE - when render in comments
	 * @return boolean true if plugin rendered this attachment
	 */
	function RenderURL( & $params )
	{
		global $Collection, $Blog;

		if( empty( $params['url'] ) || ! $this->is_url_supported( $params['url'] ) )
		{	// This file is not supported by plugin, Exit here:
			return false;
		}

		$player_Blog = NULL;
		if( ! empty( $params['Item'] ) )
		{	// Get collection of the Item;
			$player_Blog = & $params['Item']->get_Blog();
		}
		else
		{	// Use current collection:
			global $Collection, $Blog;
			$player_Blog = $Blog;
		}

		// Get video/audio player:
		$params['data'] .= $this->get_player( array(
				'file_url'  => $params['url'],
				'file_type' => 'audio',
				'Blog'      => $player_Blog,
			) );

		return true;
	}


	/**
	 * Get video/audio player
	 *
	 * @param array Params
	 * @return string HTML text of player
	 */
	function get_player( $params = array() )
	{
		$params = array_merge( array(
				'before'           => '',
				'after'            => '',
				'before_player'    => '<div class="mediajs_block">',
				'after_player'     => '</div>',
				'Blog'             => NULL,
				'file_type'        => 'video', // 'audio' | 'video'
				'file_url'         => '',
				'file_caption'     => '',
				'video_poster_url' => '',
			), $params );

		/**
		 * @var integer A number to assign each video/ausio player new id attribute
		 */
		global $html5_mediaelementjs_number;
		$html5_mediaelementjs_number++;

		$r = $params['before'];

		$r .= $params['before_player'];

		if( $params['file_type'] == 'audio' )
		{	// Audio file:
			$r .= '<audio class="html5_mediaelementjs_player '.$this->get_skin_class().'" id="html5_mediaelementjs_'.$html5_mediaelementjs_number.'">'.
				'<source src="'.$params['file_url'].'" type="'.$this->get_file_mimetype( $params['file_url'] ).'" align="center" />'.
			'</audio>';
		}
		else
		{	// Video file:

			// Initialize placeholder/poster attribute:
			$video_placeholder_attr = empty( $params['video_poster_url'] ) ? '' : ' poster="'.$params['video_poster_url'].'"';

			$r .= '<video class="html5_mediaelementjs_player '.$this->get_skin_class().'" id="html5_mediaelementjs_'.$html5_mediaelementjs_number.'"'.$video_placeholder_attr.'>'.
				'<source src="'.$params['file_url'].'" type="'.$this->get_file_mimetype( $params['file_url'] ).'" align="center" />'.
			'</video>';
		}

		if( ! empty( $params['file_caption'] ) )
		{	// Display caption:
			$r .= '<div class="mediajs_text">'.$params['file_caption'].'</div>';
		}

		if( $params['Blog'] && $this->get_coll_setting( 'allow_download', $params['Blog'] ) )
		{	// Allow to download the files:
			$r .= '<div class="mediajs_text"><a href="'.$params['file_url'].'">'
					.( $params['file_type'] == 'audio' ? T_('Download this audio') : T_('Download this video') )
				.'</a></div>';
		}

		$r .= $params['after_player'];

		$r .= $params['after'];

		return $r;
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
		global $Collection, $Blog;

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
		global $Collection, $Blog;

		$skin = $this->get_coll_setting( 'skin', $Blog );
		if( !empty( $skin ) && $skin != 'default')
		{
			$skins_path = dirname( $this->classfile_path ).'/skins';
			if( file_exists( $skins_path.'/'.$skin.'/style.css' ) )
			{	// Require css file only if it exists
				$this->require_css( 'skins/'.$skin.'/style.css' );
			}
		}
	}

	/**
	 * Get audio/video mimetype
	 *
	 * @param object File
	 * @return string Mime-type
	 */
	function get_file_mimetype( $file_url )
	{
		if( preg_match( '#\.([a-z0-9]+)(\?.+)?$#', $file_url, $match ) )
		{	// Get file extenssion from url string:
			$file_extenssion = strtolower( $match[1] );
		}

		if( empty( $file_extenssion ) )
		{	// Use this mime-type by default on unknown extenssion:
			return 'video/mp4';
		}

		// Get mime-type from file type:
		$FiletypeCache = & get_FiletypeCache();
		if( $Filetype = & $FiletypeCache->get_by_extension( $file_extenssion, false ) )
		{
			return $Filetype->mimetype;
		}

		// Get mime-type by extenssion:
		switch( $file_extenssion )
		{
			case 'flv':
			case 'f4v':
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

			case 'mp4':
			default:
				$mimetype = 'video/mp4';
				break;
		}

		return $mimetype;
	}
}
?>