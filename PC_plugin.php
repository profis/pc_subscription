<?php
Register_class_autoloader('PC_plugin_subscription', dirname(__FILE__).'/subscription.class.php');
global $pc_subscription;
$pc_subscription = new PC_plugin_subscription;
$this->core->Register_hook("site_render", array($pc_subscription, 'Site_render'));
//$this->core->Register_hook("pc_subscription_form", array($pc_subscription, 'Render_form'));