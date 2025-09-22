<div class="pps_flex pps_flex_row pps_flex_center">
    <div class="pps_flex_12 pps_flex_11_view_medium">
        <h2>Мои данные</h2>
        <form action="javascript:formWepps.send('change-name','change-name-form','/ext/Profile/Request.php')"
            id="change-name-form" class="pps_form" autocomplete="off">

            <fieldset>
                <section>
                    <div class="title">Фамилия</div>
                    <label class="pps pps_input pps_require">
                        <input type="text" name="nameSurname" value="{$user.NameSurname|escape}" placeholder="" />
                    </label>
                </section>
                <section>
                    <div class="title">Имя</div>
                    <label class="pps pps_input pps_require">
                        <input type="text" name="nameFirst" value="{$user.NameFirst|escape}" placeholder="" />
                    </label>
                </section>
                <section>
                    <div class="title">Отчество</div>
                    <label class="pps pps_input">
                        <input type="text" name="namePatronymic" value="{$user.NamePatronymic|escape}" placeholder="" />
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="pps pps_button">
                        <input type="submit" value="Изменить" />
                    </label>
                </section>
            </fieldset>
        </form>

        <h2>E-mail</h2>
        <form action="javascript:formWepps.send('change-email','change-email-form','/ext/Profile/Request.php')"
            id="change-email-form" class="pps_form pps_flex_12 pps_flex_11_view_medium" autocomplete="off">

            <fieldset>
                <section>
                    <div class="title">E-mail</div>
                    <label class="pps pps_input pps_require">
                        <input type="email" name="login" value="{$user.Email|escape}" placeholder="" />
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="pps pps_button">
                        <input type="submit" value="Изменить" />
                    </label>
                </section>
            </fieldset>
        </form>

        <h2>Телефон</h2>
        <form action="javascript:formWepps.send('change-phone','change-phone-form','/ext/Profile/Request.php')"
            id="change-phone-form" class="pps_form pps_flex_12 pps_flex_11_view_medium" autocomplete="off">

            <fieldset>
                <section>
                    <div class="title">Телефон</div>
                    <label class="pps pps_input pps_require">
                        <input type="text" name="phone" value="{$user.Phone|escape|substr:1}" placeholder="" />
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="pps pps_button">
                        <input type="submit" value="Изменить" />
                    </label>
                </section>
            </fieldset>
        </form>

        <h2>Пароль</h2>
        <form action="javascript:formWepps.send('change-password','change-password-form','/ext/Profile/Request.php')"
            id="change-password-form" class="pps_form pps_flex_12 pps_flex_11_view_medium" autocomplete="off">

            <fieldset>
                <section>
                    <div class="title">Новый пароль</div>
                    <label class="pps pps_input pps_require">
                        <input type="password" name="password" placeholder="" autocomplete="new-password" />
                    </label>
                </section>
                <section>
                    <div class="title">Повторите пароль</div>
                    <label class="pps pps_input pps_require">
                        <input type="password" name="password2" placeholder="" autocomplete="new-password" />
                    </label>
                </section>
            </fieldset>
            <fieldset>
                <section>
                    <label class="pps pps_button">
                        <input type="submit" value="Изменить" />
                    </label>
                </section>
            </fieldset>
        </form>
    </div>
</div>