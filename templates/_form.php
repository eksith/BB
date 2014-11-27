</main><form action='<?php echo $action; ?>' method='post'>
	<input type='hidden' name='<?php echo field('root'); ?>' value='<?php echo $root; ?>' />
	<input type='hidden' name='<?php echo field('parent'); ?>' value='<?php echo $parent; ?>' />
	
<?php if ( $canEdit ) { ?>
	<legend>Editing</legend>
	<input type='hidden' name='<?php echo field('edit'); ?>' value='<?php echo $id; ?>' />
	<p><input type='text' name='<?php echo field('title'); ?>' placeholder='title' size='60' 
		value='<?php echo $posts[0]->title; ?>' /></p>
	<p><textarea name='<?php echo field('body'); ?>' 
		placeholder='body' rows='10' cols='60'><?php echo $posts[0]->raw; ?></textarea></p>
<?php } else { 
	if ( $parent || $id ) { 
		if ( empty( $posts ) || $root ) { ?>
	<legend>New Reply</legend>
<?php 		} else { ?>
	<legend>Reply to "<?php echo $posts[0]->title; ?>"</legend>
<?php 		}
	} else { ?>
	<legend>New Topic</legend>
<?php 	} // if ( $thread || $id )?>

	<p><input type='text' name='<?php echo field('title'); ?>' placeholder='title' size='60' 
		value='<?php echo $title; ?>'/></p>
	<p><textarea name='<?php echo field('body'); ?>' 
		placeholder='body' rows='5' cols='60'><?php echo $body; ?></textarea></p>
<?php } // if ( $canEdit ) ?>
	<p class='func'><a href='formatting.html' rel='tooltip'>Formatting help</a></p>
	<p><input type='submit' value='Post' /></p>
</form>
