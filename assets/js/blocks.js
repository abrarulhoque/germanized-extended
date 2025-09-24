(function( wp ) {
    'use strict';

    if ( ! wp || ! wp.blocks || ! wp.hooks || ! wp.domReady ) {
        return;
    }

    var registerBlockVariation = wp.blocks.registerBlockVariation;
    var getBlockType = wp.blocks.getBlockType;
    var getBlockVariations = wp.blocks.getBlockVariations || wp.blocks.__experimentalGetBlockVariations;
    var addFilter = wp.hooks.addFilter;
    var __ = wp.i18n.__;
    var Fragment = wp.element.Fragment;
    var createElement = wp.element.createElement;
    var createHigherOrderComponent = wp.compose && wp.compose.createHigherOrderComponent;

    var VARIATION_NAME = 'gaed-aed-total-row';

    addFilter(
        'blocks.registerBlockType',
        'gaed/aed-total-row/attributes',
        function( settings, name ) {
            if ( name !== 'storeabill/item-total-row' ) {
                return settings;
            }

            settings.attributes = Object.assign( {}, settings.attributes, {
                gaedIsAed: {
                    type: 'boolean',
                    default: false,
                },
            } );

            return settings;
        }
    );

    wp.domReady( function() {
        var baseBlock = getBlockType( 'storeabill/item-total-row' );

        if ( ! baseBlock || ! registerBlockVariation ) {
            return;
        }

        var existingVariations = getBlockVariations ? getBlockVariations( 'storeabill/item-total-row', 'block' ) : [];

        var alreadyRegistered = Array.isArray( existingVariations ) && existingVariations.some( function( variation ) {
            return variation && variation.name === VARIATION_NAME;
        } );

        if ( ! alreadyRegistered ) {
            registerBlockVariation( 'storeabill/item-total-row', {
                name: VARIATION_NAME,
                title: __( 'Item Total Row (AED)', 'germanized-aed-totals' ),
                description: __( 'Displays a StoreaBill item total converted to AED.', 'germanized-aed-totals' ),
                icon: baseBlock.icon || 'money-alt',
                attributes: {
                    gaedIsAed: true,
                    content: '{total_aed}',
                    heading: __( 'Total (AED)', 'germanized-aed-totals' ),
                },
                scope: [ 'block', 'inserter' ],
            } );
        }
    } );

    if ( createHigherOrderComponent ) {
        var withAedPreviewNote = createHigherOrderComponent( function( BlockEdit ) {
            return function( props ) {
                if ( props.name !== 'storeabill/item-total-row' || ! props.attributes.gaedIsAed ) {
                    return createElement( BlockEdit, props );
                }

                return createElement(
                    Fragment,
                    null,
                    createElement( BlockEdit, props ),
                    createElement(
                        'div',
                        { className: 'gaed-aed-total-note' },
                        __( 'Preview – actual amounts will be converted from EUR totals.', 'germanized-aed-totals' )
                    )
                );
            };
        }, 'withGAEDAedPreviewNote' );

        addFilter( 'editor.BlockEdit', 'gaed/aed-total-row/preview-note', withAedPreviewNote );
    }
})( window.wp );
