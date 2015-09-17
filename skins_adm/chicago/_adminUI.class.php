<?php
/**
 * This file implements the Admin UI class.
 * Alternate admin skins should derive from this class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin-skin
 * @subpackage evo
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/../_adminUI_general.class.php';


/**
 * We define a special template for the main menu.
 *
 * @package admin-skin
 * @subpackage evo
 */
class AdminUI extends AdminUI_general
{
	/**
	 * This function should init the templates - like adding Javascript through the {@link add_headline()} method.
	 */
	function init_templates()
	{
		global $Hit, $Messages;

		// This is included before controller specifc require_css() calls:
		require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
		require_css( 'basic.css', 'rsc_url' ); // Basic styles
		require_css( 'results.css', 'rsc_url' ); // Results/tables styles
		require_css( 'item_base.css', 'rsc_url' ); // Default styles for the post CONTENT
		require_css( 'fileman.css', 'rsc_url' ); // Filemanager styles
		require_css( 'admin.global.css', 'rsc_url' ); // Basic admin styles
		require_css( 'skins_adm/chicago/rsc/css/chicago.css', true );

		if( $Hit->is_IE() ) // We can do this test because BackOffice is never page-cached
		{ // CSS for IE
			require_css( 'admin_global_ie.css', 'rsc_url' );
		}
		if( $Hit->is_IE( 9 ) ) // We can do this test because BackOffice is never page-cached
		{ // CSS for IE9
			require_css( 'ie9.css', 'rsc_url' );
		}

		require_js( '#jquery#', 'rsc_url' );
		require_js( 'jquery/jquery.raty.min.js', 'rsc_url' );

		// Set css classes for messages
		$Messages->set_params( array(
				'class_outerdiv' => 'action_messages',
			) );

		if( $Hit->get_browser_version() > 0 && $Hit->is_IE( 9, '<' ) )
		{	// IE < 9
			global $debug;
			$Messages->add( T_('Your web browser is too old. For this site to work correctly, we recommend you use a more recent browser.'), 'note' );
			if( $debug )
			{
				$Messages->add( 'User Agent: '.$Hit->get_user_agent(), 'note' );
			}
		}
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $UserSettings, $current_User;

		$r = '';
		if( $UserSettings->get( 'show_breadcrumbs', $current_User->ID ) )
		{
			$r .= $this->breadcrumbpath_get_html();
		}

		if( $UserSettings->get( 'show_menu', $current_User->ID) )
		{
			$r .= '
			<div id="header">'
				// Display MAIN menu:
				.$this->get_html_menu().'
			</div>
			';
		}

		return $r;
	}


	/**
	 *
	 *
	 * @return string
	 */
	function get_body_top()
	{
		global $Messages;

		$r = '<div class="wrapper">';

		$r .= $this->get_page_head();

		$r .= $this->get_bloglist_buttons();

		$r .= '<div id="panelbody" class="panelbody">'
			."\n\n";

		// Display info & error messages
		$r .= $Messages->display( NULL, NULL, false );

		return $r;
	}


	/**
	 * Get the end of the HTML <body>. Close open divs, etc...
	 *
	 * @return string
	 */
	function get_body_bottom()
	{
		return "\n</div>\n</div>\n";
	}


	/**
	 * Get the footer text
	 */
	function get_footer_contents()
	{
		global $app_footer_text, $copyright_text;
		global $adminskins_url;

		$r = '<div class="footer">';

		$r .= '<a href="http://b2evolution.net/" class="footer_logo"><img src="'.$adminskins_url.'chicago/rsc/img/b2evolution-footer-logo-blue-bg.gif" alt="Powered by b2evolution" width="142" height="43" longdesc="http://b2evolution.net/" /></a>';

		$r .= '<div class="copyright">';

		$r .= $app_footer_text.' &ndash; '.$copyright_text."</div></div>\n\n";

		return $r;
	}


