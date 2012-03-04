<?php
/*
 * Copyright (c) 2010 Patrik Votoček <patrik@votocek.cz>
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 */
namespace NetteTranslator;

require_once __DIR__ . '/shortcuts.php';

use Nette,
	Nette\Utils\Strings;

/**
 * Gettext translator.
 * This solution is partitionaly based on Zend_Translate_Adapter_Gettext (c) Zend Technologies USA Inc. (http://www.zend.com), new BSD license
 *
 * @author     Roman Sklenář
 * @author	   Miroslav Smetana
 * @author	   Patrik Votoček <patrik@votocek.cz>
 * @author	   Vaclav Vrbka <gmvasek@php-info.cz>
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nettephp.com/gettext-translator
 * @package    NetteTranslator\Gettext
 * @version    0.5
 *
 * @todo refactor (according to Nella Project by Vrtak-CZ)
 */
class Gettext extends Nette\Object implements IEditable
{
	const SESSION_NAMESPACE = 'NetteTranslator-Gettext';
	const CACHE_ENABLE = TRUE;
	const CACHE_DISABLE = FALSE;

	/** @var array */
	protected $files = array();

	/** @var string */
	protected $lang = "en";

	/** @var array */
	private $metadata;

	/** @var array<string|array> */
	protected $dictionary = array();

	/** @var bool */
	private $loaded = FALSE;

	/** @var bool */
	public static $cacheMode = self::CACHE_DISABLE;

	/** @var Nette\DI\IContainer */
	protected $container;

	/** @var Nette\Http\Session */
	protected $session;

	/** @var Nette\Caching\Cache */
	protected $cache;


	/**
	 * Constructor
	 *
	 * @param array $files
	 * @param string $lang
	 */
	public function __construct(Nette\DI\IContainer $container, array $files = NULL, $lang = NULL)
	{
		$this->container = $container;
		$this->session = $storage = $container->session->getSection(static::SESSION_NAMESPACE);
		$this->cache = new Nette\Caching\Cache($container->cacheStorage, static::SESSION_NAMESPACE);

		if (count($files) > 0) {
			foreach($files as $identifier => $dir) {
				$this->addFile($dir, $identifier);
			}
		}

		if(empty($lang))
			$lang = $container->params['lang'];
		$this->lang = $lang;
		if (empty($this->lang))
			throw new Nette\InvalidStateException('Language must be defined.');

		if(!isset($storage->newStrings) || !is_array($storage->newStrings))
				$storage->newStrings = array();
	}


	/**
	 * Adds a file to parse
	 * @param string
	 * @param string
	 * @return void
	 */
	public function addFile($dir, $identifier)
	{
		if(strpos($dir, '%') !== FALSE)
			$dir = $this->container->expand($dir);

		if(isset($this->files[$identifier]))
			throw new \InvalidArgumentException("Language file identified '$identifier' is already registered.");


		if(is_dir($dir))
			$this->files[$identifier] = $dir;
		else
			throw new \InvalidArgumentException("Directory '$dir' doesn't exist.");
	}


	/**
	 * Load data
	 */
	protected function loadDictonary()
	{
		if (!$this->loaded) {
			if(empty($this->files))
				throw new Nette\InvalidStateException("Language file(s) must be defined.");

			$cache = $this->cache;
			if (static::$cacheMode && isset($cache['dictionary-'.$this->lang]))
				$this->dictionary = $cache['dictionary-'.$this->lang];
			else {
				$files = array();
				foreach ($this->files as $identifier => $dir) {
					$path = "$dir/$this->lang.$identifier.mo";
					if (file_exists($path)) {
						$this->parseFile($path, $identifier);
						$files[] = $path;
					}
				}

				if (static::$cacheMode) {
					$cache->save('dictionary-'.$this->lang, $this->dictionary, array(
						'expire' => time() * 60 * 60 * 2,
						'files' => $files,
						'tags' => array('dictionary-'.$this->lang)
					));
				}
			}
			$this->loaded = TRUE;
		}
	}

