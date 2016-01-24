<?php

namespace Mint;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
	protected $fillable = ['guid', 'email', 'name', 'callback_url', 'blocknotify_url', 'rpc_connection'];
	protected $hidden = ['password', 'secret'];

	public static function getUserByGuid($guid)
	{
		return self::where('guid', $guid)->first();
	}

	public static function getRandomRPCUser()
	{
		return self::whereNotNull('rpc_connection')->orderByRaw('RAND()')->first();
	}

	public static function setFeeAddress($user_model, $address) {
		$user_model->fee_address = $address;
		$user_model->save();
		return $user_model;
	}

	public static function insertNewUser($user_data)
	{
		return self::create($user_data);
	}

	public function addresses()
	{
		return $this->hasMany('Mint\Address');
	}

	public function invoices()
	{
		return $this->hasMany('Mint\Invoice');
	}

	public function transactions()
	{
		return $this->hasMany('Mint\Transaction');
	}
}
