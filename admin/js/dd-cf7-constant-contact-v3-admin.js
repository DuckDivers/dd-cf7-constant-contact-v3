(function( $ ) {
	'use strict';
	$('document').ready(function(){
		$('#list').select2({
			"multiple" : true,
			"placeholder" : {
				id : '',
				text : 'Please Choose'
			}
		});
		$('.select2-field').select2();
	});

})( jQuery );
