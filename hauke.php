<?php
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors',1);

require_once __DIR__ .'/assets/conf.php';

include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/class.discord-helper.php';

use Discord\DiscordCommandClient;
use Discord\Parts\User\Activity;
use Discord\WebSockets\Event;
use RestCord\DiscordClient;
use HaukesHausgeist\HaukeDiscordHelper;

$discord = new DiscordCommandClient([
	'token' => DISCORD_TOKEN_HAUGE,
	'discordOptions' => [
		'loadAllMembers' => true,
		'disabledEvents' => [
			//'TYPING_START',
			'PRESENCE_UPDATE',
			//'VOICE_STATE_UPDATE',
			//'VOICE_SERVER_UPDATE',
			//'GUILD_CREATE',
			//'GUILD_DELETE',
			//'GUILD_UPDATE',
			//'GUILD_BAN_ADD',
			//'GUILD_BAN_REMOVE',
			//'GUILD_ROLE_CREATE',
			//'GUILD_ROLE_DELETE',
			//'GUILD_ROLE_UPDATE',
			//'CHANNEL_CREATE',
			//'CHANNEL_UPDATE',
			//'CHANNEL_DELETE'
		]
	],
	'prefix' => '!',
	'name' => 'HausGeist',
	'description' => 'Dieser Geist spukt hier ein bisschen rum'
]);

$restcord = new DiscordClient([
	'token' => DISCORD_TOKEN_HAUGE
]);

$botId = $restcord->user->getCurrentUser()->id;

$discordHelper = new HaukeDiscordHelper([
	'client'	=> $restcord,
	'botId'		=> $botId,
	'serverIds'	=> [
		454336351509282827
	]
]);

$serverId = $discordHelper->serverId;


