<?php
/**
 * This plugin replaces the textarea in the "Write" tab with {@link http://tinymce.moxiecode.com/ tinyMCE}.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright 2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 * @copyright 2009 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package plugins
 *
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois Planque
 * @author PhiBo: Philipp Seidel (since version 0.6)
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The TinyMCE plugin.
 *
 * It provides replacing edit components with the JavaScript rich text editor TinyMCE.
 *
 * @todo Make sure settings get transformed from 0.6 to 0.7 and obsolete ones get dropped from the DB!
 * @todo dh> use require_js() and add_js_headline() for the JavaScript includes
 * @todo fp> see bbcode plugin for an example about how to convert [tag] to <tag> on the fly for editing purposes. May be used for [img:] tags in b2evo. May also be used for b2evo smilies display. ed.onBeforeSetContent ed.onPostProcess
 * @todo fp> lang.js files should be moved to the standard language packs. Maybe served by .php files outputting javascript.
 * @todo dh> This is a nice plugin to apply classes and IDs: http://www.bram.us/projects/tinymce-plugins/tinymce-classes-and-ids-plugin-bramus_cssextras/
 * @todo dh> Integrate our Filemanager via http://wiki.moxiecode.com/index.php/TinyMCE:Configuration/file_browser_callback
 */
class tinymce_plugin extends Plugin
{
	var $code = 'evo_TinyMCE';
	var $name = 'TinyMCE';
	var $priority = 10;
	var $version = '6.7.9';
	var $group = 'editor';
	var $number_of_installs = 1;


	function PluginInit( & $params )
	{
		$this->short_desc = $this->T_('Javascript WYSIWYG editor');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_comment_using' => 'disabled' ) );

		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		return array(
			'default_use_tinymce' => array(
				'label' => $this->T_('Use TinyMCE (Default)'),
				'type' => 'checkbox',
				'defaultvalue' => '1',
				'note' => $this->T_('This is the default, which users can override in their profile.'),
			),
			'use_gzip_compressor' => array(
				'label' => $this->T_('Use compressor'),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->T_('Use the TinyMCE compressor, which improves loading time.'),
			),
			/* Ugly
			'tmce_options_begin' => array(
				'label' => $this->T_('Advanced editor options'),
				'layout' => 'begin_fieldset'
			),
			*/
			'tmce_options_contextmenu' => array( // fp> keep for now
				'label' => $this->T_('Context menu'),
				'type' => 'checkbox',
				'defaultvalue' => 1,
				'note' => $this->T_('Enable this to use an extra context menu in the editor')
			),
			'tmce_options_paste' => array( // fp> keep for now
				'label' => $this->T_('Advanced paste support'),
				'type' => 'checkbox',
				'defaultvalue' => 1,
				'note' => $this->T_('Enable this to add support for pasting easily word and plain text files')
			),
			'tmce_options_directionality' => array( // keep for now
				'label' => $this->T_('Directionality support'),
				'type' => 'checkbox',
				'defaultvalue' => 1,
				'note' => $this->T_('Enable to add directionality icons to TinyMCE for better handling of right-to-left languages')
			),
			/* /Ugly
			'tmce_options_end' => array(
					'layout' => 'end_fieldset'
			),
			*/
			'tmce_custom_conf' => array( // fp> over-kill dh> I tend to leave this in, as it allows to configure it as-you-need, especially when a lot of the advanced stuff gets removed from the admin.
				'label' => $this->T_('Custom TinyMCE init'),
				'type' => 'textarea',
				'defaultvalue' => // Provide some sample:
						'height : "240"',
				'note' => sprintf( $this->T_('Custom parameters to tinymce.init(). See the <a %s>TinyMCE manual</a>.'), 'href="http://wiki.moxiecode.com/index.php/TinyMCE:Configuration"' ),
			),
		);
	}

	/**
	 * Declare custom events that this plugin fires.
	 *
	 * The gallery2_plugin uses these.
	 *
	 * Plugins can set the "load_before_init" parameter with some javascript code
	 * that will be executed before tinymce.init() is called. This is most useful
	 * for inserting code to load an external tinymce plugin.
	 *
	 * Supported events are as follows:
	 * tinymce_before_init: Allows other b2evo plugins to load tinymce plugins
	 *                      before the tinymce init.
	 * Example:
	 * function tinymce_before_init( &$params ) {
	 *   $mypluginurl = \$this->get_plugin_url()."myplugin/plugin.min.js";
	 *   echo "tinymce.PluginManager.load('myplugin', '".$mypluginurl."');";
	 * }
	 *
	 * tinymce_extend_plugins: Allows b2evo plugins to extend the plugin list.
	 *                         TinyMCE often needs to be told not to load an
	 *                         external plugin during it's load phase because it's
	 *                         already been loaded. The plugin list is exposed
	 *                         in the tinymce_plugins property in the params.
	 * Example:
	 * function tinymce_extend_plugins( &$params ) {
	 *   array_push($params["tinymce_plugins"], "-myplugin");
	 * }
	 *
	 * tinymce_extend_buttons: Allows b2evo plugins to extend the buttons in the
	 *                         Third button panel.The buttons list is exposed
	 *                         in the tinymce_buttons property in the params.
	 * Example:
	 * function tinymce_extend_buttons( &$params ) {
	 *   array_push($params["tinymce_buttons"], "mypluginbutton");
	 * }
	 */
	function GetExtraEvents()
	{
		return array(
			"tinymce_before_init"    => "Event that is called before tinymce is initialized",
			"tinymce_extend_plugins" => "Event called to allow other plugins to extend the plugin list",
			"tinymce_extend_buttons" => "Event called to allow other plugins to extend the button list"
		);
	}


