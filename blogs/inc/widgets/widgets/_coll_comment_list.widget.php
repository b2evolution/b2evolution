<?php
/**
 * This file implements the Comment List Widget class.
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

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_comment_list_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_comment_list_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_comment_list' );
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
				'label' => T_('Block title'),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_('Recent comments'),
			),
			'disp_order' => array(
				'label' => T_('Order'),
				'note' => T_('Order to display items'),
				'type' => 'select',
				'options' => array( 'DESC' => T_( 'Newest to oldest' ), 'ASC' => T_( 'Oldest to newest' ), 'RAND' => T_( 'Random selection' ) ),
				'defaultvalue' => 'DESC',
			),
			'limit' => array(
				'label' => T_( 'Max items' ),
				'note' => T_( 'Maximum number of items to display.' ),
				'size' => 4,
				'defaultvalue' => 20,
			),
			'author_links' => array(
				'label' => T_( 'Link to author'),
				'note' => T_( 'Link the author to their url' ),
				'defaultvalue' => true,
				'type' => 'checkbox',
			),
			'hover_text' => array(
				'label' => T_( 'Hover text'),
				'note' => T_( 'Text to show when hovering over the link' ),
				'size' => 40,
				'defaultvalue' => T_( 'Read the full comment' ),
				'type' => 'text',
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
		return T_('Comment list');
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
		return T_('List of comments; click goes to comment.');
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

		$blogCache = & get_BlogCache();
		$listBlog = ( $this->disp_params[ 'blog_ID' ] ? $blogCache->get_by_ID( $this->disp_params[ 'blog_ID' ] ) : $Blog );

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = $this->disp_params[ 'limit' ];
		$order = $this->disp_params[ 'disp_order' ];

		$CommentList = & new CommentList( $listBlog, "'comment','trackback','pingback'", array('published'), '',	'',	$order,	'',	$limit );

		echo $this->disp_params[ 'block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params[ 'list_start' ];

    /**
		 * @var Comment
		 */
		while( $Comment = & $CommentList->get_next() )
		{ // Loop through comments:
			// Load comment's Item object:
			$Comment->get_Item();
			echo $this->disp_params[ 'item_start' ];
			$Comment->author( '', ' ', '', ' ', 'htmlbody', $this->disp_params[ 'author_links' ] );
			echo T_( 'on ' );
			$Comment->permanent_link( array(
				'text'        => $Comment->Item->title,
				'title'       => $this->disp_params[ 'hover_text' ],
				) );
			echo $this->disp_params[ 'item_end' ];
		}	// End of comment loop.}
		echo $this->disp_params[ 'list_end' ];
		echo $this->disp_params[ 'block_end' ];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.16  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.15  2009/09/25 07:33:31  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.14  2009/09/14 13:54:13  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.13  2009/09/12 11:03:13  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.12  2009/09/10 13:44:57  tblue246
 * Translation fixes/update
 *
 * Revision 1.11  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.10  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.9  2008/09/24 08:44:11  fplanque
 * Fixed and normalized order params for widgets (Comments not done yet)
 *
 * Revision 1.8  2008/09/23 09:04:32  fplanque
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