<?php

namespace CobaltGrid\Aviation;

use Cache;
use SimpleXMLElement;
use GuzzleHttp\Client;
use CobaltGrid\Aviation\Weather\Metar;

class Weather
{
    private $raw_res = null;
    private $raw_array = null;
    private $icao;
    private $metar;

    const METAR_CACHE_KEY = "CGWEATHER_METAR_CACHE_";

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
        if(Cache::has(self::METAR_CACHE_KEY.$this->icao_code)){
          return Cache::get(self::METAR_CACHE_KEY.$this->icao_code);
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
        Cache::put(self::METAR_CACHE_KEY.$this->icao_code, $this->metar->toArray(), 3);
        return $this->metar->toArray();
    }

    private function sendRequest($params = [])
    {
      $params['format'] = "xml";

      try {
        $client = new Client(['verify' => false]);
        $result = $client->get($this->metar_base_url, [
          'query' => $params
          ]);
        $content = $result->getBody()->getContents();
        $statuscode = $result->getStatusCode();
        if (200 !== $statuscode) {
          \Log::error("Unable to retrieve weather data, http code " . $statuscode);
          return false;
        }
      } catch (\Exception $e) {
        \Log::error($e);
        return false;
      }
      return $content;
    }
}