	/**
	 * Parse dictionary file
	 *
	 * @param string $file file path
	 */
	protected function parseFile($file, $identifier)
	{
		$f = @fopen($file, 'rb');
		if (@filesize($file) < 10)
			\InvalidArgumentException("'$file' is not a gettext file.");

		$endian = FALSE;
		$read = function($bytes) use ($f, $endian)
		{
			$data = fread($f, 4 * $bytes);
			return $endian === FALSE ? unpack('V'.$bytes, $data) : unpack('N'.$bytes, $data);
		};

		$input = $read(1);
		if (Strings::lower(substr(dechex($input[1]), -8)) == "950412de")
			$endian = FALSE;
		elseif (Strings::lower(substr(dechex($input[1]), -8)) == "de120495")
			$endian = TRUE;
		else
			throw new \InvalidArgumentException("'$file' is not a gettext file.");

		$input = $read(1);

		$input = $read(1);
		$total = $input[1];

		$input = $read(1);
		$originalOffset = $input[1];

		$input = $read(1);
		$translationOffset = $input[1];

		fseek($f, $originalOffset);
		$orignalTmp = $read(2 * $total);
		fseek($f, $translationOffset);
		$translationTmp = $read(2 * $total);

		for ($i = 0; $i < $total; ++$i) {
			if ($orignalTmp[$i * 2 + 1] != 0) {
				fseek($f, $orignalTmp[$i * 2 + 2]);
				$original = @fread($f, $orignalTmp[$i * 2 + 1]);
			} else
				$original = "";

			if ($translationTmp[$i * 2 + 1] != 0) {
				fseek($f, $translationTmp[$i * 2 + 2]);
				$translation = fread($f, $translationTmp[$i * 2 + 1]);
				if ($original === "") {
					$this->parseMetadata($translation, $identifier);
					continue;
				}

				$original = explode(Strings::chr(0x00), $original);
				$translation = explode(Strings::chr(0x00), $translation);
				$this->dictionary[is_array($original) ? $original[0] : $original]['original'] = $original;
				$this->dictionary[is_array($original) ? $original[0] : $original]['translation'] = $translation;
				$this->dictionary[is_array($original) ? $original[0] : $original]['file'] = $identifier;
			}
		}
	}

	/**
	 * Metadata parser
	 *
	 * @param string $input
	 */
	private function parseMetadata($input, $identifier)
	{
		$input = trim($input);

		$input = preg_split('/[\n,]+/', $input);
		foreach ($input as $metadata) {
			$pattern = ': ';
			$tmp = preg_split("($pattern)", $metadata);
			$this->metadata[$identifier][trim($tmp[0])] = count($tmp) > 2 ? ltrim(strstr($metadata, $pattern), $pattern) : $tmp[1];
		}
	}

	/**
	 * Translates the given string.
	 *
	 * @param string $message
	 * @param int $form plural form (positive number)
	 * @return string
	 */
	public function translate($message, $form = 1)
	{
		$this->loadDictonary();
		$files = array_keys($this->files);

		$message = (string) $message;
		$message_plural = NULL;
		if (is_array($form) && $form !== NULL) {
			$message_plural = current($form);
			$form = (int) end($form);
		}
		if (!is_int($form) || $form === NULL) {
			$form = 1;
		}

		if (!empty($message) && isset($this->dictionary[$message])) {
			$tmp = preg_replace('/([a-z]+)/', '$$1', "n=$form;".$this->metadata[$files[0]]['Plural-Forms']);
			eval($tmp);


			$message = $this->dictionary[$message]['translation'];
			if (!empty($message))
				$message = (is_array($message) && $plural !== NULL && isset($message[$plural])) ? $message[$plural] : $message;
		} else {
			if (!$this->container->httpResponse->isSent() || $this->container->session->isStarted()) {
				$space = $this->session;
				if (!isset($space->newStrings[$this->lang]))
					$space->newStrings[$this->lang] = array();
				$space->newStrings[$this->lang][$message] = empty($message_plural) ? array($message) : array($message, $message_plural);
			}
			if ($form > 1 && !empty($message_plural))
				$message = $message_plural;
		}

		if (is_array($message))
			$message = current($message);

		$args = func_get_args();
		if (count($args) > 1) {
			array_shift($args);
			if (is_array(current($args)) || current($args) === NULL)
				array_shift($args);

			if (count($args) == 1 && is_array(current($args)))
				$args = current($args);

			$message = str_replace(array("%label", "%name", "%value"), array("#label", "#name", "#value"), $message);
			if (count($args) > 0 && $args != NULL);
				$message = vsprintf($message, $args);
			$message = str_replace(array("#label", "#name", "#value"), array("%label", "%name", "%value"), $message);
		}
		return $message;
	}

