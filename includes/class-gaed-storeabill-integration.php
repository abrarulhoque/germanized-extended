<?php
/**
 * StoreaBill Integration class
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAED_StoreaBill_Integration {

    /**
     * Initialize the StoreaBill integration
     */
    public static function init() {
        // Hook into StoreaBill initialization
        add_action( 'init', array( __CLASS__, 'register_block_assets' ), 15 );
        add_action( 'init', array( __CLASS__, 'register_blocks' ), 20 );

        // Allow AED block inside StoreaBill editor
        add_filter( 'storeabill_document_template_editor_available_blocks', array( __CLASS__, 'allow_aed_block' ), 10, 3 );
        add_filter( 'allowed_block_types_all', array( __CLASS__, 'allow_block_type_in_editor' ), 20, 2 );
        add_filter( 'storeabill_document_template_editor_asset_whitelist_paths', array( __CLASS__, 'whitelist_asset_paths' ) );

        // Ensure block assets load within StoreaBill editor context
        add_action( 'storeabill_load_block_editor', array( __CLASS__, 'enqueue_editor_assets' ) );
        add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );

        // Add custom CSS for AED blocks
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_styles' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_styles' ) );
    }

    /**
     * Ensure StoreaBill does not dequeue our assets.
     *
     * @param array $paths Existing whitelist paths.
     * @return array
     */
    public static function whitelist_asset_paths( $paths ) {
        $plugin_slug = 'plugins/germanized-aed-totals';

        if ( ! in_array( $plugin_slug, $paths, true ) ) {
            $paths[] = $plugin_slug;
        }

        return $paths;
    }

    /**
     * Register block assets so WordPress can enqueue them when needed.
     */
    public static function register_block_assets() {
        wp_register_script(
            'gaed-blocks',
            GAED_PLUGIN_URL . 'assets/js/blocks.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-compose', 'wp-data' ),
            GAED_VERSION,
            true
        );

        wp_register_style(
            'gaed-blocks-editor',
            GAED_PLUGIN_URL . 'assets/css/blocks-editor.css',
            array( 'wp-edit-blocks' ),
            GAED_VERSION
        );

        wp_register_style(
            'gaed-blocks-frontend',
            GAED_PLUGIN_URL . 'assets/css/blocks-frontend.css',
            array(),
            GAED_VERSION
        );
    }

    /**
     * Enqueue assets when StoreaBill loads its block editor.
     */
    public static function enqueue_editor_assets() {
        wp_enqueue_script( 'gaed-blocks' );
        wp_enqueue_style( 'gaed-blocks-editor' );
    }

    /**
     * Register custom blocks
     */
    public static function register_blocks() {
        if ( ! class_exists( '\Vendidero\StoreaBill\Package' ) ) {
            return;
        }

        // Register the AED total block
        $aed_total_block = new GAED_AED_Total_Block();

        register_block_type(
            'gaed/aed-total-row',
            array(
                'render_callback' => array( $aed_total_block, 'render' ),
                'attributes'      => $aed_total_block->get_attributes(),
                'category'        => 'storeabill',
                'supports'        => array(),
                'editor_script'   => 'gaed-blocks',
                'editor_style'    => 'gaed-blocks-editor',
                'style'           => 'gaed-blocks-frontend',
            )
        );
    }

    /**
     * Add AED blocks to available blocks in StoreaBill editor
     *
     * @param array $blocks Available blocks
     * @return array Modified blocks array
     */
    public static function allow_aed_block( $blocks, $document_type, $template ) {
        if ( ! in_array( 'gaed/aed-total-row', $blocks, true ) ) {
            $blocks[] = 'gaed/aed-total-row';
        }

        return $blocks;
    }

    /**
     * Ensure the AED block is allowed within the block editor.
     *
     * @param array|bool               $allowed_block_types Existing allowed block types.
     * @param \WP_Block_Editor_Context $context             Editor context.
     *
     * @return array|bool
     */
    public static function allow_block_type_in_editor( $allowed_block_types, $context ) {
        if ( is_array( $allowed_block_types ) && isset( $context->post ) && 'document_template' === $context->post->post_type ) {
            if ( ! in_array( 'gaed/aed-total-row', $allowed_block_types, true ) ) {
                $allowed_block_types[] = 'gaed/aed-total-row';
            }
        }

        return $allowed_block_types;
    }

    /**
     * Enqueue frontend styles
     */
    public static function enqueue_frontend_styles() {
        if ( wp_style_is( 'gaed-blocks-frontend', 'registered' ) ) {
            wp_enqueue_style( 'gaed-blocks-frontend' );
            return;
        }

        wp_enqueue_style(
            'gaed-blocks-frontend',
            GAED_PLUGIN_URL . 'assets/css/blocks-frontend.css',
            array(),
            GAED_VERSION
        );
    }

    /**
     * Add AED totals to invoice template automatically
     *
     * @param array $totals_block The totals block data
     * @param object $document The document object
     * @return array Modified totals block
     */
    public static function add_aed_totals_automatically( $totals_block, $document ) {
        // Check if this is an invoice and if AED totals should be added automatically
        if ( ! is_a( $document, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
            return $totals_block;
        }

        // Check if auto-add is enabled
        $auto_add = get_option( 'gaed_auto_add_totals', false );
        if ( ! $auto_add ) {
            return $totals_block;
        }

        // Add AED total blocks after each regular total block
        if ( isset( $totals_block['innerBlocks'] ) && is_array( $totals_block['innerBlocks'] ) ) {
            $new_inner_blocks = array();

            foreach ( $totals_block['innerBlocks'] as $inner_block ) {
                // Add the original block
                $new_inner_blocks[] = $inner_block;

                // Check if this is a total row block
                if ( isset( $inner_block['blockName'] ) && $inner_block['blockName'] === 'storeabill/item-total-row' ) {
                    // Add corresponding AED total block
                    $aed_block = array(
                        'blockName'    => 'gaed/aed-total-row',
                        'attrs'        => array(
                            'totalType'   => isset( $inner_block['attrs']['totalType'] ) ? $inner_block['attrs']['totalType'] : 'total',
                            'heading'     => false, // Will auto-generate AED heading
                            'hideIfEmpty' => true,
                            'borders'     => array(),
                        ),
                        'innerBlocks'  => array(),
                        'innerHTML'    => '',
                        'innerContent' => array(),
                    );

                    $new_inner_blocks[] = $aed_block;
                }
            }

            $totals_block['innerBlocks'] = $new_inner_blocks;
        }

        return $totals_block;
    }
}
