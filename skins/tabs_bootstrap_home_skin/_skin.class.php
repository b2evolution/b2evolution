<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage tabs_bootstrap_home_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class tabs_bootstrap_home_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.1.2';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = true;  // true|false|'check' Set this to true for better optimization

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Tabs Bootstrap Home';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'rwd';
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 7;
	}


	/**
	 * Get supported collection kinds.
	 *
	 * This should be overloaded in skins.
	 *
	 * For each kind the answer could be:
	 * - 'yes' : this skin does support that collection kind (the result will be was is expected)
	 * - 'partial' : this skin is not a primary choice for this collection kind (but still produces an output that makes sense)
	 * - 'maybe' : this skin has not been tested with this collection kind
	 * - 'no' : this skin does not support that collection kind (the result would not be what is expected)
	 * There may be more possible answers in the future...
	 */
	public function get_supported_coll_kinds()
	{
		$supported_kinds = array(
				'minisite' => 'yes',
				'main' => 'yes',
				'std' => 'partial', // Blog
				'photo' => 'no',
				'forum' => 'no',
				'manual' => 'no',
				'group' => 'maybe',  // Tracker
				// Any kind that is not listed should be considered as "maybe" supported
			);

		return $supported_kinds;
	}


	/*
	 * What CSS framework does has this skin been designed with?
	 *
	 * This may impact default markup returned by Skin::get_template() for example
	 */
	function get_css_framework()
	{
		return 'bootstrap';
	}


	/**
	 * Get the container codes of the skin main containers
	 *
	 * This should NOT be protected. It should be used INSTEAD of file parsing.
	 * File parsing should only be used if this function is not defined
	 *
	 * @return array Array which overrides default containers; Empty array means to use all default containers.
	 */
	function get_declared_containers()
	{
		// Array to override default containers from function get_skin_default_containers():
		// - Key is widget container code;
		// - Value: array( 0 - container name, 1 - container order ),
		//          NULL - means don't use the container, WARNING: it(only empty/without widgets) will be deleted from DB on changing of collection skin or on reload container definitions.
		return array();
	}


	/**
	 * Get screen sizes for skin settings
	 */
	function get_screen_sizes()
	{
		return array(
			'sm' => array(
				'min_width'          => '768px',
				'title'              => T_('Small screen'),
				// Default values:
				'tab_font_size'      => '11px',
				'tab_padding'        => '4px 9px',
				'title_font_size'    => '18px',
				'text_font_size'     => '8px',
				'tab_text_height'    => 150,
				'static_text_height' => 75,
			),
			'md' => array(
				'min_width'          => '992px',
				'title'              => T_('Medium screen'),
				// Default values:
				'tab_font_size'      => '12px',
				'tab_padding'        => '6px 11px',
				'title_font_size'    => '22px',
				'text_font_size'     => '11px',
				'tab_text_height' => 200,
				'static_text_height' => 100,
			),
			'lg' => array(
				'min_width'          => '1200px',
				'title'              => T_('Large screen'),
				// Default values:
				'tab_font_size'      => '13px',
				'tab_padding'        => '8px 13px',
				'title_font_size'    => '26px',
				'text_font_size'     => '13px',
				'tab_text_height'    => 250,
				'static_text_height' => 125,
			),
			'xxl' => array(
				'min_width'          => '1785px',
				'title'              => T_('Extra large screen'),
				// Default values:
				'tab_font_size'      => '14px',
				'tab_padding'        => '10px 15px',
				'title_font_size'    => '30px',
				'text_font_size'     => '16px',
				'tab_text_height'    => 400,
				'static_text_height' => 190,
			),
		);
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

		// Load for function get_available_thumb_sizes():
		load_funcs( 'files/model/_image.funcs.php' );

		$ItemTypeCache = & get_ItemTypeCache();
		$ItemTypeCache->clear();
		$ItemTypeCache_SQL = $ItemTypeCache->get_SQL_object();
		$ItemTypeCache_SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID' );
		$ItemTypeCache_SQL->WHERE_and( 'itc_coll_ID = '.$Blog->ID );
		$ItemTypeCache->load_by_sql( $ItemTypeCache_SQL );
		$item_type_cache_load_all = $ItemTypeCache->load_all; // Save original value
		$ItemTypeCache->load_all = false; // Force to don't load all item types in get_option_array() below
		$item_type_option_array = $ItemTypeCache->get_option_array();
		// Revert back to original value:
		$ItemTypeCache->load_all = $item_type_cache_load_all;

		// Set default value tabs item type to "Homepage Content Tab":
		$homepage_content_tab_key = array_search( 'Homepage Content Tab', $item_type_option_array );
		if( $homepage_content_tab_key === false )
		{	// Homepage Content Tab not found, default to show all posts:
			$default_tabs_item_type = 'all';
			$default_tabs_item_type_ID = '';
		}
		else
		{
			$default_tabs_item_type = 'custom';
			$default_tabs_item_type_ID = $homepage_content_tab_key;
		}

		$r = array_merge( array(
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Layout Settings')
				),
					'main_content_image_size' => array(
						'label' => T_('Image size for main content'),
						'note' => T_('Controls Aspect, Ratio and Standard Size'),
						'defaultvalue' => 'fit-1280x720',
						'options' => get_available_thumb_sizes(),
						'type' => 'select',
					),
					'max_image_height' => array(
						'label' => T_('Max image height'),
						'input_suffix' => ' px ',
						'note' => T_('Constrain height of content images by CSS.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'size' => '7',
						'allow_empty' => true,
					),
					'message_affix_offset' => array(
						'label' => T_('Messages affix offset'),
						'note' => 'px. ' . T_('Set message top offset value.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),
				'front_page_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Front Page Settings')
				),
					'tabs_item_type_begin_line' => array(
						'type' => 'begin_line',
						'label' => T_('Show as tabs'),
					),
					'tabs_item_type' => array(
						'type' => 'radio',
						'field_lines' => true,
						'options'  => array(
							array( 'all', T_('All posts') ),
							array( 'featured', T_('Only featured posts') ),
							array( 'custom', T_('Only posts of type:') ),
						),
						'defaultvalue' => $default_tabs_item_type,
					),
					'tabs_item_type_ID' =>  array(
						'label' => '',
						'type' => 'select',
						'options' => $item_type_option_array,
						'defaultvalue' => $default_tabs_item_type_ID,
						'allow_empty' => true,
					),
					'tabs_item_type_end_line' => array(
						'type' => 'end_line',
					),
					'primary_area' => array(
						'label' => T_('Primary Area'),
						'type' => 'radio',
						'field_lines' => true,
						'options'  => array(
							array( 'below_tab_text', T_('Display at static position below tab text') ),
							array( 'below_tabs', T_('Display below tabs') ),
						),
						'defaultvalue' => 'below_tab_text',
					),
			)
		);

		foreach( $this->get_screen_sizes() as $screen_key => $screen )
		{
			$r = array_merge( $r, array(
				$screen_key.'_front_page_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => $screen['title'].' <code>(>='.$screen['min_width'].')</code>'
				),
					$screen_key.'_tab_font_size' => array(
						'label' => T_('Tab font size'),
						'defaultvalue' => isset( $screen['tab_font_size'] ) ? $screen['tab_font_size'] : '',
						'allow_empty' => true,
						'size' => 6,
					),
					$screen_key.'_tab_padding' => array(
						'label' => T_('Tab padding'),
						'defaultvalue' => isset( $screen['tab_padding'] ) ? $screen['tab_padding'] : '',
						'allow_empty' => true,
						'size' => 18,
					),
					$screen_key.'_title_font_size' => array(
						'label' => T_('Title font size'),
						'defaultvalue' => isset( $screen['title_font_size'] ) ? $screen['title_font_size'] : '',
						'allow_empty' => true,
						'size' => 6,
					),
					$screen_key.'_text_font_size' => array(
						'label' => T_('Text font size'),
						'defaultvalue' => isset( $screen['text_font_size'] ) ? $screen['text_font_size'] : '',
						'allow_empty' => true,
						'size' => 6,
					),
					$screen_key.'_tab_text_height' => array(
						'label' => T_('Tab text height'),
						'input_suffix' => ' px ',
						'defaultvalue' => isset( $screen['tab_text_height'] ) ? $screen['tab_text_height'] : '',
						'type' => 'integer',
						'allow_empty' => true,
						'valid_range' => array( 'min' => 1 ),
						'hide' => ( $this->get_setting( 'primary_area', NULL, 'below_tab_text' ) != 'below_tab_text' ),
					),
					$screen_key.'_static_text_height' => array(
						'label' => T_('Primary Area height'),
						'input_suffix' => ' px ',
						'defaultvalue' => isset( $screen['static_text_height'] ) ? $screen['static_text_height'] : '',
						'type' => 'integer',
						'allow_empty' => true,
						'valid_range' => array( 'min' => 1 ),
						'hide' => ( $this->get_setting( 'primary_area', NULL, 'below_tab_text' ) != 'below_tab_text' ),
					),
				$screen_key.'_front_page_end' => array(
					'layout' => 'end_fieldset',
				),
			) );
		}

		$r = array_merge( $r, array(
				'front_page_end' => array(
					'layout' => 'end_fieldset',
				),

				'section_colorbox_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Colorbox Image Zoom')
				),
					'colorbox' => array(
						'label' => T_('Colorbox Image Zoom'),
						'note' => T_('Check to enable javascript zooming on images (using the colorbox script)'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_post' => array(
						'label' => T_('Voting on Post Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_post_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_comment' => array(
						'label' => T_('Voting on Comment Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_comment_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_user' => array(
						'label' => T_('Voting on User Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_user_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
				'section_colorbox_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_username_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Username options')
				),
					'gender_colored' => array(
						'label' => T_('Display gender'),
						'note' => T_('Use colored usernames to differentiate men & women.'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'bubbletip' => array(
						'label' => T_('Username bubble tips'),
						'note' => T_('Check to enable bubble tips on usernames'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'autocomplete_usernames' => array(
						'label' => T_('Autocomplete usernames'),
						'note' => T_('Check to enable auto-completion of usernames entered after a "@" sign in the comment forms'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
				'section_username_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_access_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('When access is denied or requires login...')
				),
					'access_login_containers' => array(
						'label' => T_('Display on login screen'),
						'note' => '',
						'type' => 'checklist',
						'options' => array(
							array( 'header',   sprintf( T_('"%s" container'), NT_('Header') ),   1 ),
							array( 'page_top', sprintf( T_('"%s" container'), NT_('Page Top') ), 1 ),
							array( 'menu',     sprintf( T_('"%s" container'), NT_('Menu') ),     0 ),
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),   1 )
							),
						),
				'section_access_end' => array(
					'layout' => 'end_fieldset',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Additional JavaScript code for skin settings form
	 */
	function echo_settings_form_js()
	{
?>
<script>
jQuery( '[name=edit_skin_<?php echo $this->ID; ?>_set_primary_area]' ).click( function()
{
	jQuery( '[id^=ffield_edit_skin_<?php echo $this->ID; ?>_set_][id$=_tab_text_height], [id^=ffield_edit_skin_<?php echo $this->ID; ?>_set_][id$=_static_text_height]' ).toggle( jQuery( this ).val() == 'below_tab_text' );
	
} );
</script>
<?php
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		global $Messages, $disp, $debug, $Session, $blog;

		// Request some common features that the parent function (Skin::display_init()) knows how to provide:
		parent::display_init( array(
				'jquery',                  // Load jQuery
				'font_awesome',            // Load Font Awesome (and use its icons as a priority over the Bootstrap glyphicons)
				'bootstrap',               // Load Bootstrap (without 'bootstrap_theme_css')
				'bootstrap_evo_css',       // Load the b2evo_base styles for Bootstrap (instead of the old b2evo_base styles)
				'bootstrap_messages',      // Initialize $Messages Class to use Bootstrap styles
				'style_css',               // Load the style.css file of the current skin
				'colorbox',                // Load Colorbox (a lightweight Lightbox alternative + customizations for b2evo)
				'bootstrap_init_tooltips', // Inline JS to init Bootstrap tooltips (E.g. on comment form for allowed file extensions)
				'disp_auto',               // Automatically include additional CSS and/or JS required by certain disps (replace with 'disp_off' to disable this)
			) );

		// Skin specific initializations:

		// **** Layout Settings / START ****
		// Max image height:
		$this->dynamic_style_rule( 'max_image_height', '.evo_image_block img { max-height: $setting_value$px; width: auto; }', array(
			'check' => 'not_empty'
		) );
		// **** Layout Settings / END ****

		// **** Front Page Settings / START ****
		foreach( $this->get_screen_sizes() as $screen_key => $screen )
		{
			// Start of @media screen wrapper:
			$this->add_dynamic_style( '@media only screen and (min-width: '.$screen['min_width'].') { ' );
			// Tab font size:
			$this->dynamic_style_rule( $screen_key.'_tab_font_size', '.nav-tabs>li>a { font-size: $setting_value$; }', array(
					'check' => 'not_empty'
			) );
			// Tab padding:
			$this->dynamic_style_rule( $screen_key.'_tab_padding', '.nav-tabs>li>a { padding: $setting_value$; }', array(
					'check' => 'not_empty'
			) );
			// Title font size:
			$this->dynamic_style_rule( $screen_key.'_title_font_size', '.tbhs_item_title h1, .tbhs_item_content h1 { font-size: $setting_value$; }', array(
					'check' => 'not_empty'
			) );
			// Text font size:
			$this->dynamic_style_rule( $screen_key.'_text_font_size', '.tbhs_item_content, .tbhs_item_content * { font-size: $setting_value$; }', array(
					'check' => 'not_empty'
			) );
			// H2 header tag inside text content:
			$this->dynamic_style_rule( $screen_key.'_h2_in_text_font_size', '.tbhs_item_content h2 { font-size: $setting_value$; }', array(
					'check' => 'not_empty',
					// Use special middle value for H@ inside text content,
					// NOTE: This cannot be updated from customizer mode:
					'value' => ( ( intval( $this->get_setting( $screen_key.'_title_font_size' ) ) + intval( $this->get_setting( $screen_key.'_text_font_size' ) ) ) / 2 ).'px',
			) );
			if( $this->get_setting( 'primary_area' ) == 'below_tab_text' )
			{	// Use the settings only when Primary Area is displayed at static position below tab text:

				// Tab text height:
				$this->dynamic_style_rule( $screen_key.'_tab_text_height', '.tbhs_item_content { height: $setting_value$px; }', array(
						'check' => 'not_empty'
				) );
				// Primary Area height:
				$this->dynamic_style_rule( $screen_key.'_static_text_height', '.evo_container__front_page_main_area { height: $setting_value$px; }', array(
						'check' => 'not_empty'
				) );
			}
			// End of @media screen wrapper:
			$this->add_dynamic_style( ' }' );
		}
		// **** Layout Settings / END ****

		// Add dynamic CSS rules headline:
		$this->add_dynamic_css_headline();

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
	}


	/**
	 * Get Items for disp=front
	 *
	 * @return array
	 */
	function get_front_items()
	{
		if( ! isset( $this->front_items ) )
		{	// Get Items of the Collection:
			global $Blog;

			$front_ItemList = new ItemList2( $Blog );

			switch( $this->get_setting( 'tabs_item_type' ) )
			{
				case 'all':
					$itemtype_ID = NULL;
					$itemtype_usage = 'post';
					break;
				case 'featured':
					$itemtype_ID = NULL;
					$itemtype_usage = '*featured*';
					break;
				case 'custom':
					$itemtype_ID = $this->get_setting( 'tabs_item_type_ID' );
					$itemtype_usage = NULL;
					break;
			}

			// Set additional debug info prefix for SQL queries in order to know what code executes it:
			$front_ItemList->query_title_prefix = '$front_ItemList';
			$front_ItemList->set_default_filters( array(
					'types'          => $itemtype_ID,
					'itemtype_usage' => $itemtype_usage,
					'orderby'        => 'order',
					'order'          => 'ASC',
				) );
			$front_ItemList->query();

			$this->front_items = array();
			while( $Item = & $front_ItemList->get_next() )
			{
				$this->front_items[] = $Item;
			}
		}

		return $this->front_items;
	}


	/**
	 * Get active Item on disp=front
	 *
	 * @return object|false
	 */
	function & get_active_front_Item()
	{
		$front_items = $this->get_front_items();

		if( isset( $front_items[0] ) )
		{
			$front_Item = & $front_items[0];
		}
		else
		{
			$front_Item = false;
		}

		return $front_Item;
	}
}
?>