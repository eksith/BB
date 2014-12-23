<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir='ltr'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<meta name='viewport' content='width=device-width' />
<title><?php echo $data['post']->title; ?></title>
<link rel='stylesheet' href='s.css' />
<link rel='shortcut icon' type='image/png' href='assets/img/favicon.png' />
</head>
<body>
<nav><ul><li><a href='.'><img src='assets/img/favicon.png' /> Home</a></li><li><a href='/firehose'>New</a></li><li><a href='/archive.html'>Archive</a></li></ul></nav>
<h1><?php echo $data['post']->title; ?></h1>
<div class='posts'>
<form method='post' action='/posts/<?php echo $data['post']->id; ?>'>
	<legend>Editing <?php echo $title; ?></legend>
	<p><label for='title'>Title</label><input name='title' id='title' type='text' maxlength='60' required value='<?php echo $data['post']->title; ?>' /></p>
	<p><label for='body'>Message <span><a href='formatting.html' rel='tooltip' title='Simple HTML allowed'>formatting help</a></span></label>
	<textarea name='body' id='body' rows='5' cols='60' required><?php echo $data['post']->raw; ?></textarea></p>
	<p><input type='submit' class='button' value='Post'/></p>
</form>
</div>
<?php BB\Display::printJS(); ?>
<script type='text/javascript' src='m.js'></script>
</body>
</html>
