/*------------------------------------------------------------------------
 Solidres - Hotel booking extension for Joomla
 ------------------------------------------------------------------------
 @Author    Solidres Team
 @Website   https://www.solidres.com
 @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 @License   GNU General Public License version 3, or later
 ------------------------------------------------------------------------*/

Solidres.options = {
    data: {},
    'get': function (key, def) {
        return typeof this.data[key.toUpperCase()] !== 'undefined' ? this.data[key.toUpperCase()] : def;
    },
    load: function (object) {
        for (var key in object) {
            this.data[key.toUpperCase()] = object[key];
        }
        return this;
    }
};

Solidres.sprintf = function (str) {
    var counter = 1;
    var args = arguments;

    return str.replace(/%s/g, function () {
        return args[counter++];
    });
};

Solidres.printTable = function (table) {
    nw = window.open('');
    nw.document.write(table.outerHTML);
    nw.print();
    nw.close();
};

Solidres.toggle = function (el, indicator) {
    if (el.style.display === 'none') {
        el.style.display = '';
    } else {
        el.style.display = 'none';
    }

    if (indicator) {
        if (el.style.display === '') {
            indicator.setAttribute('class', 'toggle_indicator float-end fa fa-subtract');
        } else if (el.style.display === 'none') {
            indicator.setAttribute('class', 'toggle_indicator float-end fa fa-plus');
        }
    }

    return el;
}

function isAtLeastOneRoomSelected() {
    var numberRoomTypeSelected = 0;
    Solidres.jQuery(".reservation_room_select").each(function () {
        var el = Solidres.jQuery(this);
        if (el.is(':checked') && !el.prop('disabled')) {
            numberRoomTypeSelected++;
            return;
        }
    });

    if (numberRoomTypeSelected > 0) {
        Solidres.jQuery('#sr-reservation-form-room button[type="submit"]').removeAttr('disabled');
    } else {
        Solidres.jQuery('#sr-reservation-form-room button[type="submit"]').attr('disabled', 'disabled');
    }
};

var isValidCheckInDate = function (day, allowedCheckinDays) {
    if (allowedCheckinDays.length == 0) {
        return false;
    }

    if (Solidres.jQuery.inArray(day, allowedCheckinDays) > -1) {
        return true;
    } else {
        return false;
    }
};

Solidres.validateCardForm = function (container) {
    var
        $ = Solidres.jQuery,
        form = container.parents('form'),
        paymentElement = container.data('element');

    if (container.hasClass('handled')) {
        return true;
    }

    container.addClass('handled');

    var
        acceptedCards = container.data('acceptedCards') || {all: true},
        cardNumRule = {
            required: true,
            creditcard: true,
            creditcardtypes: acceptedCards,
        },
        cardCVVRule = {
            required: true,
            number: true,
        },
        cardHolderRule = {
            required: true,
            lettersWithSpacesOnly: true,
        },
        expiration = {
            required: true,
            cardExpirationRule: true,
        };

    form.on('change', 'input.payment_method_radio', function (e) {
        e.preventDefault();
        var
            inputName = form.find('[name="jform[' + paymentElement + '][cardHolder]"]'),
            inputNumber = form.find('[name="jform[' + paymentElement + '][cardNumber]"]'),
            inputCvv = form.find('[name="jform[' + paymentElement + '][cardCvv]"]'),
            inputExpiration = form.find('[name="sr_payment_' + paymentElement + '_expiration"]');

        if ($(this).is(':checked') && this.value === paymentElement) {
            form.find('.payment_method_' + paymentElement + '_details').removeClass('nodisplay');
            form.find('.payment_method_' + paymentElement + '_details input').attr('required', true);
            form.find('.payment_method_' + paymentElement + '_details select').attr('required', true);
            inputNumber.length && inputNumber.rules('add', cardNumRule);
            inputCvv.length && inputCvv.rules('add', cardCVVRule);
            inputName.length && inputName.rules('add', cardHolderRule);
            inputExpiration.length && inputExpiration.rules('add', expiration);

        } else {
            form.find('.payment_method_' + paymentElement + '_details').addClass('nodisplay');
            form.find('.payment_method_' + paymentElement + '_details input').removeAttr('required');
            form.find('.payment_method_' + paymentElement + '_details select').removeAttr('required');
            inputNumber.length && inputNumber.rules('remove');
            inputCvv.length && inputCvv.rules('remove');
            inputName.length && inputName.rules('remove');
            inputExpiration.length && inputExpiration.rules('remove');
        }
    });
};

