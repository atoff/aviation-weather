# Aviation Weather
A simple (Laravel compatible) library for fetching and decoding METARs. (Soon to be more than this, however)

Installation
-------

Using Composer:

`$ composer require cobaltgrid/aviation-weather 1.0`   

This will ensure that all required dependencies will be installed automatically.


Usage
-------
At the moment, AviationWeather only support ICAO codes. You can load a METAR by passing the ICAO code when you construct the class.
> use Cobaltgrid\AviationWeather\Metar;
> ...
$metar = new Metar("EGKK");

Method   | Description
-------- | ---
raw_response() | This gets you the raw JSON from the data source (SimpleXMLElement)
raw()    | This gets you the raw JSON METAR data from the data source (SimpleXMLElement)
raw_string()     | The raw METAR string (String)
icao()     | The ICAO code of the station (String)
time()     | A carbon object for the METAR's observation time (Carbon)
latitude()     | A float of the latitude of the observation (Float)
longitude()     | A float of the longitude of the observation (Float)
temperature()     | The temperature, in degrees celsius (Float)
dewpoint()     | The dew point, in degrees celsius (Float)
wind_direction()     | The wind direction, in whole degrees. If 0, this indicates a variable wind. (Int)
wind_speed()     | The wind speed, in knots. If both speed and direction are 0, wind is 'calm' (Int)
wind_gust()     | The wind gust, in knots (Int)
visibility($unit="m")     | Options: "km", "m", "nm", "mi". Gives the visibility in the chosen units. (Float/Int)
qnh($unit="hpa")     | Options: "hpa", "hg". Gives the QNH pressure setting in the chosen units. (Float/Int)
weather_array()     | An array of raw weather codes (i.e "-HZ +SH" etc) (Array)
weather()     | An array of decoded weather codes. Key = Code (i.e '-RA'), Value = Decoded Meaning (i.e 'Light Rain') (Array)
sky_cover()     | An array of decoded cloud cover. Each array item is an array with the following format: `['type' => 'SCT', 'type_human' => 'Scattered', 'height' => 2000]` . Height is in feet.
flight_cat()     | An indication of the type of flying permitted by the weather. Example values: MVFR, IFR, LVFR, etc.
to_array()     | Converts the above into an array format


