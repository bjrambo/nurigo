<?php
class CallerService {
    function CallerService() {
        //require_once 'constants.php';

        if(defined('API_USERNAME')) $this->API_UserName=API_USERNAME;

        if(defined('API_PASSWORD')) $this->API_Password=API_PASSWORD;

        if(defined('API_SIGNATURE')) $this->API_Signature=API_SIGNATURE;

        if(defined('API_ENDPOINT')) $this->API_Endpoint =API_ENDPOINT;

        $this->version=VERSION;

        if(defined('SUBJECT')) $this->subject = SUBJECT;
        // below three are needed if used permissioning
        if(defined('AUTH_TOKEN')) $this->AUTH_token= AUTH_TOKEN;

        if(defined('AUTH_SIGNATURE')) $this->AUTH_signature=AUTH_SIGNATURE;

        if(defined('AUTH_TIMESTAMP')) $this->AUTH_timestamp=AUTH_TIMESTAMP;
    }

    function nvpHeader()
    {
    //global $API_Endpoint,$version,$API_UserName,$API_Password,$API_Signature,$nvp_Header, $subject, $AUTH_token,$AUTH_signature,$AUTH_timestamp;
    $nvpHeaderStr = "";

    if(defined('AUTH_MODE')) {
        //$AuthMode = "3TOKEN"; //Merchant's API 3-TOKEN Credential is required to make API Call.
        //$AuthMode = "FIRSTPARTY"; //Only merchant Email is required to make EC Calls.
        //$AuthMode = "THIRDPARTY";Partner's API Credential and Merchant Email as Subject are required.
        $AuthMode = "AUTH_MODE"; 
    } 
    else {
        
        if((!empty($this->API_UserName)) && (!empty($this->API_Password)) && (!empty($this->API_Signature)) && (!empty($this->subject))) {
            $AuthMode = "THIRDPARTY";
        }
        
        else if((!empty($this->API_UserName)) && (!empty($this->API_Password)) && (!empty($this->API_Signature))) {
            $AuthMode = "3TOKEN";
        }
        
        elseif (!empty($this->AUTH_token) && !empty($this->AUTH_signature) && !empty($this->AUTH_timestamp)) {
            $AuthMode = "PERMISSION";
        }
        elseif(!empty($this->subject)) {
            $AuthMode = "FIRSTPARTY";
        }
    }
    switch($AuthMode) {
        
        case "3TOKEN" : 
                $nvpHeaderStr = "&PWD=".urlencode($this->API_Password)."&USER=".urlencode($this->API_UserName)."&SIGNATURE=".urlencode($this->API_Signature);
                break;
        case "FIRSTPARTY" :
                $nvpHeaderStr = "&SUBJECT=".urlencode($this->subject);
                break;
        case "THIRDPARTY" :
                $nvpHeaderStr = "&PWD=".urlencode($this->API_Password)."&USER=".urlencode($this->API_UserName)."&SIGNATURE=".urlencode($this->API_Signature)."&SUBJECT=".urlencode($this->subject);
                break;		
        case "PERMISSION" :
                $nvpHeaderStr = formAutorization($this->AUTH_token,$this->AUTH_signature,$this->AUTH_timestamp);
                break;
    }
        return $nvpHeaderStr;
    }

    /**
      * hash_call: Function to perform the API call to PayPal using API signature
      * @methodName is name of API  method.
      * @nvpStr is nvp string.
      * returns an associtive array containing the response from the server.
    */


    function hash_call($methodName,$nvpStr)
    {
        //declaring of global variables
        //global $API_Endpoint,$version,$API_UserName,$API_Password,$API_Signature,$nvp_Header, $subject, $AUTH_token,$AUTH_signature,$AUTH_timestamp;
        // form header string
        $nvpheader=$this->nvpHeader();
        //setting the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        
        //in case of permission APIs send headers as HTTPheders
        if(!empty($this->AUTH_token) && !empty($this->AUTH_signature) && !empty($this->AUTH_timestamp))
         {
            $headers_array[] = "X-PP-AUTHORIZATION: ".$nvpheader;
      
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);
        curl_setopt($ch, CURLOPT_HEADER, false);
        }
        else 
        {
            $nvpStr=$nvpheader.$nvpStr;
        }
        //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
       //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
        if(USE_PROXY)
        curl_setopt ($ch, CURLOPT_PROXY, PROXY_HOST.":".PROXY_PORT); 

        //check if version is included in $nvpStr else include the version.
        if(strlen(str_replace('VERSION=', '', strtoupper($nvpStr))) == strlen($nvpStr)) {
            $nvpStr = "&VERSION=" . urlencode($this->version) . $nvpStr;	
        }
        
        $nvpreq="METHOD=".urlencode($methodName).$nvpStr;
        
        //setting the nvpreq as POST FIELD to curl
        curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

        //getting response from server
        $response = curl_exec($ch);

        //convrting NVPResponse to an Associative Array
        $nvpResArray=$this->deformatNVP($response);
        $nvpReqArray=$this->deformatNVP($nvpreq);
        $_SESSION['nvpReqArray']=$nvpReqArray;

        if (curl_errno($ch)) {
            // moving to display page to display curl errors
              $_SESSION['curl_error_no']=curl_errno($ch) ;
              $_SESSION['curl_error_msg']=curl_error($ch);
         } else {
             //closing the curl
                curl_close($ch);
          }

    return $nvpResArray;
    }

    /** This function will take NVPString and convert it to an Associative Array and it will decode the response.
      * It is usefull to search for a particular key and displaying arrays.
      * @nvpstr is NVPString.
      * @nvpArray is Associative Array.
      */

    function deformatNVP($nvpstr)
    {

        $intial=0;
        $nvpArray = array();


        while(strlen($nvpstr)){
            //postion of Key
            $keypos= strpos($nvpstr,'=');
            //position of value
            $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

            /*getting the Key and Value values and storing in a Associative Array*/
            $keyval=substr($nvpstr,$intial,$keypos);
            $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
            //decoding the respose
            $nvpArray[urldecode($keyval)] =urldecode( $valval);
            $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
         }
        return $nvpArray;
    }
    function formAutorization($auth_token,$auth_signature,$auth_timestamp)
    {
        $authString="token=".$auth_token.",signature=".$auth_signature.",timestamp=".$auth_timestamp ;
        return $authString;
    }
}
?>
