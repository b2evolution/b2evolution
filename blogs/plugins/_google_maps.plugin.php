<?php
/**
 * This file implements the TEST plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package plugins
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * TEST Plugin
 *
 * This plugin responds to virtually all possible plugin events :P
 *
 * @package plugins
 */
class google_maps_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name = 'Google Maps';
	var $code = 'evo_Gmaps';
	var $priority = 50;
	var $version = '1.0';
	var $author = 'The b2evo Group';
	var $help_url = '';  // empty URL defaults to manual wiki

	/*
	 * These variables MAY be overriden.
	 */
	var $number_of_installs = 1;
	var $group = 'widget';


	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = 'Google Maps plugin';
		$this->long_desc = 'This plugin responds to positioning users';
	}


	/**
	 * Get the settings that the plugin can use.
	 *
	 * Those settings are transfered into a Settings member object of the plugin
	 * and can be edited in the backoffice (Settings / Plugins).
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @see PluginSettings
	 * @see Plugin::PluginSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultSettings( & $params )
	{
		$r = array(
			'click_me' => array(
				'label' => 'Click me!',
				'defaultvalue' => '1',
				'type' => 'checkbox',
			),
			'input_me' => array(
				'label' => 'How are you?',
				'defaultvalue' => '',
				'note' => 'Welcome to b2evolution',
			),
			'number' => array(
				'label' => 'Number',
				'defaultvalue' => '8',
				'note' => '1-9',
				'valid_range' => array( 'min'=>1, 'max'=>9 ),
			),
			'my_select' => array(
				'label' => 'Selector',
				'id' => $this->classname.'_my_select',
				'onchange' => 'document.getElementById("'.$this->classname.'_a_disabled_one").disabled = ( this.value == "sun" );',
				'defaultvalue' => 'one',
				'type' => 'select',
				'options' => array( 'sun' => 'Sunday', 'mon' => 'Monday' ),
				'note' => 'This combo is connected with the next field',
			),
			'a_disabled_one' => array(
				'label' => 'This one is disabled',
				'id' => $this->classname.'_a_disabled_one',
				'type' => 'checkbox',
				'defaultvalue' => '1',
				'disabled' => true, // this can be useful if you detect that something cannot be changed. You probably want to add a 'note' then, too.
				'note' => 'Change the above select input to "Monday" to enable it.',
			),
			'select_multiple' => array(
				'label' => $this->T_( 'Multiple select' ),
				'type' => 'select',
				'multiple' => true,
				'allow_none' => true,
				'options' => array( 'sci' => $this->T_( 'Scissors' ), 'pap' => $this->T_( 'Paper' ), 'sto' => $this->T_( 'Stone') ),
				'defaultvalue' => array( 'sci', 'sto' ),
				'note' => $this-> T_( 'This is a free style Multiple Select. You can choose zero or one or more items' )
			),
			/*
			 * note: The $this->T_( string )function tanslates the string.
			 * However since it inherits from the class Plugin you will need
			 * to provide the translation on a per plugin basis. In other
			 * words: this will not be translated through B2evolution.
			 */
			'blog' => array(
				'label' => 'A blog',
				'type' => 'select_blog',  // TODO: does not scale with 500 blogs
				'allow_none' => true,
			),
			'blogs' => array(
				'label' => 'A set of blogs',
				'type' => 'select_blog',	// TODO: BROKEN + does not scale with 500 blogs
				'multiple' => true,
				'allow_none' => true,
			),
			'single_user' => array(
				'label' => 'A single user',
				'type' => 'select_user',
				'allow_none' => true,
				'default_value' => 0,
				'note' => 'Allows chosing none or one user'
			),
			'sets' => array(
				'type' => 'select_user',
				'label' => 'Multiple users',
				'min_count' => 0,
				'max_count' => 3,
				'multiple' => 'true',
				'allow_none' => true,
				'note' => 'Allows none or one or more than one user (up to three in this example)',
				'entries' => array(
					'user' => array(
						'label' => 'A user',
						'type' => 'select_user',		// TODO: does not scale with 500 users
						'allow_none' => true,
					),
				),
			),
			'maxlen' => array(
				'label' => 'Max',
				'type' => 'textarea',
				'maxlength' => 10,
				'note' => 'Maximum length is 10 here.',
			),
		);

		if( $params['for_editing'] )
		{ // we're asked for the settings for editing:
			if( $this->Settings->get('my_select') == 'mon' )
			{
				$r['a_disabled_one']['disabled'] = false;
			}
		}

		return $r;
	}


	/**
	 * User settings.
	 *
	 * @see Plugin::GetDefaultUserSettings()
	 * @see PluginUserSettings
	 * @see Plugin::PluginUserSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultUserSettings()
	{
		return array(
				'echo_random' => array(
					'label' => 'Echo a random number in AdminBeginPayload event',
					'type' => 'checkbox',
					'defaultvalue' => '0',
				),
				'deactivate' => array(
					'label' => 'Deactivate',
					'type' => 'checkbox',
					'defaultvalue' => '0',
				),
			);
	}


	/**
	 * We trigger an extra event ourself (which we also provide ourselves).
	 *
	 * @return array
	 */
	function GetExtraEvents()
	{
		return array(
				// Gets "min" and "max" as params and should return a random number in between:
				'test_plugin_get_random' => 'TEST event that returns a random number.',
			);
	}


	/**
	 * Define a test cron job
	 */
	function GetCronJobs( & $params )
	{
		return array(
				array(
					'name' => 'TEST plugin - cron job',
					'ctrl' => 'test_job',
					'params' => array( 'param' => 1 ),
				),
			);
	}


	/**
	 * Execute/Handle a test/sample cronjob.
	 */
	function ExecCronJob( & $params )
	{
		if( $params['ctrl'] == 'test_job' )
		{
			return array( 'code' => 1, 'message' => 'Test successful.' );
		}
	}


	/**
	 * Deactive the plugin for the current request if the user wants it so.
	 * @see Plugin::AppendLoginRegisteredUser()
	 */
	function AppendLoginRegisteredUser()
	{
		if( $this->UserSettings->get('deactivate') )
		{
			$this->forget_events();
		}
	}


	/**
	 * Define some dependencies.
	 *
	 * @see Plugin::GetDependencies()
	 * @return array
	 */
	function GetDependencies()
	{
		return array(
				'recommends' => array(
					'events_by_one' => array( array('Foo', 'Bar'), array('FooBar', 'BarFoo') ), // a plugin that provides "Foo" and "Bar", and one (may be the same) that provides "FooBar" and "BarFoo"
					'events' => array( 'some_event', 'some_other_event' ),
					'plugins' => array( array( 'some_plugin', '1' ) ), // at least version 1 of some_plugin
				),

				'requires' => array(
					// Same syntax as with the 'recommends' class above, but would prevent the plugin from being installed.
				),
			);
	}


	/**
	 * Gets asked for, if user settings get updated.
	 *
	 * We just add a note.
	 *
	 * @see Plugin::PluginUserSettingsUpdateAction()
	 */
	function PluginUserSettingsUpdateAction()
	{
		if( $this->UserSettings->get('echo_random') )
		{
			$this->msg( 'TEST plugin: Random numbers have been disabled.' );
		}
		else
		{
			$this->msg( 'TEST plugin: Random numbers have been enabled.' );
		}

		return true;
	}


	/**
	 * Event handlers:
	 */




	/**
	 * @see Plugin::AdminDisplayItemFormFieldset()
	 */
	function AdminDisplayItemFormFieldset( & $params )
	{
		$Item = $params['Item'];
		require_js( '#jqueryUI#');
		$params['Form']->begin_fieldset( 'Google Maps plugin' );
		$params['Form']->hidden( 'google_map_zoom', '', array('id' => 'google_map_zoom'));
		$params['Form']->text_input( 'address', '', 50, 'Input adress', '', array('maxlength'=>500, 'id' =>'searchbox'));
		$params['Form']->button(array ('id' => 'locate_on_map', 'type' =>'button', 'value' => 'Locate on map') );

		$lat = $Item->get('double3');
		$lng = $Item->get('double4');

	?>

	<div id="map_canvas" style="width:100%; height:400px; margin: 5px 5px 5px 5px;"></div>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript">
	<?php
	if (!empty($lat) && !empty($lng))
	{
		?>
		var latlng = new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $lng;?>);
		<?php
	}
	else
	{
		?>
		var latlng = new google.maps.LatLng(48.856614, 2.3522219000000177);
		<?php
	}
	?>
	var mapTypes = new Array();
	mapTypes.push(google.maps.MapTypeId.HYBRID);
	mapTypes.push(google.maps.MapTypeId.ROADMAP);
	mapTypes.push(google.maps.MapTypeId.SATELLITE);
	mapTypes.push(google.maps.MapTypeId.TERRAIN);


	var myOptions = {
		  zoom: 11,
		  center: latlng,
		  mapTypeId: google.maps.MapTypeId.HYBRID,
		  mapTypeControlOptions:
			  {
			   style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
			   mapTypeIds: mapTypes
			  }
		};

	var map = new google.maps.Map(document.getElementById("map_canvas"),
			myOptions);

