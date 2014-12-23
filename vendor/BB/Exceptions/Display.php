<?php
namespace BB\Exceptions;

class Display implements \SplObserver {
	private $html =<<<HTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir='ltr'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<meta name='viewport' content='width=device-width' />
<title>Error</title>
<style type='text/css'>
* {
	margin:0;
	padding:0;
	border: 0;
	box-sizing: border-box;
	vertical-align: baseline;
}
html {
	background: #f1f1f1;
	min-width: 400px;
	text-size-adjust: 100%;
}
body {
	background: #fff;
	color: #555;
	font-family: sans-serif;
	margin: 2em auto;
	padding: 1em 2em;
	max-width: 800px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.13);
}
h1 {
	clear: both;
	color: #666;
	margin: 10px 0 0 0;
	padding: 0 0 7px 0;
	font: 400 24px sans-serif;
	border-bottom: 1px solid #dadada;
}
p {
	font-size: 14px;
	line-height: 1.5em;
	margin: 0 0 20px 0;
}
</style>
</head>
<body>
<h1>A site error has occured</h1>
<p>We are working quickly to resolve it quickly. Please try again in a few minutes. </p>
<p>Sorry for the inconvenience.</p>
</body>
</html>
HTML;
	
	public function update( \SplSubject $subject ) {
		if ( $subject->errType == 'exception' ) {
			
		}
	}
	
	private function catchException() {
		ob_end_clean();
		echo $this->html;
	}
}
