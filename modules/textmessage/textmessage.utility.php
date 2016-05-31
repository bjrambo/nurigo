<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  CSUtility
 * @author contact@nurigo.net
 * @brief  CSUtility
 */
class CSUtility 
{
	function CSUtility() { }

	function dispStatus($mstat) 
	{
		switch ($mstat) 
		{
			case "9":
				return "대기중";
			case "1":
				return "전송중";
			case "2":
				return "전송완료";
		}
	}
	function dispResultCode($rcode) 
	{
		$statset = array("00" => "정상"
			, "10" => "잘못된 번호"
			, "11" => "상위 서비스망 스팸 인식됨"
			, "12" => "이통사 전송불가"
			, "13" => "필드값 누락"
			, "20" => "등록된 계정이 아니거나 패스워드 틀림"
			, "21" => "존재하지 않는 메시지"
			, "30" => "가능한 전송 잔량이 없음"
			, "31" => "전송할 수 없음"
			, "32" => "미가입자"
			, "40" => "전송시간 초과"
			, "41" => "단말기 Busy"
			, "42" => "음영지역"
			, "43" => "단말기 Power off"
			, "44" => "단말기 메시지 저장갯수 초과"
			, "45" => "단말기 일시 서비스 정지"
			, "46" => "기타 단말기 문제"
			, "47" => "착신 거절"
			, "48" => "Unknown error"
			, "49" => "Format Error"
			, "50" => "SMS서비스 불가 단말기"
			, "51" => "착신측의 호불가 상태"
			, "52" => "이통사 서버 운영자 삭제"
			, "53" => "서버 메시지 Que Full"
			, "54" => "스팸인식"
			, "55" => "스팸, nospam.or.kr에 등록된 번호"
			, "56" => "전송실패(무선망단)"
			, "57" => "전송실패(무선망->단말기단)"
			, "58" => "전송경로 없음"
			, "60" => "취소"
			, "70" => "허용되지 않은 IP 주소"
			, "99" => "대기상태"
		);

		if (isset($statset[$rcode]))
			return $statset[$rcode];

		return "Unkown Code";
	}

	/**
	 * @brief 긴내용 잘라서 출력
	 * @history 2009/11/05 mb_strcut이 오동작해서 abbreviate로 교체(iconv 변환으로 비효율적).
	 */
	function dispContent($content) 
	{
		$content = iconv("utf-8", "euc-kr//TRANSLIT", $content);
		if (strlen($content) > 20) 
		{
			$content = $this->abbreviate($content, 20);
		}
		$content = iconv("euc-kr", "utf-8//TRANSLIT", $content);
		return $content;
	}

	function dispIndex($no, $page, $count)
	{
			if($page == 1)
				return $no;
			else
				return ($page-1)*$count+$no;

	}

	function dispFullnumber($country, $phonenum) 
	{
		if (strlen($phonenum) > 0 && substr($phonenum, 0, 1) == '0') $phonenum = substr($phonenum, 1);
		return $country . $phonenum;
	}

	/**
	 * @brief - 기호 붙여서 돌려줌.
	 **/
	function getDashTel($phonenum) 
	{
		$phonenum = str_replace('-', '', $phonenum);
		switch (strlen($phonenum)) 
		{
			case 10:
				$initial = substr($phonenum, 0, 3);
				$medium = substr($phonenum, 3, 3);
				$final = substr($phonenum, 6, 4);
				break;
			case 11:
				$initial = substr($phonenum, 0, 3);
				$medium = substr($phonenum, 3, 4);
				$final = substr($phonenum, 7, 4);
				break;
			default:
				return $phonenum;
		}
		return $initial . '-' . $medium . '-' . $final;
	}

	/**
	 * @brief 한글 깨짐없이 자르기(완성형 한글만 가능)
	 **/
	function cutout($msg, $limit) 
	{
		$msg = substr($msg, 0, $limit);
			if (strlen($msg) < $limit)
			$limit = strlen($msg);

		$countdown = 0;
		for ($i = $limit - 1; $i >= 0; $i--) {	
			if (ord(substr($msg,$i,1)) < 128) break;
			$countdown++;
		}

		$msg = substr($msg, 0, $limit - ($countdown % 2));

		return $msg;
	}

	/**
	 * @brief 한글 텍스트를 축약형으로 만듦.
	 * @param[in] msg 문자열
	 * @param[in] limit 자를 바이트 수
	 **/
	function abbreviate($msg, $limit) 
	{
		if ($limit >= strlen($msg))
			return $msg;
		else
			return $this->cutout($msg, $limit) . "..";
	}

	function strcut_utf8($str, $len, $checkmb=false, $tail='') 
	{
		/**
		 * UTF-8 Format
		 * 0xxxxxxx = ASCII, 110xxxxx 10xxxxxx or 1110xxxx 10xxxxxx 10xxxxxx
		 * 라틴 문자, 그리스 문자, 키릴 문자, 콥트 문자, 아르메니아 문자, 히브리 문자, 아랍 문자 는 2바이트
		 * BMP(Basic Mulitilingual Plane) 안에 들어 있는 것은 3바이트(한글, 일본어 포함)
		 **/
		preg_match_all('/[\xE0-\xFF][\x80-\xFF]{2}|./', $str, $match); // BMP 대상

		$m = $match[0];
		$slen = strlen($str); // length of source string
		$tlen = strlen($tail); // length of tail string
		$mlen = count($m); // length of matched characters

		if ($slen <= $len) return $str;
		if (!$checkmb && $mlen <= $len) return $str;

		$ret = array();
		$count = 0;
		for ($i=0; $i < $len; $i++) {
			$count += ($checkmb && strlen($m[$i]) > 1)?2:1;
			if ($count + $tlen > $len) break;
			$ret[] = $m[$i];
		}

		return join('', $ret).$tail;
	}

	function strlen_utf8($str, $checkmb = false) 
	{
		preg_match_all('/[\xE0-\xFF][\x80-\xFF]{2}|./', $str, $match); // BMP 대상
		$m = $match[0];
		$mlen = count($m); // length of matched characters

		if (!$checkmb) return $mlen;

		$count=0;
		for ($i=0; $i < $mlen; $i++) 
		{
			$count += ($checkmb && strlen($m[$i]) > 1)?2:1;
		}
		return $count;
	}

}
/* End of file textmessage.utility.php */
/* Location: ./modules/textmessage.utility.php */
