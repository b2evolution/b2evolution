<?php
/**
 * This file implements the Fast Form handling class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004 by PROGIDISTRI - {@link http://progidistri.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 *
 * @todo Provide buffering of whole Form to be able to add onsubmit-JS to enable/disabled
 *       (group) checkboxes again and other useful stuff.
 *
 * NOTE: we use an member array ($_common_params) for exchanging params between functions.
 * This will most probably cause problems, when nesting inputs. This should be refactored
 * to use a field_name-based member array. (blueyed)
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/ui/_uiwidget.class.php', 'Table' );
load_class( '_core/ui/_uiwidget.class.php', 'Widget' );

/**
 * Form class
 *
 * @package evocore
 */
class Form extends Widget
{
	/**
	 * Output HTML or just return it?
	 * @var boolean
	 */
	var $output = true;

	/**
	 * Remember number of open tags that need to be handled in {@link end_form()}.
	 *
	 * @var array
	 */
	var $_opentags = array( 'fieldset' => 0 );

	/**
	 * Suffix for LABELs.
	 * @var string
	 */
	var $label_suffix = ':';


	/**
	 * Common params shared between methods.
	 *
	 * These can all be used with the $field_params argument of the functions.
	 *
	 * - 'note': The note associated with the field.
	 * - 'note_format': The format of the note. %s gets replaced by the note.
	 * - 'label': The label for the field.
	 * - 'required': is the element required to be filled/checked? This will add a visual hint (boolean; default: false)
	 *
	 * @see handle_common_params()
	 * @var array
	 */
	var $_common_params = array();

	/**
	 * This is a buffer for hidden fields. We'll display all of them just before the end of form </form>. This avoids having them caught in between table rows.
	 *
	 * @var string
	 */
	var $hiddens = array();

	/**
	 * Do we need to add javascript for check/uncheck all functionality
	 */
	var $check_all = false;

	/**
	 * Additional Javascript to append to the form, in {@link Form::end_form()}.
	 *
	 * @access protected
	 * @var array
	 */
	var $append_javascript = array();

	/**
	 * Display param errors with fields, appended to the note?
	 * @var boolean
	 */
	var $disp_param_err_messages_with_fields = true;

	/**
	 * Stack of previous used layouts
	 * @see Form::switch_layout()
	 * @var array
	 */
	var $saved_layouts;
	var $saved_templates;


	/**
	 * A list of input field names that have been used in the form.
	 * This gets used in {@link hiddens_by_key()} to exclude those.
	 * @var array
	 */
	var $included_input_field_names = array();


	/**
	 * Indicates if the form is ended
	 * @var boolean
	 */
	var $_form_ended = false;


	/**
	 * Force all checkboxes to inline style where checkbox element is displayed before label
	 * (Example usage is in filterset forms of the Results class)
	 * @var boolean
	 */
	var $force_checkboxes_to_inline;


	/**
	 * Form type:
	 *     - 'form' - use standard form tag,
	 *     - 'div'  - use <div> tag instead of <form>
	 *
	 * @var string
	 */
	var $form_type;


	/**
	 * Constructor
	 *
	 * @param string the action destination of the form (NULL for pagenow)
	 * @param string the name of the form (will be used as an ID)
	 * @param string the method used to send data ("post" (Default), "get")
	 * @param string the form layout : 'fieldset', 'table' or '' (NULL means: if there is an {@link $AdminUI} object get it from there, otherwise use 'fieldset')
	 * @param string Form encoding ("application/x-www-form-urlencoded" (default), "multipart/form-data" (uploads))
	 */
	function __construct( $form_action = NULL, $form_name = '', $form_method = 'post', $layout = NULL, $enctype = '', $form_type = 'form' )
	{
		global $pagenow;

		$this->form_name = $form_name;
		$this->form_action = (is_null($form_action) ? $pagenow : $form_action );
		$this->form_method = $form_method;
		$this->enctype = $enctype;
		$this->form_type = $form_type;

		// Get form template depending on current skin
		$template = $this->get_template( $layout, true );

		$this->saved_layouts = array( $layout );
		$this->saved_templates = array( $template );
		$this->switch_layout( NULL );	// "restore" saved layout.


		// Add any GET params from $form_action as hidden inputs (GET forms only)
		// Otherwise those params would be overwritten by the form submit and lost.
		if( strpos( $this->form_action, '?' ) !== false && $this->form_method == 'get' )
		{
			$pos_args = strpos( $this->form_action, '?' );
			$query_str = substr( $this->form_action, $pos_args + 1 );
			// Split args by "&" (but leave "&amp;" alone).
			$query_args = preg_split( '~&(?!amp;)~', $query_str, -1, PREG_SPLIT_NO_EMPTY );
			foreach( $query_args as $query_arg )
			{
				list( $field_name, $field_value ) = explode( '=', $query_arg, 2 );
				// Remember all pairs, and add them in end_form (so that used fieldnames can be skipped):
				$this->possible_hiddens_from_action[] = array( $field_name, $field_value );
			}
			// Remove params.
			$this->form_action = substr( $form_action, 0, $pos_args );
		}
	}


	/**
	 * Get template of the current skin
	 *
	 * @param string Layout name
	 * @param boolean TRUE to use default template if the selected layout is not defined for current skin
	 *                FALSE to return NULL
	 * @return array|NULL Template params
	 */
	function get_template( & $layout, $use_default_template = false )
	{
		global $AdminUI, $Skin;

		$template = NULL;

		if( is_object( $AdminUI ) )
		{
			if( $layout == 'fieldset' || ( $use_default_template && ( empty( $layout ) || $layout == 'split' || $layout == 'none' ) ) )
			{ // Get default skin setting
				$template = $AdminUI->get_template( 'Form', 0, $use_default_template );
			}
			else
			{ // Get special form skin
				$template = $AdminUI->get_template( $layout.'_form', 0, $use_default_template );
			}
			if( is_array( $template ) && isset( $template['layout'] ) )
			{ // Get layout name from template
				$layout = $template['layout'];
			}
		}
		else
		{ // This happens for comment forms & login screen for example...
			$template_is_empty = true;
			if( is_object( $Skin ) )
			{ // Get skin setting:
				if( $layout == 'fieldset' || ( $use_default_template && ( empty( $layout ) || $layout == 'split' || $layout == 'none' ) ) )
				{ // Get default skin setting:
					$template = $Skin->get_template( 'Form' );
				}
				else
				{
					$template = $Skin->get_template( $layout.'_form' );
				}
				$template_is_empty = empty( $template );
				if( !$template_is_empty )
				{
					$layout = $template['layout'];
				}
			}

			if( $template_is_empty && $use_default_template )
			{
				$template = array(
					'layout'         => 'fieldset',
					'formclass'      => '',
					'formstart'      => '<div>',// required before (no_)title_fmt for validation
					'title_fmt'      => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
					'no_title_fmt'   => '<span style="float:right">$global_icons$</span>'."\n",
					'no_title_no_actions_fmt' => "\n",
					'fieldset_begin' => '<fieldset $fieldset_attribs$>'."\n"
												.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
					'fieldset_end'   => '</fieldset>'."\n",
					'fieldstart'     => '<fieldset$ID$>'."\n",
					'labelclass'     => '',
					'labelstart'     => '<div class="label">',
					'labelend'       => "</div>\n",
					'labelempty'     => '<div class="label"></div>', // so that IE6 aligns DIV.input correcctly
					'inputstart'     => '<div class="input">',
					'inputend'       => "</div>\n",
					'infostart'      => '<div class="info">',
					'infoend'        => "</div>\n",
					'fieldend'       => "</fieldset>\n\n",
					'buttonsstart'   => '<fieldset><div class="label"></div><div class="input">', // DIV.label for IE6
					'buttonsend'     => "</div></fieldset>\n\n",
					'customstart'    => '<div class="custom_content">',
					'customend'      => "</div>\n",
					'note_format'    => ' <span class="notes">%s</span>',
					'formend'        => '</div>',
				);
				$layout = 'fieldset';
			}
		}

		return $template;
	}


	/**
	 * Switch to other layout
	 *
	 * @param string|NULL the form layout : 'fieldset', 'table' or ''; NULL to restore previsouly saved layout
	 * @param boolean TRUE to use default layout params when current skin has no the selected layout
	 */
	function switch_layout( $layout, $use_default_layout = true )
	{
		if( $layout == NULL )
		{ // we want to restore previous layout:
			if( count( $this->saved_layouts ) )
			{
				$this->layout = array_shift( $this->saved_layouts );
				$template = $this->saved_templates[0];
				if( ! empty( $template ) )
				{
					$this->template       = $template;
					$this->formclass      = isset( $template['formclass'] ) ? $template['formclass'] : '';
					$this->formstart      = $template['formstart'];
					$this->title_fmt      = $template['title_fmt'];
					$this->no_title_fmt   = $template['no_title_fmt'];
					$this->no_title_no_icons_fmt = isset( $template['no_title_no_icons_fmt'] ) ? $template['no_title_no_icons_fmt'] : '';
					$this->group_begin    = isset( $template['group_begin'] ) ? $template['group_begin'] : '';
					$this->group_end      = isset( $template['group_end'] ) ? $template['group_end'] : '';
					$this->fieldset_title = isset( $template['fieldset_title'] ) ? $template['fieldset_title'] : '';
					$this->fieldset_begin = $template['fieldset_begin'];
					$this->fieldset_end   = $template['fieldset_end'];
					$this->fieldstart     = $template['fieldstart'];
					$this->labelclass     = $template['labelclass'];
					$this->labelstart     = $template['labelstart'];
					$this->labelend       = $template['labelend'];
					$this->labelempty     = $template['labelempty'];
					$this->inputstart     = $template['inputstart'];
					$this->inputend       = $template['inputend'];
					$this->infostart      = $template['infostart'];
					$this->infoend        = $template['infoend'];
					$this->fieldend       = $template['fieldend'];
					$this->buttonsstart   = $template['buttonsstart'];
					$this->buttonsend     = $template['buttonsend'];
					$this->customstart    = $template['customstart'];
					$this->customend      = $template['customend'];
					$this->note_format    = $template['note_format'];
					$this->formend        = $template['formend'];
					// Additional params depending on field type:
					$template = array_merge( array(
							// - checkbox
							'fieldstart_checkbox'    => $this->fieldstart,
							'fieldend_checkbox'      => $this->fieldend,
							'inputclass_checkbox'    => 'checkbox',
							'inputstart_checkbox'    => $this->inputstart,
							'inputend_checkbox'      => $this->inputend,
							'checkbox_newline_start' => '',
							'checkbox_newline_end'   => "<br />\n",
							'checkbox_basic_start'   => '<label>',
							'checkbox_basic_end'     => '</label>',
							// - radio
							'fieldstart_radio'       => $this->fieldstart,
							'fieldend_radio'         => $this->fieldend,
							'inputclass_radio'       => 'radio',
							'inputstart_radio'       => $this->inputstart,
							'inputend_radio'         => $this->inputend,
							'radio_label_format'     => '<label class="radiooption" for="$radio_option_ID$">$radio_option_label$</label>',
							'radio_newline_start'    => "<div>\n",
							'radio_newline_end'      => "</div>\n",
							'radio_oneline_start'    => '',
							'radio_oneline_end'      => '',
						), $template );
					// - checkbox
					$this->fieldstart_checkbox    = $template['fieldstart_checkbox'];
					$this->fieldend_checkbox      = $template['fieldend_checkbox'];
					$this->inputclass_checkbox    = $template['inputclass_checkbox'];
					$this->inputstart_checkbox    = $template['inputstart_checkbox'];
					$this->inputend_checkbox      = $template['inputend_checkbox'];
					$this->checkbox_newline_start = $template['checkbox_newline_start'];
					$this->checkbox_newline_end   = $template['checkbox_newline_end'];
					$this->checkbox_basic_start   = $template['checkbox_basic_start'];
					$this->checkbox_basic_end     = $template['checkbox_basic_end'];
					// - radio
					$this->fieldstart_radio       = $template['fieldstart_radio'];
					$this->fieldend_radio         = $template['fieldend_radio'];
					$this->inputclass_radio       = $template['inputclass_radio'];
					$this->inputstart_radio       = $template['inputstart_radio'];
					$this->inputend_radio         = $template['inputend_radio'];
					$this->radio_label_format     = $template['radio_label_format'];
					$this->radio_newline_start    = $template['radio_newline_start'];
					$this->radio_newline_end      = $template['radio_newline_end'];
					$this->radio_oneline_start    = $template['radio_oneline_start'];
					$this->radio_oneline_end      = $template['radio_oneline_end'];

					if( isset( $template['global_icons_class'] ) )
					{ // Set class for global icons
						if( ! isset( $this->params ) )
						{
							$this->params = array();
						}
						$this->params['global_icons_class'] = $template['global_icons_class'];
					}
				}
			}
		}
		else
		{ // We want to switch to a new layout

			// Try to get template of current skin
			$template = $this->get_template( $layout, false );

			if( empty( $template ) && ! $use_default_layout )
			{ // Don't use default layout
				return;
			}

			array_unshift( $this->saved_layouts, $this->layout );
			$this->layout = $layout;

			if( is_array( $template ) && ! empty( $template ) )
			{ // Template is detected on current skin, Use it
				foreach( $template as $t_param_name => $t_param_value )
				{
					$this->$t_param_name = $t_param_value;
				}
				// Exit here because layout is switched to template of current skin
				return;
			}

			// Use default layout if it is not defined for current skin
			switch( $this->layout )
			{
				case 'table':
					$this->formclass      = '';
					$this->formstart      = '<table cellspacing="0" class="fform">'."\n";
					// Note: no thead in here until you can safely add a tbody to the rest of the content...
					$this->title_fmt      = '<tr class="formtitle"><th colspan="2"><div class="results_title">'
																	.'<span class="right_icons">$global_icons$</span>'
																	.'$title$</div></th></tr>'."\n";
					$this->no_title_fmt   = '<tr><th colspan="2"><span class="right_icons">$global_icons$</span></th></tr>'."\n";
					$this->no_title_no_icons_fmt = "\n";
					$this->fieldset_begin = '<fieldset $fieldset_attribs$>'."\n"
																	.'<legend $title_attribs$>$fieldset_title$</legend>'."\n";
					$this->fieldset_end   = '</fieldset>'."\n";
					$this->fieldstart     = '<tr$ID$>'."\n";
					$this->labelstart     = '<td class="label">';
					$this->labelend       = "</td>\n";
					$this->labelempty     = '<td class="label">&nbsp;</td>'."\n";
					$this->inputstart     = '<td class="input">';
					$this->inputend       = "</td>\n";
					$this->infostart      = '<td class="info">';
					$this->infoend        = "</td>\n";
					$this->fieldend       = "</tr>\n\n";
					$this->buttonsstart   = '<tr class="buttons"><td colspan="2">';
					$this->buttonsend     = "</td></tr>\n";
					$this->customstart    = '<tr><td colspan="2" class="custom_content">';
					$this->customend      = "</td></tr>\n";
					$this->note_format    = ' <span class="notes">%s</span>';
					$this->formend        = "</table>\n";
					// Additional params depending on field type:
					// - checkbox
					$this->fieldstart_checkbox    = $this->fieldstart;
					$this->fieldend_checkbox      = $this->fieldend;
					$this->inputclass_checkbox    = 'checkbox';
					$this->inputstart_checkbox    = $this->inputstart;
					$this->inputend_checkbox      = $this->inputend;
					$this->checkbox_newline_start = '';
					$this->checkbox_newline_end   = "<br />\n";
					$this->checkbox_basic_start   = '<label>';
					$this->checkbox_basic_end     = '</label>';
					// - radio
					$this->fieldstart_radio       = $this->fieldstart;
					$this->fieldend_radio         = $this->fieldend;
					$this->inputclass_radio       = 'radio';
					$this->inputstart_radio       = $this->inputstart;
					$this->inputend_radio         = $this->inputend;
					$this->radio_label_format     = '<label class="radiooption" for="$radio_option_ID$">$radio_option_label$</label>';
					$this->radio_newline_start    = "<div>\n";
					$this->radio_newline_end      = "</div>\n";
					$this->radio_oneline_start    = '';
					$this->radio_oneline_end      = '';
					break;

				case 'fieldset':
					$this->formclass      = '';
					$this->formstart      = '<div>';// required before (no_)title_fmt for validation
					$this->title_fmt      = '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n";
					$this->no_title_fmt   = '<span style="float:right">$global_icons$</span>'."\n";
					$this->no_title_no_icons_fmt = "\n";
					$this->fieldset_begin = '<fieldset $fieldset_attribs$>'."\n"
																		.'<legend $title_attribs$>$fieldset_title$</legend>'."\n";
					$this->fieldset_end   = '</fieldset>'."\n";
					$this->fieldstart     = '<fieldset$ID$>'."\n";
					$this->labelstart     = '<div class="label">';
					$this->labelend       = "</div>\n";
					$this->labelempty     = '<div class="label"></div>'; // so that IE6 aligns DIV.input correcctly
					$this->inputstart     = '<div class="input">';
					$this->infostart      = '<div class="info">';
					$this->inputend       = "</div>\n";
					$this->fieldend       = "</fieldset>\n\n";
					$this->buttonsstart   = '<fieldset><div class="label"></div><div class="input">'; // DIV.label for IE6
					$this->buttonsend     = "</div></fieldset>\n\n";
					$this->customstart    = '<div class="custom_content">';
					$this->customend      = "</div>\n";
					$this->note_format    = ' <span class="notes">%s</span>';
					$this->formend        = '</div>';
					// Additional params depending on field type:
					// - checkbox
					$this->fieldstart_checkbox    = $this->fieldstart;
					$this->fieldend_checkbox      = $this->fieldend;
					$this->inputclass_checkbox    = 'checkbox';
					$this->inputstart_checkbox    = $this->inputstart;
					$this->inputend_checkbox      = $this->inputend;
					$this->checkbox_newline_start = '';
					$this->checkbox_newline_end   = "<br />\n";
					$this->checkbox_basic_start   = '<label>';
					$this->checkbox_basic_end     = '</label>';
					// - radio
					$this->fieldstart_radio       = $this->fieldstart;
					$this->fieldend_radio         = $this->fieldend;
					$this->inputclass_radio       = 'radio';
					$this->inputstart_radio       = $this->inputstart;
					$this->inputend_radio         = $this->inputend;
					$this->radio_label_format     = '<label class="radiooption" for="$radio_option_ID$">$radio_option_label$</label>';
					$this->radio_newline_start    = "<div>\n";
					$this->radio_newline_end      = "</div>\n";
					$this->radio_oneline_start    = '';
					$this->radio_oneline_end      = '';
					break;

				case 'linespan':
					$this->formclass      = '';
					$this->formstart      = '';
					$this->title_fmt      = '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n";
					$this->no_title_fmt   = '<span style="float:right">$global_icons$</span>&nbsp;'."\n";
					$this->no_title_no_icons_fmt = "\n";
					$this->fieldset_begin = '<fieldset $fieldset_attribs$>'."\n"
																	.'<legend $title_attribs$>$fieldset_title$</legend>'."\n";
					$this->fieldset_end   = '</fieldset>'."\n";
					$this->fieldstart     = '<div class="tile"$ID$>';
					$this->labelstart     = '<strong>';
					$this->labelend       = "</strong>\n";
					$this->labelempty     = '';
					$this->inputstart     = '';
					$this->inputend       = "\n";
					$this->infostart      = '';
					$this->infoend        = "\n";
					$this->fieldend       = "</div>\n";
					$this->buttonsstart   = '';
					$this->buttonsend     = "\n";
					$this->customstart    = '';
					$this->customend      = "\n";
					$this->note_format    = ' <span class="notes">%s</span>';
					$this->formend        = '';
					// Additional params depending on field type:
					// - checkbox
					$this->fieldstart_checkbox    = $this->fieldstart;
					$this->fieldend_checkbox      = $this->fieldend;
					$this->inputclass_checkbox    = 'checkbox';
					$this->inputstart_checkbox    = $this->inputstart;
					$this->inputend_checkbox      = $this->inputend;
					$this->checkbox_newline_start = '';
					$this->checkbox_newline_end   = "<br />\n";
					$this->checkbox_basic_start   = '<label>';
					$this->checkbox_basic_end     = '</label>';
					// - radio
					$this->fieldstart_radio       = $this->fieldstart;
					$this->fieldend_radio         = $this->fieldend;
					$this->inputclass_radio       = 'radio';
					$this->inputstart_radio       = $this->inputstart;
					$this->inputend_radio         = $this->inputend;
					$this->radio_label_format     = '<label class="radiooption" for="$radio_option_ID$">$radio_option_label$</label>';
					$this->radio_newline_start    = "<div>\n";
					$this->radio_newline_end      = "</div>\n";
					$this->radio_oneline_start    = '';
					$this->radio_oneline_end      = '';
					break;

				case 'blockspan':
					$this->formclass      = '';
					$this->formstart      = '';
					$this->title_fmt      = '$title$'."\n"; // TODO: icons
					$this->no_title_fmt   = '';          //           "
					$this->no_title_no_icons_fmt = '';
					$this->fieldset_begin = '<fieldset $fieldset_attribs$>'."\n"
																	.'<legend $title_attribs$>$fieldset_title$</legend>'."\n";
					$this->fieldset_end   = '</fieldset>'."\n";
					$this->fieldstart     = '<span class="block"$ID$>';
					$this->labelstart     = '';
					$this->labelend       = "\n";
					$this->labelempty     = '';
					$this->inputstart     = '';
					$this->inputend       = "\n";
					$this->infostart      = '';
					$this->infoend        = "\n";
					$this->fieldend       = '</span>'.get_icon( 'pixel' )."\n";
					$this->buttonsstart   = '';
					$this->buttonsend     = "\n";
					$this->customstart    = '';
					$this->customend      = '';
					$this->note_format    = ' <span class="notes">%s</span>';
					$this->formend        = '';
					// Additional params depending on field type:
					// - checkbox
					$this->fieldstart_checkbox    = $this->fieldstart;
					$this->fieldend_checkbox      = $this->fieldend;
					$this->inputclass_checkbox    = 'checkbox';
					$this->inputstart_checkbox    = $this->inputstart;
					$this->inputend_checkbox      = $this->inputend;
					$this->checkbox_newline_start = '';
					$this->checkbox_newline_end   = "<br />\n";
					$this->checkbox_basic_start   = '<label>';
					$this->checkbox_basic_end     = '</label>';
					// - radio
					$this->fieldstart_radio       = $this->fieldstart;
					$this->fieldend_radio         = $this->fieldend;
					$this->inputclass_radio       = 'radio';
					$this->inputstart_radio       = $this->inputstart;
					$this->inputend_radio         = $this->inputend;
					$this->radio_label_format     = '<label class="radiooption" for="$radio_option_ID$">$radio_option_label$</label>';
					$this->radio_newline_start    = "<div>\n";
					$this->radio_newline_end      = "</div>\n";
					$this->radio_oneline_start    = '';
					$this->radio_oneline_end      = '';
					break;

				default:
					// "none" (no layout)
					$this->formclass      = '';
					$this->formstart      = '';
					$this->title_fmt      = '$title$'."\n"; // TODO: icons
					$this->no_title_fmt   = '';          //           "
					$this->no_title_fmt   = '';          //           "
					$this->fieldset_begin = '<fieldset $fieldset_attribs$>'."\n"
																	.'<legend $title_attribs$>$fieldset_title$</legend>'."\n";
					$this->fieldset_end   = '</fieldset>'."\n";
					$this->fieldstart     = ''; // fp> shall we still use $ID$ here ?
					$this->labelstart     = '';
					$this->labelend       = "\n";
					$this->labelempty     = '';
					$this->inputstart     = '';
					$this->inputend       = "\n";
					$this->infostart      = '';
					$this->infoend        = "\n";
					$this->fieldend       = "\n";
					$this->buttonsstart   = '';
					$this->buttonsend     = "\n";
					$this->customstart    = '';
					$this->customend      = "\n";
					$this->note_format    = ' <span class="notes">%s</span>';
					$this->formend        = '';
					// Additional params depending on field type:
					// - checkbox
					$this->fieldstart_checkbox    = $this->fieldstart;
					$this->fieldend_checkbox      = $this->fieldend;
					$this->inputclass_checkbox    = 'checkbox';
					$this->inputstart_checkbox    = $this->inputstart;
					$this->inputend_checkbox      = $this->inputend;
					$this->checkbox_newline_start = '';
					$this->checkbox_newline_end   = "<br />\n";
					$this->checkbox_basic_start   = '<label>';
					$this->checkbox_basic_end     = '</label>';
					// - radio
					$this->fieldstart_radio       = $this->fieldstart;
					$this->fieldend_radio         = $this->fieldend;
					$this->inputclass_radio       = 'radio';
					$this->inputstart_radio       = $this->inputstart;
					$this->inputend_radio         = $this->inputend;
					$this->radio_label_format     = '<label class="radiooption" for="$radio_option_ID$">$radio_option_label$</label>';
					$this->radio_newline_start    = "<div>\n";
					$this->radio_newline_end      = "</div>\n";
					$this->radio_oneline_start    = '';
					$this->radio_oneline_end      = '';
			}

		}
	}


