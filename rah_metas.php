<?php

/**
 * Rah_metas plugin for Textpattern CMS
 *
 * @author Jukka Svahn
 * @date 2007-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_metas
 *
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	function rah_metas($atts=array()) {
		global $is_article_list, $thisarticle;
		
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
				'useauthor' => 0,
				'robots' => '',
				'imagetoolbar' => '',
				'copyright' => '',
				'language' => '',
				'messy_to_clean_redirect' => 0,
				'redirect_code' => '301',
				'relnext' => '',
				'relprev' => '',
				'prev_url' => '',
				'next_url' => '',
			),$atts)
		;

		extract($atts);

		$r = new rah_metas_pkg();
		$author = $useauthor && !empty($thisarticle) ? author(array()) : $author;
		$description = $r->description($atts);
		$keywords = $r->keywords($atts);
		
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

		$out = array();

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

class rah_metas_pkg {
	
	/**
	 * Builds keywords
	 * @param array $atts Tag attributes.
	 * @return string List of keywords.
	 */

	public function keywords($atts) {
		extract($atts);
		
		$out = array();
		$count = 0;
		
		$content = 
			$this->content(
				$keywords_from,
				$keywords_replacement,
				$keywords
			);
		
		if($content) {
			$content = $this->strip($content);
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
	 * Builds description
	 * @param array $atts Tag attributes.
	 * @return string Meta description.
	 */

	public function description($atts) {
		extract($atts);

		$content = 
			$this->content(
				$description_from,
				$description_replacement,
				$description
			);
		
		if($content) {
			
			/*
				Escape Textile
			*/
			
			if($escape) {
				@include_once txpath.'/lib/classTextile.php';
				$textile = new Textile();
				$content = $textile->TextileThis($content);
			}
			
			$content = $this->strip($content);
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
					return $this->trail($word).$description_trail;
				$count_char = strlen($token)+$count_char+1;
				$count_word++;
			}
			$content = implode(' ',$word);
		}
		return $content;
	}
	
	/**
	 * Gets the content from $thisarticle global variable
	 * @param string $string Property to search for.
	 * @param bool $replacement Whether fallback is used or not.
	 * @param string $default Default if requested property isn't found.
	 * @return mixed Either the $default, or requested content.
	 */

	protected function content($string,$replacement,$default) {
		global $thisarticle;
		
		if(empty($thisarticle) || empty($string))
			return $default;
		
		$string = strtolower($string);
		$array = explode(',',$string);
		
		foreach($array as $field) {
			$field = trim($field);
			if(!empty($field) && isset($thisarticle[$field]) && !empty($thisarticle[$field]))
				return $thisarticle[$field];
		}
		
		if($replacement)
			return $default;
	}

	/**
	 * Removes trail (&#8230;) from the end of the string
	 * @param string $out String to check and clean.
	 * @return string
	 */

	protected function trail($out) {
		$content = implode(' ',$out);
		if(
			substr($content, -7, 7) == '&#8230;'
		)
			$content = substr($content,0,-7);
		return $content;
	}

	/**
	 * Parses TXP markup, and strips valid HTML, invalid code, exceeding whitespace and line breaks.
	 * @param string $out String to clean.
	 * @return string
	 */

	protected function strip($out) {
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
}

?>