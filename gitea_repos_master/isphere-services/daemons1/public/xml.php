<?php

function xml_transform($xml,$xslt_file) {
	if ($xml) {
		$doc = new DOMDocument();
		$doc->loadXML($xml);
		$resdoc = doc_transform($doc,$xslt_file);
	} else {
		$resdoc = false;
	}
	return $resdoc;
}

function doc_transform($doc,$xslt_file) {
	require('config.php');
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
		if ($new_value!==false)
			$node->nodeValue = $new_value;
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

?>