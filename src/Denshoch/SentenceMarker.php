<?php
namespace Denshoch;

class SentenceMarker
{

    /**
    * tag names which seemed to be INLINE.
    *
    */
    private static $inlineTags = array( 
        //HTML4; see https://developer.mozilla.org/en-US/docs/HTML/Inline_elements
        "b", "big", "i", "small", "tt",
        "abbr", "acronym", "cite", "code", "dfn", "em", "kbd", "strong", "samp", "var",
        "a", "bdo", "br", "img", "map", "object", "q", "script", "span", "sub", "sup",
        "button", "input", "label", "select", "textarea",
    
        //Additional
        "font", "nobr", "strike",
        "ruby", "rb", "rp", "rt"
    
        //TODO: more html5 guys?
    );

    /**
     * tag names which seemed to be line-break.
     */
    private static $breakTags = array(
      "br"
    );

    /**
     * string which seemed to be end of sentence(full-stop).
     */
    private static $eos = "。";

    /**
     * pattern which come after full-stop at end of sentence.
     */
    private static $closingBracketsReg = "/」|』|）/";

    /** name of wrapping element*/
    private $elementName = 'span';
    
    /** class name of wrapping element*/
    private $className = 'mo';
    
    private $encoding = 'UTF-8';
    
    /** current number of nesting of inline element */
    
    public function markUp($document, $encoding) {
          $bodies = $document->documentElement->getElementsByTagName('body');
          if ($bodies->length == 0) {
              throw 'No body tag';
          }
          $this->encoding = $encoding;
          $this->handleBlockNode($bodies->item(0));
    }

    /* patterns of input nodes */
    const DISINTERESTED_NODE = 0;
    const TEXT_NODE = 1;
    const INLINE_NODE = 2;
    const BREAK_NODE = 3;
    const BLOCK_NODE = 4;

    private static function nodeTypeOf($node) {
        if ($node->nodeType == 1) {
            $localName = strtolower($node->localName);
            if (in_array(strtolower($node->localName), self::$breakTags)) {
                return self::BREAK_NODE;
            } else if (in_array($localName, self::$inlineTags)) {
                return self::INLINE_NODE;
            } else {
                return self::BLOCK_NODE;
            }
        } else if ($node->nodeType == 3) {
            return self::TEXT_NODE;
        } else {
            return self::DISINTERESTED_NODE;
        }
    }

    /**
     * @return new current node if DOM tree has mutated / $node itself otherwise
     */
    private function handleBlockNode($blockNode) {
        return $this->handleBlockNodeRec($blockNode, $blockNode->firstChild, array());
    }

    private function handleBlockNodeRec($blockNode, $startChild, $nodesToMerge) {
        if ($startChild === NULL) {
            $this->wrapAll($nodesToMerge);
            $nodesToMerge = array();
            return;
        } else {
            $nodeType = self::nodeTypeOf($startChild);
            switch ($nodeType) {
            case self::BLOCK_NODE:
                //wrap previous inline/text nodes by <span.mo>.
                $this->wrapAll($nodesToMerge);
                $nodesToMerge = array();
                //recursive apply to this block node.
                $this->handleBlockNode($startChild);
                $nextChild = $startChild->nextSibling;
                break;
            case self::BREAK_NODE:
                $this->wrapAll($nodesToMerge);
                $nodesToMerge = array();
                $nextChild = $startChild->nextSibling;
                break;
            case self::TEXT_NODE:
                //split by eos
                $mbIndex = mb_strpos($startChild->wholeText, self::$eos, 0, $this->encoding);
                if ($mbIndex === false || mb_strlen($startChild->wholeText, $this->encoding) === $mbIndex  )   {
                    $nodesToMerge[] = $startChild;
                    $nextChild = $startChild->nextSibling;
                } else {
                    $nextChar  = mb_substr($startChild->wholeText, $mbIndex + 1, 1, $this->encoding);
                    if(preg_match(self::$closingBracketsReg, $nextChar)) {
                        $newTextNode = $startChild->splitText($mbIndex + 2);
                    } else {
                        $newTextNode = $startChild->splitText($mbIndex + 1);
                    }
                    $nodesToMerge[] = $startChild;
                    $this->wrapAll($nodesToMerge);
                    $nodesToMerge = array();
                    $nextChild = $newTextNode;
                }
                break;
            case self::INLINE_NODE:
            default:
                $nodesToMerge[] = $startChild;
                $nextChild = $startChild->nextSibling;
                break;
            }
            $this->handleBlockNodeRec($blockNode, $nextChild, $nodesToMerge);
        }
    }
  
    private function wrapAll($childNodes) {
        if (count($childNodes) === 0) return NULL;
        if (self::areAllEmpty($childNodes)) return NULL;
        
        $parentNode = $childNodes[0]->parentNode;
        $wrapper = $this->createWrapper($parentNode->ownerDocument);
        $parentNode->insertBefore($wrapper, $childNodes[0]);
        foreach ($childNodes as $node) {
            $wrapper->appendChild($node);
        }
        return $wrapper;
    }

    /**
     * Test whether text is empty or consists of empty letters.
     * @param DOMText $node
     */
    private static function isEmpty($node) {
        return $node->nodeType == 3 && (!preg_match('/\S/', $node->wholeText) == 1);
    }

    private static function areAllEmpty($nodes) {
        foreach ($nodes as $node) {
            if (!self::isEmpty($node)) return false;
        }
        return true;
    }
    /**
    * create element which wraps other nodes.
    *
    * @param DOMDocument $document owner document
    * @return DOMElement new element to wrap
    */
    private function createWrapper($document) {
        $element = $document->createElement($this->elementName);
        $element->setAttribute('class', $this->className);
        return $element;
    }

}

/**
 *  main function
 *
 *  @param simple_html_dom $document document to be rewrited
 *  @return void
 */
/*
function markEverySentence($dom) { 
    //convert "meta" to HTML4
    //workaround to fix bug of DOMDocument
    $meta = $dom->find('meta[charset]', 0);
    if (!is_null($meta)) {
        $charset = $meta->getAttribute('charset');
        $meta->setAttribute('http-equiv', 'Content-Type');
        $meta->setAttribute('content', 'text/html'."; charset=$charset");
    }
    else
    {
        $charset = 'UTF-8';
    }

    //main
    $document = new \DOMDocument('1.0', $charset);
    if (!$document->loadHTML($dom->root->outertext()))
      throw 'Failed to load html';
    $obj = new SentenceMarker();
    $obj->markUp($document, $charset);
  
    //convert "meta" to HTML5
    $meta = $dom->find('meta[http-equiv]', 0);
    if (!is_null($meta)) {
      $meta->removeAttribute('http-equiv');
      $meta->removeAttribute('content');
    }
}*/

/**
 *  main function
 *
 *  @param input file
 *  @return string
 */
function markSentenceFromFile($file)
{
    $document = new \DOMDocument('1.0', 'UTF-8');
    $document->load($file);
    $obj = new SentenceMarker();
    $obj->markUp($document, 'UTF-8');
    return $document->saveXML();
}
/**
 *  main function
 *
 *  @param XML string
 *  @return string
 */
function markSentenceFromStr($str)
{
    $document = new \DOMDocument('1.0', 'UTF-8');
    $document->loadXML($str);
    $obj = new SentenceMarker();
    $obj->markUp($document, 'UTF-8');
    return $document->saveXML();
}