<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Ottosmops\Xmltoolkit\Xmltoolkit;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Xmltoolkit::class)]
class XmltoolkitTest extends TestCase
{
// ...existing code...
// ...existing code...
    // --- Regex-Tests ---

    public function testFindElementsByAttributeRegex()
    {
        $xmlString = '<root><item type="special">A</item><item type="sp1">B</item><item type="normal">C</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $elements = $this->xmltoolkit->findElementsByAttributeRegex('type', '/^sp.*/');
        $this->assertCount(2, $elements);
        $this->assertEquals('A', $elements[0]->nodeValue);
        $this->assertEquals('B', $elements[1]->nodeValue);
    }

    public function testReplaceAttributeValueRegex()
    {
        $xmlString = '<root><item type="special">A</item><item type="sp1">B</item><item type="normal">C</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $this->xmltoolkit->replaceAttributeValueRegex('type', '/^sp.*/', 'normal');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringNotContainsString('type="special"', $result);
        $this->assertStringNotContainsString('type="sp1"', $result);
        $this->assertStringContainsString('type="normal"', $result);
    }

    public function testFindElementsByTextRegex()
    {
        $xmlString = '<root><item>foo</item><item>oldtext</item><item>oldvalue</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $elements = $this->xmltoolkit->findElementsByTextRegex('/old.*/');
        $this->assertCount(2, $elements);
        $this->assertEquals('oldtext', $elements[0]->nodeValue);
        $this->assertEquals('oldvalue', $elements[1]->nodeValue);
    }

