Nette Translator (c) Patrik Votoček (Vrtak-CZ), 2010 (http://patrik.votocek.cz)


Note
========
This is short manual how to use Nette Translator in the newest Nette 2.0 in its most simple version.
No need to edit or operate with .po/.mo files required. Written 2012-02-10.

Actual info/manual: http://wiki.nette.org/cs/cookbook/zprovozneni-prekladace-nettetranslator


### 1. Enable Translator

config.neon:

	common:
		parameters:
			lang: cs # default language

		services:
			translator:
				factory: NetteTranslator\Gettext::getTranslator
				setup:
					- addFile(%appDir%/lang, front) # at leas one file required
					- NetteTranslator\Panel::register # panel to debug bar


### 2. Use in templates

default.latte:

	{_"Dog"}
	{_"Cat", $number} // for plural, default are Czech plurals: 1, 2-4, 5+


### 3. Use in forms

MyPreseneter.php:	

	createComponentMyForm ()
	{
		$form = new Form;
		// ...

		$form->setTranslator($this->context->translator);
	}
