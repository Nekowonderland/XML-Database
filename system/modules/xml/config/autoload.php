<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Xml
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'Neko',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'Neko\XML\Database\Base'  => 'system/modules/xml/XML/Database/Base.php',
	'Neko\XML\Database\File'  => 'system/modules/xml/XML/Database/File.php',
	'Neko\XML\Database\Model' => 'system/modules/xml/XML/Database/Model.php',
));
