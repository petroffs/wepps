<div class="w_flex w_flex_row w_flex_center">
    <form action="javascript:formWepps.send('reg','reg-form','/ext/Profile/Request.php')" id="reg-form"
        class="w_form w_flex_12 w_flex_11_view_medium" autocomplete="off">
        <h2>Регистрация</h2>
        <fieldset>
            <section>
                <div class="title">E-mail</div>
                <label class="pps w_input w_require">
                    <input type="email" name="login" placeholder=""/>
                </label>
            </section>
            <section>
                <div class="title">Телефон</div>
                <label class="pps w_input w_require">
                    <input type="text" name="phone" placeholder=""/>
                </label>
            </section>
        </fieldset>
        <fieldset>
            <section>
                <div class="title">Фамилия</div>
                <label class="pps w_input w_require">
                    <input type="text" name="nameSurname" placeholder=""/>
                </label>
            </section>
            <section>
                <div class="title">Имя</div>
                <label class="pps w_input w_require">
                    <input type="text" name="nameFirst" placeholder=""/>
                </label>
            </section>
            <section>
                <div class="title">Отчество</div>
                <label class="pps w_input">
                    <input type="text" name="namePatronymic" placeholder=""/>
                </label>
            </section>
        </fieldset>
        <fieldset>
            <section>
                <label class="pps w_checkbox"><input type="checkbox" name="approve"/> <span>Я согласен на обработку
                        моих персональных данных</span></label>
                <label class="pps w_button">
                    <input type="submit" value="Зарегистрироваться" disabled/>
                </label>
            </section>
        </fieldset>
    </form>
</div>