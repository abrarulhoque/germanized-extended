/**
 * AED Total Block for StoreaBill Editor
 */
(function() {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl, ToggleControl } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el } = wp.element;

    // Register AED Total Row Block
    registerBlockType('gaed/aed-total-row', {
        title: __('AED Total Row', 'germanized-aed-totals'),
        description: __('Display invoice totals converted to AED currency', 'germanized-aed-totals'),
        icon: 'money-alt',
        category: 'storeabill',
        keywords: ['aed', 'currency', 'total', 'conversion'],

        attributes: {
            totalType: {
                type: 'string',
                default: 'total'
            },
            heading: {
                type: 'string',
                default: ''
            },
            content: {
                type: 'string',
                default: '{total_aed}'
            },
            hideIfEmpty: {
                type: 'boolean',
                default: false
            },
            borders: {
                type: 'array',
                default: []
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { totalType, heading, content, hideIfEmpty, borders } = attributes;

            const totalTypeOptions = [
                { label: __('Total', 'germanized-aed-totals'), value: 'total' },
                { label: __('Subtotal', 'germanized-aed-totals'), value: 'subtotal' },
                { label: __('Tax', 'germanized-aed-totals'), value: 'taxes' },
                { label: __('Shipping', 'germanized-aed-totals'), value: 'shipping' },
                { label: __('Fee', 'germanized-aed-totals'), value: 'fee' },
                { label: __('Discount', 'germanized-aed-totals'), value: 'discount' }
            ];

            const borderOptions = [
                { label: __('Top', 'germanized-aed-totals'), value: 'top' },
                { label: __('Bottom', 'germanized-aed-totals'), value: 'bottom' },
                { label: __('Left', 'germanized-aed-totals'), value: 'left' },
                { label: __('Right', 'germanized-aed-totals'), value: 'right' }
            ];

            return [
                el(InspectorControls, {},
                    el(PanelBody, {
                        title: __('AED Total Settings', 'germanized-aed-totals'),
                        initialOpen: true
                    },
                        el(SelectControl, {
                            label: __('Total Type', 'germanized-aed-totals'),
                            value: totalType,
                            options: totalTypeOptions,
                            onChange: function(value) {
                                setAttributes({ totalType: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('Custom Heading', 'germanized-aed-totals'),
                            value: heading,
                            placeholder: __('Leave empty for auto-generated heading', 'germanized-aed-totals'),
                            onChange: function(value) {
                                setAttributes({ heading: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('Content Template', 'germanized-aed-totals'),
                            value: content,
                            help: __('Use {total_aed} to display the AED amount', 'germanized-aed-totals'),
                            onChange: function(value) {
                                setAttributes({ content: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Hide if Empty', 'germanized-aed-totals'),
                            checked: hideIfEmpty,
                            onChange: function(value) {
                                setAttributes({ hideIfEmpty: value });
                            }
                        })
                    )
                ),
                el('div', {
                    className: 'gaed-total-block-preview',
                    style: {
                        border: '1px dashed #ccc',
                        padding: '15px',
                        margin: '10px 0',
                        backgroundColor: '#f9f9f9'
                    }
                },
                    el('table', { style: { width: '100%' } },
                        el('tbody', {},
                            el('tr', {},
                                el('td', { style: { fontWeight: 'bold' } },
                                    heading || (totalType.charAt(0).toUpperCase() + totalType.slice(1) + ' (AED)')
                                ),
                                el('td', { style: { textAlign: 'right' } },
                                    content.replace('{total_aed}', 'XXX.XX AED').replace('{total}', 'XXX.XX AED')
                                )
                            )
                        )
                    ),
                    el('p', { style: { fontSize: '12px', color: '#666', margin: '5px 0 0 0' } },
                        __('Preview - actual amounts will be calculated from EUR totals', 'germanized-aed-totals')
                    )
                )
            ];
        },

        save: function() {
            // Dynamic block - rendered on server side
            return null;
        }
    });

})();