	/**
	 * Get a template by name and depth.
	 *
	 * Templates can handle multiple depth levels
	 *
	 * This is a method (and not a member array) to allow dynamic generation and T_()
	 *
	 * @param string Name of the template ('main', 'sub')
	 * @param integer Nesting level (start at 0)
	 * @param boolean TRUE to die on unknown template name
	 * @return array Associative array which defines layout and optionally properties.
	 */
	function get_template( $name, $depth = 0, $die_on_unknown = false )
	{
		global $rsc_url;

		$pb_begin1 = '<div class="pblock">';
		$pb_begin2 = '<div class="pan_left"><div class="pan_right"><div class="pan_top"><div class="pan_tl"><div class="pan"><div class="panelblock">';
		$pb_end = '</div></div></div></div></div></div>
								<div class="pan_bot"><div class="pan_bl"><div class="pan_br"></div></div></div></div>';

		switch( $name )
		{
			case 'sub':
				// a payload block with embedded submenu
				return array(
						'before' => $pb_begin1.'$top_block$'
							.'<span style="float:right">$global_icons$</span>'
							.'<table class="tabs" cellspacing="0"><tr>'
							.'<td class="first"></td>',

						'after' => '<td class="last"></td>'
							."</tr></table>\n"
							.$pb_begin2,

						'empty' => $pb_begin1.'<span style="float:right;margin-bottom:6px">$global_icons$</span>'.$pb_begin2,

						'beforeEach' => '<td class="option">',
						'afterEach'  => '</td>',
						'beforeEachSel' => '<td class="current">',
						'afterEachSel' => '</td>',

						'end' => $pb_end, // used to end payload block that opened submenu
					);


			case 'block':
				// an additional payload block, anywhere after the one with the submenu. Used by disp_payload_begin()/disp_payload_end()
				return array(
						'begin' => $pb_begin1.$pb_begin2,
						'end' => $pb_end,
					);


			case 'Results':
				// Results list:
				return array(
					'page_url' => '', // All generated links will refer to the current page
					'before' => '<div class="results">',
					'content_start' => '<div id="$prefix$ajax_content">',
					'header_start' => '<div class="results_nav">',
						'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => '',
					'header_end' => '</div>',
					'head_title' => '<div class="fieldset_title"><div class="fieldset_title_right"><div class="fieldset_title_bg">
															<span style="float:right">$global_icons$</span>$title$
														</div></div></div>'."\n",
					'filters_start' => '<div class="filters">',
					'filters_end' => '</div>',
					'messages_start' => '<div class="messages">',
					'messages_end' => '</div>',
					'messages_separator' => '<br />',
					'list_start' => '<div class="table_scroll">'."\n"
					               .'<table class="grouped $list_class$" cellspacing="0" $list_attrib$>'."\n",
						'head_start' => '<thead>'."\n",
							'line_start_head' => '<tr class="clickable_headers">',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$ $title_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$" $title_attrib$>',
							'colhead_start_last' => '<th class="lastcol $class$" $title_attrib$>',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => get_icon( 'sort_asc_off' ),
							'sort_asc_on' => get_icon( 'sort_asc_on' ),
							'sort_desc_off' => get_icon( 'sort_desc_off' ),
							'sort_desc_on' => get_icon( 'sort_desc_on' ),
							'basic_sort_off' => '',
							'basic_sort_asc' => get_icon( 'ascending' ),
							'basic_sort_desc' => get_icon( 'descending' ),
						'head_end' => "</thead>\n\n",
						'tfoot_start' => "<tfoot>\n",
						'tfoot_end' => "</tfoot>\n\n",
						'body_start' => "<tbody>\n",
							'line_start' => '<tr class="even">'."\n",
							'line_start_odd' => '<tr class="odd">'."\n",
							'line_start_last' => '<tr class="even lastline">'."\n",
							'line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'col_start' => '<td $class_attrib$>',
								'col_start_first' => '<td class="firstcol $class$">',
								'col_start_last' => '<td class="lastcol $class$">',
								'col_end' => "</td>\n",
							'line_end' => "</tr>\n\n",
							'grp_line_start' => '<tr class="group">'."\n",
							'grp_line_start_odd' => '<tr class="odd">'."\n",
							'grp_line_start_last' => '<tr class="lastline">'."\n",
							'grp_line_start_odd_last' => '<tr class="odd lastline">'."\n",
										'grp_col_start' => '<td $class_attrib$ $colspan_attrib$>',
										'grp_col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
										'grp_col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'grp_col_end' => "</td>\n",
							'grp_line_end' => "</tr>\n\n",
						'body_end' => "</tbody>\n\n",
						'total_line_start' => '<tr class="total">'."\n",
							'total_col_start' => '<td $class_attrib$>',
							'total_col_start_first' => '<td class="firstcol $class$">',
							'total_col_start_last' => '<td class="lastcol $class$">',
							'total_col_end' => "</td>\n",
						'total_line_end' => "</tr>\n\n",
					'list_end' => "</table></div>\n\n",
					'footer_start' => '<div class="results_nav nav_footer">',
					'footer_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$<br />$page_size$'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '$page_size$',
					'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'no_prev_text' => '',
						'no_next_text' => '',
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results_start' => '<table class="grouped" cellspacing="0">'."\n",
					'no_results_end'   => '<tr class="lastline"><td class="firstcol lastcol">$no_results$</td></tr>'
								                .'</table>'."\n\n",
				'content_end' => '</div>',
				'after' => '</div><div class="clear"></div>',
				'sort_type' => 'basic'
				);


			case 'compact_form':
				// Compact Form settings:
				return array(
					'layout' => 'chicago',		// Temporary dirty hack
					'formstart' => '<div class="fieldset_title"><div class="fieldset_title_right">',

					'title_fmt' => '<div class="fieldset_title_bg" $title_attribs$><span style="float:right">$global_icons$</span>$title$</div></div></div><fieldset>'."\n",
					'no_title_fmt' => '<div class="fieldset_title_bg" $title_attribs$><span style="float:right">$global_icons$</span>&nbsp;</div></div></div><fieldset>'."\n",
					'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="$id$"><h2 $title_attribs$>$fieldset_title$</h2>',
					'fieldset_end' => '</div>',
					'fieldstart' => '<fieldset $ID$>'."\n",
					'labelclass' => '',
					'labelstart' => '<div class="label">',
					'labelend' => "</div>\n",
					'labelempty' => '<div class="label"></div>', // so that IE6 aligns DIV.input correcctly
					'inputstart' => '<div class="input">',
					'infostart' => '<div class="info">',
					'infoend' => "</div>\n",
					'inputend' => "</div>\n",
					'fieldend' => "</fieldset>\n\n",
					'buttonsstart' => '<fieldset><div class="input">',
					'buttonsend' => "</div></fieldset>\n\n",
					'customstart' => '<div class="custom_content">',
					'customend' => "</div>\n",
					'note_format' => ' <span class="notes">%s</span>',
					'formend' => '</fieldset>'."\n",
				);


			case 'Form':
				// Default Form settings:
				return array(
					'layout' => 'chicago',		// Temporary dirty hack
					'formstart' => '',
					'title_fmt' => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
					'no_title_fmt' => '<span style="float:right">$global_icons$</span>'."\n",
					'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><div class="fieldset_title"><div class="fieldset_title_right">
						<div class="fieldset_title_bg" $title_attribs$>$fieldset_title$</div></div></div>
						<fieldset $fieldset_attribs$>'."\n", // $fieldset_attribs will contain ID
					'fieldset_end' => '</fieldset></div>'."\n",
					'fieldstart' => '<fieldset $ID$>'."\n",
					'labelclass' => '',
					'labelstart' => '<div class="label">',
					'labelend' => "</div>\n",
					'labelempty' => '<div class="label"></div>', // so that IE6 aligns DIV.input correcctly
					'inputstart' => '<div class="input">',
					'infostart' => '<div class="info">',
					'infoend' => "</div>\n",
					'inputend' => "</div>\n",
					'fieldend' => "</fieldset>\n\n",
					'buttonsstart' => '<fieldset><div class="input">',
					'buttonsend' => "</div></fieldset>\n\n",
					'customstart' => '<div class="custom_content">',
					'customend' => "</div>\n",
					'note_format' => ' <span class="notes">%s</span>',
					'formend' => '',
				);


			case 'file_browser':
				return array(
						'block_start' => '<div class="block_item_wrap"><div class="fieldset_title"><div class="fieldset_title_right"><div class="fieldset_title_bg">
																		<span style="float:right">$global_icons$</span>$title$
																	</div></div></div>',
						'block_end' => '</div>',
					);

			case 'block_item':
				return array(
						'block_start' => '<div class="block_item_wrap"><div class="fieldset_title"><div class="fieldset_title_right"><div class="fieldset_title_bg">
																		<span style="float:right">$global_icons$</span>$title$
																	</div></div></div>
																	<div class="block_item" id="styled_content_block">',
						'block_end' => '</div></div>',
					);

			case 'dash_item':
				return array(
						'block_start' => '<div class="block_item_wrap"><div class="fieldset_title"><div class="fieldset_title_right"><div class="fieldset_title_bg">
																		<span style="float:right">$global_icons$</span>$title$
																	</div></div></div>
																	<div class="dash_item">',
						'block_end' => '</div></div>',
					);

			case 'side_item':
				return array(
						'block_start' => '<div class="browse_side_item_wrap"><div class="fieldset_title"><div class="fieldset_title_right"><div class="fieldset_title_bg">
																		<span style="float:right">$global_icons$</span>$title$
																	</div></div></div>
																	<div class="browse_side_item">',
						'block_end' => '</div></div>',
					);

			default:
				// Delegate to parent class:
				return parent::get_template( $name, $depth, $die_on_unknown );
		}
	}

