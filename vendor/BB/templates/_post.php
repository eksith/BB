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
	<div class='post'>
		<p class='meta'><a href='/post/<?php echo $data['post']->id; ?>'><?php echo $data['post']->title; ?></a>
		<time datetime='<?php echo $data['post']->created_at; ?>'><?php echo gmdate( 'D, d M Y', $data['post']->created_at ) ?></time></p>
		<?php echo $data['post']->body; ?>
	</div>
</div>
<?php  \BB\Display::printJS(); ?>
<script type='text/javascript' src='m.js'></script>
</body>
</html>