	/**
	 * Get count of plural forms
	 *
	 * @return int
	 */
	public function getVariantsCount()
	{
		$this->loadDictonary();
		$files = array_keys($this->files);

		if (isset($this->metadata[$files[0]]['Plural-Forms'])) {
			return (int)substr($this->metadata[$files[0]]['Plural-Forms'], 9, 1);
		}
		return 1;
	}

	/**
	 * Get translations strings
	 *
	 * @return array
	 */
	public function getStrings($file = NULL)
	{
		$this->loadDictonary();

		$newStrings = array();
		$result = array();

		$storage = $this->session;
		if (isset($storage->newStrings[$this->lang])) {
			foreach (array_keys($storage->newStrings[$this->lang]) as $original) {
				if (trim($original) != "") {
					$newStrings[$original] = FALSE;
				}
			}
		}

		foreach ($this->dictionary as $original => $data) {
			if (trim($original) != "") {
				if($file && $data['file'] === $file) {
					$result[$original] = $data['translation'];
				} else {
					$result[$data['file']][$original] = $data['translation'];
				}
			}
		}


		if($file) {
			return array_merge($newStrings, $result);
		} else {
			foreach($this->getFiles() as $identifier => $path)
			{
				if(!isset($result[$identifier]))
					$result[$identifier] = array();
			}

			return array('newStrings' => $newStrings) + $result;
		}
	}


	/**
	 * Get loaded files
	 * @return array
	 */
	public function getFiles()
	{
		$this->loadDictonary();

		return $this->files;
	}

	/**
	 * Set translation string(s)
	 *
	 * @param string|array $message original string(s)
	 * @param string|array $string translation string(s)
	 */
	public function setTranslation($message, $string, $file)
	{
		$this->loadDictonary();

		$space = $this->session;
		if (isset($space->newStrings[$this->lang]) && array_key_exists($message, $space->newStrings[$this->lang]))
			$message = $space->newStrings[$this->lang][$message];

		$this->dictionary[is_array($message) ? $message[0] : $message]['original'] = (array) $message;
		$this->dictionary[is_array($message) ? $message[0] : $message]['translation'] = (array) $string;
		$this->dictionary[is_array($message) ? $message[0] : $message]['file'] = $file;
	}

	/**
	 * Save dictionary
	 */
	public function save($file)
	{
		if(!$this->loaded)
			throw new Nette\InvalidStateException("Nothing to save, translations are not loaded.");

		if(!isset($this->files[$file]))
			throw new \InvalidArgumentException("Gettext file identified as '$file' does not exist.");

		$dir = $this->files[$file];
		$path = "$dir/$this->lang.$file";

		$this->buildMOFile("$path.mo", $file);
		$this->buildPOFile("$path.po", $file);

		$storage = $this->session;
		if (isset($storage->newStrings[$this->lang])) {
			unset($storage->newStrings[$this->lang]);
		}
		if (static::$cacheMode) {
			$cache = $this->cache
				->clean(array(\Nette\Caching\Cache::TAGS => 'dictionary-'.$this->lang));
		}
	}

