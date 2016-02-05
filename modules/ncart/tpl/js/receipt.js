var backup;

function before_print()
{
    jQuery('.print','.taxbill').hide();
}

function after_print()
{
    jQuery('.print','.taxbill').show();
}

function print_page()
{
	window.onbeforeprint = before_print;
	window.onafterprint = after_print;
	window.print();
}
var DDD = new Array("02", "031", "033", "032", "042", "043", "041", "053", "054", "055", "052", "051", "063", "061", "062", "064", "011", "012", "013", "014", "015", "016", "017", "018", "019", "010", "070");

function replaceAll(str,s,d){
	var i=0;

	while(i > -1)
	{
		i = str.indexOf(s);
		str = str.substr(0,i) + d + str.substr(i+1,str.length);
	}
	return str;
}

function getDashTel(tel){
	tel = replaceAll(tel,'-','');

	if (tel == null || tel.length < 4){
			return tel;
	}
	if (tel.indexOf("-") != -1){
		return tel;
	}
	for (var i = 0; DDD.length > i; i++) {
			if (tel.substring(0, DDD[i].length) == DDD[i] ) {
					if(tel.length < 9){
						return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length);
					}else{
						return tel.substring(0, DDD[i].length) + "-"+ tel.substring(DDD[i].length, tel.length - 4) + "-" + tel.substring(tel.length - 4, tel.length);
					}
			}
	}
	return tel;
}

function isvalidphonenumber(tel)
{
	tel = replaceAll(tel,'-','');

	if (tel.length < 10)
		return false;

	return true;
}

function button_onmouseover(obj, img)
{
	if (img == null) {
		if (obj.old)
			obj.src = obj.old;
	} else {
		obj.old = obj.src;
		obj.src = img;
	}
}

function setCookie(name, value, expiredays)
{
	var todayDate = new Date();
	todayDate.setDate(todayDate.getDate() + expiredays);
	document.cookie = name + "=" + escape(value) + "; path=/; expires=" + todayDate.toGMTString() + ";"
}

function isNumeric(val)
{
	return !isNaN(val);
}

function check_valid_ipaddr(ipaddr)
{
	arr = ipaddr.split(".");
	if (arr.length != 4)
	{
		alert('IP주소를 올바르게 입력하세요.');
		return false;
	}

	for (i = 0; i < arr.length; i++)
	{
		if (!isNumeric(arr[i]))
		{
			alert('입력하신 IP주소의 ' + (i+1) + '번째 블럭에 문제가 있습니다.');
			return false;
		}
		if (parseInt(arr[i]) > 255)
		{
			alert('입력하신 IP주소의 ' + (i+1) + '번째 블럭에 문제가 있습니다.');
			return false;
		}
	}

	return true;
}