	/**
	 * Set a parts of form from array
	 *
	 * @param array:
	 *    formstart
	 *    title_fmt
	 *    no_title_fmt
	 *    no_title_no_icons_fmt
	 *    fieldset_begin
	 *    fieldset_end
	 *    fieldstart
	 *    labelstart
	 *    labelend
	 *    labelempty
	 *    inputstart
	 *    inputend
	 *    infostart
	 *    infoend
	 *    fieldend
	 *    buttonsstart
	 *    buttonsend
	 *    customstart
	 *    customend
	 *    note_format
	 *    formend
	 */
	function switch_template_parts( $parts )
	{
		if( is_array( $parts ) && count( $parts ) > 0 )
		{	// Change a default form params from defined vars on current skin
			foreach( $parts as $part => $value )
			{
				$this->$part = $value;
			}
		}
	}


	/**
	 * Start an input field.
	 *
	 * A field is a fielset containing a label div and an input div.
	 *
	 * @uses $_common_params
	 * @param string The name of the field
	 * @param string The field label
	 * @param boolean Reset {@link $_common_params}? This should be used if you build a field by yourself.
	 * @param string Field type
	 * @return The generated HTML
	 */
	function begin_field( $field_name = NULL, $field_label = NULL, $reset_common_params = false, $type = '' )
	{
		if( $reset_common_params )
		{
			$this->_common_params = array();
		}

		$field_classes = array();
		if( !empty( $this->_common_params['wide'] ) && $this->_common_params['wide'] )
		{ // Wide field
			$field_classes[] = 'field_wide';
		}
		if( !empty( $this->_common_params['inline'] ) && $this->_common_params['inline'] )
		{ // Inline field
			$field_classes[] = 'field_inline';
		}

		// Remember these, to make them available to get_label()
		if( isset($field_name) )
		{
			$this->_common_params['name'] = $field_name;
		}
		if( isset($field_label) )
		{
			$this->_common_params['label'] = $field_label;
		}

		// Start the new form field and inject an automatic DOM id
		// This is useful to show/hide the whole field by JS.
		if( !empty( $this->_common_params['id'] ) )
		{
			$ffield_id = ' id="ffield_'.$this->_common_params['id'].'" ';
		}
		else
		{ // No ID in case there's no id/name given for a field.
			$ffield_id = '';
		}
		// quick and dirty "required_field" addition, needs tidying once tested
		if( !empty( $this->_common_params['required'] ) )
		{ // required field
			$field_classes[] = 'field_required';
		}

		if( isset( $this->{'fieldstart_'.$type} ) )
		{ // Use special field start for element with type
			$r = $this->{'fieldstart_'.$type};
		}
		else
		{ // Use default field start
			$r = $this->fieldstart;
		}

		if( count( $field_classes ) > 0 )
		{
			if( strpos( $r, 'class="' ) === false )
			{ // Add class attribute
				$ffield_id .= ' class="'.implode( ' ', $field_classes ).'"';
			}
			else
			{ // Append classes to attribute
				$r = str_replace( ' class="', ' class="'.implode( ' ', $field_classes ).' ', $r );
			}
		}

		$r = str_replace( '$ID$', $ffield_id, $r );

		if( isset($this->_common_params['field_prefix']) )
		{
			$r .= $this->_common_params['field_prefix'];
		}

		$r .= $this->get_label();

		if( $type == 'info' )
		{ // Use info start:
			$r .= $this->infostart;
		}
		elseif( isset( $this->{'inputstart_'.$type} ) )
		{ // Use special input start for element with type:
			$r .= $this->{'inputstart_'.$type};
		}
		else
		{ // Use default input start:
			$r .= $this->inputstart;
		}

		if( isset( $this->_common_params['input_prefix'] ) )
		{
			$r .= $this->_common_params['input_prefix'];
		}

		return $r;
	}


	/**
	 * End an input field.
	 *
	 * A field is a fielset containing a label div and an input div.
	 *
	 * @return The generated HTML
	 */
	function end_field( $type = '' )
	{

		$r = '';

		if( !empty($this->_common_params['note']) )
		{ // We have a note
			if( ! empty( $this->is_lined_fields ) )
			{ // Fix note format for fields that are used in one line
				$this->_common_params['note_format'] = str_replace( 'class="', 'class="oneline ', $this->_common_params['note_format'] );
			}
			$r .= sprintf( $this->_common_params['note_format'], $this->_common_params['note'] );
		}

		if( isset($this->_common_params['field_suffix']) )
		{
			$r .= $this->_common_params['field_suffix'];
		}

		if( $type == 'info' )
		{ // Use info end:
			$r .= $this->infoend;
		}
		elseif( isset( $this->{'inputend_'.$type} ) )
		{ // Use special input end for element with type:
			$r .= $this->{'inputend_'.$type};
		}
		else
		{ // Use default input end:
			$r .= $this->inputend;
		}

		if( isset( $this->{'fieldend_'.$type} ) )
		{ // Use special field end for element with type
			$r .= $this->{'fieldend_'.$type};
		}
		else
		{ // Use default field end
			$r .= $this->fieldend;
		}

		return $r;
	}


