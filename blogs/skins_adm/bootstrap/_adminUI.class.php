<?php
/**
 * This file implements the Admin UI class for the evo skin.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id: _adminUI.class.php 6339 2014-03-26 10:10:38Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/../_adminUI_general.class.php';


/**
 * We'll use the default AdminUI templates etc.
 *
 * @package admin-skin
 * @subpackage evo
 */
class AdminUI extends AdminUI_general
{

	/**
	 * @var string Skin name, Must be the folder name of skin
	 */
	var $skin_name = 'bootstrap';

	/**
	 * This function should init the templates - like adding Javascript through the {@link add_headline()} method.
	 */
	function init_templates()
	{
		global $Messages;
		// This is included before controller specifc require_css() calls:
		require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
		require_css( 'basic.css', 'rsc_url' ); // Basic styles
		require_css( 'results.css', 'rsc_url' ); // Results/tables styles

		require_js( '#jquery#', 'rsc_url' );
		require_js( 'jquery/jquery.raty.min.js', 'rsc_url' );

		require_js( '#bootstrap#', 'rsc_url' );
		require_css( '#bootstrap_css#', 'rsc_url' );
		require_css( '#bootstrap_theme_css#', 'rsc_url' );
		require_css( 'bootstrap/b2evo.css', 'rsc_url' );

		require_js( '#bootstrap_typeahead#', 'rsc_url' );

		require_css( 'skins_adm/bootstrap/rsc/css/style.css', true );

		// Set bootstrap classes for messages
		$Messages->set_params( array(
				'class_success'  => 'alert alert-success fade in',
				'class_warning'  => 'alert fade in',
				'class_error'    => 'alert alert-danger fade in',
				'class_note'     => 'alert alert-info fade in',
				'before_message' => '<button class="close" data-dismiss="alert">&times;</button>',
			) );

		// Use glyph icons, @see get_icon()
		global $b2evo_icons_type;
		$b2evo_icons_type = 'glyphicons';
	}


	/**
	 * Get the top of the HTML <body>.
	 *
	 * @uses get_page_head()
	 * @return string
	 */
	function get_body_top()
	{
		global $Messages, $app_shortname, $app_version;

		$r = '';

		$r .= $this->get_page_head();

		$r .= '<div id="wrapper"><div class="row">'."\n".
			'<div class="col-md-2">'."\n";

		// Display MAIN menu:
		$r .= '<div class="well" style="padding:0">'."\n".
				$this->get_html_menu()."\n".
				'</div><p class="center">'.$app_shortname.' v <strong>'.$app_version.'</strong></p>'."\n";

		$r .= '</div>'."\n".
			'<div class="col-md-10">'."\n";

		$r .= '<div id="panelbody" class="panelbody">'."\n".
				'<div id="payload">'."\n";

		$r .= $this->get_bloglist_buttons();

		// Display info & error messages
		$r .= $Messages->display( NULL, NULL, false, 'action_messages' );

		return $r;
	}


	/**
	 * Close open div(s).
	 *
	 * @return string
	 */
	function get_body_bottom()
	{
		$r = '';

		$r .= "\n\t\t\t\t</div>";
		$r .= "\n\t\t\t</div>";
		$r .= "\n\t\t</div>";
		$r .= "\n\t</div>";

		$r .= "\n</div>\n";	// Close right col.

		$r .= get_icon( 'pixel' );

		return $r;
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $UserSettings, $current_User;

		$r = '
		<div id="header">';
		if( $UserSettings->get( 'show_breadcrumbs', $current_User->ID ) )
		{
			$r .= $this->breadcrumbpath_get_html( array(
					'before'     => '<div class="breadcrumbpath floatleft"><ul class="breadcrumb">',
					'after'      => '</ul></div><div class="breadcrumbpath floatright">'.$this->page_manual_link.'</div><div class="clear"></div>'."\n",
					'beforeText' => '',
					'beforeEach' => '<li>',
					'afterEach'  => '</li>',
					'beforeSel'  => '<li class="active">',
					'afterSel'   => '</li>',
					'separator'  => '',
				) );
		}
		else
		{
			$r .= $this->get_title_for_titlearea();
		}
		$r .= '
		</div>
		';

		return $r;
	}