document.addEventListener("DOMContentLoaded", function(event) {

    const $ = Solidres.jQuery;

    document.addEventListener('click', (event) => {
        let handler = event.target.closest('.toggle_section');
        if (handler) {
            let indicator = handler.querySelector('.toggle_indicator');
            let targetSelector = handler.dataset.toggleTarget;

            if (targetSelector && targetSelector.substring(0, 1) === '#') {
                Solidres.toggle(document.getElementById(targetSelector.substring(1)), indicator);
            } else {
                let targetElements = document.getElementsByClassName(targetSelector.substring(1));

                for (let i = 0; i < targetElements.length; i ++) {
                    Solidres.toggle(targetElements[i], indicator);
                }
            }
        }
    });

    if (!$.validator.methods.hasOwnProperty('lettersWithSpacesOnly')) {
        $.validator.addMethod('lettersWithSpacesOnly', function (value, element) {
            return this.optional(element) || /^[a-z\s]+$/i.test(value);
        }, Joomla.Text._('SR_WARN_ONLY_LETTERS_N_SPACES_MSG', 'Letters and spaces only please'));
    }

    if (!$.validator.methods.hasOwnProperty('cardExpirationRule')) {
        $.validator.addMethod('cardExpirationRule', function (value, element) {
            if (!value.match(/^[0-9]{2}\/[0-9]{2}$/g)) {
                return false;
            }

            var
                form = $(element.form),
                date = new Date(),
                expiration = value.split('/'),
                month = parseInt(expiration[0]),
                year = parseInt(date.getFullYear().toString().substring(0, 2) + expiration[1]),
                nowYear = parseInt(date.getFullYear().toString()),
                nowMonth = parseInt(date.getMonth().toString()),
                elementName = $(element).parents('.sr-payment-card-form-container').data('element');

            if (month < 1
                || month > 12
                || year < nowYear
                || (year === nowYear && month < nowMonth + 1)
            ) {
                return false;
            }

            form.find('[name="jform[' + elementName + '][expiryMonth]"]').val(month.toString().length === 1 ? '0' + month : month);
            form.find('[name="jform[' + elementName + '][expiryYear]"]').val(year);

            return true;
        }, Joomla.Text._('SR_WARN_INVALID_EXPIRATION_MSG', 'Your card\'s expiration year is invalid or in the past.'));
    }

    var validateCardForm = function () {
        $('.sr-payment-card-form-container[data-accepted-cards]').each(function () {
            Solidres.validateCardForm($(this));
        });
    };

    validateCardForm();

    $('#solidres').on('click', '.reservation-navigate-back', function (event, pstep) {
        $('.reservation-tab').removeClass('active');
        $('.reservation-single-step-holder').removeClass('nodisplay').addClass('nodisplay');
        var self = $(this);

        if (typeof pstep === 'undefined') {
            var prevstep = self.data('prevstep');
        } else {
            var prevstep = pstep;
        }

        var active = $('.' + prevstep).removeClass('nodisplay');
        active.find('button[type=submit]').removeAttr('disabled');
        $('.reservation-tab').find('span.badge').removeClass('badge-info');
        $('.reservation-tab-' + prevstep).addClass('active').removeClass('complete');
        $('.reservation-tab-' + prevstep + ' span.badge').removeClass('badge-success').addClass('badge-info');

        document.getElementById('book-form').scrollIntoView();

        if ($('.rooms-rates-summary.module').length) {
            var summaryWrapper = $('.rooms-rates-summary.module');
            if (summarySidebarId) {
                var summaryWrapperParent = summaryWrapper.parents(summarySidebarId);
            } else {
                var summaryWrapperParent = summaryWrapper.parent();
            }

            if (prevstep == 'room' || prevstep == 'guestinfo') {
                summaryWrapperParent.show();
            } else {
                summaryWrapperParent.hide();
            }
        }
    });

    $('.confirmation').on('click', '#termsandconditions', function () {
        var self = $(this),
            submitBtn = $('.confirmation').find('button[type=submit]');

        if (self.is(':checked')) {
            submitBtn.removeAttr('disabled');
        } else {
            submitBtn.attr('disabled', 'disabled');
        }
    });

    $('#solidres .guestinfo').on('change', '.country_select', function () {
        $.ajax({
            url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&format=json&task=states.find&id=' + $(this).val(),
            success: function (html) {
                $('.state_select').empty();
                if (html.length > 0) {
                    $('.state_select').html(html);
                }
            }
        });
    });

    $('#solidres').on('change', '.trigger_tariff_calculating', function (event, updateChildAgeDropdown) {
        let self = $(this);
        let raid = self.data('raid');
        let roomtypeid = self.data('roomtypeid');
        let roomindex = self.data('roomindex');
        let roomid = self.data('roomid');
        let tariffid = self.attr('data-tariffid');
        let type = self.parents('.apartment-form-holder').length ? 1 : 0;

        let target = roomtypeid + '_' + tariffid + '_' + roomid;
        if (Solidres.context == "frontend" && Solidres.options.get('Hub_Dashboard') != 1) {
            target = roomtypeid + '_' + tariffid + '_' + roomindex;
        }

        let adult_number = 1;
        if ($("select.adults_number[data-identity='" + target + "']").length) {
            adult_number = $("select.adults_number[data-identity='" + target + "']").val();
        }
        let child_number = 0;
        if ($("select.children_number[data-identity='" + target + "']").length) {
            child_number = $("select.children_number[data-identity='" + target + "']").val();
        }

        let guest_number = null;
        if ($("select.guests_number[data-identity='" + target + "']").length) {
            guest_number = $("select.guests_number[data-identity='" + target + "']").val();
        }

        if (typeof updateChildAgeDropdown === 'undefined' || updateChildAgeDropdown === null) {
            updateChildAgeDropdown = true;
        }

        if (!updateChildAgeDropdown && self.hasClass('reservation-form-child-quantity')) {
            return;
        }

        if (self.hasClass('reservation-form-child-quantity') && child_number >= 1) {
            return;
        }

        let data = {};
        data.raid = raid;
        data.room_type_id = roomtypeid;
        data.room_index = roomindex;
        data.room_id = roomid;
        data.adult_number = adult_number;
        data.child_number = child_number;
        data.type = type;
        if (guest_number) {
            data.guest_number = guest_number;
        }
        if ($('input[name=checkin]').length) {
            data.checkin = $('input[name=checkin]').val();
        }
        if ($('input[name=checkout].trigger_tariff_calculating').length) {
            data.checkout = $('input[name=checkout].trigger_tariff_calculating').val();
        }
        data.tariff_id = tariffid;
        data.extras = [];

        for (let i = 0; i < child_number; i++) {
            let prop_name = 'child_age_' + target + '_' + i;
            if ($('.' + prop_name).val()) {
                data[prop_name] = $('.' + prop_name).val();
            }
        }

        let roomExtrasCheckboxes = $(".extras_row_roomtypeform_" + target + " input[type='checkbox']");
        if (roomExtrasCheckboxes.length) {
            roomExtrasCheckboxes.each(function () {
                if (this.checked) {
                    let extra_target = $(this).attr('data-target');
                    data[extra_target] = $(this).parent().find('select#' + extra_target).val();
                    data.extras.push($(this).attr('data-extraid'));
                }
            });
        }

        $.ajax({
            type: 'POST',
            url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationasset' + (Solidres.context === "frontend" ? "" : "base") + '.calculateTariff&format=json',
            data: data,
            cache: false,
            headers: {
                'X-CSRF-Token': Joomla.getOptions('csrf.token', '')
            },
            success: function (data) {
                const breakdownWrapper = $('.breakdown_wrapper');

                if (breakdownWrapper.length) {
                    breakdownWrapper.show();
                }

                if (!data.room_index_tariff) return;

                const tariffTarget = $('.tariff_' + target);

                if (!data.room_index_tariff.code && !data.room_index_tariff.value) {
                    if (tariffTarget.length) {
                        tariffTarget.text('0');
                    }
                } else {
                    if (tariffTarget.length) {
                        tariffTarget.text(data.room_index_tariff.formatted);
                    }
                    $('#breakdown_' + target).empty().html(data.room_index_tariff_breakdown_html);
                }
            },
            dataType: "json"
        });
    });

    $(document).on('click', '.toggle_extracost_confirmation', function () {
        var target = $('.extracost_confirmation');
        var self = $(this);
        target.toggle();
        if (target.is(":hidden")) {
            $('.extracost_row').removeClass().addClass('nobordered extracost_row');
        } else {
            $('.extracost_row').removeClass().addClass('nobordered extracost_row first');
        }
    });

    $('#solidres').on('change', '.reservation-form-child-quantity', function (event, updateChildAgeDropdown) {
        if (typeof updateChildAgeDropdown === 'undefined' || updateChildAgeDropdown === null) {
            updateChildAgeDropdown = true;
        }
        if (!updateChildAgeDropdown) {
            return;
        }
        const self = $(this);
        const quantity = self.val();
        let html = '';
        const raid = self.data('raid');
        const roomtypeid = self.data('roomtypeid');
        const roomid = self.data('roomid');
        const roomindex = self.data('roomindex');
        const tariffid = self.data('tariffid');
        let child_age_holder = self.parents('.occupancy-selection').find('.child-age-details');

        // Backend
        if (typeof child_age_holder === 'undefined' || child_age_holder.length == 0) {
            child_age_holder = self.parents('.room_selection_details').find('.child-age-details');
        }

        if (quantity > 0) {
            child_age_holder.css('display', 'block');
            child_age_holder.parent()[0].scrollIntoView();
        } else {
            child_age_holder.css('display', 'none');
        }

        const childMaxAgeLimit = Joomla.getOptions('com_solidres.general').ChildAgeMaxLimit;

        for (let i = 0; i < quantity; i++) {
            html += '<li>' + Joomla.Text._('SR_CHILD') + ' ' + (i + 1) +
                ' <select name="jform[room_types][' + roomtypeid + '][' + tariffid + '][' + (Solidres.context == "frontend" && Solidres.options.get('Hub_Dashboard') != 1 ? roomindex : roomid) + '][children_ages][' + i + ']" ' +
                'data-raid="' + raid + '"' +
                ' data-roomtypeid="' + roomtypeid + '"' +
                ' data-roomid="' + roomid + '"' +
                ' data-roomindex="' + roomindex + '"' +
                ' data-tariffid="' + tariffid + '"' +
                ' required ' +
                'class="form-select child_age_' + roomtypeid + '_' + tariffid + '_' + (Solidres.context == "frontend" && Solidres.options.get('Hub_Dashboard') != 1 ? roomindex : roomid) + '_' + i + ' trigger_tariff_calculating"> ';

            html += '<option value=""></option>';

            for (let age = 0; age <= childMaxAgeLimit; age++) {
                html += '<option value="' + age + '">' +
                    (age > 1 ? age + ' ' + Joomla.Text._('SR_CHILD_AGE_SELECTION_JS') : age + ' ' + Joomla.Text._('SR_CHILD_AGE_SELECTION_1_JS')) +
                    '</option>';
            }

            html += '</select></li>';
        }

        child_age_holder.find('ul').empty().append(html);
    });

    const submitReservationForm = function (form) {
        let self = $(form),
            url = self.attr('action'),
            formHolder = self.parent('.reservation-single-step-holder'),
            submitBtn = self.find('button[type=submit]'),
            currentStep = submitBtn.data('step');

        submitBtn.attr('disabled', 'disabled');
        submitBtn.html('<i class="fa fa-arrow-right"></i> ' + Joomla.Text._('SR_PROCESSING'));

        const bookFormAnchor = document.getElementById('book-form');

        if (bookFormAnchor) {
            bookFormAnchor.scrollIntoView(true);
        }

        $.post(url, self.serialize(), function (data) {
            if (data.status == 1) {

                const progressUrl = Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservation' + (Solidres.context == 'backend' ? 'base' : '') + '.progress&next_step=' + data.next_step;

                if (data.static == 1) {
                    const confirmForm = $('#sr-reservation-form-confirmation');

                    if (data.next_step == 'confirmation') {
                        const reCaptcha = $('#sr-apartment-captcha textarea[name="g-recaptcha-response"]');

                        if (reCaptcha.length) {
                            confirmForm.append(reCaptcha.clone().removeAttr('id'));
                        }

                        const files = self.find('input[type="file"]');

                        if (files.length) {
                            confirmForm.find('input[type="file"]').remove();
                            confirmForm.append(files.clone().addClass('hide').removeAttr('id'));
                        }

                        confirmForm.submit();
                    } else {
                        location.href = data.redirection;
                    }
                }

                $.ajax({
                    type: 'POST',
                    cache: false,
                    url: progressUrl,
                    headers: {
                        'X-CSRF-Token': Joomla.getOptions('csrf.token', '')
                    },
                    success: function (response) {
                        formHolder.addClass('nodisplay');
                        submitBtn.removeClass('nodisplay');

                        if (!submitBtn.hasClass('notxtsubs')) {
                            submitBtn.html('<i class="fa fa-arrow-right"></i> ' + Joomla.Text._('SR_NEXT'));
                        }

                        const next = $('.' + data.next_step);
                        next.removeClass('nodisplay').empty().append(response);

                        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                        const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                            return new bootstrap.Popover(popoverTriggerEl)
                        });

                        if (data.next == 'payment') {
                            $.metadata.setType("attr", "validate");
                        }
                        location.hash = '#book-form';
                        $('.reservation-tab').removeClass('active');
                        $('.reservation-tab-' + currentStep).addClass('complete');
                        $('.reservation-tab-' + currentStep + ' span.badge').removeClass('badge-info').addClass('badge-success');
                        $('.reservation-tab-' + data.next_step).addClass('active');
                        $('.reservation-tab-' + data.next_step + ' span.badge').addClass('badge-info');
                        const next_form = next.find('form.sr-reservation-form');
                        const confirmation_form = next.find('form#sr-reservation-form-confirmation');
                        if (next_form.attr('id') == 'sr-reservation-form-guest') {

                            const forceCustomerRegistration = Joomla.getOptions("com_solidres.property").ForceCustomerRegistration;

                            next_form.validate({
                                rules: {
                                    'jform[customer_email]': {required: true, email: true},
                                    'jform[customer_email2]': {equalTo: '[name="jform[customer_email]"]'},
                                    'jform[payment_method]': {required: true},
                                    'jform[customer_password]': {require: forceCustomerRegistration ? true : false, minlength: 8},
                                    'jform[customer_username]': {
                                        required: forceCustomerRegistration ? true : false,
                                        remote: {
                                            url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=user.check&format=json',
                                            type: 'POST',
                                            data: {
                                                username: function () {
                                                    return $('#username').val();
                                                }
                                            }
                                        }
                                    }
                                },
                                messages: {
                                    'jform[customer_username]': {
                                        remote: Joomla.Text._('SR_USERNAME_EXISTS')
                                    }
                                }
                            });

                            validateCardForm();

                            next_form.find('input.payment_method_radio:checked').trigger('change');

                            next_form.find('.country_select').trigger('change');

                            if (typeof onSolidresAfterSubmitReservationForm === 'function') {
                                onSolidresAfterSubmitReservationForm();
                            }
                        } else {
                            next_form.validate();
                        }

                        if ($('.rooms-rates-summary.module').length) {
                            const summaryWrapper = $('.rooms-rates-summary.module');
                            let summaryWrapperParent = summaryWrapper.parent();
                            if (summarySidebarId) {
                                summaryWrapperParent = summaryWrapper.parents(summarySidebarId);
                            }

                            if (confirmation_form.length) {
                                summaryWrapperParent.hide();
                            } else {
                                summaryWrapperParent.show();
                            }
                        }
                    }
                });
            } else if (data.captchaError) {
                submitBtn.html('<i class="fa fa-arrow-right"></i> ' + Joomla.Text._('SR_NEXT')).prop('disabled', false);
                const msg = $('#captcha-message').text(data.message);
                $('html, body').animate({scrollTop: msg.offset().top}, 400);
            }
        }, "json");
    }

    $('#solidres').on('submit', 'form.sr-reservation-form', function (event) {
        event.preventDefault();
        submitReservationForm(this);
    });

    $('#solidres').on('submit', 'form#sr-reservation-form-confirmation', function (event) {
        $(this).find("button[type='submit']").prop('disabled', true);
        var confirmForm = $(this);
        confirmForm.find('#filesUpload').remove();
        var filesUpload = $('<div id="filesUpload" style="display:none!important"/>').appendTo(confirmForm);
        var files = $('#sr-reservation-form-guest input[type="file"]');

        if (files.length) {
            files.each(function () {
                filesUpload.append($(this).clone());
            });
        }
    });

    $('#solidres').on('click', '.sr-field-remove-file', function (e) {
        e.preventDefault();

        if (confirm(Joomla.Text._('SR_CUSTOM_FIELD_CONFIRM_DELETE_UPLOAD_FILE', 'Are you sure you want to delete this file?'))) {
            var a = $(this);
            var data = {
                file: a.attr('data-file'),
            };
            data[a.attr('data-token')] = 1;
            a.find('.fa-times').attr('class', 'fa fa-spin fa-spinner');
            $.ajax({
                url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=customfield.deleteFile',
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function (response) {
                    if (response.success) {
                        var ref = $(a.attr('data-ref'));

                        if (a.attr('data-required')) {
                            ref.addClass('required');
                        }

                        ref.show();
                        a.parents('.sr-file-wrap').remove();
                    } else {
                        a.find('.fa-spinner').attr('class', 'fa fa-times');
                        alert(response.message);
                    }
                }
            });
        }
    });

    $('.roomtype-reserve-exclusive').click(function () {
        var self = $(this);
        var tariffid = self.data('tariffid');
        var rtid = self.data('rtid');
        $('.tariff-box .exclusive-hidden').prop('disabled', true);
        $('.tariff-box .exclusive-hidden-' + rtid + '-' + tariffid).prop('disabled', false);

        // Either booking full room type or per room is allowed at the same time
        $('.roomtype-quantity-selection.quantity_' + rtid).val('0');
        $('.roomtype-quantity-selection.quantity_' + rtid).trigger('change');

        submitReservationForm(document.getElementById('sr-reservation-form-room'));
    });

    $.fn.srRoomType = function (params) {
        params = $.extend({}, params);

        var bindDeleteRoomRowEvent = function () {
            $('.delete-room-row').unbind().click(function () {
                removeRoomRow(this);
            });
        };

        bindDeleteRoomRowEvent();

        removeRoomRow = function (delBtn) {
            var thisDelBtn = $(delBtn),
                nextSpan = thisDelBtn.next(),
                btnId = thisDelBtn.attr('id');

            nextSpan.addClass('ajax-loading');
            if (btnId != null) {
                roomId = btnId.substring(16);
                $.ajax({
                    url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=roomtype' + (Solidres.context == 'frontend' ? 'frontend' : '') + '.checkRoomReservation&tmpl=component&format=json&id=' + roomId,
                    context: document.body,
                    dataType: "JSON",
                    success: function (rs) {
                        nextSpan.removeClass('ajax-loading');
                        if (!rs) {
                            // This room can NOT be deleted
                            nextSpan.addClass('delete-room-row-error');
                            nextSpan.html(Joomla.Text._('SR_FIELD_ROOM_CAN_NOT_DELETE_ROOM') +
                                ' <a class="room-confirm-delete" data-roomid="' + roomId + '" href="#">Yes</a> | <a class="room-cancel-delete" href="#">No</a>');
                            $('.tier-room').on('click', '.room-confirm-delete', function () {
                                $.ajax({
                                    url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=roomtype' + (Solidres.context == 'frontend' ? 'frontend' : '') + '.removeRoomPermanently&tmpl=component&format=json&id=' + roomId,
                                    context: document.body,
                                    dataType: "JSON",
                                    success: function (rs) {
                                        if (!rs) {

                                        } else {
                                            // This room can be deleted
                                            thisDelBtn.parent().parent().remove();
                                        }
                                    }
                                });
                            });
                            $('.tier-room').on('click', '.room-cancel-delete', function () {
                                nextSpan.html('');
                            });
                        } else {
                            // This room can be deleted
                            thisDelBtn.parent().parent().remove();
                        }
                    }
                });
            } else {
                // New room, can be deleted since it has not had any relationship with Reservation yet
                thisDelBtn.parent().parent().remove();
            }
        },

            initRoomRow = function () {
                var rowIdRoom = params.rowIdRoom,
                    currentId = 'tier-room-' + rowIdRoom,
                    htmlStr = '';
                $('#room_tbl tbody').append('<tr id="' + currentId + '" class="tier-room"></tr>');
                var a = $('#' + currentId);
                htmlStr += '<td><a class="delete-room-row btn btn-sm btn-secondary"><i class="fa fa-minus"></i></a></td>';
                htmlStr += '<td><input type="text" class="form-control" name="jform[rooms][' + rowIdRoom + '][label]" required />';
                htmlStr += '<input type="hidden" name="jform[rooms][' + rowIdRoom + '][id]" value="new" /></td>';

                a.append(htmlStr);
                bindDeleteRoomRowEvent();
            };

        $('#new-room-tier').click(function (event) {
            event.preventDefault();
            initRoomRow();
            params.rowIdRoom++;
        });

        return this;
    };

    $('#solidres').on('change', '.occupancy_max_constraint', function () {
        const self = $(this);
        const max = self.data('max');
        const min = self.data('min');
        const roomtypeid = self.data('roomtypeid');
        const roomindex = self.data('roomindex');
        const roomid = self.data('roomid');
        const tariffid = self.attr('data-tariffid');
        let leftover = 0;
        let totalSelectable = 0;

        let target = roomid + '_' + tariffid + '_' + roomtypeid;
        if (Solidres.context === "frontend") {
            target = roomindex + '_' + tariffid + '_' + roomtypeid;
        }

        const targetElm = $('.occupancy_max_constraint_' + target);
        if (max > 0) {
            targetElm.each(function () {
                let s = $(this);
                let val = parseInt(s.val());
                if (val > 0) {
                    leftover += val;
                }
            });

            totalSelectable = max - leftover;

            targetElm.each(function () {
                let s = $(this);
                let val = parseInt(s.val());
                let from = 0;
                if (val > 0) {
                    from = val + totalSelectable;
                } else {
                    from = totalSelectable;
                }
                disableOptions(s, from);
            });
        }

        if (min > 0) {
            let totalAdultChildNumber = 0;
            targetElm.each(function () {
                let s = $(this);
                let val = parseInt(s.val());
                if (val > 0) {
                    totalAdultChildNumber += val;
                }
            });

            if (totalAdultChildNumber < min) {
                $('#error_' + target).show();
                targetElm.addClass('warning');
                if ($('#sr-reservation-form-room').length) {
                    $('#sr-reservation-form-room button[type="submit"]').attr('disabled', 'disabled');
                } else {
                    $('.apartment-form-holder button[type="submit"]').attr('disabled', 'disabled');
                }

            } else {
                $('#error_' + target).hide();
                targetElm.removeClass('warning');
                if ($('#sr-reservation-form-room').length) {
                    $('#sr-reservation-form-room button[type="submit"]').removeAttr('disabled', 'disabled');
                } else {
                    $('.apartment-form-holder button[type="submit"]').removeAttr('disabled', 'disabled');
                }
            }
        }
    });

    $('#solidres').on('click', '.reservation_room_select', function () {
        var self = $(this);
        var room_selection_details = $('#room_selection_details_' + self.val());
        var priceTable = $('#room' + self.val() + ' dl dt table');
        var span = $('#room' + self.val() + ' dl dt label span');
        if (self.is(':checked')) {
            room_selection_details.show();
            priceTable.show();
            span.addClass('label-success');
            room_selection_details.find('select.tariff_selection').removeAttr('disabled');
            room_selection_details.find('input.guest_fullname').removeAttr('disabled');
            room_selection_details.find('select.adults_number').removeAttr('disabled');
            room_selection_details.find('select.children_number').removeAttr('disabled');
            $('#room_selection_details_' + self.val() + ' .extras_row_roomtypeform').each(function () {
                var li = $(this);
                var chk = li.find('input:checkbox');
                if (chk.is(':checked')) {
                    var sel = li.find('select');
                    sel.removeAttr('disabled');
                }
            });
            $('#room_selection_details_' + self.val() + ' .extras_row_roomtypeform input:checkbox').trigger('change');
        } else {
            room_selection_details.hide();
            priceTable.hide();
            span.removeClass('label-success');
            room_selection_details.find('select.tariff_selection').attr('disabled', 'disabled');
            room_selection_details.find('input.guest_fullname').attr('disabled', 'disabled');
            room_selection_details.find('select.adults_number').attr('disabled', 'disabled');
            room_selection_details.find('select.children_number').attr('disabled', 'disabled');
            room_selection_details.find('input:hidden').attr('disabled', 'disabled');
            room_selection_details.find('.extras_row_roomtypeform select').attr('disabled', 'disabled');
        }

        isAtLeastOneRoomSelected();
    });

    $('#solidres').on('click', '.room input:checkbox, .guestinfo input:checkbox, .sr-apartment-form input:checkbox', function () {
        var self = $(this);
        var extraItem = $('#' + self.data('target'));
        if (self.is(':checked')) {
            extraItem.removeAttr('disabled');
            extraItem.trigger('change');
        } else {
            extraItem.attr('disabled', 'disabled');
            extraItem.trigger('change');
        }
    });

    $('#solidres').on('change', '.tariff_selection', function () {
        var self = $(this);
        if (self.val() == '') {
            $('a.tariff_breakdown_' + self.data('roomid')).hide();
            $('span.tariff_breakdown_' + self.data('roomid')).text('0');
            return false;
        }
        var parent = self.parents('.room_selection_wrapper');
        var input = parent.find('.room_selection_details input[type="text"]').not('[name^="jform[roomFields]"]');
        var checkboxes = parent.find('.room_selection_details input[type="checkbox"]').not('[name^="jform[roomFields]"]');
        var select = parent.find('.room_selection_details select').not('.tariff_selection, [name^="jform[roomFields]"]');
        var spans = parent.find('dt span');
        var breakdown_trigger = parent.find('dt a.toggle_breakdown');
        var breakdown_holder = parent.find('dt span.breakdown');
        var extra_wrapper = parent.find('.extras_row_roomtypeform');
        var extra_input_hidden = parent.find('.extras_row_roomtypeform input[type="hidden"]');

        input.attr('name', input.attr('name').replace(/^(jform\[room_types\])(\[[0-9]+\])(\[[-?0-9a-z]*\])(.*)$/, '$1$2[' + self.val() + ']$4'));
        if (extra_input_hidden.length > 0) {
            extra_input_hidden.attr('name', extra_input_hidden.attr('name').replace(/^(jform\[room_types\])(\[[0-9]+\])(\[[0-9a-z]*\])(.*)$/, '$1$2[' + self.val() + ']$4'));
        }

        select.each(function () {
            var self_sel = $(this);
            self_sel.attr('name', self_sel.attr('name').replace(/^(jform\[room_types\])(\[[0-9]+\])(\[[-?0-9a-z]*\])(.*)$/, '$1$2[' + self.val() + ']$4'));
            self_sel.attr('data-tariffid', self.val());
            if (self_sel.attr('data-identity')) {
                self_sel.attr('data-identity', self_sel.attr('data-identity').replace(/^([0-9]+)(_)([-?0-9a-z]*)(_)(.*)$/, '$1$2' + self.val() + '$4$5'));
            }
            if (self_sel.hasClass('extra_quantity')) {
                self_sel.attr('id', self_sel.attr('id').replace(/^([-?0-9a-z]+)(_)([0-9]+)(_)([-?0-9a-z]*)(_)([-?0-9a-z]*)(_)(.*)$/, '$1$2$3$4' + self.val() + '$6$7$8$9'));
            }
        });
        checkboxes.each(function () {
            $(this).removeAttr('disabled');
            if ($(this).attr('data-target')) {
                $(this).attr('data-target', $(this).attr('data-target').replace(/^([a-z]+)(_)([0-9]+)(_)([-?0-9a-z]*)(_)(.*)$/, '$1$2$3$4' + self.val() + '$6$7'));
            }
        });
        breakdown_trigger.attr('data-target', breakdown_trigger.data('target').replace(/^([0-9]+)(_)([0-9a-z]*)(_)(.*)$/, '$1$2' + self.val() + '$4$5'));
        breakdown_holder.attr('id', breakdown_holder.attr('id').replace(/^([a-z]+)(_)([0-9]+)(_)([-?0-9a-z]*)(_)(.*)$/, '$1$2$3$4' + self.val() + '$6$7'));
        spans.each(function () {
            var self_spa = $(this);
            self_spa.attr('class', self_spa.attr('class').replace(/^([a-z]+)(_)([0-9]+)(_)([-?0-9a-z]*)(_)(.*)$/, '$1$2$3$4' + self.val() + '$6$7'));
        });

        if (self.val() != '') {
            $('.tariff_breakdown_' + self.data('roomid')).show();
        } else {
            $('.tariff_breakdown_' + self.data('roomid')).hide();
        }

        if (extra_wrapper.length) {
            extra_wrapper.attr('id', extra_wrapper.attr('id').replace(/^([a-z]+)(_)([a-z]+)(_)([a-z]+)(_)([0-9]+)(_)([-?0-9a-z]*)(_)(.*)$/, '$1$2$3$4$5$6$7$8' + self.val() + '$10$11'));
        }

        $('#room' + self.data('roomid') + ' .adults_number.trigger_tariff_calculating').trigger('change');
    });

    $('#solidres').on('change paste keyup', '#sr-reservation-form-confirmation .total_price_tax_excl_single_line', function () {
        var sum = 0;
        $.each($('.total_price_tax_excl_single_line'), function () {
            sum += parseFloat($(this).val() != '' ? $(this).val() : 0);
        });
        $('.total_price_tax_excl').text(sum);
        updateGrandTotal();
    });

    $('#solidres').on('change paste keyup', '#sr-reservation-form-confirmation .room_price_tax_amount_single_line', function () {
        var sum = 0;
        $.each($('.room_price_tax_amount_single_line'), function () {
            sum += parseFloat($(this).val() != '' ? $(this).val() : 0);
        });
        $('.tax_amount').val(sum);
        updateGrandTotal();
    });

    $('#solidres').on('change paste keyup', '#sr-reservation-form-confirmation .tax_amount', function () {
        updateGrandTotal();
    });

    $('#solidres').on('change paste keyup', '#sr-reservation-form-confirmation .extra_price_single_line', function () {
        var sum = 0;
        $.each($('.extra_price_single_line'), function () {
            sum += parseFloat($(this).val() != '' ? $(this).val() : 0);
        });
        $('.total_extra_price').text(sum);
        updateGrandTotal();
    });

    $('#solidres').on('change paste keyup', '#sr-reservation-form-confirmation .extra_tax_single_line', function () {
        var sum = 0;
        $.each($('.extra_tax_single_line'), function () {
            sum += parseFloat($(this).val() != '' ? $(this).val() : 0);
        });
        $('.total_extra_tax').text(sum);
        updateGrandTotal();
    });

    $('#solidres').on('change paste keyup', '#sr-reservation-form-confirmation .total_discount', function () {
        var sum = 0;
        $.each($('.total_discount'), function () {
            sum += parseFloat($(this).val() != '' ? $(this).val() : 0);
        });
        updateGrandTotal();
    });

    function updateGrandTotal() {
        sum = 0;
        $.each($('.grand_total_sub'), function () {

            if ($(this).val()) {
                sum += parseFloat($(this).val());
            } else if ($(this).attr('val')) {
                sum += parseFloat($(this).attr('val'));
            }

        });
        $('.grand_total').text(sum);
    }

    const reservationNoteForm = document.getElementById('reservationnote-form');

    if (reservationNoteForm) {
        reservationNoteForm.addEventListener('submit', function(event) {
            const action = reservationNoteForm.action;
            const submitBtn = reservationNoteForm.querySelector('button[type=submit]');
            const processingIndicator = reservationNoteForm.querySelector('div.processing');

            submitBtn.disabled = true;
            submitBtn.classList.add('nodisplay');
            processingIndicator.classList.remove('nodisplay');
            processingIndicator.classList.add('active');

            Joomla.request({
                url: action,
                method: 'POST',
                data: new FormData(reservationNoteForm),
                perform: true,
                onSuccess: rawJson => {

                    let response = JSON.parse(rawJson);

                    submitBtn.classList.remove('nodisplay');
                    submitBtn.disabled = false;
                    processingIndicator.classList.add('nodisplay');
                    processingIndicator.classList.remove('active');

                    const holder = document.getElementById('reservation-note-holder');

                    holder.insertAdjacentHTML('beforeend', response.note_html);
                    reservationNoteForm.querySelector('textarea').value = '';
                    reservationNoteForm.querySelector('input[type="checkbox"]').checked = false;

                    let noteFileInput = reservationNoteForm.querySelector('input[type="file"]');

                    if (noteFileInput) {
                        reservationNoteForm.querySelector('input[type="file"]').value = '';
                    }
                },
                onError: () => {
                }
            });

            event.preventDefault();
        });
    }

    $('#solidres .room').on('change', '.extras_row_roomtypeform input:checkbox', function () {
        var chk = $(this);
        var extraId = chk.data('extraid');
        parent = chk.parents('.room-form-item').find('.assigned-extra');
        if (chk.is(':checked') && parent.hasClass('extra-' + extraId)) {
            parent.show();
        } else if (!chk.is(':checked') && parent.hasClass('extra-' + extraId)) {
            parent.hide();
        }
    });

    var reloadSum = function (form) {
        var self = $(form),
            url = self.attr('action');

        $.post(url, self.serialize() + '&jform[reloadSum]=1', function (data) {
            if (data.status == 1 && data.next_step == '') {
                Solidres.getSummary();
            }
        }, "json");
    }

    $('#solidres').on('change', '.reload-sum', function () {
        reloadSum(document.getElementById('sr-reservation-form-guest'));
    });

    $('.toggle-discount-sub-lines').click(function () {
        $(this).siblings('.sub-line-item').toggle();
    });

    $.validator.setDefaults({
        errorPlacement: function (error, element) {
            if (element.parents("[data-fieldset-group]").length) {
                error.insertAfter(element.parents("[data-fieldset-group]"));
            } else {
                error.insertAfter(element);
            }
        }
    });
});

