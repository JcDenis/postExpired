/*global $, dotclear, datePicker */
'use strict';

$(() => {
  if (typeof datePicker === 'function') {
	var post_pe_field=document.getElementById('post_expired_date');
	if(post_pe_field!=undefined){
		var post_pe_dtPick=new datePicker(post_pe_field);
		post_pe_dtPick.img_top='1.5em';
		post_pe_dtPick.draw();
	}
  }
  $('#post_expired h4').toggleWithLegend(
  	$('#post_expired').children().not('h4'),
	{cookie:'dcx_postexpired_admin_form_sidebar',legend_click:true}
  );
});