	/**
	 * Get a template by name and depth.
	 *
	 * @param string The template name ('main', 'sub').
	 * @return array
	 */
	function get_template( $name, $depth = 0 )
	{
		switch( $name )
		{
			case 'main':
				// main level
				return array(
					'before' => '<ul class="nav nav-list">',
					'after' => '</ul>',
					'beforeEach' => '<li>',
					'afterEach' => '</li>',
					'beforeEachSel' => '<li class="active">',
					'afterEachSel' => '</li>',
					'beforeEachSelWithSub' => '<li class="active">',
					'afterEachSelWithSub' => '</li>',
					'_props' => array(
						'recurse'       => 'yes', // To display the submenus recursively
						'recurse_level' => 2,     // Limit recursion
					),
				);

			case 'menu3':
				// level 3 submenu:
				return array(
							'before' => '<ul class="nav nav-tabs" style="margin:10px 0 20px">',
							'after' => '</ul>',
							'empty' => '',
							'beforeEach' => '<li>',
							'afterEach'  => '</li>',
							'beforeEachSel' => '<li class="active">',
							'afterEachSel' => '</li>',
						);

			case 'CollectionList':
				// Template for a list of Collections (Blogs)
				return array(
						'before' => '<div class="btn-group blogs-menu">',
						'after' => '</div>',
						'select_start' => '<div class="collection_select">',
						'select_end' => '</div>',
						'buttons_start' => '',
						'buttons_end' => '',
						'beforeEach' => '',
						'afterEach' => '',
						'beforeEachSel' => '',
						'afterEachSel' => '',
					);

			case 'Results':
				// Results list:
				return array(
					'page_url' => '', // All generated links will refer to the current page
					'before' => '<div class="results panel panel-default">',
					'content_start' => '<div id="$prefix$ajax_content">',
					'header_start' => '',
						'header_text' => '<div class="center"><ul class="pagination">'
								.'$prev$$first$$list_prev$$list$$list_next$$last$$next$'
							.'</ul></div>',
						'header_text_single' => '',
					'header_end' => '',
					'head_title' => '<div class="panel-heading fieldset_title">$title$<span class="pull-right">$global_icons$</span></div>'."\n",
					'filters_start' => '<div class="filters panel-body form-inline">',
					'filters_end' => '</div>',
					'list_start' => '<div class="table_scroll">'."\n"
					               .'<table class="table table-striped table-bordered table-hover table-condensed" cellspacing="0">'."\n",
						'head_start' => "<thead>\n",
							'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$">',
							'colhead_start_last' => '<th class="lastcol $class$">',
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
					'footer_start' => '',
					'footer_text' => '<div class="center"><ul class="pagination">'
							.'$prev$$first$$list_prev$$list$$list_next$$last$$next$'
						.'</ul></div><div class="center page_size_selector">$page_size$</div>'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '<div class="center page_size_selector">$page_size$</div>',
					'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
						'page_current_template' => '<span><b>$page_num$</b></span>',
						'page_item_before' => '<li>',
						'page_item_after' => '</li>',
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'no_prev_text' => '',
						'no_next_text' => '',
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "\n\n",
					'no_results_start' => '<div class="panel-footer">'."\n",
					'no_results_end'   => '$no_results$</div>'."\n\n",
				'content_end' => '</div>',
				'after' => '</div>',
				'sort_type' => 'basic'
				);

			case 'compact_form':
			case 'Form':
				// Default Form settings:
				return array(
					'layout' => 'fieldset',
					'formstart' => '',
					'title_fmt' => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
					'no_title_fmt' => '<span style="float:right">$global_icons$</span>'."\n",
					'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
															.'<legend class="panel-heading" $title_attribs$>$fieldset_title$</legend><div class="panel-body">'."\n",
					'fieldset_end' => '</div></div></fieldset></div>'."\n",
					'fieldstart' => '<div class="form-group" $ID$>'."\n",
					'labelclass' => 'control-label col-xs-2',
					'labelstart' => '',
					'labelend' => "\n",
					'labelempty' => '<label class="control-label col-xs-2"></label>',
					'inputstart' => '<div class="controls col-xs-10">',
					'infostart' => '<div class="controls-info col-xs-10">',
					'inputend' => "</div>\n",
					'fieldend' => "</div>\n\n",
					'buttonsstart' => '<div class="form-group"><div class="control-buttons col-sm-offset-2 col-xs-10">',
					'buttonsend' => "</div></div>\n\n",
					'customstart' => '<div class="custom_content">',
					'customend' => "</div>\n",
					'note_format' => ' <span class="help-inline">%s</span>',
					'formend' => '',
				);

			case 'block_item':
			case 'dash_item':
				return array(
					'block_start' => '<div class="panel panel-default" id="styled_content_block"><div class="panel-heading"><h4><span style="float:right">$global_icons$</span>$title$</h4></div><div class="panel-body">',
					'block_end' => '</div></div>',
				);

			case 'side_item':
				return array(
					'block_start' => '<div class="panel panel-default"><div class="panel-heading"><h4><span style="float:right">$global_icons$</span>$title$</h4></div><div class="panel-body">',
					'block_end' => '</div></div>',
				);

			case 'user_navigation':
				// The Prev/Next links of users
				return array(
					'block_start'  => '<ul class="pager">',
					'prev_start'   => '<li class="previous">',
					'prev_end'     => '</li>',
					'prev_no_user' => '',
					'back_start'   => '<li>',
					'back_end'     => '</li>',
					'next_start'   => '<li class="next">',
					'next_end'     => '</li>',
					'next_no_user' => '',
					'block_end'    => '</ul>',
				);

			case 'button_classes':
				// Button classes
				return array(
					'button'       => 'btn btn-default btn-xs',
					'button_red'   => 'btn-danger',
					'button_green' => 'btn-success',
					'text'         => 'btn btn-default btn-xs',
					'group'        => 'btn-group',
				);

			case 'table_browse':
				// A browse table for items and comments
				return array(
					'table_start'     => '<div class="row">',
					'full_col_start'  => '<div class="col-md-12">',
					'left_col_start'  => '<div class="col-md-9">',
					'left_col_end'    => '</div>',
					'right_col_start' => '<div class="col-md-3 form-inline">',
					'right_col_end'   => '</div>',
					'table_end'       => '</div>',
				);

			case 'tooltip_plugin':
				// Plugin name for tooltips: 'bubbletip' or 'popover'
				return 'popover';
				break;

			case 'autocomplete_plugin':
				// Plugin name to autocomplete the fields: 'hintbox', 'typeahead'
				return 'typeahead';
				break;

			case 'modal_window_js':
				// JavaScript to init Modals, @see echo_user_ajaxwindow_js()
				return "
var modal_window_js_initialized = false;
/*
 * Build and open madal window
 *
 * @param string HTML content
 * @param string Width value in css format
 * @param boolean TRUE - to use transparent template
 * @param string Title of modal window (Used in bootstrap)
 * @param string|boolean Button to submit a form (Used in bootstrap), FALSE - to hide bottom panel with buttons
 */
function openModalWindow( body_html, width, height, transparent, title, button )
{
	if( typeof width == 'undefined' )
	{
		width = '560px';
	}
	var style_height = '';
	var css_classes = '';
	if( typeof height != 'undefined' && ( height > 0 || height != '' ) )
	{
		if( height.match( /px\$/i ) )
		{
			css_classes = ' modal_fixed_height';
			height = '100%';
		}
		style_height = ';height:' + height;
	}
	var use_buttons = true;
	if( typeof button != 'undefined' && button == false )
	{
		use_buttons = false;
	}

	if( jQuery( '#modal_window' ).length == 0 )
	{ // Build modal window
		var modal_html = '<div id=\"modal_window\" class=\"modal fade' + css_classes + '\" style=\"width:' + width + style_height + '\"><div class=\"modal-dialog\"><div class=\"modal-content\">';
		if( typeof title != 'undefined' && title != '' )
		{
			modal_html += '<div class=\"modal-header\">' +
					'<button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-hidden=\"true\">&times;</button>' +
					'<h4>' + title + '</h4>' +
				'</div>';
		}
		modal_html += '<div class=\"modal-body\">' + body_html + '</div>';

		if( use_buttons )
		{
			modal_html += '<div class=\"modal-footer\">' +
					'<button class=\"btn btn-default\" data-dismiss=\"modal\" aria-hidden=\"true\">".TS_( 'Close' )."</button>';
			if( button != '' )
			{
				modal_html += '<button class=\"btn btn-primary\" type=\"submit\">' + button + '</button>';
			}
			modal_html += '</div>';
		}
		modal_html += '</div></div></div>';
		jQuery( 'body' ).append( modal_html );
	}
	else
	{ // Use existing modal window
		jQuery( '#modal_window .modal-body' ).html( body_html );
	}

	if( use_buttons )
	{
		// Remove these elements, they are displayed as title and button of modal window
		jQuery( '#modal_window legend' ).remove();
		jQuery( '#modal_window #close_button' ).remove();

		if( jQuery( '#modal_window input[type=submit]' ).length == 0 )
		{ // Hide a submit button in the fotter if real submit input doesn't exist
			jQuery( '#modal_window .modal-footer button[type=submit]' ).hide();
		}
		else
		{
			jQuery( '#modal_window input[type=submit]' ).hide();
			jQuery( '#modal_window .modal-footer button[type=submit]' ).show();
		}

		jQuery( '#modal_window' ).change( function()
		{ // Find the submit inputs when html is changed
			var input_submit = jQuery( this ).find( 'input[type=submit]' )
			if( input_submit.length > 0 )
			{ // Hide a real submit input and Show button of footer
				input_submit.hide();
				jQuery( '#modal_window .modal-footer button[type=submit]' ).show();
			}
			else
			{ // Hide button of footer if real submit input doesn't exist
				jQuery( '#modal_window .modal-footer button[type=submit]' ).hide();
			}
		} );

		jQuery( '#modal_window .modal-footer button[type=submit]' ).click( function()
		{ // Copy a click event from real submit input to button of footer
			jQuery( '#modal_window input[type=submit]' ).click();
		} );
	}

	// Init modal window and show
	var options = {};
	if( modal_window_js_initialized )
	{
		options = 'show';
	}
	jQuery( '#modal_window' ).modal( options );

	jQuery( '#modal_window').on( 'hidden', function ()
	{ // Remove modal window on hide event to draw new window in next time with new title and button
		jQuery( this ).remove();
	} );

	modal_window_js_initialized = true;
}
";
				break;

			default:
				// Delegate to parent class:
				return parent::get_template( $name, $depth );
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
				return 'fbfbfb';
				break;
		}
		debug_die( 'unknown color' );
	}