function changeCheckButtonState() {
    if (document.querySelector('.sr-datepickers input[name="checkin"]').value
        &&
        document.querySelector('.sr-datepickers input[name="checkout"]').value) {
        document.querySelector('.sr-datepickers .searchbtn').removeAttribute('disabled');
    } else {
        document.querySelector('.sr-datepickers .searchbtn').setAttribute('disabled', 'disabled');
    }
}

function updateCheckinField(value, dateFormat) {
    const $ = Solidres.jQuery;
    $('.sr-datepickers input[name="checkin"]').val($.datepicker.formatDate("yy-mm-dd", value));
    $('.checkin_roomtype').removeAttr('readonly').val($.datepicker.formatDate(dateFormat, value)).attr('readonly', 'readonly');
}

function updateCheckoutField(value, dateFormat) {
    const $ = Solidres.jQuery;
    $('.sr-datepickers input[name="checkout"]').val($.datepicker.formatDate("yy-mm-dd", value));
    $('.checkout_roomtype').removeAttr('readonly').val($.datepicker.formatDate(dateFormat, value)).attr('readonly', 'readonly');

    if ($(".apartment-form-holder").length) {
        $(".apartment-form-holder").find(".trigger_tariff_calculating").eq(0).trigger("change");
    }
}

function getCheckInOutDates(container) {
    // Get the element out of jQuery instance
    const containerElm = container[0];
    const checkIn = containerElm.querySelector('input[name="checkin"]');
    const checkOut = containerElm.querySelector('input[name="checkout"]');

    Joomla.request({
        url: Joomla.getOptions("system.paths").base + "/index.php?option=com_solidres&task=reservationasset.getCheckInOutDates&checkin=" + checkIn.value + "&checkout=" + checkOut.value + "&bookingType=" + Joomla.getOptions("com_solidres.general").BookingType,
        method: 'POST',
        onSuccess: function(data) {
            const occupancyStatus = containerElm.querySelector('.occupancy-status');

            if (occupancyStatus) {
                occupancyStatus.style.display = 'block';
                occupancyStatus.innerHTML = data;
            }

        }
    });
}

