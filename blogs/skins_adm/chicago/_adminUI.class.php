<?php
/**
 * This file implements the Admin UI class.
 * Alternate admin skins should derive from this class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin-skin
 * @subpackage evo
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
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
	 * Display doctype + <head>...</head> section
	 */
	function disp_html_head()
	{
		require_css( 'skins_adm/chicago/rsc/css/chicago.css', true );

		parent::disp_html_head();
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $htsrv_url_sensitive, $baseurl, $admin_url, $rsc_url, $Blog;
		global $app_shortname, $app_version;

		$r = '
		<div id="header">'

			// Display MAIN menu:
			.$this->get_html_menu().'
		</div>
		';

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

		$r .= '<div class="panelbody">'
			."\n\n";

		// Display info & error messages
		$r .= $Messages->display( NULL, NULL, false, 'all', NULL, NULL, 'action_messages' );

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

		global $Hit;

		$r = '<div class="footer">';

   	if( $Hit->is_winIE )
		{
		 $r .= '<!--[if lt IE 7]>
<div style="text-align:center; color:#f00; font-weight:bold;">'.
			T_('WARNING: Internet Explorer 6 may not able to display this admin skin properly. We strongly recommend you upgrade to IE 7 or Firefox.').'</div>
<![endif]-->';
		}

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
	 * @return array Associative array which defines layout and optionally properties.
	 */
	function get_template( $name, $depth = 0 )
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
						'before' => $pb_begin1
							.'<span style="float:right">$global_icons$</span>'
							.'<table class="tabs" cellspacing="0"><tr>'
							.'<td class="first"></td>',

						'after' => '<td class="last"></td>'
							."</tr></table>\n"
							.$pb_begin2,

						'empty' => $pb_begin1.$pb_begin2,

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
					'header_start' => '<div class="results_nav">',
						'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => '',
					'header_end' => '</div>',
					'list_start' => '',
						'head_start' => '',
							'head_title' => '<div class="fieldset_title"><div class="fieldset_title_right"><div class="fieldset_title_bg">
																	<span style="float:right">$global_icons$</span>$title$
																</div></div></div>'
															."\n\n"
															.'<table class="grouped" cellspacing="0">'
							                ."\n<thead>\n",
							'filters_start' => '<tr class="filters"><td colspan="$nb_cols$">',
							'filters_end' => '</td></tr>',
							'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$">',
							'colhead_start_last' => '<th class="lastcol $class$">',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => '<img src="../admin/img/grey_arrow_up.gif" alt="A" title="'.T_('Ascending order')
							                    .'" height="12" width="11" />',
							'sort_asc_on' => '<img src="../admin/img/black_arrow_up.gif" alt="A" title="'.T_('Ascending order')
							                    .'" height="12" width="11" />',
							'sort_desc_off' => '<img src="../admin/img/grey_arrow_down.gif" alt="D" title="'.T_('Descending order')
							                    .'" height="12" width="11" />',
							'sort_desc_on' => '<img src="../admin/img/black_arrow_down.gif" alt="D" title="'.T_('Descending order')
							                    .'" height="12" width="11" />',
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
					'list_end' => "</table>\n\n",
					'footer_start' => '<div class="results_nav">',
					'footer_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '',
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
					'no_results_start' => '<div class="fieldset_title"><div class="fieldset_title_right"><div class="fieldset_title_bg">
																		<span style="float:right">$global_icons$</span>$title$
																	</div></div></div>'
																."\n\n"
																.'<table class="grouped" cellspacing="0">'
								                ."\n"
								                .'<tr class="lastline"><td class="firstcol lastcol">',
					'no_results_end'   => '</td></tr>'
								                .'</table>'."\n\n",
				'after' => '</div>',
				'sort_type' => 'basic'
				);


			case 'compact_form':
				// Compact Form settings:
				return array(
					'layout' => 'chicago',		// Temporary dirty hack
					'formstart' => '<div class="fieldset_title"><div class="fieldset_title_right">',

					'title_fmt' => '<div class="fieldset_title_bg" $title_attribs$><span style="float:right">$global_icons$</span>$title$</div></div></div><fieldset>'."\n",
					'no_title_fmt' => '<div class="fieldset_title_bg" $title_attribs$><span style="float:right">$global_icons$</span>&nbsp;</div></div></div><fieldset>'."\n",
					'fieldset_begin' => '<h2 $title_attribs$>$fieldset_title$</h2>',
					'fieldset_end' => '',
					'fieldstart' => '<fieldset $ID$>'."\n",
					'labelstart' => '<div class="label">',
					'labelend' => "</div>\n",
					'labelempty' => '<div class="label"></div>', // so that IE6 aligns DIV.input correcctly
					'inputstart' => '<div class="input">',
					'infostart' => '<div class="info">',
					'inputend' => "</div>\n",
					'fieldend' => "</fieldset>\n\n",
					'buttonsstart' => '<fieldset><div class="input">',
					'buttonsend' => "</div></fieldset>\n\n",
					'formend' => '</fieldset>'."\n",
				);


			case 'Form':
				// Default Form settings:
				return array(
					'layout' => 'chicago',		// Temporary dirty hack
					'formstart' => '',
					'title_fmt' => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
					'no_title_fmt' => '<span style="float:right">$global_icons$</span>'."\n",
					'fieldstart' => '<fieldset $ID$>'."\n",
					'fieldset_begin' => '<div class="fieldset_title"><div class="fieldset_title_right">
																<div class="fieldset_title_bg" $title_attribs$>$fieldset_title$</div></div></div>
																<fieldset $fieldset_attribs$>'."\n",
					'fieldset_end' => '</fieldset>'."\n",
					'labelstart' => '<div class="label">',
					'labelend' => "</div>\n",
					'labelempty' => '<div class="label"></div>', // so that IE6 aligns DIV.input correcctly
					'inputstart' => '<div class="input">',
					'infostart' => '<div class="info">',
					'inputend' => "</div>\n",
					'fieldend' => "</fieldset>\n\n",
					'buttonsstart' => '<fieldset><div class="input">',
					'buttonsend' => "</div></fieldset>\n\n",
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
																	<div class="block_item">',
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
				return parent::get_template( $name, $depth );
		}
	}
}
?>