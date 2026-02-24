<!DOCTYPE html>
<html lang="ru" xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>{$subject|escape:'html'}</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<style>
		* {
			margin: 0;
			padding: 0;
			font-size: 100%;
			font-family: 'Avenir Next', "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
			line-height: 1.65;
		}

		img {
			max-width: 100%;
			margin: 0 auto;
			display: block;
		}

		body,
		.body-wrap {
			width: 100% !important;
			height: 100%;
			background: #efefef;
			-webkit-font-smoothing: antialiased;
			-webkit-text-size-adjust: none;
		}

		a {
			color: #0074e8;
			text-decoration: none;
		}

		.text-center {
			text-align: center;
		}

		.text-right {
			text-align: right;
		}

		.text-left {
			text-align: left;
		}

		.button {
			display: inline-block;
			color: white;
			background: #0074e8;
			border: solid #0074e8;
			border-width: 10px 20px 8px;
			font-weight: bold;
			border-radius: 4px;
		}

		h1,
		h2,
		h3,
		h4,
		h5,
		h6 {
			margin-bottom: 20px;
			line-height: 1.25;
		}

		h1 {
			font-size: 32px;
		}

		h2 {
			font-size: 28px;
		}

		h3 {
			font-size: 24px;
		}

		h4 {
			font-size: 20px;
		}

		h5 {
			font-size: 16px;
		}

		p,
		ul,
		ol {
			font-size: 16px;
			font-weight: normal;
			margin-bottom: 20px;
		}

		.container {
			display: block !important;
			clear: both !important;
			margin: 0 auto !important;
			max-width: 580px !important;
		}

		.container table {
			width: 100% !important;
			border-collapse: collapse;
		}

		.container .header {
			padding: 80px 0;
			background: #0074e8;
			color: white;
		}

		.container .header h1 {
			margin: 0 auto !important;
			max-width: 90%;
			text-transform: uppercase;
		}

		.container .header-link {
			padding: 10px 0;
			display: inline-block;
		}

		.container .header-img {
			margin: 0;
			display: block;
		}

		.container .content {
			background: white;
			padding: 30px 35px;
		}

		.container .content.footer {
			background: none;
		}

		.container .content.footer p {
			margin-bottom: 0;
			color: #888;
			text-align: center;
			font-size: 14px;
		}

		.container .content.footer a {
			color: #888;
			text-decoration: none;
			font-weight: bold;
		}

		.content-body table {
			border: 2px solid #efefef;
		}

		.content-body table th,
		.content-body table td {
			padding: 10px;
			border: 2px solid #efefef;
		}

		.content-body table tr:nth-child(even) td {
			background-color: #f9f9f9;
		}
	</style>
</head>

<body>
	<table class="body-wrap">
		<tbody>
			<tr>
				<td class="container">
					<table>
						<tbody>
							<tr>
								<td class="text-right">
									<a href="{$settings.host.url}" class="header-link">
										<img src="{$settings.host.url}{$settings.logopng}" width="150"
											alt="{$settings.name}" /></a>
								</td>
							</tr>
							<tr>
								<td class="header text-center">
									<h1>{$subject}</h1>
								</td>
							</tr>
							<tr>
								<td class="content content-body">
									{$text}
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td class="container">
					<table>
						<tbody>
							<tr>
								<td class="content footer text-center">
									<p>Отправитель <a href="{$settings.host.url}">{$settings.name}</a>,
										{$settings.address.address}</p>
									<p><a href="mailto:{$settings.email}">{$settings.email}</a> | {$settings.phone}</p>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
</body>

</html>