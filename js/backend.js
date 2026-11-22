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
  const title = document.querySelector('#post_expired h4');
  if (title) {
    dotclear.toggleWithLegend(title, document.querySelectorAll('#post_expired > :not(h4)'), {
      user_pref: 'dcx_postexpired_admin_form_sidebar',
      legend_click: true,
    });
  }
});