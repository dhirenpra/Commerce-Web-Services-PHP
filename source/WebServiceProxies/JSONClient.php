<?php
/* Copyright (c) 2004-2010 IP Commerce, INC. - All Rights Reserved.
 *
 * This software and documentation is subject to and made
 * available only pursuant to the terms of an executed license
 * agreement, and may be used only in accordance with the terms
 * of said agreement. This software may not, in whole or in part,
 * be copied, photocopied, reproduced, translated, or reduced to
 * any electronic medium or machine-readable form without
 * prior consent, in writing, from IP Commerce, INC.
 *
 * Use, duplication or disclosure by the U.S. Government is subject
 * to restrictions set forth in an executed license agreement
 * and in subparagraph (c)(1) of the Commercial Computer
 * Software-Restricted Rights Clause at FAR 52.227-19; subparagraph
 * (c)(1)(ii) of the Rights in Technical Data and Computer Software
 * clause at DFARS 252.227-7013, subparagraph (d) of the Commercial
 * Computer Software--Licensing clause at NASA FAR supplement
 * 16-52.227-86; or their equivalent.
 *
 * Information in this software is subject to change without notice
 * and does not represent a commitment on the part of IP Commerce.
 *
 * Sample Code is for reference Only and is intended to be used for educational purposes. It's the responsibility of
 * the software company to properly integrate into their solution code that best meets their production needs.
 */

// Commerce Web Services Client class


require_once ABSPATH.'/WebServiceProxies/CWSServiceInformation.php';
require_once ABSPATH.'/WebServiceProxies/CWSTransactionProcessing.php';
require_once ABSPATH.'/WebServiceProxies/CWSTransactionManagement.php';
require_once ABSPATH.'/WebServiceProxies/RestFaultHandler.php';

class newTransaction {
	public $TxnData,
	$TndrData;
}
// Holds credit card information
class creditCard {
	public $paymentAccountDataToken = '',
	$type,
	$name,
	$number,
	$expiration, // MMYY
	$cvv = '', // Code on back of card
	$address,
	$city,
	$state,
	$zip = '',
	$phone,
	$country = 'USA',
	$currency = 'USD',
	$track1,
	$track2;
}

class achCheck {
	public $PaymentAccountDataToken,
	$AccountNumber,
	$CheckCountryCode,
	$CheckNumber,
	$OwnerType,
	$RoutingNumber,
	$UseType,
	$BusinessName,
	$FirstName,
	$MiddleName,
	$LastName;
}

class configurationValues {
	public $IdentityToken,
	$ServiceId,
	$ApplicationProfileId,
	$MerchantProfileId;
}

class achTransactionData{
	public $Amount = '0.00', // amount in decimal format
	$EffectiveDate = '', // date string
	$IsRecurring = false, //boolean
	$SECCode = 'WEB', //The three letter code that indicates what NACHA regulations the transaction must adhere to. Required.
	$ServiceType = 'ACH', //Indicates the Electronic Checking service type: ACH, RDC or ECK. Required.
	$TransactionDateTime = '', // date time string
	$TransactionType = 'Debit', //Indicates the transaction type. Required. Debit/Credit
	$Creds = '';
}

