var fieldContainerExamples, bfGeoAddressFieldInstance = {
    actionCleanLocationControl: function () {
        var element = jQuery(this);
        var targetVisualInput = element.closest('.bf-geo-address-fields.bf-address-autocomplete-active').find('input[type="text"]');
        targetVisualInput.val('');
        var fieldID = targetVisualInput.attr('id');
        var formElement = jQuery(targetVisualInput.closest('form'));
        var fieldContainer = jQuery(targetVisualInput).closest('.container-for-geo-address-field').parent();
        var hiddenDataOfDeleteField = formElement.find('[name="' + fieldID + '_data"]');
        var setDataForDelete = hiddenDataOfDeleteField.val();
        if (setDataForDelete) {
            setDataForDelete = JSON.parse(setDataForDelete);
            setDataForDelete.delete = setDataForDelete.location_id;
            hiddenDataOfDeleteField.val(JSON.stringify(setDataForDelete));
        }
        //Update the hidden data
        bfGeoAddressFieldInstance.submitForm();
        bfGeoAddressFieldInstance.setFieldStatus('error', fieldContainer);
    },
    actionUserLocation: function () {
        var element = jQuery(this);
        element.addClass('loading');
        var targetVisualInput = element.closest('.bf-geo-address-fields.bf-address-autocomplete-active').find('input[type="text"]');
        var fieldID = targetVisualInput.attr('id');

        BuddyFormsHooks.doAction('buddyforms:submit:disable');
        bfGeoAddressFieldInstance.autoLocator(fieldID, request_success, request_fail);

        function request_success(formattedAddress, place, fieldID) {
            element.removeClass('loading');
            BuddyFormsHooks.doAction('buddyforms:submit:enable');
        }

        function request_fail(error) {
            element.removeClass('loading');
            BuddyFormsHooks.doAction('buddyforms:submit:enable');
            alert(error);
        }
    },
    /**
     * Page load locator function.
     *
     * Get the user's current location on page load
     *
     * @return
     */
    autoLocator: function (fieldID, success, failed) {
        // run navigator
        BuddyFormsHooks.doAction('buddyforms:submit:disable');
        bfGeoAddressFieldInstance.navigator(fieldID,
            function (formattedAddress, location, fieldID) {
                BuddyFormsHooks.doAction('buddyforms:submit:enable');
                return (typeof success == 'function') ? success(formattedAddress, location, fieldID) : console.log('bfGeoAddress::autoLocator', formattedAddress);
            },
            function (msj) {
                BuddyFormsHooks.doAction('buddyforms:submit:enable');
                return (typeof failed == 'function') ? failed(msj) : console.log('bfGeoAddress::autoLocator', msj);
            }
        );
    },
    // Navigator error messages
    navigatorErrorMessages: {
        1: 'User denied the request for Geolocation.',
        2: 'Location information is unavailable.',
        3: 'The request to get user location timed out.',
        4: 'An unknown error occurred'
    },
    /**
     * Get user's current position
     *
     * @param  {string} fieldID string to identify the current element
     * @param  {function} success callback function when navigator success
     * @param  {function} failed  callback function when navigator failed
     *
     * @return {[type]}                   [description]
     */
    navigator: function (fieldID, success, failed) {
        // if navigator exists ( in browser ) try to locate the user
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    bfGeoAddressFieldInstance.geoCode(fieldID, position, success, failed);
                },
                function (error) {
                    var msj = bfGeoAddressFieldInstance.navigatorErrorMessages[error.code];
                    return (typeof failed == 'function') ? failed(msj) : console.log('bfGeoAddress::navigator', msj);
                },
                {enableHighAccuracy: true, Infinity: Infinity, timeout: 2000}
            );
        } else {
            return (typeof failed == 'function') ? failed('Sorry! Geolocation is not supported by this browser.') : console.log('Sorry! Geolocation is not supported by this browser.');
        }
    },
    geoCode: function (fieldID, coordinates, success, show_error) {
        if (typeof google !== 'undefined' && typeof coordinates !== 'undefined' && typeof coordinates.coords.latitude !== 'undefined' && typeof coordinates.coords.longitude !== 'undefined') {
            var geocoder = new google.maps.Geocoder;
            var latlng = {lat: coordinates.coords.latitude, lng: coordinates.coords.longitude};
            var region = typeof buddyforms_geo_field.country_code !== 'undefined' ? buddyforms_geo_field.country_code : 'us',
                language = typeof buddyforms_geo_field.language_code !== 'undefined' ? buddyforms_geo_field.language_code : 'en';
            var params = {
                'region': region,
                'language': language,
                'location': latlng
            };
            geocoder.geocode(params, function (results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        var place = results[0];
                        var targetVisualInput = jQuery('#' + fieldID);
                        targetVisualInput.val(results[0].formatted_address).change();
                        var formElement = jQuery(targetVisualInput.closest('form'));
                        var fieldContainer = jQuery(targetVisualInput).closest('.container-for-geo-address-field').parent();
                        var previousDataString = formElement.find('[name="' + fieldID + '_data"]').val();
                        var previousData = (previousDataString) ? JSON.parse(previousDataString) : '';
                        var result = {};
                        result.location = {};
                        result.location.lat = place.geometry.location.lat().toFixed(6);
                        result.location.lng = place.geometry.location.lng().toFixed(6);
                        result.address_components = place.address_components;
                        result.formatted_address = place.formatted_address;
                        result.icon = place.icon || '';
                        result.url = place.url || '';
                        result.place_id = place.place_id || '';
                        result.location_id = (previousData && previousData.location_id) ? previousData.location_id : 0;
                        formElement.find('[name="' + fieldID + '_data"]').val(JSON.stringify(result));
                        //Update the hidden data
                        bfGeoAddressFieldInstance.submitForm();
                        bfGeoAddressFieldInstance.setFieldStatus('ok', fieldContainer);
                        //todo improve the process using a cookie to not call each time
                        success(results[0].formatted_address, results[0], fieldID);
                    } else {
                        window.alert('No results found');
                    }
                } else {
                    (typeof failed !== 'undefined') ? show_error(status) : console.log('bfGeoAddress::geoCode', status);
                }
            });
        }

    },
    generateFieldId: function () {
        var text = '';
        var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        for (var i = 0; i < 5; i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        }

        return text;
    },
    setFieldStatus: function (status, target) {
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
    loadAutoComplete: function (field_id) {
        var input_field = document.getElementById(field_id);
        // verify the field
        if (input_field != null && typeof google !== 'undefined') {
            var fieldContainer = jQuery(input_field).closest('.container-for-geo-address-field').parent();
            var options = {
                types: ['geocode'],
            };
            var autocomplete = new google.maps.places.Autocomplete(input_field, options);
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                BuddyFormsHooks.doAction('buddyforms:submit:disable');
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
                BuddyFormsHooks.doAction('buddyforms:submit:enable');
                //Update the hidden data
                bfGeoAddressFieldInstance.submitForm();
            });
            jQuery(input_field).attr('attached', 'true');
        } else {
            jQuery('.container-for-geo-address-controls').hide();
        }
    },

    updateAddButtonClass: function (conatiner) {
        var containers = jQuery(conatiner).find('.bf-geo-address-fields.bf-address-autocomplete-active .container-for-geo-address-controls:visible');
        jQuery.each(containers, function (key, visibleContainer) {
            var addButton = jQuery(visibleContainer).find('.geo-address-field-add');
            if (containers.length >= 1) {
                if ((key + 1) !== containers.length) {
                    addButton.hide();
                } else {
                    addButton.css('display', 'inline');
                }
            } else {
                addButton.css('display', 'inline');
            }
        });
    },
    addField: function (source, target, targetSlug, values) {
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
        jQuery(source).addClass('bf-address-autocomplete-active');
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
        //Hide the first delete button
        if (jQuery('.bf-address-autocomplete-active .container-for-geo-address-controls p.bfgmw-action .geo-address-field-delete').length > 0) {
            jQuery(source).find('p.bfgmw-action .geo-address-field-delete').show();
        }
        //Append to higher container
        target.append(source);
        //Update action links
        bfGeoAddressFieldInstance.updateAddButtonClass(target);
        //Attach the geocode auto-complete
        bfGeoAddressFieldInstance.loadAutoComplete(targetSlug);
        //Hide actions if field is disabled
        // bfGeoAddressFieldInstance.hideActionIfDisabled(source);
		//Add to validation rules
        var targetField = jQuery(source).find('input#'+targetSlug);
		var formSlug = jQuery(targetField).attr('data-form');
		if (jQuery && jQuery.validator && formSlug) {
		    setTimeout(function() {
                jQuery('#' + targetSlug).rules('add', {'address-required': 1});
            }, 0);
		}
    },
    hideActionIfDisabled: function (source) {
        var isDisabled = jQuery(source).find('input[type="text"]').is(':disabled');
        if (isDisabled) {
            jQuery(source).find('.container-for-geo-address-controls').hide();
        }
    },
    removeField: function (targetSlug, element) {
        var targetContainer = jQuery(element).closest('.container-for-geo-address-controls').parent();
        var container = targetContainer.parent();
        targetContainer.hide();
        var activeFieldContainer = jQuery(container).find('.bf-address-autocomplete-active');
        var hiddenDataOfDeleteField = jQuery(container).find('input[type="hidden"][name="' + targetSlug + '_data"]');
        var setDataForDelete = hiddenDataOfDeleteField.val();
        if (setDataForDelete) {
            setDataForDelete = JSON.parse(setDataForDelete);
            setDataForDelete.delete = setDataForDelete.location_id;
            hiddenDataOfDeleteField.val(JSON.stringify(setDataForDelete));
        }
        var target = jQuery(container).find('#' + targetSlug);
        if (target.val() && activeFieldContainer.length === 0) {
            var exampleContainer = jQuery(container).find('.bf-geo-address-example');
            var fieldExampleInput = jQuery(container).find('.container-for-geo-address-field input[type="text"].bf-address-autocomplete-example');
            var fieldSlug = fieldExampleInput.attr('name');
            //Add an empty field
            var newFieldSlug = fieldSlug + '_' + bfGeoAddressFieldInstance.generateFieldId();
            bfGeoAddressFieldInstance.addField(jQuery(exampleContainer).clone(), jQuery(container), newFieldSlug);
        }
        //Update action links
        bfGeoAddressFieldInstance.updateAddButtonClass(container);
        //Hide actions if field is disabled
        // bfGeoAddressFieldInstance.hideActionIfDisabled(container);
        //Update the hidden data
        bfGeoAddressFieldInstance.submitForm();
    },
    actionAddField: function () {
        var element = jQuery(this);
        var fieldName = element.attr('field_name');
        var inputFieldContainer = jQuery(this).closest('.bf-geo-address-fields').parent().find('.container-for-geo-address-field input[name="' + fieldName + '"].bf-address-autocomplete-example');
        var sourceFieldContainer = inputFieldContainer.parent().parent();
        var newFieldSlug = fieldName + '_' + bfGeoAddressFieldInstance.generateFieldId();
        bfGeoAddressFieldInstance.addField(sourceFieldContainer.clone(), sourceFieldContainer.parent(), newFieldSlug);
        bfGeoAddressFieldInstance.handleNewActions(newFieldSlug);
    },
    actionRemoveField: function () {
        var element = jQuery(this);
        var fieldTarget = element.attr('field_target');
        bfGeoAddressFieldInstance.removeField(fieldTarget, element);
        bfGeoAddressFieldInstance.handleNewActions(fieldTarget);
    },
    isJsonString: function (str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    },
    submitForm: function () {
        var dataFields = jQuery('input[type="hidden"][name^="bf_"][name$="_count"]');
        if (dataFields.length > 0) {
            jQuery.each(dataFields, function (key, currentDataField) {
                var fieldTarget = jQuery(currentDataField).attr('field_name');
                var currentResultJSON = jQuery(currentDataField).val();
                var allResults = [];
                if (bfGeoAddressFieldInstance.isJsonString(currentResultJSON)) {
                    allResults = JSON.parse(currentResultJSON);
                }
                if (!Array.isArray(allResults)) {
                    allResults = [];
                }
                var hiddenFieldsData = jQuery('.bf-address-autocomplete-active input[type="hidden"][name^="' + fieldTarget + '_"][name$="_data"]');
                if (hiddenFieldsData.length > 0) {
                    jQuery.each(hiddenFieldsData, function (i, currentHiddenField) {
                        var data = jQuery(currentHiddenField).val();
                        var fieldTarget = jQuery(currentHiddenField).attr('field_target');
                        if (fieldTarget) {
                            if (allResults.length === 0) {
                                if (data) {
                                    allResults.push({field: fieldTarget, data: JSON.parse(data)});
                                }
                            } else if (Array.isArray(allResults)) {
                                var currentItem = allResults.find(o => o.field === fieldTarget);
                                if (currentItem) {
                                    var currentItemIndex = allResults.indexOf(currentItem);
                                    if (data) {
                                        allResults[currentItemIndex] = {field: fieldTarget, data: JSON.parse(data)};
                                    } else {
                                        allResults.splice(currentItem, 1);
                                    }
                                } else {
                                    if (data) {
                                        allResults.push({field: fieldTarget, data: JSON.parse(data)});
                                    }
                                }
                            }
                        }
                    });
                    if (allResults.length > 0) {
                        jQuery(currentDataField).val(JSON.stringify(allResults));
                    } else {
                        jQuery(currentDataField).val('');
                    }
                }
            });
        }
    },
    getFormSlug: function (element) {
        var formSlug = false;
        if (!formSlug) {
            var form = jQuery(element).closest('form');
            var formId = form.attr('id');
            if (formId) {
                formSlug = formId.split('buddyforms_form_');
                formSlug = (formSlug[1]) ? formSlug[1] : false;
            } else {
                formSlug = false;
            }
        }
        return formSlug;
    },
    validateField: function () {
        jQuery.validator.addMethod("address-required", function (value, element, param) {
            var currentElement = jQuery(element);
            if(!currentElement.hasClass('address-field')){
            	return true;
			}
            var fieldId = currentElement.attr('field_id');
            var formSlug = bfGeoAddressFieldInstance.getFormSlug(element);
             if (
                formSlug && buddyformsGlobal && buddyformsGlobal[formSlug] && buddyformsGlobal[formSlug].js_validation &&
                buddyformsGlobal[formSlug].js_validation[0] === 'disabled'
            ) {
                return true;
            }

			if (currentElement.hasClass('bf-address-autocomplete-example')) {
				//Avoid validate example element
				return true;
			}
			//Hide old error
			jQuery('label#' + fieldId + '-error').remove();
			var error = '<label id="' + fieldId + '-error" class="error" for="' + fieldId + '" style="display: inline-block;">' + buddyforms_geo_field.fields[fieldId].validation_error_message + '</label>';

			var isValid = (value && value.length === 0);
			if (!isValid) {
			    currentElement.parent().parent().append(error);
				return false;
			}

			return true;
        }, '');
    },
    isGutenbergActive: function () {
        return typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined';
    },
    handleNewActions: function (fieldName) {
        var currentElement = jQuery('input[type="text"][name="' + fieldName + '"]');
        if (currentElement && currentElement.length > 0) {
            var fieldID = currentElement.attr('field_id');
            if (fieldID && buddyforms_geo_field && buddyforms_geo_field.fields && buddyforms_geo_field.fields[fieldID]) {
                var parentContainer = currentElement.closest('.bf-geo-address-fields.bf-address-autocomplete-active:visible');
                if (parentContainer && parentContainer.length > 0) {
                    var isLoadUserLocationActive = buddyforms_geo_field.fields[fieldID].is_load_user_location_enabled == true;
                    var isUserLocationIconActive = buddyforms_geo_field.fields[fieldID].is_user_location_icon_enabled == true;
                    var isCleanEnabled = buddyforms_geo_field.fields[fieldID].is_clean_enabled == true;
                    if (isLoadUserLocationActive) {
                        bfGeoAddressFieldInstance.autoLocator(currentElement.attr('id'), false, false);
                    }
                    if (isUserLocationIconActive) {
                        parentContainer.find('.bf-geo-address-user-location').show();
                        parentContainer.on('click', '.bf-geo-address-user-location', bfGeoAddressFieldInstance.actionUserLocation);
                    }
                    if (isCleanEnabled) {
                        parentContainer.find('.bf-geo-address-clean-control').show();
                        parentContainer.on('click', '.bf-geo-address-clean-control', bfGeoAddressFieldInstance.actionCleanLocationControl);
                    }
                }
            }
        }
    },
    init: function () {
    	var form = jQuery('form#post, form[id^="buddyforms_form_"], form[id^="submissions_"].bf-submission, #editor div.edit-post-layout__metaboxes div[id^="buddyforms_"], .block-editor #metaboxes div[id^="buddyforms_form_"]');
    	fieldContainerExamples = jQuery('.bf-geo-address-example');
        if (fieldContainerExamples.length > 0 && form.length > 0) {
            jQuery.each(fieldContainerExamples, function (key, container) {
                var fieldExampleInput = jQuery(container).find('.container-for-geo-address-field input[type="text"].bf-address-autocomplete-example');
                if (fieldExampleInput) {
                    var fieldSlug = fieldExampleInput.attr('name');
                    var fieldID = fieldExampleInput.attr('field_id');
                    var currentDataField = jQuery(container).closest('fieldset').find('input[type="hidden"][name="bf_' + fieldSlug + '_count"]');
                    var allFieldData = currentDataField.val();
                    if (allFieldData) {
                        allFieldData = JSON.parse(currentDataField.val());
                    }
                    //Check if the field is empty and initialize one empty field
                    if (allFieldData) {
                        jQuery.each(allFieldData, function (item, fieldData) {
                            if (fieldData.field && fieldData.data) {
                                bfGeoAddressFieldInstance.addField(jQuery(container).clone(), jQuery(container).parent(), fieldData.field, fieldData.data);
                            }
                        });
                    } else {
                        var newFieldSlug = fieldSlug + '_' + bfGeoAddressFieldInstance.generateFieldId();
                        bfGeoAddressFieldInstance.addField(jQuery(container).clone(), jQuery(container).parent(), newFieldSlug);
                    }

                    bfGeoAddressFieldInstance.handleNewActions(newFieldSlug);
                }
            });
            if (BuddyFormsHooks && buddyformsGlobal) {
                form.on('click', '.geo-address-field-add', bfGeoAddressFieldInstance.actionAddField);
                form.on('click', '.geo-address-field-delete', bfGeoAddressFieldInstance.actionRemoveField);
            }
        }
    },
};

jQuery(document).on('buddyforms-ready', function () {
	bfGeoAddressFieldInstance.init();
	bfGeoAddressFieldInstance.validateField();
	if (typeof BuddyFormsHooks !== 'undefined') {
		BuddyFormsHooks.addAction('buddyforms:render:after', function () {
			bfGeoAddressFieldInstance.init();
		}, 10);
	}
});