	/**
	 * Generate gettext metadata array
	 *
	 * @return array
	 */
	private function generateMetadata($identifier)
	{
		$result = array();
		if (isset($this->metadata[$identifier]['Project-Id-Version']))
			$result[] = "Project-Id-Version: ".$this->metadata[$identifier]['Project-Id-Version'];
		else
			$result[] = "Project-Id-Version: ";
		if (isset($this->metadata[$identifier]['Report-Msgid-Bugs-To']))
			$result[] = "Report-Msgid-Bugs-To: ".$this->metadata[$identifier]['Report-Msgid-Bugs-To'];
		if (isset($this->metadata[$identifier]['POT-Creation-Date']))
			$result[] = "POT-Creation-Date: ".$this->metadata[$identifier]['POT-Creation-Date'];
		else
			$result[] = "POT-Creation-Date: ";
		$result[] = "PO-Revision-Date: ".date("Y-m-d H:iO");
		if (isset($this->metadata[$identifier]['Last-Translator']))
			$result[] = "Language-Team: ".$this->metadata[$identifier]['Language-Team'];
		else
			$result[] = "Language-Team: ";
		if (isset($this->metadata[$identifier]['MIME-Version']))
			$result[] = "MIME-Version: ".$this->metadata[$identifier]['MIME-Version'];
		else
			$result[] = "MIME-Version: 1.0";
		if (isset($this->metadata[$identifier]['Content-Type']))
			$result[] = "Content-Type: ".$this->metadata[$identifier]['Content-Type'];
		else
			$result[] = "Content-Type: text/plain; charset=UTF-8";
		if (isset($this->metadata[$identifier]['Content-Transfer-Encoding']))
			$result[] = "Content-Transfer-Encoding: ".$this->metadata[$identifier]['Content-Transfer-Encoding'];
		else
			$result[] = "Content-Transfer-Encoding: 8bit";

		// creation fix - enables all 3 forms
		$result[] = "Plural-Forms: nplurals=3; plural=((n==1) ? 0 : (n>=2 && n<=4 ? 1 : 2));\n";
		/*
		if (isset($this->metadata[$identifier]['Plural-Forms']))
			$result[] = "Plural-Forms: ".$this->metadata[$identifier]['Plural-Forms'];
		else
			$result[] = "Plural-Forms: ";
		*/

		if (isset($this->metadata[$identifier]['X-Poedit-Language']))
			$result[] = "X-Poedit-Language: ".$this->metadata[$identifier]['X-Poedit-Language'];
		if (isset($this->metadata[$identifier]['X-Poedit-Country']))
			$result[] = "X-Poedit-Country: ".$this->metadata[$identifier]['X-Poedit-Country'];
		if (isset($this->metadata[$identifier]['X-Poedit-SourceCharset']))
			$result[] = "X-Poedit-SourceCharset: ".$this->metadata[$identifier]['X-Poedit-SourceCharset'];
		if (isset($this->metadata[$identifier]['X-Poedit-KeywordsList']))
			$result[] = "X-Poedit-KeywordsList: ".$this->metadata[$identifier]['X-Poedit-KeywordsList'];

		return $result;
	}

