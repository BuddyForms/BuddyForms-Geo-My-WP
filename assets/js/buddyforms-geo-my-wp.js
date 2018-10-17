var fieldContainerExamples, bfGeoAddressFieldInstance = {

  generateFieldId: function() {
    var text = '';
    var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    for (var i = 0; i < 5; i++)
      text += possible.charAt(Math.floor(Math.random() * possible.length));

    return text;
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
          bfGeoAddressFieldInstance.loadAutoComplete(newRow['name']);
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
    var mainContainer = jQuery(this).closest('.container-for-geo-address-controls').parent();
    var element = jQuery(this);
    var post_id = jQuery('input[name="post_id"]').val();
    var previousDataString = mainContainer.find('input[name$="_data"]').val();
    var previousData = (previousDataString) ? JSON.parse(previousDataString) : '';
    var data = {
      'action': 'delete_bf_address_field',
      '_nonce': buddyforms_geo_field.nonce,
      'field_name': element.attr('field_name'),
      'field_number': element.attr('field_number'),
      'location_id': (previousData && previousData.location_id) ? previousData.location_id : 0,
      'form_slug': jQuery('div.the_buddyforms_form  form input[type="hidden"][name="form_slug"]').val(),
      'post_id': (post_id) ? post_id : 0,
    };
    bfGeoAddressFieldInstance.setFieldStatus('changed', mainContainer);
    jQuery.ajax({
      type: 'POST',
      url: buddyforms_geo_field.admin_url,
      data: data,
      success: function(newRow) {
        if (newRow && newRow['result'] && newRow['name']) {
          bfGeoAddressFieldInstance.removeFieldContainer(mainContainer, element.attr('field_number'));
        } else {
          bfGeoAddressFieldInstance.setFieldStatus('error', mainContainer);
          alert('Contact the admin, some error exist when try to add a new Address field');
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
  initField: function(fields) {
    jQuery.each(fields, function(key, input) {
      var isNotAttached = jQuery(input).attr('attached');
      setTimeout(function() {
        jQuery(input).attr('autocomplete', 'nope');
      }, 1000);
      isNotAttached = (typeof(isNotAttached) === 'undefined');
      if (isNotAttached) {
        bfGeoAddressFieldInstance.loadAutoComplete(input.id);
        var fieldContainer = jQuery(input).closest('.container-for-geo-address-field').parent();
        var isReady = jQuery('input[name="' + input.id + '_data"]').val();
        bfGeoAddressFieldInstance.setFieldStatus((isReady) ? 'ok' : 'error', fieldContainer);
        bfGeoAddressFieldInstance.updateAddButtonClass();
      }
    });

  },
  initFieldGroup: function() {
    var fields = jQuery('.bf-address-autocomplete');

    if (fields.length > 0 && form.length > 0) {
      bfGeoAddressFieldInstance.fieldInit(fields);
    }
  },
  //**************************************************************************************************************************************************
  //New functions ************************************************************************************************************************************
  //**************************************************************************************************************************************************
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
  loadAutoComplete: function(field_id) {
    var input_field = document.getElementById(field_id);
    // verify the field
    if (input_field != null) {
      var fieldContainer = jQuery(input_field).closest('.container-for-geo-address-field').parent();
      var options = {
        types: ['geocode'],
      };
      var autocomplete = new google.maps.places.Autocomplete(input_field, options);
      google.maps.event.addListener(autocomplete, 'place_changed', function() {
        bfGeoAddressFieldInstance.setFieldStatus('changed', fieldContainer);
        var place = autocomplete.getPlace();
        if (place.geometry) {
          var formElement = jQuery(input_field.closest('form'));
          var previousDataString = formElement.find('[name="' + field_id + '_data"]').val();
          var previousData = (previousDataString) ? JSON.parse(previousDataString) : '';
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
          formElement.find('[name="' + field_id + '_data"]').val(JSON.stringify(result));
          bfGeoAddressFieldInstance.setFieldStatus('ok', fieldContainer);
        } else {
          bfGeoAddressFieldInstance.setFieldStatus('error', fieldContainer);
        }
      });
      jQuery(input_field).attr('attached', 'true');
    }
  },
  updateAddButtonClass: function() {
    var containers = jQuery('.bf-geo-address-fields:visible .container-for-geo-address-controls');
    jQuery.each(containers, function(key, visibleContainer) {
      var deleteButton = jQuery(visibleContainer).find('.geo-address-field-delete');
      var addButton = jQuery(visibleContainer).find('.geo-address-field-add');
      if (containers.length > 1) {
        deleteButton.css('display', 'inline');
        if ((key + 1) !== containers.length) {
          addButton.hide();
        }
      } else {
        deleteButton.hide();
      }
    });
  },
  addField: function(source, target, targetSlug, values) {
    var formattedAddress = '';
    var addressData = '';
    var status = 'error';
    if (values && values.formatted_address) {
      formattedAddress = values.formatted_address;
      addressData = JSON.stringify(values);
      status = 'ok';
    }
    //Clean the container
    jQuery(source).removeClass('bf-geo-address-example');
    //Clean the input text
    jQuery(source).find('input[type="text"]').attr('id', targetSlug);
    jQuery(source).find('input[type="text"]').attr('value', formattedAddress);
    jQuery(source).find('input[type="text"]').removeAttr('attached');
    jQuery(source).find('input[type="text"]').removeClass('bf-address-autocomplete-example');
    jQuery(source).find('input[type="text"]').attr('name', targetSlug);
    //Clean the input hidden
    jQuery(source).find('input[type="hidden"]').attr('value', addressData);
    jQuery(source).find('input[type="hidden"]').attr('name', targetSlug + '_data');
    jQuery(source).find('input[type="hidden"]').attr('field_target', targetSlug);
    //Clean actions
    jQuery(source).find('.container-for-geo-address-controls .bfgmw-action a.geo-address-field-delete').attr('field_target', targetSlug);
    //Set field status
    jQuery(source).find('.container-for-geo-address-controls p.gmw-lf-field.message').removeClass('error ok changed');
    jQuery(source).find('.container-for-geo-address-controls p.gmw-lf-field.message').addClass(status);
    //Append to higher container
    target.append(source);
    //Update action links
    bfGeoAddressFieldInstance.updateAddButtonClass();
    //Attach the geocode auto-complete
    bfGeoAddressFieldInstance.loadAutoComplete(targetSlug);
  },
  removeField: function(targetSlug) {
    jQuery('#' + targetSlug).parent().parent().hide();
    var hiddenDataOfDeleteField = jQuery('input[type="hidden"][name="' + targetSlug + '_data"]');
    var setDataForDelete = hiddenDataOfDeleteField.val();
    if (setDataForDelete) {
      setDataForDelete = JSON.parse(setDataForDelete);
      setDataForDelete.delete = setDataForDelete.location_id;
      hiddenDataOfDeleteField.val(JSON.stringify(setDataForDelete));
    }
    //Update action links
    bfGeoAddressFieldInstance.updateAddButtonClass();
  },
  actionAddField: function() {
    var element = jQuery(this);
    var fieldName = element.attr('field_name');
    var inputFieldContainer = jQuery(this).closest('.bf-geo-address-fields').parent().find('.container-for-geo-address-field input[name="' + fieldName + '"].bf-address-autocomplete-example');
    var sourceFieldContainer = inputFieldContainer.parent().parent();
    var newFieldSlug = fieldName + '_' + bfGeoAddressFieldInstance.generateFieldId();
    bfGeoAddressFieldInstance.addField(sourceFieldContainer.clone(), sourceFieldContainer.parent(), newFieldSlug);
  },
  actionRemoveField: function() {
    var element = jQuery(this);
    var fieldTarget = element.attr('field_target');
    bfGeoAddressFieldInstance.removeField(fieldTarget);
  },
  submitForm: function() {
    var dataFields = jQuery('input[type="hidden"][name^="bf_"][name$="_count"]');
    if (dataFields.length > 0) {
      jQuery.each(dataFields, function(key, currentDataField) {
        var fieldTarget = jQuery(currentDataField).attr('field_name');
        var hiddenFieldsData = jQuery('input[type="hidden"][name^="' + fieldTarget + '_"][name$="_data"]');
        if (hiddenFieldsData.length > 0) {
          var allResults = [];
          jQuery.each(hiddenFieldsData, function(i, currentHiddenField) {
            var data = jQuery(currentHiddenField).val();
            var fieldTarget = jQuery(currentHiddenField).attr('field_target');
            if (data && fieldTarget) {
              allResults.push({field: fieldTarget, data: data});
            }
          });
          if (allResults.length > 0) {
            jQuery(currentDataField).val(JSON.stringify(allResults));
          }
        }
      });
    }
  },
  init: function() {
    var form = jQuery('form[id^="buddyforms_"]');
    if (form.length === 0) {
      form = jQuery('form[id^="submissions_"]');
    }
    fieldContainerExamples = jQuery('.bf-geo-address-example');
    if (fieldContainerExamples.length > 0 && form.length > 0) {
      jQuery.each(fieldContainerExamples, function(key, container) {
        var fieldExampleInput = jQuery(container).find('.container-for-geo-address-field input[type="text"].bf-address-autocomplete-example');
        if (fieldExampleInput) {
          var fieldSlug = fieldExampleInput.attr('name');
          var currentDataField = jQuery('input[type="hidden"][name="bf_' + fieldSlug + '_count"]');
          var allFieldData = currentDataField.val();
          if (allFieldData) {
            allFieldData = JSON.parse(currentDataField.val());
          }
          //Check if the field is empty and initialize one empty field
          if (allFieldData) {
            jQuery.each(allFieldData, function(item, fieldData) {
              if (fieldData.field && fieldData.data) {
                bfGeoAddressFieldInstance.addField(jQuery(container).clone(), jQuery(container).parent(), fieldData.field, fieldData.data);
              }
            });
          } else {
            var newFieldSlug = fieldSlug + '_' + bfGeoAddressFieldInstance.generateFieldId();
            bfGeoAddressFieldInstance.addField(jQuery(container).clone(), jQuery(container).parent(), newFieldSlug);
          }
        }
      });
      form.on('click', '.geo-address-field-add', bfGeoAddressFieldInstance.actionAddField);
      form.on('click', '.geo-address-field-delete', bfGeoAddressFieldInstance.actionRemoveField);
      form.on('click', 'button[type="submit"].bf-submit', bfGeoAddressFieldInstance.submitForm);
    }
  },
};

jQuery(document).ready(function() {
  bfGeoAddressFieldInstance.init();
});