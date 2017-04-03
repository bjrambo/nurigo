
	function storeInsertOrder() {
		var f = document.getElementById('fo_insert_order');
		return procFilter(f, insert_order);
	}



	function do_order() {
		var cartnos = makeList();
		location.href = current_url.setQuery('act','dispNproductOrderItems').setQuery('cartnos',cartnos);
	}

	function calculate_payamount(mileage) {
		var payment_amount = total_price - mileage;
		return payment_amount;
	}

	(function($) {
		jQuery(function($) {
			$('#popAddressBook').click(function() {
				var url = current_url.setQuery('act','dispNproductAddressList');
				popup_modal(url, '배송주소록 관리', 600, 400);
			});
			$('#popRecentAddress').click(function() {
				var url = current_url.setQuery('act','dispNproductRecentAddress');
				popup_modal(url, '최근배송지에서 선택', 600, 400);
			});

			$('input[name=input_mileage]').keyup(function() {
				var use_mileage = $(this).val();

				// trim except numbers
                                var reg_mileage = new RegExp("^[0-9\.]+$");
                                if (!use_mileage.match(reg_mileage)) use_mileage = 0;

				// fix input value
				if (use_mileage > my_mileage) use_mileage = my_mileage;
				if (use_mileage < 0) use_mileage = 0;

				raw_mileage = getRawPrice(use_mileage);
Z
				if (total_price < raw_mileage && total_price <= my_mileage)
				{
					raw_mileage = total_price;
					$(this).val(getPrice(raw_mileage));
				}
				if (raw_mileage > my_mileage)
				{
					raw_mileage = my_mileage;
					$(this).val(getPrice(my_mileage));
				}
				if (use_mileage < 0)
				{
					raw_mileage = 0;
					$(this).val(0);
				}

				$('input[name=use_mileage]').val(raw_mileage);

				var payment_amount = calculate_payamount(raw_mileage);
				$('#mileage_amount').text(getPrice(raw_mileage));
				$('#payment_amount').text(getPrice(payment_amount));
			});
		});
	}) (jQuery);
