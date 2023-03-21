/* -- BEGIN LICENSE BLOCK ----------------------------------
 *
 * This file is part of postExpired, a plugin for Dotclear 2.
 * 
 * Copyright (c) 2009-2013 Jean-Christian Denis and contributors
 * contact@jcdenis.fr http://jcd.lv
 * 
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * -- END LICENSE BLOCK ------------------------------------*/

$(function(){
	var post_pe_field=document.getElementById('post_expired_date');
	if(post_pe_field!=undefined){
		var post_pe_dtPick=new datePicker(post_pe_field);
		post_pe_dtPick.img_top='1.5em';
		post_pe_dtPick.draw();
	}
	$('#post_expired h4').toggleWithLegend(
		$('#post_expired').children().not('h4'),
		{cookie:'dcx_postexpired_admin_form_sidebar',legend_click:true}
	);
});