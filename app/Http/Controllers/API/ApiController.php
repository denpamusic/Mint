<?php

namespace Mint\Http\Controllers\API;

use Mint;
use Mint\Libraries\BitcoinConverter as Converter;
use Mint\Libraries\ZebraCURL;
use Mint\Libraries\JsonRPCClient;
use Mint\Exceptions\JsonException;

use Illuminate\Routing\Controller;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use Webpatser\Uuid\Uuid;

class ApiController extends Controller
{
	protected $user;
	protected $curl;
	protected $bitcoin_core;

    /**
     * Controller constructor
     *
     * @param  jsonRPCClient  $bitcoin_core
	 * @param  ZebraCURL      $curl
     * @return void
     */
	public function __construct(jsonRPCClient $bitcoin_core, ZebraCURL $curl)
	{
		$this->curl = $curl;
		$this->bitcoin_core = $bitcoin_core;
		if( $guid = Route::current()->getParameter('guid') ) {
			$this->user = $this->attemptAuth($guid);
			$this->bitcoin_core->setRpcConnection($this->user->rpc_connection);
		}
	}

	public function getIndex()
	{
		return View::make('welcome');
	}

    /**
     * Get user balance
     *
     * @return Response
     */
    public function balance()
    {
		/* Get input variables */
		$currency = Input::get('currency') ? Input::get('currency') : 'btc';

		if( !in_array($currency, Converter::$allowed_currency) ) {
			throw new JsonException( trans('error.invalidcurrency') );
		}

		$balance_model = Mint\Balance::getBalance($this->user->id);

		if(!$balance_model) {
			throw new JsonException( trans('error.nobalancedb') );
		}

		return Response::json( [ 'balance' => Converter::satoshi($balance_model->balance)->$currency, 'currency' => $currency ] );
    }

    /**
     * Get address account balance
     *
     * @return Response
     */
	public function addressBalance()
	{
		/* Get input variables */
		$address = Input::get('address');
		$currency = Input::get('currency') ? Input::get('currency') : 'btc';

		if ( empty($address) ) {
			throw new JsonException( trans('error.noaddressinput') );
		}

		if( !in_array($currency, Converter::$allowed_currency) ) {
			throw new JsonException( trans('error.invalidcurrency') );
		}

		$address_model = Mint\Address::getAddress($address);

		if(!$address_model) {
			throw new JsonException( trans('error.noaddressdb') );
		}

		return Response::json([
			'address'  => $address_model->address,
			'balance'  => Converter::satoshi($address_model->balance)->$currency,
			'currency' => $currency,
		]);
	}

    /**
     * Get bitcoin core balance
     *
     * @return Response
     */
	public function coreBalance()
	{
		/* Get input variables */
		$currency    = Input::get('currency') ? Input::get('currency') : 'btc';
		$unconfirmed = Input::get('unconfirmed');

		if( !in_array($currency, Converter::$allowed_currency) ) {
			throw new JsonException( trans('error.invalidcurrency') );
		}

		$core_balance = ( !$unconfirmed ) ? $this->bitcoin_core->getbalance() : $this->bitcoin_core->getunconfirmedbalance();
		return Response::json( ['balance' => Converter::btc($core_balance)->$currency, 'currency' => $currency, 'is_unconfirmed' => (bool)$unconfirmed] );
	}

    /**
     * Validate transaction by given Id
     *
     * @return Response
     */
	public function validateTransaction()
	{
		/* Get input variables */
		$tx_id = Input::get('txid');

		if( empty($tx_id) ) {
			throw new JsonException( trans('error.notxidinput') );
		}

		$tx_info = $this->bitcoin_core->gettransaction($tx_id);

		return Response::json( ['is_valid' => true, 'tx_id' => $tx_id] );
	}

    /**
     * Validate given address
     *
     * @return Response
     */
	public function validateAddress()
	{
		/* Get input variables */
		$address = Input::get('address');

		if( empty($address) ) {
			throw new JsonException( trans('error.noaddressinput') );
		}

		$is_valid = (bool)$this->isValidAddress($address);
		$is_mine = (bool)$this->isUserAddress($address);

		return Response::json([
			'is_valid' => $is_valid,
			'is_mine' => $is_mine,
			'address' => $address,
		]);
	}