	/**
	 * Get colors for page elements that can't be controlled by CSS (charts)
	 */
	function get_color( $what )
	{
		switch( $what )
		{
			case 'payload_background':
				return 'f1f6f8';
				break;
		}
		debug_die( 'unknown color' );
	}


	/**
	 * Display skin specific options
	 */
	function display_skin_settings( $Form, $user_ID )
	{
		global $UserSettings, $current_User;
		$Form->begin_fieldset( T_( 'Admin skin settings' ), array( 'id' => 'admin_skin_settings' ) );
		parent::display_skin_settings( $Form, $user_ID );

		$user_admin_skin = $UserSettings->get( 'admin_skin', $user_ID );
		if( $UserSettings->get( 'admin_skin', $current_User->ID ) == $user_admin_skin )
		{
			$Form->checklist( array(
						array( 'show_evobar', 1, T_('Show evobar'), $UserSettings->get( 'show_evobar', $user_ID ) ),
						array( 'show_breadcrumbs', 1, T_('Show breadcrumbs path'), $UserSettings->get( 'show_breadcrumbs', $user_ID ) ),
						array( 'show_menu', 1, T_('Show Menu'), $UserSettings->get( 'show_menu', $user_ID ) ) ),
					'chicago_settings', T_('Chicago skin settings') );
		}
		else
		{
			$Form->info( '', sprintf( T_( 'Admin skin settings for this user cannot be edited because this user is using a different admin skin (%s)' ), $user_admin_skin ) );
		}

		$Form->end_fieldset();

		// JavaScript code to dynamically change display settings. show_evobar or show_menu always have to be checked
		?>
		<script type="text/javascript">
		jQuery( '[name = show_evobar], [name = show_menu]' ).click( function()
		{
			if( ! ( jQuery( '[name = show_evobar]' ).attr( 'checked' ) || jQuery( '[name = show_menu]' ).attr( 'checked' ) ) )
			{
				jQuery( '[name = show_evobar]' ).attr( 'checked', true );
			}
		} );
		</script>
		<?php
	}


	/**
	 * Set skin specific options
	 */
	function set_skin_settings( $user_ID )
	{
		global $UserSettings;
		$show_menu = param( 'show_menu', 'boolean' );
		// evobar or menu must be visible. If menu is not visible, show_evobar must be set to true.
		$show_evobar = ( $show_menu ? param( 'show_evobar', 'boolean' ) : true );

		$UserSettings->set( 'show_evobar', $show_evobar, $user_ID );
		$UserSettings->set( 'show_breadcrumbs', param( 'show_breadcrumbs', 'boolean' ), $user_ID );
		$UserSettings->set( 'show_menu', $show_menu, $user_ID );
		// It will be saved by the user.ctrl
		// $UserSettings->dbupdate();
	}


	/**
	 * Get show evobar setting
	 * @return boolean
	 */
	function get_show_evobar()
	{
		global $UserSettings, $current_User;
		return $UserSettings->get( 'show_evobar', $current_User->ID );
	}
}


?>