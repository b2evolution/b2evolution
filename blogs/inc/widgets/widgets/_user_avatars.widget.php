<?php
/**
 * This file implements the User Avatars Widget class.
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
 * @author Yabba	- {@link http://www.astonishme.co.uk/}
 *
 * @version $Id: _user_avatars.widget.php 17 2011-10-25 04:22:09Z sam2kb $
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
class user_avatars_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function user_avatars_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'user_avatars' );
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
				'defaultvalue' => T_('Random Users'),
			),
			'thumb_size' => array(
				'label' => T_('Thumbnail size'),
				'note' => T_('Cropping and sizing of thumbnails'),
				'type' => 'select',
				'options' => get_available_thumb_sizes(),
				'defaultvalue' => 'crop-top-80x80',
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
				'defaultvalue' => 1,
			),
			'limit' => array(
				'label' => T_( 'Max pictures' ),
				'note' => T_( 'Maximum number of pictures to display.' ),
				'size' => 4,
				'defaultvalue' => 1,
			),
			'bubbletip' => array(
				'label' => T_( 'Bubble tips' ),
				'note' => T_( 'Check to enable bubble tips -- Bubble tips must also be enabled for the current skin.' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'order_by' => array(
				'label' => T_('Order by'),
				'note' => T_('How to sort the users'),
				'type' => 'select',
				'options' => array(
						'random'  => T_('Random users'),
						'regdate' => T_('Most recent registrations'),
						'moddate' => T_('Most recent profile updates'),
					),
				'defaultvalue' => 'random',
			),
		), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Users pictures');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Index of users avatars; click goes to user page.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		$UserCache = & get_UserCache();

		$UserList = new DataObjectList2( $UserCache );

		switch( $this->disp_params[ 'order_by' ] )
		{
			case 'regdate':
				$sql_order = 'user_created_datetime DESC';
				break;
			case 'moddate':
				$sql_order = 'user_profileupdate_date DESC';
				break;
			case 'random':
			default:
				$sql_order = 'RAND()';
				break;
		}

		// Query list of files:
		$SQL = new SQL();
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_users' );
		$SQL->WHERE( 'user_avatar_file_ID IS NOT NULL' );
		$SQL->ORDER_BY( $sql_order );
		$SQL->LIMIT( $this->disp_params[ 'limit' ] );

		$UserList->sql = $SQL->get();

		$UserList->query( false, false, false, 'User avatars widget' );

		$layout = $this->disp_params[ 'thumb_layout' ];

		$nb_cols = $this->disp_params[ 'grid_nb_cols' ];
		$count = 0;
		$r = '';
		/**
		 * @var User
		 */
		while( $User = & $UserList->get_next() )
		{
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

			$identity_url = get_user_identity_url( $User->ID );
			$avatar_tag = $User->get_avatar_imgtag( $this->disp_params['thumb_size'] );

			if( $this->disp_params[ 'bubbletip' ] == '1' )
			{	// Bubbletip is enabled
				$bubbletip_param = ' rel="bubbletip_user_'.$User->ID.'"';
				$avatar_tag = str_replace( '<img ', '<img '.$bubbletip_param.' ', $avatar_tag );
			}

			if( ! empty( $identity_url ) )
			{
				$r .= '<a href="'.$identity_url.'">'.$avatar_tag.'</a>';
			}
			else
			{
				$r .= $avatar_tag;
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

		echo $this->disp_params['block_body_start'];

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

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params[ 'block_end' ];

		return true;
	}
}

?>