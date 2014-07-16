(function($) {
	$(document).ready(function() {
		$('#msreader-page-name').on('input', function() {
			$('.reader-menu-page').text($(this).val());
		});

		$('#msreader-control-modules .open-module-options').click(function(event) {
			event.preventDefault();

			var target = $(this).attr('data-module');
			var position = $(this).position();

			if($(this).hasClass('active')) {
				$('#msreader-control-modules .sub-options[data-module="'+target+'"]').hide();
				$(this).removeClass('active');
			}
			else {
				$('#msreader-control-modules .sub-options[data-module="'+target+'"]').show();
				console.log(position);
				$('#msreader-control-modules .sub-options[data-module="'+target+'"] .postbox').css('min-width', position.left -150);
				$(this).addClass('active');
			}
		});
	});
})(jQuery);