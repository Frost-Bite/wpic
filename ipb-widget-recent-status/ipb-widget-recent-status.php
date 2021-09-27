<?php
/*
Plugin Name: Ipb Widget Recent Status
Version: 1.1.2
Description: Wordpress widget with statust updates on the ipb4 forum
Author: Khovl
License: GNU General Public License v3
*/

require_once dirname( __FILE__ ) . '/scb/load.php';

function iwrsInit()
{
    require_once dirname( __FILE__ ) . '/core.php';
    new iwrs();

    require_once dirname(__FILE__) . '/widget.php';
    scbWidget::init('iwrsWidget');

}

scb_init('iwrsInit');
