var MSReader = function($) {
	msreader_main_query.current_post = 0;
	msreader_main_query.ajax_loading = 0;
	msreader_main_query.end = 0;
	msreader_main_query.page = parseInt(msreader_main_query.page)-1;
	msreader_main_query.last_date = parseInt(msreader_main_query.last_date);
	msreader_main_query.comments_page = parseInt(msreader_main_query.comments_page);
	msreader_main_query.limit = parseInt(msreader_main_query.limit);
	msreader_main_query.load_count = 0;
	msreader_main_query.comments_end = 0;
	msreader_main_query.comments_offset = 0;
	msreader_main_query.comments_removed = 0;
	msreader_main_query.comment_reply_to = 0;

	$(document).ready(function() {
		//handle sticky sidebar on scroll
		var sidebar = $('.msreader-sidebar');
		var sidebar_height = sidebar.height();
		var sidebar_position_top = sidebar.position().top;
		if(sidebar_height > ($(window).height() - $('#msreader-dashboard h2').height() - $('#wpadminbar').height()))
			sidebar.removeClass('floating');
		$(window).on('scroll resize', function(){
			if($('#wpcontent').length && $(window).width() > 850 && $(window).scrollTop() >= sidebar_position_top && sidebar_height < ($(window).height() - $('#wpadminbar').height())) {
				//if(!sidebar.hasClass('floating')) {
					sidebar.removeClass('floating').css('top', $('#wpadminbar').height()).css('left', sidebar.position().left + parseInt($('#wpcontent').css('margin-left')) + parseInt($('#wpcontent').css('padding-left'))).addClass('floating');
				//}
			}
			else
				sidebar.removeClass('floating');
		});

		//initial load on start
		display_posts_ajax();

		//load more posts then limit on huge screens
		var fill_the_screen_with_posts = setInterval(function () {
			if(msreader_main_query.page > 0) {
				if(!msreader_main_query.end && $('#wpbody-content').height() < $(window).height() && msreader_main_query.load_count >= msreader_main_query.limit) {
					if(!msreader_main_query.ajax_loading)
						display_posts_ajax();
				}
				else {
					/*
					msreader_main_query.end should be set in latest "display_posts_ajax" so this one is hopefully not needed
					if(msreader_main_query.load_count < msreader_main_query.limit)
						msreader_main_query.end = 1;
					*/

					clearInterval(fill_the_screen_with_posts);
				}

			}
        },500);

		//load on windows scrolling
		$(window).scroll(function() {
			if(!msreader_main_query.ajax_loading && !msreader_main_query.end && $(this).scrollTop() >= $(document).height() - $(this).height() - $(this).height()/3) {
				display_posts_ajax();
			}
		});

		//handle opening of single post
		$('.msreader-posts').on('click', '.msreader-open-post', function(){
			display_post_ajax($(this).parents('.msreader-post'));
		})
		//handle closing of single post
		$('.msreader-post-overlay').on('click', '.msreader-post-header-navigation .close', function(){
			$('.msreader-post-overlay').hide();
			$('body').removeClass('theme-overlay-open');
		})
		//handle opening of next post
		$('.msreader-post-overlay').on('click', '.msreader-post-header-navigation .right', function(){
			display_post_ajax(msreader_main_query.current_post.next());
		})
		//handle opening of previous post
		$('.msreader-post-overlay').on('click', '.msreader-post-header-navigation .left', function(){
			display_post_ajax(msreader_main_query.current_post.prev());
		})

		//handle loading previous comments for post
		$('.msreader-post-overlay').on('click', '.load-previous-comments', function(){
			var button = $(this);
			var blog_id = msreader_main_query.current_post.attr("data-blog_id");
			var post_id = msreader_main_query.current_post.attr("data-post_id");

			if(blog_id && post_id) {
				button.parent().find('.spinner').show();
				comments_args = {
					offset: msreader_main_query.comments_offset,
					comments_removed: msreader_main_query.comments_removed
				};
				args = {
					source: 'msreader',
					action: 'dashboard_display_comments_ajax',
					blog_id: blog_id,
					post_id: post_id,
					comments_page: msreader_main_query.comments_page + 1,
					comments_args: comments_args
				};

				$.post(ajaxurl, args, function(response) {
					button.parent().find('.spinner').hide();
					if(response && response != 0) {
						response = $($.parseHTML(response));

						count = response.length;
						if(count > 0)
							$('.msreader-post-overlay .msreader-comments .comments').prepend(response);

						//check if this is last comment
						if(msreader_main_query.comments_limit > count)
							load_previous_comments_button_action('disable');

						msreader_main_query.comments_page = msreader_main_query.comments_page + 1;
					}
					else
						load_previous_comments_button_action('disable');
				});
			}
		})

		//handle publishing of post
		$('.msreader-post-overlay').on('click', '.msreader-post-header-navigation .links .publish', function(){
			var blog_id = msreader_main_query.current_post.attr("data-blog_id");
			var post_id = msreader_main_query.current_post.attr("data-post_id");
			var nonce = $(this).attr("data-nonce");

			publish_post(blog_id, post_id, nonce);
		})
		$('.msreader-posts').on('click', '.msreader-post-actions .publish', function(){
			var blog_id = $(this).parents('.msreader-post').attr("data-blog_id");
			var post_id = $(this).parents('.msreader-post').attr("data-post_id");
			var nonce = $(this).attr("data-nonce");

			publish_post(blog_id, post_id, nonce);
		})

		
		//set reply data
		$('.msreader-post-overlay').on('click', '.comments .comment-reply', function(){
			var window_width = get_window_width();
			var comment = $(this).parents('.comment-body');
			var form = $(this).parents('.msreader-post').find('.msreader-add-comment');
			var reply_info = form.find('.reply-info');
			var private_comment_option = form.find('#comment-private-option');
			var comment_offset;

			$('.msreader-post-overlay .comments .comment-reply').removeClass('button-primary');
			$(this).addClass('button-primary');
			msreader_main_query.comment_reply_to = comment.attr("data-comment_id");
			
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

			//check if we need to force mark as private
			if(comment.hasClass('private')) {
				private_comment_option.find('#comment-private').prop('checked', true).prop('disabled', true);
				private_comment_option.addClass('disabled');
			}

			//fade out old text
			if(reply_info.css('display') == 'inline')
				reply_info.fadeOut('fast');

			reply_info.find('.reply-parent-name').text(comment.find('.author').text());
			reply_info.delay('200').fadeIn('fast');
		})

		//cancel reply stuff
		$('.msreader-post-overlay').on('click', '.msreader-add-comment .reply-cancel', function(event){
			event.preventDefault;

			var window_width = get_window_width();
			var form = $(this).parents('.msreader-add-comment');
			var private_comment_option = form.find('#comment-private-option');

			private_comment_option.find('#comment-private').prop('checked', false).prop('disabled', false);
			private_comment_option.removeClass('disabled');

			msreader_main_query.comment_reply_to = 0;
			$(this).parents('.msreader-post-overlay').find('.comments .comment-reply').removeClass('button-primary');
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
			var window_width = get_window_width();

			var form = $(this).parents('.msreader-add-comment');
			var blog_id = msreader_main_query.current_post.attr("data-blog_id");
			var post_id = msreader_main_query.current_post.attr("data-post_id");
			var article;
			var level;
			var comment_offset;

			if(blog_id && post_id) {
				form.find('.spinner').show();

				if(msreader_main_query.comment_reply_to) {
					article = $( 'article[data-comment_id="'+msreader_main_query.comment_reply_to+'"]' ).parent();
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
					comment_parent: msreader_main_query.comment_reply_to,
					level: level,
					private: form.find('#comment-private').prop('checked') == true ? 1 : '',
				};
				args = {
					source: 'msreader',
					action: 'dashboard_add_get_comment_ajax',
					blog_id: blog_id,
					post_id: post_id,
					comment_add_data: comment_add_data,
					nonce: form.find('#nonce_add_comment').val()
				};

				$.post(ajaxurl, args, function(response) {
					form.find('.spinner').hide();
					if(response && response != 0) {
						response = $($.parseHTML(response));
						
						count = response.length;
						if(count > 0 && response.hasClass('comment')) {
							$('#msreader-no-comments').remove();
							//clear out old text in form
							form.find('#comment').val('');

							if(msreader_main_query.comment_reply_to) {
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

							if(!msreader_main_query.comment_reply_to || response.is('li:last'))
								comment_offset = $(".msreader-comments").scrollTop() + $(".msreader-comments").height();

							if(window_width <= 1024 && msreader_main_query.comment_reply_to) {
								form.slideUp(function() {
									$('.msreader-post-overlay .msreader-post').scrollTop(comment_offset);
									response.slideDown();

									$(form).parents('.msreader-post').find('.msreader-comments').after(form);
									form.slideDown();

									//clear out reply stuff
									if(msreader_main_query.comment_reply_to) {
										msreader_main_query.comment_reply_to = 0;
										$('.msreader-post-overlay .comments .comment-reply').removeClass('button-primary');
									}
								});
							}
							else {
								response.slideDown();
								$(".msreader-comments").animate({ scrollTop: comment_offset }, 1000, function() {
									

									//clear out reply stuff
									if(msreader_main_query.comment_reply_to) {
										msreader_main_query.comment_reply_to = 0;
										$('.msreader-post-overlay .comments .comment-reply').removeClass('button-primary');
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
			var blog_id = msreader_main_query.current_post.attr("data-blog_id");
			var post_id = msreader_main_query.current_post.attr("data-post_id");
			var comment_id = comment.attr("data-comment_id");
			var action = button.attr("data-action");
			var comment_offset = $(".msreader-comments").scrollTop() + comment.position().top + comment.height() - ($(".msreader-comments").height()/2);
			var nonce = comment.parents('.comments').attr("data-nonce");

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
							source: 'msreader',
							action: 'dashboard_moderate_get_comment_ajax',
							blog_id: blog_id,
							post_id: post_id,
							comment_moderate_data: comment_moderate_data,
							nonce: nonce
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
									comment_holder.find('.comment-'+action).text(msreader_translation[response]);
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
							else if (window.console)
								console.log(response);
						});
					}
					else
						button.parents('.comment-moderation').removeClass('visible');
				});
			}
		});

		//handle general popup opening
		$('#wpbody-content').on('click', '.msreader-show', function(event) {
			event.preventDefault();

			var button = $(this);

			if(button.attr('href') != '#')
				$(button.attr('href')).show();
			else {
				var popup = $(this).parents('.msreader-popup-container').find('.msreader-popup').removeClass('msreader-popup-bottom').removeClass('msreader-popup-left');
				var overlay_post = button.parents('.msreader-post-holder');
				var popup_container = button.parents('.msreader-popup-container');
				if(overlay_post.length && ((popup_container.position().left + popup_container.width()-15) < popup.width()) ) {
					popup.addClass('msreader-popup-left');
				}
				else if((button.offset().top- $(window).scrollTop()) < (popup.height() + 200))
					popup.addClass('msreader-popup-bottom');

				popup.show();
			}
		});
		//handle general popup closing
		$('#wpbody-content').on('click', '.msreader-hide', function(event) {
			event.preventDefault();
			
			var button = $(this);

			if(button.attr('href') != '#')
				$(button.attr('href')).hide();
			else
				$(this).parents('.msreader-popup').hide();
		});

	});

	this.display_post_ajax = function(post) {
		var blog_id = post.attr("data-blog_id");
		var post_id = post.attr("data-post_id");

		post.find('.spinner').show();
		$('.msreader-post-header-navigation').find('.spinner').show();

		if(blog_id && post_id) {
			//reset comments options
			msreader_main_query.comments_page = 1;
			msreader_main_query.comments_end = 0;
			msreader_main_query.comment_reply_to = 0;
			msreader_main_query.comments_offset = 0;
			msreader_main_query.comments_removed = 0;

			//scroll to post so next posts will load
			$("html, body").animate({ scrollTop: post.offset().top-$('#wpadminbar').height()-20 });

			args = {
				source: 'msreader',
				action: 'dashboard_display_post_ajax',
				blog_id: blog_id,
				post_id: post_id,
			};

			$.post(ajaxurl, args, function(response) {
				post.find('.spinner').fadeOut(100, function() {$(this).hide()});
				$('.msreader-post-header-navigation .spinner').fadeOut(200, function() {$(this).hide()});;
				$('body').addClass('theme-overlay-open');

				if(response && response != 0) {
					response = $($.parseHTML(response));
					msreader_main_query.current_post = post;

					//add post body to overlay
					var msreader_post_overlay = $('.msreader-post-overlay');
					msreader_post_overlay.find('.msreader-post-wrap').html(response);
					msreader_post_overlay.fadeIn(200);

					//scroll comments to the bottom
					$(".msreader-comments").scrollTop($('.msreader-comments .comments').height());

					//check if this is last comment
					var count = msreader_post_overlay.find('.msreader-comments .comments > li').length;
					if(msreader_main_query.comments_limit > count)
						load_previous_comments_button_action('disable');

					//disable next/previous if does not exists
					if(!msreader_main_query.current_post.next('.msreader-post').hasClass('msreader-post'))
						msreader_post_overlay.find("button.right").prop('disabled', true);
					else
						msreader_post_overlay.find("button.right").prop('disabled', false);
					if(!msreader_main_query.current_post.prev('.msreader-post').length)
						msreader_post_overlay.find("button.left").prop('disabled', true);
					else
						msreader_post_overlay.find("button.left").prop('disabled', false);
				}
			});
		}
	}

	//control the status of button responsible for loading older comments
	this.load_previous_comments_button_action = function(action) {
		var button = $('.msreader-post-overlay .load-previous-comments');
		if(action == 'disable') {
			msreader_main_query.comments_end = 1;

			button.prop('disabled', true);
		}
	}

	this.display_posts_ajax = function() {
		msreader_main_query.ajax_loading = 1;

		$('.msreader-post-loader').show();

		args = {
			source: 'msreader',
			action: 'dashboard_display_posts_ajax',
			page: msreader_main_query.page + 1,
			last_date: msreader_main_query.last_date,
			module: msreader_main_query.module,
			args: msreader_main_query.args,
		};

		$.post(ajaxurl, args, function(response) {
			$('.msreader-post-loader').hide();
			if(response.success) {
				msreader_main_query.load_count = response.data.count;
				
				$('.msreader-post-loader').before(response.data.html);

				if(msreader_main_query.limit > msreader_main_query.load_count)
					msreader_main_query.end = 1;

				msreader_main_query.last_date = $('.msreader-posts .msreader-post:last .post-time').attr('data-post_time');
				msreader_main_query.page = msreader_main_query.page + 1;
				msreader_main_query.ajax_loading = 0;
			}
			else {
				msreader_main_query.end = 1;
				if($('.msreader-posts .msreader-post:visible').length == 0)
					$('#msreader-404').show();
			}
		});		
	}

	this.remove_post_from_list = function(blog_id, post_id, selector, fade) {
		if(typeof(fade)==='undefined') fade = 1;

		if(typeof(selector)==='undefined')
			var selector = $( ".msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"']" );
		if(fade)
			selector.find('.fade-bg').show();
		else
			selector.hide();

		if(fade && $('.msreader-posts .msreader-post:visible').length == 0)
			$('#msreader-404').show();
	}

	this.add_post_to_list = function(blog_id, post_id, selector, fade) {
		if(typeof(fade)==='undefined') fade = 1;

		if(typeof(selector)==='undefined')
			var selector = $( ".msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"']" );
		if(fade)
			selector.find('.fade-bg').hide();
		else
			selector.show();

		if(fade && $('.msreader-posts .msreader-post:visible').length != 0)
			$('#msreader-404').hide();
	}

	this.publish_post = function(blog_id, post_id, nonce) {
		if(blog_id && post_id) {
			$(".msreader-post-header-navigation .spinner, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .spinner").show();

			args = {
				source: 'msreader',
				action: 'dashboard_publish_post',
				blog_id: blog_id,
				post_id: post_id,
				nonce: nonce
			};

			$.post(ajaxurl, args, function(response) {
				$(".msreader-post-header-navigation .spinner, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .spinner").fadeOut(200, function() {$(this).hide()});

				if(response && response != 0) {
					$( ".msreader-post-overlay .msreader-post-header-navigation .links .publish, .msreader-post[data-blog_id='"+blog_id+"'][data-post_id='"+post_id+"'] .msreader-post-actions .publish" ).text(response).prop( "disabled", true );

					$( ".msreader-post-overlay" ).find('#comment-private-option').removeClass('disabled').find('input').prop('disabled', false).prop('checked', false);
					remove_post_from_list(blog_id, post_id);
				}
			});
		}
	}

	this.get_window_width = function() {
		var window_width = $(window).width();
		var width = window.orientation == 0 ? window.screen.width : window.screen.height;
		if (navigator.userAgent.indexOf('Android') >= 0 && window.devicePixelRatio)
			width = width / window.devicePixelRatio;
		if(window_width < width)
			width = window_width;

		return width;
	}

	this.refresh_sidebar = function() {
		args = {
			source: 'msreader',
			action: 'dashboard_get_reader_sidebar_ajax',
			current_url: window.location.href
		};

		$.post(ajaxurl, args, function(response) {
			if(response && response != 0) {
				$('.msreader-sidebar').html(response);
			}
		});
	}

	return this;
};
var msreader = MSReader(jQuery);