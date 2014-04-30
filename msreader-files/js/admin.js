(function($) {
	var current_post = 0;

	$(document).ready(function() {
		//handle main query post loading on scroll
		msreader_main_query.ajax_loading = 0;
		msreader_main_query.end = 0;
		msreader_main_query.page = parseInt(msreader_main_query.page);
		msreader_main_query.comments_page = parseInt(msreader_main_query.comments_page);
		msreader_main_query.comments_end = 0;
		msreader_main_query.comments_offset = 0;
		msreader_main_query.comment_replay_to = 0;

		//load more posts then limit on huge screens
		var fill_the_screen_with_posts = setInterval(function () {
			if(!msreader_main_query.end && $('#wpbody-content').height() < $(window).height() && $('.msreader-posts').find('.msreader-post').length >= msreader_main_query.limit) {
	            if(!msreader_main_query.ajax_loading)
					msreader_display_posts_ajax();
			}
			else {
				if($('.msreader-posts').find('.msreader-post').length < msreader_main_query.limit)
					msreader_main_query.comments_end = 1;
				
				clearInterval(fill_the_screen_with_posts);
			}
        },500);

		//load on windows scrtolling
		$(window).scroll(function() {
			if(!msreader_main_query.ajax_loading && !msreader_main_query.end && $(this).scrollTop() >= $(document).height() - $(this).height() - $(this).height()/3) {
				msreader_display_posts_ajax();
			}
		});

		//handle opening of single post
		$('.msreader-posts').on('click', '.msreader-open-post', function(){
			msreader_display_post_ajax($(this).parents('.msreader-post'));
		})
		//handle closing of single post
		$('.msreader-post-overlay').on('click', '.msreader-post-header-navigation .close', function(){
			$('.msreader-post-overlay').hide();
		})
		//handle opening of next post
		$('.msreader-post-overlay').on('click', '.msreader-post-header-navigation .right', function(){
			msreader_display_post_ajax(current_post.next());
		})
		//handle opening of previous post
		$('.msreader-post-overlay').on('click', '.msreader-post-header-navigation .left', function(){
			msreader_display_post_ajax(current_post.prev());
		})

		//handle loading previous comments for post
		$('.msreader-post-overlay').on('click', '.load-previous-comments', function(){
			var button = $(this);
			var blog_id = current_post.attr("data-blog_id");
			var post_id = current_post.attr("data-post_id");

			if(blog_id && post_id) {
				comments_args = {
					offset: msreader_main_query.comments_offset
				};
				args = {
					action: 'dashboard_display_comments_ajax',
					blog_id: blog_id,
					post_id: post_id,
					comments_page: msreader_main_query.comments_page + 1,
					comments_args: comments_args
				};

				$.post(ajaxurl, args, function(response) {
					if(response && response != 0) {
						response = $($.parseHTML(response));

						count = response.length;
						if(count > 0)
							$('.msreader-post-overlay .msreader-post-comments .comments').prepend(response);

						//check if this is last comment
						if(msreader_main_query.comments_limit > count)
							msreader_load_previous_comments_button_action('disable');

						msreader_main_query.comments_page = msreader_main_query.comments_page + 1;
					}
					else
						msreader_load_previous_comments_button_action('disable');
				});
			}
		})

		
		//set replay data
		$('.msreader-post-overlay').on('click', '.comments .comment-replay', function(){
			var comment = $(this).parents('.comment-body');
			var form = $(this).parents('.msreader-post-content-comments').find('.msreader-add-comment');
			var reply_info = form.find('.reply-info');

			$('.msreader-post-overlay .comments .comment-replay').removeClass('button-primary');
			$(this).addClass('button-primary');
			msreader_main_query.comment_replay_to = comment.attr("data-comment_id");

			//scroll to reply comment
			var comment_offset = $(".msreader-post-comments").scrollTop() + comment.position().top + comment.height() - ($(".msreader-post-comments").height()/2);
			$(".msreader-post-comments").animate({ scrollTop: comment_offset }, 300, function() {});

			//fade out old text
			if(reply_info.css('display') == 'inline')
				reply_info.fadeOut('fast');

			reply_info.find('.reply-parent-name').text(comment.find('.author').text());
			reply_info.delay('200').fadeIn('fast');
		})

		//set replay data
		$('.msreader-post-overlay').on('click', '.msreader-add-comment .reply-cancel', function(event){
			event.preventDefault;

			msreader_main_query.comment_replay_to = 0;
			$(this).parents('.msreader-post-overlay').find('.comments .comment-replay').removeClass('button-primary');
			$(this).parents('.reply-info').fadeOut('fast');
		})

		//add comment to post
		$('.msreader-post-overlay').on('click', '.msreader-add-comment #submit', function(){
			var form = $(this).parents('.add-comment-form');
			var blog_id = current_post.attr("data-blog_id");
			var post_id = current_post.attr("data-post_id");
			var article;
			var level;
			var comment_offset;

			if(blog_id && post_id) {
				if(msreader_main_query.comment_replay_to) {
					article = $( 'article[data-comment_id="'+msreader_main_query.comment_replay_to+'"]' ).parent();
					level = article.parents('ul.children').length;
					level = level + 2;

					comment_offset = $(".msreader-post-comments").scrollTop() + article.position().top + article.height() - ($(".msreader-post-comments").height()/2);
					console.log(comment_offset);
				}
				else
					level = 1;

				comment_add_data = {
					comment: form.find('#comment').val(),
					comment_parent: msreader_main_query.comment_replay_to,
					level: level,
				};
				args = {
					action: 'dashboard_add_get_comment_ajax',
					blog_id: blog_id,
					post_id: post_id,
					comment_add_data: comment_add_data,
				};

				$.post(ajaxurl, args, function(response) {
					if(response && response != 0) {
						response = $($.parseHTML(response));
						count = response.length;
						if(count > 0) {
							if(msreader_main_query.comment_replay_to) {
								var article_children = article.find('ul.children');

								if(article_children.length)
									article_children.append(response.hide());
								else {
									article.append('<ul class="children"></ul>');
									article.find('ul.children').append(response.hide());
								}
							}
							else {
								$('.msreader-post-comments .comments').append(response);
								msreader_main_query.comments_offset = msreader_main_query.comments_offset +1;
							}
							if(!msreader_main_query.comment_replay_to)
								comment_offset = $(".msreader-post-comments").scrollTop() + response.position().top;

							$(".msreader-post-comments").animate({ scrollTop: comment_offset }, 1000, function() {
								response.slideDown();

								//clear out reply stuff
								if(msreader_main_query.comment_replay_to) {
									msreader_main_query.comment_replay_to = 0;
									$('.msreader-post-overlay .comments .comment-replay').removeClass('button-primary');
								}
							});

							form.find('.reply-info').fadeOut('fast');
						}
					}
				});
			}
		})

		//do comment moderation action
		$('.msreader-post-overlay').on('click', '.comments .comment-moderation a', function(event){
			event.preventDefault();

			var button = $(this);
			var comment = button.parents('.comment-body');
			var comment_holder = comment.parent();
			var blog_id = current_post.attr("data-blog_id");
			var post_id = current_post.attr("data-post_id");
			var comment_id = comment.attr("data-comment_id");
			var action = button.attr("data-action");
			var comment_offset = $(".msreader-post-comments").scrollTop() + comment.position().top + comment.height() - ($(".msreader-post-comments").height()/2);

			button.parents('.comment-moderation').addClass('visible');
			if(blog_id && post_id) {
				$(".msreader-post-comments").animate({ scrollTop: comment_offset }, '500', function() {
					
					var confirmed = 1;
					var has_replies = 0;

					if(comment.parent().hasClass('parent'))
						var has_replies = 1;	

					if(action == 'trash' || action == 'spam' || has_replies) {
						if(has_replies)
							var text = msreader_translation.confirm_child;
						else
							var text = msreader_translation.confirm;

						confirmed = confirm(text);
					}

					if(confirmed) {
						comment.find('.spinner').css('display', 'inline-block');

						comment_moderate_data = {
							comment_id: comment_id,
							action: action
						};
						args = {
							action: 'dashboard_moderate_get_comment_ajax',
							blog_id: blog_id,
							post_id: post_id,
							comment_moderate_data: comment_moderate_data,
						};

						$.post(ajaxurl, args, function(response) {
							comment.find('.spinner').hide();
							button.parents('.comment-moderation').removeClass('visible');
							console.log(response);
							if(response && response != 0) {
								//lets remove the comment when trashing or spamming
								if(action == 'trash' || action == 'spam') {
									if(response == 1)
										comment_holder.slideUp(function() {
											comment_holder.remove();
										});
								}
								//lets change texts and classes when unapproving or approving for comment and replies
								else {
									comment_holder.find('.comment-'+action).attr('data-action', response);
									comment_holder.find('.comment-'+action).parent().attr('class', response);
									comment_holder.find('.comment-'+action).text(response);
									comment_holder.find('.comment-'+action).attr('class', 'comment-'+response);

									if(response == 'approve') {
										comment_holder.find('.reply').hide();
										comment_holder.find('.comment-body').addClass('unapproved');
										comment_holder.find('.comment-body').removeClass('approved');
									}
									else {
										comment_holder.find('.reply').show();
										comment_holder.find('.comment-body').addClass('approved');
										comment_holder.find('.comment-body').removeClass('unapproved');
									}
								}						
							}
						});
					}
					else
						button.parents('.comment-moderation').removeClass('visible');
				});
			}
		})

	});

	function msreader_display_post_ajax(post) {
		var blog_id = post.attr("data-blog_id");
		var post_id = post.attr("data-post_id");

		post.find('.spinner').show();
		$('.msreader-post-header-navigation').find('.spinner').show();

		if(blog_id && post_id) {
			//reset comments options
			msreader_main_query.comments_page = 1;
			msreader_main_query.comments_end = 0;
			msreader_main_query.comment_replay_to = 0;

			//scroll to post so next posts will load
			$("html, body").animate({ scrollTop: post.offset().top-$('#wpadminbar').height()-20 });

			args = {
				action: 'dashboard_display_post_ajax',
				blog_id: blog_id,
				post_id: post_id,
			};

			$.post(ajaxurl, args, function(response) {
				post.find('.spinner').hide();
				$('.msreader-post-header-navigation').find('.spinner').hide();

				if(response && response != 0) {
					response = $($.parseHTML(response));
					current_post = post;

					//add post body to overlay
					var msreader_post_overlay = $('.msreader-post-overlay');
					msreader_post_overlay.find('.msreader-post-wrap').html(response);
					msreader_post_overlay.fadeIn('fast');

					//scroll comments to the bottom
					$(".msreader-post-comments").scrollTop($('.msreader-post-comments .comments').height());

					//check if this is last comment
					var count = msreader_post_overlay.find('.msreader-post-comments .comments > li').length;
					if(msreader_main_query.comments_limit > count)
						msreader_load_previous_comments_button_action('disable');

					//disable next/previous if does not exists
					if(!current_post.next().length)
						msreader_post_overlay.find("button.right").prop('disabled', true);
					else
						msreader_post_overlay.find("button.right").prop('disabled', false);
					if(!current_post.prev().length)
						msreader_post_overlay.find("button.left").prop('disabled', true);
					else
						msreader_post_overlay.find("button.left").prop('disabled', false);
				}
			});
		}
	}

	//control the status of button responsible for loading older comments
	function msreader_load_previous_comments_button_action(action) {
		var button = $('.msreader-post-overlay .load-previous-comments');
		if(action == 'disable') {
			msreader_main_query.comments_end = 1;

			button.prop('disabled', true);
		}
	}

	function msreader_display_posts_ajax() {
		msreader_main_query.ajax_loading = 1;

		args = {
			action: 'dashboard_display_posts_ajax',
			page: msreader_main_query.page + 1,
			module: msreader_main_query.module,
			args: msreader_main_query.args,
		};

		$.post(ajaxurl, args, function(response) {
			if(response && response != 0) {
				response = $($.parseHTML(response));
				count = response.length;
				if(count > 0)
					$('.msreader-posts').append(response);

				if(msreader_main_query.limit > count)
					msreader_main_query.end = 1;

				msreader_main_query.page = msreader_main_query.page + 1;
				msreader_main_query.ajax_loading = 0;
			}
			else
				msreader_main_query.end = 1;
		});		
	}
})(jQuery);