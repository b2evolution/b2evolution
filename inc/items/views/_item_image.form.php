
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
global $link_ID, $tag_type, $Plugins;

$tag_types = array(
	'image'     => T_('Image'),
	'thumbnail' => T_('Thumbnail'),
	'inline'    => T_('Basic inline'),
);

// Get additional tabs from active Plugins:
$plugins_tabs = $Plugins->trigger_collect( 'GetImageInlineTags', array(
	'link_ID' => $link_ID,
	'active_tag' => $tag_type
) );
foreach( $plugins_tabs as $plugin_ID => $plugin_tabs )
{
	$tag_types = array_merge( $tag_types, $plugin_tabs );
}

?>
<div class="container-fluid">
	<?php if( ! $restrict_tag ): ?>
	<ul class="nav nav-tabs">
		<?php foreach( $tag_types as $tag_type_key => $tag_type_title ): ?>
		<li role="presentation"<?php echo $tag_type == $tag_type_key ? ' class="active"' : '' ;?>><a href="#<?php echo $tag_type_key; ?>" role="tab" data-toggle="tab"><?php echo $tag_type_title; ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
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
			foreach( $tag_types as $tag_type_key => $tag_type_title )
			{
				echo '<div id="'.$tag_type_key.'" class="tab-pane'.( $tag_type == $tag_type_key ? ' active' : '' ).'">';
				switch( $tag_type_key )
				{
					case 'image':
						$Form->text( 'image_caption', get_param( 'image_caption' ), 40, T_('Caption'), '<br>
							<span style="display: flex; flex-flow: row; align-items: center; margin-top: 8px;">
								<input type="checkbox" name="image_disable_caption" id="image_disable_caption" value="1" style="margin: 0 8px 0 0;"'.( get_param( 'image_disable_caption' ) ? ' checked="checked"' : '' ).'>
								<span>'.T_('Disable caption').'</span></span>', '' );
						// TODO: Alt text:
						$Form->text( 'image_href', get_param( 'image_href' ), 40, T_('HRef') );
						// TODO: Size:
						$image_class = get_param( 'image_class' );
						$Form->text( 'image_class', $image_class, 40, T_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
							<button class="btn btn-default btn-xs">border</button>
							<button class="btn btn-default btn-xs">noborder</button>
							<button class="btn btn-default btn-xs">rounded</button>
							<button class="btn btn-default btn-xs">squared</button></div>', '' );
						break;

					case 'thumbnail':
						// TODO: Alt text:
						$Form->text( 'thumbnail_href', get_param( 'thumbnail_href' ), 40, T_('HRef') );
						$Form->radio( 'thumbnail_size', get_param( 'thumbnail_size' ), array(
								array( 'small', 'small' ),
								array( 'medium', 'medium' ),
								array( 'large', 'large' )
							), T_( 'Size') );
						$Form->radio( 'thumbnail_alignment', get_param( 'thumbnail_alignment' ), array(
								array( 'left', 'left' ),
								array( 'right', 'right' )
							), T_( 'Alignment') );
						$thumbnail_class = get_param( 'thumbnail_class' );
						$Form->text( 'thumbnail_class', get_param( 'thumbnail_class' ), 40, T_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
							<button class="btn btn-default btn-xs">border</button>
							<button class="btn btn-default btn-xs">noborder</button>
							<button class="btn btn-default btn-xs">rounded</button>
							<button class="btn btn-default btn-xs">squared</button></div>', '' );
						break;

					case 'inline':
						$inline_class = get_param( 'inline_class' );
						$Form->text( 'inline_class', get_param( 'inline_class' ), 40, T_('Styles'), '<br><div class="style_buttons" style="margin-top: 8px;">
								<button class="btn btn-default btn-xs">border</button>
								<button class="btn btn-default btn-xs">noborder</button>
								<button class="btn btn-default btn-xs">rounded</button>
								<button class="btn btn-default btn-xs">squared</button></div>', '' );
						break;

					default:
						// Display additional inline tag form from active plugins:
						$Plugins->trigger_event( 'DisplayImageInlineTagForm', array(
								'link_ID'     => $link_ID,
								'active_tag'  => $tag_type,
								'display_tag' => $tag_type_key,
								'Form'        => $Form,
							) );
				}
				echo '</div>';
			}
			echo '</div>';

			$Form->submit( array( 'value' => 'Insert', 'onclick' => 'return evo_image_submit()' ) );

			$Form->end_fieldset();
			$Form->end_form();
			?>

			<script>
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

				jQuery( 'input[name$="_disable_caption"]' ).click( function() {
						var active_tag_type = jQuery( '.tab-content .tab-pane.active' ).attr( 'id' );
						jQuery( 'input[name="' + active_tag_type + '_caption"]' ).prop( 'disabled', jQuery( this ).is( ':checked' ) );
					});

				// Update preview on tab change
				jQuery( 'a[data-toggle="tab"]' ).on( 'shown.bs.tab', function( e ) {
					var target = jQuery( e.target ).attr( 'href' );
					apply_image_class( jQuery( 'div' + target + ' input[name$="_class"]' ).val() );
				} );

				<?php
				// Apply existing classes
				if( ! empty( $image_class ) )
				{
					echo 'apply_image_class( "'.$image_class.'" );';
				}
				if( ! empty( $thumbnail_class ) )
				{
					echo 'apply_image_class( "'.$thumbnail_class.'" );';
				}
				if( ! empty( $inline_class ) )
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
				var href = jQuery( 'input[name="' + tagType + '_href"]' ).val();
				var classes = jQuery( 'input[name="' + tagType + '_class"]' ).val();
				var tag_caption = false;

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

				if( href && href.match( /^(https?:\/\/.+|\(\((.*?)\)\))$/i ) )
				{
					options += ( options == '' ? '' : ':' ) + href;
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
				<?php
				// Display additional JavaScript code from plugins before submit/insert inline tag:
				$plugins_javascript = $Plugins->trigger_collect( 'GetInsertImageInlineTagJavaScript', array( 'link_ID' => $link_ID ) );
				foreach( $plugins_javascript as $plugin_ID => $plugin_javascript )
				{
					echo "\n\n".'// START JS from plugin #'.$plugin_ID.':'."\n";
					echo $plugin_javascript;
					echo "\n".'// END JS from plugin #'.$plugin_ID.'.'."\n\n";
				}
				?>
				window.parent.evo_link_insert_inline( tagType, linkID, options, <?php echo $replace;?>, tag_caption, <?php echo param( 'prefix', 'string' ); ?>b2evoCanvas );

				closeModalWindow();

				return false;
			}
			</script>
		</div>
	</div>
</div>