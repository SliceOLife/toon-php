<?php

include('UUID.php');

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
    $formdata = array(
      "username" => $this->user,
      "password" => $this->pass
    );

    $fjson = json_encode($formdata);

    $r = $this->sendToonRequest('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/login', $fjson);

    $this->sessiondata = json_decode($r);

    /*
    * Now we do the actual handshake with the Toon backend
    * based on the agreement details we got from the initial login
    */

    $formdata = array(
    "clientId" => $this->sessiondata->{'clientId'},
    "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
    "agreementId" => $this->sessiondata->{'agreements'}[0]->{'agreementId'},
    "agreementIdChecksum" => $this->sessiondata->{'agreements'}[0]->{'agreementIdChecksum'},
    "random" => UUID::v4()
    );

    $fjson = json_encode($formdata);

    $r = $this->sendToonRequest('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/start', $fjson);
  }

  public function logout() {
    /*
    * We need this function as the Toon backend only as a maximum number of clients requesting data
    * before it starts spitting out 500 errors
    */

    $formdata = array(
      "clientId" => $this->sessiondata->{'clientId'},
      "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
      "random" => UUID::v4()
    );

    $fjson = json_encode($formdata);

    $r = $this->sendToonRequest('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/logout', $fjson);
    $this->toonstate = NULL;
    $this->sessiondata = NULL;
  }

  public function retrieve_toon_state() {
    $formdata = array(
      "clientId" => $this->sessiondata->{'clientId'},
      "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
      "random" => UUID::v4()
    );

    $fjson = json_encode($formdata);

    $r = $this->sendToonRequest('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/retrieveToonState', $fjson);
    $this->toonstate = json_decode($r);
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
    return $this->toonstate->{'powerUsage'};
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
    $formdata = array(
      "clientId" => $this->sessiondata->{'clientId'},
      "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
      "value" => $tTemp,
      "random" => UUID::v4()
    );

    $fjson = json_encode($formdata);

    $r = $this->sendToonRequest('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/setPoint', $fjson);
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

    $formdata = array(
      "clientId" => $this->sessiondata->{'clientId'},
      "clientIdChecksum" => $this->sessiondata->{'clientIdChecksum'},
      "state" => 1,
      "temperatureState" => $targetstate,
      "random" => UUID::v4()
    );

    $fjson = json_encode($formdata);

    $r = $this->sendToonRequest('https://toonopafstand.eneco.nl/toonMobileBackendWeb/client/auth/schemeState', $fjson);
  }

  private function sendToonRequest($URI, $json) {
    $ch = curl_init($URI);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $toonResponse = curl_exec($ch);

    return $toonResponse;
  }
}
