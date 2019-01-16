<?php
/**
 * This file implements the item_info_line Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
	var $icon = 'info';

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
					'defaultvalue' => true,
				),
				'permalink_icon' => array(
					'label' => T_( 'Permalink icon' ),
					'note' => T_( 'Display permalink icon' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
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
					'field_lines' => true,
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
					'field_lines' => true,
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
					'field_lines' => true,
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
					'field_lines' => true,
				),
				'last_touched' => array(
					'label' => T_( 'Last touched' ),
					'note' => T_( 'Display date and time when item/post was last touched' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'contents_updated' => array(
					'label' => T_( 'Contents last updated' ),
					'note' => T_( 'Display date and time when item/post contents (title, content, URL or attachments) were last updated' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'category' => array(
					'label' => T_( 'Category' ),
					'note' => T_( 'Display item/post category' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
				'edit_link' => array(
					'label' => T_( 'Edit link' ),
					'note' => T_( 'Display link to edit the item/post' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
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
			'author_link_text' => 'preferredname',
			'block_body_start' => '<div class="small text-muted">',
			'block_body_end'   => '</div>',
			'widget_item_info_line_display' => true,
			'widget_item_info_line_before'  => '<span class="small text-muted">',
			'widget_item_info_line_after'   => '</span>',
			'widget_item_info_line_params'  => array(),
		), $params );

		$this->init_display( $params );

		if( empty( $Item ) )
		{ // Don't display this widget when there is no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		// Get default before author:
		switch( $this->disp_params['before_author'] )
		{
			case 'posted_by':
				$before_author = T_('Posted by').' ';
				break;

			case 'started_by':
				$before_author = T_('Started by').' ';
				break;

			default:
				$before_author = '';
		}

		// Get datetime format:
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
		$before_post_time = $this->disp_params['before_author'] == 'none' ? '' : T_('on').' ';

		$widget_params = array_merge( array(
			'before_flag'         => '',
			'after_flag'          => '',
			'before_permalink'    => '',
			'after_permalink'     => ' ',
			'permalink_text'      => '#icon#',
			'before_author'       => $before_author,
			'after_author'        => ' ',
			'before_post_time'    => $before_post_time,
			'after_post_time'     => ' ',
			'before_categories'   => T_('in').' ',
			'after_categories'    => ' ',
			'before_last_touched' => '<span class="text-muted"> &ndash; '.T_('Last touched').': ',
			'after_last_touched'  => '</span>',
			'before_last_updated' => '<span class="text-muted"> &ndash; '.T_('Contents updated').': ',
			'after_last_updated'  => '</span>',
			'before_edit_link'    => ' &bull; ',
			'after_edit_link'     => '',
			'edit_link_text'      => '#',
			'format'              => '',
		), $params['widget_item_info_line_params'] );

		ob_start();

		// Flag:
		$flag = '';
		if( $this->disp_params['flag_icon'] )
		{
			$Item->flag( array(
					'before' => $widget_params['before_flag'],
					'after'  => $widget_params['after_flag'],
				)	);
			$flag = ob_get_contents();
			ob_clean();
		}

		// Permalink:
		$permalink = '';
		if( $this->disp_params['permalink_icon'] )
		{
			$Item->permanent_link( array(
					'text'   => $widget_params['permalink_text'],
					'before' => $widget_params['before_permalink'],
					'after'  => $widget_params['after_permalink'],
				) );
			$permalink = ob_get_contents();
			ob_clean();
		}

		// Author:
		$author = '';
		if( $this->disp_params['before_author'] != 'none' )
		{
			$Item->author( array(
					'before'    => $widget_params['before_author'],
					'after'     => $widget_params['after_author'],
					'link_text' => $params['author_link_text'],
				) );
			$author = ob_get_contents();
			ob_clean();
		}

		// We want to display the post time:
		$post_time = '';
		if( $this->disp_params['date_format'] != 'none' || $this->disp_params['time_format'] != 'none' )
		{
			switch( $this->disp_params['display_date'] )
			{
				case 'issue_date':
					$Item->issue_time( array(
							'before'      => $widget_params['before_post_time'],
							'after'       => $widget_params['after_post_time'],
							'time_format' => $date_format.( empty( $time_format ) ? '' : ' ' ).$time_format
						) );
					break;

				case 'date_created':
					echo $widget_params['before_post_time'];
					echo mysql2date( $date_format.( empty( $time_format ) ? '' : ' ' ).$time_format, $Item->datecreated );
					echo $widget_params['after_post_time'];
					break;
			}
			$post_time = ob_get_contents();
			ob_clean();
		}

		// Categories:
		$categories = '';
		if( $this->disp_params['category'] )
		{
			$Item->categories( array(
				'before'          => $widget_params['before_categories'],
				'after'           => $widget_params['after_categories'],
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'link_categories' => true,
			) );
			$categories = ob_get_contents();
			ob_clean();
		}

		// Last touched:
		$last_touched = '';
		if( $this->disp_params['last_touched'] )
		{
			echo $widget_params['before_last_touched'];
			echo mysql2date( $date_format.( empty( $date_format ) ? '' : ' ' ).$time_format, $Item->get( 'last_touched_ts' ) );
			echo $widget_params['after_last_touched'];
			$last_touched = ob_get_contents();
			ob_clean();
		}

		// Contents last updated:
		$last_updated = '';
		if( $this->disp_params['contents_updated'] )
		{
			echo $widget_params['before_last_updated'];
			echo mysql2date( $date_format.( empty( $date_format ) ? '' : ' ' ).$time_format, $Item->get( 'contents_last_updated_ts' ) ).$Item->get_refresh_contents_last_updated_link();
			echo $widget_params['after_last_updated'];
			$last_updated = ob_get_contents();
			ob_clean();
		}

		// Link for editing:
		$edit_link = '';
		if( $this->disp_params['edit_link'] )
		{
			$Item->edit_link( array(
					'before' => $widget_params['before_edit_link'],
					'after'  => $widget_params['after_edit_link'],
					'text'   => $widget_params['edit_link_text'],
				) );
			$edit_link = ob_get_contents();
			ob_clean();
		}

		ob_end_clean();

		// Item info line format:
		$format = empty( $widget_params['format'] ) ? '$flag$$permalink$$author$$post_time$$categories$$last_touched$$last_updated$$edit_link$' : $widget_params['format'];

		$info_line = str_replace(
				array( '$flag$', '$permalink$', '$author$', '$post_time$', '$last_touched$', '$last_updated$', '$categories$', '$edit_link$' ),
				array( $flag, $permalink, $author, $post_time, $last_touched, $last_updated, $categories, $edit_link ), $format
			);

		$display_widget = $this->disp_params['flag_icon'] || $this->disp_params['permalink_icon'] || $this->disp_params['before_author'] != 'none'
				|| $this->disp_params['date_format'] != 'none' || $this->disp_params['time_format'] != 'none' || $this->disp_params['category']
				|| $this->disp_params['last_touched'] || $this->disp_params['contents_updated'] || $this->disp_params['edit_link'];

		if( ! $display_widget && empty( $info_line ) )
		{	// Display error message when nothing to display because of widget settings:
			global $admin_url;

			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];
			echo '<span class="evo_param_error">'.sprintf( T_('Nothing to display! Check "%s" <a %s>widget settings</a>.'),
					$this->get_name(), 'href="'.url_add_param( $admin_url, array( 'ctrl' => 'widgets', 'action' => 'edit', 'wi_ID' => $this->ID ) ).'"' ).'</span>';
			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		if( $params['widget_item_info_line_display'] && ! empty( $info_line ) )
		{
			echo $this->disp_params['block_start'];

			$this->disp_title();

			echo $this->disp_params['block_body_start'];

			echo $params['widget_item_info_line_before'];
			echo $info_line;
			echo $params['widget_item_info_line_after'];

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		$this->display_debug_message();
		return false;
	}
}

?>