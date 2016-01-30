<?php
/*
 * namespace Utils\Lists;
 * Description: Some implementation of lists
 */

namespace Utils\Lists;

// ================================================================================================
// * Doubly linked list with grouping feature
// Can be iterated and traversed
// ================================================================================================
class DoublyLinked implements \Iterator, \ArrayAccess, \Countable
{
	private $list = [];
	private $top;
	private $bottom;
	private $current;
	private $null;
	private $groups = [];
	private $freed = [];

	const KEY="key";
	const VALUE="value";
	const PREV="prev";
	const NEXT="next";
	const GROUP="group";
	const FAKE="fake";

	const RET_KEY=1;
	const RET_VALUE=0;
	const RET_BOTH=2;

	const INSERT_BEFORE_HEAD=1;
	const INSERT_AFTER_TAIL=2;
	const INSERT_DEFAULT=0;

	const SEEK_BOTH=3;
	const SEEK_BEFORE=1;
	const SEEK_AFTER=2;

	private $insert_next_counter = 0;
	private $insert_prev_counter = 0;
	public $insert_flag = self::INSERT_DEFAULT;

	// ------------------------------------------------------------------------
	public function __construct()
	{
		// $this->reset_insert_counters();
	}

	// ------------------------------------------------------------------------
	// * return value of current element
	public function current($ret=self::RET_VALUE)
	{
		switch ( $ret )
		{
		default:
		case self::RET_VALUE: return $this->list[$this->current]($this)->value;
		case self::RET_KEY:   return $this->current;
		case self::RET_BOTH:  return [$this->current,$this->list[$this->current]($this)->value];
		}
	}

	// ------------------------------------------------------------------------
	// * return name of current internal key
	public function key()
	{
		return $this->current;
	}

	// private function reset_insert_counters()
	// {
	// 	// $this->insert_next_counter   = 0;
	// 	// $this->insert_prev_counter = 0;
	// 	$this->insert_flag = $this->insert_flag;
	// 	if ( $this->current === null )
	// 		return;
	// 	$this->list[$this->current]($this)->insert_shift_left  = 0;
	// 	$this->list[$this->current]($this)->insert_shift_right = 0;
	// }

	// ------------------------------------------------------------------------
	// * move internal pointer to the next element
	public function next()
	{
		if ( $this->list[$this->current]($this)->next !== null )
			$this->current = $this->list[$this->current]($this)->next;
		else
			$this->current = null;
		// $this->reset_insert_counters();
	}

	// ------------------------------------------------------------------------
	// * move internal pointer to the previous element
	public function prev()
	{
		if ( $this->list[$this->current]($this)->prev !== null )
			$this->current = $this->list[$this->current]($this)->prev;
		else
			$this->current = null;
		// $this->reset_insert_counters();
	}

	// ------------------------------------------------------------------------
	// * set internal pointer to the first element
	public function rewind()
	{
		$this->current = $this->top;
		// $this->reset_insert_counters();
	}

	// ------------------------------------------------------------------------
	// * always true if any element is present
	public function valid()
	{
// var_dump("CURRENT IS {$this->current} TOP({$this->top}) BOT({$this->bottom})");
		return $this->current !== null;
	}

	// ------------------------------------------------------------------------
	// * set internal pointer to the last element
	public function last()
	{
		$this->reset_insert_counters();
		$this->current = $this->bottom;
	}

	// ------------------------------------------------------------------------
	// * return first element value
	public function top()
	{
		$item = $this->offset($this->top);
		return $item($this)->value;
	}

	// ------------------------------------------------------------------------
	public function top_key()
	{
		return $this->top;
	}

	// ------------------------------------------------------------------------
	public function bottom_key()
	{
		return $this->bottom;
	}

	// ------------------------------------------------------------------------
	// * return first element value and remove it
	public function shift($ret=self::RET_VALUE)
	{
// var_dump("SHIFT FROM {$this->top}");
		return $this->get_unset($this->top,$ret);
	}

	// ------------------------------------------------------------------------
	// * get element value by offset and remove it
	public function get_unset($ofs,$ret=self::RET_VALUE)
	{
		$item = $this->offset($ofs);
		$this->offsetUnset($ofs);

		switch ( $ret )
		{
		default:
		case self::RET_VALUE: return $item($this)->value;
		case self::RET_KEY:   return $ofs;
		case self::RET_BOTH:  return [$ofs,$item($this)->value];
		}
	}

