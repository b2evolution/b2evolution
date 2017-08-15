<?php
/**
 * This file implements the Flow Player plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 *
 * @package plugins
 * @version $Id: _flowplayer.plugin.php 8373 2015-02-28 21:44:37Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class flowplayer_plugin extends Plugin
{
	var $code = 'b2evFlwP';
	var $name = 'Flowplayer';
	var $priority = 80;
	var $version = '6.9.3';
	var $group = 'files';
	var $number_of_installs = 1;
	var $allow_ext = array( 'flv', 'swf', 'mp4', 'ogv', 'webm', 'm3u8' );


	function PluginInit( & $params )
	{
		$this->short_desc = sprintf( T_('Media player for the these file formats: %s. Note: iOS supports only: %s; Android supports only: %s.'),
			implode( ', ', $this->allow_ext ), 'mp4', 'mp4, webm' );

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
		require_js( '#flowplayer#', 'blog' );
		add_js_headline( 'flowplayer.conf = { flashfit: true, embed: false }' );
		$this->require_skin();
		add_css_headline( '.flowplayer_block {
	margin: 1em auto 0;
	background: #000;
}
.flowplayer_block .flowplayer {
	display: block;
	margin: auto;
}
.flowplayer_text {
	font-size: 84%;
	text-align: center;
	margin: 4px 0;
}' );
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
					'defaultvalue' => 'minimalist',
					),
				'width' => array(
					'label' => T_('Video width (px)'),
					'note' => T_('100% width if left empty or 0'),
					),
				'height' => array(
					'label' => T_('Video height (px)'),
					'type' => 'integer',
					'allow_empty' => true,
					'valid_range' => array( 'min' => 1 ),
					'note' => T_('auto height if left empty'),
					),
				'allow_download' => array(
					'label' => T_('Display Download Link'),
					'note' => T_('Check to display a "Download this video" link under the video.'),
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
		{ // This file cannot be played with this player
			return false;
		}

		$Item = & $params['Item'];
		$item_Blog = $Item->get_Blog();

		if( ( ! $in_comments && ! $this->get_coll_setting( 'use_for_posts', $item_Blog ) ) ||
		    ( $in_comments && ! $this->get_coll_setting( 'use_for_comments', $item_Blog ) ) )
		{ // Plugin is disabled for post/comment videos on this Blog
			return false;
		}

		$width = intval( $this->get_coll_setting( 'width', $item_Blog ) );
		if( empty( $width ) )
		{ // Set default width
			$width = 'max-width:100%;';
		}
		else
		{ // Set width from blog plugin setting
			$width = 'width:'.$width.'px;max-width:100%;';
		}

		// Set height from blog plugin setting
		$height = trim( $this->get_coll_setting( 'height', $item_Blog ) );
		$height = empty( $height ) ? '' : 'height:'.$height.'px';

		if( $File->exists() )
		{
			if( $in_comments )
			{
				$params['data'] .= '<div style="clear: both; height: 0px; font-size: 0px"></div>';
			}
			$params['data'] .= '<div class="flowplayer_block" style="'.$width.$height.'">';

			$source_files = array( $File );

			if( ! isset( $params['Comment'] ) )
			{ // Get the fallback files for Item's content
				$source_files = array_merge(
						$source_files,
						$Item->get_fallback_files( $File )
					);
			}

			$sources = array();
			foreach( $source_files as $f => $source_File )
			{
				$sources[ $f ] = array(
						'src' => $source_File->get_url(),
					);

				if( $Filetype = & $source_File->get_Filetype() && isset( $Filetype->mimetype ) )
				{ // Get mime type of the video file
					$sources[ $f ]['type'] = $Filetype->mimetype;
				}
			}

			if( $placeholder_File = & $Item->get_placeholder_File( $File ) )
			{ // Display placeholder/poster when image file is linked to the Item with same name as current video File
				$video_placeholder_attr = ' poster="'.$placeholder_File->get_url().'"';
			}
			else
			{ // No placeholder for current video File
				$video_placeholder_attr = '';
			}

			$params['data'] .= '<div class="flowplayer '.$this->get_skin( $item_Blog ).'" style="background-color:inherit;'.$width.$height.'"><video'.$video_placeholder_attr.'>';
			foreach( $sources as $source )
			{
				$params['data'] .= '<source'.get_field_attribs_as_string( $source, false ).' />'."\n";
			}
			$params['data'] .= '</video></div>'."\n";

			if( $File->get( 'desc' ) != '' && $this->get_coll_setting( 'disp_caption', $item_Blog ) )
			{ // Display caption
				$params['data'] .= '<div class="flowplayer_text">'.$File->get( 'desc' ).'</div>'."\n";
			}

			$params['data'] .= '</div>'."\n";

			if( $this->get_coll_setting( 'allow_download', $item_Blog ) )
			{ // Allow to download the video files
				$params['data'] .= '<div class="flowplayer_text"><a href="'.$File->get_url().'">'.T_('Download this video').'</a></div>';
			}

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
		return array(
				'minimalist' => 'minimalist',
				'functional' => 'functional',
				'playful'    => 'playful',
			);
	}


	/**
	 * Get current skin
	 *
	 * @param object Blog
	 * @return string Skin name
	 */
	function get_skin( $Blog = NULL )
	{
		if( empty( $Blog ) )
		{ // Get current Blog if it is not defined
			global $Collection, $Blog;
		}

		// Get a skin name from blog plugin setting
		return empty( $Blog ) ? '' : $skin = $this->get_coll_setting( 'skin', $Blog );
	}

	/**
	 * Require css file of current skin
	 */
	function require_skin()
	{
		$skin = $this->get_skin();
		if( empty( $skin ) )
		{ // Include all skins
			$skin = 'all-skins';
		}

		$skins_path = dirname( $this->classfile_path ).'/skin';
		if( file_exists( $skins_path.'/'.$skin.'.css' ) )
		{ // Require css file only if it exists
			$this->require_css( 'skin/'.$skin.'.css' );
		}
	}

}
?>