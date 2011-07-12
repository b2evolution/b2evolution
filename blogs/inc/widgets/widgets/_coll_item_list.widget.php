<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

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
		$ItemTypeCache = & get_ItemTypeCache();
		$item_type_options =
			array(
				'#' => T_('Default'),
				''  => T_('All'),
			) + $ItemTypeCache->get_option_array() ;

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
					'options' => $item_type_options,
					'defaultvalue' => '#',
				),
				'follow_mainlist' => array(
					'label' => T_('Follow Main List'),
					'note' => T_('Do you want to restrict to contents related to what is displayed in the main area?'),
					'type' => 'radio',
					'options' => array( array ('no', T_('No') ), 
										array ('tags', T_('By tags') ) ), // may be extended
					'defaultvalue' => 'no',
				),
				'blog_ID' => array(
					'label' => T_( 'Blog' ),
					'note' => T_( 'ID of the blog to use, leave empty for the current blog.' ),
					'size' => 4,
				),
/* TODO: filter this out from all "SIMPLE" lists and keep it only in universal list
				'cat_IDs' => array(
					'label' => T_( 'Categories' ),
					'note' => T_( 'List category IDs separated by ,' ),
					'size' => 15,
				),
*/
				'item_group_by' => array(
					'label' => T_('Group by'),
					'note' => T_('Do you want to group the Items?'),
					'type' => 'radio',
					'options' => array( array( 'none', T_('None') ), 
										array( 'chapter', T_('By category/chapter') ) ),
					'defaultvalue' => 'none',
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
					'type' => 'radio',
					'options' => array( array( 'ASC', T_('Ascending') ), 
										array( 'DESC', T_('Descending') ) ),
					'defaultvalue' => 'DESC',
				),
				'limit' => array(
					'label' => T_( 'Max items' ),
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

		// pre_dump( $r['item_type']['options'] );

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
		/**
		 * @var ItemList2
		 */
		global $MainList;
		global $BlogCache, $Blog;
		global $timestamp_min, $timestamp_max;
		global $Item;

		$this->init_display( $params );

		if( $this->disp_params[ 'order_by' ] == 'RAND' && isset($this->BlockCache) )
		{	// Do NOT cache if display order is random
			$this->BlockCache->abort_collect();
		}

		$listBlog = ( $this->disp_params[ 'blog_ID' ] ? $BlogCache->get_by_ID( $this->disp_params[ 'blog_ID' ], false ) : $Blog );

		if( empty($listBlog) )
		{
			echo $this->disp_params['block_start'];
			echo T_('The requested Blog doesn\'t exist any more!');
			echo $this->disp_params['block_end'];
			return;
		}

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = $this->disp_params[ 'limit' ];

		if( $this->disp_params['disp_teaser'] )
		{ // We want to show some of the post content, we need to load more info: use ItemList2
			$ItemList = new ItemList2( $listBlog, $timestamp_min, $timestamp_max, $limit, 'ItemCache', $this->code.'_' );
		}
		else
		{ // no excerpts, use ItemListLight
			load_class( 'items/model/_itemlistlight.class.php', 'ItemListLight' );
			$ItemList = new ItemListLight( $listBlog, $timestamp_min, $timestamp_max, $limit, 'ItemCacheLight', $this->code.'_' );
		}

		//$cat_array = sanitize_id_list($this->disp_params['cat_IDs'], true);

		// Filter list:
		$filters = array(
//				'cat_array' => $cat_array, // Restrict to selected categories
				'orderby' => $this->disp_params[ 'order_by' ],
				'order' => $this->disp_params[ 'order_dir' ],
				'unit' => 'posts', // We want to advertise all items (not just a page or a day)
			);

		if( isset( $this->disp_params['page'] ) )
		{
			$filters['page'] = $this->disp_params['page'];
		}

		if( $this->disp_params['item_type'] != '#' )
		{	// Not "default", restrict to a specific type (or '' for all)
			$filters['types'] = $this->disp_params['item_type'];
		}

		if( $this->disp_params['follow_mainlist'] == 'tags' )
		{	// Restrict to Item tagged with some tag used in the Mainlist:

			if( ! isset($MainList) )
			{	// Nothing to follow, don't display anything
				return false;
			}

			$all_tags = $MainList->get_all_tags();
			if( empty($all_tags) )
			{	// Nothing to follow, don't display anything
				return false;
			}

			$filters['tags'] = implode(',',$all_tags);
			
			if( !empty($Item) )
			{	// Exclude current Item
				$filters['post_ID'] = '-'.$Item->ID;
			}

			// fp> TODO: in addition to just filtering, offer ordering in a way where the posts with the most matching tags come first
		}

		$chapter_mode = false;
		if( $this->disp_params['item_group_by'] == 'chapter' )
		{	// Group by chapter:
			$chapter_mode = true;

			# This is the list of categories to restrict the linkblog to (cats will be displayed recursively)
			# Example: $linkblog_cat = '4,6,7';
			$linkblog_cat = '';

			# This is the array if categories to restrict the linkblog to (non recursive)
			# Example: $linkblog_catsel = array( 4, 6, 7 );
			$linkblog_catsel = array(); // $cat_array;

			// Compile cat array stuff:
			$linkblog_cat_array = array();
			$linkblog_cat_modifier = '';

			compile_cat_array( $linkblog_cat, $linkblog_catsel, /* by ref */ $linkblog_cat_array, /* by ref */  $linkblog_cat_modifier, $listBlog->ID );

			$filters['cat_array'] = $linkblog_cat_array;
			$filters['cat_modifier'] = $linkblog_cat_modifier;
			$filters['orderby'] = 'main_cat_ID '.$filters['orderby'];
		}

		$ItemList->set_filters( $filters, false ); // we don't want to memorize these params

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

		if( $chapter_mode )
		{	// List grouped by chapter/category:
			/**
			 * @var ItemLight (or Item)
			 */
			while( $Item = & $ItemList->get_category_group() )
			{
				// Open new cat:
				$Chapter = & $Item->get_main_Chapter();

				echo $this->disp_params['item_start'];
				echo '<a href="'.$Chapter->get_permanent_url().'">'.$Chapter->get('name').'</a>';
				echo $this->disp_params['group_start'];

				while( $Item = & $ItemList->get_item() )
				{	// Display contents of the Item depending on widget params:
					$this->disp_contents( $Item );
				}

				// Close cat
				echo $this->disp_params['group_end'];
				echo $this->disp_params['item_end'];
			}
		}
		else
		{	// Plain list:
			/**
			 * @var ItemLight (or Item)
			 */
			while( $Item = & $ItemList->get_item() )
			{ // Display contents of the Item depending on widget params:
				$this->disp_contents( $Item );
			}
		}

		if( isset( $this->disp_params['page'] ) )
		{
			$ItemList->page_links();
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_end'];
	}


	/**
	 * Support function for above
	 *
	 * @param Item
	 */
	function disp_contents( & $Item )
	{
		echo $this->disp_params['item_start'];

		$Item->title( array(
				'link_type' => $this->disp_params['item_title_link_type'],
			) );

		if( $this->disp_params[ 'disp_excerpt' ] )
		{
			$excerpt = $Item->dget( 'excerpt', 'htmlbody' );
			if( !empty($excerpt) )
			{	// Note: Excerpts are plain text -- no html (at least for now)
				echo '<div class="item_excerpt">'.$excerpt.'</div>';
			}
		}

		if( $this->disp_params['disp_teaser'] )
		{ // we want to show some or all of the post content
			$content = $Item->get_content_teaser( 1, false, 'htmlbody' );

			if( $words = $this->disp_params['disp_teaser_maxwords'] )
			{ // limit number of words
				$content = strmaxwords( $content, $words, array(
						'continued_link' => $Item->get_permanent_url(),
						'continued_text' => '&hellip;',
					 ) );
			}
			echo '<div class="item_content">'.$content.'</div>';

			/* fp> does that really make sense?
				we're no longer in a linkblog/linkroll use case here, are we?
			$Item->more_link( array(
					'before'    => '',
					'after'     => '',
					'link_text' => T_('more').' &raquo;',
				) );
				*/
		}

		echo $this->disp_params['item_end'];
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Blog;

		return array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID,			// Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty($this->disp_params['blog_ID']) ? $Blog->ID : $this->disp_params['blog_ID'], 	// Has the content of the displayed blog changed ?
			);
	}
}


