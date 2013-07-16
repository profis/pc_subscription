<?php
Register_class_autoloader('PC_plugin_subscription_model', dirname(__FILE__).'/classes/PC_plugin_subscription_model.php');
Register_class_autoloader('PC_plugin_subscription', dirname(__FILE__).'/subscription.class.php');
global $pc_subscription;
$pc_subscription = $this->core->Get_object('PC_plugin_subscription');
$pc_subscription->debug = true;
$pc_subscription->set_instant_debug_to_file($this->cfg['path']['logs'] . 'pc_subscription.html');
$this->core->Register_hook("after_load_page", array($pc_subscription, 'Site_render'));
//Old hook: site_render