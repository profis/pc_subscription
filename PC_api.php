<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');
$out = array();
if (!$site->Is_loaded()) $site->Identify();
if (isset($_POST['ln'])) {
	$site->Set_language($_POST['ln']);
}
switch (v($routes->Get(1))) {
	case 'unsubscribe':
		$pcSubscription = $core->Get_object('PC_plugin_subscription');
		$hash = v($routes->Get(2));
		if (!empty($hash)) {
			$s = $pcSubscription->Unsubscribe($hash);
			if ($s) echo 'Sekmingai atsisakete prenumeratos.';
			else echo 'Nepavyko atsaukti prenumeratos. Susisiekite su svetaines administratoriumi.';
		}
		else $out['error'] = 'Nenurodete prenumeratoriaus kodo.';
		break;
	default: $out['error'] = 'Invalid action';
}
echo json_encode($out);