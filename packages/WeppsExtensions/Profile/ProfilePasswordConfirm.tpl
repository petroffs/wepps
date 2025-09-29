<div class="w_flex w_flex_row w_flex_center">
    <form action="javascript:formWepps.send('password-confirm','password-confirm-form','/ext/Profile/Request.php')" id="password-confirm-form"
        class="w_form w_flex_12 w_flex_11_view_medium">
        <input type="hidden" name="token" value="{$get.token}"/>
        <h2>Установить новый пароль</h2>
        <fieldset>
            <section>
                <div class="title">Новый пароль</div>
                <label class="w_label w_input w_require">
                    <input type="password" name="password" placeholder="" autocomplete="off"/>
                </label>
            </section>
            <section>
                <div class="title">Повторите пароль</div>
                <label class="w_label w_input w_require">
                    <input type="password" name="password2" placeholder="" autocomplete="off"/>
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