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
	var $version = '5.0.0';
	var $group = 'editor';
	var $number_of_installs = 1;

	/**
	 * @var string ID of the textarea to bind to (?)
	 * @todo fp>should be a param passed from core to pluginevent (editor can be invoked in many different places)
	 */
	var $tmce_editor_id = 'itemform_post_content';

	function PluginInit()
	{
		$this->short_desc = $this->T_('Javascript WYSIWYG editor');
	}


	/**
	 * These are the plugins settings + defaults that will apply to all users unless they override
	 */
	function GetDefaultSettings()
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
	 * We allow each user to disable the TinyMCE and override some of the default settings.
	 */
	function GetDefaultUserSettings()
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
	 * Get the URL to include TinyMCE.
	 * @return string
	 */
	function get_tinymce_src_url()
	{
		$relative_to = ( is_admin_page() ? 'rsc_url' : 'blog' );

		if( $this->Settings->get( 'use_gzip_compressor' ) )
		{
			$url = get_require_url( '#tinymce_gzip#', $relative_to );
			// dh> suffix of the file to compress. Looking at tiny_mce_gzip.php it only allows "_src". Needs investigation - maybe the tiny_mce_jquery.js would actually work when "_jquery" would be allowed.
		}
		else
		{
			$url = get_require_url( '#tinymce#', $relative_to );
		}

		return $url;
	}


	/**
	 * Init the TinyMCE object (in backoffice).
	 *
	 * This is done late, so that scriptaculous has been loaded before,
	 * which got used by the youtube_plugin and caused problems with tinymce.
	 *
	 * @todo dh> use jQuery's document.ready wrapper
	 *
	 * @return boolean
	 */
	function AdminDisplayEditorButton( & $params )
	{
		global $Blog;
		/**
		 * @global Item
		 */
		global $edited_Item;

		if( $params['target_type'] != 'Item' )
		{ // only for Items:
			return;
		}

		if( ! empty( $edited_Item ) && ! $edited_Item->get_type_setting( 'allow_html' ) )
		{ // Only when HTML is allowed in post
			return false;
		}

		// Get init params, depending on edit mode: simple|expert
		$tmce_init = $this->get_tmce_init( $params['edit_layout'] );

		?>

		<div class="btn-group">
			<input id="tinymce_plugin_toggle_button_html" type="button" value="<?php echo format_to_output( $this->T_('Markup'), 'htmlattr' ); ?>" class="btn btn-default active" disabled="disabled"
				title="<?php echo format_to_output( $this->T_('Toggle to the markup/pro editor.'), 'htmlattr' ); ?>" />
			<input id="tinymce_plugin_toggle_button_wysiwyg" type="button" value="WYSIWYG" class="btn btn-default"
				title="<?php echo format_to_output( $this->T_('Toggle to the WYSIWYG editor.'), 'htmlattr' ); ?>" />
		</div>

		<script type="text/javascript">
			jQuery( '[id^=tinymce_plugin_toggle_button_]').click( function()
			{
				if( jQuery( this ).val() == 'WYSIWYG' && ! confirm( '<?php echo TS_('WARNING: By switching to WYSIWYG, you might lose newline and paragraph marks as well as some other formatting. Your text is safe though! Are you sure you want to switch?') ?>' ) )
				{ // Switch to WYSIWYG only after confirmation
					return;
				}
				tinymce_plugin_toggleEditor('<?php echo $this->tmce_editor_id; ?>');
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
					tinymce_plugin_load_tinymce( function() {tinymce_plugin_toggleEditor(null)} );
					return;
				}

				if( ! tinymce.get( id ) )
				{ // Turn on WYSIWYG editor
					tinymce.execCommand( 'mceAddEditor', false, id );
					jQuery.get( '<?php echo $this->get_htsrv_url( 'save_editor_state', array( 'on' => 1, 'blog' => $Blog->ID, 'item' => $edited_Item->ID ), '&' ); ?>' );
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
					jQuery.get( '<?php echo $this->get_htsrv_url( 'save_editor_state', array( 'on' => 0, 'blog' => $Blog->ID, 'item' => $edited_Item->ID ), '&' ); ?>' );
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
			echo '<script type="text/javascript" src="'.htmlspecialchars($this->get_tinymce_src_url()).'"></script>';
			?>

			<script type="text/javascript">
			/**
			 * Javascript function to load and init TinyMCE.
			 * This gets done dynamically, either "on loading" or by AJAX, if the toggle button
			 * enables the editor.
			 * @param function to call after init
			 */
			function tinymce_plugin_load_tinymce( oninit )
			{
				<?php
				// Load TinyMCE Javascript source file:
				if( $this->Settings->get('use_gzip_compressor') )
				{
					?>

					<!-- Init tinyMCE_GZ: -->
					if( typeof tinyMCE_GZ == "undefined" )
					{
						alert( '<?php echo str_replace("'", "\'",
							sprintf( $this->T_('Compressed TinyMCE javascript could not be loaded. Check the "%s" plugin setting.'),
								$this->T_('URL to TinyMCE') ) ) ?>' );
						tinymce_plugin_displayed_error = true;
						try {
							tinymce_plugin_init_tinymce(oninit);
						} catch(e) {}
					}
					else
					{
						tinyMCE_GZ.init({
							themes: tmce_init.theme,
							plugins: tmce_init.plugins,
							languages: tmce_init.language,
							disk_cache: true,
							debug: false
						}, function() {tinymce_plugin_init_tinymce(oninit)} );
					}

					<?php
				}
				else
				{	// if not using compressor...
					?>
					tinymce_plugin_init_tinymce(oninit);
					<?php
				}
				?>
			}


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
						var ed = tinymce.get("<?php echo $this->tmce_editor_id ?>");
						if( ed && typeof b2evo_Callbacks == "object" )
						{
							// add a callback, that returns the selected (raw) html:
							b2evo_Callbacks.register_callback( "get_selected_text_for_itemform_post_content", function(value) {
									var inst = tinymce.get("<?php echo $this->tmce_editor_id ?>");
									if( ! inst ) return null;
									return inst.selection.getContent();
								}, true );

							// add a callback, that wraps a selection:
							b2evo_Callbacks.register_callback( "wrap_selection_for_itemform_post_content", function(params) {
									var inst = tinymce.get("<?php echo $this->tmce_editor_id ?>");
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
							b2evo_Callbacks.register_callback( "str_replace_for_itemform_post_content", function(params) {
									var inst = tinymce.get("<?php echo $this->tmce_editor_id ?>");
									if( ! inst ) return null;

									// Replace substring with new value
									inst.setContent( inst.getContent().replace( params.search, params.replace ) );

									return true;
								}, true );

							// add a callback, that lets us insert raw content:
							// DEPRECATED, used in b2evo 1.10.x
							b2evo_Callbacks.register_callback( "insert_raw_into_itemform_post_content", function(value) {
									tinymce.execInstanceCommand( "<?php echo $this->tmce_editor_id ?>", "mceInsertRawHTML", false, value );
									return true;
							}, true );
						}
					}

					tmce_init.setup = function( ed )
					{
						ed.on( 'init', tmce_init.oninit );
					}

					tinymce.init(tmce_init);
				}
			}

		</script>

		<?php
		$item_editor_code = ( is_object($edited_Item) ) ? $edited_Item->get_setting( 'editor_code' ) : NULL;
		if( !empty( $item_editor_code ) )
		{	// We have a preference for the current post, follow it:
			// Use tinymce if code matched the code of the current plugin.
			// fp> Note: this is a temporary solution; in the long term, this will be part of the API and the appropriate plugin will be selected.
			$use_tinymce = ($item_editor_code == $this->code);
		}
		else
		{	// We have no pref, fall back to whatever current user has last used:

			// Has the user used MCE last time he edited this particular blog?
			$use_tinymce = $this->UserSettings->get('use_tinymce_coll'.$Blog->ID );

			if( is_null($use_tinymce) )
			{	// We don't know for this blog, check if he used MCE last time he edited anything:
				$use_tinymce = $this->UserSettings->get('use_tinymce');
			}
		}

		$editor_code = 'html';
		if( $use_tinymce )
		{ // User used MCE last time, load MCE on document.ready:
			$editor_code = $this->code;
			echo '<script type="text/javascript">jQuery( tinymce_plugin_toggleEditor("'.$this->tmce_editor_id.'") );</script>';
		}
		// By default set the editor code to an empty string
		echo '<input type="hidden" name="editor_code" value="">';
		// If the js is enabled set the editor code to the currently used value
		echo '<script type="text/javascript">jQuery(\'[name="editor_code"]\').attr(\'value\', \''.$editor_code.'\');</script>';

		// We also want to save the 'last used/not-used' state: (if no NULLs, this won't change anything)
		$this->htsrv_save_editor_state( array('on'=>$use_tinymce, 'blog'=>$Blog->ID, 'item'=>$edited_Item->ID ) );

		return true;
	}


	/**
	 * Init the TinyMCE object (in front office).
	 *
	 * @return boolean
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
	 * @return string|false
	 */
	function get_tmce_init( $edit_layout )
	{
		global $Blog;
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

		// custom conf:
		$tmce_custom_conf = $this->Settings->get('tmce_custom_conf');
		// Extract plugins from the list
		$regex = '/plugins : \"(.*)\"/';
		if( preg_match($regex, $tmce_custom_conf, $matches) ) {
			// Adding extra plugins to the default list
			$tmce_plugins .= ','.$matches[1];

			// Remove plugins from the param to do not add them twice
			$tmce_custom_conf = preg_replace($regex, '', $tmce_custom_conf);
		}

		// Configuration: -- http://wiki.moxiecode.com/index.php/TinyMCE:Configuration
		$init_options = array();
		// Convert one specifc textarea to use TinyMCE:
		$init_options[] = 'selector : "textarea#'.$this->tmce_editor_id.'"';
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

		// Load the appropriate ITEM/POST styles depending on the blog's skin:
		// Note: we are not aiming for perfect wysiwyg (too heavy), just for a relevant look & feel.
		$content_css = '';
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
			$content_css .= ','.$item_css_url;		// fp> TODO: this needs to be a param... "of course" -- if none: else item_default.css ?
		}
		// else item_default.css -- is it still possible to have no skin ?

		// Load the content css files from 3rd party code, e.g. other plugins:
		global $tinymce_content_css;
		if( is_array( $tinymce_content_css ) && count( $tinymce_content_css ) )
		{
			$content_css .= ','.implode( ',', $tinymce_content_css );
		}

		$init_options[] = 'content_css : "'.$this->get_plugin_url().'editor.css?v='.( $debug ? $localtimenow : $this->version )
									.$content_css.'"';

		// Generated HTML code options:
		// do not make the path relative to "document_base_url":
		$init_options[] = 'relative_urls : false';
		$init_options[] = 'entity_encoding : "raw"';

		// Autocomplete options
		$init_options[] = 'autocomplete_options: autocomplete_static_options'; // Must be initialize before as string with usernames that are separated by comma
		$init_options[] = 'autocomplete_options_url: htsrv_url + "anon_async.php?action=autocomplete_usernames"';

		// remove_linebreaks : false,
		// not documented:	auto_cleanup_word : true,

		$init = implode( ",\n", $init_options );

		// custom conf:
		if( $tmce_custom_conf )
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
	 */
	function htsrv_save_editor_state($params)
	{
		/**
		 * @var DB
		 */
		global $DB;

		if( ! isset($params['on']) )
		{
			return;
		}

		// Save plugin usersettings
		if( !empty($params['blog']) )
		{	// This is in order to try & recall a specific state for each blog: (will be used for new posts especially)
			$this->UserSettings->set( 'use_tinymce_coll'.(int)$params['blog'], (int)$params['on'] );
		}
		$this->UserSettings->set( 'use_tinymce', (int)$params['on'] );
		$this->UserSettings->dbupdate();
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
		$Blog = $BlogCache->get_by_ID($blog);
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
		return array('save_editor_state', 'get_item_content_css');
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
	 * @see Plugin::SkinBeginHtmlHead()
	 */
	function SkinBeginHtmlHead()
	{
		global $disp;

		if( $disp == 'edit' )
		{
			require_css( $this->get_plugin_url( true ).'toolbar.css', 'blog' );
		}
	}


	/**
	 * @see Plugin::AdminEndHtmlHead()
	 */
	function AdminEndHtmlHead()
	{
		global $ctrl;

		if( $ctrl == 'items' )
		{
			require_css( $this->get_plugin_url( true ).'toolbar.css', 'blog' );
		}
	}
}

?>