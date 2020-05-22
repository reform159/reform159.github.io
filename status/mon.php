<link rel="stylesheet" href="style.css" type="text/css" />
<div id=Monitoring>
<?php
######
# НЕЛЬЗЯ ДАВАТЬ СЕРВЕРАМ ОДИНАКОВЫЕ НАЗВАНИЯ!!!!
######
//--------Пользовательские переменные--------//

### Показ времени загрузки
$tDebug = true; # Показ времени загрузки скрипта true = включен; false = выключен
### Общие настройки
$chacheLoc = 'monitoring.json'; # Путь до расположения кэша. Файл сам не создастся!!!
$checkDel = 3; # Промежуток между проверками онлайна сервера (в секундах) (мысленно умножить на кол-во серверов) НЕ СТАВИТЬ МЕНЬШЕ 1.1 !!!
$tOut = 0.125; # Время ожидания ответа сервера (если он выключен)
$offWidth = 100; # Сколько процентов бара будет заполнено, если сервер выключен
$connErr = '<b style="color: red;">Перезагрузка</b>'; # Что выводится, если сервер выключен
### Классы состояния сервера
$offClass = ''; # Класс, который добавляется, если сервер ВЫКЛ.
$onClass = 'игроков онлайн'; # Класс, который добавляется, если сервер ВКЛ.
### Список серверов
# $server[] = 'IP_сервера:Порт_сервера:Название_сервера';
// Имена серверов с пробелами не тестировались, но должны работать. Вроде..
$server[]  = '54.38.175.233:25565:Выживание:Light:1';
$server[]  = 'mc.egserv.ru:25565:Bыживание:Hard:2';
$server[]  = 'MC.BLACKRISE.RU:25565:Мини-Игры:Beta:3';
$server[]  = 'play.pigmine.ru:25565:Bыживание:Бомжа:4';

//--------Системные переменные--------//

$fullOnl = 0;
$curTime = time();
$iter = -1;

if($tDebug) {
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$start = $time;
}

//--------Считывание осн. данных--------//

$comData = json_decode(file_get_contents($chacheLoc), true);

$curOnl = $comData['comStats'][1];
$onlRec = $comData['comStats'][2];
$lCheck = $comData['comStats'][3];
$sCheck = $comData['comStats'][4];

//--------Сам скрипт--------//

$tInterval = $curTime-$lCheck;

foreach($server as $e) {
	$iter += 1;
	list($host, $port, $name, $modname, $nomer) = explode(":", $e);
	//--------Получение переменных--------//
	if( ($tInterval >= $checkDel) AND ($sCheck == $iter) ) {
		if( $socket = @stream_socket_client('tcp://'.$host.':'.$port, $erno, $erstr, $tOut) ) {
			@fwrite($socket, "\xFE");
			$data = @fread($socket, 1024);
			if( strpos($data,"\x00\x00") != 0 ) {
				$info = explode("\x00\x00", $data);
				$info = str_replace("\x00", '', $info);
				###
				$plOnl = $info[4];
				$plMax = $info[5];
				$colBar = $onClass;
			} else {
				$info = explode("\xA7", $data);
				$info = str_replace("\x00", '', $info);
				$plOnl = $info[1];
				$plMax = $info[2];
				$colBar = $onClass;
			}
			@fclose($socket);
		} else {
			$plOnl = 0;
			$plMax = 0;
			$colBar = $offClass;
		}
		unset($data);
		unset($info);
		$checkServ = $iter+1;
		if( $checkServ >= count($server) ) {
			$checkServ = 0;
		}
	} else {
		$plOnl = $comData[$name][1];
		$plMax = $comData[$name][2];
		$colBar = $comData[$name][3];
	}
	$fullOnl += $plOnl;
	$newMon[$name] = array(
		1 => $plOnl,
		2 => $plMax,
		3 => $colBar
	);
	if( $colBar == $offClass ) {
		$onBar = $offWidth;
	} else {
		$onBar = ($plOnl/$plMax)*100;
		if( $onBar > 100 ) {
			$onBar = 100;
		}
	}
	//--------Вывод самого онлайна--------//
?>
<div class="server">
                                        <div class="img">
                        <img src="assets/img/onlineBg.svg" class="bg">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="70px" height="70px" viewBox="0 0 150 150" preserveAspectRatio="none">
                            <path class="val" d="M 74.99877826952367 144.9999999893384 A 70 70 0 1 1 75 145" fill="none" stroke="#ffa500" stroke-width="10px"></path>
                        </svg>
                        <div class="v">#<?php echo $nomer;?></div>
                    </div>
                                        <div>
                    <div class="ip"><?php echo $name;?><span> <?php echo $modname;?></span></div>
                    <div class="online">
                        <span><?php 
					if( $colBar === $offClass ) {
						echo $connErr;
					} else {
						echo $plOnl;?> из <?php echo $plMax;
					}
				?></span> <?php echo $colBar;?>
                    </div>
                </div>
            </div>

<?php
}

//--------Запись общей статистики--------//

$comIter = count($server);
if( $tInterval >= $checkDel ) {
	if( $fullOnl > $onlRec ) {
		$onlRec = $fullOnl;
	}
	$newMon['comStats'] = array(
							1 => $fullOnl,
							2 => $onlRec,
							3 => $curTime,
							4 => $checkServ
						);
	file_put_contents($chacheLoc, json_encode($newMon));
} else {
	$newMon['comStats'] = array(
							1 => $fullOnl,
							2 => $onlRec,
							3 => $lCheck,
							4 => $sCheck
						);
	file_put_contents($chacheLoc, json_encode($newMon));
}

//--------Вывод общей статистики-------//
?>
<div class="server">
                                        <div class="statistics">
                    <div class="ip">Общий онлайн</div>
                    <div class="online">
                        <span><?php echo $fullOnl; ?> из 800</span> игроков онлайн
						<?php
if($tDebug) {
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $start), 4);
	echo '<div id=GenTime>получено за '.$total_time.' сек.</div>';
}
?>
                    </div>
                </div>
            </div>
</div>
