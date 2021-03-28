<?php
namespace JavierLeon9966\Alias\command;
use pocketmine\command\{Command, CommandSender, PluginIdentifiableCommand};
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\Plugin;
use JavierLeon9966\Alias\Alias;
class AliasCommand extends Command implements PluginIdentifiableCommand{
	private $plugin;
	public function __construct(Alias $plugin){
		$this->plugin = $plugin;
		parent::__construct(
			'alias',
			'Lists a player\'s possible accounts.',
			'/alias <name: target>'
		);
		$this->setPermission('alias.command.alias');
	}
	public function getPlugin(): Plugin{
		return $this->plugin;
	}
	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}
		if(count($args) == 0){
			throw new InvalidCommandSyntaxException;
		}
		$player = $sender->getServer()->getPlayer($args[0]);
		if($player === null){
			$player = $sender->getServer()->getOfflinePlayer($args[0]);
			if(!$player->hasPlayedBefore()){
				$sender->sendMessage(new TranslationContainer(TextFormat::RED.'%commands.generic.player.notFound'));
				return true;
			}
		}
		$possiblePlayers = $this->getPlugin()->getAliases($player->getName());
		$sender->sendMessage(TextFormat::YELLOW.'[Alias] '.TextFormat::RESET."'{$player->getName()}' possible accounts:");
		foreach(['Address', 'ClientRandomId', 'DeviceId', 'SelfSignedId', 'XUID'] as $key){
			$sender->sendMessage("$key: ".implode(', ', $possiblePlayers[$key] ?? ['None']));
		}
		return true;
	}
}