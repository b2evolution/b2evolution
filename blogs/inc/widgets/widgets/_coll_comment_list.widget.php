<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
				'label' => t_('Block title'),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_('Recent comments'),
			),
			'link_author' => array(
				'label' => T_( 'Author link'),
				'note' => T_( 'Link the author to their url' ),
				'defaultvalue' => true,
				'type' => 'checkbox',
			),
			'nofollow' => array(
				'label' => T_( 'Nofollow link'),
				'note' => T_( 'add nofollow to link' ),
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
			'order' => array(
				'label' => t_('Order'),
				'note' => T_('Order to display items'),
				'type' => 'select',
				'options' => array( 'DESC' => T_( 'Newest to oldest' ), 'ASC' => T_( 'Oldest to newest' ), 'RANDOM' => T_( 'Random selection' ) ),
				'defaultvalue' => 'DESC',
			),
			'limit' => array(
				'label' => T_( 'Display' ),
				'note' => T_( 'Max items to display.' ),
				'size' => 4,
				'defaultvalue' => 20,
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

		$blogCache = get_Cache( 'BlogCache' );
		$listBlog = ( $this->disp_params[ 'blog_ID' ] ? $blogCache->get_by_ID( $this->disp_params[ 'blog_ID' ] ) : $Blog );

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = $this->disp_params[ 'limit' ];
		$order = $this->disp_params[ 'order' ];

		$CommentList = & new CommentList( $listBlog, "'comment','trackback','pingback'", array('published'), '',	'',	$order,	'',	$limit );

		echo $this->disp_params[ 'block_start'];

		echo $this->disp_params[ 'block_title_start' ].$this->disp_params[ 'title' ].$this->disp_params[ 'block_title_end' ];

		echo $this->disp_params[ 'list_start' ];
		while( $Comment = & $CommentList->get_next() )
		{ // Loop through comments:
			// Load comment's Item object:
			$Comment->get_Item();
			echo $this->disp_params[ 'item_start' ];
			$Comment->author( '', ' ', '', ' ', 'htmlbody', $this->disp_params[ 'link_author' ] );
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
 * Revision 1.1  2007/12/24 11:02:42  yabs
 * added to cvs
 *
 */
?>