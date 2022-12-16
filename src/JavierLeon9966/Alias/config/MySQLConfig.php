<?php

namespace JavierLeon9966\Alias\config;

use libMarshal\attributes\Field;
use libMarshal\MarshalTrait;

class MySQLConfig
{
	use MarshalTrait;

	#[Field]
	public string $host = '127.0.0.1';
	#[Field]
	public string $username = 'Alias';
	#[Field]
	public string $password = 'mypassword123';
	#[Field]
	public string $schema = 'Alias';
	#[Field]
	public int $port = 3306;
}
