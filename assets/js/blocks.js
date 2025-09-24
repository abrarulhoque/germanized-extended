/**
 * AED Total Block for StoreaBill Editor
 * Based on Germanized's item-total-row block
 */
(function() {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { 
        AlignmentToolbar,
        BlockControls,
        InspectorControls,
        RichText,
        RichTextToolbarButton,
        FontSizePicker
    } = wp.blockEditor;
    const { 
        PanelBody, 
        RangeControl, 
        Slot, 
        ToggleControl, 
        ToolbarButton,
        Toolbar,
        DropdownMenu,
        ToolbarGroup
    } = wp.components;
    const { __, _x } = wp.i18n;
    const { createElement: el } = wp.element;
    const { compose } = wp.compose;
    const { withSelect } = wp.data;

    // Mock StoreaBill utilities for our AED block
    const ITEM_TOTAL_TYPES = [
        { type: 'total', title: __('Total', 'germanized-aed-totals'), icon: 'money-alt' },
        { type: 'subtotal', title: __('Subtotal', 'germanized-aed-totals'), icon: 'money-alt' },
        { type: 'taxes', title: __('Tax', 'germanized-aed-totals'), icon: 'money-alt' },
        { type: 'shipping', title: __('Shipping', 'germanized-aed-totals'), icon: 'money-alt' },
        { type: 'fee', title: __('Fee', 'germanized-aed-totals'), icon: 'money-alt' },
        { type: 'fees', title: __('Fees', 'germanized-aed-totals'), icon: 'money-alt' },
        { type: 'discount', title: __('Discount', 'germanized-aed-totals'), icon: 'money-alt' },
        { type: 'voucher', title: __('Voucher', 'germanized-aed-totals'), icon: 'money-alt' }
    ];

    const FORMAT_TYPES = ['core/bold', 'core/italic'];

    function getPreviewTotal(type) {
        const previews = {
            'total': '30.00€',
            'subtotal': '25.21€', 
            'taxes': '5.43€',
            'shipping': '3.36€',
            'fee': '2.00€',
            'fees': '2.00€',
            'discount': '-3.36€',
            'voucher': '-4.00€'
        };
        return previews[type] || '0.00€';
    }

    function getItemTotalTypeDefaultTitle(type) {
        const titles = {
            'total': __('Total', 'germanized-aed-totals'),
            'subtotal': __('Subtotal', 'germanized-aed-totals'),
            'taxes': __('19 % Tax', 'germanized-aed-totals'),
            'shipping': __('Shipping', 'germanized-aed-totals'),
            'fee': __('Fee', 'germanized-aed-totals'),
            'fees': __('Fee: %s', 'germanized-aed-totals'),
            'discount': __('Discount: %s', 'germanized-aed-totals'),
            'voucher': __('Voucher: %s', 'germanized-aed-totals')
        };
        return titles[type] || type;
    }

    function getItemTotalTypeTitle(type) {
        return getItemTotalTypeDefaultTitle(type);
    }

    function getBorderClasses(borders) {
        if (!borders || !Array.isArray(borders)) return [];
        return borders.map(border => `has-border-${border}`);
    }

    function getFontSizeStyle(fontSize) {
        if (!fontSize || !fontSize.size) return undefined;
        return fontSize.size;
    }

    function convertFontSizeForPicker(size) {
        return size;
    }

    // Border Select Component (simplified version)
    function BorderSelect({ label, currentBorders, onChange, borders, isMultiSelect }) {
        const borderOptions = borders.map(border => ({
            label: border.charAt(0).toUpperCase() + border.slice(1),
            value: border
        }));

        return el(DropdownMenu, {
            icon: 'admin-generic',
            label: label,
            controls: borderOptions.map(option => ({
                title: option.label,
                isActive: currentBorders.includes(option.value),
                onClick: () => {
                    let newBorders;
                    if (currentBorders.includes(option.value)) {
                        newBorders = currentBorders.filter(b => b !== option.value);
                    } else {
                        newBorders = isMultiSelect ? [...currentBorders, option.value] : [option.value];
                    }
                    onChange(newBorders);
                }
            }))
        });
    }

    // Type Select Component
    function TypeSelect({ value, onChange, types = ITEM_TOTAL_TYPES, label, isCollapsed = true }) {
        const activeType = types.find(control => control.type === value);

        return el(ToolbarGroup, {},
            el(DropdownMenu, {
                icon: 'admin-settings',
                label: label,
                controls: types.map(control => {
                    const { type } = control;
                    const isActive = value === type;

                    return {
                        ...control,
                        icon: control.icon || 'arrow-right-alt2',
                        isActive: isActive,
                        role: isCollapsed ? 'menuitemradio' : undefined,
                        onClick: () => onChange(type)
                    };
                })
            })
        );
    }

    // Color utilities (simplified)
    function useColors(colorSettings, deps) {
        return {
            InspectorControlsColorPanel: el('div'), // Placeholder
            BorderColor: ({ children }) => children,
            TextColor: ({ children }) => children
        };
    }

    function TotalRowEdit({
        attributes,
        setAttributes,
        className,
        fontSize = { size: undefined },
        setFontSize = () => {},
        borderColor = { color: undefined, class: undefined },
        backgroundColor = { color: undefined, class: undefined },
        textColor = { color: undefined, class: undefined }
    }) {

        const { heading, totalType, borders, content, hideIfEmpty } = attributes;

        let total = getPreviewTotal(totalType);
        const title = getItemTotalTypeTitle(totalType);
        const defaultContent = `<span class="item-total-inner-content placeholder-content sab-tooltip" data-tooltip="${title}" contenteditable="false"><span class="editor-placeholder"></span>{total}</span>`;
        let innerHeading = heading ? heading : getItemTotalTypeDefaultTitle(totalType);

        // Convert preview EUR to AED for display
        const aedPreview = total.replace('€', 'AED');

        const classes = [
            className,
            'item-total-row',
            ...getBorderClasses(borders)
        ].filter(Boolean).join(' ') + (borderColor.color ? ' has-border-color' : '') + (borderColor.class ? ` ${borderColor.class}` : '');

        const {
            InspectorControlsColorPanel,
            BorderColor,
            TextColor
        } = useColors(
            [
                { name: 'borderColor', className: 'has-border-color' },
                { name: 'textColor', property: 'color' },
            ],
            [fontSize.size]
        );

        const itemTotalClasses = 'item-total-row-data';

        const blockStyle = {
            borderColor: borderColor.color,
            fontSize: getFontSizeStyle(fontSize),
        };

        return [
            el(BlockControls, { key: 'controls' },
                el(TypeSelect, {
                    label: _x('Change type', 'storeabill-core', 'germanized-aed-totals'),
                    value: totalType,
                    onChange: (newType) => setAttributes({ totalType: newType })
                }),
                el(BorderSelect, {
                    label: _x('Adjust border', 'storeabill-core', 'germanized-aed-totals'),
                    currentBorders: borders,
                    isMultiSelect: true,
                    borders: ['top', 'bottom'],
                    onChange: (newBorder) => setAttributes({ borders: newBorder })
                })
            ),
            el(InspectorControls, { key: 'inspector' },
                el(PanelBody, {},
                    el(ToggleControl, {
                        label: _x('Hide if amount equals zero', 'storeabill-core', 'germanized-aed-totals'),
                        checked: hideIfEmpty,
                        onChange: () => setAttributes({ hideIfEmpty: !hideIfEmpty })
                    }),
                    el(FontSizePicker, {
                        value: convertFontSizeForPicker(fontSize.size),
                        onChange: setFontSize
                    })
                )
            ),
            InspectorControlsColorPanel,
            el('div', {
                key: 'edit',
                className: classes,
                style: blockStyle
            },
                el(TextColor, {},
                    el('div', { className: 'item-total-row-heading' },
                        el(RichText, {
                            tagName: 'span',
                            placeholder: _x('Insert heading', 'storeabill-core', 'germanized-aed-totals'),
                            value: innerHeading,
                            onChange: (value) => setAttributes({ heading: value }),
                            allowedFormats: FORMAT_TYPES,
                            className: 'item-total-heading placeholder-wrapper'
                        })
                    ),
                    el('div', { className: itemTotalClasses },
                        el(RichText, {
                            tagName: 'span',
                            value: content.replace('{total}', aedPreview),
                            placeholder: defaultContent.replace('{total}', aedPreview),
                            className: 'item-total-content placeholder-wrapper',
                            onChange: (value) => {
                                // Replace AED preview back to {total} placeholder
                                const cleanValue = value.replace(aedPreview, '{total}');
                                setAttributes({ content: cleanValue });
                            },
                            allowedFormats: FORMAT_TYPES
                        })
                    )
                )
            )
        ];
    }

    // Register AED Total Row Block with enhanced functionality
    registerBlockType('gaed/aed-total-row', {
        title: _x('AED Total Row', 'storeabill-core', 'germanized-aed-totals'),
        description: _x('Inserts an item total row converted to AED currency', 'storeabill-core', 'germanized-aed-totals'),
        category: 'storeabill',
        icon: 'money-alt',
        parent: ['storeabill/item-totals'],
        keywords: ['aed', 'currency', 'total', 'conversion'],
        example: {},

        attributes: {
            content: {
                type: 'string',
                default: '{total}'
            },
            totalType: {
                type: 'string',
                default: 'total'
            },
            borders: {
                type: 'array',
                default: []
            },
            customBorderColor: {
                type: 'string'
            },
            borderColor: {
                type: 'string'
            },
            backgroundColor: {
                type: 'string'
            },
            customBackgroundColor: {
                type: 'string'
            },
            customTextColor: {
                type: 'string'
            },
            textColor: {
                type: 'string'
            },
            fontSize: {
                type: 'string'
            },
            customFontSize: {
                type: 'string'
            },
            heading: {
                type: 'string',
                default: ''
            },
            hideIfEmpty: {
                type: 'boolean',
                default: false
            }
        },

        edit: TotalRowEdit,

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
                    borders: {
                        type: 'array',
                        default: []
                    },
                    heading: {
                        type: 'string',
                        default: ''
                    },
                    hideIfEmpty: {
                        type: 'boolean',
                        default: false
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