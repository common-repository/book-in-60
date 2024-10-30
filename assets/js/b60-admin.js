Stripe.setPublishableKey(stripe.key);

jQuery(document).ready(function ($) {

    var regexPattern_AN_DASH_U = /^[a-zA-Z0-9-_]+$/;

    var $loading = $(".showLoading");
    var $update = $("#updateDiv");
    $loading.hide();
    $update.hide();

    function resetForm($form) {
        $form.find('input:text, input:password, input:file, select, textarea').val('');
        $form.find('input:radio, input:checkbox').removeAttr('checked').removeAttr('selected');
    }

    function validField(field, fieldName, errorField) {
        var valid = true;
        if (field.val() === "") {
            showError(fieldName + " must contain a value");
            valid = false;
        }
        return valid;
    }

    function validFieldByRegex(field, regexPattern, errorMessage) {
        var valid = true;
        if (!regexPattern.test(field.val())) {
            showError(errorMessage);
            valid = false;
        }
        return valid;
    }

    function validFieldByLength(field, len, errorMessage) {
        var valid = true;
        if (field.val().length > len) {
            showError(errorMessage);
            valid = false;
        }
        return valid;
    }

    function validFieldWithMsg(field, msg) {
        var valid = true;
        if (field.val() === "") {
            showError(msg);
            valid = false;
        }
        return valid;
    }

    function showError(message) {
        showMessage('error', 'updated', message);
    }

    function showUpdate(message) {
        showMessage('updated', 'error', message);
    }

    function showMessage(addClass, removeClass, message) {
        $update.removeClass(removeClass);
        $update.addClass(addClass);
        $update.html("<p>" + message + "</p>");
        $update.show();
        document.body.scrollTop = document.documentElement.scrollTop = 0;
    }

    function clearUpdateAndError() {
        $update.html("");
        $update.removeClass('error');
        $update.removeClass('update');
        $update.hide();
    }

    //for uploading images using WordPress media library
    var custom_uploader;

    function uploadImage(inputID) {
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }

        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function () {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            $(inputID).val(attachment.url);
        });

        //Open the uploader dialog
        custom_uploader.open();
    }

    // called on form submit when we know includeCustomFields = 1
    function processCustomFields(form) {
        var valid = true;
        var count = $('#customInputNumberSelect').val();
        var customValues = '';
        for (var i = 1; i <= count; i++) {
            // first validate the field
            var field = '#form_custom_input_label_' + i;
            var fieldName = 'Custom Input Label ' + i;
            valid = validField($(field), fieldName, $update);
            valid = valid && validFieldByLength($(field), 40, 'You can enter up to 40 characters for ' + fieldName);
            if (!valid) return false;
            // save the value, stripping all single & double quotes
            customValues += $(field).val().replace(/['"]+/g, '');
            if (i < count)
                customValues += '{{';
        }

        // now append to the form
        form.append('<input type="hidden" name="customInputs" value="' + customValues + '"/>');

        return valid;
    }

    function validate_redirect() {
        var valid_redirect;
        if ($('#do_redirect_yes').prop('checked')) {
            if ($('#form_redirect_to_page_or_post').prop('checked')) {
                valid_redirect = validFieldWithMsg($('#form_redirect_page_or_post_id'), 'Select page or post to redirect to');
            } else if ($('#form_redirect_to_url').prop('checked')) {
                valid_redirect = validFieldWithMsg($('#form_redirect_url'), 'Enter an URL to redirect to', $update);
            } else {
                showError('You must check at least one redirect type');
                valid_redirect = false;
            }
        } else {
            valid_redirect = true;
        }
        return valid_redirect;
    }

    function validate_lead_redirect() {
        var valid_redirect;
        if ($('#form_redirect_lead_to_page').prop('checked')) {
            valid_redirect = validFieldWithMsg($('#form_redirect_lead_to_page_id'), 'Select page to redirect to');
        } else if ($('#form_redirect_lead_to_steps').prop('checked')) {
            valid_redirect = true;
        } else {
            showError('You must check at least one redirect type');
            valid_redirect = false;
        }
        return valid_redirect;
    }

    function do_ajax_post(ajaxUrl, form, successMessage, doRedirect) {
        $loading.show();
        // Disable the submit button
        form.find('button').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: ajaxUrl,
            data: form.serialize(),
            cache: false,
            dataType: "json",
            success: function (data) {
                $loading.hide();
                document.body.scrollTop = document.documentElement.scrollTop = 0;

                if (data.success) {
                    showUpdate(successMessage);
                    form.find('button').prop('disabled', false);
                    resetForm(form);

                    if (doRedirect) {
                        setTimeout(function () {
                            window.location = data.redirectURL;
                        }, 1000);
                    }
                } else {
                    // re-enable the submit button
                    form.find('button').prop('disabled', false);
                    // show the errors on the form
                    if (data.msg) {
                        showError(data.msg);
                    }
                    if (data.validation_result) {
                        var elementWithError = null;
                        for (var f in data.validation_result) {
                            if (data.validation_result.hasOwnProperty(f)) {
                                $('input[name=' + f + ']').after('<div class="error"><p>' + data.validation_result[f] + '</p></div>');
                                elementWithError = f;
                            }
                        }
                        if (elementWithError) {
                            var $el = $('input[name=' + elementWithError + ']');
                            if ($el && $el.offset() && $el.offset().top);
                            $('html, body').animate({
                                scrollTop: $el.offset().top
                            }, 2000);
                        }
                    }
                }
            }
        });
    }

    function enable_combobox() {
        $('#createPaymentFormSection .page_or_post-combobox-input').prop('disabled', false);
        $('#createPaymentFormSection .page_or_post-combobox-toggle').button("option", "disabled", false);
        $('#edit-payment-form .page_or_post-combobox-input').prop('disabled', false);
        $('#edit-payment-form .page_or_post-combobox-toggle').button("option", "disabled", false);
        $('#gift-card-form .page_or_post-combobox-input').prop('disabled', false);
        $('#gift-card-form .page_or_post-combobox-toggle').button("option", "disabled", false);
        $('#edit-payment-form .lead_to_page-combobox-input').prop('disabled', false);
        $('#edit-payment-form .lead_to_page-combobox-toggle').button("option", "disabled", false);
    }

    function disable_combobox() {
        $('#createPaymentFormSection .page_or_post-combobox-input').prop('disabled', true);
        $('#createPaymentFormSection .page_or_post-combobox-toggle').button("option", "disabled", true);
        $('#edit-payment-form .page_or_post-combobox-input').prop('disabled', true);
        $('#edit-payment-form .page_or_post-combobox-toggle').button("option", "disabled", true);
        $('#gift-card-form .page_or_post-combobox-input').prop('disabled', true);
        $('#gift-card-form .page_or_post-combobox-toggle').button("option", "disabled", true);
        $('#edit-payment-form .lead_to_page-combobox-input').prop('disabled', true);
        $('#edit-payment-form .lead_to_page-combobox-toggle').button("option", "disabled", true);
    }

    function init_page_or_post_redirect() {
        $('#form_redirect_to_url').prop('checked', false);
        $('#form_redirect_to_page_or_post').prop('checked', true);
        $('#form_redirect_to_page_or_post').prop('disabled', false);
        $('#form_redirect_to_url').prop('disabled', false);
        enable_combobox();
        $('#form_redirect_page_or_post_id').prop('disabled', false);
        $('#form_redirect_url').prop('disabled', false);
    }

    function init_page_or_post_giftcard_redirect() {
        $('#form_redirect_to_url_giftcard').prop('checked', false);
        $('#form_redirect_to_page_or_post_giftcard').prop('checked', true);
        $('#form_redirect_to_page_or_post_giftcard').prop('disabled', false);
        $('#form_redirect_to_url_giftcard').prop('disabled', false);
        enable_combobox();
        $('#form_redirect_page_or_post_id_giftcard').prop('disabled', false);
        $('#form_redirect_url_giftcard').prop('disabled', false);
    }

    $('#create-payment-form').submit(function (e) {
        console.log('test');
        clearUpdateAndError();

        var customAmount = $('input[name=form_custom]:checked', '#create-payment-form').val();
        var includeCustom = $('input[name=form_include_custom_input]:checked', '#create-payment-form').val();

        var valid = validField($('#form_name'), 'Name', $update);
        valid = valid && validFieldByRegex($('#form_name'), regexPattern_AN_DASH_U, 'Form Name should contain only alphanumerical characters, dashes, underscores, and whitespaces.');
        valid = valid && validField($('#form_title'), 'Form Title', $update);
        if (customAmount == 'specified_amount') {
            valid = valid && validField($('#form_amount'), 'Amount', $update);
        }
        valid = valid && validate_redirect();
        if (includeCustom == 1) {
            valid = valid && processCustomFields($(this)); //NOTE: must do this last as it appends a hidden input.
        }

        if (valid) {
            var $form = $(this);
            //post form via ajax
            do_ajax_post(admin_ajaxurl.admin_ajaxurl, $form, "Payment form created.", true);
        }

        return false;
    });

    $('#edit-payment-form').submit(function (e) {
        clearUpdateAndError();

        var customAmount = $('input[name=form_custom]:checked', '#edit-payment-form').val();
        var includeCustom = $('input[name=form_include_custom_input]:checked', '#edit-payment-form').val();

        var valid = validField($('#form_name'), 'Name', $update);
        valid = valid && validFieldByRegex($('#form_name'), regexPattern_AN_DASH_U, 'Form Name should contain only alphanumerical characters, dashes, underscores, and whitespaces.');
        valid = valid && validField($('#form_title'), 'Form Title', $update);
        if (customAmount == 'specified_amount') {
            valid = valid && validField($('#form_amount'), 'Amount', $update);
        }
        valid = valid && validate_lead_redirect();
        valid = valid && validate_redirect();
        if (includeCustom == 1) {
            valid = valid && processCustomFields($(this)); //NOTE: must do this last as it appends a hidden input.
        }

        if (valid) {
            var $form = $(this);
            //post form via ajax
            do_ajax_post(admin_ajaxurl.admin_ajaxurl, $form, "Payment form updated.", true);
        }

        return false;
    });

    //The forms delete button
    $('button.delete').click(function () {
        var id = $(this).attr('data-id');
        var type = $(this).attr('data-type');
        var to_confirm = $(this).attr('data-confirm');
        if (to_confirm == null) {
            to_confirm = 'true';
        }
        var confirm_message = 'Are you sure you want to delete the record?';
        var update_message = 'Record deleted.';
        var action = '';
        if (type === 'paymentForm') {
            action = 'wp_full_stripe_delete_payment_form';
            confirm_message = 'Are you sure you want to delete this payment form?';
            update_message = 'Payment form deleted.';
        } else if (type === 'subscriptionForm') {
            action = 'wp_full_stripe_delete_subscription_form';
            confirm_message = 'Are you sure you want to delete this subscription form?';
            update_message = 'Subscription form deleted.';
        } else if (type === 'subscriber') {
            action = 'wp_full_stripe_delete_subscriber';
        } else if (type === 'payment') {
            action = 'wp_full_stripe_delete_payment';
        } else if (type === 'subscriptionPlan') {
            action = 'wp_full_stripe_delete_subscription_plan';
            confirm_message = 'Are you sure you want to delete this subscription plan?';
            update_message = 'Subscription plan deleted.';
        }

        var row = $(this).parents('tr:first');

        $loading.show();

        var confirmed = true;
        if (to_confirm === 'true' || to_confirm === 'yes') {
            confirmed = confirm(confirm_message);
        }
        if (confirmed == true) {
            $.ajax({
                type: "POST",
                url: admin_ajaxurl.admin_ajaxurl,
                data: {id: id, action: action},
                cache: false,
                dataType: "json",
                success: function (data) {
                    $loading.hide();

                    if (data.success) {
                        $(row).remove();
                        showUpdate(update_message);
                    }
                }
            });
        }

        return false;

    });

    //Enable - Disable Stripe payment
    $('#stripe-section').css('display', 'none');
    $('#stripe-settings').css('display', 'none');    
    $('#tax_display').css('display', 'none');    
    $('#tax_display_').css('display', 'none');    

    if ($('#enable_payment_method').prop('checked')) {
        $('#stripe-section').css('display', 'table-row');
    } else {
        $('#stripe-section').css('display', 'none');
    }

    if ($('#enable_sales_tax').prop('checked')) {
        $('#tax_display').css('display', 'table-row');
        $('#tax_display_').css('display', 'table-row');
    } else {
        $('#tax_display').css('display', 'none');
        $('#tax_display_').css('display', 'none');
    }

    if ($('#enable_bookin60_crm').prop('checked')) {
        $('#bookin60_crm_display').css('display', 'table-row');
        $('#lead_to_crm_display').css('display', 'table-row');
        $('#booking_to_crm_display').css('display', 'table-row');
        $('#bookin60_crm_calendarId_display').css('display', 'table-row');
        $('#bookin60_crm_timezone_display').css('display', 'table-row');
        $('#crm_notification_display').css('display', 'table-row');
    } else {
        $('#bookin60_crm_display').css('display', 'none');
        $('#lead_to_crm_display').css('display', 'none');
        $('#booking_to_crm_display').css('display', 'none');
        $('#bookin60_crm_calendarId_display').css('display', 'none');
        $('#bookin60_crm_timezone_display').css('display', 'none');
        $('#crm_notification_display').css('display', 'none');
    }

    if ($('#enable_help_link').prop('checked')) {
        $('#enable_help_link_display').css('display', 'table-row');
    } else {
        $('#enable_help_link_display').css('display', 'none');
    }

    if ($('#payment_method').val() != 'none') {
        $('#stripe-settings').css('display', 'table-row');
    } else {
        $('#stripe-settings').css('display', 'none');
    }

    $('#enable_payment_method').click(function () {
        if ($('#enable_payment_method').prop('checked')) {
            $('#stripe-section').css('display', 'table-row');
            $('#stripe-settings').css('display', 'table-row');
        } else {
            $('#stripe-section').css('display', 'none');
            $('#stripe-settings').css('display', 'none');
        }
    });

    $('#payment_method').change(function(){
      if($(this).val() == 'payment_method_stripe'){ 
        $('#stripe-settings').css('display', 'table-row');
      } else if ($(this).val() == 'payment_method_paypal') {
        $('#stripe-settings').css('display', 'none');
      } else if ($(this).val() == 'none') {
        $('#stripe-settings').css('display', 'none');
      } else {}
    });

    $('#enable_sales_tax').click(function () {
        if ($('#enable_sales_tax').prop('checked')) {
            $('#tax_display').css('display', 'table-row');
            $('#tax_display_').css('display', 'table-row');
        } else {
            $('#tax_display').css('display', 'none');
            $('#tax_display_').css('display', 'none');
        }
    });

    $('#enable_bookin60_crm').click(function () {
            if ($('#enable_bookin60_crm').prop('checked')) {
                $('#bookin60_crm_display').css('display', 'table-row');
                $('#lead_to_crm_display').css('display', 'table-row');
                $('#booking_to_crm_display').css('display', 'table-row');
                $('#bookin60_crm_calendarId_display').css('display', 'table-row');
                $('#bookin60_crm_timezone_display').css('display', 'table-row');
                $('#crm_notification_display').css('display', 'table-row');
            } else {
                $('#bookin60_crm_display').css('display', 'none');
                $('#lead_to_crm_display').css('display', 'none');
                $('#booking_to_crm_display').css('display', 'none');
                $('#bookin60_crm_calendarId_display').css('display', 'none');
                $('#bookin60_crm_timezone_display').css('display', 'none');
                $('#crm_notification_display').css('display', 'none');
            }
        });

    $('#enable_help_link').click(function () {
            if ($('#enable_help_link').prop('checked')) {
                $('#enable_help_link_display').css('display', 'table-row');
            } else {
                $('#enable_help_link_display').css('display', 'none');
            }
        });

    $('#set_custom_amount').click(function () {
        $('#form_amount').prop('disabled', true);
    });
    $('#set_specific_amount').click(function () {
        $('#form_amount').prop('disabled', false);
    });

    $('#form_redirect_to_page_or_post').change(function () {
        if ($(this).prop('checked')) {
            enable_combobox();
            $('#redirect_to_page_or_post_section').show();
            $('#redirect_to_url_section').hide();
        } else {
            disable_combobox();
            $('#redirect_to_page_or_post_section').hide();
            $('#redirect_to_url_section').show();
        }
    });

    $('#form_redirect_to_url').change(function () {
        if ($(this).prop('checked')) {
            $('#redirect_to_page_or_post_section').hide();
            $('#redirect_to_url_section').show();
        } else {
            $('#redirect_to_page_or_post_section').show();
            $('#redirect_to_url_section').hide();
        }
    });

    $('#form_redirect_to_page_or_post_help').change(function () {
        if ($(this).prop('checked')) {
            $('#redirect_to_page_or_post_help_section').show();
            $('#redirect_to_url_help_section').hide();
        } else {
            $('#redirect_to_page_or_post_help_section').hide();
            $('#redirect_to_url_help_section').show();
        }
    });

    $('#form_redirect_to_url_help').change(function () {
        if ($(this).prop('checked')) {
            $('#redirect_to_page_or_post_help_section').hide();
            $('#redirect_to_url_help_section').show();
        } else {
            $('#redirect_to_page_or_post_help_section').show();
            $('#redirect_to_url_help_section').hide();
        }
    });

    $('#form_redirect_to_page_or_post_giftcard').change(function () {
        if ($(this).prop('checked')) {
            enable_combobox();
            $('#redirect_to_page_or_post_giftcard_section').show();
            $('#redirect_to_url_giftcard_section').hide();
        } else {
            disable_combobox();
            $('#redirect_to_page_or_post_giftcard_section').hide();
        }
    });
    $('#form_redirect_to_url_giftcard').change(function () {
        if ($(this).prop('checked')) {
            $('#redirect_to_page_or_post_giftcard_section').hide();
            $('#redirect_to_url_giftcard_section').show();
        } else {
            $('#redirect_to_url_giftcard_section').hide();
        }
    });
    $('#form_redirect_lead_to_page').change(function () {
        if ($(this).prop('checked')) {
            enable_combobox();
            $('#redirect_lead_to_page_section').show();
        } else {
            disable_combobox();
            $('#redirect_lead_to_page_section').hide();
        }
    });
    $('#form_redirect_lead_to_steps').change(function () {
        if ($(this).prop('checked')) {
            $('#redirect_lead_to_page_section').hide();
        }
    });
    $('#do_redirect_no').click(function () {
        $('#form_redirect_page_or_post_id').val($('#form_redirect_page_or_post_id').prop('defaultSelected'));
        $('#form_redirect_url').val('');
        $('#form_redirect_to_page_or_post').prop('disabled', true);
        $('#form_redirect_to_url').prop('disabled', true);
        disable_combobox();
        $('#form_redirect_page_or_post_id').prop('disabled', true);
        $('#form_redirect_url').prop('disabled', true);
    });

    $('#do_redirect_yes').click(function () {
        $('#redirect_to_url_section').hide();
        $('#redirect_to_url_giftcard_section').hide();
        init_page_or_post_redirect();
        init_page_or_post_giftcard_redirect();
        $('#redirect_to_page_or_post_section').show();
        $('#redirect_to_page_or_post_giftcard_section').show();
    });

    // custom inputs
    $('#noinclude_custom_input').click(function () {
        $('#form_custom_input_label').prop('disabled', true);
    });
    $('#include_custom_input').click(function () {
        $('#form_custom_input_label').prop('disabled', false);
    });

    // page or post combobox
    $.widget("custom.page_or_post_combobox", {
        _create: function () {
            this.wrapper = $("<span>")
                .addClass("page_or_post-combobox")
                .insertAfter(this.element);

            this.element.hide();
            this._createAutocomplete();
            this._createShowAllButton();
        },

        _createAutocomplete: function () {
            var selected = this.element.children(":selected"),
                value = selected.val() ? selected.text() : "";

            this.input = $("<input>")
                .appendTo(this.wrapper)
                .val(value)
                .prop("disabled", true)
                .attr("title", "")
                .attr("placeholder", "Select from the list or start typing")
                .addClass("ui-widget")
                .addClass("ui-widget-content")
                .addClass("ui-corner-left")
                .addClass("page_or_post-combobox-input")
                .autocomplete({
                    delay: 0,
                    minLength: 0,
                    source: $.proxy(this, "_source")
                })
                .tooltip({
                    tooltipClass: "ui-state-highlight"
                });
            this._on(this.input, {
                autocompleteselect: function (event, ui) {
                    ui.item.option.selected = true;
                    this._trigger("select", event, {
                        item: ui.item.option
                    });
                },

                autocompletechange: "_removeIfInvalid"
            });
        },

        _createShowAllButton: function () {
            var input = this.input,
                wasOpen = false;

            $("<a>")
                .attr("tabIndex", -1)
                .attr("title", "Show all page and post")
                .tooltip()
                .appendTo(this.wrapper)
                .button({
                    icons: {
                        primary: "ui-icon-triangle-1-s"
                    },
                    text: false,
                    disabled: true
                })
                .removeClass("ui-corner-all")
                .addClass("page_or_post-combobox-toggle ui-corner-right")
                .mousedown(function () {
                    wasOpen = input.autocomplete("widget").is(":visible");
                })
                .click(function () {
                    input.focus();

                    // Close if already visible
                    if (wasOpen) {
                        return;
                    }

                    // Pass empty string as value to search for, displaying all results
                    input.autocomplete("search", "");
                });
        },

        _source: function (request, response) {
            var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
            response(this.element.children("option").map(function () {
                var text = $(this).text();
                if (this.value && ( !request.term || matcher.test(text) ))
                    return {
                        label: text,
                        value: text,
                        option: this
                    };
            }));
        },

        _removeIfInvalid: function (event, ui) {

            // Selected an item, nothing to do
            if (ui.item) {
                return;
            }

            // Search for a match (case-insensitive)
            var value = this.input.val(),
                valueLowerCase = value.toLowerCase(),
                valid = false;
            this.element.children("option").each(function () {
                if ($(this).text().toLowerCase() === valueLowerCase) {
                    this.selected = valid = true;
                    return false;
                }
            });

            // Found a match, nothing to do
            if (valid) {
                return;
            }

            // Remove invalid value
            this.input
                .val("")
                .attr("title", value + " didn't match any item")
                .tooltip("open");
            this.element.val("");
            this._delay(function () {
                this.input.tooltip("close").attr("title", "");
            }, 2500);
            this.input.autocomplete("instance").term = "";
        },

        _destroy: function () {
            this.wrapper.remove();
            this.element.show();
        }
    });

