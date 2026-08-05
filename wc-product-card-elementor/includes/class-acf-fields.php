<?php
/**
 * Registers ACF fields used by the product card and gallery widgets.
 *
 * @package WC_Product_Card_Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Programmatically registers all ACF fields on the product post type.
 *
 * We register via PHP rather than JSON sync because:
 * - Fields exist as soon as the plugin activates, no admin clicks required.
 * - Clients cannot accidentally edit or delete the field group from the ACF UI.
 * - The fields are scoped to this plugin and travel with it on staging/prod sync.
 *
 * Five field groups are registered:
 * - group_wcpce_card_title    : optional short card title + hover image (card widget)
 * - group_wcpce_product_usps  : up to three short product USPs (card widget)
 * - group_wcpce_product_badges: product badge toggles — Nieuw, PFAS-vrij, Niet meer leverbaar
 * - group_wcpce_pdp_accordion : optional product manual file for the PDP Accordion widget
 * - group_wcpce_pdp_usps      : PDP USP repeater for the Product USP / Benefits widget (ACF Pro required)
 * - group_wcpce_pdp_gallery_media: YouTube video repeater for the PDP Gallery widget (ACF Pro required)
 *
 * @since 1.0.0
 */
final class WC_Product_Card_Elementor_ACF_Fields {