	// ------------------------------------------------------------------------
	// * return last element value and remove it
	public function pop($ret=self::RET_VALUE)
	{
		return $this->get_unset($this->bottom,$ret);
	}

	// ------------------------------------------------------------------------
	// * get value of the last element
	public function bottom()
	{
		$item = $this->offset($this->bottom);
		return $item($this)->value;
	}

	// ------------------------------------------------------------------------
	// * return value by offset
	public function offsetGet($ofs)
	{
		return isset($this->list[$ofs]) ? $this->list[$ofs]->value : null;
	}

	// ------------------------------------------------------------------------
	// * insert or update element by offset
	// if element was not in list it become last element
	public function offsetSet($ofs,$value)
	{
		if ( $this->offsetExists($ofs) )
			$this->list[$ofs]($this)->value = $value;
		else
			$this->insert_bottom($ofs,$value);
//		$this->list[$ofs] = [self::VALUE=>$value,self::PREV=>null,self::NEXT=>null];
	}

	// ------------------------------------------------------------------------
	// * check the offset is exist in list
	public function offsetExists($ofs)
	{
		if ( $ofs === null )
			return false;
		return isset($this->list[$ofs]);
	}

	// ------------------------------------------------------------------------
	// * remove element by offset
	public function offsetUnset($ofs)
	{
		$this->free_item($ofs,true);
	}

	// ------------------------------------------------------------------------
	public function erase($ofs)
	{
		return $this->free_item($ofs,true);
	}

	// ------------------------------------------------------------------------
	public function item($ofs)
	{
		return $this->offset($ofs);
	}

	// ------------------------------------------------------------------------
	// * Remove $ofs item from list and collapse left empty space between siblings
	private function free_item($ofs,$remove=false)
	{
// var_dump("UNSET({$ofs})");
		$item   = $this->offset($ofs);

		if ( $item->fake )
			return $remove ? null : $item;

		$before = &$this->offset($item->prev);
		$after  = &$this->offset($item->next);
		$this->top    = ($ofs === $this->top    ? $item->next : $this->top);
		$this->bottom = ($ofs === $this->bottom ? $item->prev : $this->bottom);
		$before($this)->next = $item->next;
		$after($this)->prev  = $item->prev;

		if ( !$remove && $item->prev !== null )
		{
			$item($this)->prev = null;
		}

		if ( !$remove && $item->next !== null )
		{
			$item($this)->next = null;
		}

		if ( $this->current === $ofs )
		{
			$this->current = $item->next !== null ? $item->next : ($item->prev !== null ? $item->prev : null );
			// $this->reset_insert_counters();
		}
// var_dump($before,$after,$this->current);
		if ( $remove )
		{
			if ( $item->group !== null )
				$this->group_unset($item);
			$return = $item->value;
			unset($item);
			unset($this->list[$ofs]);
		}
		else
		{
			$this->freed[$item->key] = &$item;
			$return = $item;
		}

		return $remove ? $return : $item;

	}

	// ------------------------------------------------------------------------
	// * return number of elements in list
	public function count()
	{
		return \count($this->list);
	}

	// ------------------------------------------------------------------------
	// * get element at offset or null element
	private function &offset($ofs)
	{
		if ( $ofs instanceof DoublyLinkedElement )
			return $ofs;

		if ( \is_null($ofs) || !isset($this->list[$ofs]) )
			$ret = &$this->null_elem();
		else
			$ret = &$this->list[$ofs];
		return $ret;
	}

	// ------------------------------------------------------------------------
	// * create null element
	private function &null_elem()
	{
		$null = $this->new_elem();
		$null($this)->fake = true;
		$this->null = $null;
		return $this->null;
	}

	// ------------------------------------------------------------------------
	// * create empty element
	private function new_elem($key=null,$value=null)
	{
		return new DoublyLinkedElement($this,[self::KEY=>$key,self::VALUE=>$value,self::FAKE=>false]);
	}

