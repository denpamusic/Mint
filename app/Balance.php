<?php

namespace Mint;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $table = 'balances';
	protected $fillable = ['user_id', 'balance', 'total_received', 'num_transactions'];

	public static function getBalance($user_id)
	{
		return Balance::where('user_id', $user_id)->lockForUpdate()->first();
	}

	public static function insertNewBalance($data) {
		return self::create($data);
	}

	public static function updateUserBalance($user_model, $amount)
	{
		$balance = Balance::getBalance($user_model->id);

		if($amount > 0) {
			$balance->total_received = bcadd($balance->balance, $amount);
		}
		$balance->balance = bcadd($balance->balance, $amount);
		$balance->num_transactions = bcadd($balance->num_transactions, 1);
		$balance->save();
		return $balance;
	}

	public function user()
	{
		return $this->belongsTo('Mint\User');
	}
}
