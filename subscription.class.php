<?php
final class PC_plugin_subscription extends PC_base {
	public function Subscribe($email, $site=null) {
		$this->debug("Subscribe($email)");
		if (empty($site)) $site = $this->site->data['id'];
		if (!Validate('email', $email)) return false;
		$query = "INSERT IGNORE INTO {$this->db_prefix}plugin_pc_subscription (site,ln,email,date,hash,domain) VALUES(?,?,?,?,?,?)";
		$r = $this->prepare($query);
		$query_params = array($site, $this->site->ln, $email, time(), md5($email.$this->cfg['salt']), $this->cfg['url']['base']);
		$this->debug_query($query, $query_params, 1);
		$s = $r->execute($query_params);
		return $s;
	}
	public function Unsubscribe($hash, $site=null) {
		if (empty($site)) $site = $this->site->data['id'];
		if (!Validate('md5', $hash)) return false;
		$r = $this->prepare("DELETE FROM {$this->db_prefix}plugin_pc_subscription WHERE site=? and ln=? and hash=? LIMIT 1");
		$s = $r->execute(array($site, $this->site->ln, $hash));
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
	private function _markup_styles_inliner($m) {
		global $stylesheet;
		if (isset($stylesheet[$m[2].'.'.$m[4]])) {
			return $m[1].'style="'.$stylesheet[$m[2].'.'.$m[4]].'"'.$m[5];
		}
		elseif (isset($stylesheet['.'.$m[4]])) {
			return $m[1].'style="'.$stylesheet['.'.$m[4]].'"'.$m[5];
		}
		return $m[0];
	}
	private function _markup_links_fixer($m) {
		if (preg_match("#http://#i", $m[2])) return $m[0];
		return $m[1]."=\"".$this->cfg['url']['base'].$m[2].'"';
	}
	public function Get_markup($pageId=null, $text='', $inline_styles=false) {
		if (!$this->site->Is_loaded()) {
			$pageId = null;
			$text = 'Site data is not accessible (site is not loaded).';
		}
		$p = array();
		if (!is_null($pageId)) {
			$styles = @file_get_contents(CMS_ROOT . $this->site->Get_theme_path().'custom.css');
			$p = $this->page->Get_page($pageId);
			if (!$p) {
				$pageId = null;
				$text = 'Page count not be loaded (invalid page id?).';
			}
			else $text = $p['text'];
		}
		$tpl = $this->core->Get_path('themes').'pc_subscription.tpl.php';
		if (!is_file($tpl)) {
			$tpl = $this->core->Get_path('plugins', 'pc_subscription.tpl.php', 'pc_subscription');
		}
		if (!is_file($tpl)) {
			$markup = '<html><head>'
			.'<style type="text/css">body{background:'.$this->site->data['editor_background'].'}'.(!empty($styles)?$styles:'').'</style>'
			.'</head><body style="margin:0;padding:0;"><div style="width:'.$this->site->data['editor_width'].'px">';
			$markup .= $text;
			$markup .= '</div></body></html>';
		}
		else {
			$p['text'] = $text;
			$this->site->Register_data('pc_subscription/page', $p);
			ob_start();
			require($tpl);
			$markup = ob_get_clean();
		}
		$markup = preg_replace_callback("#(src|href)=\"([^\"]+?)\"#i", array($this, '_markup_links_fixer'), $markup);
		if ($inline_styles) {
			require($this->cfg['path']['classes']."CSSParser.php");
			$sheet = new CSSParser($styles);
			$parsed = $sheet->parse();
			$sheet_selectors = $parsed->getAllSelectors();
			$sheet_rules = $parsed->getAllRuleSets();
			
			global $stylesheet;
			$stylesheet = array();
			for ($a=0; isset($sheet_selectors[$a]); $a++) {
				$selector = $sheet_selectors[$a]->getSelector();
				$selector = explode('.', $selector[0]);
				$class = $selector[1];
				$class_parts = explode(' ', $class);
				$locked = false;
				if (in_array(v($class_parts[1]), array('tr', 'td'))) {
					$tag = $class_parts[1];
					$locked = true;
				}
				else $tag = $selector[0];
				
				$rules = $sheet_rules[$a]->getRules();
				$style = '';
				foreach ($rules as &$rule) {
					$style .= $rule->__toString();
				}
				$stylesheet[$tag.'.'.$class] = $style;
			}
			$markup = preg_replace_callback("#(<([a-z0-9]+)\s+[^>]*?)(class=\"([^>]+?)\")([^>]*?>)#i", array($this, '_markup_styles_inliner'), $markup);
		}
		return $markup;
	}
}