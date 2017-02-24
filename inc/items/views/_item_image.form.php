
<?php
/**
 * This file displays the links attached to an Object, which can be an Item, Comment, ... (called within the attachment_frame)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
global $retrict_tag;
global $link_ID, $tag_type;
global $image_caption, $image_disable_caption, $image_class;
global $thumbnail_size, $thumbnail_alignment, $thumbnail_class;
global $inline_class;

?>
<div class="container-fluid">
	<?php	if( ! $restrict_tag ):	?>
	<ul class="nav nav-tabs">
		<li role="presentation" class="evo_widget widget_core_menu_link<?php echo $tag_type == 'image' ? ' active' : '' ;?>"><a href="#image" role="tab" data-toggle="tab">Image</a></li>
		<li role="presentation" class="evo_widget widget_core_menu_link<?php echo $tag_type == 'thumbnail' ? ' active' : '' ;?>"><a href="#thumbnail" role="tab" data-toggle="tab">Thumbnail</a></li>
		<li role="presentation" class="evo_widget widget_core_menu_link<?php echo $tag_type == 'inline' ? ' active' : '' ;?>"><a href="#inline" role="tab" data-toggle="tab">Inline</a></li>
	</ul>
	<?php endif;?>
	<div style="margin-top: 20px; display: flex; flex-flow: row nowrap; align-items: flex-start;">
		<div id="image_preview" style="display: flex; align-items: center; min-height: 192px; margin-right: 10px;">
		<?php echo $File->get_thumb_imgtag( 'fit-192x192' ); ?>
		</div>
		<div style="flex-grow: 1">
			<?php
			$Form = new Form( NULL, 'form' );
			$Form->begin_form( 'fform' );
			$Form->hidden( 'link_ID', $link_ID );
			$Form->begin_fieldset( T_('Parameters') );
			echo '<div class="tab-content">';
				echo '<div id="image" class="tab-pane'.($tag_type == 'image' ? ' active' : '' ).'">';
				$Form->text( 'image_caption', $image_caption, 40, T_('Caption'), '<br>
						<span style="display: flex; flex-flow: row; align-items: center; margin-top: 8px;">
							<input type="checkbox" name="image_disable_caption" id="image_disable_caption" value="1" style="margin: 0 8px 0 0;"><span>'.T_('Disable caption').'</span></span>' );
				$Form->text( 'image_class', $image_class, 40, T_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
						<button class="btn btn-default btn-xs">border</button>
						<button class="btn btn-default btn-xs">noborder</button>
						<button class="btn btn-default btn-xs">rounded</button>
						<button class="btn btn-default btn-xs">squared</button></div>' );
				echo '</div>';

				echo '<div id="thumbnail" class="tab-pane'.($tag_type == 'thumbnail' ? ' active' : '' ).'">';
				$Form->radio( 'thumbnail_size', $thumbnail_size, array(
						array( 'small', 'small' ),
						array( 'medium', 'medium' ),
						array( 'large', 'large' )
					), T_( 'Size') );
				$Form->radio( 'thumbnail_alignment', $thumbnail_alignment, array(
						array( 'left', 'left' ),
						array( 'right', 'right' )
					), T_( 'Alignment') );
				$Form->text( 'thumbnail_class', $thumbnail_class, 40, T_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
						<button class="btn btn-default btn-xs">border</button>
						<button class="btn btn-default btn-xs">noborder</button>
						<button class="btn btn-default btn-xs">rounded</button>
						<button class="btn btn-default btn-xs">squared</button></div>' );
				echo '</div>';

				echo '<div id="inline" class="tab-pane'.($tag_type == 'inline' ? ' active' : '' ).'">';
				$Form->text( 'inline_class', $inline_class, 40, T_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
						<button class="btn btn-default btn-xs">border</button>
						<button class="btn btn-default btn-xs">noborder</button>
						<button class="btn btn-default btn-xs">rounded</button>
						<button class="btn btn-default btn-xs">squared</button></div>' );
				echo '</div>';
			echo '</div>';

			$Form->submit( array( 'value' => 'Insert', 'onclick' => 'return evo_image_submit();' ) );

			$Form->end_fieldset();
			$Form->end_form();
			?>

			<script type="text/javascript">
			jQuery( document ).ready( function() {
				var img = jQuery( "#image_preview img" );
				var tagType = jQuery( '.tab-content .tab-pane.active' ).attr( 'id' );

				// Add class to text input
				jQuery( 'div.tab-pane div.style_buttons button' ).click( function() {
					return evo_image_add_class( this, jQuery( this ).text() );
				} );

				// Apply class to preview image
				jQuery( "input[name$='_class']" ).on( 'change keydown', debounce( function() {
					apply_image_class( jQuery( this ).val() );
				}, 200 ) );

				jQuery( 'input[name="' + tagType + '_disable_caption"]' ).click( function() {
						var checkbox = jQuery( this );
						jQuery( 'input[name="' + tagType + '_caption"]' ).prop( 'disabled', checkbox.is( ':checked' ) );
					});

				// Update preview on tab change
				jQuery( 'a[data-toggle="tab"]' ).on( 'shown.bs.tab', function( e ) {
					var target = jQuery( e.target ).attr( 'href' );
					apply_image_class( jQuery( 'div' + target + ' input[name$="_class"]' ).val() );
					console.log( 'Applying style...' );
				} );

				<?php
				// Apply existing classes
				if( $image_class )
				{
					echo 'apply_image_class( "'.$image_class.'" );';
				}
				if( $thumbnail_class )
				{
					echo 'apply_image_class( "'.$thumbnail_class.'" );';
				}
				if( $inline_class )
				{
					echo 'apply_image_class( "'.$inline_class.'" );';
				}
				?>
			} );

			function apply_image_class( imageClasses )
			{
				var img = jQuery( "#image_preview img" );

				imageClasses = imageClasses.split( '.' );
				img.removeClass();
				for( var i = 0; i < imageClasses.length; i++ )
				{
					img.addClass( imageClasses[i] );
				}
			}

			function evo_image_add_class( event_obj, className )
			{
				var input = jQuery( "input[name$='_class']", jQuery( event_obj ).closest( "div.tab-pane" ) );
				var img = jQuery( "#image_preview img" );
				var styles = input.val();

				styles = styles.split( '.' );
				if( styles.indexOf( className ) == -1 )
				{
					styles.push( className );
				}

				input.val( styles.join( '.' ) );
				apply_image_class( input.val() );
				return false;
			}

			function evo_image_submit()
			{
				// Get active tab pane
				var tagType = jQuery( '.tab-content .tab-pane.active' ).attr( 'id' );
				var linkID = jQuery( 'input[name="link_ID"]' ).val();
				if( tagType == 'image' )
				{
					var caption = jQuery( 'input[name="' + tagType + '_caption"]' ).val();
					var noCaption = jQuery( 'input[name="' + tagType + '_disable_caption"]' ).is( ':checked' );
				}
				if( tagType == 'thumbnail' )
				{
					var alignment = jQuery( 'input[name="' + tagType + '_alignment"]:checked' ).val();
					var size = jQuery( 'input[name="' + tagType + '_size"]:checked' ).val();
				}
				var classes = jQuery( 'input[name="' + tagType + '_class"]' ).val();

				var options = '';

				if( tagType == 'image' )
				{
					if( noCaption )
					{
						options += '-';
					}
					else
					{
						options += caption;
					}
				}

				if( tagType == 'thumbnail' )
				{
					options += ( options == '' ? '' : ':' ) + size;
					options += ':' + alignment;
				}

				if( classes )
				{
					if( tagType == 'image' )
					{
						options += ':' + classes;
					}
					else
					{
						options += ( options == '' ? '' : ':' ) + classes;
					}
				}

				window.parent.evo_link_insert_inline( tagType, linkID, options, <?php echo $replace;?> );

				closeModalWindow();

				return false;
			}
			</script>
		</div>
	</div>
</div>