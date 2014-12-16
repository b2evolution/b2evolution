<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _coll_item_list.widget.php 7815 2014-12-15 13:03:31Z yura $
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
		load_funcs( 'files/model/_image.funcs.php' );

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
				'thumb_size' => array(
					'label' => T_('Thumbnail size'),
					'note' => T_('Cropping and sizing of thumbnails'),
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'crop-80x80',
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
				'disp_title' => array(
					'label' => T_( 'Titles' ),
					'note' => T_( 'Display title.' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
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
				'attached_pics' => array(
					'label' => T_('Attached pictures'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'none', T_('None') ),
							array( 'first', T_('Display first') ),
							array( 'all', T_('Display all') ) ),
					'defaultvalue' => 'none',
				),
				'item_pic_link_type' => array(
					'label' => T_('Link pictures'),
					'note' => T_('Where should pictures be linked to?'),
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
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Disable "allow blockcache" because this widget uses the selected items
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

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
		global $Item, $Settings;

		$this->init_display( $params );

		$listBlog = ( $this->disp_params[ 'blog_ID' ] ? $BlogCache->get_by_ID( $this->disp_params[ 'blog_ID' ], false ) : $Blog );

		if( empty($listBlog) )
		{
			echo $this->disp_params['block_start'];
			echo $this->disp_params['block_body_start'];
			echo T_('The requested Blog doesn\'t exist any more!');
			echo $this->disp_params['block_end_start'];
			echo $this->disp_params['block_end'];
			return;
		}

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = $this->disp_params[ 'limit' ];

		if( $this->disp_params['disp_teaser'] )
		{ // We want to show some of the post content, we need to load more info: use ItemList2
			$ItemList = new ItemList2( $listBlog, $listBlog->get_timestamp_min(), $listBlog->get_timestamp_max(), $limit, 'ItemCache', $this->code.'_' );
		}
		else
		{ // no excerpts, use ItemListLight
			load_class( 'items/model/_itemlistlight.class.php', 'ItemListLight' );
			$ItemList = new ItemListLight( $listBlog, $listBlog->get_timestamp_min(), $listBlog->get_timestamp_max(), $limit, 'ItemCacheLight', $this->code.'_' );
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

			$filters['cat_focus'] = 'main';
			$filters['cat_array'] = $linkblog_cat_array;
			$filters['cat_modifier'] = $linkblog_cat_modifier;
			if( $Settings->get( 'chapter_ordering' ) == 'alpha' )
			{ // Sort categories by name
				$filters['orderby'] = 'T_categories.cat_name '.$filters['orderby'];
			}
			else
			{ // Sort categories by order field (But sort also by cat name in order to don't break list when all categories has NULL order)
				$filters['orderby'] = 'T_categories.cat_order T_categories.cat_name '.$filters['orderby'];
			}
		}

		$ItemList->set_filters( $filters, false ); // we don't want to memorize these params

		// Run the query:
		$ItemList->query();

		if( ! $ItemList->result_num_rows )
		{	// Nothing to display:
			return;
		}

		// Start to capture display content here in order to solve the issue to don't display empty widget
		ob_start();
		// This variable used to display widget. Will be set to true when content is displayed
		$content_is_displayed = false;

		if( !$this->disp_params['disp_title'] && in_array( $this->disp_params[ 'attached_pics' ], array( 'first', 'all' ) ) )
		{ // Don't display bullets when we show only the pictures
			$block_css_class = 'nobullets';
		}

		if( empty( $block_css_class ) )
		{
			echo $this->disp_params['block_start'];
		}
		else
		{ // Additional class for widget block
			echo preg_replace( '/ class="([^"]+)"/', ' class="$1 '.$block_css_class.'"', $this->disp_params['block_start'] );
		}

		$title = sprintf( ( $this->disp_params[ 'title_link' ] ? '<a href="'.$listBlog->gen_blogurl().'" rel="nofollow">%s</a>' : '%s' ), $this->disp_params[ 'title' ] );
		$this->disp_title( $title );

		echo $this->disp_params['block_body_start'];

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
					$content_is_displayed = $this->disp_contents( $Item ) || $content_is_displayed;
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
				$content_is_displayed = $this->disp_contents( $Item ) || $content_is_displayed;
			}
		}

		if( isset( $this->disp_params['page'] ) )
		{
			if( empty( $this->disp_params['pagination'] ) )
			{
				$this->disp_params['pagination'] = array();
			}
			$ItemList->page_links( $this->disp_params['pagination'] );
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		if( $content_is_displayed )
		{ // Some content is displayed, Print out widget
			ob_end_flush();
		}
		else
		{ // No content, Don't display widget
			ob_end_clean();
		}
	}


	/**
	 * Support function for above
	 *
	 * @param Item
	 * @return boolean TRUE - if content is displayed
	 */
	function disp_contents( & $disp_Item )
	{
		// Check if only the title was displayed before the first picture
		$displayed_only_title = false;

		// Set this var to TRUE when some content(title, excerpt or picture) is displayed
		$content_is_displayed = false;

		// Is this the current item?
		global $disp, $Item;
		if( !empty($Item) && $disp_Item->ID == $Item->ID )
		{	// The current page is currently displaying the Item this link is pointing to
			// Let's display it as selected
			$link_class = $this->disp_params['link_selected_class'];
		}
		else
		{	// Default link class
			$link_class = $this->disp_params['link_default_class'];
		}

		if( $link_class == $this->disp_params['link_selected_class'] )
		{
			echo $this->disp_params['item_selected_start'];
		}
		else
		{
			echo $this->disp_params['item_start'];
		}

		if( $this->disp_params[ 'disp_title' ] )
		{	// Display title
			$disp_Item->title( array(
					'link_type'  => $this->disp_params['item_title_link_type'],
					'link_class' => $link_class,
				) );
			$displayed_only_title = true;
			$content_is_displayed = true;
		}

		if( $this->disp_params[ 'disp_excerpt' ] )
		{
			$excerpt = $disp_Item->dget( 'excerpt', 'htmlbody' );
			if( !empty($excerpt) )
			{	// Note: Excerpts are plain text -- no html (at least for now)
				echo '<div class="item_excerpt">'.$excerpt.'</div>';
				$displayed_only_title = false;
				$content_is_displayed = true;
			}
		}

		if( $this->disp_params['disp_teaser'] )
		{ // we want to show some or all of the post content
			$content = $disp_Item->get_content_teaser( 1, false, 'htmlbody' );

			if( $words = $this->disp_params['disp_teaser_maxwords'] )
			{ // limit number of words
				$content = strmaxwords( $content, $words, array(
						'continued_link' => $disp_Item->get_permanent_url(),
						'continued_text' => '&hellip;',
					 ) );
			}
			echo '<div class="item_content">'.$content.'</div>';
			$displayed_only_title = false;
			$content_is_displayed = true;

			/* fp> does that really make sense?
				we're no longer in a linkblog/linkroll use case here, are we?
			$disp_Item->more_link( array(
					'before'    => '',
					'after'     => '',
					'link_text' => T_('more').' &raquo;',
				) );
				*/
		}

		if( in_array( $this->disp_params[ 'attached_pics' ], array( 'first', 'all' ) ) )
		{	// Display attached pictures
			$picture_limit = $this->disp_params[ 'attached_pics' ] == 'first' ? 1 : 1000;
			$LinkOnwer = new LinkItem( $disp_Item );
			if( $FileList = $LinkOnwer->get_attachment_FileList( $picture_limit ) )
			{	// Get list of attached files
				while( $File = & $FileList->get_next() )
				{
					if( $File->is_image() )
					{	// Get only images
						switch( $this->disp_params[ 'item_pic_link_type' ] )
						{	// Set url for picture link
							case 'none':
								$pic_url = NULL;
								break;

							case 'permalink':
								$pic_url = $disp_Item->get_permanent_url();
								break;

							case 'linkto_url':
								$pic_url = $disp_Item->url;
								break;

							case 'auto':
							default:
								$pic_url = ( empty( $disp_Item->url ) ? $disp_Item->get_permanent_url() : $disp_Item->url );
								break;
						}

						if( $displayed_only_title )
						{ // If only the title was displayed - Insert new line before the first picture
							echo '<br />';
							$displayed_only_title = false;
						}

						// Print attached picture
						echo $File->get_tag( '', '', '', '', $this->disp_params['thumb_size'], $pic_url );

						$content_is_displayed = true;
					}
				}
			}
		}

		if( $link_class == $this->disp_params['link_selected_class'] )
		{
			echo $this->disp_params['item_selected_end'];
		}
		else
		{
			echo $this->disp_params['item_end'];
		}

		return $content_is_displayed;
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
?>