Solidres.placeHolder = function (container, action) {
    var $ = Solidres.jQuery;

    if (container) {
        container = $(container);
    } else {
        container = $('#solidres');
    }

    var placeHolders = container.find('[sr-placeholder-item]');
    var placeHolder = container.find('.sr-placeholder-wrap');

    if (placeHolders.length) {
        if (action === 'show') {
            var el;
            placeHolders.html(function () {
                return '<div class="sr-placeholder">' + $(this).html() + '</div>';
            }).find('.sr-placeholder').each(function () {
                el = $(this);
                if (el.find('>img').length) {
                    el.css({
                        position: 'relative',
                        textAlign: 'center',
                        display: 'block',
                        padding: '25px 0',
                        marginBottom: 5
                    })
                        .html('<i class="fa fa-image" style="font-size: 50px; color: #ddd; margin: auto;"></i>');
                } else {
                    el.html(function () {
                        return '<div style="visibility: hidden">' + $(this).html() + '</div>';
                    });
                }
            });

            container.find('.sr-placeholder-hidden').hide();
        }
    } else {
        if (!placeHolder.length) {
            placeHolder = $('<div class="sr-placeholder-wrap" style="display: none; position: relative; width: 100%;">'
                + '<div class="sr-placeholder" style="width: 100%; height: 70px;"></div>'
                + '<div class="sr-placeholder" style="position: absolute; left: 0; top: 5px; right: 0; width: 98%; height: 58px; margin: auto; text-align: center;"><i class="fa fa-image" style="font-size: 50px; color: #ddd; margin-top: 5px;"></i></div>'
                + '<div class="sr-placeholder" style="display: block; margin-bottom: 5px; width: 50%; height: 15px;"></div>'
                + '<div class="sr-placeholder" style="width: 50%; height: 15px;"></div>'
                + '</div>');
            container.append(placeHolder);
        }

        if (action === 'show') {
            container.html(placeHolder.show());
        } else if (action === 'hide') {
            placeHolder.hide();
        }
    }
};

