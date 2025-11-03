<form action="javascript:formWepps.send('sign-in','sign-in-popup-form','/ext/Profile/Request.php')" id="sign-in-popup-form"
    class="w_form">
    <h2>Вход</h2>
    <fieldset>
        <section>
            <div class="title">E-mail</div>
            <label class="w_label w_input">
                <input type="email" name="login" placeholder="" />
            </label>
        </section>
        <section>
            <div class="title">Пароль</div>
            <label class="w_label w_input">
                <input type="password" name="password" placeholder="" />
            </label>
        </section>
    </fieldset>
    <fieldset>
        <section class="w_grid w_3col w_1col_view_small w_ai_center w_gap">
            <label class="w_label w_button">
                <input type="submit" value="Войти" />
            </label>
            <a href="/profile/reg.html">Регистрация</a>
            <a href="/profile/password.html">Забыли пароль?</a>
        </section>
    </fieldset>
</form>