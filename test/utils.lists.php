<?php
require_once __DIR__."/../utils.lists.php";
require_once __DIR__."/../no-cli.php";

use Utils\Lists\DoublyLinked;

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
echo "traverse foreach:\n";
foreach ( $dl as $key=>$value )
{
	echo "$key = $value\n";
}

echo "---\n";
echo "Move group_key_4 after group_key_3\n";
$dl("group_key_4")->move_after("group_key_3");
assert($dl("group_key_4")->prev === "group_key_3");
assert($dl("group_key_3")->next === "group_key_4");
echo "traverse foreach:\n";
foreach ( $dl as $key=>$value )
{
	echo "$key = $value\n";
}

//exit;
echo "---\n";
echo "Move whole group1 on the top\n";
$dl->group_sort("group1")->group_move_top("group1");
assert($dl->top() === "group_value_1");
echo "traverse foreach:\n";
foreach ( $dl as $key=>$value )
{
	echo "$key = $value\n";
}

echo "---\n";
echo "Erase all elements marked group1\n";
$dl->group_erase("group1");
assert($dl->top() === "super_value");
echo "traverse foreach:\n";
foreach ( $dl as $key=>$value )
{
	echo "$key = $value\n";
}

echo "---\n";
echo "traverse by shift/pop:\n";
$dl->rewind();
while ( $dl->valid() )
{
	echo "topped: ",$dl->shift(),"\n";
	echo "popped: ",$dl->pop(),"\n";
}
assert($dl->count()===0);
echo "Current count: ",$dl->count(),"\n";

