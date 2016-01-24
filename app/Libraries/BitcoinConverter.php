<?php

namespace Mint\Libraries;

class BitcoinConverter
{
    /**
     * Calculation scale.
     *
     * @access protected
     *
     */
	protected static $scale = 8;

    /**
     * Currencies allowed by this class.
     *
     * @access public
     *
     */
	public static $allowed_currency = ['btc', 'mbtc', 'ubtc', 'satoshi'];

    /**
     * Current amount.
     *
     * @access protected
     *
     */
	protected $amount = 0;
	
    /**
     * Currency that constructor was initialised with
     *
     * @access public
     *
     */
	public $initial_currency = 'btc';

    /**
     * Converter constructor.
	 *
     * param int $btc
     * @return void
     */
	private function __construct($amount = 0, $currency = 'btc')
	{
		$this->amount = number_format( $amount, BitcoinConverter::$scale );
		$this->initial_currency = $currency;
	}

    /**
     * Magic method to call user functions.
     *
	 * param string $name
     * @return float
     */
	public function __get($currency)
	{
		$fname = 'get_' . $currency;
		if( is_callable( ['Mint\Libraries\BitcoinConverter', $fname] ) ) {
			return call_user_func("Mint\Libraries\BitcoinConverter::$fname", $this->amount );
		}
	}

    /**
     * Send payment from one address to another
     *
	 * param string $name
	 * param array $args
     * @return Response
     */
	public static function __callStatic($currency, $args)
	{
		$fname = 'get_' . $currency;
		if( is_callable( ['self', $fname] ) ) {
			$amount = call_user_func( ['self', $fname], /* amount */ $args[0], /* from BTC */ false);
		}

		return new self($amount, $currency);
	}

    /**
     * Send payment from one address to another
     *
	 * param bool $from_btc
     * @return Response
     */
	public static function get_satoshi($amount, $from_btc = true)
	{
		return ($from_btc) ?
			bcmul($amount, 100000000, self::$scale) : bcdiv($amount, 100000000, self::$scale);
	}

    /**
     * Send payment from one address to another
     *
	 * param bool $from_btc
     * @return Response
     */
	public static function get_ubtc($amount, $from_btc = true)
	{
		return ($from_btc) ?
			bcmul($amount, 100000, self::$scale) : bcdiv($amount, 100000, self::$scale);
	}

    /**
     * Send payment from one address to another
     *
	 * param bool $from_btc
     * @return Response
     */
	public static function get_mbtc($amount, $from_btc = true)
	{
		return ($from_btc) ?
			bcmul($amount, 100, self::$scale) : bcdiv($amount, 100, self::$scale);
	}

	/**
     * Send payment from one address to another
     *
     * @return Response
     */
	public static function get_btc($amount)
	{
		return $amount;
	}

	/**
     * Send payment from one address to another
     *
     * @return Response
     */
	public static function get_guess($amount)
	{
		if( !is_numeric($amount) ) {
			$c_candidate = '';
			foreach(BitcoinConverter::$allowed_currency as $c) {
				if(strripos($amount, $c) !== false) {
					$c_candidate = ( strlen($c) < strlen($c_candidate) ) ? $c_candidate : $c;
				}
			}

			$c_position = strripos($amount, $c_candidate);
			$n_position = strlen($amount) - strlen($c_candidate);

			if($c_position == $n_position) {
				$value = substr($amount, 0, $n_position);
				$currency = substr($amount, $c_position, strlen($c_candidate) );
				if( !is_numeric($value) ) {
					return false;
				}
				return call_user_func(['self', "get_$currency"], $value, false);
			}
		}
		return call_user_func(['self', 'get_satoshi'], $amount, false);
	}
}