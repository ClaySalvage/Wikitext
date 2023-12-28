<?php

namespace wongery\wikitext\event;

// $request->enable_super_globals();
// $root = realpath($_SERVER["DOCUMENT_ROOT"]);
// $request->disable_super_globals();
// require_once("$root/n/ParseMW.php");

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use DOMDocument;

define("WIKISTARTTAG", '<span class="wikitext">');
define("WIKIENDTAG", "</span>");
define("ALTSTARTTAG", "<span");

// function debug_to_console($data)
// {
// 	$output = $data;
// 	if (is_array($output))
// 		$output = implode(',', $output);

// 	// echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
// 	echo "<script>console.log('Debug Objects: " . json_encode($output) . "' );</script>";
// }

function debug_log($object = null, $label = null)
{
	$message = json_encode($object, JSON_PRETTY_PRINT);
	$label = "Debug" . ($label ? " ($label): " : ': ');
	echo "<script>console.log(\"$label\", $message);</script>";
}

class listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		// return ['core.text_formatter_s9e_configure_after' => 'onConfigure'];
		return [
			'core.text_formatter_s9e_configure_after' => 'configure_wikitext',
			// 'core.text_formatter_s9e_renderer_setup' => 'parse_wikitext'
			'core.text_formatter_s9e_render_after' => 'parse_wikitext'
			// 'core.text_formatter_s9e_parse_after' => 'parse_wikitext'
		];
		// return ['core.text_formatter_s9e_renderer_setup' => 'parse_wikitext'];
		// return ['core.text_formatter_s9e_parse_after' => 'parse_wikitext'];
	}

	public function configure_wikitext($event)
	{
		// echo "DAGNABBIT";
		// var_dump($event);
		// $endPoint = $event->getComposer()->getPackage()->getExtra();
		// Get the BBCode configurator
		$configurator = $event['configurator'];

		// Let's unset any existing BBCode that might already exist
		unset($configurator->BBCodes['wiki']);
		unset($configurator->tags['wiki']);

		// We're going to use a custom filter, so...
		$configurator->attributeFilters->set('#wikitext', __CLASS__ . '::parse_wikitext');
		$configurator->BBCodes->bbcodeMonkey->allowedFilters[] = 'wikitext';

		// Let's create the new BBCode
		$configurator->BBCodes->addCustom(
			// '[wiki={TEXT1;useContent;postFilter=wikitext}]{TEXT}[/wiki]',
			// '{TEXT1}'
			// '[wiki]{WIKITEXT}[/wiki]',
			// '<span class="wikitext">{WIKITEXT}</span>'
			'[wiki]{TEXT}[/wiki]',
			'<span class="wikitext">{TEXT}</span>'
		);
		$tag = $event['configurator']->tags['WIKI'];
		$tag->rules->ignoreTags();
		// echo ("GOGOGO");
		// var_dump($tag);
		// $event['configurator']->tags['wiki']->filterChain
		// ->append(array(__CLASS__, 'parse_wikitext'));
		// echo ("GUGUGU");
		// debug_log("AAAG");
		// debug_log($event);
		// var_dump($event['configurator']->tags['QUOTE']);
		// var_dump($event['configurator']->tags['WIKI']);
		// var_dump($event['configurator']->tags['WIKI']->filterChain[0]);
		// var_dump($event['configurator']->tags['WIKI']->filterChain[2]);
		// debug_log($event['configurator']->tags);
		// debug_log($event['configurator']->tags['QUOTE']);
		// debug_log($event['configurator']->tags['WIKI']);
		// debug_log($event['configurator']->tags['quote']);
		// debug_log($event['configurator']->tags['wiki']);
		// $event['configurator']->tags['WIKI']->filterChain->append([__CLASS__, 'parse_wikitext']);
		// $event['configurator']->tags['WIKI']->filterChain
		// 	->append([__CLASS__, 'parse_wikitext']);
		// $event['configurator']->tags['wiki']->filterChain
		// 	->append([__CLASS__, 'parse_wikitext']);
	}

	// static public function parse_wikitext(\s9e\TextFormatter\Parser\Tag $tag)
	// static public function parse_wikitext($value)
	// {
	// echo ("GEEGEEGEE");
	// echo ($nosuch);
	// break;
	// echo $value;
	// return '<span class="wikitext">' . $value . '</span>';
	// $configurator = $event['configurator'];
	// debug_log($tag);
	// debug_log("***********");
	// var_dump($value);
	// $tag = $configurator->tags['WIKI'];
	// $tag->setAttribute('testing', 'testing');
	// }

	public function parse_wikitext($event)
	{
		// $endpoint = "http://www.virtualwongery.com/w/api.php";
		$endpoint = "https://www.wongery.com/w/api.php";
		// I tried to get it to read this from composer.json, but no luck so far...
		// You'll have to change it manually.  Sorry.
		// You also have to set $wgEnableScaryTranscluding to true in your
		// MediaWiki LocalSettings.php file.

		// $endPoint = $event->getComposer()->getPackage()->getExtra();
		// var_dump($endPoint);
		// var_dump($event);
		// $renderer = $event['renderer']->get_renderer();
		// $parser = $event['parser']->get_parser();
		// $xml = $event['xml'];
		// var_dump($xml);
		// debug_log($event);
		// var_dump($event);
		// var_dump($event['html']);
		if (strpos($event['html'], WIKISTARTTAG) === false)
			return true;
		// $event['html'] = "WHOO";
		// return true;
		// echo ($event['html']);
		$newstring = '';
		$oldstring = $event['html'];
		// echo ($oldstring);
		while ($pos = strpos($oldstring, WIKISTARTTAG)) {
			$newstring .= substr($oldstring, 0, $pos);
			$oldstring = substr($oldstring, $pos + strlen(WIKISTARTTAG));
			echo "newstring: ";
			var_dump($newstring);
			echo "\n";
			echo "oldstring: ";
			var_dump($oldstring);
			echo "\n";
			$wikitext = "";
			$pos = strpos($oldstring, WIKIENDTAG);
			// Broken for nested spans inside wikitext, but that's not an issue... yet.
			while (
				strpos($oldstring, ALTSTARTTAG) !== false &&
				(strpos($oldstring, ALTSTARTTAG) < $pos)
			) {
				$wikitext .= substr($oldstring, 0, $pos + strlen(WIKIENDTAG));
				$oldstring = substr($oldstring, $pos + strlen(WIKIENDTAG));
				$pos = strpos($oldstring, WIKIENDTAG);
			}
			$wikitext .= substr($oldstring, 0, $pos);
			$oldstring = substr($oldstring, $pos + strlen(WIKIENDTAG));
			echo "wikitext: ";
			var_dump($wikitext);
			echo "\n";
			echo "oldstring: ";
			var_dump($oldstring);
			echo "\n";
			// $newstring .= "((" . $wikitext . "))";
			// $request->enable_super_globals();
			$newstring .= MWParse($wikitext, $endpoint);
			// $request->disable_super_globals();
		}
		echo "oldstring: ";
		var_dump($oldstring);
		echo "\n";
		$newstring .= $oldstring;

		echo "newstring: ";
		var_dump($newstring);
		echo "\n";
		$event['html'] = $newstring;
		/* const div = document.createElement('div');
		$doc = new DOMDocument();
		$doc->loadHTML($event['html']);
		foreach ($doc->getElementsByTagName("span") as $span) {
			if ($span->getAttribute("class") !== "wikitext") continue;
			echo ($doc->saveHTML($span));
			$span->textContent = 'wikiosity';
		}
		$event['html'] = $doc->saveHTML(); */



		// $data = $event->getArgument("data");
		// var_dump($data);
		// var_dump($renderer);
	}

	// public function onConfigure($event)
	// {
	// 	$configurator = $event['configurator'];
	// 	// var_dump($configurator);
	// 	debug_log($configurator);
	// 	if (!isset($configurator->BBCodes['WIKI'], $configurator->tags['WIKI'])) {
	// 		return;
	// 	}

	// 	// Declare the height and width attributes
	// 	$tag = $configurator->tags['WIKI'];
	// 	// echo "***********";
	// 	// var_dump($tag);
	// 	debug_log($tag);
	// 	// exit(1);
	// 	/*
	// 	foreach (['height', 'width'] as $attrName)
	// 	{
	// 		if (isset($tag->attributes[$attrName]))
	// 		{
	// 			continue;
	// 		}

	// 		$attribute = $tag->attributes->add($attrName);
	// 		$attribute->filterChain->append('#uint');
	// 		$attribute->required = false;
	// 	}

	// 	// Reparse the default attribute's value as a pair of dimensions
	// 	$configurator->BBCodes['IMG']->defaultAttribute = 'dimensions';
	// 	$tag->attributePreprocessors->add(
	// 		$configurator->BBCodes['IMG']->defaultAttribute,
	// 		'/^(?<width>\\d+),(?<height>\\d+)/'
	// 	);

	// 	// Preserve the ability to use the default attribute to specify the URL
	// 	$tag->attributePreprocessors->add(
	// 		$configurator->BBCodes['IMG']->defaultAttribute,
	// 		'/^(?!\\d+,\\d+)(?<src>.*)/'
	// 	);

	// 	// Update the template
	// 	$dom = $tag->template->asDOM();
	// 	foreach ($dom->query('//img') as $img)
	// 	{
	// 		$img->prependXslCopyOf('@width');
	// 		$img->prependXslCopyOf('@height');
	// 	}
	// 	$dom->saveChanges();
	// }    */
	// }
}

function MWParse($MWtext, $endPoint)
{
	$params = [
		"action" => "parse",
		"contentmodel" => "wikitext",
		"text" => $MWtext,
		"format" => "json",
	];
	// $url = $endPoint . "?" . http_build_query($params);
	$url = $endPoint;
	// echo "<p>|||";
	// var_dump($url);
	// echo "|||</p>";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// COMMENT THE FOLLOWING OUT FOR PRODUCTION VERSION
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

	$output = curl_exec($ch);
	if (curl_errno($ch)) echo "<h1>ERROR :" . curl_error($ch) . "</h1>";
	curl_close($ch);

	// echo "<p>|||";
	// var_dump($output);
	// echo "|||</p>";

	// var_dump($output);
	$parseresult = json_decode($output, true);
	// var_dump($parseresult);
	#}

	// $wgTitle = Title::newFromText('irrelevant');
	// $popts = ParserOptions::newFromAnon();
	// $wgParserFactory = getParserFactory();
	// $wgParser = $wgParserFactory->create();
	// $p_result = $wgParser->parse($MWtext, $wgTitle, $popts);
	return $parseresult["parse"]["text"]["*"];
	// echo "<p>|||";
	// var_dump($result);
	// echo "|||</p>";
	// return $p_result->getText();
}