	/**
	 * Define the PER-USER settings of the plugin here. These can then be edited by each user.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function GetDefaultUserSettings( & $params )
	{
		$r = array(
			'use_tinymce' => array(
				'label' => $this->T_('Use TinyMCE'),
				'type' => 'checkbox',
				'defaultvalue' => $this->Settings->get('default_use_tinymce'),
				'note' => $this->T_('Check this to enable the extended Javascript editor (TinyMCE).'),
			)
		);

		/* Ugly
		$r['tmce_options_begin'] = array(
					'label' => $this->T_('Advanced editor options'),
					'layout' => 'begin_fieldset' // fp> ugly
				);
		*/

		$r['tmce_options_contextmenu'] = array( // fp> keep for now
					'label' => $this->T_('Context menu'),
					'type' => 'checkbox',
					'defaultvalue' => $this->Settings->get('tmce_options_contextmenu'),
					'note' => $this->T_('Enable this to use an extra context menu in the editor')
				);
		$r['tmce_options_paste'] = array( // fp> keep for now
					'label' => $this->T_('Advanced paste support'),
					'type' => 'checkbox',
					'defaultvalue' => $this->Settings->get('tmce_options_paste'),
					'note' => $this->T_('Enable this to add support for easily pasting word and plain text files')
				);
		$r['tmce_options_directionality'] = array(
					'label' => $this->T_('Directionality support'),
					'type' => 'checkbox',
					'defaultvalue' => $this->Settings->get('tmce_options_directionality'),
					'note' => $this->T_('Enable to add directionality icons to TinyMCE that enables TinyMCE to better handle languages that is written from right to left.')
				);

		/* Ugly
		$r['tmce_options_end'] = array(
					'layout' => 'end_fieldset'
				);
		*/

