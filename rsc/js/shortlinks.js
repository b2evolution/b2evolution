/**
 * Load main modal window to select collection and posts
 */
function shortlinks_load_window( mode, prefix )
{
	var window_title;

	switch( mode )
	{
		case 'move_comment_to_post':
			window_title = shortlinks_title_move_comment_to_post;
			shortlinks_action_buttons = '<button id="shortlinks_btn_move_comment" class="btn btn-primary">' + shortlinks_move_to_post + '</button>';
			break;

		case 'shortlinks':
			window_title = shortlinks_title_link_to_post;
			shortlinks_action_buttons = '<button id="shortlinks_btn_insert" class="btn btn-primary">' + shortlinks_insert_short_link + '</button>'
					+ '<button id="shortlinks_btn_options" class="btn btn-default">' + shortlinks_insert_with_options + '...</button>'
					+ '<button id="shortlinks_btn_form" class="btn btn-info">' + shortlinks_insert_snippet_link + '...</button>';
			break;
	}

  openModalWindow( '<div id="shortlinks_wrapper"></div>', 'auto', '', true,
    window_title, // Window title
    [ '-', 'shortlinks_post_buttons' ], // Fake button that is hidden by default, Used to build buttons "Back" and "Insert [[post-url-name]]"
    true );

	// Load collections:
  shortlinks_load_colls( shortlinks_coll_urlname, prefix );

  // Set max-height to keep the action buttons on screen:
  var modal_window = jQuery( '#shortlinks_wrapper' ).parent();
  var modal_height = jQuery( window ).height() - 20;
  if( modal_window.hasClass( 'modal-body' ) )
  {	// Extract heights of header and footer:
    modal_height -= 55 + 64 +
      parseInt( modal_window.css( 'padding-top' ) ) + parseInt( modal_window.css( 'padding-bottom' ) );
  }
  modal_window.css( {
    'display': 'block',
    'overflow': 'auto',
    'max-height': modal_height
  } );

  // To prevent link default event:
  return false;
}

/**
 * Get an error of fail request
 *
 * @param string Object selector
 * @param object Error data: 'message', 'code', 'data.status'
 */
function shortlinks_api_print_error( obj_selector, error, debug )
{
	if( typeof( error ) != 'string' && typeof( error.code ) == 'undefined' )
	{
		error = typeof( error.responseJSON ) == 'undefined' ? error.statusText : error.responseJSON;
	}

	if( typeof( error.code ) == 'undefined' )
	{	// Unknown non-json response:
		var error_text = '<h4 class="text-danger">Unknown error: ' + error + '</h4>';
	}
	else
	{	// JSON error data accepted:
		var error_text ='<h4 class="text-danger">' + error.message + '</h4>';
		if( debug )
		{ // Display additional error info in debug mode only:
			error_text += '<div><b>Code:</b> ' + error.code + '</div>'
					+ '<div><b>Status:</b> ' + error.data.status + '</div>';
		}
	}

	shortlinks_end_loading( obj_selector, error_text );
}

/**
 * Execute REST API request
 *
 * @param string REST API path
 * @param string Object selector
 * @param function Function on success request
 * @param array Additional params
 */
function shortlinks_api_request( api_path, obj_selector, func, params )
{
	shortlinks_start_loading( obj_selector );

	if( typeof( params ) == 'undefined' )
	{
		params = {};
	}

	jQuery.ajax(
	{
		url: restapi_url + api_path,
		data: params
	} )
	.then( func, function( jqXHR )
	{	// Error request, Display the error data:
		shortlinks_api_print_error( obj_selector, jqXHR );
	} );
}

/**
 * Set style during loading new content
 *
 * @param string Object selector
 */
function shortlinks_start_loading( obj_selector )
{
	jQuery( obj_selector ).addClass( 'shortlinks_loading' )
		.append( '<div class="shortlinks_loader">loading...</div>' );
}

/**
 * Remove style after loading new content
 *
 * @param string Object selector
 * @param string New content
 */
function shortlinks_end_loading( obj_selector, content )
{
	jQuery( obj_selector ).removeClass( 'shortlinks_loading' )
		.html( content )
		.find( '.shortlinks_loader' ).remove();
}

/**
 * Build a pagination from response data
 *
 * @param array Response data
 * @param string Search keyword
 * @return string Pagination
 */
