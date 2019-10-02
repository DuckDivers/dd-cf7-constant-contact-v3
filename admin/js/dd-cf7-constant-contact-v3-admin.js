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
        
        //$('#listChoice').change(function(){
//            alert('change');
//            $('input[name="list"]').val($('#listChoice').val());
//        });
        
    });
//    function update_list_value(){
//        var v = $('#listChoice').val();
//        $('input[name="list"]').prop('value', v);
//        $('input#tag-generator-panel-ctct-class').focus();
//    }
})( jQuery );