	/**
	 * Registers all ACF field groups.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// Field group 1: Short card title and hover image override.
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wcpce_card_title',
				'title'                 => __( 'Product Card Title', 'woo-card-chef' ),
				'fields'                => array(
					array(
						'key'          => 'field_wcpce_card_title',
						'label'        => __( 'Korte titel voor productkaart', 'woo-card-chef' ),
						'name'         => 'card_title',
						'type'         => 'text',
						'instructions' => __( 'Optionele korte titel voor categoriepagina\'s en productkaarten. Als dit veld leeg is, wordt de normale producttitel gebruikt. Voorbeeld: "Kitchen Chef Plus 6,2L"', 'woo-card-chef' ),
						'required'     => 0,
						'maxlength'    => 80,
						'placeholder'  => __( 'Leeglaten om de normale producttitel te gebruiken', 'woo-card-chef' ),
					),
					array(
						'key'           => 'field_wcpce_card_hover_image',
						'label'         => __( 'Hover-afbeelding voor productkaart', 'woo-card-chef' ),
						'name'          => 'card_hover_image',
						'type'          => 'image',
						'instructions'  => __( 'Optionele afbeelding die verschijnt als de bezoeker over de productkaart hovert. Als dit veld leeg is, wordt de eerste galerij-afbeelding van WooCommerce gebruikt. Alleen zichtbaar als "Hover image swap" aanstaat in de Elementor widget.', 'woo-card-chef' ),
						'required'      => 0,
						'return_format' => 'id',
						'preview_size'  => 'thumbnail',
						'library'       => 'all',
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'product',
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => __( 'Optionele verkorte producttitel die alleen op productkaarten en categoriepagina\'s wordt getoond.', 'woo-card-chef' ),
			)
		);

		// Field group 2: USPs for product cards.
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wcpce_product_usps',
				'title'                 => __( 'Product Card USPs', 'woo-card-chef' ),
				'fields'                => array(
					array(
						'key'           => 'field_wcpce_usp_1',
						'label'         => __( 'USP 1', 'woo-card-chef' ),
						'name'          => 'usp_1',
						'type'          => 'text',
						'instructions'  => __( 'First selling point shown on the product card. Keep it short and product-specific. Example: "Geschikt voor 4 personen".', 'woo-card-chef' ),
						'required'      => 0,
						'maxlength'     => 60,
					),
					array(
						'key'           => 'field_wcpce_usp_2',
						'label'         => __( 'USP 2', 'woo-card-chef' ),
						'name'          => 'usp_2',
						'type'          => 'text',
						'instructions'  => __( 'Second selling point. Optional.', 'woo-card-chef' ),
						'required'      => 0,
						'maxlength'     => 60,
					),
					array(
						'key'           => 'field_wcpce_usp_3',
						'label'         => __( 'USP 3', 'woo-card-chef' ),
						'name'          => 'usp_3',
						'type'          => 'text',
						'instructions'  => __( 'Third selling point. Optional.', 'woo-card-chef' ),
						'required'      => 0,
						'maxlength'     => 60,
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'product',
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => __( 'Three short USPs displayed on the custom product card on category pages.', 'woo-card-chef' ),
			)
		);

		// Field group 3: Product card badges.
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wcpce_product_badges',
				'title'                 => __( 'Product Card Badges', 'woo-card-chef' ),
				'fields'                => array(
					array(
						'key'           => 'field_wcpce_badge_nieuw',
						'label'         => __( 'Nieuw', 'woo-card-chef' ),
						'name'          => 'badge_nieuw',
						'type'          => 'true_false',
						'instructions'  => __( 'Toont een rode "Nieuw" badge linksboven op de productkaart. Verwijder deze vinkje wanneer het product niet meer nieuw is.', 'woo-card-chef' ),
						'required'      => 0,
						'default_value' => 0,
						'ui'            => 1,
						'ui_on_text'    => __( 'Ja', 'woo-card-chef' ),
						'ui_off_text'   => __( 'Nee', 'woo-card-chef' ),
					),
					array(
						'key'           => 'field_wcpce_badge_pfas_vrij',
						'label'         => __( 'PFAS-vrij', 'woo-card-chef' ),
						'name'          => 'badge_pfas_vrij',
						'type'          => 'true_false',
						'instructions'  => __( 'Toont een groene "PFAS-vrij" badge met bladicoon linksonder op de productkaart.', 'woo-card-chef' ),
						'required'      => 0,
						'default_value' => 0,
						'ui'            => 1,
						'ui_on_text'    => __( 'Ja', 'woo-card-chef' ),
						'ui_off_text'   => __( 'Nee', 'woo-card-chef' ),
					),
					array(
						'key'           => 'field_wcpce_badge_niet_leverbaar',
						'label'         => __( 'Niet meer leverbaar', 'woo-card-chef' ),
						'name'          => 'badge_niet_leverbaar',
						'type'          => 'true_false',
						'instructions'  => __( 'Markeert het product als permanent niet meer leverbaar. Toont een zwarte overlay over de afbeelding met de tekst "Niet meer leverbaar". De afbeelding wordt ook gedimd en grijs — hetzelfde als bij tijdelijk uitverkocht. Als dit product ook tijdelijk uitverkocht is in WooCommerce, wint deze badge.', 'woo-card-chef' ),
						'required'      => 0,
						'default_value' => 0,
						'ui'            => 1,
						'ui_on_text'    => __( 'Ja', 'woo-card-chef' ),
						'ui_off_text'   => __( 'Nee', 'woo-card-chef' ),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'product',
						),
					),
				),
				'menu_order'            => 5,
				'position'              => 'side',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => __( 'Badge instellingen voor de productkaart op categoriepagina\'s.', 'woo-card-chef' ),
			)
		);

		// Repeater-based PDP field groups.
		//
		// Requires ACF Pro for the repeater field type. PDP widgets that depend
		// on repeaters detect whether ACF Pro is available and show editor-only
		// notices when needed. On the frontend they fall back silently.
		//
		// PDP Gallery fields registered in v1 (v2.0.0):
		//   youtube_url      — the YouTube link (normal, youtu.be, embed, Shorts)
		//   video_title      — title used in accessibility labels and for admin clarity
		//   video_thumbnail  — optional custom thumbnail (attachment ID)
		//
		// Fields intentionally NOT registered in v1:
		//   video_position   — interleaving with WC images (v2.1+)
		//   display_mode     — inline vs lightbox (v2.1+)
		// Not registering them prevents editors from filling in data that has no
		// effect yet, avoiding confusion.
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// Field group 6: PDP Accordion — manual (PDF) download link.
		//
		// A simple file field, works with free ACF (no repeater needed).
		// The product_faq repeater used by the Accordion FAQ section is registered
		// outside the plugin and is read here by get_field() only.
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wcpce_pdp_accordion',
				'title'                 => __( 'PDP Accordion: handleiding', 'woo-card-chef' ),
				'fields'                => array(
					array(
						'key'           => 'field_wcpce_product_manual',
						'label'         => __( 'Handleiding (PDF)', 'woo-card-chef' ),
						'name'          => 'product_manual',
						'type'          => 'file',
						'instructions'  => __( 'Upload de handleiding als PDF. Leeglaten als er geen handleiding beschikbaar is. De bestandsnaam is niet zichtbaar voor de shopper — alleen het downloadlabel dat in Elementor is ingesteld.', 'woo-card-chef' ),
						'required'      => 0,
						'return_format' => 'array',
						'library'       => 'all',
						'mime_types'    => 'pdf',
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'product',
						),
					),
				),
				'menu_order'            => 11,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => __( 'Handleiding voor de PDP Accordion widget. Het PDF-bestand is downloadbaar via de Handleiding-sectie.', 'woo-card-chef' ),
			)
		);

		// Only register the repeater group when ACF Pro is available (repeater
		// is a Pro-only field type). acf_add_local_field_group() is available in
		// both free and Pro, but attempting to register a repeater sub-field in
		// free ACF will silently drop the sub-fields. We guard with
		// class_exists( 'ACF' ) to check Pro (free ACF uses 'acf' lowercase
		// without the Pro class). A safer cross-version check is whether the
		// 'repeater' field type is registered.
		if ( ! self::acf_pro_active() ) {
			return;
		}

		// Field group 4: PDP USP / Benefits content.
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wcpce_pdp_usps',
				'title'                 => __( 'PDP USP\'s', 'woo-card-chef' ),
				'fields'                => array(
					array(
						'key'          => 'field_wcpce_pdp_usps',
						'label'        => __( 'PDP USP\'s', 'woo-card-chef' ),
						'name'         => 'pdp_usps',
						'type'         => 'repeater',
						'instructions' => __( 'Vul korte USP-regels in voor de productdetailpagina. Alleen tekst; layout, iconen en styling worden geregeld in Elementor.', 'woo-card-chef' ),
						'required'     => 0,
						'min'          => 0,
						'max'          => 8,
						'layout'       => 'row',
						'button_label' => __( 'USP toevoegen', 'woo-card-chef' ),
						'sub_fields'   => array(
							array(
								'key'          => 'field_wcpce_pdp_usp_text',
								'label'        => __( 'USP tekst', 'woo-card-chef' ),
								'name'         => 'usp_text',
								'type'         => 'text',
								'instructions' => __( 'Een korte regel, bijvoorbeeld "PFAS-vrije keramische coating".', 'woo-card-chef' ),
								'required'     => 0,
								'maxlength'    => 140,
							),
						),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'product',
						),
					),
				),
				'menu_order'            => 9,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => __( 'PDP-specifieke USP-regels. Styling en layout worden in Elementor ingesteld.', 'woo-card-chef' ),
			)
		);

		// Field group 5: PDP Gallery extra media (YouTube videos).
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wcpce_pdp_gallery_media',
				'title'                 => __( 'PDP Gallery: extra media', 'woo-card-chef' ),
				'fields'                => array(
					array(
						'key'          => 'field_wcpce_pdp_gallery_videos',
						'label'        => __( 'YouTube video\'s', 'woo-card-chef' ),
						'name'         => 'pdp_gallery_videos',
						'type'         => 'repeater',
						'instructions' => __( 'Voeg YouTube-video\'s toe die als extra slides in de PDP gallery verschijnen. Video\'s worden in de thumbnailrij vóór de laatste zichtbare thumbnail geplaatst, zodat ze vóór de +x-indicator zichtbaar blijven.', 'woo-card-chef' ),
						'required'     => 0,
						'min'          => 0,
						'max'          => 10,
						'layout'       => 'block',
						'button_label' => __( 'Video toevoegen', 'woo-card-chef' ),
						'sub_fields'   => array(
							array(
								'key'          => 'field_wcpce_youtube_url',
								'label'        => __( 'YouTube URL', 'woo-card-chef' ),
								'name'         => 'youtube_url',
								'type'         => 'url',
								'instructions' => __( 'Plak hier de YouTube-link. Ondersteunde formaten: normale watch-URL, youtu.be, embed-URL en Shorts-URL.', 'woo-card-chef' ),
								'required'     => 1,
								'placeholder'  => 'https://www.youtube.com/watch?v=...',
							),
							array(
								'key'          => 'field_wcpce_video_title',
								'label'        => __( 'Videotitel', 'woo-card-chef' ),
								'name'         => 'video_title',
								'type'         => 'text',
								'instructions' => __( 'Titel voor beheer en toegankelijkheid (aria-label op de play-button). Houd het beschrijvend, bijvoorbeeld "Kitchen Chef Plus — demonstratie".', 'woo-card-chef' ),
								'required'     => 0,
								'maxlength'    => 120,
								'placeholder'  => __( 'Optionele videotitel', 'woo-card-chef' ),
							),
							array(
								'key'           => 'field_wcpce_video_thumbnail',
								'label'         => __( 'Eigen thumbnail (optioneel)', 'woo-card-chef' ),
								'name'          => 'video_thumbnail',
								'type'          => 'image',
								'instructions'  => __( 'Optionele eigen thumbnail. Als dit veld leeg is, gebruikt de widget de standaard YouTube-thumbnail. Eigen thumbnail heeft voorrang.', 'woo-card-chef' ),
								'required'      => 0,
								'return_format' => 'id',
								'preview_size'  => 'thumbnail',
								'library'       => 'all',
							),
						),
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'product',
						),
					),
				),
				'menu_order'            => 10,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'description'           => __( 'Extra media voor de PDP Gallery widget. Vereist ACF Pro.', 'woo-card-chef' ),
			)
		);
	}

	/**
	 * Checks whether ACF Pro is active and supports the repeater field type.
	 *
	 * Uses acf_get_field_types() when available (ACF 5.0+) to check for the
	 * 'repeater' type rather than relying on class names that may change between
	 * ACF versions. Falls back to a class_exists() check on older installs.
	 *
	 * @since 2.0.0
	 * @return bool True when ACF Pro repeater support is available.
	 */
	private static function acf_pro_active(): bool {
		if ( function_exists( 'acf_get_field_types' ) ) {
			$types = acf_get_field_types();
			return isset( $types['repeater'] );
		}
		// Fallback for older ACF builds: Pro registers this class.
		return class_exists( 'acf_field_repeater' );
	}
}