function shortlinks_get_pagination( data, search_keyword )
{
	var r = '';

	if( typeof( data.pages_total ) == 'undefined' || data.pages_total < 2 )
	{	// No page for this request:
		return r;
	}

	var search_keyword_attr = typeof( search_keyword ) == 'undefined' ? '' :
		' data-search="' + search_keyword.replace( '"', '\"' ) + '"';

	var current_page = data.page;
	var total_pages = data.pages_total;
	var page_list_span = 11; // Number of visible pages on navigation line
	var page_list_start, page_list_end;

	// Initialize a start of pages list:
	if( current_page <= parseInt( page_list_span / 2 ) )
	{	// the current page number is small
		page_list_start = 1;
	}
	else if( current_page > total_pages - parseInt( page_list_span / 2 ) )
	{	// the current page number is big
		page_list_start = Math.max( 1, total_pages - page_list_span + 1 );
	}
	else
	{	// the current page number can be centered
		page_list_start = current_page - parseInt( page_list_span / 2 );
	}

	// Initialize an end of pages list:
	if( current_page > total_pages - parseInt( page_list_span / 2 ) )
	{ //the current page number is big
		page_list_end = total_pages;
	}
	else
	{
		page_list_end = Math.min( total_pages, page_list_start + page_list_span - 1 );
	}

	r += '<ul class="shortlinks_pagination pagination"' + search_keyword_attr + '>';

	if( current_page > 1 )
	{	// A link to previous page:
		r += '<li><a href="#" data-page="' + ( current_page - 1 ) + '">&lt;&lt;</a></li>';
	}

	if( page_list_start > 1 )
	{ // The pages list doesn't contain the first page
		// Display a link to first page:
		r += '<li><a href="#" data-page="1">1</a></li>';

		if( page_list_start > 2 )
		{ // Display a link to previous pages range:
			r += '<li><a href="#" data-page="' + Math.ceil( page_list_start / 2 ) + '">...</a></li>';
		}
	}

	for( p = page_list_start; p <= page_list_end; p++ )
	{
		if( current_page == p )
		{	// Current page:
			r += '<li class="active"><span>' + p + '</span></li>';
		}
		else
		{
			r += '<li><a href="#" data-page="' + p + '">' + p + '</a></li>';
		}
	}

	if( page_list_end < total_pages )
	{	// The pages list doesn't contain the last page
		if( page_list_end < total_pages - 1 )
		{	// Display a link to next pages range:
			r += '<li><a href="#" data-page="' + ( page_list_end + Math.floor( ( total_pages - page_list_end ) / 2 ) ) + '">...</a></li>';
		}

		// Display a link to last page:
		r += '<li><a href="#" data-page="' + total_pages + '">' + total_pages + '</a></li>';
	}

	if( current_page < total_pages )
	{	// A link to next page:
		r += '<li><a href="#" data-page="' + ( current_page + 1 ) + '">&gt;&gt;</a></li>';
	}

	r += '</ul>';

	return r;
}


/**
 * Load all available collections for current user:
 *
 * @param string Current collection urlname
 * @param string Prefix to use several toolbars on one page
 */
