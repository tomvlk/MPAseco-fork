<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Chat plugin.
 * Provides private messages and a wide variety of shout-outs.
 * Updated by Xymph
 * updated by kremsy
 * Dependencies: requires chat.admin.php
 */

Aseco::addChatCommand('pm', 'Sends a private message to login or Player_ID');
Aseco::addChatCommand('pma', 'Sends a private message to player & admins');
Aseco::addChatCommand('pmlog', 'Displays log of your recent private messages');
Aseco::addChatCommand('pmr', 'Response to a private message.');
Aseco::addChatCommand('hi', 'Sends a Hi message to everyone');
Aseco::addChatCommand('bye', 'Sends a Bye message to everyone');
Aseco::addChatCommand('bb', 'Sends a Bye message to everyone');
Aseco::addChatCommand('thx', 'Sends a Thanks message to everyone');
Aseco::addChatCommand('lol', 'Sends a Lol message to everyone');
Aseco::addChatCommand('lool', 'Sends a Lool message to everyone');
Aseco::addChatCommand('brb', 'Sends a Be Right Back message to everyone');
Aseco::addChatCommand('afk', 'Sends an Away From Keyboard message to everyone');
Aseco::addChatCommand('gg', 'Sends a Good Game message to everyone');
Aseco::addChatCommand('gl', 'Sends a Good Luck message to everyone');
Aseco::addChatCommand('hf', 'Sends a Have fun message to everyone');
Aseco::addChatCommand('glhf', 'Sends a Good Luck and Have Fun message to everyone');
Aseco::addChatCommand('n1', 'Sends a Nice One message to everyone');
Aseco::addChatCommand('ns', 'Sends a Nice Shot message to everyone');
Aseco::addChatCommand('bgm', 'Sends a Bad Game message to everyone');
Aseco::addChatCommand('official', 'Shows a helpful message ;-)');
Aseco::addChatCommand('bootme', 'Boot yourself from the server');
Aseco::addChatCommand('ragequit', 'Make a ragequit from the server');

// first Param $aseco, second Param string who should looked in lj=last joined
function isinplayerlist($aseco,$login){
    $pid=1;
    foreach ($aseco->server->players->player_list as $pl) {   
      $nickname=str_ireplace('$w', '', $pl->nickname);    
      if($login==$pl->login || $login==$pid || $login==$pl->nickname)
        return $nickname.'$z';
      $pid++;                            
      } 
      if($login=='lj')
          return $nickname.'$z';     
      return $login;
}

function chat_pm($aseco, $command) {

   
  global $muting_available,  // from plugin.muting.php
         $pmlen;  // from chat.admin.php

  $command['params'] = explode(' ', $command['params'], 2);

  $player = $command['author'];
  $target = $player;

  // get player login or ID
  if (!$target = $aseco->getPlayerParam($player, $command['params'][0]))
    return;

  // check for a message
  if (isset($command['params'][1]) && $command['params'][1] != '') {
    $stamp = date('H:i:s');
    // strip wide fonts from nicks
    $plnick = str_ireplace('$w', '', $player->nickname);
    $tgnick = str_ireplace('$w', '', $target->nickname);

    // drop oldest pm line if sender's buffer full
    if (count($player->pmbuf) >= $pmlen) {
      array_shift($player->pmbuf);
    }
    // append timestamp, sender nickname, pm line and login to sender's history
    $target->pmbuf[] = array($stamp, $plnick, $command['params'][1], $player->login);

    // drop oldest pm line if receiver's buffer full
    if (count($target->pmbuf) >= $pmlen) {
      array_shift($target->pmbuf);
    }
    // append timestamp, sender nickname, pm line and login to sender's history
    $target->pmbuf[] = array($stamp, $plnick, $command['params'][1], $player->login);

    // show chat message to both players
    $msg = '{#error}-pm-$g[' . $plnick . '$z$s$i->' . $tgnick . '$z$s$i]$i {#interact}' . $command['params'][1];
    $msg = $aseco->formatColors($msg);
    $infomsg =  '$i {#highlite} Type /pmr <message> to respond.';
    $infomsg = $aseco->formatColors($infomsg);
    
    $aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $target->login));
    $aseco->client->addCall('ChatSendServerMessageToLogin', array($infomsg, $target->login));
    $aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $player->login));
    if (!$aseco->client->multiquery()) {
      trigger_error('[' . $aseco->client->getErrorCode() . '] ChatSend PM (multi) - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
    }

    // check if player muting is enabled
    if ($muting_available) {
      // append pm line to both players' buffers
      if (count($target->mutebuf) >= 28) {  // chat window length
        array_shift($target->mutebuf);
      }
      $target->mutebuf[] = $msg;
      if (count($player->mutebuf) >= 28) {  // chat window length
        array_shift($player->mutebuf);
      }
      $player->mutebuf[] = $msg;
    }

  } else {
    $msg = '{#server}> {#error}No message!';
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $player->login);
  }
}  // chat_pm

