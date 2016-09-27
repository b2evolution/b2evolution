
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
global $link_ID, $tag_type, $callback;
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
		<div style="display: flex; align-items: center; min-height: 192px;">
		<?php echo $File->get_thumb_imgtag( 'fit-192x192' ); ?>
		</div>
		<div>
			<?php
			$Form = new Form( NULL, 'form' );
			$Form->begin_form( 'fform' );
			$Form->hidden( 'link_ID', $link_ID );
			$Form->begin_fieldset( T_('Parameters') );
			echo '<div class="tab-content">';
				echo '<div id="image" class="tab-pane'.($tag_type == 'image' ? ' active' : '' ).'">';
				$Form->text( 'image_caption', $image_caption, 40, T_('Caption') );
				$Form->checkbox( 'image_disable_caption', $image_disable_caption, '', T_('Disable caption') );
				$Form->text( 'image_class', $image_class, 40, T_('Styles') );
				$Form->info( '',
						'<button class="btn btn-default btn-sm">border</button>
						<button class="btn btn-default btn-sm">noborder</button>
						<button class="btn btn-default btn-sm">rounded</button>
						<button class="btn btn-default btn-sm">squared</button>' );
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
				$Form->text( 'thumbnail_class', $thumbnail_class, 40, T_('Styles') );
				$Form->info( '', '<button class="btn btn-default btn-sm">border</button>
						<button class="btn btn-default btn-sm">noborder</button>
						<button class="btn btn-default btn-sm">rounded</button>
						<button class="btn btn-default btn-sm">squared</button>' );
				echo '</div>';

				echo '<div id="inline" class="tab-pane'.($tag_type == 'inline' ? ' active' : '' ).'">';
				$Form->text( 'inline_class', $inline_class, 40, T_('Styles') );
				$Form->info( '', '<button class="btn btn-default btn-sm">border</button>
						<button class="btn btn-default btn-sm">noborder</button>
						<button class="btn btn-default btn-sm">rounded</button>
						<button class="btn btn-default btn-sm">squared</button>' );
				echo '</div>';
			echo '</div>';

			$Form->submit( array( 'value' => 'Insert', 'onclick' => 'return evo_image_submit( \''.$callback.'\', window.parent );' ) );

			$Form->end_fieldset();
			$Form->end_form();
			?>

			<script type="text/javascript">
			jQuery( document ).ready( function() {
				var tagType = jQuery( '.tab-content .tab-pane.active' ).attr( 'id' );

				jQuery( 'div.tab-pane button.btn-sm' ).click( function() {
					return evo_image_add_class( this, jQuery( this ).text() );
				} );

				jQuery( 'input[name="' + tagType + '_disable_caption"]' ).click( function() {
						var checkbox = jQuery( this );
						jQuery( 'input[name="' + tagType + '_caption"]' ).prop( 'disabled', checkbox.is( ':checked' ) );
					})
			} );

			function evo_image_add_class( event_obj, className )
			{
				var input = jQuery( "input[name$='_class']", jQuery( event_obj ).closest( "div.tab-pane" ) );
				var styles = input.val();

				styles = styles.split( '.' );
				if( styles.indexOf( className ) == -1 )
				{
					styles.push( className );
				}

				input.val( styles.join( '.' ) );
				return false;
			}

			function evo_image_submit( callback, context )
			{
				if( !context )
				{
					context = window.parent;
				}

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

				var shortTag = '[';
				shortTag += tagType + ':' + linkID;

				if( tagType == 'image' )
				{
					if( noCaption )
					{
						shortTag += ':-';
					}
					else
					{
						shortTag += ':' + caption;
					}
				}

				if( tagType == 'thumbnail' )
				{
					shortTag += ':' + size;
					shortTag += ':' + alignment;
				}

				if( classes )
				{
					shortTag += ':' + classes;
				}

				shortTag += ']';

				if( callback )
				{
					callback = context[callback];
					callback( shortTag );
				}

				closeModalWindow();

				return false;
			}
			</script>
		</div>
	</div>
</div>