function shortlinks_load_colls( current_coll_urlname, prefix )
{
	shortlinks_api_request( 'collections', '#shortlinks_wrapper', function( data )
	{	// Display the colllections on success request:
		var coll_urlname = '';
		var coll_name = '';

		// Initialize html code to view the loaded collections:
		var r = '<div id="shortlinks_colls_list">'
			+ '<h2>' + shortlinks_collections + '</h2>'
			+ '<select class="form-control" id="shortlinks_collections">';
		for( var c in data.colls )
		{
			var coll = data.colls[c];
			r += '<option value="' + coll.urlname + '" data-coll-id="' + coll.id + '" '
				+ ( current_coll_urlname == coll.urlname ? ' selected="selected"' : '' )+ '>'
				+ coll.shortname + ' : ' + coll.name + '</option>';
			if( coll_urlname == '' || coll.urlname == current_coll_urlname )
			{	// Set these vars to load posts of the selected or first collection:
				coll_urlname = coll.urlname;
				coll_name = coll.name;
			}
		}
		r += '</select>'
			+ '</div>'
			+ '<div id="shortlinks_posts_block"></div>'
			+ '<div id="shortlinks_post_block"></div>'
			+ '<input type="hidden" id="shortlinks_hidden_prefix" value="' + ( prefix ? prefix : '' ) + '" />'
			+ '<div id="shortlinks_post_form" style="display:none">'
				+ '<input type="hidden" id="shortlinks_hidden_ID" />'
				+ '<input type="hidden" id="shortlinks_hidden_cover_link" />'
				+ '<input type="hidden" id="shortlinks_hidden_teaser_link" />'
				+ '<input type="hidden" id="shortlinks_hidden_urltitle" />'
				+ '<input type="hidden" id="shortlinks_hidden_title" />'
				+ '<input type="hidden" id="shortlinks_hidden_excerpt" />'
				+ '<input type="hidden" id="shortlinks_hidden_teaser" />'
				+ '<p><label><input type="checkbox" id="shortlinks_form_full_cover" /> ' + shortlinks_insert_full_cover_image + '</label><p>'
				+ '<p><label><input type="checkbox" id="shortlinks_form_title" checked="checked" /> ' + shortlinks_insert_title + '</label><p>'
				+ '<p><label><input type="checkbox" id="shortlinks_form_thumb_cover" checked="checked" /> ' + shortlinks_insert_thumbnail_of_cover + '</label><p>'
				+ '<p><label><input type="checkbox" id="shortlinks_form_excerpt" checked="checked" /> ' + shortlinks_insert_excerpt + '</label><p>'
				+ '<p><label><input type="checkbox" id="shortlinks_form_teaser" /> ' + shortlinks_insert_teaser + '</label><p>'
				+ '<p><label><input type="checkbox" id="shortlinks_form_more" checked="checked" /> ' + shortlinks_insert_read_more_link + '</label><p>'
			+ '</div>'
			+ '<div id="shortlinks_post_options" class="form-horizontal" style="display:none">'
				+ '<div class="form-group"><label class="control-label col-sm-2">' + shortlinks_slug + ':</label><div class="controls col-sm-10"><input type="text" id="shortlinks_opt_slug" class="form-control" style="width:100%" /></div></div>'
				+ '<div class="form-group"><label class="control-label col-sm-2">' + shortlinks_mode + ':</label><div class="controls col-sm-10">'
					+ '<div class="radio"><label><input type="radio" name="shortlinks_opt_mode" id="shortlinks_opt_mode_title" checked="checked"><code>[[...]]</code> ' + shortlinks_use_title + '</label></div>'
					+ '<div class="radio"><label><input type="radio" name="shortlinks_opt_mode" id="shortlinks_opt_mode_slug"><code>((...))</code> ' + shortlinks_use_slug_words + '</label></div>'
				+ '</div></div>'
				+ '<div class="form-group"><label class="control-label col-sm-2">' + shortlinks_classes + ':</label><div class="controls col-sm-10"><input type="text" id="shortlinks_opt_classes" class="form-control" style="width:100%" /></div></div>'
				+ '<div class="form-group"><label class="control-label col-sm-2">' + shortlinks_target + ':</label><div class="controls col-sm-10">'
					+ '<select id="shortlinks_opt_target" class="form-control">'
						+ '<option value="">' + shortlinks_none + '</option>'
						+ '<option value="_blank">' + shortlinks_blank + '</option>'
						+ '<option value="_parent">' + shortlinks_parent + '</option>'
						+ '<option value="_top">' + shortlinks_top + '</option>'
					+ '</select>'
				+ '</div></div>'
				+ '<div class="form-group"><label class="control-label col-sm-2">' + shortlinks_text + ':</label><div class="controls col-sm-10"><input type="text" id="shortlinks_opt_text" class="form-control" style="width:100%" /></div></div>'
			+ '</div>';

		shortlinks_end_loading( '#shortlinks_wrapper', r );

		if( coll_urlname != '' )
		{	// Load posts list of the current or first collection:
			shortlinks_load_coll_posts( coll_urlname, coll_name );
		}
	}, { list_in_frontoffice: 'all', per_page: -1 } );
}

/**
 * Load posts list with search form of the collection:
 *
 * @param string Collection urlname
 * @param string Collection name
 * @param string Predefined Search keyword
 */
function shortlinks_display_search_form( coll_urlname, coll_name, search_keyword )
{
	var r = '<h2>' + coll_name + '</h2>' +
		'<form class="form-inline" id="shortlinks_search__form" data-urlname="' + coll_urlname + '">' +
			'<div class="input-group">' +
				'<input type="text" id="shortlinks_search__input" class="form-control" value="' + ( typeof( search_keyword ) == 'undefined' ? '' : search_keyword ) + '">' +
				'<span class="input-group-btn"><button id="shortlinks_search__submit" class="btn btn-primary">' + shortlinks_search + '</button></span>' +
			'</div> ' +
			'<button id="shortlinks_search__clear" class="btn btn-default">' + shortlinks_clear + '</button>' +
		'</form>' +
		'<div id="shortlinks_posts_list"></div>';

	jQuery( '#shortlinks_posts_block' ).html( r );
}

