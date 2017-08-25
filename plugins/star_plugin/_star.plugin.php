<?php
/**
 * This file implements the Star renderer plugin for b2evolution
 *
 * Star formatting, like [stars:2.3/5]
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
	var $version = '6.9.3';
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
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlHead( & $params )
	{
		global $Collection, $Blog;

		if( ! isset( $Blog ) || (
		    $this->get_coll_setting( 'coll_apply_rendering', $Blog ) == 'never' &&
		    $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) == 'never' ) )
		{ // Don't load css/js files when plugin is not enabled
			return;
		}

		$this->require_css( 'star.css' );
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
	function RenderItemAsHtml( & $params )
	{
		$params['data'] = $this->render_stars( $params['data'] );

		return true;
	}


	/**
	 * Perform rendering of Message content
	 *
	 * NOTE: Use default coll settings of comments as messages settings
	 *
	 * @see Plugin::RenderMessageAsHtml()
	 */
	function RenderMessageAsHtml( & $params )
	{
		$params['data'] = $this->render_stars( $params['data'] );

		return true;
	}


	/**
	 * Do the same as for HTML.
	 *
	 * @see RenderItemAsHtml()
	 */
	function RenderItemAsXml( & $params )
	{
		$this->RenderItemAsHtml( $params );
	}


	/**
	 *
	 * Render comments if required
	 *
	 * @see Plugin::FilterCommentContent()
	 */
	function FilterCommentContent( & $params )
	{
		$Comment = & $params['Comment'];
		$comment_Item = & $Comment->get_Item();
		$item_Blog = & $comment_Item->get_Blog();
		if( in_array( $this->code, $Comment->get_renderers_validated() ) )
		{	// If apply_comment_rendering is set to render:
			$params['data'] = $this->render_stars( $params['data'] );
		}
	}


	/**
	 * Render stars template from [[stars:3/7]
	 *  to <span class="star_plugin" style="width:112px">
	 *       <span>*</span>
	 *       <span>*</span>
	 *       <span>*</span>
	 *       <span class="empty">-</span>
	 *       <span class="empty">-</span>
	 *       <span class="empty">-</span>
	 *       <span class="empty">-</span>
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

		$active_stars_max = floor( $active_stars );
		$percents = round( ( $active_stars - $active_stars_max ) * 100 );
		$template = '<span class="star_plugin"'.( $number_stars != 5 ? ' style="width:'.( $number_stars * 16 ).'px"' : '' ).'>';
		for( $s = 1; $s <= $number_stars; $s++ )
		{
			$attrs = '';
			if( $s > $active_stars_max )
			{ // Class for empty stars
				$attrs .= ' class="empty"';
			}
			$template .= '<span'.$attrs.'>';
			if( $s == $active_stars_max + 1 && $percents > 0 )
			{ // Star with a percent fill
				$template .= '<span style="width:'.$percents.'%">%</span>';
			}
			else
			{
				$template .= $s <= $active_stars_max ? '*' : '-';
			}
			$template .= '</span>';
		}
		$template .= '</span>';

		return $template;
	}
}

?>