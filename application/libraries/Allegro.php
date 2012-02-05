<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Allegro WebApi client
 *
 * @author          Jan Bogdanski <janek.bogdanski@gmail.com>
 * @package         Proaukcje Kreator Aukcji
 * @see             https://github.com/janbogdanski/codeigniter-allegro-webapi
 * @
 * Creation date    03.02.12 18:49
 */

class Allegro extends CI_Controller {


    private $_login;
    private $_password;
    private $_api_key;
    private $_wsdl;
    private $_country;


    private $_client;
    private $_version;
    private $_session;

    private $vars;

    private $_file_name = '.allegro_webapi_session';

    public function __construct(){

        parent::__construct();


        $this->load->config('allegro');

        $this->_login = config_item('login');
        $this->_password = config_item('password');
        $this->_api_key = config_item('api_key');
        $this->_wsdl = config_item('wsdl');
        $this->_country = config_item('country');

        //tworzymy klienta Soap
        $this->_client = new SoapClient($this->_wsdl);

        //wczytujemy sesje z pliku, majac nadzieje, ze jest jeszcze aktywna :)
        //jesli jest nieaktywna - odpytamy allegro o nia
        $this->_session['session-handle-part'] = @file_get_contents($this->_file_name);


    }

    /**
     * Pomocnicza, zamienia slowa kluczowe na ich wartosci,
     * np. 'login' na 'login_w_allegro'
     * @param $params
     * @return array
     */
    private function _replace_vars($params){

        //tablica z przepisanymi wartosciami
        $call_params  = array();

        foreach($params as $param){
            switch($param){

                case 'country-id': case 'country-code':case 'country':
                $call_params[] = $this->_country;
                    break;

                case 'local-version':case 'ver-key': $call_params[] = $this->_version['ver-key'];
                    break;

                case 'session-handle': case 'session-id': case 'session':
                $call_params[] = $this->_session['session-handle-part'];
                    break;

                case 'user-login': case 'login': $call_params[] = $this->_login;
                    break;

                case 'user-password': case 'password': $call_params[] = $this->_password;
                    break;

                case 'webapi-key': case 'api-key': $call_params[] = $this->_api_key;
                    break;

                default: $call_params[] = $param;
                    break;
            }
        }
        return $call_params;
    }

    /**
     * Przechwytuje metody, ktorymi odpytujemy Allegro
     *
     * $user_id = $this->allegro->doGetUserID('country-id', 'nick_do_sprawdzenia', 'niepuste_wymagane', 'webapi-key');

     * @param $name
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $params){

        $call_params = $this->_replace_vars($params);

        try{

            return call_user_func_array(array($this->_client, $name), $call_params);

        } catch (Exception $e){

            $code = $e->faultcode;
            if($code == 'ERR_NO_SESSION' OR $code == 'ERR_SESSION_EXPIRED' OR $code == 'ERR_INVALID_VERSION_CAT_SELL_FIELDS'){

                //to blad zw z sesja lub wersja lokalna - musimy zaktualizowac wartosci
                try{
                    $this->_version = $this->doQuerySysStatus(1,  'country-id' ,'webapi-key');
                    $this->_session = $this->doLogin('user-login', 'user-password', 'country-code', 'webapi-key', 'local-version');

                    //zapisz sesje na pozniej
                    file_put_contents($this->_file_name, $this->_session['session-handle-part']);

                    //wywolaj metode, ktorej sie wczesniej nie udalo
                    $call_params = $this->_replace_vars($params);

                    return  call_user_func_array(array($this->_client, $name), $call_params);

                } catch (SoapFault $e){
                    throw new Exception('Blad w sesji..');
                }
            }
        }
    }
}


