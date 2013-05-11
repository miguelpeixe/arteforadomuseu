(function($) {

	var container;

	$(document).ready(function() {

		container = $('#artwork_images_box');

		if(!container.length)
			return false;

		var list = container.find('.image-list');
		var template = container.find('.template');

		list.find('.remove').click(function() {
			$(this).parents('li').remove();
			updateAttrs();
			return false;
		});

		container.find('.new-image').click(function() {

			var item = template.clone().removeClass('template');

			item.find('.remove').click(function() {
				$(this).parents('li').remove();
				updateAttrs();
				return false;
			});

			list.append(item);

			updateAttrs();

			return false;

		});

		function updateAttrs() {
			list.find('li').each(function(i) {
				$(this).find('.image-title').attr('name', 'artwork_links[' + i + '][title]');
				$(this).find('.image-file').attr('name', 'artwork_links[' + i + '][file]');
				$(this).find('.image-id').attr('name', 'artwork_links[' + i + '][id]');
				$(this).find('.image-id').val(i);
				$(this).find('.featured-input').val(i);
				$(this).find('.featured-input').attr('id', 'featured_image_' + i);
				$(this).find('.featured-label').attr('for', 'featured_image_' + i);
			});
		}

	});

})(jQuery);