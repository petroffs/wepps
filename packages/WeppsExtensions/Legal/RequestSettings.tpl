<h1>Управление cookie-файлами</h1>
<p>Мы используем файлы cookie для работы сайта и аналитики. Вы можете выбрать, какие виды cookie разрешить. Необходимые
	cookie обязательны для работы сайта. Подробнее читайте в нашей <a href='/legal/cookies.html'>Политике использования
		cookie-файлов</a>.</p>
<form action="javascript:void(0)" id="legal-form" class="w_form" autocomplete="off">
	<fieldset>
		<section>
			<div class="title">Необходимые cookie (Обязательные для работы сайта)</div>
			<label class="w_label w_checkbox">
				<input type="checkbox" name="default" value="true" checked="checked"
					disabled="disabled"><span>Необходимые cookie</span></label>
			<div class="text">Эти cookie необходимы для базовой функциональности сайта. Они обеспечивают безопасность и
				основные функции, без которых сайт не сможет работать правильно. Вы не можете отключить эти cookie в
				системе.</div>
		</section>
		<section>
			<div class="title">Аналитические cookie</div>
			<label class="w_label w_checkbox"><input type="checkbox" name="analytics" value="true"
					{if $privacyPolicyAgreements.analytics=='true'} checked="checked" {/if}
					id="privacy-analytics"><span>Аналитические cookie</span></label>
			<div class="text">Эти cookie помогают нам понимать, как посетители взаимодействуют с сайтом, собирая
				анонимную информацию. Это позволяет улучшать пользовательский опыт и развивать сайт.</div>
		</section>
	</fieldset>
	<fieldset>
		<section>
			<div class="w_flex w_flex w_flex_row w_flex w_flex_start w_flex_margin">
				<label class="w_label w_button">
					<input type="button" value="Сохранить настройки" id="privacy-save" />
				</label>
				<label class="w_label w_button">
					<input type="button" value="Принять все" id="privacy-accept-all" />
				</label>
				<label class="w_label w_button">
					<input type="button" value="Отклонить все" id="privacy-reject-all" />
				</label>
			</div>
		</section>
	</fieldset>
</form>
{$get.cssjs}