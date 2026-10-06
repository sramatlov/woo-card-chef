<?php
/**
 * Custom label stack shared by the card's corner slots and body.
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $position_labels ) ) {
	return;
}
?>
<div class="wc-card__labels wc-card__labels--<?php echo esc_attr( $custom_position ); ?>" role="group" aria-label="<?php esc_attr_e( 'Productlabels', 'woo-card-chef' ); ?>">
	<?php foreach ( $position_labels as $custom_label ) : ?>
		<?php $custom_label_style = '--wcpce-label-bg:' . $custom_label['color'] . ';--wcpce-label-color:' . $custom_label['text_color'] . ';'; ?>
		<span class="wc-card__custom-label" style="<?php echo esc_attr( $custom_label_style ); ?>"><?php echo esc_html( $custom_label['text'] ); ?></span>
	<?php endforeach; ?>
</div>
