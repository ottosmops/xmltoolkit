<?php declare(strict_types=1);

namespace Ottosmops\Xmltoolkit;

class Xmltoolkit
{
    private $dom;
    private $xpath;
    private $namespaces = [];
    private $preserveFormatting = false;
    private $source = null;

    /**
     * Keep the source's XML declaration, final newline and character references when saving.
     * Restored references apply to every occurrence of the character, also where the source had it literally.
     */
    public function preserveFormatting(bool $preserve = true): void
    {
        $this->preserveFormatting = $preserve;
    }

    /**
     * Find elements by attribute value using regex.
     * @return array of DOMElement
     */
    public function findElementsByAttributeRegex(string $attributeName, string $pattern): array
    {
        $xpathExpr = sprintf('//*[@%s]', $attributeName);
        $nodes = $this->query($xpathExpr);
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
        $nodes = $this->query('//*');
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

    /**
     * Returns the XML as a string and ensures it is UTF-8 encoded. Optionally pretty-printed.
     */
    public function saveToString(bool $prettyPrint = false): string
    {
        $this->ensureLoaded();
        $this->dom->encoding = 'UTF-8';
        if ($prettyPrint) {
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = true;
        }
        return $this->restoreFormat($this->dom->saveXML());
    }

    /**
     * Save the XML document to a file and ensure it is UTF-8 encoded. Optionally pretty-printed.
     */
    public function saveToFile(string $filePath, bool $prettyPrint = false): bool
    {
        return file_put_contents($filePath, $this->saveToString($prettyPrint)) !== false;
    }

    /**
     * Find elements by attribute value.
     * @return array of DOMElement
     */
    public function findElementsByAttributeValue(string $attributeName, string $attributeValue): array
    {
        $xpathExpr = sprintf('//*[@%s=%s]', $attributeName, $this->xpathLiteral($attributeValue));
        $nodes = $this->query($xpathExpr);
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
        $xpathExpr = sprintf('//*[text()=%s]', $this->xpathLiteral($textContent));
        $nodes = $this->query($xpathExpr);
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
        $this->xpath = null;
        $this->source = null;
        $isLoaded = $this->dom->load($filePath, LIBXML_BIGLINES);
        if ($isLoaded) {
            $this->rememberFormat((string) file_get_contents($filePath));
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
        $this->xpath = null;
        $this->source = null;
        $isLoaded = $this->dom->loadXML($xmlString, LIBXML_BIGLINES);
        if ($isLoaded) {
            $this->rememberFormat($xmlString);
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
        $this->xpath = null;
        $this->source = null;
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
     * Rename a tag found by an XPath expression.
     */
    public function renameTagByXPath(string $xpathExpression, string $newTagName): void
    {
        $nodes = $this->query($xpathExpression);
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
        $nodes = $this->query($xpathExpression);

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
        $nodes = $this->query($xpathExpression);
        foreach ($nodes as $node) {
            $node->setAttribute($attributeName, $attributeValue);
        }
    }

    /**
     * Remove an attribute from a tag found by an XPath expression.
     */
    public function removeAttributeByXPath(string $xpathExpression, string $attributeName): void
    {
        $nodes = $this->query($xpathExpression);
        foreach ($nodes as $node) {
            $node->removeAttribute($attributeName);
        }
    }

    /**
     * Does an arbitrary XPath query and returns the found nodes as an array.
     */
    public function queryXPath(string $xpathExpression): array
    {
        $nodes = $this->query($xpathExpression);
        $result = [];
        foreach ($nodes as $node) {
            $result[] = $this->dom->saveXML($node);
        }
        return $result;
    }

    /**
     * Appends an HTML string as last child to the nodes found by an XPath expression.
     */
    public function appendHtmlToXPath(string $xpathExpression, string $htmlString): void
    {
        $nodes = $this->query($xpathExpression);

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
        $nodes = $this->query($xpathExpression);
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Wrap an element found by an XPath expression with a new element.
     */
    public function wrapElementByXPath(string $xpathExpression, string $wrapperTagName): void
    {
        $nodes = $this->query($xpathExpression);
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
        $nodes = $this->query($xpathExpression);

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

    /**
     * Wrap regex matches in text nodes with elements returned by a callback.
     *
     * Only text below $within is searched; text with an ancestor matching one of the $exclude
     * name tests (e.g. 'tei:bibl') is skipped. Matches spanning element boundaries are not found.
     * The callback receives the match, its text node and the match's line in the source
     * (best effort, see lineOf(); 0 if the parent element was created after loading) and returns an empty element to wrap the match in,
     * or null to leave it.
     *
     * @param callable(string, \DOMText, int): ?\DOMElement $wrapper
     * @param array<string> $exclude XPath name tests
     * @return int number of wrapped matches
     */
    public function wrapTextMatches(string $pattern, callable $wrapper, string $within = '/', array $exclude = []): int
    {
        $predicate = '';
        if ($exclude) {
            $predicate = '[' . implode(' and ', array_map(fn ($name) => "not(ancestor::$name)", $exclude)) . ']';
        }
        $textNodes = [];
        foreach ($this->query($within) as $context) {
            foreach ($this->xpath->query('.//text()' . $predicate, $context) as $textNode) {
                $textNodes[] = $textNode;
            }
        }

        $count = 0;
        foreach ($textNodes as $textNode) {
            $data = $textNode->data;
            if (!preg_match_all($pattern, $data, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $parent = $textNode->parentNode;
            $position = 0;
            foreach ($matches[0] as [$match, $offset]) {
                if ($match === '') {
                    continue;
                }
                $element = $wrapper($match, $textNode, $this->lineOf($textNode, $offset));
                if ($element === null) {
                    continue;
                }
                if ($element->ownerDocument !== $this->dom) {
                    $element = $this->dom->importNode($element, true);
                }
                if ($offset > $position) {
                    $parent->insertBefore($this->dom->createTextNode(substr($data, $position, $offset - $position)), $textNode);
                }
                $element->appendChild($this->dom->createTextNode($match));
                $parent->insertBefore($element, $textNode);
                $position = $offset + strlen($match);
                $count++;
            }
            if ($position > 0) {
                if ($position < strlen($data)) {
                    $parent->insertBefore($this->dom->createTextNode(substr($data, $position)), $textNode);
                }
                $parent->removeChild($textNode);
            }
        }
        return $count;
    }

    /**
     * Source line of an offset in a text node, counted from the parent's start tag.
     * libxml's line of a text node itself is unreliable for long text; that of an element is not.
     * Off by the line breaks inside start tags of preceding siblings; 0 if the parent was created after loading.
     */
    private function lineOf(\DOMText $textNode, int $offset): int
    {
        $line = $textNode->parentNode->getLineNo();
        if ($line === 0) {
            return 0;
        }
        for ($sibling = $textNode->parentNode->firstChild; $sibling !== $textNode; $sibling = $sibling->nextSibling) {
            $line += substr_count($this->dom->saveXML($sibling), "\n");
        }
        return $line + substr_count($textNode->data, "\n", 0, $offset);
    }

    /**
     * Run an XPath query on the loaded document.
     */
    private function query(string $xpathExpression): \DOMNodeList
    {
        $this->ensureLoaded();
        return $this->xpath->query($xpathExpression);
    }

    /**
     * Throw if no document has been loaded successfully.
     */
    private function ensureLoaded(): void
    {
        if ($this->xpath === null) {
            throw new \LogicException('No XML document loaded. Call loadFromFile(), loadFromString() or loadFromFragment() first.');
        }
    }

    /**
     * Quote a string as XPath literal, using concat() if it contains both quote types.
     */
    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        return 'concat("' . str_replace('"', '", \'"\', "', $value) . '")';
    }

    /**
     * Remember the declaration, final newline and non-ASCII character references of the source.
     */
    private function rememberFormat(string $xml): void
    {
        preg_match('/^(?:\xEF\xBB\xBF)?<\?xml[^>]*\?>/', $xml, $declaration);
        $charRefs = [];
        preg_match_all('/&#(?:x[0-9A-Fa-f]+|[0-9]+);/', $xml, $refs);
        foreach (array_unique($refs[0]) as $ref) {
            $char = html_entity_decode($ref, ENT_XML1, 'UTF-8');
            // ASCII references (&#38;, &#60; …) must stay as libxml escapes them
            if (strlen($char) > 1 && !isset($charRefs[$char])) {
                $charRefs[$char] = $ref;
            }
        }
        $this->source = [
            'declaration' => $declaration[0] ?? null,
            'finalNewline' => str_ends_with($xml, "\n"),
            'charRefs' => $charRefs,
        ];
    }

    /**
     * Apply the remembered source format to serialized XML, if preserveFormatting() is on.
     */
    private function restoreFormat(string $xml): string
    {
        if (!$this->preserveFormatting || $this->source === null) {
            return $xml;
        }
        $xml = preg_replace('/^<\?xml[^>]*\?>\n?/', $this->source['declaration'] === null ? '' : $this->source['declaration'] . "\n", $xml, 1);
        if (!$this->source['finalNewline']) {
            $xml = rtrim($xml, "\n");
        }
        return strtr($xml, $this->source['charRefs']);
    }
}
