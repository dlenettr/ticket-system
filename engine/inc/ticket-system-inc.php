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

if (!defined('DATALIFEENGINE') OR !defined('LOGGED_IN')) {
	die("Hacking attempt!");
}

defined('DATALIFEENGINE',true);

// Module attributes
$GLOBALS['module'] = array(
	'id'		=> "ticket-system-inc",
	'name'		=> "Ticket System",
	'config'	=> "/data/ticket-system-config.php",
	'setname'	=> "ticket_set",
	'lang'		=> "ticket-system-lang.lng"
);
// Module attributes

require_once ( ENGINE_DIR . $GLOBALS['module']['config'] );
require_once ENGINE_DIR . "/data/config.php";
require_once ENGINE_DIR . "/classes/mysql.php";
require_once ENGINE_DIR . "/data/dbconfig.php";
require_once ( ROOT_DIR . "/language/" . $config['langs'] . "/" . $GLOBALS['module']['lang'] );
$setting = &$$GLOBALS['module']['setname'];

if ( ! is_writable( ENGINE_DIR . $GLOBALS['module']['config'] ) ) {
	$lang['stat_system'] = str_replace("{file}", "engine/" . $GLOBALS['module']['config'], $lang['stat_system']);
	$fail = "<div class=\"ui-state-error ui-corner-all\" style=\"padding:10px;\">{$lang['stat_system']}</div>";
} else {
	$fail = "";
}

function en_serialize( $value ) { return str_replace( '"', "'", serialize( $value ) ); }
function de_serialize( $value ) { return unserialize( str_replace("'", '"', $value ) ); }

if ($_REQUEST['action'] == "save") {
	if ($member_id['user_group'] != 1) { msg("error", $lang['opt_denied'], $lang['opt_denied']); }
	if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) {
		die( "Hacking attempt! User not found" );
	}
	$save_con = $_POST['save'];

	$int_fields = array( "allow_mchar", "allow_file", "allow_filesize", "allow_filesize_br", "allow_filesize_def" );
	foreach( $int_fields as $int_field ) { $save_con[ $int_field ] = intval( $save_con[ $int_field ] ); }

	$save_con['module_ver'] = "1.2";
	$handler = fopen( ENGINE_DIR . $GLOBALS['module']['config'], "w");
	fwrite($handler, "<?php \n\$" . $GLOBALS['module']['setname'] . " = array (\n");
	foreach($save_con as $name => $value) {
		$value = (is_array($value)) ? en_serialize($value) : $db->safesql( $value );
		fwrite($handler, "\t'{$name}' => \"{$value}\",\n");
	}
	fwrite($handler, ");\n?>");
	fclose($handler);
	$find = array ("<", ">");
	$replace = array ("&lt;", "&gt;");
	$ticket_ma = $db->safesql(str_replace( $find, $replace, $_POST['ticket_ma'] ) );
	$ticket_pm = $db->safesql(str_replace( $find, $replace, $_POST['ticket_pm'] ) );
	$db->query( "UPDATE " . PREFIX . "_email SET template='{$ticket_ma}' WHERE name='ticket_ma'" );
	$db->query( "UPDATE " . PREFIX . "_email SET template='{$ticket_pm}' WHERE name='ticket_pm'" );
	msg("info", $lang['opt_sysok'], "{$lang['opt_sysok_1']}<br /><br /><a href='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&amp;action=settings'>{$lang['db_prev']}</a>");
}

if ($_REQUEST['action'] == "save_cat") {
	if ($member_id['user_group'] != 1) { msg("error", $lang['opt_denied'], $lang['opt_denied']); }
	$_POST['author_id'] = $db->safesql( $_POST['author_id'] );
	$auth_id = $db->super_query( "SELECT user_id FROM " . PREFIX . "_users WHERE name = '{$_POST['author_id']}'" );
	$db->query( "INSERT INTO " . PREFIX . "_ticket_system_cats (cat_name, author_id) VALUES ('".$db->safesql($_POST['cat_name'] )."',".$auth_id['user_id'].") ");
	msg("info", $lang['opt_sysok'], "{$lang['opt_sysok_1']}<br /><br /><a href='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&amp;action=catalog'>{$lang['db_prev']}</a>");
}
if ($_REQUEST['action'] == "del_cat") {
	if ($member_id['user_group'] != 1) { msg("error", $lang['opt_denied'], $lang['opt_denied']); }
	$db->query( "INSERT INTO " . PREFIX . "_ticket_system_cats (cat_name, author_id) VALUES ('".$db->safesql($_POST['cat_name'] )."',".intval( $db->safesql($_POST['author_id'] ) ).") ");
	msg("info", $lang['opt_sysok'], "{$lang['opt_sysok_1']}<br /><br /><a href='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&amp;action=catalog'>{$lang['db_prev']}</a>");
}


