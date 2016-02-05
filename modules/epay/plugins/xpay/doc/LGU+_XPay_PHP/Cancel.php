<?php
    /*
     * [결제취소 요청 페이지]
     *
     * LG유플러스으로 부터 내려받은 거래번호(LGD_TID)를 가지고 취소 요청을 합니다.(파라미터 전달시 POST를 사용하세요)
     * (승인시 LG유플러스으로 부터 내려받은 PAYKEY와 혼동하지 마세요.)
     */
    $CST_PLATFORM               = $HTTP_POST_VARS["CST_PLATFORM"];       //LG유플러스 결제 서비스 선택(test:테스트, service:서비스)
    $CST_MID                    = $HTTP_POST_VARS["CST_MID"];            //상점아이디(LG유플러스으로 부터 발급받으신 상점아이디를 입력하세요)
                                                                         //테스트 아이디는 't'를 반드시 제외하고 입력하세요.
    $LGD_MID                    = (("test" == $CST_PLATFORM)?"t":"").$CST_MID;  //상점아이디(자동생성)    
    $LGD_TID                	= $HTTP_POST_VARS["LGD_TID"];			 //LG유플러스으로 부터 내려받은 거래번호(LGD_TID)
    
 	$configPath 				= "C:/lgdacom"; 						 //LG유플러스에서 제공한 환경파일("/conf/lgdacom.conf") 위치 지정.   
    
    require_once("./lgdacom/XPayClient.php");
    $xpay = &new XPayClient($configPath, $CST_PLATFORM);
    $xpay->Init_TX($LGD_MID);

    $xpay->Set("LGD_TXNAME", "Cancel");
    $xpay->Set("LGD_TID", $LGD_TID);
    
    /*
     * 1. 결제취소 요청 결과처리
     *
     * 취소결과 리턴 파라미터는 연동메뉴얼을 참고하시기 바랍니다.
     */
    if ($xpay->TX()) {
        //1)결제취소결과 화면처리(성공,실패 결과 처리를 하시기 바랍니다.)
        echo "결제 취소요청이 완료되었습니다.  <br>";
        echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
        echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
    }else {
        //2)API 요청 실패 화면처리
        echo "결제 취소요청이 실패하였습니다.  <br>";
        echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
        echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
    }
?>
