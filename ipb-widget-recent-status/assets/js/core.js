jQuery(document).ready(function($) {

    $(document)
        .on('click', '.iwrs-widget .karma.minus', function () {
            ratingUp(this, -1);
        })
        .on('click', '.iwrs-widget .karma.plus', function () {
            ratingUp(this, 1);
        });

    // Изменение рейтинга игры
    function ratingUp(button, rating)
    {
        var item = $(button).parents('.karma_wrap'),
            itemId = $(item).attr('data-id');


        if ($(item).is('.disabled')) {
            return false;
        }

        $.ajax({
            url: window.ajax.url,
            dataType: 'json',
            type: 'POST',
            data: 'action=iwrs-rating-up&itemId=' + itemId + '&rating=' + rating,
            beforeSend: function() {
                $(item).addClass('disabled');
            },
            success: function(result) {

                var itemParent = $(item).parent();
                $(item).addClass('disabled');

                if (result.rating != undefined) {
                    $(item).remove();
                    itemParent.append(result.rating );
                }
            }


        });
    }

});