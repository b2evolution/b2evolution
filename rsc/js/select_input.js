/*
*
*		------------------------------------------------o------------------------------------------------
*										Plugin Setting Type: select_input
*		------------------------------------------------o------------------------------------------------
*
*		@used: 					by inc/plugins/_plugin.funcs.php function autoform_display_field();
*		@loaded:				skins_adm/bootstrap/_adminUI.class.php
*		@version:				6.10.1
*		@Released Date:			2018/04/10
*
*		------------------------------------------------o------------------------------------------------
*
* This file is part of the evoCore framework - {@link http://evocore.net/}
* See also {@link https://github.com/b2evolution/b2evolution}.
*
* @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
*
* @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
* Parts of this file are copyright (c)2018 by Jacques Joubert - {@link http://www.midnightstudios.co.za}.
*
* @package plugins
*/
var SelectInput = function(d)
{
	'use strict';
	
		this.blog_id = d.blog_id;
		this.user_id = d.user_id;
		this.htsrv_url = d.htsrv_url;
		this.action = d.action;
		this.val = d.val;
		this.id = d.id;
		this.max_number = d.max_number; 		
		this.obj_param_prefix = d.obj_param_prefix; 	
		this.obj_id = d.obj_id;
		this.set_type = d.set_type;		
		this.set_path = d.set_path;	
		this.parname = d.parname;	
		this.entries = JSON.parse(d.entries);
		this.select_msg = d.select_msg;
		this.max_msg = d.max_msg;
		this.ajax_msg = d.ajax_msg;
		this.use_single_button = d.use_single_button;
		this.remove_button = d.remove_button;	
		this.data = d;
	
	SelectInput.prototype.validate_entries(d);
	SelectInput.prototype.attach_remove_buttons(this);
	
};

