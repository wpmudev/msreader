(function($) {
	var current_post = 0;

	$(document).ready(function() {
		//handle sticky sidebar on scroll
		var sidebar = $('.msreader-sidebar');
		var sidebar_height = sidebar.height();
		var sidebar_position_top = sidebar.position().top;
		if(sidebar_height > ($(window).height() - $('#msreader-dashboard h2').height() - $('#wpadminbar').height()))
			sidebar.removeClass('floating');
		$(window).on('scroll resize', function(){
			console.log($(window).width());
			if($(window).width() > 850 && $(window).scrollTop() >= sidebar_position_top && sidebar_height < ($(window).height() - $('#wpadminbar').height())) {
				//if(!sidebar.hasClass('floating')) {
					sidebar.removeClass('floating').css('top', $('#wpadminbar').height()).css('left', sidebar.position().left + parseInt($('#wpcontent').css('margin-left'))).addClass('floating');
				//}
			}
			else
				sidebar.removeClass('floating');
		});

		//handle main query post loading on scroll
		msreader_main_query.ajax_loading = 0;
		msreader_main_query.end = 0;
		msreader_main_query.page = parseInt(msreader_main_query.page);
		msreader_main_query.comments_page = parseInt(msreader_main_query.comments_page);
		msreader_main_query.comments_end = 0;
		msreader_main_query.comments_offset = 0;
		msreader_main_query.comments_removed = 0;
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
			$('body').removeClass('theme-overlay-open');
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
				button.parent().find('.spinner').show();
				comments_args = {
					offset: msreader_main_query.comments_offset,
					comments_removed: msreader_main_query.comments_removed
				};
				args = {
					action: 'dashboard_display_comments_ajax',
					blog_id: blog_id,
					post_id: post_id,
					comments_page: msreader_main_query.comments_page + 1,
					comments_args: comments_args
				};

				$.post(ajaxurl, args, function(response) {
					button.parent().find('.spinner').hide();
					console.log(response);
					if(response && response != 0) {
						response = $($.parseHTML(response));

						count = response.length;
						if(count > 0)
							$('.msreader-post-overlay .msreader-comments .comments').prepend(response);

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
			var window_width = msreader_get_window_width();
			var comment = $(this).parents('.comment-body');
			var form = $(this).parents('.msreader-post').find('.msreader-add-comment');
			var reply_info = form.find('.reply-info');
			var comment_offset;

			$('.msreader-post-overlay .comments .comment-replay').removeClass('button-primary');
			$(this).addClass('button-primary');
			msreader_main_query.comment_replay_to = comment.attr("data-comment_id");
			
			//scroll to reply comment
			if(window_width <= 1024) {
				form.hide();
					comment.after(form);
					form.show();
					comment_offset = $('.msreader-post-holder').height() + form.position().top - comment.height();
					$('.msreader-post-overlay .msreader-post').scrollTop(comment_offset);
					
			}
			else {
				comment_offset = $(".msreader-comments").scrollTop() + comment.position().top + comment.height() - ($(".msreader-comments").height()/2);
				$(".msreader-comments").animate({ scrollTop: comment_offset }, 300);
			}

			//fade out old text
			if(reply_info.css('display') == 'inline')
				reply_info.fadeOut('fast');

			reply_info.find('.reply-parent-name').text(comment.find('.author').text());
			reply_info.delay('200').fadeIn('fast');
		})

		//cancel replay stuff
		$('.msreader-post-overlay').on('click', '.msreader-add-comment .reply-cancel', function(event){
			event.preventDefault;

			var window_width = msreader_get_window_width();

			msreader_main_query.comment_replay_to = 0;
			$(this).parents('.msreader-post-overlay').find('.comments .comment-replay').removeClass('button-primary');
			$(this).parents('.reply-info').fadeOut('fast');

			if(window_width <= 1024) {
				var form = $(this).parents('.msreader-add-comment');
				form.hide();
				$(this).parents('.msreader-post').find('.msreader-comments').after(form);
				form.show();
			}
		})

		//add comment to post
		$('.msreader-post-overlay').on('click', '.msreader-add-comment #submit', function(){
			var window_width = msreader_get_window_width();

			var form = $(this).parents('.msreader-add-comment');
			var blog_id = current_post.attr("data-blog_id");
			var post_id = current_post.attr("data-post_id");
			var article;
			var level;
			var comment_offset;

			if(blog_id && post_id) {
				form.find('.spinner').show();

				if(msreader_main_query.comment_replay_to) {
					article = $( 'article[data-comment_id="'+msreader_main_query.comment_replay_to+'"]' ).parent();
					level = article.parents('ul.children').length;
					level = level + 2;

					if(window_width <= 1024)
						comment_offset = $('.msreader-post-holder').height() + article.position().top + article.height() - ($(".msreader-post-overlay .msreader-post").height()/2);
					else
						comment_offset = $(".msreader-comments").scrollTop() + article.position().top + article.height() - ($(".msreader-comments").height()/2);
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
					form.find('.spinner').hide();
					if(response && response != 0) {
						console.log(response);
						response = $($.parseHTML(response));
						
						count = response.length;
						if(count > 0 && response.hasClass('comment')) {
							$('#msreader-no-comments').remove();
							//clear out old text in form
							form.find('#comment').val('');

							if(msreader_main_query.comment_replay_to) {
								var article_children = article.find('ul.children:first');

								if(article_children.length)
									article_children.append(response.hide());
								else {
									article.append('<ul class="children"></ul>');
									article.find('ul.children:first').append(response.hide());
								}
							}
							else {
								$('.msreader-comments .comments').append(response);
								msreader_main_query.comments_offset = msreader_main_query.comments_offset +1;
							}

							if(!msreader_main_query.comment_replay_to || response.is('li:last'))
								comment_offset = $(".msreader-comments").scrollTop() + $(".msreader-comments").height();

							if(window_width <= 1024 && msreader_main_query.comment_replay_to) {
								form.slideUp(function() {
									$('.msreader-post-overlay .msreader-post').scrollTop(comment_offset);
									response.slideDown();

									$(form).parents('.msreader-post').find('.msreader-comments').after(form);
									form.slideDown();

									//clear out reply stuff
									if(msreader_main_query.comment_replay_to) {
										msreader_main_query.comment_replay_to = 0;
										$('.msreader-post-overlay .comments .comment-replay').removeClass('button-primary');
									}
								});
							}
							else {
								response.slideDown();
								$(".msreader-comments").animate({ scrollTop: comment_offset }, 1000, function() {
									

									//clear out reply stuff
									if(msreader_main_query.comment_replay_to) {
										msreader_main_query.comment_replay_to = 0;
										$('.msreader-post-overlay .comments .comment-replay').removeClass('button-primary');
									}

									//clear out old text in form
									form.find('#comment').val('');
								});
							}

							form.find('.reply-info').fadeOut('fast');
						}
						else
							alert(response.text());
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
			var comment_offset = $(".msreader-comments").scrollTop() + comment.position().top + comment.height() - ($(".msreader-comments").height()/2);

			button.parents('.comment-moderation').addClass('visible');
			if(blog_id && post_id) {
				$(".msreader-comments").animate({ scrollTop: comment_offset }, '500', function() {
					
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

							if(response && response != 0) {
								//lets remove the comment when trashing or spamming
								if(action == 'trash' || action == 'spam') {
									if(!comment.parents('.children').length)
										msreader_main_query.comments_removed = msreader_main_query.comments_removed + 1;
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
			msreader_main_query.comments_offset = 0;
			msreader_main_query.comments_removed = 0;

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
				$('body').addClass('theme-overlay-open');

				if(response && response != 0) {
					response = $($.parseHTML(response));
					current_post = post;

					//add post body to overlay
					var msreader_post_overlay = $('.msreader-post-overlay');
					msreader_post_overlay.find('.msreader-post-wrap').html(response);
					msreader_post_overlay.fadeIn('fast');

					//scroll comments to the bottom
					$(".msreader-comments").scrollTop($('.msreader-comments .comments').height());

					//check if this is last comment
					var count = msreader_post_overlay.find('.msreader-comments .comments > li').length;
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

		$('.msreader-post-loader').show();

		args = {
			action: 'dashboard_display_posts_ajax',
			page: msreader_main_query.page + 1,
			module: msreader_main_query.module,
			args: msreader_main_query.args,
		};

		$.post(ajaxurl, args, function(response) {
			$('.msreader-post-loader').hide();
			if(response && response != 0) {
				response = $($.parseHTML(response));
				count = response.length;
				if(count > 0)
					$('.msreader-post-loader').before(response);

				if(msreader_main_query.limit > count)
					msreader_main_query.end = 1;

				msreader_main_query.page = msreader_main_query.page + 1;
				msreader_main_query.ajax_loading = 0;
			}
			else
				msreader_main_query.end = 1;
		});		
	}

	function msreader_get_window_width() {
		var window_width = $(window).width();
		var width = window.orientation == 0 ? window.screen.width : window.screen.height;
		if (navigator.userAgent.indexOf('Android') >= 0 && window.devicePixelRatio)
			width = width / window.devicePixelRatio;
		if(window_width < width)
			width = window_width;

		return width;
	}
})(jQuery);