<?php
/**
* Прекрасный генератор графиков
* Использует GD.
*Create line charts by GDlib in PHP. For example:
<?php
* // Нарисовать график синуса и косинуса, и прямую 0
* $diagram = new vsDiagram(); // будет 640x480 с черным фоном
* $diagram->xRange()->yRange(4)
*         ->addPoly() // имя - line, цвет - белый
*         ->addPoly('red', 255, 0, 0)
*         ->addPoly('blue', 0, 0, 255)
*         ->addVertex('line', 0, 2) // проведем линию из начала
*         ->addVertex('line', 10, 2); // в конец
* for ($x = 0; $x < 10; $x+=0.01) {
*     $y = sin($x) + 2;
*     $diagram->addVertex('blue', $x, $y);
* }
* for ($x = 0; $x < 10; $x+=0.01) {
*     $y = cos($x) + 2;
*     $diagram->addVertex('red', $x, $y);
* }
* header('Content-Type: image/png');
* echo $diagram;
* ?>
See result here:
* https://www.vasya.pro/pics/cossin.png
* 
* @author Vasiliy B. Shpilchin (http://www.vasya.pro)
* @copyright [vs] 2011
*/
class vsDiagram {
    // Размеры изображения
    private $x;
    private $y;
    // ресурс
    private $pic;
    // параметры горизонтальных линий
    private $xRange = array(
        'num' => null,
        'color' => null
    );
    // параметры вертикальных линий
    private $yRange = array(
        'num' => null,
        'color' => null
    );
    // На эти переменные делятся пиксельные координаты.
    // Вершины то мы будем по сетке указыват, а не в пикселях
    private $xDiv;
    private $yDiv;
    // Ломаные. Каждый элемент массива - массив с цветом ломанной и массивом
    // координат вершин.
    private $polygons = array();
 
    // Размеры и фоновый цвет
    public function __construct($x = 640, $y = 480, $r = 0, $g = 0, $b = 0) {
        $this->x = intval($x);
        $this->y = intval($y);
        $this->pic = imagecreate($this->x, $this->y);
        imagecolorallocate($this->pic, $r, $g, $b);
    }
 
    // Горзонтальные линиии (шкала X)
    public function xRange($num = 10, $div = 1, $r = 128, $g = 128, $b = 128) {
        $this->xRange['num'] = $num;
        $this->xRange['color'] = imagecolorallocate($this->pic, $r, $g, $b);
        $this->xDiv = $div;
        return $this;
    }
 
    // Вертикальные линиии (шкала Y)
    public function yRange($num = 10, $div = 1, $r = 128, $g = 128, $b = 128) {
        $this->yRange['num'] = $num;
        $this->yRange['color'] = imagecolorallocate($this->pic, $r, $g, $b);
        $this->yDiv = $div;
        return $this;
    }
 
    public function addPoly($name = 'line', $r = 255, $g = 255, $b = 255) {
        $this->polygons[$name] = array(
            'color' => imagecolorallocate($this->pic, $r, $g, $b),
            'vertices' => array()
        );
        return $this;
    }
 
    public function addVertex($poly = 'line', $x = 0, $y = 0) {
        $xC = $x * (($this->x / $this->xRange['num']) / $this->xDiv);
        $yC = ($this->y - 1) - $y * (($this->y / $this->yRange['num']) / $this->yDiv);
        array_push($this->polygons[$poly]['vertices'], array('x'=>$xC, 'y'=>$yC));
        return $this;
    }
 
    // Рисует график и возвращает картинку, ресуср gd
    public function draw() {
        // Сетка: X
        $x_step = ($this->x / $this->xRange['num']);
        $x_steps = ($this->x / $x_step);
        for ($i = 1; $i < $x_steps; $i++) {
            imageline($this->pic, $x_step * $i - 1, 0, $x_step * $i - 1,
                    $this->y - 1, $this->xRange['color']);
        }
        // Сетка: Y
        $y_step = ($this->y / $this->yRange['num']);
        $y_steps = ($this->y / $y_step);
        for ($i = 1; $i < $y_steps; $i++) {
            imageline($this->pic, 0, $y_step * $i - 1, $this->x - 1,
                    $y_step * $i - 1, $this->yRange['color']);
        }
        // Ломаные
        foreach ($this->polygons as $poly) {
            foreach ($poly['vertices'] as $n=>$vertix) {
                if (isset($poly['vertices'][$n-1])) {
                    $x1 = $poly['vertices'][$n-1]['x'];
                    $y1 = $poly['vertices'][$n-1]['y'];
                }
                else {
                    $x1 = $vertix['x'];
                    $y1 = $vertix['y'];
                }
                $x2 = $vertix['x'];
                $y2 = $vertix['y'];
                imageline($this->pic, $x1, $y1, $x2, $y2, $poly['color']);
            }
        }
        return $this->pic;
    }
 
    // удобно же :)
    public function __toString() {
        imagepng($this->draw());
    }
}
