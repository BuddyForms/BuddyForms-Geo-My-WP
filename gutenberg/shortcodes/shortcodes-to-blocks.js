var el = wp.element.createElement,
    Fragment = wp.element.Fragment,
    registerBlockType = wp.blocks.registerBlockType,
    ServerSideRender = wp.components.ServerSideRender,
    TextControl = wp.components.TextControl,
    SelectControl = wp.components.SelectControl,
    CheckboxControl = wp.components.CheckboxControl,
    ToggleControl = wp.components.ToggleControl,
    InspectorControls = wp.editor.InspectorControls;

const iconBuddyFormsGMW = el('svg', {width: 24, height: 24},
    el('path', {d: "M9.247 0.323c6.45-1.52 12.91 2.476 14.43 8.925s-2.476 12.91-8.925 14.43c-6.45 1.52-12.91-2.476-14.43-8.925s2.476-12.91 8.925-14.43zM9.033 14.121c-0.445-0.604-0.939-1.014-1.656-1.269-0.636 0.196-1.18 0.176-1.8-0.066-1.857 0.507-2.828 2.484-2.886 4.229 1.413 0.025 2.825 0.050 4.237 0.076M5.007 11.447c0.662 0.864 1.901 1.029 2.766 0.366s1.030-1.9 0.367-2.766c-0.662-0.864-1.901-1.029-2.766-0.366s-1.029 1.9-0.367 2.766zM7.476 18.878l7.256-0.376c-0.096-1.701-1.066-3.6-2.87-4.103-0.621 0.241-1.165 0.259-1.8 0.059-1.816 0.635-2.65 2.675-2.585 4.419zM9.399 13.162c0.72 0.817 1.968 0.894 2.784 0.173s0.894-1.968 0.173-2.784c-0.72-0.817-1.968-0.894-2.784-0.173s-0.894 1.968-0.173 2.784zM14.007 9.588h6.794v-1.109h-6.794v1.109zM14.007 11.645h6.794v-1.109h-6.794v1.109zM14.007 7.532h6.794v-1.109h-6.794v1.109zM9.033 14.121c-0.192 0.118-0.374 0.251-0.544 0.399-0.205 0.177-0.393 0.375-0.564 0.585-0.175 0.216-0.331 0.447-0.468 0.688-0.136 0.243-0.255 0.495-0.353 0.757-0.068 0.177-0.126 0.358-0.176 0.541"})
);

// for some strange reason this was creating a conflict with the core and I have comment itz out for now. 'BuddyForm Geo My Wp MAP' is not translatable
// @todo:'BuddyForm Geo My Wp MAP' is not translatable.
//const {__} = wp.i18n;

//
// Embed a Geo My WP Map
//
registerBlockType('buddyforms/bf-gmw-embed-map', {
    title: 'BuddyForm Geo My Wp MAP',
    icon: iconBuddyFormsGMW,
    category: 'buddyforms',

    edit: function (props) {

        var bf_by_author = [
            {value: 'logged_in_user', label: 'Logged in Author Posts'},
            {value: 'all_users', label: 'All Author Posts'},
            {value: 'author_ids', label: 'Author ID\'S'},
        ];


        var forms = [
            {value: 'no', label: 'Select a Form'},
        ];
        for (var key in buddyforms_gmw_maps) {
            forms.push({value: key, label: buddyforms_gmw_maps[key]});
        }

        return [

            el(ServerSideRender, {
                block: 'buddyforms/bf-gmw-embed-map',
                attributes: props.attributes,
            }),

            el(InspectorControls, {},
                el('p', {}, ''),
                el(SelectControl, {
                    label: 'Please Select a form',
                    value: props.attributes.bf_form_slug,
                    options: forms,
                    onChange: (value) => {
                        props.setAttributes({bf_form_slug: value});
                    },
                }),
                el(SelectControl, {
                    label: 'Please Select a form',
                    value: props.attributes.bf_logged_in_user,
                    options: bf_by_author,
                    onChange: (value) => {
                        props.setAttributes({bf_logged_in_user: value});
                    },
                }),
                el(TextControl, {
                    label: 'User ID',
                    value: props.attributes.bf_user_id,
                    onChange: (value) => {
                        props.setAttributes({bf_user_id: value});
                    },
                }),
                el(TextControl, {
                    label: 'Width',
                    value: props.attributes.bf_map_width,
                    onChange: (value) => {
                        props.setAttributes({bf_map_width: value});
                    },
                }),
                el(TextControl, {
                    label: 'Height',
                    value: props.attributes.bf_map_height,
                    onChange: (value) => {
                        props.setAttributes({bf_map_height: value});
                    },
                }),
                el(TextControl, {
                    label: 'Elements',
                    value: props.attributes.bf_elements,
                    onChange: (value) => {
                        props.setAttributes({bf_elements: value});
                    },
                }),
                el(TextControl, {
                    label: 'Info Window',
                    value: props.attributes.bf_info_window,
                    onChange: (value) => {
                        props.setAttributes({bf_info_window: value});
                    },
                })
            )
        ];
    },

    save: function () {
        return null;
    },
});