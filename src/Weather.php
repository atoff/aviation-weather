<?php

namespace CobaltGrid\Aviation;

use SimpleXMLElement;
use GuzzleHttp\Client;
use App\Models\Data\Airport;
use CobaltGrid\Aviation\Weather\Metar;

class Weather
{
    private $raw_res = null;
    private $raw_array = null;
	private $icao;
	
	private $metar_base_url = "https://aviationweather.gov/adds/dataserver_current/httpparam";

    public function __construct($icao_code)
    {
        $this->icao_code = $icao_code;
    }

    public function latest_metar()
    {
        if($this->metar){
          return $this->metar;
        }
        $params = [
            "dataSource" => "metars",
            "requestType" => "retrieve",
            "hoursBeforeNow" => "12",
            "mostRecent" => "true",
            "stationString" => $this->icao_code
        ];
        $xml_raw = $this->sendRequest($params);
        if(!$xml_raw){
          return false;
        }
        $xml = new SimpleXMLElement($xml_raw);
        if(((int) $xml->data->attributes()->num_results) == 0){
          return false;
        }
        $this->metar = new Metar($xml);
        return $this->metar;
    }

    private function sendRequest($params = [])
    {
      $params['format'] = "xml";

      try {
        $client = new Client(['verify' => false]);
        $result = $client->get($this->base_url, [
          'query' => $params
          ]);
        $content = $result->getBody()->getContents();
        $statuscode = $result->getStatusCode();
        if (200 !== $statuscode) {
          \Log::error("Unable to retrieve weather data, http code " . $statuscode);
          return false;
        }
      } catch (\Exception $e) {
        \Log::info($e);
        return false;
      }
      return $content;
    }
}
