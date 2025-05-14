<?php
/**
 * Plugin Name: TV Products and Block
 * Description: Adds a "Voeg toe aan TV" checkbox to WooCommerce products, registers a CPT "TV afbeeldingen", and provides a Gutenberg block to display selected products plus featured images of TV afbeeldingen.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: tv-products-block
 *
 * @package TV_Products_Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the "Voeg toe aan TV" checkbox field to the WooCommerce product general tab, including a nonce field.
 *
 * @package TV_Products_Block
 */
function tv_add_custom_checkbox() {
	woocommerce_wp_checkbox(
		array(
			'id'          => '_tv_checkbox',
			'label'       => __( 'Voeg toe aan TV', 'tv-products-block' ),
			'description' => __( 'Dit product moet op de TV te zien zijn.', 'tv-products-block' ),
		)
	);
	wp_nonce_field( 'tv_save_checkbox', 'tv_checkbox_nonce' );
}
add_action( 'woocommerce_product_options_general_product_data', 'tv_add_custom_checkbox' );
add_action( 'woocommerce_product_options_advanced', 'tv_add_custom_checkbox' );

/**
 * Saves the "Voeg toe aan TV" checkbox value when the product is saved, verifying nonce and user capability.
 *
 * @param int $post_id The ID of the current product.
 * @package TV_Products_Block
 */
function tv_save_custom_checkbox( $post_id ) {
	if ( ! isset( $_POST['tv_checkbox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tv_checkbox_nonce'] ) ), 'tv_save_checkbox' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$value = isset( $_POST['_tv_checkbox'] ) ? 'yes' : 'no';
	$value = sanitize_text_field( $value );
	update_post_meta( $post_id, '_tv_checkbox', $value );
}
add_action( 'woocommerce_process_product_meta', 'tv_save_custom_checkbox' );

/**
 * Add a “Voeg toe aan TV” column to the Products list.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 * @package TV_Products_Block
 */
function tv_add_product_column( $columns ) {
	$columns['tv_flag'] = __( 'Voeg toe aan TV', 'tv-products-block' );
	return $columns;
}
add_filter( 'manage_edit-product_columns', 'tv_add_product_column' );

/**
 * Render the “Voeg toe aan TV” column value.
 *
 * @param string $column  Column name.
 * @param int    $post_id Product ID.
 * @package TV_Products_Block
 */
function tv_render_product_column( $column, $post_id ) {
	if ( 'tv_flag' !== $column ) {
		return;
	}
	$checked = get_post_meta( $post_id, '_tv_checkbox', true ) === 'yes';
	echo $checked
		? '<span aria-label="' . esc_attr__( 'Yes', 'tv-products-block' ) . '">✔️</span>'
		: '<span aria-label="' . esc_attr__( 'No', 'tv-products-block' ) . '">—</span>';
}
add_action( 'manage_product_posts_custom_column', 'tv_render_product_column', 10, 2 );

/**
 * Add Quick-Edit checkbox to the inline edit row.
 *
 * @param string $column   Column name.
 * @param string $post_type Post type.
 * @package TV_Products_Block
 */
function tv_quick_edit_field( $column, $post_type ) {
	if ( 'product' !== $post_type || 'tv_flag' !== $column ) {
		return;
	}
	wp_nonce_field( 'tv_quick_edit', 'tv_quick_edit_nonce' );
	?>
	<fieldset class="inline-edit-col-right">
		<div class="inline-edit-col">
		<label>
			<span class="title"><?php esc_html_e( 'Voeg toe aan TV', 'tv-products-block' ); ?></span>
			<span class="input-text-wrap">
			<input type="checkbox" name="_tv_checkbox" value="yes">
			</span>
		</label>
		</div>
	</fieldset>
	<?php
}
add_action( 'quick_edit_custom_box', 'tv_quick_edit_field', 10, 2 );

/**
 * Save the Quick-Edit value.
 *
 * @param int $post_id The ID being saved.
 * @package TV_Products_Block
 */
