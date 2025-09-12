<div class="pps_flex pps_flex_row pps_flex_center">
    <form action="javascript:formWepps.send('password','password-form','/ext/Profile/Request.php')" id="password-form"
        class="pps_form pps_flex_12 pps_flex_11_view_medium">
        <h2>Восстановление доступа</h2>
        <fieldset>
            <section>
                <div class="title">E-mail</div>
                <label class="pps pps_input pps_require">
                    <input type="email" name="login" placeholder="" />
                </label>
            </section>
        </fieldset>
        <fieldset>
            <section>
                <label class="pps pps_button">
                    <input type="submit" value="Восстановить доступ" />
                </label>
                <div class="pps_interval"></div>
                {$recaptcha}
            </section>
        </fieldset>
    </form>
</div>