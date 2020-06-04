jQuery( document ).ready(function() {
	jQuery( ".ini-cardpart-cancle" ).on("click", function(){
		var cancle_price = jQuery("#ini_cancle_price").val();
		cancle_price = cancle_price.replace(/[^0-9]/g,"");
		if(cancle_price == "" || cancle_price == "0"){
			alert("취소할 금액을 입력해주세요.")
			return;
		}

		if(parseInt(cancle_price) > parseInt(jQuery('#ini_cancle_amount_limit').val())){
			alert("취소 가능한 금액을 입력해주세요.");
			return;
		}
		if(confirm("정말 취소하시겠습니까?")){
			var t_srl = jQuery("#ini_order_srl").val();
			var c_desc = jQuery("#ini_cancle_desc").val();
			var params = {
				order_srl:t_srl,
				cancle_desc:c_desc,
				cancle_part_price : cancle_price
			};
			exec_json('inipaystandard.dispInipaystandardAdminCardPartCancle', params, completeIniCardPartCancle);
		}
	});
});

function completeIniCardPartCancle(ret_obj){
	if(ret_obj['result'] == "ok"){
		alert("부분 취소 성공");
		window.location.reload();
	}else{
		var errmsg = "부분 취소 실패\r\n"+ret_obj['result_code']+":"+ret_obj['result_msg'];
		console.log(errmsg);
		alert(errmsg);
	}
}