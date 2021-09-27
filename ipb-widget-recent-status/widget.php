<?php

class iwrsWidget extends scbWidget
{
    protected $defaults = array(
        'title' => 'Самое актуальное (IPB4)',
        'description' => 'Выводит статусы пользователей с форума'
    );

    /**
     * ID групп пользователей, статусы которых будут выводиться
     * @var array
     */
    protected $usersGroups = array(4,6,7); // администраторы, модераторы и эксперты

    function __construct() {
        parent::__construct( 'iwrs-widget', 'Самое актуальное (IPB4)', array(
            'description' => 'Выводит статусы пользователей с форума'
        ) );
    }

    function form( $instance ) {
        echo html( 'p', $this->input( array(
            'type' => 'text',
            'name' => 'title',
            'desc' => 'Заголовок:'
        ), $instance ) );

        echo html( 'p', $this->input( array(
            'type' => 'text',
            'name' => 'num',
            'desc' =>  'Количество выводимых статусов'
        ), $instance ) );

        echo html( 'p', $this->input( array(
            'type' => 'textarea',
            'extra' => 'style="width: 100%"',
            'name' => 'text_after',
            'desc' =>  'Текст после:<br/>'
        ), $instance ) );
    }

    function content( $instance )
    {

        if (!$instance['num']) {
            $instance['num'] = 5;
        }

        // Получаем пользователей, которые находятся в группах $usersGroups
        $members = array();
        foreach( \IPS\Db::i()->select('member_id', 'core_members',
            array('member_group_id IN (' . implode(',', $this->usersGroups) . ')') ) as $memberId ) {

            $members[$memberId] = BridgeIpb4Wp::getMemberById($memberId);
        }

        $membersIds = array_keys($members);

        $out = '<ul class="iwrs-widget">';

        // Получаем статусы этих пользователей
        foreach( \IPS\Db::i()->select(
            '*',
            'core_member_status_updates',
            array('status_author_id IN (' . implode(',', $membersIds) . ')'),
            'status_date DESC',
            array( 0, (int) $instance['num'] ) ) as $statusItem ) {

            $member = $members[$statusItem['status_author_id']];
            $memberProfileUrl = BridgeIpb4Wp::getProfileUrl($member);

            $rating = iwrs::getKarmaTemplate($statusItem['status_id']);

            // Если с момента публикации прошло менее суток, то выводим в формате "27 минут назад"
            // если более суток, то в формате "27 Мар"
            if ( (time()-$statusItem['status_date']) < (24*3600) ) {
                $statusItem['status_date'] = $this->dateAgo($statusItem['status_date']);
            } else {
                $statusItem['status_date'] = date_i18n('d M' , $statusItem['status_date'] );
            }

            $out .= '<li class="ipsDataItem"><div class="ipsDataItem_icon ipsPos_top">';
            $out .= '<a href="' . $memberProfileUrl . '" class="ipsUserPhoto_tiny"><img src="' . $member->get_photo() . '" /></a>';
            $out .= '</div><div class="ipsDataItem_main ipsType_medium ipsType_break"><p class="ipsType_medium ipsType_reset">';
            $out .= '<strong><a href="' . $memberProfileUrl . '">' . $member->name . '</a></strong>';
            $out .= '</p><div class="ipsContained">' . $statusItem['status_content'] . '</div>';
            $out .= '<span class="ipsType_light ipsType_small">
                <a class="ipsType_blendLinks" href="' . $memberProfileUrl . '?status=' . $statusItem['status_id'] . '&amp;type=status">
                ' . $statusItem['status_date'];
            $out .= ' | Ответов: ' . $statusItem['status_replies'] . '</a>' . $rating;
            $out .= '</span>';


            $out .= '</div></li>';
        }

        $out .= '</ul>';

        $out .= $instance['text_after'];

        echo $out;
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

        if ($diff_d>0)
            return pluralize($diff_d,'d') . ' назад';
        elseif ($diff_h>0)
            return pluralize($diff_h,'h') . ' назад';
        elseif ($diff_m>0)
            return pluralize($diff_m,'m') . ' назад';
        else
            return 'Только что';

    }
}