    /**
     * Create new address and assign it to account with the same name
     *
     * @return Response
     */
	public function newAddress()
	{
		/* Get input variables */
		$label = Input::get('label') ? Input::get('label') : '';

		$address = $this->getAddress($label);
		return Response::json( ['address' => $address, 'label' => $label] );
	}

    /**
     * Return users fee address
     *
     * @return Response
     */
	public function feeAddress()
	{
		/* Get input variables */
		$currency = Input::get('currency')  ? Input::get('currency') : 'btc';

		if( !in_array($currency, Converter::$allowed_currency) ) {
			throw new JsonException( trans('error.invalidcurrency') );
		}

		$fee_address = $this->getFeeAddress();

		$address_model = Mint\Address::getAddress($fee_address);

		$address_balance = Converter::satoshi( $address_model->balance );
		$merchant_fee = Converter::btc( Mint\Settings::getVal('merchant_fee') );

		return Response::json( ['address' => $address_model->address, 'balance' => $address_balance->$currency, 'merchant_fee' => $merchant_fee->$currency, 'currency' => $currency] );
	}

    /**
     * Get transaction confirmations.
     *
     * @return Response
     */
	public function txConfirmations()
	{
		if( !$tx_id = Input::get('txid') ) {
			throw new JsonException( trans('error.notxidinput') );
		}

		$tx_info = $this->bitcoin_core->gettransaction($tx_id);
		return Response::json( [ 'confirmations' => $tx_info["confirmations"], 'txid' => $tx_id ] );
	}

    /**
     * Get address transactions
     *
     * @return Response
     */
	public function addressTransactions()
	{
		/* Get input variables */
		$address  = Input::get('address');
		$confirms = Input::get('confirms') ? Input::get('confirms') : 0;
		$currency = Input::get('currency') ? Input::get('currency') : 'btc';

		if( empty($address) ) {
			throw new JsonException( trans('error.notaddressinput') );
		}

		if( !in_array($currency, Converter::$allowed_currency) ) {
			throw new JsonException( trans('error.invalidcurrency') );
		}

		if( !$this->isValidAddress($address) || !$this->isUserAddress($address) ) {
			throw new JsonException( trans('error.invalidaddress') );
		}

		$tx_collection = Mint\Transaction::getTransactionsByAddressAndConfirms($address, $confirms);
		$response = [
			'amount'   => 0,
			'fee'      => 0,
			'currency' => $currency,
			'tx_num'   => count($tx_collection),
			'tx_list'  => [],
		];

		foreach($tx_collection as $tx_model) {
			$tx_amount = Converter::satoshi($tx_model->crypto_amount);
			$tx_fee = Converter::satoshi( bcadd($tx_model->network_fee, $tx_model->merchant_fee, 8) );
			$response['amount'] = bcadd($response['amount'], $tx_amount->$currency, 8);
			$response['fee'] = bcadd($response['fee'], $tx_fee->$currency, 8);
			$response['tx_list'][] = [
				'tx_id'       => $tx_model->tx_id,
				'tx_from'     => $tx_model->address_from,
				'tx_to'       => $tx_model->address_to,
				'tx_amount'   => $tx_amount->$currency,
				'tx_fee'      => $tx_fee->$currency,
                'tx_confirms' => $tx_model->confirmations,
			];
		}

		return Response::json( $response );
	}

    /**
     * Get unpaid invoices
     *
     * @return Response
     */
	public function unpaidInvoices()
	{
		/* Get input variables */
		$currency = Input::get('currency') ? Input::get('currency') : 'btc';

		$response = [];

		$invoice_collection = Mint\Invoice::getUnpaidInvoices();
		foreach($invoice_collection as $invoice) {
			$response[] = [
				'address'         => $invoice->address->address,
				'invoice_amount'  => Converter::satoshi( $invoice->invoice_amount )->$currency,
				'received_amount' => Converter::satoshi( $invoice->received_amount )->$currency,
				'date'            => $invoice->created_at->toDateTimeString(),
			];
		}

		return Response::json( $response );
	}

