<div class="bcomment" id="t{ticket.id}">
	<div class="dtop">
		<div class="lcol"><span><img src="{user.foto}" alt=""/></span></div>
		<div class="rcol">
			<span class="reply"></span>
			<ul class="reset">
				<li><h4><a {sender.link}>{sender.name}</a></h4></li>
				<li>{ticket.date}</li>
			</ul>
			<ul class="cmsep reset">
				<li>Grubu: {user.group-name}</li>
				[not-active]
				<li>Cevap Tarihi: [response]{resp.date}[/response][not-response]<i>Bekliyor...</i>[/not-response]</li>
				<li>Cevaplayan: [response]<a {resp.link}>{resp.name}</a>[/response][not-response]<i>Bekliyor...</i>[/not-response]</li>
				[/not-active]
			</ul>
		</div>
		<div class="clr"></div>
	</div>
	<div class="cominfo"><div class="dpad">
		[not-group=5]
		<div class="comedit">
			<div class="selectmass"></div>
			<ul class="reset">
				[file]<li><a href="{ticket.file}">Dosyayı İndir</a></li>[/file]
				[response][not-active]<li><a href="javascript:Toggle('{ticket.id}-{identy.id}');">Cevabı Göster / Gizle</a></li>[/not-active][/response]
				[active]<li><a href="javascript:TActions('{ticket.id}', 'toggletic');" id="a{ticket.id}">Kapat</a></li>[/active]
				[not-active]<li><a href="javascript:TActions('{ticket.id}', 'toggletic');" id="a{ticket.id}">Aç</a></li>[/not-active]
				<li><a href="javascript:TActions('{ticket.id}', 'deltic');">Sil</a></li>
			</ul>
		</div>
		[/not-group]
		<ul class="cominfo reset">
			<li>Ticket Durumu: [active]<img src="{THEME}/images/online.png" style="vertical-align: middle;" title="Aktif ticket" alt="Aktif ticket" />[/active][not-active]<img src="{THEME}/images/offline.png" style="vertical-align: middle;" title="Pasif ticket" alt="Pasif ticket" />[/not-active]</li>
			<li>Yorumları: {user.comm-num}</li>
			<li>Makaleleri: {user.news-num}</li>
		</ul>
	</div>
	<span class="thide">^</span>
	</div>
	<div class="dcont">
		<h3 style="margin-bottom: 0.4em;" class="[active]pri{ticket.pri}[/active][not-active]passive[/not-active]" id="h{ticket.id}">Departman : {ticket.cat}</h3>
		<br clear="all" />
		{ticket.text}
		<br clear="all" />
		[not-active]
		[response]
		<div id="resp-{ticket.id}-{identy.id}"><br />
			<b><i>{resp.name}</i> : </b><br />
			{resp.text}
		</div>
		[/response]
		[/not-active]
		<div class="clr"></div>
	</div>
</div>
{navigation}