function showRow( $title = "", $description = "", $field = "", $hide = false, $id = "" ) {
	$hide = ($hide) ? " style=\"display:none;\"" : "";
	$id = ($id != "") ? " id=\"{$id}\"" : "";
	echo "<tr{$hide}{$id}>
		<td class=\"col-xs-10 col-sm-6 col-md-7\"><h6>{$title}</h6><span class=\"note large\">{$description}</span></td>
		<td class=\"col-xs-2 col-md-5 settingstd\">{$field}</td>
	</tr>";
}


function makeDropDown( $options, $name, $selected ) {
	$output = "<select class=\"uniform\" type=\"settings\" style=\"min-width:100px;\" name=\"{$name}\" id=\"{$name}\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"{$value}\"";
		if ( $selected == $value ) {
			$output .= " selected ";
			$tname = $value;
		}
		$output .= ">{$description}</option>\n";
	}
	$output .= "</select>";
	return $output;
	unset( $output );
}


function makeButton( $name, $selected ) {
	$selected = $selected ? "checked" : "";
	return "<input class=\"iButton-icons-tab\" type=\"checkbox\" name=\"{$name}\" value=\"1\" {$selected}>";
}


function makeMultiSelect($options, $name, $selected, $class = '') {
	$size = (count($options) >= 6) ? 6 : count($options);
	$class = ( $class != '' ) ? " class=\"{$class}\"" : "";
	$output = "<select{$class} size=\"".$size."\" style=\"width:240px;\" name=\"{$name}[]\" multiple=\"multiple\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"{$value}\"";
		for ($x = 0; $x <= count($selected); $x++) {
			if ($value == $selected[$x]) $output .= " selected ";
		}
		$output .= ">{$description}</option>\n";
	}
	$output .= "</select>";
	return $output;
}


function GetGroups( $guest = false ) {
	global $db, $user_group;
	$result = array();
	if ( isset( $user_group ) and is_array( $user_group ) ) {
		foreach ( $user_group as $row ) { $result[ $row['id'] ] =  $row['group_name']; }
	} else {
		$db->query( "SELECT id, group_name FROM " . PREFIX . "_usergroups" );
		while ( $row = $db->get_row() ) { $result[ $row['id'] ] =  $row['group_name']; }
	}
	if ( ! $guest ) unset( $result['5'] );
	unset($id, $name);
	return $result;
}


function mainTable_head( $title, $right = "", $id = false ) {
	if ( $id ) {
		$id = " id=\"{$id}\"";
		$style = " style=\"display:none\"";
	} else { $style = ""; }
	echo <<< HTML
	<div class="box">
		<div class="box-header">
			<div class="title">{$title}</div>
			<ul class="box-toolbar">
				<li class="toolbar-link">
					{$right}
				</li>
			</ul>
		</div>
		<div class="box-content">
			<table class="table table-normal">
HTML;
}


function mainTable_foot( ) {
	echo <<< HTML
			</table>
		</div>
	</div>
HTML;
}


function openTab( $id, $active = false ) {
	$active = ( $active ) ? " active" : "";
	echo <<<HTML
<div class="tab-pane{$active}" id="tab{$id}" >
	<table class="table table-normal table-hover settingsgr">
HTML;
}


function closeTab( ) {
	echo <<<HTML
	</table>
</div>
HTML;
}


function navigation_bar( ) {
	global $lng_inc, $module, $db;
	$ncount = "";
	$active = $db->super_query("SELECT COUNT(id) as c FROM ".USERPREFIX."_ticket_system WHERE active = 1");
	if ( $active['c'] > 0 ) { $ncount = "<span class=\"triangle-button red\"><i>{$active['c']}</i></span>"; }
	echo <<< HTML
	<div class="box">
		<div class="box-content">
			<div class="row box-section">
				<div class="action-nav-normal action-nav-line">
					<div class="row action-nav-row">
						<div class="col-sm-3 action-nav-button">
							<a data-original-title="{$lng_inc['00']}" href="javascript:void();" onClick="window.location='{$PHP_SELF}?mod={$GLOBALS['module']['id']}'" class="tip" title="">
								<i class="icon-home"></i><span>{$lng_inc['00']}</span>
							</a>
						</div>
						<div class="col-sm-2 action-nav-button">
							<a data-original-title="{$lng_inc['01']}" href="javascript:void();" onClick="window.location='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&amp;action=ntickets'" class="tip" title="">
								<i class="icon-bookmark"></i><span>{$lng_inc['01']}</span>
							</a>
							{$ncount}
						</div>
						<div class="col-sm-2 action-nav-button">
							<a data-original-title="{$lng_inc['04']}" href="javascript:void();" onClick="window.location='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&amp;action=tickets'" class="tip" title="">
								<i class="icon-bookmark-empty"></i><span>{$lng_inc['03']}</span>
							</a>
						</div>
						<div class="col-sm-2 action-nav-button">
							<a data-original-title="{$lng_inc['06']}" href="javascript:void();" onClick="window.location='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&amp;action=catalog'" class="tip" title="">
								<i class="icon-folder-close-alt"></i><span>{$lng_inc['05']}</span>
							</a>
							<span class="triangle-button green"><i class="icon-plus"></i></span>
						</div>
						<div class="col-sm-3 action-nav-button">
							<a data-original-title="{$lng_inc['08']}" href="javascript:void();" onClick="window.location='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&amp;action=settings'" class="tip" title="">
								<i class="icon-wrench"></i><span>{$lng_inc['07']}</span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
HTML;
}

