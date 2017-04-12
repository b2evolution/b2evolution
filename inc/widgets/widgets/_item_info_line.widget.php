<?php
/**
 * This file implements the item_info_line Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
 * @author erhsatingin: Erwin Rommel Satingin.
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
class item_info_line_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_info_line' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'info-line-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Info Line');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Info Line') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display information about the item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $Blog;

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'flag_icon' => array(
					'label' => T_( 'Flag icon' ),
					'note' => T_( 'Display flag icon' ),
					'type' => 'checkbox',
					'defaultvalue' => true
				),
				'permalink_icon' => array(
					'label' => T_( 'Permalink icon' ),
					'note' => T_( 'Display permalink icon' ),
					'type' => 'checkbox',
					'defaultvalue' => true
				),
				'before_author' => array(
					'label' => T_( 'Before author' ),
					'note' => T_( 'Display author information' ),
					'type' => 'radio',
					'options' => array(
						array( 'posted_by', T_( 'Posted by' ) ),
						array( 'started_by', T_( 'Started by' ) ),
						array( 'none', T_( 'None' ) )
					),
					'defaultvalue' => 'posted_by',
					'field_lines' => true
				),
				'date_format' => array(
					'label' => T_( 'Date format' ),
					'note' => T_( 'Item/post date display format' ),
					'type' => 'radio',
					'options' => array(
						array( 'extended', sprintf( T_('Extended format %s'), '<code>'.locale_extdatefmt().'</code>' ) ),
						array( 'long', sprintf( T_('Long format %s'), '<code>'.locale_longdatefmt().'</code>' ) ),
						array( 'short', sprintf( T_('Short format %s'), '<code>'.locale_datefmt().'</code>' ) ),
						array( 'none', T_('None') )
					),
					'defaultvalue' => 'extended',
					'field_lines' => true
				),
				'time_format' => array(
					'label' => T_( 'Time format' ),
					'note' => T_( 'Item/post time display format' ),
					'type' => 'radio',
					'options' => array(
						array( 'long', sprintf( T_('Long format %s'), '<code>'.locale_timefmt().'</code>' ) ),
						array( 'short', sprintf( T_('Short format %s'), '<code>'.locale_shorttimefmt().'</code>' ) ),
						array( 'none', T_('None') )
					),
					'defaultvalue' => 'none',
					'field_lines' => true
				),
				'display_date' => array(
					'label' => T_('Date and time to use'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
						array( 'issue_date', T_('Issue date') ),
						array( 'date_created', T_('Date created') )
					),
					'defaultvalue' => in_array( $Blog->type, array( 'forum', 'group' ) ) ? 'date_created' : 'issue_date',
					'field_lines' => true
				),
				'last_touched' => array(
					'label' => T_( 'Last touched' ),
					'note' => T_( 'Display date and time when item/post was last touched' ),
					'type' => 'checkbox',
					'defaultvalue' => false
				),
				'category' => array(
					'label' => T_( 'Category' ),
					'note' => T_( 'Display item/post category' ),
					'type' => 'checkbox',
					'defaultvalue' => true
				),
				'edit_link' => array(
					'label' => T_( 'Edit link' ),
					'note' => T_( 'Display link to edit the item/post' ),
					'type' => 'checkbox',
					'defaultvalue' => true
				)
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget displays dynamic data:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		$params = array_merge( array(
			'author_link_text' => 'preferredname'
		), $params );

		$this->init_display( $params );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		// Flag:
		if( $this->disp_params['flag_icon'] )
		{
			$Item->flag();
		}

		// Permalink:
		if( $this->disp_params['permalink_icon'] )
		{
			$Item->permanent_link( array(
					'text' => '#icon#',
					'after' => ' ',
				) );
		}

		// Author
		if( $this->disp_params['before_author'] != 'none' )
		{
			switch( $this->disp_params['before_author'] )
			{
				case 'posted_by':
					$before_author = T_('posted by').' ';
					break;

				case 'started_by':
					$before_author = T_('started by').' ';
					break;

				default:
					$before_author = '';
			}
			$Item->author( array(
				'before'    => /* TRANS: author name */ $before_author,
				'after'     => ' ',
				'link_text' => $params['author_link_text'],
			) );
		}

		// We want to display the post time:
		$date_format = '';
		if( $this->disp_params['date_format'] != 'none' )
		{
			switch( $this->disp_params['date_format'] )
			{
				case 'extended':
					$date_format = locale_extdatefmt();
					break;

				case 'long':
					$date_format = locale_longdatefmt();
					break;

				case 'short':
					$date_format = locale_datefmt();
					break;
			}
		}

		$time_format = '';
		if( $this->disp_params['time_format'] != 'none' )
		{
			switch( $this->disp_params['time_format'] )
			{
				case 'long':
					$time_format = locale_timefmt();
					break;

				case 'short':
					$time_format = locale_shorttimefmt();
					break;
			}
		}

		if( $this->disp_params['date_format'] != 'none' || $this->disp_params['time_format'] != 'none' )
		{
			switch( $this->disp_params['display_date'] )
			{
				case 'issue_date':
					$Item->issue_time( array(
							'before'      => $this->disp_params['before_author'] == 'none' ? '' : T_('on').' ',
							'after'       => ' ',
							'time_format' => $date_format.( empty( $date_format ) ? '' : ' ' ).$time_format
						) );
					break;

				case 'date_created':
					echo $this->disp_params['before_author'] == 'none' ? '' : T_('on').' ';
					echo mysql2date( $date_format.( empty( $date_format ) ? '' : ' ' ).$time_format, $Item->datecreated ).' ';
					break;
			}
		}


		// Categories
		if( $this->disp_params['category'] )
		{
			$Item->categories( array(
				'before'          => /* TRANS: category name(s) */ T_('in').' ',
				'after'           => ' ',
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'link_categories' => true,
			) );
		}

		// Last touched
		if( $this->disp_params['last_touched'] )
		{
			echo '<span class="text-muted"> &ndash; '
				.T_('Last touched').': '
				.mysql2date( $date_format.( empty( $date_format ) ? '' : ' ' ).$time_format, $Item->get( 'last_touched_ts' ) )
				.'</span>';
		}

		// Link for editing
		if( $this->disp_params['edit_link'] )
		{
			$Item->edit_link( array(
				'before'    => ' &bull; ',
				'after'     => '',
			) );
		}

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>