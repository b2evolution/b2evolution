<?php
/**
 * This file implements the Media Index Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @author Yabba	- {@link http://www.astonishme.co.uk/}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );
load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_media_index_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_media_index_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_media_index' );
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
			) + $ItemTypeCache->get_option_array() ;

		$r = array_merge( array(
			'title' => array(
				'label' => T_('Block title'),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_('Recent photos'),
			),
			'item_type' => array(
				'label' => T_('Item type'),
				'note' => T_('What kind of items do you want to list?'),
				'type' => 'select',
				'options' => $item_type_options,
				'defaultvalue' => '1',
			),
			'thumb_size' => array(
				'label' => T_('Thumbnail size'),
				'note' => T_('Cropping and sizing of thumbnails'),
				'type' => 'select',
				'options' => get_available_thumb_sizes(),
				'defaultvalue' => 'crop-80x80',
			),
			'thumb_layout' => array(
				'label' => T_('Layout'),
				'note' => T_('How to lay out the thumbnails'),
				'type' => 'select',
				'options' => array( 'grid' => T_( 'Grid' ), 'list' => T_( 'List' ) ),
				'defaultvalue' => 'grid',
			),
			'disp_image_title' => array(
				'label' => T_( 'Display image title' ),
				'note' => T_( 'Check this to display image title. This falls back to post title if image title is not set.' ),
				'type' => 'checkbox',
				'defaultvalue' => false,
			),
			'grid_nb_cols' => array(
				'label' => T_( 'Columns' ),
				'note' => T_( 'Number of columns in grid mode.' ),
				'size' => 4,
				'defaultvalue' => 2,
			),
			'limit' => array(
				'label' => T_( 'Max items' ),
				'note' => T_( 'Maximum number of items to display.' ),
				'size' => 4,
				'defaultvalue' => 3,
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
			'blog_ID' => array(
				'label' => T_( 'Blogs' ),
				'note' => T_( 'IDs of the blogs to use, leave empty for the current blog. Separate multiple blogs by commas.' ),
				'size' => 4,
			),
		), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Photo index');
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
		return T_('Index of photos; click goes to original image post.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $localtimenow;

		$this->init_display( $params );

		if( $this->disp_params[ 'order_by' ] == 'RAND' && isset($this->BlockCache) )
		{	// Do NOT cache if display order is random
			$this->BlockCache->abort_collect();
		}

		global $Blog;
		$list_blogs = ( $this->disp_params[ 'blog_ID' ] ? $this->disp_params[ 'blog_ID' ] : $Blog->ID );
		//pre_dump( $list_blogs );

		// Display photos:
		// TODO: permissions, complete statuses...
		// TODO: A FileList object based on ItemListLight but adding File data into the query?
		//          overriding ItemListLigth::query() for starters ;)


		$FileCache = & get_FileCache();

		$FileList = new DataObjectList2( $FileCache );

		// Query list of files:
		$SQL = new SQL();
		$SQL->SELECT( 'post_ID, post_datestart, post_datemodified, post_main_cat_ID, post_urltitle, post_canonical_slug_ID,
									post_tiny_slug_ID, post_ptyp_ID, post_title, post_excerpt, post_url, file_ID,
									file_title, file_root_type, file_root_ID, file_path, file_alt, file_desc' );
		$SQL->FROM( 'T_categories INNER JOIN T_postcats ON cat_ID = postcat_cat_ID
									INNER JOIN T_items__item ON postcat_post_ID = post_ID
									INNER JOIN T_links ON post_ID = link_itm_ID
									INNER JOIN T_files ON link_file_ID = file_ID' );
		$SQL->WHERE( 'cat_blog_ID IN ('.$list_blogs.')' ); // fp> TODO: want to restrict on images :]
		$SQL->WHERE_and( 'post_status = "published"' );	// TODO: this is a dirty hack. More should be shown.
		$SQL->WHERE_and( 'post_datestart <= \''.remove_seconds( $localtimenow ).'\'' );
		if( !empty( $this->disp_params[ 'item_type' ] ) )
		{ // Get items only with specified type
			$SQL->WHERE_and( 'post_ptyp_ID = \''.(int)$this->disp_params[ 'item_type' ].'\'' );
		}
		$SQL->GROUP_BY( 'link_ID' );
		$SQL->LIMIT( $this->disp_params[ 'limit' ]*4 ); // fp> TODO: because we have no way of getting images only, we get 4 times more data than requested and hope that 25% at least will be images :/
		$SQL->ORDER_BY(	gen_order_clause( $this->disp_params['order_by'], $this->disp_params['order_dir'],
											'post_', 'post_ID '.$this->disp_params['order_dir'].', link_ID' ) );

		$FileList->sql = $SQL->get();

		$FileList->query( false, false, false, 'Media index widget' );

		$layout = $this->disp_params[ 'thumb_layout' ];

		$nb_cols = $this->disp_params[ 'grid_nb_cols' ];
		$count = 0;
		$r = '';
		/**
		 * @var File
		 */
		while( $File = & $FileList->get_next() )
		{
			if( $count >= $this->disp_params[ 'limit' ] )
			{	// We have enough images already!
				break;
			}

			if( ! $File->is_image() )
			{	// Skip anything that is not an image
				// fp> TODO: maybe this property should be stored in link_ltype_ID or in the files table
				continue;
			}

			if( $layout == 'grid' )
			{
				if( $count % $nb_cols == 0 )
				{
					$r .= $this->disp_params[ 'grid_colstart' ];
				}
				$r .= $this->disp_params[ 'grid_cellstart' ];
			}
			else
			{
				$r .= $this->disp_params[ 'item_start' ];
			}

			// 1/ Hack a dirty permalink( will redirect to canonical):
			// $link = url_add_param( $Blog->get('url'), 'p='.$post_ID );

			// 2/ Hack a link to the right "page". Very daring!!
			// $link = url_add_param( $Blog->get('url'), 'paged='.$count );

			// 3/ Instantiate a light object in order to get permamnent url:
			$ItemLight = new ItemLight( $FileList->get_row_by_idx( $FileList->current_idx - 1 ) );	// index had already been incremented

			$r .= '<a href="'.$ItemLight->get_permanent_url().'">';
			// Generate the IMG THUMBNAIL tag with all the alt, title and desc if available
			$r .= $File->get_thumb_imgtag( $this->disp_params['thumb_size'], '', '', $ItemLight->title );
			$r .= '</a>';
			if( $this->disp_params[ 'disp_image_title' ] )
			{
				$title = ($File->get('title')) ? $this->get('title') : $ItemLight->title;
				$r .= '<span class="note">'.$title.'</span>';
			}

			++$count;

			if( $layout == 'grid' )
			{
				$r .= $this->disp_params[ 'grid_cellend' ];
				if( $count % $nb_cols == 0 )
				{
					$r .= $this->disp_params[ 'grid_colend' ];
				}
			}
			else
			{
				$r .= $this->disp_params[ 'item_end' ];
			}
		}

		// Exit if no files found
		if( empty($r) ) return;

		echo $this->disp_params[ 'block_start'];

		// Display title if requested
		$this->disp_title();

		if( $layout == 'grid' )
		{
			echo $this->disp_params[ 'grid_start' ];
		}
		else
		{
			echo $this->disp_params[ 'list_start' ];
		}

		echo $r;

		if( $layout == 'grid' )
		{
			if( $count && ( $count % $nb_cols != 0 ) )
			{
				echo $this->disp_params[ 'grid_colend' ];
			}

			echo $this->disp_params[ 'grid_end' ];
		}
		else
		{
			echo $this->disp_params[ 'list_end' ];
		}

		echo $this->disp_params[ 'block_end' ];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.27  2013/11/06 08:05:09  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>