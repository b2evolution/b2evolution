<?php
/**
 * This file implements the Map View plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Map View Plugin
 *
 *
 *
 * @package plugins
 */
class mapview_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name = 'Map View';
	var $code = 'evo_mapview';
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
	var $number_of_widgets;


	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Map View plugin');
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
				'options' => array( array( 'roadmap', T_( 'Map' ) ), array( 'satellite', T_( 'Satellite' ) ) ),
				'defaultvalue' => 'roadmap',
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
				'defaultvalue' => T_('Location Map'),
				'note' => T_('Widget title'),
				'set_for_plugin' => true,
				),
			'map_width' => array(
				'label' => T_('Map width'),
				'defaultvalue' => '',
				'note' => T_('100% width if left empty'),
				),
			'map_height' => array(
				'label' => T_('Map height'),
				'defaultvalue' => '300px',
				'note' => '',
				),
			'map_type' => array(
				'label' => T_( 'Map default view ' ),
				'type' => 'radio',
				'options' => array( array('roadmap', T_( 'Map' ) ), array( 'satellite', T_( 'Satellite' ) ) ),
				'defaultvalue' => 'roadmap',
				'note' => ''
				),
			'blog_ID' => array(
				'label' => T_('Collections'),
				'note' => T_('List collection IDs separated by \',\', \'*\' for all collections, \'-\' for current collection without aggregation or leave empty for current collection including aggregation.'),
				'size' => 4,
				'type' => 'text',
				'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																	'error'   => T_('Invalid list of Collection IDs.') ),
				'defaultvalue' => '',
				),
			'order_by' => array(
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
			'order_dir' => array(
				'label' => T_('Direction'),
				'note' => T_('How to sort the items'),
				'type' => 'radio',
				'options' => array( array( 'ASC', T_('Ascending') ),
									array( 'DESC', T_('Descending') ) ),
				'defaultvalue' => 'DESC',
				),
			'limit' => array(
				'label' => T_( 'Max items' ),
				'note' => T_( 'Maximum number of items to display.' ),
				'size' => 4,
				'defaultvalue' => 10,
				)
			), parent::get_widget_param_definitions( $params ) );

		if( $preview && isset( $r['allow_blockcache'] ) )
		{	// Disable block caching for this widget when item is previewed currently:
			$r['allow_blockcache']['defaultvalue'] = false;
		}

		return $r;
	}


	function SkinTag( & $params )
	{
		global $map_view_initialized;
		global $admin_url, $DB, $BlogCache, $ItemCache, $Collection, $Blog;
		global $Item, $disp;

		/**
		 * Default params:
		 */
		$params = array_merge( array(
				// This is what will enclose the block in the skin:
				'block_start'       => '<div>',
				'block_end'         => "</div>\n",
				// This is what will enclose the title:
				'block_title_start' => '<h3>',
				'block_title_end'   => '</h3>',
				// This is what will enclose the body:
				'block_body_start'  => '',
				'block_body_end'    => '',
			), $params );

		$api_key = $this->get_coll_setting( 'api_key', $Blog );
		if( empty( $api_key ) )
		{
			$url = $admin_url.'?ctrl=coll_settings&tab=plugins&blog='.$Blog->ID.'&plugin_group=widget';

			echo sprintf( T_('You must specify a valid Google Maps API key in the Plugins settings <a %s>Collection Settings</a> tab to use the plugin.'), 'href="'.$url.'"' );
			return;
		}

		$this->number_of_widgets += 1;
		$blog_ID = $this->get_widget_setting( 'blog_ID', $params );
		if( empty( $blog_ID ) )
		{
			$collection_condition = 'cat_blog_ID = '.$Blog->ID;
		}
		elseif( $blog_ID == '*' )
		{
			$collection_condition = NULL;
		}
		else
		{
			$blog_IDs = array_map( 'intval', explode( ',', $this->get_widget_setting( 'blog_ID', $params ) ) );
			if( count( $blog_IDs ) === 1 )
			{
				$collection_condition = 'cat_blog_ID = '.$blog_IDs[0];
			}
			elseif( count( $blog_IDs ) > 1 )
			{
				$collection_condition = 'cat_blog_ID IN ('.implode( ',', $blog_IDs ).')';
			}
		}

		// Create ItemList
		$limit = intval( $this->get_widget_setting( 'limit', $params ) );
		$order_by = 'post_'.$this->get_widget_setting( 'order_by', $params ).' '.$this->get_widget_setting( 'order_dir', $params );

		$SQL = new SQL();
		$SQL->SELECT( 'post_ID, cat_blog_ID,
				MAX(IF(iset_name = "latitude", iset_value, NULL)) AS latitude,
				MAX(IF(iset_name = "longitude", iset_value, NULL)) AS longitude' );
		$SQL->FROM( 'T_items__item' );
		$SQL->FROM_add( 'LEFT JOIN T_items__item_settings ON iset_item_ID = post_ID AND iset_name IN ( "latitude", "longitude" )' );
		$SQL->FROM_add( 'LEFT JOIN T_postcats ON postcat_post_ID = post_ID' );
		$SQL->FROM_add( 'LEFT JOIN T_categories ON cat_ID = postcat_cat_ID' );
		$SQL->WHERE( '( iset_value IS NOT NULL OR CONCAT(post_ctry_ID, post_rgn_ID, post_subrg_ID, post_city_ID) <> " " )' );
		if( ! empty( $collection_condition ) )
		{
			$SQL->WHERE_and( $collection_condition );
		}
		$SQL->GROUP_BY( 'post_ID' );
		$SQL->ORDER_BY( $order_by );
		$SQL->LIMIT( $limit );

		$items = $DB->get_results( $SQL->get(), ARRAY_A, 'Fetching posts with location information' );

		if( empty( $items ) )
		{ // Do not display if there are no locations to show
			return;
		}

		echo $this->get_widget_setting( 'block_start', $params );
		echo $this->get_widget_setting( 'block_title_start', $params );
		$title = $this->get_widget_setting( 'map_title', $params );
		echo '<div class="map_title">'.$title.'</div>';
		echo $this->get_widget_setting( 'block_title_end', $params );
		echo $this->get_widget_setting( 'block_body_start', $params );

		$map_attrs = array(
			'id' => 'map_view'.$this->number_of_widgets
		);

		// Initialize image attributes:
		$map_attrs['style'] = 'width:'.( empty( $this->get_widget_setting( 'map_width', $params ) ) ? 'auto' : format_to_output( $this->get_widget_setting( 'map_width', $params ), 'htmlattr' ) ).';';
		// Image height:
		$map_attrs['style'] .= 'height:'.( empty( $this->get_widget_setting( 'map_height', $params ) ) ? 'auto' : format_to_output( $this->get_widget_setting( 'map_height', $params ), 'htmlattr' ) ).';';
		// If no unit is specified in a size, consider the unit to be px:
		$map_attrs['style'] = preg_replace( '/(\d+);/', '$1px;', $map_attrs['style'] );

		// Print out div container tag:
		echo '<div'.get_field_attribs_as_string( $map_attrs ).'></div>';

		$map_options = array(
				'zoom' => 4,
				'mapTypeId' => $this->get_widget_setting( 'map_type', $params )
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

		if( ! $map_view_initialized )
		{
		?>
		<script	src="https://maps.googleapis.com/maps/api/js?key=<?php echo $api_key; ?>"></script>
		<script type="text/javascript">
			var maps = {};
			var markers = {};
			var infoWindow = new google.maps.InfoWindow({
					content: 'Initial content'
				});

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

			function codeLocation( map, address, title, data )
			{
				var geocoder = new google.maps.Geocoder();

				geocoder.geocode( { 'address': address }, function( results, status ) {
					if( status == 'OK' ) {
						// erwin > We should cache the latitude and longitude of the location (preferably to the DB) to avoid excessive geocoding
						// But how do we update these values when any of the location fields, i.e., country, region, city, etc., are updated?
						var marker = addMarker( map, results[0].geometry.location, title, data );
						var bounds = map.getBounds();

						bounds.extend( marker.getPosition() );
						map.fitBounds( bounds );
					}
					else
					{
						console.error( 'Geocode was not successful for the following reason: ' + status );
					}
				} );
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
							codeLocation( map, markerData[i].location, markerData[i].title, extraData );
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
			$map_view_initialized = true;
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










	/**
	 * Event handler: Called when the plugin has been installed.
	 * @see Plugin::AfterInstall()
	 */
	function AfterInstall()
	{
		$this->msg( T_('Google Maps View plugin sucessfully installed.') );
	}


	/**
	 * Event handler: Called before the plugin is going to be un-installed.
	 * @see Plugin::BeforeUninstall()
	 */
	function BeforeUninstall( & $params )
	{
		$this->msg( T_('Google Maps View plugin sucessfully un-installed.') );
		return true;
	}

}

?>