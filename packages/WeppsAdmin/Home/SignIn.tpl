<div class="w_flex_11 way">
	<ul class="w_list w_flex w_flex_row w_flex_start">
		<li><a href="/_wepps/">Главная</a></li>
		<li>Авторизация</li>
	</ul>
</div>
<div class="w_flex w_flex_row w_flex_row_str">
	<div class="w_flex_11 w_flex w_flex_row w_flex_center centercontent">
		<div class="w_flex_13 w_flex_23_view_medium w_flex_11_view_small w_padding w_bg_silver w_rounded">
			<h2>{$content.Name}</h2>
			<form class="list-data w_flex w_flex_row" action="javascript:formWepps.send('sign-in','sign-in-form','/packages/WeppsAdmin/Admin/Request.php')"
				id="sign-in-form">
				<div class="w_flex_11 w_flex w_flex_row w_flex_start w_flex_margin">
					<label class="w_label w_input w_flex_11"><input type="text" name="login"/></label>
					<label class="w_label w_input w_flex_11"><input type="password" name="password"/></label>
					<label class="w_label w_button"><input type="submit" value="Войти"/></label>
				</div>
			</form>
		</div>
	</div>
</div>
