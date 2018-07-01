var bfGeoAddressFieldInstance = {
    updateAddButtonClass: function() {
        var deleteButton = jQuery('.geo-address-field-delete');
        if(deleteButton.length === 1){
            deleteButton.hide();
        } else {
            deleteButton.css('display', 'inline');
        }
        jQuery('.geo-address-field-add').removeClass('geo-address-field-add-last').hide();
        jQuery('.geo-address-field-add:last').addClass('geo-address-field-add-last').css('display', 'inline');
    },
    loadAutcomplete: function(field_id) {
        var input_field = document.getElementById(field_id);
        // verify the field
        if (input_field != null) {
            var options = {
                types: ['geocode'],
            };
            var autocomplete = new google.maps.places.Autocomplete(input_field, options);
            google.maps.event.addListener(autocomplete, 'place_changed', function() {
                var place = autocomplete.getPlace();
                if (place.geometry) {
                    var formElement = jQuery(input_field.closest('form'));
                    // make sure coords fields exist.
                    formElement.find('[name="' + field_id + '_lat"]').val(place.geometry.location.lat().toFixed(6));
                    formElement.find('[name="' + field_id + '_lng"]').val(place.geometry.location.lng().toFixed(6));
                }
            });
            jQuery(input_field).attr('attached', 'true');
        }
    },
    addNewField: function() {
        var mainContainer = jQuery(this).closest('.bf-geo-address-fields').parent();
        var element = jQuery(this);
        var data = {
            'action': 'get_new_bf_address_field',
            '_nonce': buddyforms_geo_field.nonce,
            'count': jQuery('#geo_my_wp_address_count').val(),
            'field_id': element.attr('field_id'),
            'field_name': element.attr('field_name'),
            'field_number': element.attr('field_number'),
            'default_value': element.attr('data-default-value'),
            'description': element.attr('data-description'),
        };
        jQuery.ajax({
            type: 'POST',
            url: buddyforms_geo_field.admin_url,
            data: data,
            success: function(newRow) {
                if (newRow && newRow['html'] && newRow['count'] && newRow['name']) {
                    mainContainer.append(newRow['html']);
                    jQuery('#geo_my_wp_address_count').val(newRow['count']);
                    bfGeoAddressFieldInstance.updateAddButtonClass();
                    bfGeoAddressFieldInstance.loadAutcomplete(newRow['name']);
                } else {
                    alert('Contact the admin, some error exist when try to add a new Address field');
                }
            }
        });

    },
    removeNewField: function() {

    },
    init: function() {
        var fields = jQuery('.bf-address-autocomplete');
        if (fields.length > 0) {
            bfGeoAddressFieldInstance.fieldInit(fields);
        }
    },
    fieldInit: function(fields) {
        jQuery.each(fields, function(key, input) {
            var formElement = jQuery(input.closest('form'));
            var isNotAttached = jQuery(input).attr('attached');
            isNotAttached = (typeof(isNotAttached) === 'undefined');
            if (isNotAttached) {
                bfGeoAddressFieldInstance.loadAutcomplete(input.id);
                bfGeoAddressFieldInstance.updateAddButtonClass();
                jQuery(formElement).on('click', '.geo-address-field-add', bfGeoAddressFieldInstance.addNewField);
                jQuery(formElement).on('click', '.geo-address-field-delete', bfGeoAddressFieldInstance.removeNewField);
            }
        });

    }
};

jQuery(document).ready(function() {
    bfGeoAddressFieldInstance.init();
});