// Help link page or post combobox
    $.widget("custom.help_page_or_post_combobox", {
        _create: function () {
            this.wrapper = $("<span>")
                .addClass("page_or_post-combobox")
                .insertAfter(this.element);

            this.element.hide();
            this._createAutocomplete();
            this._createShowAllButton();
        },

        _createAutocomplete: function () {
            var selected = this.element.children(":selected"),
                value = selected.val() ? selected.text() : "";

            this.input = $("<input>")
                .appendTo(this.wrapper)
                .val(value)
                .prop("disabled", true)
                .attr("title", "")
                .attr("placeholder", "Select from the list or start typing")
                .addClass("ui-widget")
                .addClass("ui-widget-content")
                .addClass("ui-corner-left")
                .addClass("page_or_post-combobox-input")
                .autocomplete({
                    delay: 0,
                    minLength: 0,
                    source: $.proxy(this, "_source")
                })
                .tooltip({
                    tooltipClass: "ui-state-highlight"
                });
            this._on(this.input, {
                autocompleteselect: function (event, ui) {
                    ui.item.option.selected = true;
                    this._trigger("select", event, {
                        item: ui.item.option
                    });
                },

                autocompletechange: "_removeIfInvalid"
            });
        },

        _createShowAllButton: function () {
            var input = this.input,
                wasOpen = false;

            $("<a>")
                .attr("tabIndex", -1)
                .attr("title", "Show all page and post")
                .tooltip()
                .appendTo(this.wrapper)
                .button({
                    icons: {
                        primary: "ui-icon-triangle-1-s"
                    },
                    text: false,
                    disabled: true
                })
                .removeClass("ui-corner-all")
                .addClass("page_or_post-combobox-toggle ui-corner-right")
                .mousedown(function () {
                    wasOpen = input.autocomplete("widget").is(":visible");
                })
                .click(function () {
                    input.focus();

                    // Close if already visible
                    if (wasOpen) {
                        return;
                    }

                    // Pass empty string as value to search for, displaying all results
                    input.autocomplete("search", "");
                });
        },

        _source: function (request, response) {
            var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
            response(this.element.children("option").map(function () {
                var text = $(this).text();
                if (this.value && ( !request.term || matcher.test(text) ))
                    return {
                        label: text,
                        value: text,
                        option: this
                    };
            }));
        },

        _removeIfInvalid: function (event, ui) {

            // Selected an item, nothing to do
            if (ui.item) {
                return;
            }

            // Search for a match (case-insensitive)
            var value = this.input.val(),
                valueLowerCase = value.toLowerCase(),
                valid = false;
            this.element.children("option").each(function () {
                if ($(this).text().toLowerCase() === valueLowerCase) {
                    this.selected = valid = true;
                    return false;
                }
            });

            // Found a match, nothing to do
            if (valid) {
                return;
            }

            // Remove invalid value
            this.input
                .val("")
                .attr("title", value + " didn't match any item")
                .tooltip("open");
            this.element.val("");
            this._delay(function () {
                this.input.tooltip("close").attr("title", "");
            }, 2500);
            this.input.autocomplete("instance").term = "";
        },

        _destroy: function () {
            this.wrapper.remove();
            this.element.show();
        }
    });

