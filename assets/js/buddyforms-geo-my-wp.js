var bfGeoAddressFieldInstance = {
  updateAddButtonClass: function() {
    var deleteButton = jQuery('.geo-address-field-delete');
    if (deleteButton.length === 1) {
      deleteButton.hide();
    } else {
      deleteButton.css('display', 'inline');
    }
    jQuery('.geo-address-field-add').
        removeClass('geo-address-field-add-last').
        hide();
    jQuery('.geo-address-field-add:last').
        addClass('geo-address-field-add-last').
        css('display', 'inline');
  },
  setFieldStatus: function(status, target) {
    var actionContainer = jQuery(target).find('p.gmw-lf-field.message');
    if (actionContainer.length > 0) {
      actionContainer.removeClass('error ok changed');
      switch (status) {
        case 'ok':
          actionContainer.addClass('ok');
          break;
        case 'changed':
          actionContainer.addClass('changed');
          break;
        default:
          actionContainer.addClass('error');
      }
    }
  },
  loadAutocomplete: function(field_id) {
    var input_field = document.getElementById(field_id);
    // verify the field
    if (input_field != null) {
      var fieldContainer = jQuery(input_field).
          closest('.container-for-geo-address-field').
          parent();
      var options = {
        types: ['geocode'],
      };
      var autocomplete = new google.maps.places.Autocomplete(input_field,
          options);
      google.maps.event.addListener(autocomplete, 'place_changed', function() {
        bfGeoAddressFieldInstance.setFieldStatus('changed', fieldContainer);
        var place = autocomplete.getPlace();
        if (place.geometry) {
          var formElement = jQuery(input_field.closest('form'));
          var previousDataString = formElement.find('[name="' + field_id + '_data"]').val();
          var previousData = JSON.parse(previousDataString);
          var result = {};
          result.location = {};
          result.location.lat = place.geometry.location.lat().toFixed(6);
          result.location.lng = place.geometry.location.lng().toFixed(6);
          result.address_components = place.address_components;
          result.formatted_address = place.formatted_address;
          result.icon = place.icon;
          result.url = place.url;
          result.place_id = place.place_id;
          result.location_id = (previousData && previousData.location_id) ? previousData.location_id : 0;
          // make sure coords fields exist.
          formElement.find('[name="' + field_id + '_data"]').val(JSON.stringify(result));
          bfGeoAddressFieldInstance.setFieldStatus('ok', fieldContainer);
        } else {
          bfGeoAddressFieldInstance.setFieldStatus('error', fieldContainer);
        }
      });
      jQuery(input_field).attr('attached', 'true');
    }
  },
  addNewField: function() {
    var mainContainer = jQuery(this).closest('.bf-geo-address-fields').parent();
    var element = jQuery(this);
    var fieldContainer = jQuery(this).
        closest('.container-for-geo-address-field').
        parent();
    var data = {
      'action': 'get_new_bf_address_field',
      '_nonce': buddyforms_geo_field.nonce,
      'count': jQuery('#geo_my_wp_address_count').val(),
      'field_id': element.attr('field_id'),
      'field_name': element.attr('field_name'),
      'field_number': element.attr('field_number'),
      'default_value': element.attr('data-default-value'),
      'form_slug': jQuery(
          'div.the_buddyforms_form  form input[type="hidden"][name="form_slug"]').
          val(),
      'description': element.attr('data-description'),
    };
    bfGeoAddressFieldInstance.setFieldStatus('changed', fieldContainer);
    jQuery.ajax({
      type: 'POST',
      url: buddyforms_geo_field.admin_url,
      data: data,
      success: function(newRow) {
        if (newRow && newRow['html'] && newRow['count'] && newRow['name']) {
          mainContainer.append(newRow['html']);
          jQuery('#geo_my_wp_address_count').val(newRow['count']);
          bfGeoAddressFieldInstance.updateAddButtonClass();
          bfGeoAddressFieldInstance.loadAutocomplete(newRow['name']);
          bfGeoAddressFieldInstance.setFieldStatus('ok', fieldContainer);
        } else {
          bfGeoAddressFieldInstance.setFieldStatus('error', fieldContainer);
          alert(
              'Contact the admin, some error exist when try to add a new Address field');
        }
      },
      error: function() {
        bfGeoAddressFieldInstance.setFieldStatus('error', fieldContainer);
      },
    });
  },
  removeNewField: function() {
    var mainContainer = jQuery(this).
        closest('.container-for-geo-address-controls').
        parent();
    var element = jQuery(this);
    var post_id = jQuery('input[name="post_id"]').val();

    var data = {
      'action': 'delete_bf_address_field',
      '_nonce': buddyforms_geo_field.nonce,
      'field_name': element.attr('field_name'),
      'field_number': element.attr('field_number'),
      'form_slug': jQuery(
          'div.the_buddyforms_form  form input[type="hidden"][name="form_slug"]').
          val(),
      'post_id': (post_id) ? post_id : 0,
    };
    bfGeoAddressFieldInstance.setFieldStatus('changed', mainContainer);
    jQuery.ajax({
      type: 'POST',
      url: buddyforms_geo_field.admin_url,
      data: data,
      success: function(newRow) {
        if (newRow && newRow['result'] && newRow['name']) {
          bfGeoAddressFieldInstance.removeFieldContainer(mainContainer,
              element.attr('field_number'));
        } else {
          bfGeoAddressFieldInstance.setFieldStatus('error', mainContainer);
          alert(
              'Contact the admin, some error exist when try to add a new Address field');
        }
      },
    });

    bfGeoAddressFieldInstance.updateAddButtonClass();
  },
  removeFieldContainer: function(container, count) {
    var finalCount = parseInt(count);
    jQuery('#geo_my_wp_address_count').val(finalCount--);
    jQuery(container).remove();
    bfGeoAddressFieldInstance.updateAddButtonClass();
  },
  init: function() {
    var fields = jQuery('.bf-address-autocomplete');
    var form = jQuery('form[id^="buddyforms_"]');
    if (form.length === 0) {
      form = jQuery('form[id^="submissions_"]');
    }
    if (fields.length > 0 && form.length > 0) {
      bfGeoAddressFieldInstance.fieldInit(fields);
      form.on('click', '.geo-address-field-add',
          bfGeoAddressFieldInstance.addNewField);
      form.on('click', '.geo-address-field-delete',
          bfGeoAddressFieldInstance.removeNewField);
    }
  },
  fieldInit: function(fields) {
    jQuery.each(fields, function(key, input) {
      var isNotAttached = jQuery(input).attr('attached');
      setTimeout(function() {
        jQuery(input).attr('autocomplete', 'nope');
      }, 1000);
      isNotAttached = (typeof(isNotAttached) === 'undefined');
      if (isNotAttached) {
        bfGeoAddressFieldInstance.loadAutocomplete(input.id);
        var fieldContainer = jQuery(input).
            closest('.container-for-geo-address-field').
            parent();
        var isReady = jQuery('input[name="' + input.id + '_data"]').val();
        bfGeoAddressFieldInstance.setFieldStatus((isReady) ? 'ok' : 'error',
            fieldContainer);
        bfGeoAddressFieldInstance.updateAddButtonClass();
      }
    });

  },
};

jQuery(document).ready(function() {
  bfGeoAddressFieldInstance.init();
});