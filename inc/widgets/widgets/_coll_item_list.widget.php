<?php
/**
 * This file implements the Universal Item List Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
	var $icon = 'list';

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
		global $admin_url;

		// Get available templates:
		$context = 'content_list_master';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );

		$template_options = array( NULL => T_('No template / use settings below').':' ) + $TemplateCache->get_code_option_array();

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

		$r = array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 60,
					'defaultvalue' => T_('Items'),
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $template_options,
					'defaultvalue' => NULL,
					'input_suffix' => ( check_user_perm( 'options', 'edit' ) ? '&nbsp;'
							.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context, NULL, NULL, NULL,
							array( 'onclick' => 'return b2template_list_highlight( this )', 'target' => '_blank' ),
							array( 'title' => T_('Manage templates').'...' ) ) : '' ),
					'class' => 'evo_template_select',
				),
				'highlight_current' => array(
					'label' => T_('Highlight current'),
					'note' => T_('Check this to highlight the currently displayed item.'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
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
				'flagged' => array(
					'label' => T_('Flagged'),
					'note' => T_('Do you want to restrict only to flagged contents?'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
				),
				'follow_mainlist' => array(
					'label' => T_('Follow Main List'),
					'note' => T_('Do you want to restrict to contents related to what is displayed in the main area?'),
					'type' => 'radio',
					'options' => array(
							array( 'no', T_('No') ),
							array( 'tags', T_('By any tag included in Main List (OR match)') ),
							array( 'tags_and', T_('By all tags included in Main List (AND match)') ),
							array( 'tags_order', T_('By priority to best match (OR match + ORDER BY highest number of matches)') ),
						),
					'defaultvalue' => 'no',
					'field_lines' => true,
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
					'note' => sprintf( T_('List category IDs separated by %s.'), '<code>,</code>' ),
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
			);

		// Display the 3 orderby fields with order direction
		for( $order_index = 0; $order_index <= 2 /* The number of orderby fields - 1 */; $order_index++ )
		{
			$field_suffix = ( $order_index == 0 ? '' : '_'.$order_index );
			$r = array_merge( $r, array(
				'orderby'.$field_suffix.'_begin_line' => array(
					'type' => 'begin_line',
					'label' => ( $order_index == 0 ? T_('Order by') : '' ),
				),
				'order_by'.$field_suffix.'' => array(
					'type' => 'select',
					'options' => get_available_sort_options( NULL, $order_index > 0 ),
					'defaultvalue' => ( $order_index == 0 ? 'datestart' : '' ),
				),
				'order_dir'.$field_suffix.'' => array(
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => array( 'ASC' => T_('Ascending'), 'DESC' => T_('Descending') ),
					'defaultvalue' => ( $order_index == 0 ? 'DESC' : 'ASC' ),
					'allow_none' => true,
				),
				'orderby'.$field_suffix.'_end_line' => array(
					'type' => 'end_line',
				),
			) );
		}

		$r = array_merge( $r, array(
				'limit' => array(
					'label' => T_( 'Max items' ),
					'note' => T_( 'Maximum number of items to display.' ),
					'size' => 4,
					'defaultvalue' => 10,
				),
				'disp_cat' => array(
					'label' => T_('Category'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'no',   T_('No display') ),
							array( 'main', T_('Display main category') ),
							array( 'all',  T_('Display all categories') ) ),
					'defaultvalue' => 'no',
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
							array( 'category', T_('Display main category picture' ) ),
							array( 'first', T_('Display first post picture') ),
							array( 'all', T_('Display all post pictures') ) ),
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
					'label' => T_( 'Max words' ),
					'type' => 'integer',
					'defaultvalue' => 20,
					'note' => T_( 'Max number of words for the teasers.' ),
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{
			if( $this->get_param( 'highlight_current', 1 ) )
			{	// Disable "allow blockcache" because this widget uses the selected items:
				$r['allow_blockcache']['defaultvalue'] = false;
				$r['allow_blockcache']['disabled'] = 'disabled';
			}
			// Additional note when random order is used:
			$r['allow_blockcache']['note'] .= ' <span class="red">'.T_('If you use random order and you cache, the random order will stay the same after the initial cache filling.').'</span>';
		}

		return $r;
	}


	/**
	 * Get JavaScript code which helps to edit widget form
	 *
	 * @return string
	 */
	function get_edit_form_javascript()
	{
		return get_post_orderby_js( $this->get_param_prefix().'order_by', $this->get_param_prefix().'order_dir' )
			// Disable option "Allow caching" when option "Highlight current" is used:
			.'jQuery( "#'.$this->get_param_prefix().'highlight_current" ).click( function()
				{
					jQuery( "#'.$this->get_param_prefix().'allow_blockcache" ).prop( "disabled", jQuery( this ).prop( "checked" ) );
				} );';
	}


	/**
	 * Get order field
	 *
	 * @param string What return: 'field' - Field/column to order, 'dir' - Order direction
	 * @return string
	 */
	function get_order( $return = 'field' )
	{
		$result = '';

		switch( $return )
		{
			case 'field':
				// Get field for ORDERBY sql clause:
				$result = $this->get_param( 'order_by' );
				if( $this->get_param( 'order_by_1' ) != '' )
				{	// Append second order field:
					$result .= ','.$this->get_param( 'order_by_1' );
					if( $this->get_param( 'order_by_2' ) != '' )
					{	// Append third order field:
						$result .= ','.$this->get_param( 'order_by_2' );
					}
				}
				break;

			case 'dir':
				// Get direction(ASC|DESC) for ORDERBY sql clause:
				$result = $this->get_param( 'order_dir' );
				if( $this->get_param( 'order_by_1' ) != '' && $this->get_param( 'order_dir_1' ) != '' )
				{	// Append second order direction
					$result .= ','.$this->get_param( 'order_dir_1' );
					if( $this->get_param( 'order_by_2' ) != '' && $this->get_param( 'order_dir_2' ) != '' )
					{	// Append third order direction:
						$result .= ','.$this->get_param( 'order_dir_2' );
					}
				}
				break;
		}

		return $result;
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
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		parent::init_display( $params );

		if( $this->disp_params['highlight_current'] )
		{	// Disable block caching for this widget when it highlights the selected items:
			$this->disp_params['allow_blockcache'] = 0;
		}
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
		global $BlogCache, $Collection, $Blog;
		global $Item, $Settings;

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		$listBlog = ( $blog_ID ? $BlogCache->get_by_ID( $blog_ID, false ) : $Blog );

		if( empty( $listBlog ) )
		{	// Display error when wrong collection is requested by this widget:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because the requested Collection #'.$this->disp_params['blog_ID'].' doesn\'t exist any more.' );
			return false;
		}

		// @var $placeholder_dimension is empty by default
		$placeholder_dimension = '';
		if( $this->disp_params['attached_pics'] != 'none' && // If "Display first image"
		    $this->disp_params['disp_first_image'] == 'special' && // If "Special image placement"
		    $this->disp_params['layout'] == 'list' ) // If "List layout"
		{	// Create placeholder dimension from selected thumb_size param
			global $thumbnail_sizes;
			if( isset( $thumbnail_sizes[ $this->disp_params['thumb_size'] ] ) )
			{	// Get thumbnail width & height from config:
				$thumb_size = $thumbnail_sizes[ $this->disp_params['thumb_size'] ];
				$placeholder_dimension = ' style="width:'.$thumb_size[1].'px;height:'.$thumb_size[2].'px"';
			}
		}

		// Define default template params that can be rewritten by skin
		$this->disp_params = array_merge( array(

				// In case of cross-posting, we EXPECT to navigate in same collection if possible:
				'target_blog'     => 'auto', 						// Stay in current collection if it is allowed for the Item
				// 'post_navigation' => 'same_category',			// Stay in the same category if Item is cross-posted -- IN this case, we are NOT in a category

				// The following are to auto-templates only:
				'item_first_image_before'      => '<div class="item_first_image">',
				'item_first_image_after'       => '</div>',
				'item_first_image_placeholder' => '<div class="item_first_image_placeholder"'.$placeholder_dimension.'><a href="$item_permaurl$"></a></div>',
				'item_categories_before'       => '<div class="item_categories">',
				'item_categories_after'        => '</div>',
				'item_categories_separator'    => ', ',
				'item_title_before'            => '<div class="item_title">',
				'item_title_after'             => '</div>',
				'item_title_single_before'     => '',
				'item_title_single_after'      => '',
				'item_excerpt_before'          => '<div class="item_excerpt">',
				'item_excerpt_after'           => '</div>',
				'item_content_before'          => '<div class="item_content">',
				'item_content_after'           => '</div>',
				'item_readmore_text'           => '&hellip;',
				'item_readmore_class'          => 'btn btn-default',
				'item_images_before'           => '<div class="item_images">',
				'item_images_after'            => '</div>',
			), $this->disp_params );

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = intval( $this->disp_params['limit'] );

		if( $this->disp_params['disp_teaser'] // We want to show some of the post content...
			|| !empty($this->disp_params['template']) ) // We have potentially an elaborate template to display...
		// TODO: allow "ItemLight templates"
		{	// ... to do that, we need to load more info: use ItemList2
			$ItemList = new ItemList2( $listBlog, $listBlog->get_timestamp_min(), $listBlog->get_timestamp_max(), $limit, 'ItemCache', $this->code.'_' );
		}
		else
		{	// no excerpts, use ItemListLight
			load_class( 'items/model/_itemlistlight.class.php', 'ItemListLight' );
			$ItemList = new ItemListLight( $listBlog, $listBlog->get_timestamp_min(), $listBlog->get_timestamp_max(), $limit, 'ItemCacheLight', $this->code.'_' );
		}

		// Set additional debug info prefix for SQL queries to know what widget executes it:
		$ItemList->query_title_prefix = get_class( $this );

		$cat_array = sanitize_id_list( $this->disp_params['cat_IDs'], true );

		// Filter list:
		$filters = array(
				'cat_array' => $cat_array, // Restrict to selected categories
				'orderby'   => $this->get_order( 'field' ),
				'order'     => $this->get_order( 'dir' ),
				'unit'      => 'posts', // We want to advertise all items (not just a page or a day)
				'coll_IDs'  => $this->disp_params['blog_ID'],
			);
		if( $this->disp_params['item_visibility'] == 'public' )
		{	// Get only the public items
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

		if( $this->disp_params['flagged'] == 1 )
		{	// Restrict to flagged Items:
			$filters['flagged'] = true;
		}


		if( strpos( $this->disp_params['follow_mainlist'], 'tags' ) === 0 )
		{	// Restrict to Item tagged with some or all tags used in the Mainlist:

			if( ! isset($MainList) )
			{	// Nothing to follow, don't display anything
				$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is no MainList object.' );
				return false;
			}

			$all_tags = $MainList->get_all_tags();
			if( empty($all_tags) )
			{	// Nothing to follow, don't display anything
				$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is nothing to display.' );
				return false;
			}

			$filters['tags'] = implode( ',', $all_tags );
			if( $this->disp_params['follow_mainlist'] == 'tags_and' )
			{	// Filter posts which have all tags:
				$filters['tags_operator'] = 'AND';
			}
			// else 'OR' operator by default

			if( $this->disp_params['follow_mainlist'] == 'tags_order' )
			{	// Order by highest number of matched tags:
				$filters['orderby'] = 'matched_tags_num'.( empty( $filters['orderby'] ) ? '' : ','.$filters['orderby'] );
				$filters['order'] = 'DESC'.( empty( $filters['order'] ) ? '' : ','.$filters['order'] );
			}

			if( !empty($Item) )
			{	// Exclude current Item
				$filters['post_ID'] = '-'.$Item->ID;
			}

			// fp> TODO: in addition to just filtering, offer ordering in a way where the posts with the most matching tags come first
		}

		if( $this->disp_params['item_group_by'] == 'chapter' )
		{	// Group by chapter:

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
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there are no results to display.' );
			return false;
		}

		// Check if the widget displays only single title
		$this->disp_params['disp_only_title'] = ! (
				( $this->disp_params['disp_cat'] != 'no' ) || // display categories
				( $this->disp_params['attached_pics'] != 'none' ) || // display first and other images
				( $this->disp_params['disp_excerpt'] ) || // display excerpt
				( $this->disp_params['disp_teaser'] ) // display teaser
			);

		// Get content of items list to display:
		ob_start();
		$items_list_result = $ItemList->display_list( $this->disp_params );
		$items_list_content = ob_get_clean();

		if( $items_list_result !== true )
		{	// Display error message:
			$this->display_error_message( $items_list_result );
			return false;
		}

		if( empty( $items_list_content ) )
		{	// Nothing to display:
			return true;
		}

		if( ! empty( $this->disp_params['template'] ) )
		{	// For template mode we cannot know what will be displayed so no extra classes:
			echo $this->disp_params['block_start'];
		}
		else
		{	// Get extra classes depending on widget settings:
			$block_css_class = $this->get_widget_extra_class();
			if( empty( $block_css_class ) )
			{	// No extra class, Display default wrapper:
				echo $this->disp_params['block_start'];
			}
			else
			{	// Append extra classes for widget block:
				echo update_html_tag_attribs( $this->disp_params['block_start'], array( 'class' => $block_css_class ) );
			}
		}

		// Display widget title:
		$this->disp_title( sprintf( $this->disp_params[ 'title_link' ] ? '<a href="'.$listBlog->gen_blogurl().'" rel="nofollow">%s</a>' : '%s', $this->disp_params[ 'title' ] ) );

		echo $this->disp_params['block_body_start'];

		// Display items list:
		echo $items_list_content;

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Get extra classes depending on widget settings
	 *
	 * @return string
	 */
	function get_widget_extra_class()
	{
		$block_css_class = ' widget_uil_autotemp'; // Force tagging "Universal Item List Auto Template" for simplifying backward compatibility CSS

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
		global $Collection, $Blog;

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty( $blog_ID ) ? $Blog->ID : $blog_ID, // Has the content of the displayed blog changed ?
				'template_code'=> $this->get_param( 'template' ), // Has the Template changed?
				'master_template' => true, // This widget cache must be invalidated on updating of any Template because it may has a Master Template.
			);
	}
}
?>
