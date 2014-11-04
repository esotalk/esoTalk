<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * Message master view. Displays a simple HTML template. Used for things like fatal errors and the installer.
 *
 * @package esoTalk
 */
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo sanitizeHTML($data["pageTitle"]); ?></title>
		<meta charset='<?php echo T("charset", "utf-8"); ?>'>
		<?php if (!empty($data["head"])) echo $data["head"]; ?>
		<link type="text/css" rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400,600">
		<style>
			body {
				margin: 0;
				color: #666;
				line-height: 1.5em;
			}
			body, input, textarea, select {
				font-size: 16px;
				font-family: open sans, helvetica, arial, sans-serif;
			}
			a {
				text-decoration: none;
				color: #354059;
			}
			a:hover {
				color: #000;
			}
			p, ul, ol, h1, h2, h3 {
				margin: 0 0 1em;
			}
			h1 {
				font-size: 200%;
				font-weight: 300;
				color: #354059;
				margin-top: 30px;
			}
			h2 {
				font-size: 120%;
				font-weight: 300;
				margin-bottom: 50px;
				line-height: 1.5em;
			}
			h3 {
				font-size: 100%;
				font-weight: normal;
				margin: 1.5em 0 0.5em;
			}
			hr {
				border: solid #eee;
				border-width: 2px 0 0;
				margin: 15px 0;
			}
			.clear {
				clear: both;
			}

			#container {
				margin: 50px auto;
				max-width: 700px;
				text-align: center;
			}
			.details {
				text-align: left;
				margin: 0 auto;
			}

			.code {
				background: #eee;
				margin: 0 0 1em;
				padding: 20px;
				overflow: auto;
				border-radius: 3px;
			}
			.code pre {
				margin: 0 -18px;
				padding: 0 18px;
				font-size: 80%;
			}
			.code pre.highlight {
				font-weight: bold;
				background: #fff;
			}
			.code em {
				color: #aaa;
				font-style: normal;
			}
			span.highlight {
				background: #fdef34; 
				padding: 1px 4px; 
				border-radius: 3px;
			}

			.list {
				padding: 0;
				list-style-type: none;
			}
			.list li {
				border-top: 2px solid #eee;
				padding-left: 200px;
				margin-top: 10px;
				padding-top: 10px;
				overflow: hidden;
			}
			.list label {
				float: left;
				margin-left: -200px;
				color: #aaa;
			}

			.form {
				padding: 0;
				margin: 0;
				overflow: hidden;
				text-align: left;
			}
			.form > li {
				display: block;
				list-style: none;
				margin-bottom: 7px;
				overflow: hidden;
				margin-right: 2%;
			}
			.form > li.half {
				width: 48%;
				margin-right: 2%;
				float: left;
			}
			.form input[type=text],
			.form input[type=password],
			.form select,
			.form textarea,
			.form .input {
				margin: 0 3px 3px 0;
				font-size: 120%;
				padding: 10px;
				width: 100%;
				box-sizing: border-box;
			}

			.button {
				cursor: pointer;
				border-radius: 3px;
				background: #f6f6f6;
				font-weight: 600;
				border: 1px solid transparent;
				white-space: nowrap;
				font-size: 120%;
				padding: 15px 25px;
				-webkit-appearance: none;
			}
			.button:hover, 
			.button:active {
				border-color: #ddd;
			}
			.button:active {
				background-color: #ddd;
			}
			a.button {
				display: inline-block;
			}

			.button.submit {
				color: #fff;
				background-color: #354059;
			}
			.button.submit:hover {
				border-color: #000;
				color: #fff;
			}

			input[type=text],
			input[type=password],
			textarea,
			.input {
				font-weight: 300;
				background: #fff;
				border: 1px solid #e5e5e5;
				padding: 5px;
				border-radius: 3px;
				vertical-align: -1px;
				margin: 0;
				-webkit-appearance: none;
			}
			input[type=text]:focus, input[type=password]:focus, textarea:focus, .input:focus {
				outline: 0;
				border-color: #354059;
			}
			input.error {
				border-color: #c00;
			}
			div.error {
				color: #c00;
				font-size: 90%;
			}
			.warning strong {
				display:block;
				font-weight: 600;
				color: #666;
			}
			.warning {
				color: #aaa;
				font-size: 90%;
			}
		</style>
	</head>
	<body>

		<div id='container'>
			<img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEQAAAA8CAMAAADovOwsAAAASFBMVEUAAAA2QVk1QFk1QVk2QVk2QVg2QVk1RVU7QUk2QFg2QVk2QVk1QVk2QVk2QVk1QVk2QVk2QVk2QVk2QVk5RF42QVo2Qlo2QVnF7oIpAAAAE3RSTlMAai57xiCxAQQQPvLaWuhNipaiHZBk0wAAArNJREFUWMOllouyqyAMRfGB4AutCfn/T70GrDlXDocy3TOdtigrBDYElZexSrV772AZmlEpq+rFnaYBMMp1ozKmksA9xhchIZDz/D2vzK0dxjojILza0eq1RyIc9JlhBcIoPVz9olbHxCY8EhUmtCEEnLezkzXG2JAbAfbthznxmvQIRK+R48osLzyYzoTG8jA6QMBlikGl3T7ac5KIu5WIjxHKo4ck3A4ouadzxU8dz9Ufw9hcSDyzClbxqnnCI2c9cy+BzgYy7B9CiNYzWXuRmCEX6kDy0XoZex36HmghaR+iJVN22avsgsR6qb3KEuvxal9g85mN8tYzib0KSq3HkfearZXO46LPP4PYq6zUeoBuVB0m9qobDOCuHNFePwyx10rkFABN9QxJSQMAQ6D+DBbb9RggILuyvqg4hAgB2ZX1RQVuCEhBqCwqECEeWDUFgRV3PVwQ//5FNa41wa1vKSB4SwpCOZcJCQRCP/542cklyIrwgIiQ9nCmlGcE/B36CYGrIBQhdsEbQmomeFDK1uMoh0DgASlZTyDNT4hLIcF6SU7GnjLmnU8rb3vVYwpJrSdIE0in5e8UCNTrAUmsJ6Xv1W3aXkirBhTIhvCr7oIgxzIigRv2tQ2kTjoqISaK1uOLUiwQp4hOFMz93rTb/dqhlHZ5Cpcz1rSgl2bPJMl7NyocTj6P6Vq9DYSQiK4XtitfHmtGhOGTE/Y6zpsJs0aQ5eQRxPXq/2JWK5yns/PzelLHoIE3fHpkVjHAKvvrPQprIKMymXvUVxC5xn4DkXsUfQORu9c3ENmw9AUksV41pGg9RPIFSMl6hH1PQHiiIt8HSPne8Dx1x3ZrXr0Dj6dm+4CUVhtx0PJIT80+9FuJcVmvmRGJM1i2MFWG64a88eE9ZmyOmZbhREhNjCjzo+Efew16mea0NoYAAAAASUVORK5CYII=' id='logo'>

			<?php echo $data["content"]; ?>
		</div>

	</body>
</html>