//$js_array[] = "engine/skins/autocomplete.js";

echoheader( "<i class=\"icon-tag\"></i> Ticket System v1.4", $lng_inc['93'] );

echo <<< HTML
<style>
.controls { display: none; }
.ticket_status { padding: 3px 10px; font-size: 12px; border-radius: 5px; }
</style>
<script type="text/javascript">
	var dle_root       = '';
	var dle_p_send     = '{$lng_inc['09']}';
	var dle_p_send_ok  = '{$lng_inc['10']}';
	var dle_p_del_ok   = '{$lng_inc['11']}';
	var dle_u_del_ok   = '{$lng_inc['12']}';
	var dle_title  	   = '{$lng_inc['13']}';
	var dle_info       = '{$lng_inc['14']}';
	$(document).ready(function( ) {
		$("li.ticket").hover(function( ) {
			$(this).find("div.controls").stop().slideDown();
		}, function( ) {
			$(this).find("div.controls").stop().slideUp();
		});


		$("input[name='save[allow_extension_def]']").on('change', function( ) {
			if ( $(this).attr("checked") ) {
				$("input[name='save[allow_extensions]']").prop("readonly", true).addClass("readonly");
			} else {
				$("input[name='save[allow_extensions]']").prop("readonly", false).removeClass("readonly");
			}
		});
		$("input[name='save[allow_filesize_def]']").on('change', function( ) {
			if ( $(this).attr("checked") ) {
				$("select[name='save[allow_filesize_br]']").prop("readonly", true).addClass("readonly");
				$("input[name='save[allow_filesize]']").prop("readonly", true).addClass("readonly");
			} else {
				$("select[name='save[allow_filesize_br]']").prop("readonly", false).removeClass("readonly");
				$("input[name='save[allow_filesize]']").prop("readonly", false).removeClass("readonly");
			}
		});
		$('.usergroup').chosen({ allow_single_deselect: true, no_results_text: '{$lng_inc['88']}', width: '100%' });
		$("input[name='save[date_format]']").bind('keydown', function(event) {
			var format = $(this).val();
			//$("span[id='date_format']").html( curr );
		});
	});

	$(function(){
		function split( val ) {
			return val.split( /,\s*/ );
		}
		function extractLast( term ) {
			return split( term ).pop();
		}
		$( '#name' ).autocomplete({
			source: function( request, response ) {
				$.getJSON( dle_root + 'engine/ajax/ticket-system-ajax.php', {
					term: extractLast( request.term )
				}, response );
			},
			search: function( ) {
				var term = extractLast( this.value );
				if ( term.length < 3 ) {
					return false;
				}
			},
			focus: function( ) {
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				terms.pop();
				terms.push( ui.item.value );
				terms.push( '' );
				this.value = terms.join( '' );
				return false;
			}
		});
	});

	var is_open = false;
	function ShowOrHidePanel( id ) {
		if ( is_open == false ) {
			$("#"+id).slideDown();
			$('.chzn-container').css({'width': '350px'});
			is_open = true;
		} else {
			$("#"+id).slideUp();
			is_open = false;
		}
	}


	function DelTicket( tid, action ){
		$.post( dle_root + 'engine/ajax/ticket-system-ajax.php', { tid: tid, action: action },
			function(data){
				if (data == 'ok') {
					$('#tic_'+tid.toString()).hide('explode');
					DLEalert(dle_p_del_ok, dle_info);
				} else {
					DLEalert(data, dle_info);
				}
			}
		);
	}

	function DelAnswer( aid ){
		$.post( dle_root + 'engine/ajax/ticket-system-ajax.php', { aid: aid, action: 'del' },
			function(data){
				if (data == 'ok') {
					$('#ans_'+aid.toString()).hide('explode');
					DLEalert(dle_p_del_ok, dle_info);
				} else {
					DLEalert(data, dle_info);
				}
			}
		);
	}

	function DelCat( cid ){
		$.post( dle_root + 'engine/ajax/ticket-system-ajax.php', { tid: cid, action: 'delcat' },
			function(data){
				if (data == 'ok') {
					$('#cat_'+cid.toString()).hide('explode');
					$('#cat_s'+cid.toString()).hide('explode');
					DLEalert(dle_u_del_ok, dle_info);
				} else {
					DLEalert(data, dle_info);
				}
			}
		);
	}


	function ReplyTicket( tid, suid, uid, name, action, area ){
		var b = {};
		b[dle_act_lang[3]] = function( ) {
			$(this).dialog('close');
		};
		b[dle_p_send] = function( ) {
			if ( $('#dle-promt-text').val().length < 1) {
				$('#dle-promt-text').addClass('ui-state-error');
			} else {
				var response = $('#dle-promt-text').val()
				$(this).dialog('close');
				$('#dlepopup').remove();
				$.post( dle_root + 'engine/ajax/ticket-system-ajax.php', { tid: tid, suid: suid, uid: uid, text: response, name: name, action: action },
					function(data){
						if (data == 'ok') {
							if (area != 'archive') $('#tic_'+tid.toString()).hide('blind');
							DLEalert(dle_p_send_ok, dle_info);
						} else {
							DLEalert(data, dle_info);
						}
					}
				);
			}
		};
		$('#dlepopup').remove();
		$('body').append("<div id='dlepopup' title='"+dle_title+"' style='display:none'><br /><textarea name='dle-promt-text' id='dle-promt-text' class='ui-widget-content ui-corner-all' style='width:97%;height:100px; padding: .4em;'></textarea></div>");
		$('#dlepopup').dialog({
			autoOpen: true,
			width: 600,
			dialogClass: "modalfixed",
			buttons: b
		});
		$('.modalfixed.ui-dialog').css({position:"fixed"});
		$('#dlepopup').dialog( "option", "position", ['0','0'] );
	};

