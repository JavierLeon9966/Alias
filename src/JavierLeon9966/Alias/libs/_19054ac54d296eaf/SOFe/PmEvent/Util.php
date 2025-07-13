<?php

declare(strict_types=1);

namespace JavierLeon9966\Alias\libs\_19054ac54d296eaf\SOFe\PmEvent;

use Closure;
use JavierLeon9966\Alias\libs\_19054ac54d296eaf\SOFe\AwaitGenerator\Await;
use JavierLeon9966\Alias\libs\_19054ac54d296eaf\SOFe\AwaitGenerator\Channel;
use JavierLeon9966\Alias\libs\_19054ac54d296eaf\SOFe\AwaitGenerator\Traverser;

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