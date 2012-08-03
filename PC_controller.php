<?php
final class PC_controller_pc_subscription extends PC_controller {
	public function Init() {
		parent::Init();
		global $pc_subscription;
		$this->cls =& $pc_subscription;
	}
	public function Process($data) {
		if (isset($this->route[2]) || isset($_POST['pc_unsubscribe'])) {
			$hash = (isset($_POST['pc_unsubscribe'])?$_POST['pc_unsubscribe']:$this->route[2]);
			$r = $this->cls->Unsubscribe($hash);
			$this->site->Register_data('pc_unsubscribe', $r);
			$this->Render('unsubscribe');
		}
		else {
			if (isset($_POST['pc_subscribe'])) {
				$r = $this->cls->Subscribe($_POST['pc_subscribe']);
				$this->site->Register_data('pc_subscribe', $r);
			}
			$this->Render();
		}
	}
}