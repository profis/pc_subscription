<?php
Register_class_autoloader('PC_plugin_subscription', dirname(__FILE__).'/subscription.class.php');
global $pc_subscription;
$pcSubscription = $this->core->Get_object('PC_plugin_subscription');
$this->core->Register_hook("site_render", array($pcSubscription, 'Site_render'));