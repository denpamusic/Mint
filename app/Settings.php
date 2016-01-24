<?php

namespace Mint;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table = 'settings';
	protected $fillable = ['key', 'value'];

	public static function getVal($key)
	{
		if ( $object = self::getSetting($key) ) {
			return ( !isset($object->value) || empty($object->value) ) ? $object->def : $object->value;
		}
		return null;
	}

	public static function updateVal($key, $value)
	{
		return self::where('key', $key)->update( ['value' => $value] );
	}

	public static function getSetting($key)
	{
		return self::where('key', $key)->first();
	}

	public static function createSetting($key_name, $value)
	{
		return self::create([
			'key'   => $key_name,
			'value' => $value,
		]);
	}
}