/**
 * Load posts list with search form of the collection:
 *
 * @param string Collection urlname
 * @param string Collection name
 * @param integer Page
 */
function shortlinks_load_coll_posts( coll_urlname, coll_name, page )
{
	if( typeof( coll_name ) != 'undefined' && coll_name !== false )
	{
		shortlinks_display_search_form( coll_urlname, coll_name );
	}

	var page_param = ( typeof( page ) == 'undefined' || page < 2 ) ? '' : '&page=' + page;

	shortlinks_api_request( 'collections/' + coll_urlname + '/items&orderby=datemodified&order=DESC' + page_param, '#shortlinks_posts_list', function( data )
	{	// Display the posts on success request:
		var r = '<ul>';
		for( var p in data.items )
		{
			var post = data.items[p];
			r += '<li><a href="#" data-id="' + post.id + '" data-urlname="' + coll_urlname + '">' + post.title + '</a></li>';
		}
		r += '</ul>';
		r += shortlinks_get_pagination( data );
		shortlinks_end_loading( '#shortlinks_posts_list', r );
	} );
}

/**
 * Load the searched posts list:
 *
 * @param string Collection urlname
 * @param string Search keyword
 * @param integer Page
 */
function shortlinks_load_coll_search( coll_urlname, search_keyword, page )
{
	var page_param = ( typeof( page ) == 'undefined' || page < 2 ) ? '' : '&page=' + page;

	shortlinks_api_request( 'collections/' + coll_urlname + '/search/' + search_keyword + '&kind=item' + page_param, '#shortlinks_posts_list', function( data )
	{	// Display the post data in third column on success request:
		if( typeof( data.code ) != 'undefined' )
		{	// Error code was responsed:
			shortlinks_api_print_error( '#shortlinks_posts_list', data );
			return;
		}

		var r = '<ul>';
		for( var s in data.results )
		{
			var search_item = data.results[s];
			if( search_item.kind != 'item' )
			{	// Dsiplay only items and skip all other:
				continue;
			}
			r += '<li>';
			//r += '<a href="' + search_item.permalink + '" target="_blank"><?php echo get_icon( 'permalink' ); ?></a> ';
			r += '<a href="#" data-id="' + search_item.id + '" data-urlname="' + coll_urlname + '">' + search_item.title + '</a>';
			r += '</li>';
		}
		r += '</ul>';
		r += shortlinks_get_pagination( data, search_keyword );
		shortlinks_end_loading( '#shortlinks_posts_list', r );
	} );
}

