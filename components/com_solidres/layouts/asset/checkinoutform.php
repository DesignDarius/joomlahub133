<?php
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

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/checkinoutform.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.1
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

extract($displayData);

HTMLHelper::_('script', 'com_solidres/assets/datePicker/localization/jquery.ui.datepicker-' . Factory::getLanguage()->getTag() . '.js', ['version' => SRVersion::getHashVersion(), 'relative' => true]);

if (0 == $type)
{
	echo SRLayoutHelper::getInstance()->render(
		'asset.checkinoutform' . ((defined('SR_LAYOUT_STYLE') && SR_LAYOUT_STYLE != '') ? '_' . SR_LAYOUT_STYLE : '_style1'),
		$displayData
	);
}
else
{
	echo SRLayoutHelper::getInstance()->render(
		'asset.checkinoutform_apartment',
		$displayData
	);
}

$dpMinCheckoutDate = $minDaysBookInAdvance + $minLengthOfStay;
$inlineScript = <<<JS
    Solidres.jQuery(function ($) {
        const dateFormat = Joomla.getOptions('com_solidres.general').DateFormatJS;
        const firstDay = Joomla.getOptions('com_solidres.general').WeekStartDay;
        const minLengthOfStay = Joomla.getOptions('com_solidres.general').MinLengthOfStay;
		const dpMinCheckoutDate = {$dpMinCheckoutDate};
		const dpMinCheckinDate = {$minDaysBookInAdvance};
		const dpMaxCheckinDate = {$maxDaysBookInAdvance};
        const checkout_roomtype = $('.checkout_datepicker_inline').datepicker({
            minDate : '+' + dpMinCheckoutDate,
            numberOfMonths : Joomla.getOptions('com_solidres.general').DatePickerMonthNum,
            showButtonPanel : true,
            dateFormat : dateFormat,
            firstDay: firstDay,
            onSelect: function() {
                $('.sr-datepickers input[name="checkout"]').val($.datepicker.formatDate('yy-mm-dd', $(this).datepicker('getDate')));
                $('.checkout_roomtype').removeAttr('readonly').val($.datepicker.formatDate(dateFormat, $(this).datepicker('getDate'))).attr('readonly', 'readonly');
                $('.checkout_datepicker_inline').slideToggle();
                $('.checkin_roomtype').removeClass('disabledCalendar');
                changeCheckButtonState();
                if ($('.apartment-form-holder').length) {
                    $('.apartment-form-holder').find('.trigger_tariff_calculating').eq(0).trigger('change');
                }
            }
        });
        const checkin_roomtype = $('.checkin_datepicker_inline').datepicker({
            minDate : '+' + dpMinCheckinDate + 'd',
            maxDate: dpMaxCheckinDate > 0 ? '+' + dpMaxCheckinDate : null,
            numberOfMonths : Joomla.getOptions("com_solidres.general").DatePickerMonthNum,
            showButtonPanel : true,
            dateFormat : dateFormat,
            firstDay: firstDay,
            onSelect : function() {
                var currentSelectedDate = $(this).datepicker('getDate');
                var checkoutMinDate = $(this).datepicker('getDate', '+1d');
                checkoutMinDate.setDate(checkoutMinDate.getDate() + minLengthOfStay);
                checkout_roomtype.datepicker( 'option', 'minDate', checkoutMinDate );
                checkout_roomtype.datepicker( 'setDate', checkoutMinDate);

                $('.sr-datepickers input[name="checkin"]').val($.datepicker.formatDate('yy-mm-dd', currentSelectedDate));
                $('.sr-datepickers input[name="checkout"]').val($.datepicker.formatDate('yy-mm-dd', checkoutMinDate));

                $('.checkin_roomtype').removeAttr('readonly').val($.datepicker.formatDate(dateFormat, currentSelectedDate)).attr('readonly', 'readonly');
                $('.checkout_roomtype').removeAttr('readonly').val($.datepicker.formatDate(dateFormat, checkoutMinDate)).attr('readonly', 'readonly');
                $('.checkin_datepicker_inline').slideToggle();
                $('.checkout_roomtype').removeClass('disabledCalendar');
                changeCheckButtonState();
                if ($('.apartment-form-holder').length) {
                    $('.apartment-form-holder').find('.trigger_tariff_calculating').eq(0).trigger('change');
                }
            }
        });
        $('.ui-datepicker').addClass('notranslate');
    });
JS;

echo "<script>$inlineScript</script>";
