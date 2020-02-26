<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Widget class which provides an interface to widget methods for other classes.
 *
 * It provides a method {@link replace_vars()} that can be used to replace object properties in given strings.
 * You can also register global action icons.
 *
 * @package evocore
 * @abstract
 */
class Widget
{
	/**
	 * Display parameters.
	 * Example params would be 'block_start' and 'block_end'.
	 * Params may contain special variables that will be replaced by replace_vars()
	 * Different types of Widgets will expect different parameters.
	 * @var array
	 */
	var $params = NULL;

	/**
	 * Title of the widget (to be displayed)
	 */
	var $title;

	/**
	 * List of registered global action icons that get substituted through '$global_icons$'.
	 * @see global_icon()
	 */
	var $global_icons = array();

	/**
	 * Top block which located near second level tabs
	 */
	var $top_block = '';

	/**
	 * Constructor
	 *
	 * @param string template name to get from $AdminUI
	 */
	function __construct( $ui_template = NULL )
	{
		global $AdminUI;

		if( !empty( $ui_template ) )
		{ // Get template params from Admin Skin:
			$this->params = $AdminUI->get_template( $ui_template );
		}
	}


	/**
	 * Registers a global action icon
	 *
	 * @param string TITLE text (IMG and A link)
	 * @param string icon code for {@link get_icon()}
	 * @param string URL to link to
	 * @param integer 1-5: weight of the icon. the icon will be displayed only if its weight is >= than the user setting threshold
	 * @param integer 1-5: weight of the word. the word will be displayed only if its weight is >= than the user setting threshold
	 * @param array Additional attributes to the A tag. See {@link action_icon()}.
	 * @param string Group name is used to group several buttons in one as dropdown button for bootstrap skins
	 * @param array Group options: 'parent', 'class', 'item_class', 'btn_class'
	 */
	function global_icon( $title, $icon, $url, $word = '', $icon_weight = 3, $word_weight = 2, $link_attribs = array(), $group = NULL, $group_options = NULL )
	{
		$link_attribs = array_merge( array(
				'class'  => 'action_icon',
				'before' => '',
				'after'  => '',
			), $link_attribs );

		$this->global_icons[] = array(
			'title'        => $title,
			'icon'         => $icon,
			'url'          => $url,
			'word'         => $word,
			'icon_weight'  => $icon_weight,
			'word_weight'  => $word_weight,
			'link_attribs' => $link_attribs,
			'group'        => $group,
			'group_options'=> $group_options );
	}


  /**
	 * Display a template param without replacing variables
	 */
	function disp_template_raw( $param_name )
	{
		echo $this->params[ $param_name ];
	}


  /**
	 * Display a template param with its variables replaced
	 */
	function disp_template_replaced( $param_name )
	{
		echo $this->replace_vars( $this->params[ $param_name ] );
	}


	/**
	 * Replaces $vars$ with appropriate values.
	 *
	 * You can give an alternative string to display, if the substituted variable
	 * is empty, like:
	 * <code>$vars "Display if empty"$</code>
	 *
	 * @param string template
	 * @param array optional params that are put into {@link $this->params}
	 *              to be accessible by derived replace_callback() methods
	 * @return string The substituted string
	 */
	function replace_vars( $template, $params = NULL )
	{
		if( !is_null( $params ) )
		{
			$this->params = $params;
		}

		return preg_replace_callback(
			'~\$([a-z_]+)(?:\s+"([^"]*)")?\$~', # pattern
			array( $this, 'replace_callback_wrapper' ), # callback
			$template );
	}


	/**
	 * This is an additional wrapper to {@link replace_vars()} that allows to react
	 * on the return value of it.
	 *
	 * Used by replace_callback()
	 *
	 * @param array {@link preg_match() preg match}
	 * @return string
	 */
	function replace_callback_wrapper( $match )
	{
		// Replace the variable with its content (which will be computed on the fly)
		// This method is designed to be OVERRIDDEN by derived classes !
		$r = $this->replace_callback( $match );

		if( empty($r) )
		{	// Empty result
			if( !empty($match[2]) )
			{
				return $match[2]; // "display if empty"
			}

			// return $match[1];
		}
		return $r;
	}


