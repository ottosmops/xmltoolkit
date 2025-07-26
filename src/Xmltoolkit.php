<?php declare(strict_types=1);

namespace Ottosmops\Xmltoolkit;

class Xmltoolkit
{
    private $dom;
    private $xpath;
    private $namespaces = [];
    /**
     * Find elements by attribute value using regex.
     * @return array of DOMElement
     */
    public function findElementsByAttributeRegex(string $attributeName, string $pattern): array
    {
        $xpathExpr = sprintf('//*[@%s]', $attributeName);
        $nodes = $this->xpath->query($xpathExpr);
        $result = [];
        foreach ($nodes as $node) {
            $value = $node->getAttribute($attributeName);
            if (preg_match($pattern, $value)) {
                $result[] = $node;
            }
        }
        return $result;
    }

    /**
     * Replace attribute value using regex.
     */
    public function replaceAttributeValueRegex(string $attributeName, string $pattern, string $replacement): void
    {
        $nodes = $this->findElementsByAttributeRegex($attributeName, $pattern);
        foreach ($nodes as $node) {
            $value = $node->getAttribute($attributeName);
            $newValue = preg_replace($pattern, $replacement, $value);
            $node->setAttribute($attributeName, $newValue);
        }
    }

    /**
     * Find elements by text content using regex.
     * @return array of DOMElement
     */
    public function findElementsByTextRegex(string $pattern): array
    {
        $nodes = $this->xpath->query('//*');
        $result = [];
        foreach ($nodes as $node) {
            $hasElementChild = false;
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $hasElementChild = true;
                    break;
                }
            }
            if (!$hasElementChild && preg_match($pattern, $node->textContent)) {
                $result[] = $node;
            }
        }
        return $result;
    }

    /**
     * Replace text content using regex.
     */
    public function replaceElementTextRegex(string $pattern, string $replacement): void
    {
        $nodes = $this->findElementsByTextRegex($pattern);
        foreach ($nodes as $node) {
            $node->nodeValue = preg_replace($pattern, $replacement, $node->nodeValue);
        }
    }
// ...existing code...
    /**
     * Returns the XML as a string and ensures it is UTF-8 encoded. Optionally pretty-printed.
     */
    public function saveToString(bool $prettyPrint = false): string
    {
        $this->dom->encoding = 'UTF-8';
        if ($prettyPrint) {
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = true;
        }
        return $this->dom->saveXML();
    }

    /**
     * Save the XML document to a file and ensure it is UTF-8 encoded. Optionally pretty-printed.
     */
    public function saveToFile(string $filePath, bool $prettyPrint = false): bool
    {
        $this->dom->encoding = 'UTF-8';
        if ($prettyPrint) {
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = true;
        }
        return (bool) $this->dom->save($filePath);
    }

    /**
     * Find elements by attribute value.
     * @return array of DOMElement
     */
    public function findElementsByAttributeValue(string $attributeName, string $attributeValue): array
    {
        $xpathExpr = sprintf('//*[@%s="%s"]', $attributeName, $attributeValue);
        $nodes = $this->xpath->query($xpathExpr);
        $result = [];
        foreach ($nodes as $node) {
            $result[] = $node;
        }
        return $result;
    }

    /**
     * Replace attribute value for all elements with a given attribute value.
     */
    public function replaceAttributeValue(string $attributeName, string $oldValue, string $newValue): void
    {
        $nodes = $this->findElementsByAttributeValue($attributeName, $oldValue);
        foreach ($nodes as $node) {
            $node->setAttribute($attributeName, $newValue);
        }
    }

    /**
     * Find elements by text content.
     * @return array of DOMElement
     */
    public function findElementsByTextContent(string $textContent): array
    {
        $xpathExpr = sprintf('//*[text()="%s"]', $textContent);
        $nodes = $this->xpath->query($xpathExpr);
        $result = [];
        foreach ($nodes as $node) {
            $result[] = $node;
        }
        return $result;
    }

    /**
     * Replace text content for all elements with a given text content.
     */
    public function replaceElementTextContent(string $oldText, string $newText): void
    {
        $nodes = $this->findElementsByTextContent($oldText);
        foreach ($nodes as $node) {
            $node->nodeValue = $newText;
        }
    }


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

    // ...existing code...

    /**
     * Rename a tag found by an XPath expression.
     */
    public function renameTagByXPath(string $xpathExpression, string $newTagName): void
    {
        $nodes = $this->xpath->query($xpathExpression);
        foreach ($nodes as $node) {
            $newElement = $this->dom->createElement($newTagName);

            // Copy all attributes
            foreach ($node->attributes as $attribute) {
                $newElement->setAttribute($attribute->name, $attribute->value);
            }
            // Copy all child nodes
            while ($node->firstChild) {
                $newElement->appendChild($node->firstChild);
            }
            // Replace old element with new
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
            // Create fragment for HTML string
            $fragment = $this->dom->createDocumentFragment();
            $fragment->appendXML($htmlString);
            // Append fragment to current XML document
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