/*
 * $Log$
 * Revision 1.32  2011/07/12 23:15:34  sam2kb
 * Sanitize input ID list
 *
 * Revision 1.31  2011/05/31 14:20:27  efy-asimo
 * paged nav on ?disp=postidx
 *
 * Revision 1.30  2010/06/07 19:00:17  sam2kb
 * Exclude current Item from related posts list
 *
 * Revision 1.29  2010/02/08 17:54:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.28  2010/01/30 18:55:36  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.27  2010/01/27 15:20:08  efy-asimo
 * Change select list to radio button
 *
 * Revision 1.26  2010/01/26 15:49:35  efy-asimo
 * Widget param type radio
 *
 * Revision 1.25  2009/12/22 23:13:39  fplanque
 * Skins v4, step 1:
 * Added new disp modes
 * Hooks for plugin disp modes
 * Enhanced menu widgets (BIG TIME! :)
 *
 * Revision 1.24  2009/12/22 03:30:24  blueyed
 * cleanup
 *
 * Revision 1.23  2009/12/20 23:15:00  fplanque
 * rollback broken stuff
 *
 * Revision 1.22  2009/12/13 02:41:11  sam2kb
 * Link to categories in chapter mode
 *
 * Revision 1.21  2009/12/13 00:05:37  sam2kb
 * Restrict to categories fix
 *
 * Revision 1.20  2009/12/12 23:51:53  sam2kb
 * Restrict ItemList to selected categories
 *
 * Revision 1.19  2009/12/01 04:19:25  fplanque
 * even more invalidation dimensions
 *
 * Revision 1.18  2009/12/01 03:45:37  fplanque
 * multi dimensional invalidation
 *
 * Revision 1.17  2009/11/30 04:31:38  fplanque
 * BlockCache Proof Of Concept
 *
 * Revision 1.16  2009/09/25 07:33:31  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.15  2009/09/16 20:27:26  tblue246
 * Fix fatal error
 *
 * Revision 1.14  2009/09/14 13:54:13  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.13  2009/09/12 11:03:13  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.12  2009/09/05 18:34:48  fplanque
 * minor
 *
 * Revision 1.11  2009/09/04 13:30:26  tblue246
 * Doc
 *
 * Revision 1.10  2009/09/03 23:52:35  fplanque
 * minor
 *
 * Revision 1.9  2009/09/03 15:04:23  waltercruz
 * Fixing universal item list. array_merge won't work here
 *
 * Revision 1.8  2009/07/05 16:39:10  sam2kb
 * "Limit" to "Max items"
 *
 * Revision 1.7  2009/07/04 00:54:53  fplanque
 * bugfix: linkblog cannot make proper groups if posts are not ordered by cat
 *
 * Revision 1.6  2009/03/20 22:44:04  fplanque
 * Related Items -- Proof of Concept
 *
 * Revision 1.4  2009/03/15 23:09:09  blueyed
 * coll_item_list_widget: fix order as per todo
 *
 * Revision 1.3  2009/03/15 22:48:16  fplanque
 * refactoring... final step :)
 *
 * Revision 1.2  2009/03/15 21:40:23  fplanque
 * killer factoring
 *
 * Revision 1.1  2009/03/15 20:35:18  fplanque
 * Universal Item List proof of concept
 *
 */
?>
