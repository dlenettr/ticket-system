<?php
/*
=====================================================
 MWS Ticket System v1.4 - by MaRZoCHi
-----------------------------------------------------
 Site: http://dle.net.tr/
-----------------------------------------------------
 Copyright (c) 2016
-----------------------------------------------------
 Lisans: GPL License
=====================================================
*/

if( ! defined( 'E_DEPRECATED' ) ) {
	@error_reporting ( E_ALL ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_NOTICE );
} else {
	@error_reporting ( E_ALL ^ E_DEPRECATED ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_DEPRECATED ^ E_NOTICE );
}

define ( 'DATALIFEENGINE', true );
define ( 'ROOT_DIR', dirname ( __FILE__ ) );
define ( 'ENGINE_DIR', ROOT_DIR . '/engine' );
define ( 'LANG_DIR', ROOT_DIR . '/language/' );

require_once ENGINE_DIR . "/inc/include/functions.inc.php";
require_once ENGINE_DIR . "/data/config.php";
require_once ROOT_DIR . "/language/".$config['langs']."/adminpanel.lng";
require_once ENGINE_DIR . "/classes/mysql.php";
require_once ENGINE_DIR . "/data/dbconfig.php";
require_once ENGINE_DIR . "/modules/sitelogin.php";
require_once ENGINE_DIR . "/classes/install.class.php";
require_once ENGINE_DIR . "/api/api.class.php";

@header( "Content-type: text/html; charset=" . $config['charset'] );
require_once(ROOT_DIR."/language/".$config['langs']."/adminpanel.lng");

$Turkish = array ( 'm01' => "Kuruluma Başla", 'm02' => "Yükle", 'm03' => "Kaldır", 'm04' => "Yapımcı", 'm05' => "Çıkış Tarihi", 'm08' => "Kurulum Tamamlandı", 'm10' => "dosyasını silerek kurulumu bitirebilirsiniz", 'm11' => "Modül Kaldırıldı", 'm21' => "Kuruluma başlamadan önce olası hatalara karşı veritabanınızı yedekleyin", 'm22' => "Eğer herşeyin tamam olduğuna eminseniz", 'm23' => "butonuna basabilirsiniz.", 'm24' => "Güncelle", 'm25' => "Site", 'm26' => "Çeviri", 'm27' => "Hata", 'm28' => "Bu modül DLE sürümünüz ile uyumlu değil.", 'm29' => "Buradan sürümünüze uygun modülü isteyebilirsiniz" );
$English = array ( 'm01' => "Start Installation", 'm02' => "Install", 'm03' => "Uninstall", 'm04' => "Author", 'm05' => "Release Date", 'm06' => "Module Page", 'm07' => "Support Forum", 'm08' => "Installation Finished", 'm10' => "delete this file to finish installation", 'm11' => "Module Uninstalled", 'm21' => "Back up your database before starting the installation for possible errors", 'm22' => "If you are sure that everything is okay, ", 'm23' => "click button.", 'm24' => "Upgrade", 'm25' => "Site", 'm26' => "Translation", 'm27' => "Error", 'm28' => "This module not compatible with your DLE.", 'm29' => "You can ask for compatible version from here" );
$Russian = array ( 'm01' => "Начало установки", 'm02' => "Установить", 'm03' => "Удалить", 'm04' => "Автор", 'm05' => "Дата выпуска", 'm06' => "Страница модуля", 'm07' => "Форум поддержки", 'm08' => "Установка завершена", 'm10' => "удалите этот фаля для окончания установки", 'm11' => "Модуль удален", 'm21' => "Сделайте резервное копирование базы данных для избежания возможных ошибок", 'm22' => "Если вы уверены что всё впорядке, ", 'm23' => "нажмите кнопку.", 'm24' => "обновлять", 'm25' => "сайт", 'm26' => "перевод" );
$lang = array_merge( $lang, $$config['langs'] );

function mainTable_head( $title ) {
	echo <<< HTML
	<div class="box">
		<div class="box-header">
			<div class="title"><div class="box-nav"><font size="2">{$title}</font></div></div>
		</div>
		<div class="box-content">
			<table class="table table-normal">
HTML;
}

function mainTable_foot() { echo "</table></div></div>"; }

