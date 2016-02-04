<?php
    /*
     * [최종결제요청 페이지(STEP2-2)]
     *
     * LG유플러스으로 부터 내려받은 LGD_PAYKEY(인증Key)를 가지고 최종 결제요청.(파라미터 전달시 POST를 사용하세요)
     */

	$configPath = "C:/lgdacom"; //LG유플러스에서 제공한 환경파일("/conf/lgdacom.conf,/conf/mall.conf") 위치 지정. 

    /*
     *************************************************
     * 1.최종결제 요청 - BEGIN
     *  (단, 최종 금액체크를 원하시는 경우 금액체크 부분 주석을 제거 하시면 됩니다.)
     *************************************************
     */
    $CST_PLATFORM               = $HTTP_POST_VARS["CST_PLATFORM"];
    $CST_MID                    = $HTTP_POST_VARS["CST_MID"];
    $LGD_MID                    = (("test" == $CST_PLATFORM)?"t":"").$CST_MID;
    $LGD_PAYKEY                 = $HTTP_POST_VARS["LGD_PAYKEY"];

    require_once("./lgdacom/XPayClient.php");
    $xpay = &new XPayClient($configPath, $CST_PLATFORM);
    $xpay->Init_TX($LGD_MID);    
    
    $xpay->Set("LGD_TXNAME", "PaymentByKey");
    $xpay->Set("LGD_PAYKEY", $LGD_PAYKEY);
    
    //금액을 체크하시기 원하는 경우 아래 주석을 풀어서 이용하십시요.
	//$DB_AMOUNT = "DB나 세션에서 가져온 금액"; //반드시 위변조가 불가능한 곳(DB나 세션)에서 금액을 가져오십시요.
	//$xpay->Set("LGD_AMOUNTCHECKYN", "Y");
	//$xpay->Set("LGD_AMOUNT", $DB_AMOUNT);
	    
    /*
     *************************************************
     * 1.최종결제 요청(수정하지 마세요) - END
     *************************************************
     */

    /*
     * 2. 최종결제 요청 결과처리
     *
     * 최종 결제요청 결과 리턴 파라미터는 연동메뉴얼을 참고하시기 바랍니다.
     */
    if ($xpay->TX()) {
        //1)결제결과 화면처리(성공,실패 결과 처리를 하시기 바랍니다.)
        echo "결제요청이 완료되었습니다.  <br>";
        echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
        echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
            
        echo "거래번호 : " . $xpay->Response("LGD_TID",0) . "<br>";
        echo "상점아이디 : " . $xpay->Response("LGD_MID",0) . "<br>";
        echo "상점주문번호 : " . $xpay->Response("LGD_OID",0) . "<br>";
        echo "결제금액 : " . $xpay->Response("LGD_AMOUNT",0) . "<br>";
        echo "결과코드 : " . $xpay->Response("LGD_RESPCODE",0) . "<br>";
        echo "결과메세지 : " . $xpay->Response("LGD_RESPMSG",0) . "<p>";
            
        $keys = $xpay->Response_Names();
        foreach($keys as $name) {
            echo $name . " = " . $xpay->Response($name, 0) . "<br>";
        }
          
        echo "<p>";
           
        if( "0000" == $xpay->Response_Code() ) {
         	//최종결제요청 결과 성공 DB처리
           	echo "최종결제요청 결과 성공 DB처리하시기 바랍니다.<br>";

            //최종결제요청 결과 성공 DB처리 실패시 Rollback 처리
          	$isDBOK = true; //DB처리 실패시 false로 변경해 주세요.
          	if( !$isDBOK ) {
           		echo "<p>";
           		$xpay->Rollback("상점 DB처리 실패로 인하여 Rollback 처리 [TID:" . $xpay->Response("LGD_TID",0) . ",MID:" . $xpay->Response("LGD_MID",0) . ",OID:" . $xpay->Response("LGD_OID",0) . "]");            		            		
            		
                echo "TX Rollback Response_code = " . $xpay->Response_Code() . "<br>";
                echo "TX Rollback Response_msg = " . $xpay->Response_Msg() . "<p>";
            		
                if( "0000" == $xpay->Response_Code() ) {
                  	echo "자동취소가 정상적으로 완료 되었습니다.<br>";
                }else{
          			echo "자동취소가 정상적으로 처리되지 않았습니다.<br>";
                }
          	}            	
        }else{
          	//최종결제요청 결과 실패 DB처리
         	echo "최종결제요청 결과 실패 DB처리하시기 바랍니다.<br>";            	            
        }
    }else {
        //2)API 요청실패 화면처리
        echo "결제요청이 실패하였습니다.  <br>";
        echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
        echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
            
        //최종결제요청 결과 실패 DB처리
        echo "최종결제요청 결과 실패 DB처리하시기 바랍니다.<br>";            	                        
    }
?>
