<?php
/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 22.02.2015
 * Time: 17:58
 */

namespace Neko\XML\Database;

use DOMNodeList;

class Model implements \Iterator
{
	/**
	 * The current element counter.
	 *
	 * @var int
	 */
	protected $intCounter = -1;

	/**
	 * Data with the current file.
	 *
	 * @var array
	 */
	protected $arrCurrent = null;

	/**
	 * @var DOMNodeList
	 */
	protected $objDomNote = null;

	/**
	 * @var Base
	 */
	protected $objDatabase = null;

	/**
	 * @param DOMNodeList $objDomeNote The note list.
	 *
	 * @param Base        $objDatabase The Database with all functions.
	 */
	public function __construct(DOMNodeList $objDomeNote, $objDatabase)
	{
		$this->objDomNote  = $objDomeNote;
		$this->objDatabase = $objDatabase;
		$this->next();
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the current element
	 *
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current()
	{
		return $this->arrCurrent;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Move forward to next element
	 *
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 */
	public function next()
	{
		$this->intCounter++;
		if ( $this->valid() )
		{
			$this->arrCurrent = $this->objDatabase->readXML(simplexml_import_dom($this->objDomNote->item($this->intCounter)));
		}
		else
		{
			$this->arrCurrent = array();
		}
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the key of the current element
	 *
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key()
	{
		return $this->valid() ? $this->intCounter : null;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Checks if current position is valid
	 *
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 *       Returns true on success or false on failure.
	 */
	public function valid()
	{
		return $this->objDomNote->length > ($this->intCounter);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Rewind the Iterator to the first element
	 *
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind()
	{
		$this->intCounter = -1;
		$this->next();
	}
}