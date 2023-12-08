<?php defined( 'ABSPATH' ) || die();

class Bitrix24Leads {
	public static string $endpoint = 'https://yourdomain.bitrix24.ru/rest/61/12312312312/';
	
	public static function create_and_get_company_id( $company_name ) {
		// set the company fields
		$fields = array(
			'TITLE' => $company_name,
			// add any additional fields here as needed
		);
		
		// build the API request
		$requestUrl  = self::$endpoint . 'crm.company.add.json';
		$requestData = http_build_query( array(
			'fields' => $fields,
			'params' => array( 'REGISTER_SONET_EVENT' => 'Y' ),
		) );
		
		// send the API request and get the response
		$curl = curl_init();
		curl_setopt_array( $curl, array(
			CURLOPT_URL            => $requestUrl,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $requestData,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
		) );
		$response = curl_exec( $curl );
		curl_close( $curl );
		
		// parse the response
		$responseData = json_decode( $response, true );
		if ( $responseData[ 'result' ] ) {
			$companyId = $responseData[ 'result' ];
			
			return $companyId;
		}
		
		return false;
	}
	
	public static function upload_file_and_get_id( $file_key ) {
		return null;
		$upload_url = self::$endpoint . 'disk.folder.uploadfile.json';
		
		try {
			$data_post = [
				'id'                 => 1,
				'generateUniqueName' => true,
			];
			
			// send the API request and get the response
			$curl = curl_init();
			curl_setopt_array( $curl, array(
				CURLOPT_URL            => $upload_url,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $data_post,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
			) );
			$response = curl_exec( $curl );
			curl_close( $curl );
			
			$data = json_decode( $response, true );
			
			$name = $_FILES[ $file_key ][ 'name' ];
			$path = $_FILES[ $file_key ][ 'tmp_name' ];
			$type = $_FILES[ $file_key ][ 'type' ];
			
			$data_post = [
				'file' => curl_file_create( $path, $type, $name ),
			];
			// send the API request and get the response
			$curl = curl_init();
			curl_setopt_array( $curl, array(
				CURLOPT_URL            => $data[ 'result' ][ 'uploadUrl' ],
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $data_post,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HEADER         => false,
				CURLOPT_SSL_VERIFYPEER => false,
			) );
			$response = curl_exec( $curl );
			curl_close( $curl );
			$data = json_decode( $response, true );
			
			
			return $data[ 'result' ][ 'FILE_ID' ];
		}
		catch ( \Exception $e ) {
			return null;
		}
	}
	
	public static function create_and_get_contact_id( $name, $email = null, $phone = null, $company_name = null ) {
		if ( ! $name ) {
			return false;
		}
		
		$fields = array(
			'NAME' => $name,            // add any additional fields here as needed
		);
		if ( $email ) {
			$fields[ 'EMAIL' ] = array( array( 'VALUE' => $email, 'TYPE' => 'WORK' ) );
		}
		
		if ( $phone ) {
			$fields[ 'PHONE' ] = array( array( 'VALUE' => $phone, 'TYPE' => 'WORK' ) );
		}
		
		if ( $company_name ) {
			$company_id             = self::create_and_get_company_id( $company_name );
			$fields[ 'COMPANY_ID' ] = $company_id;
		}
		
		
		// build the API request
		$requestUrl  = self::$endpoint . 'crm.contact.add.json';
		$requestData = http_build_query( array(
			'fields' => $fields,
			'params' => array( 'REGISTER_SONET_EVENT' => 'Y' ),
		) );
		
		// send the API request and get the response
		$curl = curl_init();
		curl_setopt_array( $curl, array(
			CURLOPT_URL            => $requestUrl,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $requestData,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
		) );
		$response = curl_exec( $curl );
		curl_close( $curl );
		
		// parse the response
		$responseData = json_decode( $response, true );
		if ( $responseData[ 'result' ] ) {
			$contactId = $responseData[ 'result' ];
			
			return $contactId;
		}
		
		return false;
	}
	