    /**
     * Send payment from one address to another
     *
     * @return Response
     */
	public function payment()
	{
		/* Get input variables */
		$to_address   = Input::get('to');
		$from_address = Input::get('from');
		$amount       = Input::get('amount');
		$note         = Input::get('note') ? Input::get('note') : '';
		$delay        = Input::get('delay');
		$currency     = Input::get('currency') ? Input::get('currency') : 'btc';

		if( empty($to_address) || empty($amount) ) {
			throw new JsonException( trans('error.noaddressamountinput') );
		}

		if( !in_array($currency, Converter::$allowed_currency) ) {
			throw new JsonException( trans('error.invalidcurrency') );
		}

		/* Check if both address from and address to is valid */
		if( !$this->isValidAddress($from_address) || !$this->isValidAddress($to_address) ) {
			throw new JsonException( trans('error.invalidaddress') );
		}

		/* Additionally check that from address belong to user. */
		if( !$this->isUserAddress($from_address) ) {
			throw new JsonException( trans('error.notuserfrom') );
		}

		if( !empty($delay) ) {
			if( !( $fee = $this->estimateFee( (integer)$delay ) ) ) {
				throw new JsonException( trans('error.noesteemfee') );
			}
		} else {
			$fee = Mint\Settings::getVal('tx_fee');
		}

		$amount = Converter::guess($amount);
		$network_fee = Converter::btc($fee);
		$merchant_fee = Converter::btc( Mint\Settings::getVal('merchant_fee') );

		$response = $this->sendFrom(
			$from_address,
			$to_address,
			$note,
			$amount,
			$network_fee,
			$merchant_fee
		);

		return Response::json([
			'message'      => 'success',
			'amount'       => $response['amount']->$currency,
			'network_fee'  => $response['tx_fee']->$currency,
			'merchant_fee' => $response['merchant_fee']->$currency,
			'from_address' => $response['from_address'],
			'to_address'   => $response['to_address'],
			'txid'         => $response['tx_id'],
			'is_internal'  => $response['is_internal'],
		]);
	}

    /**
     * Create invoice address
     *
     * @return Response
     */
	public function receive()
	{
		/* Get input variables */
		$key                 = Input::get('key');
		$method              = Input::get('method');
		$user_id             = Input::get('userid');
		$destination_address = Input::get('address');
		$callback_url        = Input::get('callback');
		$label               = Input::get('label');
		$forward             = Input::get('forward');
		$invoice_amount      = Input::get('amount');
		$currency            = Input::get('currency') ? Input::get('currency') : 'btc';

		$allowed_methods = [ 'create', 'delete' ];

		if( !in_array($method, $allowed_methods) ) {
			throw new JsonException( trans('error.invoicemethodnotallowed') );
		}

		if ( $key != Mint\Settings::getVal('api_key') ) {
			throw new JsonException( trans('error.invalidkey') );
		}

		if ( empty($user_id) ) {
			throw new JsonException( trans('error.nouser') );
		}

		$this->user = Mint\User::find($user_id);
		$this->bitcoin_core->setRpcConnection($this->user->rpc_connection);

		$invoice_amount = Converter::guess(!empty( $invoice_amount ) ? $invoice_amount : 0);

		if ( empty($destination_address) ) {
			$forward = 0;
		} else {
			if ( !$this->isValidAddress($destination_address) ) {
				throw new JsonException( trans('error.invalidaddress') );
			}
		}

		$input_address = $this->getAddress();
		$input_address_model = Mint\Address::getAddress($input_address);

		Mint\Invoice::saveInvoice([
			'address_id'            => $input_address_model->id,
			'destination_address'   => $destination_address,
			'invoice_amount'        => $invoice_amount->satoshi,
			'label'                 => $label,
			'callback_url'          => $callback_url,
			'forward'               => $forward,
			'user_id'               => $this->user->id,
		]);

		return Response::json([
			'amount'		=> $invoice_amount->$currency,
			'currency'      => $currency,
			'forward'       => $forward,
			'destination'   => $destination_address,
			'input_address' => $input_address,
			'callback_url'  => $callback_url,
		]);
	}