$module = array(
	'name'	=> "MWS Ticket System v1.4",
	'desc'	=> "Ticket destek sistemi",
	'id'	=> "ticket-system-inc",
	'icon'	=> "ticket-system.png",
	'ticon' => "tag",
	'date'	=> "26.06.2016",
	'ifile'	=> "install_module.php",
	'link'	=> "http://dle.net.tr",
	'image'	=> "http://img.dle.net.tr/mws/ticket_system.png",
	'author_n'	=> "Mehmet Hanoğlu (MaRZoCHi)",
	'author_s'	=> "http://dle.net.tr",
	'tran_n'	=> "",
	'tran_s'	=> "",
);

echoheader("<i class=\"icon-{$module['ticon']}\"></i> " . $module['name'], $module['desc'] );

if ( $_REQUEST['action'] == "install" ) {

	$mod = new VQEdit();
	$mod->backup = True;
	$mod->bootup( $path = ROOT_DIR, $logging = True );
	$dle_api->install_admin_module( $module['id'], $module['name'], $module['desc'], $module['icon'] , "1" );
	$mod->file( ROOT_DIR . "/install/xml/ticket-system13.xml" );
	$mod->close();

	$tableSchema = array();
	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_ticket_system";
	$tableSchema[] = "CREATE TABLE IF NOT EXISTS " . PREFIX . "_ticket_system (
	`id` mediumint(8) NOT NULL auto_increment,
	`send_id` mediumint(8) NOT NULL,
	`send_date` varchar(20) default NULL,
	`send_name` varchar(50) NOT NULL,
	`send_ip` varchar(16) NOT NULL default '',
	`resp_id` mediumint(8) NOT NULL,
	`resp_date` varchar(20) default NULL,
	`resp_name` varchar(50) NOT NULL,
	`priority` mediumint(1) NOT NULL,
	`cat_id` mediumint(8) NOT NULL,
	`message` text NOT NULL,
	`filelink` varchar(60) NOT NULL default '',
	`active` int(1) NOT NULL,
	PRIMARY KEY  (`id`)
	) ENGINE=MyISAM /*!40101 DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci */";
	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_ticket_system_ans";
	$tableSchema[] = "CREATE TABLE IF NOT EXISTS " . PREFIX . "_ticket_system_ans (
	`id` mediumint(8) NOT NULL auto_increment,
	`ticket_id` mediumint(8) NOT NULL,
	`resp_id` mediumint(8) NOT NULL,
	`resp_date` varchar(20) default NULL,
	`resp_name` varchar(50) NOT NULL,
	`message` text NOT NULL,
	`filelink` varchar(60) NOT NULL default '',
	PRIMARY KEY  (`id`)
	) ENGINE=MyISAM /*!40101 DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci */";
	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_ticket_system_cats";
	$tableSchema[] = "CREATE TABLE IF NOT EXISTS " . PREFIX . "_ticket_system_cats (
	`cat_id` mediumint(8) NOT NULL auto_increment,
	`cat_name` varchar(50) NOT NULL,
	`author_id` mediumint(8) NOT NULL,
	PRIMARY KEY  (`cat_id`)
	) ENGINE=MyISAM /*!40101 DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci */";
	$tableSchema[] = "ALTER TABLE " . PREFIX . "_usergroups ADD max_day_ticks SMALLINT(6) NOT NULL DEFAULT 0";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email (`name`, `template`) VALUES
	('ticket_ma', 'Sayın {%username_to%}, size {%username_from%}\'den ticket gönderildi.\r\n\r\nMesaj :\r\n---------------------------------------------------------\r\n{%text%}\r\n---------------------------------------------------------\r\n\r\nCevap vermek için {%ticket_url%}\r\n\r\n---------------------------------------------------------\r\nEk Dosya : {%filelink%}\r\nTarih : {%date%}\r\nGönderilen IP Adresi: {%ip%}'),
	('ticket_pm', 'Sayın {%username_to%}, size {%username_from%}\'den ticket gönderildi.\r\n\r\nMesaj :\r\n---------------------------------------------------------\r\n{%text%}\r\n---------------------------------------------------------\r\n\r\nCevap vermek için {%ticket_url%}\r\n\r\n---------------------------------------------------------\r\nEk Dosya : {%filelink%}\r\nTarih : {%date%}\r\nGönderilen IP Adresi: {%ip%}');";

	foreach($tableSchema as $table) {
		$db->query($table);
	}


	mainTable_head( $lang['m08'] );
	echo <<< HTML
	<table width="100%">
		<tr>
			<td width="210" align="center" valign="middle" style="padding:4px;">
				<img src="{$module['image']}" alt="" />
			</td>
			<td style="padding-left:20px;padding-top: 4px;" valign="top">
				<b><a href="{$module['link']}">{$module['name']}</a></b><br /><br />
				<b>{$lang['m04']}</b> : <a href="{$module['author_s']}">{$module['author_n']}</a><br />{$translation}
				<b>{$lang['m05']}</b> : <font color="#555555">{$module['date']}</font><br />
				<b>{$lang['m25']}</b> : <a href="{$module['link']}">{$module['link']}</a><br />
				<br /><br />
				<b><font color="#BF0000">{$module['ifile']}</font> {$lang['m10']}</b><br />
			</td>
		</tr>
	</table>