// Holds transaction information for bankcard processing
class transData {
	public $InvoiceNumber = '',
	$OrderNumber = '',
	$CustomerPresent = '', // Present, Ecommerce, MOTO, NotPresent
	$EmployeeId = '', //Used for Retail, Restaurant, MOTO
	$EntryMode = '', // Keyed, TrackDataFromMSR
	$GoodsType = '', // DigitalGoods - PhysicalGoods
	$IndustryType = '', // Retail, Restaurant, Ecommerce, MOTO
	$AccountType = '', // SavingsAccount, CheckingAccount
	$Amount = '0.00', // in a decimal format xx.xx
	$CashBackAmount = '', // in a decimal format. used for PINDebit transactions
	$CurrencyCode = '', // TypeISOA3 Currency Codes USD CAD
	$SignatureCaptured = false, // boolean true or false
	$TipAmount = '0.00', // in a decimal format
	$ApprovalCode = '',
	$ReportingData = null,
	$Creds = '',
	$DateTime = '',
	$AltMerchantData = null;
}
class transDataPro {
	public $InvoiceNumber = '',
	$OrderNumber = '',
	$CustomerPresent = '', // Present, Ecommerce, MOTO, NotPresent
	$EmployeeId = '', //Used for Retail, Restaurant, MOTO
	$EntryMode = '', // Keyed, TrackDataFromMSR
	$GoodsType = '', // DigitalGoods - PhysicalGoods
	$IndustryType = '', // Retail, Restaurant, Ecommerce, MOTO
	$AccountType = '', // SavingsAccount, CheckingAccount
	$Amount = '0.00', // in a decimal format xx.xx
	$CashBackAmount = '', // in a decimal format. used for PINDebit transactions
	$CurrencyCode = '', // TypeISOA3 Currency Codes USD CAD
	$SignatureCaptured = false, // boolean true or false
	$TipAmount = '0.00', // in a decimal format
	$PartialApprovalCapable = 'NotSet', // Capable | NotCapable | NotSet
	$ApprovalCode = '',
	$ReportingData = null,
	$DateTime = '',
	$Creds = '',
	$CFeeAmount = '0.00',
	$AltMerchantData = null,
	$InterchangeData = null,
	$Level2Data = null;
}

class altMerchantData{
	public $CustomerServiceInternet = '', $CustomerServicePhone = '', $Description = '', $SIC = '', $MerchantId = '', $Name = '', $Address = null;
}

class interchangeData {
	public $BillPayment = '', $RequestCommercialCard = '', $ExistingDebt = '', $RequestACI = '', $TotalNumberOfInstallments = '', $CurrentInstallmentNumber = '', $RequestAdvice = '';
}

// Creates a client class to process Service Information and Transaction messages
class JSONClient {
	private $token, // IdentityToken
	$session_token = '', // Temporary session token used for transactions (Expires every 30 minutes)
	$serviceKey = '', // Only used with dedicated endpoints
	$merchantProfileID = '', // This is your merchant ProfileId
	$workflowId = '', // ServiceId/WorkFlowId of service connecting to
	$appProfileID = '', // This value is returned on your SaveApplicationData call
	$svcInfo, // Service information WSDL
	$txn, // Bank Card WSDL
	$svc,
	$tms; // Data Services - Transaction Management Service


	public function __construct($token, $baseURL, $merchProfileID = '', $workflowId = '', $applicationID = '', $_svc = null) {
		$this->token = new SignOnWithToken ();

		$this->svcInfo = $baseURL.'SvcInfo';
		$this->txn = $baseURL.'Txn';
		$this->tms = $baseURL.'DataServices/TMS';

		$this->token->identityToken = $token;
		$this->merchantProfileID = $merchProfileID;
		$this->workflowId = $workflowId;
		$this->appProfileID = $applicationID;
		$this->svc = $_svc;
	}

	/*
	 *
	 * Sign on and retrieve the session token from the WSDL
	 *
	 */
	public function signOn() {		
		if ($this->session_token == ''){			
			$msgBody = '';
			$url = $this->svcInfo.'/token';
			$action = 'GET'; // HttpMethod::Get			
			$response = curl_json($msgBody, $url, $action, $this->token->identityToken);
			if(isset($response->body->ErrorId))
			{
				handleRestFault($response);
				return false;	
			}
			if(isset($response[2]))
			{
				$body = $response[2];
				$info = $response[1];
			}
			else return false;				
			// 	Currently the session token does not have any slashes that would be escaped to \/.
			// This is safe as the generated saml is functionally confined to the ASCII character set.
			$this->session_token= trim($body, "\"");						
		}
		return true;
	}	