function chat_pma($aseco, $command) {
  global $muting_available,  // from plugin.muting.php
         $pmlen;  // from chat.admin.php

  $command['params'] = explode(' ', $command['params'], 2);

  $player = $command['author'];
  $target = $player;

  // check for admin ability
  if ($aseco->allowAbility($player, 'chat_pma')) {
    // get player login or ID
    if (!$target = $aseco->getPlayerParam($player, $command['params'][0]))
      return;

    // check for a message
    if ($command['params'][1] != '') {
      $stamp = date('H:i:s');
      // strip wide fonts from nicks
      $plnick = str_ireplace('$w', '', $player->nickname);
      $tgnick = str_ireplace('$w', '', $target->nickname);

      // drop oldest pm line if receiver's history full
      if (count($target->pmbuf) >= $pmlen) {
        array_shift($target->pmbuf);
      }
      // append timestamp, sender nickname, pm line and login to sender's history
      $target->pmbuf[] = array($stamp, $plnick, $command['params'][1], $player->login);

      // show chat message to receiver
      $msg = '{#error}-pm-$g[' . $plnick . '$z$s$i->' . $tgnick . '$z$s$i]$i {#interact}' . $command['params'][1];
      $msg = $aseco->formatColors($msg);
      $aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $target->login));

      // check if player muting is enabled
      if ($muting_available) {
        // drop oldest message if receiver's mute buffer full
        if (count($target->mutebuf) >= 28) {  // chat window length
          array_shift($target->mutebuf);
        }
        // append pm line to receiver's mute buffer
        $target->mutebuf[] = $msg;
      }

      // show chat message to all admins
      foreach ($aseco->server->players->player_list as $admin) {
        // check for admin ability
        if ($aseco->allowAbility($admin, 'chat_pma')) {
          // drop oldest pm line if admin's buffer full
          if (count($admin->pmbuf) >= $pmlen) {
            array_shift($admin->pmbuf);
          }
      // append timestamp, sender nickname, pm line and login to sender's history
          $target->pmbuf[] = array($stamp, $plnick, $command['params'][1], $player->login);

          // CC the message
          $aseco->client->addCall('ChatSendServerMessageToLogin', array($msg, $admin->login));

          // check if player muting is enabled
          if ($muting_available) {
            // append pm line to admin's mute buffer
            if (count($admin->mutebuf) >= 28) {  // chat window length
              array_shift($admin->mutebuf);
            }
            $admin->mutebuf[] = $msg;
          }
        }
      }
      if (!$aseco->client->multiquery()) {
        trigger_error('[' . $aseco->client->getErrorCode() . '] ChatSend PMA (multi) - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
      }

    } else {
      $msg = '{#server}> {#error}No message!';
      $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $player->login);
    }
  } else {
    $msg = $aseco->getChatMessage('NO_ADMIN');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $player->login);
  }
}  // chat_pma

//response to a private message
function chat_pmr($aseco, $command){
  $player = $command['author'];
  $message = $command['params'];
  if(!empty($player->pmbuf)){      
     $target = end($player->pmbuf)[3];
     $command = array();
     $command['author'] = $player;
     $command['params'] = $target. " " . $message;      
     chat_pm($aseco, $command);
  }else{
      $msg = '{#server}> {#error}No recent private messages!';
      $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $player->login);  
  }
}   // chat_pmr

function chat_pmlog($aseco, $command) {
  global $lnlen;  // from chat.admin.php

  $player = $command['author'];
  $login = $player->login;

  if (!empty($player->pmbuf)) {
    $head = 'Your recent PM history:';
    $msg = array();
    $lines = 0;
    $player->msgs = array();
    $player->msgs[0] = array(1, $head, array(1.2), array('Icons64x64_1', 'Outbox'));
    foreach ($player->pmbuf as $item) {
      // break up long lines into chunks with continuation strings
      $multi = explode(LF, wordwrap(stripColors($item[2]), $lnlen+30, LF . '...'));
      foreach ($multi as $line) {
        $line = substr($line, 0, $lnlen+33);  // chop off excessively long words
        $msg[] = array('$z' . ($aseco->settings['chatpmlog_times'] ? '<{#server}' . $item[0] . '$z> ' : '') .
                       '[{#black}' . $item[1] . '$z] ' . $line);
        if (++$lines > 14) {
          $player->msgs[] = $msg;
          $lines = 0;
          $msg = array();
        }
      }
    }
    // add if last batch exists
    if (!empty($msg))
      $player->msgs[] = $msg;

    // display ManiaLink message
    display_manialink_multi($player);
  } else {
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No PM history found!'), $login);
  }
}  // chat_pmlog