Solidres.getSummary = function () {
    var $ = Solidres.jQuery;
    var summaryWrapper = $('.rooms-rates-summary.module');
    var summaryStickyWrapper = $('.rooms-rates-summary-sticky');
    var isApartmentView = summaryWrapper.hasClass('apartment_view');
    $.ajax({
        type: 'GET',
        cache: false,
        url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservation' + (Solidres.context == 'backend' ? 'base' : '') + '.getSummary&type=' + (isApartmentView ? 1 : 0),
        beforeSend: function () {
            summaryWrapper.remove('.sticky-loading').append($('<div class="sticky-loading"></div>'));
            var stickyWidth = summaryStickyWrapper.parent().width() - parseInt(summaryStickyWrapper.css('padding-left')) - parseInt((summaryStickyWrapper.css('padding-right')));
            var stickyHeight = summaryWrapper.height();
            $('.sticky-loading').css({'width': stickyWidth, 'height': stickyHeight});
        },
        complete: function () {
            summaryWrapper.remove('.sticky-loading');
        },
        success: function (response) {
            summaryWrapper.empty().append(response);
            $.ajax({
                type: 'GET',
                cache: false,
                url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservation' + (Solidres.context == 'backend' ? 'base' : '') + '.getOverviewCost&format=json',
                success: function (response) {
                    $('.overview-cost-grandtotal').empty().append(response.grand_total);
                }
            });
        }
    });
}