// lead to page combobox
    $.widget("custom.lead_to_page_combobox", {
        _create: function () {
            this.wrapper = $("<span>")
                .addClass("lead_to_page-combobox")
                .insertAfter(this.element);

            this.element.hide();
            this._createAutocomplete();
            this._createShowAllButton();
        },

        _createAutocomplete: function () {
            var selected = this.element.children(":selected"),
                value = selected.val() ? selected.text() : "";

            this.input = $("<input>")
                .appendTo(this.wrapper)
                .val(value)
                .prop("disabled", true)
                .attr("title", "")
                .attr("placeholder", "Select from the list or start typing")
                .addClass("ui-widget")
                .addClass("ui-widget-content")
                .addClass("ui-corner-left")
                .addClass("lead_to_page-combobox-input")
                .autocomplete({
                    delay: 0,
                    minLength: 0,
                    source: $.proxy(this, "_source")
                })
                .tooltip({
                    tooltipClass: "ui-state-highlight"
                });
            this._on(this.input, {
                autocompleteselect: function (event, ui) {
                    ui.item.option.selected = true;
                    this._trigger("select", event, {
                        item: ui.item.option
                    });
                },

                autocompletechange: "_removeIfInvalid"
            });
        },

        _createShowAllButton: function () {
            var input = this.input,
                wasOpen = false;

            $("<a>")
                .attr("tabIndex", -1)
                .attr("title", "Show all page and post")
                .tooltip()
                .appendTo(this.wrapper)
                .button({
                    icons: {
                        primary: "ui-icon-triangle-1-s"
                    },
                    text: false,
                    disabled: true
                })
                .removeClass("ui-corner-all")
                .addClass("lead_to_page-combobox-toggle ui-corner-right")
                .mousedown(function () {
                    wasOpen = input.autocomplete("widget").is(":visible");
                })
                .click(function () {
                    input.focus();

                    // Close if already visible
                    if (wasOpen) {
                        return;
                    }

                    // Pass empty string as value to search for, displaying all results
                    input.autocomplete("search", "");
                });
        },

        _source: function (request, response) {
            var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
            response(this.element.children("option").map(function () {
                var text = $(this).text();
                if (this.value && ( !request.term || matcher.test(text) ))
                    return {
                        label: text,
                        value: text,
                        option: this
                    };
            }));
        },

        _removeIfInvalid: function (event, ui) {

            // Selected an item, nothing to do
            if (ui.item) {
                return;
            }

            // Search for a match (case-insensitive)
            var value = this.input.val(),
                valueLowerCase = value.toLowerCase(),
                valid = false;
            this.element.children("option").each(function () {
                if ($(this).text().toLowerCase() === valueLowerCase) {
                    this.selected = valid = true;
                    return false;
                }
            });

            // Found a match, nothing to do
            if (valid) {
                return;
            }

            // Remove invalid value
            this.input
                .val("")
                .attr("title", value + " didn't match any item")
                .tooltip("open");
            this.element.val("");
            this._delay(function () {
                this.input.tooltip("close").attr("title", "");
            }, 2500);
            this.input.autocomplete("instance").term = "";
        },

        _destroy: function () {
            this.wrapper.remove();
            this.element.show();
        }
    });

    $("#form_redirect_page_or_post_id").page_or_post_combobox();
    $("#form_redirect_page_or_post_id_help").help_page_or_post_combobox();
    $("#form_redirect_lead_to_page_id").lead_to_page_combobox();

    // currency combobox
    $.widget("custom.currency_combobox", {
        _create: function () {
            this.wrapper = $("<span>")
                .addClass("currency-combobox")
                .insertAfter(this.element);

            this.element.hide();
            this._createAutocomplete();
            this._createShowAllButton();
        },

        _createAutocomplete: function () {
            var selected = this.element.children(":selected"),
                value = selected.val() ? selected.text() : "";

            this.input = $("<input>")
                .appendTo(this.wrapper)
                .val(value)
                .attr("title", "")
                .attr("placeholder", "Select from the list or start typing")
                .addClass("ui-widget")
                .addClass("ui-widget-content")
                .addClass("ui-corner-left")
                .addClass("currency-combobox-input")
                .autocomplete({
                    delay: 0,
                    minLength: 0,
                    source: $.proxy(this, "_source")
                })
                .tooltip({
                    tooltipClass: "ui-state-highlight"
                });
            this._on(this.input, {
                autocompleteselect: function (event, ui) {
                    ui.item.option.selected = true;
                    this._trigger("select", event, {
                        item: ui.item.option
                    });
                },

                autocompletechange: "_removeIfInvalid"
            });
        },

        _createShowAllButton: function () {
            var input = this.input,
                wasOpen = false;

            $("<a>")
                .attr("tabIndex", -1)
                .attr("title", "Show all currencies")
                .tooltip()
                .appendTo(this.wrapper)
                .button({
                    icons: {
                        primary: "ui-icon-triangle-1-s"
                    },
                    text: false,
                    disabled: false
                })
                .removeClass("ui-corner-all")
                .addClass("currency-combobox-toggle ui-corner-right")
                .mousedown(function () {
                    wasOpen = input.autocomplete("widget").is(":visible");
                })
                .click(function () {
                    input.focus();

                    // Close if already visible
                    if (wasOpen) {
                        return;
                    }

                    // Pass empty string as value to search for, displaying all results
                    input.autocomplete("search", "");
                });
        },

        _source: function (request, response) {
            var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
            response(this.element.children("option").map(function () {
                var text = $(this).text();
                if (this.value && ( !request.term || matcher.test(text) ))
                    return {
                        label: text,
                        value: text,
                        option: this
                    };
            }));
        },

        _removeIfInvalid: function (event, ui) {

            // Selected an item, nothing to do
            if (ui.item) {
                return;
            }

            // Search for a match (case-insensitive)
            var value = this.input.val(),
                valueLowerCase = value.toLowerCase(),
                valid = false;
            this.element.children("option").each(function () {
                if ($(this).text().toLowerCase() === valueLowerCase) {
                    this.selected = valid = true;
                    return false;
                }
            });

            // Found a match, nothing to do
            if (valid) {
                return;
            }

            // Remove invalid value
            this.input
                .val("")
                .attr("title", value + " didn't match any item")
                .tooltip("open");
            this.element.val("");
            this._delay(function () {
                this.input.tooltip("close").attr("title", "");
            }, 2500);
            this.input.autocomplete("instance").term = "";
        },

        _destroy: function () {
            this.wrapper.remove();
            this.element.show();
        }
    });

    $("#currency").currency_combobox();

    $('#settings-form').submit(function (e) {

        $(".showLoading").show();
        $(".tips").removeClass('alert alert-error');
        $(".tips").html("");

        var $form = $(this);

        // Disable the submit button
        $form.find('button').prop('disabled', true);

        var valid = true;

        if (valid) {
            $.ajax({
                type: "POST",
                url: admin_ajaxurl.admin_ajaxurl,
                data: $form.serialize(),
                cache: false,
                dataType: "json",
                success: function (data) {
                    $(".showLoading").hide();
                    document.body.scrollTop = document.documentElement.scrollTop = 0;

                    if (data.success) {
                        $("#updateMessage").text("Settings updated");
                        $("#updateDiv").addClass('updated').show();
                        $form.find('button').prop('disabled', false);
                    }
                    else {
                        // re-enable the submit button
                        $form.find('button').prop('disabled', false);
                        // show the errors on the form
                        $(".tips").addClass('alert alert-error');
                        $(".tips").html(data.msg);
                        $(".tips").fadeIn(500).fadeOut(500).fadeIn(500);
                    }
                }
            });

            return false;
        }

    });

    $('#email-notifications-form').submit(function (e) {

        $(".showLoading").show();
        $(".tips").removeClass('alert alert-error');
        $(".tips").html("");

        var $form = $(this);

        //console.log($form.serialize());

        // Disable the submit button
        $form.find('button').prop('disabled', true);

        var valid = true;

        if (valid) {
            $.ajax({
                type: "POST",
                url: admin_ajaxurl.admin_ajaxurl,
                data: $form.serialize(),
                cache: false,
                dataType: "json",
                success: function (data) {

                    $(".showLoading").hide();
                    document.body.scrollTop = document.documentElement.scrollTop = 0;

                    if (data.success) {
                        $("#updateMessage").text("Settings updated");
                        $("#updateDiv").addClass('updated').show();
                        $form.find('button').prop('disabled', false);
                    }
                    else {
                        // re-enable the submit button
                        $form.find('button').prop('disabled', false);
                        // show the errors on the form
                        $(".tips").addClass('alert alert-error');
                        $(".tips").html(data.msg);
                        $(".tips").fadeIn(500).fadeOut(500).fadeIn(500);
                    }
                }
            });

            return false;
        }

    });

    $('#gift-card-form').submit(function (e) {

        $(".showLoading").show();
        $(".tips").removeClass('alert alert-error');
        $(".tips").html("");

        var $form = $(this);

        //console.log($form.serialize());

        // Disable the submit button
        $form.find('button').prop('disabled', true);

        var valid = true;

        if (valid) {
            $.ajax({
                type: "POST",
                url: admin_ajaxurl.admin_ajaxurl,
                data: $form.serialize(),
                cache: false,
                dataType: "json",
                success: function (data) {

                    console.log(data);

                    $(".showLoading").hide();
                    document.body.scrollTop = document.documentElement.scrollTop = 0;

                    if (data.success) {
                        $("#updateMessage").text("Settings updated");
                        $("#updateDiv").addClass('updated').show();
                        $form.find('button').prop('disabled', false);
                    }
                    else {
                        // re-enable the submit button
                        $form.find('button').prop('disabled', false);
                        // show the errors on the form
                        $(".tips").addClass('alert alert-error');
                        $(".tips").html(data.msg);
                        $(".tips").fadeIn(500).fadeOut(500).fadeIn(500);
                    }
                }
            });

            return false;
        }

    });

    $('#sms-form').submit(function (e) {

        $(".showLoading").show();
        $(".tips").removeClass('alert alert-error');
        $(".tips").html("");

        var $form = $(this);

        //console.log($form.serialize());

        // Disable the submit button
        $form.find('button').prop('disabled', true);

        var valid = true;

        if (valid) {
            $.ajax({
                type: "POST",
                url: admin_ajaxurl.admin_ajaxurl,
                data: $form.serialize(),
                cache: false,
                dataType: "json",
                success: function (data) {

                    $(".showLoading").hide();
                    document.body.scrollTop = document.documentElement.scrollTop = 0;

                    if (data.success) {
                        $("#updateMessage").text("Settings updated");
                        $("#updateDiv").addClass('updated').show();
                        $form.find('button').prop('disabled', false);
                    }
                    else {
                        // re-enable the submit button
                        $form.find('button').prop('disabled', false);
                        // show the errors on the form
                        $(".tips").addClass('alert alert-error');
                        $(".tips").html(data.msg);
                        $(".tips").fadeIn(500).fadeOut(500).fadeIn(500);
                    }
                }
            });

            return false;
        }

    });

       /**
        * ADD NEW CUSTOM SLOT
        */   

        $('#time-slot-form').submit(function (e) {
            var $form = $(this);
            do_ajax_post(admin_ajaxurl.admin_ajaxurl, $form, "Time slots updated.", true);
        });
        
       $('body').on('click', '#time-slots .add-slot', function() { 

            $('.save-slot').css('display', 'inline-block');

            //console.log('clicked');
         
            var data = {
                'action': 'service_add_slot',
            };  
            
            var $this = $(this);
            
            $.post(admin_ajaxurl.admin_ajaxurl, data, function(response) {
                //$this.closest('#time-slots').find('tbody').append( response );
                $('#time-slots:last-child').append( response );

                //console.log(response);
            });  

       }); 

       /**
        * SAVE TIME SLOT
        */   

       //$('.save-slot').click(function() {
      $('body').on('click', '#time-slots .save-slot', function() { 
          var id = $(this).attr('id');
          var week_day = [];

          $.each($("input[name=week_day_"+id+"]:checked"), function(){
               week_day.push($(this).val());
          });
          
          var data = {
               'id': $(this).attr('id'),
               'start_time': $('#start_time_'+id).val(),
               'end_time': $('#end_time_'+id).val(),
               'week_day': week_day,
               'capacity': $('#capacity_'+id).val(),
               'action': 'save_time_slot',
           };  
                      
           $.post(admin_ajaxurl.admin_ajaxurl, data, function(response) {
               console.log(response);
           });  
       });   

       
       
       /**
        * REMOVE CUSTOM SLOT
        */    
       $('body').on('click', '#time-slots .slot-delete', function() { 
         if( confirm('Are you sure?') ) {
           $(this).closest('tr').fadeOut(150, function() {
             $(this).remove();
           }); 

           var data = {
               'id': $('#time-slots .slot-delete').attr('id'),               
               'action': 'service_delete_slot',
           };  
                      
           $.post(admin_ajaxurl.admin_ajaxurl, data, function(response) {
               console.log(response);
           });       


         }
       });   

     /**
    * ADD NEW HOLIDAY
    */   

    $('#holiday-form').submit(function (e) {
            var $form = $(this);
            do_ajax_post(admin_ajaxurl.admin_ajaxurl, $form, "Calendar holiday updated.", true);
        });

      $('body').on('click', '#holiday-slots .add-slot', function() { 

           $('.save-slot').css('display', 'inline-block');
        
           var data = {
               'action': 'service_calendar_holiday',
           };  
           
           var $this = $(this);
           
           $.post(admin_ajaxurl.admin_ajaxurl, data, function(response) {
               $('#holiday-slots:last-child').append( response );

               $('.holiday_date').each(function() {
                  $('#'+this.id).datepicker({
                      dateFormat : 'yy-mm-dd',
                      minDate: 0,
                  }); 
               });
           });  

      }); 

      /**
       * SAVE HOLIDAY
       */   

      //$('.save-slot').click(function() {
     $('body').on('click', '#holiday-slots .save-slot', function() { 
         var id = $(this).attr('id');
         
         var data = {
              'id': $(this).attr('id'),
              'holiday_name': $('#holiday_name_'+id).val(),
              'holiday_description': $('#holiday_description_'+id).val(),
              'holiday_date': $('#holiday_date_'+id).val(),
              'action': 'save_holiday',
          };  
                     
          $.post(admin_ajaxurl.admin_ajaxurl, data, function(response) {
              console.log(response);
          });  
      });   

      
      
      /**
       * REMOVE HOLIDAY
       */    
      $('body').on('click', '#holiday-slots .slot-delete', function() { 
        if( confirm('Are you sure?') ) {
          $(this).closest('tr').fadeOut(150, function() {
            $(this).remove();
          }); 

          var data = {
              'id': $('#holiday-slots .slot-delete').attr('id'),               
              'action': 'service_delete_holiday',
          };  
                     
          $.post(admin_ajaxurl.admin_ajaxurl, data, function(response) {
              console.log(response);
          });       


        }
      });      

      $('.holiday_date').each(function() {
          $('#'+this.id).datepicker({
              dateFormat : 'MM dd, yy',
              minDate: 0,
          }); 
      }); 

    var wpzap_plus=$("#wpzap_plus");
    var wpzap_custom_url_column=$("#wpzap_custom_url_column");
    var i=1;

    wpzap_plus.on('click',function(event){      
        event.preventDefault();
         i++;
         if (i>1) {
                 wpzap_custom_url_column.after("<tr id='wpzap_custom_url_column"+i+"'><td><input  style='' type='text' name='zap_url_name[]' class='zap_url_name' id='zap_url_name"+i+"' value='' placeholder='Url name'> <input style='width:60%' type='text' name='zap_url_slug[]' class='zap_url_slug'  id='zap_url_slug"+i+"' value='' placeholder='Description'></label></td><td><input style='min-width:500px' type='text' class='wpzap_zapier_url_cls' name='wpzap_zapier_url[]' id='wpzap_zapier_url"+i+"' value=''></td><td><button style='height:27px; width:46px; margin-left:146px;' id='"+i+"' class='wz_remove_field '>x</button><button style='padding: 0 10px;margin-left: 21px;height: 32px;' id='wpzap_trigger_btn' class='button' >Test</button></td></tr>");         
          }
    });

    jQuery(document).on('click','.wz_remove_field',function(event){
              event.preventDefault();
              var button_id=$(this).attr("id");

              $("#wpzap_custom_url_column"+button_id+"").remove();

        });

    $(document).on('click','#wpzap_trigger_btn',function(event){
        event.preventDefault();
    
        var btn = $(this);
        var url = btn.parents('tr').find('.wpzap_zapier_url_cls');
        var wpzap_url = url.val();  
     
        if(wpzap_url.length <= 0){
            alert('Please enter a valid url before apply trigger.');
            return false;
        }
      
      
        var params ={
            'action' : 'trigger_zap',
            'api_url' : wpzap_url
        };

        $.post(admin_ajaxurl.admin_ajaxurl, params, function(response) {
            if(response.success) {
                alert('Zapier webhook triggered successfully. Please check your webhook zap.');
            }
        });  
                    
    });

    var booking_zap_plus=$("#booking_zap_plus");
    var booking_zap_custom_url_column=$("#booking_zap_custom_url_column");
    var i=1;

    booking_zap_plus.on('click',function(event){      
        event.preventDefault();
         i++;
         if (i>1) {
                 booking_zap_custom_url_column.after("<tr id='booking_zap_custom_url_column"+i+"'><td><input  style='' type='text' name='zap_url_name[]' class='zap_url_name' id='zap_url_name"+i+"' value='' placeholder='Url name'> <input style='width:60%' type='text' name='zap_url_slug[]' class='zap_url_slug'  id='zap_url_slug"+i+"' value='' placeholder='Description'></label></td><td><input style='min-width:500px' type='text' class='booking_zapier_url_cls' name='booking_zapier_url[]' id='booking_zapier_url"+i+"' value=''></td><td><button style='height:27px; width:46px; margin-left:146px;' id='"+i+"' class='booking_zap_remove_field '>x</button><button style='padding: 0 10px;margin-left: 21px;height: 32px;' id='booking_trigger_btn' class='button' >Test</button></td></tr>");         
          }
    });

    jQuery(document).on('click','.booking_zap_remove_field',function(event){
              event.preventDefault();
              var button_id=$(this).attr("id");

              $("#booking_zap_custom_url_column"+button_id+"").remove();

        });

    $(document).on('click','#booking_zap_trigger_btn',function(event){
        event.preventDefault();
    
        var btn = $(this);
        var url = btn.parents('tr').find('.booking_zap_url_cls');
        var booking_zap_url = url.val();  
     
        if(booking_zap_url.length <= 0){
            alert('Please enter a valid url before apply trigger.');
            return false;
        }
      
      
        var params ={
            'action' : 'trigger_booking_zap',
            'api_url' : booking_zap_url
        };

        $.post(admin_ajaxurl.admin_ajaxurl, params, function(response) {
            if(response.success) {
                alert('Zapier webhook triggered successfully. Please check your webhook zap.');
            }
        });  
                    
    });

    $(document).on('click','#send_test_email',function(event){
         event.preventDefault();     
      
        var params ={
            'action' : 'send_test_email',
            'booking': {
                'service_date': 'April 01, 2021',
                'service_time': 'April 01, 2021',
            }
        };

        $.post(admin_ajaxurl.admin_ajaxurl, params, function(response) {
            //alert('Test email sent.');
            console.log(response);
        }); 
                    
    });

});

/* When the user clicks on the button,
toggle between hiding and showing the dropdown content */
function myFunction() {
  document.getElementById("myDropdown").classList.toggle("show");
}

// Close the dropdown menu if the user clicks outside of it
window.onclick = function(event) {
  if (!event.target.matches('.dropbtn')) {
    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
    }
  }
}