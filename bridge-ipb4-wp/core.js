jQuery(document).ready(function($) {

    $('#elRegisterButton').hover(function() {
        var width = $(this).padding;
        $(this).css('width', width);
        $(this).text('Go Go Go!');
    }, function () {
        $(this).text('Регистрация');
    });

});