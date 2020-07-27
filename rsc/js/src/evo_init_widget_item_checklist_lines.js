/**
 * This file initialize Widget "Checklist Lines"
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */
jQuery( document ).ready( function()
{
	if( typeof( evo_init_checklist_lines_config ) == 'undefined' )
	{	// No config found:
		return;
	}

	var config = evo_init_checklist_lines_config;
	window.toggle_add_checklist_line_input = function( wrapper, show_input )
		{
			var new_checklist_line_input  = jQuery( '.add_checklist_line_input', wrapper );
			var new_checklist_line_button = jQuery( '.checklist_add_btn' );
			var new_checklist_line_close  = jQuery( '.checklist_close_btn' );

			if( show_input == undefined )
			{
				show_input = jQuery( '.add_checklist_line_input', wrapper ).is( ':visible' ).length === 0;
			}

			if( show_input == true )
			{
				new_checklist_line_button.html( config.button_label_add );
				new_checklist_line_input.show();
				new_checklist_line_close.show();
				new_checklist_line_input.focus();
			}
			else if( show_input == false )
			{
				new_checklist_line_button.html( config.button_label_add_an_item );
				new_checklist_line_input.hide();
				new_checklist_line_close.hide();
			}
		};

	window.update_checklist_line = function ( input_field, checklist_line_ID )
		{
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'action.php',
				data: {
						'mname': 'collections',
						'action': 'checklist_line',
						'item_ID': config.item_ID,
						'check_ID': checklist_line_ID,
						'check_label': input_field.val(),
						'check_checked': input_field.checked,
						'crumb_collections_checklist_line': config.crumb_checklist_line,
					},
				dataType: 'json',
				success: function( result )
					{
						if( result.status == 'add' )
						{	// Add checklist line:
							var wrapper = input_field.closest( 'div.checklist_wrapper' );
							var checklist = jQuery( '.checklist_lines', wrapper );
							var new_checklist_line = config.checklist_line_template;

							new_checklist_line = new_checklist_line.replace( /\$checklist_line_ID\$/g, result.check_ID );
							new_checklist_line = new_checklist_line.replace( /\$checklist_line_label\$/g, result.check_label );

							new_checklist_line = jQuery( new_checklist_line );
							checklist.append( new_checklist_line );
							window.dragndrop_checklist_lines( new_checklist_line );

							// Clear input field:
							if( input_field.hasClass( 'add_checklist_line_input' ) )
							{	// Clear input field:
								input_field.val( '' );

								// Reset textarea size:
								var el = input_field.get( 0 );
								el.setAttribute( 'style', 'height:' + ( el.scrollHeight ) + 'px;overflow-y:hidden;' );
								el.style.height = 'auto';
								el.style.height = ( el.scrollHeight ) + 'px';

								input_field.focus();
							}
						}
						else if( result.status == 'update' )
						{	// Update checklist item:
							var label = input_field.closest( '.checklist_line_label' );
							label.html( result.check_label );
						}
					},
				error: function()
					{
						console.error( 'Add/update checklist line request error.' );
					}
			} );
		};

	window.toggle_checklist_line = function ( checkbox_field, checklist_line_ID )
		{
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'action.php',
				data: {
						'mname': 'collections',
						'action': 'checklist_line',
						'item_action': 'toggle_check',
						'item_ID': config.item_ID,
						'check_ID': checklist_line_ID,
						'check_checked': jQuery( checkbox_field ).is(':checked'),
						'crumb_collections_checklist_line': config.crumb_checklist_line,
					},
				dataType: 'json',
				success: function( result )
					{
						// Do nothing
					},
				error: function()
					{
						console.error( 'Add/update checklist line request error.' );
					}
			} );
		}

	window.delete_checklist_line = function ( obj, checklist_line_ID )
		{
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'action.php',
				data: {
						'mname': 'collections',
						'action': 'checklist_line',
						'item_action': 'delete',
						'item_ID': config.item_ID,
						'check_ID': checklist_line_ID,
						'crumb_collections_checklist_line': config.crumb_checklist_line,
					},
				dataType: 'json',
				success: function( result )
					{
						if( result.status == 'delete' )
						{	// Delete checklist line:
							var checklist_line = jQuery( obj ).closest( '.checklist_line' );
							checklist_line.remove();
						}
					},
				error: function()
					{
						console.error( 'Delete checklist line request error.' );
					}
			} );
		};

	window.reorder_checklist_lines = function ( checklist )
		{
			var checklist_line_order = [];
			jQuery( '.checklist_line', checklist ).each( function( index, el ) {
					checklist_line_order.push( jQuery( 'input', el ).val() );
				} );

			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'action.php',
				data: {
					'mname': 'collections',
					'action': 'checklist_line',
					'item_action': 'reorder',
					'item_ID': config.item_ID,
					'item_order': checklist_line_order,
					'crumb_collections_checklist_line': config.crumb_checklist_line,
				},
				dataType: 'json',
				success: function( result )
					{
						// Do nothing for now
					},
				error: function()
					{
						console.error( 'Delete checklist line request error.' );
					}
			} );
		};

	window.dragndrop_checklist_lines = function ( selector )
		{
			// Make checklist line draggable:
			jQuery( selector ).draggable( {
					axis: 'y',
					helper: 'original',
					scroll: true, // scroll the window during dragging
					scrollSensitivity: 100, // distance from edge before scoll occurs
					zIndex: 999, // z-index whilst dragging
					opacity: .8, // opacity whilst dragging
					cursor: 'move', // change the cursor whilst dragging
					cancel: 'input,textarea,button,select,option,a', // prevents dragging from starting on specified elements
					stop: function()
						{
							// Remove style so dragged item "snaps" back to the list
							jQuery( this ).removeAttr( 'style' );
						}
				} ).addClass( 'draggable_checklist_line' ); // add our css class

			// Make checklist item droppable:
			jQuery( selector ).droppable( {
					accept: '.draggable_checklist_line', // classname of objects that can be dropped
					hoverClass: 'droppable-hover', // classname when object is over this one
					greedy: true, // stops propogation if over more than one
					tolerance : 'pointer', // droppable active when cursor over
					delay: 1000,
					drop: function( event, ui )
						{	// function called when object dropped
							var checklist = jQuery( this ).closest( '.checklist_lines' );

							// Move the dragged item:
							jQuery( this ).after( ui.draggable );

							// Send the order to the server for persistence:
							window.reorder_checklist_lines( checklist );
						}
				} );
		};

	// Show new checklist line input on Add Item button click:
	jQuery( '.checklist_add_btn' ).on( 'click', function() {
			var wrapper = jQuery( this ).closest( 'div.checklist_wrapper' );
			var input_field = jQuery( 'textarea.add_checklist_line_input', wrapper );
			if( input_field.is( ':visible' ) )
			{	// Add checklist line input is visible, send request:
				window.update_checklist_line( input_field );
			}
			else
			{	// Show the add checklist line input:
				window.toggle_add_checklist_line_input( wrapper, true );
			}
		} );

	// Hide new checklist line input on Close button click:
	jQuery( '.checklist_close_btn' ).on( 'click', function() {
			var wrapper = jQuery( this ).closest( 'div.checklist_wrapper' );
			window.toggle_add_checklist_line_input( wrapper, false );
		} );


	// Edit checklist line label on click:
	jQuery( document ).on( 'click', '.checklist_lines label .checklist_line_label', function( event ) {
			var label = jQuery( this );
			var content = label.html();
			var checklist_line = label.closest( '.checklist_line' );
			var checkbox = jQuery( 'input[type="checkbox"]', checklist_line );
			var wrapper = checklist_line.closest( 'div.checklist_wrapper' );

			event.preventDefault();

			if( label.has( 'textarea.checklist_line_input' ).length === 0 )
			{
				// Close new checklist line input:
				window.toggle_add_checklist_line_input( wrapper, false );

				// Show textarea input for checklist line label:
				var input_template = jQuery( config.checklist_line_input_template );
				var textarea = jQuery( 'textarea.checklist_line_input', input_template );
				textarea.attr( 'id', 'checklist_line_input_' + checkbox.val() );
				textarea.attr( 'name', 'checklist_line_input_' + checkbox.val() );
				textarea.val( content );
				textarea.data( 'checkId', checkbox.val() );
				textarea.data( 'content', content );
				label.html( textarea );

				// Make textarea auto-resize:
				var el = textarea.get( 0 );
				el.setAttribute( 'style', 'height:' + ( el.scrollHeight ) + 'px;overflow-y:hidden;' );
				el.style.height = 'auto';
				el.style.height = ( el.scrollHeight ) + 'px';

				textarea.focus();
			}
		} );

	// Cancel checklist line label edit on blur:
	jQuery( document ).on( 'blur', '.checklist_lines .checklist_line_label .checklist_line_input', function( event ) {
			var input_field = jQuery( this );
			var content = input_field.data( 'content' );
			var label = input_field.closest( '.checklist_line_label' );
			setTimeout( function() {
					// Placed inside a setTimeout to prevent jQuery error:
					label.html( content );
				}, 10 );
		} );

	// Add/Update checklist line on Enter keypress:
	jQuery( document ).on( 'keypress', '.checklist_line_input', function( event ) {
			var input_field = jQuery( this );
			if( event.keyCode == 13 )
			{
				event.preventDefault();
				if( input_field.val().length )
				{
					window.update_checklist_line( input_field, input_field.data( 'checkId' ) );
				}
			}
		} );

	// Update checklist line on checkbox click:
	jQuery( document ).on( 'change', '.checklist_lines .checklist_line input[type="checkbox"]', function( event ) {
			var checkbox_field = jQuery( this );
			window.toggle_checklist_line( checkbox_field, checkbox_field.val() );
		} );

	// Delete checklist line on delete icon click:
	jQuery( document ).on( 'click', '.checklist_lines .checklist_line .checklist_line_delete', function( event ) {
			var delete_link = jQuery( this );	
			var checklist_line = delete_link.closest( '.checklist_line' );
			var checkbox = jQuery( 'input[type="checkbox"]', checklist_line );
			var wrapper = checklist_line.closest( 'div.checklist_wrapper' );

			// Close new checklist line input:
			window.toggle_add_checklist_line_input( wrapper, false );

			// Delete the checklist line:
			window.delete_checklist_line( delete_link, checkbox.val() );

			return false;
		} );

	// Auto-resize checklist line input:
	jQuery( document ).on( 'input', 'textarea.checklist_line_input', function( event ) {
			this.style.height = 'auto';
			this.style.height = ( this.scrollHeight ) + 'px';
		} );

	// Make checklist line draggable and droppable:
	window.dragndrop_checklist_lines( '.checklist_lines .checklist_line, .checklist_droparea' );
} );