	// ------------------------------------------------------------------------
	// * check and set item edges if needed
	private function refresh_edges(&$new)
	{
		$key = $new->key;
		if ( $new->next === null && $new->prev === null )
		{
			$new->__invoke($this)->prev = $this->bottom;
			$this->item($new->prev)->__invoke($this)->next = $key;
			$this->bottom = $key;

			if ( isset($this->freed[$key]) )
				unset($this->freed[$key]);
		}
		else
		{
			if ( $new->next === null )
				$this->bottom = $key;

			if ( $new->prev === null )
				$this->top = $key;

			if ( $this->current === null )
				$this->current = $this->top;
		}

		if ( $this->bottom === null )
			$this->bottom = $key;

		if ( $this->top === null )
			$this->top = $key;
		// $this->current = $key;	// it might be a mistake to set current here
	}

	// ------------------------------------------------------------------------
	// * insert new pair key=>value before some key
	public function insert_before($ofs,$key,$value)
	{
		// $after  = &$this->offset($ofs);
		// $before = &$this->offset($after->prev);
		$new    = $this->new_elem($key,$value);
		// $this->insert_item_between($before,$after,$new);
		$this->insert_item_before($ofs,$new);
		return $new;
	}

	// ------------------------------------------------------------------------
	public function insert_item_before($ofs,DoublyLinkedElement &$item)
	{
		$after = &$this->offset($ofs);

		if ( $this->insert_flag & self::INSERT_BEFORE_HEAD )
		{
			$after0 = &$this->offset($ofs);
			$shift_count = $after->insert_shift_left($this)+1;
		}

		$before = &$this->offset($after->prev);
		$_ofs = $ofs;

		if ( $this->insert_flag & self::INSERT_BEFORE_HEAD )
		for ( $shift=0; $shift<$shift_count; $shift++)
		{
			$ofs = $before->key;
			$after = &$this->offset($_ofs);
			$before = &$this->offset($after->prev);
			if ( $before->key === null )
				break;
			$_ofs = $before->key;
		}

		$this->insert_item_between($before,$after,$item);

		if ( $this->insert_flag & self::INSERT_BEFORE_HEAD )
			$after0->insert_shift_left($this,1);

		return $item;
	}

	// ------------------------------------------------------------------------
	// * insert new pair between specified siblings
	private function insert_between(&$before,&$after,$key,$value)
	{
		$new = $this->new_elem($key,$value);
		$this->insert_item_between($before_key,$before,$after_key,$after,$new);
		return $new;
// var_dump($this->list,$before,$after);
	}

	private function insert_item_between(&$before,&$after,DoublyLinkedElement &$elem)
	{
		$elem($this)->next = $after->key;
		$elem($this)->prev = $before->key;
		$elem($this)->fake = false;
		// $elem->value = $value;
		$before($this)->next = $elem->key;
		$after($this)->prev = $elem->key;
		$this->list[$elem->key] = $elem;
		$this->null_elem();
		$this->refresh_edges($elem);
	}

	// ------------------------------------------------------------------------
	// * insert new pair key=>value after some key
	public function insert_after($ofs,$key,$value)
	{
		// $before = &$this->offset($ofs);
		// $after  = &$this->offset($before->next);
		$new    = $this->new_elem($key,$value);
		// $this->insert_item_between($before,$after,$new);
		$this->insert_item_after($ofs,$new);
		return $new;
	}

	public function insert_item_after($ofs, DoublyLinkedElement &$item)
	{
		$before = &$this->offset($ofs);
		if ( $this->insert_flag & self::INSERT_AFTER_TAIL )
		{
			$before0 = &$this->offset($ofs);
			$shift_count = $before->insert_shift_right($this)+1;
		}

		$after  = &$this->offset($before->next);
		$_ofs = $ofs;

		if ( $this->insert_flag & self::INSERT_AFTER_TAIL )
		for ( $shift=0; $shift < $shift_count; $shift++ )
		{
			$before = &$this->offset($ofs);
			$after  = &$this->offset($before->next);
			if ( $after->key === null )
				break;
			$ofs = $after->key;
		}

		$this->insert_item_between($before,$after,$item);

		if ( $this->insert_flag & self::INSERT_AFTER_TAIL )
			$before0->insert_shift_right($this,1);

		return $item;
	}
	// ------------------------------------------------------------------------
	// * insert new pair key=>value as first
	public function insert_top($key,$value)
	{
		return $this->insert_before($this->top,$key,$value);
	}

	// ------------------------------------------------------------------------
	// * insert new pair key=>value as last
	public function insert_bottom($key,$value)
	{
		return $this->insert_after($this->bottom,$key,$value);
	}