function chat_hi($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/hi');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }
  

  if ($command['params'] != '') {

    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;

    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Hello ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Hello All !';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_hi

function chat_bb($aseco, $command) {
 chat_bye($aseco, $command);
}
function chat_bye($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/bye');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  if ($command['params'] != '') {
    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Bye ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}I have to go... Bye All!';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_bye

function chat_thx($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/thx');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  if ($command['params'] != '') {
    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Thanks ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Thanks All!';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_thx

function chat_lol($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/lol');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  $msg = '$g[' . $player->nickname . '$z$s] {#interact}LoL!';
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_lol

function chat_lool($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/lool');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  $msg = '$g[' . $player->nickname . '$z$s] {#interact}LooOOooL!';
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_lool

function chat_brb($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/brb');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  $msg = '$g[' . $player->nickname . '$z$s] {#interact}Be Right Back !';
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_brb

function chat_afk($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/afk');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  $msg = '$g[' . $player->nickname . '$z$s] {#interact}Away From Keyboard !';
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));

  // check for auto force spectator
  if ($aseco->settings['afk_force_spec']) {
    if (!$aseco->isSpectator($player)) {
      // force player into spectator
      $rtn = $aseco->client->query('ForceSpectator', $player->login, 1);
      if (!$rtn) {
        trigger_error('[' . $aseco->client->getErrorCode() . '] ForceSpectator - ' . $aseco->client->getErrorMessage(), E_USER_WARNING);
      } else {
        // allow spectator to switch back to player
        $rtn = $aseco->client->query('ForceSpectator', $player->login, 0);
      }
    }

    // force free camera mode on spectator
    $aseco->client->addCall('ForceSpectatorTarget', array($player->login, '', 2));
    // free up player slot
    $aseco->client->addCall('SpectatorReleasePlayerSlot', array($player->login));
  }
}  // chat_afk

function chat_gg($aseco, $command) {

  $player = $command['author'];
  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/gg');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  if ($command['params'] != '') {
    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Game ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Game All!';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_gg

function chat_gl($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/gl');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  if ($command['params'] != '') {
    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Luck ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Luck All!';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_gl

function chat_hf($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/hf');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  if ($command['params'] != '') {
    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Have Fun ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Have Fun All!';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_hf

function chat_glhf($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/glhf');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  if ($command['params'] != '') {
    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Luck and Have Fun ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Good Luck and Have Fun All!';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_glhf


function chat_ns($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/ns');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  if ($command['params'] != '') {
    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Nice Shot ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Nice Shot!';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_ns

function chat_n1($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/n1');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  if ($command['params'] != '') {
    $plnickname=isinplayerlist($aseco,$command['params']);
    $command['params']=$plnickname;
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Nice One ' . $command['params'] . ' $i!';
  } else {
    $msg = '$g[' . $player->nickname . '$z$s] {#interact}Nice One!';
  }
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_n1

function chat_bgm($aseco, $command) {

  $player = $command['author'];

  // check if on global mute list
  if (in_array($player->login, $aseco->server->mutelist)) {
    $message = formatText($aseco->getChatMessage('MUTED'), '/bgm');
    $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($message), $player->login);
    return;
  }

  $msg = '$g[' . $player->nickname . '$z$s] {#interact}Bad Game for Me :(';
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
}  // chat_bgm

function chat_official($aseco, $command) {
  global $rasp;

  $msg = $rasp->messages['OFFICIAL'][0];
  $aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($msg), $command['author']->login);
}  // chat_official

function chat_bootme($aseco, $command) {
  global $rasp;

  // show departure message and kick player
  $msg = formatText($rasp->messages['BOOTME'][0],
                    $command['author']->nickname);
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors($msg));
  if (isset($rasp->messages['BOOTME_DIALOG'][0]) && $rasp->messages['BOOTME_DIALOG'][0] != '')
    $aseco->client->addCall('Kick', array($command['author']->login,
                            $aseco->formatColors($rasp->messages['BOOTME_DIALOG'][0] . '$z')));
  else
    $aseco->client->addCall('Kick', array($command['author']->login));
}  // chat_bootme

function chat_rq($aseco, $command) {
  chat_ragequit($aseco, $command);
}
function chat_ragequit($aseco, $command) {
  global $rasp;

  // show departure message and kick player
  if(!isset($rasp->messages['RAGEQUIT'][0])){
    $msg = '{#error}>> {#highlite}{1}$z$s{#error} said: "@"#!" and ragequitted.';
  }else{
    $msg = $rasp->messages['RAGEQUIT'][0];
  }
  
  $aseco->client->query('ChatSendServerMessage', $aseco->formatColors(formatText($msg, $command['author']->nickname)));
  if (isset($rasp->messages['BOOTME_DIALOG'][0]) && $rasp->messages['BOOTME_DIALOG'][0] != '')
    $aseco->client->addCall('Kick', array($command['author']->login,
                            $aseco->formatColors($rasp->messages['BOOTME_DIALOG'][0] . '$z')));
  else
    $aseco->client->addCall('Kick', array($command['author']->login));
}  // chat_bootme
?>