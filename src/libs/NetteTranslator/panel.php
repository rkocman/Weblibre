<?php

namespace NetteTranslator;

use Nette;

/**
 * Panel for Nette DebugBar, which enables you to translate strings
 * directly from your browser.
 *
 * @author Jan Smitka <jan@smitka.org>
 * @author Patrik Votoček <patrik@votocek.cz>
 * @author Vaclav Vrbka <gmvasek@php-info.cz>
 */
class Panel extends Nette\Object implements Nette\Diagnostics\IBarPanel
{
	const XHR_HEADER = 'X-Translation-Client';
	const SESSION_NAMESPACE = 'NetteTranslator-Panel';
	const LANGUAGE_KEY = 'X-NetteTranslator-Lang';
	const FILE_KEY = 'X-NetteTranslator-File';
	/* Layout constants */
	const LAYOUT_HORIZONTAL = 1;
	const LAYOUT_VERTICAL = 2;

	/** @var int TranslationPanel layout */
	protected $layout = self::LAYOUT_VERTICAL;

	/** @var int Height of the editor */
	protected $height = 410;

	/** @var Nette\DI\IContainer */
	protected $container;

	/** @var IEditable */
	protected $translator;

	public function __construct(Nette\DI\IContainer $container, IEditable $translator, $layout = NULL, $height = NULL)
	{
		$this->container = $container;
		$this->translator = $translator;

		if ($height !== NULL) {
			if (!is_numeric($height))
				throw new \InvalidArgumentException('Panel height has to be a numeric value.');
			$this->height = $height;
		}

		if ($layout !== NULL) {
			$this->layout = $layout;
			if ($height === NULL)
				$this->height = 500;
		}

		$this->processRequest();
	}

	/**
	 * Return's panel ID.
	 * @return string
	 */
	public function getId()
	{
		return 'translation-panel';
	}

	/**
	 * Returns the code for the panel tab.
	 * @return string
	 */
	public function getTab()
	{
		ob_start();
		require __DIR__ . '/tab.phtml';
		return ob_get_clean();
	}

	/**
	 * Returns the code for the panel itself.
	 * @return string
	 */
	public function getPanel()
	{
		$translator = $this->translator;
		$files = array_keys($translator->getFiles());
		$strings = $translator->getStrings();

		$requests = $this->container->application->requests;
		$count = count($requests);
		$presenterName = ($count > 0) ? $requests[count($requests) - 1]->presenterName : NULL;
		$module = (!$presenterName) ? : strtolower(str_replace(':', '.', ltrim(substr($presenterName, 0, -(strlen(strrchr($presenterName, ':')))), ':')));
		$activeFile = (in_array($module, $files)) ? $module : $files[0];

		if ($this->container->session->isStarted()) {
			$session = $this->container->session->getSection(static::SESSION_NAMESPACE);
			$untranslatedStack = isset($session['stack']) ? $session['stack'] : array();
			foreach ($strings as $string => $data) {
				if (!$data) {
					$untranslatedStack[$string] = FALSE;
				}
			}
			$session['stack'] = $untranslatedStack;

			foreach ($untranslatedStack as $string => $value) {
				if (!isset($strings[$string]))
					$strings[$string] = FALSE;
			}
		}

		ob_start();
		require __DIR__ . '/panel.phtml';
		return ob_get_clean();
	}

	/**
	 * Handles an incomuing request and saves the data if necessary.
	 */
	private function processRequest()
	{
		// Try starting the session
		try {
			$session = $this->container->session->getSection(self::SESSION_NAMESPACE);
		} catch (Nette\InvalidStateException $e) {
			$session = FALSE;
		}

		$request = $this->container->httpRequest;
		if ($request->isPost() && $request->isAjax() && $request->getHeader(self::XHR_HEADER)) {
			$data = json_decode(file_get_contents('php://input'));
			$translator = $this->translator;

			if ($data) {
				if ($session) {
					$stack = isset($session['stack']) ? $session['stack'] : array();
				}

				$translator->lang = $data->{self::LANGUAGE_KEY};
				$file = $data->{self::FILE_KEY};
				unset($data->{self::LANGUAGE_KEY}, $data->{self::FILE_KEY});

				foreach ($data as $string => $value) {
					$translator->setTranslation($string, $value, $file);
					if ($session && isset($stack[$string]))
						unset($stack[$string]);
				}
				$translator->save($file);

				if ($session)
					$session['stack'] = $stack;
			}
			exit;
		}
	}

	/**
	 * Return an odrdinal number suffix.
	 * @param string $count
	 * @return string
	 */
	protected function ordinalSuffix($count)
	{
		switch (substr($count, -1)) {
			case '1':
				return 'st';
			case '2':
				return 'nd';
			case '3':
				return 'rd';
			default:
				return 'th';
		}
	}

	/**
	 * Register this panel
	 *
	 * @param NetteTranslator\IEditable $translator
	 * @param int $layout
	 * @param int $height
	 */
	public static function register(Nette\DI\IContainer $container, IEditable $translator, $layout = NULL, $height = NULL)
	{
		Nette\Diagnostics\Debugger::$bar->addPanel(new static($container, $translator, $layout, $height));
	}
}