</script>
HTML;

navigation_bar();


function show_products( ) {
	global $db, $lng_inc;
	$db->query( "SELECT c.cat_id, c.cat_name, c.author_id, u.user_id, u.name, u.email
				 FROM " . PREFIX . "_ticket_system_cats as c," . PREFIX . "_users as u
				 WHERE u.user_id = c.author_id" );
	echo <<< HTML
<thead>
	<tr>
		<td align="center"><b>{$lng_inc['20']}</b></td>
		<td align="left"><b>{$lng_inc['21']}</b></td>
		<td width="200" align="center"><b>{$lng_inc['22']}</b></td>
		<td align="center"><b>{$lng_inc['19']}</b></td>
		<td align="center"><b>{$lng_inc['24']}</b></td>
		<td align="center"><b>&nbsp;</b></td>
	</tr>
</thead>
HTML;
	while ( $row = $db->get_row() ) {
		echo <<< HTML
<tr id="cat_{$row['cat_id']}">
	<td width="120" align="center">{$row['cat_id']}</td>
	<td width="180" align="left">{$row['cat_name']}</td>
	<td width="85" align="center">{$row['author_id']}</td>
	<td width="180" align="center"><a onclick="ShowProfile('{$row['name']}', '{$config['http_home_url']}user/{$row['name']}/', '1'); return false;" href="{$config['http_home_url']}user/{$row['name']}/">{$row['name']}</a></td>
	<td width="220" align="center">{$row['email']}</td>
	<td width="45" align="center"><input type="button" value="{$lng_inc['26']}" onclick="DelCat('{$row['cat_id']}')" class="btn btn-red btn-sm"></td>
</tr>
HTML;
	}
	$db->free();
}


function show_templates( ) {
	global $db, $lng_inc;
	$db->query( "SELECT name, template
				 FROM " . PREFIX . "_email
				 WHERE name='ticket_ma' OR name = 'ticket_pm'"  );
	while ( $row = $db->get_row() ) {
		$$row['name'] = stripslashes( $row['template'] );
	}
	$db->free();
	echo <<< HTML
	<table class="table table-normal">
		<tr>
			<td><b>{$lng_inc['27']} :</b><br /><br/>
				<b>{%username_to%}</b> - {$lng_inc['28']}<br />
				<b>{%username_from%}</b> - {$lng_inc['29']}<br />
				<b>{%text%}</b> - {$lng_inc['30']}<br />
				<b>{%ip%}</b> - {$lng_inc['31']}<br />
				<b>{%date%}</b> - {$lng_inc['67']}<br />
				<b>{%ticket_url%}</b> - {$lng_inc['32']}<br />
			</td>
		</tr>
	</table>
	<br />
	<table class="table table-normal">
		<tr>
			<td><b>{$lng_inc['33']}</b><br/></td>
		</tr>
		<tr>
			<td>
				<textarea rows="15" style="width:98%;" name="ticket_pm">{$ticket_pm}</textarea>
			</td>
		</tr>
	</table>
	<table class="table table-normal">
		<tr>
			<td><b>{$lng_inc['34']}</b><br/></td>
		</tr>
		<tr>
			<td>
				<textarea rows="15" style="width:98%;" name="ticket_ma">{$ticket_ma}</textarea>
			</td>
		</tr>
	</table>
HTML;
}


function show_stats( ) {
	global $db, $lng_inc;
	$passive = $db->super_query("SELECT COUNT(id) as c FROM ".USERPREFIX."_ticket_system WHERE active = 0");
	$active = $db->super_query("SELECT COUNT(id) as c FROM ".USERPREFIX."_ticket_system WHERE active = 1");
	$passive = $passive['c'];$active = $active['c'];$total = $active + $passive;
	$answers = $db->super_query("SELECT COUNT(id) as c FROM ".USERPREFIX."_ticket_system_ans");
	$answers = $answers['c'];
	$db->free();
	echo <<< HTML
	<b>{$lng_inc['60']}</b> : {$passive} |
	<b>{$lng_inc['63']}</b> : {$active} |
	<b>{$lng_inc['70']}</b> : {$total} |
	<b>{$lng_inc['71']}</b> : {$answers} |
	<b>{$lng_inc['72']}</b> : {$active}
HTML;
}



function show_tickets( $active = True, $tid = False ) {
	global $db, $member_id, $lng_inc, $lng_mod;
	$uname = $member_id['name'];
	$uid = $member_id['user_id'];
	if ($tid != False AND $active == 2) {
		$WHERE = "t.id = ".intval($tid);
	} else {
		$WHERE = "t.active = ".intval($active);
	}
	$db->query("
		SELECT t.send_name, t.send_id, t.send_date, t.send_ip, t.resp_id, t.priority, t.message, t.id, t.filelink, c.cat_name
		FROM ".PREFIX."_ticket_system as t," . PREFIX . "_ticket_system_cats as c
		WHERE {$WHERE} AND t.cat_id = c.cat_id
		ORDER BY t.send_date DESC
	");
	$count = 0;
	while ($row = $db->get_row()) {
		$count++;
		$area = $active ? "main" : "archive";
		if (intval($row['priority']) == 2) {$style = "red";$color = "#eee";$name = $lng_mod['priority_high'];}
		else if (intval($row['priority']) == 1) {$style = "#FF8C00";$color = "#eee";$name = $lng_mod['priority_norm'];}
		else if (intval($row['priority']) == 0) {$style = "blue";$color = "#eee";$name = $lng_mod['priority_low'];}
		$time = langdate("j F Y - H:i", $row['send_date']);
		$filelink = (!empty($row['filelink'])) ? "&nbsp;<a href=\"".$row['filelink']."\" class=\"btn btn-sm btn-blue\">{$lng_inc['35']}</a>" : "";
		$resplink = ($row['resp_id'] == 0) ? "" : "&nbsp;<input type=\"button\" value=\"{$lng_inc['77']}\" class=\"btn btn-sm btn-gold\" onclick=\"window.location='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&action=showanswer&aid={$row['id']}'\">";
		echo <<< HTML
<li id="tic_{$row['id']}" class="gray ticket">
	<div class="info">
		<span class="name">
			{$lng_inc['90']}: <i class="icon-user"></i> <a onclick="ShowProfile('{$row['send_name']}', '{$config['http_home_url']}user/{$row['send_name']}/', '1'); return false;" href="{$config['http_home_url']}user/{$row['send_name']}/"><font color="#185AA7"><b>{$row['send_name']}</b></font></a>&nbsp;&nbsp;
			{$lng_inc['91']}: <i class="icon-arrow-right"></i> <strong class="label">{$row['cat_name']}</strong>
		</span>
		<span class="time"><i class="icon-time"></i>{$time}
			&nbsp;&nbsp;<i class="icon-arrow-right"></i> <span class="ticket_status" style="background:{$style};color:{$color}">{$name}</span>
		</span>
	</div>
	<div class="content">
		{$row['message']}
		<div class="info controls" style="text-align:right;">
			<input type="button" value="{$lng_inc['26']}" class="btn btn-sm btn-red" onclick="DelTicket('{$row['id']}','del')" />&nbsp;
			<input type="button" value="{$lng_inc['37']}" class="btn btn-sm btn-green" onclick="ReplyTicket('{$row['id']}','{$row['send_id']}','{$uid}','{$uname}','reply','{$area}')" />
			{$resplink}
			{$filelink}
		</div>
	</div>
</li>
HTML;
	}
	if ($count == 0) {
		echo <<< HTML
<li>
	<div class="info">
		{$lng_inc['02']}
	</div>
</li>
HTML;
	}
}


function show_answers( $tid = False) {
	global $db, $member_id, $lng_inc, $lng_mod;
	$uname = $member_id['name'];
	$uid = $member_id['user_id'];
	$WHERE = ($tid) ? "WHERE t.id = ".intval($tid) : "";
	$db->query("
		SELECT
			c.cat_name,
			t.id as tid, t.send_id, t.cat_id, t.send_date, t.send_name, t.send_ip, t.priority, t.filelink,
			a.id as aid, a.resp_id, a.resp_date, a.resp_name, a.message
		FROM " . PREFIX . "_ticket_system_ans as a
		LEFT JOIN " . PREFIX . "_ticket_system as t ON ( t.id = a.ticket_id )
		LEFT JOIN " . PREFIX . "_ticket_system_cats as c ON ( t.cat_id = c.cat_id )
		{$WHERE}
		ORDER BY a.resp_date DESC
	");
	$count = 0;
	while ($row = $db->get_row()) {
		$count++;
		if (intval($row['priority']) == 2) {$style = "red";$color = "#eee";$name = $lng_mod['priority_high'];}
		else if (intval($row['priority']) == 1) {$style = "#FF8C00";$color = "#eee";$name = $lng_mod['priority_norm'];}
		else if (intval($row['priority']) == 0) {$style = "blue";$color = "#eee";$name = $lng_mod['priority_low'];}
		$time = langdate("j F Y - H:i", $row['resp_date']);
		$filelink = (!empty($row['filelink'])) ? "&nbsp;<a href=\"".$row['filelink']."\" class=\"btn btn-sm btn-blue\">{$lng_inc['35']}</a>" : "";
		$ticklink = "&nbsp;<input type=\"button\" value=\"{$lng_inc['78']}\" class=\"btn btn-sm btn-gold\" onclick=\"window.location='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&action=showticket&tid={$row['tid']}'\">";

		echo <<< HTML
<li id="ans_{$row['aid']}" class="gray ticket">
	<div class="info">
		<span class="name">
			{$lng_inc['90']}: <i class="icon-user"></i> <a onclick="ShowProfile('{$row['send_name']}', '{$config['http_home_url']}user/{$row['send_name']}/', '1'); return false;" href="{$config['http_home_url']}user/{$row['send_name']}/"><font color="#185AA7"><b>{$row['send_name']}</b></font></a>&nbsp;&nbsp;
			{$lng_inc['91']}: <i class="icon-arrow-right"></i> <strong class="label">{$row['cat_name']}</strong>&nbsp;&nbsp;
			{$lng_inc['92']}: <i class="icon-share-alt"></i> <a onclick="ShowProfile('{$row['resp_name']}', '{$config['http_home_url']}user/{$row['resp_name']}/', '1'); return false;" href="{$config['http_home_url']}user/{$row['resp_name']}/"><font color="#185AA7"><b>{$row['resp_name']}</b></font></a>
		</span>
		<span class="time"><i class="icon-time"></i>{$time}
			&nbsp;&nbsp;<i class="icon-arrow-right"></i> <span class="ticket_status" style="background:{$style};color:{$color}">{$name}</span>
		</span>
	</div>
	<div class="content">
		{$row['message']}
		<div class="info controls" style="text-align:right;">
			<input type="button" value="{$lng_inc['26']}" class="btn btn-sm btn-red" onclick="DelAnswer('{$row['aid']}')" />&nbsp;
			{$resplink}
			{$filelink}
		</div>
	</div>
</li>
HTML;
	}
	if ($count == 0) {
		echo <<< HTML
<li>
	<div class="info">
		{$lng_inc['89']}
	</div>
</li>
HTML;
	}
}

if ($_REQUEST['action'] == "settings") {
	echo <<< HTML
	<form action="{$PHP_SELF}?mod={$GLOBALS['module']['id']}&action=save" name="conf" id="conf" method="post">
		<div class="box">
			<div class="box-header">
				<ul class="nav nav-tabs nav-tabs-left">
					<li class="active"><a href="#tabsettings" data-toggle="tab"><i class="icon-wrench"></i> {$lng_inc['58']}</a></li>
					<li><a href="#tabtemplates" data-toggle="tab"><i class="icon-envelope"></i> {$lng_inc['59']}</a></li>
				</ul>
			</div>
			<div class="box-content">
				<div class="tab-content">
HTML;
	openTab( "settings", $active = true );

	showRow( $lng_inc['39'], $lng_inc['40'], makeButton( "save[allow_file]", $setting['allow_file'] ) );

	showRow(
		$lng_inc['41'], $lng_inc['42'],
		"<input class=\"{$allow_filesize}\" type=\"text\" style=\"text-align: left;\" name='save[allow_filesize]' value=\"{$setting['allow_filesize']}\" size=\"6\"{$allow_filesize}>&nbsp;&nbsp;" .
		makeDropDown( array( "1" => "KB", "1024" => "MB" ), "save[allow_filesize_br]", $setting['allow_filesize_br'], $allow_filesize ) .
		"&nbsp;&nbsp;<!--input type=\"checkbox\" name=\"save[allow_filesize_def]\" id=\"save[allow_filesize_def]\"{$allow_filesize_def}>&nbsp;{$lng_inc['87']}-->"
	);

	showRow( $lng_inc['43'], $lng_inc['44'], "<input type=\"text\" style=\"text-align: left;\" name='save[upload_dir]' value=\"{$setting['upload_dir']}\" size=\"20\">" . "&nbsp;CHMOD : 777" );

	$allow_extension_def = ($setting['allow_extension_def'] == "on") ? " checked" : "";
	$allow_extensions = ($setting['allow_extension_def'] == "on") ? " readonly" : "";

	showRow( $lng_inc['45'], $lng_inc['46'], "<input class=\"{$allow_extensions}\" type=\"text\" style=\"text-align: left;\" name='save[allow_extensions]' id='save[allow_extensions]' value=\"{$setting['allow_extensions']}\" size=\"20\"{$allow_extensions}>
		&nbsp;&nbsp;<!--input type=\"checkbox\" name=\"save[allow_extension_def]\" id=\"save[allow_extension_def]\"{$allow_extension_def}>&nbsp;{$lng_inc['87']}-->" );

	showRow( $lng_inc['47'], $lng_inc['48'],
	"<input type=\"text\" style=\"text-align: left;\" name='save[allow_mchar]' value=\"{$setting['allow_mchar']}\" size=\"10\">" );

	showRow( $lng_inc['65'], $lng_inc['66'] . "<br /><a onClick=\"javascript:Help('date'); return false;\" class=\"main\" href=\"#\">{$lang[opt_sys_and]}</a>",
	"<input type=\"text\" style=\"text-align: left;\" name='save[date_format]' value=\"{$setting['date_format']}\" size=\"20\">&nbsp;&nbsp;<span id=\"date_format\"></span>" );

	showRow( $lng_inc['49'], $lng_inc['50'],  makeDropDown(array(
		"off" => "{$lng_inc['51']}",
		"on1" => $lang['opt_sys_gd2'],
		"on2" => $lang['opt_sys_recaptcha'],
	) , "save[sec_method]", $setting['sec_method']) );

	showRow(
		$lng_inc['81'],
		$lng_inc['82'],
		makeDropDown(array(
				"date"  => $lng_inc['83'],
				"priority"  => $lng_inc['84'],
				"cat_id"  => $lng_inc['85'],
				"active"  => $lng_inc['86'],
			),"save[order_col]", $setting['order_col']
		) . "&nbsp;&nbsp;" .
		makeDropDown(array(
				"ASC"  => $lng_inc['79'],
				"DESC" => $lng_inc['80'],
			),"save[order_by]", $setting['order_by']
		) . "&nbsp;&nbsp;" .
		"<input type=\"text\" style=\"text-align: left\" size=\"2\" name=\"save[order_limit]\" value=\"{$setting['order_limit']}\">"
	);

	showRow(
		$lng_inc['52'], $lng_inc['53'],
		makeMultiSelect(
			GetGroups(),
			"save[allowed_groups]", de_serialize( $setting['allowed_groups'] ),
			"usergroup"
		)
	);

	showRow(
		$lng_inc['57'], $lng_inc['55'],
		makeMultiSelect(
			array(
				"pm" => $lng_inc['56'],
				"mail" => $lng_inc['54']
			),
			"save[allowed_notify]", de_serialize( $setting['allowed_notify'] ),
			"usergroup"
		)
	);
	closeTab();

	openTab( "templates", $active = false );
	show_templates();
	closeTab();

	echo <<< HTML
			</div>
			<div class="padded">
				<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
				<input type="submit" class="btn btn-green" value="{$lang['user_save']}">&nbsp;&nbsp;
				<input type="button" value="{$lang['user_brestore']}" class="btn btn-gold" onclick="window.location='{$PHP_SELF}?mod={$MNAME}'">
			</div>
		</form>
	</div>
HTML;

	mainTable_foot();
}


else if ($_REQUEST['action'] == "catalog") {
	echo <<< HTML
<div style="display:none" id="addcatalog">
	<div class="box">
		<div class="box-header">
			<div class="title">{$lng_inc['61']}</div>
			<ul class="box-toolbar">
				<li class="toolbar-link">
					<a href="javascript:ShowOrHidePanel('addcatalog');"><i class="icon-plus"></i> {$lng_inc['61']}</a>
				</li>
			</ul>
		</div>
		<div class="box-content">
			<table class="table table-normal">
				<form action="{$PHP_SELF}?mod={$GLOBALS['module']['id']}&action=save_cat" name="conf" id="conf" method="post">
					<table class="table table-normal">
						<tr>
							<td>
								{$lng_inc['18']}
							</td>
							<td>
								<input type="text" name="cat_name" size="40" />
								<button class="btn btn-sm btn-black tip" title="{$lng_inc['15']}">?</button>
							</td>
						</tr>
						<tr>
							<td>
								{$lng_inc['19']}
							</td>
							<td>
								<input type="text" name="author_id" id="name" size="40" autocomplete="on" />
								<button class="btn btn-sm btn-black tip" title="{$lng_inc['16']}">?</button>
							</td>
						</tr>
						<tr>
							<td colspan="2" style="padding:4px;">
								<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
								<input type="submit" class="btn btn-green btn-sm" value="{$lng_inc['17']}">
							</td>
						</tr>
					</table>
				</form>
			</table>
		</div>
	</div>
</div>

<form action="{$PHP_SELF}?mod=autotags&action=save" name="conf" id="conf" method="post">
<div class="box">
	<div class="box-header">
		<div class="title">{$lng_inc['06']}</div>
		<ul class="box-toolbar">
			<li class="toolbar-link">
				<a href="javascript:ShowOrHidePanel('addcatalog');"><i class="icon-plus"></i> {$lng_inc['61']}</a>
			</li>
		</ul>
	</div>
	<div class="box-content">
		<table class="table table-normal">
HTML;

			show_products();

echo <<<HTML
		</table>
	</div>
</div>
</form>
HTML;

}


else if ($_REQUEST['action'] == "tickets") {
	echo <<< HTML
	<div class="box">
		<div class="box-header">
			<div class="title">{$lng_inc['60']}</div>
			<ul class="box-toolbar">
				<li class="toolbar-link">
					<a href="javascript:void();" onClick="window.location='{$PHP_SELF}?mod={$GLOBALS['module']['id']}&amp;action=allanswers'"><i class="icon-folder-open-alt"></i> {$lng_inc['76']}</a>
				</li>
			</ul>
		</div>
		<div class="box-content">
			<div class="row box-section">
				<ul class="chat-box timeline">
HTML;
					show_tickets( $active = False, $tid = False );

	echo <<< HTML
			</ul>
		</div>
	</div>
</div>
HTML;


}


else if ($_REQUEST['action'] == "ntickets") {
	echo <<< HTML
	<div class="box">
		<div class="box-header">
			<div class="title">{$lng_inc['63']}</div>
		</div>
		<div class="box-content">
			<div class="row box-section">
				<ul class="chat-box timeline">
HTML;
					show_tickets( );

	echo <<< HTML
			</ul>
		</div>
	</div>
</div>
HTML;

}


else if ($_REQUEST['action'] == "showticket" AND !empty( $_REQUEST['tid'] )) {
	$_REQUEST['tid'] = $db->safesql( $_REQUEST['tid'] );

	echo <<< HTML
	<div class="box">
		<div class="box-header">
			<div class="title">Ticket</div>
		</div>
		<div class="box-content">
			<div class="row box-section">
				<ul class="chat-box timeline">
HTML;
					show_tickets( $active = 2, $tid = $_REQUEST['tid'] );

	echo <<< HTML
			</ul>
		</div>
	</div>
</div>
HTML;

}

else if ($_REQUEST['action'] == "showanswer" AND !empty( $_REQUEST['aid'] ) ) {
	$_REQUEST['aid'] = $db->safesql( $_REQUEST['aid'] );
	echo <<< HTML
	<div class="box">
		<div class="box-header">
			<div class="title">{$lng_inc['77']}</div>
		</div>
		<div class="box-content">
			<div class="row box-section">
				<ul class="chat-box timeline">
HTML;
					show_answers( $_REQUEST['aid'] );

	echo <<< HTML
			</ul>
		</div>
	</div>
</div>
HTML;

}


else if ($_REQUEST['action'] == "allanswers") {
	echo <<< HTML
	<div class="box">
		<div class="box-header">
			<div class="title">{$lng_inc['74']}</div>
		</div>
		<div class="box-content">
			<div class="row box-section">
				<ul class="chat-box timeline">
HTML;
					show_answers();

	echo <<< HTML
			</ul>
		</div>
	</div>
</div>
HTML;

}



else {

echo <<< HTML
<div class="box">
	<div class="box-header">
		<div class="title">{$lng_inc['63']}</div>
	</div>
	<div class="box-content">
		<div class="row box-section">
			<ul class="chat-box timeline">
HTML;
			show_tickets( $active = True, $tid = False );

echo <<< HTML
			</ul>
		</div>
		<div class="row box-section">
HTML;
		show_stats();

echo <<< HTML
		</div>
	</div>
</div>
HTML;

}
echofooter();

?>