    public function testReplaceElementTextRegex()
    {
        $xmlString = '<root><item>foo</item><item>oldtext</item><item>oldvalue</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $this->xmltoolkit->replaceElementTextRegex('/old.*/', 'newtext');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<item>newtext</item>', $result);
        $this->assertStringContainsString('<item>foo</item>', $result);
    }
    public function testSaveToStringPrettyPrint()
    {
        $xmlString = '<root><item>1</item><item>2</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $pretty = $this->xmltoolkit->saveToString(true);
        $this->assertStringContainsString("\n", $pretty, 'Pretty print should contain newlines');
    }

    public function testSaveToFilePrettyPrint()
    {
        $xmlString = '<root><item>1</item><item>2</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $filePath = __DIR__ . '/pretty.xml';
        $this->xmltoolkit->saveToFile($filePath, true);
        $content = file_get_contents($filePath);
        $this->assertStringContainsString("\n", $content, 'Pretty print file should contain newlines');
        unlink($filePath);
    }

    public function testFindElementsByAttributeValue()
    {
        $xmlString = '<root><item type="special">A</item><item type="normal">B</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $elements = $this->xmltoolkit->findElementsByAttributeValue('type', 'special');
        $this->assertCount(1, $elements);
        $this->assertEquals('item', $elements[0]->nodeName);
        $this->assertEquals('A', $elements[0]->nodeValue);
    }

    public function testReplaceAttributeValue()
    {
        $xmlString = '<root><item type="special">A</item><item type="normal">B</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $this->xmltoolkit->replaceAttributeValue('type', 'special', 'normal');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringNotContainsString('type="special"', $result);
        $this->assertStringContainsString('type="normal"', $result);
    }

    public function testFindElementsByTextContent()
    {
        $xmlString = '<root><item>foo</item><item>bar</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $elements = $this->xmltoolkit->findElementsByTextContent('foo');
        $this->assertCount(1, $elements);
        $this->assertEquals('item', $elements[0]->nodeName);
        $this->assertEquals('foo', $elements[0]->nodeValue);
    }

    public function testReplaceElementTextContent()
    {
        $xmlString = '<root><item>foo</item><item>bar</item></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $this->xmltoolkit->replaceElementTextContent('foo', 'baz');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<item>baz</item>', $result);
        $this->assertStringContainsString('<item>bar</item>', $result);
    }

    public function testQueryXpathWithNamespace()
    {
        $xmlString = '<?xml version="1.0"?><root xmlns:ns="http://example.com/ns"><ns:tag>data</ns:tag></root>';
        $this->xmltoolkit->registerNamespaces(['ns' => 'http://example.com/ns']);
        $this->xmltoolkit->loadFromString($xmlString);
        $result = $this->xmltoolkit->queryXPath('//ns:tag');
        $this->assertCount(1, $result);
        $this->assertStringContainsString('<ns:tag>data</ns:tag>', $result[0]);
    }
    private $xmltoolkit;

    protected function setUp(): void
    {
        $this->xmltoolkit = new Xmltoolkit();
    }

    public function testLoadFromFile()
    {
        $filePath = __DIR__ . '/sample.xml';
        file_put_contents($filePath, '<root><test>data</test></root>');

        $result = $this->xmltoolkit->loadFromFile($filePath);
        $this->assertTrue($result);

        unlink($filePath);
    }

    public function testLoadFromString()
    {
        $xmlString = '<root><test>data</test></root>';
        $result = $this->xmltoolkit->loadFromString($xmlString);
        $this->assertTrue($result);
    }

    public function testLoadFromFragment()
    {
        $xmlFragment = '<test>data</test>';
        $result = $this->xmltoolkit->loadFromFragment($xmlFragment);
        $this->assertTrue($result);
    }

    public function testSaveToFile()
    {
        $xmlString = '<root><test>data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $filePath = __DIR__ . '/output.xml';
        $result = $this->xmltoolkit->saveToFile($filePath);
        $this->assertTrue($result);

        $this->assertFileExists($filePath);
        unlink($filePath);
    }

    public function testSaveToString()
    {
        $xmlString = '<root><test>data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<root><test>data</test></root>', $result);
    }

    public function testRenameTagByXpath()
    {
        $xmlString = '<root><test old="value">data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $this->xmltoolkit->renameTagByXPath('//test', 'renamed');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<renamed old="value">data</renamed>', $result);
    }

    public function testRenameAttributeByXpath()
    {
        $xmlString = '<root><test old="value">data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $this->xmltoolkit->renameAttributeByXPath('//test', 'old', 'new');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<test new="value">data</test>', $result);

        $xmlString = '<root><test old="value">data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);
        $this->xmltoolkit->renameAttributeByXPath('//test2', 'old', 'new');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<root><test old="value">data</test></root>', $result);
    }

    public function testAddAttributeByXpath()
    {
        $xmlString = '<root><test>data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $this->xmltoolkit->addAttributeByXPath('//test', 'new', 'value');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<test new="value">data</test>', $result);
    }

    public function testRemoveAttributeByXpath()
    {
        $xmlString = '<root><test attr="value">data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $this->xmltoolkit->removeAttributeByXPath('//test', 'attr');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringNotContainsString('attr="value"', $result);
    }

    public function testQueryXpath()
    {
        $xmlString = '<root><test>data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $result = $this->xmltoolkit->queryXPath('//test');
        $this->assertCount(1, $result);
        $this->assertStringContainsString('<test>data</test>', $result[0]);
    }

    public function testAppendHtmlToXpath()
    {
        $xmlString = '<root><test>data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $this->xmltoolkit->appendHtmlToXPath('//test', '<added>more data</added>');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<test>data<added>more data</added></test>', $result);
    }

    public function testRemoveElementByXpath()
    {
        $xmlString = '<root><test>data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $this->xmltoolkit->removeElementByXPath('//test');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringNotContainsString('<test>data</test>', $result);
    }

    public function testWrapElementByXpath()
    {
        $xmlString = '<root><test>data</test></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $this->xmltoolkit->wrapElementByXPath('//test', 'wrapper');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<wrapper><test>data</test></wrapper>', $result);
    }

    public function testUnwrapElementByXpath()
    {
        $xmlString = '<root><wrapper><test>data</test></wrapper></root>';
        $this->xmltoolkit->loadFromString($xmlString);

        $this->xmltoolkit->unwrapElementByXpath('//wrapper');
        $result = $this->xmltoolkit->saveToString();
        $this->assertStringContainsString('<root><test>data</test></root>', $result);
    }
}
