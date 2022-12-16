<?php

namespace JavierLeon9966\Alias\config;

use libMarshal\attributes\Field;
use libMarshal\MarshalTrait;

class SQLiteConfig
{
	use MarshalTrait;

	#[Field]
	public string $file = 'players.sqlite';
}