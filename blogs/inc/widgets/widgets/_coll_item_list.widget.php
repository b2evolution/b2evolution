<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_item_list_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_item_list_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_item_list' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		/**
		 * @var ItemTypeCache
		 */
		$ItemTypeCache = & get_Cache( 'ItemTypeCache' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 60,
					'defaultvalue' => T_('Items'),
				),
				'title_link' => array(
					'label' => T_('Link to blog'),
					'note' => T_('Link the block title to the blog?'),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'item_type' => array(
					'label' => T_('Item type'),
					'note' => T_('What kind of items do you want to list?'),
					'type' => 'select',
					'options' => array_merge( array(
							'#' => T_('Default'),
							'' => T_('All'),
						), $ItemTypeCache->get_option_array() ),
					'defaultvalue' => '#',
				),
				'blog_ID' => array(
					'label' => T_( 'Blog' ),
					'note' => T_( 'ID of the blog to use, leave empty for the current blog.' ),
					'size' => 4,
				),
				'order_by' => array(
					'label' => T_('Order by'),
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => get_available_sort_options(),
					'defaultvalue' => 'datestart',
				),
				'order_dir' => array(
					'label' => T_('Direction'),
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => array( 'ASC'  => T_('Ascending'), 'DESC' => T_('Descending') ),
					'defaultvalue' => 'DESC',
				),
				'limit' => array(
					'label' => T_( 'Limit' ),
					'note' => T_( 'Maximum number of items to display.' ),
					'size' => 4,
					'defaultvalue' => 20,
				),
				'item_title_link_type' => array(
					'label' => T_('Link titles'),
					'note' => T_('Where should titles be linked to?'),
					'type' => 'select',
					'options' => array(
							'auto'        => T_('Automatic'),
							'permalink'   => T_('Item permalink'),
							'linkto_url'  => T_('Item URL'),
							'none'        => T_('Nowhere'),
						),
					'defaultvalue' => 'auto',
				),
				'disp_excerpt' => array(
					'label' => T_( 'Excerpt' ),
					'note' => T_( 'Display excerpt for each item.' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'disp_teaser' => array(
					'label' => T_( 'Content teaser' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
					'note' => T_( 'Display content teaser for each item.' ),
				),
				'disp_teaser_maxwords' => array(
					'label' => T_( 'Max Words' ),
					'type' => 'integer',
					'defaultvalue' => 20,
					'note' => T_( 'Max number of words for the teasers.' ),
				),
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Universal Item list');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Can list Items (Posts/Pages/Links...) in a variety of ways.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		// List of posts:
		$this->disp_item_list();

		return true;
	}


	/**
	 * List of items
	 *
	 * @param string 'pages' or 'posts'
	 */
	function disp_item_list()
	{
		global $Blog;
		global $timestamp_min, $timestamp_max;

		$blogCache = get_Cache( 'BlogCache' );
		// TODO: dh> does it make sense to die in $blogCache, in case the blog does not exist anymore?
		$listBlog = ( $this->disp_params[ 'blog_ID' ] ? $blogCache->get_by_ID( $this->disp_params[ 'blog_ID' ] ) : $Blog );

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = $this->disp_params[ 'limit' ];

		if( $this->disp_params['disp_teaser'] )
		{ // We want to show some of the post content, we need to load more info: use ItemList2
			$ItemList = & new ItemList2( $listBlog, $timestamp_min, $timestamp_max, $limit, 'ItemCache', $this->code.'_' );
		}
		else
		{ // no excerpts, use ItemListLight
			$ItemList = & new ItemListLight( $listBlog, $timestamp_min, $timestamp_max, $limit, 'ItemCacheLight', $this->code.'_' );
		}

		// Filter list:
		$filters = array(
				'orderby' => $this->disp_params[ 'order_by' ],
				'order' => $this->disp_params[ 'order_dir' ],
				'unit' => 'posts',						// We want to advertise all items (not just a page or a day)
			);

		if( $this->disp_params['item_type'] != '#' )
		{	// Not "default", restrict to a specific type (or '' for all)
			$filters['types'] = $this->disp_params['item_type'];
		}

		$ItemList->set_filters( $filters, false );

		// Run the query:
		$ItemList->query();

		if( ! $ItemList->result_num_rows )
		{	// Nothing to display:
			return;
		}

		echo $this->disp_params['block_start'];

		$title = sprintf( ( $this->disp_params[ 'title_link' ] ? '<a href="'.$listBlog->gen_blogurl().'" rel="nofollow">%s</a>' : '%s' ), $this->disp_params[ 'title' ] );

		$this->disp_title( $title );

		echo $this->disp_params['list_start'];

		while( $Item = & $ItemList->get_item() )
		{
			echo $this->disp_params['item_start'];

			// Display contents of the Item depending on widget params:
			$this->disp_contents( $Item );

			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_end'];
	}

}


/*
 * $Log$
 * Revision 1.1  2009/03/15 20:35:18  fplanque
 * Universal Item List proof of concept
 *
 */
?>