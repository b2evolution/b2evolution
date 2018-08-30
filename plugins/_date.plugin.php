<?php
/**
 * This file implements the Date renderer plugin for b2evolution
 *
 * Date formatting, like [date:server:F d, Y:-03.30]
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class date_plugin extends Plugin
{
	var $code = 'b2evDate';
	var $name = 'Date tag';
	var $priority = 57;
	var $version = '6.10.3';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'date-plugin';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = sprintf( T_('Date formatting e-g %s'), '<code>[date:server:F d, Y:-03.30]</code>' );
		$this->long_desc = sprintf( T_('This plugin allows to render date inside blog posts and comments by using the syntax %s for example'), '<code>[date:server:F d, Y:-03.30]</code>' );
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function DisplayItemAsHtml( & $params )
	{
		$this->rendering_Object = NULL;
		if( isset( $params['Comment'] ) )
		{	// Render for Comment:
			$this->rendering_Object = $params['Comment'];
		}
		elseif( isset( $params['Item'] ) )
		{	// Render for Item:
			$this->rendering_Object = $params['Item'];
		}
		elseif( isset( $params['Message'] ) )
		{	// Render for Message:
			$this->rendering_Object = $params['Message'];
		}
		elseif( isset( $params['EmailCampaign'] ) )
		{	// Render for EmailCampaign:
			$this->rendering_Object = $params['EmailCampaign'];
		}
		elseif( isset( $params['Widget'] ) )
		{	// Render for Widget:
			$this->rendering_Object = $params['Widget'];
		}

		$params['data'] = $this->render_dates( $params['data'] );

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
	 * Perform rendering of email
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 */
	function RenderEmailAsHtml( & $params )
	{
		return $this->DisplayItemAsHtml( $params );
	}


	/**
	 * Convert inline date tags into HTML tags like:
	 *    [date]
	 *    [date:server]
	 *    [date:server:F d, Y]
	 *    [date:server:F d, Y:-03.30]
	 *    [date:issued:Y-m-d H\:i\:s]
	 *    [date:issued:F d, Y H\:i\:s:+1\:04]
	 *    [date:modified:Y-m-d H\:i\:s]
	 *    [date:modified:F d, Y:0\:30]
	 *    [date:touched:F d, Y:11.30]
	 *    [date:touched:F d, Y]
	 *
	 * @param string Source content
	 * @return string Rendered content
	 */
	function render_dates( $content )
	{
		if( empty( $this->rendering_Object ) )
		{	// Don't render if no object is provided:
			return $content;
		}

		return replace_content_outcode( '#\[date(:([^\]]+))?\]#i', array( $this, 'get_date_text' ), $content, 'replace_content_callback' );
	}


	/**
	 * Get text for date
	 *
	 * @param array Matches
	 * @return string Date text
	 */
	function get_date_text( $matches )
	{
		if( empty( $matches ) )
		{	// No dates found
			return;
		}

		$object_type = get_class( $this->rendering_Object );

		// Get additional options:
		$options = isset( $matches[2] ) ? trim( $matches[2] ) : false;
		$options = empty( $options ) ? false : preg_split( '/(?<!\\\):/', $options );

		// Date source:
		$date_source = isset( $options[0] ) ? $options[0] : 'server';

		// Date format:
		if( isset( $options[1] ) )
		{	// Use custom date format:
			$date_format = $options[1];
		}
		else
		{	// Use a date format from the rendering object:
			switch( $object_type )
			{
				case 'Item':
					$rendering_Item = $this->rendering_Object;
					$date_locale = $rendering_Item->get( 'locale' );
					break;
				case 'Comment':
					$rendering_Comment = $this->rendering_Object;
					$rendering_comment_Item = $rendering_Comment->get_Item();
					$date_locale = $rendering_comment_Item->get( 'locale' );
					break;
				default:
					// Use current locale:
					$date_locale = NULL;
			}
			$date_format = locale_datefmt( $date_locale );
		}

		// Date offset/shifting:
		$date_offset = isset( $options[2] ) ? $options[2] : 0;

		switch( $date_source )
		{
			case 'issued':
				switch( $object_type )
				{
					case 'Item':
						$rendering_Item = $this->rendering_Object;
						$date_source = strtotime( $rendering_Item->get( 'datestart' ) );
						break;
					case 'Comment':
						$rendering_Comment = $this->rendering_Object;
						$date_source = strtotime( $rendering_Comment->get( 'date' ) );
						break;
					case 'Message':
						$rendering_Message = $this->rendering_Object;
						$date_source = strtotime( $rendering_Message->get( 'datetime' ) );
						break;
					case 'EmailCampaign':
						$rendering_EmailCampaign = $this->rendering_Object;
						$date_source = strtotime( $rendering_EmailCampaign->get( 'date_ts' ) );
						break;
					default:
						$date_source = false;
				}
				break;
			case 'modified':
				switch( $object_type )
				{
					case 'Item':
						$rendering_Item = $this->rendering_Object;
						$date_source = strtotime( $rendering_Item->get( 'datemodified' ) );
						break;
					default:
						$date_source = false;
				}
				break;
			case 'touched':
				switch( $object_type )
				{
					case 'Item':
						$rendering_Item = $this->rendering_Object;
						$date_source = strtotime( $rendering_Item->get( 'last_touched_ts' ) );
						break;
					case 'Comment':
						$rendering_Comment = $this->rendering_Object;
						$date_source = strtotime( $rendering_Comment->get( 'last_touched_ts' ) );
						break;
					default:
						$date_source = false;
				}
				break;
			default: // 'server'
				global $servertimenow;
				$date_source = $servertimenow;
		}

		if( $date_source === false )
		{	// Display error if source cannot be used for the rendering object:
			return '<span class="evo_param_error">'.sprintf( T_('%s is not valid for %s'), $matches[0], $object_type ).'</span>';
		}

		if( preg_match( '/-?\d{1,2}([\.,:]\d{1,2})?/', $date_offset ) && ! empty( $date_offset ) )
		{	// Shift date:
			$date_offset = preg_split( '/[\.,:]/', $date_offset );
			$o_date_source = $date_source;
			// Shift with hours:
			$date_source += rtrim( $date_offset[0], '\\' ) * 3600;
			if( isset( $date_offset[1] ) )
			{	// Shift with minutes:
				if( $date_offset[0] < 0 )
				{	// Use correct sign for minutes as hours have:
					$date_offset[1] = -$date_offset[1];
				}
				$date_source += $date_offset[1] * 60;
			}
		}

		return date( $date_format, $date_source );
	}
}

?>