	/**
	 * Display the start of a payload block
	 *
	 * Note: it is possible to display several payload blocks on a single page.
	 *       The first block uses the "sub" template, the others "block".
	 *
	 * @see disp_payload_end()
	 */
	function disp_payload_begin( $params = array() )
	{
		parent::disp_payload_begin( array(
				'display_menu2' => false,
			) );
	}


	/**
	 * Returns list of buttons for available Collections (aka Blogs) to work on.
	 *
	 * @param string Title
	 * @return string HTML
	 */
	function get_bloglist_buttons( $title = '' )
	{
		global $blog;

		$max_buttons = 7;

		if( empty( $this->coll_list_permname ) )
		{	// We have not requested a list of blogs to be displayed
			return;
		}

		// Prepare url params:
		$url_params = '?';
		foreach( $this->coll_list_url_params as $name => $value )
		{
			$url_params .= $name.'='.$value.'&amp;';
		}

		$template = $this->get_template( 'CollectionList' );

		$BlogCache = & get_BlogCache();

		$blog_array = $BlogCache->load_user_blogs( $this->coll_list_permname, $this->coll_list_permlevel );

		$buttons = '';
		$select_options = '';
		$not_favorite_blogs = false;

		foreach( $blog_array as $l_blog_ID )
		{ // Loop through all blogs that match the requested permission:

			$l_Blog = & $BlogCache->get_by_ID( $l_blog_ID );

			if( $l_Blog->get( 'favorite' ) || $l_blog_ID == $blog )
			{ // If blog is favorute OR current blog, Add blog as a button:
				$buttons .= $template[ $l_blog_ID == $blog ? 'beforeEachSel' : 'beforeEach' ];

				$buttons .= '<a href="'.$url_params.'blog='.$l_blog_ID
							.'" class="btn btn-default'.( $l_blog_ID == $blog ? ' active' : '' ).'"';

				if( !is_null($this->coll_list_onclick) )
				{	// We want to include an onclick attribute:
					$buttons .= ' onclick="'.sprintf( $this->coll_list_onclick, $l_blog_ID ).'"';
				}

				$buttons .= '>'.$l_Blog->dget( 'shortname', 'htmlbody' ).'</a> ';

				if( $l_blog_ID == $blog )
				{
					$buttons .= $template['afterEachSel'];
				}
				else
				{
					$buttons .= $template['afterEach'];
				}
			}

			if( !$l_Blog->get( 'favorite' ) )
			{ // If blog is not favorute, Add it into the select list:
				$not_favorite_blogs = true;
				$select_options .= '<li>';
				if( $l_blog_ID == $blog )
				{
					//$select_options .= ' selected="selected"';
				}
				$select_options .= '<a href="'.$url_params.'blog='.$l_blog_ID.'">'
					.$l_Blog->dget( 'shortname', 'formvalue' ).'</a></li>';
			}
		}

		$r = $template['before'];

		$r .= $title;

		if( !empty( $this->coll_list_all_title ) )
		{ // We want to add an "all" button
			$r .= $template[ $blog == 0 ? 'beforeEachSel' : 'beforeEach' ];
			$r .= '<a href="'.$this->coll_list_all_url
						.'" class="btn btn-default'.( $blog == 0 ? ' active' : '' ).'">'
						.$this->coll_list_all_title.'</a> ';
			$r .= $template[ $blog == 0 ? 'afterEachSel' : 'afterEach' ];
		}

		$r .= $template['buttons_start'];
		$r .= $buttons;
		$r .= $template['buttons_end'];


		//$r .= $template['select_start'];
		if( $not_favorite_blogs )
		{ // Display select list with not favorite blogs
			$r .= $template['after'].$template['before']
				.'<a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'.T_('More')
				.'<span class="caret"></span></a>'
				.'<ul class="dropdown-menu">'
				.$select_options
				.'</ul>';
		}
		//$r .= $template['select_end'];


		$r .= $template['after'];

		return $r;
	}


}

?>