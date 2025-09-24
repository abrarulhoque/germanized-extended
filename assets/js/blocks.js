/**
 * AED Total Block for StoreaBill Editor
 * Simplified version to avoid dependency issues
 */
(function() {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { InspectorControls, RichText } = wp.blockEditor;
    const { PanelBody, SelectControl, TextControl, ToggleControl } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el } = wp.element;

    // Register AED Total Row Block
    registerBlockType('gaed/aed-total-row', {
        title: __('AED Total Row', 'germanized-aed-totals'),
        description: __('Display invoice totals converted to AED currency', 'germanized-aed-totals'),
        icon: 'money-alt',
        category: 'storeabill',
        parent: ['storeabill/item-totals'],
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
                default: '{total}'
            },
            hideIfEmpty: {
                type: 'boolean',
                default: false
            },
            borders: {
                type: 'array',
                default: []
            },
            borderColor: {
                type: 'string'
            },
            customBorderColor: {
                type: 'string'
            },
            backgroundColor: {
                type: 'string'
            },
            customBackgroundColor: {
                type: 'string'
            },
            textColor: {
                type: 'string'
            },
            customTextColor: {
                type: 'string'
            },
            fontSize: {
                type: 'string'
            },
            customFontSize: {
                type: 'string'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, className } = props;
            const { totalType, heading, content, hideIfEmpty, borders } = attributes;

            const totalTypeOptions = [
                { label: __('Total', 'germanized-aed-totals'), value: 'total' },
                { label: __('Subtotal', 'germanized-aed-totals'), value: 'subtotal' },
                { label: __('Tax', 'germanized-aed-totals'), value: 'taxes' },
                { label: __('Shipping', 'germanized-aed-totals'), value: 'shipping' },
                { label: __('Fee', 'germanized-aed-totals'), value: 'fee' },
                { label: __('Fees', 'germanized-aed-totals'), value: 'fees' },
                { label: __('Discount', 'germanized-aed-totals'), value: 'discount' },
                { label: __('Voucher', 'germanized-aed-totals'), value: 'voucher' }
            ];

            const borderOptions = [
                { label: __('Top', 'germanized-aed-totals'), value: 'top' },
                { label: __('Bottom', 'germanized-aed-totals'), value: 'bottom' }
            ];

            // Preview values
            const previewValues = {
                'total': '147.00 AED',
                'subtotal': '135.21 AED',
                'taxes': '25.83 AED',
                'shipping': '12.36 AED',
                'fee': '7.36 AED',
                'fees': '7.36 AED',
                'discount': '-12.36 AED',
                'voucher': '-14.72 AED'
            };

            const previewTotal = previewValues[totalType] || '0.00 AED';
            const displayHeading = heading || (totalType.charAt(0).toUpperCase() + totalType.slice(1) + ' (AED)');

            return [
                el(InspectorControls, { key: 'inspector' },
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
                            help: __('Use {total} to display the AED amount', 'germanized-aed-totals'),
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
                        }),
                        el('div', {
                            style: { marginTop: '16px' }
                        },
                            el('label', {
                                style: { 
                                    display: 'block',
                                    marginBottom: '8px',
                                    fontSize: '11px',
                                    fontWeight: '500',
                                    textTransform: 'uppercase',
                                    color: '#1e1e1e'
                                }
                            }, __('Borders', 'germanized-aed-totals')),
                            borderOptions.map(function(option) {
                                return el('label', {
                                    key: option.value,
                                    style: {
                                        display: 'block',
                                        marginBottom: '4px',
                                        fontSize: '13px'
                                    }
                                },
                                    el('input', {
                                        type: 'checkbox',
                                        checked: borders.includes(option.value),
                                        onChange: function(e) {
                                            let newBorders;
                                            if (e.target.checked) {
                                                newBorders = [...borders, option.value];
                                            } else {
                                                newBorders = borders.filter(b => b !== option.value);
                                            }
                                            setAttributes({ borders: newBorders });
                                        },
                                        style: { marginRight: '8px' }
                                    }),
                                    option.label
                                );
                            })
                        )
                    )
                ),
                el('div', {
                    key: 'edit',
                    className: className + ' item-total-row' + (borders.includes('top') ? ' has-border-top' : '') + (borders.includes('bottom') ? ' has-border-bottom' : ''),
                    style: {
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        padding: '8px 12px',
                        border: '1px dashed #ccc',
                        backgroundColor: '#f9f9f9',
                        borderTop: borders.includes('top') ? '2px solid #ddd' : undefined,
                        borderBottom: borders.includes('bottom') ? '2px solid #ddd' : undefined
                    }
                },
                    el('div', {
                        className: 'item-total-row-heading',
                        style: { fontWeight: '600', flex: '1' }
                    },
                        el(RichText, {
                            tagName: 'span',
                            placeholder: __('Insert heading', 'germanized-aed-totals'),
                            value: displayHeading,
                            onChange: function(value) {
                                setAttributes({ heading: value });
                            },
                            allowedFormats: ['core/bold', 'core/italic']
                        })
                    ),
                    el('div', {
                        className: 'item-total-row-data',
                        style: { textAlign: 'right', minWidth: '100px' }
                    },
                        el(RichText, {
                            tagName: 'span',
                            value: content.replace('{total}', previewTotal),
                            placeholder: content.replace('{total}', previewTotal),
                            onChange: function(value) {
                                // Replace preview back to {total} placeholder
                                const cleanValue = value.replace(previewTotal, '{total}');
                                setAttributes({ content: cleanValue });
                            },
                            allowedFormats: ['core/bold', 'core/italic'],
                            className: 'sab-price'
                        })
                    )
                ),
                el('p', {
                    key: 'help',
                    style: { 
                        fontSize: '12px', 
                        color: '#666', 
                        fontStyle: 'italic',
                        marginTop: '8px',
                        marginBottom: '0'
                    }
                },
                    __('Preview - actual amounts will be calculated from EUR totals', 'germanized-aed-totals')
                )
            ];
        },

        save: function() {
            // Dynamic block - rendered on server side
            return null;
        },

        deprecated: [
            {
                attributes: {
                    content: {
                        type: 'string',
                        default: '{total_aed}'
                    },
                    totalType: {
                        type: 'string',
                        default: 'total'
                    },
                    heading: {
                        type: 'string',
                        default: ''
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
                isEligible({ content }) {
                    return content && content.includes('{total_aed}');
                },
                migrate(attributes) {
                    return {
                        ...attributes,
                        content: attributes.content ? attributes.content.replace('{total_aed}', '{total}') : '{total}'
                    };
                },
                save() {
                    return null;
                }
            }
        ]
    });

})();