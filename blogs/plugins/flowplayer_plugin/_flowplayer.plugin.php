<?php
/**
 * This file implements the Flow Player plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 *
 * @package plugins
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class flowplayer_plugin extends Plugin
{
	var $code = 'b2evFlp';
	var $name = 'Flowplayer';
	var $priority = 80;
	var $version = '5.0.0';
	var $group = 'files';
	var $number_of_installs = 1;
	var $allow_ext = array( 'flv', 'swf', 'm4v', 'f4v', 'mov', 'mp4' );


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
		require_js( $this->get_plugin_url( true ).'flowplayer.min.js', 'relative' );
		require_js( $this->get_plugin_url( true ).'flowplayer_init.js', 'relative' );
		add_js_headline( 'flowplayer_url = "'.$this->get_plugin_url( true ).'";' );
	}


	/**
	 * @see Plugin::AdminEndHtmlHead()
	 */
	function AdminEndHtmlHead( & $params )
	{
		$this->SkinBeginHtmlHead( $params );
	}


	/**
	 * Get the settings that the plugin can use.
	 *
	 * Those settings are transfered into a Settings member object of the plugin
	 * and can be edited in the backoffice (Settings / Plugins).
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @see PluginSettings
	 * @see Plugin::PluginSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultSettings( & $params )
	{
		return array(
			'width' => array(
				'label' => T_('Video width (px)'),
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

		$width = (int) $this->Settings->get( 'width' );
		if( empty( $width ) )
		{	// Set default width
			$width = 'width:100%';
		}
		else
		{
			$width = 'width:'.$width.'px';
		}

		$height = (int) $this->Settings->get( 'height' );
		$height = 'height:'.$height.'px';

		if( $File->exists() )
		{
			if( $in_comments )
			{
				$params['data'] .= '<div style="clear: both; height: 0px; font-size: 0px"></div>';
			}
			$params['data'] .= '<br /><a class="flowplayer" style="display: block; '.$width.';'.$height.';" href="'.$File->get_url().'"></a>';

			if( $this->Settings->get( 'allow_download' ) )
			{	// Allow to download the video files
				$params['data'] .= '<div class="small center"><a href="'.$File->get_url().'">'.T_('Download this video').'</a></div>';
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
		return $this->RenderItemAttachment( $params, true );
	}

}
?>