    /**
     * Walletnotify callback
     *
     * @return Response
     */
	public function callback()
	{
		$api_key = Mint\Settings::getVal('api_key');

		if ( $api_key != Input::get('key') ) {
			throw new JsonException( trans('error.invalidkey') );
		}

		if( !$tx_id = Input::get('txid') ) {
			throw new JsonException( trans('error.notxidinput') );
		}

		$this->user = Mint\User::getRandomRPCUser();
		$this->bitcoin_core->setRpcConnection($this->user->rpc_connection);

		$tx_info = $this->bitcoin_core->gettransaction($tx_id);


		$confirms      = $tx_info['confirmations'];
		$block_hash    = isset( $tx_info['blockhash'] ) ? $tx_info['blockhash'] : null;
		$block_index   = isset( $tx_info['blockindex'] ) ? $tx_info['blockindex'] : null;
		$block_time    = isset( $tx_info['blocktime'] ) ? $tx_info['blocktime'] : null;
		$time          = $tx_info['time'];
		$time_received = $tx_info['timereceived'];
		$network_fee   = isset($tx_info['fee']) ? abs( Converter::btc($tx_info['fee'])->satoshi ) : null;
		$merchant_fee  = (float)Mint\Settings::getVal('merchant_fee');
		$merchant_fee  = Converter::btc($merchant_fee)->satoshi;

		$transaction_details = $tx_info["details"];

		/* Get input addresses */
		$addresses = array();
		$raw_tx = $this->bitcoin_core->getrawtransaction( $tx_id, 1 );

		foreach($raw_tx['vin'] as $i) {
			$i_raw_tx = $this->bitcoin_core->getrawtransaction( $i['txid'], 1 );
			$addresses[] = $i_raw_tx['vout'][$i['vout']]['scriptPubKey']['addresses'][0];
		}

		foreach ($transaction_details as $tx)
		{
			$account_name  = $tx['account'];
			$category      = $tx['category'];
			$amount        = Converter::btc( $tx["amount"] );
			$address_from  = ($amount->btc < 0) ? $tx['account'] : $addresses[0];
			$address_to    = $tx['address'];

			$address_model = Mint\Address::getAddress( ($amount->btc < 0) ? $address_from : $address_to );
			if( !isset($address_model->user->id) ) {
				Log::info('#callback: couldn\'t fetch user by address: ' . ($amount->btc < 0) ? $address_from : $address_to);
				continue; // loop more in case there is something
			}

			$user_id = $address_model->user->id;
			$this->user = Mint\User::find($user_id);

			$this->bitcoin_core->setRpcConnection($this->user->rpc_connection);

			Log::info( "Address $address_to, amount (BTC): " . $amount->btc . ", confirms: $confirms received transaction id $tx_id" );

			$transaction_model = Mint\Transaction::getTransactionByTxIdAndCategory( $tx_id, $category );

			$common_data = [
				'tx_id'             => $tx_id,
				'user_id'           => $this->user->id,
				'crypto_amount'     => $amount->satoshi,
				'network_fee'       => $network_fee,
				'merchant_fee'		=> $merchant_fee,
				'address_to'        => $address_to,
				'address_from'      => $address_from,
				'confirmations'     => $confirms,
				'block_hash'        => $block_hash,
				'block_index'       => $block_index,
				'block_time'        => $block_time,
				'tx_time'           => $time,
				'tx_timereceived'   => $time_received,
				'tx_category'       => $category,
				'address_account'   => $account_name,
			];

			$ret = ($category == 'send') ?
				$this->processOutgoingTransaction($transaction_model, $address_model, $common_data, $amount) :
				$this->processIncomingTransaction($transaction_model, $address_model, $common_data, $amount);
		}

		return '*ok*';
	}

    /**
     * Process transaction with negative amount
     *
     * @return Response
     */
	private function processOutgoingTransaction($transaction_model, $address_model, $common_data, Converter $amount)
	{
		if(!$transaction_model) {
			$address_model = Mint\Address::getAddress( $common_data['address_from'] );

			$real_amount = bcadd( abs( $amount->satoshi ), $common_data['network_fee'] );

			$balance_model = Mint\Balance::updateUserBalance($this->user, -$real_amount);
			$address_model = Mint\Address::updateAddressBalance($address_model, -$real_amount);

			$common_data['transaction_type'] = 'send';
			$common_data['address_balance']  = $address_model->balance;
			$common_data['user_balance']     = $balance_model->balance;
			$common_data['bitcoind_balance'] = $this->bitcoin_core->getbalance();

			$transaction_model = Mint\Transaction::insertNewTransaction($common_data);

			if( !empty($this->user->callback_url) ) {
				$this->fetchUrl($this->user->callback_url, $common_data, $transaction_model);
			}
		} else {
			die();
		}
	}

