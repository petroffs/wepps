<div class="page contacts">
	<section>
		<div class="content-block contacts-header">
			<h1>{$content.Name}</h1>
			{if $content.Text1}
				<div class="w_interval_small"></div>
				<div class="text">{$content.Text1}</div>
			{/if}
		</div>
		<div class="content-block contacts-address">
			<section class="contacts-offices">
				{foreach name="out" item="item" from=$elements}
					<section>
						<h2>{$item.Name}</h2>
						<div class="w_grid w_5col w_gap_medium">
							<div class="w_3scol">
								{if $item.Descr}
									<div class="text">{$item.Descr}</div>
								{/if}
							</div>
							<div class="w_2scol">
								{if $item.Email}
									<div class="text">{$item.Email}</div>
								{/if}
								{if $item.Phone}
									<div class="text">{$item.Phone}</div>
								{/if}
								{if $item.LatLng}
									<div class="text"><a href="" data-coord="{$item.LatLng}">Карта</a></div>
								{/if}
							</div>
						</div>
					</section>
				{/foreach}
			</section>
		</div>
		<div class="contacts-map">
			<div id="map" class="map"></div>
		</div>
		<div class="content-block contacts-form">
			<div class="w_flex w_flex_row w_flex_center">
				<form action="javascript:formWepps.send('feedback','feedback-form','/ext/Contacts/Request.php')"
					id="feedback-form" class="w_form w_flex_11 w_flex_11_view_medium">
					<h2>Напишите сообщение</h2>
					<fieldset>
						<section>
							<div class="title">Ваше имя</div>
							<label class="w_label w_input w_require"><input type="text" name="name"
									placeholder="" /></label>
						</section>
						<section>
							<div class="title">Адрес электронной почты</div>
							<label class="w_label w_input w_require"> <input type="email" name="email"
									placeholder="" /></label>
						</section>
						<section>
							<div class="title">Телефон</div>
							<label class="w_label w_input w_require"> <input type="text" name="phone" placeholder="" />
							</label>
						</section>
					</fieldset>
					<fieldset>
						<section>
							<div class="title">Сообщение</div>
							<label class="w_label w_area w_require"> <textarea name="comment" placeholder=""></textarea>
							</label>
						</section>
						<section>
							<div class="title">Вложение</div>
							<label class="w_label w_upload"> <input type="file" name="feedback-upload" /> <span>Прикрепить
									файл</span>
							</label>
							<div class="w_upload_add">
								{foreach name="o" item="i" key="k" from=$uploaded['feedback-upload']}
									<div class="w_upload_file" data-key="{$k}">{$i.name} <i
											class="bi bi-x-circle-fill"></i>
									</div>
								{/foreach}
							</div>
						</section>
					</fieldset>
					<fieldset>
						<section>
							<div class="title">Test Checkbox</div>
							<label class="w_label w_checkbox">
								<input type="checkbox" name="checkboxtest[]" value="opt1"> <span>Опция 1</span>
							</label>
							<label class="w_label w_checkbox">
								<input type="checkbox" name="checkboxtest[]" value="opt2"> <span>Опция 2</span>
							</label>
						</section>
						<section>
							<div class="title">Test Radio</div>
							<label class="w_label w_radio">
								<input type="radio" name="radiotest" value="opt1"> <span>Опция 1</span>
							</label>
							<label class="w_label w_radio">
								<input type="radio" name="radiotest" value="opt2"> <span>Опция 2</span>
							</label>
						</section>
						<section>
							<div class="title">Test Select</div>
							<label class="w_label w_select">
								<select name="selecttest">
									<option value="opt1">Выбор 1</option>
									<option value="opt2">Выбор 2</option>
									<option value="opt3">Выбор 3</option>
								</select>
							</label>
						</section>
						<section>
							<div class="title">Test Select multiple</div>
							<label class="w_label w_select">
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
							<label class="w_label w_checkbox"><input type="checkbox" name="approve" /> <span>Я согласен на
									обработку
									моих персональных данных</span></label>
							<label class="w_label w_button">
								<input type="submit" value="Отправить" disabled />
							</label>
						</section>
					</fieldset>
				</form>
			</div>
		</div>
	</section>
</div>