<?php declare(strict_types=1);

namespace Ottosmops\Xmltoolkit;

class Xmltoolkit
{
    private $dom;
    private $xpath;
    private $namespaces = [];
    /**
     * Register namespaces for XPath queries.
     * @param array $namespaces ['prefix' => 'namespaceURI']
     */
    public function registerNamespaces(array $namespaces): void
    {
        $this->namespaces = $namespaces;
        if ($this->xpath) {
            foreach ($namespaces as $prefix => $uri) {
                $this->xpath->registerNamespace($prefix, $uri);
            }
        }
    }

    /**
     * Load an XML file into the DOM object and ensure it is UTF-8 encoded.
     */
    public function loadFromFile(string $filePath): bool
    {
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $isLoaded = $this->dom->load($filePath);
        if ($isLoaded) {
            $this->xpath = new \DOMXPath($this->dom);
            if (!empty($this->namespaces)) {
                foreach ($this->namespaces as $prefix => $uri) {
                    $this->xpath->registerNamespace($prefix, $uri);
                }
            }
        }
        return $isLoaded;
    }

    /**
     * Load an XML string into the DOM object and ensure it is UTF-8 encoded.
     */
    public function loadFromString(string $xmlString): bool
    {
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $isLoaded = $this->dom->loadXML($xmlString);
        if ($isLoaded) {
            $this->xpath = new \DOMXPath($this->dom);
            if (!empty($this->namespaces)) {
                foreach ($this->namespaces as $prefix => $uri) {
                    $this->xpath->registerNamespace($prefix, $uri);
                }
            }
        }
        return $isLoaded;
    }

    public function loadFromFragment(string $xmlFragment): bool
    {
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->loadXML('<root></root>');

        $fragment = $this->dom->createDocumentFragment();
        $isLoaded = $fragment->appendXML($xmlFragment);

        $this->dom->documentElement->appendChild($fragment);
        
        if ($isLoaded) {
            $this->xpath = new \DOMXPath($this->dom);
            if (!empty($this->namespaces)) {
                foreach ($this->namespaces as $prefix => $uri) {
                    $this->xpath->registerNamespace($prefix, $uri);
                }
            }
        }
        return $isLoaded;
    }

    /**
     * Save the XML document to a file and ensure it is UTF-8 encoded.
     */
    public function saveToFile(string $filePath): bool
    {
        $this->dom->encoding = 'UTF-8';
        return (bool) $this->dom->save($filePath);
    }

    /**
     * Returns the XML as a string and ensures it is UTF-8 encoded.
     */
    public function saveToString(): string
    {
        $this->dom->encoding = 'UTF-8';
        return $this->dom->saveXML();
    }

    /**
     * Rename a tag found by an XPath expression.
     */
    public function renameTagByXPath(string $xpathExpression, string $newTagName): void
    {
        $nodes = $this->xpath->query($xpathExpression);
        foreach ($nodes as $node) {
            $newElement = $this->dom->createElement($newTagName);

            // Kopiere alle Attribute
            foreach ($node->attributes as $attribute) {
                $newElement->setAttribute($attribute->name, $attribute->value);
            }

            // Kopiere alle Kindknoten
            while ($node->firstChild) {
                $newElement->appendChild($node->firstChild);
            }

            // Ersetze das alte Element durch das neue
            $node->parentNode->replaceChild($newElement, $node);
        }
    }

    /**
     * Rename an attribute found by an XPath expression.
     */
    public function renameAttributeByXPath(string $xpathExpression, string $oldAttributeName, string $newAttributeName): void
    {
        $nodes = $this->xpath->query($xpathExpression);

        foreach ($nodes as $node) {
            if ($node->hasAttribute($oldAttributeName)) {
                $value = $node->getAttribute($oldAttributeName);
                $node->removeAttribute($oldAttributeName);
                $node->setAttribute($newAttributeName, $value);
            }
        }
    }

    /**
     * Add a new attribute to a tag found by an XPath expression.
     */
    public function addAttributeByXPath(string $xpathExpression, string $attributeName, string $attributeValue): void
    {
        $nodes = $this->xpath->query($xpathExpression);
        foreach ($nodes as $node) {
            $node->setAttribute($attributeName, $attributeValue);
        }
    }

    /**
     * Remove an attribute from a tag found by an XPath expression.
     */
    public function removeAttributeByXPath(string $xpathExpression, string $attributeName): void
    {
        $nodes = $this->xpath->query($xpathExpression);
        foreach ($nodes as $node) {
            $node->removeAttribute($attributeName);
        }
    }

    /**
     * Does an arbitrary XPath query and returns the found nodes as an array.
     */
    public function queryXPath(string $xpathExpression): array
    {
        $nodes = $this->xpath->query($xpathExpression);
        $result = [];
        foreach ($nodes as $node) {
            $result[] = $this->dom->saveXML($node);
        }
        return $result;
    }

    /**
     * Inserts an HTML string after the nodes found by an XPath expression.
     */
    public function appendHtmlToXPath(string $xpathExpression, string $htmlString): void
    {
        $nodes = $this->xpath->query($xpathExpression);

        foreach ($nodes as $node) {
            // Erstelle ein Fragment für den HTML-String
            $fragment = $this->dom->createDocumentFragment();
            $fragment->appendXML($htmlString);

            // Füge das Fragment in das aktuelle XML-Dokument ein
            $node->appendChild($fragment);
        }
    }

    /**
     * Removes an element found by an XPath expression.
     */
    public function removeElementByXPath(string $xpathExpression): void
    {
        $nodes = $this->xpath->query($xpathExpression);
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Wrap an element found by an XPath expression with a new element.
     */
    public function wrapElementByXPath(string $xpathExpression, string $wrapperTagName): void
    {
        $nodes = $this->xpath->query($xpathExpression);
        foreach ($nodes as $node) {
            // Create the new node that wraps the element
            $wrapper = $this->dom->createElement($wrapperTagName);

            // Copy the current element into the wrapper
            $node->parentNode->replaceChild($wrapper, $node);
            $wrapper->appendChild($node);
        }
    }

    /**
     * Remove an element but keep its content (unwrap the element) based on an XPath expression.
     */
    public function unwrapElementByXPath(string $xpathExpression): void
    {
        $nodes = $this->xpath->query($xpathExpression);

        foreach ($nodes as $node) {
            $parent = $node->parentNode;

            // Move all child nodes of the element to be removed to the parent element
            while ($node->firstChild) {
                $parent->insertBefore($node->firstChild, $node);
            }

            // Remove the empty element itself
            $parent->removeChild($node);
        }
    }
}