    /**
     * Process transaction with positive amount
     *
     * @return Response
     */
	private function processIncomingTransaction($transaction_model, $address_model, $common_data, $amount)
	{
		if (!$transaction_model) {
			$balance_model = Mint\Balance::updateUserBalance($this->user, $amount->satoshi);
			$address_model = Mint\Address::updateAddressBalance($address_model, $amount->satoshi);

			$common_data['transaction_type'] = 'receive';
			$common_data['address_balance']  = $address_model->balance;
			$common_data['user_balance']     = $balance_model->balance;
			$common_data['bitcoind_balance'] = $this->bitcoin_core->getbalance();

			$transaction_model = Mint\Transaction::insertNewTransaction($common_data);

			$callback_url = ( $invoice_model = Mint\Invoice::getAddress($address_model->address) ) ?
				$invoice_model->callback_url : $this->user->callback_url;

			if( !empty($callback_url) ) {
				$this->fetchUrl($callback_url, $common_data, $transaction_model);
			}
		} else {
			if ( $invoice_model = Mint\Invoice::getAddress($address_model->address) ) {
				Mint\Invoice::updateReceived($invoice_model, $amount->satoshi);

				if ($invoice_model->forward == 1) {
					$network_fee  = Converter::btc( Mint\Settings::getVal('tx_fee') );
					$merchant_fee = Converter::btc( Mint\Settings::getVal('merchant_fee') );
					$response = $this->sendFrom(
						$address_model->address,
						$invoice_model->destination_address,
						'invoice forward',
						$amount,
						$network_fee,
						$merchant_fee,
						'move'
					);
				}
			}
		}
	}

    /**
     * Blocknotify callback
     *
     * @return Response
     */
	public function blocknotify()
	{
		$key       = Input::get('key');
		$blockhash = Input::get('blockhash');

		if ($key != Mint\Settings::getVal('api_key')) {
			throw new JsonException( trans('error.invalidkey') );
		}

		// Get transactions with minimum amount of confirmations required for callback.
		$min_confirmations = Mint\Settings::getVal('min_confirmations');
		$transaction_collection = Mint\Transaction::getTransactionByMinimumConfirms($min_confirmations);

		$common_data = [];
		foreach($transaction_collection as $transaction_model) {
			$user_model = Mint\User::find($transaction_model->user->id);
			$this->bitcoin_core->setRpcConnection($user_model->rpc_connection);

			$tx_info = $this->bitcoin_core->gettransaction( $transaction_model['tx_id'] );

			$common_data['confirmations'] = $tx_info['confirmations'];
			$common_data['block_hash'] = isset( $tx_info['blockhash'] ) ? $tx_info['blockhash'] : null;
			$common_data['block_index'] = isset( $tx_info['blockindex'] ) ? $tx_info['blockindex'] : null;

			if( !$transaction_model['callback_status'] && $common_data['confirmations'] >= $min_confirmations ) {
				$common_data['crypto_amount']    = $transaction_model['crypto_amount'];
				$common_data['address_from']     = $transaction_model['address_from'];
				$common_data['address_to']       = $transaction_model['address_to'];
				$common_data['tx_id']            = $transaction_model['tx_id'];
				$common_data['network_fee']      = $transaction_model['network_fee'];
				$common_data['transaction_type'] = $transaction_model['transaction_type'];

				if( !empty($user_model->blocknotify_url) ) {
					$this->fetchUrl($user_model->blocknotify_url, $common_data, $transaction_model);
				}
			}

			Mint\Transaction::updateTxConfirmation($transaction_model, $common_data);
		}
		return '*ok*';
	}

    /**
     * Attempt to fetch user by Guid
     *
     * @return Mint\User
     */
	private function attemptAuth($guid)
	{
		$user = Mint\User::getUserByGuid($guid);

		// no user found
		if ( !$user )
		{
			throw new JsonException( trans('error.nouser') );
		}

		if ( $user->password != Input::get('password') )
		{
			throw new JsonException( trans('error.invalidpass') );
		}

		return $user;
	}

