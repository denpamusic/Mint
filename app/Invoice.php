<?php

namespace Mint;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
	protected $fillable = ['user_id', 'address_id', 'destination_address', 'forward', 'label', 'invoice_amount', 'received_amount', 'received', 'callback_url'];

	public static function getAddress($address) {
		return self::whereHas('address', function ($query) use ($address) {
			$query->where('address', $address);
		})->first();
	}
	
	public static function getUnpaidInvoices() {
		return self::with('address')->where('received', 0)->get();
	}

	public static function saveInvoice($forward_data) {
		return self::create($forward_data);
	}

	public static function updateReceived($invoice_model, $amount) {
		$invoice_model->received_amount = bcadd($invoice_model->received_amount, $amount);

		if ($invoice_model->invoice_amount == $invoice_model->received_amount) {
			$invoice_model->received = 1;
		}
		$invoice_model->save();
	}

	public function user()
	{
		return $this->belongsTo('Mint\User');
	}

	public function address()
	{
		return $this->belongsTo('Mint\Address');
	}
}