	// ------------------------------------------------------------------------
	// * Remove items from list and return them as array
	// May be deprecated
	protected function slice($ofs,$count)
	{
		$this->reset_insert_counters();
		$slice = [];
		$n = 0;

		if ( $count === 0 )
			return $slice;

		$slice[$n] = $this->offset($ofs);

		if ( $count > 0 )
		{
			$dir = self::NEXT;
		}
		else
		{
			$dir = self::PREV;
		}

		$count = \abs($count);

		while ( $slice[$n]->$dir !== null && $n < $count )
		{
			$this->offsetUnset($ofs);
			$n++;
			$slice[$n] = $this->offset($ofs);
		}

		if ( $slice[$n]($this)->fake )
			unset($slice[$n]);

		return $slice;
	}

	// ------------------------------------------------------------------------
	// protected function unslice_after($after_key,array $slice)
	// {

	// }

	// ------------------------------------------------------------------------
	public function move_after($after_key,$ofs)
	{
		$item = $this->free_item($ofs);

		if ( $item->fake )
			return $item;

		return $this->insert_item_after($after_key,$item);
		// $slice = $this->slice($ofs,1);
		// $this->unslice_after($after_key,$slice);
	}

	// ------------------------------------------------------------------------
	public function move_before($before_key,$ofs)
	{
		$item = $this->free_item($ofs);

		if ( $item->fake )
			return $item;

		return $this->insert_item_before($before_key,$item);
	}

	public function move_top($ofs)
	{
		$item = $this->free_item($ofs);

		if ( $item->fake )
			return $item;

		return $this->insert_item_before($this->top,$item);
	}

	public function move_bottom($ofs)
	{
		$item = $this->free_item($ofs);

		if ( $item->fake )
			return $item;

		return $this->insert_item_after($this->bottom,$item);
	}

	// * Move an element by $distance
	public function move_distance($ofs,$distance=1)
	{
		$item = $this->offset($ofs);

		if ( $item->fake || $distance == 0 )
			return $item;

		$item2 = $this->lookup($ofs,$distance);

		if ( $item2->fake )
			return $item;

		$item = $this->free_item($ofs);
		if ( $distance > 0 )
			return $this->insert_item_after($item2,$item);
		else
			return $this->insert_item_before($item2,$item);
	}
	// ------------------------------------------------------------------------
//	public function group($name)
//	{
//		if ( !isset($this->groups[$name]) )
//			$this->groups[$name] = new DoublyLinkedGroup($this,$name);
//
//		return $this->groups[$name];
//	}

	// ------------------------------------------------------------------------
	public function group_move_after($group_name,$after_key)
	{
		if ( !isset($this->groups[$group_name]) )
			return $this;

		$prev = null;
		$n=0;
		foreach ( $this->groups[$group_name] as $item_key )
		{
			if ( $n === 0 )
			{
				$this->move_after($item_key,$after_key);
			}
			else
			{
				$this->move_after($item_key,$prev);
			}
			$prev = $item_key;
			$n++;
		}
		return $this;
	}

	public function group_move_before($group_name,$before_key)
	{
		if ( !isset($this->groups[$group_name]) )
			return $this;
		foreach ( $this->groups[$group_name] as $item_key )
		{
			$this->move_before($item_key,$before_key);
		}
		return $this;
	}

	public function group_move_top($group_name)
	{
		return $this->group_move_before($group_name,$this->top_key());
	}

	public function group_move_bottom($group_name)
	{
		return $this->group_move_after($group_name,$this->bottom_key());
	}

	// ------------------------------------------------------------------------
	public function group_set(DoublyLinkedElement &$elem,$group_name=null)
	{
		$ofs = $elem->key;

		if ( $this->offsetExists($ofs) )
		{
			// $ofs = $this->current;
			if ( $group_name !== null )
				$elem($this)->group = $group_name;
			else
				$group_name = $elem->group;

			if ( !isset($this->groups[$group_name]) || !in_array($ofs,$this->groups[$group_name]) )
			{
				$this->groups[$group_name][] = $ofs;
			}

			return $elem;
		}
		return $this->null_elem();
	}