function tv_quick_edit_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( 'product' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( empty( $_REQUEST['tv_quick_edit_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['tv_quick_edit_nonce'] ) ), 'tv_quick_edit' )
	) {
		return;
	}
	$new = isset( $_REQUEST['_tv_checkbox'] ) ? 'yes' : 'no';
	update_post_meta( $post_id, '_tv_checkbox', sanitize_text_field( $new ) );
}
add_action( 'save_post', 'tv_quick_edit_save' );

/**
 * Add Bulk-Edit dropdown to the Bulk Edit row.
 *
 * @param string $column   Column name.
 * @param string $post_type Post type.
 * @package TV_Products_Block
 */
function tv_bulk_edit_field( $column, $post_type ) {
	if ( 'product' !== $post_type || 'tv_flag' !== $column ) {
		return;
	}
	wp_nonce_field( 'tv_bulk_edit', 'tv_bulk_edit_nonce' );
	?>
	<fieldset class="inline-edit-col-right">
		<div class="inline-edit-col">
		<label>
			<span class="title"><?php esc_html_e( 'Voeg toe aan TV', 'tv-products-block' ); ?></span>
			<span class="input-text-wrap">
			<select name="tv_bulk_action">
				<option value=""><?php esc_html_e( '— No change —', 'tv-products-block' ); ?></option>
				<option value="add"><?php esc_html_e( 'Add to TV', 'tv-products-block' ); ?></option>
				<option value="remove"><?php esc_html_e( 'Remove from TV', 'tv-products-block' ); ?></option>
			</select>
			</span>
		</label>
		</div>
	</fieldset>
	<?php
}
add_action( 'bulk_edit_custom_box', 'tv_bulk_edit_field', 10, 2 );

/**
 * Enqueue the small script to propagate bulk-edit values.
 *
 * @param string $hook_suffix Current admin page.
 * @package TV_Products_Block
 */
function tv_enqueue_admin_scripts( $hook_suffix ) {
	if ( 'edit.php' !== $hook_suffix || 'product' !== get_current_screen()->post_type ) {
		return;
	}
	wp_enqueue_script( 'jquery' );
	$inline = <<<JS
jQuery(function($){
  $('#bulk_edit').on('click',function(){
    var sel = $('#bulk-edit select[name="tv_bulk_action"]').val();
    if ( sel ) {
      $('<input>').attr({type:'hidden',name:'tv_bulk_action',value:sel})
        .appendTo('#posts-filter');
    }
  });
});
JS;
	wp_add_inline_script( 'jquery', $inline );
}
add_action( 'admin_enqueue_scripts', 'tv_enqueue_admin_scripts' );

/**
 * Handle the Bulk-Edit action on page load.
 *
 * @package TV_Products_Block
 */
function tv_handle_bulk_action() {
	if ( empty( $_REQUEST['tv_bulk_action'] ) ||
		empty( $_REQUEST['post'] ) ||
		empty( $_REQUEST['tv_bulk_edit_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['tv_bulk_edit_nonce'] ) ), 'tv_bulk_edit' )
	) {
		return;
	}
	$action   = sanitize_text_field( wp_unslash( $_REQUEST['tv_bulk_action'] ) );
	$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

	foreach ( $post_ids as $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			continue;
		}
		if ( 'add' === $action ) {
			update_post_meta( $post_id, '_tv_checkbox', 'yes' );
		} elseif ( 'remove' === $action ) {
			update_post_meta( $post_id, '_tv_checkbox', 'no' );
		}
	}

	// Redirect back without our query args.
	wp_redirect( remove_query_arg( array( 'tv_bulk_action', 'post', 'tv_bulk_edit_nonce' ), wp_get_referer() ) );
	exit;
}
add_action( 'load-edit.php', 'tv_handle_bulk_action' );

/**
 * Adds a filter dropdown for the “Voeg toe aan TV” flag on the Products admin list.
 *
 * @package TV_Products_Block
 */
function tv_filter_products_by_tv_flag() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-product' !== $screen->id ) {
		return;
	}

	$val = isset( $_GET['tv_flag_filter'] )
		? sanitize_text_field( wp_unslash( $_GET['tv_flag_filter'] ) )
		: '';
	?>
	<select name="tv_flag_filter">
		<option value=""><?php esc_html_e( 'Alle producten', 'tv-products-block' ); ?></option>
		<option value="yes" <?php selected( $val, 'yes' ); ?>><?php esc_html_e( 'Alle TV-producten', 'tv-products-block' ); ?></option>
		<option value="no"  <?php selected( $val, 'no' ); ?>><?php esc_html_e( 'Producten zonder TV', 'tv-products-block' ); ?></option>
	</select>
	<?php
}
add_action( 'restrict_manage_posts', 'tv_filter_products_by_tv_flag' );

/**
 * Adjusts the Products admin query to filter by the TV flag meta when requested.
 *
 * @param WP_Query $query The current WP_Query instance.
 * @package TV_Products_Block
 */
function tv_pre_get_posts_filter( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'product' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( empty( $_GET['tv_flag_filter'] ) ) {
		return;
	}

	$flag = sanitize_text_field( wp_unslash( $_GET['tv_flag_filter'] ) );
	if ( 'yes' === $flag ) {
		$meta_query = array(
			array(
				'key'     => '_tv_checkbox',
				'value'   => 'yes',
				'compare' => '=',
			),
		);
	} elseif ( 'no' === $flag ) {
		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'     => '_tv_checkbox',
				'value'   => 'no',
				'compare' => '=',
			),
			array(
				'key'     => '_tv_checkbox',
				'compare' => 'NOT EXISTS',
			),
		);
	} else {
		return;
	}

	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'tv_pre_get_posts_filter' );

/**
 * Registers the 'tv_afbeelding' custom post type.
 *
 * @package TV_Products_Block
 */
