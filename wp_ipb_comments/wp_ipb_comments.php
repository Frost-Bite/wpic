<?php
/*
Plugin Name: WP-IPB comments
Version: 1.4.1
Description: Allows to write comments to users authorized on the IPB forum
Author: Khovl
License: GNU General Public License v3
*/

class WpIpbComments {
    public $v = '1.4.0';
    public $db;


    public $month = array(
        '01' => 'Январь', '02' => 'Февраль', '03' => 'Март', '04' => 'Апрель', '05' => 'Май', '06' => 'Июнь',
        '07' => 'Июль', '08' => 'Август', '09' => 'Сентябрь', '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
    );


    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;

        add_action('wp', array($this, 'actionWp'));
        add_action('init', array($this, 'actionInit'));

        add_action('current_screen', array($this, 'actionAdminHead'));


    }

    public function actionAdminHead()
    {
        $screen = get_current_screen();

        if ($screen->id == 'edit-comments') {
            add_filter('get_comment', array($this, 'filterGetComment'));
        }
    }

    // Срабатывает при событии wp (после определения типа страницы)
    public function actionWp()
    {

        if (is_single()) {
            add_action('wp_head', array($this, 'actionWpHead'));

            // подключение jquery
            add_action('wp_enqueue_scripts', function() {
                if (!is_admin()) {
                    wp_enqueue_script('jquery');
                }
            });
        }

    }


    // Подключение скриптов и стилей
    public function actionWpHead()
    {
        echo '
        <script type="text/javascript" src="' . plugins_url('core.js', __FILE__) . '?v=' . $this->v  . '"></script>
        <script type="text/javascript" src="' . plugins_url('markitup/sets/default/set.js', __FILE__) . '"></script>
        <link rel="stylesheet" type="text/css" href="' . plugins_url('style.css', __FILE__) . '?v=' . $this->v . '"/>
        ';
    }

    // Выводит форму комментирования и сами комментарии
    public function comments()
    {

        $postId = get_the_ID();
        $currentPageUrl = get_permalink($postId );

        $loginUrl = BridgeIpb4Wp::$forumUrl . 'index.php?/login/&ref=' . base64_encode($currentPageUrl);
        $unloginUrl = BridgeIpb4Wp::$forumUrl . 'index.php?/logout/&csrfKey=' . BridgeIpb4Wp::$csrfKey . '&ref=' . base64_encode($currentPageUrl);

        $comments = get_comments(array(
            'orderby' => 'comment_date',
            'post_id' => $postId,
            'type' => 'wp_ipb',
            'status' => 'approve'
        ));

        ?>

        <div id="comments_wrap">
            <h3>Комментарии</h3>

            <div id="comments_form_wrap">
                <div id="comments_form">
                    <?php if ( BridgeIpb4Wp::$ipbMember->member_id ): ?>

                        <?= BridgeIpb4Wp::$ipbMember->link() ?>,
                        <a href="<?= $unloginUrl ?>">выйти</a>.

                        <form class="comment-form" id="commentform" method="post" action="//www.progamer.ru/wp-comments-post.php">

                            <input type="hidden" id="comment_post_ID" value="<?= $postId ?>" name="comment_post_ID">

                            <p class="comment-form-comment">
                                <label for="comment">Ваш комментарий</label>
                                <textarea aria-required="true" rows="8" name="comment" id="comment" maxlength="3000" required></textarea>
                            </p>

                            <div class="form-submit">
                                <input type="submit" value="Отправить комментарий" id="submit" name="submit">

                                <div class="css_load">
                                    <div id="squaresWaveG">
                                        <div id="squaresWaveG_1" class="squaresWaveG">
                                        </div>
                                        <div id="squaresWaveG_2" class="squaresWaveG">
                                        </div>
                                        <div id="squaresWaveG_3" class="squaresWaveG">
                                        </div>
                                        <div id="squaresWaveG_4" class="squaresWaveG">
                                        </div>
                                        <div id="squaresWaveG_5" class="squaresWaveG">
                                        </div>
                                        <div id="squaresWaveG_6" class="squaresWaveG">
                                        </div>
                                        <div id="squaresWaveG_7" class="squaresWaveG">
                                        </div>
                                        <div id="squaresWaveG_8" class="squaresWaveG">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="parent" value="0">
                            <input type="hidden" name="parent-author" value="0">
                        </form>

                    <?php elseif ( BridgeIpb4Wp::$ipbMember->member_id && BridgeIpb4Wp::$ipbMember->isBanned() ) : ?>

                        <a href="<?= BridgeIpb4Wp::getProfileUrl(BridgeIpb4Wp::$ipbMember) ?>"><?= BridgeIpb4Wp::$ipbMember->members_seo_name ?></a>,
                        ваш аккаунт заблокирован, вы не можете оставлять комментарии.

                    <?php else: ?>
                        <div data-role='replyArea' class='cTopicPostArea ipsBox ipsBox_transparent ipsAreaBackground ipsPad cTopicPostArea_noSize ipsSpacer_top'>
                            <div class='ipsType_center ipsPad'>
		                    <p class='ipsType_light ipsType_normal ipsType_reset'>Вы должны быть зарегистрированы, чтобы оставить комментарий</p>
		                    <div class='ipsGrid ipsGrid_collapsePhone ipsSpacer_top'>
		                    	<div class='ipsGrid_span6 ipsAreaBackground_light ipsPad'>
		                    		<p class='ipsType_normal ipsType_reset ipsType_light'>Зарегистрируйтесь, это просто!</p>
		                    		<br>
		                    		<a href="<?= get_bloginfo('home') . '/' . BridgeIpb4Wp::$forumDir ?>/index.php?/register/" class='ipsButton ipsButton_primary ipsButton_small'>Зарегистрироваться</a>
		                    	</div>
		                    	<div class='ipsGrid_span6 ipsAreaBackground_light ipsPad'>
		                    		<p class='ipsType_normal ipsType_reset ipsType_light'>Уже зарегистрированы?</p>
		                    		<br>
		                    		<a href="<?= $loginUrl ?>" data-ipsDialog data-ipsDialog-size='medium' data-ipsDialog-title="Войти сейчас" class='ipsButton ipsButton_primary ipsButton_small'>Войти сейчас</a>
		                    	</div>
		                    </div>
	                        </div>
                        </div>
                    <?php endif; ?>

                    <?php if (function_exists('show_manual_subscription_form')) show_manual_subscription_form(); ?>

                </div>

            </div>

            <div id="comments_list">

                <?php
                foreach ($comments as $comment) {
                    if ($comment->comment_parent == 0)
                        echo $this->commentTemplate($comment);
                }
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Формирует html код комментария
     * @param $comment
     * @return string
     */
    public function commentTemplate($comment)
    {
        static $members = array();

        $commentMemberId = get_comment_meta($comment->comment_ID, 'ipb_member_id', true);

        if (!isset($members[$commentMemberId])) {
            $commentMemberData = BridgeIpb4Wp::getMemberById($commentMemberId);
            $members[$commentMemberId] = $commentMemberData;
        } else {
            $commentMemberData = $members[$commentMemberId];
        }

        $commentTime = strtotime($comment->comment_date);
        $commentTimeStr = date('j', $commentTime) . ' '
            . strtr(date('m', $commentTime), $this->month)
            . date(', Y - H:i', $commentTime);

        $commentRating = $this->getCommentRating($comment->comment_ID);
        $commentHide = ($commentRating <= -5) ? 'comment_hide' : '';

        // Список пользователей, которые уже выставляли рейтинг
        $query = 'SELECT member_id FROM wp_comments_rating_log WHERE comment_id =' . $comment->comment_ID;
        $membersSetRating = $this->db->get_col($query);

        $ratingDisabledClass = '';
        $ratingDisabled = '';

        if (
            !BridgeIpb4Wp::memberAuth()
            || in_array(BridgeIpb4Wp::$ipbMember->member_id, $membersSetRating)
            || $commentMemberId == BridgeIpb4Wp::$ipbMember->member_id
        ) {
            $ratingDisabledClass = 'disabled';
            $ratingDisabled = 'disabled="disabled"';
        }

        $commentsChildrens = get_comments(array(
            'orderby' => 'comment_date',
            'parent' => $comment->comment_ID,
            'type' => 'wp_ipb',
            'status' => 'approve',
            'order' => 'ASC',
            'hierarchical' => 'flat'
        ));

        uasort($commentsChildrens, function($a, $b) {
            if ($a->comment_ID == $b->comment_ID) {
                return 0;
            }
            return ($a->comment_ID < $b->comment_ID) ? -1 : 1;
        });

        $commentChild = false;

        ob_start();

        ?>
        <div class="comment_item <?= $commentHide ?>" data-id="<?= $comment->comment_ID ?>"
             data-author="<?= $commentMemberData->members_seo_name ?>"
             id="comment-<?= $comment->comment_ID ?>" >
            <?php require dirname(__FILE__) . '/comment-wrap.php'; ?>

            <?php if ($commentsChildrens) {
                echo '<div class="comments-childrens">';
                foreach ($commentsChildrens as $comment) {

                    echo $this->commentChildrenTemplate($comment);

                }
                echo '</div>';
            } ?>
        </div>

        <?php

        $commentBody = ob_get_contents();
        ob_end_clean();

        return $commentBody;
    }

    public function commentChildrenTemplate($comment)
    {
        ob_start();

        $commentChild = true;
        $commentMemberId = get_comment_meta($comment->comment_ID, 'ipb_member_id', true);

        if (!isset($members[$commentMemberId])) {
            $commentMemberData = BridgeIpb4Wp::getMemberById($commentMemberId);
            $members[$commentMemberId] = $commentMemberData;
        } else {
            $commentMemberData = $members[$commentMemberId];
        }

        $commentTime = strtotime($comment->comment_date);
        $commentTimeStr = date('j', $commentTime) . ' '
            . strtr(date('m', $commentTime), $this->month)
            . date(', Y - H:i', $commentTime);

        $commentRating = $this->getCommentRating($comment->comment_ID);
        $commentHide = ($commentRating <= -5) ? 'comment_hide' : '';

        // Список пользователей, которые уже выставляли рейтинг
        $query = 'SELECT member_id FROM wp_comments_rating_log WHERE comment_id =' . $comment->comment_ID;
        $membersSetRating = $this->db->get_col($query);

        $ratingDisabledClass = '';
        $ratingDisabled = '';

        if (
            !BridgeIpb4Wp::memberAuth()
            || in_array(BridgeIpb4Wp::$ipbMember->member_id, $membersSetRating)
            || $commentMemberId == BridgeIpb4Wp::$ipbMember->member_id
        ) {
            $ratingDisabledClass = 'disabled';
            $ratingDisabled = 'disabled="disabled"';
        }

        echo '<div class="comment_item ' . $commentHide . '" data-id="' . $comment->comment_ID . '" data-author="' . $commentMemberData->members_seo_name . '" id="comment-' . $comment->comment_ID . '">';
        require dirname(__FILE__) . '/comment-wrap.php';
        echo '</div>';

        $commentBody = ob_get_contents();
        ob_end_clean();

        return $commentBody;
    }

    public function actionInit()
    {

        if (isset($_POST['action'])) {

            switch ($_POST['action']) {

                case 'wpIpbSendComment': // Отправлен комментарий
                    $this->sendComment();
                    break;

                case 'ratingUp': // Изменение рейтинга комментария
                    $this->ratingUp();
                    break;

                case 'deleteRestore': // удаление восстановление комментариев
                    $this->deleteRestore();
                    break;

                case 'commentUpdate': // обновление комментария
                    $this->commentUpdate();
                    break;
            }
        }
    }

    public function commentUpdate()
    {
        $commentText = preg_replace("#\\\\[?]{1}#suix", '?', $_POST['commentText']);
        $commentText = preg_replace('#\&\#092;#suix', '', $commentText);
        $commentText =  apply_filters( 'pre_comment_content', $commentText);
        $commentId = (int) $_POST['commentId'];

        $commentMemberId = get_comment_meta($commentId, 'ipb_member_id', true);
        if (!$commentMemberId || $commentMemberId != BridgeIpb4Wp::$ipbMember->member_id) {
            echo json_encode(array('error' => 2));
            exit();
        }

        wp_update_comment(array('comment_ID' => $commentId, 'comment_content' => $commentText));

        echo json_encode(array('success' => 1));
        exit();
    }

    /**
     * Отправлен комментарий
     */
    public function sendComment()
    {
        $commentText = preg_replace("#\\\\[?]{1}#suix", '?', $_POST['comment']);
        $commentText = apply_filters( 'pre_comment_content', $commentText );
	    $commentText = htmlentities( $commentText, ENT_NOQUOTES, "UTF-8" );

        $postId = (int) $_POST['comment_post_ID'];
        $parent = (int) $_POST['parent'];

        if (!BridgeIpb4Wp::memberAuth()) {
            echo json_encode(array('error' => 'userNotAuth'));
            exit();
        }

        $commentData = array(
            'comment_author' => BridgeIpb4Wp::$ipbMember->members_seo_name,
            'comment_post_ID' => $postId,
            'comment_content' => $commentText,
            'comment_type' => 'wp_ipb',
            'comment_approved' => 1,
            'comment_parent' => $parent
        );

        $commentId = wp_insert_comment($commentData);
        add_comment_meta($commentId, 'ipb_member_id', BridgeIpb4Wp::$ipbMember->member_id);
        do_action( 'comment_post', $commentId, $commentData['comment_approved'], $commentData );

        $commentData = get_comment($commentId);

        if (!$parent)
            $commentBody = $this->commentTemplate($commentData);
        else
            $commentBody = $this->commentChildrenTemplate($commentData);

        if ($parent) BridgeIpb4Wp::sendNotificationNewReply($postId, $parent, $commentId, BridgeIpb4Wp::$ipbMember);

        echo json_encode(array('success' => 1, 'commentBody' => $commentBody));

        exit();
    }

    /**
     * Изменение рейтинга комментария
     */
    public function ratingUp()
    {

        $commentId = (int) $_POST['commentId'];
        $rating = $_POST['rating'];

        if ($rating != 1 && $rating != -1) {
            echo json_encode(array('error' => 1));
            exit();
        }

        $commentMemberId = get_comment_meta($commentId, 'ipb_member_id', true);
        if (!$commentMemberId || !BridgeIpb4Wp::$ipbMember->member_id || $commentMemberId == BridgeIpb4Wp::$ipbMember->member_id) {
            echo json_encode(array('error' => 2));
            exit();
        }

        if (!BridgeIpb4Wp::memberPoints()) {
            echo json_encode(array('error' => 3));
            exit();
        }

        $query = 'SELECT id FROM wp_comments_rating_log WHERE member_id = '
            . BridgeIpb4Wp::$ipbMember->member_id
            . ' AND comment_id = ' . $commentId;
        if ($this->db->get_results($query)) {
            echo json_encode(array('error' => 4));
            exit();
        }

        $query = 'INSERT IGNORE INTO wp_comments_rating_log SET member_id = '
            . BridgeIpb4Wp::$ipbMember->member_id
            . ', comment_id = ' . $commentId
            . ', rating = ' . $rating;
        $this->db->query($query);

        // Изменяем рейтинг в IPB
        $memberReceived = BridgeIpb4Wp::getMemberById($commentMemberId);
        $memberReceived->pp_reputation_points = ($rating == 1) ? $memberReceived->pp_reputation_points+1 : $memberReceived->pp_reputation_points-1;
        $memberReceived->save();

        $commentRatingResult = $this->getCommentRating($commentId);
        $commentRatingTemplate = $this->getCommentRatingTemplate($commentRatingResult);

        echo json_encode(array('success' => 1, 'rating' => $commentRatingTemplate));
        exit();
    }

    public function getCommentRating($commentId) {
        $query = 'SELECT SUM(rating) FROM wp_comments_rating_log WHERE comment_id = ' . $commentId;
        return (int) $this->db->get_var($query);
    }

    public function getCommentRatingTemplate($rating)
    {
        if ($rating > 0) {
            return '<span class="score positive">+' . $rating . '</span>';
        } elseif ($rating < 0) {
            return '<span class="score negative">' . $rating . '</span>';
        } else {
            return '<span class="score">' . $rating . '</span>';
        }
    }

    public function deleteRestore()
    {

        $commentId = (int) $_POST['commentId'];
        $comment = get_comment($commentId);
        $action = $_POST['commentAction'];

        if ($action != 'delete' && $action != 'restore') {
            echo json_encode(array('error' => 1));
            exit();
        }

        $commentMemberId = get_comment_meta($commentId, 'ipb_member_id', true);
        if (!$commentMemberId || $commentMemberId != BridgeIpb4Wp::$ipbMember->member_id) {
            echo json_encode(array('error' => 2));
            exit();
        }

        $commentStatus = ($action == 'restore') ? 'approve' : 'trash';

        // При удалении комментария удаляем оповещение на форуме
        if ($commentStatus == 'trash') {
            $this->db->delete('ipb_core_notifications',
                array('notification_key' => 'reply', 'extra' => '[' . $comment->comment_post_ID . ',' . $commentId . ']') );
        }

        wp_set_comment_status($commentId, $commentStatus);

        echo json_encode(array('success' => 1));
        exit();
    }

    public function filterGetComment($comment)
    {

        static $members = array();

        if (is_admin() && $comment->comment_type == 'wp_ipb') {

            $commentMemberId = get_comment_meta($comment->comment_ID, 'ipb_member_id', true);

            if (!isset($members[$commentMemberId])) {
                $commentMemberData = \IPS\Member::load($commentMemberId);
                $members[$commentMemberId] = $commentMemberData;
            } else {
                $commentMemberData = $members[$commentMemberId];
            }

            $comment->comment_author = $commentMemberData->members_seo_name;
            $comment->comment_author_email = $commentMemberData->email;
            $comment->comment_author_url = BridgeIpb4Wp::getProfileUrl($commentMemberData);

        }

        return $comment;
    }
}

$wpIpbComments = new WpIpbComments();


?>