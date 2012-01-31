<?php	##################
	#
	#	rah_metas-plugin for Textpattern
	#	version 1.4
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	function rah_metas($atts=array()) {
		
		global $is_article_list,$thisarticle;
		
		$atts = 
			lAtts(array(
				'keywords' => '',
				'keywords_from' => 'keywords',
				'keywords_replacement' => '',
				'keywords_limit' => '25',
				'description' => '',
				'description_from' => 'body,excerpt',
				'description_trail' => '&hellip;',
				'description_replacement' => '',
				'escape' => '',
				'maxchars' => '250',
				'words' => '25',
				'author' => '',
				'useauthor' => '',
				'robots' => '',
				'imagetoolbar' => '',
				'copyright' => '',
				'language' => '',
				'messy_to_clean_redirect' => '',
				'redirect_code' => '301',
				'relnext' => '',
				'relprev' => '',
				'prev_url' => '',
				'next_url' => '',
			),$atts)
		;

		extract($atts);
		
		$out = array();
		
		$author = ($useauthor && !empty($thisarticle)) ? author(array()) : $author;
		$description = rah_metas_description($atts);
		$keywords = rah_metas_keywords($atts);
		
		if($is_article_list == true) {
			if($relprev)
				$prev_url = older(array(),false);
			if($relnext)
				$next_url = newer(array(),false);
		} else {
			if($relprev)
				$prev_url = link_to_prev(array(),false);
			if($relnext)
				$next_url = link_to_next(array(),false);
		}
		
		if($imagetoolbar)
			$out[] = '<meta http-equiv="imagetoolbar" content="'.$imagetoolbar.'" />';
		if($language)
			$out[] = '<meta http-equiv="content-language" content="'.$language.'" />';
		if($copyright) 
			$out[] = '<meta name="copyright" content="'.$copyright.'" />';
		if($robots) 
			$out[] = '<meta name="robots" content="'.$robots.'" />';
		if($author)
			$out[] = '<meta name="author" content="'.$author.'" />';
		if($keywords) 
			$out[] = '<meta name="keywords" content="'.$keywords.'" />';
		if($description)
			$out[] = '<meta name="description" content="'.$description.'" />';
		if($prev_url)
			$out[] = '<link rel="prev" href="'.$prev_url.'" title="'.$relprev.'" />';
		if($next_url)
			$out[] = '<link rel="next" href="'.$next_url.'" title="'.$relnext.'" />';
		
		if($messy_to_clean_redirect) {
			if(gps('s')) 
				header('Location: '.pagelinkurl(array('s' => gps('s'))),TRUE,$redirect_code);
			elseif(is_numeric(gps('id'))) 
				header('Location: '.permlink(array('id' => gps('id'))),TRUE,$redirect_code);
		}
		
		return implode(n,$out);
	}

/**
	Builds keywords
*/

	function rah_metas_keywords($atts) {
		extract($atts);
		
		$out = array();
		$count = 0;
		
		$content = 
			rah_metas_content(
				$keywords_from,
				$keywords_replacement,
				$keywords
			);
		
		if($content) {
			$content = rah_metas_strip($content);
			$keywords = explode(',',$content);
			$keywords = array_unique($keywords);
			
			foreach($keywords as $keyword) {
				$keyword = trim($keyword);
				if(!empty($keyword)) {
					$count++;
					$out[] = $keyword;
				}
				if($keywords_limit <= $count)
					return implode(', ',$out);
			}
			
			$content = implode(', ',$out);
		}
		return $content;
	}

/**
	Builds description
*/

	function rah_metas_description($atts) {
		extract($atts);

		$content = 
			rah_metas_content(
				$description_from,
				$description_replacement,
				$description
			);
		
		if($content) {
			if($escape) 
				$content = rah_metas_textile($content);
			$content = rah_metas_strip($content);
			$word = array();
			$count_char = 0;
			$count_word = 0;
			$tokens = explode(' ',$content);
			foreach($tokens as $token) {
				$token = trim($token);
				if(empty($token))
					continue;
				if($count_char <= $maxchars && $count_word <= $words)
					$word[] = $token;
				else 
					return rah_metas_trail($word).$description_trail;
				$count_char = strlen($token)+$count_char+1;
				$count_word++;
			}
			$content = implode(' ',$word);
		}
		return $content;
	}

/**
	Gets the content from @$thisarticle
*/

	function rah_metas_content($string,$replacement,$default) {
		global $thisarticle;
		
		if(empty($thisarticle) || empty($string))
			return $default;
		
		$string = strtolower($string);
		
		$array = 
			explode(',',$string);
		
		foreach($array as $field) {
			$field = trim($field);
			if(!empty($field) && isset($thisarticle[$field]) && !empty($thisarticle[$field]))
				return $thisarticle[$field];
		}
		
		if($replacement)
			return $default;
	}

/**
	Removes trail (&#8230;) from the end of the string
*/

	function rah_metas_trail($out) {
		$content = implode(' ',$out);
		if(
			substr($content, -7, 7) == '&#8230;'
		)
			$content = substr($content,0,-7);
		return $content;
	}

/**
	Parses TXP markup, and strips valid HTML, invalid code, exceeding whitespace and line breaks.
*/

	function rah_metas_strip($out) {
		return 
			trim(
				str_replace(
					array("\n","\r","\t",'"','>','<'),
					array(' ',' ',' ','&quot;','&gt;','&lt;'),
					strip_tags(
						parse($out)
					)
				)
			)
		;
	}

/**
	Textiles
*/

	function rah_metas_textile($out) {
		@include_once(txpath.'/lib/classTextile.php');
		$textile = new Textile();
		return $textile->TextileThis($out);
	}