<?php
header('Cache-Control: no-cache');
$out = array();
if (!$site->Is_loaded()) $site->Identify();
if (isset($_POST['ln'])) {
	$site->Set_language($_POST['ln']);
}
$site_id = v($_GET['site_id'], null);
switch (v($routes->Get(1))) {
	case 'unsubscribe':
		$pcSubscription = $core->Get_object('PC_plugin_subscription');
		$hash = v($routes->Get(2));
		if (!empty($hash)) {
			$s = $pcSubscription->Unsubscribe($hash, $routes->Get(3));
			if ($s) {
				$path = CMS_ROOT . $core->Get_theme_path() . "pc_subscription.unsubscribe.success.tpl.php";
				if (file_exists($path)) {
					$site->Render_template($path);
					exit;
				}
				else {
					$path = $core->Get_path('plugins', "pc_subscription.unsubscribe.success.tpl.php", 'pc_subscription');
					if (file_exists($path)) {
						$site->Render_template($path);
						exit;
					}
					echo 'Sekmingai atsisakete prenumeratos.';
				}
				
			}
			else {
				$path = CMS_ROOT . $core->Get_theme_path() . "pc_subscription.unsubscribe.error.tpl.php";
				if (file_exists($path)) {
					$site->Render_template($path);
					exit;
				}
				else {
					$path = $core->Get_path('plugins', "pc_subscription.unsubscribe.error.tpl.php", 'pc_subscription');
					if (file_exists($path)) {
						$site->Render_template($path);
						exit;
					}
					echo 'Nepavyko atsaukti prenumeratos. Susisiekite su svetaines administratoriumi.';
				}
			}
		}
		else $out['error'] = 'Nenurodete prenumeratoriaus kodo.';
		break;
	default: $out['error'] = 'Invalid action';
}
$content_type = 'application/json';
if (!empty($content_type)) {
	header('Content-Type: ' . $content_type);
}

echo json_encode($out);