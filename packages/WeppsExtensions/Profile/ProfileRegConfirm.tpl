<div class="w_flex w_flex_row w_flex_center">
    <form action="javascript:formWepps.send('reg-confirm','reg-confirm-form','/ext/Profile/Request.php')" id="reg-confirm-form"
        class="w_form w_flex_12 w_flex_11_view_medium">
        <input type="hidden" name="token" value="{$get.token}"/>
        <h2>Завершите регистрацию</h2>
        <p>После установки пароля - Ваш аккаунт будет активирован.</p>
        <fieldset>
            <section>
                <div class="title">Пароль</div>
                <label class="w_label w_input w_require">
                    <input type="password" name="password" placeholder="" autocomplete="new-password"/>
                </label>
            </section>
            <section>
                <div class="title">Повторите пароль</div>
                <label class="w_label w_input w_require">
                    <input type="password" name="password2" placeholder="" autocomplete="new-password"/>
                </label>
            </section>
        </fieldset>
        <fieldset>
            <section>
                <label class="w_label w_button">
                    <input type="submit" value="Сохранить пароль" />
                </label>
            </section>
        </fieldset>
    </form>
</div>