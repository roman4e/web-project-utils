<?php
namespace Utils\Timing;

class Timestamp
{
	static private $times = [];
	static private $cur;
	private $points = [];
	private $prev;
	private $paused = false;
	private $disposition = 0;
	private $done = false;
	private $step = 0;

	static public $round = 5; // digits

	// * new timer
	protected function __construct()
	{
		//                          step          time          prevstep  prevtime disposition
		$this->points["@"] = [0=>[$this->step++,microtime(true),null,null,null]];
		//             name stepsnum step
		$this->prev = ["@",0,$this->step-1];
	}

	// * set checkpoint 
	protected function point($name)
	{
		if ( $this->paused !== false || $this->done !== false )
			return false;

		if ( !isset($this->points[$name]) )
		{
			$this->points[$name] = [];
		}

		$pt = &$this->points[$name];
		$pt[] = [$this->step++,microtime(true),$this->prev[0],$this->prev[1],$this->disposition];
		$this->prev = [$name,count($pt)-1,$this->step-1];
		$this->disposition = 0;
	}
	
	// * switch timer
	static public function activate($n)
	{
		if ( !isset(self::$times[$n]) )
		{
			self::$times[$n] = new self();
		}
		self::$cur = &self::$times[$n];
	}
	
	// * start working
	// start timer #0 or do nothing
	// any other timers must be activated with activate(n) 
	static public function start()
	{
		if ( !count(self::$times) )
			self::activate(0);
	}

	// * set checkpoint
	static public function checkpoint($name)
	{
		self::$cur->point($name);
	}

	// * Return raw timing data as array
	// each row is <key> => [0=>[time, prev_key, prev_idx, pause_time],1=>[...]];
	static public function get_timing($n=-1,$advance=false)
	{
		return $n < 0 ? self::$cur->points : ( isset(self::$times[$n]) ? self::$times[$n]->points : null );
	}

	// * Return raw timing data with advanced calculations as array
	// each row is <key> => [0=>[<time_data>],1=>[...]];
	// <time_data> indexes:
	// 0 := index
	// 1 := time,
	// 2 := prev_key,
	// 3 := prev_idx,
	// 4 := pause time
	// 5 := delta to previous time
	// 6 := delta to first time in group (does not include time while paused)
	// 7 := delta to previous time in group
	// 8 := key_name
	static public function get_advanced_timing($n=-1,$sort_mode=0)
	{
		$report = self::get_timing($n);

		if ( is_null($report) )
			return $report;

		foreach ( $report as $name=>&$array )
		{
			array_walk($array,function(&$item,$key) use ($array,$report,$name)
			{
				if ( !is_null($item[2]) )
				{
					$prev = $report[$item[2]][$item[3]];
					$item[5] = round($item[1] - $prev[1] - $item[4],self::$round);	// delta between previous
					$item[6] = round($item[1] - $report[$item[2]][0][1],self::$round); // delta between first in group

					if ( $key > 0 )
						$item[7] = round($item[1] - $array[$key-1][1] - $item[4],self::$round); // delta between previous in group
					else
						$item[7] = 0.0;
				}
				else
				{
					$item[5] = $item[6] = $item[7] = 0.0;
				}

				$item[8] = $name;
			});
		}


		if ( $sort_mode )
		{
			$new_rep = [];
			$prev_ptr = $n < 0 ? self::$cur->prev : self::$times[$n]->prev;
			$prev = $report[$prev_ptr[0]][$prev_ptr[1]]; // hack

			while ( $prev[2] !== null )
			{
				$new_rep[] = $prev;
				$prev = $report[$prev[2]][$prev[3]];
			}

			$new_rep[] = $prev;
			$report = array_reverse($new_rep);
		}

		return $report;
	}

	// * pause current or specific timer
	static public function pause($n=-1)
	{
		if ( $n === -1 )
		{
			self::$cur->paused = microtime(true);
		}
		else
		{
			if ( !isset(self::$times[$n]) )
				return;

			self::$times[$n]->paused = microtime(true);
		}
	}

	// * resume current or specific timer
	static public function resume($n=-1)
	{
		if ( $n === -1 && self::$cur->paused !== false )
		{
			self::$cur->disposition = microtime(true) - self::$cur->paused;
			self::$cur->paused = false;
		}
		else
		{
			if ( !isset(self::$times[$n]) )
				return;

			$t = &self::$times[$n];

			if ( $t->paused !== false )
			{
				self::$cur->disposition = microtime(true) - self::$times[$n]->paused;
				self::$times[$n]->paused = false;
			}
		}
	}

	// * stop current or specific timer
	// timer cannot be continued
	static public function stop($n=-1)
	{
		if ( $n < 0 )
			$t = &self::$cur;
		else
		{
			if ( !isset(self::$times[$n]) )
				return;
			$t = &self::$times[$n];

			if ( $t->paused )
				self::resume($n);

			$t->done = true;
		}
	}

	// * stop all timers
	static public function stop_all()
	{
		foreach ( self::$times as $key=>$t )
			self::stop($key);
	}
}

//deprecated autostart
//Timestamp::activate(0);
