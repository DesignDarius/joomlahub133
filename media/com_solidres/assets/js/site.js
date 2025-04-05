/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 ------------------------------------------------------------------------
 */

if (typeof(Solidres) === 'undefined') {
    var Solidres = {};
}

Solidres.context = 'frontend';

Solidres.setCurrency = function(id) {
    Solidres.jQuery.ajax({
        type: 'POST',
        url: window.location.pathname,
        data: 'option=com_solidres&format=json&task=currency.setId&id='+parseInt(id),
        success: function(msg) {
            location.reload();
        }
    });
};

function isAtLeastOnRoomTypeSelected() {
    var numberRoomTypeSelected = 0;
    Solidres.jQuery(".room-form").each(function () {
        if (Solidres.jQuery(this).children().length > 0) {
            numberRoomTypeSelected++;
            return;
        }
    });

    if (numberRoomTypeSelected > 0) {
        Solidres.jQuery('#sr-reservation-form-room button[type="submit"]').removeAttr('disabled');
    } else {
        Solidres.jQuery('#sr-reservation-form-room button[type="submit"]').attr('disabled', 'disabled');
    }
}

function isElVisible(el) {
	return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
}

document.addEventListener("DOMContentLoaded", function () {
	document.getElementById('coupon-code-check')?.addEventListener('click', function (e) {
		const couponCode = document.getElementById('coupon_code')?.value;
		const propertyId = document.querySelector('input[name="id"]')?.value;

		if (couponCode && propertyId) {

			Joomla.request({
				url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=coupon.validate&format=json',
				method: 'POST',
				data: `code=${couponCode}&pid=${propertyId}`,
				onSuccess: function (response) {
					const data = JSON.parse(response);
					document.getElementById('property-coupon-msg')?.remove();
					const holder = document.getElementById('property-coupon-form');
					holder.insertAdjacentHTML('beforeend', data.message);
					const applyBtn = document.getElementById('coupon-code-apply');

					if (!data.status) {
						applyBtn.disabled = true;
					} else {
						applyBtn.removeAttribute('disabled');
					}

					applyBtn.addEventListener('click', function () {
						const couponCode = document.getElementById('coupon_code').value;
						const propertyId = document.querySelector('input[name="id"]')?.value;

						if (couponCode && propertyId) {
							Joomla.request({
								method: 'POST',
								url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=coupon.apply&format=json',
								data: `code=${couponCode}&pid=${propertyId}`,
								onSuccess: function (response) {
									const data = JSON.parse(response);
									if (data.status) {
										location.reload();
									}
								}
							});
						}

					});
				},
			});
		}
	});

	document.getElementById('coupon-code-remove')?.addEventListener('click', function (e) {
		Joomla.request({
			url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=coupon.remove&format=json',
			method: 'POST',
			onSuccess: function (response) {
				const data = JSON.parse(response);
				if (data.status) {
					location.reload();
				} else {
					alert(Joomla.Text._('SR_CAN_NOT_REMOVE_COUPON'));
				}
			},
		});
	});

	let loadCalendarButtons = document.getElementsByClassName('load-calendar');

	for (let i = 0; i < loadCalendarButtons.length; i++) {
		loadCalendarButtons[i].addEventListener('click', function () {
			let id = this.dataset.roomtypeid;
			let targetHolder = document.getElementById(`availability-calendar-${id}`);
			this.innerHTML = '<i class="fa fa-calendar"></i> ' + Joomla.Text._('SR_PROCESSING');
			this.disabled = true;

			if (targetHolder.children.length === 0) {
				Joomla.request({
					url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationasset.getAvailabilityCalendar&id=' + id,
					method: 'POST',
					onSuccess: response => {
						this.removeAttribute('disabled');

						if (response.length > 0) {
							targetHolder.style.display = '';
							targetHolder.innerHTML = response;
							this.innerHTML = '<i class="fa fa-calendar"></i> ' + Joomla.Text._('SR_AVAILABILITY_CALENDAR_CLOSE');
						}
					}
				});
			} else {
				targetHolder.replaceChildren();
				targetHolder.style.display = 'none';
				this.innerHTML = '<i class="fa fa-calendar"></i> ' + Joomla.Text._('SR_AVAILABILITY_CALENDAR_VIEW');
				this.removeAttribute('disabled');
			}

			// Hide More Info section to make it easier to read in small screen
			const moreInfoSection = document.getElementById(`more_desc_${id}`);
			if (moreInfoSection && isElVisible(moreInfoSection)) {
				moreInfoSection.style.display = 'none';
				const toggleMoreDesc = moreInfoSection.parentElement.querySelector('.toggle_more_desc');
				toggleMoreDesc.innerHTML = '<i class="fa fa-eye-slash"></i> ' + Joomla.Text._('SR_SHOW_MORE_INFO');
			}
		});
	}

	const apartmentRatePlanPicker = document.getElementById('apartment-rateplan-picker');
	if (apartmentRatePlanPicker) {
		apartmentRatePlanPicker.addEventListener('change', function(e) {
			let tariffId = e.currentTarget.value;
			let roomTypeId = Joomla.getOptions('com_solidres.apartment').roomTypeId;

			if (tariffId) {
				const apartmentFormHolder = document.getElementById('apartment-form-holder');
				apartmentFormHolder.replaceChildren();

				const postData = new FormData();
				postData.append('Itemid', Joomla.getOptions('com_solidres.apartment').itemId);
				postData.append('id', Joomla.getOptions('com_solidres.apartment').propertyId);
				postData.append('roomtype_id', roomTypeId);
				postData.append('tariff_id', tariffId);
				postData.append('type', 1);
				Joomla.request({
					method: 'POST',
					data: postData,
					url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationasset.getCheckInOutForm',
					onSuccess: function(data) {
						Solidres.jQuery('#apartment-form-holder').html(data);
						let btn = apartmentFormHolder.querySelector('button[type="button"]');
						btn.setAttribute('type', 'submit');
						btn.removeAttribute('disabled');

						apartmentFormHolder.querySelector('.trigger_tariff_calculating').dispatchEvent(new Event('change', { bubbles: true }));
					}
				});
			}
		});

		apartmentRatePlanPicker.dispatchEvent(new Event('change'));
	}
});

