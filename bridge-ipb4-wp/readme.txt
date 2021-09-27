Add code to header.php
<div class="mobile-content" data-controller="core.front.core.mobileNav" data-default="all">
  <div class="mobile-menu-ico" data-ipsdrawer data-ipsdrawer-drawerelem="#elMobileDrawer">
    <?php BridgeIpb4Wp::userNavMobileNotificationTotal() ?>
  </div>
<a href="https://www.progamer.ru/?s="><div class="mobileSearch-ico" data-action="mobileSearch"></div></a>
</div>

<?php BridgeIpb4Wp::userNav() ?> 

<?php BridgeIpb4Wp::navBar() ?>

<?php BridgeIpb4Wp::userNavMobile() ?>