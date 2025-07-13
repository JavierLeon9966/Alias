<?php

declare(strict_types=1);

namespace JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\SOFe\PmEvent;

use Closure;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\SOFe\AwaitGenerator\Await;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\SOFe\AwaitGenerator\Channel;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\SOFe\AwaitGenerator\Traverser;

final class Util {
	/**
	 * @template T
	 * @param Channel<T>[] $channels
	 * @param ?Closure(): void $finalize
	 * @return Traverser<T>
	 */
	public static function traverseChannels(array $channels, ?Closure $finalize = null) : Traverser {
		return Traverser::fromClosure(function() use ($channels, $finalize) {
			try {
				while (true) {
					[, $value] = yield from Await::safeRace(array_map(fn(Channel $channel) => $channel->receive(), $channels));
					yield $value => Traverser::VALUE;
				}
			} finally {
				if($finalize !== null) {
					$finalize();
				}
			}
		});
	}
}