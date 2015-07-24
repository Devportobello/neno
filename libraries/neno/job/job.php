<?php
/**
 * @package     Neno
 * @subpackage  Job
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Class NenoJob
 *
 * @since  1.0
 */
class NenoJob extends NenoObject
{
    /**
     * Status when the job has been generated
     */
    const JOB_STATE_GENERATED = 1;

    /**
     * Status when the job has been sent to the API Server
     */
    const JOB_STATE_SENT = 2;

    /**
     * Status when the job has been completed by the API server
     */
    const JOB_STATE_COMPLETED = 3;

    /**
     * Status when the job has been processed by the component
     */
    const JOB_STATE_PROCESSED = 4;

    /**
     * Status when the job has tried to be sent but the user does not have enough translation credits
     */
    const JOB_STATE_NO_TC = 5;

    /**
     * @var array
     */
    public $translations;

    /**
     * @var integer
     */
    protected $state;

    /**
     * @var Datetime
     */
    protected $createdTime;

    /**
     * @var Datetime
     */
    protected $sentTime;

    /**
     * @var Datetime
     */
    protected $completedTime;

    /**
     * @var string
     */
    protected $translationMethod;

    /**
     * @var string
     */
    protected $fromLanguage;

    /**
     * @var string
     */
    protected $toLanguage;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var int
     */
    protected $wordCount;

    /**
     * @var int
     */
    protected $translationCredits;

    /**
     * @var Datetime
     */
    protected $estimatedTime;

    /**
     * Constructor
     *
     * @param   mixed $data Data
     */
    public function __construct($data)
    {
        parent::__construct($data);

        if (is_string($this->createdTime))
        {
            $this->createdTime = new DateTime($this->createdTime);
        }

        if (!empty($this->translationMethod))
        {
            $this->translationMethod = NenoHelper::getTranslationMethodById($this->translationMethod);
        }
    }

