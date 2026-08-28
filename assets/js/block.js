(function (wp) {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, RangeControl, TextControl, ToggleControl } = wp.components;
	const { createElement: el } = wp.element;
	const { __ } = wp.i18n;
	const ServerSideRender = wp.serverSideRender;

	registerBlockType('event-schedule-wp/upcoming', {
		edit: function Edit(props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Upcoming events', 'event-schedule-wp'), initialOpen: true },
						el(TextControl, {
							label: __('Title', 'event-schedule-wp'),
							value: attributes.title,
							onChange: function (value) {
								setAttributes({ title: value });
							},
						}),
						el(RangeControl, {
							label: __('Number of events', 'event-schedule-wp'),
							min: 1,
							max: 20,
							value: attributes.limit,
							onChange: function (value) {
								setAttributes({ limit: value });
							},
						}),
						el(RangeControl, {
							label: __('Days ahead (0 = no date cap)', 'event-schedule-wp'),
							min: 0,
							max: 365,
							value: attributes.days,
							onChange: function (value) {
								setAttributes({ days: value });
							},
						}),
						el(TextControl, {
							label: __('Category name', 'event-schedule-wp'),
							value: attributes.category,
							onChange: function (value) {
								setAttributes({ category: value });
							},
						}),
						el(ToggleControl, {
							label: __('Show location', 'event-schedule-wp'),
							checked: attributes.showLocation,
							onChange: function (value) {
								setAttributes({ showLocation: value });
							},
						}),
						el(ToggleControl, {
							label: __('Show category', 'event-schedule-wp'),
							checked: attributes.showCategory,
							onChange: function (value) {
								setAttributes({ showCategory: value });
							},
						})
					)
				),
				el(ServerSideRender, {
					block: 'event-schedule-wp/upcoming',
					attributes: attributes,
				})
			);
		},
		save: function save() {
			return null;
		},
	});
})(window.wp);
