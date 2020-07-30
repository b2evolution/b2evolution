<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
class coll_search_form_Widget extends ComponentWidget
{
	var $icon = 'search';
	private static $widget_instance_count = 0;
	private $widget_instance_ID;

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_search_form' );
		self::$widget_instance_count++;
		$this->widget_instance_ID = $this->code.'_'.self::$widget_instance_count;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'search-form-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Search Form');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


  	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display search form');
	}

  	/**
   	 * Get definitions for editable params
   	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $admin_url;
		// Get available templates:
		$context = 'search_form';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );

		$r = array_merge( array(
				'title' => array(
					'label'        => T_('Block title'),
					'note'         => T_( 'Title to display in your skin.' ),
					'size'         => 40,
					'defaultvalue' => T_('Search'),
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $TemplateCache->get_code_option_array(),
					'defaultvalue' => 'search_form_simple',
					'input_suffix' => ( check_user_perm( 'options', 'edit' ) ? '&nbsp;'
							.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context, NULL, NULL, NULL,
							array( 'onclick' => 'return b2template_list_highlight( this )', 'target' => '_blank' ),
							array( 'title' => T_('Manage templates').'...' ) ) : '' ),
					'class' => 'evo_template_select',
				),
				'blog_ID' => array(
					'label' => T_('Collection ID'),
					'note' => T_('Leave empty for current collection.'),
					'type' => 'integer',
					'allow_empty' => true,
					'size' => 5,
					'defaultvalue' => '',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget uses the selected items:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
		if( is_logged_in() )
		{	// Load JS to edit tags if it is enabled by widget setting and current User has a permission to edit them:
			init_tokeninput_js( 'blog' );

			// The JS file below requires jQuery tokeninput plugin and is not bundled with evo_generic.bmin.js
			// as that file is loaded before the tokeninput JS is initialized above:
			require_js_defer( 'src/evo_init_widget_coll_search_form.js', 'blog' );
		}
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		$params = array_merge( array(
				'search_input_before'        => '',
				'search_input_after'         => '',
				'search_input_author_before' => '',
				'search_input_author_after'  => '',
				'search_input_age_before'    => '',
				'search_input_age_after'     => '',
				'search_input_type_before'   => '',
				'search_input_type_after'    => '',
				'search_submit_before'       => '',
				'search_submit_after'        => '',
				'search_line_before'         => '',
				'search_line_after'          => '',
			), $params );

		parent::init_display( $params );
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Settings, $requested_404_title;

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );
		if( $blog_ID > 0 )
		{	// Get Blog for widget setting
			$BlogCache = & get_BlogCache();
			$widget_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}
		if( empty( $widget_Blog ) )
		{	// Use current blog
			global $Collection, $Blog;
			$widget_Blog = & $Blog;
		}

		if( ! $widget_Blog->get_setting( 'search_enable' ) )
		{	// A search page for widget's collection is disabled:
			$coll_name_link = '<a href="'.$widget_Blog->get( 'url' ).'">'.$widget_Blog->get( 'name' ).'</a>';
			$coll_setting_links = '';
			if( check_user_perm( 'blog_properties', 'edit', false, $widget_Blog->ID ) )
			{	// Display a link to edit collection search setting:
				$coll_setting_links = ' <a href="'.get_admin_url( 'ctrl=coll_settings&tab=search&blog='.$widget_Blog->ID ).'" target="_blank">Change setting &raquo;</a>';
			}
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because a search form is disabled for Collection "'.$coll_name_link.'".'.$coll_setting_links );
			return false;
		}

		$TemplateCache = & get_TemplateCache();
		if( ! $TemplateCache->get_by_code( $this->disp_params['template'], false, false ) )
		{
			$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
			return false;
		}

		$render_template_objects = array();
		$Form = new Form( $widget_Blog->gen_blogurl(), 'SearchForm', 'get' );
		$Form->switch_layout( 'none' );
		$render_template_objects['Form'] = $Form;

		// Render search form template:
		$search_form = render_template_code( $this->disp_params['template'], $this->disp_params, $render_template_objects );

		if( ! empty( $search_form ) )
		{
			// Collection search form:
			echo $this->disp_params['block_start'];

			$this->disp_title();

			echo $this->disp_params['block_body_start'];

			if( empty( $this->disp_params['search_class'] ) )
			{	// Class name is not defined, Use class depend on serach options
				$search_form_class = 'search_form';
			}
			else
			{	// Use class from params
				$search_form_class = $this->disp_params['search_class'];
			}

			echo '<div class="'.$search_form_class.'" data-search-id="'.$this->widget_instance_ID.'">';
			
			$Form->begin_form( 'search' );
			$Form->hidden( 'disp', 'search' );
			
			echo $search_form;

			$Form->end_form();
			
			echo '</div>';

			// JS for author autocomplete:
			if( is_logged_in() )
			{
				$selected_author_array = param( 'search_author_array', 'array' );
				$selected_author = array();
				foreach( $selected_author_array as $field => $row )
				{
					foreach( $row as $key => $value )
					{
						$selected_author[$key][$field] = $value;
					}
				}
			
				expose_var_to_js( 'evo_widget_coll_search_form', '{
						selector: "[data-search-id] #search_author",
						url: "'.format_to_js( get_restapi_url().'users/authors' ).'",
						config:
						{
							theme: "facebook",
							queryParam: "q",
							propertyToSearch: "login",
							preventDuplicates: true,
							prePopulate: '.evo_json_encode( $selected_author ).',
							hintText: "'.TS_('Type in a username').'",
							noResultsText: "'.TS_('No results').'",
							searchingText: "'.TS_('Searching...').'",
							jsonContainer: "users",
							tokenFormatter: function( user )
							{
								return "<li>" +
										'.( $Settings->get( 'username_display' ) == 'name' ? 'user.fullname' : 'user.login' ).' +
										\'<input type="hidden" name="search_author_array[id][]" value="\' + user.id + \'" />\' +
										\'<input type="hidden" name="search_author_array[login][]" value="\' + user.login + \'" />\' +
									"</li>";
							},
							resultsFormatter: function( user )
							{
								var title = user.login;
								if( user.fullname != null && user.fullname !== undefined )
								{
									title += "<br />" + user.fullname;
								}
								return "<li>" +
										user.avatar +
										"<div>" +
											title +
										"</div><span></span>" +
									"</li>";
							},
							onAdd: function()
							{
								if( this.tokenInput( "get" ).length > 0 )
								{
									jQuery( "#token-input-search_author" ).attr( "placeholder", "" );
								}
							},
							onDelete: function()
							{
								if( this.tokenInput( "get" ).length === 0 )
								{
									jQuery( "#token-input-search_author" ).attr( "placeholder", "'.TS_('Any author' ).'" ).css( "width", "100%" );
								}
							},'.
							( param_has_error( 'search_author' ) ?
							// Mark this field as error
							'onReady: function()
							{
								jQuery( ".token-input-list-facebook" ).addClass( "token-input-list-error" );
							}' : '' ).'
						},'.
					( empty( $selected_author ) ? '
						placeholder: "'.TS_('Any author' ).'",' : '' ).'
					}' );
			}

			echo $this->disp_params['block_body_end'];

			echo $this->disp_params['block_end'];

			return true;
		}
		else
		{
			$this->display_debug_message();
			return false;
		}
	}
}

?>