	/*
	 *
	 * Retrieve all available services
	 *
	 */
	public function getServiceInformation() {
		if (! $this->signOn ())
			return false;

		$msgBody = 	'';
		$url = $this->svcInfo.'/serviceInformation';							
		$action = 'GET';
		$siResponse = curl_json($msgBody, $url, $action, $this->session_token);		
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($siResponse[2])){
			// This section will parse the response and create the required object
			// for each service. It will only grab the first of each, a foreach would
			// be needed to grab all. 
			$si = new stdClass();
			$si->BankcardServices = new stdClass(); 
			
			if(count($siResponse[2]->BankcardServices))
			{
				$si->BankcardServices->BankcardService = new BankcardService;
				$si->BankcardServices->BankcardService = $siResponse[2]->BankcardServices[0];  
			}	
			if(count($siResponse[2]->ElectronicCheckingServices))	
			{
				$si->ElectronicCheckingServices->ElectronicCheckingServices = new ElectronicCheckingServices;
				$si->ElectronicCheckingServices->ElectronicCheckingService = $siResponse[2]->ElectronicCheckingServices[0];
			}			
				
			if(count($siResponse[2]->StoredValueServices))	
			{
				$si->StoredValueServices->StoredValueService = new StoredValueService;
				$si->StoredValueServices->StoredValueService = $siResponse[2]->StoredValueServices[0];
			}			
				
			if(count($siResponse[2]->Workflows))	
			{
				$si->Workflows->Workflow = new Workflow;
				$si->Workflows->Workflow = $siResponse[2]->Workflows[0]; 
			}			
				
			return $si;
		}				
		return false; 
	}
	// Return only the ServiceID
	public function getServiceID() {
		$si = getServiceInformation();
		return $si->BankcardServices->BankcardService->ServiceId;
	}

	/*
	 *
	 * Retrieve all available Merchant Profiles for a given Service Id
	 *
	 */
	public function getMerchantProfiles($svcId, $tndrType) {		
		if (! $this->signOn ())
			return false;

		$msgBody = 	'';
		//if($svcId != '' || $svcId != null || )
			$url = $this->svcInfo.'/merchProfile?serviceId='.$svcId;		
		//else
			//$url = $this->svcInfo.'/merchProfile?merchantProfileId='.$merchantProfileID;							
		$action = 'GET';
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}			
		if(isset($response[2]))
			return $response[2];	
		return false; 	
	}
	/*
	 *
	 * Retrieve a specific Merchant Profile for a given Service Id and Merchant Profile Id
	 *
	 */
	public function getMerchantProfile($svcId, $merchProfId) {		
		if (! $this->signOn ())
			return false;

		$msgBody = 	'';		
		$url = $this->svcInfo.'/merchProfile/'.$merchProfId.'?serviceId='.$svcId;							
		$action = 'GET';
		$response = curl_json($msgBody, $url, $action, $this->session_token);	
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}		
		if(isset($response[2]))
			return $response[2];	
		return false; 
	}
	/*
	 *
	 * Return only the Profile Id
	 * NOTE: The GetMerchantProfilesByProfileId operation is not available for REST implementations.
	 */

	public function getMerchantProfileId() {
		// Not implemented
	}

	/*
	 *
	 * Is the Merchant Profile Initialized
	 *
	 */
	public function isMerchantProfileInitialized($merchProfileId, $serviceId) {
		if (! $this->signOn ())
			return false;

		$msgBody = 	'';		
		$url = $this->svcInfo.'/merchProfile/'.$merchProfileId.'/OK?serviceId='.$serviceId;							
		$action = 'GET';
		$response = curl_json($msgBody, $url, $action, $this->session_token);	
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}		
		if(isset($response[0]))
			return $response[0];	
		return false; 	
	}
	/*
	 *
	 * Save Application Data
	 *
	 */
	public function saveApplicationData($appData) {
		if (! $this->signOn ())
			return false;
		
		$msgBody = 	$appData;						
		$action = 'PUT';
		$url = $this->svcInfo.'/appProfile';
		$msgBody = (string)json_encode($msgBody);
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2]->id;		
		
		return false; 
	}	
	/*
	 *
	 * Save Merchant Profiles
	 *
	 */
	public function saveMerchantProfiles($merchantProfile, $tenderType, $serviceId) {
		if (! $this->signOn ())
			return false;

		$msgBody = new stdClass();
		$msgBody->typeHOLDER = 'REPLACE'; // The beginning of the JSON msg needs $type and the schema location replaced below.
		$msgBody->ProfileId = $merchantProfile->ProfileId;
		$msgBody->ServiceId = $serviceId;
		$msgBody->ServiceName = $merchantProfile->ServiceName;
		$msgBody->LastUpdated = $merchantProfile->LastUpdated;
		$msgBody->MerchantData = $merchantProfile->MerchantData;
		$msgBody->TransactionData = $merchantProfile->TransactionData;										
		$action = 'PUT';
		$url = $this->svcInfo.'/merchProfile?serviceId='.$serviceId;

		//Format the message 
		$msgBody = (string)json_encode($msgBody);
		$msgBody = str_replace('typeHOLDER', '$type', $msgBody);
		$msgBody = str_replace('REPLACE', "MerchantProfile, http://schemas.ipcommerce.com/CWS/v2.0/ServiceInformation", $msgBody);
		$msgBody = '['.$msgBody.']';
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2][0];
	}
	/*
	 *
	 * Query Account

	 * $trans_info is class type transData
	 * $amount and $tip_amount: ('#.##'} (At least $1, two decimals required (1.00))
	 * 
	 */	
	public function queryAccount($ach_info, $trans_info) {
		// TODO Implement QueryAccount for JSON
	}
	/*
	 *
	 * Authorize a payment amount

	 * $trans_info is class type transData
	 * $amount and $tip_amount: ('#.##'} (At least $1, two decimals required (1.00))
	 *
	 */
	public function authorize($credit_info, $trans_info, $processAsPro = false) {
		if (! $this->signOn ())
			return false;
			
		if ($this->svc instanceof BankcardService || $this->svc == null){  
			// Bank transactionPro
			if ($processAsPro == true){
				$Transaction = buildTransactionPro ( $credit_info, $trans_info );
				$TxnType = '"$type":"BankcardTransactionPro,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard/Pro",';
				$TxnDataType = '"$type":"BankcardTransactionDataPro,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard/Pro",';
			}
			// Bank Transaction
			else{
				$Transaction = buildTransaction ( $credit_info, $trans_info );
				$TxnType = '"$type":"BankcardTransaction,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
				$TxnDataType = '"$type":"BankcardTransactionData,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
			}
		}
		if ($this->svc instanceof ElectronicCheckingService){
			$Transaction = buildACHTransaction($credit_info, $trans_info);
			$TxnType = '"$type":"ElectronicCheckingTransaction,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/ElectronicChecking",';
			$TxnDataType = '"$type":"ElectronicCheckingTransactionData,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/ElectronicChecking",';
		}
			
		$msgBody = new Rest_AuthorizeTransaction();
		$msgBody->ApplicationProfileId = $this->appProfileID; 
		$msgBody->MerchantProfileId = $this->merchantProfileID;
		$msgBody->Transaction = $Transaction;										
		$action = 'POST';
		$url = $this->txn.'/'.$this->workflowId;

		//Format the message 
		$txnString = '"Transaction":{';
		$txnDataString = '"TransactionData":{';
		$msgBody = (string)json_encode($msgBody);
		$msgBody = str_replace('{"ApplicationProfileId"', '{"$type":"AuthorizeTransaction,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Rest","ApplicationProfileId"', $msgBody);
		$msgBody = str_replace($txnString, $txnString.$TxnType, $msgBody);
		$msgBody = str_replace($txnDataString, $txnDataString.$TxnDataType, $msgBody);
		$msgBody = str_replace(' ', '', $msgBody); // Make sure no spaces remain in the body.		
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2];		
	}	
	/*
	 *
	 * Charge funds from an account based on transaction id
	 * $transactionID is the known transaction ID of a previous transaction
	 * $amount is the amount of money to charge, leave it empty to charge existing amount
	 * $tip_amount is the amount of tip money to charge, leave it empty to charge existing amount
	 *
	 */
	public function capture($transactionID, $creds = null, $amount = null, $tip_amount = null) {
		if (! $this->signOn ())
			return false;

		$CapType = '"$type":"Capture,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Rest",';
		$DiffType = '"$type":"BankcardCapture,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
			
		$DifferenceData = new BankcardCapture ();
		$DifferenceData->TransactionId = $transactionID;
		$DifferenceData->Amount = $amount ? $amount:'0.00';
		$DifferenceData->TipAmount = $tip_amount ? $tip_amount:'0.00';
		$DifferenceData->ChargeType = 'NotSet'; // Conditional - If Industry is Retail, this value MUST be set.
		$DifferenceData->ShipDate = date('Y-m-d\TH:i:s.u\Z');
		if ($creds != null) {
			$DifferenceData->Addendum = new Addendum ();
			$DifferenceData->Addendum->Unmanaged = new Unmanaged ();
			$DifferenceData->Addendum->Unmanaged->Any = new Any ();
			$DifferenceData->Addendum->Unmanaged->Any->string = $creds;
		}

		// Build Capture
		$msgBody = new Rest_Capture ();		
		$msgBody->ApplicationProfileId = $this->appProfileID;
		$msgBody->DifferenceData = $DifferenceData;
											
		$action = 'PUT';
		$url = $this->txn.'/'.$this->workflowId.'/'.$transactionID;

		//Format the message 
		$diffString = '"DifferenceData":{';		
		$msgBody = (string)json_encode($msgBody);
		$msgBody = str_replace('{"ApplicationProfileId"', '{'.$CapType.'"ApplicationProfileId"', $msgBody);
		$msgBody = str_replace($diffString, $diffString.$DiffType, $msgBody);		
		$msgBody = str_replace(' ', '', $msgBody); // Make sure no spaces remain in the body.
		$response = curl_json($msgBody, $url, $action, $this->session_token);		
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2];		
	}

	/*
	 *
	 * Authorize and Capture a payment amount
	 * $trans_info is class type transData
	 * $amount and $tip_amount: ('#.##'} (At least $1, two decimals required (1.00))
	 *
	 */
	public function authorizeAndCapture($credit_info, $trans_info, $processAsPro) {
		if (! $this->signOn ())
			return false;
			
		if ($this->svc instanceof BankcardService || $this->svc == null){  
			// Bank transactionPro
			if ($processAsPro == true){
				$Transaction = buildTransactionPro ( $credit_info, $trans_info );
				$TxnType = '"$type":"BankcardTransactionPro,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard/Pro",';
				$TxnDataType = '"$type":"BankcardTransactionDataPro,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard/Pro",';
			}
			// Bank Transaction
			else{
				$Transaction = buildTransaction ( $credit_info, $trans_info );
				$TxnType = '"$type":"BankcardTransaction,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
				$TxnDataType = '"$type":"BankcardTransactionData,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
			}
		}
		if ($this->svc instanceof ElectronicCheckingService){
			$Transaction = buildACHTransaction($credit_info, $trans_info);
			$TxnType = '"$type":"ElectronicCheckingTransaction,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/ElectronicChecking",';
			$TxnDataType = '"$type":"ElectronicCheckingTransactionData,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/ElectronicChecking",';
		}
			
		$msgBody = new Rest_AuthorizeTransaction();
		$msgBody->ApplicationProfileId = $this->appProfileID; 
		$msgBody->MerchantProfileId = $this->merchantProfileID;
		$msgBody->Transaction = $Transaction;									
		$action = 'POST';
		$url = $this->txn.'/'.$this->workflowId;

		//Format the message 
		$txnString = '"Transaction":{';
		$txnDataString = '"TransactionData":{';
		$msgBody = (string)json_encode($msgBody);
		$msgBody = str_replace('{"ApplicationProfileId"', '{"$type":"AuthorizeAndCaptureTransaction,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Rest","ApplicationProfileId"', $msgBody);
		$msgBody = str_replace($txnString, $txnString.$TxnType, $msgBody);
		$msgBody = str_replace($txnDataString, $txnDataString.$TxnDataType, $msgBody);
		$msgBody = str_replace(' ', '', $msgBody); // Make sure no spaces remain in the body.
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2];		
	}

	/*
	 *
	 * Void or Return funds to an account based on transaction id
	 * NOTE: Use this function to void Authorize
	 * $transactionID is the known transaction ID of a previous transaction
	 *
	 */
	public function undo( $transactionID, $creds = null, $txnType = null ) {
		if (! $this->signOn ())
			return false;

		$UndoType = '"$type":"Undo,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Rest",';		
		
		if ($this->svc instanceof BankcardService || $txnType == "BCP"){
			$DifferenceData = new BankcardUndo();
			$DifferenceData->PINDebitReason = 'NotSet';
			$DifferenceData->ForceVoid = false;
			$DiffType = '"$type":"BankcardUndo,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
		}
		if ($this->svc instanceof ElectronicCheckingService || $txnType == "ECK"){
			$DifferenceData = new UndoDifferenceData();
			$DiffType = '"$type":"DifferenceData,http://schemas.ipcommerce.com/CWS/v2.0/Transactions",';
		}
				
		$DifferenceData->TransactionId = $transactionID;		
		if ($creds != null) {
			$DifferenceData->Addendum = new Addendum ();
			$DifferenceData->Addendum->Unmanaged = new Unmanaged ();
			$DifferenceData->Addendum->Unmanaged->Any = new Any ();
			$DifferenceData->Addendum->Unmanaged->Any->string = $creds;
		}

		// Build Capture 
		$msgBody = new Rest_Undo ();		
		$msgBody->ApplicationProfileId = $this->appProfileID;
		$msgBody->DifferenceData = $DifferenceData;
											
		$action = 'PUT';
		$url = $this->txn.'/'.$this->workflowId.'/'.$transactionID;

		//Format the message 
		$diffString = '"DifferenceData":{';		
		$msgBody = (string)json_encode($msgBody);
		$msgBody = str_replace('{"ApplicationProfileId"', '{'.$UndoType.'"ApplicationProfileId"', $msgBody);
		$msgBody = str_replace($diffString, $diffString.$DiffType, $msgBody);		
		$msgBody = str_replace(' ', '', $msgBody); // Make sure no spaces remain in the body.
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2];
	}

	/*
	 *
	 * Return funds to an account based on transaction id
	 * $transactionID is the known transaction ID of a previous transaction
	 * $amount is the amount of money to return, leave it empty to return full amount
	 *
	 */
	public function returnByID( $transactionID, $creds = null, $amount = null) {
		if (! $this->signOn ())
			return false;
			
		$TxnType = '"$type":"ReturnById,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Rest",';
		$DiffDataType = '"$type":"BankcardReturn,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
			
		$return_amount = new BankcardReturn ();
		$return_amount->Amount = $amount;
		$return_amount->TransactionId = $transactionID;
		if ($creds != null) {
			$return_amount->Addendum = new Addendum ();
			$return_amount->Addendum->Unmanaged = new Unmanaged ();
			$return_amount->Addendum->Unmanaged->Any = new Any ();
			$return_amount->Addendum->Unmanaged->Any->string = $creds;
		}

		// Build ReturnById
		$msgBody = new Rest_ReturnById ();
		$msgBody->DifferenceData = $return_amount;
		$msgBody->ApplicationProfileId = $this->appProfileID;										
		$action = 'POST';
		$url = $this->txn.'/'.$this->workflowId;

		//Format the message 
		$txnTypeString = '"ApplicationProfileId"';
		$diffDataTypeString = '"DifferenceData":{';
		$msgBody = (string)json_encode($msgBody);
		$msgBody = str_replace('{'.$txnTypeString, '{'.$TxnType.$txnTypeString, $msgBody);
		$msgBody = str_replace($diffDataTypeString, $diffDataTypeString.$DiffDataType, $msgBody);		
		$msgBody = str_replace(' ', '', $msgBody); // Make sure no spaces remain in the body.
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2];
	}

	/*
	 *
	 * Return funds to an account (see Authorize/Authorize and Capture for structure)
	 *
	 */
	public function returnUnlinked( $credit_info, $trans_info) {
		if (! $this->signOn ())
			return false;
			
		$MsgType = '"$type":"ReturnTransaction,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Rest",';
		$TxnType = '"$type":"BankcardTransaction,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
		$TxnDataType = '"$type" : "BankcardTransactionData,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';
		
		// Bank transaction
		$bank_trans = buildTransaction ( $credit_info, $trans_info );
			
		// Build ReturnUnlinked
		$msgBody = new Rest_ReturnTransaction ();
		$msgBody->MerchantProfileId = $this->merchantProfileID;
		$msgBody->ApplicationProfileId = $this->appProfileID;
		$msgBody->Transaction = $bank_trans;										
		$action = 'POST';
		$url = $this->txn.'/'.$this->workflowId;

		//Format the message 
		$msgTypeString = '"ApplicationProfileId"';
		$txnTypeString = '"Transaction":{';
		$txnDataTypeString = '"TransactionData":{';
		$msgBody = (string)json_encode($msgBody);
		$msgBody = str_replace('{'.$msgTypeString, '{'.$MsgType.$msgTypeString, $msgBody);
		$msgBody = str_replace($txnTypeString, $txnTypeString.$TxnType, $msgBody);
		$msgBody = str_replace($txnDataTypeString, $txnDataTypeString.$TxnDataType, $msgBody);			
		$msgBody = str_replace(' ', '', $msgBody); // Make sure no spaces remain in the body.
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2];
	}

	/*
	 *
	 * Settle specific transactions from the day - Do not pass in Undo's or Authorize txns
	 * that have had an Undo processed against it
	 * $transactionIds are a list of transactions that you wish to settle
	 * $differenceData is an object that contains any data to adjust at the time of settlement
	 *
	 */
	public function captureSelective($transactionIds, $differenceData, $creds = null) {
		if (! $this->signOn ())
			return false;
		$DifferenceData = array ();

		if ($differenceData != null) {
			$i = 0;
			if (is_array ( $differenceData )) {
				foreach ( $differenceData as $diffData ) {
					$Capture = new stdClass();
					$Capture->Type = 'REPLACE';
					$Capture->TransactionId = $diffData->transactionID;
					$Capture->Amount = $diffData->amount ? $diffData->amount : '0.00';
					$Capture->TipAmount = $diffData->tip_amount ? $diffData->tip_amount : '0.00';	
					$Capture->ChargeType = 'NotSet';
					$Capture->ShipDate = date('Y-m-d\TH:i:s.u\Z');									
					$DifferenceData [$i] = $Capture;
					$i++;
				}				
			} else {
				$Capture = new stdClass ();
				$Capture->TransactionId = $diffData->transactionID;
				$Capture->Amount = $diffData->amount ? $diffData->amount : '0.00';
				$Capture->TipAmount = $diffData->tip_amount ? $diffData->tip_amount : '0.00';				
				$DifferenceData [$i] = $Capture;				
			}
			if ($creds != null) {
				$DifferenceData [0]->Addendum = new Addendum ();
				$DifferenceData [0]->Addendum->Unmanaged = new Unmanaged ();
				$DifferenceData [0]->Addendum->Unmanaged->Any = new Any ();
				$DifferenceData [0]->Addendum->Unmanaged->Any->string = $creds;
			}
		}

		$CapType = '"$type":"CaptureSelective,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Rest",';
		$DiffType = '"$type":"BankcardCapture,http://schemas.ipcommerce.com/CWS/v2.0/Transactions/Bankcard",';

		// Build Capture
		$msgBody = new Rest_CaptureSelective ();		
		$msgBody->ApplicationProfileId = $this->appProfileID;
		$msgBody->TransactionIds = $transactionIds;
		$msgBody->DifferenceData = $DifferenceData;
											
		$action = 'PUT';
		$url = $this->txn.'/'.$this->workflowId;

		//Format the message 
		$diffString = '"Type":"REPLACE",';		
		$msgBody = (string)json_encode($msgBody);
		$msgBody = str_replace('{"ApplicationProfileId"', '{'.$CapType.'"ApplicationProfileId"', $msgBody);
		$msgBody = str_replace($diffString, $DiffType, $msgBody);		
		$msgBody = str_replace(' ', '', $msgBody); // Make sure no spaces remain in the body.
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2]))
			return $response[2];	
		
	}
	/*
	 *
	 * Settle all transactions from the day
	 *
	 * $differenceData is an object that contains any data to adjust at the time of settlement
	 *
	 */
	public function captureAll($differenceData, $creds = null) {
		return false; // TODO Implement captureAll for JSON
	}

	/*
	 * Data Services API calls below
	 */

	public function queryTransactionsSummary($queryTransactionParameters, $includeRelated, $pagingParameters) {
		if (! $this->signOn ())
			return false;
		
		// Build QueryTransactionsSummary
		$qts = new QueryTransactionsSummary();
		$qts->sessionToken = null; // Session token not included in body for REST message. 
		$qts->queryTransactionsParameters = $queryTransactionParameters;
		$qts->includeRelated = $includeRelated;
		$qts->pagingParameters = $pagingParameters;					
		$action = 'POST';
		$url = $this->tms.'/transactionsSummary';
		if($qts->queryTransactionsParameters->MerchantProfileIds == '')  // Empty string not allowed for REST
			$qts->queryTransactionsParameters->MerchantProfileIds = null;
		$msgBody = (string)json_encode($qts);
		$msgBody = str_replace('"sessionToken":null,', '', $msgBody);
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2])){
			$obj = new stdClass();
			$obj->SummaryDetail = $response[2];
			return $obj;	
		}			
		return false; 
	}

	public function queryTransactionFamilies($queryTransactionParameters, $includeRelated, $pagingParameters) {
		if (! $this->signOn ())
			return false;
		
		// Build QueryTransactionsSummary
		$qtf = new QueryTransactionFamilies();
		$qtf->sessionToken = null; // Session token not included in body for REST message. 
		$qtf->queryTransactionsParameters = $queryTransactionParameters;
		$qtf->includeRelated = $includeRelated;
		$qtf->pagingParameters = $pagingParameters;						
		$action = 'POST';
		$url = $this->tms.'/transactionsFamily';
		
		$msgBody = (string)json_encode($qtf);
		$msgBody = str_replace('"sessionToken":null,', '', $msgBody);
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2])){
			$obj = new stdClass();
			$obj->FamilyDetail = $response[2][0];
			if(is_array($obj->FamilyDetail->TransactionIds)){
				$txnId = $obj->FamilyDetail->TransactionIds[0];
				$obj->FamilyDetail->TransactionIds = new stdClass();
				$obj->FamilyDetail->TransactionIds->string = $txnId;
			}
			if(is_array($obj->FamilyDetail->TransactionMetaData)){
				$md = $obj->FamilyDetail->TransactionMetaData[0];
				$obj->FamilyDetail->TransactionMetaData = new stdClass();
				$obj->FamilyDetail->TransactionMetaData->TransactionMetaData = $md;
			}				
			return $obj;
		}			
		return false; 
	}
	public function queryTransactionsDetail($queryTransactionParameters, $includeRelated, $transactionDetailFormat, $pagingParameters) {
		if (! $this->signOn ())
			return false;
		
		// Build QueryTransactionsDetail
		$qtd = new QueryTransactionsDetail();
		$qtd->sessionToken = null; // Sessiontoken is not included in the body for REST
		$qtd->queryTransactionsParameters = $queryTransactionParameters;
		$qtd->includeRelated = $includeRelated;
		$qtd->transactionDetailFormat = $transactionDetailFormat;
		$qtd->pagingParameters = $pagingParameters;						
		$action = 'POST';
		$url = $this->tms.'/transactionsDetail';		

		$msgBody = (string)json_encode($qtd);
		$msgBody = str_replace('"sessionToken":null,', '', $msgBody);
		$response = curl_json($msgBody, $url, $action, $this->session_token);
		if(isset($response->body->ErrorId))
		{
			handleRestFault($response);
			return false;	
		}
		if(isset($response[2])){
			return $response[2][0];			
		}			
		return false; 
	}
}

?>