	// ------------------------------------------------------------------------
	public function group_unset(DoublyLinkedElement &$elem,$group_name=null)
	{
		$ofs = $elem->key;

		if ( $this->offsetExists($ofs) )
		{

			if ( $group_name === null )
				$group_name = $elem->group;

			$elem($this)->group = null;

			if ( ($idx = array_search($elem->key,$this->groups[$group_name])) !== false )
				unset($this->groups[$group_name][$idx]);

			return $elem;
		}

		return $this->null_elem();
	}

	// ------------------------------------------------------------------------
	// * Remove elements from group
	public function drop_group($group_name)
	{
		if ( !isset($this->groups[$group_name]) )
			return false;

		foreach ( $this->groups[$group_name] as $ofs )
		{
			$this->list[$ofs]($this)->group = null;
		}

		unset($this->groups[$group_name]);
		return true;
	}

	// ------------------------------------------------------------------------
	public function group_sort($group_name,$cb=null,$sort_method = null)
	{
		if ( !isset($this->groups[$group_name]) )
			return $this;

		if ( \is_null($sort_method) )
			$sort_method = \SORT_NATURAL|\SORT_ASC;

		if ( \is_null($cb) )
		{
			sort($this->groups[$group_name],$sort_method);
		}
		else
		{
			usort($this->groups[$group_name],$cb);
		}
		return $this;
	}
	// ------------------------------------------------------------------------
	// * Erase all elements from list with specific group name
	public function group_erase($group_name)
	{
		if ( !isset($this->groups[$group_name]) )
			return $this;

		foreach ( $this->groups[$group_name] as $item_key )
		{
			$this->erase($item_key);
		}

		$this->drop_group($group_name);
		return $this;
	}

	public function __invoke($ofs)
	{
		return $this->item($ofs);
	}

	// * Find the distance between two offsets
	// Helpful for checking item order
	public function distance($ofs,$ofs2,$seekway=self::SEEK_BOTH)
	{
		$item2 = &$this->offset($ofs2);
		if ( $item2->fake )
			return false;

		if ( $seekway & self::SEEK_BEFORE )
		{
			$item  = &$this->offset($ofs);
			$shift = 0;
			while ( $item->prev !== null )
			{
				if ( $item->key === $item2->key )
					return $shift;
				$item = &$this->offset($item->prev);
				$shift--;
			}
		}
		if ( $seekway & self::SEEK_AFTER )
		{
			$item  = &$this->offset($ofs);
			$shift = 0;
			while ( $item->next !== null )
			{
				if ( $item->key === $item2->key )
					return $shift;
				$item = &$this->offset($item->next);
				$shift++;
			}
		}
		return false;
	}

	// * Look for n-th sibling to $ofs moving forward ($distance>0) or backward ($distance<0)
	public function lookup($ofs,$distance=1)
	{
		$item = &$this->offset($ofs);

		if ( $item->fake )
			return $item;

		if ( $distance < 0)
			$move = 1;
		else
			$move = -1;

		while ( $distance && $item->key !== null )
		{
			$item = &$this->offset($item->{$move>0?'prev':'next'});
			$distance += $move;
		}

		return $item;
	}
}

// ================================================================================================
// * DoublyLinkedElement
// ================================================================================================

class DoublyLinkedElement
{
	private $owner;
	private $key;
	private $next;
	private $prev;
	public $value;
	private $group;
	private $fake = false;
	private $allow_set = false;
	private $insert_shift_left  = 0;
	private $insert_shift_right = 0;

	public function __construct(DoublyLinked $owner,$init_values=null)
	{
		$this->owner = $owner;
		if ( $init_values !== null )
			foreach ( $init_values as $k=>$v )
			{
				$this->{$k} = $v;
			}
	}

	public function value()
	{
		return $this->value;
	}

	public function group_set($group_name)
	{
		$this->group = $group_name;
		return $this->owner->group_set($this,$group_name);
	}

	public function move_before($ofs)
	{
		return $this->owner->move_before($ofs,$this->key);
	}

	public function move_after($ofs)
	{
		return $this->owner->move_after($ofs,$this->key);
	}

	public function move_top()
	{
		return $this->owner->move_before($this->owner->top_key(),$this->key);
	}

	public function move_bottom()
	{
		return $this->owner->move_after($this->owner->bottom_key(),$this->key);
	}

	public function insert_after($key,$value)
	{
		return $this->owner->insert_after($this->key,$key,$value);
	}

