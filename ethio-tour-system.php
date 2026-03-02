<?php
/*
Plugin Name: Ethio Tour System
Description: World stanrad Ethiopian tour system for WooCommerce. Complete custom frontend.
Author: Amanuel Wasihun
Author URI: https://www.helloamanuel.co.uk
Version: 4.0
Requires at least: 6.0
Requires PHP: 8.0
WC requires at least: 7.0
*/
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Ethio Tour System</strong> requires WooCommerce to be installed and active.</p></div>';
    });
    
    // Prevent plugin from running
    return;
}
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ETS_VERSION', '4.0' );
define( 'ETS_DIR',     plugin_dir_path( __FILE__ ) );
define( 'ETS_URL',     plugin_dir_url( __FILE__ ) );

/*═══════════════════════════════════════
  FRONTEND SUBMISSION FORM
═══════════════════════════════════════*/
require_once ETS_DIR . 'includes/submit-form.php';


/*═══════════════════════════════════════
  DEFAULTS & CONSTANTS
═══════════════════════════════════════*/
define( 'ETS_DEFAULT_INCLUDES', implode( "\n", [
    'Professional licensed guide',
    'Hotel pickup and drop-off',
    'All entrance fees',
    'Bottled water',
    'Air-conditioned transport',
] ) );

define( 'ETS_DEFAULT_EXCLUDES', implode( "\n", [
    'Meals and drinks (unless stated)',
    'Gratuities / tips',
    'Personal expenses',
    'Travel insurance',
] ) );

function ets_cancellation_options() {
    return [
        ''              => '— Select cancellation policy —',
        'free_24h'      => 'Free cancellation up to 24 hours before start',
        'free_48h'      => 'Free cancellation up to 48 hours before start',
        'free_72h'      => 'Free cancellation up to 72 hours before start',
        'free_7d'       => 'Free cancellation up to 7 days before start',
        'non_refundable'=> 'Non-refundable — no cancellation allowed',
        'partial_50'    => '50% refund if cancelled 24h before start',
        'custom'        => 'Custom policy (see Terms of Service)',
    ];
}

function ets_languages_list() {
    return [ 'English','French', 'German', 'Spanish', 'Italian', 'Arabic', 'Chinese', 'Japanese', 'Portuguese', 'Russian'];
}

function ets_transport_modes() {
    return [
      
  'Private Sedan',
  '4x4 Land Cruiser',
  'Coaster Bus',
  'Domestic Flight',
  '4x4 + Flight',
  'Trekking (Mule)',
  'Camel Caravan',
  'Lake Boat',
  'Mixed Transport'

    ];
}

/*═══════════════════════════════════════
  REGISTER TAXONOMIES
═══════════════════════════════════════*/
add_action( 'init', function () {

    register_taxonomy( 'tour_type', 'product', [
        'label'             => 'Tour Types',
        'hierarchical'      => true,
        'rewrite'           => [ 'slug' => 'tour-type' ],
        'show_admin_column' => true,
        'show_in_rest'      => true,
    ] );

    register_taxonomy( 'tour_location', 'product', [
        'label'             => 'Tour Locations',
        'hierarchical'      => true,
        'rewrite'           => [ 'slug' => 'tour-location' ],
        'show_admin_column' => true,
        'show_in_rest'      => true,
    ] );

} );


