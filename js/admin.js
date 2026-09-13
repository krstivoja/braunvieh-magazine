jQuery(document).ready(function ($) {
	let counter = 0;
	$('td.acf-field[data-name="schnellzugriffe"]').each(function(){
		let checked = $(this).find('input[type=checkbox]').attr('checked');
        if(checked=='checked'){
            $(this).css('opacity','1');
			counter++;
        }
	});
	if(counter===0){
		$('td.acf-field[data-name="schnellzugriffe"]').css('opacity','1');
	}
});