<?php

/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 09.04.14
 * Time: 23:09
 */

namespace Neko\XML\Database;

class File extends Base
{
	/**
	 * The base XML.
	 *
	 * @var string
	 */
	protected $strBaseXml =
		"<?xml version=\"1.0\"?>
		<files>
			<meta>
				<ai>1</ai>
				<last_add>0</last_add>
			</meta>
			<data>
			</data>
		</files>";

	/**
	 * XPath to the data.
	 *
	 * @var string
	 */
	protected $strXPathData = '/files/data';

	/**
	 * Name of the tag.
	 *
	 * @var string
	 */
	protected $strTagName = 'file';

	/**
	 * Return the basic XML.
	 *
	 * @return mixed
	 */
	public function getBaseXML()
	{
		return $this->strBaseXml;
	}

	/**
	 * Return the xpath for the data.
	 *
	 * @return mixed
	 */
	public function getXpathData()
	{
		return $this->strXPathData;
	}

	/**
	 * Get the name of the tags.
	 *
	 * @return mixed
	 */
	public function getDataTagName()
	{
		return $this->strTagName;
	}
}