HTML;
	mainTable_foot();

} else if ( $_REQUEST['action'] == "uninstall" ) {

	$dle_api->uninstall_admin_module( $module['id'] );

	$tableSchema[] = "DROP TABLE " . PREFIX . "_ticket_system";
	$tableSchema[] = "DROP TABLE " . PREFIX . "_ticket_system_ans";
	$tableSchema[] = "DROP TABLE " . PREFIX . "_ticket_system_cats";
	$tableSchema[] = "ALTER TABLE " . PREFIX . "_usergroups DROP max_day_ticks";
	foreach($tableSchema as $table) {
		$db->query($table);
	}

	mainTable_head( $lang['m11'] );
	echo <<< HTML
	<table width="100%">
		<tr>
			<td width="210" align="center" valign="middle" style="padding:4px;">
				<img src="{$module['image']}" alt="" />
			</td>
			<td style="padding-left:20px;padding-top: 4px;" valign="top">
				<b><a href="{$module['link']}">{$module['name']}</a></b><br /><br />
				<b>{$lang['m04']}</b> : <a href="{$module['author_s']}">{$module['author_n']}</a><br />{$translation}
				<b>{$lang['m05']}</b> : <font color="#555555">{$module['date']}</font><br />
				<b>{$lang['m25']}</b> : <a href="{$module['link']}">{$module['link']}</a><br />
				<br /><br />
				<b><font color="#BF0000">{$module['ifile']}</font> {$lang['m10']}</b><br />
			</td>
		</tr>
	</table>
HTML;
	mainTable_foot();
	$db->free();

} else {

	mainTable_head( $lang['m01'] );
	$translation = ( ! empty( $module['tran_n'] ) ) ? "<b>{$lang['m26']}</b> : <a href=\"{$module['tran_s']}\">{$module['tran_n']}</a><br />" : "";
	echo <<< HTML
	<table width="100%">
		<tr>
			<td width="210" align="center" valign="middle" style="padding:4px;">
				<img src="{$module['image']}" alt="" /><br /><br />
			</td>
			<td style="padding-left:20px;padding-top: 4px;" valign="top">
				<b><a href="{$module['link']}">{$module['name']}</a></b><br /><br />
				<b>{$lang['m04']}</b> : <a href="{$module['author_s']}">{$module['author_n']}</a><br />{$translation}
				<b>{$lang['m05']}</b> : <font color="#555555">{$module['date']}</font><br />
				<b>{$lang['m25']}</b> : <a href="{$module['link']}">{$module['link']}</a><br />
				<br /><br />
				<b><font color="#BF0000">{$lang['m01']} ...</font></b><br /><br />
				<b>*</b> {$lang['m21']}<br />
				<b>*</b> {$lang['m22']} <font color="#51A351"><b>{$lang['m02']}</b></font> {$lang['m23']}<br />
			</td>
		</tr>
		<tr>
			<td width="150" align="left" style="padding:4px;"></td>
			<td colspan="2" style="padding:4px;" align="right">
HTML;
		echo "<input type=\"button\" value=\"{$lang['m02']}\" class=\"btn btn-green btn-success\" onclick=\"location.href='{$PHP_SELF}?action=install'\" />";
		echo <<< HTML
			</td>
		</tr>
	</table>
HTML;
	mainTable_foot();
	$db->free();
}

echofooter();
?>