<?php
/**
 * This file implements the Universal Item List Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * Universal Item List Widget Class
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
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_item_list' );
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
				''  => T_('All'),
			) + $ItemTypeCache->get_option_array();

		$item_type_usage_options =
			array(
				'' => T_('All'),
			) + $ItemTypeCache->get_usage_option_array();

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
				'layout' => array(
					'label' => T_('Layout'),
					'note' => T_('How to lay out the items'),
					'type' => 'select',
					'options' => array(
							'rwd'  => T_( 'RWD Blocks' ),
							'flow' => T_( 'Flowing Blocks' ),
							'list' => T_( 'List' ),
						),
					'defaultvalue' => 'list',
				),
				'rwd_block_class' => array(
					'label' => T_('RWD block class'),
					'note' => T_('Specify the responsive column classes you want to use.'),
					'size' => 60,
					'defaultvalue' => 'col-lg-4 col-md-6 col-sm-6 col-xs-12',
				),
				'item_visibility' => array(
					'label' => T_('Item visibility'),
					'note' => T_('What post statuses should be included in the list?'),
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
							array( 'public', T_('show public posts') ),
							array( 'all', T_('show all posts the current user is allowed to see') ) ),
					'defaultvalue' => 'all',
				),
				'item_type_usage' => array(
					'label' => T_('Post type usage'),
					'note' => T_('Restrict to a specific item type usage?'),
					'type' => 'select',
					'options' => $item_type_usage_options,
					'defaultvalue' => '',
				),
				'item_type' => array(
					'label' => T_('Exact post type'),
					'note' => T_('What type of items do you want to list?'),
					'type' => 'select',
					'options' => $item_type_options,
					'defaultvalue' => '',
				),
				'featured' => array(
					'label' => T_('Featured'),
					'note' => T_('Do you want to restrict to featured contents?'),
					'type' => 'radio',
					'options' => array(
							array ('all', T_('All posts') ),
							array ('featured', T_('Only featured') ),
							array ('other', T_('Only NOT featured') ),
						),
					'defaultvalue' => 'all',
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
					'label' => T_('Collections'),
					'note' => T_('List collection IDs separated by \',\', \'*\' for all collections, \'-\' for current collection without aggregation or leave empty for current collection including aggregation.'),
					'size' => 4,
					'type' => 'text',
					'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																		'error'   => T_('Invalid list of Collection IDs.') ),
					'defaultvalue' => '',
				),
				'cat_IDs' => array(
					'label' => T_('Categories'),
					'note' => T_('List category IDs separated by ,'),
					'size' => 15,
					'type' => 'text',
					'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																		'error'   => T_('Invalid list of Category IDs.') ),
				),
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
					'defaultvalue' => 10,
				),
				'disp_title' => array(
					'label' => T_( 'Titles' ),
					'note' => T_( 'Display title.' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
				'item_title_link_type' => array(
					'label' => /* TRANS: Where should titles be linked to? */ T_('Link titles to'),
					'note' => T_('Where should titles be linked to?'),
					'type' => 'select',
					'options' => array(
							'auto'        => T_('Automatic'),
							'permalink'   => T_('Item permalink'),
							'linkto_url'  => T_('Item URL'),
							'none'        => T_('Nowhere'),
						),
					'defaultvalue' => 'permalink',
				),
				'attached_pics' => array(
					'label' => T_('Attached pictures'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'none', T_('None') ),
							array( 'first', T_('Display first picture') ),
							array( 'all', T_('Display all pictures') ) ),
					'defaultvalue' => 'none',
				),
				'disp_first_image' => array(
					'label' => T_('First picture'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'special', T_('Special placement before title') ),
							array( 'normal', T_('No special treatment (same as other pictures)') ) ),
					'defaultvalue' => 'normal',
				),
				'max_pics' => array(
					'label' => T_('Max pictures'),
					'note' => T_('Maximum number of pictures to display after the title.'),
					'size' => 4,
					'type' => 'integer',
					'defaultvalue' => '',
					'allow_empty' => true,
				),
				'thumb_size' => array(
					'label' => T_('Image size'),
					'note' => T_('Cropping and sizing of thumbnails'),
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'crop-80x80',
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
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'universal-item-list-widget' );
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

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		$listBlog = ( $blog_ID ? $BlogCache->get_by_ID( $blog_ID, false ) : $Blog );

		if( empty( $listBlog ) )
		{
			echo $this->disp_params['block_start'];
			echo $this->disp_params['block_body_start'];
			echo T_('The requested Blog doesn\'t exist any more!');
			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];
			return;
		}

		// Define default template params that can be rewritten by skin
		$this->disp_params = array_merge( array(
				'item_first_image_before'      => '<div class="item_first_image">',
				'item_first_image_after'       => '</div>',
				'item_first_image_placeholder' => '<div class="item_first_image_placeholder"><a href="$item_permaurl$"></a></div>',
				'item_title_before'            => '<div class="item_title">',
				'item_title_after'             => '</div>',
				'item_title_single_before'     => '',
				'item_title_single_after'      => '',
				'item_excerpt_before'          => '<div class="item_excerpt">',
				'item_excerpt_after'           => '</div>',
				'item_content_before'          => '<div class="item_content">',
				'item_content_after'           => '</div>',
				'item_images_before'           => '<div class="item_images">',
				'item_images_after'            => '</div>',
			), $this->disp_params );

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = intval( $this->disp_params['limit'] );

		if( $this->disp_params['disp_teaser'] )
		{ // We want to show some of the post content, we need to load more info: use ItemList2
			$ItemList = new ItemList2( $listBlog, $listBlog->get_timestamp_min(), $listBlog->get_timestamp_max(), $limit, 'ItemCache', $this->code.'_' );
		}
		else
		{ // no excerpts, use ItemListLight
			load_class( 'items/model/_itemlistlight.class.php', 'ItemListLight' );
			$ItemList = new ItemListLight( $listBlog, $listBlog->get_timestamp_min(), $listBlog->get_timestamp_max(), $limit, 'ItemCacheLight', $this->code.'_' );
		}

		// Set additional debug info prefix for SQL queries to know what widget executes it:
		$ItemList->query_title_prefix = get_class( $this );

		$cat_array = sanitize_id_list( $this->disp_params['cat_IDs'], true );

		// Filter list:
		$filters = array(
				'cat_array' => $cat_array, // Restrict to selected categories
				'orderby'   => $this->disp_params['order_by'],
				'order'     => $this->disp_params['order_dir'],
				'unit'      => 'posts', // We want to advertise all items (not just a page or a day)
				'coll_IDs'  => $this->disp_params['blog_ID'],
			);
		if( $this->disp_params['item_visibility'] == 'public' )
		{ // Get only the public items
			$filters['visibility_array'] = array( 'published' );
		}

		if( isset( $this->disp_params['page'] ) )
		{
			$filters['page'] = $this->disp_params['page'];
		}

		if( $this->disp_params['item_type'] != '' &&
		    $this->disp_params['item_type'] != '#' /* deprecated value, it was used as default value of ItemList filter */ )
		{	// Not "default", restrict to a specific type (or '' for all)
			$filters['types'] = $this->disp_params['item_type'];
		}

		if( isset( $this->disp_params['item_type_usage'] ) )
		{	// Not "default", restrict to a specific type usage (or '' for all):
			$filters['itemtype_usage'] = $this->disp_params['item_type_usage'];
		}

		if( $this->disp_params['featured'] == 'featured' )
		{	// Restrict to featured Items:
			$filters['featured'] = true;
		}
		elseif( $this->disp_params['featured'] == 'other' )
		{	// Restrict to NOT featured Items:
			$filters['featured'] = false;
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

			$filters['tags'] = implode( ',', $all_tags );

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
		}

		$ItemList->set_filters( $filters, false ); // we don't want to memorize these params

		// Run the query:
		$ItemList->query();

		if( ! $ItemList->result_num_rows )
		{	// Nothing to display:
			return;
		}

		// Check if the widget displays only single title
		$this->disp_params['disp_only_title'] = ! (
				( $this->disp_params['attached_pics'] != 'none' ) || // display first and other images
				( $this->disp_params['disp_excerpt'] ) || // display excerpt
				( $this->disp_params['disp_teaser'] ) // display teaser
			);

		// Start to capture display content here in order to solve the issue to don't display empty widget
		ob_start();
		// This variable used to display widget. Will be set to true when content is displayed
		$content_is_displayed = false;

		// Get extra classes depending on widget settings:
		$block_css_class = $this->get_widget_extra_class();

		if( empty( $block_css_class ) )
		{	// No extra class, Display default wrapper:
			echo $this->disp_params['block_start'];
		}
		else
		{	// Append extra classes for widget block:
			echo preg_replace( '/ class="([^"]+)"/', ' class="$1'.$block_css_class.'"', $this->disp_params['block_start'] );
		}

		$title = sprintf( ( $this->disp_params[ 'title_link' ] ? '<a href="'.$listBlog->gen_blogurl().'" rel="nofollow">%s</a>' : '%s' ), $this->disp_params[ 'title' ] );
		$this->disp_title( $title );

		echo $this->disp_params['block_body_start'];

		if( $chapter_mode )
		{	// List grouped by chapter/category:
			$items_map_by_chapter = array();
			$chapters_of_loaded_items = array();
			$group_by_blogs = false;
			$prev_chapter_blog_ID = NULL;

			while( $iterator_Item = & $ItemList->get_item() )
			{ // Display contents of the Item depending on widget params:
				$Chapter = & $iterator_Item->get_main_Chapter();
				if( ! isset( $items_map_by_chapter[$Chapter->ID] ) )
				{
					$items_map_by_chapter[$Chapter->ID] = array();
					$chapters_of_loaded_items[] = $Chapter;
				}
				$items_map_by_chapter[$Chapter->ID][] = $iterator_Item;
				// Group by blogs if there are chapters from multiple blogs
				if( ! $group_by_blogs && ( $Chapter->blog_ID != $prev_chapter_blog_ID ) )
				{ // group by blogs is not decided yet
					$group_by_blogs = ( $prev_chapter_blog_ID != NULL );
					$prev_chapter_blog_ID = $Chapter->blog_ID;
				}
			}

			usort( $chapters_of_loaded_items, 'Chapter::compare_chapters' );
			$displayed_blog_ID = NULL;

			if( $group_by_blogs && isset( $this->disp_params['collist_start'] ) )
			{ // Start list of blogs
				echo $this->disp_params['collist_start'];
			}
			else
			{ // Display list start, all chapters are in the same group ( not grouped by blogs )
				echo $this->get_layout_start();
			}

			$item_index = 0;
			foreach( $chapters_of_loaded_items as $Chapter )
			{
				if( $group_by_blogs && $displayed_blog_ID != $Chapter->blog_ID )
				{
					$Chapter->get_Blog();
					if( $displayed_blog_ID != NULL )
					{ // Display the end of the previous blog's chapter list
						echo $this->get_layout_end( $item_index );
					}
					echo $this->disp_params['coll_start'].$Chapter->Blog->get('shortname'). $this->disp_params['coll_end'];
					// Display start of blog's chapter list
					echo $this->get_layout_start();
					$displayed_blog_ID = $Chapter->blog_ID;
				}
				$content_is_displayed = $this->disp_chapter( $Chapter, $items_map_by_chapter, $item_index ) || $content_is_displayed;
			}

			if( $content_is_displayed )
			{ // End of a chapter list - if some content was displayed this is always required
				echo $this->get_layout_end( $item_index );
			}

			if( $group_by_blogs && isset( $this->disp_params['collist_end'] ) )
			{ // End of blog list
				echo $this->disp_params['collist_end'];
			}
		}
		else
		{ // Plain list:
			echo $this->get_layout_start();

			$item_index = 0;
			/**
			 * @var ItemLight (or Item)
			 */
			while( $Item = & $ItemList->get_item() )
			{ // Display contents of the Item depending on widget params:
				$content_is_displayed = $this->disp_contents( $Item, false, $item_index ) || $content_is_displayed;
			}

			if( isset( $this->disp_params['page'] ) )
			{
				if( empty( $this->disp_params['pagination'] ) )
				{
					$this->disp_params['pagination'] = array();
				}
				$ItemList->page_links( $this->disp_params['pagination'] );
			}

			echo $this->get_layout_end( $item_index );
		}

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
	 * Display a chapter with all of its loaded items
	 *
	 * @param Chapter
	 * @param array Items map by Chapter
	 * @param integer Item index
	 * @return boolean true if content was displayed, false otherwise
	 */
	function disp_chapter( $Chapter, & $items_map_by_chapter, & $item_index )
	{
		$content_is_displayed = false;

		if( isset( $items_map_by_chapter[$Chapter->ID] ) && ( count( $items_map_by_chapter[$Chapter->ID] ) > 0 ) )
		{ // Display Chapter only if it has some items
			echo $this->get_layout_item_start();
			$Chapter->get_Blog();
			echo '<a href="'.$Chapter->get_permanent_url().'">'.$Chapter->get('name').'</a>';
			echo $this->get_layout_item_end();
			echo $this->disp_params['group_start'];

			$item_index = 0;
			foreach( $items_map_by_chapter[$Chapter->ID] as $iterator_Item )
			{ // Display contents of the Item depending on widget params:
				$content_is_displayed = $this->disp_contents( $iterator_Item, true, $item_index ) || $content_is_displayed;
			}

			// Close cat group
			echo $this->disp_params['group_end'];
		}

		return $content_is_displayed;
	}


	/**
	 * Support function for above
	 *
	 * @param Item
	 * @param boolean set to true if Items are displayed grouped by chapters, false otherwise
	 * @param integer Item index
	 * @return boolean TRUE - if content is displayed
	 */
	function disp_contents( & $disp_Item, $chapter_mode = false, & $item_index )
	{
		global $disp, $Item;

		// Set this var to TRUE when some content(title, excerpt or picture) is displayed
		$content_is_displayed = false;

		// Set a 'group_' prefix for param keys if the items are grouped by chapters
		$disp_param_prefix = $chapter_mode ? 'group_' : '';

		// Is this the current item?
		if( !empty($Item) && $disp_Item->ID == $Item->ID )
		{	// The current page is currently displaying the Item this link is pointing to
			// Let's display it as selected
			$link_class = $this->disp_params['link_selected_class'];
		}
		else
		{	// Default link class
			$link_class = $this->disp_params['link_default_class'];
		}

		$item_is_selected = ( $link_class == $this->disp_params['link_selected_class'] );

		echo $this->get_layout_item_start( $item_index, $item_is_selected, $disp_param_prefix );

		if( $this->disp_params['disp_first_image'] == 'special' )
		{	// If we should display first picture before title then get "Cover" images and order them at top:
			$cover_image_params = array(
					'restrict_to_image_position' => 'cover,teaser,teaserperm,teaserlink,aftermore,inline',
					// Sort the attachments to get firstly "Cover", then "Teaser", and "After more" as last order
					'links_sql_select'  => ', CASE '
							.'WHEN link_position = "cover"      THEN "1" '
							.'WHEN link_position = "teaser"     THEN "2" '
							.'WHEN link_position = "teaserperm" THEN "3" '
							.'WHEN link_position = "teaserlink" THEN "4" '
							.'WHEN link_position = "aftermore"  THEN "5" '
							.'WHEN link_position = "inline"     THEN "6" '
							// .'ELSE "99999999"' // Use this line only if you want to put the other position types at the end
						.'END AS position_order',
					'links_sql_orderby' => 'position_order, link_order',
				);
		}
		else
		{
			$cover_image_params = array();
		}

		if( $this->disp_params['attached_pics'] != 'none' && $this->disp_params['disp_first_image'] == 'special' )
		{ // We want to display first image separately before the title
			// Display before/after even if there is no image so we can use it as a placeholder.
			$this->disp_images( array_merge( array(
					'before'      => $this->disp_params['item_first_image_before'],
					'after'       => $this->disp_params['item_first_image_after'],
					'placeholder' => $this->disp_params['item_first_image_placeholder'],
					'Item'        => $disp_Item,
					'start'       => 1,
					'limit'       => 1,
				), $cover_image_params ),
				$content_is_displayed );
		}

		if( $this->disp_params['disp_title'] )
		{ // Display title
			$disp_Item->title( array(
					'before'     => $this->disp_params['disp_only_title'] ? $this->disp_params['item_title_single_before'] : $this->disp_params['item_title_before'],
					'after'      => $this->disp_params['disp_only_title'] ? $this->disp_params['item_title_single_after'] : $this->disp_params['item_title_after'],
					'link_type'  => $this->disp_params['item_title_link_type'],
					'link_class' => $link_class,
				) );
			$content_is_displayed = true;
		}

		if( $this->disp_params['disp_excerpt'] )
		{ // Display excerpt
			$excerpt = $disp_Item->get_excerpt();

			if( ! $this->disp_params['disp_teaser'] )
			{ // only display if there is no teaser to display
				$excerpt .= ' <a href="'.$disp_Item->get_permanent_url().'">&hellip;</a>';
			}

			if( !empty($excerpt) )
			{	// Note: Excerpts are plain text -- no html (at least for now)
				echo $this->disp_params['item_excerpt_before'].$excerpt.$this->disp_params['item_excerpt_after'];
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
			echo $this->disp_params['item_content_before'].$content.$this->disp_params['item_content_after'];
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

		if( $this->disp_params['attached_pics'] == 'all' ||
		   ( $this->disp_params['attached_pics'] == 'first' && $this->disp_params['disp_first_image'] == 'normal' ) )
		{ // Display attached pictures
			if( $this->disp_params['attached_pics'] == 'first' )
			{	// Display only one first image:
				$picture_limit = 1;
			}
			else
			{
				$max_pics = intval( $this->disp_params['max_pics'] );
				if( $max_pics > 0 )
				{	// Limit images after title with widget param:
					$picture_limit = $max_pics;
					if( $this->disp_params['disp_first_image'] == 'special' )
					{	// If first image is already displayed before title, then we should skip this first to get next images:
						$picture_limit += 1;
					}
				}
				else
				{	// Don't limit the images:
					$picture_limit = 1000;
				}
			}
			$this->disp_images( array_merge( array(
					'before' => $this->disp_params['item_images_before'],
					'after'  => $this->disp_params['item_images_after'],
					'Item'   => $disp_Item,
					'start'  => ( $this->disp_params['disp_first_image'] == 'special' ? 2 : 1 ), // Skip first image if it is displayed on top
					'limit'  => $picture_limit,
				), $cover_image_params ),
				$content_is_displayed );
		}

		++$item_index;

		echo $this->get_layout_item_end( $item_index, $item_is_selected, $disp_param_prefix );

		return $content_is_displayed;
	}


	/**
	 * Display images of the selected item
	 *
	 * @param array Params
	 * @param boolean Changed by reference when content is displayed
	 */
	function disp_images( $params = array(), & $content_is_displayed )
	{
		$params = array_merge( array(
				'before'                     => '',
				'after'                      => '',
				'placeholder'                => '',
				'Item'                       => NULL,
				'start'                      => 1,
				'limit'                      => 1,
				'restrict_to_image_position' => 'teaser,teaserperm,teaserlink,aftermore,inline',
				'links_sql_select'           => '',
				'links_sql_orderby'          => 'link_order',
			), $params );

		$links_params = array(
				'sql_select_add' => $params['links_sql_select'],
				'sql_order_by'   => $params['links_sql_orderby']
			);

		$disp_Item = & $params['Item'];

		// Get list of ALL attached files:
		$LinkOwner = new LinkItem( $disp_Item );

		$images = '';

		if( $LinkList = $LinkOwner->get_attachment_LinkList( $params['limit'], $params['restrict_to_image_position'], 'image', $links_params ) )
		{	// Get list of attached files
			$image_num = 1;
			while( $Link = & $LinkList->get_next() )
			{
				if( ( $File = & $Link->get_File() ) && $File->is_image() )
				{	// Get only images
					if( $image_num < $params['start'] )
					{ // Skip these first images
						$image_num++;
						continue;
					}
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

					// Print attached picture
					$images .= $File->get_tag( '', '', '', '', $this->disp_params['thumb_size'], $pic_url );

					$content_is_displayed = true;

					$image_num++;
				}
			}
		}

		if( ! empty( $images ) )
		{ // Print out images only when at least one exists
			echo $params['before'];
			echo $images;
			echo $params['after'];
		}
		else
		{	// Display placeholder if no images:
			// Replace mask $item_permaurl$ with the item permanent URL:
			echo str_replace( '$item_permaurl$', $disp_Item->get_permanent_url(), $params['placeholder'] );
		}

	}


	/**
	 * Get extra classes depending on widget settings
	 *
	 * @return string
	 */
	function get_widget_extra_class()
	{
		$block_css_class = '';

		if( ! $this->disp_params['disp_title'] && in_array( $this->disp_params[ 'attached_pics' ], array( 'first', 'all' ) ) )
		{	// Don't display bullets when we show only the pictures:
			$block_css_class .= ' nobullets';
		}

		if( $this->disp_params['item_group_by'] == 'chapter' )
		{	// If items are grouped by category/chapter:
			$block_css_class .= ' evo_withgroup';
		}
		else
		{	// No grouping:
			$block_css_class .= ' evo_nogroup';
		}

		if( $this->disp_params['disp_title'] )
		{	// If item title is displayed:
			$block_css_class .= ' evo_withtitle';
		}
		else
		{	// Item title is hidden:
			$block_css_class .= ' evo_notitle';
		}

		if( $this->disp_params['attached_pics'] == 'first' )
		{	// If only first picture is displayed:
			$block_css_class .= ' evo_1pic';
		}
		elseif( $this->disp_params['attached_pics'] == 'all' )
		{	// If all item pictures are displayed:
			$block_css_class .= ' evo_pics';
		}
		else
		{	// Item pictures are hidden:
			$block_css_class .= ' evo_nopic';
		}

		if( $this->disp_params['attached_pics'] != 'none' )
		{	// If at least one picture should be displayed:
			if( $this->disp_params['disp_first_image'] == 'special' )
			{	// Special placement for first image:
				$block_css_class .= ' evo_1pic__special';
			}
			else
			{	// Normal placement for first image:
				$block_css_class .= ' evo_1pic__normal';
			}

			// Add class for each image size:
			$block_css_class .= ' evo_imgsize_'.$this->disp_params['thumb_size'];
		}

		if( $this->disp_params['disp_excerpt'] )
		{	// If item excerpt is displayed:
			$block_css_class .= ' evo_withexcerpt';
		}
		else
		{	// Item excerpt is hidden:
			$block_css_class .= ' evo_noexcerpt';
		}

		if( $this->disp_params['disp_teaser'] )
		{	// If item teaser is displayed:
			$block_css_class .= ' evo_withteaser';
		}
		else
		{	// Item teaser is hidden:
			$block_css_class .= ' evo_noteaser';
		}

		return $block_css_class;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Blog;

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty( $blog_ID ) ? $Blog->ID : $blog_ID, // Has the content of the displayed blog changed ?
			);
	}
}
?>