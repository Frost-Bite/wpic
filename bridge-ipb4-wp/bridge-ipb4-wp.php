<?php
/*
Plugin Name: Bridge IPB4-WP
Description: required for wp_ipb_comments
Version: 1.1.1
Author: Khovl
License: GNU General Public License v3
*/

class BridgeIpb4Wp
{

    public static $forumDir = 'forum'; // Invision community folder
    public static $reputationPointsMin = 1; // The minimum user rating at which he can vote
    public static $csrfKey;
    public static $memberProfileLink;
    public static $forumUrl;
    public static $forumLoginLink;
    public static $forumUnLoginLink;
    public static $forumRegistrationLink;
    public static $bripbwpConfigLanguages;

    /**
     * Информация о пользователе IPB
     * @var null | object
     * member_id, name, profile_url, member_banned = 0, members_display_name, pp_small_photo, pp_reputation_points
     */
    public static $ipbMember = null;

    function __construct()
    {

        require_once ABSPATH . self::$forumDir . '/init.php';

        \IPS\Dispatcher\Build::i()->init();
        $member = \IPS\Member::loggedIn();

        if ($member->member_id) {
            self::$ipbMember = $member;
            self::$memberProfileLink = self::getProfileUrl($member);
        }

        self::$csrfKey = \IPS\Session::i()->csrfKey;

        if (!is_admin()) {
            wp_enqueue_script('bridge-ipb4-wp-core', plugin_dir_url(__FILE__) . 'core.js', array('jquery'), '1.0.4');
            wp_enqueue_style('bridge-ipb4-wp-global', plugin_dir_url(__FILE__) . 'global.css', array(), '1.0.1');
            wp_enqueue_style('bridge-ipb4-wp-elsearch', plugin_dir_url(__FILE__) . 'elsearch.css', array(), '1.0.0');

            wp_enqueue_script('bridge-ipb4-howler', get_bloginfo('wpurl') . '/' . self::$forumDir . '/applications/core/interface/howler/howler.core.min.js', array(), '2.0.9');
        }

        // ссылки
        $currentPage = 'https://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $currentPageEncode = base64_encode($currentPage);

        self::$forumUrl = get_bloginfo('url') . '/' . self::$forumDir . '/';
        //self::$forumLoginLink = self::$forumUrl . 'index.php?/login/&ref=' . $currentPageEncode;
        self::$forumLoginLink = self::$forumUrl . 'login/?ref=' . $currentPageEncode;
        self::$forumUnLoginLink = self::$forumUrl . 'index.php?/logout/&csrfKey=' . self::$csrfKey;
        self::$forumRegistrationLink = self::$forumUrl . 'index.php?/register';


        // Вывод скриптов с форума IPS
        add_action('wp_head', function () {

            echo '<script type="text/javascript">

                jQuery(document).on("submit", "form[data-controller=\'core.global.core.login\']", function() {
                    var value = "usernamepassword";
                    if (document.activeElement.getAttribute("type") === "submit") {
                        value = document.activeElement.getAttribute("value");
                    }
                  jQuery(this).append("<input type=\'hidden\' name=\'_processLogin\' value=\'" + value + "\'>");
                });
                </script>';



            echo \IPS\Theme::i()->getTemplate('global', 'core', 'global')->includeJS();

            \IPS\Output::i()->jsFiles = array_merge(\IPS\Output::i()->jsFiles,
                \IPS\Output::i()->js('library.js', 'global', 'root'),
                \IPS\Output::i()->js('framework.js', 'global', 'root'),
                \IPS\Output::i()->js('front.js', 'global', 'root')
            );

            foreach (\IPS\Output::i()->jsFiles as $js) :
                echo '<script type="text/javascript" src="' . \IPS\Http\Url::external($js) . '"></script>';
            endforeach;

        });

        // Переключение языков на форуме, при активированном плагине WPML
        add_action('init', function () {
            if (defined('ICL_SITEPRESS_VERSION')) {
                $confLangFilePath = dirname(__FILE__) . '/config-languages.php';
                if (!file_exists($confLangFilePath)) {
                    return;
                } else {
                    require_once $confLangFilePath;
                }

                if (isset($bripbwpConfigLanguages) && is_array($bripbwpConfigLanguages) && isset($bripbwpConfigLanguages[ICL_LANGUAGE_CODE])) {
                    self::$bripbwpConfigLanguages = $bripbwpConfigLanguages;

                    $ips4LangId = $bripbwpConfigLanguages[ICL_LANGUAGE_CODE];
                    setcookie('ips4_language', $ips4LangId, 0, '/');

                    \IPS\Member::loggedIn()->language()->id = $ips4LangId;
                } else {

                    $ips4LangId = 1;
                    setcookie('ips4_language', $ips4LangId, 0, '/');

                    \IPS\Member::loggedIn()->language()->id = $ips4LangId;
                }

                if (self::$ipbMember->member_id) {
                    \IPS\Db::i()->update('core_members', array('language' => $ips4LangId), array(
                        'member_id=?',
                        self::$ipbMember->member_id
                    ));
                }

            }
        }, 1);


    }

