<a href="" class="pps_button pps_animate">Тест</a>
<div class="elements ContactsMain">
	<div
		class="items pps_flex pps_flex_row pps_flex_row_top pps_animate">
		{foreach name="out" item="item" from=$elementsMain}
		<div class="item pps_flex_12 pps_flex_11_view_small ">
			<div class="title">{$item.Name}</div>
			{if $item.Street}
			<div class="param mapData" data-coord="{$item.AdGoogle}">{$item.Street}</div>
			{/if} {if $item.Email}
			<div class="param">{$item.Email}</div>
			{/if} {if $item.Phone}
			<div class="param">Телефон: {$item.Phone}</div>
			{/if} {if $item.Fax}
			<div class="param">Факс: {$item.Fax}</div>
			{/if} {if $item.PhoneMob}
			<div class="param">Моб. телефон: {$item.PhoneMob}</div>
			{/if}
		</div>
		{/foreach}
		<div class="item pps_flex_12 pps_flex_11_view_small">
			<div
				class="items pps_flex pps_flex_row pps_flex_row_top pps_animate">
				{foreach name="out" item="item" from=$elements}
				<div
					class="item pps_flex_12 pps_flex_11_view_medium pps_flex_11_view_small ">
					<div class="title">{$item.Name}</div>
					{if $item.Email}
					<div class="param">{$item.Email}</div>
					{/if} {if $item.Phone}
					<div class="param">{$item.Phone}</div>
					{/if} {if $item.Fax}
					<div class="param">{$item.Fax}</div>
					{/if} {if $item.PhoneMob}
					<div class="param">{$item.PhoneMob}</div>
					{/if}
					<div class="pps_interval"></div>
				</div>
				{/foreach}
			</div>
		</div>
	</div>

</div>
<div class="elements ContactsMap">
	<div id="map"></div>
</div>

<h1>Остались вопросы?</h1>
<p>Отправьте свой вопрос через данную форму и мы обязательно свяжемся с Вами!</p>

<div class="elements ContactsForm">
	<form action="javascript:formWepps.send('feedback','feedbackForm','/ext/Contacts/Request.php')" id="feedbackForm" class="pps_form pps_flex_11">
		<div class="form pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
			<div
				class="pps_flex_11 pps_flex_11_view_medium pps_flex pps_flex_row pps_flex_row_str pps_flex_margin">
				<div class="pps_flex_11">		
					<span class="title pps_require">Ваше имя</span>
					<label class="pps pps_input"><input type="text" name="name" placeholder="" />
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title pps_require">Адрес электронной почты</span>
					<label class="pps pps_input"> <input type="text" name="email"
						placeholder="" />
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title pps_require">Телефон</span>
					<label class="pps pps_input"> <input type="text" name="phone"
						placeholder="" />
					</label>
				</div>
				<div class="pps_flex_11">
					<span class="title pps_require">Сообщение</span>
					<label class="pps pps_area"> <textarea form="feedbackForm"
							name="comment" placeholder=""></textarea>
					</label>
				</div>
				
				<div class="pps_flex_11">
					<span class="title">Вложение</span>
					<label class="pps pps_upload"> <input type="file"
						name="feedback-upload" /> <span>Прикрепить файл</span>
					</label>
				</div>
				
				<div class="pps_flex_11">
					<span class="title">Test Checkbox</span>
					<label class="pps pps_checkbox">
						<input type="checkbox"> <span>Опция 1</span>
					</label>
					<label class="pps pps_checkbox">
						<input type="checkbox"> <span>Опция 2</span>
					</label>
				</div>
				
				<div class="pps_flex_11">
					<span class="title">Test Radio</span>
					<label class="pps pps_radio">
						<input type="radio" name="radiotest"> <span>Опция 1</span>
					</label>
					<label class="pps pps_radio">
						<input type="radio" name="radiotest"> <span>Опция 2</span>
					</label>
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
				
				
				
				
				<div class="pps_flex_11 pps_flex pps_flex_row">
					
					<label class="pps pps_checkbox pps_flex_23 pps_flex_11_view_small"><input type="checkbox" name="approve"/> <span>Я согласен на обработку моих персональных данных</span></label>
					<label class="pps pps_button  pps_flex_13 pps_flex_11_view_small pps_right pps_center_view_small"> <input type="submit"
						value="Отправить" disabled="disabled" />
					</label>
					<span class="descr pps_require">Обязательные поля</span>
				</div>
			</div>
		</div>
	</form>
</div>