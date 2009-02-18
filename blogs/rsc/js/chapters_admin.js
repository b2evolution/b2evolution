/**
 * Chapter Admin functions
 *
 * This file implements the chapters javascript interface
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package main
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author yabs {@link http://innervisions.org.uk/ }
 *
 * @version $Id$
 */
var _b2evoChapters = function(){

	var me; // reference to self

	var _chapters = Array(); // array of all chapters

	var _key; // holds our key for updates

	return {
		/**
		 * Initialise admin functions
		 */
		Init: function(){
			// set available params to defaults
			var params = jQuery.fn.extend({
					// no comma after final entry or IE barfs
					key: '' // key for our server calls
					}, ( arguments.length ? arguments[0] : '' ) );
			// store reference to self
			me = this;
			_key = params.key; // store our key

			// basic check to see if we have perms to edit
			if( jQuery('#chapter_list .action_icon' ).html() )
			{ // we have action icons
				// convert current chapters
				jQuery( '#chapter_list tr' ).each( function(i){ // step through each row
					if( jQuery( this ).find( 'td:nth-child(3) a' ).html() )
					{ // this is a chapter entry
						var ID = jQuery( this ).attr('id').replace(/[^0-9]/g, '' );
						var parent_ID = jQuery( this ).attr('className').replace(/^.*chapter_parent_([0-9]*).*$/, '$1' );
						var title = jQuery( this ).find( 'td:nth-child(3) a' ).html();
						var urlslug = jQuery( this ).find( 'td:nth-child(4)' ).html();
						var actions = jQuery( this ).find( 'td:nth-child(5)' ).html();
						me.AddChapter({
							group:'chapter_list',
							ID: ID,
							parent_ID:parent_ID,
							title: title,
							urlslug: urlslug,
							actions:actions
						})
					}
				});

				// replace current table with ourselves
				var new_list = jQuery( '<ul id="chapter_list" class="grouped"></ul>' );
				for( chapter in _chapters['chapter_list'][0]['children'] )
				{ // recursively step through all chapters in root
					new_list = me.ChapterLine( new_list, chapter );
				}
				jQuery( '#chapter_list' ).replaceWith( new_list );

				// add droppabable areas for sub categories
				jQuery( '<li class="subchapters"><div>'+b2evoHelper.T_( 'New Main category' )+'</div></li>').appendTo( jQuery( '#chapter_list' ) );
				jQuery( '<li class="subchapters"><div>'+b2evoHelper.T_( 'New %s sub category' ).replace(/%s/, '<span class="chapter_parent_name">&nbsp;</span>' )+'</div></li>').appendTo( jQuery( '#chapter_list ul' ) );

				// store current blog
				var blog_ID = jQuery( "[href*='action=make_default']" ).attr( 'href' ).replace( /^.*blog=([0-9]*).*$/, '$1' );
				jQuery( me ).data( 'blog', blog_ID );
				b2evoHelper.log ( 'Blog : '+blog_ID );

				// initialise the new layout
				me.ReInitChapters();
				// save initial order
				me.GetChapterOrder({group:'current'});
			}
		}, // Init


		/**
		 * Recursively build the initial chapter lists
		 */
		ChapterLine:function( chapters, chapter ){
			var current_chapter = _chapters['chapter_list'][chapter];
			var new_chapter = jQuery( '<li id="chapter_'+current_chapter['ID']+'"><div class="draggable_object chapter_'+current_chapter['ID']+'"><span class="actions">'+current_chapter['actions']+'</span><span class="chapter_title">('+current_chapter['ID']+') <strong>'+current_chapter['title']+'</strong> - '+current_chapter['urlslug']+'</span></div></li>' );
			var children = jQuery( '<ul id="chapter_parent_'+current_chapter['ID']+'"></ul>' );
			if( current_chapter['children' ].length )
			{ // recurse
				for( child in current_chapter['children'] )
				{ // step through children
					children = me.ChapterLine( children , child );
				}
			}
			children.appendTo( new_chapter ); // add any children
			new_chapter.appendTo( chapters ); // add chapter entry
			return chapters; // return entry
		}, // ChapterLine


		/**
		 * Resets odd / even classes
		 * Redo drag and drop functionality
		 */
		ReInitChapters:function(){
			jQuery( '#chapter_list li' ).droppable('destroy' );
			jQuery( '#chapter_list li' ).draggable('destroy' );
			jQuery( '#chapter_list li' ).droppable({
				accept: ".draggable_chapter", // classname of objects that can be dropped
				hoverClass: "droppable-hover", // classname when object is over this one
				greedy: true, // stops propogation if over more than one
				tolerance : "pointer", // droppable active when cursor over
				delay: 100,
				drop: function(ev, ui) {	// function called when object dropped
					jQuery( ui.draggable ).insertBefore( this ); // add the dragged category(ies) after this category
					jQuery( ui.draggable ).animate({
							backgroundColor: "#ffff44"
						},"fast" ).animate({
							backgroundColor: "#ffffff"
						},"fast", "", function(){
							jQuery( this ).removeAttr( "style" );
							jQuery( this ).removeClass( 'odd even' ).addClass( 'unsaved_change' );
							jQuery( this ).find( '.odd' ).addClass( 'unsaved_change' );
							jQuery( this ).find( '.even' ).addClass( 'unsaved_change' );
							b2evoCommunications.BufferedServerCall({
								ticker_callback : me.CheckChapterOrder,
								send_callback: me.SaveChapterOrder,
								buffer_name:'chapter_reorder'
							})
							me.ReInitChapters(); // redo odd/even etc
					});
				}
			}).addClass( "draggable_chapter" ); // add our css class
			jQuery( '.subchapters' ).removeClass( 'draggable_chapter' ); // remove class from sub category placeholders
			jQuery( '.draggable_chapter' ).each( function(){ // add draggable to relevent li's
				jQuery( this ).draggable({
					helper: "clone", // use a copy of the object
					scroll: true, // scroll the window during dragging
					scrollSensitivity: 100, // distance from edge before scoll occurs
					zIndex: 999, // z-index whilst dragging
					opacity: .8, // opacity whilst dragging
					handle: jQuery( '.'+jQuery( this ).attr( 'id' ) ), // cures all sorts of funky behaviour
					cursor: "pointer" // change the cursor whilst dragging
				});
			});
			jQuery( '#chapter_list li:odd' ).removeClass( 'even' ).addClass('odd' );
			jQuery( '#chapter_list li:even' ).removeClass( 'odd' ).addClass('even' );
			jQuery( '.unsaved_change' ).removeClass( 'odd even' );
			jQuery( '.subchapters .chapter_parent_name' ).each( function(){
				if( parent_ID = jQuery( this ).parent().parent().parent().attr('id').replace( /[^0-9]/g, '' ) )
				{ // we have a parent
					jQuery( this ).html( '&laquo;'+_chapters['chapter_list'][parent_ID]['title']+'&raquo;' );
				}
			});
		}, // ReInitChapters


		/**
		 * Adds a chapters details to the relevant list
		 */
		AddChapter:function(){
			// set available params to defaults
			var params = jQuery.fn.extend({
					// no comma after final entry or IE barfs
					group: 'current', // add chapter to which group ?
					ID: 0,
					parent_ID: 0,
					title: '',
					urlslug: '',
					actions: ''
					}, ( arguments.length ? arguments[0] : '' ) );
			if( typeof( _chapters[params.group] ) != 'object' )
			{ // new group
				b2evoHelper.log( 'new group : '+params.group );
				_chapters[params.group] = Array();
			}

			if( typeof( _chapters[params.group][params.parent_ID] ) != 'object' )
			{	// new parent
				b2evoHelper.log( 'new parent : '+params.parent_ID );
				_chapters[params.group][params.parent_ID] = Array();
				_chapters[params.group][params.parent_ID]['children'] = Array();
			}

			_chapters[params.group][params.ID] = params;
			_chapters[params.group][params.ID]['children'] = Array();
			_chapters[params.group][params.parent_ID]['children'][params.ID] = params.ID;
			b2evoHelper.log( 'new child : '+params.ID );
		}, // AddChapter


		/**
		 * Get the Chapter order
		 */
		GetChapterOrder:function(){
			// set available params to defaults
			var params = jQuery.fn.extend({
					// no comma after final entry or IE barfs
					group: 'current' // set chapters for which group ?
					}, ( arguments.length ? arguments[0] : '' ) );
			_chapters[ params.group ] = Array(); // reset the group
			jQuery( '#chapter_list .draggable_chapter' ).each( function(){
				var parent_ID = jQuery( this ).parent().attr('id' ).replace(/[^0-9]/g, '' ).toString();
				var chapter_ID = jQuery( this ).attr( 'id' ).replace( /[^0-9]/g, '' ).toString();
				_chapters[ params.group ].push( ( parent_ID ? parent_ID : 0 )+'_'+chapter_ID );
			});
			b2evoHelper.log( 'Get Chapter ( '+params.group+' ) : '+_chapters[params.group ] );
		}, // GetChapterOrder


		/**
		 * Callback for buffered save
		 * Checks if chapter order still needs saving
		 */
		CheckChapterOrder:function( when ){
			// needs to compare against last saved order
			// get current order
			me.GetChapterOrder({group:'unsaved'});
			if( typeof( _chapters['saving'] ) == 'object' && _chapters['saving'].toString() )
			{ // we have categories that are being saved
				if( _chapters['saving'].toString() == _chapters['unsaved'].toString() )
				{ // categories are unchanged
					jQuery( '.unsaved_change' ).removeClass( 'unsaved_change' );
					return 'cancel';
				}
				else
				{
					return 'pause';
				}
			}
			else if( _chapters['current'].toString() == _chapters['unsaved'].toString() )
			{ // no changes to save
				jQuery( '.unsaved_change' ).removeClass( 'unsaved_change' );
				return 'cancel';
			}
			return true;
		}, // CheckChapterOrder


		/**
		 * Callback for buffered save
		 * Saves the current chapter order
		 */
		SaveChapterOrder:function()
		{
			me.GetChapterOrder({ group: 'saving' });
			jQuery( '.unsaved_change' ).removeClass('unsaved_change' ).addClass( 'saving_change' );
		} // SaveChapterOrder
	}
} // _b2evoChapters

var b2evoChapters = new _b2evoChapters();

/*
 * $Log$
 * Revision 1.1  2009/02/18 10:48:59  yabs
 * Start of category admin
 *
 *
 */
