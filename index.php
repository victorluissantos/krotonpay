<?php

if(isset($_GET['debug']) && $_GET['debug']==true) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

/*Load all libraries in composee*/
require_once __DIR__ . '/vendor/autoload.php';


/*Load all files extensions .php in __DIR_ folder*/
foreach (glob("classes/*.php") as $filename)
{
    include $filename;
}

use \Firebase\JWT\JWT;

class kroton extends Krotonpay {

    private $user = null;
    private $password = null;
    private $key = ""; // info key
    private $endWSDL = ""; // indo WSDL/EndPoint

	public function __construct($endpoint=null) {
        if (!is_null($endpoint)) {
            $this->endWSDL=$endpoint;
        }
	}

	/**
	 * @see 
	*/
	public function index($allowed) {		

		$data = array(
					'type' => 'danger',
					'msg' => 'Área de acesso restrito!'
					);
		
		if (isset($allowed)&&!empty($allowed)) {

			$data = JWT::decode($allowed, $this->key, array('HS256'));
			if (is_object($data)) {
				
				if($data->debug):
					ini_set('display_errors', 1);
					ini_set('display_startup_errors', 1);
					error_reporting(E_ALL);
				endif;

				$kroton = new Krotonpay($this->endWSDL, $data->user, $data->password);
				
				$payload = $kroton->makeCheckoutPayload($data);

				$data = array(
							'type' => 'success',
							'payload' => ($data->debug?$payload:'desabled'),
							'data' => ($data->debug?$data:'desabled'),
							'msg' => $kroton->Checkout($payload)
						);
			} else {
				$data = array(
							'type' => 'warning',
							'msg' => 'Falha na comunicação verifique suas credenciais de criptografia!'
							);
			}
		}

		return json_encode($data);
	}
}

$kroton = New kroton();

// // Call method index if need
if(isset($_POST['token'])) {
	echo $kroton->index($_POST['token']);
}