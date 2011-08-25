<?php

include_once('lib/rss_php.php');
include_once('lib/ptagcloud/ptagcloud.php');

class block_external_resources extends block_base {
	function init() {
		$this->title   = 'Related educational resources';
		$this->version = 2011052500;
	}
 
	function get_content() {
	
		if ($this->content !== null) {
			return $this->content;
		}
		
		$this->content = new stdClass;
		$this->content->footer = '<br/><em>Powered by <a href="http://objectspot.org" target="_blank">ObjectSpot</a> and <a href="http://widgetstore.role-demo.de/content/binocs" target="_blank">Binocs</a></em>';
	
		global $COURSE;
		
		if(strcmp($COURSE->summary, '') == 0) {
			$this->content->text .= 'No recommendations are available for this course.<br/>';
			return $this->content;
		}

		$cloud = new PTagCloud(3);
		$cloud->addTagsFromTitle($COURSE->fullname);
		$cloud->addTagsFromText($COURSE->summary);
		$tags = array_keys($cloud->emitCloud(false));
		
		if(count($tags) == 0) {
			$this->content->text .= 'No recommendations are available for this course.<br/>';
			return $this->content;
		}
		
		// $this->content->text .= 'Search terms: '.$tags[0].'+'.$tags[1].'+'.$tags[2].'<br/><br/>';
		
		$this->content->text .= '<strong>Google Scholar articles</strong><br/>';
		$response = $this->objectspot_search($tags[0].'+'.$tags[1].'+'.$tags[2], '8560');
		if(strcmp($response, '') == 0)
			$this->content->text .= 'No articles available<br/>';
		else
			$this->content->text .= $response;
			
		$this->content->text .= '<br/><strong>YouTube videos</strong><br/>';
		$response = $this->binocs_search($tags[0].'+'.$tags[1].'+'.$tags[2], 2, 'youtube.com');
		if(strcmp($response, '') == 0)
			$this->content->text .= 'No videos available<br/>';
		else
			$this->content->text .= $response;
		
		$this->content->text .= '<br/><strong>SlideShare presentations</strong><br/>';
		$response = $this->binocs_search($tags[0].'+'.$tags[1].'+'.$tags[2], 2, 'slideshare.net');
		if(strcmp($response, '') == 0)
			$this->content->text .= 'No presentations available<br/>';
		else
			$this->content->text .= $response;
		
		$this->content->text .= '<br/><strong>Wikipedia articles</strong><br/>';
		// $response = $this->binocs_search($tags[0], 1, 'wikipedia.com').$this->binocs_search($tags[1], 1, 'wikipedia.com');
		$response = $this->binocs_search($tags[0], 1, 'wikipedia.com');
		if(strcmp($response, '') == 0)
			$this->content->text .= 'No articles available<br/>';
		else
			$this->content->text .= $response;
		
		return $this->content;
		
	}
	
	function objectspot_search($keywords, $repository) {   
		 
		$objectspot_response = file_get_contents('http://teldev.wu-wien.ac.at/objectspot/portlet/async-search?rank=cd&q='.$keywords.'&repositories='.$repository);
		$fh = fopen('../blocks/external_resources/tmp/objectspot_response.xml', 'w');
		fwrite($fh, substr($objectspot_response, 54, -32));
		fclose($fh);
		
		$rss = new rss_php;
		$rss->load('../blocks/external_resources/tmp/objectspot_response.xml');
		$items = $rss->getItems();		
		
		// find max rank
		$rank1 = 0.0;
		foreach($items as $index => $item) {
			if($item['rank'] > $rank1)
				$rank1 = $item['rank'];
		}
		
		if($rank1 == 0.0)
			return '';
			
		// find second max rank
		$rank2 = 0.0;
		foreach($items as $index => $item) {
			if($item['rank'] > $rank2 && $item['rank'] < $rank1)
				$rank2 = $item['rank'];
		}
			
		// return results with max ranks
		$response = '';
		foreach($items as $index => $item) {
			if($item['rank'] == $rank1 || $item['rank'] == $rank2)
				$response .= '<a href="'.$item['link'].'" title="'.$item['title'].'" target="_blank">'.$item['title'].'</a><br/>';
		}	
		
		return $response;
		
	}
	
	function binocs_search($keywords, $max_results, $repository) {  
	
		$chan = new DOMDocument();
		$chan->load('http://role-demo.de/richMediaContentSearchService_v2/search/fullTextQuery?q='.$keywords.'&format=atom&max-results='.$max_results.'&repository='.$repository);
		$sheet = new DOMDocument(); 
		$sheet->load('../blocks/external_resources/lib/atom2rss.xsl');
		$processor = new XSLTProcessor();
		$processor->registerPHPFunctions();
		$processor->importStylesheet($sheet);
		$result = $processor->transformToXML($chan);
		
		$rss = new rss_php;
		$rss->loadRSS($result);
		$items = $rss->getItems();	
		
		$response = '';
		foreach($items as $index => $item) {
			if(strcmp($item['link'], '') !== 0)
				$response .= '<a href="'.$item['link'].'" title="'.$item['title'].'" target="_blank">'.$item['title'].'</a><br/>';
		}
		
		return $response;
		
	}

} 
?>