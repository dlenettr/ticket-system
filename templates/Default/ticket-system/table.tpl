<style>
.pri0 {
	background-color:#6F8F52;
	color: #FFF;
}
.pri1 {
	background-color:#FBEC88;
	color: #363636;
}
.pri2 {
	background-color: #CD0A0A;
	color: #FFF;
}
.active {
	background-color:#259AC3;
}
.passive {
	background-color:#FFFFFF;
}
.passive, .active, .pri0, .pri1, .pri2 {
	border-radius: 5px;
	padding: 5px;
	display: inline-flex;
}
.clr {
	clear: both;
}
</style>
<script type="text/javascript">
	function Toggle( id ) {
		$("#resp-" + id).toggle("show");
	}
	function TActions( id, type ) {
		ShowLoading('');
		$.post( dle_root + 'engine/ajax/ticket-system-ajax.php', { tid: id, action: type },
			function(data) {
				if (data) {
					if (type == 'deltic') {
						if (data == 'ok') $('#t' + id ).hide('explode');
					} else if (type == 'toggletic') {
						if (data == 'act') {
							ShowLoading('Açılıyor...');
							$('#a' + id ).html('Kapat');
							$('#h' + id ).removeClass('passive').addClass('active');
						} else {
							ShowLoading('Kapatılıyor...');
							$('#a' + id ).html('Aç');
							$('#h' + id ).removeClass('pri0 pri1 pri2').addClass('passive');
						}
					}
				} else {
					DLEalert(data, dle_info);
				}
			}
		);
		HideLoading();
	}
</script>

<div class="pm_status" style="height:50px;width:98%;margin:5px;">
	<div class="pm_status_head">Ticket Sistemi</div>
	<div class="pm_status_content">
		{link.main} | {link.new} | {link.view}
	</div>
</div>
<div class="clr"></div>

{messages}