<?php

class Neuro {

  private $links = [];
  private $power = 0;
  private $id;
  private $last_scan = [];

  public function __construct() {
    $this->id = uniqid();
  }

  public function addLink(Neuro $n) {
    if (!isset($this->links[$n->id()])) {
      $this->links[$n->id()] = new Link($n);
      return true;
    }
    return false;
  }

  public function input(Neuro $n) {
    $pow = ($this->links[$n->id()]->weight() * $n->output());
    $this->power += $pow;
    $this->last_scan[$n->id()] = $pow;
  }

  public function scanLinks() {
    foreach ($this->links as $link) {
      $this->input($link->neuro());
    }
  }

  public function output() {
    $power = $this->power;
    return $power;
  }

  public function id() {
    return $this->id;
  }

  public function direct() {
    $this->power++;
  }

  public function relax() {
    $this->power = 0;
  }

  public function good($size) {
    foreach ($this->last_scan as $id => $pow) {
      if ($pow > 0) {
        $this->links[$id]->inc(1 / $size);
      } elseif ($pow < 0) {
        //$this->links[$id]->spend(1 / $size);
      }
    }
  }

  public function bad($size) {
    foreach ($this->last_scan as $id => $pow) {
      if ($pow > 0) {
        $this->links[$id]->spend(1 / $size);
      } elseif ($pow < 0) {
        //$this->links[$id]->inc(1 / $size);
      }
    }
  }

}

class Link {

  private $neuro;
  private $weight = 1;

  public function __construct(Neuro $n) {
    $this->neuro = $n;
  }

  public function neuro() {
    return $this->neuro;
  }

  public function weight() {
    return $this->weight;
  }

  public function inc($val) {
    $this->weight += $val;
  }

  public function spend($val) {
    $this->weight -= $val;
  }

}

$brain = [];

$layer = [];
for ($i = 0; $i < 16; $i++) {
  array_push($layer, new Neuro);
}
array_push($brain, $layer);

$prev_layer = $layer;
$layer = [];
for ($i = 0; $i < 5; $i++) {
  $n = new Neuro;
  foreach ($prev_layer as $ln) {
    $n->addLink($ln);
  }
  array_push($layer, $n);
}
array_push($brain, $layer);

$prev_layer = $layer;
$layer = [];
for ($i = 0; $i < 1; $i++) {
  break;
  $n = new Neuro;
  foreach ($prev_layer as $ln) {
    $n->addLink($ln);
  }
  array_push($layer, $n);
}
//array_push($brain, $layer);

/**
 * XXXX XOOO OOOX OOOO
 * XOOX OXOO OOXO OXXO
 * XOOX OOXO OXOO OXXO
 * XXXX OOOX XOOO OOOO
 */
$figs = [
    [
        1, 1, 1, 1,
        1, 0, 0, 1,
        1, 0, 0, 1,
        1, 1, 1, 1]
    ,
    [
        1, 0, 0, 0,
        0, 1, 0, 0,
        0, 0, 1, 0,
        0, 0, 0, 1
    ],
    [
        0, 0, 0, 1,
        0, 0, 1, 0,
        0, 1, 0, 0,
        1, 0, 0, 0
    ],
    [
        0, 0, 0, 0,
        0, 1, 1, 0,
        0, 1, 1, 0,
        0, 0, 0, 0
    ],
    [
        0, 0, 1, 0,
        1, 1, 1, 0,
        0, 1, 1, 1,
        0, 1, 0, 0
    ]
];

/* THE TRAINING */

for ($i = 0; $i < 1000; $i++) {
  foreach ($figs as $fig_n => $fig) {
    foreach ($fig as $n => $v) {
      if ($v) {
        $brain[0][$n]->direct();
      }
    }

    process($brain);

    $winner = -1;
    $max = -10000000;
    $two = -10000000;
    foreach ($brain[1] as $num => $edu_neuro) {
      if ($edu_neuro->output() > $max) {
        $max = $edu_neuro->output();
        $winner = $num;
      }
      elseif ($edu_neuro->output() > $two) {
        $two = $edu_neuro->output();
      }
    }

    if ($winner != $fig_n || $max - $two < 1) {
      foreach ($brain[1] as $num => $edu_neuro) {
        if ($num == $fig_n) {
          $edu_neuro->good(1000);
        } else {
          $edu_neuro->bad(1000);
        }
        $edu_neuro->relax();
      }
    }
  }
}

/* RUN TEST */

$test_figs = [
    [
        0, 0, 0, 1,
        0, 0, 1, 0,
        0, 1, 0, 0,
        1, 0, 0, 0
    ],
    [
        0, 1, 1, 0,
        1, 1, 1, 1,
        1, 1, 1, 1,
        0, 1, 1, 0
    ]
];

foreach ($test_figs as $tf) {
  array_push($figs, $tf);
}

foreach ($figs[5] as $n => $v) {
  if ($v) {
    $brain[0][$n]->direct();
  }
}
process($brain);
echo '<pre>';
var_dump($brain);

function process($brain) {
  $prev_layer = [];
  foreach ($brain as $layer) {
    foreach ($layer as $n) {
      $n->scanLinks();
    }
    foreach ($prev_layer as $n) {
      $n->relax();
    }
    $prev_layer = $layer;
  }
}
