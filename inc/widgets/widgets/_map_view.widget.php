<?php
/**
 * This file implements the Map Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * map_Widget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class map_view_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'map_view' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'map-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Map View');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * MAY be overriden by core widgets. Example: menu link widget.
	 */
	function get_short_desc()
	{
		$this->load_param_array();
		if( !empty($this->param_array['map_view'] ) )
		{
			return $this->param_array['map_view'];
		}
		else
		{
			return $this->get_name();
		}
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display a map showing post and/or user locations.');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 60,
					'defaultvalue' => T_('Location Map'),
				),
				'title_link' => array(
					'label' => T_('Link to blog'),
					'note' => T_('Link the block title to the blog?'),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'size_begin_line' => array(
					'type' => 'begin_line',
					'label' => T_('Map size'),
				),
					'width' => array(
						'label' => T_('Map width'),
						'note' => '',
						'defaultvalue' => '',
						'allow_empty' => true,
						'size' => 4,
						'hide_label' => true,
						'valid_pattern' => array(
								'pattern' => '~^(\d+(px|%)?)?$~i',
								'error'   => sprintf( T_('Invalid map size, it must be specified in px or %%.') ) ),
					),
					'size_separator' => array(
						'label' => ' x ',
						'type' => 'string',
					),
					'height' => array(
						'label' => T_('Map height'),
						'note' => '',
						'defaultvalue' => '350px',
						'allow_empty' => true,
						'size' => 4,
						'hide_label' => true,
						'valid_pattern' => array(
								'pattern' => '~^(\d+(px|%)?)?$~i',
								'error'   => sprintf( T_('Invalid map size, it must be specified in px or %%.') ) ),
					),
				'size_end_line' => array(
					'type' => 'end_line',
					'label' => T_('Leave blank for auto.'),
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
					'options' => get_available_sort_options(),
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
				),
				'api_key' => array(
					'label' => T_('API key'),
					'note' => sprintf( T_('Visit the <a %s>Google Maps API</a> documentation site for instructions on how to obtain an API key'),
						'href="https://developers.google.com/maps/documentation/javascript/get-api-key#get-an-api-key" target="_blank"' ),
					'defaultvalue' => '',
					'size' => 40,
				),
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $DB;
		global $BlogCache, $ItemCache, $Collection, $Blog;

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		$listBlog = ( $blog_ID ? $BlogCache->get_by_ID( $blog_ID, false ) : $Blog );

		if( empty( $listBlog ) )
		{
			echo $this->disp_params['block_start'];
			echo $this->disp_params['block_body_start'];
			echo T_('The requested Blog doesn\'t exist any more!');
			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];
			return;
		}

		// Create ItemList
		$limit = intval( $this->disp_params['limit'] );

		$ItemList = new ItemListLight( $listBlog, $listBlog->get_timestamp_min(), $listBlog->get_timestamp_max(), $limit, 'ItemCacheLight', $this->code.'_' );

		// Set additional debug info prefix for SQL queries to know what widget executes it:
		$ItemList->query_title_prefix = get_class( $this );

		$filters = array(
				'orderby' 	=> $this->disp_params['order_by'],
				'order'			=> $this->disp_params['order_dir'],
				'coll_IDs'	=> $this->disp_params['blog_ID']
			);

		$ItemList->set_filters( $filters, false ); // we don't want to memorize these params

		// Run the query
		$ItemList->query();

		if( ! $ItemList->result_num_rows )
		{ // Nothing to display
			return;
		}

		echo $this->disp_params['block_start'];
		$title = sprintf( ( $this->disp_params[ 'title_link' ] ? '<a href="'.$listBlog->gen_blogurl().'" rel="nofollow">%s</a>' : '%s' ), $this->disp_params[ 'title' ] );
		$this->disp_title( $title );
		echo $this->disp_params['block_body_start'];

		$map_attrs = array(
			'id' => 'map_view'
		);

		// Initialize image attributes:
		$map_attrs['style'] = 'width:'.( empty( $this->disp_params['width'] ) ? 'auto' : format_to_output( $this->disp_params['width'], 'htmlattr' ) ).';';
		// Image height:
		$map_attrs['style'] .= 'height:'.( empty( $this->disp_params['height'] ) ? 'auto' : format_to_output( $this->disp_params['height'], 'htmlattr' ) ).';';
		// If no unit is specified in a size, consider the unit to be px:
		$map_attrs['style'] = preg_replace( '/(\d+);/', '$1px;', $map_attrs['style'] );

		// Print out div container tag:
		echo '<div'.get_field_attribs_as_string( $map_attrs ).'></div>';

		$marker_data = array();
		while( $iterator_Item = & $ItemList->get_item() )
		{
			$loop_Item = & $ItemCache->get_by_ID( $iterator_Item->ID );

			$item_latitude = $loop_Item->get_setting( 'latitude' );
			$item_longitude = $loop_Item->get_setting( 'longitude' );

			$item_location = $loop_Item->location( '', '', ', ', false );

			if( ! empty( $item_latitude ) && ! empty( $item_longitude ) )
			{
				$marker_data[] = array(
					'title' => $iterator_Item->title,
					'coordinates' => array( $item_longitude, $item_latitude ),
					'excerpt' => $iterator_Item->get_excerpt(),
					'id' => $iterator_Item->ID
				);
			}
			elseif( ! empty( $item_location ) )
			{
				$marker_data[] = array(
					'title' => $iterator_Item->title,
					'location' => $item_location,
					'excerpt' => $iterator_Item->get_excerpt(),
					'id' => $iterator_Item->ID
				);
			}
		}
		?>
		<script>
			function initMap()
			{
				var bounds = new google.maps.LatLngBounds();
				var mapCenter = { lat: 48.8370875, lng: 2.2372931 };
        var map = new google.maps.Map( document.getElementById( 'map_view' ), {
          zoom: 4,
          center: mapCenter
        } );
				var infoWindow = new google.maps.InfoWindow({
					content: 'Initial content'
				});

				var markerData = <?php echo json_encode( $marker_data );?>;
				var markers = {};

				for ( var i = 0; i < markerData.length; i++ )
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

				if( markerData.length > 1 )
				{
					map.fitBounds( bounds );
				}
				else
				{
					map.setCenter( mapCenter );
				}

				function addMarker( map, latLng, title, extra )
				{
					var marker = new google.maps.Marker({
						"title": title,
						"position": latLng,
						"map": map,
						"extra": extra
					} );

					marker.addListener( 'click', function() {
						infoWindow.setContent( '<h4>' + this.title + '</h4>' + '<p>' + this.extra.excerpt + '</p>' );
						infoWindow.open( map, this );
					});

					markers[extra.postId] = marker;

					return marker;
				}

				function codeLocation( map, location, title, extra )
				{
					var geocoder = new google.maps.Geocoder();

					geocoder.geocode( { 'address': location }, function( results, status ) {
						if( status == 'OK' ) {
							// erwin > We should cache the latitude and longitude of the location (preferably to the DB) to avoid excessive geocoding
							// But how do we update these values when any of the location fields, i.e., country, region, city, etc., are updated?
							addMarker( map, results[0].geometry.location, title, extra );
						}
						else
						{
							console.error( 'Geocode was not successful for the following reason: ' + status );
						}
					} );
				}
			}
		</script>

		<script async defer
    	src="https://maps.googleapis.com/maps/api/js?key=<?php echo $this->disp_params['api_key'];?>&callback=initMap">
    </script>
		<?php
		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>