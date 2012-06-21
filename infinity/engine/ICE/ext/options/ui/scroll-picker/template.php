<?php
/**
 * ICE API: option extensions, ui scrollpane template file
 *
 * @author Marshall Sorenson <marshall@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Marshall Sorenson
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package ICE-extensions
 * @subpackage options
 * @since 1.0
 */

/* @var $this ICE_Option_Renderer */
?>
<div id="<?php $this->render_id('widget') ?>" class="<?php $this->render_class('widget') ?> ui-corner-all">
	<div class="ice-value ui-widget-header ui-corner-all">
		<div class="ui-widget-header ui-corner-bottom">
			<?php _e( 'Current Selection', infinity_text_domain ) ?>
		</div>
	</div>
	<div class="ice-vienxtort">
		<?php foreach( $field_options as $field_option_val => $field_option_desc ): ?>
			<a id="<?php $this->render_id('widget','item',$field_option_val) ?>" class="ice-item ui-widget-header" href="#<?php print esc_attr( $field_option_val ) ?>"><?php $this->component()->render_field_option( $field_option_val ) ?></a>
		<?php endforeach; ?>
	</div>
	<div class="ice-wrapper ui-corner-br">
		<div class="ice-controls"></div>
	</div>
	<?php $this->render_input( 'hidden' ); ?>
</div>

<script type="text/javascript">
	(function($){
		// the input
		var input = $('input[name=<?php $this->render_name() ?>]');
		// options from component
		var options = <?php print $scroll_options ?>;
		// add application options
		options.valueSelector = 'div.ice-value';
		options.viewSelector = 'div.ice-vienxtort';
		options.itemSelector = 'a.ice-item';
		options.barWrapSelector = 'div.ice-wrapper';
		options.barSelector = 'div.ice-controls';
		// add selection options
		<?php if ( !is_null( $value ) ): ?>
		options.itemActiveSelector = 'a#<?php $this->render_id('widget','item',$value) ?>';
		<?php endif; ?>
		options.itemEvents = {
			click: function(event){
				var target = $(event.target),
					anchor = (target.is('a')) ? target: target.parent('a');
				input.val(anchor.prop('hash').substr(1));
				return false;
			}
		};
		// add scroll pane
		jQuery('div#<?php $this->render_id('widget') ?>').iceEasyScrollPane(options);
	})(jQuery);
</script>