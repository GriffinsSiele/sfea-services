<?php
// Функции для работы с xml
function xml_transform($xml,$xslt_file) {
	if ($xml && (strpos($xml,'<')!==false)) {
		$doc = new DOMDocument();
        $doc->formatOutput = true;
        try {
            $doc->loadXML(strtr($xml, array('&nbsp;' => ' ', '&ensp;' => ' ', '&emsp;' => ' ', '&ndash;' => '–', '&mdash;' => '—', '&bull;' => '•', '&deg;' => '°', '&trade;' => '™', '&copy;' => '©', '&infin;' => '∞', '&hearts;' => '♥', '' => '♥', '' => '♠', '' => '♣', '' => '♦', '' => '•', '' => '◄', '' => '►', '' => '♫', '' => '☼', '' => '↕', '' => '‼', '' => '¶', '' => '§', '' => '▬', '' => '↨', '' => '↑', '' => '↓', '' => '→', '' => '←', '' => '', '' => '↔', '' => '▲', '' => '▼', '' => '♂', '' => '♀', '' => '◘', '' => '☻', '' => '☺', '￿' => '')));
        } catch (\Throwable $e) {
            if (!$GLOBALS['app']->getContainer()->getParameter('kernel.debug')
                || !str_contains($xml, 'Symfony Exception')
            ) {
                throw $e;
            }

            http_response_code(502);
            echo $xml;
            return false;
        }
		$resdoc = doc_transform($doc,$xslt_file);
	} else {
		$resdoc = false;
	}
	return $resdoc;
}
function doc_transform($doc,$xslt_file) {
	global $xslt_dir;
	$resdoc = false;
	if ($doc) {
		$xsldoc = new DOMDocument();
		if ($xsldoc->load(($xslt_dir ? $xslt_dir : '') . $xslt_file)) {
			$xsl = new XSLTProcessor();
			$xsl->importStyleSheet($xsldoc);
			$resdoc = $xsl->transformToDoc($doc);
		}
	}
	return $resdoc;
}
function doc_import($doc,$fromdoc,$parenttag=false,$fromtag=false) {
	if ($doc)
		if ($parenttag)
			$parentnode = $doc->getElementsByTagName($parenttag)->item(0);
	else
			$parentnode = $doc->firstChild;
	if ($fromdoc) {
		if ($fromtag)
			$fromnode = $fromdoc->getElementsByTagName($fromtag)->item(0);
		else
			$fromnode = $fromdoc->firstChild;
	}
	if ($parentnode && $fromnode) {
		$node = $doc->importNode($fromnode, true);
		$parentnode->appendChild($node);
		return true;
	}
	return false;
}
function doc_tag($doc,$tag,$index=0,$new_value=false) {
	$res = false;
	if ($doc && $tag && ($node = $doc->getElementsByTagName($tag)->item($index))) {
		$res = $node->nodeValue;
		if ($new_value!==false){
			$node->nodeValue = $new_value;
		}
	}
	return $res;
}
function doc_tag_attr($doc,$tag,$attr,$index=0,$new_value=false) {
	if ($doc && $tag && $attr && ($node = $doc->getElementsByTagName($tag)->item($index))) {
		$res = $node->getAttribute($attr);
		if ($new_value!==false)
			$node->setAttribute($attr,$new_value);
	} else {
		$res = false;
	}
	return $res;
}
function node_xpath($node,$query,$index=0,$new_value=false) {
	$res = false;
	if ($node && $query) {
		$xpath = new DOMXpath($node->ownerDocument);
		$nodes = $xpath->query($query,$node);
		if ($nodes->length>0) {
			$node = $nodes->item($index);
			$res = $node->nodeValue;
			if ($new_value!==false)
				$node->nodeValue = $new_value;
		}
	}
	return $res;
}
function node_xpath_attr($node,$query,$attr,$index=0,$new_value=false) {
	$res = false;
	if ($node && $query && $attr) {
		$xpath = new DOMXpath($node->ownerDocument);
		$nodes = $xpath->query($query,$node);
		if ($nodes->length>0) {
			$node = $nodes->item($index);
			$res = $node->getAttribute($attr);
			if ($new_value!==false)
				$node->setAttribute($attr,$new_value);
		}
	}
	return $res;
}
function xml_encode($mixed,$domElement=null,$DOMDocument=null){
	if(is_null($DOMDocument)){
		$DOMDocument=new DOMDocument;
		$DOMDocument->formatOutput=true;
		xml_encode($mixed,$DOMDocument,$DOMDocument);
		return $DOMDocument->saveXML();
	}else{
		if(is_array($mixed)){
			foreach($mixed as $index=>$mixedElement){
				if(is_int($index)){
					if($index==0){
						$node=$domElement;
					}else{
						$node=$DOMDocument->createElement($domElement->tagName);
						$domElement->parentNode->appendChild($node);
					}
				}else{
					$plural=$DOMDocument->createElement($index);
					$domElement->appendChild($plural);
					$node=$plural;
					if(rtrim($index,'s')!==$index){
						$singular=$DOMDocument->createElement(rtrim($index,'s'));
						$plural->appendChild($singular);
						$node=$singular;
					}
				}
				return xml_encode($mixedElement,$node,$DOMDocument);
			}
		}else{
			$domElement->appendChild($DOMDocument->createTextNode($mixed));
		}
	}
}
