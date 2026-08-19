<?php
/**
 * Reusable product labels.
 *
 * Registers a private product taxonomy whose terms act as reusable label
 * definitions. Each term stores its colour, card position, priority, active
 * state and optional visibility window. Products can select existing labels or
 * create a new one directly from the product editor.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.7.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product label taxonomy, admin UI and frontend data helper.
 *
 * @since 2.7.1
 */
final class WCPCE_Product_Labels {

	/** Taxonomy name. */
	public const TAXONOMY = 'wcpce_product_label';

	/** Term meta keys. */
	private const META_COLOR    = 'wcpce_label_color';
	private const META_POSITION = 'wcpce_label_position';
	private const META_PRIORITY = 'wcpce_label_priority';
	private const META_ACTIVE   = 'wcpce_label_active';
	private const META_VISIBLE_FROM  = 'wcpce_label_visible_from';
	private const META_VISIBLE_UNTIL = 'wcpce_label_visible_until';
	private const META_PDP_DETAILS   = 'wcpce_label_pdp_details';

	/** Default label colour. */
	private const DEFAULT_COLOR = '#B4211C';

	/**
	 * Visible label data cached per product for the current PHP request.
	 *
	 * @var array<int,array<int,array<string,mixed>>>
	 */
	private static $visible_label_cache = array();

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_taxonomy' ) );
		add_action( self::TAXONOMY . '_add_form_fields', array( self::class, 'render_add_term_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( self::class, 'render_edit_term_fields' ) );
		add_action( 'created_' . self::TAXONOMY, array( self::class, 'save_term_fields' ) );
		add_action( 'edited_' . self::TAXONOMY, array( self::class, 'save_term_fields' ) );
		add_action( 'save_post_product', array( self::class, 'save_product_labels' ), 20, 2 );

		add_filter( 'manage_edit-' . self::TAXONOMY . '_columns', array( self::class, 'register_admin_columns' ) );
		add_filter( 'manage_' . self::TAXONOMY . '_custom_column', array( self::class, 'render_admin_column' ), 10, 3 );
	}

	/**
	 * Registers the reusable product-label taxonomy.
	 *
	 * Labels are private presentation data: they have an admin screen and product
	 * relationships, but no public archives or rewrite rules.
	 *
	 * @return void
	 */
	public static function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			array( 'product' ),
			array(
				'labels'            => array(
					'name'                       => __( 'Productlabels', 'woo-card-chef' ),
					'singular_name'              => __( 'Productlabel', 'woo-card-chef' ),
					'menu_name'                  => __( 'Productlabels', 'woo-card-chef' ),
					'all_items'                  => __( 'Alle productlabels', 'woo-card-chef' ),
					'edit_item'                  => __( 'Productlabel bewerken', 'woo-card-chef' ),
					'view_item'                  => __( 'Productlabel bekijken', 'woo-card-chef' ),
					'update_item'                => __( 'Productlabel bijwerken', 'woo-card-chef' ),
					'add_new_item'               => __( 'Nieuw productlabel toevoegen', 'woo-card-chef' ),
					'new_item_name'              => __( 'Naam van het nieuwe productlabel', 'woo-card-chef' ),
					'search_items'               => __( 'Productlabels zoeken', 'woo-card-chef' ),
					'not_found'                  => __( 'Geen productlabels gevonden.', 'woo-card-chef' ),
					'no_terms'                   => __( 'Geen productlabels', 'woo-card-chef' ),
					'items_list_navigation'      => __( 'Productlabelnavigatie', 'woo-card-chef' ),
					'items_list'                 => __( 'Productlabellijst', 'woo-card-chef' ),
					'back_to_items'              => __( 'Terug naar productlabels', 'woo-card-chef' ),
					'item_link'                  => __( 'Productlabellink', 'woo-card-chef' ),
					'item_link_description'      => __( 'Een link naar een productlabel.', 'woo-card-chef' ),
				),
				'public'            => false,
				'publicly_queryable' => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => false,
				'show_in_rest'      => false,
				'show_admin_column' => false,
				'hierarchical'      => false,
				'query_var'         => false,
				'rewrite'           => false,
				'meta_box_cb'       => array( self::class, 'render_product_metabox' ),
				'capabilities'      => array(
					'manage_terms' => 'manage_woocommerce',
					'edit_terms'   => 'manage_woocommerce',
					'delete_terms' => 'manage_woocommerce',
					'assign_terms' => 'edit_products',
				),
			)
		);
	}

	/**
	 * Renders reusable label fields on the taxonomy add screen.
	 *
	 * @return void
	 */
	public static function render_add_term_fields(): void {
		wp_nonce_field( 'wcpce_save_product_label_term', 'wcpce_product_label_term_nonce' );
		?>
		<div class="form-field term-wcpce-label-color-wrap">
			<label for="wcpce-label-color"><?php esc_html_e( 'Achtergrondkleur', 'woo-card-chef' ); ?></label>
			<input type="color" id="wcpce-label-color" name="wcpce_label_color" value="<?php echo esc_attr( self::DEFAULT_COLOR ); ?>">
			<p><?php esc_html_e( 'De tekstkleur wordt automatisch wit of zwart voor het beste contrast.', 'woo-card-chef' ); ?></p>
		</div>
		<div class="form-field term-wcpce-label-position-wrap">
			<label for="wcpce-label-position"><?php esc_html_e( 'Positie op productkaart', 'woo-card-chef' ); ?></label>
			<select id="wcpce-label-position" name="wcpce_label_position">
				<option value="top-left"><?php esc_html_e( 'Linksboven', 'woo-card-chef' ); ?></option>
				<option value="top-right"><?php esc_html_e( 'Rechtsboven', 'woo-card-chef' ); ?></option>
			</select>
			<p><?php esc_html_e( 'Meerdere labels op dezelfde positie worden onder elkaar gestapeld.', 'woo-card-chef' ); ?></p>
		</div>
		<div class="form-field term-wcpce-label-priority-wrap">
			<label for="wcpce-label-priority"><?php esc_html_e( 'Prioriteit', 'woo-card-chef' ); ?></label>
			<input type="number" id="wcpce-label-priority" name="wcpce_label_priority" value="10" min="0" max="999" step="1">
			<p><?php esc_html_e( 'Een lager getal verschijnt eerder. Bij gelijke prioriteit wordt alfabetisch gesorteerd.', 'woo-card-chef' ); ?></p>
		</div>
		<div class="form-field term-wcpce-label-visible-from-wrap">
			<label for="wcpce-label-visible-from"><?php esc_html_e( 'Zichtbaar vanaf', 'woo-card-chef' ); ?></label>
			<input type="datetime-local" id="wcpce-label-visible-from" name="wcpce_label_visible_from" step="60">
			<p><?php echo esc_html( self::get_schedule_field_description( false ) ); ?></p>
		</div>
		<div class="form-field term-wcpce-label-visible-until-wrap">
			<label for="wcpce-label-visible-until"><?php esc_html_e( 'Zichtbaar tot', 'woo-card-chef' ); ?></label>
			<input type="datetime-local" id="wcpce-label-visible-until" name="wcpce_label_visible_until" step="60">
			<p><?php echo esc_html( self::get_schedule_field_description( true ) ); ?></p>
		</div>
		<div class="form-field term-wcpce-label-pdp-details-wrap">
			<label for="wcpce-label-pdp-details-add"><?php esc_html_e( 'PDP-toelichting', 'woo-card-chef' ); ?></label>
			<textarea id="wcpce-label-pdp-details-add" name="wcpce_label_pdp_details" rows="8" placeholder="<?php echo esc_attr__( 'Optionele uitleg met tekst, HTML en links.', 'woo-card-chef' ); ?>"></textarea>
			<p><?php esc_html_e( 'Optioneel. Na het toevoegen kun je deze inhoud via Productlabel bewerken met de volledige WordPress Visueel/Tekst-editor beheren.', 'woo-card-chef' ); ?></p>
		</div>
		<div class="form-field term-wcpce-label-active-wrap">
			<label for="wcpce-label-active">
				<input type="checkbox" id="wcpce-label-active" name="wcpce_label_active" value="1" checked>
				<?php esc_html_e( 'Label actief', 'woo-card-chef' ); ?>
			</label>
			<p><?php esc_html_e( 'Inactieve labels blijven aan producten gekoppeld, maar worden niet getoond.', 'woo-card-chef' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Renders reusable label fields on the taxonomy edit screen.
	 *
	 * @param \WP_Term $term Label term being edited.
	 * @return void
	 */
	public static function render_edit_term_fields( \WP_Term $term ): void {
		$color    = self::get_term_color( $term->term_id );
		$position = self::get_term_position( $term->term_id );
		$priority = self::get_term_priority( $term->term_id );
		$active   = self::is_term_active( $term->term_id );
		$visible_from  = self::get_term_datetime_input_value( $term->term_id, self::META_VISIBLE_FROM );
		$visible_until = self::get_term_datetime_input_value( $term->term_id, self::META_VISIBLE_UNTIL );
		$pdp_details   = self::get_term_pdp_details( $term->term_id );

		wp_nonce_field( 'wcpce_save_product_label_term', 'wcpce_product_label_term_nonce' );
		?>
		<tr class="form-field term-wcpce-label-color-wrap">
			<th scope="row"><label for="wcpce-label-color"><?php esc_html_e( 'Achtergrondkleur', 'woo-card-chef' ); ?></label></th>
			<td>
				<input type="color" id="wcpce-label-color" name="wcpce_label_color" value="<?php echo esc_attr( $color ); ?>">
				<p class="description"><?php esc_html_e( 'De tekstkleur wordt automatisch wit of zwart voor het beste contrast.', 'woo-card-chef' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-wcpce-label-position-wrap">
			<th scope="row"><label for="wcpce-label-position"><?php esc_html_e( 'Positie op productkaart', 'woo-card-chef' ); ?></label></th>
			<td>
				<select id="wcpce-label-position" name="wcpce_label_position">
					<option value="top-left" <?php selected( $position, 'top-left' ); ?>><?php esc_html_e( 'Linksboven', 'woo-card-chef' ); ?></option>
					<option value="top-right" <?php selected( $position, 'top-right' ); ?>><?php esc_html_e( 'Rechtsboven', 'woo-card-chef' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Meerdere labels op dezelfde positie worden onder elkaar gestapeld.', 'woo-card-chef' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-wcpce-label-priority-wrap">
			<th scope="row"><label for="wcpce-label-priority"><?php esc_html_e( 'Prioriteit', 'woo-card-chef' ); ?></label></th>
			<td>
				<input type="number" id="wcpce-label-priority" name="wcpce_label_priority" value="<?php echo esc_attr( $priority ); ?>" min="0" max="999" step="1">
				<p class="description"><?php esc_html_e( 'Een lager getal verschijnt eerder. Bij gelijke prioriteit wordt alfabetisch gesorteerd.', 'woo-card-chef' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-wcpce-label-visible-from-wrap">
			<th scope="row"><label for="wcpce-label-visible-from"><?php esc_html_e( 'Zichtbaar vanaf', 'woo-card-chef' ); ?></label></th>
			<td>
				<input type="datetime-local" id="wcpce-label-visible-from" name="wcpce_label_visible_from" value="<?php echo esc_attr( $visible_from ); ?>" step="60">
				<p class="description"><?php echo esc_html( self::get_schedule_field_description( false ) ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-wcpce-label-visible-until-wrap">
			<th scope="row"><label for="wcpce-label-visible-until"><?php esc_html_e( 'Zichtbaar tot', 'woo-card-chef' ); ?></label></th>
			<td>
				<input type="datetime-local" id="wcpce-label-visible-until" name="wcpce_label_visible_until" value="<?php echo esc_attr( $visible_until ); ?>" step="60">
				<p class="description"><?php echo esc_html( self::get_schedule_field_description( true ) ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-wcpce-label-pdp-details-wrap">
			<th scope="row"><label for="wcpce-label-pdp-details-<?php echo esc_attr( $term->term_id ); ?>"><?php esc_html_e( 'PDP-toelichting', 'woo-card-chef' ); ?></label></th>
			<td>
				<?php self::render_pdp_details_editor( $pdp_details, 'wcpce-label-pdp-details-' . $term->term_id ); ?>
				<p class="description"><?php esc_html_e( 'Optioneel. Deze inhoud kan via de Product Label Details-widget op de productdetailpagina worden getoond. Gebruik de editor voor tekst, opmaak en links.', 'woo-card-chef' ); ?></p>
			</td>
		</tr>
		<tr class="form-field term-wcpce-label-active-wrap">
			<th scope="row"><?php esc_html_e( 'Status', 'woo-card-chef' ); ?></th>
			<td>
				<label for="wcpce-label-active">
					<input type="checkbox" id="wcpce-label-active" name="wcpce_label_active" value="1" <?php checked( $active ); ?>>
					<?php esc_html_e( 'Label actief', 'woo-card-chef' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Inactieve labels blijven aan producten gekoppeld, maar worden niet getoond.', 'woo-card-chef' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves taxonomy term presentation fields.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public static function save_term_fields( int $term_id ): void {
		if ( ! isset( $_POST['wcpce_product_label_term_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['wcpce_product_label_term_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'wcpce_save_product_label_term' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$color    = isset( $_POST['wcpce_label_color'] ) ? self::sanitise_color( sanitize_hex_color( wp_unslash( $_POST['wcpce_label_color'] ) ) ) : self::DEFAULT_COLOR;
		$position = isset( $_POST['wcpce_label_position'] ) ? self::sanitise_position( sanitize_key( wp_unslash( $_POST['wcpce_label_position'] ) ) ) : 'top-left';
		$priority = isset( $_POST['wcpce_label_priority'] ) ? self::sanitise_priority( absint( wp_unslash( $_POST['wcpce_label_priority'] ) ) ) : 10;
		$active   = isset( $_POST['wcpce_label_active'] ) ? '1' : '0';
		$visible_from  = isset( $_POST['wcpce_label_visible_from'] ) ? self::sanitise_datetime_local( sanitize_text_field( wp_unslash( $_POST['wcpce_label_visible_from'] ) ), false ) : '';
		$visible_until = isset( $_POST['wcpce_label_visible_until'] ) ? self::sanitise_datetime_local( sanitize_text_field( wp_unslash( $_POST['wcpce_label_visible_until'] ) ), true ) : '';
		$pdp_details   = isset( $_POST['wcpce_label_pdp_details'] ) ? trim( wp_kses_post( wp_unslash( $_POST['wcpce_label_pdp_details'] ) ) ) : '';

		update_term_meta( $term_id, self::META_COLOR, $color );
		update_term_meta( $term_id, self::META_POSITION, $position );
		update_term_meta( $term_id, self::META_PRIORITY, $priority );
		update_term_meta( $term_id, self::META_ACTIVE, $active );
		self::update_optional_term_meta( $term_id, self::META_VISIBLE_FROM, $visible_from );
		self::update_optional_term_meta( $term_id, self::META_VISIBLE_UNTIL, $visible_until );
		self::update_optional_term_meta( $term_id, self::META_PDP_DETAILS, $pdp_details );
		self::$visible_label_cache = array();
	}

	/**
	 * Renders the product-editor label selector and inline creation form.
	 *
	 * @param \WP_Post $post Product post.
	 * @return void
	 */
	public static function render_product_metabox( \WP_Post $post ): void {
		$terms        = self::get_admin_terms();
		$selected_ids = wp_get_object_terms( $post->ID, self::TAXONOMY, array( 'fields' => 'ids' ) );
		$selected_ids = is_wp_error( $selected_ids ) ? array() : array_map( 'absint', $selected_ids );
		$can_create   = current_user_can( 'manage_woocommerce' );

		wp_nonce_field( 'wcpce_save_product_labels', 'wcpce_product_labels_nonce' );
		?>
		<div class="wcpce-product-labels-metabox">
			<p><?php esc_html_e( 'Selecteer herbruikbare labels voor dit product.', 'woo-card-chef' ); ?></p>
			<div style="max-height:180px;overflow:auto;border:1px solid #dcdcde;padding:8px;background:#fff;">
				<?php if ( empty( $terms ) ) : ?>
					<p style="margin:0;"><?php esc_html_e( 'Er zijn nog geen productlabels.', 'woo-card-chef' ); ?></p>
				<?php else : ?>
					<ul style="margin:0;">
						<?php foreach ( $terms as $term ) : ?>
							<?php
							$color    = self::get_term_color( $term->term_id );
							$position = self::get_term_position( $term->term_id );
							$active   = self::is_term_active( $term->term_id );
							$schedule_status = self::get_schedule_status_label( $term->term_id, true );
							$has_pdp_details = '' !== self::get_term_pdp_details( $term->term_id );
							?>
							<li style="margin:0 0 6px;">
								<label>
									<input type="checkbox" name="wcpce_product_label_ids[]" value="<?php echo esc_attr( $term->term_id ); ?>" <?php checked( in_array( $term->term_id, $selected_ids, true ) ); ?>>
									<span aria-hidden="true" style="display:inline-block;width:12px;height:12px;border-radius:3px;vertical-align:-1px;background:<?php echo esc_attr( $color ); ?>;"></span>
									<?php echo esc_html( $term->name ); ?>
									<small>(<?php echo esc_html( 'top-left' === $position ? __( 'linksboven', 'woo-card-chef' ) : __( 'rechtsboven', 'woo-card-chef' ) ); ?><?php echo $active ? '' : ', ' . esc_html__( 'inactief', 'woo-card-chef' ); ?><?php echo '' === $schedule_status ? '' : ', ' . esc_html( $schedule_status ); ?><?php echo $has_pdp_details ? ', ' . esc_html__( 'PDP-uitleg', 'woo-card-chef' ) : ''; ?>)</small>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if ( $can_create ) : ?>
				<details style="margin-top:10px;">
					<summary style="cursor:pointer;font-weight:600;"><?php esc_html_e( 'Nieuw label maken', 'woo-card-chef' ); ?></summary>
					<p>
						<label for="wcpce-new-label-name"><strong><?php esc_html_e( 'Tekst', 'woo-card-chef' ); ?></strong></label><br>
						<input type="text" class="widefat" id="wcpce-new-label-name" name="wcpce_new_label_name" maxlength="60" placeholder="<?php echo esc_attr__( 'Bijvoorbeeld Bestseller', 'woo-card-chef' ); ?>">
					</p>
					<p>
						<label for="wcpce-new-label-color"><strong><?php esc_html_e( 'Achtergrondkleur', 'woo-card-chef' ); ?></strong></label><br>
						<input type="color" id="wcpce-new-label-color" name="wcpce_new_label_color" value="<?php echo esc_attr( self::DEFAULT_COLOR ); ?>">
					</p>
					<p>
						<label for="wcpce-new-label-position"><strong><?php esc_html_e( 'Positie', 'woo-card-chef' ); ?></strong></label><br>
						<select id="wcpce-new-label-position" name="wcpce_new_label_position" class="widefat">
							<option value="top-left"><?php esc_html_e( 'Linksboven', 'woo-card-chef' ); ?></option>
							<option value="top-right"><?php esc_html_e( 'Rechtsboven', 'woo-card-chef' ); ?></option>
						</select>
					</p>
					<p>
						<label for="wcpce-new-label-priority"><strong><?php esc_html_e( 'Prioriteit', 'woo-card-chef' ); ?></strong></label><br>
						<input type="number" id="wcpce-new-label-priority" name="wcpce_new_label_priority" value="10" min="0" max="999" step="1" class="small-text">
						<span class="description"><?php esc_html_e( 'Lager verschijnt eerder.', 'woo-card-chef' ); ?></span>
					</p>
					<p>
						<label for="wcpce-new-label-visible-from"><strong><?php esc_html_e( 'Zichtbaar vanaf', 'woo-card-chef' ); ?></strong></label><br>
						<input type="datetime-local" id="wcpce-new-label-visible-from" name="wcpce_new_label_visible_from" step="60" class="widefat">
					</p>
					<p>
						<label for="wcpce-new-label-visible-until"><strong><?php esc_html_e( 'Zichtbaar tot', 'woo-card-chef' ); ?></strong></label><br>
						<input type="datetime-local" id="wcpce-new-label-visible-until" name="wcpce_new_label_visible_until" step="60" class="widefat">
						<span class="description"><?php echo esc_html( self::get_schedule_field_description( true ) ); ?></span>
					</p>
					<p>
						<label for="wcpce-new-label-pdp-details"><strong><?php esc_html_e( 'PDP-toelichting', 'woo-card-chef' ); ?></strong></label><br>
						<textarea id="wcpce-new-label-pdp-details" name="wcpce_new_label_pdp_details" rows="6" class="widefat" placeholder="<?php echo esc_attr__( 'Optionele uitleg met tekst, HTML en links.', 'woo-card-chef' ); ?>"></textarea>
						<span class="description"><?php esc_html_e( 'Na het opslaan kun je deze inhoud met de volledige WordPress-editor beheren via Producten > Productlabels.', 'woo-card-chef' ); ?></span>
					</p>
					<p class="description"><?php esc_html_e( 'Het nieuwe label wordt opgeslagen, direct geselecteerd en is daarna herbruikbaar bij andere producten.', 'woo-card-chef' ); ?></p>
				</details>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Saves selected labels and optionally creates a reusable label inline.
	 *
	 * @param int      $post_id Product post ID.
	 * @param \WP_Post $post    Product post object.
	 * @return void
	 */
	public static function save_product_labels( int $post_id, \WP_Post $post ): void {
		if ( 'product' !== $post->post_type || wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		if ( ! isset( $_POST['wcpce_product_labels_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['wcpce_product_labels_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'wcpce_save_product_labels' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$term_ids       = array();
		$posted_term_ids = isset( $_POST['wcpce_product_label_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['wcpce_product_label_ids'] ) ) : array();
		foreach ( $posted_term_ids as $term_id ) {
				if ( $term_id > 0 && term_exists( $term_id, self::TAXONOMY ) ) {
					$term_ids[] = $term_id;
				}
		}

		$can_create = current_user_can( 'manage_woocommerce' );
		$new_name   = $can_create && isset( $_POST['wcpce_new_label_name'] ) ? self::clamp_label_text( sanitize_text_field( wp_unslash( $_POST['wcpce_new_label_name'] ) ) ) : '';
		if ( '' !== $new_name ) {
			$inserted = wp_insert_term( $new_name, self::TAXONOMY );

			if ( is_wp_error( $inserted ) && 'term_exists' === $inserted->get_error_code() ) {
				$existing_id = absint( $inserted->get_error_data( 'term_exists' ) );
				if ( $existing_id > 0 ) {
					$term_ids[] = $existing_id;
				}
			} elseif ( ! is_wp_error( $inserted ) ) {
				$new_term_id = absint( $inserted['term_id'] );
				$color       = isset( $_POST['wcpce_new_label_color'] ) ? self::sanitise_color( sanitize_hex_color( wp_unslash( $_POST['wcpce_new_label_color'] ) ) ) : self::DEFAULT_COLOR;
				$position    = isset( $_POST['wcpce_new_label_position'] ) ? self::sanitise_position( sanitize_key( wp_unslash( $_POST['wcpce_new_label_position'] ) ) ) : 'top-left';
				$priority    = isset( $_POST['wcpce_new_label_priority'] ) ? self::sanitise_priority( absint( wp_unslash( $_POST['wcpce_new_label_priority'] ) ) ) : 10;
				$visible_from  = isset( $_POST['wcpce_new_label_visible_from'] ) ? self::sanitise_datetime_local( sanitize_text_field( wp_unslash( $_POST['wcpce_new_label_visible_from'] ) ), false ) : '';
				$visible_until = isset( $_POST['wcpce_new_label_visible_until'] ) ? self::sanitise_datetime_local( sanitize_text_field( wp_unslash( $_POST['wcpce_new_label_visible_until'] ) ), true ) : '';
				$pdp_details   = isset( $_POST['wcpce_new_label_pdp_details'] ) ? trim( wp_kses_post( wp_unslash( $_POST['wcpce_new_label_pdp_details'] ) ) ) : '';

				update_term_meta( $new_term_id, self::META_COLOR, $color );
				update_term_meta( $new_term_id, self::META_POSITION, $position );
				update_term_meta( $new_term_id, self::META_PRIORITY, $priority );
				update_term_meta( $new_term_id, self::META_ACTIVE, '1' );
				self::update_optional_term_meta( $new_term_id, self::META_VISIBLE_FROM, $visible_from );
				self::update_optional_term_meta( $new_term_id, self::META_VISIBLE_UNTIL, $visible_until );
				self::update_optional_term_meta( $new_term_id, self::META_PDP_DETAILS, $pdp_details );
				$term_ids[] = $new_term_id;
			}
		}

		$term_ids = array_values( array_unique( array_map( 'absint', $term_ids ) ) );
		wp_set_object_terms( $post_id, $term_ids, self::TAXONOMY, false );
		unset( self::$visible_label_cache[ $post_id ] );
	}

	/**
	 * Bulk-primes product term relationships and label metadata before card loops.
	 *
	 * WordPress skips IDs whose relationship cache is already warm, so calling
	 * this for normal WP_Query results is cheap while cached-ID render paths avoid
	 * falling back to one taxonomy query per product.
	 *
	 * @param array<int,int|\WC_Product> $products Product IDs or objects.
	 * @return void
	 */
	public static function prime_product_label_caches( array $products ): void {
		if ( empty( $products ) || ! taxonomy_exists( self::TAXONOMY ) ) {
			return;
		}

		$product_ids = array();
		foreach ( $products as $product ) {
			$product_id = $product instanceof \WC_Product ? $product->get_id() : absint( $product );
			if ( $product_id > 0 ) {
				$product_ids[] = $product_id;
			}
		}

		$product_ids = array_values( array_unique( $product_ids ) );
		if ( empty( $product_ids ) ) {
			return;
		}

		update_object_term_cache( $product_ids, 'product' );

		$term_ids = array();
		foreach ( $product_ids as $product_id ) {
			$terms = get_object_term_cache( $product_id, self::TAXONOMY );
			if ( ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( $term instanceof \WP_Term ) {
					$term_ids[] = (int) $term->term_id;
				}
			}
		}

		$term_ids = array_values( array_unique( $term_ids ) );
		if ( ! empty( $term_ids ) ) {
			update_termmeta_cache( $term_ids );
		}
	}

	/**
	 * Returns active labels for frontend card rendering.
	 *
	 * Results are sorted by priority, then label text, and limited across both
	 * card positions together.
	 *
	 * @param int $product_id Product ID.
	 * @param int $limit      Maximum number of labels.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_product_labels( int $product_id, int $limit = 3 ): array {
		$limit = max( 1, min( 10, $limit ) );
		return array_slice( self::get_visible_product_label_data( $product_id ), 0, $limit );
	}

	/**
	 * Returns visible labels that have optional PDP rich-text content.
	 *
	 * The same active state, schedule and priority contract used by cards and the
	 * Gallery applies here. A blank PDP field makes the label opt out.
	 *
	 * @param int $product_id Product ID.
	 * @param int $limit      Maximum number of detail panels.
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_product_label_details( int $product_id, int $limit = 3 ): array {
		$limit   = max( 1, min( 10, $limit ) );
		$details = array();
		foreach ( self::get_visible_product_label_data( $product_id ) as $label ) {
			$content = self::get_term_pdp_details( (int) $label['term_id'] );
			if ( '' === $content ) {
				continue;
			}

			$label['pdp_details'] = $content;
			$details[]            = $label;
			if ( count( $details ) >= $limit ) {
				break;
			}
		}

		return $details;
	}

	/** Returns all active, currently scheduled labels in deterministic order. */
	private static function get_visible_product_label_data( int $product_id ): array {
		if ( $product_id <= 0 || ! taxonomy_exists( self::TAXONOMY ) ) {
			return array();
		}

		if ( array_key_exists( $product_id, self::$visible_label_cache ) ) {
			return self::$visible_label_cache[ $product_id ];
		}

		$terms = get_the_terms( $product_id, self::TAXONOMY );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			self::$visible_label_cache[ $product_id ] = array();
			return self::$visible_label_cache[ $product_id ];
		}

		update_termmeta_cache( wp_list_pluck( $terms, 'term_id' ) );

		$labels = array();
		$now    = current_datetime();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term || ! self::is_term_active( $term->term_id ) || ! self::is_term_visible_at( $term->term_id, $now ) ) {
				continue;
			}

			$color = self::get_term_color( $term->term_id );
			$labels[] = array(
				'term_id'    => (int) $term->term_id,
				'text'       => (string) $term->name,
				'color'      => $color,
				'text_color' => self::get_contrast_color( $color ),
				'position'   => self::get_term_position( $term->term_id ),
				'priority'   => self::get_term_priority( $term->term_id ),
			);
		}

		usort(
			$labels,
			static function ( array $first, array $second ): int {
				if ( $first['priority'] !== $second['priority'] ) {
					return $first['priority'] <=> $second['priority'];
				}

				$name_order = strcasecmp( (string) $first['text'], (string) $second['text'] );
				return 0 !== $name_order ? $name_order : (int) $first['term_id'] <=> (int) $second['term_id'];
			}
		);

		self::$visible_label_cache[ $product_id ] = $labels;
		return self::$visible_label_cache[ $product_id ];
	}

	/**
	 * Adds useful columns to the central label overview.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function register_admin_columns( array $columns ): array {
		$columns['wcpce_preview']  = __( 'Voorbeeld', 'woo-card-chef' );
		$columns['wcpce_position'] = __( 'Positie', 'woo-card-chef' );
		$columns['wcpce_priority'] = __( 'Prioriteit', 'woo-card-chef' );
		$columns['wcpce_schedule'] = __( 'Zichtbaarheid', 'woo-card-chef' );
		$columns['wcpce_pdp']      = __( 'PDP-toelichting', 'woo-card-chef' );
		$columns['wcpce_status']   = __( 'Status', 'woo-card-chef' );
		return $columns;
	}

	/**
	 * Renders a central label overview column.
	 *
	 * @param string $content     Existing content.
	 * @param string $column_name Column key.
	 * @param int    $term_id     Term ID.
	 * @return string
	 */
	public static function render_admin_column( string $content, string $column_name, int $term_id ): string {
		switch ( $column_name ) {
			case 'wcpce_preview':
				$term = get_term( $term_id, self::TAXONOMY );
				if ( ! $term instanceof \WP_Term ) {
					return $content;
				}
				$color      = self::get_term_color( $term_id );
				$text_color = self::get_contrast_color( $color );
				return '<span style="display:inline-block;padding:4px 8px;border-radius:6px;background:' . esc_attr( $color ) . ';color:' . esc_attr( $text_color ) . ';font-weight:700;">' . esc_html( $term->name ) . '</span>';

			case 'wcpce_position':
				return 'top-left' === self::get_term_position( $term_id ) ? esc_html__( 'Linksboven', 'woo-card-chef' ) : esc_html__( 'Rechtsboven', 'woo-card-chef' );

			case 'wcpce_priority':
				return esc_html( (string) self::get_term_priority( $term_id ) );

			case 'wcpce_schedule':
				$schedule_status = self::get_schedule_status_label( $term_id, false );
				return '' === $schedule_status ? esc_html__( 'Altijd', 'woo-card-chef' ) : esc_html( $schedule_status );

			case 'wcpce_pdp':
				return '' === self::get_term_pdp_details( $term_id ) ? '&mdash;' : esc_html__( 'Ingevuld', 'woo-card-chef' );

			case 'wcpce_status':
				return self::is_term_active( $term_id ) ? esc_html__( 'Actief', 'woo-card-chef' ) : esc_html__( 'Inactief', 'woo-card-chef' );
		}

		return $content;
	}

	/**
	 * Returns all terms for the product metabox in display order.
	 *
	 * @return array<int,\WP_Term>
	 */
	private static function get_admin_terms(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		usort(
			$terms,
			static function ( \WP_Term $first, \WP_Term $second ): int {
				$priority_order = self::get_term_priority( $first->term_id ) <=> self::get_term_priority( $second->term_id );
				return 0 !== $priority_order ? $priority_order : strcasecmp( $first->name, $second->name );
			}
		);

		return $terms;
	}

	/** Returns a term colour with a safe fallback. */
	private static function get_term_color( int $term_id ): string {
		return self::sanitise_color( (string) get_term_meta( $term_id, self::META_COLOR, true ) );
	}

	/** Returns a whitelisted term position. */
	private static function get_term_position( int $term_id ): string {
		return self::sanitise_position( (string) get_term_meta( $term_id, self::META_POSITION, true ) );
	}

	/** Returns a bounded term priority. */
	private static function get_term_priority( int $term_id ): int {
		$value = get_term_meta( $term_id, self::META_PRIORITY, true );
		return '' === $value ? 10 : self::sanitise_priority( $value );
	}

	/** Returns the sanitised optional PDP rich-text content. */
	private static function get_term_pdp_details( int $term_id ): string {
		return self::sanitise_pdp_details( get_term_meta( $term_id, self::META_PDP_DETAILS, true ) );
	}

	/** Renders WordPress's visual/text editor for optional PDP label content. */
	private static function render_pdp_details_editor( string $content, string $editor_id ): void {
		wp_editor(
			$content,
			$editor_id,
			array(
				'textarea_name' => 'wcpce_label_pdp_details',
				'textarea_rows' => 8,
				'media_buttons' => false,
				'teeny'         => false,
				'quicktags'     => true,
				'tinymce'       => array(
					'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo',
					'toolbar2' => '',
				),
			)
		);
	}

	/** Allows standard WordPress post HTML while stripping unsafe markup. */
	private static function sanitise_pdp_details( $value ): string {
		return trim( wp_kses_post( (string) $value ) );
	}

	/** Returns whether a term is active; missing legacy meta defaults to active. */
	private static function is_term_active( int $term_id ): bool {
		$value = get_term_meta( $term_id, self::META_ACTIVE, true );
		return '' === $value || '1' === (string) $value;
	}

	/** Returns whether a label falls inside its optional visibility window. */
	private static function is_term_visible_at( int $term_id, \DateTimeImmutable $now ): bool {
		if ( self::has_invalid_term_datetime( $term_id, self::META_VISIBLE_FROM ) || self::has_invalid_term_datetime( $term_id, self::META_VISIBLE_UNTIL ) ) {
			return false;
		}

		$visible_from  = self::get_term_datetime( $term_id, self::META_VISIBLE_FROM );
		$visible_until = self::get_term_datetime( $term_id, self::META_VISIBLE_UNTIL );

		if ( null !== $visible_from && null !== $visible_until && $visible_until < $visible_from ) {
			return false;
		}

		if ( null !== $visible_from && $now < $visible_from ) {
			return false;
		}

		return null === $visible_until || $now <= $visible_until;
	}

	/** Returns a stored visibility boundary in the WordPress site timezone. */
	private static function get_term_datetime( int $term_id, string $meta_key ): ?\DateTimeImmutable {
		$value = (string) get_term_meta( $term_id, $meta_key, true );
		if ( '' === $value ) {
			return null;
		}

		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) || $date->format( 'Y-m-d H:i:s' ) !== $value ) {
			return null;
		}

		return $date;
	}

	/** Returns whether non-empty schedule meta cannot be parsed safely. */
	private static function has_invalid_term_datetime( int $term_id, string $meta_key ): bool {
		$value = (string) get_term_meta( $term_id, $meta_key, true );
		return '' !== $value && null === self::get_term_datetime( $term_id, $meta_key );
	}

	/** Formats a stored boundary for an HTML datetime-local input. */
	private static function get_term_datetime_input_value( int $term_id, string $meta_key ): string {
		$date = self::get_term_datetime( $term_id, $meta_key );
		return null === $date ? '' : $date->format( 'Y-m-d\TH:i' );
	}

	/** Sanitises a datetime-local value into a site-local database value. */
	private static function sanitise_datetime_local( $value, bool $is_until ): string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		$date   = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) || $date->format( 'Y-m-d\TH:i' ) !== $value ) {
			return '';
		}

		if ( $is_until ) {
			$date = $date->setTime( (int) $date->format( 'H' ), (int) $date->format( 'i' ), 59 );
		}

		return $date->format( 'Y-m-d H:i:s' );
	}

	/** Updates an optional term-meta value or removes it when left blank. */
	private static function update_optional_term_meta( int $term_id, string $meta_key, string $value ): void {
		if ( '' === $value ) {
			delete_term_meta( $term_id, $meta_key );
			return;
		}

		update_term_meta( $term_id, $meta_key, $value );
	}

	/** Returns a human-readable current schedule state for admin screens. */
	private static function get_schedule_status_label( int $term_id, bool $compact ): string {
		if ( self::has_invalid_term_datetime( $term_id, self::META_VISIBLE_FROM ) || self::has_invalid_term_datetime( $term_id, self::META_VISIBLE_UNTIL ) ) {
			return __( 'Ongeldige periode', 'woo-card-chef' );
		}

		$visible_from  = self::get_term_datetime( $term_id, self::META_VISIBLE_FROM );
		$visible_until = self::get_term_datetime( $term_id, self::META_VISIBLE_UNTIL );
		if ( null === $visible_from && null === $visible_until ) {
			return '';
		}

		if ( null !== $visible_from && null !== $visible_until && $visible_until < $visible_from ) {
			return __( 'Ongeldige periode', 'woo-card-chef' );
		}

		$format = $compact ? 'j M H:i' : get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$now    = current_datetime();
		if ( null !== $visible_from && $now < $visible_from ) {
			return sprintf(
				/* translators: %s: local start date and time. */
				__( 'Gepland vanaf %s', 'woo-card-chef' ),
				wp_date( $format, $visible_from->getTimestamp(), wp_timezone() )
			);
		}

		if ( null !== $visible_until && $now > $visible_until ) {
			return sprintf(
				/* translators: %s: local end date and time. */
				__( 'Verlopen sinds %s', 'woo-card-chef' ),
				wp_date( $format, $visible_until->getTimestamp(), wp_timezone() )
			);
		}

		if ( null !== $visible_until ) {
			return sprintf(
				/* translators: %s: local end date and time. */
				__( 'Nu zichtbaar, tot %s', 'woo-card-chef' ),
				wp_date( $format, $visible_until->getTimestamp(), wp_timezone() )
			);
		}

		return sprintf(
			/* translators: %s: local start date and time. */
			__( 'Zichtbaar sinds %s', 'woo-card-chef' ),
			wp_date( $format, $visible_from->getTimestamp(), wp_timezone() )
		);
	}

	/** Returns schedule-field guidance including the active site timezone. */
	private static function get_schedule_field_description( bool $is_until ): string {
		$timezone = wp_timezone_string();
		if ( $is_until ) {
			return sprintf(
				/* translators: %s: WordPress site timezone. */
				__( 'Optioneel. Leeg betekent geen eindtijd. Tijdzone: %s. Het label blijft zichtbaar tot en met de gekozen minuut. Een eindtijd voor de starttijd verbergt het label.', 'woo-card-chef' ),
				$timezone
			);
		}

		return sprintf(
			/* translators: %s: WordPress site timezone. */
			__( 'Optioneel. Leeg betekent geen starttijd. Tijdzone: %s.', 'woo-card-chef' ),
			$timezone
		);
	}

	/** Sanitises a hex colour and applies the project default. */
	private static function sanitise_color( $value ): string {
		$color = sanitize_hex_color( (string) $value );
		return $color ? $color : self::DEFAULT_COLOR;
	}

	/** Sanitises a card position. */
	private static function sanitise_position( $value ): string {
		return 'top-right' === (string) $value ? 'top-right' : 'top-left';
	}

	/** Sanitises and bounds a label priority. */
	private static function sanitise_priority( $value ): int {
		return max( 0, min( 999, absint( $value ) ) );
	}

	/** Sanitises and length-limits inline-created label text. */
	private static function clamp_label_text( $value ): string {
		$value = sanitize_text_field( wp_strip_all_tags( (string) $value, true ) );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, 60 );
		}
		return substr( $value, 0, 60 );
	}

	/**
	 * Chooses black or white label text using WCAG relative contrast.
	 *
	 * @param string $hex Six-digit background colour.
	 * @return string
	 */
	private static function get_contrast_color( string $hex ): string {
		$hex      = ltrim( self::sanitise_color( $hex ), '#' );
		$channels = array(
			hexdec( substr( $hex, 0, 2 ) ) / 255,
			hexdec( substr( $hex, 2, 2 ) ) / 255,
			hexdec( substr( $hex, 4, 2 ) ) / 255,
		);

		foreach ( $channels as &$channel ) {
			$channel = $channel <= 0.03928 ? $channel / 12.92 : ( ( $channel + 0.055 ) / 1.055 ) ** 2.4;
		}
		unset( $channel );

		$luminance      = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
		$white_contrast = 1.05 / ( $luminance + 0.05 );
		$black_contrast = ( $luminance + 0.05 ) / 0.05;

		return $black_contrast >= $white_contrast ? '#000000' : '#ffffff';
	}
}
