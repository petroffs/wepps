<div class="s001">
Распечатайте эту квитанцию и посетите любое отделение Сбербанка или любой другой банк, принимающий платежи от физических лиц. Обратите, пожалуйста, Ваше внимание, что за перевод денежных средств банк взимает комиссию в размере 2-3% от суммы платежа (в зависимости от действующих тарифов выбранного банка).
</div>
<DIV align="center" class="s002">

	<TABLE cellSpacing="0" cellPadding="4" width="600" border="1">
		<TBODY>
			<TR>
				<TD vAlign=bottom align="right" width="25%">
					<P align="right">
						<b>ИЗВЕЩЕНИЕ</b>
					</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>

					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">Кассир</P>
				</TD>
				<TD width="75%">
					<TABLE cellSpacing="0" cellPadding="2" width="100%" border=0>
						<TBODY>
							<TR>
								<TD colSpan="3"><STRONG>Получатель платежа</STRONG></TD>
							</TR>
							<TR>
								<TD colSpan="3">{$shopInfo.Name}</TD>
							</TR>
							<TR>
								<TD>Счет: {$shopInfo.UrRaschShet}</TD>
								<TD>ИНН: {$shopInfo.UrINN}</TD>
								<TD>КПП: {$shopInfo.UrKPP}</TD>
							</TR>
							<TR>
								<TD colSpan="3">Наименование банка: {$shopInfo.UrBank}</TD>
							</TR>
							<TR>
								<TD>Кор. счет: {$shopInfo.UrCorSchet}</TD>
								<TD colSpan="2">БИК: {$shopInfo.UrBIK}</TD>
							</TR>
						</TBODY>
					</TABLE>
					<BR/>
					<TABLE cellSpacing="0" cellPadding="2" width="100%" class="tablethin">
						<TBODY>
							<TR>
								<TD><STRONG>Плательщик</STRONG></TD>
							</TR>
							<TR>
								<TD>{$user.Name}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$order.AddressIndex}, {$order.CityName}</TD>
							</TR>
							<TR>
								<TD>{$order.Address}</TD>
							</TR>
						</TBODY>
					</TABLE>
					<BR/>
					
					<TABLE cellSpacing="0" cellPadding="2" width="100%" border=1 class="tablethin">
						<TBODY>
							<TR>
								<TD>
									<DIV align="center">Назначение платежа</DIV>
								</TD>
								<TD>
									<DIV align="center">Дата</DIV>
								</TD>
								<TD>
									<DIV align="center">Сумма</DIV>
								</TD>
							</TR>
							<TR>
								<TD>
									<DIV align="center">Оплата товаров по заказу №{$order.Id|number} от {$order.ODate|date_format:"%d.%m.%Y"}</DIV>
								</TD>
								<TD>
									<DIV align="center">&nbsp;</DIV>
								</TD>
								<TD align="right">
									<DIV align="center">{$order.Summ|money}</DIV>
								</TD>
							</TR>
						</TBODY>
					</TABLE>
					<P>Подпись плательщика:</P>
				</TD>
			</TR>
			<TR>
				<TD vAlign=bottom align="right">

					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">&nbsp;</P>
					<P align="right">
						<b>КВИТАНЦИЯ</b>
					</P>
					<P align="right">&nbsp;</P>
					<P align="right">Кассир</P>
				</TD>
				<TD>
					<TABLE cellSpacing="0" cellPadding="2" width="100%" border=0>
						<TBODY>
							<TR>
								<TD colSpan="3"><STRONG>Получатель платежа</STRONG></TD>
							</TR>
							<TR>
								<TD colSpan="3">{$shopInfo.Name}</TD>
							</TR>
							<TR>
								<TD>Счет: {$shopInfo.UrRaschShet}</TD>
								<TD>ИНН: {$shopInfo.UrINN}</TD>
								<TD>КПП: {$shopInfo.UrKPP}</TD>
							</TR>
							<TR>
								<TD colSpan="3">Наименование банка: {$shopInfo.UrBank}</TD>
							</TR>
							<TR>
								<TD>Кор. счет: {$shopInfo.UrCorSchet}</TD>
								<TD colSpan="2">БИК: {$shopInfo.UrBIK}</TD>
							</TR>
						</TBODY>
					</TABLE>
					<BR/>
					<TABLE cellSpacing="0" cellPadding="2" width="100%" class="tablethin">
						<TBODY>
							<TR>
								<TD><STRONG>Плательщик</STRONG></TD>
							</TR>
							<TR>
								<TD>{$user.Name}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$order.AddressIndex}, {$order.CityName}</TD>
							</TR>
							<TR>
								<TD>{$order.Address}</TD>
							</TR>
						</TBODY>
					</TABLE>
					<BR>
					<TABLE cellSpacing="0" cellPadding="2" width="100%" class="tablethin">
						<TBODY>
							<TR>
								<TD>
									<DIV align="center">Назначение платежа</DIV>
								</TD>
								<TD>
									<DIV align="center">Дата</DIV>
								</TD>
								<TD>
									<DIV align="center">Сумма</DIV>
								</TD>
							</TR>
							<TR>
								<TD>
									<DIV align="center">Оплата товаров по заказу №{$order.Id|number} от {$order.ODate|date_format:"%d.%m.%Y"}</DIV>
								</TD>
								<TD>
									<DIV align="center">&nbsp;</DIV>
								</TD>
								<TD align="right">
									<DIV align="center">{$order.Summ|money}</DIV>
								</TD>
							</TR>
						</TBODY>
					</TABLE>
					<P>Подпись плательщика:</P>
				</TD>
			</TR>
		</TBODY>
	</TABLE>
</DIV>