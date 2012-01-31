<?php	##################
	#
	#	rah_metas-plugin for Textpattern
	#	version 1.3
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	function rah_metas($atts=array()) {
		extract(lAtts(rah_metas_atts(),$atts));

		global $is_article_list,$thisarticle;
		$out = array();
		
		$author = ($useauthor && !empty($thisarticle)) ? author(array()) : $author;
		$description = rah_metas_description($atts);
		$keywords = rah_metas_keywords($atts);
		
		if($is_article_list == true) {
			if($relprev) $prev_url = older(array(),false);
			if($relnext) $next_url = newer(array(),false);
		} else {
			if($relprev) $prev_url = link_to_prev(array(),false);
			if($relnext) $next_url = link_to_next(array(),false);
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
			if(is_numeric(gps('id'))) 
				header('Location: '.permlink(array('id' => gps('id'))),TRUE,$redirect_code);
		}
		
		return implode(n,$out);
	}

	function rah_metas_atts() {
		return 
			array(
				'language' => '',
				'keywords' => '',
				'keywords_from' => 'keywords',
				'keywords_replacement' => '',
				'keywords_limit' => '25',
				'description' => '',
				'description_from' => 'body,excerpt',
				'description_trail' => '&hellip;',
				'escape' => '',
				'maxchars' => '250',
				'words' => '25',
				'description_replacement' => '',
				'author' => '',
				'useauthor' => '',
				'robots' => '',
				'imagetoolbar' => '',
				'copyright' => '',
				'messy_to_clean_redirect' => '',
				'redirect_code' => '301',
				'relnext' => '',
				'relprev' => '',
				'prev_url' => '',
				'next_url' => '',
			)
		;
	}

	function rah_metas_keywords($atts) {
		extract(lAtts(rah_metas_atts(),$atts));
		
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

	function rah_metas_description($atts) {
		extract(lAtts(rah_metas_atts(),$atts));

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

	function rah_metas_content($string='',$replacement='',$default='') {
		
		global $thisarticle;
		
		if(empty($thisarticle))
			return $default;
		
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

	function rah_metas_trail($out=array()) {
		$content = implode(' ',$out);
		if(
			substr($content, -7, 7) == '&#8230;'
		)
			$content = substr($content,0,-7);
		return $content;
	}

	function rah_metas_strip($out='') {
		return 
			trim(
				str_replace(
					array("\n","\t",'"','>','<'),
					array(' ','','&quot;','&gt;','&lt;'),
					strip_tags(
						trim(
							parse($out)
						)
					)
				)
			)
		;
	}

	function rah_metas_textile($out='') {
		@include_once(txpath.'/lib/classTextile.php');
		$textile = new Textile();
		return $textile->TextileThis($out);
	}?>