	public function insert_before($key,$value)
	{
		return $this->owner->insert_before($this->key,$key,$value);
	}

	public function is_free()
	{
		return is_null($this->prev) && is_null($this->next);
	}

	public function __set($p,$v)
	{
		if ( $this->allow_set )
			$this->{$p} = $v;
		$this->allow_set = false;
	}

	public function set_value(DoublyLinked $owner,$key,$value)
	{
		if ( $this->owner !== $owner )
			return $this;
		$this->$key = $value;
		return $this;
	}

	// public function __call($m,$a)
	// {
	// 	if ( is_a($a[0],"DoublyLinked") )
	// 		$this->allow_set = true;
	// 	return $this;
	// }

	public function __get($p)
	{
		return $this->$p;
	}

	public function __invoke(DoublyLinked $owner)
	{
		$this->allow_set = true;
		return $this;
	}

	public function insert_shift_left(DoublyLinked &$owner,$dif_left=null)
	{
		if ( is_null($dif_left) )
		{
			return $this->insert_shift_left;
		}
		$this->insert_shift_left += $dif_left;
	}

	public function insert_shift_right(DoublyLinked &$owner,$dif_right=null)
	{
		if ( is_null($dif_right) )
		{
			return $this->insert_shift_right;
		}
		$this->insert_shift_right += $dif_right;

	}

	// * Check is it before $ofs
	public function is_before($ofs)
	{
		return $this->owner->distance($this->key,$ofs) > 0;
	}

	// * Check is it after $ofs
	public function is_after($ofs)
	{
		return $this->owner->distance($this->key,$ofs) < 0;
	}

	// * Lookup other item by $distance from current
	public function lookup($distance=1)
	{
		return $this->owner->lookup($this->key,$distance);
	}

	// * Move item by $distance
	public function move_distance($distance=1)
	{
		return $this->owner->move_distance($this->key,$distance);
	}
}

// ================================================================================================
// * DoublyLinkedGroup
// ================================================================================================

class DoublyLinkedGroup implements \ArrayAccess, \Countable
{
	private $items = [];
	private $owner;
	private $name;

	public function __construct(DoublyLinked &$owner, $name)
	{
		$this->owner = &$owner;
		$this->name = $name;
	}

	public function sort($cb=null)
	{
		if ( $cb !== null )
		{
			usort($this->items,$cb);
		}
		else
		{
			sort($this->items,SORT_NATURAL);
		}
		return $this;
	}

	public function move_before($before_key)
	{
		foreach ( $this->items as $n=>$item_key )
		{
			$this->owner->move_before($item_key,$before_key);
		}
		return $this;
	}

	public function move_top()
	{
		return $this->move_before($this->owner->top_key());
	}

	public function move_after($after_key)
	{
		foreach ( $this->items as $n=>$item_key )
		{
			$this->owner->move_after($item_key,$after_key);
		}
		return $this;
	}

	public function move_bottom()
	{
		return $this->move_after($this->owner->bottom_key());
	}

	public function clear()
	{
		$this->items = [];
		return $this;
	}

	public function owner()
	{
		return $this->owner;
	}

	public function name($new_name=null)
	{
		return $this->name;
	}

	public function count()
	{
		return \count($this->items);
	}

	public function has($ofs)
	{
		return \array_search($ofs,$this->items);
	}

	// * Return n-th element of group or
	public function offsetGet($ofs)
	{
		if ( \is_int($ofs) || is_numeric($ofs) )
		{
			settype($ofs,"int");
			if ( isset($this->items[$ofs]) )
				return $this->owner->item($this->items[$ofs]);
		}
		return $this->owner->item(null);
	}

	public function offsetSet($ofs,$item_key)
	{
//		if ( \is_object($item_key) && \is_a($item_key,"DoublyLinkedElement") )
//		{
//
//		}

		if ( isset($this->owner[$item_key]) )
			array_splice($this->items,$ofs,0,$item_key);
	}

	// * remove n-th element
	public function offsetUnset($ofs)
	{
		if  ($this->offsetExists($ofs) )
			array_splice($this->items,$ofs,1);
	}

	// * Check the n-th element exists
	public function offsetExists($ofs)
	{
		return isset($this->items[$ofs]);
	}

	public function item($ofs)
	{
		if ( $r=$this->has($ofs) )
			return $this->owner->item($this->items[$r]);
	}
}
