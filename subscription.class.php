<?php
final class PC_plugin_subscription extends PC_base {
	public function Subscribe($email, $site=null) {
		if (empty($site)) $site = $this->site->data['id'];
		if (!Validate('email', $email)) return false;
		$r = $this->prepare("INSERT INTO {$this->db_prefix}plugin_pc_subscription (site,email,date,hash,domain) VALUES(?,?,?,?,?)");
		$s = $r->execute(array($site, $email, time(), md5($email.$this->cfg['salt']), $this->cfg['url']['base']));
		return $s;
	}
	public function Unsubscribe($hash, $site=null) {
		if (empty($site)) $site = $this->site->data['id'];
		if (!Validate('md5', $hash)) return false;
		$r = $this->prepare("DELETE FROM {$this->db_prefix}plugin_pc_subscription WHERE site=? and hash=?");
		$s = $r->execute(array($site, $hash));
		return $s;
	}
	public function Site_render($params=null) {
		if (isset($_POST['pc_subscribe'])) {
			$r = $this->Subscribe($_POST['pc_subscribe']);
			$this->site->Register_data('pc_subscribe', $r);
		}
		if (isset($_POST['pc_unsubscribe'])) {
			$r = $this->Unsubscribe($_POST['pc_unsubscribe']);
			$this->site->Register_data('pc_unsubscribe', $r);
		}
	}
	public function Render_form($params=null) {
		$tpl = 'PC_plugin_tpl_subscription.php';
		$custom_tpl = $this->core->Get_path('themes', $tpl);
		if (is_file($custom_tpl)) include($custom_tpl);
		else include(dirname(__FILE__).'/'.$tpl);
	}
	
	public function Get_subscribers($site=null) {
		if( is_null($site) ) $site = $this->site->data["id"];
		$r = $this->prepare("SELECT * FROM {$this->db_prefix}plugin_pc_subscription WHERE site=?");
		$r->execute(Array($site));
		return $r->fetchAll();
	}
}