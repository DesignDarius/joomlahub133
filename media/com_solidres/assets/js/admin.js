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

Solidres.context = 'backend';

Solidres.jQuery(function ($) {

    $('.ui-datepicker').addClass('notranslate');

    var changeTaxSelectStatus = function () {
        if ($(".asset_tax_select").length) {
            if ($(".asset_tax_select").val() > 0) {
                $('.tax_select').removeAttr('disabled');
            } else {
                $('.tax_select').attr('disabled', 'disabled');
            }
        }

        if ($(".country_select").length) {
            if ($(".country_select").val() > 0) {
                $('.tax_select').removeAttr('disabled');
            } else {
                $('.tax_select').attr('disabled', 'disabled');
            }
        }
    };

    changeTaxSelectStatus();

    $(".asset_tax_select").change(function () {
        $.ajax({
            url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=taxes.find&id=' + $(this).val(),
            success: function (html) {
                $('.tax_select').empty().html(html);
            }
        });

        changeTaxSelectStatus();
    });

    $(".country_select").change(function () {
        $.ajax({
            url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&format=json&task=states.find&id=' + $(this).val(),
            success: function (html) {
                $('.state_select').empty().html(html);
            }
        });

        $.ajax({
            url: Joomla.getOptions('system.paths').base + '/index.php?option=com_solidres&task=taxes.find&country_id=' + $(this).val(),
            success: function (html) {
                $('.tax_select').empty().html(html);
            }
        });

        changeTaxSelectStatus();
    });

    var nav = $('#sr_panel_left');

    $('#sr_side_navigation.disabled li>a').on('click', function (e) {
        e.preventDefault();
    });

    nav.on('click', '.sr_toggle .sr_indicator', function (e) {
        e.preventDefault();
        $(this).siblings('ul').slideToggle('fast');
    });

    nav.find('.sr_toggle').hover(
        function () {
            $(this).addClass('hover').siblings('.active').addClass('not');
        },
        function () {
            $(this).removeClass('hover').siblings('.active').removeClass('not');
        }
    );

    function toggleSideNavVert(nav) {
        var toggleClass = 'col-md-10';
        if (nav.hasClass('showIcon')) {
            $('#sr_panel_right.sr_list_view').removeClass(toggleClass).addClass('showIcon');
            $('#sr-toggle i').removeClass().addClass('fa fa-chevron-circle-right')
        } else {
            $('#sr_panel_right.sr_list_view').addClass(toggleClass).removeClass('showIcon');
            $('#sr-toggle i').removeClass().addClass('fa fa-chevron-circle-left')
        }
    }

    function toggleSideNavHorz(nav) {
        if ($(window).width() <= 767) {
            nav.find('.sr_toggle ul').hide();
        } else {
            if (!nav.hasClass('showIcon')) {
                nav.find('.sr_toggle ul').show();
            }
        }
    }

    $('#sr-toggle').on('click', function (e) {
        e.preventDefault();
        nav.toggleClass('showIcon');
        localStorage.setItem('sr_toggle_vert', nav.hasClass('showIcon') ? 'true' : 'false');
        toggleSideNavVert(nav);
    });

    if (localStorage.getItem('sr_toggle_vert') === 'true') {
        nav.addClass('showIcon');
    }

    toggleSideNavVert(nav);

    toggleSideNavHorz(nav);

    $(window).on('resize', function () {
        toggleSideNavHorz(nav);
    });
});