    /**
     * Возвращает id языка в wpml для текущего id языка из ips4
     * @return bool|mixed
     */
    public static function getWpLangByIpsLang()
    {
        if (isset(self::$bripbwpConfigLanguages) && is_array(self::$bripbwpConfigLanguages) && isset(\IPS\Member::loggedIn()->language()->id)) {
            if ($wpLangId = array_search(\IPS\Member::loggedIn()->language()->id, self::$bripbwpConfigLanguages)) {
                return $wpLangId;
            }
        }

        return false;
    }

    /**
     * Проверяет авторизован ли пользователь
     * @return bool
     */
    public static function memberAuth()
    {
        if (
            !self::$ipbMember->member_id
            || self::$ipbMember->isBanned()
        ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Проверяет достаточен ли рейтинг у пользователя для голосования
     * @return bool
     */
    public static function memberPoints()
    {
        if (self::memberAuth() && self::$ipbMember->pp_reputation_points >= self::$reputationPointsMin) {
            return true;
        } else {
            return false;
        }
    }

    public static function getProfileUrl($member)
    {
        if (preg_match('#href=(?:"|\')(.*?)(?:"|\')#', $member->link(), $linkMatch)) {
            return $linkMatch[1];
        } else {
            return null;
        }
    }

    /**
     *  Получение информации о пользователе по его ID
     */
    public static function getMemberById($id)
    {
        return \IPS\Member::load($id);
    }

    /**
     * Выводит ссылки на авторизацию, регистрацию
     * аватар и ссылку на профиль пользователя
     */
    public static function userNav()
    {
        $userBar = \IPS\Theme::i()->getTemplate('global', 'core', 'front')->userBar();
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay($userBar);

        echo $userBar;
    }

    /**
     * Выводит ссылки на авторизацию, регистрацию
     * аватар и ссылку на профиль пользователя
     */
    public static function userNavMobile()
    {

        $userBar = \IPS\Theme::i()->getTemplate('global', 'core', 'front')->mobileNavigation();
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay($userBar);

        echo $userBar;

    }

    /**
     * Выводит навигационное меню с формой поиска
     */
    public static function navBar()
    {
        \IPS\Output::i()->defaultSearchOption[0] = 'blog';
        //\IPS\Output::i()->defaultSearchOption[1] = 'blog_search';

        $navBar = \IPS\Theme::i()->getTemplate('global', 'core', 'front')->navBar();
        \IPS\Member::loggedIn()->language()->parseOutputForDisplay($navBar);

        echo $navBar;
    }

    /**
     * Выводит суммарное кол-во оповещений в навигационном меню в мобильной версии сайта
     */
    public static function userNavMobileNotificationTotal()
    {
        $total = \IPS\Member::loggedIn()->notification_cnt;
        if (!\IPS\Member::loggedIn()->members_disable_pm and \IPS\Member::loggedIn()->canAccessModule(\IPS\Application\Module::get('core', 'messaging'))) {
            $total += \IPS\Member::loggedIn()->msg_count_new;
        }
        if (\IPS\Member::loggedIn()->canAccessModule(\IPS\Application\Module::get('core', 'modcp')) and \IPS\Member::loggedIn()->modPermission('can_view_reports')) {
            $total += \IPS\Member::loggedIn()->reportCount();
        }

        if ($total) :?>
            <span data-controller='core.front.core.mobileNav'>
                <span class="ipsNotificationCount" data-notificationtype="total"
                      style="top: 7px; right: 7px;"><?= $total ?></span>
            </span>
        <?php endif;
    }

    /**
     * Отправка оповещения об ответе на комментарий
     *
     * @param $postId
     * @param $parentId
     * @param $commentId
     */
    public static function sendNotificationNewReply($postId, $parentId, $commentId, $member)
    {

        ini_set('display_errors', 'On');
        error_reporting('E_ALL');

        $parentMemberId = get_comment_meta($parentId, 'ipb_member_id', true);
        $parentMember = \IPS\Member::load($parentMemberId);
        $post = get_post($postId);
        $comment = get_comment($commentId);
        $parentComment = get_comment($parentId);

        $notification = new \IPS\Notification(\IPS\Application::load('siteconnect'), 'reply', null,
            array(
                $postId,
                $post->post_title,
                $member,
                $commentId,
                $comment->comment_content,
                $parentMember,
                $parentComment->comment_content,
            ),
            array($postId, $commentId)
        );
        $notification->recipients->attach($parentMember);
        $notification->send();


    }
}

new BridgeIpb4Wp;

?>