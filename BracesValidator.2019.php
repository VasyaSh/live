<?php
/**
 * @author Vasilii B. Shpilchin
 * Function to validate a string, is all a braces are closed in correct order.
 * Here is high-performance solution written on php to validate a nested braces closing order.
 *
 * @param $str a string to validation
 * @param $b list of the braces
 * @param $ops reference is not useful
 * @param $cls reference is not useful
 * @return boolean|integer Returns true if the string is valid, otherwise return string position where an error occurred.
 */
function validate_braces($str, $b = ['[' => ']', '{' => '}', '(' => ')'], &$ops = '', &$cls = - 1) {
    for ($i = 0; $i < strlen($str) && '' !== ($c = $str{$i}) && false !== (isset($b[$str{$i}]) ? (++$cls . $ops{$cls} = $c) : ((false !== ($c = array_search($str{$i}, $b)) && - 1 < $cls && $ops{$cls} === $c) ? $cls-- : false === $c)); $i++);
    return (-1 === $cls && $i === strlen($str)) ? : $i;
}

$good_string = 'Vv2~h<OO5hZ@v%38Bfq{}aRD\(7Up8)W& {ek#p>}{\j4{NwOIEztG8jn$s970M`=[]aK^O<TzV0Z(t$C~6_7n`jaQcrbf4YmY*JJ5*zjr7 rBT,EGjsb7AolZ)*|T=g#k/G_l1=9HBaN:.,grd8}l3(cUc[(n*\h`M8GqWi8t`li[E3(@&@w5.(.Sjy7L7:@2jdwq1R(r+zrfT9Ev/T =-OXLkwV pKG|Ht4xylf.b)s8a:vaYVwj:|/_zzZ))])])}{}';
$result = validate_braces($good_string);
var_dump($result); // true

$bad_string = ',kPf[|:p=oe=l$R DWdC"5H^\'.X]eKQ@mQE$r^=TKp6E4CWo=oa,[p4q>apb{rVcqO_\|=TDycR?^eiFq8"dI%$PJ/Vl:J.L3\5g#ue-\'7OUy\XOVEk&sxZj|Gs;b&{s,R vQ0dz:]h~8$A>qe@.w(&1^JJqmb*%2. L%_KxkVr>D)B{XvMmq# 5GJ-4kL_<$TjM.0a\'UAx#4N`2 i4"<"-.RdB[-2#ZSj_HFTA2N[v9v{-IUz q"x ru8}]]}}}]';
$result = validate_braces($bad_string);
var_dump($result); // 137
