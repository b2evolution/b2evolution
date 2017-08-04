<?php
/**
 * This file implements the Google Maps plugin plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Google Maps Plugin
 *
 *
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
	var $version = '6.9.3';
	var $author = 'The b2evo Group';
	var $help_url = '';  // empty URL defaults to manual wiki

	/*
	 * These variables MAY be overriden.
	 */
	var $number_of_installs = 1;
	var $group = 'widget';
	var $subgroup = 'infoitem';
	var $number_of_widgets ;


	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Google Maps plugin');
		$this->long_desc = T_('This plugin displays positions on a map.');
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
		$r = array(
			'height_back' => array(
				'label' => T_( 'Map height on edit post page' ),
				'defaultvalue' => '300',
				'note' => '',
				),
			'map_type' => array(
				'label' => T_( 'Map default view ' ),
				'type' => 'radio',
				'options' => array( array( 'map', T_( 'Map' ) ), array( 'satellite', T_( 'Satellite' ) ) ),
				'defaultvalue' => 'map',
				'note' => ''
			)
			);
		return $r;
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return
	 */
	function get_coll_setting_definitions( & $params )
	{
		$r = array_merge( array(
			'api_key' => array(
				'label' => T_('API key'),
				'size' => 40,
				'defaultvalue' => '',
				'note' => sprintf( T_('Visit the <a %s>Google Maps API</a> documentation site for instructions on how to obtain an API key'),
						'href="https://developers.google.com/maps/documentation/javascript/get-api-key#get-an-api-key" target="_blank"' ),
				)
			), parent::get_coll_setting_definitions( $params ) );

		return $r;
	}


		/**
	 * Get definitions for widget editable params
	 *
	 * @see Plugin::get_widget_param_definitions()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		global $preview;

		$r = array_merge( array(
			'map_title' => array(
				'label' => T_('Widget title'),
				'defaultvalue' => T_('Google maps Widget'),
				'note' => T_('Widget title'),
				'set_for_plugin' => true,
				),
			'width' => array(
				'label' => T_('Map width on page'),
				'defaultvalue' => '',
				'note' => T_('100% width if left empty'),
				),
			'height_front' => array(
				'label' => T_('Map height on page'),
				'defaultvalue' => '300px',
				'note' => '',
				),
			'map_type' => array(
				'label' => T_( 'Map default view ' ),
				'type' => 'radio',
				'options' => array( array('map', T_( 'Map' ) ), array( 'satellite', T_( 'Satellite' ) ) ),
				'defaultvalue' => 'map',
				'note' => ''
				),
			), parent::get_widget_param_definitions( $params ) );

		if( $preview && isset( $r['allow_blockcache'] ) )
		{	// Disable block caching for this widget when item is previewed currently:
			$r['allow_blockcache']['defaultvalue'] = false;
		}

		return $r;
	}


	/**
	 * Get keys for block/widget caching
	 *
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @param integer Widget ID
	 * @return array of keys this widget depends on
	 */
	function get_widget_cache_keys( $widget_ID = 0 )
	{
		global $Collection, $Blog, $Item;

		return array(
				'wi_ID'        => $widget_ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
				'item_ID'      => $Item->ID, // Has the Item page changed?
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
		return array();
	}


	/**
	 *  Add 'px' to display param if need.
	 *
	 * @param mixed display param
	 * @return string
	 */
	function display_param($param)
	{
		if ( ! empty ($param) )
		{
			if( ! preg_match("/\D{1,}$/", $param) )
			{
				$param = $param.'px';
			}
		}
		else
		{
			$param = '100%';
		}
		return  $param;
	}

	/**
	 * @see Plugin::AdminDisplayItemFormFieldset()
	 */
	function AdminDisplayItemFormFieldset( & $params )
	{
		global $Collection, $Blog, $DB, $admin_url;

		// fp>vitaliy : make thhis title configurable per blog . default shoul dbe as below.
		$plugin_title = $this->Settings->get( 'map_title_coll'.$Blog->ID );
		$plugin_title = empty( $plugin_title ) ? T_( 'Google Maps plugin' ) : $plugin_title;
		$params['Form']->begin_fieldset( $plugin_title, array( 'id' => 'itemform_googlemap', 'fold' => ( isset( $params['edit_layout'] ) && $params['edit_layout'] == 'expert' ) ) );
		$api_key = $this->get_coll_setting( 'api_key', $Blog );

		$Item = $params['Item'];

		if( $Item->get_type_setting( 'use_coordinates' ) == 'never' )
		{
			$url = $admin_url.'?ctrl=itemtypes&amp;action=edit&amp;blog='.$Blog->ID.'&amp;ityp_ID='.$Item->get_ItemType()->ID.'#itemtype_features';

			echo sprintf( T_('You must turn on the <b>"Use coordinates"</b> setting in Post Type settings <a %s>Settings</a> tab so the Google Maps plugin can save its coordinates.'), 'href="'.$url.'"' );
			$params['Form']->end_fieldset();
			return;
		}

		if( empty( $api_key ) )
		{
			$url = $admin_url.'?ctrl=coll_settings&tab=plugins&blog='.$Blog->ID.'&plugin_group=widget';

			echo sprintf( T_('You must specify a valid Google Maps API key in the Plugins settings <a %s>Collection Settings</a> tab to use the plugin.'), 'href="'.$url.'"' );
			$params['Form']->end_fieldset();
			return;
		}

		$params['Form']->switch_layout( 'linespan' );

		require_js( '#jqueryUI#', 'blog' );

		$lat = $Item->get_setting('latitude');
		$lng = $Item->get_setting('longitude');
		$zoom = $Item->get_setting('map_zoom');
		$map_type = $Item->get_setting('map_type');

		$city_ID = $Item->get('city_ID');
		$subrg_ID = $Item->get('subrg_ID');
		$rgn_ID = $Item->get('rgn_ID');
		$ctry_ID = $Item->get('ctry_ID');

		$search_location = '';

		if (empty( $lat ) && empty( $lng ) )
		{	// post location not set

			if ( ! empty ( $city_ID ) )
			{
				$query = '
					SELECT city_name
					FROM T_regional__city
					WHERE city_ID  = '.$DB->quote( $city_ID );

				$text = $DB->get_var($query);
				$search_location .= "$text";
			}

			if ( ! empty ( $subrg_ID ) )
			{
				$query = '
					SELECT subrg_name
					FROM T_regional__subregion
					WHERE subrg_ID  = '.$DB->quote( $subrg_ID );

				$text = $DB->get_var($query);
				if ( empty ( $search_location ) )
				{
					$search_location .= "$text";
				}
				else
				{
					$search_location .= ", $text";
				}
			}

			if ( ! empty ( $rgn_ID ) )
			{
				$query = '
					SELECT rgn_name
					FROM T_regional__region
					WHERE rgn_ID = '.$DB->quote( $rgn_ID );

				$text = $DB->get_var($query);
				if ( empty ( $search_location ) )
				{
					$search_location .= "$text";
				}
				else
				{
					$search_location .= ", $text";
				}
			}

			if ( ! empty ( $ctry_ID ) )
			{
				$query = '
					SELECT ctry_name
					FROM T_regional__country
					WHERE ctry_ID = '.$DB->quote( $ctry_ID );

				$text = $DB->get_var($query);
				if ( empty ( $search_location ) )
				{
					$search_location .= "$text";
				}
				else
				{
					$search_location .= ", $text";
				}
			}

		}

		if( empty( $zoom ) )
		{
			$zoom = 17;
		}

		if( empty( $map_type ) )
		{
			$map_type = (string)$this->Settings->get('map_type');
		}

		$params['Form']->hidden( 'google_map_zoom', $zoom, array('id' => 'google_map_zoom'));
		$params['Form']->hidden( 'google_map_type', $map_type, array('id' => 'google_map_type'));
		$params['Form']->text_input( 'address', $search_location, 40, '<strong>1. </strong>'.T_('<b>Search for an address</b> (may be approximate)'), '', array('maxlength'=>500, 'id' =>'searchbox'));
		$params['Form']->button(array ('id' => 'locate_on_map', 'type' =>'button', 'value' => T_('Locate on map') ) );

		$height = $this->display_param($this->Settings->get('height_back'));
		$height = 'height:'.$height;

		echo '<div style="margin-top:1ex"><strong>2. '.T_('Drag the pin to the exact location you want:').'</strong></div>';

	?>
	<div id="map_canvas" style="width:100%; <?php echo $height; ?>; margin: 5px 0px;"></div>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=<?php echo $api_key;?>"></script>
	<script type="text/javascript">
	var post_position = 0;
	<?php
	switch ($map_type)
	{
		case 'satellite':
			?>
			var mapTypeId = google.maps.MapTypeId.SATELLITE;
			<?php
			break;
		case 'hybrid':
			?>
			var mapTypeId = google.maps.MapTypeId.HYBRID;
			<?php
			break;
		default:
			?>
			var mapTypeId = google.maps.MapTypeId.ROADMAP;
			<?php
			break;
	}

	if (!empty( $lat ) || !empty( $lng ) )
	{
		?>
		var latlng = new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $lng;?>);
		var zoom = <?php echo $zoom; ?>;
		var post_position = 1; // position is set
		<?php
	}
	else
	{
		?>
		var latlng = new google.maps.LatLng(48.856614, 2.3522219000000177);
		var zoom = 11;
		<?php
		if ( !empty ( $search_location ) )
		{
			?>

			var geocoder = new google.maps.Geocoder();
			geocoder.geocode( {'address': '<?php echo format_to_js( $search_location ); ?>'}, function(results, status)
			{
				if (status == google.maps.GeocoderStatus.OK)
				{
					var searchLoc = results[0].geometry.location;
					var bounds = results[0].geometry.bounds;
					if (bounds != null)
					{
						map.fitBounds(bounds);
					}
					else
					{
						map.setCenter(searchLoc);
					}

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
					marker_dragend(marker, map);
				}
			});

			<?php

		}
	}
	?>


	var mapTypes = new Array();
	mapTypes.push(google.maps.MapTypeId.HYBRID);
	mapTypes.push(google.maps.MapTypeId.ROADMAP);
	mapTypes.push(google.maps.MapTypeId.SATELLITE);
	mapTypes.push(google.maps.MapTypeId.TERRAIN);


	var myOptions = {
		  zoom: zoom,
		  center: latlng,
		  mapTypeId: mapTypeId,
		  scrollwheel : false,
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
		title:"Position",
		draggable: true
		});

	var geocoder = new google.maps.Geocoder();
	var geo_region = null;

	// If the map is initially hidden, we will need to trigger the resize event of the map when it is initially shown,
	// otherwise the map display will be empty for quite a while.
	var mapEl = jQuery( '#map_canvas' );

	if( mapEl.not(':visible') )
	{ // map is hidden in folded fieldset
		var target = document.getElementById( 'itemform_googlemap' ).parentElement; // this is the element that we need to observe
		var config = { attributes: true };
		var observer = new MutationObserver( function( mutations )
			{
				mutations.forEach( function( mutation )
					{
						if( mapEl.is( ':visible' ) )
						{ // map is now visible
							google.maps.event.trigger( map, 'resize' );
							observer.disconnect(); // we only need to do this once so we can stop observing
						}
					} );
			} );

		observer.observe( target, config );
	}

	function set_region(region_code)
	{
		geo_region = region_code;
	}

	geocoder.geocode({'latLng': latlng}, function(region_res, region_status)
		{
			if (region_status == google.maps.GeocoderStatus.OK)
			{
				if (region_res)
				{
					var country = region_res.pop();
					set_region(country.address_components[0].short_name);
				}
			}
			else
			{
				set_region('');
			}
		});


	var searchLoc = null;
	var bounds = null;

	function marker_dragend(marker, map)
	{
	google.maps.event.addListener(marker, 'dragend', function()
	{
		map.setCenter(marker.getPosition());
		jQuery('input[name=item_latitude]').val(marker.getPosition().lat());



		jQuery('input[name=item_longitude]').val(marker.getPosition().lng());

		geocoder.geocode({'latLng': marker.getPosition()}, function(region_res, region_status)
		{
			if (region_status == google.maps.GeocoderStatus.OK)
			{
				if (region_res)
				{
					var country = region_res.pop();
					set_region(country.address_components[0].short_name);
				}
			}
			else
			{
				set_region('');
			}
		});

	});
	}

	marker_dragend(marker, map);

	google.maps.event.addListener(map, 'zoom_changed', function()
	{
		jQuery('#google_map_zoom').val(map.getZoom());
	});
	google.maps.event.addListener(map, 'maptypeid_changed', function()
	{
		jQuery('#google_map_type').val(map.getMapTypeId());
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

		geocoder.geocode({'latLng': event.latLng}, function(region_res, region_status)
		{
			if (region_status == google.maps.GeocoderStatus.OK)
			{
				if (region_res)
				{
					var country = region_res.pop();
					set_region(country.address_components[0].short_name);
				}
			}
			else
			{
				set_region('');
			}
		});

		map.setCenter(marker.getPosition());
		jQuery('input[name=item_latitude]').val(event.latLng.lat());
		jQuery('input[name=item_longitude]').val(event.latLng.lng());

		marker_dragend(marker, map);
	});


	jQuery("#searchbox").autocomplete(
		{
		source: function(request, response)
		  {
			if (geocoder == null)
			{
				geocoder = new google.maps.Geocoder();
			}
			geocoder.geocode( {'address': request.term, 'region' : geo_region, 'bounds':  map.getBounds() }, function(results, status)
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

function locate()
{
	if (searchLoc != null)
	{

	geocoder.geocode({'latLng': searchLoc}, function(region_res, region_status)
		{
			if (region_status == google.maps.GeocoderStatus.OK)
			{
				if (region_res)
				{
					var country = region_res.pop();
					set_region(country.address_components[0].short_name);
				}
			}
			else
			{
				set_region('');
			}
		});

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
		if (bounds !== undefined)
		{
			map.fitBounds(bounds);
		}
		else
		{
			map.setCenter(searchLoc);
		}
		marker_dragend(marker, map);
		jQuery('input[name=item_latitude]').val(searchLoc.lat());
		jQuery('input[name=item_longitude]').val(searchLoc.lng());
		jQuery('#google_map_zoom').val(map.getZoom());
		jQuery('#google_map_type').val(map.getMapTypeId());
	}

}

	jQuery("#searchbox").keypress(function(event){
		if (event.keyCode == 13)
		{
			locate();
			return false
		}

	});

	jQuery('#locate_on_map').click( locate );

	function post_location_change( adress )
	{
		if ( adress == '')
		{
			adress = 'Paris, France';
		}
			jQuery('#searchbox').val(adress);
			geocoder.geocode( {'address': adress}, function(results, status)
			{
				if (status == google.maps.GeocoderStatus.OK)
				{
					var searchLoc = results[0].geometry.location;
					var bounds = results[0].geometry.bounds;
					if (bounds != null)
					{
						map.fitBounds(bounds);
					}
					else
					{
						map.setCenter(searchLoc);
					}
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
					marker_dragend(marker, map);
				}
			});

	}

	jQuery(document).ready( function(){
		if ( post_position == 0 )
		{
			jQuery('#item_ctry_ID').change(function(){
				var adress = '';
				var text =  jQuery('#item_city_ID option:selected').text();

				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}

				text =  jQuery('#item_subrg_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}

				text =  jQuery('#item_rgn_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}

				text =  jQuery('#item_ctry_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}

			post_location_change( adress );
			});

			jQuery('#item_subrg_ID').change(function(){
				var adress = '';
				text =  jQuery('#item_subrg_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}

				text =  jQuery('#item_rgn_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}

				text =  jQuery('#item_ctry_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}
				post_location_change( adress );
				});

			jQuery('#item_rgn_ID').change(function(){
				var adress = '';
				text =  jQuery('#item_rgn_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}

				text =  jQuery('#item_ctry_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}
				post_location_change( adress );
				});

			jQuery('#item_ctry_ID').change(function(){
				var adress = '';
				text =  jQuery('#item_ctry_ID option:selected').text();
				if (text != 'Unknown')
				{
					if (adress == '')
					{
						adress = adress + text;
					}
					else
					{
						adress = adress + ', ' + text;
					}
				}
				post_location_change( adress );
				});
			}

		});
	</script>

	<?php
		$params['Form']->switch_layout( NULL );

		$params['Form']->end_fieldset();
	}


	function DisplayItemFormFieldset(& $params)
	{
		$this->AdminDisplayItemFormFieldset( $params );
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
		require_js( '#jquery#', 'blog' );
	}


	function SkinTag( & $params )
	{
		global $Collection, $Blog, $Item;

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object:
			return;
		}

		$this->number_of_widgets += 1;

		if( $Item->get_type_setting( 'use_coordinates' ) == 'never' )
		{	// Coordinates are not allowed for the item type:
			return;
		}

		$lat = $Item->get_setting( 'latitude' );
		$lng = $Item->get_setting( 'longitude' );
		if( empty( $lat ) && empty( $lng ) )
		{	// Coordinates must be defined for the viewed Item:
			return;
		}

		$width = $this->display_param($this->get_widget_setting('width', $params));
		$width = 'width:'.$width;

		$height = $this->display_param($this->get_widget_setting('height_front', $params));
		$height = 'height:'.$height;

		?>
		<div class="map_title"><?php echo $this->get_widget_setting('map_title_coll'.$Blog->ID, $params); ?></div>
		<div class="map_canvas" id="map_canvas<?php echo $this->number_of_widgets; ?>" style="<?php echo $width; ?>; <?php echo $height; ?>; margin: 5px 5px 5px 5px;"></div>
		<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=<?php echo $api_key;?>"></script>
		<script type="text/javascript">
		<?php
		$map_type = (string)$this->get_widget_setting('map_type', $params);
		switch ($map_type)
		{
			case 'satellite':
				?>
				var mapTypeId = google.maps.MapTypeId.SATELLITE;
				<?php
				break;
			default:
				?>
				var mapTypeId = google.maps.MapTypeId.ROADMAP;
				<?php
				break;
		}
		?>
			var latlng = new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $lng;?>);
			var mapTypes = new Array();
			mapTypes.push(google.maps.MapTypeId.HYBRID);
			mapTypes.push(google.maps.MapTypeId.ROADMAP);
			mapTypes.push(google.maps.MapTypeId.SATELLITE);
			mapTypes.push(google.maps.MapTypeId.TERRAIN);

			var myOptions = {
				  zoom: 17,
				  center: latlng,
				  mapTypeId: mapTypeId,
				  scrollwheel: false,
				  mapTypeControlOptions:
					  {
					   style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
					   mapTypeIds: mapTypes
					  }
				};
			var map<?php echo $this->number_of_widgets; ?> = new google.maps.Map(document.getElementById("map_canvas<?php echo $this->number_of_widgets; ?>"),
					myOptions);
			var marker<?php echo $this->number_of_widgets; ?> = new google.maps.Marker({
				position: latlng,
				map: map<?php echo $this->number_of_widgets; ?>,
				title:"Position"
				});
			</script>
			<?php
	}

	/**
	 * Event handler: Called when the plugin has been installed.
	 * @see Plugin::AfterInstall()
	 */
	function AfterInstall()
	{
		$this->msg( T_('Google Maps plugin sucessfully installed.') );
	}


	/**
	 * Event handler: Called before the plugin is going to be un-installed.
	 * @see Plugin::BeforeUninstall()
	 */
	function BeforeUninstall( & $params )
	{
		$this->msg( T_('Google Maps plugin sucessfully un-installed.') );
		return true;
	}

}

?>