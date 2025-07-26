<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Ottosmops\Xmltoolkit\Xmltoolkit;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Xmltoolkit::class)]
class XmltoolkitTest extends TestCase
{
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