if( typeof( b2evo_shortlinks_initialized ) == 'undefined' )
{	// Initialize the code below only once:

	b2evo_shortlinks_initialized = true;

	// Load the posts of the selected collection:
	jQuery( document ).on( 'change', '#shortlinks_colls_list select', function()
	{
		shortlinks_load_coll_posts( jQuery( this ).val(), jQuery( 'option:selected', this ).text() );

		// To prevent link default event:
		return false;
	} );

	// Submit a search form:
	jQuery( document ).on( 'submit', '#shortlinks_search__form', function()
	{
		var coll_urlname = jQuery( this ).data( 'urlname' );
		var search_keyword = jQuery( '#shortlinks_search__input' ).val();

		shortlinks_load_coll_search( coll_urlname, search_keyword );

		// To prevent link default event:
		return false;
	} );

	// Clear the search results:
	jQuery( document ).on( 'click', '#shortlinks_search__clear', function()
	{
		shortlinks_load_coll_posts( jQuery( this ).closest( 'form' ).data( 'urlname' ) );

		// Clear search input field:
		jQuery( '#shortlinks_search__input' ).val( '' );

		// To prevent link default event:
		return false;
	} );

	// Load the data of the selected post:
	jQuery( document ).on( 'click', '#shortlinks_posts_list a[data-id]', function()
	{
		var coll_urlname = jQuery( this ).data( 'urlname' );
		var post_id = jQuery( this ).data( 'id' );

		// Hide the lists of collectionss and posts:
		jQuery( '#shortlinks_colls_list, #shortlinks_posts_block' ).hide();

		// Show the post preview block, because it can be hidded after prevous preview:
		jQuery( '#shortlinks_post_block' ).show();

		if( jQuery( '#shortlinks_post_block' ).data( 'post' ) == post_id )
		{	// If user loads the same post, just display the cached content to save ajax calls:
			// Show the action buttons:
			jQuery( '#shortlinks_btn_back_to_list, #shortlinks_btn_insert' ).show();
		}
		else
		{	// Load new post:
			jQuery( '#shortlinks_post_block' ).html( '' ); // Clear previous cached content
			shortlinks_api_request( 'collections/' + coll_urlname + '/items/' + post_id, '#shortlinks_post_block', function( post )
			{	// Display the post data on success request:
				jQuery( '#shortlinks_post_block' ).data( 'post', post.id );

				// Store item field values in hidden inputs to use on insert complex link:
				jQuery( '#shortlinks_hidden_ID' ).val( post.id );
				jQuery( '#shortlinks_hidden_urltitle' ).val( post.urltitle );
				jQuery( '#shortlinks_hidden_title' ).val( post.title );
				jQuery( '#shortlinks_hidden_excerpt' ).val( post.excerpt );
				jQuery( '#shortlinks_hidden_teaser' ).val( post.teaser );
				jQuery( '#shortlinks_hidden_cover_link' ).val( '' );
				jQuery( '#shortlinks_hidden_teaser_link' ).val( '' );
				jQuery( '#shortlinks_hidden_coll_ID' ).val(  );

				// Item title:
				var item_content = '<h2>' + post.title + '</h2>';
				// Item attachments, Only images:
				if( typeof( post.attachments ) == 'object' && post.attachments.length > 0 )
				{
					for( var a in post.attachments )
					{
						var attachment = post.attachments[a];
						if( attachment.type == 'image' )
						{	// Use only images:
							if( attachment.position == 'cover' )
							{	// Store link ID of cover image in hidden field to use on insert complex link:
								jQuery( '#shortlinks_hidden_cover_link' ).val( attachment.link_ID );
							}
							if( jQuery( '#shortlinks_hidden_teaser_link' ).val() == '' )
							{	// Store link ID of any first image in hidden field to use on insert complex link:
								jQuery( '#shortlinks_hidden_teaser_link' ).val( attachment.link_ID );
							}
						}
					}
				}
				// Item content:
				var post_content = post.content.replace( /(<h([1-6]).*id\s*=\s*"[^"]+"[^>]*>.+?)(<\/h\2>)/ig,
					'$1 <button class="btn btn-primary shortlinks_btn_insert_anchor">' + shortlinks_insert_short_link + '</button>$3' );
				item_content += '<div id="shortlinks_post_content">' + post_content + '</div>';

				shortlinks_end_loading( '#shortlinks_post_block', item_content );

				// Display the buttons to back and insert a post link to textarea:
				var buttons_side_obj = jQuery( '.shortlinks_post_buttons' ).length ?
					jQuery( '.shortlinks_post_buttons' ) :
					jQuery( '#shortlinks_post_content' );
				jQuery( '#shortlinks_btn_back_to_list, #shortlinks_btn_move_comment, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).remove();
				buttons_side_obj.after( '<button id="shortlinks_btn_back_to_list" class="btn btn-default">&laquo; ' + shortlinks_back + '</button>'
					+ shortlinks_action_buttons );
				jQuery( '#shortlinks_opt_slug' ).val( post.urltitle );
			} );
		}

		// To prevent link default event:
		return false;
	} );

	// Set new comment Item:
	jQuery( document ).on( 'click', '#shortlinks_btn_move_comment', function()
	{
		// Update info field link:
		var item_link = jQuery('.comment_item_title')[0];
		item_link.innerHTML = jQuery('#shortlinks_hidden_title').val();
		item_link.href = '?ctrl=items&blog=' + jQuery('#shortlinks_collections').find(':selected').data('coll-id') + '&p=' + jQuery( '#shortlinks_hidden_ID').val();

		// Update modal selected collection:
		shortlinks_coll_urlname = jQuery('#shortlinks_collections').find(':selected').val();

		// Update moveto_post field:
		jQuery('input[name="moveto_post"]').val( jQuery('#shortlinks_hidden_ID').val() );

		// Close main modal window:
		closeModalWindow();
	} );

	// Insert a post link to textarea:
	jQuery( document ).on( 'click', '#shortlinks_btn_insert', function()
	{
		shortlinks_insert_link_text( '[[' + jQuery( '#shortlinks_hidden_urltitle' ).val() + ']]' );
	} );

	// Insert a post link with anchor(from header tags inside content) to textarea:
	jQuery( document ).on( 'click', '.shortlinks_btn_insert_anchor', function()
	{
		var header_obj = jQuery( this ).parent();
		shortlinks_insert_link_text( '((' + jQuery( '#shortlinks_hidden_urltitle' ).val() + '#' + header_obj.attr( 'id' ) + ' ' + header_obj.html().replace( /(\s<a[^>]+>.+?<\/a>)?\s<button[^>]+>.+?<\/button>$/, '' ) + '))' );
	} );

	// Insert a post link with options to textarea:
	jQuery( document ).on( 'click', '#shortlinks_btn_insert_with_options', function()
	{
		// Select what brakets to use:
		if( jQuery( '#shortlinks_opt_mode_title' ).is( ':checked' ) )
		{
			var brakets_start = '[[';
			var brakets_end = ']]';
		}
		else
		{
			var brakets_start = '((';
			var brakets_end = '))';
		}

		var link_text = brakets_start;

		// Slug:
		link_text += jQuery( '#shortlinks_opt_slug' ).val();

		// Classes:
		var classes = jQuery( '#shortlinks_opt_classes' ).val().trim();
		classes = classes.replace( /^\./g, '' ).replace( /\s+/g, '.' ).replace( /\.+/g, '.' );
		if( classes != '' )
		{
			link_text += ' .' + classes;
		}

		// Link target:
		var target = jQuery( '#shortlinks_opt_target option:selected' ).val().trim();
		if( target != '' )
		{
			link_text += ' ' + target;
		}

		// Custom text:
		var custom_text = jQuery( '#shortlinks_opt_text' ).val().trim();
		if( custom_text != '' )
		{
			link_text += ' ' + custom_text;
		}

		link_text += brakets_end;

		shortlinks_insert_link_text( link_text );
	} );

	// Display a form to insert complex link:
	jQuery( document ).on( 'click', '#shortlinks_btn_form', function()
	{
		// Set proper title for modal window:
		jQuery( '#modal_window .modal-title' ).html( shortlinks_insert_snippet_link );

		// Hide the post preview block:
		jQuery( '#shortlinks_post_block, #shortlinks_btn_insert' ).hide();

		// Show the form to select options before insert complex link:
		jQuery( '#shortlinks_post_form' ).show();

		// Display the buttons to back and insert a complex link to textarea:
		var buttons_side_obj = jQuery( '.shortlinks_post_buttons' ).length ?
			jQuery( '.shortlinks_post_buttons' ) :
			jQuery( '#shortlinks_post_content' );
		jQuery( '#shortlinks_btn_back_to_list, #shortlinks_btn_move_comment, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).hide();
		buttons_side_obj.after( '<button id="shortlinks_btn_back_to_post" class="btn btn-default">&laquo; ' + shortlinks_back + '</button>'
			+ '<button id="shortlinks_btn_insert_complex" class="btn btn-primary">' + shortlinks_insert_snippet_link + '</button>' );

		// To prevent link default event:
		return false;
	} );

	// Insert complex link:
	jQuery( document ).on( 'click', '#shortlinks_btn_insert_complex', function()
	{
		if( ! jQuery( 'input[id^=shortlinks_form_]' ).is( ':checked' ) )
		{	// Display message if no item option checkbox is checked:
			alert( shortlinks_select_item );
			return false;
		}

		var dest_type = false;
		var dest_object_ID = false;
		if( jQuery( 'input[type=hidden][name=temp_link_owner_ID]' ).length )
		{	// New object form:
			dest_type = 'temporary';
			dest_object_ID = jQuery( 'input[type=hidden][name=temp_link_owner_ID]' ).val();
		}
		else if( jQuery( 'input[type=hidden][name=post_ID]' ).length && jQuery( 'input[type=hidden][name=item_typ_ID]' ).length )
		{	// Item form:
			dest_type = 'item';
			dest_object_ID = jQuery( 'input[type=hidden][name=post_ID]' ).val();
		}
		else if( jQuery( 'input[type=hidden][name=comment_ID]' ).length || jQuery( 'input[type=hidden][name=comment_item_ID]' ).length )
		{	// Comment form:
			dest_type = 'comment';
			if( jQuery( 'input[type=hidden][name=comment_ID]' ).length )
			{
				dest_object_ID = jQuery( 'input[type=hidden][name=comment_ID]' ).val();
			}
		}
		else if( jQuery( 'input[type=hidden][name=ecmp_ID]' ).length )
		{	// Email Campaign form:
			dest_type = 'emailcampaign';
			dest_object_ID = jQuery( 'input[type=hidden][name=ecmp_ID]' ).val();
		}
		else if( jQuery( 'input[name=msg_text]' ).length )
		{	// Message form:
			dest_type = 'message';
			dest_object_ID = 0;
		}

		// Check if at least one image is requested to insert:
		var insert_images = ( jQuery( '#shortlinks_form_full_cover' ).is( ':checked' ) && jQuery( '#shortlinks_hidden_cover_link' ).val() != '' )
			|| ( jQuery( '#shortlinks_form_thumb_cover' ).is( ':checked' ) && jQuery( '#shortlinks_hidden_teaser_link' ).val() != '' );

		if( insert_images && dest_type != false && dest_object_ID > 0 )
		{	// We need to insert at least one image/file inline tag:
			shortlinks_start_loading( '#shortlinks_post_block' );

			// Get first image with any position:
			var source_position = '';
			if( jQuery( '#shortlinks_form_full_cover' ).is( ':checked' ) &&
				! jQuery( '#shortlinks_form_thumb_cover' ).is( ':checked' ) )
			{	// Get only cover image:
				source_position = 'cover';
			}

			// Call REST API request to copy the links from the selected Item to the edited object:
			evo_rest_api_request( 'links',
			{
				'action':           'copy',
				'source_type':      'item',
				'source_object_ID': jQuery( '#shortlinks_hidden_ID' ).val(),
				'source_position':  source_position,
				'source_file_type': 'image',
				'dest_type':        dest_type,
				'dest_object_ID':   dest_object_ID,
				'dest_position':    'inline',
				'limit_position':   1,
			}, function( data )
			{
				var full_cover = '';
				var thumb_cover = '';

				for( var l in data.links )
				{
					var link = data.links[l];
					if( link.orig_position == 'cover' && jQuery( '#shortlinks_form_full_cover' ).is( ':checked' ) )
					{	// Build inline tag for full cover image:
						full_cover = '[image:' + link.ID + ']';
					}
					if( jQuery( '#shortlinks_form_thumb_cover' ).is( ':checked' ) )
					{	// Build inline tag for thumbnail cover image:
						thumb_cover = '[thumbnail:' + link.ID + ']';
					}
				}
				shortlinks_insert_complex_link( full_cover, thumb_cover );

				shortlinks_end_loading( '#shortlinks_post_block', jQuery( '#shortlinks_post_block' ).html() );

				// Refresh the attachments block after adding new links:
				var refresh_attachments_button = jQuery( 'a[onclick*=evo_link_refresh_list]' );
				if( refresh_attachments_button.length )
				{
					refresh_attachments_button.click();
				}
			} );
		}
		else
		{	// Insert only simple text without images:
			if( insert_images )
			{	// Display this alert if user wants to insert image for new creating object but it doesn't support:
				alert( 'Please save your ' + dest_type + ' before trying to attach files. This limitation will be removed in a future version of b2evolution.' );
			}
			shortlinks_insert_complex_link();
		}

		// To prevent link default event:
		return false;
	} );

	// Display a form to insert a link with options:
	jQuery( document ).on( 'click', '#shortlinks_btn_options', function()
	{
		// Set proper title for modal window:
		jQuery( '#modal_window .modal-title' ).html( shortlinks_insert_with_options + '...' );

		// Hide the post preview block:
		jQuery( '#shortlinks_post_block, #shortlinks_btn_insert' ).hide();

		// Show the form to select options before insert a link:
		jQuery( '#shortlinks_post_options' ).show();

		// Display the buttons to back and insert a complex link to textarea:
		var buttons_side_obj = jQuery( '.shortlinks_post_buttons' ).length ?
			jQuery( '.shortlinks_post_buttons' ) :
			jQuery( '#shortlinks_post_content' );
		jQuery( '#shortlinks_btn_back_to_list, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).hide();
		buttons_side_obj.after( '<button id="shortlinks_btn_back_to_post" class="btn btn-default">&laquo; ' + shortlinks_back + '</button>'
			+ '<button id="shortlinks_btn_insert_with_options" class="btn btn-primary">' + shortlinks_insert_link + '</button>' );

		// To prevent link default event:
		return false;
	} );

	/*
		* Insert simple link data to content
		*
		* @param string Text
		*/
	function shortlinks_insert_link_text( text )
	{

		if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
		{	// tinyMCE plugin is active now, we should focus cursor to the edit area:
			tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
		}
		// Insert tag text in area:
		textarea_wrap_selection( window[ jQuery( '#shortlinks_hidden_prefix' ).val() + 'b2evoCanvas' ], text, '', 0 );
		// Close main modal window:
		closeModalWindow();
	}

	/*
		* Insert complex link data to content
		*
		* @param string Full cover image inline tag
		* @param string Thumbnail cover image inline tag
		*/
	function shortlinks_insert_complex_link( full_cover, thumb_cover )
	{
		var post_content = '';

		if( typeof( full_cover ) != 'undefined' && full_cover != '' )
		{	// Full cover image:
			post_content += "\r\n" + full_cover;
		}
		if( jQuery( '#shortlinks_form_title' ).is( ':checked' ) )
		{	// Title:
			post_content += "\r\n" + '## [[' + jQuery( '#shortlinks_hidden_urltitle' ).val() + ' ' + jQuery( '#shortlinks_hidden_title' ).val() + ']]';
		}
		if( typeof( thumb_cover ) != 'undefined' && thumb_cover != '' )
		{	// Thumbnail cover image:
			post_content += "\r\n" + thumb_cover;
		}
		if( jQuery( '#shortlinks_form_excerpt' ).is( ':checked' ) )
		{	// Excerpt:
			post_content += ( typeof( thumb_cover ) != 'undefined' && thumb_cover != '' ? ' ' : "\r\n" )
				+ jQuery( '#shortlinks_hidden_excerpt' ).val();
		}
		if( jQuery( '#shortlinks_form_teaser' ).is( ':checked' ) )
		{	// Teaser (text before [teaserbreak]):
			post_content += "\r\n" + jQuery( '#shortlinks_hidden_teaser' ).val();
		}
		if( jQuery( '#shortlinks_form_more' ).is( ':checked' ) )
		{	// "Read more" link:
			post_content += "\r\n" + '[[' + jQuery( '#shortlinks_hidden_urltitle' ).val() + ' ' + shortlinks_read_more + '...]]';
		}
		if( post_content != '' )
		{
			post_content = post_content + "\r\n\r\n";
		}

		if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
		{	// tinyMCE plugin is active now, we should focus cursor to the edit area:
			tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
		}
		// Insert tag text in area:
		textarea_wrap_selection( window[ jQuery( '#shortlinks_hidden_prefix' ).val() + 'b2evoCanvas' ], post_content, '', 0 );
		// Close main modal window:
		closeModalWindow();
	}

	// Back to previous list:
	jQuery( document ).on( 'click', '#shortlinks_btn_back_to_list', function()
	{
		// Show the lists of collections and posts:
		jQuery( '#shortlinks_colls_list, #shortlinks_posts_block' ).show();

		// Hide the post preview block and action buttons:
		jQuery( '#shortlinks_post_block, #shortlinks_btn_back_to_list, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).hide();

		// To prevent link default event:
		return false;
	} );

	// Back to previous post preview:
	jQuery( document ).on( 'click', '#shortlinks_btn_back_to_post', function()
	{
		// Set proper title for modal window:
		jQuery( '#modal_window .modal-title' ).html( shortlinks_link_to_post );

		// Show the post preview block and action buttons:
		jQuery( '#shortlinks_post_block, #shortlinks_btn_back_to_list, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).show();

		// Hide the post complex form and action buttons:
		jQuery( '#shortlinks_btn_back_to_post, #shortlinks_btn_insert_complex, #shortlinks_btn_insert_with_options, #shortlinks_post_form, #shortlinks_post_options' ).hide();

		// To prevent link default event:
		return false;
	} );

	// Switch page:
	jQuery( document ).on( 'click', '.shortlinks_pagination a', function()
	{
		var coll_selector = jQuery( '#shortlinks_colls_list select' );
		var pages_list = jQuery( this ).closest( '.shortlinks_pagination' );

		if( pages_list.data( 'search' ) == undefined )
		{	// Load posts/items for selected page:
			shortlinks_load_coll_posts( coll_selector.val(), false, jQuery( this ).data( 'page' ) );
		}
		else
		{	// Load search list for selected page:
			shortlinks_load_coll_search( coll_selector.val(), pages_list.data( 'search' ), jQuery( this ).data( 'page' ) );
		}

		// To prevent link default event:
		return false;
	} );

}