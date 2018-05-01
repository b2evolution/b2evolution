<?php
/**
 * -----------------------------------------------------------------------------------------
 * This file provides a skeleton to create a new {@link http://b2evolution.net/ b2evolution}
 * plugin quickly.
 * See also:
 *  - {@link http://b2evolution.net/man/creating-plugin}
 *  - {@link http://doc.b2evolution.net/stable/plugins/Plugin.html}
 * (Delete this first paragraph, of course)
 * -----------------------------------------------------------------------------------------
 *
 * This file implements the Foo Plugin for {@link http://b2evolution.net/}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2010 by Your NAME - {@link http://example.com/}.
 *
 * @package plugins
 *
 * @author Your NAME
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Foo Plugin
 *
 * Your description
 *
 * @package plugins
 */
class development_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	/**
	 * Human readable plugin name.
	 */
	var $name = 'Plugin Name';
	/**
	 * Code, if this is a renderer or pingback plugin.
	 */
	var $code = 'devplugin';
	var $priority = 50;
	var $version = '0.1-dev';
	var $author = 'http://example.com/';
	var $help_url = '';

	/**
	 * Group of the plugin, e.g. "widget", "rendering", "antispam"
	 */
	var $group = 'Test';


	/**
	 * Init: This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = $this->T_('Short description');
		$this->long_desc = $this->T_('Longer description. You may also remove this.');
	}
	
	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		$r = $this->Form_Parts();
			
		return $r;
	}
	
	/**
	 * Define the PER-USER settings of the plugin here. These can then be edited by each user.
	 *
	 * You can access them in the plugin through the member object
	 * {@link $UserSettings}, e.g.:
	 * <code>$this->UserSettings->get( 'my_param' );</code>
	 *
	 * This method behaves exactly like {@link Plugin::GetDefaultSettings()},
	 * except that it defines user specific settings instead of global settings.
	 *
	 * @todo 3.0 fp> 1) This is not an event: RENAME to lowercase (in b2evo 3.0)
	 * @todo 3.0 fp> 2) This defines more than Default values ::  confusing name
	 * @todo name tentative get_user_param_definitions()
	 *
	 * @see Plugin::GetDefaultUserSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function GetDefaultUserSettings( & $params )
	{
		$r = $this->Form_Parts();
			
		return $r;
	}
	
	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params,
			array(
				'default_comment_rendering' => 'stealth',
				'default_post_rendering' => 'opt-out'
			)
		);

		$r = array_merge( parent::get_coll_setting_definitions( $default_params ), $this->Form_Parts() );
			
		return $r;
	}
		
	/**
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'stealth' ) );

		$r = array_merge( parent::get_msg_setting_definitions( $default_params ), $this->Form_Parts() );
			
		return $r;
	}
	
	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'stealth' ) );

		$r = array_merge( parent::get_email_setting_definitions( $default_params ), $this->Form_Parts());

		return $r;
	}
	
	/**
	 * Param definitions when added as a widget.
	 *
	 * Plugins used as widget need to implement the SkinTag hook.
	 *
	 * @return array
	 */
	function get_widget_param_definitions( $params )
	{
		$r = array_merge( $this->Form_Parts(), parent::get_widget_param_definitions( $params ) );
			
		return $r;	
		
	}
	
	/**
	 * Param definitions when added as a widget.
	 *
	 * Plugins used as widget need to implement the SkinTag hook.
	 *
	 * @return array
	 */
	function Form_Parts()
	{
		global $app_version;
		
		return array(
			
			/*
								
	'_vagtables' => array(
										'label' 		=> T_('Vegtables'),
										'type' 			=> 'input_group',
										'inputs' 		=> array(


														'_value' => array(
																			'label' => T_('Value'),
																			'defaultvalue' => 'D value',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),
														'_label' => array(
																			'label' => T_('Label'),
																			'defaultvalue' => 'D label',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),
								)),
			
			*/
				'single' => array(
					'label' 		=> T_('Normal Static Input'),
					'note' 			=> T_('Your note'), 
					'max_number' 	=> 4, 
					'type' 			=> 'select_input',
					'entries' 		=> array(

									'_vagtables' => array(
										'label' 		=> T_('Vegtables'),
										'type' 			=> 'input_group',
										'inputs' 		=> array(


														'_value' => array(
																			'label' => T_('Value'),
																			'defaultvalue' => 'D single value',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),
														'_label' => array(
																			'label' => T_('Label'),
																			'defaultvalue' => 'D single label',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),

								)),
			
											'_color' => array(
																'label' => T_('Single Item Color'),
																'defaultvalue' => '#fed136',
																'type' => 'color',
															),
			
											'_integer' => array(
																'label' => T_('Single Item Integer'),
																'note' => '1-9',
																'valid_range' => array( 'min'=>1, 'max'=>9 ),
																'type' => 'integer',
																'defaultvalue' => 1,
															),
			
											'_text' => array(
																'label' => T_('Single Item Text'),
																'defaultvalue' => 'blank text',
																'type' => 'text',
																'allow_empty' => false,
															),
		
		)),
		
			/*
			
					'list' => array(
					'label' 		=> T_('Single Input'),
					'note' 			=> T_('Your note'), 
					'max_number' 	=> 4, 
					'type' 			=> 'select_input',
					'entries' 		=> array(

									'_color' => array(
										'label' 		=> T_('Color'),
										'type' 			=> 'input_group',
										'inputs' 		=> array(


														'_value' => array(
																			'label' => T_('Color'),
																			'defaultvalue' => '#444444',
																			'type' => 'color',
																			//'allow_empty' => false,
																		),
														'_label' => array(
																			'label' => T_('Color'),
																			'defaultvalue' => '#444444',
																			'type' => 'color',
																			//'allow_empty' => false,
																		),

								)),
									'_vagtables' => array(
										'label' 		=> T_('Vegtables'),
										'type' 			=> 'input_group',
										'inputs' 		=> array(


														'_value' => array(
																			'label' => T_('Value'),
																			'defaultvalue' => 'D list value',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),
														'_label' => array(
																			'label' => T_('Label'),
																			'defaultvalue' => 'D list label',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),

								)),
									'_fruits' => array(
										'label' 		=> T_('Fruits'),
										'type' 			=> 'input_group',
										'inputs' 		=> array(


														'_value' => array(
																			'label' => T_('Value'),
																			'defaultvalue' => 'value',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),
														'_label' => array(
																			'label' => T_('Label'),
																			'defaultvalue' => 'label',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),

								)),




			)),
		'plugin_sets'	=> array(
												'label'			=> T_('Dynamic Fields'),
												'note'			=> T_('Click to add another field item'),
												// limited by array:array:string therefore html_textarea won't work
												'type'			=> version_compare( $app_version, '6.6.5', '>' ) ? 'array:array:string' : 'array',
												'max_number'		=> 3,
												'fold' => true,
												'entries'		=> array(
			
								'single' => array(
										'label' 		=> T_('Single Input'),
										'note' 			=> T_('Your note'), 
										'max_number' 	=> 4, 
										'type' 			=> 'select_input',
										'entries' 		=> array(

														'_vagtables' => array(
															'label' 		=> T_('Vegtables'),
															'type' 			=> 'input_group',
															'inputs' 		=> array(


																			'_value' => array(
																								'label' => T_('Value'),
																								'defaultvalue' => 'DD single value',
																								'type' => 'text',
																								//'allow_empty' => false,
																							),
																			'_label' => array(
																								'label' => T_('Label'),
																								'defaultvalue' => 'DD single label',
																								'type' => 'text',
																								//'allow_empty' => false,
																							),

								)))),
			'list' => array(
					'label' 		=> T_('Single Input'),
					'note' 			=> T_('Your note'), 
					'max_number' 	=> 4, 
					'type' 			=> 'select_input',
					'entries' 		=> array(

									'_color' => array(
										'label' 		=> T_('Color'),
										'type' 			=> 'input_group',
										'inputs' 		=> array(


														'_value' => array(
																			'label' => T_('Color'),
																			'defaultvalue' => '#f8a5e3',
																			'type' => 'color',
																			//'allow_empty' => false,
																		),
														'_label' => array(
																			'label' => T_('Color'),
																			'defaultvalue' => '#f8a5e3',
																			'type' => 'color',
																			//'allow_empty' => false,
																		),

								)),
									'_vagtables' => array(
										'label' 		=> T_('Vegtables'),
										'type' 			=> 'input_group',
										'inputs' 		=> array(


														'_value' => array(
																			'label' => T_('Value'),
																			'defaultvalue' => 'DD list value',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),
														'_label' => array(
																			'label' => T_('Label'),
																			'defaultvalue' => 'DD list label',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),

								)),
									'_fruits' => array(
										'label' 		=> T_('Fruits'),
										'type' 			=> 'input_group',
										'inputs' 		=> array(


														'_value' => array(
																			'label' => T_('Value'),
																			'defaultvalue' => 'DD list value',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),
														'_label' => array(
																			'label' => T_('Label'),
																			'defaultvalue' => 'DD list label',
																			'type' => 'text',
																			//'allow_empty' => false,
																		),

								)),




			)),
					
			), 
			)
			*/
		);

	}
	
	/**
	 * Called when a plugin gets called by its {@link $code}.
	 *
	 * If you provide this event, b2evolution will assume your plugin
	 * provides a widget and list it in the "Available widgets" list.
	 *
	 * @see Plugins::SkinTag()
	 * @param array The array passed to {@link Plugins::call_by_code()}.
	 */
	function SkinTag( & $params )
	{
	}
	
}
?>