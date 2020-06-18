/**
 * This file initialize Widget "Checklist Items"
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
	if( typeof( evo_init_checklist_items_config ) == 'undefined' )
	{	// No config found:
		return;
	}

	var config = evo_init_checklist_items_config;
	window.toggle_add_checklist_item_input = function( form, show_input )
		{
			var new_checklist_item_input  = jQuery( '.add_checklist_item_input', form );
			var new_checklist_item_button = jQuery( '.checklist_add_btn' );
			var new_checklist_item_close  = jQuery( '.checklist_close_btn' );

			if( show_input == undefined )
			{
				show_input = jQuery( '.add_checklist_item_input', form ).is( ':visible' ).length === 0;
			}

			if( show_input == true )
			{
				new_checklist_item_button.html( config.button_label_add );
				new_checklist_item_input.show();
				new_checklist_item_close.show();
				new_checklist_item_input.focus();
			}
			else if( show_input == false )
			{
				new_checklist_item_button.html( config.button_label_add_an_item );
				new_checklist_item_input.hide();
				new_checklist_item_close.hide();
			}
		};

	window.update_checklist_item = function ( input_field, item_ID )
		{
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'action.php',
				data: {
						'mname': 'collections',
						'action': 'checklist_item',
						'item_ID': config.item_ID,
						'check_ID': item_ID,
						'check_label': input_field.val(),
						'check_checked': input_field.checked,
						'crumb_collections_checklist_item': config.crumb_checklist_item,
					},
				dataType: 'json',
				success: function( result )
					{
						if( result.status == 'add' )
						{	// Add checklist item:
							var form = input_field.closest( 'form' );
							var checklist = jQuery( '.checklist_items', form );
							var new_checklist_item = config.checklist_item_template;

							new_checklist_item = new_checklist_item.replace( '$checklist_item_ID$', 'checklist_item_' + result.check_ID );
							new_checklist_item = new_checklist_item.replace( '$checklist_item_value$', result.check_ID );
							new_checklist_item = new_checklist_item.replace( '$checklist_item_label$', result.check_label );

							new_checklist_item = jQuery( new_checklist_item );
							checklist.append( new_checklist_item );
							window.dragndrop_checklist_items( new_checklist_item );

							// Clear input field:
							if( input_field.hasClass( 'add_checklist_item_input' ) )
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
							var label = input_field.closest( '.checklist_item_label' );
							label.html( result.check_label );
						}
					},
				error: function()
					{
						console.error( 'Add/update checklist item request error.' );
					}
			} );
		};

	window.toggle_checklist_item = function ( checkbox_field, item_ID )
		{
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'action.php',
				data: {
						'mname': 'collections',
						'action': 'checklist_item',
						'item_action': 'toggle_check',
						'item_ID': config.item_ID,
						'check_ID': item_ID,
						'check_checked': jQuery( checkbox_field ).is(':checked'),
						'crumb_collections_checklist_item': config.crumb_checklist_item,
					},
				dataType: 'json',
				success: function( result )
					{
						// Do nothing
					},
				error: function()
					{
						console.error( 'Add/update checklist item request error.' );
					}
			} );
		}

	window.delete_checklist_item = function ( obj, item_ID )
		{
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'action.php',
				data: {
						'mname': 'collections',
						'action': 'checklist_item',
						'item_action': 'delete',
						'item_ID': config.item_ID,
						'check_ID': item_ID,
						'crumb_collections_checklist_item': config.crumb_checklist_item,
					},
				dataType: 'json',
				success: function( result )
					{
						if( result.status == 'delete' )
						{	// Delete checklist item:
							var checklist_item = jQuery( obj ).closest( '.checklist_item' );
							checklist_item.remove();
						}
					},
				error: function()
					{
						console.error( 'Delete checklist item request error.' );
					}
			} );
		};

	window.reorder_checklist_items = function ( checklist )
		{
			var checklist_item_order = [];
			jQuery( '.checklist_item', checklist ).each( function( index, el ) {
					checklist_item_order.push( jQuery( 'input', el ).val() );
				} );

			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'action.php',
				data: {
					'mname': 'collections',
					'action': 'checklist_item',
					'item_action': 'reorder',
					'item_ID': config.item_ID,
					'item_order': checklist_item_order,
					'crumb_collections_checklist_item': config.crumb_checklist_item,
				},
				dataType: 'json',
				success: function( result )
					{
						// Do nothing for now
					},
				error: function()
					{
						console.error( 'Delete checklist item request error.' );
					}
			} );
		};

	window.dragndrop_checklist_items = function ( selector )
		{
			// Make checklist item draggable:
			jQuery( selector ).draggable( {
					helper: "original",
					scroll: true, // scroll the window during dragging
					scrollSensitivity: 100, // distance from edge before scoll occurs
					zIndex: 999, // z-index whilst dragging
					opacity: .8, // opacity whilst dragging
					cursor: "move", // change the cursor whilst dragging
					cancel: 'input,textarea,button,select,option,a,span.fa,span.widget_checkbox', // prevents dragging from starting on specified elements
					stop: function()
						{
							// Remove style so dragged item "snaps" back to the list
							jQuery( this ).removeAttr( 'style' );
						}
				} ).addClass( "draggable_checklist_item" ); // add our css class

			// Make checklist item droppable:
			jQuery( selector ).droppable( {
					accept: ".draggable_checklist_item", // classname of objects that can be dropped
					hoverClass: "droppable-hover", // classname when object is over this one
					greedy: true, // stops propogation if over more than one
					tolerance : "pointer", // droppable active when cursor over
					delay: 1000,
					drop: function( event, ui )
						{	// function called when object dropped
							var checklist = jQuery( this ).closest( '.checklist_items' );

							// Move the dragged item:
							jQuery( this ).after( ui.draggable );

							// Send the order to the server for persistence:
							window.reorder_checklist_items( checklist );
						}
				} );
		};

	// Show new checklist item input on Add Item button click:
	jQuery( '.checklist_add_btn' ).on( 'click', function() {
			var form = jQuery( this ).closest( 'form' );
			var input_field = jQuery( 'textarea.add_checklist_item_input', form );
			if( input_field.is( ':visible' ) )
			{	// Add checklist item input is visible, send request:
				window.update_checklist_item( input_field );
			}
			else
			{	// Show the add checklist item input:
				window.toggle_add_checklist_item_input( form, true );
			}
		} );

	// Hide new checklist item input on Close button click:
	jQuery( '.checklist_close_btn' ).on( 'click', function() {
			var form = jQuery( this ).closest( 'form' );
			window.toggle_add_checklist_item_input( form, false );
		} );


	// Edit checklist item label on click:
	jQuery( document ).on( 'click', '.checklist_items label .checklist_item_label', function( event ) {
			var label = jQuery( this );
			var content = label.html();
			var checklist_item = label.closest( '.checklist_item' );
			var checkbox = jQuery( 'input[type="checkbox"]', checklist_item );
			var form = checklist_item.closest( 'form' );
			event.preventDefault();

			if( label.has( 'textarea.checklist_item_input' ).length === 0 )
			{
				// Close new checklist item input:
				window.toggle_add_checklist_item_input( form, false );

				// Show textarea input for checklist item label:
				var input_template = jQuery( config.checklist_item_input_template );
				var textarea = jQuery( 'textarea.checklist_item_input', input_template );
				textarea.attr( 'id', 'checklist_item_input_' + checkbox.val() );
				textarea.attr( 'name', 'checklist_item_input_' + checkbox.val() );
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

	// Cancel checklist item label edit on blur:
	jQuery( document ).on( 'blur', '.checklist_items .checklist_item_label .checklist_item_input', function( event ) {
			var input_field = jQuery( this );
			var content = input_field.data( 'content' );
			var label = input_field.closest( '.checklist_item_label' );
			setTimeout( function() {
					// Placed inside a setTimeout to prevent jQuery error:
					label.html( content );
				}, 10 );
		} );

	// Add/Update checklist item on Enter keypress:
	jQuery( document ).on( 'keypress', '.checklist_item_input', function( event ) {
			var input_field = jQuery( this );
			if( event.keyCode == 13 )
			{
				event.preventDefault();
				if( input_field.val().length )
				{
					window.update_checklist_item( input_field, input_field.data( 'checkId' ) );
				}
			}
		} );

	// Update checklist item on checkbox click:
	jQuery( document ).on( 'change', '.checklist_items .checklist_item input[type="checkbox"]', function( event ) {
			var checkbox_field = jQuery( this );
			window.toggle_checklist_item( checkbox_field, checkbox_field.val() );
		} );

	// Delete checklist item on delete icon click:
	jQuery( document ).on( 'click', '.checklist_items .checklist_item .checklist_item_delete', function( event ) {
			var delete_link = jQuery( this );	
			var checklist_item = delete_link.closest( '.checklist_item' );
			var checkbox = jQuery( 'input[type="checkbox"]', checklist_item );
			var form = checklist_item.closest( 'form' );

			// Close new checklist item input:
			window.toggle_add_checklist_item_input( form, false );

			// Delete the checklist item:
			window.delete_checklist_item( delete_link, checkbox.val() );

			return false;
		} );

	// Auto-resize checklist item input:
	jQuery( document ).on( 'input', 'textarea.checklist_item_input', function( event ) {
			this.style.height = 'auto';
			this.style.height = ( this.scrollHeight ) + 'px';
		} );

	// Make checklist items draggable and droppable:
	window.dragndrop_checklist_items( '.checklist_items .checklist_item, .checklist_droparea' );
} );