	public static function send_lead_to_bitrix24() {
		// Get Bitrix24 Lead fields
		$lead_name    = $_POST[ 'name' ];
		$lead_email   = $_POST[ 'email' ];
		$lead_phone   = $_POST[ 'phone' ];
		$lead_company = null;//$posted_data[ 'company' ];
		$lead_message = '';
		
		$fields_for_message = array(
			'point-from'    => array(
				'label' => 'Город/Место отправления',
			),
			'point-to'      => array(
				'label' => 'Город/Место назначения',
			),
			'is-from-point' => array(
				'label' => 'от Адреса',
			),
			'is-to-point'   => array(
				'label' => 'до Адреса',
			),
			'weight'        => array(
				'label'   => 'Вес',
				'postfix' => 'кг',
			),
			'volume'        => array(
				'label'   => 'Объем',
				'postfix' => 'м3'
			),
			'cost'          => array(
				'label'   => 'Стоимость',
				'postfix' => 'руб',
			),
			'time'          => array(
				'label'   => 'Время доставки',
				'postfix' => 'дней',
			),
		);
		
		foreach ( $fields_for_message as $key => $field ){
			if( isset( $_POST[ $key ] ) ){
				$value = $_POST[$key] === '1' ? 'да' : $_POST[$key];
				
				$lead_message .= "[B]{$field['label']}: [/B] {$value} {$field['postfix']}\n";
			}
		}
		
		//$lead_file = isset( $_FILES[ 'attachment' ] ) ? self::upload_file_and_get_id( 'attachment' ) : null;
		
		$contact_id = self::create_and_get_contact_id( $lead_name, $lead_email, $lead_phone, $lead_company );
		
		if ( $contact_id ) {
			$restApiUrl = self::$endpoint . "crm.lead.add.json";
			$userId     = 1;
			
			$leadSender = new B24LeadSender( $restApiUrl, $userId );
			$leadSender->SetTitle( $lead_name );
			$leadSender->SetUserField( 'CONTACT_ID', $contact_id );
			$leadSender->SetUserField( 'CATEGORY_ID', 0 );
			$leadSender->SetUserField( 'STAGE_ID', 'NEW' );
			$leadSender->SetUserField( 'UF_CRM_1675854987', 3 );
			
			/*if ( $lead_file ) {
				$leadSender->SetUserField( 'UF_CRM_1689664332023', $lead_file );
			}*/
			
			if ( $lead_message ) {
				$leadSender->SetComments( $lead_message );
			}
			
			$leadSender->SetUtmSource( $_POST[ 'utm_source' ] );
			$leadSender->SetUtmMedium( $_POST[ 'utm_medium' ] );
			$leadSender->SetUtmCampaign( $_POST[ 'utm_source' ] );
			$leadSender->SetUtmContent( $_POST[ 'utm_campaign' ] );
			$leadSender->SetUtmTerm( $_POST[ 'utm_term' ] );
			
			$leadSender->SetTitle( 'Заявка из калькулятора Сборка РФ | KKC Logistics' );
			$leadSender->Send();
		}
	}
}


class B24LeadSender {
	private $queryUrl;
	private $queryData = [
		"fields" => [],
		"params" => []
	];
	
	private $errorText;
	
	public function __construct( $url, $userId ) {
		$this->queryUrl                                        = $url;
		$this->queryData[ "fields" ][ "ASSIGNED_BY_ID" ]       = $userId;
		$this->queryData[ "params" ][ "REGISTER_SONET_EVENT" ] = "Y"; // значение по-умолчанию, отключить можно через DontRegisterSonetEvent
	}
	
	public function SetName( $name ) {
		$this->queryData[ "fields" ][ "NAME" ] = $name;
		
		if ( ! strlen( $this->queryData[ "fields" ][ "TITLE" ] ) ) {
			$this->queryData[ "fields" ][ "TITLE" ] = "Новый лид: $name";
		}
	}
	
	public function SetTitle( $title ) {
		$this->queryData[ "fields" ][ "TITLE" ] = $title;
	}
	
	public function AddPhone( $tel, $type = "MOBILE" ) {
		if ( ! is_array( $this->queryData[ "fields" ][ "PHONE" ] ) ) {
			$this->queryData[ "fields" ][ "PHONE" ] = [];
		}
		$this->queryData[ "fields" ][ "PHONE" ][] = [ "VALUE" => $tel, "VALUE_TYPE" => $type ];
	}
	
	public function AddEmail( $email, $type = "WORK" ) {
		if ( ! is_array( $this->queryData[ "fields" ][ "EMAIL" ] ) ) {
			$this->queryData[ "fields" ][ "EMAIL" ] = [];
		}
		$this->queryData[ "fields" ][ "EMAIL" ][] = [ "VALUE" => $email, "VALUE_TYPE" => $type ];
	}
	
	public function SetComments( $msg ) {
		$this->queryData[ "fields" ][ "COMMENTS" ] = $msg;
	}
	
	public function SetUtmSource( $value ) {
		$this->queryData[ "fields" ][ "UTM_SOURCE" ] = $value;
	}
	
	public function SetUtmMedium( $value ) {
		$this->queryData[ "fields" ][ "UTM_MEDIUM" ] = $value;
	}
	
	public function SetUtmCampaign( $value ) {
		$this->queryData[ "fields" ][ "UTM_CAMPAIGN" ] = $value;
	}
	
	public function SetUtmContent( $value ) {
		$this->queryData[ "fields" ][ "UTM_CONTENT" ] = $value;
	}
	
	public function SetUtmTerm( $value ) {
		$this->queryData[ "fields" ][ "UTM_TERM" ] = $value;
	}
	
	public function SetUserField( $name, $value ) {
		$this->queryData[ "fields" ][ $name ] = $value;
	}
	
	// список всех полей: https://dev.1c-bitrix.ru/rest_help/crm/leads/crm_lead_fields.php
	public function SetOther( $name, $value ) {
		$this->queryData[ "fields" ][ $name ] = $value;
	}
	
	public function DontRegisterSonetEvent() {
		$this->queryData[ "params" ][ "REGISTER_SONET_EVENT" ] = "N";
	}
	
	public function Send() {
		$curl = curl_init();
		curl_setopt_array( $curl, [
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST           => 1,
			CURLOPT_HEADER         => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $this->queryUrl,
			CURLOPT_POSTFIELDS     => http_build_query( $this->queryData ),
		] );
		
		$result = curl_exec( $curl );
		curl_close( $curl );
		
		if ( $result === false ) {
			$this->errorText = "curl_exec has returned false";
			
			return false;
		}
		
		$result = json_decode( $result, true );
		
		if ( array_key_exists( 'error', $result ) ) {
			$this->errorText = "B24 has returned error: " . $result[ 'error_description' ];
			
			return false;
		}
		
		return true;
	}
	
	public function GetError() {
		return $this->errorText;
	}
	
	public function GetQueryData() {
		return $this->queryData;
	}
}