$discord->on('ready', function ($discord) use ($botId, $discordHelper) {

	echo "HaukesHausGeist is ready." . PHP_EOL;
	mail('info@troopermaxx.de', ' HaukesHausGeist gestartet', 'HaukesHausGeist gestartet');

	$guild_hauke = $discord->guilds->get('id', '454336351509282827');//Get Haukes's Server
	$channel_voice_n_chad = $guild_hauke->channels->get('id', 729487121970233355);// Get #chad_n_voice Channel
    $channel_bot_pn = $guild_hauke->channels->get('id', 729248550969671694);// Get #bot_only Channel
    $channel_id_bot = '731486968906514523';
	$channel_rechte_id = '740968819140591637';


	$discord->on(Event::GUILD_MEMBER_ADD, function ($member) {
		try {
			$member->user->sendMessage(BEGRUESSUNG);
		} catch(Exception $e) {
			echo $e->getMessage();
		}
		echo $member->user->username . ' ist dem Server beigetreten!'.PHP_EOL;

	});

	$discord->on('message', function ($onmessage) use ($channel_bot_pn, $discordHelper, $botId) {
		if( !is_object($onmessage) ) {
			return;
		}

		if( $onmessage->author->id == $botId ) {
			return;
		}


        /***************************************************************************************************
         ***************BOT PMs werden als Nachricht in deinen Mod-Channel geschreiben**********************
         ***************************************************************************************************/
		if($onmessage->channel->is_private && $onmessage->author->id !='731443851604131870'){
			$channel_bot_pn->sendMessage('PN von <@'.$onmessage->author->id . '> mit dem Inhalt: '.PHP_EOL.'>>> '. $onmessage->content);

			echo 'nachricht privat erhalten von <@'.$onmessage->author->username . '> mit dem Inhalt: "'. $onmessage->content .'"'. PHP_EOL;
		}

		/** Replies Planner **/
		$repliesbybotFile = 'https://haukedashboard.troopermaxx.de/replies-hauke.txt';
		$repliesbybotData = json_decode(file_get_contents($repliesbybotFile), true);

		$skipped = false;
		$found = false;

		if( count($repliesbybotData) > 0 ) {
			foreach( $repliesbybotData as $replybybot ) {
				$botMention = $botMentioned = false;
				if( $replybybot['botmention'] == 'mention' ) {
					$botMention = true;
					if( $onmessage->mentions->has($botId) ) $botMentioned = true;
				}

				if( $discordHelper->checkTrigger($replybybot['trigger'], $onmessage->content ) ) {
					if( $botMention && !$botMentioned ) continue;

					$randomMsgs = explode('||', $replybybot['replies']);
					$randomNumberDiscordBot = array_rand($randomMsgs);

					$postContent = $discordHelper->replaceEmotes($randomMsgs[$randomNumberDiscordBot]);
					$postContent = $discordHelper->replaceChannels($postContent);
					$postContent = trim($postContent);

					if( $replybybot['type'] == 'reply' ) {
						$onmessage->reply($postContent);
					} elseif( $replybybot['type'] == 'message' ) {
						$onmessage->channel->sendMessage($postContent);
					}
					$found = true;

					if( $replybybot['skip'] == 'skip' ) {
						$skipped = true;
						break;
					}
				}
			}
		}

		if( $found ) {
			return;
		}

		if( $skipped ) {
			return;
		}

		if( $onmessage->mentions->has($botId) ) {
			$replymsgs = $discordHelper->getServerEmotesByNames( ['hauge'] );
			
			srand((double)microtime() * 1000000);
			$randomNumber = array_rand($replymsgs);
			$onmessage->reply($replymsgs[$randomNumber]);
			return;
		}
	});


    /***************************************************************************************************
     ***************Command zum Berechnen der Qualität eines Witzes*************************************
     ***************************************************************************************************/
	$discord->registerCommand('humor', function ($message, $params) use ($channel_id_bot) {
		// Do some processing etc.
		if($message->channel_id =='729487121970233355' ||$message->channel_id =='454336351509282829' || $message->channel_id =='454337859940515880') {
			$mentions = $message->mentions;

			$responses = ['Kritischer Erfolg! <:HaugeLaser:541569422071169039> HAMMER Witz!!!', 'Kritischer Misserfolg! 😫 Richtig schlechter Witz!!!', 'joa war ok <:HaugeKappa:457515547987410945>', 'NUN <:HaugeTears:522501285296078849> '];
			echo array_rand($responses, 1);
			echo $responses[array_rand($responses, 1)];
			foreach ($mentions as &$user) {
				$message->channel->sendMessage('<@' . $user->id . '> , ' . $responses[array_rand($responses, 1)]);
			}
		}

	});

    /***************************************************************************************************
     ************************Rollen per Reaction auf Post zuweisen**************************************
     ***************************************************************************************************/
	$discord->on(Event::MESSAGE_REACTION_ADD, function ($message, $discord) use ($channel_rechte_id) {
		if($message->channel_id ==$channel_rechte_id){


			$guild = $discord->guilds->get('id', $message->guild_id);
			$member = $discord->guilds->get('id', $message->guild_id)->members[$message->user_id];

            /**NEWS-Rolle**/
			if( $message->emoji->name == 'HaugeAgree' ) {
				$roleReaction = $guild->roles->get('id', 740957674887184475);
				if( $roleReaction != null ) {
					if( $member->addRole($roleReaction) ) {
						$guild->members->save($member)->then(function() use($message, $guild, $roleReaction, $member) {
							// Lääääääuft
							echo $member->username .'hat sich die Rolle ' . $roleReaction->name . ' gegeben' .PHP_EOL;
						}, function($e) {
							// Fehler
							echo 'FEHLER! ' . $e.PHP_EOL;
						});
					} else {
						echo $member->username .'hat Rolle ' . $roleReaction->name .' schon!'.PHP_EOL;
					}
				}
			}
            /**AmongUs-Rolle**/
            if( $message->emoji->name == 'amongushauge' ) {
                $roleReaction = $guild->roles->get('id', 804280194810249216);//AmongUs Rolle
                if( $roleReaction != null ) {
                    if( $member->addRole($roleReaction) ) {
                        $guild->members->save($member)->then(function() use($message, $guild, $roleReaction, $member) {
                            // Lääääääuft
                            echo $member->username .'hat sich die Rolle ' . $roleReaction->name . ' gegeben' .PHP_EOL;
                        }, function($e) {
                            // Fehler
                            echo 'FEHLER! ' . $e.PHP_EOL;
                        });
                    } else {
                        echo $member->username .'hat Rolle ' . $roleReaction->name .' schon!'.PHP_EOL;
                    }
                }
            }
            /**PnP-Rolle**/
            if( $message->emoji->name == 'dice' ) {
                $roleReaction = $guild->roles->get('id', 819474507872862239);//PnP Rolle
                if( $roleReaction != null ) {
                    if( $member->addRole($roleReaction) ) {
                        $guild->members->save($member)->then(function() use($message, $guild, $roleReaction, $member) {
                            // Lääääääuft
                            echo $member->username .'hat sich die Rolle ' . $roleReaction->name . ' gegeben' .PHP_EOL;
                        }, function($e) {
                            // Fehler
                            echo 'FEHLER! ' . $e.PHP_EOL;
                        });
                    } else {
                        echo $member->username .'hat Rolle ' . $roleReaction->name .' schon!'.PHP_EOL;
                    }
                }
            }
		}
	});

    /***************************************************************************************************
     **************************Rollen per Reaction auf Post entfernen***********************************
     ***************************************************************************************************/
	$discord->on(Event::MESSAGE_REACTION_REMOVE, function ($message, $discord) use ($channel_rechte_id) {
		if($message->channel_id ==$channel_rechte_id){


			$guild = $discord->guilds->get('id', $message->guild_id);
			$member = $discord->guilds->get('id', $message->guild_id)->members[$message->user_id];
            /**NEWS-Rolle**/
			if( $message->emoji->name == 'HaugeAgree' ) {

				$roleReaction = $guild->roles->get('id', 740957674887184475);
				if( $roleReaction != null ) {

					if( $member->removeRole($roleReaction) ) {

						$guild->members->save($member)->then(function() use($message, $guild, $roleReaction, $member) {
							// Lääääääuft
							echo $member->username .'hat sich die Rolle ' . $roleReaction->name . ' weggenommen' .PHP_EOL;
						}, function($e) {
							// Fehler
							echo 'FEHLER! ' . $e.PHP_EOL;
						});
					} else {
						echo $member->username .'hatte Rolle ' . $roleReaction->name .' nicht!'.PHP_EOL;
					}
				}
			}
            /**AmongUs-Rolle**/
            if( $message->emoji->name == 'amongushauge' ) {

                $roleReaction = $guild->roles->get('id', 804280194810249216);
                if( $roleReaction != null ) {

                    if( $member->removeRole($roleReaction) ) {

                        $guild->members->save($member)->then(function() use($message, $guild, $roleReaction, $member) {
                            // Lääääääuft
                            echo $member->username .'hat sich die Rolle ' . $roleReaction->name . ' weggenommen' .PHP_EOL;
                        }, function($e) {
                            // Fehler
                            echo 'FEHLER! ' . $e.PHP_EOL;
                        });
                    } else {
                        echo $member->username .'hatte Rolle ' . $roleReaction->name .' nicht!'.PHP_EOL;
                    }
                }
            }
            /**PnP-Rolle**/
            if( $message->emoji->name == 'dice' ) {

                $roleReaction = $guild->roles->get('id', 819474507872862239);
                if( $roleReaction != null ) {

                    if( $member->removeRole($roleReaction) ) {

                        $guild->members->save($member)->then(function() use($message, $guild, $roleReaction, $member) {
                            // Lääääääuft
                            echo $member->username .'hat sich die Rolle ' . $roleReaction->name . ' weggenommen' .PHP_EOL;
                        }, function($e) {
                            // Fehler
                            echo 'FEHLER! ' . $e.PHP_EOL;
                        });
                    } else {
                        echo $member->username .'hatte Rolle ' . $roleReaction->name .' nicht!'.PHP_EOL;
                    }
                }
            }
		}
	});

    /*******************************************UNGENUTZT***********************************************
     ***************Command zum löschen der letzten 100 Nachriten im #chad-n-voice**********************
     ***************************************************************************************************/
    /*
    $discord->registerCommand('clear', function ($message, $params)use($channel_voice_n_chad,$channel_id_bot) {
    // Do some processing etc.
    if($message->channel_id ==$channel_id_bot){


    // $options['after']=$erste_message;
    $options['before']=$message;
    $options['limit']=100;
    $channel_voice_n_chad->getMessageHistory($options)->then(function($hist)use ($channel_voice_n_chad){$channel_voice_n_chad->deleteMessages($hist)->then(function($loesch)use ($channel_voice_n_chad){var_dump($loesch);});});

    }
    });
     */
    /*******************************************UNGENUTZT***********************************************
     ***************Command gibt aktuelle Tokyoer Zeit zurück*******************************************
     ***************************************************************************************************/
    /*    $discord->registerCommand('time', function ($message, $params) {
        // Do some processing etc.
        $date = new DateTime("now", new DateTimeZone('Asia/Tokyo') );
        $uhrzeit=$date->format('H:i');
        $reply = 'Bei Hauke ist es grade '. $uhrzeit . ' Uhr.';


        return $reply;
    });*/

});


$discord->run();