		return $r;
	}


	/**
	 * We require b2evo 3.3+
	 */
	function GetDependencies()
	{
		return array(
				'requires' => array(
					'api_min' => array( 3, 3 ), // obsolete, but required for b2evo 1.8 before 1.8.3
					'app_min' => '3.3.0-rc1',
				),
			);
	}


	/**
	 * Init the TinyMCE object (in backoffice).
	 *
	 * This is done late, so that scriptaculous has been loaded before,
	 * which got used by the youtube_plugin and caused problems with tinymce.
	 *
	 * @todo dh> use jQuery's document.ready wrapper
	 *
	 * ---
	 *
	 * Event handler: Called when displaying editor buttons (in back-office).
	 *
	 * This method, if implemented, should output the buttons (probably as html INPUT elements)
	 * and return true, if button(s) have been displayed.
	 *
	 * You should provide an unique html ID with each button.
	 *
	 * @param array Associative array of parameters.
	 *   - 'target_type': either 'Comment' or 'Item'.
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display a button?
	 */
	function AdminDisplayEditorButton( & $params )
	{
		global $wysiwyg_toggle_switch_js_initialized;

		if( empty( $params['content_id'] ) )
		{	// Value of html attribute "id" of textarea where tibymce is applied
			// Don't allow empty id:
			return false;
		}

		switch( $params['target_type'] )
		{
			case 'Item':
				// Initialize settings for item:
				global $Collection, $Blog;

				$edited_Item = & $params['target_object'];

				if( ! empty( $edited_Item ) && ! $edited_Item->get_type_setting( 'allow_html' ) )
				{	// Only when HTML is allowed in post:
					return false;
				}

				$item_Blog = & $edited_Item->get_Blog();

				if( ! $this->get_coll_setting( 'coll_use_for_posts', $item_Blog ) )
				{	// This plugin is disabled to use for posts:
					return false;
				}

				$show_wysiwyg_warning = $this->UserSettings->get( 'show_wysiwyg_warning_'.$Blog->ID );
				$wysiwyg_checkbox_label = TS_("Don't show this again for this Collection");

				$state_params = array(
						'type' => $params['target_type'],
						'blog' => $Blog->ID,
						'item' => $edited_Item->ID,
					);
				break;

			case 'EmailCampaign':
				// Initialize settings for email campaign:
				$edited_EmailCampaign = & $params['target_object'];

				$show_wysiwyg_warning = $this->UserSettings->get( 'show_wysiwyg_warning_emailcampaign' );
				$wysiwyg_checkbox_label = TS_("Don't show this again when composing email campaigns");

				$state_params = array(
						'type'  => $params['target_type'],
						'email' => $edited_EmailCampaign->ID,
					);
				break;

			default:
				// Don't allow this plugin for another things:
				return false;
		}

		if( empty( $wysiwyg_toggle_switch_js_initialized ) )
		{
		?>
			<script type="text/javascript">
			function toggle_switch_warning( state )
			{
				var params = <?php echo json_encode( $state_params );?>;
				var activate_link = '<?php echo $this->get_htsrv_url( 'save_wysiwyg_warning_state', array_merge( $state_params, array( 'on' => 1 ) ), '&' );?>';
				var deactivate_link = '<?php echo $this->get_htsrv_url( 'save_wysiwyg_warning_state', array_merge( $state_params, array( 'on' => 0 ) ), '&' );?>';
				jQuery.get( ( state ? activate_link : deactivate_link ),
						function( data )
						{
							// Fire wysiwyg warning state change event
							jQuery( document ).trigger( 'wysiwyg_warning_changed', [ state ] );
						} );
			}
			</script>
		<?php
			$wysiwyg_toggle_switch_js_initialized = true;
		}

		switch( $params['edit_layout'] )
		{
			case 'expert_quicksettings':
				$params = array_merge( array(
						'quicksetting_item_id' => 'quicksetting_wysiwyg_switch',
						'quicksetting_item_start' => '<span id="%quicksetting_id%">',
						'quicksetting_item_end' => '</span>'
					), $params );

				$params['quicksetting_item_start'] = str_replace( '%quicksetting_id%', $params['quicksetting_item_id'], $params['quicksetting_item_start'] );

				$activate_warning_link = action_icon( '', 'activate', '', T_('Show an alert when switching from markup to WYSIWYG'), 3, 4, array( 'onclick' => 'toggle_switch_warning( false ); return false;' ) );
				$deactivate_warning_link = action_icon( '', 'deactivate', '', T_('Never show alert when switching from markup to WYSIWYG'), 3, 4, array( 'onclick' => 'toggle_switch_warning( true ); return false;' ) );

				echo $params['quicksetting_item_start'];
				echo ( is_null( $show_wysiwyg_warning ) || $show_wysiwyg_warning ) ? $activate_warning_link : $deactivate_warning_link;
				echo $params['quicksetting_item_end'];
				?>
				<script type="text/javascript">
					var quicksetting_switch = jQuery( '#<?php echo $params['quicksetting_item_id'];?>' );
					jQuery( document ).on( 'wysiwyg_warning_changed', function( event, state ) {
							quicksetting_switch.html( state ? '<?php echo $activate_warning_link;?>' : '<?php echo $deactivate_warning_link;?>' );
						} );
				</script>
				<?php

				return true;

			default:
				// Get init params, depending on edit mode: simple|expert
				$tmce_init = $this->get_tmce_init( $params['edit_layout'], $params['content_id'] );

				?>

				<div class="btn-group">
					<input id="tinymce_plugin_toggle_button_html" type="button" value="<?php echo format_to_output( $this->T_('Markup'), 'htmlattr' ); ?>" class="btn btn-default active" disabled="disabled"
						title="<?php echo format_to_output( $this->T_('Toggle to the markup/pro editor.'), 'htmlattr' ); ?>" />
					<input id="tinymce_plugin_toggle_button_wysiwyg" type="button" value="WYSIWYG" class="btn btn-default"
						title="<?php echo format_to_output( $this->T_('Toggle to the WYSIWYG editor.'), 'htmlattr' ); ?>" />
				</div>

				<script type="text/javascript">
					var displayWarning = <?php echo ( is_null( $show_wysiwyg_warning ) || $show_wysiwyg_warning ) ? 'true' : 'false';?>;

					jQuery( document ).on( 'wysiwyg_warning_changed', function( event, state ) {
						displayWarning = state;
					} );

					function confirm_switch()
					{
						if( jQuery( 'input[name=hideWarning]' ).is(':checked') )
						{ // Do not show warning again
							toggle_switch_warning( false );
						}

						// switch to WYSIWYG
						tinymce_plugin_toggleEditor('<?php echo $params['content_id']; ?>');

						// close the modal window
						closeModalWindow();

						return false;
					}

					jQuery( '[id^=tinymce_plugin_toggle_button_]').click( function()
					{
						if( jQuery( this ).val() == 'WYSIWYG' )
						{
							if( displayWarning )
							{
								evo_js_lang_close = '<?php echo TS_('Cancel');?>';
								openModalWindow( '<p><?php echo TS_('By switching to WYSIWYG, you might lose newline and paragraph marks as well as some other formatting. Your text is safe though! Are you sure you want to switch?');?></p>'
									+ '<form>'
									+ '<input type="checkbox" name="hideWarning" value="1"> ' + '<?php echo $wysiwyg_checkbox_label;?>'
									+ '<input type="submit" name="submit" onclick="return confirm_switch();">'
									+ '</form>',
									'500px', '', true,
									'<span class="text-danger"><?php echo TS_('WARNING');?></span>',
									[ '<?php echo TS_('OK');?>', 'btn-primary' ] );
							}
							else
							{
								tinymce_plugin_toggleEditor('<?php echo $params['content_id']; ?>');
							}
						}
						else
						{
							tinymce_plugin_toggleEditor('<?php echo $params['content_id']; ?>');
						}
					} );

					/**
					* Toggle TinyMCE editor on/off.
					* This updates the corresponding PluginUserSetting, too.
					*/
					function tinymce_plugin_toggleEditor(id)
					{
						jQuery( '[id^=tinymce_plugin_toggle_button_]' ).removeClass( 'active' ).attr( 'disabled', 'disabled' );

						if( ! tinymce_plugin_init_done )
						{
							tinymce_plugin_init_done = true;
							// call this method on init again, with "null" id, so that mceAddControl gets called.
							tinymce_plugin_init_tinymce( function() {tinymce_plugin_toggleEditor(null)} );
							return;
						}

						if( ! tinymce.get( id ) )
						{ // Turn on WYSIWYG editor
							tinymce.execCommand( 'mceAddEditor', false, id );
							jQuery.get( '<?php echo $this->get_htsrv_url( 'save_editor_state', array_merge( $state_params, array( 'on' => 1 ) ), '&' ); ?>' );
							jQuery( '#tinymce_plugin_toggle_button_wysiwyg' ).addClass( 'active' );
							jQuery( '#tinymce_plugin_toggle_button_html' ).removeAttr( 'disabled' );
							jQuery( '[name="editor_code"]').attr('value', '<?php echo $this->code; ?>' );
							// Hide the plugin toolbars that allow to insert html tags
							jQuery( '.quicktags_toolbar, .evo_code_toolbar, .evo_prism_toolbar, .b2evMark_toolbar' ).hide();
							jQuery( '#block_renderer_evo_code, #block_renderer_evo_prism, #block_renderer_b2evMark' ).addClass( 'disabled' );
							jQuery( 'input#renderer_evo_code, input#renderer_evo_prism, input#renderer_b2evMark' ).each( function()
							{
								if( jQuery( this ).is( ':checked' ) )
								{
									jQuery( this ).addClass( 'checked' );
								}
								jQuery( this ).attr( 'disabled', 'disabled' ).removeAttr( 'checked' );
							} );
						}
						else
						{ // Hide the editor, Display only source HTML
							tinymce.execCommand( 'mceRemoveEditor', false, id );
							jQuery.get( '<?php echo $this->get_htsrv_url( 'save_editor_state', array_merge( $state_params, array( 'on' => 0 ) ), '&' ); ?>' );
							jQuery( '#tinymce_plugin_toggle_button_html' ).addClass( 'active' );
							jQuery( '#tinymce_plugin_toggle_button_wysiwyg' ).removeAttr( 'disabled' );
							jQuery( '[name="editor_code"]' ).attr( 'value', 'html' );
							// Show the plugin toolbars that allow to insert html tags
							jQuery( '.quicktags_toolbar, .evo_code_toolbar, .evo_prism_toolbar, .b2evMark_toolbar' ).show();
							jQuery( '#block_renderer_evo_code, #block_renderer_evo_prism, #block_renderer_b2evMark' ).removeClass( 'disabled' );
							jQuery( 'input#renderer_evo_code, input#renderer_evo_prism, input#renderer_b2evMark' ).each( function()
							{
								if( jQuery( this ).hasClass( 'checked' ) )
								{
									jQuery( this ).attr( 'checked', 'checked' ).removeClass( 'checked' );
								}
								jQuery( this ).removeAttr( 'disabled' );
							} );
						}
					}

					// Init array with all usernames from the page for autocomplete plugin
					var autocomplete_static_options = [];
					jQuery( '.user.login' ).each( function()
					{
						var login = jQuery( this ).text();
						if( login != '' && autocomplete_static_options.indexOf( login ) == -1 )
						{
							if( login[0] == '@' )
							{
								login = login.substr( 1 );
							}
							autocomplete_static_options.push( login );
						}
					} );
					autocomplete_static_options = autocomplete_static_options.join();

					var tmce_init={<?php echo $tmce_init; ?>};
					var tinymce_plugin_displayed_error = false;
					var tinymce_plugin_init_done = false;

					</script>

					<?php
					// Load TinyMCE Javascript source file:
					// This cannot be done through AJAX, since there appear to be scope problems on init then (TinyMCE problem?! - "u not defined").
					// Anyway, not using AJAX to fetch the file makes it more cachable anyway.
					require_js( '#tinymce#', 'blog', false, true );
					require_js( '#tinymce_jquery#', 'blog', false, true );
					?>

					<script type="text/javascript">
					function tinymce_plugin_init_tinymce(oninit)
					{
						// Init tinymce:
						if( typeof tinymce == "undefined" )
						{
							if( ! tinymce_plugin_displayed_error )
							{
								alert( '<?php echo str_replace("'", "\'",
									sprintf( $this->T_('TinyMCE javascript could not be loaded. Check the "%s" plugin setting.'),
									$this->T_('URL to TinyMCE') ) ) ?>' );
								tinymce_plugin_displayed_error = true;
							}
						}
						else
						{
							<?php
							global $Plugins;
							$Plugins->trigger_event('tinymce_before_init');
							?>

							// Define oninit function for TinyMCE
							if( typeof tmce_init.oninit != "undefined" )
							{
								oninit = function() {
									tmce_init.oninit();
									oninit();
								}
							}

							tmce_init.oninit = function ()
							{
								oninit();

								// Provide hooks for textarea manipulation (where other plugins should hook into):
								var ed = tinymce.get("<?php echo $params['content_id']; ?>");
								if( ed && typeof b2evo_Callbacks == "object" )
								{
									// add a callback, that returns the selected (raw) html:
									b2evo_Callbacks.register_callback( "get_selected_text_for_<?php echo $params['content_id']; ?>", function(value) {
											var inst = tinymce.get("<?php echo $params['content_id']; ?>");
											if( ! inst ) return null;
											return inst.selection.getContent();
										}, true );

									// add a callback, that wraps a selection:
									b2evo_Callbacks.register_callback( "wrap_selection_for_<?php echo $params['content_id']; ?>", function(params) {
											var inst = tinymce.get("<?php echo $params['content_id']; ?>");
											if( ! inst ) return null;
											var sel = inst.selection.getContent();

											if( params.replace )
											{
												var value = params.before + params.after;
											}
											else
											{
												var value = params.before + sel + params.after;
											}
											inst.selection.setContent(value);

											return true;
										}, true );

									// add a callback, that replaces a string
									b2evo_Callbacks.register_callback( "str_replace_for_<?php echo $params['content_id']; ?>", function(params) {
											var inst = tinymce.get("<?php echo $params['content_id']; ?>");
											if( ! inst ) return null;

											// Replace substring with new value
											inst.setContent( inst.getContent().replace( params.search, params.replace ) );

											return true;
										}, true );

									// add a callback, that lets us insert raw content:
									// DEPRECATED, used in b2evo 1.10.x
									b2evo_Callbacks.register_callback( "insert_raw_into_<?php echo $params['content_id']; ?>", function(value) {
											tinymce.execInstanceCommand( "<?php echo $params['content_id']; ?>", "mceInsertRawHTML", false, value );
											return true;
									}, true );
								}
							}

							tmce_init.setup = function( ed )
							{
								ed.on( 'init', tmce_init.oninit );
							}

							tinymce.init( tmce_init );
						}
					}

				</script>

				<?php
				$use_tinymce = $this->get_editor_state( $state_params );

				$editor_code = 'html';
				if( $use_tinymce )
				{ // User used MCE last time, load MCE on document.ready:
					$editor_code = $this->code;
					echo '<script type="text/javascript">jQuery( tinymce_plugin_toggleEditor("'.$params['content_id'].'") );</script>';
				}
				// By default set the editor code to an empty string
				echo '<input type="hidden" name="editor_code" value="">';
				// If the js is enabled set the editor code to the currently used value
				echo '<script type="text/javascript">jQuery(\'[name="editor_code"]\').attr(\'value\', \''.$editor_code.'\');</script>';

				// We also want to save the 'last used/not-used' state: (if no NULLs, this won't change anything)
				$this->htsrv_save_editor_state( array_merge( $state_params, array( 'on' => $use_tinymce ) ) );

				return true;
		}
	}


	/**
	 * Init the TinyMCE object (in front office).
	 *
	 * Event handler: Called when displaying editor buttons (in front-office).
	 *
	 * This method, if implemented, should output the buttons (probably as html INPUT elements)
	 * and return true, if button(s) have been displayed.
	 *
	 * You should provide an unique html ID with each button.
	 *
	 * @param array Associative array of parameters.
	 *   - 'target_type': either 'Comment' or 'Item'.
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display a button?
	 */
	function DisplayEditorButton( & $params )
	{
		return $this->AdminDisplayEditorButton($params);
	}


	/* PRIVATE */
	/**
	 * Create Options for TinyMCE.init() (non-compressor) - not TinyMCE_GZ.init (compressor)!!
	 *
	 * @todo fp> valid_elements to try to generate less validation errors
	 *
	 * @param string simple|expert
	 * @param string ID of the edidted content (value of html attribure "id")
	 * @return string|false
	 */
	function get_tmce_init( $edit_layout, $content_id )
	{
		global $Collection, $Blog;
		global $Plugins;
		global $localtimenow, $debug, $rsc_url, $rsc_path, $skins_url;
		global $UserSettings;
		global $ReqHost;

		$tmce_plugins_array = array( 'image', 'importcss', 'link', 'pagebreak', 'morebreak', 'textcolor', 'media', 'nonbreaking', 'charmap', 'fullscreen', 'table', 'searchreplace', 'autocomplete' );

		if( function_exists( 'enchant_broker_init' ) )
		{ // Requires Enchant spelling library
			$tmce_plugins_array[] = 'spellchecker';
		}

		$tmce_theme_advanced_buttons1_array = array();
		$tmce_theme_advanced_buttons2_array = array();
		$tmce_theme_advanced_buttons3_array = array();
		$tmce_theme_advanced_buttons4_array = array();

		if( $UserSettings->get('control_form_abortions') )
		{	// Activate bozo validator: autosave plugin in TinyMCE
			$tmce_plugins_array[] = 'autosave';
		}

		if( $this->UserSettings->get('tmce_options_contextmenu') == 1 )
		{
			$tmce_plugins_array[] = 'contextmenu';
		}

		if( $edit_layout == 'inskin' )
		{ // In-skin editing mode

			/* ----------- button row 1 ------------ */

			$tmce_theme_advanced_buttons1_array = array(
				'bold italic strikethrough forecolor backcolor',
				'removeformat',
				'nonbreaking charmap',
				'image media',
				'fontselect fontsizeselect',
				'bullist numlist',
				'outdent indent'
			);

			/* ----------- button row 2 ------------ */

			$tmce_theme_advanced_buttons2_array = array(
				'formatselect styleselect',
				'alignleft aligncenter alignright alignjustify',
				'pagebreak'
			);

			/* ----------- button row 3 ------------ */

			$tmce_theme_advanced_buttons3_array = array(
				'link unlink',
				'undo redo',
				'searchreplace',
				'fullscreen'
			);
		}
		else
		{ // Simple & Expert modes

			/* ----------- button row 1 ------------ */

			$tmce_theme_advanced_buttons1_array = array(
				'bold italic strikethrough forecolor backcolor',
				'fontselect fontsizeselect',
				'removeformat',
				'nonbreaking charmap',
				'image media',
				'link unlink',
				'fullscreen'
			);

			/* ----------- button row 2 ------------ */

			$tmce_theme_advanced_buttons2_array = array(
				'formatselect styleselect',
				'bullist numlist',
				'outdent indent',
				'alignleft aligncenter alignright alignjustify',
				'morebreak pagebreak',
				'undo redo',
				'searchreplace'
			);
		}

		if( $edit_layout == 'expert' )
		{ // Simple needs to be simpler than expert
			$tmce_plugins_array[] = 'visualchars code';

			/* ----------- button row 3 ------------ */

			$tmce_theme_advanced_buttons3_array = array(
				'visualchars',
				'table',
				'subscript superscript'
			);

			if( $this->UserSettings->get('tmce_options_directionality') == 1 )
			{
				$tmce_plugins_array[] = 'directionality';
				array_push($tmce_theme_advanced_buttons3_array, 'ltr rtl');
			}

			if( $this->UserSettings->get('tmce_options_paste') == 1 )
			{
				$tmce_plugins_array[] = 'paste';
				$tmce_theme_advanced_buttons3_array[] = 'pastetext';
			}

			if( function_exists( 'enchant_broker_init' ) )
			{ // Requires Enchant spelling library
				$tmce_theme_advanced_buttons3_array[] = 'spellchecker';
			}

			$tmce_theme_advanced_buttons3_array[] = 'code';

			/* ----------- button row 4 ------------ */

			$tmce_theme_advanced_buttons4_array = array();

			$tmce_theme_advanced_buttons4_array =
				$Plugins->get_trigger_event("tinymce_extend_buttons",
					array("tinymce_buttons" => $tmce_theme_advanced_buttons4_array),
						"tinymce_buttons");
		}

		$tmce_theme_advanced_buttons1 = implode( ' | ' , $tmce_theme_advanced_buttons1_array );
		$tmce_theme_advanced_buttons2 = implode( ' | ' , $tmce_theme_advanced_buttons2_array );
		$tmce_theme_advanced_buttons3 = implode( ' | ' , $tmce_theme_advanced_buttons3_array );
		$tmce_theme_advanced_buttons4 = implode( ' | ' , $tmce_theme_advanced_buttons4_array );

		// PLUGIN EXTENSIONS:
		$tmce_plugins_array =
			$Plugins->get_trigger_event("tinymce_extend_plugins",
				array("tinymce_plugins" => $tmce_plugins_array),
					"tinymce_plugins");

		$tmce_plugins = implode( ',' , $tmce_plugins_array );

		global $current_locale, $plugins_path;
		$tmce_language = substr($current_locale, 0, 2);
		// waltercruz> Fallback to english if there's no tinymce equivalent to the user locale
		// to avoid some strange screens like http://www.flickr.com/photos/waltercruz/3390729964/
		$lang_path = $rsc_path.'js/tiny_mce/langs/'.$tmce_language.'.js';
		if( !file_exists( $lang_path ) )
		{
			$tmce_language = 'en';
		}

		// Configuration: -- http://wiki.moxiecode.com/index.php/TinyMCE:Configuration
		$init_options = array();
		$init_options[] = 'selector: "textarea#'.$content_id.'"';
		if( $this->Settings->get( 'use_gzip_compressor' ) )
		{	// Load script to use gzip compressor:
			$init_options[] = 'script_url: "'.get_require_url( 'tiny_mce/tinymce.gzip.php', 'blog', 'js' ).'"';
		}
		// TinyMCE Theme+Skin+Variant to use:
		$init_options[] = 'theme : "modern"';
		$init_options[] = 'menubar : false';
		// comma separated list of plugins: -- http://wiki.moxiecode.com/index.php/TinyMCE:Plugins
		$init_options[] = 'plugins : "'.$tmce_plugins.'"';
		$init_options[] = 'external_plugins: {
				"morebreak"    : "'.$rsc_url.'js/tiny_mce/plugins/morebreak/plugin.min.js"
			}';
		$init_options[] = 'morebreak_separator : "[teaserbreak]"';
		$init_options[] = 'pagebreak_separator : "[pagebreak]"';
		// Toolbars:
		$init_options[] = 'toolbar1: "'.$tmce_theme_advanced_buttons1.'"';
		$init_options[] = 'toolbar2: "'.$tmce_theme_advanced_buttons2.'"';
		$init_options[] = 'toolbar3: "'.$tmce_theme_advanced_buttons3.'"';
		$init_options[] = 'toolbar4: "'.$tmce_theme_advanced_buttons4.'"';
		// Context menu:
		$init_options[] = 'contextmenu: "cut copy paste | link image | inserttable"';
		// UI options:
		$init_options[] = 'block_formats : "Paragraph=p;Preformatted=pre;Block Quote=blockquote;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Address=address;Definition Term=dt;Definition Description=dd;DIV=div"';
		$init_options[] = 'resize : true';
		$init_options[] = 'language : "'.$tmce_language.'"';
		$init_options[] = 'language_url : "'.$rsc_url.'js/tiny_mce/langs/'.$tmce_language.'.js"';
		if( function_exists( 'enchant_broker_init' ) )
		{ // Requires Enchant spelling library
			$init_options[] = 'spellchecker_rpc_url: \'spellchecker.php\'';
		}
		// body_class : "my_class"
		// CSS used in the iframe/editable area: -- http://wiki.moxiecode.com/index.php/TinyMCE:Configuration/content_css
		// note: $version may not be needed below because of automatic suffix? not sure..
		// TODO: we don't want all of basic.css here

		$content_css = '';
		if( ! empty( $Blog ) )
		{	// Load the appropriate ITEM/POST styles depending on the blog's skin:
			// Note: we are not aiming for perfect wysiwyg (too heavy), just for a relevant look & feel.
			$blog_skin_ID = $Blog->get_skin_ID();
			if( ! empty( $blog_skin_ID ) )
			{
				$SkinCache = & get_SkinCache();
				/**
				 * @var Skin
				 */
				$Skin = $SkinCache->get_by_ID( $blog_skin_ID );
				$item_css_url = $skins_url.$Skin->folder.'/item.css';
				// else: $item_css_url = $rsc_url.'css/item_base.css';
				if( file_exists( $item_css_url ) )
				{
					$content_css .= ','.$item_css_url;		// fp> TODO: this needs to be a param... "of course" -- if none: else item_default.css ?
				}
			}
			// else item_default.css -- is it still possible to have no skin ?
		}

		// Load the content css files from 3rd party code, e.g. other plugins:
		global $tinymce_content_css, $app_version_long;
		if( is_array( $tinymce_content_css ) && count( $tinymce_content_css ) )
		{
			$content_css .= ','.implode( ',', $tinymce_content_css );
		}

		$init_options[] = 'content_css : "'.$this->get_plugin_url().'editor.css?v='.( $debug ? $localtimenow : $this->version.'+'.$app_version_long )
									.$content_css.'"';

		// Generated HTML code options:
		// Do not make the path relative to "document_base_url":
		$init_options[] = 'relative_urls : false';
		// Do not convert absolute urls to relative if url domain is the same as current page,
		// (we should keep urls as they were entered manually, because urls can be broken if collection has different domain than back-office; also an issue with RSS feeds):
		$init_options[] = 'convert_urls : false';
		$init_options[] = 'entity_encoding : "raw"';

		// Autocomplete options:
		$init_options[] = 'autocomplete_options: autocomplete_static_options'; // Must be initialize before as string with usernames that are separated by comma
		$init_options[] = 'autocomplete_options_url: restapi_url + "users/autocomplete"';

		// remove_linebreaks : false,
		// not documented:	auto_cleanup_word : true,

		$init = implode( ",\n", $init_options );

		// custom conf:
		if( $tmce_custom_conf = $this->Settings->get('tmce_custom_conf') )
		{
			$init .= ",\n// tmce_custom_conf (from PluginSettings):\n".$tmce_custom_conf;
		}
		return $init;
	}


	/**
	 * Get URL of file to include as "content_css" for layout and classes in TinyMCE.
	 *
	 * @return array (path, url)
	 *
	function get_item_css_path_and_url($Blog)
	{
		global $skins_url, $skins_path;

		# TODO: make this a setting
		#if( $r = $this->Settings->get('content_css') )
		#{
		#	return $r;
		#}

		// Load the appropriate ITEM/POST styles depending on the blog's skin:
		if( ! empty( $Blog->skin_ID) )
		{
			$SkinCache = & get_SkinCache();
			/**
			 * @var Skin
			 *
			$Skin = $SkinCache->get_by_ID( $Blog->skin_ID );
			$item_css_path = $Skin->folder.'/item.css';		// fp> TODO: this needs to be a param... "of course" -- if none: else item_default.css ?
			// else: $item_css_path = 'css/item_base.css';

			$item_css_path = $Skin->folder.'/style.css';

			return array($skins_path.$item_css_path, $skins_url.$item_css_path);
		}
		// else item_default.css -- is it still possible to have no skin ?

		return array(NULL, NULL);
	}
	*/

	/**
	 * AJAX callback to save editor state (on or off).
	 *
	 * @param array Params
	 */
	function htsrv_save_editor_state( $params )
	{
		if( ! isset( $params['on'] ) )
		{	// Wrong request:
			return;
		}

		switch( $params['type'] )
		{
			case 'Item':
				// Save an edit state for item edit form:

				if( ! empty( $params['blog'] ) )
				{	// This is in order to try & recall a specific state for each blog: (will be used for new posts especially)
					$this->UserSettings->set( 'use_tinymce_coll'.intval( $params['blog'] ), intval( $params['on'] ) );
				}
				$this->UserSettings->set( 'use_tinymce', intval( $params['on'] ) );
				$this->UserSettings->dbupdate();
				break;

			case 'EmailCampaign':
				// Save an edit state for email campaign edit form:
				$EmailCampaignCache = & get_EmailCampaignCache();
				if( $EmailCampaign = & $EmailCampaignCache->get_by_ID( intval( $params['email'] ), false, false ) )
				{
					$EmailCampaign->set( 'use_wysiwyg', intval( $params['on'] ) );
					$EmailCampaign->dbupdate();
				}
				break;
		}
	}


	/**
	 * AJAX callback to save WYSIWYG switch warning state (on or off).
	 *
	 * @param array Params
	 */
	function htsrv_save_wysiwyg_warning_state( $params )
	{
		if( ! isset( $params['on'] ) )
		{ // Wrong request:
			 return;
		}

		switch( $params['type'] )
		{
			case 'Item':
				$this->UserSettings->set( 'show_wysiwyg_warning_'.intval( $params['blog'] ), intval( $params['on'] ) );
				break;

			case 'EmailCampaign':
				$this->UserSettings->set( 'show_wysiwyg_warning_emailcampaign', intval( $params['on'] ) );
				break;
		}

		$this->UserSettings->dbupdate();
	}


	/**
	 * Get editor state
	 *
	 * @param array Params
	 */
	function get_editor_state( $params )
	{
		switch( $params['type'] )
		{
			case 'Item':
				// Get an edit state for item edit form:

				$ItemCache = & get_ItemCache();
				$Item = & $ItemCache->get_by_ID( $params['item'], false, false );

				$item_editor_code = ( empty( $Item ) ? NULL : $Item->get_setting( 'editor_code' ) );

				if( ! empty( $item_editor_code ) )
				{	// We have a preference for the current post, follow it:
					// Use tinymce if code matched the code of the current plugin.
					// fp> Note: this is a temporary solution; in the long term, this will be part of the API and the appropriate plugin will be selected.
					$editor_state = ( $item_editor_code == $this->code );
				}
				else
				{	// We have no pref, fall back to whatever current user has last used:

					// Has the user used MCE last time he edited this particular blog?
					$editor_state = $this->UserSettings->get( 'use_tinymce_coll'.$params['blog'] );

					if( is_null( $editor_state ) )
					{	// We don't know for this blog, check if he used MCE last time he edited anything:
						$editor_state = $this->UserSettings->get( 'use_tinymce' );
					}
				}

				return $editor_state;

			case 'EmailCampaign':
				// Get an edit state for email campaign edit form:
				$EmailCampaignCache = & get_EmailCampaignCache();
				if( $EmailCampaign = & $EmailCampaignCache->get_by_ID( intval( $params['email'] ), false, false ) )
				{
					return $EmailCampaign->get( 'use_wysiwyg' );
				}
		}

		return 0;
	}


	/**
	 * HtSrv callback to get the contents of the CSS file configured for "content_css".
	 * This gets used when the CSS is not on the same domain and the browser would not
	 * allow to handle the CSS cross domain (e.g. FF 3.5).
	 *
	 * @param array Params passed to the HtSrv call
	 *              - "blog": selected blog
	 * @return string
	 *
	function htsrv_get_item_content_css($params)
	{
		$blog = $params['blog'];
		$BlogCache = get_BlogCache($blog);
		$Collection = $Blog = $BlogCache->get_by_ID($blog);
		$path = array_shift($this->get_item_css_path_and_url($Blog));
		$r = file_get_contents($path);
		if( $r )
		{
			header('Content-Type: text/css');
			echo $r;
		}
		else
		{
			header('HTTP/1.0 404 Not Found');
		}
		exit;
	}
	*/

	/**
	 * Return the list of Htsrv (HTTP-Services) provided by the plugin.
	 *
	 * This implements the plugin interface for the list of methods that are valid to
	 * get called through htsrv/call_plugin.php.
	 *
	 * @return array
	 */
	function GetHtsrvMethods()
	{
		return array( 'save_editor_state', 'save_wysiwyg_warning_state'/*, 'get_item_content_css'*/ );
	}


	/**
	 * Event handler: Called as action just before updating the {@link Plugin::$Settings plugin's settings}.
	 *
	 * @return false|NULL Return false to prevent the settings from being updated to DB.
	 */
	function PluginSettingsUpdateAction()
	{
		if( $this->Settings->get( 'use_gzip_compressor' ) == 1 )
		{ // Check if the cache folder is not writable
			global $cache_path;
			$cache_folder = $cache_path.'plugins/tinymce'; // Cache path, this is where the .gz files will be stored

			if( !is_writable( $cache_folder ) )
			{
				global $Messages;
				$Messages->add( sprintf( T_( 'TinyMCE plugin cannot uses the compressor because folder %s is not writable' ), '<b>'.$cache_folder.'</b>' ), 'note' );

				// Disable gzip compressor
				$this->Settings->set( 'use_gzip_compressor', 0 );
			}
		}
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlHead( & $params )
	{
		global $disp;

		if( $disp == 'edit' )
		{
			$this->require_css( 'toolbar.css' );
		}
	}


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		global $ctrl;

		if( $ctrl == 'items' || $ctrl == 'campaigns' )
		{
			$this->require_css( 'toolbar.css' );
		}
	}
}

?>