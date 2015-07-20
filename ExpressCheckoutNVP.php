<?php

/**
 * PayPal Express Checkout via NVP API
 * 
 * @author Nettraction <dev@nettraction.in>
 * @version v0.1
 * 
 * @todo $this->log_msg need not be part of this class
 */
class ExpressCheckoutNVP {

  private $api_username;
  private $api_password;
  private $api_signature;
  private $api_version = '119.0';
  private $api_env = 'live';
  private $api_endpoint;
  private $last_curl_errno;
  private $last_curl_error;
  private $mask_fields = array('USER', 'PWD', 'SIGNATURE');
  private $echo_logs;
  private $debug_level; // dev or live

  public function __construct($username, $password, $signature, $ver = '119.0', $env = 'live', $echo_logs = TRUE, $dlevel='dev') {

    $this->api_username = $username;
    $this->api_password = $password;
    $this->api_signature = $signature;

    $this->api_version = $ver;
    $this->api_env = $env;

    $this->echo_logs = $echo_logs;
    $this->debug_level = $dlevel;

    // Figure out API endpoint sandbox/live
    $this->api_endpoint = "https://api-3t.paypal.com/nvp";
    if ("sandbox" === $this->api_env || "beta-sandbox" === $this->api_env) {
      $this->api_endpoint = "https://api-3t.$env.paypal.com/nvp";
    }

    $this->log_msg('debug', 'Endpoint set to ' . $this->api_endpoint);
  }

  /**
   * 
   */
  public function set_express_checkout($payment_request) {

    $this->log_msg('debug', 'Inside ' . __METHOD__);

    //$api_params = array();
    $api_output = array();

    $api_params['USER'] = $this->api_username;
    $api_params['PWD'] = $this->api_password;
    $api_params['SIGNATURE'] = $this->api_signature;
    $api_params['VERSION'] = $this->api_version;
    $api_params['METHOD'] = 'SetExpressCheckout';

    $this->log_msg('debug', 'Sending Request...');
    $this->log_msg('debug', $api_params, $this->mask_fields);

    $api_params = array_merge($api_params, $payment_request);


    $api_response = $this->do_post($this->api_endpoint, $api_params);

    parse_str($api_response, $api_output);

    return $api_output;
  }

  public function get_express_checkout_details($token) {

    $this->log_msg('debug', 'Inside ' . __METHOD__);

    $api_params = array();

    $api_params['USER'] = $this->api_username;
    $api_params['PWD'] = $this->api_password;
    $api_params['SIGNATURE'] = $this->api_signature;
    $api_params['VERSION'] = $this->api_version;
    $api_params['METHOD'] = 'GetExpressCheckoutDetails';
    $api_params['TOKEN'] = $token;

    $this->log_msg('debug', 'Sending Request...');
    $this->log_msg('debug', $api_params, $this->mask_fields);

    $api_response = $this->do_post($this->api_endpoint, $api_params);

    parse_str($api_response, $api_output);

    return $api_output;
  }

  public function do_express_checkout_payment($token, $payment_request) {
    $this->log_msg('debug', 'Inside ' . __METHOD__);

    $api_params = array();

    $api_params['USER'] = $this->api_username;
    $api_params['PWD'] = $this->api_password;
    $api_params['SIGNATURE'] = $this->api_signature;
    $api_params['VERSION'] = $this->api_version;
    $api_params['METHOD'] = 'DoExpressCheckoutPayment';
    $api_params['TOKEN'] = $token;

    $api_params = array_merge($api_params, $payment_request);

    $this->log_msg('debug', 'Sending Request...');
    $this->log_msg('debug', $api_params, $this->mask_fields);

    $api_response = $this->do_post($this->api_endpoint, $api_params);

    parse_str($api_response, $api_output);

    return $api_output;
  }

  /**
   * 
   * @param string $url API Endpoint URL
   * @param array $post_data 
   * @param array $curl_options (Optional) CURL Options
   * @return boolean
   */
  private function do_post($url, $post_data, $curl_options = array()) {

    $this->log_msg('debug', 'Inside ' . __METHOD__);

    // setting the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    $this->log_msg('debug', 'curl_init to ' . $url);


    // Any additional curl options
    if (is_array($curl_options)) {
      if (count($curl_options) > 0) {
        curl_setopt_array($ch, $curl_options);
      }
    }

    // Set the basic curl options
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    // Set the request as a POST FIELD for curl.
    $this->log_msg('debug', 'Post Data>> ' . print_r($post_data, true));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

    // Get response from the server.
    $api_response = curl_exec($ch);

    $this->log_msg('debug', $api_response);

    $this->last_curl_errno = curl_errno($ch);
    $this->last_curl_error = curl_error($ch);

    if ($this->last_curl_errno !== 0) {
      $this->log_msg('error', 'Curl Error: #' . $this->last_curl_errno . ' (' . $this->last_curl_error . ')');
    }

    if ($api_response === FALSE) {
      // Error
      return FALSE;
    } else {
      return $api_response;
    }
  }

  private function log_msg($_type, $_msg, $mask_fields = array()) {

    $dlevel = array();
    $dlevel['dev'] = array('error', 'info', 'debug');
    $dlevel['live'] = array('error');

    if (is_array($_msg)) {
      $_msg = $this->mask_array_fields($_msg, $mask_fields);
      $_msg = print_r($_msg, true);
    }

    switch ($this->debug_level) {
      case 'live':
        if (in_array($_type, $dlevel['live'])) {
          error_log(strtoupper($_type) . ': ' . $_msg);
        }
        break;
      default:
        error_log(strtoupper($_type) . ': ' . $_msg);
        break;
    }

    if ($this->echo_logsecho_logs == true) {
      echo strtoupper($_type) . ': ' . $_msg . '<br/>';
    }
  }
  
  
  private function mask_array_fields($list, $fields, $mask = '*') {

    if (empty($mask)) {
      $mask = '*';
    }

    if (count($fields) > 0) {
      foreach ($fields as $m) {
        $list[$m] = str_repeat($mask, (strlen($list[$m])));
      }
    }

    return $list;
  }

}
