<div class="w_flex w_flex_row w_flex_center">
    <form action="javascript:formWepps.send('password','password-form','/ext/Profile/Request.php')" id="password-form"
        class="w_form w_flex_12 w_flex_11_view_medium">
        <h2>Восстановление доступа</h2>
        <fieldset>
            <section>
                <div class="title">E-mail</div>
                <label class="pps w_input w_require">
                    <input type="email" name="login" placeholder="" />
                </label>
            </section>
        </fieldset>
        <fieldset>
            <section>
                <label class="pps w_button">
                    <input type="submit" value="Восстановить доступ" />
                </label>
                <div class="w_interval"></div>
                {$recaptcha}
            </section>
        </fieldset>
    </form>
</div>