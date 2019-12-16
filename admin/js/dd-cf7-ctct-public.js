(function ($) {
    'use strict';
    $('document').ready(function () {
		var lists = new Array;
		// set lists as array
		lists = $('input[data-controls]');
		// Loop through lists
        $.each( lists, function(index, value){
			var list = $(value).prop('id');
			$('#'+list).change(function(){
				if ($(this).is(":checked")){
                    var el = $('input[data-id="'+list+'"]');
                    $.each(el, function(index, value){
                        $(value).prop('value', $(value).data('value'));
                    });
				} else {
					$('input[data-id="'+list+'"]').val('');
				}
			});
		});
        jQuery('input[data-controls]').trigger('change');
	});
})(jQuery);