    /**
     * Fetch remote callback
     *
     * @return mixed
     */
	private function fetchURL($url, $common_data, $transaction_model)
	{
		$data = [
			'value'                  => $common_data['crypto_amount'],
			'fee'                    => $common_data['network_fee'],
			'address_from'           => $common_data['address_from'],
			'address_to'             => $common_data['address_to'],
			'confirmations'          => $common_data['confirmations'],
			'txid' 					 => $common_data['tx_id'],
			'type'                   => $common_data['transaction_type'],
			'secret'				 => Mint\Settings::getVal('api_secret'),
			'host'                   => gethostname(),
		];

		if( Mint\Settings::getVal('callback_method') == 'post' ) {
			$this->curl->post([ $url => $data ], [$this, 'processResponse'], $transaction_model, $common_data);
		} else {
			$query = $url . '?' . http_build_query($data);
			$this->curl->get($query, [$this, 'processResponse'], $transaction_model, $common_data);
		}
	}

    /**
     * Curl callback
     *
     * @return mixed
     */
	public function processResponse($result, $transaction_model, $common_data)
	{
		if($result->response[1] === CURLE_OK) {
			if ($result->info['http_code'] == 200) {
				$bom = pack('H*','EFBBBF');
				$app_response = preg_replace("/^$bom/", '', $result->body);
				$callback_status = ( $app_response == '*ok*' ) ? 1 : 0;

				if($transaction_model) {
					Mint\Transaction::updateTxConfirmation($transaction_model, $common_data);
					Mint\Transaction::updateTxOnAppResponse($transaction_model,
						$app_response,
						$result->info['url'],
						$callback_status
					);
				}
			}
		}
	}

    /**
     * Caculate sum of values
     *
     * @return mixed
     */
	private function bcsum(...$numbers)
	{
		$sum = '';
		foreach($numbers as $n) {
			$sum = bcadd($sum, $n, 8);
		}

		return $sum;
	}

    /**
     * Estimate fee.
     *
     * @return mixed
     */
	private function estimateFee($blocks = 1)
	{
		$tx_fee = $this->bitcoin_core->estimatefee($blocks);
		return ( $tx_fee == -1 ) ? false : $tx_fee;
	}

    /**
     * Send from address or address account
     *
     * @return mixed
     */
	private function sendFrom($from_address, $to_address, $note = '',  Converter $amount, Converter $network_fee, Converter $merchant_fee, $method = 'sendfrom')
	{
		$from_address_model = Mint\Address::getAddress($from_address);

		if($method == 'move') {
			if( !$this->isUserAddress($to_address) ) {
				$method = 'sendfrom';
			}
		}

		/* Check balance */
		if( $from_address_model->balance < $amount->satoshi) {
			throw new JsonException( trans('error.nofunds') );
		}

		/* Pay merchant fee if any */
		if($merchant_fee->satoshi > 0) {
			$fee_address = $this->getFeeAddress();

			$this->sendFrom(
				$from_address_model->address,
				$fee_address,
				'merchant fee',
				$merchant_fee,
				Converter::btc(0), /* network_fee  */
				Converter::btc(0), /* merchant_fee */
				'move'             /* method       */
			);
		}

		try {
			if($method == 'move') {
				$to_address_model = Mint\Address::getAddress($to_address);

				/* Move currency between user accounts. */
				$response = $this->bitcoin_core->move($from_address_model->address, $to_address_model->address, (float)$amount->btc, /* unused int */ 1, $note);
				if( !$response ) {
					throw new JsonException( trans('error.movefailed') );
				}

				/* Internal transaction. No txid returned and no network fee required. */
				$tx_id  = 0;
				$tx_fee = Converter::btc(0);

				$send_amount = Converter::satoshi( bcadd($amount->satoshi, $merchant_fee->satoshi) );

				DB::beginTransaction();

				/* Update every balance, because we won't be getting callback on move */
				Mint\Address::updateAddressBalance($from_address_model, -$send_amount->satoshi);
				$address_model = Mint\Address::updateAddressBalance($to_address_model, $send_amount->satoshi);

				switch(true) {
					case $to_address_model->address == $this->getFeeAddress():
						/* Update user balance if this transaction is fee */
						$user_balance = Mint\Balance::updateUserBalance($this->user, -$send_amount->satoshi);
						$types = ['send'];
						break;
					case $from_address_model->address == $this->getFeeAddress():
						/* Update user balance if this transaction is fee */
						$user_balance = Mint\Balance::updateUserBalance($this->user, $send_amount->satoshi);
						$types = ['receive'];
						break;
					default:
						$user_balance = Mint\Balance::getBalance($this->user->id);
						$types = ['send', 'receive'];
						break;
				}

				DB::commit();

				list($usecs, $secs) = explode( ' ', microtime() );
				$txid = $this->getInternalTxId($from_address_model->address, $to_address_model->address, $amount->satoshi, (double)$secs + (double)$usecs);

				foreach($types as $type) {
					$common_data = [
						'tx_id'            => $txid,
						'user_id'          => $this->user->id,
						'address_from'     => $from_address_model->address,
						'address_to'       => $to_address_model->address,
						'crypto_amount'    => ($type == 'send') ? -$amount->satoshi : $amount->satoshi,
						'confirmations'    => Mint\Settings::getVal('min_confirmations'),
						'network_fee'      => 0,
						'merchant_fee'     => $merchant_fee->satoshi,
						'tx_time'          => (int)$secs,
						'tx_timereceived'  => (int)$secs,
						'tx_category'      => $type,
						'user_balance'     => $user_balance->balance,
						'address_balance'  => $address_model->balance,
						'bitcoind_balance' => $this->bitcoin_core->getbalance(),
						'note'             => $note,
						'transaction_type' => 'internal-' . $type,
					];

					$transaction_model = Mint\Transaction::insertNewTransaction($common_data);
					if( !empty($this->user->callback_url) ) {
						$this->fetchUrl($this->user->callback_url, $common_data, $transaction_model);
					}
				}
			} else {
				/* Set transaction fee and deduct it from the payment amount */
				$this->bitcoin_core->settxfee( (float)$network_fee->btc );
				$total_amount = Converter::btc( bcsub($amount->btc, $network_fee->btc, 8) );

				$tx_id = $this->bitcoin_core->sendfrom($from_address, $to_address, (float)$amount->btc, /* confirmations */ 1, $note);
				$tx_info = $this->bitcoin_core->gettransaction($tx_id);
				$tx_fee = Converter::btc( abs( $tx_info['fee'] ) );
			}
		} catch(JsonException $e) {
			/* Refund merchant fee if payment fails */
			$this->sendFrom(
				$fee_address,
				$from_address_model->address,
				'merchant fee refund',
				$merchant_fee,
				Converter::btc(0), /* network_fee  */
				Converter::btc(0), /* merchant_fee */
				'move'	       	   /* method       */
			);

			throw new JsonException( $e->getMessage() );
		}

		return [
			'tx_id'        => $tx_id,
			'tx_fee'       => $tx_fee,
			'merchant_fee' => $merchant_fee,
			'amount'       => $amount,
			'from_address' => $from_address,
			'to_address'   => $to_address,
			'is_internal'  => ($tx_id === 0),
		];
	}