	/**
	 * Callback function used to replace only necessary values in template.
	 *
	 * This gets used by {@link replace_vars()} to replace $vars$.
	 *
	 * @param array {@link preg_match() preg match}. Index 1 is the template variable.
	 * @return string to be substituted
	 */
	function replace_callback( $matches )
	{
		//echo $matches[1];
		switch( $matches[1] )
		{
			case 'global_icons' :
				// Icons for the whole result set:
				return $this->gen_global_icons();

			case 'title':
				// Results title:
				// Espace $title$ strings from the title to avoid infinite loop replacing
				$escaped_title = str_replace( '$title$', '&#36;title&#36;', $this->title );
				// Replace vars on the title
				$result = $this->replace_vars( $escaped_title );
				// Replace back the $title$ strings and return the result
				return str_replace( '&#36;title&#36;', '$title$', $result );

			case 'no_results':
				// No Results text:
				return $this->no_results_text;

			case 'top_block':
				// Top block:
				return $this->top_block;

			case 'prefix' :
				//prefix
				return $this->param_prefix;

			case 'group_id':
				// Group ID (e-g used for accordion style)
				return isset( $this->params['group_id'] ) ? $this->params['group_id'] : '';

			case 'group_item_id':
				// ID of group element (e-g used for accordion style)
				return isset( $this->params['group_item_id'] ) ? $this->params['group_item_id'] : '';

			default:
				return '[Unknown:'.$matches[1].']';
		}
	}


	/**
	 * Generate img tags for registered icons, through {@link global_icon()}.
	 *
	 * This is used by the default callback to replace '$global_icons$'.
	 */
	function gen_global_icons()
	{
		global $AdminUI;

		$icons = array();

		foreach( $this->global_icons as $icon_params )
		{
			if( isset( $this->params, $this->params['global_icons_class'] ) )
			{ // Append a link class from global params
				if( strpos( $icon_params['link_attribs']['class'], 'btn-' ) !== false )
				{
					$global_icons_class = str_replace( 'btn-default', '', $this->params['global_icons_class'] );
				}
				else
				{
					$global_icons_class = $this->params['global_icons_class'];
				}
				$icon_params['link_attribs']['class'] = ( empty( $icon_params['link_attribs']['class'] ) ? '' : $icon_params['link_attribs']['class'].' ' ).$global_icons_class;
			}
			if( $icon_params['group'] != NULL )
			{ // It is a groupped button
				if( ! isset( $icons[ $icon_params['group'] ] ) )
				{ // Initialize an array for grouped icons:
					$icons[ $icon_params['group'] ] = array();
				}
				$icons[ $icon_params['group'] ][] = $icon_params;
			}
			else
			{ // Separated icon
				$before = $icon_params['link_attribs']['before'];
				$after = $icon_params['link_attribs']['after'];
				unset( $icon_params['link_attribs']['before'] );
				unset( $icon_params['link_attribs']['after'] );
				$icons[] = $before.action_icon( $icon_params['title'], $icon_params['icon'], $icon_params['url'], $icon_params['word'],
							$icon_params['icon_weight'], $icon_params['word_weight'], $icon_params['link_attribs'] ).$after;
			}
		}

		$r = '';
		foreach( $icons as $group => $icon )
		{
			if( is_array( $icon ) && count( $icon ) )
			{ // Grouped icons
				$first_icon = $icon[0];
				$r .= '<div class="btn-group dropdown'.( empty( $first_icon['group_options']['class'] ) ? '' : ' '.$first_icon['group_options']['class'] ).'">';
				$r .= '<a href="'.$first_icon['url'].'" class="'.$first_icon['link_attribs']['class'].'" title="'.format_to_output( $first_icon['title'], 'htmlattr' ).'">'.get_icon( $first_icon['icon'] ).' '.$first_icon['word'].'</a>';
				$r .= '<button type="button" class="btn btn-sm btn-default dropdown-toggle'.( empty( $first_icon['group_options']['btn_class'] ) ? '' : ' '.$first_icon['group_options']['btn_class'] ).'" data-toggle="dropdown" aria-expanded="false">'
							.' <span class="caret"></span></button>';
				$r .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">';
				foreach( $icon as $grouped_icon )
				{
					$r .= '<li role="presentation"><a href="'.$grouped_icon['url'].'" role="menuitem" tabindex="-1" title="'.format_to_output( $grouped_icon['title'], 'htmlattr' ).'">'.get_icon( $grouped_icon['icon'] ).' '.$grouped_icon['word'].'</a></li>';
				}
				foreach( $this->global_icons as $icon_params )
				{
					if( isset( $icon_params['group_options']['parent'] ) && $icon_params['group_options']['parent'] == $group )
					{	// Append also items from others buttons if they a linked with this button by group:
						$r .= '<li role="presentation"'.( empty( $icon_params['group_options']['item_class'] ) ? '' : ' class="'.$icon_params['group_options']['item_class'].'"' ).'><a href="'.$icon_params['url'].'" role="menuitem" tabindex="-1" title="'.format_to_output( $icon_params['title'], 'htmlattr' ).'">'.get_icon( $icon_params['icon'] ).' '.$icon_params['word'].'</a></li>';
					}
				}
				$r .= '</ul>';
				$r .= '</div>';
			}
			else
			{ // Separated icon
				$r .= $icon;
			}
		}

		return $r;
	}

}

?>