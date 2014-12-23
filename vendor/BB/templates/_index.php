<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir='ltr'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<meta name='viewport' content='width=device-width' />
<title>Forum</title>
<link rel='stylesheet' href='s.css' />
<link rel='shortcut icon' type='image/png' href='assets/img/favicon.png' />
</head>
<body>
<nav class='main'><ul><li class='active'><a href='.'><img src='assets/img/favicon.png' /> Home</a></li><li><a href='/firehose'>New</a></li><li><a href='/archive.html'>Archive</a></li></ul></nav>

<?php if ( $thread->hasPosts() ) { ?>
<h1>This just in</h1>
<ul class='posts'>
<?php

$thread->each( function( $post ) { echo <<<HTML
	<li><a href='/thread/{$post->root_id}'>{$post->title}</a>
		<time datetime='{$post->created_at}'>{${gmdate( 'D, d M Y', $post->created_at )}}</time></li>
HTML;
}); ?>

</ul>
<?php } else { ?>
<p>No posts found</p>
<?php } 
if ( CANPOST ) { ?>
<form method='post' action='/'>
	<legend>New topic</legend>
	<p><label for='title'>Title</label><input name='title' id='title' type='text' maxlength='60' required /></p>
	<p><label for='body'>Message <span><a href='formatting.html' rel='tooltip' title='Simple HTML allowed'>formatting help</a></span></label><textarea name='body' id='body' rows='5' cols='60' required></textarea></p>
	<p><input type='submit' class='button' value='Post'/></p>
</form>
<?php }
\BB\Display::printJS(); ?>

<script type='text/javascript' src='m.js'></script>
</body>
</html>