function tv_register_cpt() {
	$labels = array(
		'name'          => __( 'TV afbeeldingen', 'tv-products-block' ),
		'singular_name' => __( 'TV afbeelding', 'tv-products-block' ),
		'add_new_item'  => __( 'Add New TV Afbeelding', 'tv-products-block' ),
		'edit_item'     => __( 'Edit TV Afbeelding', 'tv-products-block' ),
		'new_item'      => __( 'New TV Afbeelding', 'tv-products-block' ),
		'view_item'     => __( 'View TV Afbeelding', 'tv-products-block' ),
		'search_items'  => __( 'Search TV Afbeeldingen', 'tv-products-block' ),
		'not_found'     => __( 'No TV afbeeldingen found', 'tv-products-block' ),
	);
	$args   = array(
		'labels'       => $labels,
		'public'       => true,
		'has_archive'  => false,
		'show_in_rest' => true,
		'supports'     => array( 'title', 'editor', 'thumbnail' ),
	);
	register_post_type( 'tv_afbeelding', $args );
}
add_action( 'init', 'tv_register_cpt' );

/**
 * Registers the Gutenberg block for displaying TV products and afbeeldingen.
 *
 * @package TV_Products_Block
 */
function tv_register_block() {
	$asset = include plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
	wp_register_script(
		'tv-block-editor',
		plugins_url( 'build/index.js', __FILE__ ),
		$asset['dependencies'],
		$asset['version'],
		true
	);
	register_block_type(
		'tv/products-block',
		array(
			'editor_script'   => 'tv-block-editor',
			'render_callback' => 'tv_render_products_block',
		)
	);
}
	add_action( 'init', 'tv_register_block' );


/**
 * Render callback for the TV products block with Tiny Slider slideshow.
 *
 * @param array $attributes Block attributes.
 * @return string HTML output.
 * @package TV_Products_Block
 */
function tv_render_products_block( $attributes ) {
	$plugin_url = plugin_dir_url( __FILE__ );
	$count      = 0;
	ob_start();
	echo '<link rel="stylesheet" href="' . esc_url( $plugin_url . 'build/frontend.css' ) . '">'; //phpcs:ignore
	echo '<script type="module" src="' . esc_url( $plugin_url . 'build/frontend.js' ) . '"></script>'; //phpcs:ignore
	$products     = get_posts(
		array(
			'post_type'      => 'product',
			'meta_key'       => '_tv_checkbox',
			'meta_value'     => 'yes',
			'posts_per_page' => -1,
		)
	);
	$afbeeldingen = get_posts(
		array(
			'post_type'      => 'tv_afbeelding',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);
	echo '<div class="tv-swiper-container">';
	echo '<div class="swiper-wrapper">';
	foreach ( $products as $p ) {
		$prod = wc_get_product( $p->ID );
		if ( ! $prod ) {
			continue;
		}
		++$count;
		$img_url   = $prod->get_image_id() ? wp_get_attachment_image_url( $prod->get_image_id(), 'full' ) : '';
		$raw_title = $prod->get_name();
		if ( is_a( $prod, 'WC_Product_Variable' ) ) {
			$var_max_price = $prod->get_variation_regular_price( 'max' );
			$sale_price    = $prod->get_variation_sale_price( 'max' );
		} else {
			$sale_price    = $prod->get_sale_price();
			$var_max_price = $prod->get_regular_price();
		}
		$from_class = ( $var_max_price === $sale_price || 0 === (int) $sale_price ) ? 'hasnofromprice' : 'hasfromprice';
		echo '<div class="swiper-slide">';
		if ( $img_url ) {
			echo '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $raw_title ) . '">';
		}
		echo '<div class="tv-product-title">';
		echo '<h3>' . esc_html( $raw_title ) . '</h3>';
		echo '<div class="wp-block-robinshoes-product-price">';
		echo '<div class="' . esc_html( $from_class ) . '">' . wc_price( $var_max_price ) . '</div>'; //phpcs:ignore
		echo '<div class="forprice">';
        echo $var_max_price !== $sale_price && 0 !== (int) $sale_price ? wc_price( $sale_price ) : wc_price( $var_max_price ); //phpcs:ignore
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}
	foreach ( $afbeeldingen as $item ) {
		++$count;
		$thumb_url = get_the_post_thumbnail_url( $item->ID, 'full' );
		$raw_title = get_the_title( $item->ID );
		echo '<div class="swiper-slide">';
		if ( $thumb_url ) {
			echo '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $raw_title ) . '">';
		}
		echo '<h3>' . esc_html( $raw_title ) . '</h3>';
		echo '</div>';
	}
	echo '</div>';
	echo '<div class="swiper-pagination"></div>';
	echo '<img src="https://robinshoes.nl/wp-content/uploads/2023/04/cropped-Logo_robinshoes.png" id="tv-logo" alt="Robin Shoes" />';
	echo '<input name="duration" id="duration" type="hidden" value="' . $count * 10 . '">'; //phpcs:ignore
	echo '</div>';
	return ob_get_clean();
}
