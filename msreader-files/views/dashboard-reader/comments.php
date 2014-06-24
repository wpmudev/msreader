<?php
//new walker for listing comments
class MSReader_Dashboard_Walker_Comment extends Walker_Comment {
	function end_el( &$output, $comment, $depth = 0, $args = array() ) {
		if ( !empty( $args['end-callback'] ) ) {
			ob_start();
			call_user_func( $args['end-callback'], $comment, $args, $depth );
			$output .= ob_get_clean();
			return;
		}
		if ( 'div' == $args['style'] )
			$output .= "</div>";
		else
			$output .= "</li>";
	}

    protected function html5_comment( $comment, $depth, $args ) {
    	global $msreader_comment_level;

		$tag = ( 'div' === $args['style'] ) ? 'div' : 'li'; ?>
<<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" class="comment<?php echo empty( $args['has_children'] ) ? '' : ' parent'; ?>">
	<article data-comment_id="<?php comment_ID(); ?>" id="comment-<?php comment_ID(); ?>" class="comment-body<?php echo ('0' == $comment->comment_approved) ? ' unapproved' : ' approved'; ?>">
		<footer class="comment-meta">

			<div class="comment-author vcard">
				<?php if ( 0 != $args['avatar_size'] ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
				<?php printf( __( '%s <span class="says">says:</span>', 'wmd_msreader' ), sprintf( '<b class="fn author">%s</b>', get_comment_author_link() ) ); ?>
			</div>

			<div class="comment-metadata">
				<time datetime="<?php comment_time( 'c' ); ?>">
					<?php printf( _x( '%1$s at %2$s', '1: date, 2: time', 'wmd_msreader' ), get_comment_date(), get_comment_time() ); ?>
				</time>
			</div>
		</footer>

		<div class="comment-content">
			<?php comment_text(); ?>
		</div>
		<div class="comment-tools">
			<?php 
			if ( current_user_can('moderate_comments') ) {
			?>
				<div class="comment-moderation row-actions">
					<span class="spinner spinner-save"></span>
					<?php if('0' == $comment->comment_approved ) { ?>
						<span class="approve"><a class="comment-approve" data-action="approve" href="#"><?php _e( 'Approve', 'wmd_msreader' ); ?></a></span>
					<?php } else { ?>
						<span class="unapprove"><a class="comment-unapprove" data-action="unapprove" href="#"><?php _e( 'Unapprove', 'wmd_msreader' ); ?></a></span>
					<?php } ?>
					 | <span class="trash"><a class="comment-spam" href="#" data-action="spam"><?php _e( 'Spam', 'wmd_msreader' ); ?></a></span> 
					 | <span class="trash"><a class="comment-trash" href="#" data-action="trash"><?php _e( 'Trash', 'wmd_msreader' ); ?></a>
				</div>
			<?php 
			}
			$depth = (isset($msreader_comment_level) && $msreader_comment_level > $depth) ? $msreader_comment_level : $depth;
			if(comments_open( $comment->comment_post_ID ) && $depth < $args['max_depth'] && !('0' == $comment->comment_approved && !current_user_can('moderate_comments'))) { ?>
			<div class="reply"<?php echo ('0' == $comment->comment_approved) ? ' style="display:none"' : ''; ?>>
				<button class="comment-reply button button-secondary"><?php _e( 'Reply', 'wmd_msreader' ); ?></button>
			</div>
			<?php 
			} 
			?>
		</div>
	</article>
	<?php
    }
}
$depth = 1;
if(get_option('thread_comments'))
	$depth = get_option('thread_comments_depth');

if(count($comments) > 0)
	wp_list_comments(
		array(
			'max_depth' => $depth, 
			'page' => $comments_page, 
			'per_page' => $comments_limit,
			'reverse_top_level' => true,
			'reverse_children'  => true,
			'format' => 'html5',
			'walker' => new MSReader_Dashboard_Walker_Comment()
		), 
		$comments
	);
else {
?>
<div id="msreader-no-comments">
	<?php _e( 'No comments yet :(', 'wmd_msreader' ); ?>
</div>
<?php
}
?>