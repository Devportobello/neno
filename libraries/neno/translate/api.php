<?php

/**
 * @package     Neno
 * @subpackage  TranslateApi
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_NENO') or die;

/**
 * Class NenoTranslateApi
 *
 * @since  1.0
 */
abstract class NenoTranslateApi extends JHttp
{
	/**
	 * Method to translate content
	 *
	 * @param   string $text   text to translate
	 * @param   string $source source language
	 * @param   string $target target language
	 *
	 * @return string
	 */
	abstract public function translate($text, $source, $target);

	/**
	 * Method to make supplied language codes equivalent to translation api codes
	 *
	 * @param   string $jiso Joomla ISO language code
	 *
	 * @return string
	 */
	abstract public function convertFromJisoToIso($jiso);

	/**
	 * Method to check if language pair is available or not in translation api
	 *
	 * @param   string $isoPair    ISO2 language code pair
	 * @param   string $methodName api method name to check
	 *
	 * @return boolean
	 */
	public function isTranslationAvailable($isoPair, $methodName)
	{
		// Split the language pair using comma
		$isoParts = (explode(",", $isoPair));

		$available = 1;

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from($db->quoteName('#__neno_translation_methods_language_pairs', 'mlp'))
			->innerJoin($db->quoteName('#__neno_translation_methods', 'm') . ' ON (' . $db->quoteName('mlp.translation_method_id') . ' = ' . $db->quoteName('m.id') . ')')
			->where($db->quoteName('m.translator_name') . '=' . $db->quote($methodName))
			->where('mlp.source_language = ' . $db->quote($isoParts[0]))
			->where('mlp.destination_language = ' . $db->quote($isoParts[1]));

		$db->setQuery($query);

		$db->setQuery($query);
		$db->execute();
		$num_rows = $db->getNumRows();

		if ($num_rows == 0)
		{
			$available = 0;
		}

		return $available;
	}

	/**
	 * Method to save supported language pairs for translation apis
	 *
	 * @param   string $methodName api method name
	 *
	 * @return boolean
	 */
	protected function storeApiSupportedLanguagePairs($methodName)
	{
		$exe = 1;

		// Fetching the id for method name
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName('id'))
			->from($db->quoteName('#__neno_translation_methods'))
			->where($db->quoteName('translator_name') . '=' . $db->quote($methodName));

		$db->setQuery($query);

		$data = $db->loadObject();

		$jsonArray = json_decode($this->getSupportedLanguagePairs($methodName));
		$langArray = $jsonArray['Language Pairs'];

		foreach ($langArray as $langPair)
		{
			// Split the language code parts using hypen
			$isoParts = (explode("-", $langPair));

			// Check if row already exists
			$query = $db->getQuery(true);
			$query
				->select('*')
				->from($db->quoteName('#__neno_translation_methods_language_pairs'))
				->where($db->quoteName('translation_method_id') . '=' . $db->quote($data->id))
				->where('source_language = ' . $db->quote($isoParts[0]))
				->where('destination_language = ' . $db->quote($isoParts[1]));

			$db->setQuery($query);
			$db->execute();
			$num_rows = $db->getNumRows();

			// If row doesn't exist then insert it
			if ($num_rows == 0)
			{
				$query   = $db->getQuery(true);
				$columns = array ('translation_method_id', 'source_language', 'destination_language');
				$values  = array ($db->quote($data->id), $db->quote($isoParts[0]), $db->quote($isoParts[1]));

				$query
					->insert($db->quoteName('#__neno_translation_methods_language_pairs'))
					->columns($db->quoteName($columns))
					->values(implode(',', $values));

				$db->setQuery($query);
				$exe = $db->execute();
			}
		}

		return $exe;
	}

	/**
	 * Method to get supported language pairs for translation from our server
	 *
	 * @param   string $methodName api method name
	 *
	 * @return string JSON string
	 */
	public static function getSupportedLanguagePairs($methodName)
	{
		// Proper url is not available so function is kept commented
		/*$url = 'https://serverUrl?method='.$methodName;

		// Invoke the GET request.
		$response =JHttp::get($url);

		$text = null;

		// Log it if server response is not OK.
		if ($response->code != 200)
		{
			NenoLog::log('Call to server url failed with response: ' . $response->code, 1);
		}
		else
		{
			$text         = $response->body;
		}

		return $text;*/
	}
}
