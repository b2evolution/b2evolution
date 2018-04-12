/*
*
*		------------------------------------------------o------------------------------------------------
*										Plugin Setting Type: array:array:string
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
var InputSupport = function(d){
	'use strict'; 
	
		this.blog_id = d.blog_id;
		this.user_id = d.user_id;
		this.htsrv_url = d.htsrv_url;
		this.action = d.action;
		this.id = d.id;
		this.max_number = d.max_number; 			
		this.obj_id = d.obj_id;
		this.set_type = d.set_type;		
		this.set_path = d.set_path;	
		this.parname = d.parname;	
		this.max_msg = d.max_msg;
		this.ajax_msg = d.ajax_msg;
		this.remove_button = d.remove_button;	
		this.data = d;
	
	InputSupport.prototype.rebuild_index(d);
};
$.extend(InputSupport.prototype, {
	
	add: function(e)
		{ 
			'use strict';
					 
			function ajax_call(e)
			{ 
				var i = InputSupport.prototype.get_set_count(e.parname), set_path = e.parname + '['+i+']', ajax_running = false, $container = jQuery('#' + e.id + '_add_new'), html = '';
				
				if( $.active > 0)
				{
					if( jQuery('#ajax-running').length === 0 )
					{
						html = jQuery.parseHTML('<div class="alert alert-warning" id="ajax-running"><button type="button" class="close" data-dismiss="alert">x</button>' + e.ajax_msg + '</div>');

						jQuery(html).disableSelection();

						$container.after(html);

						$("#ajax-running").fadeTo(2000, 500).slideUp(500, function(){
						$("#ajax-running").slideUp(500).remove();});
					}
					ajax_running = true;
				}

				if( ajax_running === true ){ return; }
					
					if( e.max_number !== 0 && i >= e.max_number  )
					{ 
						html = jQuery.parseHTML('<div class="alert alert-warning" id="ajax-running"><button type="button" class="close" data-dismiss="alert">x</button>' + e.max_msg + '</div>');

						jQuery(html).disableSelection();

						$container.after(html);

						$("#ajax-running").fadeTo(2000, 500).slideUp(500, function(){
						$("#ajax-running").slideUp(500).remove();});
						
					}

				jQuery.get( e.htsrv_url + 'async.php',
				{
					action: e.action,
					plugin_ID: e.obj_id,
					set_type: e.set_type,
					set_path: set_path,
					blog: e.blog_id,
					user_ID: e.user_id
				},
				function(r, status) 
				{
					if( status !== 'success' ) {return;}

					jQuery('#' + e.parname + '_add_new').replaceWith(r);
					if( e.has_color_field === true )
					{
						evo_initialize_colorpicker_inputs();
					}
					
					$('#' + e.parname + '_add_new').delegate('.remove_'+e.id,'click',function() {
						InputSupport.prototype.remove_button(e);
					});

					InputSupport.prototype.rebuild_index(e);
				}

			);

		 }
						
ajax_call(e);
		
	},
								  
	remove_button : function(e) 
				{
					'use strict';
					
					jQuery(e.this).closest('.fieldset_wrapper').remove(); 
					
					var i = InputSupport.prototype.get_set_count(e.parname);
					
					var html = jQuery('<div>').html( e.js_add_link ).text();
					
					if( e.max_number !== 0 && i < e.max_number  )
					{ 
						if( jQuery('#' + e.id + '_add_new').length === 0 )
						{ 
							jQuery('#' + e.id + '_add').before(html);
						}
					}
					InputSupport.prototype.rebuild_index(e);
					
					i = InputSupport.prototype.get_set_count(e.parname);
					for(var x = 0; x < i; x++)
					{
						if($('#updating-'+x).length > 0 ){continue;}
						html = jQuery.parseHTML( '<span id="updating-'+x+'" class="text-success" style="margin-left:15px;">Updated...</span>' );
						jQuery( '#'+e.parname + '_'+x+'_title' ).after(html);
						$('#updating-'+x).fadeTo(2000, 500).slideUp(500, function(){$('#updating-'+x).slideUp(500).remove();});	
					}
			},
								  
		rebuild_index: function(e)
					{
						'use strict';
					 
							var i = InputSupport.prototype.get_set_count(e.parname) - 1;
							jQuery('#' + e.parname + '_add').prevAll('.fieldset_wrapper').each(function()
							{
								var id = this.id, p = id.indexOf( e.parname + '_');
								id = id.substr(0, p) + e.parname + '_'+i+ id.substr(p+(e.parname + '_'+i).length);
								this.id = id;
								jQuery(this).find('*').each(function()
								{		
									if( this.id.length > 0 )
									{
										id = this.id;
										p = id.indexOf(e.parname + '_');
										f = id.substr(p+(e.parname + '_').length).search(/[a-zA-Z\_]/);
										if( f > 0 && $.isNumeric(id.substr(p+(e.parname + '_').length, f )) )
										{
											id = id.substr(0, p) + e.parname + '_'+i+ id.substr(p+(e.parname + '_'+i).length);
											this.id = id;	
										}
									}
									if( typeof this.name !== 'undefined' )
									{
										if( this.name.length > 0 )
										{	
											id = this.name;
											p = id.indexOf(e.parname + '_');
											f = id.substr(p+(e.parname + '_').length).search(/[a-zA-Z\_]/);
											if( f > 0 && $.isNumeric(id.substr(p+(e.parname + '_').length, f )) )
											{
												id = id.substr(0, p) + e.parname + '_'+i+ id.substr(p+(e.parname + '_'+i).length);
												this.name = id;	
											}
										}
									}						
									var id = this.id, p = id.indexOf(e.parname + '_'), f = id.substr(p+(e.parname + '_').length).search(/[a-zA-Z\_]/);
									var v = id.substr(p+(e.parname + '_').length, f );
									if( this.id.length > 0 && this.id === e.parname + '_'+v+'_title' ) 
									{ 
										this.innerHTML = this.innerHTML.replace(/\d+/,v);
									}
								});
								i--;
							});
					 
					$( '.remove_'+e.id ).each(function() {
						$(this).on( "click",function(c){
							e.this = this;
							 InputSupport.prototype.remove_button(e);
							
							c.stopPropagation();
							c.preventDefault();

							// This does the trick:
							c.stopImmediatePropagation();
						});
					});		
					 
						 jQuery('#add_' + e.id).on( "click",function(f){

							 InputSupport.prototype.add(e);
								f.stopPropagation();
								f.preventDefault();

								// This does the trick:
								f.stopImmediatePropagation();
							});
					 
						},
	get_set_count: function(parname){'use strict';return jQuery('#' + parname + '_add').prevAll('.fieldset_wrapper').length;}
});