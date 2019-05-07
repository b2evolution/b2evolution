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
		close_text: 'x',
		width: '355px',
		height: '400px',
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
							'<div id="evo_helpdesk_widget__close">' + evo_helpdesk_widget.options.close_text + '</div>' +
						'</div>' +
						'<div id="evo_helpdesk_widget__body"></div>' +
					'</div>' +
				'</div>' );

			// Set size for window with content:
			jQuery( '#evo_helpdesk_widget__window' ).css( {
				width: evo_helpdesk_widget.options.width,
				height: evo_helpdesk_widget.options.height,
			} );

			// Initialize events:
			jQuery( '#evo_helpdesk_widget__sticker' ).click( evo_helpdesk_widget.show );
			jQuery( '#evo_helpdesk_widget__close' ).click( evo_helpdesk_widget.hide );

			// Submit a search form:
			jQuery( document ).on( 'submit', '#evo_helpdesk_widget__search_form', function()
			{
				var search_keyword = jQuery( '#evo_helpdesk_widget__search_form input' ).val();

				evo_helpdesk_widget.rest_api_request( 'collections/' + evo_helpdesk_widget.options.collection + '/search/' + search_keyword,
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
							r += ' <a href="' + search_item.permalink + '" target="_blank" title="Open post in new window">&gt;&gt;</a> ';
							r += '</li>';
						}
						r += '</ul>';
					}
					return r;
				} );

				// To prevent form default event:
				return false;
			} );

			// Clear the searched results:
			jQuery( document ).on( 'click', '#evo_helpdesk_widget__search_form button[type=button]', function()
			{
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

				// To prevent form default event:
				return false;
			} );
		} );
	},

	/**
	 * Show window and load content
	 */
	show: function()
	{
		jQuery( '#evo_helpdesk_widget__window' ).show();
		jQuery( '#evo_helpdesk_widget__sticker' ).hide();

		// Load content once:
		if( this.loaded !== true )
		{
			evo_helpdesk_widget.rest_api_request( 'collections/' + evo_helpdesk_widget.options.collection + '/posts',
			'#evo_helpdesk_widget__body',
			function( data )
			{	// Display the posts on success request:
				var r = '<form class="form-inline" id="evo_helpdesk_widget__search_form">' +
						'<input type="text"><button type="submit" class="evo_helpdesk_widget__button">Search</button> ' +
						'<button type="button" class="evo_helpdesk_widget__button">Clear</button>' +
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
	hide: function()
	{
		jQuery( '#evo_helpdesk_widget__window' ).hide();
		jQuery( '#evo_helpdesk_widget__sticker' ).show();
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

		evo_helpdesk_widget.rest_api_start_loading( content_selector )
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
	 * Set style during loading new REST API content
	 *
	 * @param string Object selector
	 */
	rest_api_start_loading: function( obj_selector )
	{
		jQuery( obj_selector ).addClass( 'evo_helpdesk_widget_loading' )
			.append( '<div class="evo_helpdesk_widget_loader">loading...</div>' );
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