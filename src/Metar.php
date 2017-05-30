<?php

namespace CobaltGrid\AviationWeather\Metar;

use Carbon\Carbon;
use SimpleXMLElement;
use GuzzleHttp\Client;

class Metar
{
    private $raw_res = null;
    private $raw_array = null;
	
	private $base_url = "https://aviationweather.gov/adds/dataserver_current/httpparam";

    public function __construct ($icao)
    {
      $params = [
          "dataSource" => "metars",
          "requestType" => "retrieve",
          "hoursBeforeNow" => "12",
          "mostRecent" => "true",
          "stationString" => $icao
      ];
      $xml_raw = $this->sendRequest($params);
	  if(!$xml_raw){
	    return false;
	  }
	  $xml = new SimpleXMLElement($xml_raw);
	  if(((int) $xml->data->attributes()->num_results) == 0){
	    return false;
	  }
      $this->raw_res = $xml;
      $this->raw_array = $xml->data->METAR;
	  return true;
    }

    public function raw_response()
    {
      return $this->raw_res;
    }

    public function raw()
    {
      return $this->raw_array;
    }

    public function raw_string()
    {
      return (string) $this->raw_array->raw_text;
    }

    public function icao()
    {
      return (string) $this->raw_array->station_id;
    }

    public function time()
    {
      return new Carbon($this->raw_array->observation_time);
    }

    public function latitude()
    {
      return (float) $this->raw_array->latitude;
    }

    public function longitude()
    {
      return (float) $this->raw_array->longitude;
    }

    public function temperature()
    {
      return (float) $this->raw_array->temp_c;
    }

    public function dewpoint()
    {
      return (float) $this->raw_array->dewpoint_c;
    }

    public function wind_direction()
    {
      // 0 = Variable Direction
      return (int) $this->raw_array->wind_dir_degrees;
    }

    public function wind_speed()
    {
      // 0 & wind direction 0 = Wind Calm
      return (int) $this->raw_array->wind_speed_kt;
    }

    public function wind_gust()
    {
      return (int) $this->raw_array->wind_gust_kt;
    }

    public function visibility($unit = "m")
    {
      $vis = (float) $this->raw_array->visibility_statute_mi;

      switch ($unit) {
        case "km":
          return $vis * 1.609;
          break;
        case "m":
          return round(($vis * 1.609)*1000);
          break;
        case "nm":
          return $vis * 0.868976;
          break;
        case "mi":
          return (float) $vis;
          break;
        default:
          return (float) $vis;
          break;
      }
    }

    public function qnh($unit = "hpa")
    {
      $qnh = (float) $this->raw_array->altim_in_hg;

      switch ($unit) {
        case "hpa":
          if($this->raw_array->sea_level_pressure_mb){
            return (float) $this->raw_array->sea_level_pressure_mb;
          }
          return round($qnh * 33.863886666718315);
          break;
        case "hg":
          return $qnh;
          break;
        default:
          return $qnh;
          break;
      }
    }

    public function weather_array()
    {
      return explode(' ', (string) $this->raw_array->wx_string);
    }

    public function weather()
    {
      $orig_weather = $this->weather_array();

      // Replace + / - 's

      $weather = str_replace('+', 'Heavy ', $orig_weather);
      $weather = str_replace('-', 'Light ', $weather);
      $weather = str_replace('VC', 'In Vicinity: ', $weather);

      $weatherCodes = collect([
          "SH" => "Showers",
          "TS" => "Thunderstorm",
          "FZ" => "Freezing",
          "DZ" => "Drizzle",
          "RA" => "Rain",
          "SN" => "Snow",
          "SG" => "Snow Grains",
          "IC" => "ice crystals",
          "PL" => "Ice Pellets",
          "GR" => "Hail",
          "GS" => "Small Hail",
          "BR" => "Mist",
          "FG" => "Fog",
          "FU" => "Smoke",
          "VA" => "Volcanic ash",
          "DU" => "Widespread Dust",
          "SA" => "Sand",
          "HZ" => "Haze",
          "PY" => "Spray",
      ]);

      $just_keys = $weatherCodes->keys()->all();
      $just_vals = $weatherCodes->values()->all();

      $human_weather = str_replace($just_keys, $just_vals, $weather);

      $combined = collect($orig_weather)->values()->combine(collect($human_weather))->all();

      return $combined;
    }

    public function sky_cover()
    {

      $cloud_definitions = [
          "FEW" => "Few",
          "SCT" => "Scattered",
          "BKN" => "Broken",
          "OVC" => "Overcast"
      ];

      $clouds = $this->raw_array->sky_condition;
      $cloud_array = collect();
      foreach ($clouds as $cloud) {
        $cloud_array->push(['type' => (string) $cloud['sky_cover'], 'type_human' => $cloud_definitions[(string) $cloud['sky_cover']], 'height' => (int) $cloud['cloud_base_ft_agl']]);
      }
      return $cloud_array;
    }

    public function flight_cat()
    {
      return $this->raw_array->flight_category;
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
          // Unable to retrieve weather data
          return false;
        }
      } catch (\Exception $e) {
        return false;
      }
      return $content;
    }
	
    public function toArray()
    {
      $exclude_functions = [
        'raw',
        'raw_response',
        '__construct',
        'toArray',
		'sendRequest'
      ];
      $array = [];
      $methods = get_class_methods($this);
      foreach ($methods as $method_name) {
        if(array_search($method_name, $exclude_functions) === false){
          $array[$method_name] = $this->$method_name();
        }
      }

      return $array;

    }
}
