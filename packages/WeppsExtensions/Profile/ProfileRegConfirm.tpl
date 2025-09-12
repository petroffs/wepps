<div class="pps_flex pps_flex_row pps_flex_center">
    <form action="javascript:formWepps.send('reg-confirm','reg-confirm-form','/ext/Profile/Request.php')" id="reg-confirm-form"
        class="pps_form pps_flex_12 pps_flex_11_view_medium">
        <input type="hidden" name="token" value="{$get.token}"/>
        <h2>Завершите регистрацию</h2>
        <p>После установки пароля - Ваш аккаунт будет активирован.</p>
        <fieldset>
            <section>
                <div class="title">Пароль</div>
                <label class="pps pps_input pps_require">
                    <input type="password" name="password" placeholder="" autocomplete="off"/>
                </label>
            </section>
            <section>
                <div class="title">Повторите пароль</div>
                <label class="pps pps_input pps_require">
                    <input type="password" name="password2" placeholder="" autocomplete="off"/>
                </label>
            </section>
        </fieldset>
        <fieldset>
            <section>
                <label class="pps pps_button">
                    <input type="submit" value="Сохранить пароль" />
                </label>
            </section>
        </fieldset>
    </form>
</div>