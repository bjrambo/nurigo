<?php
    /*
     * [현금영수증 발급 요청 페이지]
     *
     * 파라미터 전달시 POST를 사용하세요
     */
    $CST_PLATFORM               = $HTTP_POST_VARS["CST_PLATFORM"];       		//LG유플러스 결제 서비스 선택(test:테스트, service:서비스)
    $CST_MID                    = $HTTP_POST_VARS["CST_MID"];            		//상점아이디(LG유플러스으로 부터 발급받으신 상점아이디를 입력하세요)
                                                                         		//테스트 아이디는 't'를 반드시 제외하고 입력하세요.
    $LGD_MID                    = (("test" == $CST_PLATFORM)?"t":"").$CST_MID;  //상점아이디(자동생성)    
    $LGD_TID                	= $HTTP_POST_VARS["LGD_TID"];			 		//LG유플러스으로 부터 내려받은 거래번호(LGD_TID)
    
	$LGD_METHOD   		    	= $HTTP_POST_VARS["LGD_METHOD"];                //메소드('AUTH':승인, 'CANCEL' 취소)
    $LGD_OID                	= $HTTP_POST_VARS["LGD_OID"];					//주문번호(상점정의 유니크한 주문번호를 입력하세요)
    $LGD_PAYTYPE                = $HTTP_POST_VARS["LGD_PAYTYPE"];				//결제수단 코드 (SC0030:계좌이체, SC0040:가상계좌, SC0100:무통장입금 단독)
    $LGD_AMOUNT     		    = $HTTP_POST_VARS["LGD_AMOUNT"];            	//금액("," 를 제외한 금액을 입력하세요)
    $LGD_CASHCARDNUM        	= $HTTP_POST_VARS["LGD_CASHCARDNUM"];           //발급번호(주민등록번호,현금영수증카드번호,휴대폰번호 등등)
    $LGD_CUSTOM_MERTNAME 		= $HTTP_POST_VARS["LGD_CUSTOM_MERTNAME"];    	//상점명
    $LGD_CUSTOM_BUSINESSNUM 	= $HTTP_POST_VARS["LGD_CUSTOM_BUSINESSNUM"];    //사업자등록번호
    $LGD_CUSTOM_MERTPHONE 		= $HTTP_POST_VARS["LGD_CUSTOM_MERTPHONE"];    	//상점 전화번호
    $LGD_CASHRECEIPTUSE     	= $HTTP_POST_VARS["LGD_CASHRECEIPTUSE"];		//현금영수증발급용도('1':소득공제, '2':지출증빙)
    $LGD_PRODUCTINFO        	= $HTTP_POST_VARS["LGD_PRODUCTINFO"];			//상품명
    $LGD_TID        			= $HTTP_POST_VARS["LGD_TID"];					//LG유플러스 거래번호

	$configPath 				= "C:/lgdacom"; 						 		//LG유플러스에서 제공한 환경파일("/conf/lgdacom.conf") 위치 지정.   
    	
    require_once("./lgdacom/XPayClient.php");
    $xpay = &new XPayClient($configPath, $CST_PLATFORM);
    $xpay->Init_TX($LGD_MID);
    $xpay->Set("LGD_TXNAME", "CashReceipt");
    $xpay->Set("LGD_METHOD", $LGD_METHOD);
    $xpay->Set("LGD_PAYTYPE", $LGD_PAYTYPE);

    if ($LGD_METHOD == "AUTH"){					// 현금영수증 발급 요청
    	$xpay->Set("LGD_OID", $LGD_OID);
    	$xpay->Set("LGD_AMOUNT", $LGD_AMOUNT);
    	$xpay->Set("LGD_CASHCARDNUM", $LGD_CASHCARDNUM);
    	$xpay->Set("LGD_CUSTOM_MERTNAME", $LGD_CUSTOM_MERTNAME);
    	$xpay->Set("LGD_CUSTOM_BUSINESSNUM", $LGD_CUSTOM_BUSINESSNUM);
    	$xpay->Set("LGD_CUSTOM_MERTPHONE", $LGD_CUSTOM_MERTPHONE);
    	$xpay->Set("LGD_CASHRECEIPTUSE", $LGD_CASHRECEIPTUSE);

		if ($LGD_PAYTYPE == "SC0030"){				//기결제된 계좌이체건 현금영수증 발급요청시 필수 
			$xpay->Set("LGD_TID", $LGD_TID);
		}
		else if ($LGD_PAYTYPE == "SC0040"){			//기결제된 가상계좌건 현금영수증 발급요청시 필수 
			$xpay->Set("LGD_TID", $LGD_TID);
			$xpay->Set("LGD_SEQNO", "001");
		}
		else {										//무통장입금 단독건 발급요청
			$xpay->Set("LGD_PRODUCTINFO", $LGD_PRODUCTINFO);
    	}
    }else {											// 현금영수증 취소 요청 
    	$xpay->Set("LGD_TID", $LGD_TID);
 
    	if ($LGD_PAYTYPE == "SC0040"){				//가상계좌건 현금영수증 발급취소시 필수
			$xpay->Set("LGD_SEQNO", "001");
    	}
    }


    /*
     * 1. 현금영수증 발급/취소 요청 결과처리
     *
     * 결과 리턴 파라미터는 연동메뉴얼을 참고하시기 바랍니다.
     */
    if ($xpay->TX()) {
        //1)현금영수증 발급/취소결과 화면처리(성공,실패 결과 처리를 하시기 바랍니다.)
        echo "현금영수증 발급/취소 요청처리가 완료되었습니다.  <br>";
        echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
        echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
        
        echo "결과코드 : " . $xpay->Response("LGD_RESPCODE",0) . "<br>";
        echo "결과메세지 : " . $xpay->Response("LGD_RESPMSG",0) . "<br>";
        echo "거래번호 : " . $xpay->Response("LGD_TID",0) . "<p>";
        
        $keys = $xpay->Response_Names();
            foreach($keys as $name) {
                echo $name . " = " . $xpay->Response($name, 0) . "<br>";
            }
 
    }else {
        //2)API 요청 실패 화면처리
        echo "현금영수증 발급/취소 요청처리가 실패되었습니다.  <br>";
        echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
        echo "TX Response_msg = " . $xpay->Response_Msg() . "<p>";
    }
?>
