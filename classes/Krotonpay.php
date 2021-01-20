<?php

/**
 * @author Santos L. Victor
 * Class Kroton Pay
 * @property Krotonpay_model $kroton
 * @see https://dev-kp-lb-1088104097.us-east-1.elb.amazonaws.com/swagger/index.html?urls.primaryName=2.0
 */

Class Krotonpay {

    private $end = null;
    private $user = unll;
    private $password = null;
    private $jwt = null;


    function __construct($endPoint, $user, $password) {
        
        $this->end = $endPoint;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @see Resposável por montar o payload
     * @return [Json|Array] array json encode
     * @param [Object] Objeto
     * @param [datetime] $dueDate '2020-12-20T13:50:12.454Z'
     * @param [datetime] $dueDateInformative '2020-12-10T13:50:12.454Z'
     * @param [datetime] $issueDate '2020-12-01T13:50:12.454Z
     * @param [datetime] $limitDate '2020-12-01T13:50:12.454Z'
    */
    public function makeCheckoutPayload($allow) {

        $params = array (
                'orderReference' => $allow->ordemProdutcService,
                'invoiceType' => $allow->productService,
                'fineValue' => 0,
                'fineValuePercentage' => 0,
                'lateInterestValue' => 0,
                'lateInterestValuePercentage' => 0,
                'dueDate' => $allow->dueDate, // data de vencimento
                'dueDateInformative' => $allow->dueDateInformative, // data informativa
                'discount' =>
                    array (
                        'amount' => isset($allow->discount)?$allow->discount:0,
                        'limitDate' => $allow->limitDate
                    ),
                'student' =>
                    array (
                        'studentReference' => $allow->alunoid, // num mlid = matricula
                        'cpf' => $allow->alunocpf,
                        'subscription' => $allow->alunosubscription,
                        'name' => $allow->alunonome,
                        'email' => $allow->alunoemail,
                        'address' =>
                            array (
                                'street' => $allow->alunoemail,
                                'number' => $allow->alunonumber,
                                'complement' => $allow->alunocomplement,
                                'city' => $allow->alunocity,
                                'state' => $allow->alunostate,
                                'zip' => $allow->alunocep,
                                'country' => $allow->alunocountry,
                                'neighborhood' => $allow->alunoneighborhood
                            ),
                    ),
                'redirectUrl' => $allow->redirectUrl,
                'krotonBrand' => $allow->krotonBrand,
                'adjustLayoutToIFrame' => false,
                'school' =>
                    array (
                        'name' => $allow->school_name, // depende da unidade , Tamandaré ou  Sergipe
                        'cnpj' => $allow->school_cnpj,
                        'address' =>
                            array (
                                'street' => $allow->school_address_street,
                                'number' => $allow->school_address_number,
                                'city' => $allow->school_address_city,
                                'state' => $allow->school_address_state,
                                'zip' => $allow->school_address_zip,
                                'country' => $allow->school_address_country,
                                'complement' => (!isset($allow->school_address_complement)?null:$allow->school_address_complement),
                                'neighborhood' => $allow->school_address_neighborhood
                            ),
                    ),
                'checkoutVersion' => 'V2'
        );


        if (isset($allow->products)) {
            

            foreach ($allow->products as $key => $product) {

                $params['charges'][$key] = array (
                                    'chargeReference' => $allow->chargeReference,
                                    'description' => $product->description,
                                    'issueDate' => $allow->issueDate,
                                    'course' =>
                                        array (
                                            'courseReference' => $product->course->courseReference, 
                                            'name' => $product->course->name
                                        )
                                );
                
                foreach ($product->items as $k => $item) {

                    $params['charges'][$key]['items'][] = array (
                                                            'itemReference' => $allow->itemId,
                                                            'lineNumber' => 1,
                                                            'product' => $allow->productServiceItem,
                                                            'description' => $item->description,
                                                            'unitPrice' => $item->unitPrice,
                                                            'quantity' => $allow->itemQtd,
                                                            'totalPrice' => $item->totalPrice
                                                        );

                }

            }
        }

        return json_encode($params);
    }

    /**
     * @see Responsável por receber um pacote JWT, descripto e criar um payload
     * @param [Json] $params is paylod valid
     * @author Santos L. Victor
    */
    public function Checkout($params) {
        
        if (!empty($params)) {

            $data['response'] = $this->curlPost(__FUNCTION__, $params);
        
        } else {
            $data['response'] = array('type'=>'danger','msg'=>'Área de acesso restrito!');
        }
        return $data['response'];
    }

    /**
     * @see responsável por gerar um token JWT
     */
    public function tokenCreate() {

        $params = array($this->user, $this->password);

        $end = 'v2/Token/create';

        self::$jwt = $this->curlPost($end, $params);
    }

    /**
     * @see Realiza a chamada de requisilção
     * @param [Array] $data array dos valores que serão enviado via postman
     * @param [Array] $method url final endpoint
     * @param [String] $basic , hash
     * @author Santoos L. victor
     */
    private function curlPost($method=null, $data=array(), $basic) {

        if(empty($method) || is_null($method))
            return false;

        if(empty($data) || is_null($data))
            return false;

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->end.$method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_POSTREDIR => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS=> 10,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSLVERSION => 1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$data, // in json_encode
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic '.$basic,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            // Check if any error occurred
            if(curl_errno($curl)) {
                $response = 'Curl error: ' . curl_error($curl);
            }
            curl_close ($curl);

        } catch (\RuntimeException $ex) {
            $response = $ex->getMessage();
        }
        return $response;
    }
}