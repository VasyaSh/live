<?php
/* Функции */
function addShip ($shipN, $matrix, $x1, $y1) {
    /* Матрицы кораблей
    $ships[N][x][y,y,...] */
    $ships[0][0] = array(0);
    $ships[1][0] = array(0);
    $ships[2][0] = array(0);
    $ships[2][1] = array(0,1);
    $ships[2][2] = array(0);
    $ships[3][0] = array(0,1);
    $ships[3][1] = array(1,2);
    $ships[4][0] = array(1);
    $ships[4][1] = array(1);
    $ships[4][2] = array(0,1);
    $ships[5][0] = array(0,1);
    $ships[5][1] = array(1);
 
    foreach ($ships[$shipN] as $x2 => $row) {
        foreach ($row as $y2) {
            if ($x1 + $x2 > 7 or $y1 + $y2 > 7
                or $matrix[$x1 + $x2][$y1 + $y2] === 'ok') {
                return false;
            }
            $matrix[$x1 + $x2][$y1 + $y2] = 'ok';
        }
    }
    return $matrix;
}
 
function genMatrix ($mode = false) {
    $matrix = array();
    for ($i=0; $i<8; $i++) {
        for ($c=0; $c<8; $c++) {
            $matrix[$i][$c] = true;
        }
    }
    if ($mode) {
        for ($i=0; $i<6; $i++) {
            $matrixTmp = addShip($i, $matrix, rand(0, 6), rand(0, 6));
            if ($matrixTmp) {
                $matrix = $matrixTmp;
            } else {
                $i--;
            }
        }
    }
    return $matrix;
}
 
function printMatrix($matrix, $user = false) {
    $buff = '<table>';
    foreach ($matrix as $x => $row) {
        $buff .= '<tr>';
        foreach ($row as $y => $cell) {
            $buff .= '<td bgcolor="';
            if (!$cell) {
                $buff .= '#FF0000';
            }
            elseif ($cell === 'ok') {
                $buff .= '#00FF00';
            }
            elseif ($cell === 'sea') {
                $buff .= '#7777FF';
            } else {
                $buff .= '#0000FF';
            }
            $buff .= '">';
            if ($cell && $cell !== 'sea' && !$user) {
                $buff .= '<a href="?x='.$x.'&y='.$y.'">';
            }
            $buff .= '    </a></td>';
        }
        $buff .= '</tr>';
    }
    $buff .= '</table>';
    return $buff;
}
 
function countLives ($matrix) {
    $lives = 0;
    foreach ($matrix as $row) {
        foreach ($row as $cell) {
            if ($cell === 'ok') {
                $lives++;
            }
        }
    }
    return $lives;
}
 
/* Процедурная часть */
session_start();
 
if (isset($_GET['reset'])) {
    session_destroy();
    header ('Location: /seabattle.php');
    die();
}
 
/* Карты-матрицы 8-) */
if (!isset($_SESSION['pcShips'])) { //Корабли компьютера
    $_SESSION['pcShips'] = genMatrix(1);
}
$pcShips = &$_SESSION['pcShips'];
 
if (!isset($_SESSION['uShips'])) { //Корабли пользователя
    $_SESSION['uShips'] = genMatrix(1);
}
$uShips = &$_SESSION['uShips'];
 
if (!isset($_SESSION['pcUncover'])) { //Открытая карта компьютера
    $_SESSION['pcUncover'] = genMatrix();
}
$pcUncover = &$_SESSION['pcUncover'];
 
if (!isset($_SESSION['uUncover'])) { //Открытая карта пользователя
    $_SESSION['uUncover'] = genMatrix();
}
$uUncover = &$_SESSION['uUncover'];
 
/* Алгоритм хода */
if (isset($_GET['x']) && isset($_GET['y'])) {
    $livesBeforeShoot = countLives($pcShips);
    if ($pcShips[$_GET['x']][$_GET['y']] === 'ok') {
        $pcShips[$_GET['x']][$_GET['y']] = false;
    } else {
        $pcShips[$_GET['x']][$_GET['y']] = 'sea';
    }
    $pcUncover[$_GET['x']][$_GET['y']] = $pcShips[$_GET['x']][$_GET['y']];
 
    /* Ход компьютера */
    if ($livesBeforeShoot === countLives($pcShips)) {
        while (true) {
            $livesBeforeShoot = countLives($uShips);
            $x = rand(0,7);
            $y = rand(0,7);
            if ($uUncover[$x][$y] !== false && $uUncover[$x][$y] !== 'sea') {
                if ($uShips[$x][$y] === 'ok') {
                    $uShips[$x][$y] = false;
                } else {
                    $uShips[$x][$y] = 'sea';
                }
                $uUncover[$x][$y] = $uShips[$x][$y];
                if (countLives($uShips) === $livesBeforeShoot || countLives($uShips) === 0) {
                    break;
                }
            }
        }
    }
}
 
$userLives = countLives($uShips);
$user = printMatrix($uShips, 1);
$pcLives = countLives($pcShips);
if ($userLives < 1 or $pcLives < 1) {
    $pc = printMatrix($pcShips, 1);
} else {
    $pc = printMatrix($pcUncover);
}
$precent = round((100/($userLives + $pcLives))*$userLives);
?>
 
<html>
<head>
<title>Sea Battle by [vs] v.php</title>
<style>
    a:link {
        text-decoration: none;
    }
    a:visited {
        text-decoration: none;
    }
</style>
</head>
<body>
<pre>
 __        __                            ___
/   _  _  |  \  _  ___ ___ |    _       / _
\  |  / \ |_ / / \  |   |  |   |    \  / /_
 \ |- |-| |  | |-|  |   |  |   |-    \/    \
_/ |_ | | |__/ | |  |   |  |__ |_ ::  _____/
</pre>
<table border="1">
<th bgcolor="#EEEEFF">Карта игрока</th><th bgcolor="#CCCCCC">
    
</th><th bgcolor="#FFEEEE">Карта компьютера</th>
<tr>
<td><?=$user;?></td><td bgcolor="#CCCCCC"></td><td><?=$pc;?></td>
</tr>
<tr>
<td>Живых секций: <?=$userLives;?></td>
<td></td>
<td>Живых секций: <?=$pcLives;?></td>
</tr>
</table>
<?php
if ($userLives + $pcLives == 0) {
    echo '<b>Ничья!</b>';
}
elseif ($userLives < 1) {
    echo '<b style="color: #FF0000">Вы прогирали!</b>';
}
elseif ($pcLives < 1) {
    echo '<b style="color: #00FF00">Победа!</b>';
} else {
    echo '<b>Удача: </b>'.$precent.'%';
}
?>
 | [url="?reset"]Сбросить[/url]
