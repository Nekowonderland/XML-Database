<?php

/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 09.04.14
 * Time: 23:09
 */

namespace Neko\XML\Database;

use DOMDocument;
use DOMElement;
use DOMXPath;
use SimpleXMLElement;
use SimpleXMLIterator;
use XMLReader;

abstract class Base
{
	/**
	 * Path to the xml file.
	 *
	 * @var string
	 */
	protected $strPath = null;

	/**
	 * File object with the current file.
	 *
	 * @var \File
	 */
	protected $objFile = null;

	/**
	 * The XML reader.
	 *
	 * @var \XMLReader
	 */
	protected $objXMLReader = null;

	/**
	 * The XML writer for updates.
	 *
	 * @var DomDocument
	 */
	protected $objXmlWrite = null;

	/**
	 * Data with the current file.
	 *
	 * @var array
	 */
	protected $arrCurrent = null;

	/**
	 * If true skip the first rows we didn't need them.
	 *
	 * @var bool
	 */
	protected $blnFirstNext = true;

	/**
	 * Flag if we have new data.
	 *
	 * @var bool
	 */
	protected $blnHasChanged = false;

	/**
	 * The next id in the list for a new dataset.
	 *
	 * @var int
	 */
	protected $intAI = 1;

	/**
	 * @param string  $strPath   Path to the file.
	 *
	 * @param boolean $blnCreate If true don't check if we have the file.
	 *
	 * @throws \RuntimeException If the file not exists.
	 */
	public function __construct($strPath, $blnCreate)
	{
		// Check if we have the path.
		if (!$blnCreate && !file_exists(TL_ROOT . DIRECTORY_SEPARATOR . $strPath))
		{
			throw new \RuntimeException('Could not found file for reading');
		}

		// Set the base vars.
		$this->strPath = $strPath;

		// Create.
		if ($blnCreate)
		{
			$this->create();
		}

		// Open.
		$this->open();
	}

	/**
	 * Do some last things.
	 */
	public function __destruct()
	{
		$this->save();
	}

	/* ---------------------------------------------------------------------------------
	 * Abstract part.
	 */

	/**
	 * Return the basic XML.
	 *
	 * @return mixed
	 */
	abstract public function getBaseXML();

	/**
	 * Return the xpath for the data.
	 *
	 * @return mixed
	 */
	abstract public function getXpathData();

	/**
	 * Get the name of the tags.
	 *
	 * @return mixed
	 */
	abstract public function getDataTagName();

	/* ---------------------------------------------------------------------------------
	 * Open and close.
	 */

	/**
	 * Open the file.
	 */
	public function open()
	{
		// Init the reader.
		$this->objXMLReader = new XMLReader();
		$this->objXMLReader->open(TL_ROOT . DIRECTORY_SEPARATOR . $this->strPath);
		$this->readMeta();

		// Init the writer.
		$this->objXmlWrite = new DomDocument();
		// Set some vars.
		$this->objXmlWrite->preserveWhiteSpace = false;
		$this->objXmlWrite->formatOutput       = true;
		// Open the file.
		$this->objXmlWrite->load(TL_ROOT . DIRECTORY_SEPARATOR . $this->strPath);
	}

	/**
	 * Reopen the file.
	 */
	public function reopen()
	{
		$this->objXMLReader->close();
		$this->objXMLReader->open(TL_ROOT . DIRECTORY_SEPARATOR . $this->strPath);
		$this->readMeta();
	}

	/**
	 * Create a new file.
	 */
	public function create()
	{
		$this->objFile = new \File($this->strPath, false);
		$this->objFile->write($this->getBaseXML());
		$this->objFile->close();
	}

	/**
	 * Add a new data tag to the list.
	 *
	 * @param array $arrData The Data to add.
	 */
	public function addData($arrData)
	{
		// Set the flag.
		$this->blnHasChanged = true;

		// Set the next ID.
		$arrData['attributes']['id'] = $this->intAI++;

		// Grab a node.
		$xpath        = new DOMXPath($this->objXmlWrite);
		$results      = $xpath->query($this->getXpathData());
		$objFirstNode = $results->item(0);

		// Create a new, free standing node
		$objNewNode = $this->objXmlWrite->createElement($this->getDataTagName());

		// If we have a attributes array add it.
		if (isset($arrData['attributes']))
		{
			foreach ($arrData['attributes'] as $strName => $strValue)
			{
				$objNewNode->setAttribute($strName, $strValue);
			}

			unset($arrData['attributes']);
		}

		// Add sub nodes.
		$this->addNodes($this->objXmlWrite, $objNewNode, $arrData);

		// Append our new node to the node we pulled out
		$objFirstNode->appendChild($objNewNode);

		// Update the meta data.
		$this->objXmlWrite->getElementsByTagName('ai')->item(0)->nodeValue       = $this->intAI;
		$this->objXmlWrite->getElementsByTagName('last_add')->item(0)->nodeValue = time();
	}

