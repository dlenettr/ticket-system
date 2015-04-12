{table}<br />
<div class="pheading"><h2>Yeni Destek Talebi</h2></div>
<div class="baseform">
	<table class="tableform">
		<tr>
			<td class="label">
				Kullandığınız Ürün:<span class="impot">*</span>
			</td>
			<td>{product}</td>
		</tr>
		[priority]
		<tr>
			<td class="label">
				Aciliyet:<span class="impot">*</span>
			</td>
			<td>{priority}</td>
		</tr>
		[/priority]
		<tr>
			<td class="label" valign="top">
				Mesajınız:
			</td>
			<td><textarea name="message" style="width: 380px; height: 160px" class="f_textarea" /></textarea></td>
		</tr>
      [attach_file]
		<tr>
			<td class="label">
				&nbsp;&nbsp;Dosya:
			</td>
			<td><input type="file" name="attachfile" id="attachfile" style="width:300px" class="f_input" />&nbsp;&nbsp;(Max : <u>{maxsize}</u>, Tip : <i>{extensions}</i>)</td>
		</tr>
		[/attach_file]
		[sec_code]
		<tr>
			<td class="label">
				Kodu girin:<span class="impot">*</span>
			</td>
			<td>
				<div>{code}</div>
				<div><input type="text" maxlength="45" name="sec_code" style="width:115px" class="f_input" /></div>
			</td>
		</tr>
		[/sec_code]
		[recaptcha]
		<tr>
			<td class="label">
				Resimde görünen, iki kelimeyi girin:<span class="impot">*</span>
			</td>
			<td>
				<div>{recaptcha}</div>
			</td>
		</tr>
		[/recaptcha]
	</table>
	<div class="fieldsubmit">
		<button name="send_btn" class="fbutton" type="submit"><span>Gönder</span></button>
	</div>
</div>