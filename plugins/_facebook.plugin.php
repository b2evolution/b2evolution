<?php
/**
 * This file implements the facebook like plugin
 * 
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * 
 * @package plugins
 * 
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory - Attila Simo
 * 
 * @version $Id: _facebook.plugin.php 8856 2015-05-02 01:25:05Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Facebook Plugin
 *
 * This plugin displays
 */
class facebook_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name;
	var $code = 'evo_facebook';
	var $priority = 20;
	var $version = '1.0';
	var $author = 'The b2evo Group';
	var $group = 'widget';
	var $subgroup = 'other';

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_( 'Facebook Like Widget' );
		$this->short_desc = T_('This skin tag displays a Facebook Like button.');
		$this->long_desc = T_('Shows how many users like the current page.');
	}

	/**
	 * Event handler: SkinTag (widget)
	 *
	 * @param array Associative array of parameters.
	 * @return boolean did we display?
	 */
	function SkinTag( & $params )
	{
		/**
		 * Default params:
		 */
		$params = array_merge( array(
				// This is what will enclose the block in the skin:
				'block_start'       => '<div class="bSideItem">',
				'block_end'         => "</div>\n",
				// This is what will enclose the body:
				'block_body_start'  => '',
				'block_body_end'    => '',
			), $params );

		global $baseurlroot;
		//$test_url = url_absolute( regenerate_url( '', '', '', '&' ), 'http://127.0.0.1' );
		$current_url = url_absolute( regenerate_url( '', '', '', '&' ), $baseurlroot );

		echo $params['block_start'];
		echo $params['block_body_start'];
		echo '<iframe src="http://www.facebook.com/plugins/like.php?href='.urlencode($current_url)
					.'&amp;layout=standard&amp;show_faces=true&amp;width=190&amp;action=like&amp;font=arial&amp;colorscheme=light&amp;height=66" 
					scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:190px; height:66px;" 
					allowTransparency="true"></iframe>';
		echo $params['block_body_end'];
		echo $params['block_end'];

		return true;
	}
}

?>