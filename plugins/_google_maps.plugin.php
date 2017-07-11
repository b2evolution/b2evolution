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
	var $version = '6.9.2';
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
				),
			'map_title_coll' => array(
				'label' => T_('Widget title'),
				'defaultvalue' => T_('Google Maps plugin'),
				'note' => T_('Widget title on edit post page')
				),
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
			'section_post_edit_start' => array(
				'layout' => 'begin_fieldset',
				'label' => T_('Item detail widget settings')
			),
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
				'map_zoom' => array(
					'label' => T_('Map default zoom' ),
					'type' => 'integer',
					'defaultvalue' => 10,
					'size' => 4
					),
			'section_post_edit_end' => array(
				'layout' => 'end_fieldset'
			),

			'section_list_start' => array(
				'layout' => 'begin_fieldset',
				'label' => T_('Listing content widget settings')
			),
				'list_map_title' => array(
					'label' => T_('Widget title'),
					'defaultvalue' => T_('Location Map'),
					'note' => T_('Widget title'),
					'set_for_plugin' => true,
					),
				'list_map_width' => array(
					'label' => T_('Map width'),
					'defaultvalue' => '',
					'note' => T_('100% width if left empty'),
					),
				'list_map_height' => array(
					'label' => T_('Map height'),
					'defaultvalue' => '300px',
					'note' => '',
					),
				'list_map_type' => array(
					'label' => T_( 'Map default view ' ),
					'type' => 'radio',
					'options' => array( array('roadmap', T_( 'Map' ) ), array( 'satellite', T_( 'Satellite' ) ) ),
					'defaultvalue' => 'roadmap',
					'note' => ''
					),
				'list_blog_ID' => array(
					'label' => T_('Collections'),
					'note' => T_('List collection IDs separated by \',\', \'*\' for all collections, \'-\' for current collection without aggregation or leave empty for current collection including aggregation.'),
					'size' => 4,
					'type' => 'text',
					'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																		'error'   => T_('Invalid list of Collection IDs.') ),
					'defaultvalue' => '',
					),
				'list_order_by' => array(
					'label' => T_('Order by'),
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => array(
							'datestart'                => T_('Date issued (Default)'),
							'order'                    => T_('Order (as explicitly specified)'),
							//'datedeadline'           => T_('Deadline'),
							'title'                    => T_('Title'),
							'datecreated'              => T_('Date created'),
							'datemodified'             => T_('Date last modified'),
							'last_touched_ts'          => T_('Date last touched'),
							'contents_last_updated_ts' => T_('Contents last updated'),
							'urltitle'                 => T_('URL "filename"'),
							'priority'                 => T_('Priority'),
							'numviews'                 => T_('Number of members who have viewed the post (If tracking enabled)'),
						),
					'defaultvalue' => 'datestart',
					),
				'list_order_dir' => array(
					'label' => T_('Direction'),
					'note' => T_('How to sort the items'),
					'type' => 'radio',
					'options' => array( array( 'ASC', T_('Ascending') ),
										array( 'DESC', T_('Descending') ) ),
					'defaultvalue' => 'DESC',
					),
				'list_limit' => array(
					'label' => T_( 'Max items' ),
					'note' => T_( 'Maximum number of items to display.' ),
					'size' => 4,
					'defaultvalue' => 10,
					),
			'section_list_end' => array(
				'layout' => 'end_fieldset'
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
		$plugin_title = $this->get_coll_setting( 'map_title_coll', $Blog );
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
		global $google_maps_initialized;
		/**
		 * Default params:
		 */
		$params = array_merge( array(
				// This is what will enclose the block in the skin:
				'block_start'            => '<div>',
				'block_end'              => '</div>\n',
				'list_block_start'       => '<div>',
				'list_block_end'         => "</div>\n",
				// This is what will enclose the title:
				'block_title_start' => '<h3>',
				'block_title_end'   => '</h3>',
				'list_block_title_start' => '<h3>',
				'list_block_title_end'   => '</h3>',
				// This is what will enclose the body:
				'block_body_start'  => '',
				'block_body_end'    => '',
				'list_block_body_start'  => '',
				'list_block_body_end'    => '',
			), $params );

		$api_key = $this->get_coll_setting( 'api_key', $Blog );

		if( empty( $api_key ) )
		{
			$url = $admin_url.'?ctrl=coll_settings&tab=plugins&blog='.$Blog->ID.'&plugin_group=widget';

			echo sprintf( T_('You must specify a valid Google Maps API key in the Plugins settings <a %s>Collection Settings</a> tab to use the plugin.'), 'href="'.$url.'"' );
			return;
		}

		if( ! $google_maps_initialized )
		{
			echo '<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key='.$api_key.'"></script>';
			$google_maps_initialized = true;
		}

		if( isset( $params['widget_context'] ) && $params['widget_context'] == 'item' )
		{ // Single item container

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

			$width = $this->display_param( $this->get_widget_setting( 'width', $params ) );
			$width = 'width:'.$width;

			$height = $this->display_param( $this->get_widget_setting( 'height_front', $params ) );
			$height = 'height:'.$height;

			$zoom = $this->get_widget_setting( 'map_zoom', $params );

			echo $this->get_widget_setting( 'block_start', $params );
			$map_title = $this->get_widget_setting( 'map_title', $params );
			if( ! empty( $map_title ) )
			{
				echo $this->get_widget_setting( 'block_title_start', $params );
				echo '<div class="map_title">'.$map_title.'</div>';
				echo $this->get_widget_setting( 'block_title_end', $params );
			}
			echo $this->get_widget_setting( 'block_body_start', $params );
			?>
			<div class="map_canvas" id="map_canvas<?php echo $this->number_of_widgets; ?>" style="<?php echo $width; ?>; <?php echo $height; ?>;"></div>
			<script type="text/javascript">
			<?php
			$map_type = (string) $this->get_widget_setting( 'map_type', $params );
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
				var latlng = new google.maps.LatLng( <?php echo $lat; ?>, <?php echo $lng;?> );
				var mapTypes = new Array();
				mapTypes.push( google.maps.MapTypeId.HYBRID );
				mapTypes.push( google.maps.MapTypeId.ROADMAP );
				mapTypes.push( google.maps.MapTypeId.SATELLITE );
				mapTypes.push( google.maps.MapTypeId.TERRAIN );

				var myOptions = {
						zoom: <?php echo $zoom; ?>,
						center: latlng,
						mapTypeId: mapTypeId,
						scrollwheel: false,
						mapTypeControlOptions:
							{
							style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
							mapTypeIds: mapTypes
							}
					};
				var map<?php echo $this->number_of_widgets; ?> = new google.maps.Map( document.getElementById( "map_canvas<?php echo $this->number_of_widgets; ?>" ),
						myOptions);
				var marker<?php echo $this->number_of_widgets; ?> = new google.maps.Marker( {
					position: latlng,
					map: map<?php echo $this->number_of_widgets; ?>,
					title:"Position"
					} );
				</script>
			<?php
			echo $this->get_widget_setting( 'block_body_end', $params );
			echo $this->get_widget_setting( 'block_end', $params );
		}
		else
		{ // Collection list widget
			global $map_functions_initialized;
			global $admin_url, $DB, $BlogCache, $ItemCache;
			global $disp;

			$this->number_of_widgets += 1;
			$blog_ID = $this->get_widget_setting( 'list_blog_ID', $params );
			if( empty( $blog_ID ) ) $blog_ID = NULL;

			// Create ItemList
			$limit = intval( $this->get_widget_setting( 'list_limit', $params ) );
			$order_by = 'post_'.$this->get_widget_setting( 'list_order_by', $params ).' '.$this->get_widget_setting( 'list_order_dir', $params );

			$SQL = new SQL();
			$SQL->SELECT( 'post_ID, cat_blog_ID,
					MAX(IF(iset_name = "latitude", iset_value, NULL)) AS latitude,
					MAX(IF(iset_name = "longitude", iset_value, NULL)) AS longitude' );
			$SQL->FROM( 'T_items__item' );
			$SQL->FROM_add( 'LEFT JOIN T_items__item_settings ON iset_item_ID = post_ID AND iset_name IN ( "latitude", "longitude" )' );
			$SQL->FROM_add( 'LEFT JOIN T_postcats ON postcat_post_ID = post_ID' );
			$SQL->FROM_add( 'LEFT JOIN T_categories ON cat_ID = postcat_cat_ID' );
			$SQL->WHERE( 'iset_value IS NOT NULL' );
			$SQL->WHERE_or( 'post_ctry_ID IS NOT NULL' );
			$SQL->WHERE_or( 'post_rgn_ID IS NOT NULL' );
			$SQL->WHERE_or( 'post_subrg_ID IS NOT NULL' );
			$SQL->WHERE_or( 'post_city_ID IS NOT NULL' );
			$SQL->WHERE_and( $Blog->get_sql_where_aggregate_coll_IDs( 'cat_blog_ID', $blog_ID ) );
			$SQL->GROUP_BY( 'post_ID' );
			$SQL->ORDER_BY( $order_by );
			$SQL->LIMIT( $limit );

			$items = $DB->get_results( $SQL->get(), ARRAY_A, 'Fetching posts with location information' );

			if( empty( $items ) )
			{ // Do not display if there are no locations to show
				return;
			}

			echo $this->get_widget_setting( 'list_block_start', $params );
			$map_title = $this->get_widget_setting( 'list_map_title', $params );
			if( ! empty( $map_title ) )
			{
				echo $this->get_widget_setting( 'list_block_title_start', $params );
				echo '<div class="map_title">'.$map_title.'</div>';
				echo $this->get_widget_setting( 'list_block_title_end', $params );
			}
			echo $this->get_widget_setting( 'list_block_body_start', $params );

			$map_attrs = array(
				'id' => 'map_view'.$this->number_of_widgets
			);

			// Initialize image attributes:
			$map_attrs['style'] = 'width:'.( empty( $this->get_widget_setting( 'list_map_width', $params ) ) ? 'auto' : format_to_output( $this->get_widget_setting( 'list_map_width', $params ), 'htmlattr' ) ).';';
			// Image height:
			$map_attrs['style'] .= 'height:'.( empty( $this->get_widget_setting( 'list_map_height', $params ) ) ? 'auto' : format_to_output( $this->get_widget_setting( 'list_map_height', $params ), 'htmlattr' ) ).';';
			// If no unit is specified in a size, consider the unit to be px:
			$map_attrs['style'] = preg_replace( '/(\d+);/', '$1px;', $map_attrs['style'] );

			// Print out div container tag:
			echo '<div'.get_field_attribs_as_string( $map_attrs ).'></div>';

			$map_options = array(
					'zoom' => 4,
					'mapTypeId' => $this->get_widget_setting( 'list_map_type', $params )
				);

			$marker_data = array();
			foreach( $items as $item )
			{
				$loop_Item = & $ItemCache->get_by_ID( $item['post_ID'] );

				$item_latitude = $loop_Item->get_setting( 'latitude' );
				$item_longitude = $loop_Item->get_setting( 'longitude' );

				$item_location = $loop_Item->location( '', '', ', ', false );

				if( ! empty( $item_latitude ) && ! empty( $item_longitude ) )
				{
					$marker_data[] = array(
						'title' => $loop_Item->title,
						'coordinates' => array( $item_longitude, $item_latitude ),
						'excerpt' => $loop_Item->get_excerpt(),
						'id' => $loop_Item->ID
					);
				}
				elseif( ! empty( $item_location ) )
				{
					$marker_data[] = array(
						'title' => $loop_Item->title,
						'location' => $item_location,
						'excerpt' => $loop_Item->get_excerpt(),
						'id' => $loop_Item->ID
					);
				}
			}

			if( ! $map_functions_initialized )
			{
			?>
			<script type="text/javascript">
				var maps = {};
				var markers = {};
				var unknownLocations = [];
				var nextLocation = 0;
				var delay = 100;
				var geocoder = new google.maps.Geocoder();
				var infoWindow = new google.maps.InfoWindow({
						content: 'Initial content'
					});
				var exceededQueryLimit = false;

				function addMarker( map, latLng, title, data )
				{
					var pinFillColor = '#F75850';

					var pinIcon = {
							path: 'M79.185,29.644 c0-16.11-13.04-29.172-29.137-29.224 V0.416 c-0.015,0-0.032,0.002-0.049,0.002   c-0.015,0-0.032-0.002-0.047-0.002V0.42C33.854,0.472,20.815,13.534,20.815,29.644c0,0-0.307,7.275,3.33,14.812   c2.71,5.622,6.149,9.38,9.68,14.492c5.444,7.88,8.03,12.018,10.744,19.646c1.928,5.413,3.778,11.801,5.429,20.99   c1.653-9.189,3.504-15.577,5.432-20.99c2.717-7.629,5.303-11.767,10.744-19.646c3.531-5.111,6.968-8.87,9.681-14.492   C79.491,36.919,79.185,29.644,79.185,29.644z M49.979,40.219c-5.971,0-10.809-4.839-10.809-10.811   c0-5.969,4.837-10.806,10.809-10.806c5.973,0,10.81,4.837,10.81,10.806C60.789,35.379,55.952,40.219,49.979,40.219z',
							anchor: new google.maps.Point(50, 98),
							scale: 0.4,
							fillColor: pinFillColor,
							fillOpacity: 1.0,
							strokeWeight: 1,
							strokeColor: '#424242',
						};

					var marker = new google.maps.Marker({
							map: map,
							position: latLng,
							title: title,
							extra: data,
							icon: pinIcon
						} );

					marker.addListener( 'click', function() {
							infoWindow.setContent( '<h4>' + this.title + '</h4>' + '<p>' + this.extra.excerpt + '</p>' );
							infoWindow.open( map, this );
						} );

					return marker;
				}

				function geocodeLocation( map, address, title, data )
				{
					if( ! exceededQueryLimit )
					{
						geocoder.geocode( { 'address': address }, function( results, status ) {
							if( status == google.maps.GeocoderStatus.OK ) {
								// erwin > We should cache the latitude and longitude of the location (preferably to the DB) to avoid excessive geocoding
								// But how do we update these values when any of the location fields, i.e., country, region, city, etc., are updated?
								var marker = addMarker( map, results[0].geometry.location, title, data );
								var bounds = map.getBounds();

								bounds.extend( marker.getPosition() );
								map.fitBounds( bounds );

								locateNext();
							}
							else if ( status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT )
							{
								exceededQueryLimit = true;
								console.error( 'Geocode has exceeded the query limit');
							}
							else
							{
								console.error( 'Geocode was not successful for the following reason: ' + status );
							}
						} );
					}
				}

				function locateNext()
				{
					if( ! exceededQueryLimit && ( nextLocation < unknownLocations.length ) )
					{
						var loc = unknownLocations[nextLocation];
						var extraData = { postId: loc.id, excerpt: loc.excerpt };
						// We need to add a delay to our geocoding
						setTimeout( geocodeLocation( maps[loc.mapId], loc.location, loc.title, extraData ), delay );
						nextLocation++;
					}
				}

				function initMap( mapId, mapOptions, markerData )
				{
					var bounds = new google.maps.LatLngBounds();
					var mapCenter = { lat: 48.8370875, lng: 2.2372931 };
					var map = new google.maps.Map( document.getElementById( 'map_view' + mapId ), mapOptions );

					maps[mapId] = map;

					if( markerData )
					{
						var markerCount = markerData.length;
						for( var i = 0; i < markerCount; i++ )
						{
							var extraData = { postId: markerData[i].id, excerpt: markerData[i].excerpt };
							if( markerData[i].coordinates )
							{
								var coords = markerData[i].coordinates;
								var latLng = new google.maps.LatLng( coords[1], coords[0] );
								var marker = addMarker( map, latLng, markerData[i].title, extraData );
								mapCenter = latLng;

								bounds.extend( marker.getPosition() );
							}
							else if( markerData[i].location )
							{
								markerData[i].mapId = mapId;
								unknownLocations.push( markerData[i] );
							}
						}

						if( markerCount > 1 )
						{
							map.fitBounds( bounds );
						}
						else
						{
							map.setCenter( mapCenter );
						}
					}
				}
			</script>
			<?php
				$map_functions_initialized = true;
			}

			?>
			<script type="text/javascript">
				var mapOptions<?php echo $this->number_of_widgets; ?> = <?php echo json_encode( $map_options ); ?>;
				var markerData<?php echo $this->number_of_widgets; ?> = <?php echo json_encode( $marker_data ); ?>;
				initMap( <?php echo $this->number_of_widgets; ?>, mapOptions<?php echo $this->number_of_widgets; ?>, markerData<?php echo $this->number_of_widgets; ?> );
			</script>
			<?php
			echo $this->get_widget_setting( 'block_body_end', $params );
			echo $this->get_widget_setting( 'block_end', $params );

			return true;
		}
	}


	/**
	 * Event handler: Called at the end of the skin's HTML BODY section.
	 *
	 * Use this to add any HTML snippet at the end of the generated page.
	 *
	 * @param array Associative array of parameters
	 */
	function SkinEndHtmlBody( & $params )
	{
		global $map_functions_initialized;

		if( $map_functions_initialized )
		{ // Page has listing content widget displayed
		?>
		<script type="text/javascript">
		if( typeof locateNext == 'function' )
		{
			if( unknownLocations.length )
			{ // There are locations with no coordinates, trigger geocoding for these:
				locateNext();
			}
		}
		</script>
		<?php
		}
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