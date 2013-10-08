<?php
require_once 'vendor/autoload.php';

class SentenceMarkerTest extends PHPUnit_Framework_TestCase
{
  public function setUp()
    {
        $this->parser = new Denshoch\SentenceMarker;
        $this->fixtureDir = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';
    }
    public function testBodyText()
    {
      $this->assertTransformation('body-text');
    }
     public function testBodyBlockText()
    {
      $this->assertTransformation('body-block-text');
    }  
    public function testBodyTextBlockText()
    {
      $this->assertTransformation('body-text-block-text');
    } 
    public function testBodyTextBr()
    {
      $this->assertTransformation('body-text-br');
    } 
    public function testBlocknodes()
    {
      $this->assertTransformation('blocknodes');
    }
    public function testBodyBlockTextBr()
    {
      $this->assertTransformation('body-block-text-br');
    }
    public function testBodyBlockTextBlockText()
    {
      $this->assertTransformation('body-block-text-block-text');
    }
    public function testBodyBlockBlockText()
    {
      $this->assertTransformation('body-block-block-text');
    }
    public function testBodyTextInline()
    {
      $this->assertTransformation('body-text-inline');
    } 
    public function testBodyTextRuby()
    {
      $this->assertTransformation('body-text-ruby');
    }
    public function testComplex()
    {
      $this->assertTransformation('complex');
    }
    protected function assertTransformation($fixtureName)
    {
        $sourceFile = $fixtureName . '-before.html';
        $transformedFile = $fixtureName . '-after.html';
        $this->assertTransformedFile($transformedFile, $sourceFile);
    }

    protected function assertTransformedFile($transformedFile, $sourceFile)
    {

        $expected = $this->fixtureDir . DIRECTORY_SEPARATOR . $transformedFile;
        $actual = Denshoch\markSentenceFromFile($this->fixtureDir . DIRECTORY_SEPARATOR . $sourceFile);

        $this->assertXmlStringEqualsXmlFile($expected, $actual); 
    }
}
