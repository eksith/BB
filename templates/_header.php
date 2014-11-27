<!DOCTYPE html>
<html><head>
<title><?php 
if ( count( $posts ) ) {
	echo $posts[0]->title;
} else {
	echo 'Forum';
} ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel='stylesheet' href='assets/s.css' />
</head>
<body>
<div class='page'>
<main>
