jQuery(function($) {
    $(document).ready(function() {

        if ($('.comment-form-comment textarea').length > 0 && $('.comment-form-comment textarea').val().length > 0) {
            $('#comments_form label[for=comment]').hide();
        }
        $('#comments_form textarea')
            .focus(function() {
                $('#comments_form label[for=comment]').hide();
            })
            .focusout(function() {
                if ($('#comments_form textarea').val().trim().length == 0) {
                    $('#comments_form label[for=comment]').show();
                }
            });

        $('form#commentform input[type=submit]').removeAttr('disabled');
        $('form#commentform .css_load').hide();

        // Отправка комментария
        $(document).on('submit', 'form#commentform', function() {

            var comment = $(this).find('textarea').val().trim().replace(/[?]{1}/g, '\\?'),
                postId = $(this).find('[name=comment_post_ID]').val().trim(),
                parent = parseInt( $(this).find('[name=parent]').val() );

            if (comment.length > 0) {
                $.ajax({
                    type: 'POST',
                    url: '',
                    dataType: 'json',
                    data: 'action=wpIpbSendComment&comment=' + comment + '&comment_post_ID=' + postId + '&parent=' + parent,
                    beforeSend: function() {
                        $('form#commentform .css_load').show();
                        $('form#commentform input[type=submit]').attr('disabled', 'disabled');
                    },
                    success: function(result) {

                        $('form#commentform .css_load').hide();
                        $('form#commentform input[type=submit]').removeAttr('disabled');

                        if (result.error != undefined) {
                            alert('При отправке комментария произошла ошибка, повторите попытку позже');
                        } else {

                            if (!parent) {
                                $('form#commentform textarea').val('');
                                $('#comments_list').prepend(result.commentBody);
                                $('#comments_list .comment_item:eq(0)').hide();
                                $('#comments_list .comment_item:eq(0)').fadeIn('slow');
                            } else {
                                var parentItem = $('#comments_list .comment_item[data-id=' + parent +']');
                                if (parentItem.parents('.comment_item').length > 0) parentItem = parentItem.parents('.comment_item');

                                var childrensWrap = parentItem.find('.comments-childrens:last');
                                childrensWrap.prepend(result.commentBody);

                                childrensWrap.find('#comments_form').remove();
                                $('#comments_form_wrap #comments_form').show();
                                $('form#commentform textarea').val('');

                                childrensWrap.find('.comment_item:eq(0)').hide();
                                childrensWrap.find('.comment_item:eq(0)').fadeIn('slow');

                                parentItem.find('.comment_item_wrap:eq(0) .tool.delete:eq(0)').addClass('disabled');
                            }

                            $('#comments_list .tool.reply').show();

                            setTimeout(function() {
                                var commentId = $(result.commentBody).attr('data-id');
                                $('[data-id='+commentId+']').find('.tool.edit, .tool.delete').hide();
                            }, 180000);
                        }

                    }
                });
            }

            return false;
        });

        $(document)
            .on('click', '.tool.reply', function() {
                $('#comments_list .tool.reply').show();
                $(this).hide();

                var replyAuthor =  $('#comments_list #comments_form input[name=parent-author]').val(),
                    oldComment = $('#comments_list #comments_form textarea').val();

                $('#comments_form label[for=comment]').hide();
                var commentItem = $(this).closest('.comment_item'),
                    commentId = commentItem.data('id'),
                    commentAuthor = commentItem.data('author');

                var commentForm = $('#comments_form_wrap').html();
                $('#comments_form_wrap #comments_form').hide();
                $('#comments_list #comments_form').remove();

                if (commentItem.parents('.comment_item').length > 0) { // Если отвечаем на дочерний комментарий

                    commentItem.parents('.comment_item').find('.comments-childrens:last').after('<div class="comments-childrens">' + commentForm + '</div>');

                } else { // Если отвечаем на родительский комментарий
                    if (commentItem.find('.comments-childrens').length > 0) {
                        commentItem.find('.comments-childrens:last').after('<div class="comments-childrens">' + commentForm + '</div>');
                    } else {
                        commentItem.find('.comment_item_wrap:eq(0)').after('<div class="comments-childrens">' + commentForm + '</div>');
                    }
                }


                $('#comments_list #comments_form').show();
                $('#comments_list #comments_form').find('input[name=parent]').val(commentId);
                $('#comments_list #comments_form').find('input[name=parent-author]').val(commentAuthor);
                $('#comments_list #comments_form').prepend('<span class="close" title="Закрыть">&#10006;</span>');

                var comment = '';
                if (oldComment) comment = oldComment.replace(new RegExp("^" + replyAuthor + ', ',''), '');
                comment = commentAuthor + ', ' + comment;

                $('form#commentform textarea').focus().val(comment);

            })
            .on('click', '#comments_list #comments_form .close', function() {
                $('#comments_list #comments_form').remove();
                $('#comments_form_wrap #comments_form').show();
                $('#comments_list .tool.reply').show();
                $('#comments_form_wrap #comments_form textarea').val('');
            })
            .on('click', '.karma.minus', function() {
                ratingUp(this, -1);
            })
            .on('click', '.karma.plus', function() {
                ratingUp(this, 1);
            })
            .on('click', '.tool.delete', function() {
                deleteRestore(this, 'delete');
            })
            .on('click', '.restore', function() {
                deleteRestore(this, 'restore');
            })
            .on('click', '.tool.edit', function() {

                var commentItem =  $(this).closest('.comment_item');
                $(commentItem).find('.comment_text:eq(0), .manage:eq(0)').hide();
                var commentText = $(commentItem).find('.comment_text:eq(0)').html().trim();
                commentText = commentText.replace(/<\s*\/?p>/mg,"").replace(/<br\s*\/?>/mg,"\n").replace(/\n\n/mg, "\n");

                var editForm = '<div class="comment_edit_form">' +
                    '<textarea rows="4" name="comment" maxlength="1500" required>' +
                    commentText  +
                    '</textarea>' +
                    '<p class="form-submit"><input type="submit" value="Сохранить комментарий" id="save_comment" name="save_comment">' +
                    '<input type="submit" value="Отмена" class="edit_cancel" id="edit_cancel" name="edit_cancel">' +
                    '</p>' +
                    '</div>';

                $(commentItem).find('.comment_right:eq(0)').prepend(editForm);

                // Лимит символов
                updateNumChars();
                $(document).on('keyup', '.comment_edit_form textarea', function() {
                    updateNumChars();
                });

            })
            .on('click', '[name=save_comment]', 'click', function() {
                var commentItem =  $(this).closest('.comment_item');
                var commentId = $(commentItem).attr('data-id');
                var commentText = $(commentItem).find('textarea:eq(0)').val().trim();

                if (commentText.length > 0) {
                    $(commentItem).find('.comment_text:eq(0)').html(nl2br(commentText));
                    $(commentItem).find('.comment_text:eq(0), .manage:eq(0)').show();
                    $(commentItem).find('.comment_edit_form:eq(0)').remove();
                }

                $.ajax({
                    url: '',
                    dataType: 'json',
                    type: 'POST',
                    data: 'action=commentUpdate&commentText=' + commentText.replace(/[?]{1}/g, '\\?') + '&commentId=' + commentId,
                    beforeSend: function() {},
                    success: function(result) {}
                });

            })
            .on('click', '[name=edit_cancel]', 'click', function() {
                var commentItem =  $(this).closest('.comment_item');
                $(commentItem).find('.comment_text, .manage').show();
                $(commentItem).find('.comment_edit_form').remove();
            })
            .on('click', '.comment_hide_button button', function() {
                var commentItem =  $(this).closest('.comment_item');
                $(commentItem).css('opacity', '1');
                $(commentItem).find('.comment_text').show();
                $(this).hide();
            });

        // Изменение рейтинга комментария
        function ratingUp(button, rating) {

            if ($(button).attr('disabled') == 'disabled') {
                return false;
            }

            var commentItem =  $(button).closest('.comment_item');
            var commentId = $(commentItem).attr('data-id');

            $(commentItem).find('.karma.tool.minus:eq(0), .karma.tool.plus:eq(0)').attr('disabled', 'disabled');
            $(commentItem).find('.karma.tool.minus:eq(0), .karma.tool.plus:eq(0)').addClass('disabled');

            $.ajax({
                url: '',
                dataType: 'json',
                type: 'POST',
                data: 'action=ratingUp&commentId=' + commentId + '&rating=' + rating,
                beforeSend: function() {},
                success: function(result) {

                    if (result.error != undefined) {
                        if (result.error == 3) {
                            alert('Пишите больше комментариев и вы сможете ставить оценки.');
                        }
                    } else if (result.rating != undefined) {
                        $(commentItem).find('.karma:eq(0) .score').remove();
                        $(commentItem).find('.karma_wrap:eq(0)').prepend(result.rating);
                    }

                }
            });
        }

        // Удаление, восстановление комментариев
        function deleteRestore(button, action)
        {
            if ($(button).is('.disabled')) return;

            var commentItem =  $(button).closest('.comment_item');
            var commentId = $(commentItem).attr('data-id');

            if (action == 'delete') {

                /*
                if (commentItem.parents('.comment_item').length > 0) {
                    if ( commentItem.parents('.comment_item').find('.comments-childrens .comment_item .comment_text').length > 1) {
                        commentItem.parents('.comment_item').find('.tool.delete').addClass('disabled');
                    } else {
                        commentItem.parents('.comment_item').find('.tool.delete').removeClass('disabled');
                    }
                }*/

                $(commentItem).find('.comment_item_wrap').hide();
                commentItem.children('.comment_item_wrap')
                    .after('<div class="restore">Восстановить комментарий</div>');

            } else if (action == 'restore') {
                $(commentItem).find('.restore').remove();
                $(commentItem).find('.comment_item_wrap').show();
            }

            $.ajax({
                url: '',
                dataType: 'json',
                type: 'POST',
                data: 'action=deleteRestore&commentId=' + commentId + '&commentAction=' + action,
                beforeSend: function() {},
                success: function(result) {}
            });

        }

        // Подписка на новые комментарии (плагин subscribe-to-comments)
        $(document).on('change', '#subscription-form input', function() {
            var checked = $(this).is(':checked'),
                postId = $('#commentform input[name=comment_post_ID]').val();

            $.ajax({
                url: '',
                type: 'POST',
                data: 'solo-comment-subscribe=solo-comment-subscribe&postid=' + postId + '&checked=' + checked,
                beforeSend: function() {},
                success: function(result) {}
            });

        });


    });
});

function nl2br(str) {
    return str.replace(/([^>])\n/g, '$1\n<br/>');
}
function br2nl(str) {
    return str.replace(/<br\s*\/?>/mg,"\n");
}

function updateNumChars() {
    if (jQuery('.comment-form-comment textarea').length > 0) {
        var chars  = jQuery('.comment-form-comment textarea').val().length;
        jQuery('.comment-form-comment textarea').parents('form').find('.limit-chars .num').text(chars);
    }

    if (jQuery('.comment_edit_form textarea').length > 0) {
        var chars  = jQuery('.comment_edit_form textarea').val().length;
        jQuery('.comment_edit_form').find('.limit-chars .num').text(chars);
    }
}
