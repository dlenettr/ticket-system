<?php
/*
=====================================================
 MWS Ticket System v1.3 - by MaRZoCHi
-----------------------------------------------------
 Site: http://dle.net.tr/
-----------------------------------------------------
 Copyright (c) 2015
-----------------------------------------------------
 Lisans: GPL License
=====================================================
*/

if ( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}

$doaction = ( isset( $_REQUEST['doaction'] ) ) ? $_REQUEST['doaction'] : "";
$department = ( isset( $_REQUEST['department'] ) ) ? $_REQUEST['department'] : "";

$is_uploaded = false;

include_once ENGINE_DIR . '/data/ticket-system-config.php';
require_once ROOT_DIR .'/language/' . $config['langs'] . "/ticket-system-lang.lng"; unset( $lng_inc );

if ( $config['allow_alt_url'] ) {
	$links = array(
		"main"	=> "tickets/main.html",
		"new"	=> "tickets/new.html",
		"view"	=> "tickets/view.html",
		"user"	=> $config['http_home_url'] . "user/"
	);
} else {
	$links = array(
		"main"	=> "index.php?do=ticket",
		"new"	=> "index.php?do=ticket&doaction=new",
		"view"	=> "index.php?do=ticket&doaction=view",
		"user"	=> $PHP_SELF . "?subaction=userinfo&amp;user="
	);
}


