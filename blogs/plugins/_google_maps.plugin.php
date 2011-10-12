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
			'width' => array(
				'label' => 'Widget width(px)',
				'defaultvalue' => '',
				'note' => '100% width if left empty',
				),
			'height_front' => array(
				'label' => 'Map height on page (px)',
				'defaultvalue' => '300',
				'note' => '',
				'valid_range' => array( 'min'=>1)),
			'height_back' => array(
				'label' => 'Map height on edit post page (px)',
				'defaultvalue' => '300',
				'note' => '',
				'valid_range' => array( 'min'=>1)),
			'map_type' => array(
				'label' => T_( 'Map default view ' ),
				'type' => 'radio',
				'options' => array( array('map', T_( 'Map' )), array('satellite',T_( 'Satellite' ))),
				'defaultvalue' => 'map',
				'note' => ''
			)
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
	 * @see Plugin::AdminDisplayItemFormFieldset()
	 */
	function AdminDisplayItemFormFieldset( & $params )
	{
		global $Blog;

		$params['Form']->begin_fieldset( 'Google Maps plugin' );

		$field_name1 = strtolower($Blog->get_setting('custom_double3'));
		$field_name2 = strtolower($Blog->get_setting('custom_double4'));

		if ($field_name1 != 'latitude' || $field_name2 != 'longitude')
		{
		  echo T_('You must configure the following custom fields (double3 as Latitude, double4 as Longitude )
      so the Google Maps plugin can save its coordinates.');
		$params['Form']->end_fieldset();
		return;
		}


		$Item = $params['Item'];
		require_js( '#jqueryUI#');
		$params['Form']->hidden( 'google_map_zoom', '', array('id' => 'google_map_zoom'));
		$params['Form']->text_input( 'address', '', 50, 'Input adress', '', array('maxlength'=>500, 'id' =>'searchbox'));
		$params['Form']->button(array ('id' => 'locate_on_map', 'type' =>'button', 'value' => 'Locate on map') );

		$lat = $Item->get('double3');
		$lng = $Item->get('double4');

		$height = (int)$this->Settings->get('height_back');
		$height = 'height:'.$height.'px';


	?>
	<div id="map_canvas" style="width:100%; <?php echo $height; ?>; margin: 5px 5px 5px 5px;"></div>
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

	$map_type = (string)$this->Settings->get('map_type');
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
	var mapTypes = new Array();
	mapTypes.push(google.maps.MapTypeId.HYBRID);
	mapTypes.push(google.maps.MapTypeId.ROADMAP);
	mapTypes.push(google.maps.MapTypeId.SATELLITE);
	mapTypes.push(google.maps.MapTypeId.TERRAIN);


	var myOptions = {
		  zoom: 11,
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

//	var traffic = new google.maps.TrafficLayer();
//	traffic.setMap(map);

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
		jQuery('input[name=item_double3]').val(marker.getPosition().lat());



		jQuery('input[name=item_double4]').val(marker.getPosition().lng());

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
		jQuery('input[name=item_double3]').val(event.latLng.lat());
		jQuery('input[name=item_double4]').val(event.latLng.lng());

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
		jQuery('input[name=item_double3]').val(searchLoc.lat());
		jQuery('input[name=item_double4]').val(searchLoc.lng());
		jQuery('#google_map_zoom').val(map.getZoom());
	}

}

	jQuery("#searchbox").keypress(function(event){
		if (event.keyCode == 13)
		{
			locate();
			return false
		}

	});

	jQuery('#locate_on_map').click(locate);
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

		global $Blog;

		$field_name1 = strtolower($Blog->get_setting('custom_double3'));
		$field_name2 = strtolower($Blog->get_setting('custom_double4'));

		if ($field_name1 != 'latitude' || $field_name2 != 'longitude')
		{
			return;
		}

		$lat = $Item->get('double3');
		$lng = $Item->get('double4');
		if (empty($lat) && empty($lng))
		{
			return;
		}

		 $width = (int)$this->Settings->get('width');
		 if (empty($width))
		 {
			$width = 'width:100%';
		 }
		 else
		 {
			$width = 'width:'.$width.'px';
		 }

		 $height = (int)$this->Settings->get('height_front');
		 $height = 'height:'.$height.'px';


		?>
		<div id="map_canvas" style="<?php echo $width; ?>; <?php echo $height; ?>; margin: 5px 5px 5px 5px;"></div>
		<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
		<script type="text/javascript">
	<?php
	$map_type = (string)$this->Settings->get('map_type');
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
 * Revision 1.9  2011/10/12 12:43:34  efy-vitalij
 * fix bug
 *
 * Revision 1.8  2011/10/12 02:23:52  fplanque
 * minor
 *
 * Revision 1.7  2011/10/11 11:34:35  efy-vitalij
 * add gmaps plugin functional
 *
 * Revision 1.6  2011/10/10 11:39:52  efy-vitalij
 * add gmaps plugin functional
 *
 * Revision 1.5  2011/10/10 10:37:00  efy-vitalij
 * add gmaps plugin functional
 *
 * Revision 1.4  2011/10/07 15:08:57  efy-vitalij
 * change widget display zoom
 *
 * Revision 1.3  2011/10/07 14:35:36  efy-vitalij
 * remake google maps plugin
 *
 */
?>
