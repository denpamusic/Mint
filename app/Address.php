<?php

namespace Mint;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'addresses';
	protected $fillable = ['user_id', 'address', 'label', 'balance', 'total_received'];

	public static function getAddress($address)
	{
		return self::where('address', $address)->first();
	}

	public static function insertNewAddress($address_data)
	{
		return self::create($address_data);
	}

	public static function updateAddressBalance($address_model, $amount)
	{
		if($amount > 0) {
			$address_model->total_received = bcadd($address_model->total_received, $amount);
		}
		$address_model->balance = bcadd($address_model->balance, $amount);
		$address_model->num_transactions = bcadd($address_model->num_transactions, 1);
		$address_model->save();
		return $address_model;
	}

	public function user()
	{
		return $this->belongsTo('Mint\User');
	}

	public function invoice()
	{
		return $this->hasOne('Mint\Invoice');
	}
}
