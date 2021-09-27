<?php
/*
Plugin Name: WP-IPB comments Top Comment
Version: 1.1.1
Description: Displays the most popular post comment posted using the plugin wp_ipb_comments
Author: Khovl
License: GNU General Public License v3
*/

$ratingMin = 1; // Minimum comment rating at which it will be displayed
$commentTextChars = 55; // number of characters of comment text to display

/**
 * Выводит самый популярный комментарий
 */
function wicShowTopComment()
{
    $postId = get_the_ID();

    if ($commentData = wicGetTopComment($postId)) {
        $commentPrint = '<span class="top-comment">
        <span class="time">' . $commentData['time'] . '</span>
        <span class="author">' . $commentData['author'] . '</span>
        <span class="photo"><img src="' . $commentData['photo'] . '" /></span>
        <span class="text"><a href="' . get_permalink($postId) . '#comment-' . $commentData['id'] . '">' . $commentData['text'] . '</a></span>
        </span>
        ';

        echo $commentPrint;
    }
}

/**
 * Возвращает самый популярный комментарий
 * @param int $postId ID записи
 * @return null|array
 */
function wicGetTopComment($postId)
{
    global $ratingMin, $wpdb, $commentTextChars;

    $commentData = array();

    // Проверяем количество комментариев у записи
    $query = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'comments
        WHERE comment_post_ID = ' . $postId . ' AND comment_type = "wp_ipb" AND comment_approved = 1';
    if ($wpdb->get_var($query) < 2) {
        return null;
    }

 // Получаем комментарий с самым высоким рейтингом
    $query = '
    SELECT ' . $wpdb->prefix . 'comments.comment_ID, comment_date, comment_content, SUM(rating) as rat FROM ' . $wpdb->prefix . 'comments
    LEFT JOIN ' . $wpdb->prefix . 'comments_rating_log
    ON ' . $wpdb->prefix . 'comments.comment_ID = ' . $wpdb->prefix . 'comments_rating_log.comment_id
    WHERE comment_post_ID = ' . $postId . '
    AND comment_approved = 1
    GROUP BY comment_ID
    ORDER BY rat DESC
    LIMIT 1
    ';

    $topComment = $wpdb->get_row($query, ARRAY_A);

    // Если рейтинг ниже заданного значения
    if ($topComment['rat'] < $ratingMin) {
        return null;
    }

    // Получаем данные об авторе комментария
    $commentId = $topComment['comment_ID'];
    $commentMemberId = get_comment_meta($commentId, 'ipb_member_id', true);
    $commentMemberData = BridgeIpb4Wp::getMemberById($commentMemberId);

    $commentData['id'] = $commentId;
    $commentData['photo'] = $commentMemberData->get_photo();
    $commentData['author'] = $commentMemberData->members_seo_name;
    $commentData['text'] = (mb_strlen($topComment['comment_content']) > $commentTextChars)
        ? mb_substr($topComment['comment_content'], 0, $commentTextChars) . '...'
        : $topComment['comment_content'];
    $commentData['time'] = dateAgo(strtotime($topComment['comment_date'] . '+04:00'));

    return $commentData;
}

function pluralize($count, $type)
{
    $part_texts = array(
        'd'=>array( 'день', 'дня', 'дней' ),
        'h'=>array( 'час', 'часа', 'часов' ),
        'm'=>array( 'минута', 'минуты', 'минут' )
    );

    if ($count==0)
        return '';

    if ( $count>=11 && $count<=20 )
        return $count.' '.$part_texts[$type][2];

    if ( $count%10==1 )
        return $count.' '.$part_texts[$type][0];

    if ( $count%10>=2 && $count%10<=4 )
        return $count.' '.$part_texts[$type][1];

    return $count.' '.$part_texts[$type][2].' ';
}

function dateAgo($timestamp)
{
    $diff = time()-$timestamp;

    $diff_d = floor($diff/86400);  // 86400 - число секунд в сутках
    $diff = $diff - ($diff_d*86400);

    $diff_h = floor($diff/3600);  // 3600 - число секунд в дне
    $diff = $diff - ($diff_h*3600);

    $diff_m = floor($diff/60);
    $diff = $diff - ($diff_m*60);

  $diff_arr = array();
    if ($diff_d>0)
        $diff_arr['d'] = pluralize($diff_d,'d');
    if ($diff_h>0)
        $diff_arr['h'] = pluralize($diff_h,'h');
    if ($diff_m>0)
        $diff_arr['m'] = pluralize($diff_m,'m');

    if ( count($diff_arr)==0 )
        return 'только что';

    // если разница возвращается как "2 дня 3 часа 5 минут", то сокращаем ее до "2 дня 3 часа"
    if ( isset($diff_arr['d']) ) {
        if (isset($diff_arr['h'])) unset($diff_arr['h']);
        if (isset($diff_arr['m'])) unset($diff_arr['m']);
    }
    return implode(' ', $diff_arr).' назад';
}