<?php

namespace PrestoPlayer\Pro\Models\Bunny;

use PrestoPlayer\Pro\Models\Bunny\Support\ZoneModel;

class PullZone extends ZoneModel {

	/**
	 * Option name
	 *
	 * @var string
	 */
	protected $option_name = 'bunny_pull_zones';

	/**
	 * Fillable field schema
	 *
	 * @var array
	 */
	protected $fillable = array(
		// public
		'public_id'            => array(
			'type' => 'integer',
		),
		'public_name'          => array(
			'type' => 'string',
		),
		'public_hostname'      => array(
			'type' => 'string',
		),

		// private
		'private_id'           => array(
			'type' => 'integer',
		),
		'private_name'         => array(
			'type' => 'string',
		),
		'private_hostname'     => array(
			'type' => 'string',
		),
		'private_security_key' => array(
			'type' => 'string',
		),
	);
}