	/**
     * Create new internal txid(starts with "-") based on two addresses time and sum.
     *
     * @return string
     */
	private function getInternalTxId($address_to, $address_from, $amount, $microtime) {
		$ns = "$address_to.$address_from.$amount.$microtime";

		$uuid = Uuid::generate(5, $ns, Uuid::NS_DNS);
		$txid = (string)hash('sha256', $uuid);

		/* Replace first character of string with "-", to mark this transaction as internal */
		$txid[0] = '-';

		return $txid;
	}


	/**
     * Create new address and assign it to account with the same name
     *
     * @return string
     */
	private function getAddress($label = '')
	{
		$address = $this->bitcoin_core->getnewaddress();
		/* Assign address to the account with the same name. */
		$this->bitcoin_core->setaccount($address, $address);

		Mint\Address::insertNewAddress([
				'user_id' => $this->user->id,
				'address' => $address,
				'label'   => $label,
		]);

		return $address;
	}

	/**
     * Get merchant fee address for user or create new address and assign it to account
     *
     * @return string
     */
	private function getFeeAddress()
	{
		if( empty($this->user->fee_address) ) {
			$this->user = Mint\User::setFeeAddress( $this->user, /* Generate new fee address */ $this->getAddress('fee address') );
		}

		return $this->user->fee_address;
	}

    /**
     * Check if given address is valid bitcoin address
     *
     * @return bool
     */
	private function isValidAddress($address)
	{
		$address_valid = $this->bitcoin_core->validateaddress($address);
		return (bool)$address_valid['isvalid'];
	}

    /**
     * Check if given address is user address.
     *
     * @return bool
     */
	private function isUserAddress($address)
	{
		return ( isset(Mint\Address::getAddress($address)->user->id) && Mint\Address::getAddress($address)->user->id === $this->user->id );
	}
}