Solidres.loadingLayer = function (show = true) {
    if (show) {
        document.body.appendChild(document.createElement('joomla-core-loader'));
    } else {
        const spinnerElement = document.querySelector('joomla-core-loader');
        spinnerElement.parentNode.removeChild(spinnerElement);
    }
};

Solidres.initAgeFields = function (wrapper) {
    const formChildQuantitySelects = wrapper.getElementsByClassName("form-select-occupancy-child");
    if (formChildQuantitySelects) {
        for (let i = 0; i < formChildQuantitySelects.length; i++) {
            formChildQuantitySelects[i].addEventListener("change", function(event) {
                let roomChildQuantity = event.target.value;
                const formRoomChildAgeSelectsWrapper = wrapper.getElementsByClassName(event.target.id);

                for (let y = 0; y < formRoomChildAgeSelectsWrapper.length; y++) {
                    formRoomChildAgeSelectsWrapper[y].style.display = "none";
                    let targetChildAgeInput = formRoomChildAgeSelectsWrapper[y].getElementsByTagName("select");
                    targetChildAgeInput[0].setAttribute('disabled', 'disabled');
                    targetChildAgeInput[0].removeAttribute('required');
                }

                for (let x = 1; x <= roomChildQuantity; x++) {
                    let targetChildAgeInputWrapper = wrapper.getElementsByClassName(event.target.id + "_" + x)[0];
                    let targetChildAgeInput = targetChildAgeInputWrapper.getElementsByTagName("select");
                    targetChildAgeInputWrapper.style.display = "block";
                    targetChildAgeInput[0].removeAttribute('disabled');
                    targetChildAgeInput[0].required = true;
                }
            });
        }
    }
}

