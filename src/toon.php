<?php namespace SliceOfLife\toonphp;

include('UUID.php');
require 'vendor/autoload.php';

class Toon {

  private $user;
  private $pass;
  private $toonstate;
  private $sessiondata;

  public function __construct($user,$pass){
        $this->user=$user;
        $this->pass=$pass;
    }


  public function login() {
    /*
    * Do initial login handshake with Toon backend to get agreement data
    */
    $payload = array(
      "username" => $this->user,
      "password" => $this->pass
    );

    $r = Requests::post('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/login', array(), $payload);

    $this->sessiondata = json_decode($r->body);


    /*
    * Now we do the actual handshake with the Toon backend
    * based on the agreement details we got from the initial login
    */

    $payload = array(
    "clientId" => $this->sessiondata->{'clientId'},
    "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
    "agreementId" => $this->sessiondata->{'agreements'}[0]->{'agreementId'},
    "agreementIdChecksum" => $this->sessiondata->{'agreements'}[0]->{'agreementIdChecksum'},
    "random" => UUID::v4()
    );

    $r = Requests::post('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/start', array(), $payload);
  }

  public function logout() {
    /*
    * We need this function as the Toon backend only as a maximum number of clients requesting data
    * before it starts spitting out 500 errors
    */

    $payload = array(
      "clientId" => $this->sessiondata->{'clientId'},
      "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
      "random" => UUID::v4()
    );

    $r = Requests::post('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/logout', array(), $payload);
    $this->toonstate = NULL;
    $this->sessiondata = NULL;
  }

  public function retrieve_toon_state() {
    $payload = array(
      "clientId" => $this->sessiondata->{'clientId'},
      "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
      "random" => UUID::v4()
    );

    $clientId = $this->sessiondata->{'clientId'};
    $clientIdChecksum = $this->sessiondata->{'clientIdChecksum'};
    $random = UUID::v4();
    $uriPref = 'https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/retrieveToonState?clientId=' . $clientId . '&clientIdChecksum=' . $clientIdChecksum . '&random=' . $random;
    $headers = array('Accept' => 'application/json');
    $r = Requests::get($uriPref, $headers);
    print($r->body);
    print('</br>');
    print($r->status_code);
    $this->toonstate = json_decode($r->body);
  }

  public function refresh_toon_state() {
    $this->toonstate = NULL;
    $this->retrieve_toon_state();
  }

  public function get_gas_usage() {
    $this->retrieve_toon_state();
    return $this->toonstate->{'gasUsage'};
  }

  public function get_power_usage() {
    $this->retrieve_toon_state();
    $powerusage = $this->toonstate->{'powerUsage'};
    return $powerusage;
  }

  public function get_thermostat_info() {
    $this->retrieve_toon_state();
    return $this->toonstate->{'thermostatInfo'};
  }

  public function get_thermostat_states() {
    $this->retrieve_toon_state();
    return $this->toonstate->{'thermostatStates'};
  }

  public function set_thermostat($temperature) {
    $tTemp = $temperature * 100;
    $payload = array(
      "clientId" => $this->sessiondata->{'clientId'},
      "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
      "value" => $tTemp,
      "random" => UUID::v4()
    );

    $r = Requests::post('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/setPoint', array(), $payload);
}

  public function get_program_state() {
    $this->retrieve_toon_state();
    return $this->toonstate->{'thermostatInfo'}->{'activeState'};
  }

  public function set_program_state($targetstate) {
    /* Sets the state to one of the 4 pre-programmed states
    * State zero (0)
    * State one (1)
    * State two (2)
    * State three (3)
    */

    $payload = array(
      "clientId" => $this->sessiondata->{'clientId'},
      "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
      "state" => 2,
      "temperatureState" => $targetstate,
      "random" => UUID::v4()
    );

    $r = Requests::post('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/schemeState', array(), $payload);
  }
}