	/**
	 * Build gettext MO file
	 *
	 * @param string $file
	 */
	private function buildPOFile($file, $identifier)
	{
		$po = "# Gettext keys exported by GettextTranslator and Translation Panel\n"
			."# Created: ".date('Y-m-d H:i:s')."\n".'msgid ""'."\n".'msgstr ""'."\n";
		$po .= '"'.implode('\n"'."\n".'"', $this->generateMetadata($identifier)).'\n"'."\n\n\n";
		foreach ($this->dictionary as $message => $data) {
			if($data['file'] !== $identifier)
				continue;

			$po .= 'msgid "'.str_replace(array('"', "'"), array('\"', "\\'"), $message).'"'."\n";
			if (is_array($data['original']) && count($data['original']) > 1)
				$po .= 'msgid_plural "'.str_replace(array('"', "'"), array('\"', "\\'"), end($data['original'])).'"'."\n";
			if (!is_array($data['translation']))
				$po .= 'msgstr "'.str_replace(array('"', "'"), array('\"', "\\'"), $data['translation']).'"'."\n";
			elseif (count($data['translation']) < 2)
				$po .= 'msgstr "'.str_replace(array('"', "'"), array('\"', "\\'"), current($data['translation'])).'"'."\n";
			else {
				$i = 0;
				foreach ($data['translation'] as $string) {
					$po .= 'msgstr['.$i.'] "'.str_replace(array('"', "'"), array('\"', "\\'"), $string).'"'."\n";
					$i++;
				}
			}
			$po .= "\n";
		}

		$storage = $this->session;
		if (isset($storage->newStrings[$this->lang])) {
			foreach ($storage->newStrings[$this->lang] as $original) {
				if (trim(current($original)) != "" && !\array_key_exists(current($original), $this->dictionary)) {
					$po .= 'msgid "'.str_replace(array('"', "'"), array('\"', "\\'"), current($original)).'"'."\n";
					if (count($original) > 1)
						$po .= 'msgid_plural "'.str_replace(array('"', "'"), array('\"', "\\'"), end($original)).'"'."\n";
					$po .= "\n";
				}
			}
		}

		file_put_contents($file, $po);
	}

	/**
	 * Build gettext MO file
	 *
	 * @param string $file
	 */
	private function buildMOFile($file, $identifier)
	{
		$dictionary = array_filter($this->dictionary, function($data) use($identifier) {
			return $data['file'] === $identifier;
		});

		ksort($dictionary);

		$metadata = implode("\n", $this->generateMetadata($identifier));
		$items = count($dictionary) + 1;
		$ids = Strings::chr(0x00);
		$strings = $metadata.Strings::chr(0x00);
		$idsOffsets = array(0, 28 + $items * 16);
		$stringsOffsets = array(array(0, strlen($metadata)));

		foreach ($dictionary as $key => $value) {
			$id = $key;
			if (is_array($value['original']) && count($value['original']) > 1)
				$id .= Strings::chr(0x00).end($value['original']);

			$string = implode(Strings::chr(0x00), $value['translation']);
			$idsOffsets[] = strlen($id);
			$idsOffsets[] = strlen($ids) + 28 + $items * 16;
			$stringsOffsets[] = array(strlen($strings), strlen($string));
			$ids .= $id.Strings::chr(0x00);
			$strings .= $string.Strings::chr(0x00);
		}

		$valuesOffsets = array();
		foreach ($stringsOffsets as $offset) {
			list ($all, $one) = $offset;
			$valuesOffsets[] = $one;
			$valuesOffsets[] = $all + strlen($ids) + 28 + $items * 16;
		}
		$offsets= array_merge($idsOffsets, $valuesOffsets);

		$mo = pack('Iiiiiii', 0x950412de, 0, $items, 28, 28 + $items * 8, 0, 28 + $items * 16);
		foreach ($offsets as $offset)
			$mo .= pack('i', $offset);

		file_put_contents($file, $mo.$ids.$strings);
	}

	/**
	 * Get translator
	 *
	 * @param Nette\DI\IContainer $container
	 * @param array|Nette\ArrayHash $options
	 * @return NetteTranslator\Gettext
	 */
	public static function getTranslator(Nette\DI\IContainer $container, $options = NULL)
	{
		return new static($container, isset($options['files']) ? (array) $options['files'] : NULL);
	}


	/**
	 * Returns current language
	 */
	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * Sets a new language
	 */
	public function setLang($lang)
	{
		if($this->lang === $lang)
			return;

		$this->lang = $lang;
		$this->dictionary = array();
		$this->loaded = FALSE;

		// Lazy load
		// $this->loadDictonary();
	}
}
