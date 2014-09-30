<?php
/**
 * This script tries to detect current version of the plugin's database part.
 * 
 * @var array $cfg
 * @var PC_updater $this
 * @var PC_database $db
 * @var PC_core $core
 */

if( !$db->getTableInfo('plugin_pc_subscription') )
	return null; // plugin was not installed (enabled)

if( $db->getColumnInfo('plugin_pc_subscription', 'ln') )
	return '1.2.2';

return '1.0.0';