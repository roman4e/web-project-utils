<?php

namespace Utils\FS;

// * Locker works as multiprocess mutex
// It can works as global locker (as static) or nested locker (as object)
// set_lockfile - sets global lock file
// do new Locker for the nested lock
// lock/lockf - lock files
// unlock/unlockf - unlocks file
// wait_lock/wait_lockf - wait number of attempts to lock file (each attempt costs 1 second)
// You can specify non blocking flag state in $wait argument
class Locker
{
	static protected $locks = [];
	static protected $lockfile = null;
	private $lock = null;

	static public function set_lockfile($lockfile,$fp=null)
	{
		self::$lockfile = new static($lockfile,$fp);
	}

	public function __construct($lockfile,$fp=null)
	{
		if ( is_string($lockfile) )
		{
			if ( !is_resource($fp) )
			{
				$fp = fopen($lockfile,"w");
			}
		}
		else
			throw new \Exception("Locker cannot operate with non string or resource types");

		self::$locks[$lockfile] = $this;
		$this->lock = $fp;
	}

	// * Locks global lockfile
	static public function lockf($wait = true)
	{
		return lock(self::$lockfile, $wait);
	}

	// * Locks nested lockfile
	static public function lock(Locker $l, $wait = true)
	{
		return flock($l->lock,LOCK_EX | ($wait?0:LOCK_NB) );
	}

	// * Tries to get shared lock (read-only lock)
	static public function soft_lock(Locker $l, $wait = true)
	{
		return flock($l->lock,LOCK_SH | ($wait?0:LOCK_NB));
	}

	// * Unlock global lock file
	static public function unlockf()
	{
		return flock(self::$lockfile->lock, LOCK_UN);
	}

	// * Unlock specified locker
	static public function unlock(Locker $l)
	{
		fflush($l->lock);
		$ret = flock($l->lock, LOCK_UN);
		if ( ($i = array_search($l->lock,self::$locks)) !== false )
		{
			unset(self::$locks[$i]);
		}
		return $ret;
	}


	public function __destruct()
	{
		self::unlock($this);
	}

	// * Waits number of $attempts to lock file (each attempts costs 1 second)
	// # <true> or <false> if fail
	static public function wait_lock(Locker $l,$attempts)
	{
		while ( !self::lock($l,false) && $attempts )
		{
			sleep(1);
			$attempts--;
		}
		return $attempts > 0;
	}

	// * Waits number of $attempts to global lock (each attempts costs 1 second)
	// # <true> or <false> if fail
	static public function wait_lockf($attempts)
	{
		return self::wait_lock(self::$lockfile,$attempts);
	}
}
