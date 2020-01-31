<?php
/**
 * This file implements the Nofollow UGC Sponsored plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Nofollow UGC Sponsored plugin.
 *
 * @package plugins
 */
class nofollow_plugin extends Plugin
{
	var $code = 'evo_nofollow';
	var $name = 'Tag external links';
	var $priority = 99;
	var $version = '7.1.0';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'tag-external-links-plugin';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = sprintf( T_('Add options %s for absolute links'), '<code>rel="nofollow"</code>, <code>rel="ugc"</code>, <code>rel="sponsored"</code>, <code>target="_blank"</code>' );
		$this->long_desc = $this->short_desc;
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_values = array(
				'abs_links_posts_nofollow'     => 0,
				'abs_links_posts_ugc'          => 0,
				'abs_links_posts_sponsored'    => 0,
				'abs_links_posts_blank'        => 0,
				'abs_links_comments_nofollow'  => 1,
				'abs_links_comments_ugc'       => 1,
				'abs_links_comments_sponsored' => 0,
				'abs_links_comments_blank'     => 0,
			);

		if( ! empty( $params['blog_type'] ) )
		{	// Set the default settings depends on collection type:
			switch( $params['blog_type'] )
			{
				case 'forum':
					$default_values['abs_links_posts_nofollow'] = 1;
					$default_values['abs_links_posts_ugc'] = 1;
					break;
			}
		}

		// set params to allow rendering for comments by default
		$default_params = array_merge( $params, array(
				'default_post_rendering' => 'always',
				'default_comment_rendering' => 'always',
			) );
		return array_merge( parent::get_coll_setting_definitions( $default_params ),
			array(
				'abs_links_posts' => array(
						'label' => T_('For absolute links in posts'),
						'type' => 'checklist',
						'options' => array(
							array( 'nofollow', sprintf( $this->T_('Add %s'), '<code>rel="nofollow"</code>' ), $default_values['abs_links_posts_nofollow'] ),
							array( 'ugc', sprintf( $this->T_('Add %s'), '<code>rel="ugc"</code>' ), $default_values['abs_links_posts_ugc'] ),
							array( 'sponsored', sprintf( $this->T_('Add %s'), '<code>rel="sponsored"</code>' ), $default_values['abs_links_posts_sponsored'] ),
							array( 'blank', sprintf( $this->T_('Add %s'), '<code>target="_blank"</code>' ), $default_values['abs_links_posts_blank'] ),
						)
					),
				'abs_links_comments' => array(
						'label' => T_('For absolute links in comments'),
						'type' => 'checklist',
						'options' => array(
							array( 'nofollow', sprintf( $this->T_('Add %s'), '<code>rel="nofollow"</code>' ), $default_values['abs_links_comments_nofollow'] ),
							array( 'ugc', sprintf( $this->T_('Add %s'), '<code>rel="ugc"</code>' ), $default_values['abs_links_comments_ugc'] ),
							array( 'sponsored', sprintf( $this->T_('Add %s'), '<code>rel="sponsored"</code>' ), $default_values['abs_links_comments_sponsored'] ),
								array( 'blank', sprintf( $this->T_('Add %s'), '<code>target="_blank"</code>' ), $default_values['abs_links_comments_blank'] ),
						)
					),
			)
		);
	}


	/**
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'never' ) );
		return array_merge( parent::get_msg_setting_definitions( $default_params ) );
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'never' ) );
		return array_merge( parent::get_email_setting_definitions( $default_params ) );
	}


	/**
	 * Define here default shared settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_shared_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_shared_rendering' => 'never' ) );
		return array_merge( parent::get_shared_setting_definitions( $default_params ) );
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// Get collection from given params (also it is used to build link for tag links):
		$current_Blog = $this->get_Blog_from_params( $params );

		// Define the setting names depending on what is rendering now:
		$setting_name = ( empty( $params['Comment'] ) ? 'abs_links_posts' : 'abs_links_comments' );
		$this->setting_rel_options = array();
		if( $this->get_checklist_setting( $setting_name, 'nofollow', 'coll', $current_Blog ) )
		{
			$this->setting_rel_options[] = 'nofollow';
		}
		if( $this->get_checklist_setting( $setting_name, 'ugc', 'coll', $current_Blog ) )
		{
			$this->setting_rel_options[] = 'ugc';
		}
		if( $this->get_checklist_setting( $setting_name, 'sponsored', 'coll', $current_Blog ) )
		{
			$this->setting_rel_options[] = 'sponsored';
		}
		$this->setting_target_options = array();
		if( $this->get_checklist_setting( $setting_name, 'blank', 'coll', $current_Blog ) )
		{
			$this->setting_target_options[] = 'blank';
		}

		if( ! empty( $this->setting_rel_options ) || ! empty( $this->setting_target_options ) )
		{	// Try to find links only of at least one rel option should be added:
			$content = replace_content_outcode( '#<a([^>]+href="https?://[^"]+"[^>]*)>(.+?)</a>#i', array( $this, 'callback_render_content' ), $content, 'replace_content_callback' );
		}

		return false;
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
		return true;
	}


	/**
	 * Callback function to render content of Item, Comment
	 *
	 * @param array Matches
	 */
	function callback_render_content( $link_match )
	{
		$link_attrs = $link_match[1];

		if( ! empty( $this->setting_rel_options ) )
		{	// Add attribute "rel":
			if( preg_match( '# rel="([^"]*)"#i', $link_attrs, $rel_match ) )
			{	// If link already has attrbiute "rel":
				$rel_options = trim( $rel_match[1] );
				$rel_options = ( $rel_options === '' ? array() : explode( ' ', trim( $rel_match[1] ) ) );
				$rel_options_exist = true;
			}
			else
			{	// If link has no attrbiute "rel" yet:
				$rel_options = array();
				$rel_options_exist = false;
			}

			foreach( $this->setting_rel_options as $setting_rel_option )
			{
				if( ! in_array( $setting_rel_option, $rel_options ) )
				{	// Add only new option to avoid duplicates:
					$rel_options[] = $setting_rel_option;
				}
			}

			if( $rel_options_exist )
			{	// Update attribute "rel" with added options:
				$link_attrs = preg_replace( '# rel="([^"]*)"#i', ' rel="'.implode( ' ', $rel_options ).'"', $link_attrs );
			}
			else
			{	// Add attribute "rel":
				$link_attrs .= ' rel="'.implode( ' ', $rel_options ).'"';
			}
		}

		if( ! empty( $this->setting_target_options ) &&
		    in_array( 'blank', $this->setting_target_options ) )
		{	// Add attribute "target" only if it is not provided in the link:
			if( preg_match( '# target="([^"]*)"#i', $link_attrs, $target_match ) )
			{	// If link already has attrbiute "target":
				$target_option = trim( $target_match[1] );
				$target_options_exist = true;
			}
			else
			{	// If link has no attrbiute "target" yet:
				$target_option = '';
				$target_options_exist = false;
			}

			if( $target_option === '' )
			{	// Add new option to target only if this attribute is not defined or value is empty string:
				// Do NOT rewrite already defined attributes like `target="_parent"`, `target="_self"`, etc.!
				if( $target_options_exist )
				{	// Update attribute "target" with added options:
					$link_attrs = preg_replace( '# target="([^"]*)"#i', ' target="_blank"', $link_attrs );
				}
				else
				{	// Add attribute "rel":
					$link_attrs .= ' target="_blank"';
				}
			}
		}

		return '<a'.$link_attrs.'>'.$link_match[2].'</a>';
	}
}

?>