	/**
	 * Save the writer.
	 */
	public function save()
	{
		if ($this->blnHasChanged)
		{
			$this->objXmlWrite->save(TL_ROOT . DIRECTORY_SEPARATOR . $this->strPath);
		}

		$this->blnHasChanged = false;
	}

	/* ---------------------------------------------------------------------------------
	 * Moving operations
	 */

	/**
	 * Reset the pointer to start and reset the next.
	 */
	public function rewind()
	{
		$this->objXMLReader->moveToFirstAttribute();
		$this->blnFirstNext;
	}

	/**
	 * Go to the next one and check if is it valid.
	 *
	 * @return bool
	 */
	public function next()
	{
		// Skip the first line and set the pointer at the first file.
		if ($this->blnFirstNext)
		{
			$this->blnFirstNext = false;

			// Search the data tag
			while ($this->objXMLReader->read())
			{
				if ($this->objXMLReader->localName == 'data')
				{
					if (!$this->objXMLReader->read())
					{
						return false;
					}

					break;
				}
			}
		}

		// Read each file.
		while ($this->objXMLReader->next($this->getDataTagName()))
		{
			$node             = new SimpleXmlIterator($this->objXMLReader->readOuterXML());
			$this->arrCurrent = $this->readXML($node);

			return true;
		}

		return false;
	}

	/**
	 * Get the current file.
	 *
	 * @return array
	 */
	public function current()
	{
		return $this->arrCurrent;
	}

	/* ---------------------------------------------------------------------------------
	 * Function
	 */

	/**
	 * Read the meta data from the head.
	 */
	protected function readMeta()
	{
		if ($this->objXMLReader->read() && $this->objXMLReader->read())
		{
			// Read each file.
			while ($this->objXMLReader->next('meta'))
			{
				$node    = new SimpleXmlIterator($this->objXMLReader->readOuterXML());
				$arrMeta = $this->readXML($node);

				// Read meta and set it.
				if (isset($arrMeta['ai']))
				{
					$this->intAI = intval($arrMeta['ai']);
				}

				$this->rewind();
				return;
			}
		}
	}

	/**
	 * @param SimpleXMLIterator|SimpleXmlElement $objXmlIterator
	 *
	 * @return array
	 */
	protected function readXML($objXmlIterator)
	{
		$arrReturn = array();

		if ($objXmlIterator->getName() == $this->getDataTagName())
		{
			/** @var SimpleXmlIterator $objAttribute */
			foreach ($objXmlIterator->attributes() as $objAttribute)
			{
				$strTagName                           = $objAttribute->getName();
				$arrReturn['attributes'][$strTagName] = (string)$objAttribute;
			}
		}
		/** @var SimpleXmlIterator $objXML */
		foreach ($objXmlIterator as $objXML)
		{
			$strTagName        = $objXML->getName();
			$arrReturnChildren = $this->readXML($objXML->children());

			if (empty($arrReturnChildren))
			{
				$arrReturn[$strTagName] = (string)$objXML;
			}
			else
			{
				$arrReturn[$strTagName] = $arrReturnChildren;
			}
		}

		return $arrReturn;
	}

	/**
	 * Function for adding the files to the list.
	 *
	 * @param DomDocument $objXml
	 *
	 * @param DOMElement  $objParentNode
	 *
	 * @param array       $arrData
	 */
	protected function addNodes($objXml, $objParentNode, $arrData)
	{
		foreach ($arrData as $strName => $mixData)
		{
			if (is_array($mixData))
			{
				// Create a new, free standing node.
				$objNewNode = $objXml->createElement($strName);
				// Add the sub bodes.
				$this->addNodes($objXml, $objNewNode, $mixData);
				// Append it.
				$objParentNode->appendChild($objNewNode);
			}
			else
			{
				// Create a new, free standing node.
				$objNewNode = $objXml->createElement($strName);
				// Add the text.
				$objTextNode = $objXml->createTextNode($mixData);
				// Append the text.
				$objNewNode->appendChild($objTextNode);
				// Append the node.
				$objParentNode->appendChild($objNewNode);
			}
		}
	}
}

