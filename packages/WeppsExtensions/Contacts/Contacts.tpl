<a href="" class="pps_button pps_animate">Тест</a>
<div class="pps_interval"></div>
<section class="contacts-wrapper">
	{foreach name="out" item="item" from=$elements}
		<div class="item">
			<div class="title">{$item.Name}</div>
			{if $item.Email}
				<div class="param">{$item.Email}</div>
			{/if}
			{if $item.Phone}
				<div class="param">{$item.Phone}</div>
			{/if}
			{if $item.Fax}
				<div class="param">{$item.Fax}</div>
			{/if}
			{if $item.PhoneMob}
				<div class="param">{$item.PhoneMob}</div>
			{/if}

			{if $item.LatLng && $item.Address}
				<div class="pps_interval"></div>
				<div class="param mapData pps_hide" data-coord="{$item.LatLng}">{$item.Address}</div>
				<div id="map" class="map"></div>
			{/if}
			<div class="pps_interval"></div>
		</div>
	{/foreach}
</section>


<h1>Напишите сообщение</h1>
<div class="pps_interval"></div>
<div class="elements ContactsForm">
	<form action="javascript:formWepps.send('feedback','feedbackForm','/ext/Contacts/Request.php')" id="feedbackForm"
		class="pps_form pps_flex_11">
		<div class="form">
			<div class="pps_flex pps_flex_row pps_flex_row_str pps_flex_margin pps_padding">
				<div class="pps_flex_11">
					<span class="title">Ваше имя</span>
					<label class="pps pps_input pps_require"><input type="text" name="name" placeholder="" />
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title">Адрес электронной почты</span>
					<label class="pps pps_input pps_require"> <input type="text" name="email" placeholder="" />
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title">Телефон</span>
					<label class="pps pps_input pps_require"> <input type="text" name="phone" placeholder="" />
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title">Сообщение</span>
					<label class="pps pps_area pps_require"> <textarea form="feedbackForm" name="comment"
							placeholder=""></textarea>
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title">Вложение</span>
					<label class="pps pps_upload"> <input type="file" name="feedback-upload" /> <span>Прикрепить
							файл</span>
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title">Test Checkbox</span>
					<div class="w_fields">
						<label class="pps pps_checkbox">
							<input type="checkbox"> <span>Опция 1</span>
						</label>
						<label class="pps pps_checkbox">
							<input type="checkbox"> <span>Опция 2</span>
						</label>
					</div>
				</div>
				<div class="pps_flex_11">
					<span class="title">Test Radio</span>
					<div class="w_fields">
						<label class="pps pps_radio">
							<input type="radio" name="radiotest"> <span>Опция 1</span>
						</label>
						<label class="pps pps_radio">
							<input type="radio" name="radiotest"> <span>Опция 2</span>
						</label>
					</div>
				</div>
				<div class="pps_flex_11">
					<span class="title">Test Select</span>
					<label class="pps pps_select">
						<select>
							<option>Выбор 1</option>
							<option>Выбор 2</option>
							<option>Выбор 3</option>
						</select>
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title">Test Select</span>
					<label class="pps pps_select">
						<select multiple>
							<option>Выбор 1</option>
							<option>Выбор 2</option>
							<option>Выбор 3</option>
						</select>
					</label>
				</div>
				<div class="pps_interval"></div>
				<div class="pps_interval"></div>
				<div class="pps_interval"></div>
				<div class="pps_interval"></div>
				<div class="pps_interval"></div>
				<div class="pps_interval"></div>
				<div class="pps_interval"></div>
				<div class="pps_flex_11 pps_flex pps_flex_row">
					<div class="pps_flex_23 pps_flex_11_view_small">
						<label class="pps pps_checkbox"><input type="checkbox" name="approve" /> <span>Я согласен
								на<br /><br /> обработку моих персональных данных</span></label>
					</div>
					<div class="pps_flex_13 pps_flex_11_view_small pps_right pps_center_view_small">
						<label class="pps pps_button"> <input type="submit" value="Отправить" disabled="disabled" />
						</label>
					</div>

					<div class="pps_flex_11 pps_padding">
						<label class="pps pps_require pps_title">Обязательные поля</label>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
<div class="pps_interval"></div>