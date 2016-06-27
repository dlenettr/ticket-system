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

@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

define( 'DATALIFEENGINE', true );
define( 'ROOT_DIR', substr( dirname(  __FILE__ ), 0, -12 ) );
define( 'ENGINE_DIR', ROOT_DIR . '/engine' );

include ENGINE_DIR . '/data/config.php';

date_default_timezone_set ( $config['date_adjust'] );

$_TIME = time ();

if ( $config['http_home_url'] == "" ) {
	$config['http_home_url'] = explode( "engine/ajax/ticket-system-ajax.php", $_SERVER['PHP_SELF'] );
	$config['http_home_url'] = reset( $config['http_home_url'] );
	$config['http_home_url'] = "http://" . $_SERVER['HTTP_HOST'] . $config['http_home_url'];
}

require_once ENGINE_DIR . '/classes/mysql.php';
require_once ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . '/classes/templates.class.php';
require_once ENGINE_DIR . '/modules/sitelogin.php';

dle_session();

if ( ! $is_logged ) die( "Hacking attempt!" );

$_COOKIE['dle_skin'] = trim(totranslit( $_COOKIE['dle_skin'], false, false ));

if( $_COOKIE['dle_skin'] ) {
	if( @is_dir( ROOT_DIR . '/templates/' . $_COOKIE['dle_skin'] ) ) {
		$config['skin'] = $_COOKIE['dle_skin'];
	}
}

if( $config["lang_" . $config['skin']] ) {
	if ( file_exists( ROOT_DIR . '/language/' . $config["lang_" . $config['skin']] . '/website.lng' ) ) {
		@include_once (ROOT_DIR . '/language/' . $config["lang_" . $config['skin']] . '/website.lng');
	} else die("Language file not found");
} else {
	include_once ROOT_DIR . '/language/' . $config['langs'] . '/website.lng';
}

include_once ROOT_DIR . '/language/' . $config['langs'] . '/ticket-system-lang.lng';

$config['charset'] = ($lang['charset'] != '') ? $lang['charset'] : $config['charset'];

require_once ENGINE_DIR	. '/api/api.class.php';

@header( "Content-type: text/html; charset=" . $config['charset'] );

if( !$is_logged ) die( "error" );

if (isset($_POST['tid'])) {
	$tid = intval( $db->safesql( $_POST['tid'] ) );

	if ($_POST['action'] == 'reply') {
		$uid = intval( $db->safesql( $_POST['uid'] ) );
		$suid = intval( $db->safesql( $_POST['suid'] ) );

		include_once ENGINE_DIR . '/classes/parse.class.php';
		$parse = new ParseFilter( Array (), Array (), 1, 1 );
		$text = $db->safesql( $parse->BB_Parse( $parse->process( $_POST['text'] ), false ) );
		$name = $db->safesql($_POST['name']);
		if( !$tid OR !$text OR !$uid) die( "error" );
		$dle_api->send_pm_to_user($suid, $lng_mod['ticket_notify'] . " " . $lng_mod['ticket_rtag'], $text, $name);
		$db->query( "
			UPDATE " . USERPREFIX . "_ticket_system
			SET active = '0',resp_id = '".$uid."',resp_name = '".$name."', resp_date = '".$_TIME."'
			WHERE active = '1' AND id = '".$tid."'");
		$db->free();
		$db->query( "
			INSERT INTO " . USERPREFIX . "_ticket_system_ans (ticket_id, resp_id, resp_date, resp_name, message, filelink)
			VALUES ('".$tid."', '".$uid."','".$_TIME."','".$name."','".$text."', '')" );
		$db->free();
		echo "ok";
	} else if ($_POST['action'] == 'del') {
		$row = $db->super_query( "
			SELECT filelink FROM " . USERPREFIX . "_ticket_system
			WHERE id = '{$tid}'");
		$file_link = str_replace('"','',str_replace($config['http_home_url'],'',$row['filelink']));
		@unlink(ROOT_DIR."/".$file_link);
		unset($row);
		$db->query( "
			DELETE FROM " . USERPREFIX . "_ticket_system
			WHERE id = '".$tid."'");
		$db->free();
		echo "ok";
	} else if ($_POST['action'] == 'delcat') {
		$db->query( "
			DELETE FROM " . USERPREFIX . "_ticket_system_cats
			WHERE cat_id = '".$tid."'");
		$db->free();
		echo "ok";
	} else if ($_POST['action'] == 'toggletic') {
		$row = $db->super_query( "
			SELECT active
			FROM ".USERPREFIX."_ticket_system
			WHERE id='".$tid."'"
		);
		if (intval($row['active']) == 0) {
			$db->query( "
				UPDATE " . USERPREFIX . "_ticket_system
				SET active = '1'
				WHERE id = '".$tid."'"
			);
			$db->free();
			echo "act";
		} else {
			$db->query( "
				UPDATE " . USERPREFIX . "_ticket_system
				SET active = '0'
				WHERE id = '".$tid."'"
			);
			$db->free();
			echo "dact";
		}
	} else if ($_POST['action'] == 'deltic') {
		$db->query( "
			DELETE FROM " . USERPREFIX . "_ticket_system
			WHERE id = '".$tid."'");
		$db->free();
		echo "ok";
	}
}

else if (isset($_POST['aid'])) {
	$aid = intval( $db->safesql( $_POST['aid'] ) );
	if ($_POST['action'] == 'del') {
		$db->query( "
			DELETE FROM " . USERPREFIX . "_ticket_system_ans
			WHERE id = '".$aid."'");
		$db->free();
		echo "ok";
	}

}

else if (isset($_GET['term'])) {

	$term = convert_unicode( $_GET['term'], $config['charset'] );
	if( preg_match( "/[\||\<|\>|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\+]/", $term ) ) $term = "";
	else $term = $db->safesql( htmlspecialchars( strip_tags( stripslashes( trim( $term ) ) ), ENT_QUOTES, $config['charset'] ) );
	if( $term == "" ) die("[]");
	$users = array ();
	$db->query("SELECT name FROM " . PREFIX . "_users WHERE `name` like '{$term}%' LIMIT 10");
	while( $row = $db->get_row() ) { $users[] = $row['name']; }
	echo json_encode( $users );
}

else {
	die( "error" );
}

?>
