<?php
/**
 * This file implements the Info dots renderer plugin for b2evolution
 *
 * Info dots formatting, like [infodot:1234:40:60:20ex]text of the info dot additional info text including <a href="/">link text</a>[enddot]
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class infodots_plugin extends Plugin
{
	var $code = 'b2evoDot';
	var $name = 'Info dots renderer';
	var $priority = 95;
	var $version = '6.7.8';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'infodots-plugin';
	var $number_of_installs = 1;

	/*
	 * Internal vars
	 */
	var $search_text;
	var $replace_func;
	var $dots = NULL;
	var $object_ID = 0;
	var $loaded_objects = NULL;
	var $dot_numbers = NULL;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Info dots formatting e-g [infodot:1234:40:60:20ex]html text[enddot]');
		$this->long_desc = T_('This plugin allows to render info dots over images by using the syntax [infodot:1234:40:60:20ex]html text[enddot] for example');
	}


	/**
	 * Define here default custom settings that are to be made available
	 *     in the backoffice for collections, private messages and newsletters.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_custom_setting_definitions()}.
	 */
	function get_custom_setting_definitions( & $params )
	{
		return array(
			'coll_min_width' => array(
					'label' => T_('Min width'),
					'type' => 'integer',
					'size' => 4,
					'defaultvalue' => 400,
					'note' => T_('Enter the minimum pixel width an image must have for dots to be displayed.')
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
		$default_params = array_merge( $params, array(
				'default_comment_rendering' => 'never',
				'default_post_rendering' => 'opt-out'
			) );

		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Include JS/CSS files in HTML head
	 */
	function init_html_head()
	{
		global $Blog;

		if( ! isset( $Blog ) || (
		    $this->get_coll_setting( 'coll_apply_rendering', $Blog ) == 'never' &&
		    $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) == 'never' ) )
		{ // Don't load css/js files when plugin is not enabled
			return;
		}

		$this->require_css( 'infodots.css' );

		// Bubbletip
		require_js( '#jquery#', 'blog' );
		require_js( 'jquery/jquery.bubbletip.min.js', 'blog' );
		require_css( 'jquery/jquery.bubbletip.css', 'blog' );

		add_js_headline( 'jQuery( document ).ready( function()
{
	jQuery( ".infodots_dot" ).each( function()
	{ // Check what dot we can show on the page
		if( jQuery( "#" + jQuery( this ).attr( "rel" ) ).length )
		{ // Display dot if a content exists
			jQuery( this ).show();
		}
		else
		{ // Remove dot from the page, probably this dot appears after <more> separator
			jQuery( this ).remove();
		}
	} );

	jQuery( ".infodots_dot" ).mouseover( function()
	{
		var tooltip_obj = jQuery( "#" + jQuery( this ).attr( "rel" ) );
		if( tooltip_obj.length )
		{ // Init bubbletip for point once
			if( typeof( infodots_bubbletip_wrapperContainer ) == "undefined" ||
			    jQuery( infodots_bubbletip_wrapperContainer ).length == 0 )
			{ // Check for correct container
				infodots_bubbletip_wrapperContainer = "body";
			}

			jQuery( this ).bubbletip( tooltip_obj,
			{
				showOnInit: true,
				deltaShift: -5,
				wrapperContainer: infodots_bubbletip_wrapperContainer,
			} );
		}
		jQuery( this ).addClass( "hovered" );
	} )
	.bind( "click", function()
	{ // Duplicate this event for "touch" devices
		jQuery( this ).mouseover();
	} );
} );' );
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
		$this->init_html_head();
	}


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		$this->init_html_head();
	}


	/**
	 * Perform rendering
	 *
	 * Renders code:
	 * from [infodot:1234:40:60:20ex]html text[enddot]
	 * to <div class="infodots_info" id="infodot_1234_1" data-xy="40:60" style="width:20ex:>html text</div>
	 * This html <div> is hidden by css .infodots_info { display: none; }. 
	 * This plugin event is called only once to render content.
	 * This only generates the content divs. The red dots themselves are added later to the evo_image_block divs, in the "RenderItemAttachment" event.
	 * 
	 * This event also collects all dots in the array $this->dots.
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$params['data'] = $this->render_infodot_captions( 'itm_'.$params['Item']->ID, $params['data'] );

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
	 * Perform rendering of Message content
	 *
	 * NOTE: Use default coll settings of comments as messages settings
	 *
	 * @see Plugin::RenderMessageAsHtml()
	 */
	function RenderMessageAsHtml( & $params )
	{
		// This plugin cannot works with messages:
		return true;
	}


	/**
	 * Perform rendering of Email content
	 *
	 * NOTE: Use default coll settings of comments as messages settings
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 */
	function RenderEmailAsHtml( & $params )
	{
		// This plugin cannot works with emails:
		return true;
	}


	/**
	 * Render comments if required
	 *
	 * Renders code:
	 * from [infodot:1234:40:60:20ex]html text[enddot]
	 * to <div class="infodots_info" id="infodot_1234_1" data-xy="40:60" style="width:20ex:>html text</div>
	 * This html <div> is hidden by css .infodots_info { display: none; }. 
	 * This plugin event is called only once to render content.
	 * This only generates the content divs. The red dots themselves are added later to the evo_image_block divs, in the "RenderCommentAttachment" event.
	 * 
	 * This event also collects all dots in the array $this->dots.
	 *
	 * @see Plugin::FilterCommentContent()
	 */
	function FilterCommentContent( & $params )
	{
		$Comment = & $params['Comment'];

		if( in_array( $this->code, $Comment->get_renderers_validated() ) )
		{	// If apply_comment_rendering is set to render:
			$params['data'] = $this->render_infodot_captions( 'cmt_'.$Comment->ID, $params['data'] );
		}
	}


	/**
	 * Render infodots from like [infodot:1234:40:60:20ex]html text[enddot]
	 *    to <div class="infodots_info" id="infodot_1234_1" data-xy="40:60" style="width:20ex:>html text</div>
	 *
	 * @param string Object ID: for example, 'itm_123', 'cmt_456'.
	 * @param string Source content
	 * @return string Rendered content
	 */
	function render_infodot_captions( $object_ID, $content )
	{
		$this->dot_numbers = NULL;
		$this->object_ID = $object_ID;

		$content = replace_content_outcode( '#((<br />|<p>)\r?\n?)?\[infodot:(\d+):(-?\d+[pxecm%]*):(-?\d+[pxecm%]*)(:\d+[pxecm%]*)?\](.+?)\[enddot\](\r?\n?(<br />|</p>))?#is',
				array( $this, 'load_infodot_from_source' ),
				$content, 'replace_content_callback' );

		$this->loaded_objects[ $this->object_ID ] = 1;

		return $content;
	}


	/**
	 * Callback function to load a dot from NOT rendered content
	 *
	 * @param array Matches
	 * @param boolean TRUE is used only to load dot without returning of tooltip template
	 * @return string Empty string to don't display the dot template in content, It is printed out before image tag
	 */
	function load_infodot_from_source( $matches, $only_load_dot = false )
	{
		$link_ID = intval( $matches[3] );

		if( empty( $link_ID ) || empty( $matches ) || empty( $this->object_ID ) )
		{ // Skip this incorrect match
			return;
		}

		$LinkCache = & get_LinkCache();
		$Link = & $LinkCache->get_by_ID( $link_ID, false, false );
		if( ! $Link )
		{ // Inform about invalid Link ID
			return '<div style="color:#F00"><b>'.T_('Invalid Link ID').' - '.$matches[0].'</b></div>';
		}

		if( $this->dot_numbers === NULL )
		{ // Init dot numbers array first time
			$this->dot_numbers = array();
		}

		if( ! isset( $this->dot_numbers[ $link_ID ] ) )
		{ // Start to calculate number of the dots for current Link object
			$this->dot_numbers[ $link_ID ] = 1;
		}

		if( ! isset( $this->loaded_objects[ $this->object_ID ] ) )
		{ // Load dots only once
			if( $this->dots === NULL )
			{ // Init dots array first time
				$this->dots = array();
			}

			if( ! isset( $this->dots[ $link_ID ] ) )
			{ // Init sub array for each Link
				$this->dots[ $link_ID ] = array();
			}

			// Add dot
			$this->dots[ $link_ID ][] = array(
					'x' => $matches[4].( strlen( intval( $matches[4] ) ) == strlen( $matches[4] ) ? 'px' : '' ), // Left
					'y' => $matches[5].( strlen( intval( $matches[5] ) ) == strlen( $matches[5] ) ? 'px' : '' ), // Top
				);
		}

		if( $only_load_dot )
		{ // Exit here to don't execute a code below
			return;
		}

		$dot_num = $this->dot_numbers[ $link_ID ];
		if( empty( $matches[6] ) )
		{ // No defined width
			$tooltip_width = '';
		}
		else
		{ // Set css style for width
			$tooltip_width = substr( $matches[6], 1 );
			$tooltip_width = ( strlen( intval( $tooltip_width ) ) == strlen( $tooltip_width ) ? $tooltip_width.'px' : $tooltip_width );
			$tooltip_width = ' style="width:'.$tooltip_width.'"';
		}
		$dot_xy = ' data-xy="'.$this->dots[ $link_ID ][ $dot_num - 1 ]['x'].':'.$this->dots[ $link_ID ][ $dot_num - 1 ]['y'].'"';

		$this->dot_numbers[ $link_ID ]++;

		// Print this element that will be used for tooltip of the dot
		return '<div class="infodots_info" id="infodot_'.$link_ID.'_'.$dot_num.'"'.$dot_xy.$tooltip_width.'>'
				.balance_tags( $matches[7] )
			.'</div>'."\n";
	}


	/**
	 * Callback function to load a dot from the rendered content
	 *
	 * @param array Matches
	 */
	function load_infodot_from_rendered_content( $matches )
	{
		// Load a dot from the rendered content
		$this->load_infodot_from_source( array(
				0 => $matches[0],
				3 => $matches[1],
				4 => $matches[4],
				5 => $matches[5]
			), true );
	}


	/**
	 * Render the dots before <img> tag
	 *
	 * Renders code:
	 * from the before collected array $this->dots
	 * OR from the prerendered content <div class="infodots_info" id="infodot_1234_1" data-xy="40:60">html text</div>
	 * to <div class="infodots_dot" rel="infodot_1234_1" style="left:40px;top:60px"></div>
	 *
	 * @param array Associative array of parameters. $params['File'] - attachment, $params['data'] - output
	 * @param string Content of the Item/Comment
	 */
	function render_infodots( & $params, $content )
	{
		if( empty( $params['File'] ) || empty( $params['Link'] ) )
		{ // Check input data
			return;
		}

		$File = $params['File'];
		$Link = $params['Link'];

		if( ! $File->is_image() )
		{ // This plugin works only with image files
			return;
		}

		if( ( $LinkOwner = & $Link->get_LinkOwner() ) === false || ( $Blog = & $LinkOwner->get_Blog() ) === false )
		{ // Couldn't get Blog object
			return;
		}

		global $thumbnail_sizes;
		$thumbnail_width = isset( $thumbnail_sizes[ $params['image_size'] ] ) ? $thumbnail_sizes[ $params['image_size'] ][1] : 0;

		if( $File->get_image_size( 'width' ) < $this->get_coll_setting( 'coll_min_width', $Blog ) ||
		    $thumbnail_width < $this->get_coll_setting( 'coll_min_width', $Blog ) )
		{ // Don't draw a dot on image if width is less than setting value
			return;
		}

		if( ! isset( $this->loaded_objects[ $this->object_ID ] ) )
		{ // Load the info dots if they were not loaded before:
			replace_content_outcode( '#<div class="infodots_info" id="infodot_(\d+)_(\d+)" (data-)?xy="(-?\d+[pxecm%]*):(-?\d+[pxecm%]*)"[^>]*>(.+?)</div>#is', 
				array( $this, 'load_infodot_from_rendered_content' ), $content, 'replace_content_callback' );
			$this->loaded_objects[ $this->object_ID ] = 1;
		}

		if( empty( $this->dots[ $Link->ID ] ) )
		{ // No dots for this Link
			return;
		}

		$before_image = '<div class="infodots_image">'."\n";
		foreach( $this->dots[ $Link->ID ] as $d => $dot )
		{ // Init html element for each dot
			$before_image .= '<div class="infodots_dot" rel="infodot_'.$Link->ID.'_'.( $d + 1 ).'" style="left:'.$dot['x'].';top:'.$dot['y'].'"></div>'."\n";
		}

		// Append info dots html to current image tag
		$params['before_image'] = $params['before_image'].$before_image;
		$params['after_image'] = '</div>'.$params['after_image'];
	}


	/**
	 * Event handler: Called when displaying item attachment.
	 *
	 * THis will render the red dots in the same <div> as the image, which is important for using relative positioning based on the image coordinates.
	 *
	 * Renders the red dots before each image by getting them from array $this->dots if it was initialized during RenderItemAsHtml().
	 * If RenderItemAsHtml() was not called (which happens if content was already pre-rendered) then the array $this->dots is built by preg_match from prerendered content 
	 * from hidden div <div class="infodots_info" id="infodot_1234_1" data-xy="40:60" style="width:20ex:>html text</div>
	 *
	 * The dots are rendered as <div class="infodots_image"> <div class="infodots_dot" rel="infodot_1234_1" style="left:40px;top:60px"></div> </div> before attached image.
	 *
	 * @param array Associative array of parameters. $params['File'] - attachment, $params['data'] - output
	 * @param boolean TRUE - when render in comments
	 * @return boolean true if plugin rendered this attachment
	 */
	function RenderItemAttachment( & $params, $in_comments = false )
	{
		if( empty( $params['Item'] ) || empty( $params['Link'] ) )
		{ // Check input data
			return false;
		}

		$Item = & $params['Item'];

		$this->dot_numbers = NULL;
		$this->object_ID = 'itm_'.$Item->ID;

		// Render dots
		$this->render_infodots( $params, $Item->get_prerendered_content( 'htmlbody' ) );

		// Plugin only adds data to $params['before_image?] but doesn?t actually display the image.
		// So, return false to communicate we do NOT display the image, so that the core will take care of displaying the image:
		return false;
	}


	/**
	 * Event handler: Called when displaying comment attachment.
	 *
	 * See sister function RenderItemAttachment() above for more details.
	 *
	 * @param array Associative array of parameters. $params['File'] - attachment, $params['data'] - output
	 * @return boolean true if plugin rendered this attachment
	 */
	function RenderCommentAttachment( & $params )
	{
		if( empty( $params['Comment'] ) || empty( $params['Link'] ) )
		{ // Check input data
			return false;
		}

		$Comment = & $params['Comment'];

		$this->dot_numbers = NULL;
		$this->object_ID = 'cmt_'.$Comment->ID;

		// Render dots
		$this->render_infodots( $params, $Comment->get_prerendered_content( 'htmlbody' ) );

		// Plugin just modifies the params and doesn't touch/render a real object
		// So return FALSE here everytime to don't rewrite the real object
		return false;
	}
}

?>