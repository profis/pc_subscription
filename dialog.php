<?php
/** ProfisCMS - Opensource Content Management System Copyright (C) 2011 JSC "ProfIS"
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$cfg['auth']['permission_required'] = 'pc_subscription.edit';
$cfg['auth']['error_return_type'] = 'json';

$cfg['core']['no_login_form'] = true;
require_once '../../admin/admin.php';

$items_per_page = 30;
function PC_subscription_get_recipients($to) {
	$recipients = array();
	$tmp = preg_split("#\s*(\n|,)\s*#", $to);
	foreach ($tmp as $recipient) {
		if (!Validate('email', $recipient)) continue;
		$recipients[] = $recipient;
	}
	return $recipients;
}

if (isset($_POST['api']) || isset($_GET['ajax'])) {
	//header('Content-Type: application/json');
	header('Cache-Control: no-cache');
	$out = array();
	switch (v($_GET['action'])) {
		case 'delete_subscribers':
			if (!isset($_GET['action'], $_POST['site'])) break;
			$site->Load($_POST['site']);
			$ln = v($_POST['ln']);
			$emails = v($_POST['emails']);
			if (!empty($emails)) {
				$emails = explode(',', $emails);
				if (count($emails)) {
					$r = $db->prepare("DELETE FROM {$cfg['db']['prefix']}plugin_pc_subscription WHERE site=? and ln=? and email in('".implode("','", $emails)."')");
					$s = $r->execute(array($site->data['id'], $ln));
					if ($s) {
						$out = array('success'=> true);
						break;
					}
					$error = 'Database error';
				}
				else $error = 'Invalid format.';
			}
			else $error = 'No subscribers were selected.';
			$out['error'] = (!empty($error)?$error:'');
			break;
		case 'add_subscribers':
			$sid = v($_POST['site']);
			$ln = v($_POST['ln']);
			$list = PC_subscription_get_recipients(v($_POST['list']));
			if (!count($list)) $out['error'] = 'No recipients were found';
			$r = $db->prepare("SELECT email FROM {$cfg['db']['prefix']}plugin_pc_subscription WHERE site=? and ln=?");
			$s = $r->execute(array($sid, $ln));
			if ($s) {
				while ($email = $r->fetchColumn()) {
					if (in_array($email, $list)) {
						$rs = array_search($email, $list);
						if ($rs !== false) unset($list[$rs]);
					}
				}
			}
			if (count($list)) {
				//$list = array_values($list);
				$values = array();
				$params = array();
				$values = substr(str_repeat('(?,?,?,?,?,?),', count($list)), 0 , -1);
				foreach ($list as $email) {
					$params[] = $sid;
					$params[] = $ln;
					$params[] = $email;
					$params[] = time();
					$params[] = md5($email.$cfg['salt']);
					$params[] = $cfg['url']['base'];
				}
				$r = $db->prepare("INSERT INTO {$cfg['db']['prefix']}plugin_pc_subscription VALUES ".$values);
				$s = $r->execute($params);
				if (!$s) $out['error'] = 'Database error';
				else {
					$out['success'] = true;
				}
			}
			else $out['success'] = true;
			break;
		case 'get_subscribers':
			if (!isset($_GET['action'], $_POST['site'])) break;
			$site->Load($_POST['site']);
			$start = (int)v($_POST['start']);
			$limit = (int)v($_POST['limit']);
			if ($start < 0) $start = 0;
			if ($limit < 1) $limit = $items_per_page;
			$date_from = v($_POST['date_from']);
			$date_to = v($_POST['date_to']);
			$search_phrase = v($_POST['search_phrase']);
			$site_id = v($_POST['site']);
			$ln = v($_POST['ln']);
			if (!ctype_digit($site_id)) $site_id = 0; //all sites
			//---
			$where = array();
			$parameters = array();
			//---
			if (!empty($date_from)) {
				if (!empty($date_to)) {
					$where[] = 'date between ? and ?';
					array_push($parameters, strtotime($date_from), strtotime($date_to)+86399);
				}
				else {
					$where[] = 'date >= ?';
					$parameters[] = strtotime($date_from);
				}
			}
			elseif (!empty($date_to)) {
				$where[] = 'date <= ?';
				$parameters[] = strtotime($date_to)+86399;
			}
			//---
			
			if (!empty($search_phrase)) {
				$where[] = 'email like ?';
				$parameters[] = '%'.$search_phrase.'%';
			};
			if ($site_id>0) {
				$where[] = 'site=?';
				$parameters[] = $site_id;
			}
			$where[] = 'ln=?';
			$parameters[] = $ln;
			//---
			//get sorted&paged subscribers
			$r_subscribers = $db->prepare("SELECT *"
			." FROM {$cfg['db']['prefix']}plugin_pc_subscription"
			.(count($where)?' WHERE '.implode(' and ', $where):'')
			." ORDER BY date desc"
			." LIMIT $limit OFFSET $start");
			$r_subscribers->execute($parameters);

			//print_pre($r_subscribers);
			//print_pre($parameters);
			//append total subscribers count
			$r_total = $db->prepare("SELECT count(*) FROM {$cfg['db']['prefix']}plugin_pc_subscription".(count($where)?' WHERE '.implode(' and ', $where):''));
			$r_total->execute($parameters);
			if ($r_total) $out['total'] = $r_total->fetchColumn();
			$out['subscribers'] = array();
			if ($r_subscribers) {
				while ($data = $r_subscribers->fetch()) {
					$out['subscribers'][] = array(
						'site'=> $data['site'],
						'email'=> $data['email'],
						'date'=> date('Y-m-d H:i', $data['date'])
					);
				}
			}
			break;
		case 'show_preview':
			$subscription = $core->Get_object('PC_plugin_subscription');
			if (isset($_GET['action'], $_GET['ln'], $_GET['site'])) {
				$site->Load($_GET['site']);
				$site->Set_language($_GET['ln']);
				if (v($_GET['pid'])) {
					if (ctype_digit($_GET['pid'])) {
						$markup = $subscription->Get_markup($_GET['pid']);
						if ($markup) {
							echo $markup;
						}
						else echo 'Cannot generate markup';
					}
					else {
						echo $subscription->Get_markup(null, '<center>Preview</center>');
					}
				}
				else {
					echo $subscription->Get_markup(null, '<center>Preview</center>');
				}
			}
			else {
				echo $subscription->Get_markup(null, '<center>Preview</center>');
			}
			$dont_send_json = true;
			break;
		case 'send':
			set_time_limit(3000);
			$logger = new PC_debug();
			$logger->debug = true;
			$logger->set_instant_debug_to_file($cfg['path']['logs'] . 'pc_subscription_send.html', null, 20);
			$subject = v($_POST['subject']);
			$from = v($_POST['from']);
			$from_email = v($_POST['from_email']);
			$pid = v($_POST['pid']);
			$to = v($_POST['to']);
			$site_id = v($_POST['site']);
			$ln = v($_POST['ln']);
			if (!Validate('email', $from_email)) $out['errors']['from_email'] = true;
			if (!ctype_digit($pid)) $out['errors']['pid'] = true;
			//get recipients
			$recipients = array();
			if ($to == 'all') {
				$r = $db->prepare("SELECT email,hash,domain FROM {$cfg['db']['prefix']}plugin_pc_subscription WHERE site=? AND ln=?");
				$s = $r->execute(array($site_id, $ln));
				if ($s) {
					while ($recipient = $r->fetch()) {
						$recipients[] = $recipient;
					}
				}
			}
			else {
				$recipients = PC_subscription_get_recipients($to);
			}
			if (!count($recipients)) $out['errors']['to'] = true;
			//if everything is fine -> continue sending
			if (!count(v($out['errors']))) {
				if (isset($_POST['ln'], $_POST['site'])) {
					$site->Load($site_id);
					$site->Set_language($ln);
					$subscription = $core->Get_object('PC_plugin_subscription');
					$markup = $subscription->Get_markup($pid, '', true);
					
					$sbscr_route = 'rassylka-novostej';
					$ids = $page->Get_by_controller('pc_subscription');
					if (count($ids)) {
						$p = $page->Get_page($ids[0]);
						if ($p) $sbscr_route = $p['route'];
					}
					
					if ($markup) {
						$headers = "From: $from <$from_email>". "\r\n"
							."X-Mailer: ProfisCMS/".PC_VERSION." (PHP/".phpversion().")". "\r\n";
						$headers  .= 'MIME-Version: 1.0' . "\r\n";
						$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
						$r_hash = $db->prepare("SELECT hash FROM {$cfg['db']['prefix']}plugin_pc_subscription WHERE email=? LIMIT 1");
						$custom_real_recipients = array();
						if ($to != 'all') {
							$model = new PC_plugin_subscription_model();
							$custom_real_recipients = $model->get_all(array(
								'where' => array(
									'email' => $recipients,
									'site' => $site_id,
									'ln' => $ln
								),
								'key' => 'email',
							));
						}
						$logger->debug('Number of recipients: ' . count($recipients), 1);
						$counter = 0;
						foreach ($recipients as $recipient) {
							$counter++;
							if ($counter % 2 == 0) {
								//sleep (2);
							}
							if ($to == 'all') {
								$recip_email = $recipient['email'];
								//$markup2 = str_replace("</body>", '<a target="blank" href="'.$recipient['domain'].$sbscr_route.'/'.$recipient['hash'].'/">'.lang('subscription_unsubscribe').'</a></body>', $markup);
							}
							else $recip_email = $recipient;
							if (is_array($recipient) && isset($recipient['hash'])) {
								//$unsubscribe_link = (isset($recipient['domain'])?$recipient['domain']:$cfg['url']['base']);
								$unsubscribe_link = $cfg['url']['base'];
								$unsubscribe_link .= $ln . '/api/plugin/pc_subscription/unsubscribe/'.$recipient['hash'].'/' . $site_id;
								$markup2 = preg_replace("/#unsubscribe:([\pL\pN\-_\s]+?)#/ui", '<a href="'.$unsubscribe_link.'">$1</a>', $markup);
							}
							elseif (isset($custom_real_recipients[$recip_email]) and !empty($custom_real_recipients[$recip_email]['hash'])) {
								$custom_real_recipient = $custom_real_recipients[$recip_email];
								//$unsubscribe_link = (isset($custom_real_recipient['domain'])?$custom_real_recipient['domain']:$cfg['url']['base']);
								$unsubscribe_link = $cfg['url']['base'];
								$unsubscribe_link .= $ln . '/api/plugin/pc_subscription/unsubscribe/'.$custom_real_recipient['hash'].'/' . $site_id;
								$markup2 = preg_replace("/#unsubscribe:([\pL\pN\-_\s]+?)#/ui", '<a href="'.$unsubscribe_link.'">$1</a>', $markup);
							}
							else {
								$markup2 = preg_replace("/(#unsubscribe:[\pL\pN\-_]+?#)/ui", '', $markup);
							}
							//PC_utils::debugEmail($recip_email, $markup2);
							$s = PC_utils::sendEmail($recip_email, $markup2, array(
								'from_email' => $from_email,
								'from_name' => $from,
								'subject' => $subject,
								
							));
							//$s = mail($recip_email, $subject, $markup2, $headers);
							$logger->debug("Sending $counter ...: " . $s, 2);
							if (!$s) {
								$logger->debug(PC_utils::$last_send_email_error . $s, 3);
							}
							$out['markup'] = $markup;
							if ($s) $out['success'] = true;
							unset($markup2);
						}
						if (!v($out['success'])) $out['errors']['send'] = true;
					}
					else $out['errors']['markup'] = true;
				}
				else $out['errors']['unknown'] = true;
			}
			break;
	}
	if (!isset($dont_send_json)) echo json_encode($out);
	return;
}

$mod['name'] = 'Subscription';
$mod['onclick'] = 'mod_subscription_click()';
$mod['priority'] = 10;

$plugin_name = basename(dirname(__FILE__));
$plugin_url = $cfg['url']['base'].$cfg['directories']['plugins'].'/'.$plugin_name.'/';
$plugin_file = $plugin_url.basename(__FILE__);
?>
<script type="text/javascript">
PC.utils.localize('mod.pc_subscription', {
	en: {
		add: 'Add',
		title: 'Subscription',
		subject: 'Subject',
		sender: 'Sender name',
		sender_email: 'Sender email',
		page_to_send: 'Page to send',
		send_to: 'Send to',
		all_subscribers: 'All subscribers',
		custom_recipients: 'Custom recipients',
		recipients: 'Recipients',
		send: 'Send',
		confirm_send: 'Are you sure?',
		sent_title: 'Success',
		sent: 'Page was sent successfully.',
		add_error: 'Subscribers was not added, errors occurred.',
		sending_error: 'Page was not sent, errors occurred.',
		manage_subscribers: 'Manage subscribers',
		date_subscribed: 'Date subscribed',
		email: 'Email',
		email_list: 'List of new subscribers',
		delete_selected: 'Delete',
		add_subscribers: 'Add subscribers'
	},
	lt: {
		add: 'Pridėti',
		title: 'Prenumerata',
		subject: 'Tema',
		sender: 'Siuntėjas',
		sender_email: 'Siuntėjo el. paštas',
		page_to_send: 'Siunčiamas puslapis',
		send_to: 'Siųsti',
		all_subscribers: 'Prenumeratoriams',
		custom_recipients: 'Nurodytiems gavėjams',
		recipients: 'Gavėjai',
		send: 'Siųsti',
		confirm_send: 'Ar tikrai siųsti?',
		sent_title: 'Išsiųsta',
		sent: 'Puslapis sėkmingai išsiųstas nurodytiems gavėjams.',
		add_error: 'Subscribers was not added, errors occurred.',
		sending_error: 'Įvyko klaida. Puslapis neišsiųstas.',
		manage_subscribers: 'Prenumeratoriai',
		date_subscribed: 'Prenumeratorius nuo',
		email: 'El. paštas',
		email_list: 'Naujų prenumeratorių el. pašto adresai',
		delete_selected: 'Ištrinti',
		add_subscribers: 'Pridėti prenumeratorius'
	},
	ru: {
		add: 'Добавить',
		title: 'Подписка',
		subject: 'Тема',
		sender: 'Имя отправителя',
		sender_email: 'Эл. почта отправителя',
		page_to_send: 'Выбрать страницу',
		send_to: 'Адресаты',
		all_subscribers: 'Все подписчики',
		custom_recipients: 'Выборочные подписчики',
		recipients: 'Подписчики',
		send: 'Отправить',
		confirm_send: 'Вы уверены?',
		sent_title: 'Подтверждение рассылки',
		sent: 'Страница была успешно отправлена',
		add_error: 'Произошла ошибка, подписчики не добавлены',
		sending_error: 'Страница не была отправлена, произошла ошибка',
		manage_subscribers: 'Подписчики',//'Управление подписчиками',
		date_subscribed: 'Дата подписания',
		email: 'Эл. почта',
		email_list: 'Список новых подписчиков',
		delete_selected: 'Удалить',
		add_subscribers: 'Добавить подписчиков'
    }
});

Ext.namespace('PC.plugins');

var items_per_page = <?php echo $items_per_page; ?>;
var plugin_file = '<?php echo $plugin_file; ?>';

function mod_subscription_click() {
	PC.plugin.subscription.dialog = {};
	var dialog = PC.plugin.subscription.dialog;
	var ln = PC.i18n.mod.pc_subscription;
	dialog.ln = ln;
	
	dialog.Get_preview_link = function(site, pid, ln) {
		if (site == undefined) {
			if (dialog.window != undefined) {
				var site_and_ln = dialog.window._f._site._id.getValue();
				site_and_ln = site_and_ln.split('_');
				var site = site_and_ln[0];
				var ln = site_and_ln[1];
			}
			else site = PC.global.site;
		}
		if (ln == undefined) ln = PC.global.ln;
		return plugin_file +'?ajax=1&action=show_preview&ln='+ln+'&site='+site+'&pid='+ pid;
	}
	dialog.Generate_preview = function(ln, forced){
		if (ln==undefined) ln = PC.global.ln;
		var value = dialog.window._f._pid.getValue();
		if (value.length || forced) {
			Ext.get('pc_subscription_preview_frame').dom.src = dialog.Get_preview_link(null, value, ln);
		}
	}
	dialog.Get_site_ln_combo_object = function() {
		var o = {};
		Ext.iterate(PC.global.SITES, function(site){
			Ext.iterate(site[3], function(lang_data){
				 o[site[0] + '_' + lang_data[0]] = site[1] + ' - ' + lang_data[0];
			});
		});
		return o;
	}
	dialog.form = {
		ref: '_f',
		flex: 1,
		layout: 'form',
		padding: 6,
		border: false,
		bodyCssClass: 'x-border-layout-ct',
		labelWidth: 100,
		labelAlign: 'right',
		defaults: {xtype: 'textfield', anchor: '100%'},
		items: [
			{	ref: '_site',
				xtype: 'fieldset',
				//labelWidth: 100,
				value: PC.global.site,
				style: {
					padding: '10px 5px 10px 0',
					margin: '0 0 5px 0',
					border: '2px solid #D2DCEB'
				},
				defaults: {
					anchor: '100%'
				},
				items: [
					{
						ref: '_id',
						value: PC.global.site + '_' + PC.global.ln,
						xtype: 'combo',
						mode: 'local',
						store: {
							xtype: 'arraystore',
							fields: ['id', 'label'],
							idIndex: 0,
							data: PC.utils.getComboArrayFromObject(dialog.Get_site_ln_combo_object())
						},
						displayField: 'id',
						valueField: 'label',
						editable: false,
						forceSelection: true,
						triggerAction: 'all',
						
						listeners: {
							beforeselect: function(cmbbox, rec, ndx) {
								dialog.window._f._pid.setValue('');
								
							},
							select: function() {
								dialog.Generate_preview(undefined, true);
							}
							
						}
					},
					/*
					new PC.ux.SiteCombo({
						ref: '_id',
						value: PC.global.site,
						listeners: {
							beforeselect: function(cmbbox, rec, ndx) {
								dialog.Generate_preview();
							}
						}
					}),
					*/
					{	ref: '_manage_subscribers',
						xtype: 'button',
						fieldLabel: '&nbsp;',
						labelSeparator: '',
						text: ln.manage_subscribers,
						icon: 'images/edit.gif',
						handler: function() {
							var site_and_ln = dialog.window._f._site._id.getValue();
							site_and_ln = site_and_ln.split('_');
							var site_id = site_and_ln[0];
							var lang = site_and_ln[1];
							if (dialog.subscribers != undefined) {
								dialog.subscribers.store.baseParams.site = site_id;
								dialog.subscribers.store.baseParams.ln = lang;
								dialog.subscribers.grid._site.setValue(site_id);
								dialog.subscribers.grid._site.afterSelect(site_id, lang);
								dialog.subscribers.window.show();
								return;
							}
							var initial_date_from = '';
							var initial_date_to = '';
							dialog.subscribers = {};
							var subscribers = dialog.subscribers;
							subscribers.store = new Ext.data.JsonStore({
								url: plugin_file +'?action=get_subscribers',
								remoteSort: true,
								fields: ['site', 'email', 'date'],
								baseParams: {
									api: true,
									site: site_id,
									ln: lang,
									limit: items_per_page
								},
								totalProperty: 'total',
								root: 'subscribers',
								idProperty: 'id',
								autoLoad: true
							});
							subscribers.gridSelectionModel = new Ext.grid.CheckboxSelectionModel({
								listeners: {
									selectionchange: function(sm) {
										var selected = sm.getSelections();
										if (selected.length) {
											subscribers.grid._action_delete.enable();
										}
										else {
											subscribers.grid._action_delete.disable();
										}
									}
								}
							});
							subscribers.add_subscriber = function(){
								var window = new PC.ux.Window({
									title: ln.add_subscribers,
									width: 400, height: 200,
									layout: 'form',
									padding: 5,
									labelWidth: 100,
									labelAlign: 'right',
									items: [{
										ref: '_list',
										fieldLabel: ln.email_list,
										xtype: 'textarea',
										anchor: '100%',
										height: 125
									}],
									buttons: [
										{	text: ln.add,
											icon: 'images/add.png',
											ref: '_add',
											handler: function(b, e) {
												var list = window._list.getValue();
												if (list == '') {
													window.close();
													return;
												}
												Ext.Ajax.request({
													url: plugin_file +'?action=add_subscribers',
													params: {
														api: true,
														site: subscribers.grid._site.getValue(),
														ln: subscribers.store.baseParams.ln,
														list: list
													},
													method: 'POST',
													callback: function(opts, success, rspns) {
														if (success && rspns.responseText) {
															try {
																var data = Ext.decode(rspns.responseText);
																if (data.success) {
																	window.close();
																	subscribers.store.reload();
																	return; //ok
																}
																if (typeof data.errors == 'object') {
																	Ext.iterate(data.errors, function(err){
																		alert(err);
																	});
																}
															} catch(e) {};
														}
														//Ext.Msg.hide();
														Ext.MessageBox.show({
															title: PC.i18n.error,
															msg: ln.add_error,
															buttons: Ext.MessageBox.OK,
															icon: Ext.MessageBox.ERROR
														});
													}
												});
											}
										}
									],
									closeAction: 'hide'
								});
								window.show();
							};
							var paging_toolbar = new Ext.PagingToolbar({
								ref: '../_paging',
								store: subscribers.store,
								displayInfo: true,
								pageSize: items_per_page,
								prependButtons: true
							});
							subscribers.store._paging = paging_toolbar;
							subscribers.grid = new Ext.grid.GridPanel({
								_paging: paging_toolbar,
								region: 'center',
								border: false,
								store: subscribers.store,
								columns: [
									subscribers.gridSelectionModel,
									{	id: 'pc_subscription_subscriber_email',
										header: ln.email, dataIndex: 'email'
										/*editor: {
											xtype: 'textfield',
											selectOnFocus: true,
											listeners: {
												blur: function(field){
													var record = subscribers.grid.selModel.getSelected();
													if (!record) return;
													if (record.data._new === true) subscribers.store.remove(record);
												},
												change: function(field, value, old){
													if (value == '') {
														subscribers.store.rejectChanges();
														return false;
													}
													var record = subscribers.grid.selModel.getSelected();
													if (!record) return;
													if (record.data._new) {
														//create new
														Ext.Ajax.request({
															url: plugin_file +'?action=add_subscriber',
															method: 'POST',
															params: {
																api: true,
																site: subscribers.grid._site.getValue(),
																email: value,
																date: record.data.date
															},
															callback: function(opts, success, response) {
																if (success && response.responseText) {
																	try {
																		var data = Ext.decode(response.responseText);
																		if (data.success) {
																			subscribers.store.reload();
																			Ext.Msg.hide();
																			return;
																		}
																		else var error = data.error;
																	} catch(e) {
																		var error = 'Invalid JSON data returned.';
																	};
																}
																else var error = 'Connection error.';
																Ext.MessageBox.show({
																	title: PC.i18n.error,
																	msg: (error?'<b>'+ error +'</b><br />':'') +'Subscriber was not added.',
																	buttons: Ext.MessageBox.OK,
																	icon: Ext.MessageBox.ERROR
																});
															}
														});
														record.data._new = false;
														subscribers.store.commitChanges();
													}
													else {
														//edit old
														alert('edit old!');
														subscribers.store.commitChanges();
													}
												}
											}
										}*/
									},
									{header: ln.date_subscribed, dataIndex: 'date', width: 100}
								],
								sm: subscribers.gridSelectionModel,
								autoExpandColumn: 'pc_subscription_subscriber_email',
								tbar: [
									{	ref: '../_action_add',
										text: ln.add,
										icon: 'images/add.png',
										handler: subscribers.add_subscriber
									},
									{	ref: '../_action_delete',
										disabled: true,
										text: ln.delete_selected,
										icon: 'images/delete.png',
										handler: function() {
											Ext.MessageBox.show({
												title: PC.i18n.msg.title.confirm,
												msg: ln.confirm_send,
												buttons: Ext.MessageBox.YESNO,
												icon: Ext.MessageBox.QUESTION,
												fn: function(r) {
													if (r == 'yes') {
														Ext.MessageBox.show({
															title: PC.i18n.msg.title.loading,
															msg: PC.i18n.msg.loading,
															width: 300,
															wait: true,
															waitConfig: {interval:100}
														});
														var selections = subscribers.grid.selModel.getSelections();
														if (!selections.length) return;
														var emails = [];
														Ext.iterate(selections, function(rec){
															emails.push(rec.data.email);
														});
														Ext.Ajax.request({
															url: plugin_file +'?action=delete_subscribers',
															method: 'POST',
															params: {
																api: true,
																site: subscribers.grid._site.getValue(),
																ln: subscribers.store.baseParams.ln,
																emails: emails.join(',')
															},
															callback: function(opts, success, response) {
																if (success && response.responseText) {
																	try {
																		var data = Ext.decode(response.responseText);
																		if (data.success) {
																			subscribers.store.reload();
																			Ext.Msg.hide();
																			return;
																		}
																		else var error = data.error;
																	} catch(e) {
																		var error = 'Invalid JSON data returned.';
																	};
																}
																else {
																	var error = 'Connection error.';
																}
																Ext.MessageBox.show({
																	title: PC.i18n.error,
																	msg: (error?'<b>'+ error +'</b><br />':'') +'Subscribers was not deleted.',
																	buttons: Ext.MessageBox.OK,
																	icon: Ext.MessageBox.ERROR
																});
															}
														});
													}
												}
											});
										}
									},
									{xtype:'tbfill'},
									{xtype:'tbtext', hidden: true, text: PC.i18n.site},
									new PC.ux.SiteCombo({
										ref: '../_site',
										hidden: true,
										width: 180,
										value: site_id,
										listeners: {
											beforeselect: function(cmbbox, rec, ndx) {
												cmbbox.afterSelect(rec.get('id'));
											}
										},
										afterSelect: function(id, ln){
									
											//debugger;
											subscribers.store._paging.changePage(1);
											subscribers.store.setBaseParam('site', id);
											subscribers.store.setBaseParam('ln', ln);
											subscribers.store.setBaseParam('start', 0);
											subscribers.store.reload();
													
											//subscribers.store.reload({
											//	page: 1,
											//	start: 0
											//});
											//if (dialog.Initial_site_value == rec.get('id')) return;
										}
									})/**//*,
									{xtype:'tbtext', text: 'Show from:'},
									{	ref: '../date_from',
										xtype:'datefield',
										width: 80,
										value: initial_date_from,
										maxValue: new Date()
									},
									{xtype:'tbtext', text: 'to', style:'margin: 0 2px;'},
									{	ref: '../date_to',
										xtype:'datefield',
										width: 80,
										value: initial_date_to,
										maxValue: new Date()
									},
									{	xtype:'tbtext',
										text: 'With phrase:',//ln.with_phrase,
										style:'margin: 0 2px;'
									},
									{	ref: '../search_phrase',
										xtype:'textfield',
										width: 80
									},
									{	icon:'images/zoom.png',
										handler: function() {
											//site
											var site = dialog.w.site.getValue();
											dialog.store.setBaseParam('site', site);
											//date from
											var date_from = dialog.w.date_from.getValue();
											if (date_from instanceof Date) {
												dialog.store.setBaseParam('date_from', date_from.format('Y-m-d'));
											}
											else {
												dialog.store.setBaseParam('date_from', undefined);
											}
											//date to
											var date_to = dialog.w.date_to.getValue();
											if (date_to instanceof Date) {
												dialog.store.setBaseParam('date_to', date_to.format('Y-m-d'));
											}
											else {
												dialog.store.setBaseParam('date_to', undefined);
											}
											//search phrase
											var search_phrase = dialog.w.search_phrase.getValue();
											if (search_phrase.length) {
												dialog.store.setBaseParam('search_phrase', search_phrase);
											}
											else {
												dialog.store.setBaseParam('search_phrase', undefined);
											}
											dialog.store.load({
												params: {
													start: 0 // reset the start to 0 since you want the filtered results to start from the first page
												}
											});
										}
									},
									{	icon:'images/zoom_out.png',
										handler: function() {
											dialog.store.setBaseParam('site', dialog.Initial_site_value);
											dialog.store.setBaseParam('date_from', undefined);
											dialog.store.setBaseParam('date_to', undefined);
											dialog.store.setBaseParam('search_phrase', undefined);
											dialog.store.load({
												params: {
													start: 0 // reset the start to 0 since you want the filtered results to start from the first page
												}
											});
											dialog.w.search_phrase.setValue('');
											dialog.w.date_from.setValue(initial_date_from);
											dialog.w.date_to.setValue(initial_date_to);
										}
									}*/
								],
								bbar: paging_toolbar
							});
							subscribers.window = new PC.ux.Window({
								title: ln.manage_subscribers,
								width: 420, height: 400,
								layout: 'border',
								items: [subscribers.grid],
								closeAction: 'hide'
							});
							subscribers.window.show();
						}
					}
				]
			},
			{	ref: '_subject',
				xtype: 'textarea',
				fieldLabel: ln.subject,
				height: 32
			},
			{	ref: '_from',
				fieldLabel: ln.sender,
				value: PC.global.admin
			},
			{	ref: '_from_email',
				fieldLabel: ln.sender_email
			},
			{	ref: '_pid',
				xtype: 'trigger',
				fieldLabel: ln.page_to_send,
				selectOnFocus: true,
				triggerClass: 'x-form-folder-trigger',
				onTriggerClick: function() {
					var site_and_ln = dialog.window._f._site._id.getValue();
					site_and_ln = site_and_ln.split('_');
					var site = site_and_ln[0];
					var lang = site_and_ln[1];
					var field = this;
					Show_redirect_page_window(function(value, ln){
						field.setValue(value);
						dialog.Generate_preview(ln);
					}, {
						select_node_path:this.getValue(), 
						init_value: dialog.window._f._site._id.getValue().split('_')[0],
						disable_ln_combo: true,
						site: site,
						ln: lang,
						tree_config: {
							ln: lang
						}
					});
				},
				listeners: {
					change: function(field, value, ovalue) {
						dialog.Generate_preview();
					}
				}
			},
			{	ref: '_to',
				id: 'db_fld_date_container',
				xtype: 'fieldset',
				labelWidth: 100,
				style: {
					padding: '10px 5px 10px 0',
					margin: '0 0 5px 0',
					border: '2px solid #D2DCEB'
				},
				defaults: {
					anchor: '100%'
				},
				items: [
					{	ref: '_type',
						fieldLabel: ln.send_to,
						xtype: 'combo', mode: 'local',
						store: {
							xtype: 'arraystore',
							fields: ['value', 'display'],
							idIndex: 0,
							data: [
								['all', ln.all_subscribers],
								['custom', ln.custom_recipients]
							]
						},
						valueField: 'value',
						displayField: 'display',
						value: 'all',
						forceSelection: true,
						triggerAction: 'all',
						editable: false,
						listeners: {
							change: function(field, value, ovalue) {
								if (value == 'custom') dialog.window._f._to._custom.enable();
								else dialog.window._f._to._custom.disable();
							},
							select: function(cb, rec, idx) {
								cb.fireEvent('change', cb, cb.value, cb.originalValue);
							}
						}
					},
					{	ref: '_custom',
						xtype: 'textarea',
						fieldLabel: ln.recipients,
						disabled: true
					}
				]
			}
		],
		buttonAlign: 'center',
		buttons: [
			{	text: ln.send,
				icon: '<?php echo $plugin_url.$plugin_name; ?>.png',
				ref: '_send',
				handler: function(b, e) {
					var site_and_ln = dialog.window._f._site._id.getValue();
					site_and_ln = site_and_ln.split('_');
					var site = site_and_ln[0];
					var lang = site_and_ln[1];
					var params = {
						api: true,
						site: site,
						ln: lang,
						subject: dialog.window._f._subject.getValue(),
						from: dialog.window._f._from.getValue(),
						from_email: dialog.window._f._from_email.getValue(),
						pid: dialog.window._f._pid.getValue(),
						to: (dialog.window._f._to._type.getValue()=='custom'?dialog.window._f._to._custom.getValue():'all')
					};
					Ext.MessageBox.show({
						title: PC.i18n.msg.title.confirm,
						msg: ln.confirm_send,
						buttons: Ext.MessageBox.YESNO,
						icon: Ext.MessageBox.QUESTION,
						fn: function(r) {
							if (r == 'yes') {
								Ext.MessageBox.show({
									title: PC.i18n.msg.title.loading,
									msg: PC.i18n.msg.loading,
									width: 300,
									wait: true,
									waitConfig: {interval:100}
								});
								Ext.Ajax.request({
									url: plugin_file +'?action=send',
									timeout:300000,
									params: params,
									method: 'POST',
									callback: function(opts, success, rspns) {
										if (success && rspns.responseText) {
											try {
												var data = Ext.decode(rspns.responseText);
												if (data.success) {
													Ext.MessageBox.show({
														title: ln.sent_title,
														msg: ln.sent,
														buttons: Ext.MessageBox.OK,
														icon: Ext.MessageBox.INFO
													});
													Ext.Msg.hide();
													return;//ok
												}
												if (typeof data.errors == 'object') {
													Ext.iterate(data.errors, function(err){
														alert(err);
													});
												}
											} catch(e) {};
										}
										//Ext.Msg.hide();
										Ext.MessageBox.show({
											title: PC.i18n.error,
											msg: ln.sending_error,
											buttons: Ext.MessageBox.OK,
											icon: Ext.MessageBox.ERROR
										});
									}
								});
							}
						}
					});
				}
			},
			{	text: PC.i18n.close,
				handler: function() {
					dialog.window.close();
				}
			}
		]
	};
	dialog.preview = {
		ref: '_preview',
		width: 630,
		html: '<iframe frameborder="0" id="pc_subscription_preview_frame" width="100%" height="100%" src="'+ dialog.Get_preview_link() +'"></iframe>'
	};
	dialog.window = new PC.ux.Window({
		modal: true,
		title: ln.title,
		width: 910,
		height: 500,
		layout: 'hbox',
		layoutConfig: {
			align: 'stretch'
		},
		resizable: false,
		items: [dialog.preview, dialog.form]
	});
	dialog.window.show();
}

PC.plugin.subscription = {
	name: PC.i18n.mod.pc_subscription.title,
	onclick: mod_subscription_click,
	icon: <?php echo json_encode(get_plugin_icon()) ?>,
	priority: <?php echo $mod['priority'] ?>
};

</script>