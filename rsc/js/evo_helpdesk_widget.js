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
		sticker: '?',
		title: 'b2evolution widget',
		width: '370px',
		height: '450px',
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
					'<div id="evo_helpdesk_widget__sticker">' + evo_helpdesk_widget.options.sticker + '</div>' +
					'<div id="evo_helpdesk_widget__window">' +
						'<div id="evo_helpdesk_widget__header">' +
							evo_helpdesk_widget.options.title +
							'<svg id="evo_helpdesk_widget__close" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><line x1="1" y1="15" x2="15" y2="1"></line><line x1="1" y1="1" x2="15" y2="15"></line></svg>' +
						'</div>' +
						'<div id="evo_helpdesk_widget__body">' +
							'<div id="evo_helpdesk_widget__results_list"></div>' +
							'<div id="evo_helpdesk_widget__result_details"></div>' +
							'<button id="evo_helpdesk_widget__results_back" class="evo_helpdesk_widget__button">&lt;&lt; Back</button>' +
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
			// Hide helpdesk window:
			jQuery( '#evo_helpdesk_widget__close' ).click( evo_helpdesk_widget.hide );
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

		// Load content once:
		if( this.loaded !== true )
		{
			evo_helpdesk_widget.rest_api_request( 'collections/' + evo_helpdesk_widget.options.collection + '/posts',
			'#evo_helpdesk_widget__results_list',
			function( data )
			{	// Display the posts on success request:
				var r = '<form id="evo_helpdesk_widget__search_form">' +
						'<div>' +
							'<div><input type="text"></div>' +
							'<div><button type="submit" class="evo_helpdesk_widget__button">Search</button></div>' +
							'<div><button type="button" class="evo_helpdesk_widget__button">Clear</button></div>' +
						'</div>' +
					'</form>' +
					'<div id="evo_helpdesk_widget__posts_list">' +
						'<ul>';
				for( var p in data.items )
				{
					var post = data.items[p];
					r += '<li><a href="#" data-id="' + post.id + '" data-urlname="' + evo_helpdesk_widget.options.collection + '">' + post.title + '</a></li>';
				}
				r += '</ul>' +
					'</div>';
				return r;
			} );
		}
		this.loaded = true;
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
		function( data )
		{	// Display the post data on success request:
			if( data.found === 0 || data.results.length === 0 )
			{	// empty search result
				var r = '<div>Sorry, we could not find anything matching your request, please try to broaden your search.</div>';
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
					r += ' <a href="' + search_item.permalink + '" target="_blank" title="Open in new window">&gt;&gt;</a> ';
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

		evo_helpdesk_widget.rest_api_request( 'collections/' + evo_helpdesk_widget.options.collection + '/posts',
		'#evo_helpdesk_widget__posts_list',
		function( data )
		{	// Display the posts on success request:
			jQuery( '#evo_helpdesk_widget__search_form input' ).val( '' )
			var r = '<ul>';
			for( var p in data.items )
			{
				var post = data.items[p];
				r += '<li><a href="#" data-id="' + post.id + '" data-urlname="' + evo_helpdesk_widget.options.collection + '">' + post.title + '</a></li>';
			}
			r += '</ul>';
			return r;
		} );

		// Prevent default event of the pressed button:
		if( typeof( event ) == 'object' )
		{
			event.preventDefault();
		}
		return false;
	},

	/**
	 * Load Item/Post
	 */
	load_item: function( event, collection, item_id )
	{
		var is_event_call = ( typeof( event ) == 'object' );
		if( ! is_event_call || typeof( item_id ) == 'undefined' )
		{	// Set params when they are not defined depending on call mode:
			item_id = ( is_event_call ? jQuery( event.target ).data( 'id' ) : collection );
			collection = ( is_event_call ? jQuery( event.target ).data( 'urlname' ) : event );
		}

		evo_helpdesk_widget.switch_layout( 'item' );

		evo_helpdesk_widget.rest_api_request( 'collections/' + collection + '/items/' + item_id,
		'#evo_helpdesk_widget__result_details',
		function( item )
		{	// Display the post data in third column on success request:

			// Item title:
			var item_content = '<h2><a href="' + item.URL + '" target="_blank">' + item.title + '</a></h2>';
			// Item attachments, Only images and on teaser positions:
			if( typeof( item.attachments ) == 'object' && item.attachments.length > 0 )
			{
				item_content += '<div id="evo_helpdesk_widget__attachments">';
				for( var a in item.attachments )
				{
					var attachment = item.attachments[a];
					if( attachment.type == 'image' &&
							( attachment.position == 'teaser' ||
								attachment.position == 'teaserperm' ||
								attachment.position == 'teaserlink' )
						)
					{
						item_content += '<img src="' + attachment.url + '" />';
					}
				}
				item_content += '</div>';
			}
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

		jQuery( '#evo_helpdesk_widget__results_list' ).toggle( layout == 'results' );
		jQuery( '#evo_helpdesk_widget__result_details, #evo_helpdesk_widget__results_back' ).toggle( layout == 'item' );
		if( layout == 'item' )
		{
			jQuery( '#evo_helpdesk_widget__result_details' ).html( '' );
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
		jQuery( content_selector ).addClass( 'evo_helpdesk_widget_loading' )
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
		{
			if( typeof( jqXHR.responseJSON ) == 'object' )
			{	// Call function only when we get correct JSON response:
				evo_helpdesk_widget.rest_api_end_loading( content_selector, eval( func )( data, textStatus, jqXHR ) );
			}
		} );
	},

	/**
	 * Remove style after loading new content
	 *
	 * @param string Object selector
	 * @param string New content
	 */
	rest_api_end_loading: function( obj_selector, content )
	{
		jQuery( obj_selector ).removeClass( 'evo_helpdesk_widget_loading' )
			.html( content )
			.find( '.evo_helpdesk_widget_loader' ).remove();
	}
}