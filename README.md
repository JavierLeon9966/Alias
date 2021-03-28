# Alias
Detects players possible alternatives accounts

## Overview
This plugin stores players `Address`, `ClientRandomId`, `DeviceId`, `SelfSignedId`, and `XUID`.
Which will detect players accurately and hard for hackers to ban evade.

Also it has a command and alert system to staff members.

## Configuration
In the configuration you can modify the `mode`, `data` and `alert`.

Here's a example of the default configuration:
```yaml
alert: false #Everyone that has alias.alerts permission
ban: 'You are banned' #Ban message
mode: none #Options: none, ban
data: ['Address', 'ClientRandomId', 'DeviceId', 'SelfSignedId', 'XUID'] #Check for matching data in which will alert the staff members or ban the player
```

## Command
`/alias <name: target>`

Only users with `alias.command.alias` permission can execute this command.

Returns a list of possible players matching `IP`, `ClientRandomId`, `DeviceId`, `SelfSignedId`, and `XUID`.

## API
You can use this plugin API by the following:
```php
use JavierLeon9966\Alias\Alias;
$aliases = Alias::getInstance()->getAliases($player->getName()); //returns a array of detected players
```

## Database
In the data folder of this plugin there is a file called `players.json` which there's stored all the players that have logged into the server.
