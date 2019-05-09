/**
 * jQuery b2evolution helpdesk widget
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 */

var evo_helpdesk_widget = {
	// Default options:
	defaults: {
		site_url: null, // Absolute site URL
		collection: null, // Collection urlname
		title: 'Help',
		width: '370px',
		height: '450px',
		default_tag: null, // If set, default page will be filtered by this tag
		default_slug: null, // If set, default page will show specific page
		results_per_page: 25,
		icon_sticker: '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M50,5C25.2,5,5,25.2,5,50s20.2,45,45,45s45-20.2,45-45S74.8,5,50,5z M50,7c11.5,0,21.9,4.5,29.7,11.9L67.3,31.3  c-4.5-4.2-10.6-6.8-17.3-6.8s-12.7,2.6-17.3,6.8L20.3,18.9C28.1,11.5,38.5,7,50,7z M73.4,50c0,12.9-10.5,23.4-23.4,23.4  S26.6,62.9,26.6,50S37.1,26.6,50,26.6S73.4,37.1,73.4,50z M7,50c0-11.5,4.5-21.9,11.9-29.7l12.4,12.4c-4.2,4.5-6.8,10.6-6.8,17.3  s2.6,12.7,6.8,17.3L18.9,79.7C11.5,71.9,7,61.5,7,50z M50,93c-11.5,0-21.9-4.5-29.7-11.9l12.4-12.4c4.5,4.2,10.6,6.8,17.3,6.8  s12.7-2.6,17.3-6.8l12.4,12.4C71.9,88.5,61.5,93,50,93z M81.1,79.7L68.7,67.3c4.2-4.5,6.8-10.6,6.8-17.3s-2.6-12.7-6.8-17.3  l12.4-12.4C88.5,28.1,93,38.5,93,50S88.5,71.9,81.1,79.7z"/></svg>',
		icon_search: '<svg viewBox="0 0 90 100" xmlns="http://www.w3.org/2000/svg"><path d="M93.667,93.66711a4.54882,4.54882,0,0,0,0-6.43475L70.94507,64.51221A36.9358,36.9358,0,1,0,64.51,70.94672L87.23218,93.66711a4.54887,4.54887,0,0,0,6.43481,0ZM41.87268,69.64435A27.77146,27.77146,0,1,1,69.64417,41.87286,27.80231,27.80231,0,0,1,41.87268,69.64435Z"/></svg>',
		icon_close: '<svg viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><line x1="1" y1="15" x2="15" y2="1"></line><line x1="1" y1="1" x2="15" y2="15"></line></svg>',
		icon_permalink: '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M73.7883228,16 L44.56401,45.2243128 C42.8484762,46.9398466 42.8459918,49.728257 44.5642987,51.4465639 C46.2791092,53.1613744 49.0684023,53.1650001 50.7865498,51.4468526 L80,22.2334024 L80,32.0031611 C80,34.2058797 81.790861,36 84,36 C86.2046438,36 88,34.2105543 88,32.0031611 L88,11.9968389 C88,10.8960049 87.5527117,9.89722307 86.8294627,9.17343595 C86.1051125,8.44841019 85.1063303,8 84.0031611,8 L63.9968389,8 C61.7941203,8 60,9.790861 60,12 C60,14.2046438 61.7894457,16 63.9968389,16 L73.7883228,16 L73.7883228,16 Z M88,56 L88,36.9851507 L88,78.0296986 C88,83.536144 84.0327876,88 79.1329365,88 L16.8670635,88 C11.9699196,88 8,83.5274312 8,78.0296986 L8,17.9703014 C8,12.463856 11.9672124,8 16.8670635,8 L59.5664682,8 L40,8 C42.209139,8 44,9.790861 44,12 C44,14.209139 42.209139,16 40,16 L18.2777939,16 C17.0052872,16 16,17.1947367 16,18.668519 L16,77.331481 C16,78.7786636 17.0198031,80 18.2777939,80 L77.7222061,80 C78.9947128,80 80,78.8052633 80,77.331481 L80,56 C80,53.790861 81.790861,52 84,52 C86.209139,52 88,53.790861 88,56 L88,56 Z"/></svg>',
	},

	/**
	 * Initialize widget
	 *
	 * @param Array Options
	 */
	init: function( options )
	{
		if( this.initialized === true )
		{	// Don't initliaze this widget twice:
			return;
		}
		this.initialized = true;

		// Get custom options:
		this.options = jQuery.extend( {}, this.defaults, options );

		if( this.options.site_url === null )
		{	// Widget cannot work without site URL:
			alert( 'Please define site URL for evo helpdesk widget!' );
			return;
		}

		if( this.options.collection === null )
		{	// Widget cannot work without collection urlname:
			alert( 'Please define collection for evo helpdesk widget!' );
			return;
		}

		jQuery( document ).ready( function()
		{
			// HTML template for sticker and content:
			jQuery( 'body' ).append( '<div id="evo_helpdesk_widget">' +
					'<div id="evo_helpdesk_widget__sticker">' + evo_helpdesk_widget.options.icon_sticker + '</div>' +
					'<div id="evo_helpdesk_widget__window">' +
						'<div id="evo_helpdesk_widget__header">' +
							'<div id="evo_helpdesk_widget__icon_search">' + evo_helpdesk_widget.options.icon_search + '</div>' +
							'<div>' + evo_helpdesk_widget.options.title + '</div>' +
							'<div id="evo_helpdesk_widget__icon_close">' + evo_helpdesk_widget.options.icon_close + '</div>' +
						'</div>' +
						'<div id="evo_helpdesk_widget__body">' +
							'<div id="evo_helpdesk_widget__results_list">' +
								'<form id="evo_helpdesk_widget__search_form">' +
									'<div>' +
										'<div><input type="text"></div>' +
										'<div><button type="submit" class="evo_helpdesk_widget__button">Search</button></div>' +
										'<div><button type="button" class="evo_helpdesk_widget__button">Clear</button></div>' +
									'</div>' +
								'</form>' +
								'<div id="evo_helpdesk_widget__posts_list"></div>' +
							'</div>' +
							'<div id="evo_helpdesk_widget__result_details"></div>' +
							'<button id="evo_helpdesk_widget__results_back" class="evo_helpdesk_widget__button">&laquo; Back</button>' +
						'</div>' +
					'</div>' +
				'</div>' );

			// Set size for window with content:
			jQuery( '#evo_helpdesk_widget__window' ).css( {
				width: evo_helpdesk_widget.options.width,
				height: evo_helpdesk_widget.options.height,
			} );

			/* Initialize events: */
			// Show helpdesk window:
			jQuery( '#evo_helpdesk_widget__sticker' ).click( evo_helpdesk_widget.show );
			// Back to search layout:
			jQuery( '#evo_helpdesk_widget__icon_search' ).click( evo_helpdesk_widget.switch_layout );
			// Hide helpdesk window:
			jQuery( '#evo_helpdesk_widget__icon_close' ).click( evo_helpdesk_widget.hide );
			// Submit a search form:
			jQuery( document ).on( 'submit', '#evo_helpdesk_widget__search_form', evo_helpdesk_widget.search );
			// Clear the searched results:
			jQuery( document ).on( 'click', '#evo_helpdesk_widget__search_form button[type=button]', evo_helpdesk_widget.reset );
			// Load the data of the selected post:
			jQuery( document ).on( 'click', '#evo_helpdesk_widget__posts_list a:not([target])', evo_helpdesk_widget.load_item );
			// Load the data of the selected post:
			jQuery( document ).on( 'click', '#evo_helpdesk_widget__results_back', evo_helpdesk_widget.switch_layout );
		} );
	},

	/**
	 * Show window and load content
	 */
	show: function( event )
	{
		jQuery( '#evo_helpdesk_widget__window' ).show();
		jQuery( '#evo_helpdesk_widget__sticker' ).hide();
		jQuery( '#evo_helpdesk_widget__search_form input' ).focus();

		if( evo_helpdesk_widget.shown === true )
		{	// Don't load content twice:
			return;
		}
		evo_helpdesk_widget.shown = true;

		if( evo_helpdesk_widget.options.default_slug !== null )
		{	// Load single Item by slug:
			evo_helpdesk_widget.load_item( evo_helpdesk_widget.options.collection, evo_helpdesk_widget.options.default_slug );
		}
		else
		{	// Load Items/Posts:
			evo_helpdesk_widget.load_items();
		}
	},

	/**
	 * Hide window with loaded content
	 */
	hide: function( event )
	{
		jQuery( '#evo_helpdesk_widget__window' ).hide();
		jQuery( '#evo_helpdesk_widget__sticker' ).show();
	},

	/**
	 * Search categories/posts/comments/tags by keyword
	 */
	search: function( event, keyword )
	{
		var is_event_call = ( typeof( event ) == 'object' );
		if( ! is_event_call || typeof( keyword ) == 'undefined' )
		{	// Set params when they are not defined depending on call mode:
			keyword = ( is_event_call ? jQuery( '#evo_helpdesk_widget__search_form input' ).val() : event );
		}

		evo_helpdesk_widget.switch_layout( 'results' );

		evo_helpdesk_widget.rest_api_request( 'collections/' + evo_helpdesk_widget.options.collection + '/search/' + keyword,
		'#evo_helpdesk_widget__posts_list',
		{
			per_page: evo_helpdesk_widget.options.results_per_page,
		},
		function( data )
		{	// Display the post data on success request:
			if( data.found === 0 || data.results.length === 0 )
			{	// empty search result
				var r = '<div class="evo_helpdesk_widget__text_danger">Sorry, we could not find anything matching your request, please try to broaden your search.</div>';
			}
			else
			{
				var r = '<ul>';
				for( var s in data.results )
				{
					var search_item = data.results[s];
					r += '<li>' + search_item.kind + ': ';
					if( search_item.kind == 'item' )
					{ // item: (Display this as link to load data)
						r += '<a href="#" data-id="' + search_item.id + '" data-urlname="' + evo_helpdesk_widget.options.collection + '">' + search_item.title + '</a>';
					}
					else
					{	// category, comment, tag:
						r += search_item.title;
					}
					r += ' <a href="' + search_item.permalink + '" target="_blank" title="Open in new tab">' +
							'<span class="evo_helpdesk_widget__icon_permalink">' + evo_helpdesk_widget.options.icon_permalink + '</span>' +
						'</a> ';
					r += '</li>';
				}
				r += '</ul>';
			}
			return r;
		} );

		// Prevent default event of the submitted form:
		if( is_event_call )
		{
			event.preventDefault();
		}
		return false;
	},

	/**
	 * Reset the searched results
	 */
	reset: function( event )
	{
		evo_helpdesk_widget.switch_layout( 'results' );

		jQuery( '#evo_helpdesk_widget__search_form input' ).val( '' );

		// Load Items/Posts:
		evo_helpdesk_widget.load_items();

		// Prevent default event of the pressed button:
		if( typeof( event ) == 'object' )
		{
			event.preventDefault();
		}
		return false;
	},

	/**
	 * Load Items/Posts
	 */
	load_items: function( collection, filter_tag )
	{
		if( typeof( collection ) == 'undefined' )
		{	// Set params when they are not defined:
			collection = evo_helpdesk_widget.options.collection;
		}

		var request_params = { per_page: evo_helpdesk_widget.options.results_per_page };

		if( typeof( filter_tag ) !== 'undefined' )
		{	// Filter by default tag:
			request_params.tag = filter_tag;
		}
		else if( evo_helpdesk_widget.options.default_tag !== null )
		{	// Filter by default tag:
			request_params.tag = evo_helpdesk_widget.options.default_tag;
		}

		evo_helpdesk_widget.rest_api_request( 'collections/' + collection + '/posts',
		'#evo_helpdesk_widget__posts_list',
		request_params,
		function( data )
		{	// Display the posts on success request:
			var r = '<ul>';
			for( var p in data.items )
			{
				var post = data.items[p];
				r += '<li><a href="#" data-id="' + post.id + '" data-urlname="' + collection + '">' + post.title + '</a></li>';
			}
			r += '</ul>';
			return r;
		} );

		// Flag to know we already loaded items once:
		evo_helpdesk_widget.loaded_items = true;
	},

	/**
	 * Load Item/Post
	 */
	load_item: function( event, collection, item_id_slug )
	{
		var is_event_call = ( typeof( event ) == 'object' );
		if( ! is_event_call || typeof( item_id_slug ) == 'undefined' )
		{	// Set params when they are not defined depending on call mode:
			item_id_slug = ( is_event_call ? jQuery( event.target ).data( 'id' ) : collection );
			collection = ( is_event_call ? jQuery( event.target ).data( 'urlname' ) : event );
		}

		evo_helpdesk_widget.switch_layout( 'item' );

		evo_helpdesk_widget.rest_api_request( 'collections/' + collection + '/items/' + item_id_slug,
		'#evo_helpdesk_widget__result_details',
		{
			content_params: {
				before_image:        '<figure class="evo_helpdesk_widget__image">',
				before_image_legend: '<figcaption class="evo_helpdesk_widget__image_caption">',
				after_image_legend:  '</figcaption>',
				after_image:         '</figure>',
				before_gallery:      '<div class="evo_helpdesk_widget__gallery">',
				after_gallery:       '</div>',
				gallery_cell_start:  '<div class="evo_helpdesk_widget__gallery_image">',
				gallery_cell_end:    '</div>',
			}
		},
		function( item )
		{	// Display the post data in third column on success request:

			// Item title:
			var item_content = '<h2><a href="' + item.URL + '" target="_blank" title="Open in new tab">' +
					item.title +
					' <span class="evo_helpdesk_widget__icon_permalink">' + evo_helpdesk_widget.options.icon_permalink + '</span>' +
				'</a></h2>';
			// Item content:
			item_content += item.content;

			return item_content;
		} );

		// Prevent default event of the clicked link:
		if( is_event_call )
		{
			event.preventDefault();
		}
		return false;
	},

	/**
	 * Switch layout between search results and result details
	 */
	switch_layout: function( event, layout )
	{
		var is_event_call = ( typeof( event ) == 'object' );
		if( ! is_event_call || typeof( layout ) == 'undefined' )
		{	// Set params when they are not defined depending on call mode:
			layout = ( is_event_call ? ( typeof( layout ) == 'undefined' ? 'results' : 'layout' ) : event );
		}

		// Show/Hide layouts:
		jQuery( '#evo_helpdesk_widget__results_list' ).toggle( layout == 'results' );
		jQuery( '#evo_helpdesk_widget__result_details, #evo_helpdesk_widget__results_back' ).toggle( layout == 'item' );
		// Additional actions:
		switch( layout )
		{
			case 'results':
				jQuery( '#evo_helpdesk_widget__search_form input' ).focus();
				if( ! evo_helpdesk_widget.loaded_items )
				{	// Load items when only single Item was loaded on initialization, e.g. by filter 'default_slug':
					evo_helpdesk_widget.load_items();
				}
				break;
			case 'item':
				jQuery( '#evo_helpdesk_widget__result_details' ).html( '' );
				break;
		}

		// Prevent default event of the clicked link:
		if( is_event_call )
		{
			event.preventDefault();
		}
		return false;
	},

	/**
	 * Execute REST API request
	 *
	 * @param string URL
	 * @param string jQuery selector for content block
	 * @param array|function Additional params for request OR Function on success request
	 * @param function Function on success request
	 * @param string Type method: 'GET', 'POST', 'DELETE', etc.
	 */
	rest_api_request: function ( request, content_selector, params_func, func_method, method )
	{
		var params = params_func;
		var func = func_method;
		if( typeof( params_func ) == 'function' )
		{	// This is a request without additional params:
			func = params_func;
			params = {};
			method = func_method;
		}

		if( typeof( method ) == 'undefined' )
		{	// Use GET method by default:
			method = 'GET';
		}

		var rest_api_url = evo_helpdesk_widget.options.site_url;
		if( rest_api_url.slice( -1 ) != '/' )
		{
			rest_api_url += '/';
		}
		rest_api_url += 'api/v1/';

		// Set style during loading new REST API content:
		jQuery( '#evo_helpdesk_widget__body' ).addClass( 'evo_helpdesk_widget_loading' )
			.append( '<div class="evo_helpdesk_widget_loader">loading...</div>' );

		// Request data by REST API:
		jQuery.ajax(
		{
			contentType: 'application/json; charset=utf-8',
			type: method,
			url: rest_api_url + request,
			data: params
		} )
		.then( function( data, textStatus, jqXHR )
		{	// Success:
			if( typeof( jqXHR.responseJSON ) == 'object' )
			{	// Call function only when we get correct JSON response:
				evo_helpdesk_widget.rest_api_end_loading( content_selector, eval( func )( data, textStatus, jqXHR ) );
			}
		},
		function( jqXHR )
		{	// Error:
			var msg = ( typeof( jqXHR.responseJSON ) == 'undefined' ? jqXHR.statusText : jqXHR.responseJSON );
			if( typeof( msg.code ) == 'undefined' )
			{	// Unknown non-json response:
				msg = '<div class="evo_helpdesk_widget__text_error">Unknown error: ' + msg + '</div>';
			}
			else
			{	// JSON error data accepted:
				switch( msg.data.status )
				{
					case 200:
						msg = '<div>' + msg.message + '</div>';
						break;
					default:
						msg = '<div class="evo_helpdesk_widget__text_error">' + msg.message + '</div>';
						break;
				}
			}
			evo_helpdesk_widget.rest_api_end_loading( content_selector, msg );
		} );
	},

	/**
	 * Remove style after loading new content
	 *
	 * @param string Content selector
	 * @param string New content
	 */
	rest_api_end_loading: function( content_selector, content )
	{
		jQuery( content_selector ).html( content );
		jQuery( '#evo_helpdesk_widget__body' ).removeClass( 'evo_helpdesk_widget_loading' )
			.find( '.evo_helpdesk_widget_loader' ).remove();
	}
}