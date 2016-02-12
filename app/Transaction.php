<?php

namespace Mint;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
	protected $table = 'transactions';

	protected $fillable = [
		'tx_id', 'user_id', 'crypto_type_id', 'address_to', 'address_from', 'crypto_amount', 'confirmations', 'response_callback',
		'block_hash', 'block_index', 'block_time', 'tx_time', 'tx_timereceived', 'tx_category', 'balance', 'bitcoind_balance',
		'note', 'transaction_type', 'user_balance', 'network_fee'
	];

	public static function getTransactionByTxId($txId) {
		return self::where('tx_id', $txId)->lockForUpdate()->first();
	}

	public static function getTransactionByTxIdAndAddress($txId, $address) {
		return self::where('tx_id', $txId)->where('address_to', $address)->lockForUpdate()->first();
	}

	public static function getTransactionByMinimumConfirms($min_confirmations) {
		return self::where('confirmations', '<', $min_confirmations)->get();
	}

	public static function getTransactionsByAddressAndConfirms($address, $confirms) {
		return self::where(function ($query) use ($address) {
				$query->where('address_to', $address)->orWhere('address_from', $address);
		})->where('confirmations', '>=', $confirms)->get();
	}

	public static function updateTxConfirmation($transactionModel, $data)
	{
		$transactionModel->confirmations = isset($data['confirmations']) ? $data['confirmations'] : 0;
		$transactionModel->block_hash = isset($data['block_hash']) ? $data['block_hash'] : null;
		$transactionModel->block_index = isset($data['block_index']) ? $data['block_hash'] : null;
		$transactionModel->save();
		return $transactionModel;
	}

	public static function insertNewTransaction($data) {
		return self::create($data);
	}

	public static function updateTxOnAppResponse( $transaction_model, $app_response, $full_callback_url, $callback_status, $external_user_id = null ) {
		$transaction_model->response_callback = $app_response;
		$transaction_model->callback_url = $full_callback_url;
		$transaction_model->callback_status = $callback_status;
		$transaction_model->save();
		return $transaction_model;
	}

	public function user()
	{
		return $this->belongsTo('Mint\User');
	}
}
