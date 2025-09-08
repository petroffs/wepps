<div class="page contacts">
	<section>
		<div class="content-block">
			<a href="" class="pps_button pps_animate">Тест</a>
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
						<div class="pps_interval"></div>
					</div>
				{/foreach}
			</section>
		</div>
		<div class="pps_interval"></div>
		{if $item.LatLng && $item.Address}
			<div class="content-block map-block">
				<div class="param mapData pps_hide" data-coord="{$item.LatLng}">{$item.Address}</div>
				<div id="map" class="map"></div>
			</div>
			<div class="pps_interval"></div>
		{/if}
		<div class="content-block">
			<div class="pps_flex pps_flex_row pps_flex_center">
				<form action="javascript:formWepps.send('feedback','feedback-form','/ext/Contacts/Request.php')"
					id="feedback-form" class="pps_form pps_flex_12 pps_flex_11_view_medium">
					<h2>Напишите сообщение</h2>
					<fieldset>
						<section>
							<div class="title">Ваше имя</div>
							<label class="pps pps_input pps_require"><input type="text" name="name"
									placeholder="" /></label>
						</section>
						<section>
							<div class="title">Адрес электронной почты</div>
							<label class="pps pps_input pps_require"> <input type="email" name="email"
									placeholder="" /></label>
						</section>
						<section>
							<div class="title">Телефон</div>
							<label class="pps pps_input pps_require"> <input type="text" name="phone" placeholder="" />
							</label>
						</section>
					</fieldset>
					<fieldset>
						<section>
							<div class="title">Сообщение</div>
							<label class="pps pps_area pps_require"> <textarea name="comment" placeholder=""></textarea>
							</label>
						</section>
						<section>
							<div class="title">Вложение</div>
							<label class="pps pps_upload"> <input type="file" name="feedback-upload" /> <span>Прикрепить
									файл</span>
							</label>
							<div class="pps_upload_add">
								{foreach name="o" item="i" key="k" from=$uploaded['feedback-upload']}
									<div class="pps_upload_file" data-key="{$k}">{$i.name} <i
											class="bi bi-x-circle-fill"></i></div>
								{/foreach}
							</div>
						</section>
					</fieldset>
					<fieldset>
						<section>
							<div class="title">Test Checkbox</div>
							<label class="pps pps_checkbox">
								<input type="checkbox" name="checkboxtest[]" value="opt1"> <span>Опция 1</span>
							</label>
							<label class="pps pps_checkbox">
								<input type="checkbox" name="checkboxtest[]" value="opt2"> <span>Опция 2</span>
							</label>
						</section>
						<section>
							<div class="title">Test Radio</div>
							<label class="pps pps_radio">
								<input type="radio" name="radiotest" value="opt1"> <span>Опция 1</span>
							</label>
							<label class="pps pps_radio">
								<input type="radio" name="radiotest" value="opt2"> <span>Опция 2</span>
							</label>
						</section>
						<section>
							<div class="title">Test Select</div>
							<label class="pps pps_select">
								<select name="selecttest">
									<option value="opt1">Выбор 1</option>
									<option value="opt2">Выбор 2</option>
									<option value="opt3">Выбор 3</option>
								</select>
							</label>
						</section>
						<section>
							<div class="title">Test Select multiple</div>
							<label class="pps pps_select">
								<select multiple name="selectmultipletest[]">
									<option value="opt1">Выбор 1</option>
									<option value="opt2">Выбор 2</option>
									<option value="opt3">Выбор 3</option>
								</select>
							</label>
						</section>
					</fieldset>
					<fieldset>
						<section>
							<label class="pps pps_checkbox"><input type="checkbox" name="approve" /> <span>Я согласен на
									обработку
									моих персональных данных</span></label>
							<label class="pps pps_button">
								<input type="submit" value="Отправить" disabled />
							</label>
						</section>
					</fieldset>
				</form>
			</div>
		</div>
	</section>
</div>