Solidres.initDatePickers = function (wrapperId, dpMinCheckoutDate, dpDefaultCheckinDate, dpDefaultCheckoutDate, dpMinCheckinDate = '', dpMaxCheckinDate = '', enableUnoccupiedPricing = false, enabledCheckinDays = []) {
    const $ = Solidres.jQuery;
    const generalOptions = Joomla.getOptions('com_solidres.general');
    const minLengthOfStay = generalOptions.MinLengthOfStay;
    const minDaysBookInAdvance = generalOptions.MinDaysBookInAdvance;
    const maxDaysBookInAdvance = generalOptions.MaxDaysBookInAdvance;
    const dateFormat = generalOptions.DateFormatJS;
    const moduleInstance = $('#' + wrapperId);

    if (dpMinCheckinDate.length === 0) {
        dpMinCheckinDate = minDaysBookInAdvance;
    }

    if (dpMaxCheckinDate.length === 0) {
        dpMaxCheckinDate = maxDaysBookInAdvance;
    }

    const checkout = moduleInstance.find('.checkout_datepicker_inline_module').datepicker({
        minDate: typeof dpMinCheckoutDate === 'number' ? '+' + dpMinCheckoutDate + 'd' : new Date(Date.parse(dpMinCheckoutDate)),
        numberOfMonths: generalOptions.DatePickerMonthNum,
        showButtonPanel: true,
        dateFormat: dateFormat,
        firstDay: generalOptions.WeekStartDay,
        defaultDate: dpDefaultCheckoutDate.length > 0 ? new Date(Date.parse(dpDefaultCheckoutDate)) : null,
        onSelect: function () {
            moduleInstance.find('input[name="checkout"]').val($.datepicker.formatDate('yy-mm-dd', $(this).datepicker('getDate')));
            moduleInstance.find('.checkout_module').removeAttr("readonly").val($.datepicker.formatDate(dateFormat, $(this).datepicker('getDate'))).attr('readonly', 'readonly');
            moduleInstance.find('.checkout_datepicker_inline_module').slideToggle();
            moduleInstance.find('.checkin_module').removeClass('disabledCalendar');
            if (generalOptions.EnableUnoccupiedPricing === 1 && enableUnoccupiedPricing) {
                getCheckInOutDates(moduleInstance);
            }
        }
    });
    const checkin = moduleInstance.find('.checkin_datepicker_inline_module').datepicker({
        minDate: typeof dpMinCheckinDate === 'number' ? '+' + dpMinCheckinDate + 'd' : new Date(Date.parse(dpMinCheckinDate)),
        maxDate: typeof dpMaxCheckinDate === 'number' && dpMaxCheckinDate > 0 ? '+' + maxDaysBookInAdvance : (dpMaxCheckinDate.length > 0 ? new Date(Date.parse(dpMaxCheckinDate)) : null),
        numberOfMonths: generalOptions.DatePickerMonthNum,
        showButtonPanel: true,
        dateFormat: dateFormat,
        firstDay: generalOptions.WeekStartDay,
        defaultDate: dpDefaultCheckinDate.length > 0 ? new Date(Date.parse(dpDefaultCheckinDate)) : null,
        onSelect: function () {
            var currentSelectedDate = $(this).datepicker('getDate');
            var checkoutMinDate = $(this).datepicker('getDate', '+1d');
            checkoutMinDate.setDate(checkoutMinDate.getDate() + minLengthOfStay);
            checkout.datepicker('option', 'minDate', checkoutMinDate);
            checkout.datepicker('setDate', checkoutMinDate);

            moduleInstance.find('input[name="checkin"]').val($.datepicker.formatDate('yy-mm-dd', currentSelectedDate));
            moduleInstance.find('input[name="checkout"]').val($.datepicker.formatDate('yy-mm-dd', checkoutMinDate));

            moduleInstance.find('.checkin_module').removeAttr('readonly').val($.datepicker.formatDate(dateFormat, currentSelectedDate)).attr('readonly', 'readonly');
            moduleInstance.find('.checkout_module').removeAttr('readonly').val($.datepicker.formatDate(dateFormat, checkoutMinDate)).attr('readonly', 'readonly');
            moduleInstance.find('.checkin_datepicker_inline_module').slideToggle();
            moduleInstance.find('.checkout_module').removeClass('disabledCalendar');
            if (generalOptions.EnableUnoccupiedPricing === 1 && enableUnoccupiedPricing) {
                getCheckInOutDates(moduleInstance);
            }
        },
        beforeShowDay: function (date) {

            if (enabledCheckinDays.length === 0) {
                return [true, 'bookable'];
            }

            let day = date.getDay();

            if (isValidCheckInDate(day, enabledCheckinDays)) {
                return [true, 'bookable'];
            } else {
                return [false, 'notbookable'];
            }
        }

    })
    $(".ui-datepicker").addClass('notranslate');
    moduleInstance.find('.checkin_module').click(function () {
        if (!$(this).hasClass('disabledCalendar')) {
            moduleInstance.find('.checkin_datepicker_inline_module').slideToggle('fast', function () {
                if ($(this).is(':hidden')) {
                    moduleInstance.find('.checkout_module').removeClass('disabledCalendar');
                } else {
                    moduleInstance.find('.checkout_module').addClass('disabledCalendar');
                }
            });
        }
    });

    moduleInstance.find('.checkout_module').click(function () {
        if (!$(this).hasClass('disabledCalendar')) {
            moduleInstance.find('.checkout_datepicker_inline_module').slideToggle('fast', function () {
                if ($(this).is(":hidden")) {
                    moduleInstance.find('.checkin_module').removeClass('disabledCalendar');
                } else {
                    moduleInstance.find('.checkin_module').addClass('disabledCalendar');
                }
            });
        }
    });

    moduleInstance.find('.room_quantity').change(function () {
        let curQuantity = $(this).val();
        moduleInstance.find('.room_num_row').each(function (index) {
            let index2 = index + 1;
            if (index2 <= curQuantity) {
                moduleInstance.find('#room_num_row_' + index2).show();
                moduleInstance.find('#room_num_row_' + index2 + ' select.form-select-occupancy').removeAttr('disabled');
            } else {
                moduleInstance.find('#room_num_row_' + index2).hide();
                moduleInstance.find('#room_num_row_' + index2 + ' select.form-select-occupancy').attr('disabled', 'disabled');
            }
        });
    });

    if (moduleInstance.find('.room_quantity').val() > 0) {
        moduleInstance.find('.room_quantity').trigger('change');
    }

    if (generalOptions.EnableUnoccupiedPricing === 1 && enabledCheckinDays) {
        getCheckInOutDates(moduleInstance);
    }
}

function disableOptions(selectEl, from) {
    const $ = Solidres.jQuery;

    $('option', selectEl).each(function () {
        let val = parseInt($(this).attr('value'));
        if (val > from) {
            $(this).attr('disabled', 'disabled');
        } else {
            $(this).removeAttr('disabled');
        }
    });
}