//	var traffic = new google.maps.TrafficLayer();
//	traffic.setMap(map);

	var marker = new google.maps.Marker({
		position: latlng,
		map: map,
		title:"Position",
		draggable: true
		});

	var geocoder = new google.maps.Geocoder();
	var searchLoc = null;
	var bounds = null;
	
	google.maps.event.addListener(marker, 'dragend', function()
	{
		map.setCenter(marker.getPosition());
		jQuery('#item_double3').val(marker.getPosition().lat());
		jQuery('#item_double4').val(marker.getPosition().lng());
	});

	google.maps.event.addListener(map, 'zoom_changed', function()
	{
		jQuery('#google_map_zoom').val(map.getZoom());
	});
	google.maps.event.addListener(map, 'click', function(event)
	{
		if (marker != null)
		{
			marker.setMap(null);
		}
		marker = new google.maps.Marker({
		position: event.latLng,
		map: map,
		title:"Position",
		draggable: true
		});

		map.setCenter(marker.getPosition());
		jQuery('#item_double3').val(event.latLng.lat());
		jQuery('#item_double4').val(event.latLng.lng());

		google.maps.event.addListener(marker, 'dragend', function()
		{
			map.setCenter(marker.getPosition());
			jQuery('#item_double3').val(marker.getPosition().lat());
			jQuery('#item_double4').val(marker.getPosition().lng());
		});
	});


	jQuery("#searchbox").autocomplete(
		{
		source: function(request, response)
		  {
			if (geocoder == null)
			{
				geocoder = new google.maps.Geocoder();
			}
			geocoder.geocode( {'address': request.term }, function(results, status)
			{
				if (status == google.maps.GeocoderStatus.OK)
				{
					searchLoc = results[0].geometry.location;
					bounds = results[0].geometry.bounds;

					geocoder.geocode({'latLng': searchLoc}, function(results1, status1)
					{
						if (status1 == google.maps.GeocoderStatus.OK)
						{
							if (results1[1])
							{
								response(jQuery.map(results1, function(loc)
								{
									return {
										label  : loc.formatted_address,
										value  : loc.formatted_address,
										bounds : loc.geometry.bounds,
										location : loc.geometry.location
									  }
								})
								);
							}
						}
					});
				}
				else
				{
					searchLoc = null;
					bounds = null;
				}
				  });
			   },
		select: function(event,ui)
		{
			var pos = ui.item.position;
			var lct = ui.item.locType;
			bounds = ui.item.bounds;
			searchLoc = ui.item.location;
		}
		});
	jQuery('#locate_on_map').click(function(){
	if (searchLoc != null)
	{
				if (marker != null)
					{
						marker.setMap(null);
					}

					marker = new google.maps.Marker({
					position: searchLoc,
					map: map,
					title:"Position",
					draggable: true
					});
					map.fitBounds(bounds);
					google.maps.event.addListener(marker, 'dragend', function()
					{
						map.setCenter(marker.getPosition());
						jQuery('#item_double3').val(marker.getPosition().lat());
						jQuery('#item_double4').val(marker.getPosition().lng());

					});
					jQuery('#item_double3').val(searchLoc.lat());
					jQuery('#item_double4').val(searchLoc.lng());
					jQuery('#google_map_zoom').val(map.getZoom());
	}

	})
	</script>

	<?php
		$params['Form']->end_fieldset();
	}


	/**
	 * @see Plugin::SkinBeginHtmlHead()
	 */
	function SkinBeginHtmlHead()
	{
		require_js( '#jquery#');
	}

	function SkinTag( $params )
	{
		global $Item;
		$lat = $Item->get('double3');
		$lng = $Item->get('double4');
	?>
	<div id="map_canvas" style="width:300px; height:300px; margin: 5px 5px 5px 5px;"></div>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript">
	<?php
	if (!empty($lat) && !empty($lng))
	{
		?>
		var latlng = new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $lng;?>);
		<?php
	}
	else
	{
		?>
		var latlng = new google.maps.LatLng(48.856614, 2.3522219000000177);
		<?php
	}
	?>
	var mapTypes = new Array();
	mapTypes.push(google.maps.MapTypeId.HYBRID);
	mapTypes.push(google.maps.MapTypeId.ROADMAP);
	mapTypes.push(google.maps.MapTypeId.SATELLITE);
	mapTypes.push(google.maps.MapTypeId.TERRAIN);

	var myOptions = {
		  zoom: 11,
		  center: latlng,
		  mapTypeId: google.maps.MapTypeId.HYBRID,
		  mapTypeControlOptions:
			  {
			   style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
			   mapTypeIds: mapTypes
			  }
		};

	var map = new google.maps.Map(document.getElementById("map_canvas"),
			myOptions);

	var marker = new google.maps.Marker({
		position: latlng,
		map: map,
		title:"Position"
		});

	</script>
	<?php
	}

	/**
	 * Event handler: Gets invoked in /toolbar.inc.php after the menu structure is built.
	 *
	 * @see Plugin::AdminAfterEvobarInit()
	 */
	function AdminAfterEvobarInit()
	{

		// The following is a tiny bit hackish and should probably be abstracted a bit, but just a little bit
		// The idea is too let plugins hook pretty much anywhere into the menu structure, including Left AND Right menus.

		global $topleft_Menu;
		$topleft_Menu->add_menu_entries( 'tools', array(
				'urls_sep' => array(
						'separator' => true,
					),
				'urls' => array(
						'text' => 'Test plugin&hellip;',
						'href' => $this->get_tools_tab_url(),
					),
			) );

	}


	/**
	 * Event handler: Gets invoked in /admin.php for every backoffice page after
	 *                the menu structure is built. You can use the {@link $AdminUI} object
	 *                to modify it.
	 *
	 * This is the hook to register menu entries. See {@link register_menu_entry()}.
	 *
	 * @see Plugin::AdminAfterMenuInit()
	 */
	function AdminAfterMenuInit()
	{
		$this->register_menu_entry( 'Test tab' );
	}


	/**
	 * Event handler: Called when handling actions for the "Tools" menu.
	 *
	 * Use {@link $Messages} to add Messages for the user.
	 *
	 * @see Plugin::AdminToolAction()
	 */
	function AdminToolAction( $params )
	{
		global $Messages;

		$Messages->add( 'Hello, This is the AdminToolAction for the TEST plugin.' );
	}


	/**
	 * Event handler: Called when displaying the block in the "Tools" menu.
	 *
	 * @see Plugin::AdminToolPayload()
	 */
	function AdminToolPayload( $params )
	{
		echo 'Hello, This is the AdminToolPayload for the TEST plugin.';
	}


	/**
	 * Event handler: Method that gets invoked when our tab (?tab=plug_ID_X) is selected.
	 *
	 * You should catch params (GET/POST) here and do actions (no output!).
	 * Use {@link $Messages} to add messages for the user.
	 *
	 * @see Plugin::AdminTabAction()
	 */
	function AdminTabAction()
	{
		global $Plugins;

		$this->text_from_AdminTabAction = '<p>This is text from AdminTabAction for the TEST plugin.</p>'
			.'<p>Here is a random number: '
			.$Plugins->get_trigger_event_first_return('test_plugin_get_random', array( 'min'=>-1000, 'max'=>1000 )).'</p>';

		if( $this->param_text = param( $this->get_class_id('text') ) )
		{
			$this->text_from_AdminTabAction .= '<p>You have said: '.$this->param_text.'</p>';
		}
	}


	/**
	 * Event handler: Gets invoked when our tab is selected and should get displayed.
	 *
	 * @see Plugin::AdminTabPayload()
	 */
	function AdminTabPayload()
	{
		echo 'Hello, this is the AdminTabPayload for the TEST plugin.';

		echo $this->text_from_AdminTabAction;

		// TODO: this is tedious.. should either be a global function (get_admin_Form()) or a plugin helper..
		$Form = new Form();
		$Form->begin_form();

		$Form->add_crumb( 'plugin_test' );
		$Form->hidden_ctrl(); // needed to pass the "ctrl=tools" param
		$Form->hiddens_by_key( get_memorized() ); // needed to pass all other memorized params, especially "tab"

		$Form->text_input( $this->get_class_id().'_text', $this->param_text, '20', 'Text' );

		$Form->button_input(); // default "submit" button

		$Form->end_form();
	}

	/**
	 * Event handler: Called when the plugin has been installed.
	 * @see Plugin::AfterInstall()
	 */
	function AfterInstall()
	{
		$this->msg( 'Google Maps plugin sucessfully installed.' );
	}


	/**
	 * Event handler: Called before the plugin is going to be un-installed.
	 * @see Plugin::BeforeUninstall()
	 */
	function BeforeUninstall()
	{
		$this->msg( 'Google Maps plugin sucessfully un-installed.' );
		return true;
	}

}
/*
 * $Log$
 * Revision 1.3  2011/10/07 14:35:36  efy-vitalij
 * remake google maps plugin
 *
 */
?>
