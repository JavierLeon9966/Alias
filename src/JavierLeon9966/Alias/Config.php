<?php

namespace JavierLeon9966\Alias;

use JavierLeon9966\Alias\config\DatabaseConfig;
use libMarshal\attributes\Field;
use libMarshal\MarshalTrait;

class Config{
	use MarshalTrait;

	#[Field]
	public DatabaseConfig $database;
	#[Field]
	public bool $alert = false;
	#[Field]
	public string $ban = "You are banned";
	#[Field]
	public string $mode = "none";
	/** @var string[] $data */
	#[Field]
	public array $data = ['Address', 'ClientRandomId', 'DeviceId', 'SelfSignedId', 'XUID'];
}
