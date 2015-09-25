<?php
/**
 * This file implements the Admin UI class for the evo skin.
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
		global $Messages, $debug, $Hit;

		// This is included before controller specifc require_css() calls:
		require_css( 'results.css', 'rsc_url' ); // Results/tables styles

		require_js( '#jquery#', 'rsc_url' );
		require_js( 'jquery/jquery.raty.min.js', 'rsc_url' );

		require_js( '#bootstrap#', 'rsc_url' );
		require_css( '#bootstrap_css#', 'rsc_url' );
		// require_css( '#bootstrap_theme_css#', 'rsc_url' );
		require_js( '#bootstrap_typeahead#', 'rsc_url' );

		if( $debug )
		{	// Use readable CSS:
			// rsc/less/bootstrap-basic_styles.less
			// rsc/less/bootstrap-basic.less
			// rsc/less/bootstrap-evoskins.less
			require_css( 'bootstrap-backoffice-b2evo_base.bundle.css', 'rsc_url' ); // Concatenation of the above
		}
		else
		{	// Use minified CSS:
			require_css( 'bootstrap-backoffice-b2evo_base.bmin.css', 'rsc_url' ); // Concatenation + Minifaction of the above
		}
		
		// Make sure standard CSS is called ahead of custom CSS generated below:
		if( $debug )
		{	// Use readable CSS:
			require_css( 'skins_adm/bootstrap/rsc/css/style.css', 'relative' );	// Relative to <base> tag (current skin folder)
		}
		else
		{	// Use minified CSS:
			require_css( 'skins_adm/bootstrap/rsc/css/style.min.css', 'relative' );	// Relative to <base> tag (current skin folder)
		}

		// Set bootstrap css classes for messages
		$Messages->set_params( array(
				'class_outerdiv' => 'action_messages container-fluid',
				'class_success'  => 'alert alert-dismissible alert-success fade in',
				'class_warning'  => 'alert alert-dismissible alert-warning fade in',
				'class_error'    => 'alert alert-dismissible alert-danger fade in',
				'class_note'     => 'alert alert-dismissible alert-info fade in',
				'before_message' => '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>',
			) );

		// Initialize font-awesome icons and use them as a priority over the glyphicons, @see get_icon()
		init_fontawesome_icons( 'fontawesome-glyphicons' );

		if( $Hit->get_browser_version() > 0 && $Hit->is_IE( 9, '<' ) )
		{	// IE < 9
			$Messages->add( T_('Your web browser is too old. For this site to work correctly, we recommend you use a more recent browser.'), 'note' );
			if( $debug )
			{
				$Messages->add( 'User Agent: '.$Hit->get_user_agent(), 'note' );
			}
		}
	}


	/**
	 * Get the top of the HTML <body>.
	 *
	 * @uses get_page_head()
	 * @return string
	 */
	function get_body_top()
	{
		$r = $this->get_page_head();

		// Blog selector
		$r .= $this->get_bloglist_buttons();

		return $r;
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $admin_url, $Settings;

		$r = '<nav class="navbar level1 navbar-inverse navbar-static-top">
			<div class="container-fluid">
				 <!-- Brand and toggle get grouped for better mobile display -->
				 <div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#b2evo-top-navbar">
							 <span class="sr-only">Toggle navigation</span>
							 <span class="icon-bar"></span>
							 <span class="icon-bar"></span>
							 <span class="icon-bar"></span>
						</button>
						<a class="navbar-brand" href="'.$admin_url.'?ctrl=dashboard"'
								.( $Settings->get( 'site_color' ) != '' ? 'style="color:'.$Settings->get( 'site_color' ).'"' : '' ).'>'
							.$Settings->get( 'site_code' )
						.'</a>
				 </div>

				 <!-- Collect the nav links, forms, and other content for toggling -->
				 <div class="collapse navbar-collapse" id="b2evo-top-navbar">
						'.$this->get_html_menu().'
						<ul class="nav navbar-nav navbar-right">
							 <li>'.$this->page_manual_link.'</li>
						</ul>
				 </div><!-- /.navbar-collapse -->
			</div><!-- /.container-fluid -->
		</nav>';

		return $r;
	}


	/**
	 * Dsiplay the top of the HTML <body>...
	 *
	 * Typically includes title, menu, messages, etc.
	 *
	 * @param boolean Whether or not to display messages.
	 */
	function disp_body_top( $display_messages = true )
	{
		global $Messages;

		parent::disp_body_top( $display_messages );

		parent::disp_payload_begin();

		// Display info & error messages
		$Messages->display();

		echo '<div class="container-fluid page-content">'."\n\t"
				.'<div class="row">'."\n\t\t"
			.'<div class="col-md-12">'."\n";
	}


	/**
	 * Display body bottom, debug info and close </html>
	 */
	function disp_global_footer()
	{
		echo "\n\t\t</div>"
				."\n\t</div>"
			."\n</div>";

		parent::disp_payload_end();

		parent::disp_global_footer();
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
		// Nothing display here, because all already is printed in $this->disp_body_top()
	}


	/**
	 * Display the end of a payload block
	 *
	 * Note: it is possible to display several payload blocks on a single page.
	 *       The first block uses the "sub" template, the others "block".
	 * @see disp_payload_begin()
	 */
	function disp_payload_end()
	{
		// Nothing display here, because all already is printed in $this->disp_global_footer()
	}


	/**
	 * Get the footer text
	 *
	 * @return string
	 */
	function get_footer_contents()
	{
		global $app_footer_text, $copyright_text;

		return '<footer class="footer"><div class="container"><p class="text-muted text-center">'.$app_footer_text.' &ndash; '.$copyright_text."</p></div></footer>\n\n";
	}


	/**
	 * Get a template by name and depth.
	 *
	 * @param string The template name ('main', 'sub').
	 * @param integer Nesting level (start at 0)
	 * @param boolean TRUE to die on unknown template name
	 * @return array
	 */
	function get_template( $name, $depth = 0, $die_on_unknown = false )
	{
		switch( $name )
		{
			case 'main':
				// main level
				return array(
					'before' => '<ul class="nav navbar-nav">',
					'after' => '</ul>',
					'beforeEach' => '<li>',
					'afterEach' => '</li>',
					'beforeEachSel' => '<li class="active">',
					'afterEachSel' => ' <span class="sr-only">(current)</span></li>',
					'beforeEachSelWithSub' => '<li class="active">',
					'afterEachSelWithSub' => '</li>',
				);

			case 'sub':
				// a payload block with embedded submenu
				return array(
						'before' => '<div class="container-fluid level2">'."\n"
									.'<nav>'."\n"
								.'<ul class="nav nav-tabs">'."\n",
						'after' => '</ul>'."\n"
										.'</nav>'."\n"
									.'</div>'."\n"
									.'<div class="container-fluid pull-right">$global_icons$</div>',
						'empty' => '<div class="container-fluid pull-right">$global_icons$</div>',
						'beforeEach'    => '<li role="presentation">',
						'afterEach'     => '</li>',
						'beforeEachSel' => '<li role="presentation" class="active">',
						'afterEachSel'  => '</li>',
						'beforeEachGrpLast'    => '<li role="presentation" class="grplast">',
						'afterEachGrpLast'     => '</li>',
						'beforeEachSelGrpLast' => '<li role="presentation" class="grplast active">',
						'afterEachSelGrpLast'  => '</li>',
						'end' => '', // used to end payload block that opened submenu
					);

			case 'menu3':
				// level 3 submenu:
				return array(
						'before' => '<div class="container-fluid level3">'."\n"
										.'<nav>'."\n"
									.'<ul class="nav nav-pills">'."\n",
						'after' => '</ul>'."\n"
									.'</nav>'."\n"
								.'</div>'."\n",
						'empty' => '',
						'beforeEach' => '<li role="presentation">',
						'afterEach'  => '</li>',
						'beforeEachSel' => '<li role="presentation" class="active">',
						'afterEachSel' => '</li>',
					);

			case 'CollectionList':
				// Template for a list of Collections (Blogs)
				return array(
						'before' => '<div class="container-fluid coll-selector"><nav><div class="btn-group">',
						'after' => '</div>$button_add_blog$</nav></div>',
						'select_start' => '<div class="btn-group" role="group">',
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
					'head_title' => '<div class="panel-heading fieldset_title"><span class="pull-right">$global_icons$</span><h3 class="panel-title">$title$</h3></div>'."\n",
					'global_icons_class' => 'btn btn-default btn-sm',
					'filters_start'        => '<div class="filters panel-body">',
					'filters_end'          => '</div>',
					'filter_button_class'  => 'btn-sm btn-info',
					'filter_button_before' => '<div class="form-group pull-right">',
					'filter_button_after'  => '</div>',
					'messages_start' => '<div class="messages form-inline">',
					'messages_end' => '</div>',
					'messages_separator' => '<br />',
					'list_start' => '<div class="table_scroll">'."\n"
					               .'<table class="table table-striped table-bordered table-hover table-condensed $list_class$" cellspacing="0" $list_attrib$>'."\n",
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
					'footer_start' => '<div class="panel-footer">',
					'footer_text' => '<div class="center"><ul class="pagination">'
							.'$prev$$first$$list_prev$$list$$list_next$$last$$next$'
						.'</ul></div><div class="center page_size_selector">$page_size$</div>'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '<div class="center page_size_selector">$page_size$</div>',
					'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
						'page_current_template' => '<span>$page_num$</span>',
						'page_item_before' => '<li>',
						'page_item_after' => '</li>',
						'page_item_current_before' => '<li class="active">',
						'page_item_current_after' => '</li>',
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'no_prev_text' => '',
						'no_next_text' => '',
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results_start' => '<div class="panel-footer">'."\n",
					'no_results_end'   => '$no_results$</div>'."\n\n",
				'content_end' => '</div>',
				'after' => '</div>',
				'sort_type' => 'basic'
				);

			case 'blockspan_form':
				// Form settings for filter area:
				return array(
					'layout'         => 'blockspan',
					'formclass'      => 'form-inline',
					'formstart'      => '',
					'formend'        => '',
					'title_fmt'      => '$title$'."\n",
					'no_title_fmt'   => '',
					'fieldset_begin' => '<fieldset $fieldset_attribs$>'."\n"
																.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
					'fieldset_end'   => '</fieldset>'."\n",
					'fieldstart'     => '<div class="form-group form-group-sm" $ID$>'."\n",
					'fieldend'       => "</div>\n\n",
					'labelclass'     => 'control-label',
					'labelstart'     => '',
					'labelend'       => "\n",
					'labelempty'     => '<label></label>',
					'inputstart'     => '',
					'inputend'       => "\n",
					'infostart'      => '<div class="form-control-static">',
					'infoend'        => "</div>\n",
					'buttonsstart'   => '<div class="panel-footer control-buttons"><div class="col-sm-offset-3 col-sm-9">',
					'buttonsend'     => '</div></div>'."\n\n",
					'customstart'    => '<div class="custom_content">',
					'customend'      => "</div>\n",
					'note_format'    => ' <span class="help-inline">%s</span>',
					// Additional params depending on field type:
					// - checkbox
					'fieldstart_checkbox'    => '<div class="form-group form-group-sm checkbox" $ID$>'."\n",
					'fieldend_checkbox'      => "</div>\n\n",
					'inputclass_checkbox'    => '',
					'inputstart_checkbox'    => '',
					'inputend_checkbox'      => "\n",
					'checkbox_newline_start' => '',
					'checkbox_newline_end'   => "\n",
					'checkbox_basic_start'   => '<div class="checkbox"><label>',
					'checkbox_basic_end'     => "</label></div>\n",
					// - radio
					'inputclass_radio'       => '',
					'radio_label_format'     => '$radio_option_label$',
					'radio_newline_start'    => '',
					'radio_newline_end'      => "\n",
					'radio_oneline_start'    => '',
					'radio_oneline_end'      => "\n",
				);

			case 'compact_form':
				// Default Form settings:
				return array(
					'layout'         => 'fieldset',
					'formclass'      => 'form-horizontal',
					'formstart'      => '<div class="panel panel-default $formstart_class$">'."\n",
					'formend'        => '</div></div>',
					'title_fmt'      => '<div class="panel-heading"><span class="pull-right">$global_icons$</span><h3 class="panel-title">$title$</h3></div><div class="panel-body $class$">'."\n",
					'no_title_fmt'   => '<div class="panel-body $class$"><span class="pull-right">$global_icons$</span><div class="clear"></div>'."\n",
					'no_title_no_icons_fmt' => '<div class="panel-body $class$">'."\n",
					'global_icons_class' => 'btn btn-default btn-sm',
					'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
															.'<legend class="panel-heading" $title_attribs$><h3 class="panel-title">$fieldset_title$</h3></legend><div class="panel-body $class$">'."\n",
					'fieldset_end'   => '</div></div></fieldset></div>'."\n",
					'fieldstart'     => '<div class="form-group" $ID$>'."\n",
					'fieldend'       => "</div>\n\n",
					'labelclass'     => 'control-label col-sm-3',
					'labelstart'     => '',
					'labelend'       => "\n",
					'labelempty'     => '<label class="control-label col-sm-3"></label>',
					'inputstart'     => '<div class="controls col-sm-9">',
					'inputend'       => "</div>\n",
					'infostart'      => '<div class="controls col-sm-9"><div class="form-control-static">',
					'infoend'        => "</div></div>\n",
					'buttonsstart'   => '<div class="panel-footer control-buttons"><div class="col-sm-offset-3 col-sm-9">',
					'buttonsend'     => '</div></div>'."\n\n",
					'customstart'    => '<div class="custom_content">',
					'customend'      => "</div>\n",
					'note_format'    => ' <span class="help-inline">%s</span>',
					// Additional params depending on field type:
					// - checkbox
					'inputclass_checkbox'    => '',
					'inputstart_checkbox'    => '<div class="controls col-sm-9"><div class="checkbox"><label>',
					'inputend_checkbox'      => "</label></div></div>\n",
					'checkbox_newline_start' => '<div class="checkbox">',
					'checkbox_newline_end'   => "</div>\n",
					'checkbox_basic_start'   => '<div class="checkbox"><label>',
					'checkbox_basic_end'     => "</label></div>\n",
					// - radio
					'fieldstart_radio'       => '<div class="form-group radio-group" $ID$>'."\n",
					'fieldend_radio'         => "</div>\n\n",
					'inputclass_radio'       => '',
					'radio_label_format'     => '$radio_option_label$',
					'radio_newline_start'    => '<div class="radio"><label>',
					'radio_newline_end'      => "</label></div>\n",
					'radio_oneline_start'    => '<label class="radio-inline">',
					'radio_oneline_end'      => "</label>\n",
				);

			case 'Form':
				// Default Form settings:
				return array(
					'layout'         => 'fieldset',
					'formclass'      => 'form-horizontal',
					'formstart'      => '',
					'formend'        => '',
					'title_fmt'      => '<span class="global_icons">$global_icons$</span><h2 class="page-title">$title$</h2>'."\n",
					'no_title_fmt'   => '<span class="global_icons no_title">$global_icons$</span><div class="clear"></div>'."\n",
					'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
															.'<legend class="panel-heading" $title_attribs$><h3 class="panel-title">$fieldset_title$</h3></legend><div class="panel-body $class$">'."\n",
					'fieldset_end'   => '</div></div></fieldset></div>'."\n",
					'fieldstart'     => '<div class="form-group" $ID$>'."\n",
					'fieldend'       => "</div>\n\n",
					'labelclass'     => 'control-label col-sm-3',
					'labelstart'     => '',
					'labelend'       => "\n",
					'labelempty'     => '<label class="control-label col-sm-3"></label>',
					'inputstart'     => '<div class="controls col-sm-9">',
					'inputend'       => "</div>\n",
					'infostart'      => '<div class="controls col-sm-9"><div class="form-control-static">',
					'infoend'        => "</div></div>\n",
					'buttonsstart'   => '<div class="form-group"><div class="control-buttons col-sm-offset-3 col-sm-9">',
					'buttonsend'     => "</div></div>\n\n",
					'customstart'    => '<div class="custom_content">',
					'customend'      => "</div>\n",
					'note_format'    => ' <span class="help-inline">%s</span>',
					// Additional params depending on field type:
					// - checkbox
					'inputclass_checkbox'    => '',
					'inputstart_checkbox'    => '<div class="controls col-sm-9"><div class="checkbox"><label>',
					'inputend_checkbox'      => "</label></div></div>\n",
					'checkbox_newline_start' => '<div class="checkbox">',
					'checkbox_newline_end'   => "</div>\n",
					'checkbox_basic_start'   => '<div class="checkbox"><label>',
					'checkbox_basic_end'     => "</label></div>\n",
					// - radio
					'fieldstart_radio'       => '<div class="form-group radio-group" $ID$>'."\n",
					'fieldend_radio'         => "</div>\n\n",
					'inputclass_radio'       => '',
					'radio_label_format'     => '$radio_option_label$',
					'radio_newline_start'    => '<div class="radio"><label>',
					'radio_newline_end'      => "</label></div>\n",
					'radio_oneline_start'    => '<label class="radio-inline">',
					'radio_oneline_end'      => "</label>\n",
				);

			case 'linespan_form':
				// Linespan form:
				return array(
					'layout'         => 'linespan',
					'formclass'      => 'form-horizontal',
					'formstart'      => '',
					'formend'        => '',
					'title_fmt'      => '<span class="pull-right">$global_icons$</span><h2 class="page-title">$title$</h2>'."\n",
					'no_title_fmt'   => '<span class="pull-right">$global_icons$</span>'."\n",
					'fieldset_begin' => '<div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
															.'<legend class="panel-heading" $title_attribs$><h3 class="panel-title">$fieldset_title$</h3></legend><div class="panel-body $class$">'."\n",
					'fieldset_end'   => '</div></div></fieldset></div>'."\n",
					'fieldstart'     => '<div class="form-group" $ID$>'."\n",
					'fieldend'       => "</div>\n\n",
					'labelclass'     => '',
					'labelstart'     => '',
					'labelend'       => "\n",
					'labelempty'     => '',
					'inputstart'     => '<div class="controls">',
					'inputend'       => "</div>\n",
					'infostart'      => '<div class="controls"><div class="form-control-static">',
					'infoend'        => "</div></div>\n",
					'buttonsstart'   => '<div class="form-group"><div class="control-buttons">',
					'buttonsend'     => "</div></div>\n\n",
					'customstart'    => '<div class="custom_content">',
					'customend'      => "</div>\n",
					'note_format'    => ' <span class="help-inline">%s</span>',
					// Additional params depending on field type:
					// - checkbox
					'inputclass_checkbox'    => '',
					'inputstart_checkbox'    => '<div class="controls"><div class="checkbox"><label>',
					'inputend_checkbox'      => "</label></div></div>\n",
					'checkbox_newline_start' => '<div class="checkbox">',
					'checkbox_newline_end'   => "</div>\n",
					'checkbox_basic_start'   => '<div class="checkbox"><label>',
					'checkbox_basic_end'     => "</label></div>\n",
					// - radio
					'fieldstart_radio'       => '',
					'fieldend_radio'         => '',
					'inputstart_radio'       => '<div class="controls">',
					'inputend_radio'         => "</div>\n",
					'inputclass_radio'       => '',
					'radio_label_format'     => '$radio_option_label$',
					'radio_newline_start'    => '<div class="radio"><label>',
					'radio_newline_end'      => "</label></div>\n",
					'radio_oneline_start'    => '<label class="radio-inline">',
					'radio_oneline_end'      => "</label>\n",
				);

			case 'file_browser':
				return array(
					'block_start' => '<div class="panel panel-default file_browser"><div class="panel-heading"><span class="pull-right">$global_icons$</span><h3 class="panel-title">$title$</h3></div><div class="panel-body">',
					'block_end'   => '</div></div>',
					'global_icons_class' => 'btn btn-default btn-sm',
				);

			case 'block_item':
			case 'dash_item':
				return array(
					'block_start' => '<div class="panel panel-default evo_content_block" id="styled_content_block"><div class="panel-heading"><span class="pull-right">$global_icons$</span><h3 class="panel-title">$title$</h3></div><div class="panel-body">',
					'block_end'   => '</div></div>',
					'global_icons_class' => 'btn btn-default btn-sm',
				);

			case 'side_item':
				return array(
					'block_start' => '<div class="panel panel-default"><div class="panel-heading"><span class="pull-right">$global_icons$</span><h3 class="panel-title">$title$</h3></div><div class="panel-body">',
					'block_end'   => '</div></div>',
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
					'button'       => 'btn btn-default',
					'button_red'   => 'btn-danger',
					'button_green' => 'btn-success',
					'text'         => 'btn btn-default',
					'text_primary' => 'btn btn-primary',
					'text_success' => 'btn btn-success',
					'text_danger'  => 'btn btn-danger',
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

			case 'modal_window_js_func':
				// JavaScript function to initialize Modal windows, @see echo_user_ajaxwindow_js()
				return 'echo_modalwindow_js_bootstrap';
				break;

			case 'plugin_template':
				// Template for plugins
				return array(
						'toolbar_before'       => '<div class="btn-toolbar $toolbar_class$" role="toolbar">',
						'toolbar_after'        => '</div>',
						'toolbar_title_before' => '<div class="btn-toolbar-title">',
						'toolbar_title_after'  => '</div>',
						'toolbar_group_before' => '<div class="btn-group btn-group-xs" role="group">',
						'toolbar_group_after'  => '</div>',
						'toolbar_button_class' => 'btn btn-default',
					);

			case 'pagination':
				// Pagination, @see echo_comment_pages()
				return array(
						'list_start' => '<div class="center"><ul class="pagination">',
						'list_end'   => '</ul></div>',
						'prev_text'  => T_('Previous'),
						'next_text'  => T_('Next'),
						'pages_text' => '',
						'page_before'         => '<li>',
						'page_after'          => '</li>',
						'page_current_before' => '<li class="active"><span>',
						'page_current_after'  => '</span></li>',
					);
				break;

			case 'blog_base.css':
				// File name of blog_base.css that are used on several back-office pages
				return 'bootstrap-blog_base.css';
				break;

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
				return 'fbfbfb';
				break;
		}
		debug_die( 'unknown color' );
	}


	/**
	 * Returns list of buttons for available Collections (aka Blogs) to work on.
	 *
	 * @param string Title
	 * @return string HTML
	 */
	function get_bloglist_buttons( $title = '' )
	{
		global $blog, $current_User, $admin_url;

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


		if( $not_favorite_blogs )
		{ // Display select list with not favorite blogs
			$r .= $template['select_start']
				.'<a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'.T_('Other')
				.'<span class="caret"></span></a>'
				.'<ul class="dropdown-menu">'
				.$select_options
				.'</ul>'
				.$template['select_end'];
		}

		// Button to add new collection
		if( is_logged_in() && $current_User->check_perm( 'blogs', 'create' ) )
		{ // Display a button to add new collection if current user has a permission
			$button_add_blog = '<a href="'.$admin_url.'?ctrl=collections&amp;action=new" class="btn btn-default" title="'.T_('New Collection').'"><span class="fa fa-plus"></span></a>';
		}
		else
		{ // No permission to add new collection
			$button_add_blog = '';
		}

		$r .= str_replace( '$button_add_blog$', $button_add_blog, $template['after'] );

		return $r;
	}


}

?>