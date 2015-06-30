<?php
/**
 * This file implements the User Avatars Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
				'options' => array( 'grid' => T_( 'Grid' ), 'list' => T_( 'List' ), 'flow' => T_( 'Flowing Blocks' ) ),
				'defaultvalue' => 'flow',
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
			'style' => array(
				'label' => T_('Display'),
				'note' => '',
				'type' => 'select',
				'options' => array(
						'simple' => T_('Pictures only'),
						'badges' => T_('User badges'),
					),
				'defaultvalue' => 'simple',
			),
			'gender' => array(
				'label' => T_('Gender filtering'),
				'note' => '',
				'type' => 'select',
				'options' => array(
						'any'      => T_('Any'),
						'same'     => T_('Same gender as User'),
						'opposite' => T_('Opposite gender as User'),
					),
				'defaultvalue' => 'any',
			),
			'location' => array(
				'label' => T_('Location filtering'),
				'note' => '',
				'type' => 'select',
				'options' => array(
						'any'       => T_('Any'),
						'country'   => T_('Same country as User'),
						'region'    => T_('Same region as User'),
						'subregion' => T_('Same sub-region as User'),
						'city'      => T_('Same city as User'),
						'closest'   => T_('Closest users'),
					),
				'defaultvalue' => 'any',
			),
		), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'users-pictures-widget' );
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

		// Query list of users with picture and not closed:
		$SQL = new SQL();
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_users' );
		$SQL->WHERE( 'user_avatar_file_ID IS NOT NULL' );
		$SQL->WHERE_and( 'user_status <> "closed"' );
		if( is_logged_in() )
		{ // Add filters
			global $current_User, $DB;
			switch( $this->disp_params[ 'gender' ] )
			{ // Filter by gender
				case 'same':
					$SQL->WHERE_and( 'user_gender = "'.$current_User->gender.'"' );
					break;
				case 'opposite':
					$SQL->WHERE_and( 'user_gender != "'.$current_User->gender.'"' );
					break;
			}
			switch( $this->disp_params[ 'location' ] )
			{ // Filter by location
				case 'city':
					$SQL->WHERE_and( 'user_city_ID '.( empty( $current_User->city_ID ) ? 'IS NULL' : '= "'.$current_User->city_ID.'"' ) );
				case 'subregion':
					$SQL->WHERE_and( 'user_subrg_ID '.( empty( $current_User->subrg_ID  ) ? 'IS NULL' : '= "'.$current_User->subrg_ID .'"' ) );
				case 'region':
					$SQL->WHERE_and( 'user_rgn_ID '.( empty( $current_User->rgn_ID  ) ? 'IS NULL' : '= "'.$current_User->rgn_ID .'"' ) );
				case 'country':
					$SQL->WHERE_and( 'user_ctry_ID '.( empty( $current_User->ctry_ID ) ? 'IS NULL' : '= "'.$current_User->ctry_ID.'"' ) );
					break;
				case 'closest':
					if( !empty( $current_User->city_ID ) )
					{ // Check if users exist with same city
						$user_exists = $DB->get_var( 'SELECT user_ID
							 FROM T_users
							WHERE user_city_ID ="'.$current_User->city_ID.'"
							  AND user_ID != "'.$current_User->ID.'"
							LIMIT 1' );
						if( !empty( $user_exists ) )
						{
							$SQL->WHERE_and( 'user_city_ID = "'.$current_User->city_ID.'"' );
							$SQL->WHERE_and( 'user_subrg_ID = "'.$current_User->subrg_ID .'"' );
							$SQL->WHERE_and( 'user_rgn_ID = "'.$current_User->rgn_ID .'"' );
							$SQL->WHERE_and( 'user_ctry_ID = "'.$current_User->ctry_ID.'"' );
							break;
						}
					}
					if( !empty( $current_User->subrg_ID ) && empty( $user_exists ) )
					{ // Check if users exist with same sub-region
						$user_exists = $DB->get_var( 'SELECT user_ID
							 FROM T_users
							WHERE user_subrg_ID ="'.$current_User->subrg_ID.'"
							  AND user_ID != "'.$current_User->ID.'"
							LIMIT 1' );
						if( !empty( $user_exists ) )
						{
							$SQL->WHERE_and( 'user_subrg_ID = "'.$current_User->subrg_ID .'"' );
							$SQL->WHERE_and( 'user_rgn_ID = "'.$current_User->rgn_ID .'"' );
							$SQL->WHERE_and( 'user_ctry_ID = "'.$current_User->ctry_ID.'"' );
							break;
						}
					}
					if( !empty( $current_User->rgn_ID ) && empty( $user_exists ) )
					{ // Check if users exist with same region
						$user_exists = $DB->get_var( 'SELECT user_ID
							 FROM T_users
							WHERE user_rgn_ID ="'.$current_User->rgn_ID.'"
							  AND user_ID != "'.$current_User->ID.'"
							LIMIT 1' );
						if( !empty( $user_exists ) )
						{
							$SQL->WHERE_and( 'user_rgn_ID = "'.$current_User->rgn_ID .'"' );
							$SQL->WHERE_and( 'user_ctry_ID = "'.$current_User->ctry_ID.'"' );
							break;
						}
					}
					if( !empty( $current_User->ctry_ID ) && empty( $user_exists ) )
					{ // Check if users exist with same country
						$user_exists = $DB->get_var( 'SELECT user_ID
							 FROM T_users
							WHERE user_ctry_ID ="'.$current_User->ctry_ID.'"
							  AND user_ID != "'.$current_User->ID.'"
							LIMIT 1' );
						if( !empty( $user_exists ) )
						{
							$SQL->WHERE_and( 'user_ctry_ID = "'.$current_User->ctry_ID.'"' );
						}
					}
					break;
			}
		}
		$SQL->ORDER_BY( $sql_order );
		$SQL->LIMIT( intval( $this->disp_params[ 'limit' ] ) );

		$UserList->sql = $SQL->get();

		$UserList->query( false, false, false, 'User avatars widget' );

		$avatar_link_attrs = '';
		if( $this->disp_params[ 'style' ] == 'badges' )
		{ // Remove borders of <td> elements
			$this->disp_params[ 'grid_cellstart' ] = str_replace( '>', ' style="border:none">', $this->disp_params[ 'grid_cellstart' ] );
			$avatar_link_attrs = ' class="avatar_rounded"';
		}

		$layout = $this->disp_params[ 'thumb_layout' ];

		$nb_cols = intval( $this->disp_params[ 'grid_nb_cols' ] );
		$count = 0;
		$r = '';
		/**
		 * @var User
		 */
		while( $User = & $UserList->get_next() )
		{
			if( $layout == 'grid' )
			{ // Grid layout
				if( $count % $nb_cols == 0 )
				{
					$r .= $this->disp_params[ 'grid_colstart' ];
				}
				$r .= $this->disp_params[ 'grid_cellstart' ];
			}
			elseif( $layout == 'flow' )
			{ // Flow block layout
				$r .= $this->disp_params[ 'flow_block_start' ];
			}
			else
			{ // List layout
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
				$r .= '<a href="'.$identity_url.'"'.$avatar_link_attrs.'>';
				$r .= $avatar_tag;
				if( $this->disp_params[ 'style' ] == 'badges' )
				{ // Add user login after picture
					$r .= '<br >'.$User->get_colored_login();
				}
				$r .= '</a>';
			}
			else
			{
				$r .= $avatar_tag;
			}

			++$count;

			if( $layout == 'grid' )
			{ // Grid layout
				$r .= $this->disp_params[ 'grid_cellend' ];
				if( $count % $nb_cols == 0 )
				{
					$r .= $this->disp_params[ 'grid_colend' ];
				}
			}
			elseif( $layout == 'flow' )
			{ // Flow block layout
				$r .= $this->disp_params[ 'flow_block_end' ];				
			}
			else
			{ // List layout
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
		elseif( $layout == 'flow' )
		{ // Flow block layout
			echo $this->disp_params[ 'flow_start' ];
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
		elseif ( $layout == 'flow' )
		{ // Flow block layout
			echo $this->disp_params[ 'flow_end' ];
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