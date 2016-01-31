<?php
require_once __DIR__."/../utils.lists.php";
require_once __DIR__."/../no-cli.php";

use Utils\Lists\DoublyLinked;
use function Utils\Lists\print_r as list_print_r;

$dl = new DoublyLinked();
echo "insert top_key, bottom_key\n";
$dl->insert_top("top_key","top_value");
assert($dl['top_key']==="top_value");
//var_dump("top_key_VALUE={$dl['top_key']}");
$dl->insert_bottom("bottom_key","bottom_value");
assert($dl['bottom_key']==="bottom_value");
assert($dl('bottom_key')->prev === "top_key");
//var_dump("bottom_key_VALUE={$dl['bottom_key']}");

echo "---\n";
echo "insert second_key,pre_last_key,super_bottom,super_top\n";
$dl->insert_after("top_key","second_key","second_value");
$dl->insert_before("bottom_key","pre_last_key","pre_last_value");
$dl->insert_bottom("super_bottom","super_value");
$dl->insert_top("super_top","super_value");
assert($dl["super_bottom"] === "super_value");


echo "---\n";
echo "delete second_key\n";
unset($dl['second_key']);
assert($dl['second_key']===null);


echo "---\n";
echo "traverse foreach:\n";
foreach ( $dl as $key=>$value )
{
	echo "$key = $value\n";
}

echo "---\n";
echo "Insert few elements and make a move group on the top:\n";
$dl->insert_after("pre_last_key","group_key_1","group_value_1")->group_set("group1");
$dl->insert_after("undefined","group_key_5","group_value_5")->group_set("group1");	// must appear as a last element
$dl->insert_after("group_key_1","group_key_4","group_value_4")->group_set("group1") // this case is to move futher right way
	->insert_after("group_key_2","group_value_2")->group_set("group1")
	->insert_after("group_key_3","group_value_3")->group_set("group1");

list_print_r($dl);

echo "---\n";
echo "Move group_key_4 after group_key_3\n";
$dl("group_key_4")->move_after("group_key_3");
assert($dl("group_key_4")->prev === "group_key_3");
assert($dl("group_key_3")->next === "group_key_4");

list_print_r($dl);

//exit;
echo "---\n";
echo "Move whole group1 on the top\n";
$dl->group_sort("group1")->group_move_top("group1");
assert($dl->top() === "group_value_1");

list_print_r($dl);

echo "---\n";
echo "Erase all elements marked group1\n";
$dl->group_erase("group1");
assert($dl->top() === "super_value");

list_print_r($dl);

echo "---\n";
echo "traverse by shift/pop:\n";
$dl->rewind();
while ( $dl->valid() )
{
	echo "topped: ",($val=$dl->shift())," of <",gettype($val),">\n";
	echo "popped: ",($val=$dl->pop())," of <",gettype($val),">\n";
}
assert($dl->count()===0);
echo "Current count: ",$dl->count(),"\n";

echo "---\n";
echo "insert top, bottom and few elements after, set INSERT_AFTER_LAST flag\n";
$dl->insert_top("simple_top","top_value_1");
$dl->insert_bottom("simple_bottom","bottom_value_1");
$dl->insert_flag = $dl::INSERT_AFTER_TAIL;
echo "Insert value_1 after simple_top\n";
$dl->insert_after("simple_top","next_1","value_1");
echo "Insert overtop after simple_top then move it on the most top and insert value_2, it should be after value_1\n";
$dl->insert_after("simple_top","overtop","overtop");
$dl->move_top("overtop");
echo "Insert value_2 after simple_top\n";
$dl->insert_after("simple_top","next_2","value_2");
echo "Insert value_3 after simple_top\n";
$dl->insert_after("simple_top","next_3","value_3");

echo "Set INSERT_BEFORE_FIRST\n";
$dl->insert_flag = $dl::INSERT_BEFORE_HEAD;
echo "Insert value_4 before simple_top\n";
$dl->insert_before("simple_top","before_1","value_4");
echo "Insert value_5 before simple_top\n";
$dl->insert_before("simple_top","before_2","value_5");
echo "Insert value_6 before simple_top\n";
$dl->insert_before("simple_top","before_3","value_6");

assert($dl->item("next_2")->prev === "next_1" && $dl->item("next_2")->next === "next_3");
echo "Remove bottom\n";
unset($dl["simple_bottom"]);

echo "Traverse foreach:\n";
list_print_r($dl);

echo "Reset insert flag\n";
$dl->insert_flag = $dl::INSERT_DEFAULT;

echo "---\n";
echo "insert after undefined element\n";
$dl->insert_after("undefined","hack_item","hack_value");
$dl->insert_before("undefined","hack_item_2","hack_value_2");

echo "traverse foreach:\n";
list_print_r($dl);
// $n=0;
// foreach ( $dl as $key=>$value )
// {
// 	echo "[$n] $key = $value\n";
// 	$n++;
// }
// unset($n);

echo "---\n";
echo "Distances\n";
$dist = $dl->distance("next_2","before_2",$dl::SEEK_BOTH);
assert($dist === -4);
echo "The distance between next_2 and before_2 = {$dist}\n";
$dist = $dl->distance("simple_top","next_1",$dl::SEEK_BOTH);
assert($dist === 1);
echo "The distance between simple_top and next_1 = {$dist}\n";
unset($dist);
echo "Lookup\n";
$item = $dl->lookup("simple_top",3);
assert($item->key === "next_3");
echo "3rd element after simple_top is ".$item->key."\n";
$item = $dl("hack_item")->lookup(-5);
assert($item->key === "before_1");
echo "5th element before hack_item is ".$item->key."\n";
unset($item);

echo "---\n";
echo "Moving\n";
$item = $dl->insert_before("hack_item","to_move_item","moving value");
echo "Previous to new item named 'to_move_item' is ".$item->prev." and next to is ".$item->next."\n";
echo "Move it 6 points back and the 4 points forward\n";
$item = $item->move_distance(-6);
echo "1st move, previous to item is ".$item->prev." and next to is ".$item->next."\n";
$item = $item->move_distance(4);
echo "2nd move, previous to item is ".$item->prev." and next to is ".$item->next."\n";

list_print_r($dl);