$.extend(SelectInput.prototype, {
		clear_messages: function()
						{
							'use strict';
							
							var action_msg_container = jQuery( '#' + this.id + '_action_messages' );

							// Clear all previous action messages, if any: 
							action_msg_container.children().remove();

						},
	remove_button : function(t,e) 
				{
					'use strict';
					
				var remove_item = jQuery(t).closest('.form-group'),
					remove_item_id = remove_item.prop('id'); 
		
				$('.'+remove_item_id).each(function(){ 
					$(this).remove();
				});
				
				remove_item.remove(); 
			SelectInput.prototype.validate_entries(e);

			},
	attach_remove_buttons: function(e)
						{
							'use strict';
														
							var removeButton = jQuery( '<span>' ).html( e.remove_button ).text();
							
							jQuery( '#' + e.id + '_disp' ).children( '.form-group' ).each( function()
							{	
								jQuery( this ).find( '.controls' ).append( removeButton );
							} );	
							
							$('.remove_'+e.id).each(function(){
								
							$(this).on("click", function(){
								SelectInput.prototype.remove_button(this, e);
							});
							});
							
						},
      validate_entries: function(e) 
						{
							'use strict';
							
							if( typeof e.id === 'undefined' ){return;}

							var max_items_container = $('#'+e.id+'_max_items'),
								entries = ( typeof e.entries === 'string' ) ? JSON.parse(e.entries) : e.entries,
								select_input_add = $('#'+e.id+'_add_new'), 
								select_input = $('#'+e.id), 
								select_input_empty = $('#'+e.id+'_empty'),
								k_nb = $('#'+e.id+'_disp').children('.form-group').length;
					
							if( k_nb > 0 )
							{
								select_input_empty.css({'display':'none'});
							}
							else
							{	
								select_input_empty.css({'display':''});
							}
							
							if( typeof e.max_number === 'undefined' ){return;}
							
							function hasOwnProp(a, b) {
								return Object.prototype.hasOwnProperty.call(a, b);
							}
							
							var keys;
							
							if (Object.keys) {
								keys = Object.keys;
							} else {
								keys = function (obj) {
									var i, res = [];
									for (i in obj) {
										if (hasOwnProp(obj, i)) {
											res.push(i);
										}
									}
									return res;
								};
							}
							
							var single = ( keys(entries).length === 1 ) ? true : false;

							function send_notice(m)
							{
								if( k_nb < m )
								{
									max_items_container.css({'display':'none'});
									select_input_add.css({'display':''});
									select_input.css({'display':''});
								} 
								else 
								{

									var action_msg_container = jQuery( '#' + e.id + '_action_messages' );

									action_msg_container.html('<div class="alert alert-info" id="max_items_msg_' + e.id + '"><button type="button" class="close" data-dismiss="alert">x</button>' + e.max_msg + '</div>').disableSelection();

									$("#max_items_msg_" + e.id).fadeTo(4000, 500).slideUp(500, function(){
									$("#max_items_msg_" + e.id).slideUp(500).remove();});

									max_items_container.css({'display':''});
									select_input_add.css({'display':'none'});
									select_input.css({'display':'none'});
								}
							}
							
							if( single )
							{ 
								//var m = entries[keys(entries)[0]].max_number;
								var m = entries[e.val].max_number;
								
								if( typeof m !== 'undefined' )
								{
									send_notice(m);
								}
							}
							else
							{
								send_notice(e.max_number);
							}
							
						},
      input_select_add: function() 
						{
							'use strict';
							
							var k_nb = $('#' + this.id + '_disp').children('.form-group').length,
								entry_name = ( this.val === null || typeof this.val === 'undefined' ) ? jQuery('#' + this.id + ' option:selected').val() : this.val,
								entries = this.entries,
								has_color_field = false,
								entry_max = 0,
								action_msg_container = jQuery( '#' + this.id + '_action_messages' ),
								entry_type = '';
							
						if( typeof entries[entry_name] !== 'undefined' )
						{				
							entry_type = ( typeof entries[entry_name].type !== 'undefined' ) ? entries[entry_name].type : '';
							entry_max = ( entries[entry_name].max_number !== 'undefined' ) ? entries[entry_name].max_number:0;

							if( entry_type !== 'undefined' )
							{
								if( entry_type === 'color' )
								{
									has_color_field = true;
								}
							}	
							if( typeof entries[entry_name].inputs !== 'undefined' )
							{
								var inputs = entries[entry_name].inputs;

								$.each( inputs, function( key, value ) {

									if( typeof inputs[key].type !== 'undefined' )
									{
										if( inputs[key].type === 'color' )
										{
											has_color_field = true;
										}
									}
								});
							}
						}
						else
						{
							entry_type = '';
							entry_max = 0;
						}

						// Get param_prefix used:
						var param_prefix = 'ffield_' + this.obj_param_prefix + this.id + '_#_';

						// Create an array with all used input types:
						var disp_entries = $('#' + this.id + '_disp').children('.form-group').map(function () {

						// Strip param_prefix from the string: 
						var r = $(this).prop('id').substring(param_prefix.length);

						// Isolate and return the input type from the remaining string:
						return r.substring(1, r.length-1);
						// End of map
						}).get();

						// Create function to build new array with type occurrence {input_name:occurrences}:	
						var p = 'occurrence';
						if(!Object.prototype.hasOwnProperty(p)){
							Object.defineProperty(Array.prototype, p, {
								enumerable: false,
								writable: true,
								value: function () {
									var occurrence = {}; 
									this.map( function (a){ 
										if (!(a in this)) 
										{ this[a] = 1; } 
										else 
										{ this[a] += 1; } 
										return a; 
									}, occurrence );  
									return occurrence; 
								}
							});	
						}

						// Build new array with type occurrence {input_name:occurrences}:
						disp_entries = disp_entries.occurrence();
							
						if( entry_max > 0 || this.entry_max > 0 )
						{	// Did the user reach maximum amount of entries allowed?
							if( disp_entries[entry_name] >= entry_max )
							{	// Send a messsage to the user, else it might seem like there is no response on the click action? 
								action_msg_container.html( '<div id="max_items_msg_' + entry_name + this.id + '" class="action_messages container-fluid"><ul><li><div class="alert alert-dismissible alert-danger fade in">' + this.max_msg +  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div></li></ul></div>' );

								$("#max_items_msg_" + entry_name + this.id).fadeTo(4000, 500).slideUp(500, function(){
    							$("#max_items_msg_" + entry_name + this.id).slideUp(500).remove();});
								//Already added the maximum number of items for this type!
								return false;
							}
							//Sure, let\'s add this type!
						}
							
						if( entry_type === '' && this.use_single_button !== true )
						{	// Mark select element of field types as error
							
							field_type_error( this.select_msg, this.id );
							// We should stop the ajax request without entry_type
							return false;
						}
						else
						{	// Remove an error class from the field
							field_type_error_clear(this.id);
						}

						function field_type_error( message, id )
						{	// Add an error message for the 'field of type' select
							jQuery( '#' + id ).addClass( 'field_error' );
							var span_error = jQuery( '#' + id ).siblings( 'span.field_error' );
							if( span_error.length > 0 )
							{	// Replace a content of the existing span element
								span_error.html( message );
							}
							else
							{	// Create a new span element for error message

								var err = $('<span>').css({'padding':'0px 15px'}).addClass('field_error').html(message);

								jQuery( '#' + id ).next().after( err );
							}
						}

						function field_type_error_clear(id)
						{	// Remove an error style from the 'field of type' select
							jQuery( '#' + id ).removeClass( 'field_error' )
							.siblings( 'span.field_error' ).remove();
						}
							
						function ajax_call(e)
						{
							
							var ajax_running = false;
							if( $.active > 0) {
								
								action_msg_container.html('<div class="alert alert-warning" id="ajax-running"><button type="button" class="close" data-dismiss="alert">x</button>' + e.ajax_msg + '</div>').disableSelection();
								
								$("#ajax-running").fadeTo(2000, 500).slideUp(500, function(){
    							$("#ajax-running").slideUp(500).remove();});
								ajax_running = true;
							}
							
							if( ajax_running === true ){ return; }
							
							jQuery.get( e.htsrv_url + 'async.php',
							{
								action: e.action,
								plugin_ID: e.obj_id,
								set_type: e.set_type,
								set_path: e.set_path,
								parname: e.parname,
								k_nb: k_nb,
								entry_name: entry_name,
								entry_type: entry_type,
								blog: e.blog_id,
								user_ID: e.user_id
							},
							function( data, status )
							{
								
								if( status !== 'success' ) {return;}
								
								var html = jQuery.parseHTML( data, document, true ),
									controls = jQuery(html).find('.controls'),
									removeButton = $('<div>').html(e.remove_button).text(),
									id = e.id.replace(/(\[|\])/g, "\\\$1");
									
								switch( entry_type )
								{
									case 'checkbox':
										$(removeButton).css('vertical-align','top'); // align
										break;
									case 'radio':
										$(removeButton).css('vertical-align','bottom'); // align
										break;
									case 'checklist':
										$(removeButton).css('display','block'); // align
										break;
									default:
										$(removeButton).css('vertical-align','middle'); // align
										break;
								}
								
								if( controls.children('div').length > 0 )
								{	// this should target checkboxes
									removeButton = controls.children().last().append(removeButton);
								}
								else
								{
									removeButton = controls.append(removeButton);	
								}
								
								$(removeButton).delegate('.remove_'+e.id,'click',function() {
								SelectInput.prototype.remove_button(this, e);
								});
								
								
								var container = jQuery('#' + id + '_disp');
								
								if( container.children('.form-group').length === 0 )
								{
									container.append(html);
								}
								else
								{
									container.children('.form-group').last().after(html);
								}
								if( has_color_field === true )
								{
									evo_initialize_colorpicker_inputs();
								}

								SelectInput.prototype.validate_entries(e);
								
							} )
						}
ajax_call(this);
}
});