Solidres.jQuery(function($) {
    if (document.getElementById('sr-reservation-form-room')) {
        $('#sr-reservation-form-room').validate();
    }

    if (document.getElementById("sr-checkavailability-form")) {
        $("#sr-checkavailability-form").validate();
    }

	$(".roomtype-quantity-selection").change(function() {
		isAtLeastOnRoomTypeSelected();
	});

    if (document.getElementById("sr-availability-form")) {
        $("#sr-availability-form").validate();
    }

	function loadRoomForm(self) {
		const rtid = self.data('rtid');
		const raid = self.data('raid');
		const tariffid = self.data('tariffid');
		const roomFormHolder = $('#solidres .room #room-form-' + rtid + '-' + tariffid);
		const token = Joomla.getOptions('csrf.token', '');

		$.ajax({
			type: 'POST',
			url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationasset.getRoomTypeForm',
			data: {
				rtid: rtid,
				raid: raid,
				tariffid: tariffid,
				quantity: (self.val() > 0 ? self.val() : 1)
			},
			headers: {
				'X-CSRF-Token': token
			},
			success: function (data) {
				self.parent().find('.processing').css({'display': 'none'});
				roomFormHolder.empty().show().html(data);
				$('.sr-reservation-form').validate();
				// trigger change at this time will update the child age form too, we don't want that!
				let updateChildAgeDropdown = false;
				// However for specific case when the ch_min > 0 and the form has no previous session data,
				// let generate the child age drop down
				let childQuantity = parseInt(roomFormHolder.find('.children_number').val());
				let childAgeDropDown = roomFormHolder.find('.child-age-details > ul').html().trim();
				if (childQuantity === 1 && childAgeDropDown.length === 0) {
					updateChildAgeDropdown = true;
				}
				roomFormHolder.find('.trigger_tariff_calculating').trigger('change', [updateChildAgeDropdown]);
				isAtLeastOnRoomTypeSelected();
				roomFormHolder.find('.extras_row_roomtypeform input:checkbox').trigger('change');
			}
		});
	}

    // In case the page is reloaded, we have to reload the previous submitted room type selection form
    $('.roomtype-quantity-selection').each(function() {
        var self = $(this);
        if ( self.val() > 0) {
            self.parent().find('.processing').css({'display': 'block'});
			$('#selected_tariff_' + self.data('rtid') + '_' + self.data('tariffid')).removeAttr("disabled");
            loadRoomForm(self);
        }
    });

    $('.roomtype-quantity-selection').change(function() {
        var self = $(this);
		var tariffid = self.data('tariffid');
		var rtid = self.data('rtid');
		var totalRoomsLeft = self.data('totalroomsleft');
        var isPrivate = self.data('isprivate');
		var currentQuantity = parseInt(self.val());
		var currentSelectedRoomTypeRooms = 0;
		var totalSelectableRooms = 0;
        if ( currentQuantity > 0) {
			self.parent().find('.processing').css({'display': 'block'});
			$('#selected_tariff_' + rtid + '_' + tariffid).removeAttr("disabled");
            loadRoomForm(self);
            // In a room type, either booking full room type or per room is allowed at the same time
            $('#tariff-holder-' + rtid + ' .exclusive-hidden').prop('disabled', true);
        } else {
            $('#room-form-' + rtid + '-' + tariffid).empty().hide();
			$('input[name="jform[selected_tariffs][' + rtid + ']"]').attr("disabled", "disabled");
			$('#selected_tariff_' + rtid + '_' + tariffid).attr("disabled", "disabled");
            isAtLeastOnRoomTypeSelected();
        }

		$('.quantity_' + rtid).each(function() {
			var s = $(this);
			var val = parseInt(s.val());
			if (val > 0) {
                currentSelectedRoomTypeRooms += val;
            }
		});

		totalSelectableRooms = totalRoomsLeft - currentSelectedRoomTypeRooms;

        $('.quantity_' + rtid).each(function () {
            var s = $(this);
            var val = parseInt(s.val());
            var from, to = 0;
            var qMin = s.data('qmin');
            var qMax = s.data('qmax');

            if (qMin > 0 && qMax > 0) {
                if (totalSelectableRooms >= qMax) {
                    from = qMin;
                    to = qMax;
                    enableOptionsRange(s, from, to);
                } else if (totalSelectableRooms < qMax && totalSelectableRooms >= qMin) {
                    from = qMin;

                    if (val > 0) {
                        if (val + totalSelectableRooms > qMax) {
                            to = qMax;
                        } else {
                            to = val + totalSelectableRooms;
                        }
                    } else {
                        to = totalSelectableRooms;
                    }

                    enableOptionsRange(s, from, to);
                } else {
                    if (val > 0) {
                        from = qMin;
                        if (val + totalSelectableRooms > qMax) {
                            to = qMax;
                        } else {
                            to = val + totalSelectableRooms;
                        }

                        enableOptionsRange(s, from, to);
                    } else {
                        if (totalSelectableRooms < qMin) {
                            from = val;
                        } else {
                            from = val + totalSelectableRooms;
                        }

                        disableOptions(s, from);
                    }
                }
            } else {
                from = val + totalSelectableRooms;
                disableOptions(s, from);
            }
        });

		var messageWrapper = $('#num_rooms_available_msg_' + rtid);
		if (totalSelectableRooms > 0 && totalSelectableRooms < totalRoomsLeft) {
            messageWrapper.empty().text(Joomla.Text._('SR_ONLY_' + totalSelectableRooms + '_LEFT' + (!isPrivate ? '_BED' : '')));
		} else if (totalSelectableRooms == 0) {
            messageWrapper.empty();
		} else {
            messageWrapper.empty().text($('#num_rooms_available_msg_' + rtid).data('original-text'));
		}
    });

    $('.roomtype-reserve').click(function() {
        var self = $(this);
        var tariffid = self.data('tariffid');
        var rtid = self.data('rtid');
        if ( $("#room-form-" + rtid + "-" + tariffid).children().length == 0) {
            $('#room_type_row_' + rtid + ' .room-form').empty().hide();
            self.siblings('.processing').css({'display': 'block'});
            $('#selected_tariff_' + rtid + '_' + tariffid).removeAttr("disabled");
            loadRoomForm(self);
        } else {
            $('#room-form-' + rtid + '-' + tariffid).empty().hide();
            $('input[name="jform[selected_tariffs][' + rtid + ']"]').attr("disabled", "disabled");
            $('#selected_tariff_' + rtid + '_' + tariffid).attr("disabled", "disabled");
        }
        isAtLeastOnRoomTypeSelected();
    });

    function enableOptionsRange(selectEl, from, to) {
        $('option', selectEl).each(function() {
            var val = parseInt($(this).attr('value'));
            if (val >= from && val <= to) {
                $(this).removeAttr('disabled');
            } else {
                $(this).attr('disabled', 'disabled');
            }

            if (val == 0) { // The placeholder should be selectable
                $(this).removeAttr('disabled');
            }
        });
    }
	
    $('.guestinfo').on('click', 'input:checkbox', function() {
        var self = $(this);
        if (self.is(':checked')) {
            $('.' + self.data('target') ).removeAttr('disabled');
        } else {
            $('.' + self.data('target') ).attr('disabled', 'disabled');
        }
    });

    $('.room-form').on('click', 'input:checkbox', function() {
        var self = $(this);
        if (self.is(':checked')) {
            $('.' + self.data('target') ).removeAttr('disabled');
        } else {
            $('.' + self.data('target') ).attr('disabled', 'disabled');
        }
    });

	function clearExistingCheckInOutForms() {
		let checkInOutForms = document.getElementsByClassName('checkinoutform');

		for (let i = 0; i < checkInOutForms.length; i++) {
			checkInOutForms[i].replaceChildren();
		}
	}

	let checkInOutFormTriggers = document.getElementsByClassName('trigger_checkinoutform');

	for (let i = 0; i < checkInOutFormTriggers.length; i++) {
		checkInOutFormTriggers[i].addEventListener('click', function () {
			let tariffId = this.dataset.tariffid;
			let roomtypeId = this.dataset.roomtypeid;
			let oldLabel = this.textContent;

			if (tariffId != '') {
				clearExistingCheckInOutForms();

				this.textContent = Joomla.Text._('SR_PROCESSING');

				const postData = new FormData();
				postData.append('Itemid', this.dataset.itemid);
				postData.append('id', this.dataset.assetid);
				postData.append('roomtype_id', roomtypeId);
				postData.append('tariff_id', tariffId);

				Joomla.request({
					url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=reservationasset.getCheckInOutForm',
					method: 'POST',
					data: postData,
					onSuccess: response => {
						clearExistingCheckInOutForms();

						$(`#checkinoutform-${roomtypeId}-${tariffId}`).show().empty().html(response);
						this.textContent = oldLabel;
					}
				});
			}
		});
	}

	$('#solidres').on('click', '.searchbtn', function () {
		const tariffid = $(this).data('tariffid');
		const roomtypeid = $(this).data('roomtypeid');
		const formComponent = $('#sr-checkavailability-form-component');
		const ratePlanWrapperId = '#tariff-box-' + roomtypeid + '-' + tariffid
		const ratePlanWrapper = $(ratePlanWrapperId);

		if (Joomla.getOptions('com_solidres.general').AutoScroll === 1) {
			let target = ratePlanWrapperId;
			if (Joomla.getOptions('com_solidres.general').AutoScrollTariff === 0) {
				target = '#srt_' + roomtypeid;
			}
			formComponent.attr('action', formComponent.attr('action') + target);
		}
		formComponent.find('input[name=checkin]').val(ratePlanWrapper.find('input[name="checkin"]').val());
		formComponent.find('input[name=checkout]').val(ratePlanWrapper.find('input[name="checkout"]').val());
		formComponent.find('input[name=ts]').val($('input[name=fts]').val());

		if (Joomla.getOptions('com_solidres.general').EnableUnoccupiedPricing === 1) {
			let occupiedDatesCheckboxes = document.querySelectorAll(ratePlanWrapperId + ' input[name="occupied_dates[]"]:checked');

			for (let i = 0; i < occupiedDatesCheckboxes.length; i++) {
				$('<input>', {
					type: 'hidden',
					name: 'occupied_dates[]',
					value: occupiedDatesCheckboxes[i].value,
				}).appendTo(formComponent);
			}
		}

		formComponent.submit();
	});

	$('.toggle_more_desc').click(function() {
		var self = $(this);
		$('#more_desc_' + self.data('target')).toggle();
		if ($('#more_desc_' + self.data('target')).is(':visible')) {
			self.empty().html('<i class="fa fa-eye"></i> ' + Joomla.Text._('SR_HIDE_MORE_INFO'));
		} else {
			self.empty().html('<i class="fa fa-eye-slash"></i> ' + Joomla.Text._('SR_SHOW_MORE_INFO'));
		}

	});

	$('#solidres').on('click', '.checkin_roomtype', function() {
		if (!$(this).hasClass("disabledCalendar")) {
			$('.checkin_datepicker_inline').slideToggle();
		}
	});

	$('#solidres').on('click', '.checkout_roomtype', function() {
		if (!$(this).hasClass("disabledCalendar")) {
			$('.checkout_datepicker_inline').slideToggle();
		}
	});

	$('#solidres').on('click', '#register_an_account_form', function() {
		var self = $(this);
		self.parents('form').find('button[data-step="guestinfo"]').prop('disabled', false);

		if (self.is(':checked')) {
			$('.' + self.attr('id') ).show();
		} else {
			$('.' + self.attr('id') ).hide();
		}
	});

	$('.toggle-tariffs').click(function() {
		var self = $(this);
		var roomtypeid = self.data('roomtypeid');
		var target = $('#tariff-holder-' + roomtypeid);

		// Hide More Info and Availability Calendar sections
        if ($('#more_desc_' + roomtypeid).is(':visible')) {
            $('#more_desc_' + roomtypeid).hide();
            self.parent().find('.toggle_more_desc').empty().html('<i class="fa fa-eye-slash"></i> ' + Joomla.Text._('SR_SHOW_MORE_INFO'));
        }

        if ($('#availability-calendar-' + roomtypeid).is(':visible')) {
            $('#availability-calendar-' + roomtypeid).empty().hide();
            self.parent().find('.load-calendar').empty().html('<i class="fa fa-calendar"></i> ' + Joomla.Text._('SR_AVAILABILITY_CALENDAR_VIEW'));
        }

		target.toggle();
		if (target.is(":hidden")) {
			self.html('<i class="fa fa-expand"></i> ' + Joomla.Text._('SR_SHOW_TARIFFS'));
		} else {
			self.html('<i class="fa fa-compress"></i> ' + Joomla.Text._('SR_HIDE_TARIFFS'));
		}
	});

	var hash = location.hash;

	if (hash.indexOf('tariff-box') > -1) {
		var $el = $(hash),
			x = 1500,
			originalColor = $el.css("backgroundColor"),
			targetColor = $el.data("targetcolor");

		$el.css("backgroundColor", "#" + targetColor);
		setTimeout(function(){
			$el.css("backgroundColor", originalColor);
		}, x);
	}

    $('html').click(function(e) {
        if (!$(e.target).hasClass('datefield')
            &&
            !$(e.target).parent().hasClass('datefield')
            &&
            $(e.target).closest('div.ui-datepicker').length == 0
            &&
            $(e.target).closest('a.ui-datepicker-prev').length == 0
            &&
            $(e.target).closest('a.ui-datepicker-next').length == 0
            &&
            $(e.target).closest('div.ui-datepicker-buttonpane').length == 0
        ) {
            $(".datepicker_inline").hide();
        }
    });

    $("#show-other-roomtypes").click(function() {
        $('.room_type_row').each(function() {
            if (!$(this).hasClass('prioritizing')) {
                $(this).toggle();
            }
        });
        $('.prioritizing-roomtype-notice').hide();
    });

    $('.guestinfo').on('click', 'button[data-step="guestinfo"]', function (e) {
        var pc = $('#privacy-consent');
        var btn = $(this);

        if (pc.length
            && $('#register_an_account_form').is(':checked')
            && !pc.is(':checked')
        ) {
            e.preventDefault();
            e.stopPropagation();
            pc.parent('label').addClass('error');
            $('html, body').animate({
               scrollTop:  $('#register_an_account_form').offset().top
            }, 400);
            btn.prop('disabled', true);

            return false;
        }

        btn.prop('disabled', false);
        pc.parent('label').removeClass('error');
    });

    $('.guestinfo').on('change', '#privacy-consent', function () {
        $(this).parents('form').find('button[data-step="guestinfo"]').prop('disabled', !$(this).is(':checked'));
    });

    if ($('.rooms-rates-summary.module').length) {
        var summaryWrapper = $('.rooms-rates-summary.module');
        var solidresWrapper = $('#solidres');
        var scrollData = {};
        scrollData.pos = summaryWrapper.offset().top;
        scrollData.pos_bottom = 1000;
        if (solidresWrapper) {
            scrollData.pos_bottom = solidresWrapper.outerHeight();
        }

        if (summarySidebarId) {
            var summaryWrapperParent = summaryWrapper.parents(summarySidebarId);
            scrollData.width = summaryWrapperParent.width() - parseInt(summaryWrapper.css('padding-left')) - parseInt((summaryWrapper.css('padding-right')));
        } else {
            var summaryWrapperParent = summaryWrapper.parent();
            scrollData.width = summaryWrapperParent.width() - parseInt(summaryWrapper.css('padding-left')) - parseInt((summaryWrapper.css('padding-right')));
        }

        function stickyScrollHandler() {
            if ($(window).scrollTop() >= scrollData.pos && $(window).scrollTop() < scrollData.pos_bottom) {
                summaryWrapperParent.addClass('rooms-rates-summary-sticky');
                summaryWrapperParent.css({'width': scrollData.width});
            } else {
                summaryWrapperParent.removeClass("rooms-rates-summary-sticky");
            }
        }

        $(window).scroll(scrollData, stickyScrollHandler);

        window.addEventListener('resize', function(event){
            var summaryWrapper = $('.rooms-rates-summary.module');
            var scrollData = {};
            scrollData.pos = summaryWrapper.offset().top;
            scrollData.width = summaryWrapperParent.width() - parseInt(summaryWrapper.css('padding-left')) - parseInt((summaryWrapper.css('padding-right')));
            $(window).off('scroll', stickyScrollHandler);
            $(window).scroll(scrollData, stickyScrollHandler);
        });
    }

    $('.booking-summary a.open-overlay').click(function() {
        summaryWrapperParent.removeClass('rooms-rates-summary-sticky');
        summaryWrapper.addClass('sr-overlay');
        $('.sr-close-overlay').show();
    });

    if ($('.sr-apartment-form').length) {
        var bookFormWrapper = $('.sr-apartment-form');
        var solidresWrapper = $('#solidres');
        var scrollData = {};
        var galleryWrapper = $('.sr-gallery');
        scrollData.pos = bookFormWrapper.offset().top;
        scrollData.pos_bottom = 1000;
        if (solidresWrapper) {
            scrollData.pos_bottom = solidresWrapper.outerHeight();
        }

        if (galleryWrapper.length) {
            scrollData.pos += galleryWrapper.offset().top;
        }

        var bookFormWrapperParent = bookFormWrapper.parents('.sr-apartment-aside');
        scrollData.width = bookFormWrapperParent.width() - parseInt(bookFormWrapper.css('padding-left')) - parseInt((bookFormWrapper.css('padding-right')));

        function stickyScrollHandler() {
            if ($(window).scrollTop() >= scrollData.pos && $(window).scrollTop() < scrollData.pos_bottom) {
                bookFormWrapperParent.addClass('rooms-rates-summary-sticky');
                bookFormWrapperParent.css({'width': scrollData.width});
            } else {
                bookFormWrapperParent.removeClass("rooms-rates-summary-sticky");
            }
        }

        $(window).scroll(scrollData, stickyScrollHandler);

        window.addEventListener('resize', function(event){
            var bookFormWrapper = $('.sr-apartment-form');
            var scrollData = {};
            scrollData.pos = bookFormWrapper.offset().top;
            scrollData.width = bookFormWrapperParent.width() - parseInt(bookFormWrapper.css('padding-left')) - parseInt((bookFormWrapper.css('padding-right')));
            $(window).off('scroll', stickyScrollHandler);
            $(window).scroll(scrollData, stickyScrollHandler);
        });
    }

    $('.booking-summary a.open-overlay-apartment').click(function() {
        bookFormWrapperParent.removeClass('rooms-rates-summary-sticky');
        bookFormWrapper.addClass('sr-overlay');
        $('.sr-close-overlay').show();
        $('.sr-apartment-form h3').show();
    });

    $(document).on('click', '.sr-close-overlay', function () {
        if (summaryWrapper) {
            summaryWrapper.removeClass('sr-overlay');
            $(this).hide();
        }

        if (bookFormWrapper) {
            bookFormWrapper.removeClass('sr-overlay');
            $(this).hide();
        }
    });

    $(document).on('click', '.summary_edit_room', function() {
        var target = $(this).data('target');

        $('.reservation-navigate-back').trigger('click', ['room']);

        if ($('#tariff-box-' + target).length > 0) {
            $('html, body').animate({
                scrollTop: $('#tariff-box-' + target).offset().top
            }, 700);
        }
    });
});
