<?php
/**
 * This file implements the coll_xml_feeds Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_xml_feeds_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_xml_feeds_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_xml_feeds' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'xml-feeds-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('XML Feeds (RSS / Atom)');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->get_title());
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('List of all available XML feeds.');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $use_strict;
		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display, $icon$ will be replaced by the feed icon' ),
					'defaultvalue' => '$icon$ '.T_('XML Feeds'),
				),
				'disp_info_link' => array(
					'label' => T_( 'Help link' ),
					'type' => 'checkbox',
					'note' => T_( 'Check this to display "What is RSS?" link' ),
					'defaultvalue' => 1,
				),
				'info_link' => array(
					'label' => T_( 'New Window' ),
					'type' => 'checkbox',
					'note' => T_( 'Check this to add target="_blank" to the "What is RSS?" link' ),
					'defaultvalue' => !$use_strict,
				),
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	function get_title()
	{
		global $rsc_uri;

		$title = str_replace( '$icon$', get_icon('feed'), $this->disp_params['title']);
		// fp> TODO: support for different icon sizes and backgrounds (at least black and white; mid grey would be cool also)

		return $title;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog;

		$this->init_display( $params );

		// Available XML feeds:
		echo $this->disp_params['block_start'];

		$this->disp_title( $this->get_title() );

		echo $this->disp_params['block_body_start'];

		echo $this->disp_params['list_start'];

		$SkinCache = & get_SkinCache();
		$SkinCache->load_by_type( 'feed' );

		// TODO: this is like touching private parts :>
		foreach( $SkinCache->cache as $Skin )
		{
			if( $Skin->type != 'feed' )
			{	// This skin cannot be used here...
				continue;
			}

			echo $this->disp_params['item_start'];
			echo $Skin->name.': ';
			echo '<a href="'.$Blog->get_item_feed_url( $Skin->folder ).'">'.T_('Posts').'</a>';
			if ( $Blog->get_setting( 'allow_comments' ) != 'never' && $Blog->get_setting( 'comment_feed_content' ) != 'none' && $Blog->get_setting( 'comments_latest' ) )
			{
				echo ', <a href="'.$Blog->get_comment_feed_url( $Skin->folder ).'">'.T_('Comments').'</a>';
			}

			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];


		// Display "info" link, if activated.
		if( $this->disp_params['disp_info_link'] )
		{
			/**
			 * @var AbstractSettings
			 */
			global $global_Cache;

			$feedhlp = $global_Cache->get( 'feedhlp' );
			if( empty( $feedhlp ) )
			{	// Use basic default: (fp> needs serious update) -- Note: no localization because destination is in English anyway.
				$feedhlp = array( array( 'http://www.webreference.fr/defintions/rss-atom-xml', 'What is RSS?' ) );
			}

			if( $this->disp_params[ 'info_link' ] )
			{
				$link_params = array( 'target' => '_blank' );
			}
			else
			{
				$link_params = array( 'target' => '' );
			}
			display_list( $feedhlp, $this->disp_params['notes_start'], $this->disp_params['notes_end'], ' ', '', '', NULL, 1, $link_params );
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>