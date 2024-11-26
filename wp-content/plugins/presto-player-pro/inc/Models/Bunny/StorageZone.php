<?php

namespace PrestoPlayer\Pro\Models\Bunny;

use PrestoPlayer\Pro\Models\Bunny\Support\ZoneModel;

class StorageZone extends ZoneModel {

	/**
	 * Option name
	 *
	 * @var string
	 */
	protected $option_name = 'bunny_storage_zones';

	/**
	 * Fillable field schema
	 *
	 * @var array
	 */
	protected $fillable = array(
		// public
		'public_id'    => array(
			'type' => 'integer',
		),
		'public_name'  => array(
			'type' => 'string',
		),

		// private
		'private_id'   => array(
			'type' => 'integer',
		),
		'private_name' => array(
			'type' => 'string',
		),
	);
}