	/**
	 * Builds a fieldset group tag,
	 * Used as start of wrapper for fieldsets, e-g on accordion layout
	 *
	 * @param string Params
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function begin_group( $params = array() )
	{
		$params = array_merge( array(
				'id'    => 'evo_accordion',
				'class' => '',
			), $params );

		$field_params = array();

		if( ! empty( $params['id'] ) )
		{	// If group id is defined:
			$field_params['id'] = $params['id'];
			// Set current group ID, used on building feildset:
			$this->current_group_ID = $params['id'];
			$this->current_group_item_num = 1;
		}

		$r = str_replace( array( '$group_class$', '$group_attribs$' ),
			array( $params['class'], get_field_attribs_as_string( $field_params ) ),
			$this->group_begin );

		return $this->display_or_return( $r );
	}


	/**
	 * Ends a fieldset group tag,
	 * Used as end of wrapper for fieldsets, e-g on accordion layout
	 *
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function end_group()
	{
		$r = $this->group_end;

		if( isset( $this->current_group_ID ) )
		{	// Unset current group ID and counter of group items:
			unset( $this->current_group_ID );
			unset( $this->current_group_item_num );
		}

		return $this->display_or_return( $r );
	}


	/**
	 * Builds a fieldset tag. This is a "fieldset" element by default, but a "th" element
	 * for table layout.
	 *
	 * @param string the title of the fieldset
	 * @param string the field params to the fieldset
	 *               additionally 'legend_params' can be used to give an array of field params
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function begin_fieldset( $title = '', $field_params = array() )
	{
		$field_params = array_merge( array(
				'class'     => 'fieldset',
				'fold'      => false, // TRUE to enable folding for this fieldset
				'deny_fold' => false, // TRUE to don't allow fold the block and keep it opened always on page loading
			), $field_params );

		if( $field_params['fold'] )
		{ // Display icon to collapse/expand fildset
			$folding_icon = get_fieldset_folding_icon( $field_params['id'], $field_params );
			if( ! $field_params['deny_fold'] && is_logged_in() )
			{ // Only loggedin users can fold fieldset
				global $UserSettings, $Collection, $Blog, $ctrl;
				if( empty( $Blog ) || ( isset( $ctrl ) && in_array( $ctrl, array( 'plugins', 'user' ) ) ) )
				{ // Get user setting value
					$value = intval( $UserSettings->get( 'fold_'.$field_params['id'] ) );
				}
				else
				{ // Get user-collection setting
					$value = intval( $UserSettings->get_collection_setting( 'fold_'.$field_params['id'], $Blog->ID ) );
				}
				if( $value === 1 )
				{
					$field_params['class'] = trim( $field_params['class'].' folded' );
				}
			}

			// Add wrapper for title text to fold fieldset by JS as such as icon does this
			$title = preg_replace( '/^([^<]+)(<.+)?$/', '<span id="title_folding_'.$field_params['id'].'">$1</span>$2', $title );
		}
		else
		{ // No folding icon
			$folding_icon = '';
		}
		unset( $field_params['fold'] );
		unset( $field_params['deny_fold'] );

		if( ! empty( $this->fieldset_title ) )
		{	// Replace text part of fieldset title with provided html code:
			$fieldset_title = str_replace( '$fieldset_title$', '$1', $this->fieldset_title );
			$title = preg_replace( '/^([^<]+)/', $fieldset_title, $title );
		}

		if( isset( $this->current_group_ID ) )
		{	// If fieldset is grouped, Replace the group masks with values:
			$group_item_ID = $this->current_group_ID.'_item_'.$this->current_group_item_num;
			$title = str_replace( array( '$group_ID$', '$group_item_ID$' ), array( $this->current_group_ID, $group_item_ID ), $title );
			$this->current_group_item_num ++;
		}

		switch( $this->layout )
		{
			case 'table':
				$r = '<tr'.get_field_attribs_as_string( $field_params ).'><th colspan="2">'."\n";
				// NOTE: empty THs can be rendered and/or are DHTML scriptable

				if( $title != '' )
				{ // there is a title to display
					$r .= $title;
				}

				$r .= "</th></tr>\n";
				break;

			default:
				if( ! empty( $field_params['legend_params'] ) )
				{ // We have params specifically passed for the title
					$legend_params = $field_params['legend_params'];
					unset( $field_params['legend_params'] );
				}

				$r = str_replace( '$fieldset_attribs$', get_field_attribs_as_string( $field_params ), $this->fieldset_begin );

				$r = str_replace( '$fieldset_title$', $folding_icon.$title, $r );
				if( isset($field_params['id']) )
				{
					$r = str_replace( '$id$', $field_params['id'], $r );
				}
				$r = str_replace( '$class$', $field_params['class'], $r );

				if( empty( $legend_params ) )
				{ // there are no legend_params, remove the placeholder
					$r = str_replace( '$title_attribs$', '', $r );
				}
				else
				{
					$r = str_replace( '$title_attribs$', get_field_attribs_as_string( $legend_params ), $r );
				}

				if( isset( $group_item_ID ) )
				{	// Replace a mask of current group item ID with value:
					$r = str_replace( '$group_item_id$', $group_item_ID, $r );
				}

				// Remove any empty legend tags: they cause a small gap in the fieldset border (FF 2.0.0.11)
				$r = preg_replace('~<legend[^>]*></legend>~', '', $r);

				$this->_opentags['fieldset']++;
		}

		return $this->display_or_return( $r );
	}


	/**
	 * Ends a fieldset.
	 *
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function end_fieldset()
	{
		switch( $this->layout )
		{
			case 'table':
				$r = '';
				break;

			default:
				$r = $this->fieldset_end;
				$this->_opentags['fieldset']--;
		}

		return $this->display_or_return( $r );
	}


	/**
	 * Builds a text (or password) input field.
	 *
	 * Note: please use {@link Form::password_input()} for password fields.
	 *
	 * @param string The name of the input field. This gets used for id also, if no id given in $field_params.
	 * @param string Initial value
	 * @param integer Size of the input field
	 * @param string Label displayed with the field
	 * @param string "help" note (Should provide something useful, otherwise leave it empty)
	 * @param array Extended attributes/params.
	 *                 - 'maxlength': if not set, $field_size gets used (use '' to disable it)
	 *                 - 'class': the CSS class to use for the <input> element
	 *                 - 'type': 'text', 'password' (defaults to 'text')
	 *                 - 'force_to': 'UpperCase' (JS onchange handler)
	 *                 - NOTE: any other attributes will be used as is (onchange, onkeyup, id, ..).
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function text_input( $field_name, $field_value, $field_size, $field_label, $field_note = '', $field_params = array() )
	{
		$field_params = array_merge( array(
				'inline'    => false,
				'type'      => 'text',
				'value'     => $field_value,
				'note'      => $field_note,
				'size'      => $field_size,
				'maxlength' => $field_size,
				'name'      => $field_name,
				'label'     => $field_label,
				'class'     => '', // default class 'form_text_input form-control'
			), $field_params );

		if( isset( $field_params['force_to'] ) )
		{
			if( $field_params['force_to'] == 'UpperCase' )
			{ // Force input to uppercase (at front of onchange event)
				$field_params['onchange'] = 'this.value = this.value.toUpperCase();'
					.( empty( $field_params['onchange'] ) ? '' : ' '.$field_params['onchange'] );
			}
			unset( $field_params['force_to'] ); // not a html attrib
		}

		// Give it a class, so it can be selected for CSS in IE6
		$field_params['class'] = ( empty( $field_params['class'] ) ? '' : $field_params['class'].' ' ).'form_text_input form-control';

		return $this->input_field( $field_params );
	}


	/**
	 * Builds a text (or password) input field.
	 *
	 * Note: please use {@link Form::password()} for password fields
	 *
	 * @param string the name of the input field
	 * @param string initial value
	 * @param integer size of the input field
	 * @param string label displayed in front of the field
	 * @param string note displayed with field
	 * @param integer max length of the value (if 0 field_size will be used!)
	 * @param string the CSS class to use
	 * @param string input type (only 'text' or 'password' makes sense)
	 * @param string 'Uppercase'
	 * @param string placeholder text
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function text( $field_name, $field_value, $field_size, $field_label, $field_note = '',
											$field_maxlength = 0, $field_class = '', $inputtype = 'text', $force_to = '', $placeholder = '' )
	{
		$field_params = array();

		if( $field_maxlength !== 0 )
		{
			$field_params['maxlength'] = $field_maxlength;
		}
		if( $field_class !== '' )
		{
			$field_params['class'] = $field_class;
		}
		if( $inputtype !== 'text' )
		{
			$field_params['type'] = $inputtype;
		}
		if( $force_to !== '' )
		{
			$field_params['force_to'] = $force_to;
		}
		if( $placeholder !== '' )
		{
			$field_params['placeholder'] = $placeholder;
		}

		return $this->text_input( $field_name, $field_value, $field_size, $field_label, $field_note, $field_params );
	}


	/**
	 * Builds a color input field.
	 *
	 * @param string The name of the input field. This gets used for id also, if no id given in $field_params.
	 * @param string Initial value
	 * @param string Label displayed with the field
	 * @param string "help" note (Should provide something useful, otherwise leave it empty)
	 * @param array Extended attributes/params.
	 *                 - 'maxlength': if not set, $field_size gets used (use '' to disable it)
	 *                 - 'class': the CSS class to use for the <input> element
	 *                 - 'type': 'text', 'password' (defaults to 'text')
	 *                 - 'force_to': 'UpperCase' (JS onchange handler)
	 *                 - NOTE: any other attributes will be used as is (onchange, onkeyup, id, ..).
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function color_input( $field_name, $field_value, $field_label, $field_note = '', $field_params = array() )
	{
		$field_params = array_merge( array(
				'inline'    => false,
				'type'      => 'text',
				'value'     => $field_value,
				'note'      => $field_note,
				'size'      => 7,
				'maxlength' => 7,
				'name'      => $field_name,
				'label'     => $field_label,
				'class'     => '', // default class 'form_text_input form-control form_color_input'
			), $field_params );

		if( isset( $field_params['force_to'] ) )
		{
			if( $field_params['force_to'] == 'UpperCase' )
			{ // Force input to uppercase (at front of onchange event)
				$field_params['onchange'] = 'this.value = this.value.toUpperCase();'
					.( empty( $field_params['onchange'] ) ? '' : ' '.$field_params['onchange'] );
			}
			unset( $field_params['force_to'] ); // not a html attrib
		}

		// Give it a class, so it can be selected for CSS in IE6
		$field_params['class'] = ( empty( $field_params['class'] ) ? '' : $field_params['class'].' ' ).'form_text_input form-control form_color_input';

		return $this->input_field( $field_params );
	}


	/**
	 * Builds a password input field.
	 *
	 * Calls the text_input() method with type == 'password'.
	 *
	 * @param string The name of the input field. This gets used for id also, if no id given in $field_params.
	 * @param string Initial value
	 * @param integer Size of the input field
	 * @param string Label displayed in front of the field
	 * @param string Note displayed with field
	 * @param integer Max length of the value (if 0 field_size will be used!)
	 * @param string Extended attributes, see {@link text_input()}.
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function password_input( $field_name, $field_value, $field_size, $field_label, $field_params = array() )
	{
		$field_params['type'] = 'password';

		return $this->text_input( $field_name, $field_value, $field_size, $field_label, '', $field_params );	// TEMP: Note already in params
	}


	/**
	 * Builds a file input field
	 *
	 * @param string the field name
	 * @param string the field value
	 * @param string the field label
	 * @param string the field note
	 * @param array Extended attributes, see {@link input_field()}
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function file_input( $field_name, $field_value, $field_label, $field_note = '', $field_params = array() )
	{

		$field_params = array_merge( array(
				'type' => 'file',
				'value' => $field_value,
				'note' => $field_note,
				'name' => $field_name,
				'label' => $field_label,
				'class' => ''
			), $field_params );

		$element = $this->get_input_element( $field_params );

		$field_params = array_merge( array(
				'note_format' => ' <small class="notes">%s</small>',
			), $field_params );

		if( isset($field_params['format_info']) )
		{
			$format_info = $field_params['format_info'];
			unset($field_params['format_info']); // not an HTML element
		}
		else
		{
			$format_info = 'htmlbody';
		}

		$r = $this->fieldstart;

		// Start the new form field and inject an automatic DOM id
		// This is useful to show/hide the whole field by JS.
		if( !empty( $this->_common_params['id'] ) )
		{
			$ffield_id = ' id="ffield_'.$this->_common_params['id'].'" ';
		}
		else
		{ // No ID in case there's no id/name given for a field.
			$ffield_id = '';
		}
		$r = $this->fieldstart;
		if( !empty( $field_params['class'] ) )
		{
			if( strpos( $r, 'class="' ) === false )
			{ // Add class attribute
				$ffield_id .= ' class="'.$field_params['class'].'"';
			}
			else
			{ // Append classes to attribute
				$r = str_replace( ' class="', ' class="'.$field_params['class'].' ', $r );
			}
		}
		$r = str_replace( '$ID$', $ffield_id, $r );

		$r .= $this->get_label();

		$r .= $this->infostart;

		// PAYLOAD:
		$r .= format_to_output( $element, $format_info );

		// Taken from end_field() - but we use $infoend:
		if( !empty($this->_common_params['note']) )
		{ // We have a note
			$r .= sprintf( $this->_common_params['note_format'], $this->_common_params['note'] );
		}

		if( isset($this->_common_params['field_suffix']) )
		{
			$r .= $this->_common_params['field_suffix'];
		}

		$r .= $this->infoend.$this->fieldend;

		return $this->display_or_return( $r );
	}


	/**
	 * Builds a password input field.
	 *
	 * Calls the text() method with a 'password' parameter.
	 *
	 * @param string the name of the input field
	 * @param string initial value
	 * @param integer size of the input field
	 * @param string label displayed in front of the field
	 * @param string note displayed with field
	 * @param integer max length of the value (if 0 field_size will be used!)
	 * @param string the CSS class to use
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function password( $field_name, $field_value, $field_size, $field_label, $field_note = '',
											$field_maxlength = 0 , $field_class = '' )
	{
		$field_params = array( 'type' => 'password' );

		if( $field_maxlength !== 0 )
		{
			$field_params['maxlength'] = $field_maxlength;
		}
		if( !empty($field_class) )
		{
			$field_params['class'] = $field_class;
		}

		return $this->text_input( $field_name, $field_value, $field_size, $field_label, $field_note, $field_params );
	}


	/**
	 * Build username field
	 *
	 * @param string the name of the input field
	 * @param User initial value
	 * @param string label displayed in front of the field
	 * @param string note displayed with field
	 * @param string class of the input field. Class name "only_assignees" provides to load only assignee users of the blog
	 * @param array Field params
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function username( $field_name, &$User, $field_label, $field_note = '', $field_class = '', $field_params = array() )
	{
		$field_params = array_merge( array(
				'type' => 'text',
				'note' => $field_note,
				'size' => 20,
				'autocapitalize' => 'off',
				'autocorrect' => 'off',
				'status' => 'all', // Restrict users by status, 'all' - get users with all statuses, '' - activated and autoactivated, or custom statuses separated by comma like 'new,activated,autoactivated,closed,deactivated,emailchanged,failedactivation'
			), $field_params );

		$this->handle_common_params( $field_params, $field_name, $field_label );

		$r = $this->begin_field();

		$field_params['value'] = empty( $User ) ? '' : $User->login;
		$field_params['class'] = ( empty( $field_params['class'] ) ? '' : $field_params['class'].' ' ).'form_text_input form-control autocomplete_login '.$field_class;
		if( ! empty( $field_params['status'] ) )
		{
			$field_params['data-status'] = $field_params['status'];
		}

		// Unset params which should not be used as html attributes:
		unset( $field_params['status'] );
		unset( $field_params['autocapitalize'] );
		unset( $field_params['autocorrect'] );

		$r .= $this->get_input_element( $field_params );

		$r .= $this->end_field();

		return $this->display_or_return( $r );
	}


	/**
	 * Build login field.
	 *
	 * @param string the name of the input field
	 * @param string User login
	 * @param integer size of the input field
	 * @param string label displayed in front of the field
	 * @param string note displayed with field
	 * @param array Field params
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function login_input( $field_name, $field_value, $field_size, $field_label, $field_note = '', $field_params = array() )
	{
		$field_params = array_merge( array(
				'size' => $field_size,
				'class' => 'input_text',
				'autocapitalize' => 'off',
				'autocorrect' => 'off'
			), $field_params );

		return $this->text_input( $field_name, $field_value, $field_params['size'], $field_label, $field_note, $field_params );
	}


	/**
	 * Callback for preg_replace_callback in date_input
	 */
	private static function _date_input_format_callback( $matches )
	{
		if( $matches[1] == "\\" ) return "\\".$matches[0]; // leave escaped
		switch( $matches[2] )
		{
			case "d": return "dd"; // day, 01-31
			case "j": return "d"; // day, 1-31
			case "l": return "EE"; // weekday (name)
			case "D": return "E"; // weekday (abbr)
			case "S": return "";

			case "e": return ""; // weekday letter, not supported

			case "m": return "MM"; // month, 01-12
			case "n": return "M"; // month, 1-12
			case "F": return "MMM"; // full month name; "name or abbr" in date.js
			case "M": return "NNN"; // month name abbr

			case "y": return "yy"; // year, 00-99
			case "Y": return "yyyy"; // year, XXXX
			default:
				return $matches[0];
		}
	}


	/**
	 * Callback for preg_replace_callback in date_input
	 */
	private static function _date_input_length_callback( $matches )
	{
		if( $matches[1] == "\\" ) return "\\".$matches[0]; // leave escaped
		switch( $matches[2] )
		{
			case "d": return "nn"; // day, 01-31(2)
			case "j": return "nn"; // day, 1-31(2)
			case "l": return "XXXXXXXXX"; // weekday (name) - Wednesday(9)
			case "D": return "XXX"; // weekday (abbr)(3)
			case "S": return "";

			case "e": return ""; // weekday letter, not supported

			case "m": return "nn"; // month, 01-12(2)
			case "n": return "nn"; // month, 1-12(2)
			case "F": return "XXXXXXXXX"; // full month name; "name or abbr" in date.js - September(9)
			case "M": return "XXX"; // month name abbr(3)

			case "y": return "nn"; // year, 00-99(2)
			case "Y": return "nnnn"; // year, 1970 to 2038(4)
			default:
				return "_"; // (1)
		}
	}

	/**
	 * Builds a date input field.
	 *
	 * @param string the name of the input field
	 * @param string initial value (ISO datetime (YYYY-MM-DD HH:MM:SS)
	 *               or erroneous if the field is in error state)
	 * @param string label displayed in front of the field
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - date_format: Format of the date (string, PHP format, default taken from {@link locale_datefmt()})
	 *              - add_date_format_note: If true, date format note gets prepended to the field's note
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function date_input( $field_name, $field_value, $field_label, $field_params = array() )
	{
		global $month, $weekday_letter;

		if( empty($field_params['date_format']) )
		{	// Use locale date format:
			$date_format = locale_input_datefmt();
		}
		else
		{
			$date_format = $field_params['date_format'];
		}
		// Don't keep that attrib in the list:
		unset( $field_params['date_format'] );

		// Convert PHP date format to JS library date format:
		// NOTE: when editing/extending this here, you probably also have to adjust param_check_date()!
		$js_date_format = preg_replace_callback( '~(\\\)?(\w)~', array( 'Form', '_date_input_format_callback' ), $date_format );

		// Get max length of each date component
		$js_date_length = preg_replace_callback( '~(\\\)?(\w)~', array( 'Form', '_date_input_length_callback' ), $date_format );

		$field_params['type'] = 'text';

		if( param_has_error( $field_name )
			&&  ! preg_match('~^\d\d\d\d-\d\d-\d\d(?: \d\d:\d\d:\d\d)?$~', $field_value) )
		{ // There is an error message for this field:

			// We do not try to format the date, we keep the erroneous date (if it is not obviously valid).
			// We could have used param_error() ourself (e.g. "date outside of range"), and the erroneous
			// field should have the correct format.

			//echo 'error on '.$field_name.' keep erroneous entry intact ';

			// Keep original value, but strip off the time part (if any).
			$field_params['value'] = preg_replace( '~ \d\d:\d\d:\d\d$~', '', $field_value );
		}
		else
		{ // Make the date value clean for display:

			// The date value may be compact, in this case we have to decompact it
			if( preg_match( '/^[0-9]+$/', $field_value ) )
			{	// The date is compact, so we decompact it
				$field_value = decompact_date( $field_value );
			}

			// Get DATE part of datetime and format it to locale format:
			$field_params['value'] = mysql2date( $date_format, $field_value );
			if( ! $field_params['value'] )
			{ // Conversion failed (e.g. for dates before ~1902 / PHP < 5.1 (Windows) / 32bit / without DateTime support), use the original value (better than 1970-01-01 anyway!).
				$field_params['value'] = $field_value;
			}
		}


		if( !empty($field_params['add_date_format_note']) )
		{ // Prepend $date_format to note
			$field_params['note'] = empty($field_params['note'])
				? '('.$date_format.')'
				: '('.$date_format.') '.$field_params['note'];
		}
		unset( $field_params['add_date_format_note'] );


		if( !isset($field_params['size']) )
		{ // Get size out of $date_format if not explicitly set
			$field_params['size'] = strlen( $js_date_length );
		}

		/*
		dh> do not use maxlength by default. Makes no sense IMHO and fails with dateformats like "j \d\e F, Y"
		if( !isset($field_params['maxlength']) )
		{
			$field_params['maxlength'] = $field_params['size'];
		}
		*/
		/*
		 Afwas > In the existing locales only d m y and Y are used. Currently the jQuery Datepicker
		 can't handle other dateformats. I will see to some basic check or enable all.
		 @TODO ^^ fp> It might make sense to have 2 date formats for locales: 1 for display and 1 for inputs. Input formats could be forced to used numeric data only.
		 */
		// Give it a class, so it can be selected for CSS in IE6
		$field_params['class'] = ( empty( $field_params['class'] ) ? '' : $field_params['class'].' ' ).'form_date_input form-control';

		$this->handle_common_params( $field_params, $field_name, $field_label );

		$r = $this->begin_field() . $this->get_input_element($field_params, false);
		$r .= $this->end_field();

