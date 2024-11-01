<?php
const WZ_URL_NAME = 'https://dzen.ru/api/v3/launcher/more?country_code=ru&channel_name=';
const WZ_URL_ID   = 'https://dzen.ru/api/v3/launcher/more?country_code=ru&channel_id=';
const WZ_ZEN_URL  = 'https://dzen.ru/';

class WzZenUtil {
	const WZ_URL_NAME = 'https://dzen.ru/api/v3/launcher/more?country_code=ru&channel_name=';
	const WZ_URL_ID = 'https://dzen.ru/api/v3/launcher/more?country_code=ru&channel_id=';
	const ZEN_URL = "https://dzen.ru/";


	/*
	 * Get json data by chanel name or id
	 */
	static function get_zen_json( $media ) {
		$url     = WZ_ZEN_URL . $media;
		$path    = parse_url( $url, PHP_URL_PATH );
		$url_arr = explode( '/', $path );

		if ( $url_arr[1] == "id" ) {
			$url = WZ_URL_ID . $url_arr[2];
		} else {
			$url = WZ_URL_NAME . $url_arr[1];
		}

		$options = array(
			'http' => array(
				'method' => "GET",
				'header' => "Accept-language: en\r\n" .
				            "User-Agent: Widget for Zen (https://wordpress.org/plugins/widget-for-yandex-zen/)\r\n"
			)
		);
		$context = stream_context_create( $options );

		return file_get_contents( $url, false, $context );
	}
}