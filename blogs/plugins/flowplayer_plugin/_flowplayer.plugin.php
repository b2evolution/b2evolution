<?php
/**
 * This file implements the Flow Player plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 *
 * @package plugins
 * @version $Id: _flowplayer.plugin.php 198 2011-11-05 21:34:08Z fplanque $
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
		add_css_headline( '.flowplayer_block {
	margin: 0 auto 1em;
}
.flowplayer_block .flowplayer {
	display: block;
	margin: auto;
}
.flowplayer_block .flowplayer_text {
	font-size: 84%;
	text-align: center;
	margin: 4px 0;
}' );
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

		$width = intval( $this->get_coll_setting( 'width', $item_Blog ) );
		if( empty( $width ) )
		{ // Set default width
			$width = 'width:100%;';
		}
		else
		{ // Set width from blog plugin setting
			$width = 'width:'.$width.'px;';
		}

		// Set height from blog plugin setting
		$height = intval( $this->get_coll_setting( 'height', $item_Blog ) );
		$height = 'height:'.$height.'px;';

		if( $File->exists() )
		{
			if( $in_comments )
			{
				$params['data'] .= '<div style="clear: both; height: 0px; font-size: 0px"></div>';
			}
			$params['data'] .= '<div class="flowplayer_block">';

			$params['data'] .= '<a class="flowplayer" style="'.$width.$height.'" href="'.$File->get_url().'"></a>';

			if( $File->get( 'desc' ) != '' && $this->get_coll_setting( 'disp_caption', $item_Blog ) )
			{ // Display caption
				$params['data'] .= '<div class="flowplayer_text">'.$File->get( 'desc' ).'</div>';
			}

			if( $this->get_coll_setting( 'allow_download', $item_Blog ) )
			{ // Allow to download the video files
				$params['data'] .= '<div class="flowplayer_text"><a href="'.$File->get_url().'">'.T_('Download this video').'</a></div>';
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

}
?>