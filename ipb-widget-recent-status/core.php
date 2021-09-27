<?php

class iwrs {

    function __construct()
    {
        add_action('wp_ajax_iwrs-rating-up', array($this, 'ratingUp'));
        add_action('wp_ajax_nopriv_iwrs-rating-up', array($this, 'ratingUp'));

        if (!is_admin()) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('iwrs-core', plugin_dir_url(__FILE__) . 'assets/js/core.js', array('jquery'), '1.0.1');
            wp_localize_script('iwrs-core', 'ajax', array('url' => admin_url('admin-ajax.php')) );
        }
    }

    public static function getRatingTemplate($rating)
    {
        if ($rating > 0) {
            return '<span class="score positive">+' . $rating . '</span>';
        } elseif ($rating < 0) {
            return '<span class="score negative">' . $rating . '</span>';
        } else {
            return '<span class="score">' . $rating . '</span>';
        }
    }

    public static function getKarmaTemplate($itemId)
    {

        $membersIds = array(); // Получаем список пользователей, которые голосовали
        $membersIdsInfo = array(); // Информация о том как какой пользователь проголосовал
        $ratingTotal = 0; // рейтинг статуса

        foreach( \IPS\Db::i()->select(
            'rep_rating, member_id',
            'core_reputation_index',
            array('type="status_id" AND app="core" AND type_id="' .  $itemId . '"') ) as $itemRepLog) {

            $membersIds[] = $itemRepLog['member_id'];
            $ratingTotal += (int) $itemRepLog['rep_rating'];
            $membersIdsInfo[$itemRepLog['member_id']] = (int) $itemRepLog['rep_rating'];
        }

        $statusMemberId = \IPS\Db::i()->select(
            'status_member_id',
            'core_member_status_updates',
            array('status_id = ' . $itemId))->first();

        $out = ' <span class="karma karma_wrap" data-id="' . $itemId. '">';
        $out .= self::getRatingTemplate($ratingTotal);

        // Если пользователь авторизован и статус не принадлежит ему, то добавляем кнопки
        if (BridgeIpb4Wp::memberAuth() && BridgeIpb4Wp::$ipbMember->member_id != $statusMemberId) {

            $memberId = BridgeIpb4Wp::$ipbMember->member_id;

            if (!in_array($memberId, $membersIds)) {

                $out .= ' <span class="karma tool minus">-</span>
                        <span class="karma tool plus">+</span>';

            } elseif ($membersIdsInfo[$memberId] === 1) {
                $out .= ' <span class="karma tool minus">-</span>';
            } else {
                $out .= ' <span class="karma tool plus">+</span>';
            }


        }

        return $out;
    }

    public function ratingUp()
    {
        $itemId = (int) $_POST['itemId'];
        $rating = (int) $_POST['rating'];

        if ($rating != 1 && $rating != -1) {
            echo json_encode(array('error' => 1));
            exit();
        }

        if (!BridgeIpb4Wp::memberAuth()) {
            echo json_encode(array('error' => 3));
            exit();
        }

        $memberId = BridgeIpb4Wp::$ipbMember->member_id;

        $voted = \IPS\Db::i()->select(
            'rep_rating',
            'core_reputation_index',
            array('type="status_id" AND app="core" AND type_id="' .  $itemId . '" AND member_id ="' . $memberId . '"'));

        // Если пользователь уже голосовал
        if ( $voted->count() ) {

            if ($voted->first() == $rating) { // Если голосовал также, как сейчас
                echo json_encode(array('error' => 3));
                exit();
            } else { // Если голосовал иначе, то отменяем голос
                \IPS\Db::i()->delete(
                    'core_reputation_index',
                    array('type="status_id" AND app="core" AND type_id="' .  $itemId . '" AND member_id="' . $memberId . '"')
                );
            }

        } else { // Если не голосовал

            $statusMemberId = \IPS\Db::i()->select(
                'status_member_id',
                'core_member_status_updates',
                array('status_id = ' . $itemId))->first();


            \IPS\Db::i()->insert(
                'core_reputation_index',
                array(
                    'member_id' => $memberId,
                    'app' => 'core',
                    'type' => 'status_id',
                    'type_id' => $itemId,
                    'rep_date' => time(),
                    'rep_rating' => $rating,
                    'member_received' => $statusMemberId
                )
            );

        }

        echo json_encode(array('success' => 1, 'rating' => self::getKarmaTemplate($itemId)));
        exit();
    }
}