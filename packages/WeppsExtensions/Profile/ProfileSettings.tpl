<div class="w_flex w_flex_row w_flex_center">
    <div class="w_flex_12 w_flex_11_view_medium">
        <h2>Мои данные</h2>
        <form action="javascript:formWepps.send('change-name','change-name-form','/ext/Profile/Request.php')"
            id="change-name-form" class="w_form" autocomplete="off">

            <fieldset>
                <section>
                    <div class="title">Фамилия</div>
                    <label class="w_label w_input w_require">
                        <input type="text" name="nameSurname" value="{$user.NameSurname|escape}" placeholder="" />
                    </label>
                </section>
                <section>
                    <div class="title">Имя</div>
                    <label class="w_label w_input w_require">
                        <input type="text" name="nameFirst" value="{$user.NameFirst|escape}" placeholder="" />
                    </label>
                </section>
                <section>
                    <div class="title">Отчество</div>
                    <label class="w_label w_input">
                        <input type="text" name="namePatronymic" value="{$user.NamePatronymic|escape}" placeholder="" />
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="w_label w_button">
                        <input type="submit" value="Изменить" />
                    </label>
                </section>
            </fieldset>
        </form>
        <h2>E-mail</h2>
        <form action="javascript:formWepps.send('change-email','change-email-form','/ext/Profile/Request.php')"
            id="change-email-form" class="w_form w_flex_12 w_flex_11_view_medium" autocomplete="off">
            <fieldset>
                <section>
                    <div class="title">E-mail</div>
                    <label class="w_label w_input w_require">
                        <input type="email" name="login" value="{$user.Email|escape}" placeholder="" />
                    </label>
                </section>
                <section class="w_hide change-email-code">
                    <div class="title">Код подтверждения</div>
                    <label class="w_label w_input w_require">
                        <input type="text" name="code" value="" placeholder="" disabled/>
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="w_label w_button">
                        <input type="submit" value="Изменить" />
                    </label>
                </section>
            </fieldset>
        </form>
        <h2>Телефон</h2>
        <form action="javascript:formWepps.send('change-phone','change-phone-form','/ext/Profile/Request.php')"
            id="change-phone-form" class="w_form w_flex_12 w_flex_11_view_medium" autocomplete="off">

            <fieldset>
                <section>
                    <div class="title">Телефон</div>
                    <label class="w_label w_input w_require">
                        <input type="text" name="phone" value="{$user.Phone|escape|substr:1}" placeholder="" />
                    </label>
                </section>
				<section class="w_hide change-phone-code">
                    <div class="title">Код подтверждения</div>
                    <label class="w_label w_input w_require">
                        <input type="text" name="code" value="" placeholder="" disabled/>
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="w_label w_button">
                        <input type="submit" value="Изменить" />
                    </label>
                </section>
            </fieldset>
        </form>
        <h2>Пароль</h2>
        <form action="javascript:formWepps.send('change-password','change-password-form','/ext/Profile/Request.php')"
            id="change-password-form" class="w_form w_flex_12 w_flex_11_view_medium" autocomplete="off">

            <fieldset>
                <section>
                    <div class="title">Новый пароль</div>
                    <label class="w_label w_input w_require">
                        <input type="password" name="password" placeholder="" autocomplete="new-password" />
                    </label>
                </section>
                <section>
                    <div class="title">Повторите пароль</div>
                    <label class="w_label w_input w_require">
                        <input type="password" name="password2" placeholder="" autocomplete="new-password" />
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="w_label w_button">
                        <input type="submit" value="Изменить" />
                    </label>
                </section>
            </fieldset>
        </form>
        <h2>Удалить профиль</h2>
        <form action="javascript:formWepps.send('remove','remove-form','/ext/Profile/Request.php')"
            id="remove-form" class="w_form w_flex_12 w_flex_11_view_medium" autocomplete="off">
            <fieldset>
                <section>
                    <div class="title">Напишите слово "УДАЛИТЬ"</div>
                    <label class="w_label w_input w_require">
                        <input type="text" name="word" value="" placeholder="" autocomplete="new-password"/>
                    </label>
                </section>
				<section class="w_hide remove-code">
                    <div class="title">Код подтверждения</div>
                    <label class="w_label w_input w_require">
                        <input type="text" name="code" value="" placeholder="" disabled/>
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="w_label w_button">
                        <input type="submit" value="Удалить профиль" />
                    </label>
                </section>
            </fieldset>
        </form>
    </div>
</div>