    /**
     * Find a job and creates it.
     *
     * @param   string $toLanguage        JISO Language format
     * @param   string $translationMethod Translation Method chosen
     *
     * @return NenoJob|null It will return a NenoJob object if there are translations pending or null if there aren't any.
     */
    public static function createJob($toLanguage, $translationMethod)
    {
        /* @var $db NenoDatabaseDriverMysqlx */
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query
            ->select('id')
            ->from('#__neno_content_element_translations AS tr')
            ->where(
                array (
                    'language = ' . $db->quote($toLanguage),
                    'state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE,
                    'EXISTS (SELECT 1 FROM #__neno_content_element_translation_x_translation_methods AS trtm WHERE tr.id = trtm.translation_id AND translation_method_id = ' . $translationMethod . ')',
                    'NOT EXISTS (SELECT 1 FROM #__neno_jobs_x_translations AS jt WHERE tr.id = jt.translation_id)'
                )
            );

        $db->setQuery($query);
        $translationObjects = $db->loadArray();

        $job = null;

        if (!empty($translationObjects))
        {
            $jobData = array (
                'fromLanguage'      => NenoSettings::get('source_language'),
                'toLanguage'        => $toLanguage,
                'state'             => self::JOB_STATE_GENERATED,
                'createdTime'       => new DateTime,
                'translationMethod' => $translationMethod
            );

            $job = new NenoJob($jobData);
            $job
                ->setTranslations($translationObjects)
                ->persist();

            NenoTaskMonitor::addTask('job_sender');
        }

        return $job;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function persist()
    {
        // If the job has been persisted properly, let's save the translations
        if (parent::persist())
        {
            $db = JFactory::getDbo();
            /* @var $query NenoDatabaseQueryMysqli */
            $query = $db->getQuery(true);

            if (!empty($this->translations))
            {
                $query
                    ->replace('#__neno_jobs_x_translations')
                    ->columns(
                        array (
                            'job_id',
                            'translation_id'
                        )
                    );

                foreach ($this->translations as $translation)
                {
                    $query->values($db->quote($this->getId()) . ',' . (int) $translation);
                }

                $db->setQuery($query);
                $db->execute();
            }

            $query
                ->select('SUM(word_counter)')
                ->from('#__neno_jobs_x_translations AS jt')
                ->innerJoin('#__neno_content_element_translations AS tr ON jt.translation_id = tr.id')
                ->where('jt.job_id = ' . $this->id)
                ->group('jt.job_id');
            $db->setQuery($query);
            $wordCount          = $db->loadResult();
            $translationCredits = 0;

            switch (NenoHelper::convertTranslationMethodIdToName($this->translationMethod->id))
            {
                case 'machine':
                    $translationCredits = $wordCount;
                    break;
                case 'professional':
                    $translationCredits = $wordCount * 200;
                    break;
            }

            $this->wordCount          = $wordCount;
            $this->translationCredits = $translationCredits;

            return parent::persist();
        }

        return false;
    }

    /**
     * To object method
     *
     * @param   bool $allFields         Convert all the fields
     * @param   bool $recursive         If the method should be run recursive
     * @param   bool $convertToDatabase Convert to database naming
     *
     * @return stdClass
     */
    public function toObject($allFields = false, $recursive = false, $convertToDatabase = true)
    {
        $data = parent::toObject($allFields, $recursive, $convertToDatabase);

        if (is_object($this->translationMethod))
        {
            $data->translation_method = $this->translationMethod->id;
        }

        return $data;
    }

    /**
     * Get Job status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->state;
    }

    /**
     * Get the date when the job was sent
     *
     * @return Datetime
     */
    public function getSentTime()
    {
        return $this->sentTime;
    }

    /**
     * Set sent time
     *
     * @param   Datetime $sentTime Time when the job has been sent
     *
     * @return $this
     */
    public function setSentTime($sentTime)
    {
        $this->sentTime = $sentTime;

        return $this;
    }

    /**
     * Get the date when the job was completed
     *
     * @return Datetime
     */
    public function getCompletedTime()
    {
        return $this->completedTime;
    }

    /**
     * Set completed time
     *
     * @param   Datetime $completedTime Time when the job has been completed
     *
     * @return $this
     */
    public function setCompletedTime(Datetime $completedTime)
    {
        $this->completedTime = $completedTime;

        return $this;
    }

    /**
     * Fetch the job file from the server
     *
     * @return bool|JError True on Success or false|JError if something goes wrong.
     */
    public function fetchJobFromServer()
    {
        $config   = JFactory::getConfig();
        $tmpPath  = $config->get('tmp_path');
        $filename = $this->getFileName();

        /* @var $zipAdapter JArchiveZip */
        $zipAdapter = JArchive::getAdapter('zip');

        file_put_contents(
            JPATH_ROOT . '/tmp/' . $filename . '.json.zip',
            fopen(NenoSettings::get('server_url') . 'tmp/' . NenoSettings::get('license_code') . '/' . $filename . '.json.zip', 'r')
        );

        try
        {
            return $zipAdapter->extract(
                JPATH_ROOT . '/tmp/' . $filename . '.json.zip',
                $tmpPath . '/' . $filename . '.json'
            );
        }
        catch (RuntimeException $e)
        {
            return false;
        }
    }

    /**
     * Generate filename for the job
     *
     * @return string
     */
    public function getFileName()
    {
        return strtolower($this->fromLanguage) . '-to-' . strtolower($this->toLanguage) . '-' . $this->getId();
    }

    /**
     * Process a file
     *
     * @return bool True on success, false otherwise
     */
    public function processJobFinished()
    {
        $config       = JFactory::getConfig();
        $tmpPath      = $config->get('tmp_path');
        $filename     = $this->getFileName();
        $fileContents = json_decode(file_get_contents($tmpPath . '/' . $filename . '.json' . '/' . $filename . '.json'), true);

        if ($fileContents !== null)
        {
            foreach ($fileContents['strings'] as $translationId => $translationText)
            {
                /* @var $translation NenoContentElementTranslation */
                $translation = NenoContentElementTranslation::load($translationId);

                if (!empty($translation))
                {
                    $translation->setString($translationText);

                    // Mark this translation method as completed
                    $translation->markTranslationMethodAsCompleted($this->translationMethod->id);

                    if ($translation->isBeingCompleted())
                    {
                        $translation->setState(NenoContentElementTranslation::TRANSLATED_STATE);
                    }

                    $translation->persist();
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Send Job
     *
     * @return bool
     */
    public function sendJob()
    {
        $this->generateJobFile();
        $this
            ->setSentTime(new DateTime)
            ->setState(self::JOB_STATE_SENT);

        $data = array (
            'filename'             => $this->getFileName() . '.json.zip',
            'words'                => $this->getWordCount(),
            'translation_method'   => NenoHelper::convertTranslationMethodIdToName($this->getTranslationMethod()->id),
            'source_language'      => $this->getFromLanguage(),
            'destination_language' => $this->getToLanguage()
        );

        list($status, $response) = NenoHelperApi::makeApiCall('job', 'POST', $data);

        if ($status === false)
        {
            $this
                ->setSentTime(null)
                ->setState(self::JOB_STATE_GENERATED);

            if ($response['code'] !== 200)
            {
                $this->setState(self::JOB_STATE_NO_TC);
            }
        }

        $this->persist();

        return $status !== false;
    }

    /**
     * Create a job file
     *
     * @return bool True on success
     *
     * @throws Exception If something happens when the zip file is being created.
     */
    public function generateJobFile()
    {
        $filename = $this->getFileName();

        $jobData = array (
            'jobId'              => $this->getId(),
            'job_create_time'    => $this->getCreatedTime(true),
            'file_name'          => $filename,
            'translation_method' => NenoHelper::convertTranslationMethodIdToName($this->getTranslationMethod()->id),
            'from'               => $this->getFromLanguage(),
            'to'                 => $this->getToLanguage(),
            'comment'            => NenoSettings::get('external_translators_notes'),
            'word_count'         => $this->getWordCount(),
            'strings'            => $this->getTranslations()
        );

        $config  = JFactory::getConfig();
        $tmpPath = $config->get('tmp_path');

        $fileData = array (
            'name' => $filename . '.json',
            'data' => json_encode($jobData)
        );

        /* @var $zipArchiveAdapter JArchiveZip */
        $zipArchiveAdapter = JArchive::getAdapter('zip');
        $result            = $zipArchiveAdapter->create($tmpPath . '/' . $filename . '.json.zip', array ($fileData));

        $this->fileName = $filename;

        // If something happens in the process of creating the job file, let's throw an exception
        if (!$result)
        {
            throw new Exception('Error creating job file');
        }

        $this->persist();

        return $result;
    }

    /**
     * Get created date
     *
     * @param   bool   $formatted If the date should be formatted
     * @param   string $format    Which format should be used
     *
     * @return Datetime|string
     */
    public function getCreatedTime($formatted = false, $format = 'Y-m-d H:i:s')
    {
        if ($formatted)
        {
            return $this->createdTime->format($format);
        }
        else
        {
            return $this->createdTime;
        }
    }

    /**
     * Get Translation method
     *
     * @return stdClass
     */
    public function getTranslationMethod()
    {
        return $this->translationMethod;
    }

    /**
     * Get the language that the strings will be translate from
     *
     * @return string
     */
    public function getFromLanguage()
    {
        return $this->fromLanguage;
    }

    /**
     * Get the language that the strings will be translate to
     *
     * @return string
     */
    public function getToLanguage()
    {
        return $this->toLanguage;
    }

    /**
     * Get all the strings that needs to be translated.
     *
     * @return array
     */
    public function getTranslations()
    {
        if (empty($this->translations))
        {
            /* @var $db NenoDatabaseDriverMysqlx */
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query
                ->select(
                    array (
                        't.id',
                        't.content_type',
                        't.content_id',
                        't.comment'
                    )
                )
                ->from('`#__neno_jobs_x_translations` AS jt')
                ->innerJoin('`#__neno_content_element_translations` AS t ON jt.translation_id = t.id')
                ->where('job_id = ' . $this->getId());
            $db->setQuery($query);
            $translations       = $db->loadAssocList();
            $this->translations = array ();

            foreach ($translations as $translation)
            {
                $translationOriginalText                = NenoHelper::getTranslationOriginalText(
                    $translation['id'],
                    $translation['content_type'],
                    $translation['content_id']
                );
                $this->translations[$translation['id']] = array (
                    'text'    => NenoHelper::splitHtmlText($translationOriginalText),
                    'comment' => $translation['comment']
                );
            }
        }

        return $this->translations;
    }

    /**
     * Set translations
     *
     * @param   array $translations Translations
     *
     * @return $this
     */
    public function setTranslations(array $translations)
    {
        $this->translations = $translations;

        return $this;
    }

    /**
     * Set State
     *
     * @param   int $state State
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get how many word this job has
     *
     * @return int
     */
    public function getWordCount()
    {
        return $this->wordCount;
    }
}
