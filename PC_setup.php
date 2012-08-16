<?php
function pc_subscription_install($controller) {
	global $core;
	//create database table
	$sql_files = array(
		'mysql'=> 'setup/mysql.sql',
		'pgsql'=> 'setup/pgsql.sql'
	);
	$driver = $core->sql_parser->Get_default_driver();
	if (isset($sql_files[$driver])) {
		$sql = file_get_contents($core->plugins->Get_plugin_path('pc_subscription').$sql_files[$driver]);
		if ($sql) {
			$core->sql_parser->Replace_variables($sql);
			$queries = explode(';', $sql);
			foreach ($queries as $query) {
				if (!empty($query)) {
					$core->db->query($query);
				}
			}
		}
	}
	//prepare variables
	$vars = array(
		'subscription_subscribe'=> array(
			'en'=> 'Subscribe',
			'lt'=> 'Prenumeruoti',
			'ru'=> 'Подписаться'
		),
		'subscription_unsubscribe'=> array(
			'en'=> 'Unsubscribe',
			'lt'=> 'Atsisakyti prenumeratos',
			'ru'=> 'Отписаться'
		),
		'subscription_subscribed'=> array(
			'en'=> 'You have successfully subscribed to our newsletter.',
			'lt'=> 'Sėkmingai užsiprenumeravote naujienlaiškį',
			'ru'=> 'Вы подписаны на рассылку.'
		),
		'subscription_not_subscribed'=> array(
			'en'=> 'There was an error while trying to subscribe. Please try again later.',
			'lt'=> 'Užsiprenumeruoti nepavyko. Bandykite vėliau.',
			'ru'=> 'В процессе подписки на рассылку произошла ошибка.'
		),
		'subscription_unsubscribed'=> array(
			'en'=> 'Your subscription was cancelled successfully',
			'lt'=> 'Sėkmingai atsisakėte prenumeratos',
			'ru'=> 'Ваша подписка отменена'
		),
		'subscription_not_unsubscribed'=> array(
			'en'=> 'There was an error while trying to unsubscribe you now, please try again later.',
			'lt'=> 'Prenumeratos atsisakyti nepavyko. Pabandykite vėliau.',
			'ru'=> 'В процессе отписки от рассылки произошла ошибка.'
		),
		'subscription_hash'=> array(
			'en'=> 'Hash',
			'lt'=> 'Kodas',
			'ru'=> 'Хэш'
		),
		'email'=> array(
			'en'=> 'Email',
			'lt'=> 'El. paštas',
			'ru'=> 'Эл. почта'
		)
	);
	//insert variables
	$sql = $params = array();
	foreach ($vars as $key=>$values) {
		foreach ($values as $ln=>$value) {
			$sql[] = '(?,?,?,?,?)';
			array_push($params, $key, $controller, 0, $ln, $value);
		}
	}
	if (count($sql)) {
		$r = $core->db->prepare("INSERT INTO {$core->db_prefix}variables VALUES ".implode(',', $sql));
		$success = $r->execute($params);
	}
	return v($success, false);
}