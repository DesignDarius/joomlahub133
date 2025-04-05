Solidres.jQuery(function ($) {
    $(document).on('click', '[data-wishlist-id]', function (e) {
        e.preventDefault();
        var
            el = $(this),
            objectId = el.data('wishlistId'),
            itemId = el.data('itemid'),
            scope = el.data('scope'),
            url = Joomla.getOptions('system.paths').base + '/index.php?option=com_ajax&format=jsonRaw&plugin=solidres&group=extension';
        if (el.data('wishlistPage')) {
            el.prev('.ajax-loader').show();
            el.find('i').hide();
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                data: 'type=remove&objectId=' + objectId + '&scope=' + scope,
                success: function (response) {
                    el.parents('wish-list-row:eq(0)').fadeOut('slow', function () {
                        $(this).remove();
                    });
                    !response && location.reload();
                    el.find('i').show().removeClass('added');
                }
            });
        } else {

            if (el.hasClass('wishlist-added')) {
                return;
            }

            el.find('.ajax-loader').show();
            el.find('i').hide();
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                data: 'type=add&objectId=' + objectId + '&scope=' + scope + '&Itemid=' + itemId,
                success: function (response) {
                    el.find('.ajax-loader').hide();
                    el.addClass('wishlist-added');
                    el.find('i').show().addClass('added');
                    const wishListPopover = new bootstrap.Popover(el, {
                        title: '<strong class="text-success">' + Joomla.Text._('SR_ADD_TO_WISH_LIST_SUCCESS') + '</strong>',
                        placement: 'auto',
                        trigger: 'click',
                        html: true,
                        content: '<strong class="text-info">'
                            + response.history.name + '</strong> '
                            + Joomla.Text._('SR_WISH_LIST_WAS_ADDED')
                            + ' <a href="' + response.wishListUrl + '">' + Joomla.Text._('SR_GO_TO_WISH_LIST') + '</a>'
                    });
                    wishListPopover.show();
                }
            });
        }
    });
});