/*═══════════════════════════════════════
  PRODUCT META FIELDS
═══════════════════════════════════════*/
add_action( 'woocommerce_product_options_general_product_data', function () {

    $pid = get_the_ID();
    echo '<div class="options_group">';

    woocommerce_wp_text_input( [ 'id' => '_tour_duration',    'label' => 'Tour Duration',   'placeholder' => 'e.g. Half Day / 3 Days' ] );
    woocommerce_wp_text_input( [ 'id' => '_tour_group_size',  'label' => 'Group Size',      'placeholder' => 'e.g. 2–12 People' ] );
    woocommerce_wp_text_input( [ 'id' => '_tour_start_point', 'label' => 'Start Point',     'placeholder' => 'e.g. Hotel Lobby' ] );
    woocommerce_wp_text_input( [ 'id' => '_tour_end_point',   'label' => 'End Point',       'placeholder' => 'e.g. Same as start point' ] );
    woocommerce_wp_text_input( [ 'id' => '_tour_whatsapp',    'label' => 'WhatsApp Number (with country code)', 'placeholder' => 'e.g. +447700900000' ] );

    // Languages — multi-select
    $saved_langs = get_post_meta( $pid, '_tour_languages', true );
    $saved_langs = $saved_langs ? array_map( 'trim', explode( ',', $saved_langs ) ) : [];
    echo '<p class="form-field _tour_languages_field"><label>Languages</label>';
    echo '<select name="_tour_languages[]" multiple style="height:100px;width:50%;">';
    foreach ( ets_languages_list() as $lang ) {
        $sel = in_array( $lang, $saved_langs, true ) ? 'selected' : '';
        echo '<option value="' . esc_attr( $lang ) . '" ' . $sel . '>' . esc_html( $lang ) . '</option>';
    }
    echo '</select> <span class="description">Hold Ctrl/Cmd to select multiple</span></p>';
	
	// Transportation Modes — multi-select
$saved_transport = get_post_meta( $pid, '_tour_transport', true );
$saved_transport = $saved_transport ? array_map( 'trim', explode( ',', $saved_transport ) ) : [];

echo '<p class="form-field _tour_transport_field"><label>Transportation</label>';
echo '<select name="_tour_transport[]" multiple style="height:100px;width:50%;">';

foreach ( ets_transport_modes() as $mode ) {
    $sel = in_array( $mode, $saved_transport, true ) ? 'selected' : '';
    echo '<option value="' . esc_attr( $mode ) . '" ' . $sel . '>' . esc_html( $mode ) . '</option>';
}

echo '</select> <span class="description">How travelers move during the tour</span></p>';

    // Cancellation — dropdown
    $saved_cancel = get_post_meta( $pid, '_tour_cancellation', true );
    echo '<p class="form-field _tour_cancellation_field"><label for="_tour_cancellation">Cancellation Policy</label>';
    echo '<select id="_tour_cancellation" name="_tour_cancellation" style="width:50%;">';
    foreach ( ets_cancellation_options() as $val => $label ) {
        echo '<option value="' . esc_attr( $val ) . '" ' . selected( $saved_cancel, $val, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select></p>';

    woocommerce_wp_checkbox( [ 'id' => '_tour_free_cancellation', 'label' => 'Free Cancellation',    'description' => 'Show free cancellation badge' ] );
    woocommerce_wp_checkbox( [ 'id' => '_tour_instant_confirm',   'label' => 'Instant Confirmation', 'description' => 'Show instant confirmation badge' ] );

    woocommerce_wp_select( [
        'id'      => '_tour_badge',
        'label'   => 'Badge',
        'options' => [ '' => '— None —', 'bestseller' => 'Best Seller', 'likely_to_sell' => 'Likely to Sell Out', 'new' => 'New', 'exclusive' => 'Exclusive' ],
    ] );

    woocommerce_wp_textarea_input( [ 'id' => '_tour_highlights',  'label' => 'Highlights (one per line)', 'rows' => 4 ] );

    // Includes / Excludes with defaults
    $inc_val = get_post_meta( $pid, '_tour_includes', true );
    $exc_val = get_post_meta( $pid, '_tour_excludes', true );
    woocommerce_wp_textarea_input( [ 'id' => '_tour_includes', 'label' => "What's Included (one per line)", 'rows' => 4, 'value' => $inc_val !== '' ? $inc_val : ETS_DEFAULT_INCLUDES ] );
    woocommerce_wp_textarea_input( [ 'id' => '_tour_excludes', 'label' => 'Not Included (one per line)',    'rows' => 4, 'value' => $exc_val !== '' ? $exc_val : ETS_DEFAULT_EXCLUDES ] );
    woocommerce_wp_textarea_input( [ 'id' => '_tour_know_before', 'label' => 'Know Before You Go (one per line)', 'rows' => 4 ] );

    echo '</div>';
} );


/*═══════════════════════════════════════
  META BOX — Itinerary only
═══════════════════════════════════════*/
add_action( 'add_meta_boxes', function () {
    add_meta_box( 'ets_itinerary', '📋 Tour Itinerary', 'ets_itinerary_box', 'product', 'normal', 'high' );
} );

function ets_itinerary_box( $post ) {
    $saved = get_post_meta( $post->ID, '_tour_itinerary', true );
    $json  = $saved ? esc_attr( wp_json_encode( $saved ) ) : '[]';
    echo '<div id="ets-itinerary-rows"></div>';
    echo '<button type="button" class="button button-primary" id="ets-add-day" style="margin-top:10px;">+ Add Day / Stop</button>';
    echo '<input type="hidden" id="ets-itinerary-json" name="ets_itinerary_json" value="' . $json . '">';
}


/*═══════════════════════════════════════
  SAVE META
═══════════════════════════════════════*/
add_action( 'woocommerce_process_product_meta', function ( $post_id ) {

    $text = [ '_tour_duration', '_tour_group_size', '_tour_start_point', '_tour_end_point', '_tour_badge', '_tour_whatsapp', '_tour_cancellation' ];
    foreach ( $text as $f ) {
        update_post_meta( $post_id, $f, sanitize_text_field( $_POST[$f] ?? '' ) );
    }
	
	// Transportation — multi-select → comma-separated
$transport = isset( $_POST['_tour_transport'] ) ? (array) $_POST['_tour_transport'] : [];
$transport = array_map( 'sanitize_text_field', $transport );
update_post_meta( $post_id, '_tour_transport', implode( ',', $transport ) );

    // Languages — multi-select → comma-separated
    $langs = isset( $_POST['_tour_languages'] ) ? (array) $_POST['_tour_languages'] : [];
    $langs = array_map( 'sanitize_text_field', $langs );
    update_post_meta( $post_id, '_tour_languages', implode( ',', $langs ) );

    $textarea = [ '_tour_highlights', '_tour_includes', '_tour_excludes', '_tour_know_before' ];
    foreach ( $textarea as $f ) {
        update_post_meta( $post_id, $f, sanitize_textarea_field( $_POST[$f] ?? '' ) );
    }

    $checkbox = [ '_tour_free_cancellation', '_tour_instant_confirm' ];
    foreach ( $checkbox as $f ) {
        update_post_meta( $post_id, $f, isset( $_POST[$f] ) ? 'yes' : 'no' );
    }

    if ( ! empty( $_POST['ets_itinerary_json'] ) ) {
        $rows  = json_decode( stripslashes( $_POST['ets_itinerary_json'] ), true );
        $clean = [];
        if ( is_array( $rows ) ) {
            foreach ( $rows as $r ) {
                $clean[] = [
                    'label'         => sanitize_text_field( $r['label'] ?? '' ),
                    'title'         => sanitize_text_field( $r['title'] ?? '' ),
                    'description'   => wp_kses_post( $r['description'] ?? '' ),
                    'accommodation' => sanitize_text_field( $r['accommodation'] ?? '' ),
                    'meals'         => sanitize_text_field( $r['meals'] ?? '' ),
                ];
            }
        }
        update_post_meta( $post_id, '_tour_itinerary', $clean );
    }

} );


/*═══════════════════════════════════════
  NUCLEAR LAYOUT TAKEOVER
═══════════════════════════════════════*/
add_action( 'wp', function () {
    if ( ! is_singular( 'product' ) ) return;

    $wc_hooks = [
        [ 'woocommerce_before_main_content',            'woocommerce_output_content_wrapper',     10 ],
        [ 'woocommerce_before_main_content',            'woocommerce_breadcrumb',                 20 ],
        [ 'woocommerce_after_main_content',             'woocommerce_output_content_wrapper_end', 10 ],
        [ 'woocommerce_before_single_product',          'woocommerce_output_all_notices',         10 ],
        [ 'woocommerce_before_single_product_summary',  'woocommerce_show_product_sale_flash',    10 ],
        [ 'woocommerce_before_single_product_summary',  'woocommerce_show_product_images',        20 ],
        [ 'woocommerce_single_product_summary',         'woocommerce_template_single_title',      5  ],
        [ 'woocommerce_single_product_summary',         'woocommerce_template_single_rating',     10 ],
        [ 'woocommerce_single_product_summary',         'woocommerce_template_single_price',      10 ],
        [ 'woocommerce_single_product_summary',         'woocommerce_template_single_excerpt',    20 ],
        [ 'woocommerce_single_product_summary',         'woocommerce_template_single_add_to_cart',30 ],
        [ 'woocommerce_single_product_summary',         'woocommerce_template_single_meta',       40 ],
        [ 'woocommerce_single_product_summary',         'woocommerce_template_single_sharing',    50 ],
        [ 'woocommerce_after_single_product_summary',   'woocommerce_output_product_data_tabs',   10 ],
        [ 'woocommerce_after_single_product_summary',   'woocommerce_upsell_display',             15 ],
        [ 'woocommerce_after_single_product_summary',   'woocommerce_output_related_products',    20 ],
        [ 'woocommerce_sidebar',                        'woocommerce_get_sidebar',                10 ],
    ];
    foreach ( $wc_hooks as $h ) remove_action( $h[0], $h[1], $h[2] );

    add_filter( 'woocommerce_show_page_title',    '__return_false' );
    add_filter( 'woocommerce_breadcrumb_defaults', '__return_empty_array' );
    add_action( 'woocommerce_before_single_product', 'ets_render_single_tour', 5 );

    add_filter( 'body_class', function( $classes ) {
        $classes[] = 'ets-product-page';
        return $classes;
    } );
} );


/*═══════════════════════════════════════
  RENDER SINGLE TOUR — v4.0
═══════════════════════════════════════*/
function ets_render_single_tour() {

    global $product;
    if ( ! $product ) $product = wc_get_product( get_the_ID() );
    if ( ! $product ) return;

    $id = $product->get_id();

    /* ---- Meta ---- */
    $duration    = get_post_meta( $id, '_tour_duration', true );
    $group_size  = get_post_meta( $id, '_tour_group_size', true );
	
    $start_pt    = get_post_meta( $id, '_tour_start_point', true );
    $end_pt      = get_post_meta( $id, '_tour_end_point', true );
    $whatsapp    = get_post_meta( $id, '_tour_whatsapp', true );
    $cancel_key  = get_post_meta( $id, '_tour_cancellation', true );
    $free_cancel = get_post_meta( $id, '_tour_free_cancellation', true ) === 'yes';
    $instant     = get_post_meta( $id, '_tour_instant_confirm', true ) === 'yes';
    $badge_key   = get_post_meta( $id, '_tour_badge', true );
    $itinerary   = get_post_meta( $id, '_tour_itinerary', true ) ?: [];
	$transport_raw = get_post_meta( $id, '_tour_transport', true );
$transport     = $transport_raw ? implode( ', ', array_map( 'trim', explode( ',', $transport_raw ) ) ) : '';

    $langs_raw   = get_post_meta( $id, '_tour_languages', true );
    $languages   = $langs_raw ? implode( ', ', array_map( 'trim', explode( ',', $langs_raw ) ) ) : '';

    $highlights  = array_filter( array_map( 'trim', explode( "\n", get_post_meta( $id, '_tour_highlights', true ) ) ) );

    $inc_raw     = get_post_meta( $id, '_tour_includes', true );
    $exc_raw     = get_post_meta( $id, '_tour_excludes', true );
    $includes    = array_filter( array_map( 'trim', explode( "\n", $inc_raw !== '' ? $inc_raw : ETS_DEFAULT_INCLUDES ) ) );
    $excludes    = array_filter( array_map( 'trim', explode( "\n", $exc_raw !== '' ? $exc_raw : ETS_DEFAULT_EXCLUDES ) ) );
    $know        = array_filter( array_map( 'trim', explode( "\n", get_post_meta( $id, '_tour_know_before', true ) ) ) );

    /* ---- Cancellation ---- */
    $cancel_opts  = ets_cancellation_options();
    $cancel_label = $cancel_opts[ $cancel_key ] ?? '';
    $tos_page     = get_page_by_path( 'terms-of-service' );
    $tos_url      = $tos_page ? get_permalink( $tos_page ) : home_url( '/terms-of-service/' );

    /* ---- Taxonomies ---- */
    $types     = get_the_terms( $id, 'tour_type' );
    $locations = get_the_terms( $id, 'tour_location' );
    $type_name = ( $types && ! is_wp_error( $types ) )     ? $types[0]->name     : '';
    $type_link = ( $types && ! is_wp_error( $types ) )     ? get_term_link( $types[0] ) : '#';
    $loc_name  = ( $locations && ! is_wp_error( $locations ) ) ? $locations[0]->name : '';

    /* ---- Gallery ---- */
    $gallery_ids  = array_merge( [ get_post_thumbnail_id( $id ) ], $product->get_gallery_image_ids() );
    $gallery_ids  = array_filter( $gallery_ids );

    /* ---- Rating ---- */
    $rating       = $product->get_average_rating();
    $review_count = $product->get_review_count();

    /* ---- WhatsApp ---- */
    $wa_number  = preg_replace( '/[^0-9]/', '', $whatsapp );
    $wa_message = urlencode( 'Hi, I\'m interested in booking: ' . get_the_title() );
    $wa_url     = $wa_number ? 'https://wa.me/' . $wa_number . '?text=' . $wa_message : 'https://wa.me/';

    /* ---- Pricing ---- */
    $base_price      = (float) $product->get_price();
    $currency_symbol = get_woocommerce_currency_symbol();

    /* ---- Badge labels ---- */
    $badge_labels = [
        'bestseller'     => 'Best Seller',
        'likely_to_sell' => 'Likely to Sell Out',
        'new'            => 'New',
        'exclusive'      => 'Exclusive',
    ];

    ob_start();

    // Force-enqueue styles right now in case wp_enqueue_scripts already ran
    if ( ! wp_style_is( 'ets-css', 'done' ) ) {
        wp_enqueue_style( 'ets-css', ETS_URL . 'assets/css/ets-frontend.css', [], ETS_VERSION );
    }
    if ( ! wp_script_is( 'ets-js', 'done' ) && ! wp_script_is( 'ets-js', 'enqueued' ) ) {
        wp_enqueue_script( 'ets-js', ETS_URL . 'assets/js/ets-frontend.js', [], ETS_VERSION, true );
    }
    ?>

<!-- ████████ ETS SINGLE TOUR v4.0 ████████ -->
<?php if ( ! wp_style_is( 'ets-css', 'done' ) ) : ?>
<link rel="stylesheet" href="<?php echo esc_url( ETS_URL . 'assets/css/ets-frontend.css?v=' . ETS_VERSION ); ?>" media="all">
<?php endif; ?>
<div class="ets-wrap ets-single" id="ets-tour-<?php echo $id; ?>">

   <!-- ── Breadcrumb ── -->
<div class="ets-breadcrumb-bar">
    <nav class="ets-breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url( home_url() ); ?>">Home</a>
        <span aria-hidden="true">›</span>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>">Tours</a>

        <?php
        // Get primary product category
        $primary_cat  = '';
        $primary_link = '#';
        $cats = get_the_terms( $id, 'product_cat' );
        if ( $cats && ! is_wp_error( $cats ) ) {
            $primary = $cats[0]; // first category
            $primary_cat  = $primary->name;
            $primary_link = get_term_link( $primary );
        }

        // Optional: Use Yoast Primary Category if available
        if ( class_exists('WPSEO_Primary_Term') ) {
            $wpseo_primary_term = new WPSEO_Primary_Term( 'product_cat', $id );
            $primary_term_id    = $wpseo_primary_term->get_primary_term();
            $primary_term       = get_term( $primary_term_id );
            if ( ! is_wp_error( $primary_term ) ) {
                $primary_cat  = $primary_term->name;
                $primary_link = get_term_link( $primary_term );
            }
        }

        if ( $primary_cat ) : ?>
            <span aria-hidden="true">›</span>
            <a href="<?php echo esc_url( $primary_link ); ?>"><?php echo esc_html( $primary_cat ); ?></a>
        <?php endif; ?>

        <span aria-hidden="true">›</span>
        <span class="ets-breadcrumb-current" aria-current="page"><?php echo esc_html( get_the_title() ); ?></span>
    </nav>
</div>

    <!-- ── Hero Title Area ── -->
    <div class="ets-hero-area">
        <div class="ets-hero-inner">
            <div class="ets-badge-row">
                <?php if ( $free_cancel ) : ?>
                    <span class="ets-tag ets-tag--green">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Free Cancellation
                    </span>
                <?php endif; ?>
                <?php if ( $instant ) : ?>
                    <span class="ets-tag ets-tag--amber">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        Instant Confirmation
                    </span>
                <?php endif; ?>
                <?php if ( $badge_key && isset( $badge_labels[$badge_key] ) ) : ?>
                    <span class="ets-tag ets-tag--red"><?php echo esc_html( $badge_labels[$badge_key] ); ?></span>
                <?php endif; ?>
            </div>

            <h1 class="ets-tour-title"><?php echo esc_html( get_the_title() ); ?></h1>

            <div class="ets-title-meta">
                <?php if ( $rating ) : ?>
                    <div class="ets-star-row">
                        <span class="ets-stars-display"><?php echo ets_stars_html( (float) $rating ); ?></span>
                        <strong><?php echo number_format( (float) $rating, 1 ); ?></strong>
                        <?php if ( $review_count ) : ?>
                            <span class="ets-review-count"><?php echo number_format( $review_count ); ?> Reviews</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $loc_name ) : ?>
                    <span class="ets-meta-pill ets-meta-pill--location">
                        <svg width="11" height="13" viewBox="0 0 24 28" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/></svg>
                        <?php echo esc_html( $loc_name ); ?>
                    </span>
                <?php endif; ?>
                <?php if ( $type_name ) : ?>
                    <span class="ets-meta-pill ets-meta-pill--category">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                        <?php echo esc_html( $type_name ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Main 2-Column Layout ── -->
    <div class="ets-page-layout">

        <!-- ══ LEFT COLUMN ══ -->
        <div class="ets-content-col">

            <!-- GALLERY: Viator-style (thumbs left, main right) -->
            <?php if ( ! empty( $gallery_ids ) ) : ?>
            <div class="ets-gallery-viator" id="ets-gallery-viator">
                <!-- Left: thumbnail strip -->
                <div class="ets-gallery-thumbstrip" id="ets-thumbstrip">
                    <?php
                    $all_fulls = [];
                    foreach ( $gallery_ids as $i => $att_id ) :
                        $thumb = wp_get_attachment_image_url( $att_id, 'medium' );
                        $full  = wp_get_attachment_image_url( $att_id, 'large' );
                        $all_fulls[] = $full;
                        if ( $i >= 5 ) {
                            // "See More" as last thumb
                            if ( $i === 5 ) : ?>
                                <div class="ets-gthumb ets-gthumb-more"
                                     data-index="<?php echo $i; ?>"
                                     style="background-image:url('<?php echo esc_url( $thumb ); ?>');">
                                    <div class="ets-gthumb-more-overlay">See More</div>
                                </div>
                            <?php endif;
                            continue;
                        }
                    ?>
                    <div class="ets-gthumb <?php echo $i === 0 ? 'is-active' : ''; ?>"
                         data-index="<?php echo $i; ?>"
                         style="background-image:url('<?php echo esc_url( $thumb ); ?>');">
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Right: main image -->
                <?php
                $first_full  = wp_get_attachment_image_url( $gallery_ids[0], 'large' );
                $first_src   = wp_get_attachment_image_url( $gallery_ids[0], 'large' );
                ?>
                <div class="ets-gallery-mainpane" id="ets-gallery-mainpane">
                    <div class="ets-gallery-main-img" id="ets-gmain"
                         style="background-image:url('<?php echo esc_url( $first_src ); ?>');"
                         data-fulls="<?php echo esc_attr( wp_json_encode( $all_fulls ) ); ?>">
                        <?php if ( count( $gallery_ids ) > 1 ) : ?>
                        <button class="ets-gnav ets-gnav-prev" id="ets-gnav-prev" aria-label="Previous">‹</button>
                        <button class="ets-gnav ets-gnav-next" id="ets-gnav-next" aria-label="Next">›</button>
                        <?php endif; ?>
                        <button class="ets-gallery-expand-btn" id="ets-open-gallery">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            See all <?php echo count( $gallery_ids ); ?> photos
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

<!-- Quick Facts Bar -->
<?php
$icons = [
    'duration' => '
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>
            <path d="M12 7v6l4 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>',
    'group' => '
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <circle cx="9" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
            <circle cx="17" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
            <path d="M3 20c0-3 3-5 6-5s6 2 6 5" fill="none" stroke="currentColor" stroke-width="2"/>
        </svg>',
	'transport' => '
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
        <rect x="3" y="7" width="18" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
        <circle cx="7" cy="17" r="2"/>
        <circle cx="17" cy="17" r="2"/>
    </svg>',
    'start' => '
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path d="M12 22s7-7 7-12a7 7 0 1 0-14 0c0 5 7 12 7 12z" fill="none" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="10" r="2" fill="currentColor"/>
        </svg>',
    'end' => '
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path d="M4 4v16M6 4h10l-2 4 2 4H6" fill="none" stroke="currentColor" stroke-width="2"/>
        </svg>',
    'language' => '
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/>
            <path d="M3 12h18M12 3c3 4 3 14 0 18M12 3c-3 4-3 14 0 18" fill="none" stroke="currentColor" stroke-width="2"/>
        </svg>',
];

$facts = [];

if ( $duration )   $facts[] = [ $icons['duration'], 'Duration',   $duration   ];
if ( $group_size ) $facts[] = [ $icons['group'],    'Group Size', $group_size ];
	if ( $transport ) $facts[] = [ $icons['transport'], 'Transport', $transport ];
// if ( $start_pt )   $facts[] = [ $icons['start'],    'Starts',     $start_pt   ];
// if ( $end_pt )     $facts[] = [ $icons['end'],      'Ends',       $end_pt     ];
if ( $languages )  $facts[] = [ $icons['language'], 'Languages',  $languages  ];

if ( ! empty( $facts ) ) :
?>
<div class="ets-facts-bar">
    <?php foreach ( $facts as [ $icon, $lbl, $val ] ) : ?>
        <div class="ets-fact-item">
            <span class="ets-fact-icon"><?php echo $icon; ?></span>
            <div>
                <div class="ets-fact-label"><?php echo esc_html( $lbl ); ?></div>
                <div class="ets-fact-value"><?php echo esc_html( $val ); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
			
			<!-- Overview -->
            <?php $desc = $product->get_description() ?: $product->get_short_description(); if ( $desc ) : ?>
            <div class="ets-section">
                <h2 class="ets-section-h">Overview</h2>
                <div class="ets-desc"><?php echo wp_kses_post( $desc ); ?></div>
            </div>
            <?php endif; ?>

            <!-- Highlights -->
            <?php if ( $highlights ) : ?>
            <div class="ets-section">
                <h2 class="ets-section-h">Highlights</h2>
                <ul class="ets-highlight-list">
                    <?php foreach ( $highlights as $h ) : ?><li><?php echo esc_html( $h ); ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Includes / Excludes -->
            <?php if ( $includes || $excludes ) : ?>
            <div class="ets-section">
                <h2 class="ets-section-h">What's Included</h2>
                <div class="ets-inc-exc-grid">
                    <?php if ( $includes ) : ?>
                    <div>
                        <p class="ets-inc-head">✓ Included</p>
                        <ul class="ets-inc-list">
                            <?php foreach ( $includes as $item ) : ?><li class="ets-inc-item"><span class="ets-icon-check">✓</span><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php if ( $excludes ) : ?>
                    <div>
                        <p class="ets-exc-head">✕ Not Included</p>
                        <ul class="ets-inc-list">
                            <?php foreach ( $excludes as $item ) : ?><li class="ets-exc-item"><span class="ets-icon-cross">✕</span><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Itinerary with Read More toggle -->
            <?php if ( $itinerary ) : $show_toggle = count( $itinerary ) > 3; ?>
            <div class="ets-section">
                <h2 class="ets-section-h">Itinerary</h2>
                <div class="ets-itin-wrap" id="ets-itin-wrap" data-expanded="false">
                    <?php foreach ( $itinerary as $idx => $day ) : ?>
                    <div class="ets-itin-row<?php echo ( $show_toggle && $idx >= 3 ) ? ' ets-itin-hidden' : ''; ?>">
                        <div class="ets-itin-aside">
                            <div class="ets-itin-dot"><?php echo esc_html( $day['label'] ?: ( 'D'.($idx+1) ) ); ?></div>
                            <?php if ( $idx < count($itinerary) - 1 ) : ?><div class="ets-itin-line"></div><?php endif; ?>
                        </div>
                        <div class="ets-itin-body">
                            <?php if ( $day['title'] ) : ?><strong class="ets-itin-title"><?php echo esc_html( $day['title'] ); ?></strong><?php endif; ?>
                            <?php if ( $day['description'] ) : ?><p class="ets-itin-desc"><?php echo wp_kses_post( $day['description'] ); ?></p><?php endif; ?>
                            <div class="ets-itin-chips">
                                <?php if ( ! empty( $day['accommodation'] ) ) echo '<span class="ets-chip">🏨 ' . esc_html( $day['accommodation'] ) . '</span>'; ?>
                                <?php if ( ! empty( $day['meals'] ) )         echo '<span class="ets-chip">🍽 ' . esc_html( $day['meals'] ) . '</span>'; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if ( $show_toggle ) : ?>
                    <button class="ets-itin-toggle" id="ets-itin-toggle" type="button">
                        <span class="ets-itin-toggle-label">Read More</span>
                        <svg class="ets-itin-toggle-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Know Before You Go -->
            <?php if ( $know ) : ?>
            <div class="ets-section">
                <h2 class="ets-section-h">Good to Know</h2>
                <ul class="ets-know-list">
                    <?php foreach ( $know as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Cancellation Policy -->
            <?php if ( $cancel_label && $cancel_key ) : ?>
            <div class="ets-section">
                <h2 class="ets-section-h">Cancellation Policy</h2>
                <div class="ets-cancel-box">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/></svg>
                    <div>
                        <p class="ets-cancel-label"><?php echo esc_html( $cancel_label ); ?></p>
                        <a href="<?php echo esc_url( $tos_url ); ?>" class="ets-cancel-tos-link" target="_blank" rel="noopener noreferrer">View full Terms of Service →</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /content-col -->


        <!-- ══ RIGHT: BOOKING SIDEBAR ══ -->
        <aside class="ets-sidebar-col">

            <div class="ets-booking-card" id="ets-booking-card">

                <!-- Price header -->
              <div class="ets-booking-header">
    <?php if ( $base_price > 0 ) : ?>
        <div class="ets-booking-from">From</div>
        <div class="ets-booking-price"><?php echo $product->get_price_html(); ?></div>
        <div class="ets-booking-per">per person</div>
    <?php else : ?>
        <div class="ets-booking-price ets-price-on-request">
            Price on request
        </div>
    <?php endif; ?>
</div>

                <div class="ets-booking-body">

                    <!-- Trust signals -->
                    <?php if ( $free_cancel || $instant ) : ?>
                    <div class="ets-booking-trust">
                        <?php if ( $free_cancel ) : ?><span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Free cancellation</span><?php endif; ?>
                        <?php if ( $instant ) :     ?><span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Instant confirmation</span><?php endif; ?>
                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Reserve now &amp; pay later</span>
                    </div>
                    <?php endif; ?>

                    <!-- Traveler count -->
                    <div class="ets-qty-row">
                        <label class="ets-qty-label">Travelers</label>
                        <div class="ets-qty-ctrl">
                            <button type="button" class="ets-qty-btn" id="ets-qty-minus" aria-label="Decrease">−</button>
                            <input type="number" id="ets-qty-input" class="ets-qty-input" value="1" min="1" max="99" readonly>
                            <button type="button" class="ets-qty-btn" id="ets-qty-plus" aria-label="Increase">+</button>
                        </div>
                    </div>

                    <!-- Dynamic total price -->
                    <?php if ( $base_price > 0 ) : ?>
                    <div class="ets-total-row" id="ets-total-row">
                        <span class="ets-total-label">Total</span>
                        <span class="ets-total-price" id="ets-total-price"><?php echo esc_html( $currency_symbol . number_format( $base_price, 2 ) ); ?></span>
                    </div>
                    <input type="hidden" id="ets-base-price" value="<?php echo esc_attr( $base_price ); ?>">
                    <input type="hidden" id="ets-currency-symbol" value="<?php echo esc_attr( $currency_symbol ); ?>">
                    <?php endif; ?>

                    <!-- CTA buttons -->
                    <div class="ets-cta-group">
                        <?php
                        // Rename "Add to Cart" → "Book Now"
                        add_filter( 'woocommerce_product_single_add_to_cart_text', function() { return 'Book Now'; }, 20 );
                        woocommerce_template_single_add_to_cart();
                        ?>

                        <a href="<?php echo esc_url( $wa_url ); ?>"
                           class="ets-whatsapp-btn"
                           target="_blank"
                           rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Chat on WhatsApp
                        </a>
                    </div>

                    <p class="ets-booking-secure">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Secure booking — no hidden fees
                    </p>

                </div><!-- /booking-body -->

                <?php if ( $rating ) : ?>
                <div class="ets-booking-rating">
                    <div class="ets-br-score"><?php echo number_format( (float) $rating, 1 ); ?></div>
                    <div>
                        <div class="ets-stars-sm"><?php echo ets_stars_html( (float) $rating ); ?></div>
                        <?php if ( $review_count ) : ?>
                            <div class="ets-br-count"><?php echo number_format( $review_count ); ?> verified reviews</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /booking-card -->

            <!-- Why Book This Tour -->
            <div class="ets-why-book">
                <h3 class="ets-why-title">Why Book This Tour?</h3>
                <ul class="ets-why-list">
                    <li><span class="ets-why-icon">✦</span> Expert local guides with deep knowledge</li>
                    <li><span class="ets-why-icon">✦</span> Small groups for a personal experience</li>
                    <li><span class="ets-why-icon">✦</span> Flexible cancellation on most tours</li>
                    <li><span class="ets-why-icon">✦</span> 24/7 WhatsApp support throughout your trip</li>
                    <li><span class="ets-why-icon">✦</span> Best-price guarantee — no hidden charges</li>
                </ul>
            </div>

        </aside><!-- /sidebar-col -->

    </div><!-- /page-layout -->

</div><!-- /ets-single -->
<!-- ████████ /ETS v4.0 ████████ -->

    <?php
    echo ob_get_clean();

    add_filter( 'the_content', function( $content ) {
        static $done = false;
        if ( ! $done && is_singular( 'product' ) ) { $done = true; return ''; }
        return $content;
    }, 999 );
}


/*═══════════════════════════════════════
  TEMPLATE OVERRIDE
═══════════════════════════════════════*/
add_filter( 'template_include', function ( $template ) {
    if ( is_singular( 'product' ) ) {
        $custom = ETS_DIR . 'templates/single-tour-template.php';
        if ( file_exists( $custom ) ) return $custom;
    }
    if ( is_tax( 'tour_type' ) || is_tax( 'tour_location' ) ) {
        $custom = ETS_DIR . 'templates/tours-archive.php';
        if ( file_exists( $custom ) ) return $custom;
    }
    return $template;
}, 99 );


/*═══════════════════════════════════════
  ENQUEUE ASSETS
  Always load on all frontend pages — scoped to .ets-wrap.
  Conditional checks can silently fail when template_include
  or a custom theme intercepts the request early.
═══════════════════════════════════════*/
add_action( 'wp_enqueue_scripts', function () {
    if ( is_admin() ) return;

    wp_enqueue_style(
        'ets-css',
        ETS_URL . 'assets/css/ets-frontend.css',
        [],
        ETS_VERSION
    );
    wp_enqueue_script(
        'ets-js',
        ETS_URL . 'assets/js/ets-frontend.js',
        [],
        ETS_VERSION,
        true
    );
}, 5 );

/*═══════════════════════════════════════
  INLINE STYLE FALLBACK via wp_head
  Guarantees CSS loads even if wp_enqueue_scripts fires too late
  or the theme's template bypass causes styles to be dropped.
═══════════════════════════════════════*/
add_action( 'wp_head', function () {
    if ( ! is_singular( 'product' ) && ! is_tax( 'tour_type' ) && ! is_tax( 'tour_location' ) ) return;
    $css_file = ETS_DIR . 'assets/css/ets-frontend.css';
    if ( ! file_exists( $css_file ) ) return;
    // Only inject inline if the handle wasn't already output
    global $wp_styles;
    if ( ! empty( $wp_styles->done ) && in_array( 'ets-css', $wp_styles->done, true ) ) return;
    echo '<link rel="stylesheet" id="ets-css" href="' . esc_url( ETS_URL . 'assets/css/ets-frontend.css?v=' . ETS_VERSION ) . '" media="all">' . "
";
    echo '<script defer src="' . esc_url( ETS_URL . 'assets/js/ets-frontend.js?v=' . ETS_VERSION ) . '"></script>' . "
";
}, 1 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    global $post;
    $post_type = '';
    if      ( isset( $post->post_type ) )     $post_type = $post->post_type;
    elseif  ( ! empty( $_GET['post_type'] ) ) $post_type = sanitize_key( $_GET['post_type'] );
    elseif  ( ! empty( $_GET['post'] ) )      { $p = get_post( absint( $_GET['post'] ) ); if ( $p ) $post_type = $p->post_type; }
    if ( $post_type !== 'product' ) return;

    wp_enqueue_script( 'ets-admin-js', ETS_URL . 'assets/js/ets-admin.js', ['jquery'], ETS_VERSION, true );
    $pid = isset( $post->ID ) ? (int) $post->ID : 0;
    wp_localize_script( 'ets-admin-js', 'ets_admin', [
        'itinerary' => $pid ? ( get_post_meta( $pid, '_tour_itinerary', true ) ?: [] ) : [],
    ] );
} );


/*═══════════════════════════════════════
  HELPERS
═══════════════════════════════════════*/
function ets_stars_html( float $rating ) : string {
    $out   = '';
    $full  = (int) floor( $rating );
    $half  = ( $rating - $full ) >= 0.4;
    $empty = 5 - $full - (int) $half;
    for ( $i = 0; $i < $full;  $i++ ) $out .= '<span class="ets-s full">★</span>';
    if  ( $half )                      $out .= '<span class="ets-s half">★</span>';
    for ( $i = 0; $i < $empty; $i++ ) $out .= '<span class="ets-s empty">☆</span>';
    return $out;
}
/*═══════════════════════════════════════
  PRICE FALLBACK — Price on Request
═══════════════════════════════════════*/
add_filter( 'woocommerce_get_price_html', function ( $price_html, $product ) {

    if ( ! $product instanceof WC_Product ) {
        return $price_html;
    }

    $price = (float) $product->get_price();

    if ( $price <= 0 ) {
        return '<span class="ets-price-on-request">Price on request</span>';
    }

    return $price_html;

}, 20, 2 );

/*═══════════════════════════════════════
  SHORTCODE: [ets_tours]
═══════════════════════════════════════*/
add_shortcode( 'ets_tours', function ( $atts ) {
    $atts = shortcode_atts( [ 'type' => '', 'location' => '', 'limit' => 9 ], $atts );
    $args = [ 'post_type' => 'product', 'posts_per_page' => (int) $atts['limit'], 'post_status' => 'publish' ];
    $tq   = [];
    if ( $atts['type'] )     $tq[] = [ 'taxonomy' => 'tour_type',     'field' => 'slug', 'terms' => $atts['type'] ];
    if ( $atts['location'] ) $tq[] = [ 'taxonomy' => 'tour_location', 'field' => 'slug', 'terms' => $atts['location'] ];
    if ( $tq ) $args['tax_query'] = $tq;

    $q = new WP_Query( $args );
    ob_start();
    if ( $q->have_posts() ) {
        echo '<div class="ets-tour-grid">';
        while ( $q->have_posts() ) { $q->the_post(); ets_render_tour_card( get_the_ID() ); }
        echo '</div>';
        wp_reset_postdata();
    }
    return ob_get_clean();
} );


/*═══════════════════════════════════════
  TOUR CARD
═══════════════════════════════════════*/
function ets_render_tour_card( $post_id ) {
    $product = wc_get_product( $post_id );
    if ( ! $product ) return;
    $duration  = get_post_meta( $post_id, '_tour_duration', true );
    $group     = get_post_meta( $post_id, '_tour_group_size', true );
    $badge_key = get_post_meta( $post_id, '_tour_badge', true );
    $free_can  = get_post_meta( $post_id, '_tour_free_cancellation', true ) === 'yes';
    $rating    = $product->get_average_rating();
    $rev_count = $product->get_review_count();
    $badge_labels = [ 'bestseller' => 'Best Seller', 'likely_to_sell' => 'Likely to Sell Out', 'new' => 'New', 'exclusive' => 'Exclusive' ];
    ?>
    <div class="ets-card">
        <a href="<?php the_permalink( $post_id ); ?>" class="ets-card-img-wrap">
            <?php if ( has_post_thumbnail( $post_id ) ) : echo get_the_post_thumbnail( $post_id, 'medium_large', ['class'=>'ets-card-img','loading'=>'lazy'] ); else : ?><div class="ets-card-img ets-card-no-img"></div><?php endif; ?>
            <?php if ( $badge_key && isset( $badge_labels[$badge_key] ) ) : ?><span class="ets-card-badge"><?php echo esc_html( $badge_labels[$badge_key] ); ?></span><?php endif; ?>
            <?php if ( $free_can ) : ?><span class="ets-card-cancel-badge">✓ Free Cancel</span><?php endif; ?>
        </a>
        <div class="ets-card-body">
            <?php if ( $rating ) : ?>
            <div class="ets-card-rating">
                <span class="ets-stars-sm"><?php echo ets_stars_html( (float) $rating ); ?></span>
                <strong><?php echo number_format( (float) $rating, 1 ); ?></strong>
                <?php if ( $rev_count ) : ?><span>(<?php echo number_format( $rev_count ); ?>)</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <h3 class="ets-card-title"><a href="<?php the_permalink( $post_id ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
            <div class="ets-card-pills">
                <?php if ( $duration ) echo '<span class="ets-pill">⏱ ' . esc_html( $duration ) . '</span>'; ?>
                <?php if ( $group )    echo '<span class="ets-pill">👥 ' . esc_html( $group )    . '</span>'; ?>
            </div>
            <div class="ets-card-footer">
                <div>
                    <div class="ets-card-from">From</div>
                    <div class="ets-card-price"><?php echo $product->get_price_html(); ?></div>
                </div>
                <a href="<?php the_permalink( $post_id ); ?>" class="ets-card-btn">View Tour</a>
            </div>
        </div>
    </div>
    <?php
}
