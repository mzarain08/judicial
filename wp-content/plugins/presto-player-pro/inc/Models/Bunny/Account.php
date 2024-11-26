<?php

namespace PrestoPlayer\Pro\Models\Bunny;

use PrestoPlayer\Pro\Models\Bunny\Support\SettingModel;

class Account extends SettingModel {

	/**
	 * Option name
	 *
	 * @var string
	 */
	protected $option_name = 'bunny_keys';

	/**
	 * Fillable field schema
	 *
	 * @var array
	 */
	protected $fillable = array(
		'api_key'                       => array(
			'type' => 'string',
		),
		'public_storage_zone_password'  => array(
			'type' => 'string',
		),
		'private_storage_zone_password' => array(
			'type' => 'string',
		),
	);
}
