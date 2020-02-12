<?php
/**
 * This file implements the Star renderer plugin for b2evolution
 *
 * Star formatting, like [stars:2.3/5]
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 *
 * @version $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class star_plugin extends Plugin
{
	var $code = 'b2evStar';
	var $name = 'Star renderer';
	var $priority = 55;
	var $version = '7.1.2';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'star-plugin';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Star formatting e-g [stars:2.3/5]');
		$this->long_desc = T_('This plugin allows to render star ratings inside blog posts and comments by using the syntax [stars:2.3/5] for example');
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// Set empty array to disable this plugin for Email Campaign:
		return array();
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
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function DisplayItemAsHtml( & $params )
	{
		$params['data'] = $this->render_stars( $params['data'] );

		return true;
	}


	/**
	 * Do the same as for HTML.
	 *
	 * @see RenderItemAsHtml()
	 */
	function DisplayItemAsXml( & $params )
	{
		return $this->DisplayItemAsHtml( $params );
	}


	/**
	 * Render stars template from [[stars:3/7]
	 *  to <span class="evo_stars_img" style="width:112px">
	 *       <i>*</i>
	 *       <i>*</i>
	 *       <i class="evo_stars_img_empty"><i style="width:50%">%</i></i>
	 *       <i class="evo_stars_img_empty">-</i>
	 *       <i class="evo_stars_img_empty">-</i>
	 *     </span>
	 *
	 * @param string Source content
	 * @return string Rendered content
	 */
	function render_stars( $content )
	{
		return replace_content_outcode( '#\[stars:([\d\.]+)(/\d+)?\]#', array( $this, 'get_stars_template' ), $content, 'replace_content_callback' );
	}


	/**
	 * Get HTML template for stars
	 *
	 * @param array Matches
	 * @return string HTML stars
	 */
	function get_stars_template( $matches )
	{
		global $b2evo_icons_type;

		if( empty( $matches ) )
		{ // No stars found
			return;
		}

		$active_stars = $matches[1];

		if( ! empty( $matches[2] ) )
		{ // Get a number of stars from content
			$number_stars = intval( substr( $matches[2], 1 ) );
		}
		if( empty( $number_stars ) )
		{ // Use 5 stars by default
			$number_stars = 5;
		}

		return get_star_rating( $active_stars, $number_stars );
	}
}

?>