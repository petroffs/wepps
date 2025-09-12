<div class="pps_flex pps_flex_row pps_flex_center">
    <form action="javascript:formWepps.send('reg','reg-form','/ext/Profile/Request.php')" id="reg-form"
        class="pps_form pps_flex_12 pps_flex_11_view_medium" autocomplete="off">
        <h2>Регистрация</h2>
        <fieldset>
            <section>
                <div class="title">E-mail</div>
                <label class="pps pps_input pps_require">
                    <input type="email" name="login" placeholder=""/>
                </label>
            </section>
            <section>
                <div class="title">Телефон</div>
                <label class="pps pps_input pps_require">
                    <input type="text" name="phone" placeholder=""/>
                </label>
            </section>
        </fieldset>
        <fieldset>
            <section>
                <div class="title">Фамилия</div>
                <label class="pps pps_input pps_require">
                    <input type="text" name="nameSurname" placeholder=""/>
                </label>
            </section>
            <section>
                <div class="title">Имя</div>
                <label class="pps pps_input pps_require">
                    <input type="text" name="nameFirst" placeholder=""/>
                </label>
            </section>
            <section>
                <div class="title">Отчество</div>
                <label class="pps pps_input">
                    <input type="text" name="namePatronymic" placeholder=""/>
                </label>
            </section>
        </fieldset>
        <fieldset>
            <section>
                <label class="pps pps_checkbox"><input type="checkbox" name="approve"/> <span>Я согласен на обработку
                        моих персональных данных</span></label>
                <label class="pps pps_button">
                    <input type="submit" value="Зарегистрироваться" disabled/>
                </label>
            </section>
        </fieldset>
    </form>
</div>