		return $this->display_or_return( $r );
	}


	/**
	 * Builds a date input field.
	 *
	 * @param string the name of the input field
	 * @param string initial value (ISO datetime)
	 * @param string label displayed in front of the field
	 * @param string date format (php format)
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function date( $field_name, $field_value, $field_label, $date_format = NULL )
	{
		$field_params = array( 'date_format' => $date_format, 'type' => 'text' );

		return $this->date_input( $field_name, $field_value, $field_label, $field_params );
	}


	/**
	 * Builds a time input field.
	 *
	 * @param string The name of the input field
	 * @param string Initial value (ISO datetime)
	 * @param string Label displayed in front of the field
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - 'time_format': Format of the time (string, default 'hh:mm:ss')
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function time_input( $field_name, $field_value, $field_label, $field_params = array() )
	{
		if( isset($field_params['time_format']) )
		{
			$field_format = $field_params['time_format'];
			unset( $field_params['time_format'] ); // not an attribute
		}
		else
		{
			$field_format = 'hh:mm:ss';
		}

		// Use format as field note
		if( ! isset($field_params['note']) )
		{
			$field_params['note'] = '('.$field_format.')';
		}

		if( ! isset($field_params['size']) )
		{
			$field_size = strlen($field_format);
		}
		else
		{
			$field_size = $field_params['size'];
		}

		// Get time part of datetime:
		$field_value = substr( $field_value, 11, $field_size );

		return $this->text_input( $field_name, $field_value, $field_size, $field_label, '', $field_params ); // TEMP: Note already in params
	}


	/**
	 * Builds a time input field.
	 *
	 * @param string the name of the input field
	 * @param string initial value (ISO datetime)
	 * @param string label displayed in front of the field
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function time( $field_name, $field_value, $field_label, $field_format = 'hh:mm:ss', $note = NULL )
	{
		$field_params = array(
				'time_format' => $field_format,
				'note' => $note,
			);

		return $this->time_input( $field_name, $field_value, $field_label, $field_params );
	}


	/**
	 * Builds a time select input field
	 *
	 * @param string field name
	 * @param string initial value (ISO datetime or time only)
	 * @param string precison xmn or xsec (x:integer) for the options minutes or secondes
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param string CSS class for select
	 * @param string Javascript to add for onchange event (trailing ";").
	 */
	function time_select( $field_name, $field_value = NULL, $precision = '5mn', $field_label, $field_note = NULL, $field_class = NULL, $field_onchange = NULL )
	{
		preg_match( '#([0-9]+)(mn|s)#', $precision, $matches );

		if( !isset( $matches[1] ) && !isset( $matches[2] ) )
		{	// precison has a bad format
			return;
		}

		$field_params = array(
		'note' => $field_note,
		'class' => $field_class,
		'onchange' => $field_onchange );

		/***  instantiate the precison for the minutes and secondes select options  ****/

		if( $matches[2] == 'mn' )
		{
			$precision_mn = $matches[1];
			$precision_s = 0;
			// convert the precision in sec
			$precision *= 60;
		}
		else
		{
			$precision_mn = 1;
			$precision_s = $matches[1];
		}

		// Check if field value is only a time
		if( strlen( $field_value ) <= 8 )
		{	// Add date part:
			$field_value = '2000-01-01 '.$field_value;
		}

		/***  set round time with the precision  ***/
		// Get nb sec since unix...
		$nbsec = mysql2timestamp( $field_value );
		$modulo =  $nbsec % $precision;

		if( $modulo < ( $precision / 2 ) )
		{ // The round time is before
			$nbsec -= $modulo;
		}
		else
		{ // The round time is after
			$nbsec += $precision - $modulo;
		}

		/******************************************************/

		$this->handle_common_params( $field_params, $field_name, $field_label );

		$r = $this->begin_field();

		/**********   select options for the hours *************/

		$field_params['name'] = $field_name . '_h';
		$field_params['id'] = $field_params['name'];
		// Get Hour part of datetime:
		$hour = date( 'H', $nbsec );

		$r .= $this->_number_select(  $hour, 23 , 1, $field_params);

		/*********  select options for the minutes *************/

		$field_params['name'] = $field_name . '_mn';
		$field_params['id'] = $field_params['name'];
		// Get Minute part of datetime:
		$minute = date('i',$nbsec);

		$r .= ':'.$this->_number_select(  $minute, 59, $precision_mn, $field_params);

		if( $precision_s )
		{/*********  select options for the minutes  ***********/

			$field_params['name'] = $field_name . '_s';
			$field_params['id'] = $field_params['name'];
			// Get Secondes part of datetime:
			$seconde = substr( $field_value, 17, 2 );

			$r .=':'.$this->_number_select(  $seconde, 59, $precision_s, $field_params);
		}

		$r .= $this->end_field();

		return $this->display_or_return( $r );
	}


	/**
	 * Build a select input field number
	 * @access private
	 *
	 * @param string 	field value of selected
	 * @param integer maximum value for the input select
	 * @param integer increment for the loop (precision)
	 * @param array params
	 */
	function _number_select( $field_value, $max, $precision = 1, $field_params )
	{
			$r	=	'<select'.get_field_attribs_as_string( $field_params ).'>';

			for( $i=0; $i <= $max ; $i += $precision)
			{
				$val = sprintf( '%02d', $i );
				$r .= '<option value="'.$val.'"'.
								($field_value == $val ? ' selected="selected"' : '') .
								'>'	.$val.'</option>';
			}

			$r .= '</select>';

			return $r;
	}


	/**
	 * Builds a duration input field.
	 *
	 * @todo fp> move params array to the end
	 *
	 * @param string the name of the input field
	 * @param string initial value (seconds)
	 * @param string label displayed in front of the field
	 * @param string **** OLD PARAM **** display from field: months, days, hours, minutes, seconds
	 * @param string **** OLD PARAM **** display to field: months, days, hours, minutes, seconds
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - minutes_step ( default = 15 );
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function duration_input( $field_prefix, $duration, $field_label, $from_subfield = 'days', $to_subfield = 'minutes', $field_params = array() )
	{
		$field_params = array_merge( array(
				'allow_none_value' => true,
				'none_value_label' => '---',
				'allow_none_title' => true,
				'none_title_label' => '---',
			), $field_params );

		$this->handle_common_params( $field_params, $field_prefix, $field_label );

		$periods_values = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 15, 20, 25, 50 );
		$periods = array(
			array( 'name' => 'second', 'title' => T_('second(s)'), 'seconds' => 1,        'size' => 1 ), // 1 seconds
			array( 'name' => 'minute', 'title' => T_('minute(s)'), 'seconds' => 50,       'size' => 60 ), // 50 seconds
			array( 'name' => 'hour',   'title' => T_('hour(s)'),   'seconds' => 3000,     'size' => 3600 ), // 50 minutes
			array( 'name' => 'day',    'title' => T_('day(s)'),    'seconds' => 72000,    'size' => 86400 ), // 20 hours
			array( 'name' => 'month',  'title' => T_('month(s)'),  'seconds' => 2160000,  'size' => 2592000 ), // 25 days
			array( 'name' => 'year',   'title' => T_('year(s)'),   'seconds' => 25920000, 'size' => 31536000 ), // 10 months
		);

		$r = $this->begin_field();

		// Set the values for <select> lists below
		$current_value = 0;
		$current_period = '';
		if( !empty( $duration ) )
		{
			$periods_count = count( $periods );
			for( $p = 0; $p <= $periods_count; $p++ )
			{
				$period = $periods[ $p < $periods_count ? $p : $periods_count - 1 ];
				if( ( $p == 0 && $duration <= $period['seconds'] ) ||
				    ( $p == $periods_count && $duration > $period['seconds'] ) ||
				    ( $p > 0 && $duration > $periods[ $p - 1 ]['seconds'] && $duration <= $period['seconds'] ) )
				{
					$period = $periods[ $p > 0 ? $p - 1 : 0 ];
					$duration_value = floor( $duration / $period['size'] );
					foreach( $periods_values as $v => $value )
					{
						if( $duration_value <= $value )
						{
							$current_value = $value;
							break;
						}
					}
					$current_period = $period['name'];
					break;
				}
			}
		}

		$field_class = 'form-control';
		if( param_has_error( $field_prefix ) )
		{
			$field_class .= ' field_error';
		}
		$field_class = ' class="'.$field_class.'"';

		// Display <select> with periods values
		$r .= "\n".'<select name="'.$field_prefix.'_value" id="'.Form::get_valid_id( $field_prefix ).'_value"'.$field_class.'>';
		if( $field_params['allow_none_value'] )
		{	// Allow null value:
			$r .= '<option value="0"'.( 0 == $current_value ? ' selected="selected"' : '' ).'>'.$field_params['none_value_label'].'</option>'."\n";
		}
		foreach( $periods_values as $period_value )
		{
			$r .= '<option value="'.$period_value.'"'.( $current_value == $period_value ? ' selected="selected"' : '' ).'>'.$period_value."</option>\n";
		}
		$r .= '</select>'."\n";

		// Display <select> with periods titles
		$r .= "\n".'<select name="'.$field_prefix.'_name" id="'.Form::get_valid_id( $field_prefix ).'_name"'.$field_class.'>';
		if( $field_params['allow_none_title'] )
		{	// Allow none period name:
			$r .= '<option value="0"'.( '' == $current_period ? ' selected="selected"' : '' ).'>'.$field_params['none_title_label'].'</option>'."\n";
		}
		foreach( $periods as $period )
		{
			$r .= '<option value="'.$period['name'].'"'.( $current_period == $period['name'] ? ' selected="selected"' : '' ).'>'.$period['title']."</option>\n";
		}
		$r .= '</select>'."\n";

		$r .= $this->end_field();

		return $this->display_or_return( $r );
	}


	/**
	 * Builds a duration input field.
	 *
	 * @param string the name of the input field
	 * @param string initial value (seconds)
	 * @param string label displayed in front of the field
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function duration( $field_prefix, $duration, $field_label )
	{
		return $this->duration_input( $field_prefix, $duration, $field_label );
	}


	/**
	 * Build a select to choose a weekday.
	 *
	 * @uses select_input_options()
	 *
	 * @return true|string
	 */
	function dayOfWeek_input( $field_name, $field_value, $field_label, $field_params = array() )
	{
		global $weekday_abbrev;

		$field_options = '';

		foreach( $weekday_abbrev as $lNumber => $lWeekday )
		{
			$field_options .= '<option';

			if( $field_value == $lNumber )
			{
				$field_options .= ' selected="selected"';
			}
			$field_options .= ' value="'.$lNumber.'">'.T_($lWeekday).'</option>';
		}

		return $this->select_input_options( $field_name, $field_options, $field_label, '', $field_params );
	}


	/**
	 * Build a select to choose a weekday.
	 *
	 * @return true|string
	 */
	function dayOfWeek( $field_name, $field_value, $field_label, $field_note = NULL, $field_class = NULL )
	{
		$field_params = array();
		if( isset($field_note) )
		{
			$field_params['note'] = $field_note;
		}

		if( isset($field_class) )
		{
			$field_params['class'] = $field_class;
		}

		return $this->dayOfWeek_input( $field_name, $field_value, $field_label, $field_params );
	}


	/**
	 * Builds a checkbox field
	 *
	 * @param string the name of the checkbox
	 * @param boolean indicating if the checkbox must be checked by default
	 * @param string label
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              'value': the value attribute of the checkbox (default 1)
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function checkbox_input( $field_name, $field_checked, $field_label, $field_params = array() )
	{
		$field_params = array_merge( array(
				'value' => 1,
				'name'  => $field_name,
				'label' => $field_label,
				'type'  => 'checkbox',
				'class' => $this->inputclass_checkbox, // 'checkbox'
			), $field_params );

		if( $field_checked )
		{
			$field_params['checked'] = 'checked';
		}

		if( ! empty( $this->force_checkboxes_to_inline ) && ! isset( $field_params['inline'] ) )
		{ // Force to display checkbox before label
			$field_params['inline'] = true;
			$field_params['label'] = '%s '.$field_params['label'];
		}

		return $this->input_field( $field_params );
	}


	/**
	 * EXPERIMENTAL: simpler method of obtaining basic checkboxes
	 */
	function checkbox_basic_input( $field_name, $field_checked, $field_label, $field_params = array() )
	{
		$field_params = array_merge( array(
				'name'  => $field_name,
				'type'  => 'checkbox',
				'value' => 1,
				'class' => $this->inputclass_checkbox, // 'checkbox'
			), $field_params );

		if( $field_checked )
		{
			$field_params['checked'] = 'checked';
		}

		echo $this->checkbox_basic_start;
		echo $this->get_input_element( $field_params );
		echo $field_label;
		echo $this->checkbox_basic_end;
	}


	/**
	 * Builds a checkbox field
	 *
	 * @param string the name of the checkbox
	 * @param boolean indicating if the checkbox must be checked
	 * @param string label
	 * @param string note
	 * @param string CSS class
	 * @param string value to use
	 * @param boolean an optional indicating whether the box is disabled or not
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function checkbox( $field_name, $field_checked, $field_label, $field_note = '',
											$field_class = '', $field_value = 1, $field_disabled = false )
	{
		$field_params = array();

		if( $field_note !== '' )
		{
			$field_params['note'] = $field_note;
		}
		if( $field_class !== '' )
		{
			$field_params['class'] = $field_class;
		}
		if( $field_value !== 1 )
		{
			$field_params['value'] = $field_value;
		}
		if( $field_disabled != false )
		{
			$field_params['disabled'] = 'disabled';
		}

		return $this->checkbox_input( $field_name, $field_checked, $field_label, $field_params );
	}


	/**
	 * Return links to check and uncheck all check boxes of the form
	 */
	function check_all()
	{
		// Need to add event click on links at the form end.
		$this->check_all = true;

		$r = '<span class="btn-group">';

		// fp> This is "name=" and I mean it!!! The JS is looking for all elements with this name!
		$r .= '<a name="check_all_nocheckchanges" href="'.regenerate_url().'" class="btn btn-default">'
				//.T_('Check all').' '
				.get_icon( 'check_all', 'imgtag', NULL, true )
				.'</a> <a name="uncheck_all_nocheckchanges" href="'.regenerate_url().'" class="btn btn-default">'
				//.T_('Uncheck all').' '
				.get_icon( 'uncheck_all', 'imgtag', NULL, true ).'</a> '.'&nbsp;';

		$r .= '</span>';

		return $r;
	}


	/**
	 * Builds the form field
	 *
	 * @param string the class to use for the form tag
	 * @param string title to display on top of the form
	 * @param array Additional params to the form element. See {@link $_common_params}.
	 *              These may override class members.
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function begin_form( $form_class = NULL, $form_title = '', $form_params = array() )
	{
		global $use_strict;
		if( $use_strict ) unset( $form_params[ 'target' ] );// target isn't valid for XHTML Strict

		$this->handle_common_params( $form_params, NULL /* "name" attribute is deprecated in xhtml */ );

		if( ! empty( $this->form_name ) )
		{
			$form_params['id'] = $this->form_name;
		}

		// Set non-mandatory attributes if given in $form_params
		if( ! isset( $form_params['enctype'] ) && ! empty( $this->enctype ) )
		{
			$form_params['enctype'] = $this->enctype;
		}

		if( ! isset( $form_params['class'] ) && ! empty( $form_class ) )
		{
			$form_params['class'] = $form_class;
		}
		// Append bootstrap class
		$form_params['class'] = ( empty( $form_params['class'] ) ? '' : $form_params['class'].' ' ).$this->formclass;

		if( ! isset($form_params['method']) )
		{
			$form_params['method'] = $this->form_method;
		}

		if( ! isset( $form_params['action'] ) )
		{
			$form_params['action'] = $this->form_action;
		}

		if( ! empty( $form_params['bozo_start_modified'] ) )
		{
			$bozo_start_modified = true;
			unset( $form_params['bozo_start_modified'] );
		}
		unset( $form_params['disp_edit_categories'] );
		unset( $form_params['edit_form_params'] );
		unset( $form_params['skin_form_params'] );

		if( isset( $form_params['title'] ) )
		{ // Additional title when $form_title has html tags. It is used for js confirmation message on leave the changed form.
			$clear_title = $form_params['title'];
			unset( $form_params['title'] );
		}

		if( isset( $form_params['formstart_class'] ) )
		{ // CSS class for formstart tag
			$formstart_class = $form_params['formstart_class'];
			unset( $form_params['formstart_class'] );
		}

		if( $this->form_type == 'div' )
		{ // Use <div> tag instead of <form>
			unset( $form_params['action'] );
			unset( $form_params['method'] );
			unset( $form_params['enctype'] );
			$r = "\n\n<div".get_field_attribs_as_string( $form_params ).">\n";
		}
		else
		{ // Standard form
			if( $form_params['action'] == '' )
			{ // Remove action attribute instead of an empty one which is not HTML4/5 compliant
				unset( $form_params['action'] );
			}
			$r = "\n\n<form".get_field_attribs_as_string( $form_params ).">\n";
		}

		// $r .= '<div>'; // for XHTML (dh> removed 'style="display:inline"' because it's buggy with FireFox 1.0.x, at least at the "Write" admin page; see http://forums.b2evolution.net/viewtopic.php?t=10130)
		// fp> inline was needed for inline forms like the DELETE confirmation.
		// fp> why does XHTML require all forms to have an embedded DIV?

		if( isset( $formstart_class ) )
		{
			$r .= str_replace( '$formstart_class$', $formstart_class, $this->formstart );
		}
		else
		{
			$r .= $this->formstart;
		}

		if( empty( $form_title ) )
		{
			if( empty($this->global_icons) )
			{	// No title, no icons:
				$r .= $this->replace_vars( $this->no_title_no_icons_fmt );
			}
			else
			{ // No title, but there are icons:
				$r .= $this->replace_vars( $this->no_title_fmt );
			}
		}
		else
		{	// Title and icons:
			$this->title = $form_title;

			$r .= $this->replace_vars( $this->title_fmt );
		}


		if( $this->form_type == 'form' )	// DO not do this for div's
		{	// Initialization of javascript vars used to create parent_child select lists
			// fp>yura: TODO: does this make sense to add it to every form??
			$r .= '<script type="text/javascript">
								var nb_dynamicSelects = 0;
								var tab_dynamicSelects = Array();
						</script>';
		}


		global $UserSettings;
		if( isset( $UserSettings ) && $UserSettings->get( 'control_form_abortions' )
			&& preg_match( '#^(.*)_checkchanges#', $this->form_name ) )
		{ // This form will trigger the bozo validator, preset a localized bozo confirm message:

			$r .= '<script type="text/javascript">
					if( typeof bozo == "object" )
					{ // If Bozo validator is active:
						bozo.confirm_mess = \'';
			$js_form_title = trim( strip_tags( empty( $clear_title ) ? ( empty( $this->title ) ? '' : $this->title ) : $clear_title ) );
			if( empty( $js_form_title ) )
			{ // No form title:
				$r .= TS_( 'You have modified this form but you haven\'t submitted it yet.\nYou are about to lose your edits.\nAre you sure?' );
			}
			else
			{ // with form title:
				$r .= sprintf( TS_( 'You have modified the form \"%s\"\nbut you haven\'t submitted it yet.\nYou are about to lose your edits.\nAre you sure?' ), format_to_js( $js_form_title ) );
			}

			$r .= '\';';

			if( ! empty( $bozo_start_modified ) )
			{
				$r .= '
					// Update number of changes for this form:
					bozo.tab_changes["'.$this->form_name.'"] = 1;
					// Update Total # of changes:
					bozo.nb_changes++;
				';
			}


			$r .='	}
				</script>';
		}

		return $this->display_or_return( $r );
	}


	/**
	 * Ends the form, optionally displays buttons and closes all open fieldsets.
	 *
	 * @param array Optional array to display the buttons before the end of the form, see {@link buttons_input()}
	 * @return true|string true (if output) or the generated HTML if not outputting.
	 */
	function end_form( $buttons = array() )
	{
		if( $this->_form_ended )
		{	// The form is already ended
			return;
		}

		$r = '';
		if( !empty( $buttons ) )
		{
			$save_output = $this->output;
			$this->output = 0;

			$r .= $this->buttons( $buttons ); // converts old-style to new style, through convert_button_to_field_params()

			$this->output = $save_output;
		}

		while( $this->_opentags['fieldset']-- > 0 )
		{
			$r .= "\n</fieldset>\n";
		}

		$r .= $this->formend;

		// Add hiddens extracted from Form action:
		if( ! empty($this->possible_hiddens_from_action) )
		{
			foreach($this->possible_hiddens_from_action as $pair)
			{
				list($name, $value) = $pair;
				if( ! in_array($name, $this->included_input_field_names) )
				{
					$this->hidden($name, $value);
				}
			}
		}

		// Display all buffered hidden fields in a 0 height DIV (for XHTML):
		$r .= '<div class="inline">'.implode( '', $this->hiddens ).'</div>';

		if( $this->form_type == 'div' )
		{ // Use <div> tag instead of <form>:
			$r .= "\n</div>\n\n";
		}
		else
		{ // Standard form:
			$r .= "\n</form>\n\n";

			// When the page loads, Initialize all the parent child select lists + other javascripts
			$r .= '
				<script type="text/javascript">
					//<![CDATA[
					if( typeof init_dynamicSelect == "function" )
					{
						jQuery( document ).bind( "ready", init_dynamicSelect );
						';
						if( $this->check_all )
						{ // Init check_all event on check_all links
							$r .= 'jQuery( document ).bind( "ready", init_check_all );';
						}
						$r .= '
					}
					';

					if( $this->append_javascript )
					{ // Append Javascript that we have added
						$r .= implode( "\n", $this->append_javascript );
					}
					$r .= '
					//]]>
				</script>';
		}

		// Reset (in case we re-use begin_form! NOTE: DO NOT REUSE begin_form, it's against the spec.)
		$this->hiddens = array();
		$this->_form_ended = true;

		return $this->display_or_return( $r );
	}


	/**
	 * Builds a checkbox list
	 *
	 * the two-dimension array must indicate, for each checkbox:
	 *  - the name,
	 *  - the value -- in practice this will often be just 1
	 *  - the comment to put between <input> and <br />
	 *  - "effective value": a boolean indicating whether the box should be checked or not on display
	 *  - an optional boolean indicating whether the box is disabled or not
	 *  - an optional note
	 *  - an optional class (html attribute)
	 *  - an optional boolean TRUE - to print out an option as hidden field instead of checkbox
	 *  - an optional array of additional attributes for the option label
	 *
	 * @todo Transform to $field_params schema.
	 * @param array a two-dimensional array containing the parameters of the input tag
	 * @param string name
	 * @param string label
	 * @param boolean true to surround checkboxes if they are required
	 * @param boolean true add a surround_check span, used by check_all mouseover
	 * @param array Params
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function checklist( $options, $field_name, $field_label, $required = false, $add_highlight_spans = false, $field_params = array() )
	{
		$field_params = array_merge( array(
				'wide' => false,
				'type' => 'checkbox',
				'input_prefix' => '',
				'input_suffix' => '',
			), $field_params );

		$this->handle_common_params( $field_params, $field_name, $field_label );

		$r = $this->begin_field( $field_name, $field_label );

		foreach( $options as $option )
		{ //loop to construct the list of 'input' tags

			$loop_field_name = $option[0];

			if( ! empty( $option[7] ) )
			{	// Print out this checkbox as hidden field:
				if( $option[3] )
				{	// Only if it is checked:
					$this->hidden( $loop_field_name, 1 );
				}
				continue;
			}

			// Start of checkbox option for multi line format
			$r .= $this->checkbox_newline_start;

			$loop_field_note = empty( $option[5] ) ? '' : $option[5];

			// extra params for checklist option label
			$extra_attribs = '';
			if( ! empty( $option[8] ) )
			{
				$extra_attribs = ' '.get_field_attribs_as_string( $option[8] );
			}

			// asimo>> add id for label: id = label_for_fieldname_fieldvalue
			$r .= '<label'.( empty( $option[6] ) ? '' : ' class="'.$option[6].'"' ).' id="label_for_'.$loop_field_name.'_'.$option[1].'"'.$extra_attribs.'>';

			if( $add_highlight_spans )
			{ // Need it to highlight checkbox for check_all and uncheck_all mouseover
				$r .= '<span name="surround_check" class="checkbox_surround_init">';
				$after_field_highlight = '</span>';
			}
			else
			{
				$after_field_highlight = '';
			}

			$after_field = '';
			if( param_has_error( $field_name ) )
			{ // There is an error message for this field, we want to mark the checkboxes with a red border:
				$r .= '<span class="checkbox_error">';
				$after_field .= '</span>';
			}
			if( $required )
			{	//this field is required
				$r .= '<span class="checkbox_required">';
				$after_field .= '</span>';
			}

			$r .= "\t".'<input type="checkbox" name="'.$loop_field_name.'" value="'.$option[1].'" ';
			if( $option[3] )
			{ //the checkbox has to be checked by default
				$r .= ' checked="checked" ';
			}
			if( isset( $option[4] ) && $option[4] )
			{ // the checkbox has to be disabled
				$r .= ' disabled="disabled" ';
			}
			$r .= ' class="'.$this->inputclass_checkbox.'" />';

			$r .= $after_field;

			$r .= $after_field_highlight;

			$r .= ' '.$option[2];

			$r .='</label>';

			if( ! empty( $loop_field_note ) )
			{ // We want to display a note:
				$r .= sprintf( $this->note_format, $loop_field_note );
			}

			// End of checkbox option for multi line format
			$r .= $this->checkbox_newline_end;
		}

		$r .= $field_params['input_suffix'];

		$r .= $this->end_field();

		return $this->display_or_return( $r );
	}


	/**
	 * Display a select field and populate it with a callback function.
	 *
	 * @param string field name
	 * @param string default field value
	 * @param callback callback function
	 * @param string field label to be display before the field
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              Nothing yet.
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_input( $field_name, $field_value, $field_list_callback, $field_label, $field_params = array() )
	{
		$field_options = call_user_func( $field_list_callback, $field_value );

		return $this->select_input_options( $field_name, $field_options, $field_label, '', $field_params );
	}


	/**
	 * Display a select field and populate it with a callback function.
	 *
	 * @param string field name
	 * @param string default field value
	 * @param callback callback function
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param string CSS class for select
	 * @param string Javascript to add for onchange event (trailing ";").
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select(
		$field_name,
		$field_value,
		$field_list_callback,
		$field_label,
		$field_note = '',
		$field_class = '',
		$field_onchange = NULL )
	{
		$field_params = array();
		if( $field_note !== '' )
		{
			$field_params['note'] = $field_note;
		}
		if( $field_class !== '' )
		{
			$field_params['class'] = $field_class;
		}
		if( isset($field_onchange) )
		{
			$field_params['onchange'] = $field_onchange;
		}

		return $this->select_input( $field_name, $field_value, $field_list_callback, $field_label, $field_params );
	}


	/**
	 * Display a select field and populate it with a cache object by using a callback
	 * method.
	 *
	 * @uses select_input_options()
	 * @param string Field name
	 * @param string Default field value
	 * @param DataObjectCache Cache containing values for list
	 * @param string Field label to be display with the field
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - 'allow_none': allow to select [none] in list (boolean, default false)
	 *              - 'object_callback': Object's callback method name (string, default 'get_option_list')
	 *              - 'loop_object_method': The method on the objects inside the callback (string, default NULL)
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_input_object( $field_name, $field_value, & $field_object, $field_label, $field_params = array() )
	{
		if( isset($field_params['allow_none']) )
		{
			$allow_none = $field_params['allow_none'];
			unset( $field_params['allow_none'] );
		}
		else
		{
			$allow_none = false;
		}

		if( isset($field_params['object_callback']) )
		{
			$field_object_callback = $field_params['object_callback'];
			unset( $field_params['object_callback'] );
		}
		else
		{
			$field_object_callback = 'get_option_list';
		}

		$field_options = '';
		if( isset( $field_params['prepend_options'] ) )
		{	// Prepend additional options before cached object (Used to use several none options):
			$field_options .= $this->get_select_options_string( $field_params['prepend_options'], $field_value, true );
			unset( $field_params['prepend_options'] );
		}

		if( isset($field_params['loop_object_method']) )
		{
			$field_options .= $field_object->$field_object_callback( $field_value, $allow_none, $field_params['loop_object_method'] );
			unset( $field_params['loop_object_method'] );
		}
		else
		{
			$field_options .= $field_object->$field_object_callback( $field_value, $allow_none );
		}

		if( isset($field_params['note']) )
		{
			$field_note = $field_params['note'];
			unset( $field_params['note'] );
		}
		else
		{
			$field_note = '';
		}

		return $this->select_input_options( $field_name, $field_options, $field_label, $field_note, $field_params );
	}


	/**
	 * Display counrty select field and populate it with a cache object by using a callback
	 * method.
	 *
	 * @uses select_input_options()
	 * @param string Field name
	 * @param string Default field value
	 * @param DataObjectCache Cache containing values for list
	 * @param string Field label to be display with the field
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - 'allow_none': allow to select [none] in list (boolean, default false)
	 *              - 'object_callback': Object's callback method name (string, default 'get_option_list')
	 *              - 'loop_object_method': The method on the objects inside the callback (string, default NULL)
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_country( $field_name, $field_value, & $field_object, $field_label, $field_params = array() )
	{
		global $edited_User;

		if( isset($field_params['allow_none']) )
		{
			$allow_none = $field_params['allow_none'];
			unset( $field_params['allow_none'] );
		}
		else
		{
			$allow_none = false;
		}

		if( isset($field_params['object_callback']) )
		{
			$field_object_callback = $field_params['object_callback'];
			unset( $field_params['object_callback'] );
		}
		else
		{
			if (empty($edited_User->ctry_ID))
			{	// if edited user didn't select a country then we form a dropdown list of countries using 'get_group_country_option_list' function.
				$field_object_callback = 'get_group_country_option_list';
			}
			else
			{	// if edited user has a selected country then we display a usual list of countries.
				$field_object_callback = 'get_option_list';
			}

		}

		if( isset($field_params['loop_object_method']) )
		{
			$field_options = $field_object->$field_object_callback( $field_value, $allow_none, $field_params['loop_object_method'] );
			unset( $field_params['loop_object_method'] );
		}
		else
		{
			$field_options = $field_object->$field_object_callback( $field_value, $allow_none );
		}

		if( isset($field_params['note']) )
		{
			$field_note = $field_params['note'];
			unset( $field_params['note'] );
		}
		else
		{
			$field_note = '';
		}

		return $this->select_input_options( $field_name, $field_options, $field_label, $field_note, $field_params );
	}
	/**
	 * Display a select field and populate it with a cache object.
	 *
	 * @uses select_input_object()
	 * @param string field name
	 * @param string default field value
	 * @param DataObjectCache Cache containing values for list
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param boolean allow to select [none] in list
	 * @param string CSS class for select
	 * @param string Object's callback method name.
	 * @param string Javascript to add for onchange event (trailing ";").
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_object(
		$field_name,
		$field_value,
		& $field_object,
		$field_label,
		$field_note = '',
		$allow_none = false,
		$field_class = '',
		$field_object_callback = 'get_option_list',
		$field_onchange = NULL )
	{
		$field_params = array(
			'note' => $field_note,
			'allow_none' => $allow_none,
			'class' => $field_class,
			'object_callback' => $field_object_callback,
			'onchange' => $field_onchange );

		return $this->select_input_object( $field_name, $field_value, $field_object, $field_label, $field_params  );
	}


	/**
	 * Display a select field and populate it with a cache object.
	 *
	 * @param string field name
	 * @param string string containing options '<option>...</option>'
	 * @param string field label to be display before the field
	 * @param string "help" note (Should provide something useful, otherwise leave it empty)
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - 'label': Field label to be display before the field
	 *              - 'class': CSS class for select
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_input_options( $field_name, $field_options, $field_label, $field_note = '', $field_params = array() )
	{
		$this->handle_common_params( $field_params, $field_name, $field_label, $field_note );

		$r = $this->begin_field();
		if( !empty( $field_params['parent'] ) )
		{	// need to display an arrow to show that this select list options has a preselection from a parent
			$r .= get_icon( 'multi_action' );
		}

		$field_params['class'] = ( empty( $field_params['class'] ) ? '' : $field_params['class'].' ' ).'form-control';

		$r .="\n<select".get_field_attribs_as_string($field_params).'>'
			 .$field_options
			 ."</select>\n";

		$r .= $this->end_field();

		if( !empty( $field_params['parent'] ) )
		{ // Set up the dynamic preselection array from the parent to this select list options
			$r .= "<script type='text/javascript'>
								tab_dynamicSelects[nb_dynamicSelects] = Array();
								tab_dynamicSelects[nb_dynamicSelects]['parent'] = '".$field_params['parent']."';
								tab_dynamicSelects[nb_dynamicSelects]['child'] = '$field_name';
								nb_dynamicSelects++;
							</script>";
		}

		return $this->display_or_return( $r );
	}


	/**
	 * Display a select field and populate it with a cache object.
	 *
	 * @uses select_input_options()
	 * @param string field name
	 * @param string string containing options
	 * @param string field label to be display before the field
	 * @param string note to be displayed after the field
	 * @param string CSS class for select
	 * @param string Javascript to add for onchange event (trailing ";").
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_options(
		$field_name,
		$field_options,
		$field_label,
		$field_note = NULL,
		$field_class = NULL,
		$field_onchange = NULL )
	{
		$field_params = array(
			'note' => $field_note,
			'class' => $field_class,
			'onchange' => $field_onchange,
			 );

		return $this->select_input_options( $field_name, $field_options, $field_label, '', $field_params );
	}


	/**
	 * This is a stub for {@link select_input_options()} which builds the required list
	 * of <option> elements from a given list of options ($field_options) and
	 * the selected value ($field_value).
	 *
	 * @uses select_input_options()
	 * @param string field name
	 * @param mixed Initial value
	 * @param array Options. If an associative key (string) is used, this gets the value attribute.
	 *              NOTE: numeric strings get converted to integers by PHP!
	 * @param string Field label to be display before the field
	 * @param string Note
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - 'force_keys_as_values': Use the key of $field_options for "value" attrib always.
	 *              - Plus all of {@link select_input_options()}.
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function select_input_array( $field_name, $field_value, $field_options, $field_label, $field_note = NULL, $field_params = array() )
	{
		if( isset($field_params['force_keys_as_values']) )
		{
			$force_keys_as_values = $field_params['force_keys_as_values'];
			unset($field_params['force_keys_as_values']); // not an attribute to <select>
		}
		else
		{
			$force_keys_as_values = false;
		}

		if( isset($field_params['background_color']) )
		{
			$color_array = $field_params['background_color'];
			unset($field_params['background_color']); // not an attribute to <select>
		}
		else
		{
			$color_array = false;
		}


		// Build $options_list
		$options_list = Form::get_select_options_string($field_options, $field_value, $force_keys_as_values, $color_array);

		return $this->select_input_options( $field_name, $options_list, $field_label, $field_note, $field_params );
	}


	/**
	 * Get the OPTION list as string for use in a SELECT.
	 *
	 * @param array Options (key => value)
	 * @param string Selected value (if any)
	 * @param boolean Force keys from $options as values? (Default: false, only array keys,
	 *                which are strings will be used).
	 * @return string
	 */
	static function get_select_options_string($field_options, $field_value = NULL, $force_keys_as_values = false, $color_array = false)
	{
		$r = '';

		foreach( $field_options as $l_key => $l_option )
		{
			if( is_array( $l_option ) )
			{	// If option is array then it is a group of the options:
				$r .= Form::get_select_group_options_string( array( $l_key => $l_option ), $field_value, $force_keys_as_values, $color_array );
				continue;
			}

			// Get the value attribute from key if is_string():
			$l_value = ($force_keys_as_values || is_string($l_key)) ? $l_key : $l_option;

			if (empty($color_array) || !isset($color_array[$l_value]))
			{
				$r .= '<option value="'.format_to_output($l_value, 'formvalue').'"';
			}
			else
			{
				$r .= '<option style="background-color: #'.$color_array[$l_value].'" value="'.format_to_output($l_value, 'formvalue').'"';
			}
			if(
					( is_array( $field_value ) && in_array( $l_value, $field_value ) ) ||
					( !is_array( $field_value ) && (string)$l_value == (string)$field_value ) // cast to string so "1,2" is != 1
				)
			{
				$r .= ' selected="selected"';
			}

			$r .= '>'.format_to_output($l_option).'</option>';
		}
		return $r;
	}


	/**
	 * Get the grouped OPTION list as string for use in a SELECT.
	 *
	 * @param array Groups ( title => array( Options (key => value) )
	 * @param string Selected value (if any)
	 * @param boolean Force keys from $options as values? (Default: false, only array keys,
	 *                which are strings will be used).
	 * @return string
	 */
	static function get_select_group_options_string( $group_options, $field_value = NULL, $force_keys_as_values = false, $color_array = false )
	{
		$r = '';

		foreach( $group_options as $group_title => $group_options )
		{
			$r .= '<optgroup label="'.format_to_output( $group_title, 'htmlattr' ).'">';
			$r .= Form::get_select_options_string( $group_options, $field_value, $force_keys_as_values, $color_array );
			$r .= '</optgroup>';
		}

		return $r;
	}


	/**
	 * Combo box
	 * Display a select options list with an option 'new',
	 * and when this one is seleted, display a combo input text to add a new value
	 *
	 * @param string field name
	 * @param string field value
	 * @param string containing options
	 * @param string field label
	 * @param array Optional params
	 *
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function combo_box( $field_name, $field_value, $field_options, $field_label, $field_params = array() )
	{
		$input_class = 'form-control input-sm';
		if( param_has_error( $field_name) )
		{ // There is an error on the combo, so we need to set the combo input text class to 'field_error'
			$input_class .= ' field_error';
		}
		elseif( isset( $field_params['required'] ) && $field_params['required'] )
		{ // The field is required, so update its class:
			$input_class .= ' field_required';
		}
		unset( $field_params['required'] ); // already handled above, do not pass to handle_common_params()

		// Set size param for input with new value
		if( isset( $field_params['new_field_size'] ) )
		{
			$new_field_size = $field_params['new_field_size'];
		}
		else
		{
			$new_field_size = 30;
		}
		unset( $field_params['new_field_size'] );

		// Set onchange event on the select, when the select changes, we check the value to display or hide an input text after it
		$field_params['onchange']= 'check_combo( this.id, this.options[this.selectedIndex].value, "'.$input_class.'")';

		$this->handle_common_params( $field_params, $field_name, $field_label );

		$r = $this->begin_field();

		// Select option to add after the select list a combo input text:
		$option_new  = '<option value="new">'.T_('New').': </option>'."\n";

		// Add the new option to the select list:
		$field_options = $option_new . $field_options;

		$field_params['class'] = ( empty( $field_params['class'] ) ? '' : $field_params['class'].' ' ).'form-control input-sm';

		// Select list
		$r .="\n<select".get_field_attribs_as_string($field_params).'>'
			 .$field_options
			 ."</select>\n";

		if( $field_options == $option_new  || strpos( $input_class, 'field_error' ) !== false || $field_value != '' )
		{	// The list is empty or there is an error on the combo or no field value, so we have to display the input text:
			$visible = 'inline';
		}
		else
		{ // Hide the input text:
			$visible = 'none' ;
		}

		$r .= '<input type="text" id="'.$field_name.'_combo" name="'.$field_name.'_combo" size="'.$new_field_size.'" class="'.$input_class.'" style="display:'.$visible.'" value="'.$field_value.'" />';

		if( $visible == 'none' )
		{ // The input text is hidden, so if no javascript activated, we always display input text:
			$r .= '<script type="text/javascript"></script>'; // We need <script> tag here to use a <noscript> tag when javascript is deactivated:
			$r .= '<noscript>
							<input type="text" id="'.$field_name.'_combo" name="'.$field_name.'_combo" size="30" class="'.$input_class.'">
						</noscript>';
		}

		$r .= $this->end_field();

		return $this->display_or_return( $r );
	}


	/**
	 * Build a text area.
	 *
	 * @param string Name of the field
	 * @param string Value of the field
	 * @param integer Number of rows
	 * @param string Label for the field
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - 'cols': Number of columns (integer, default 50)
	 *              - 'note_format': Format of the note (%s being replaced by the note)
	 *              - 'format_value': Format of value content, see {@link format_to_output()} (string, default 'formvalue')
	 */
	function textarea_input( $field_name, $field_value, $field_rows, $field_label, $field_params = array() )
	{
		global $rsc_url;

		// Default params
		$field_params += array(
			'cols' => 50,
			'note_format' => $this->note_format, // handled as common param
			'format_value' => 'formvalue'
		);

		$format_value = $field_params['format_value'];
		unset( $field_params['format_value'] ); // no HTML attrib

		if( isset( $field_params['input_prefix'] ) )
		{ // Some text should be displayed before textarea element
			$input_prefix = $field_params['input_prefix'];
			unset( $field_params['input_prefix'] );
		}
		else
		{ // Nothing before textarea element
			$input_prefix = '';
		}

		$this->handle_common_params( $field_params, $field_name, $field_label );

		// Give it a class, so it can be selected for CSS in IE6
		$field_params['class'] = ( empty( $field_params['class'] ) ? '' : $field_params['class'].' ' ).'form_textarea_input form-control';

		if( isset($field_params['maxlength']) )
		{ // attach event to the textarea to accomplish max length:
			$this->append_javascript['textarea_maxlength'.$field_name] = '
				if( typeof jQuery == "function" )
				{
				jQuery("#'.$field_params['id'].'").bind( "keyup", function(event)
					{
						if( this.value.length > '.$field_params['maxlength'].' )
						{
							this.value = this.value.substr(0,'.$field_params['maxlength'].');
							event.preventDefault();
						}
					} );
				}';
			unset($field_params['maxlength']); // not a HTML attribute for textarea
		}

		$r = $this->begin_field();
		$r .= $input_prefix;
		$r .= '<textarea'
			.get_field_attribs_as_string( $field_params )
			.' rows="'.$field_rows.'">'
			.format_to_output( $field_value, $format_value )
			.'</textarea>';
		$r .= $this->end_field();

		return $this->display_or_return( $r );
	}


	/**
	 * Build a text area.
	 *
	 * @uses textarea_input()
	 *
	 * @param string
	 * @param string
	 * @param integer
	 * @param string
	 * @param string
	 * @param integer
	 * @param string
	 * @param boolean
	 * @param string Placeholder text
	 */
	function textarea( $field_name, $field_value, $field_rows, $field_label, $field_note = '', $field_cols = 50 , $field_class = '', $required = false, $placeholder = '' )
	{
		$field_params = array(
			'note' => $field_note,
			'cols' => $field_cols,
			'class' => $field_class);

		if( $placeholder != '' )
		{
			$field_params['placeholder'] = $placeholder;
		}

		if( $required )
		{ // Set required only for case TRUE, because in the following code we have a condition "isset($required)" instead of "$required == true"
			$field_params['required'] = $required;
		}

		return $this->textarea_input( $field_name, $field_value, $field_rows, $field_label, $field_params );
	}


	/**
	 * Builds an info field.
	 * An info field is a fieldset containing a label div and an info div.
	 *
	 * {@internal
	 * NOTE: we don't use {@link begin_field()} here, because the label is meant
	 * to be always on the left and this avoids fiddling with the <label> tag.
	 * }}
	 *
	 * @param string the field label
	 * @param string the field info
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - 'format_info': Format of info content, see {@link format_to_output()} (string, default 'htmlbody')
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function info_field( $field_label, $field_info, $field_params = array() )
	{
		$field_params = array_merge( array(
				'note_format' => ' <small class="notes">%s</small>',
			), $field_params );

		if( isset($field_params['format_info']) )
		{
			$format_info = $field_params['format_info'];
			unset($field_params['format_info']); // not an HTML element
		}
		else
		{
			$format_info = 'htmlbody';
		}

		$this->handle_common_params( $field_params, NULL, $field_label );

		$r = $this->fieldstart;

		// Start the new form field and inject an automatic DOM id
		// This is useful to show/hide the whole field by JS.
		if( !empty( $this->_common_params['id'] ) )
		{
			$ffield_id = ' id="ffield_'.$this->_common_params['id'].'" ';
		}
		else
		{ // No ID in case there's no id/name given for a field.
			$ffield_id = '';
		}
		$r = $this->fieldstart;
		if( !empty( $field_params['class'] ) )
		{
			if( strpos( $r, 'class="' ) === false )
			{ // Add class attribute
				$ffield_id .= ' class="'.$field_params['class'].'"';
			}
			else
			{ // Append classes to attribute
				$r = str_replace( ' class="', ' class="'.$field_params['class'].' ', $r );
			}
		}
		$r = str_replace( '$ID$', $ffield_id, $r );

		$r .= $this->get_label();

		$r .= $this->infostart;

		// PAYLOAD:
		$r .= format_to_output( $field_info, $format_info );


		// Taken from end_field() - but we use $infoend:
		if( !empty($this->_common_params['note']) )
		{ // We have a note
			$r .= sprintf( $this->_common_params['note_format'], $this->_common_params['note'] );
		}

		if( isset($this->_common_params['field_suffix']) )
		{
			$r .= $this->_common_params['field_suffix'];
		}

		$r .= $this->infoend.$this->fieldend;

		return $this->display_or_return( $r );
	}


	/**
	 * Builds an info field.
	 * An info field is a fieldset containing a label div and an info div.
	 *
	 * @param string the field label
	 * @param string the field info
	 * @param string see {@link format_to_output()}
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function info( $field_label, $field_info, $field_note = NULL, $format = 'htmlbody' )
	{
		$field_params = array(
			'note' => $field_note,
			'format_info' => $format );

		return $this->info_field( $field_label, $field_info, $field_params );
	}


	/**
	 * Builds a button list.
	 *
	 * The array contains an associative array for each button (params to {@link button_input()}.
	 *
	 * @param array a two-dimension array containing the elements of the input tags
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function buttons_input( $buttons = array() )
	{
		$r = '';
		$hidden = true; // boolean that tests if the buttons are all hidden

		$save_output = $this->output;
		$this->output = false;

		foreach( $buttons as $l_button )
		{
			if( !isset($l_button['type']) || $l_button['type'] != 'hidden' )
			{ // not a hidden button
				$hidden = false;
			}

			$r .= $this->button_input( $l_button );
		}
		/*
		else
		{ // Default: Save and Reset
			$r .= $this->get_input_element( array(
				'type' => 'submit',
				'value' => T_('Save !'),
				'class' => 'SaveButton',
				'input_prefix => "\t\t\t" );
			$r .= $this->get_input_element( array(
				'type' => 'reset',
				'value' => T_('Reset'),
				'class' => 'ResetButton',
				'input_prefix => "\t\t\t" );
		}*/

		$this->output = $save_output;

		if( ! $hidden )
		{ // there are not only hidden buttons : additional tags
			$r = $this->buttonsstart.$r.$this->buttonsend;
		}

		return $this->display_or_return( $r );
	}


	/**
	 * Builds a button list.
	 *
	 * Array entries with numeric (deprecated) keys are converted to their equivalent string indexes.
	 *
	 * the two-dimension array must contain :
	 *  - the button type
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *  - the style (optional)
	 *
	 * @param array a two-dimension array containing the elements of the input tags
	 * @param boolean to select or not the default display
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function buttons( $buttons = array() )
	{
		$buttons_list = array();

		foreach( $buttons as $l_button )
		{
			$buttons_list[] = $this->convert_button_to_field_params( $l_button );
		}

		return $this->buttons_input( $buttons_list );
	}


	/**
	 * Builds a button.
	 *
	 * You probably want to use {@link buttons_input()}, which uses
	 * {@link $buttonsstart}/{@link $buttonsend} to align the buttons properly.
	 *
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - type: The type attribute (string, default 'submit')
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function button_input( $field_params = array() )
	{
		$field_params = array_merge( array(
				'tag'          => 'input',
				'type'         => 'submit',
				'input_prefix' => "\t\t\t",
				'class'        => '',
			), $field_params );

		if( empty($field_params['type']) )
		{ // default type
			$field_params['type'] = 'submit';
		}

		// Use bootstrap classes for buttons
		$field_params['class'] .= ' btn';
		if( strpos( $field_params['class'], 'btn-' ) === false )
		{ // Only when it is not defined from skin
			if( empty( $this->btn_primary_is_used ) &&
					( strpos( $field_params['class'], 'SaveButton' ) !== false ||
					  strpos( $field_params['class'], 'SaveEditButton' ) !== false ) )
			{ // Submit button
				$field_params['class'] .= ' btn-primary';
				// Set this var to true in order to don't use a primary button twice on one form
				$this->btn_primary_is_used = true;
			}
			elseif( strpos( $field_params['class'], 'ResetButton' ) !== false )
			{ // Reset button
				$field_params['class'] .= ' btn-danger';
			}
			elseif( strpos( $field_params['class'], 'PreviewButton' ) !== false )
			{ // Preview button
				$field_params['class'] .= ' btn-info';
			}
			elseif( strpos( $field_params['class'], 'SmallButton' ) !== false )
			{ // Small button
				$field_params['class'] .= ' btn-xs';
			}
			if( ! preg_match( '/btn\-(primary|success|info|warning|danger)/', $field_params['class'] ) )
			{ // This button is default
				$field_params['class'] .= ' btn-default';
			}
		}

		return $this->display_or_return( $this->get_button_element( $field_params ) );
	}


	/**
	 * Builds a button.
	 *
	 * You probably want to use {@link buttons_input()}, which uses
	 * {@link $buttonsstart}/{@link $buttonsend} to align the buttons properly.
	 *
	 * The array must contain :
	 *  - the button html tag: 'input', 'button'
	 *  - the button type
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *  - the style (optional)
	 *
	 * @param array a two-dimension array containing the elements of the input tags
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function button( $options )
	{
		$field_params = $this->convert_button_to_field_params( $options );

		if( empty($field_params['type']) )
		{
			$field_params['type'] = 'submit'; // default type
		}

		return $this->button_input( $field_params );
	}


	/**
	 * Convert a deprecated, numeric button array to a field_params array.
	 *
	 * @deprecated
	 * @param array A button array like button() and buttons() are getting.
	 * @return array The button array converted to a string indexed button array (field_params).
	 */
	function convert_button_to_field_params( $options )
	{
		$field_params = array();

		foreach( array_keys($options) as $l_key )
		{
			if( is_int($l_key) )
			{
				switch( $l_key )
				{
					case 0: $field_params['type'] = $options[0]; break;
					case 1: $field_params['name'] = $options[1]; break;
					case 2: $field_params['value'] = $options[2]; break;
					case 3: $field_params['class'] = $options[3]; break;
					case 4: $field_params['onclick'] = $options[4]; break;
					case 5: $field_params['style'] = $options[5]; break;
				}
			}
			else
			{
				$field_params[$l_key] = $options[$l_key];
			}
		}

		return $field_params;
	}


	/**
	 * Builds an hidden input tag, overwriting any previous hidden values (except for "foo[]").
	 *
	 * This generates no output and returns nothing: the hidden fields get added to {@link $hiddens},
	 * and get appended to the end of the form.
	 *
	 * @param string Field name
	 * @param string Field value
	 * @param array Optional params. This is e.g. useful for "id".
	 *              See {@link $_common_params}.
	 */
	function hidden( $field_name, $field_value, $field_params = array() )
	{
		if( is_array( $field_value ) )
		{ // this happens for example when we've POSTed an array (for PHP it's an array then)
			foreach( $field_value as $l_key => $l_value )
			{
				// Recursion:
				$this->hidden( $field_name.'['.$l_key.']', $l_value, $field_params );
			}
		}
		else
		{
			$field_params['name'] = $field_name;
			$field_params['type'] = 'hidden';
			$field_params['value'] = $field_value;

			if( strpos($field_name, '[]') )
			{ // array-style name or we don't want to overwrite, just add it:
				$this->hiddens[] = $this->get_input_element( $field_params );
			}
			else
			{
				if( isset($this->existing_hiddens[$field_name]) )
				{
					unset($this->hiddens[$this->existing_hiddens[$field_name]]);
				}

				// add the field and remember that it already exists:
				end($this->hiddens);
				$key = key($this->hiddens)+1;
				$this->hiddens[$key] = $this->get_input_element( $field_params );
				$this->existing_hiddens[$field_name] = $key;
			}
		}
	}


	/**
	 * Add a crumb to the current form
	 *
	 * Use this for all forms leading to DATA CHANGING actions
	 * (anything other than info retrieval), for example:
	 * -search forms don't need this
	 * -create, update, delete actions definitely need this
	 * -change order, change status and more subtle actions also need this
	 *
	 * @param string crumb name
	 */
	function add_crumb( $crumb_name )
	{
		$this->hidden( 'crumb_'.$crumb_name, get_crumb( $crumb_name ) );
	}


	/**
	 * Add the "ctrl" param, used in the backoffice, as a hidden field.
	 */
	function hidden_ctrl()
	{
		global $ctrl;
		if( !empty( $ctrl) )
		{
			$this->hidden( 'ctrl', $ctrl );
		}
	}


	/**
	 * Builds a list of hidden inputs.
	 *
	 * @param array Array of parameters to {@link hidden()}:
	 *               - 0: field_name
	 *               - 1: field_value
	 *               - 2: field_params
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function hiddens( $hiddens )
	{
		$save_output = $this->output;
		$this->output = false;
		foreach( $hiddens as $hidden )
		{
			$this->hidden( $hidden[0], $hidden[1], isset($hidden[2]) ? $hidden[2] : array() );
		}
		$this->output = $save_output;
	}


	/**
	 * Builds a list of hidden inputs from an array where the keys are the field names.
	 *
	 * It supports array values (one-dimensional) and generates appropriate key-value pairs.
	 *
	 * @uses Form::hidden()
	 * @param array associative array ( name => value ) of hidden fields.
	 * @param array|NULL A list of keys to ignore, in addition to {@link $included_input_field_names}.
	 */
	function hiddens_by_key( $hiddens, $exclude = NULL )
	{
		if( $this->output )
		{ // only save output once, if necessary (recursion!)
			$save_output = $this->output;
			$this->output = false;
		}

		if( is_null($exclude) )
		{
			$exclude = $this->included_input_field_names;
		}
		else
		{
			$exclude = array_merge($this->included_input_field_names, $exclude);
		}

		foreach( $hiddens as $l_name => $l_value )
		{
			if( isset($exclude) && in_array( $l_name, $exclude ) )
			{
				continue;
			}
			if( is_array( $l_value ) )
			{ // this happens for example when we've POSTed an array (for PHP it's an array then)
				foreach( $l_value as $ll_key => $ll_value )
				{
					// Recursion:
					$this->hiddens_by_key( array( $l_name.'['.$ll_key.']' => $ll_value ), $exclude );
				}
			}
			else
			{
				$this->hidden( $l_name, $l_value );
			}
		}

		if( isset($save_output) )
		{
			$this->output = $save_output;
		}
	}


	/**
	 * Builds a submit input tag.
	 *
	 * You probably want to use {@link buttons_input()}, which uses
	 * {@link $buttonsstart}/{@link $buttonsend} to align the buttons properly.
	 *
	 * the array must contain :
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *  - the style (optional)
	 *
	 * @param array Optional params. See {@link $_common_params}.
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function submit_input( $field_params = array() )
	{
		$field_params['type'] = 'submit';
		return $this->button_input( $field_params );
	}


	/**
	 * Builds a submit input tag
	 *
	 * You probably want to use {@link buttons_input()}, which uses
	 * {@link $buttonsstart}/{@link $buttonsend} to align the buttons properly.
	 *
	 * the array must contain :
	 *  - the name (optional)
	 *  - the value (optional)
	 *  - the class (optional)
	 *  - the onclick attribute (optional)
	 *  - the style (optional)
	 *
	 * @param array an array containing the elements of the input tags
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function submit( $options )
	{
		array_unshift( $options, 'submit' );
		return $this->button( $options );
	}


	/**
	 * Generate set of radio options.
	 *
	 * @param string The name of the radio options
	 * @param string The checked option's value
	 * @param array of arrays The radio options
	 *        Keys:
	 *         - 'value' (required)
	 *         - 'label' (required)
	 *         - 'note'
	 *         - 'type' (default: "radio")
	 *         - 'class' (default: "radio")
	 *         - 'checked' (default: 'value' gets compared to $field_value)
	 *         - 'name' (default: $field_name)
	 *         - 'suffix' (gets used after the radio's label)
	 *         - Plus everything for {@link get_input_element()} )
	 * @param string Label
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - lines: Options on seperate lines (DIVs) (boolean, default false)
	 *              NOTE: these params/attribs get used as default for every INPUT field,
	 *                    overridden by $field_options
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function radio_input( $field_name, $field_value, $field_options, $field_label, $field_params = array() )
	{
		$field_params = array_merge( array(
				'note_format' => $this->note_format,
			), $field_params );

		if( isset( $field_params['lines'] ) )
		{
			$field_lines = $field_params['lines'];
			unset( $field_params['lines'] ); // no HTML attribute
		}
		else
		{
			$field_lines = false;
		}

		if( $field_lines )
		{
			$field_params['note_format'] = '<div>'.$field_params['note_format'].'</div>';
		}

		$field_params['id'] = false; // No ID attribute for the label
		$this->handle_common_params( $field_params, $field_name, $field_label );
		unset($field_params['id']);  // unset, so it gets handled correctly as default below

		$r = $this->begin_field( NULL, NULL, false, 'radio' );

		/*
		 * Build options list:
		 */
		$count_options = 0; // used for unique IDs (label/radio)
		foreach( $field_options as $loop_radio )
		{
			// Merge defaults from $field_params:
			$loop_radio = array_merge( $field_params, $loop_radio );

			if( $field_lines )
			{ // Start of radio option for multi line format
				$r .= $this->radio_newline_start;
			}
			else
			{ // Start of radio option for single line format
				$r .= $this->radio_oneline_start;
			}

			// Defaults:
			if( ! isset( $loop_radio['type'] ) )  $loop_radio['type'] = 'radio';
			if( ! isset( $loop_radio['class'] ) ) $loop_radio['class'] = $this->inputclass_radio; // 'radio'
			if( ! isset( $loop_radio['name'] ) )  $loop_radio['name'] = $field_name;
			if( ! isset( $loop_radio['id'] ) )
			{ // build unique id:
				$loop_radio['id'] = Form::get_valid_id( $field_params['name'].'_radio_'.( ++$count_options ) );
			}

			if( isset($loop_radio['checked']) )
			{ // convert boolean:
				if( $loop_radio['checked'] ) $loop_radio['checked'] = 'checked';
			}
			elseif( $field_value == $loop_radio['value'] )
			{ // Current selection:
				$loop_radio['checked'] = 'checked';
			}

			// Unset non-HTML attribs:
			$label = $loop_radio['label'];
			$note = isset($loop_radio['note']) ? $loop_radio['note'] : null;
			$suffix = isset($loop_radio['suffix']) ? $loop_radio['suffix'] : '';
			unset( $loop_radio['label'], $loop_radio['note'], $loop_radio['suffix'] );

			// the radio element:
			$r .= $this->get_input_element( $loop_radio, false );

			// the label:
			$r .= str_replace( array( '$radio_option_ID$', '$radio_option_label$' ), array( $loop_radio['id'], $label ), $this->radio_label_format );

			if( ! empty( $note ) )
			{ // Add a note for the current radio option:
				$r .= sprintf( $this->note_format, $note );
			}

			// optional text for radio option (like additional fieldsets or input boxes)
			$r .= $suffix;

			// Split radio options by whitespace:
			$r .= "\n";

			if( $field_lines )
			{ // End of radio option for multi line format
				$r .= $this->radio_newline_end;
			}
			else
			{ // End of radio option for single line format
				$r .= $this->radio_oneline_end;
			}
		}

		$r .= $this->end_field( 'radio' );

		return $this->display_or_return( $r );
	}


	/**
	 * Generate set of radio options.
	 *
	 * @param string the name of the radio options
	 * @param string the checked option
	 * @param array of arrays the radio options (0: value, 1: label, 2: notes, 3: additional HTML [input field, ..], 4: attribs for <input tag> )
	 * @param string label
	 * @param boolean options on seperate lines (DIVs)
	 * @param string notes
	 * @param boolean required
	 * @return mixed true (if output) or the generated HTML if not outputting
	 */
	function radio( $field_name, $field_value, $field_options, $field_label, $field_lines = false, $field_note = '', $field_required = false )
	{
		$new_field_options = array();

		foreach( $field_options as $l_key => $l_options )
		{
			$new_field_options[$l_key] = array(
				'value' => $l_options[0],
				'label' => $l_options[1] );

			if( isset($l_options[2]) )
			{
				$new_field_options[$l_key]['note'] = $l_options[2];
			}
			if( isset($l_options[4]) )
			{ // Convert "inline attribs" to "params" array
				preg_match_all( '#(\w+)=[\'"](.*)[\'"]#', $l_options[4], $matches, PREG_SET_ORDER );

				foreach( $matches as $l_set_nr => $l_match )
				{
					$new_field_options[$l_key][$l_match[1]] = $l_match[2];
				}
			}

			if( isset($l_options[3]) )
			{
				$new_field_options[$l_key]['suffix'] = $l_options[3];
			}
		}

		$field_params = array( 'lines' => $field_lines, 'note' => $field_note );
		if( $field_required )
		{	// Field is required
			$field_params['required'] = true;
		}

		return $this->radio_input( $field_name, $field_value, $new_field_options, $field_label, $field_params );
	}


	/**
	 * Builds an interval input fields.
	 *
	 * @deprecated Use $Form->begin_line(); ... $Form->end_line(); instead.
	 *
	 * @param string The name of the input field "From". This gets used for id also, if no id given in $field_params.
	 * @param string Initial value "From"
	 * @param string The name of the input field "To". This gets used for id also, if no id given in $field_params.
	 * @param string Initial value "To"
	 * @param integer Size of the input field
	 * @param string Label displayed with the field
	 * @param string "help" note (Should provide something useful, otherwise leave it empty)
	 * @param array Extended attributes/params.
	 *                 - 'maxlength': if not set, $field_size gets used (use '' to disable it)
	 *                 - 'class': the CSS class to use for the <input> element
	 *                 - 'type': 'text', 'password' (defaults to 'text')
	 *                 - 'force_to': 'UpperCase' (JS onchange handler)
	 *                 - NOTE: any other attributes will be used as is (onchange, onkeyup, id, ..).
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function interval( $field_name_from, $field_value_from, $field_name_to, $field_value_to, $field_size, $field_label, $field_note = '', $field_params = array() )
	{
		$save_output = $this->output;
		$save_params = array(
			'labelempty' => $this->labelempty,
			'fieldstart' => $this->fieldstart,
			'fieldend' => $this->fieldend,
			'inputstart' => $this->inputstart,
			'inputend' => $this->inputend,
		);

		// clear form params
		$this->switch_template_parts( array_fill_keys( array_keys( $save_params ), '' ) );
		$this->output = false;

		if( isset( $field_params['input_prefix'] ) )
		{ // Save an input prefix and append it only to input "From"
			$input_prefix = $field_params['input_prefix'];
			unset( $field_params['input_prefix'] );
		}

		// Field "To"
		$field_params['input_suffix'] = T_(' to ').$this->text_input( $field_name_to, $field_value_to, $field_size, '', '', $field_params );

		// return saved params
		$this->output = $save_output;
		$this->switch_template_parts( $save_params );

		if( isset( $input_prefix ) )
		{ // Display an input prefix before first input "From"
			$field_params['input_prefix'] = $input_prefix;
		}

		// Field "From"
		return $this->text_input( $field_name_from, $field_value_from, $field_size, $field_label, $field_note, $field_params );
	}


	/**
	 * Generate a general input field.
	 *
	 * This is the base function for text_input(), checkbox_input(), ..
	 *
	 * @uses get_input_element() to generate the <input> element
	 *
	 * @param array Optional params. Additionally to {@link $_common_params} you can use:
	 *              - see {@link get_input_element()}
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function input_field( $field_params = array() )
	{
		$field_params = array_merge( array(
				'inline' => false,
			), $field_params );

		$element = $this->get_input_element( $field_params );

		if( $field_params['inline'] )
		{ // Display label and input field in one line
			$this->_common_params['label'] = sprintf( $this->_common_params['label'], $element );

			// Save the form elements:
			$label_suffix = $this->label_suffix;
			$labelstart = $this->labelstart;
			$labelend = $this->labelend;
			$inputstart = $this->inputstart;
			$inputend = $this->inputend;

			// Remove suffix ':' after label and change other form elements for inline mode
			$this->label_suffix = '';
			if( isset( $this->inline_labelstart ) )
			{
				$this->labelstart = $this->inline_labelstart;
			}
			if( isset( $this->inline_labelend ) )
			{
				$this->labelend = $this->inline_labelend;
			}
			if( isset( $this->inline_inputstart ) )
			{
				$this->inputstart = $this->inline_inputstart;
			}
			if( isset( $this->inline_inputend ) )
			{
				$this->inputend = $this->inline_inputend;
			}
		}

		$input_type = isset( $field_params['type'] ) ? $field_params['type'] : '';
		$r = $this->begin_field( NULL, NULL, false, $input_type );
		if( !$field_params['inline'] )
		{ // Append input field
			$r .= $element;
		}
		$r .= $this->end_field( $input_type );

		if( $field_params['inline'] )
		{ // Restore the changed form elements in mode 'inline'
			$this->label_suffix = $label_suffix;
			$this->labelstart = $labelstart;
			$this->labelend = $labelend;
			$this->inputstart = $inputstart;
			$this->inputend = $inputend;
		}

		return $this->display_or_return( $r );
	}


	function fileselect( $field_name, $field_value, $field_label, $field_note = '', $field_params = array() )
	{
		global $thumbnail_sizes, $file_select_js_initialized;

		$field_params['note'] = $field_note;
		$this->handle_common_params( $field_params, $field_name, $field_label );

		$field_params = array_merge( array(
				'field_item_start' => '<div class="file_select_item" data-item-value="%value%">',
				'field_item_end' => '</div>',
				'size_name' => 'crop-64x64',
				'class' => '',
				'remove_file_text' => T_('Remove file'),
				'edit_file_text' => T_('Select another'),
				'max_file_num' => 1,
				'file_type' => 'image',
				'initialize_with' => '',

				'window_title' => T_('Attach files'),
				'value_separator' => ';',
				'overflow_mode' => 'queue', // valid values are queue and stack
				'root' => '',
				'path' => ''
			), $field_params );

			$FileCache = & get_FileCache();
			$counter = 0;

			if( ! isset( $field_value ) && ! empty( $field_params['initialize_with'] ) )
			{
				if( $initial_File = & get_file_by_abspath( $field_params['initialize_with'], true ) )
				{
					$field_value = $initial_File->ID;
				}
			}

			$field_values = empty( $field_value ) ? $field_value : explode( $field_params['value_separator'], $field_value );

			$r = $this->begin_field();
			$remove_icon = get_icon( 'remove' ); // we'll use this to replace the icon in the AJAX added file_select_items later
			$edit_icon = get_icon( 'edit' ); // we'll use this to replace the icon in the AJAX added file_select_items later

			$r .= '<input type="hidden" id="'.$field_name.'" name="'.$field_name.'" value="'.$field_value.'">';

			$r .= '<div name="'.$field_name.'" class="file_select_wrapper" data-max-length="'.$field_params['max_file_num']
					.'" data-thumb-size="'.$field_params['size_name']
					.'" data-root="'.$field_params['root']
					.'" data-path="'.$field_params['path']
					.'" data-file-type="'.$field_params['file_type']
					.'" data-overflow-mode="'.$field_params['overflow_mode'].'">';

			if( ! empty( $field_values ) )
			{
				foreach( $field_values as $file_ID )
				{
					$r .= file_select_item( $file_ID, $field_params );
					$counter++;
				}
			}

			if( ! isset( $thumbnail_sizes[ $field_params['size_name'] ] ) )
			{ // Wrong thumbnail size name
				debug_die( 'Invalid thumbnail size name' );
			}
			$thumb_type = $thumbnail_sizes[ $field_params['size_name'] ][0];
			$thumb_width = $thumbnail_sizes[ $field_params['size_name'] ][1];
			$thumb_height = $thumbnail_sizes[ $field_params['size_name'] ][2];

			$button_label = ( $counter === 0 ? /* TRANS: verb */ T_('Select') : get_icon( 'new' ).' '.T_('Add') );

			$r .= '<button class="btn btn-sm btn-info file_select_item" data-title="'.$field_params['window_title'].'" onclick="return window.parent.file_select_attachment_window( this, false );" style="display: '.( $counter < $field_params['max_file_num'] ? 'block' : 'none' ).';">'.$button_label.'</button>';

			$r .= '</div>';
			$r .= $this->end_field();
			echo_modalwindow_js();

			// We have to encode the params that contain HTML tags
			$script_params = $field_params;
			$script_params['field_item_start'] = base64_encode( $script_params['field_item_start'] );
			$script_params['field_item_end'] = base64_encode( $script_params['field_item_end'] );

			if( empty( $file_select_js_initialized ) )
			{
				$r .= '
						<script type="text/javascript">
						var fsel_size, fsel_name, fsel_type, fsel_obj, fsel_replace = false, fsel_title;

						function file_select_attachment_window( event_object, replace_item, fm_highlight )
						{
							fsel_obj = event_object;
							fsel_replace = replace_item;
							field_object = jQuery( event_object ).closest( ".file_select_wrapper" );
							fsel_title = jQuery( event_object ).data(  "title" );
							fsel_size = field_object.data( "thumbSize" );
							fsel_name = field_object.attr( "name" );
							fsel_type = field_object.data( "fileType" );
							root = field_object.data( "root" );
							path = field_object.data( "path" );

							openModalWindow( \'<span class="loader_img loader_user_report absolute_center" title="'.T_('Loading...').'"></span>\',
								"90%", "80%", true, fsel_title, "", true );
							jQuery.ajax(
							{
								type: "POST",
								url: "'.get_htsrv_url().'async.php",
								data:
								{
									"action": "file_attachment",
									"crumb_file_attachment": "'.get_crumb( 'file_attachment' )/* We use a different crumb name ('file_attachment' vs 'file') for extra security. */.'",
									"root": typeof( root ) == "undefined" ? "" : root,
									"path": typeof( path ) == "undefined" ? "" : path,
									"fm_highlight": typeof( fm_highlight ) == "undefined" ? "" : fm_highlight,
									"field_name": field_object.attr( "name" ),
									"file_type": field_object.data( "fileType" ),
								},
								success: function(result)
								{
									result = ajax_debug_clear( result );
									openModalWindow( result, "90%", "80%", true, "'.$field_params['window_title'].'", "" );
								}
							} );
							return false;
						}

						function file_select_add( fieldName, root, path )
						{
							// check if value is already present
							fieldName = fieldName.replace(/(\[|\])/g, "\\\\$1");
							var inputField = jQuery( "input#" + fieldName );
							var values = inputField.val().split( "'.$field_params['value_separator'].'" );

							// Add new item
							jQuery.ajax({
								type: "GET",
								url: "'.get_htsrv_url().'anon_async.php",
								data: {
										"action": "get_file_select_item",
										"field_name": fieldName,
										"root": root,
										"path": path,
										"params": '.json_encode( $script_params ).'
									},
								success: function( result )
									{
										result = jQuery.parseJSON( ajax_debug_clear( result) );
										var fieldName = result.fieldName;
										var fieldValue = result.fieldValue;
										var inputField = jQuery( "input#" + fieldName );
										var wrapper = jQuery( "div[name=" + fieldName + "].file_select_wrapper" );
										var maxLength = wrapper.data( "maxLength" );
										var overflowMode = wrapper.data( "overflowMode" );
										var addButton = jQuery( "button", wrapper );
										var items = jQuery( ".file_select_item:not(button)", wrapper );
										var lastItem = items.last();

										var newItem = jQuery( atob( result.item ) );

										if( fsel_replace )
										{
											var item = jQuery( fsel_obj ).closest( ".file_select_item" );
											newItem.insertAfter( item );
											file_select_delete( item );
										}
										else
										{
											// Attach new item
											// check if adding item will result to an overflow
											if( items.length >= maxLength )
											{ // remove extra item first depending on overflow mode
												if( overflowMode == "queue" )
												{
													file_select_delete( items.first() );
												}
												else if( overflowMode == "stack" )
												{
													file_select_delete( items.last() );
												}

												items = jQuery( ".file_select_item:not(button)", wrapper );
												lastItem = items.last();
											}

											if( lastItem.length )
											{ // attachment already exists, add to the last
												newItem.insertAfter( lastItem );
											}
											else
											{ // no attachments yet
												wrapper.prepend( newItem );
											}
										}

										newItem.find( "span.remove_file_icon" ).replaceWith(\''.$remove_icon.'\'); // replace unlink icon with skin specific icon saved earlier
										newItem.find( "span.edit_file_icon" ).replaceWith(\''.$edit_icon.'\'); // replace unlink icon with skin specific icon saved earlier

										items = jQuery( ".file_select_item:not(button)", wrapper );
										lastItem = items.last();

										// Toggle add button
										addButton.html( items.length === 0 ? "'./* TRANS: verb */ T_('Select').'" : \''.get_icon( 'new' ).' '.T_('Add').'\' );
										if( maxLength > items.length )
										{
											addButton.show();
										}
										else
										{
											addButton.hide();
										}

										// append field value
										var values = inputField.val();
										values = values ? ( inputField.val().split( "'.$field_params['value_separator'].'" ) ) : [];
										values.push( fieldValue );
										inputField.val( values.join( "'.$field_params['value_separator'].'" ) );

										// Trigger change so bozo validator will pickup the change
										inputField.trigger( "change" );

										// close modal if single item select
										if( maxLength == 1 )
										{
											closeModalWindow();
										}
									}
							});

							return false;
						}

						function file_select_delete( event_object )
						{
							var wrapper = jQuery( event_object ).closest( ".file_select_wrapper" );
							var item = jQuery( event_object ).closest( ".file_select_item" );
							var fieldName = wrapper.attr( "name" );
							fieldName = fieldName.replace(/(\[|\])/g, "\\\\$1");
							var fieldValue = item.data( "itemValue" ).toString(); // converted to string because it will later be compared to array of strings
							var maxLength = wrapper.data( "maxLength" );
							var addButton = jQuery( "button", wrapper );

							// Remove file select item
							item.remove();

							var items = jQuery( ".file_select_item:not(button)", wrapper );
							var lastItem = items.last();

							// Toggle add button
							addButton.html( items.length === 0 ? "'./* TRANS: verb */ T_('Select').'" : \''.get_icon( 'new' ).' '.T_('Add').'\' );
							if( maxLength > items.length )
							{
								addButton.show();
							}
							else
							{
								addButton.hide();
							}

							// Change input value
							var inputField = jQuery( "input#" + fieldName );
							var values = inputField.val().split( "'.$field_params['value_separator'].'" );
							var index =  values.indexOf( fieldValue );
							if( index != -1 )
							{
								values.splice( index, 1 );
							}
							inputField.val( values.join( "'.$field_params['value_separator'].'" ) );
							inputField.trigger( "change" );

							return false;
						}
						</script>';
			}

			$file_select_js_initialized = true;
			return $this->display_or_return( $r );
	}


	/**
	 * Generate a general input element.
	 *
	 * @param array Optional params.
	 *    Additionally to {@link $_common_params} you can use:
	 *    - input_prefix: Text before <input /> (string, default '')
	 *    - input_suffix: Text after <input /> (string, default "\n")
	 *    - input_help: Gets used as default value on empty input (type=text)
	 *      elements. It gets attached through JavaScript (onfocus, onblur and form.onsubmit).
	 *    - format_to_output: Use format_to_output in get_field_attribs_as_string? (boolean, default True)
	 *
	 * @return string The <input /> element.
	 */
	function get_input_element( $field_params = array(), $parse_common = true )
	{
		if( $parse_common )
		{
			$this->handle_common_params( $field_params );
		}

		if( isset($field_params['input_prefix']) )
		{
			$input_prefix = $field_params['input_prefix'];
			unset($field_params['input_prefix']); // no HTML attribute
		}
		else
		{
			$input_prefix = '';
		}

		if( isset($field_params['input_suffix']) )
		{
			$input_suffix = $field_params['input_suffix'];
			unset($field_params['input_suffix']); // no HTML attribute
		}
		else
		{
			$input_suffix = "\n";
		}

		if( isset($field_params['input_help']) && ( empty($field_params['type']) || $field_params['type'] == 'text' ) )
		{
			$this->append_javascript[] = 'input_decorated_help( "'.$field_params['id'].'", "'.format_to_output($field_params['input_help'], 'formvalue').'" );';

			unset($field_params['input_help']); // no HTML attribute
		}

		if( isset($field_params['format_to_output']) )
		{
			$format_to_output = $field_params['format_to_output'];
			unset($field_params['format_to_output']);
		}
		else
		{
			$format_to_output = true;
		}

		if( isset( $field_params['inline'] ) )
		{ // Delete 'inline' param from attributes list
			unset( $field_params['inline'] );
		}

		if( isset( $field_params['hide_label'] ) )
		{ // Delete 'hide_label' param from attributes list
			unset( $field_params['hide_label'] );
		}

		if( isset( $field_params['input_required'] ) )
		{ // Set html attribute "required" (used to highlight input with red border/shadow by bootstrap)
			$field_params['required'] = $field_params['input_required'];
			unset( $field_params['input_required'] );
		}

		$r = $input_prefix
			.'<input'.get_field_attribs_as_string( $field_params, $format_to_output ).' />'
			.$input_suffix;

		return $r;
	}


	/**
	 * Generate a general button element.
	 *
	 * @param array Optional params.
	 *    Additionally to {@link $_common_params} you can use:
	 *    - input_prefix: Text before <input /> (string, default '')
	 *    - input_suffix: Text after <input /> (string, default "\n")
	 *    - input_help: Gets used as default value on empty input (type=text)
	 *      elements. It gets attached through JavaScript (onfocus, onblur and form.onsubmit).
	 *    - format_to_output: Use format_to_output in get_field_attribs_as_string? (boolean, default True)
	 *
	 * @return string The <input /> element.
	 */
	function get_button_element( $field_params = array(), $parse_common = true )
	{
		if( $parse_common )
		{
			$this->handle_common_params( $field_params );
		}

		if( isset( $field_params['input_prefix'] ) )
		{
			$input_prefix = $field_params['input_prefix'];
			unset( $field_params['input_prefix'] ); // no HTML attribute
		}
		else
		{
			$input_prefix = '';
		}

		if( isset( $field_params['input_suffix'] ) )
		{
			$input_suffix = $field_params['input_suffix'];
			unset($field_params['input_suffix']); // no HTML attribute
		}
		else
		{
			$input_suffix = "\n";
		}

		if( isset($field_params['input_help']) && ( empty( $field_params['type'] ) || $field_params['type'] == 'text' ) )
		{
			$this->append_javascript[] = 'input_decorated_help( "'.$field_params['id'].'", "'.format_to_output( $field_params['input_help'], 'formvalue' ).'" );';

			unset( $field_params['input_help'] ); // no HTML attribute
		}

		if( isset($field_params['format_to_output']) )
		{
			$format_to_output = $field_params['format_to_output'];
			unset( $field_params['format_to_output'] );
		}
		else
		{
			$format_to_output = true;
		}

		if( isset( $field_params['inline'] ) )
		{ // Delete 'inline' param from attributes list
			unset( $field_params['inline'] );
		}

		if( isset( $field_params['input_required'] ) )
		{ // Set html attribute "required" (used to highlight input with red border/shadow by bootstrap)
			$field_params['required'] = $field_params['input_required'];
			unset( $field_params['input_required'] );
		}

		$r = $input_prefix;

		if( $field_params['tag'] == 'button' )
		{
			$value = $field_params['value'];
			unset( $field_params['value'] );
			unset( $field_params['tag'] );

			$r .= '<button'.get_field_attribs_as_string( $field_params, $format_to_output ).'>'.$value.'</button>';
		}
		else
		{
			unset( $field_params['tag'] );
			$r .= '<input'.get_field_attribs_as_string( $field_params, $format_to_output ).' />';
		}

		$r .= $input_suffix;

		return $r;
	}


	/**
	 * Convert a given string (e.g. fieldname) to a valid HTML id.
	 *
	 * @return string
	 */
	static function get_valid_id( $id )
	{
		static $id_count = 0;
		if( substr( $id, -2 ) == '[]' )
		{
			$id = substr( $id, 0, -2 ).'_A'.(++$id_count);
		}
		return str_replace( array( '[', ']' ), '_', $id );
	}


	/**
	 * Get the label of a field. This is used by {@link begin_field()} or {@link end_field()},
	 *
	 * @access protected
	 * @return string
	 */
	function get_label()
	{
		$r = '';

		if( isset( $this->_common_params['hide_label'] ) && $this->_common_params['hide_label'] )
		{ // Hide label if it is required by param
			return $r;
		}

		$label = $this->_common_params['label'];

		if( strlen($label) )
		{
			$r .= $this->labelstart;

			if( isset( $this->_common_params['clickable_label'] ) && ! $this->_common_params['clickable_label'] )
			{	// Not set if this method is invoked by ::begin_field()

				if( ! empty( $this->_common_params['required'] ) )
				{
					$r .= '<span class="label_field_required">*</span>';
				}

				$r .= format_to_output($label, 'htmlbody').$this->label_suffix;
			}
			else
			{
				if( empty( $this->is_lined_fields ) )
				{ // Don't print a label tag for "lined" mode:
					$r .= '<label'
						.( ! empty( $this->labelclass )
							? ' class="'.format_to_output( $this->labelclass, 'htmlattr' ).'"'
							: '' )
						.( ! empty( $this->_common_params['id'] )
							? ' for="'.format_to_output( $this->_common_params['id'], 'htmlattr' ).'"'
							: '' )
						.'>';
				}

					if( ! empty( $this->_common_params['required'] ) )
					{
						$r .= '<span class="label_field_required">*</span>';
					}

					$r .= format_to_output($label, 'htmlbody');

				if( empty( $this->is_lined_fields ) )
				{ // Don't print a label tag and suffix for "lined" mode:
					$r .= $this->label_suffix;

					$r .= '</label>';
				}
			}

			$r .= $this->labelend;
		}
		else
		{ // Empty label:
			$r .= $this->labelempty;
		}

		return $r;
	}


	/**
	 * Extract common params out of $field_params into {@link $_common_params} and unsets them in $field_params.
	 *
	 * Also handles adding errors to the note.
	 *
	 * @access protected
	 * @param array An array passed to a field generating function like {@link text_input()}. By reference!
	 * @param string|NULL The name of the field. If not empty it gets used to build the id attribute.
	 */
	function handle_common_params( & $field_params, $field_name = NULL, $field_label = NULL, $field_note = NULL )
	{
		#pre_dump( 'handle_common_params (before)', $field_params );

		$this->_common_params = array(); // Reset

		// Copy optional variables, if given:
		if( isset($field_name) )
		{
			$field_params['name'] = $field_name;
		}

		if( isset($field_label) )
		{
			$field_params['label'] = $field_label;
		}

		if( isset($field_params['note']) )
		{
			$this->_common_params['note'] = $field_params['note'];
			unset($field_params['note']); // no HTML attribute
		}
		elseif( isset($field_note) ) // Note: allow "0" as a note
		{
			$this->_common_params['note'] = $field_note;
		}
		else
		{
			$this->_common_params['note'] = NULL;
		}

		if( isset($field_params['note_format']) )
		{
			$this->_common_params['note_format'] = $field_params['note_format'];
			unset($field_params['note_format']); // no HTML attribute
		}
		else
		{
			$this->_common_params['note_format'] = $this->note_format;
		}

		if( isset($field_params['label']) )
		{
			$this->_common_params['label'] = $field_params['label'];
			unset($field_params['label']); // no HTML attribute
		}
		else
		{
			$this->_common_params['label'] = '';
		}

		if( isset($field_params['clickable_label']) )
		{
			$this->_common_params['clickable_label'] = $field_params['clickable_label'];
			unset($field_params['clickable_label']); // no HTML attribute
		}
		else
		{
			$this->_common_params['clickable_label'] = true;
		}

		if( isset($field_params['field_prefix']) )
		{
			$this->_common_params['field_prefix'] = $field_params['field_prefix'];
			unset( $field_params['field_prefix'] );
		}

		if( isset($field_params['field_suffix']) )
		{
			$this->_common_params['field_suffix'] = $field_params['field_suffix'];
			unset( $field_params['field_suffix'] );
		}

		if( isset($field_params['input_prefix']) )
		{
			$this->_common_params['input_prefix'] = $field_params['input_prefix'];
			unset( $field_params['input_prefix'] );
		}

		if( isset($field_params['required']) )
		{
			$this->_common_params['required'] = $field_params['required'];
			unset($field_params['required']);
		}


		if( !empty($field_params['name']) )
		{
			if( !isset($field_params['id']) )
			{ // Autogenerate id attrib (not for hidden, radio and submit types)
				if( empty($field_params['type'])
						|| ( $field_params['type'] != 'hidden'
									&& $field_params['type'] != 'radio'
									&& $field_params['type'] != 'submit'
									) )
				{ // Save ID with field_params and _common_params (for get_label())
					$field_params['id'] = $this->_common_params['id'] = Form::get_valid_id( $field_params['name'] );
				}
			}
			else
			{
				$this->_common_params['id'] = $field_params['id'];
			}

			// Remember the field name, so hiddens_by_key can skip it.
			$this->included_input_field_names[] = $field_params['name'];
		}

		// Mark required fields:
		if( isset($this->_common_params['required']) && $this->_common_params['required'] )
		{ // add "field_required" class:
			if( isset($field_params['type']) && $field_params['type'] == 'checkbox' )
			{ // checkboxes need a span
				$field_params['input_prefix'] = ( isset($field_params['input_prefix']) ? $field_params['input_prefix'] : '' ).'<span class="checkbox_required">';
				$field_params['input_suffix'] = '</span>'.( isset($field_params['input_suffix']) ? $field_params['input_suffix'] : '' );
			}
			else
			{
				$field_params['class'] = isset( $field_params['class'] ) ? $field_params['class'].' field_required' : 'field_required';
			}
		}

		// Error handling:
		if( isset($field_params['name']) && param_has_error( $field_params['name'] ) )
		{ // There is an error message for this field:
			if( isset($field_params['type']) && $field_params['type'] == 'checkbox' )
			{ // checkboxes need a span
				$field_params['input_prefix'] = ( isset($field_params['input_prefix']) ? $field_params['input_prefix'] : '' ).'<span class="checkbox_error">';
				$field_params['input_suffix'] = '</span>'.( isset($field_params['input_suffix']) ? $field_params['input_suffix'] : '' );
			}
			else
			{
				$field_params['class'] = isset( $field_params['class'] ) ? $field_params['class'].' field_error' : 'field_error';
			}

			if( $this->disp_param_err_messages_with_fields )
			{
				$this->_common_params['note'] .= ' <span class="field_error"'
						.' rel="'.$field_params['name'].'"'.'>'// May be used by JS, for example, to display errors in tooltip on bootstrap skins
						.param_get_error_msg( $field_params['name'] )
					.'</span>';
			}
		}

		if( isset( $field_params['wide'] ) && $field_params['wide'] )
		{
			$this->_common_params['wide'] = $field_params['wide'];
			unset( $field_params['wide'] );
		}

		if( isset( $field_params['inline'] ) && $field_params['inline'] )
		{
			$this->_common_params['inline'] = $field_params['inline'];
			unset( $field_params['inline'] );
		}

		if( isset( $field_params['hide_label'] ) && $field_params['hide_label'] )
		{
			$this->_common_params['hide_label'] = $field_params['hide_label'];
			unset( $field_params['hide_label'] );
		}

		#pre_dump( 'handle_common_params (after)', $field_params );
	}


	/**
	 * Display or return, according to {@link $output}.
	 *
	 * @return true|string True, if we want to display, the string if not.
	 */
	function display_or_return( $r )
	{
		if( $this->output )
		{
			echo $r;
			return true;
		}
		else
		{
			return $r;
		}
	}


	/**
	 * Builds a custom content field.
	 *
	 * @param string Content
	 * @return true|string true (if output) or the generated HTML if not outputting
	 */
	function custom_content( $content )
	{
		$r = $this->customstart;
		$r .= $content;
		$r .= $this->customend;

		return $this->display_or_return( $r );
	}


	/**
	 * Begin line for several fields
	 * Used to keep many fields on one line
	 *
	 * @param string Field title
	 * @param string Field name (Used for attribute "for" af the label tag)
	 * @param string Field type
	 * @param array Params
	 */
	function begin_line( $field_label = NULL, $field_name = NULL, $field_type = '', $field_params = array() )
	{
		$this->handle_common_params( $field_params, $field_name, $field_label );

		$r = $this->begin_field( $field_name, $field_label, false, $field_type );

		// Switch layout to keep all fields in one line:
		$this->switch_layout( 'none' );

		// Set TRUE to mark all calls of the next fields as lined:
		$this->is_lined_fields = true;

		return $this->display_or_return( $r );
	}


	/**
	 * End line of several fields
	 *
	 * @param string Suffix
	 * @param string Field type
	 * @param array Params
	 */
	function end_line( $suffix_text = NULL, $field_type = '', $field_params = array() )
	{
		$this->handle_common_params( $field_params );

		$r = '';

		if( !is_null( $suffix_text ) )
		{ // Display a suffix:
			$r .= $suffix_text;
		}

		// Stop "lined" mode:
		$this->is_lined_fields = false;

		// Switch layout back:
		$this->switch_layout( NULL );

		// End field:
		$r .= $this->end_field( $field_type );

		return $this->display_or_return( $r );
	}
}

?>