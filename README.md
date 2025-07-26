[![Build Status](https://github.com/ottosmops/xmltoolkit/actions/workflows/ci.yaml/badge.svg)](https://github.com/ottosmops/xmltoolkit/actions)
[![License](https://poser.pugx.org/ottosmops/xmltoolkit/license)](https://packagist.org/packages/ottosmops/xmltoolkit)
[![Total Downloads](http://poser.pugx.org/ottosmops/xmltoolkit/downloads)](https://packagist.org/packages/ottosmops/xmltoolkit)


Xmltoolkit is a PHP class that provides various utilities for manipulating XML documents. It allows loading XML from files or strings, performing XPath queries (with namespace support), and modifying XML elements and attributes.

## Installation

Use Composer to install the package:

```sh
composer require ottosmops/xmltoolkit
```

## Usage

```php 
<?php
require 'vendor/autoload.php';

use Ottosmops\Xmltoolkit;

$xml = new Xmltoolkit();
$xml->loadFromFile('example.xml');
$xml->renameTagByXPath('//a', 'ref');
$xml->saveToFile('example.xml');

```

## Namespace Support

You can register XML namespaces for XPath queries:

```php
$xml = new Ottosmops\Xmltoolkit();
$xml->registerNamespaces(['ns' => 'http://example.com/ns']);
$xml->loadFromFile('example.xml');
$nodes = $xml->queryXPath('//ns:tag');
```

## Methods

Load an XML file into the DOM object and ensure it is UTF-8 encoded.

```php
public function loadFromFile(string $filePath): bool
```

Load an XML string into the DOM object and ensure it is UTF-8 encoded.

```php
public function loadFromString(string $xmlString): bool
```

Load an XML/HTML Fragment

```php
public function loadFromFragment(string $xmlFragment): bool
```

Save the XML document to a file and ensure it is UTF-8 encoded.

```php
public function saveToFile(string $filePath): bool
```

Returns the XML as a string and ensures it is UTF-8 encoded.

```php
public function saveToString(): string
```

Rename a tag found by an XPath expression.

```php
public function renameTagByXPath(string $xpathExpression, string $newTagName): 
void
```

Rename an attribute found by an XPath expression.

```php
public function renameAttributeByXPath(string $xpathExpression, string $oldAttributeName, string $newAttributeName): void
```

Add a new attribute to a tag found by an XPath expression.

```php
public function addAttributeByXPath(string $xpathExpression, string $attributeName, string $attributeValue): void
```

Remove an attribute from a tag found by an XPath expression.

```php
public function removeAttributeByXPath(string $xpathExpression, string $attributeName): void
```

Does an arbitrary XPath query and returns the found nodes as an array.

```php
public function queryXPath(string $xpathExpression): array
```

Inserts an HTML string after the nodes found by an XPath expression.

```php
public function appendHtmlToXPath(string $xpathExpression, string $htmlString): void
```

Removes an element found by an XPath expression.
```php

    public function removeElementByXPath(string $xpathExpression): void
```

Wrap an element found by an XPath expression with a new element.

```php
public function wrapElementByXPath(string $xpathExpression, string $wrapperTagName): void
```

Remove an element but keep its content (unwrap the element) based on an XPath expression.

```php
public function unwrapElementByXPath(string $xpathExpression): void
```

## Tests

Run the tests with PHPUnit:

```sh
vendor/bin/phpunit
```
