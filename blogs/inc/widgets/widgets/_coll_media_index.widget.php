<?php
/**
 * This file implements the Media Index Widget class.
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
 * @author Yabba	- {@link http://www.astonishme.co.uk/}
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

		$r = array_merge( array(
			'title' => array(
				'label' => T_('Block title'),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_('Recent photos'),
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
				'type' => 'select',
				'options' => array( 'ASC'  => T_('Ascending'), 'DESC' => T_('Descending') ),
				'defaultvalue' => 'DESC',
			),
			'blog_ID' => array(
				'label' => T_( 'Blog' ),
				'note' => T_( 'ID of the blog to use, leave empty for the current blog.' ),
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
		$this->init_display( $params );

		global $Blog;

		$blogCache = get_Cache( 'BlogCache' );
		// fp> TODO: damn references!
		$list_Blog = ( $this->disp_params[ 'blog_ID' ] ? $blogCache->get_by_ID( $this->disp_params[ 'blog_ID' ] ) : $Blog );


		echo $this->disp_params[ 'block_start'];

		// Display title if requested
		$this->disp_title();

		// Display photos:
		// TODO: permissions, complete statuses, aggregations...
		// TODO: A FileList object based on ItemListLight but adding File data into the query?
		//          overriding ItemListLigth::query() for starters ;)


		$FileCache = & get_Cache( 'FileCache' );

		$FileList = & new DataObjectList2( $FileCache );

		// Query list of files:
		$SQL = & new SQL();
		$SQL->SELECT( 'post_ID, post_datestart, post_datemodified, post_main_cat_ID, post_urltitle, post_ptyp_ID, post_title, post_excerpt, post_url,
									file_ID, file_title, file_root_type, file_root_ID, file_path, file_alt, file_desc' );
		$SQL->FROM( 'T_categories INNER JOIN T_postcats ON cat_ID = postcat_cat_ID
									INNER JOIN T_items__item ON postcat_post_ID = post_ID
									INNER JOIN T_links ON post_ID = link_itm_ID
									INNER JOIN T_files ON link_file_ID = file_ID' );
		$SQL->WHERE( 'cat_blog_ID = '.$list_Blog->ID ); // fp> TODO: want to restrict on images :]
		$SQL->WHERE_and( 'post_status = "published"' );	// TODO: this is a dirty temporary hack. More should be shown.
		$SQL->GROUP_BY( 'link_ID' );
		$SQL->LIMIT( $this->disp_params[ 'limit' ] );
		$SQL->ORDER_BY(	gen_order_clause( $this->disp_params['order_by'], $this->disp_params['order_dir'],
											'post_', 'post_ID '.$this->disp_params['order_dir'].', link_ID' ) );

		$FileList->sql = $SQL->get();

		$FileList->query( false, false, false );

		$layout = $this->disp_params[ 'thumb_layout' ];

		if( $layout == 'grid' )
		{
			echo $this->disp_params[ 'grid_start' ];
		}
		else
		{
			echo $this->disp_params[ 'list_start' ];
		}

		$nb_cols = $this->disp_params[ 'grid_nb_cols' ];
		$count = 0;
		/**
		 * @var File
		 */
		while( $File = & $FileList->get_next() )
		{
			if( ! $File->is_image() )
			{	// Skip anything that is not an image
				// fp> TODO: maybe this property should be stored in link_ltype_ID
				// Tblue> TODO: If LIMIT is 1 and we order by RAND(), we
				//        sometimes get no images (only normal files) and
				//        nothing is displayed "inside" the widget.
				continue;
			}

			if( $layout == 'grid' )
			{
				if( $count % $nb_cols == 0 )
				{
					echo $this->disp_params[ 'grid_colstart' ];
				}
				echo $this->disp_params[ 'grid_cellstart' ];
			}
			else
			{
				echo $this->disp_params[ 'item_start' ];
			}

			// 1/ Hack a dirty permalink( will redirect to canonical):
			// $link = url_add_param( $Blog->get('url'), 'p='.$post_ID );

			// 2/ Hack a link to the right "page". Very daring!!
			// $link = url_add_param( $Blog->get('url'), 'paged='.$count );

			// 3/ Instantiate a light object in order to get permamnent url:
			$ItemLight = & new ItemLight( $FileList->get_row_by_idx( $FileList->current_idx - 1 ) );	// index had already been incremented

			echo '<a href="'.$ItemLight->get_permanent_url().'">';
			// Generate the IMG THUMBNAIL tag with all the alt, title and desc if available
			echo $File->get_thumb_imgtag( $this->disp_params['thumb_size'] );
			echo '</a>';

			++$count;

			if( $layout == 'grid' )
			{
				echo $this->disp_params[ 'grid_cellend' ];
				if( $count % $nb_cols == 0 )
				{
					echo $this->disp_params[ 'grid_colend' ];
				}
			}
			else
			{
				echo $this->disp_params[ 'item_end' ];
			}
		}

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
 * Revision 1.10  2009/08/27 13:15:28  tblue246
 * Removed left over pre_dump()...
 *
 * Revision 1.9  2009/08/27 13:13:54  tblue246
 * - Doc/todo
 * - Minor bugfix
 *
 * Revision 1.8  2009/07/07 04:52:54  sam2kb
 * Made some strings translatable
 *
 * Revision 1.7  2009/03/31 18:28:25  tblue246
 * Fixing http://forums.b2evolution.net/viewtopic.php?t=18387
 *
 * Revision 1.6  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.5  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.4  2009/02/08 11:31:56  yabs
 * Minor
 *
 * Revision 1.3  2008/09/29 08:30:36  fplanque
 * Avatar support
 *
 * Revision 1.2  2008/09/24 08:44:11  fplanque
 * Fixed and normalized order params for widgets (Comments not done yet)
 *
 * Revision 1.1  2008/09/23 09:04:33  fplanque
 * moved media index to a widget
 *
 * Revision 1.7  2008/05/06 23:35:47  fplanque
 * The correct way to add linebreaks to widgets is to add them to $disp_params when the container is called, right after the array_merge with defaults.
 *
 * Revision 1.5  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.4  2007/12/26 23:12:48  yabs
 * changing RANDOM to RAND
 *
 * Revision 1.3  2007/12/26 20:04:54  fplanque
 * minor
 *
 * Revision 1.2  2007/12/24 12:05:31  yabs
 * bugfix "order" is a reserved name, used by wi_order
 *
 * Revision 1.1  2007/12/24 11:02:42  yabs
 * added to cvs
 *
 */
?>
