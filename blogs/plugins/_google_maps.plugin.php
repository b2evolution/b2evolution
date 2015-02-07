<?php
/**
 * This file implements the Google Maps plugin plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _google_maps.plugin.php 8174 2015-02-06 03:26:12Z fplanque $
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
	var $version = '5.0.0';
	var $author = 'The b2evo Group';
	var $help_url = '';  // empty URL defaults to manual wiki

	/*
	 * These variables MAY be overriden.
	 */
	var $number_of_installs = 1;
	var $group = 'widget';
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
	 * Get definitions for widget editable params
	 *
	 * @see Plugin::get_widget_param_definitions()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		$r = array(
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
				'note' => ''),

			);
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
		global $Blog, $DB;

		// fp>vitaliy : make thhis title configurable per blog . default shoul dbe as below.
		$plugin_title = $this->Settings->get( 'map_title_coll'.$Blog->ID );
		$plugin_title = empty( $plugin_title ) ? T_( 'Google Maps plugin' ) : $plugin_title;
		$params['Form']->begin_fieldset( $plugin_title );

		if( !$Blog->get_setting( 'show_location_coordinates' ) )
		{
			echo T_('You must turn on the "Show location coordinates" setting in Blog settings Post Features tab so the Google Maps plugin can save its coordinates.');
			$params['Form']->end_fieldset();
			return;
		}

		$params['Form']->switch_layout( 'linespan' );

		$Item = $params['Item'];
		require_js( '#jqueryUI#' );

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
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
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
			geocoder.geocode( {'address': '<?php echo $search_location; ?>'}, function(results, status)
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
	 * @see Plugin::SkinBeginHtmlHead()
	 */
	function SkinBeginHtmlHead()
	{
		require_js( '#jquery#', 'blog' );
	}

	function SkinTag( $params )
	{
		global $Item;
		global $Blog;

		$this->number_of_widgets += 1;

		if( !$Blog->get_setting( 'show_location_coordinates' ) )
		{
			return;
		}

		if( !empty( $Item ) )
		{
			$lat = $Item->get_setting('latitude');
			$lng = $Item->get_setting('longitude');
			if (empty($lat) && empty($lng))
			{
				return;
			}
		}
		else
		{
			$lat = 0;
			$lng = 0;
		}
		$width = $this->display_param($this->get_widget_setting('width', $params));
		$width = 'width:'.$width;

		$height = $this->display_param($this->get_widget_setting('height_front', $params));
		$height = 'height:'.$height;

		?>
		<div class="map_title"><?php echo $this->get_widget_setting('map_title_coll'.$Blog->ID, $params); ?></div>
		<div class="map_canvas" id="map_canvas<?php echo $this->number_of_widgets; ?>" style="<?php echo $width; ?>; <?php echo $height; ?>; margin: 5px 5px 5px 5px;"></div>
		<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
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
	function BeforeUninstall()
	{
		$this->msg( T_('Google Maps plugin sucessfully un-installed.') );
		return true;
	}

}

?>