if ( $doaction == "new" ) {
	$allow_file = $ticket_set['allow_file'] ? true : false;
	if ( $ticket_set['allow_extension_def'] && $config['version_id'] >= "9.7" ) {
		$allow_extensions = explode(",",$user_group[ $member_id['user_group'] ]['files_type']);
	} else {
		$allow_extensions = explode(",",$ticket_set['allow_extensions']);
	}
	if ( $ticket_set['allow_filesize_def'] && $config['version_id'] >= "9.7") {
		$allow_filesize = 1024 * intval($user_group[ $member_id['user_group'] ]['max_file_size']);
	} else {
		$allow_filesize = 1024 * intval($ticket_set['allow_filesize_br']) * intval($ticket_set['allow_filesize']);
	}
	$upload_dir = $ticket_set['upload_dir'];
	$date_format = $ticket_set['date_format'];
	$allow_mchar = intval($ticket_set['allow_mchar']);
	$allow_captcha = $ticket_set['sec_method'] == "on1" ? true : false;
	$allow_recaptcha = $ticket_set['sec_method'] == "on2" ? true : false;
	$allow_mail = $ticket_set['allow_mail'] == "on" ? true : false;
	$allow_pm = $ticket_set['allow_pm'] == "on" ? true : false;
	$allow_groups = unserialize(str_replace("'", '"', $ticket_set['allowed_groups']));
	$max_ticket = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_ticket_system WHERE FROM_UNIXTIME(send_date) > NOW() - INTERVAL 24 HOUR AND send_id = '{$member_id['user_id']}'");
	if ( in_array( $member_id['user_group'], array_values($allow_groups) ) ) {

		if ( isset( $_POST['send'] ) ) {
			$stop = "";

			include_once ENGINE_DIR . '/classes/parse.class.php';
			$parse = new ParseFilter( Array (), Array (), 1, 1 );
			$message = $db->safesql( $parse->BB_Parse( $parse->process( $_POST['message'] ), false ) );

			if ($allow_file) {
				$attach = $_FILES['attachfile']['tmp_name'];
				$attach_name = $_FILES['attachfile']['name'];
				$attach_size = $_FILES['attachfile']['size'];
				$attach_name = str_replace( " ", "_", $attach_name );
				$attach_arr = explode( ".", $attach_name );
				$type = totranslit( end( $attach_arr ) );
				if( stripos ( $attach_name, "php" ) !== false ) die("Hacking attempt!");
			}
			if( empty( $message ) OR dle_strlen($message, $config['charset']) > $allow_mchar ) {
				$stop .= "<li>" . $lang['feed_err_5'] . "</li>";
			}
			if ($allow_file) {
				if( is_uploaded_file( $attach ) ) {
					if( $attach_size < $allow_filesize || $allow_filesize == 0 ) {
						if( in_array( $type, $allow_extensions ) AND $attach_name ) {
							$att_name = "uploads/" . $upload_dir . "/" . $member_id['user_id'] . "_" .$_TIME. "." . $type;
							@move_uploaded_file( $attach, ROOT_DIR ."/". $att_name );
							$is_uploaded = true;
						} else {
							$stop .= "<li>" . $lang['reg_err_13'] . "</li>";
						}
					} else {
						$stop .= "<li>" . $lang['news_err_16'] . "</li>";
					}
				}
			}

			if ($allow_recaptcha) {
				if ($_POST['recaptcha_response_field'] AND $_POST['recaptcha_challenge_field']) {
					require_once ENGINE_DIR . '/classes/recaptcha.php';
					$resp = recaptcha_check_answer ($config['recaptcha_private_key'],$_SERVER['REMOTE_ADDR'],$_POST['recaptcha_challenge_field'],$_POST['recaptcha_response_field']);
					if ($resp->is_valid) {
						$_POST['sec_code'] = 1;
						$_SESSION['sec_code_session'] = 1;
					} else $_SESSION['sec_code_session'] = false;
				} else $_SESSION['sec_code_session'] = false;
			}

			if ($allow_captcha) {
				if( $_POST['sec_code'] != $_SESSION['sec_code_session'] OR !$_SESSION['sec_code_session'] ) {
					$stop .= "<li>" . $lang['reg_err_19'] . "</li>";
				}
			} else $_SESSION['sec_code_session'] = false;

			if ( $stop ) {
				msgbox( $lang['all_err_1'], "<ul>{$stop}</ul><a href=\"javascript:history.go(-1)\">{$lang['all_prev']}</a>" );
			}

			if ( ! $stop ) {
				$query = $db->super_query( "SELECT author_id FROM " . USERPREFIX . "_ticket_system_cats WHERE cat_id='" . $_POST['product_author'] . "' LIMIT 0,1" );
				$author = $query['author_id'];
				$authorient = $db->super_query( "SELECT name, email, fullname FROM " . USERPREFIX . "_users WHERE user_id=" . $author . "" );
				$filelink = "";$fileadmin = "";
				$name = $member_id['name'];
				$user_ip = get_ip();
				if ($allow_file && $is_uploaded) {
					$filelink = "<a target=\"_blank\" href=\"{$config['http_home_url']}{$att_name}\">{$lng_mod['ticket_down']}</a>";
					$fileadmin = $config['http_home_url'].$att_name;
				}
				if ($allow_mail) {
					include_once ENGINE_DIR . '/classes/mail.class.php';
					$mail = new dle_mail( $config );
					$row = $db->super_query( "SELECT template FROM " . PREFIX . "_email WHERE name='ticket_ma' LIMIT 0,1" );
					$row['template'] = stripslashes( $row['template'] );
					$row['template'] = str_replace( "{%username_to%}", $authorient['fullname'], $row['template'] );
					$row['template'] = str_replace( "{%username_from%}", $name, $row['template'] );
					$row['template'] = str_replace( "{%text%}", $message, $row['template'] );
					$row['template'] = str_replace( "{%ip%}", $user_ip, $row['template'] );
					$row['template'] = str_replace( "{%date%}", langdate($date_format, $_TIME), $row['template'] );
					$row['template'] = str_replace( "{%filelink%}", $filelink, $row['template'] );
					$row['template'] = str_replace( "{%ticket_url%}", "<a target=\"_blank\" href=\"{$config['http_home_url']}{$config['admin_path']}?mod=ticket-system-inc&action=ntickets\">{$lng_mod['click_here']} {$lng_mod['click_click']}</a>", $row['template'] );
					$mail->from = $email;
					$mail->send( $authorient['email'], $subject, $row['template'] );
					if( $mail->send_error ) msgbox( $lang['all_info'], $mail->smtp_msg );
					else {
						if( $user_group[$member_id['user_group']]['max_mail_day'] ) {
							if ( !$is_logged ) $check_user = $_IP; else $check_user = $member_id['name'];
							$db->query( "INSERT INTO " . PREFIX . "_sendlog (user, date, flag) values ('{$check_user}', '{$_TIME}', '2')" );
						}
					}
				}
				if ($allow_pm) {
					require_once ENGINE_DIR	.'/api/api.class.php';
					$row = $db->super_query( "SELECT template FROM " . PREFIX . "_email WHERE name='ticket_pm' LIMIT 0,1" );
					$row['template'] = stripslashes( $row['template'] );
					$row['template'] = str_replace( "{%username_to%}", $authorient['fullname'], $row['template'] );
					$row['template'] = str_replace( "{%username_from%}", $name, $row['template'] );
					$row['template'] = str_replace( "{%text%}", $message, $row['template'] );
					$row['template'] = str_replace( "{%ip%}", $user_ip, $row['template'] );
					$row['template'] = str_replace( "{%date%}", langdate($date_format, $_TIME), $row['template'] );
					$row['template'] = str_replace( "{%filelink%}", $filelink, $row['template'] );
					$row['template'] = str_replace( "{%ticket_url%}", "<a target=\"_blank\" href=\"{$config['http_home_url']}{$config['admin_path']}?mod=ticket-system-inc&action=ntickets\">{$lng_mod['click_here']} {$lng_mod['click_click']}</a>", $row['template'] );
					$dle_api->send_pm_to_user($author, $lng_mod['ticket_notify'], str_replace("\n","<br />",$row['template'] ), $member_id['name']);
				}

				msgbox( $lang['feed_ok_1'], "{$lang['feed_ok_2']} <a href=\"{$config['http_home_url']}\">{$lang['feed_ok_4']}</a>, {$lng_mod['ticket_view']} <a href=\"{$config['http_home_url']}{$links['view']}\">{$lng_mod['click_here']}</a> {$lng_mod['click_click']}" );

				$db->query( "INSERT INTO " . USERPREFIX . "_ticket_system (send_id, send_date, send_name, send_ip, message, resp_id, resp_date, resp_name, priority, cat_id, filelink, active) VALUES ('".$member_id['user_id']."','". $_TIME ."','".$member_id['name']."','".$user_ip."','".$message."', '0', '', '', '".intval($_POST['priority'])."','".$_POST['product_author']."','".$fileadmin."','1' )" );
				unset($message, $query, $author, $authorient, $filelink, $fileadmin, $name, $user_ip);
				$db->free();
			}
		} else {
			$stop = "";
			if ( $user_group[$member_id['user_group']]['max_day_ticks'] ) {
				if ($max_ticket['count'] >= $user_group[$member_id['user_group']]['max_day_ticks'] ) {
					$stop .= "<li>" . $lng_mod['ticket_full'] . "</li>";
				}
			}
			if( $stop ) {
				msgbox( $lang['all_err_1'], "<ul>{$stop}</ul><a href=\"javascript:history.go(-1)\">{$lang['all_prev']}</a>" );
			}

			if( ! $stop ) {
				$hiddens = "";
				if ( $department == "" ) {
					$product = "<select name=\"product_author\">";
					$sql = $db->query("SELECT cat_id,cat_name FROM " . PREFIX . "_ticket_system_cats");
					while ($row = $db->get_row($sql)) {
						$product .= "<option value=\"" . $row['cat_id'] . "\">" . $row['cat_name'] . "</option>\n";
					}
					$product .= "</select>";
					$db->free();
				} else {
					$department = $db->safesql( $department );
					$sql = $db->super_query("SELECT cat_name FROM " . PREFIX . "_ticket_system_cats WHERE cat_id = '{$department}'");
					$hiddens .= "<input name=\"product_author\" value=\"" . $db->safesql( $department ) . "\" type=\"hidden\" />";
					if ( $sql ) {
						$product = $sql['cat_name'];
					} else {
						$product = "<font color=\"red\">" . $lng_mod['undefined_dep'] . "</font>";
					}
				}

				$max_ticket = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_ticket_system WHERE FROM_UNIXTIME(send_date) > NOW() - INTERVAL 24 HOUR AND send_id = '{$member_id['user_id']}'");
				$user_group[$member_id['user_group']]['max_day_ticks'] = ($user_group[$member_id['user_group']]['max_day_ticks'] == 0) ? "&#8734;" : $user_group[$member_id['user_group']]['max_day_ticks'];
				$tpl->load_template('ticket-system/table.tpl');
				$tpl->set( '{link.new}', "<a href=\"{$config['http_home_url']}{$links['new']}\">{$lng_mod['ticket_dnew']}</a>" );
				$tpl->set( '{link.view}', "<a href=\"{$config['http_home_url']}{$links['view']}\">{$lng_mod['ticket_dview']}</a>" );
				$tpl->set( '{link.main}', "<a href=\"{$config['http_home_url']}{$links['main']}\">{$lng_mod['ticket_dmain']}</a>" );
				$tpl->set( '{ticket.stats}', $max_ticket['count'] . " / " . $user_group[$member_id['user_group']]['max_day_ticks'] );
				$tpl->set( '{messages}', "");
				$tpl->compile('ticket-system/table.tpl');
				unset($max_ticket);

				$tpl->load_template('ticket-system/new.tpl');
				$path = parse_url( $config['http_home_url'] );
				$tpl->set( '{table}', $tpl->result['ticket-system/table.tpl'] );
				$tpl->set( '{product}', $product );
				$tpl->set( '[priority]', "" );
				$tpl->set( '[/priority]', "" );
				$tpl->set( '{priority}', "<select name=\"priority\">\n<option value=\"0\">" . $lng_mod['priority_low'] . "</option>\n<option value=\"1\" selected=\"selected\">" . $lng_mod['priority_norm'] . "</option>\n<option value=\"2\">" . $lng_mod['priority_high'] . "</option>\n</select>" );
				$tpl->set( '{maxsize}', formatsize($allow_filesize) );
				$tpl->set( '{extensions}', implode(",", $allow_extensions) );

				if ( $allow_recaptcha ) {
					$tpl->set( '[recaptcha]', "" );
					$tpl->set( '[/recaptcha]', "" );
					$tpl->set( '{recaptcha}', '
					<script language="javascript" type="text/javascript">
					<!--
						var RecaptchaOptions = {
							theme: \''.$config['recaptcha_theme'].'\',
							lang: \''.$lang['wysiwyg_language'].'\'
						};
					//-->
					</script>
					<script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k='.$config['recaptcha_public_key'].'"></script>' );
					$tpl->set_block( "'\\[sec_code\\](.*?)\\[/sec_code\\]'si", "" );
					$tpl->set( '{code}', "" );
				} else {
					$tpl->set_block( "'\\[recaptcha\\](.*?)\\[/recaptcha\\]'si", "" );
					$tpl->set( '{recaptcha}', "" );
					if ( $allow_captcha ) {
						$tpl->set( '[sec_code]', "" );
						$tpl->set( '[/sec_code]', "" );
						$tpl->set( '{code}', "<span id=\"dle-captcha\"><img src=\"" . $path['path'] . "engine/modules/antibot/antibot.php\" alt=\"{$lang['sec_image']}\" width=\"160\" height=\"80\" /><br /><a onclick=\"reload(); return false;\" href=\"#\">{$lang['reload_code']}</a></span>" );
					} else {
						$tpl->set_block( "'\\[sec_code\\](.*?)\\[/sec_code\\]'si", "" );
						$tpl->set( '{code}', "" );
					}
				}

				if ( $allow_file ) {
					$tpl->set( '[attach_file]', "" );
					$tpl->set( '[/attach_file]', "" );
				} else {
					$tpl->set_block( "'\\[attach_file\\](.*?)\\[/attach_file\\]'si", "" );
				}

				if( ! $is_logged ) {
					$tpl->set( '[not-logged]', "" );
					$tpl->set( '[/not-logged]', "" );
				} else
					$tpl->set_block( "'\\[not-logged\\](.*?)\\[/not-logged\\]'si", "" );

				$tpl->copy_template = "<form  method=\"post\" id=\"sendticket\" name=\"sendticket\" enctype=\"multipart/form-data\" action=\"\">\n" . $tpl->copy_template . "
					<input name=\"send\" type=\"hidden\" value=\"send\" />
					{$hiddens}
					</form>";

				$tpl->copy_template .= <<<HTML
					<script language="javascript" type="text/javascript">
					<!--
					function reload () {
						var rndval = new Date().getTime();
						document.getElementById('dle-captcha').innerHTML = '<img src="{$path['path']}engine/modules/antibot/antibot.php?rndval=' + rndval + '" width="160" height="80" alt="" /><br /><a onclick="reload(); return false;" href="#">{$lang['reload_code']}</a>';
					};
					//-->
					</script>
HTML;
				$tpl->compile( 'content' );
				$tpl->clear();
			}

		}
		unset($allow_file, $allow_filesize, $allow_extensions, $upload_dir, $date_format, $allow_mchar, $allow_captcha, $allow_recaptcha, $allow_mail, $allow_pm, $allow_groups);
	} else {
		msgbox( $lng_mod['ticket_sys'], $lng_mod['ticket_perm'] );
	}


} else if ( $doaction == "view" ) {

	if ( $is_logged && isset( $member_id ) ) {

		$page = ( isset( $_GET['page'] ) ) ? $db->safesql( intval( $_GET['page'] ) ) : 1;
		$ticket_set['order_limit'] = intval( $ticket_set['order_limit'] );

		$total = $db->super_query("SELECT COUNT(id) as c FROM " . PREFIX . "_ticket_system as t WHERE t.send_id = {$member_id['user_id']}");
		$_page['current'] = $page;
		$_page['total'] = ceil( $total['c'] / $ticket_set['order_limit'] );
		$_page['items'] = $total['c'];

		if ( $page > $_page['total'] ) {
			$tpl->load_template('ticket-system/menu.tpl');
			$tpl->set( '{link.new}', "<a href=\"{$config['http_home_url']}{$links['new']}\">{$lng_mod['ticket_dnew']}</a>" );
			$tpl->set( '{link.view}', "<a href=\"{$config['http_home_url']}{$links['view']}\">{$lng_mod['ticket_dview']}</a>" );
			$tpl->set( '{link.main}', "<a href=\"{$config['http_home_url']}{$links['main']}\">{$lng_mod['ticket_dmain']}</a>" );

			$tpl->compile("table");
			$table = $tpl->result['table'];

			$tpl->load_template('info.tpl');
			$tpl->copy_template = $table . $tpl->copy_template;
			$tpl->set( '{title}', $lng_mod['error_1'] );
			if ( $_page['items'] == 0 ) {
				$tpl->set( '{error}', $lng_mod['error_3'] );
			} else {
				$tpl->set( '{error}', $lng_mod['error_2'] );
			}
		} else {
			if ( ( $page * $ticket_set['order_limit'] ) <= ( $_page['total'] * $ticket_set['order_limit'] ) ) {
				$limit_0 = ( $page - 1 ) * $ticket_set['order_limit'];
			} else {
				$limit_0 = "0";
			}

			$prev_pages = "";
			$next_pages = "";
			$prev_link = "";
			$next_link = "";
			$curr_page = "<span>" . $page ."</span>";

			for ( $curr = 1; $curr < $_page['current']; $curr++ ) { $prev_pages .= "<a href=\"" . $config['http_home_url'] . "index.php?do=ticket&doaction=view&page=" . $curr . "\"\">" . $curr . "</a>"; }
			for ( $curr = $_page['current'] + 1; $curr <= $_page['total']; $curr++ ) { $next_pages .= "<a href=\"" . $config['http_home_url'] . "index.php?do=ticket&doaction=view&page=" . $curr . "\"\">" . $curr . "</a>"; }
			if ( ! empty( $prev_pages ) ) { $prev_link = "<a href=\"" . $config['http_home_url'] . "index.php?do=ticket&doaction=view&page=" . ( $_page['current'] - 1 ) . "/\"\">"; }
			if ( ! empty( $next_pages ) ) { $next_link = "<a href=\"" . $config['http_home_url'] . "index.php?do=ticket&doaction=view&page=" . ( $_page['current'] + 1 ) . "/\"\">"; }
			$tpl->load_template( 'navigation.tpl' );
			$tpl->set( "{pages}", $prev_pages . $curr_page . $next_pages );
			if ( ! empty( $prev_link ) ) {
				$tpl->set( "[prev-link]", $prev_link );
				$tpl->set( "[/prev-link]", "</a>" );
			} else { $tpl->set_block( "'\[prev-link\](.*?)\[/prev-link\]'si", "" ); }
			if ( ! empty( $next_link ) ) {
				$tpl->set( "[next-link]", $next_link );
				$tpl->set( "[/next-link]", "</a>" );
			} else { $tpl->set_block( "'\[next-link\](.*?)\[/next-link\]'si", "" ); }
			$tpl->compile( 'navigation' );
			$tpl->clear();
			if( $config['allow_alt_url'] == "no" ) {
				$tpl->result['navigation'] = preg_replace( "#requests\/page\/([0-9]+)\/([A-Za-z]+)\/([A-Za-z]+)(/?)#", "index.php?do=film-requests&page=$1&order=$2&by=$3", $tpl->result['navigation'] );
				$tpl->result['navigation'] = preg_replace( "#requests\/page\/([0-9]+)(/?)#", "index.php?do=film-requests&page=$1", $tpl->result['navigation'] );
			}
			// Navigation - end

			$tpl->load_template('ticket-system/row.tpl');
			$identy = 0;
			$sql = $db->query("
				SELECT
					t.id as tid, t.send_id, t.cat_id, t.send_date, t.send_name, t.send_ip, t.priority, t.message, t.filelink, t.active,
					a.id as aid, a.resp_id as aresp_id, a.resp_date as aresp_date, a.resp_name as aresp_name, a.message as amessage,
					c.cat_name
				FROM " . PREFIX . "_ticket_system as t
				INNER JOIN " . PREFIX . "_ticket_system_cats as c ON t.cat_id = c.cat_id
				LEFT OUTER JOIN " . PREFIX . "_ticket_system_ans as a ON t.id = a.id
				WHERE t.send_id = {$member_id['user_id']}
				ORDER BY t.send_date
				LIMIT {$limit_0},{$ticket_set['order_limit']}
			");

			while ($row = $db->get_row($sql)) {
				// user
				$tpl->set( '{user.group-name}', $user_group[$member_id['user_group']]['group_name'] );
				$tpl->set( '{user.group-icon}', str_replace("{THEME}", "/templates/" . $config['skin'], $user_group[$member_id['user_group']]['icon']) );
				$tpl->set( '{user.news-num}', $member_id['news_num'] );
				$tpl->set( '{user.comm-num}', $member_id['comm_num'] );
				if ( empty($member_id['foto']) ) {
					$tpl->set( '{user.foto}', $config['http_home_url'] . "templates/" . $config['skin'] . "/dleimages/noavatar.png" );
				} else {
					$tpl->set( '{user.foto}', "/uploads/fotos/" . $member_id['foto'] );
				}
				// user
				// sender
				$tpl->set( '{sender.id}', $member_id['user_id'] );
				$tpl->set( '{sender.name}', urlencode( $member_id['name'] ) );
				$tpl->set( '{sender.ip}', $row['send_ip'] );
				$tpl->set( '{sender.link}', "onclick=\"ShowProfile('" . htmlspecialchars( urlencode( $member_id['name'] ), ENT_QUOTES, $config['charset'] ) . "', '" . $links['user'] . "" . urlencode( $member_id['name'] ) . "/', '1'); return false;\" href=\"" . $links['user'] . "" . urlencode( $member_id['name'] ) . "/\"" );
				// sender
				// ticket
				$tpl->set( '{ticket.id}', $row['tid'] );
				$tpl->set( '{ticket.date}', langdate($date_format, $row['send_date']) );
				$tpl->set( '{ticket.pri}', $row['priority'] );
				$tpl->set( '{ticket.cat}', $row['cat_name'] );
				$tpl->set( '{ticket.text}', $row['message'] );
				if( empty($row['filelink']) ) {
					$tpl->set_block( "'\\[file\\](.*?)\\[/file\\]'si", "" );
				} else {
					$tpl->set( '[file]', "" );
					$tpl->set( '[/file]', "" );
					$tpl->set( '{ticket.file}', $row['filelink'] );
				}
				// ticket
				// responder
				$tpl->set( '{resp.id}', $row['aresp_id'] );
				$tpl->set( '{resp.date}', langdate($date_format, $row['aresp_date']) );
				$tpl->set( '{resp.name}', urlencode( $row['aresp_name'] ) );
				$tpl->set( '{resp.link}', "onclick=\"ShowProfile('" . htmlspecialchars( urlencode( $row['aresp_name'] ), ENT_QUOTES, $config['charset'] ) . "', '" . $links['user'] . "" . urlencode( $row['aresp_name'] ) . "/', '1'); return false;\" href=\"" . $links['user'] . "" . urlencode( $row['aresp_name'] ) . "/\"" );
				$tpl->set( '{resp.text}', $row['amessage'] );
				// responder

				if( !empty($row['amessage']) ) {
					$tpl->set( '[response]', "" );
					$tpl->set( '[/response]', "" );
					$tpl->set_block( "'\\[not-response\\](.*?)\\[/not-response\\]'si", "" );
				} else {
					$tpl->set( '[not-response]', "" );
					$tpl->set( '[/not-response]', "" );
					$tpl->set_block( "'\\[response\\](.*?)\\[/response\\]'si", "" );
				}

				if( !empty($row['active']) ) {
					$tpl->set( '[active]', "" );
					$tpl->set( '[/active]', "" );
					$tpl->set_block( "'\\[not-active\\](.*?)\\[/not-active\\]'si", "" );
				} else {
					$tpl->set( '[not-active]', "" );
					$tpl->set( '[/not-active]', "" );
					$tpl->set_block( "'\\[active\\](.*?)\\[/active\\]'si", "" );
				}
				$tpl->set( '{identy.id}', $identy );
				if ( $identy + 1 == $ticket_set['order_limit'] OR ( ( $_page['current'] - 1 ) * $ticket_set['order_limit'] + $identy + 1 ) == $_page['items'] ) {
					$tpl->set( "{navigation}", $tpl->result['navigation'] );
				} else {
					$tpl->set( "{navigation}", "" );
				}
				$tpl->compile('ticket-system/row.tpl');
				$identy++;
			}

			$max_ticket = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_ticket_system WHERE FROM_UNIXTIME(send_date) > NOW() - INTERVAL 24 HOUR AND send_id = '{$member_id['user_id']}'");
			$user_group[$member_id['user_group']]['max_day_ticks'] = ($user_group[$member_id['user_group']]['max_day_ticks'] == 0) ? "&#8734;" : $user_group[$member_id['user_group']]['max_day_ticks'];

			$tpl->load_template('ticket-system/table.tpl');

			$tpl->set( '{link.new}', "<a href=\"{$config['http_home_url']}{$links['new']}\">{$lng_mod['ticket_dnew']}</a>" );
			$tpl->set( '{link.view}', "<a href=\"{$config['http_home_url']}{$links['view']}\">{$lng_mod['ticket_dview']}</a>" );
			$tpl->set( '{link.main}', "<a href=\"{$config['http_home_url']}{$links['main']}\">{$lng_mod['ticket_dmain']}</a>" );
			$tpl->set( '{ticket.stats}', $max_ticket['count'] . " / " . $user_group[$member_id['user_group']]['max_day_ticks'] );

			if ($identy == 0) {
				msgbox( $lng_mod['ticket_sys'], "{$lng_mod['ticket_none']}");
				$tpl->set( '{messages}', "");
			} else {
				$tpl->set( '{messages}', $tpl->result['ticket-system/row.tpl'] );
			}

			unset($max_ticket);
		}
		$tpl->compile('content');
		$db->free();
	}

} else if ( $doaction == "main" || $doaction == "") {

	$max_ticket = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_ticket_system WHERE FROM_UNIXTIME(send_date) > NOW() - INTERVAL 24 HOUR AND send_id = '{$member_id['user_id']}'");
	$user_group[$member_id['user_group']]['max_day_ticks'] = ($user_group[$member_id['user_group']]['max_day_ticks'] == 0) ? "&#8734;" : $user_group[$member_id['user_group']]['max_day_ticks'];

	$tpl->load_template('ticket-system/table.tpl');

	$tpl->set( '{link.new}', "<a href=\"{$config['http_home_url']}{$links['new']}\">{$lng_mod['ticket_dnew']}</a>" );
	$tpl->set( '{link.view}', "<a href=\"{$config['http_home_url']}{$links['view']}\">{$lng_mod['ticket_dview']}</a>" );
	$tpl->set( '{link.main}', "<a href=\"{$config['http_home_url']}{$links['main']}\">{$lng_mod['ticket_dmain']}</a>" );
	$tpl->set( '{ticket.stats}', $max_ticket['count'] . " / " . $user_group[$member_id['user_group']]['max_day_ticks'] );
	$tpl->set( '{messages}', "");
	unset($max_ticket);

	$tpl->compile('content');
	$db->free();
} else {
	header( "Location: ".$_SERVER['PHP_SELF'] );
}
?>