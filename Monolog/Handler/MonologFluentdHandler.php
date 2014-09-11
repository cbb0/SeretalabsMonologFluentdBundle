<?php
/*
 * Landingi
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Seretalabs\Bundle\MonologFluentdBundle\Monolog\Handler;

use Fluent\Logger\FluentLogger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

/**
 * A fluentd log handler for Symfony. It based on Dakatsuka's monolog fluent extension
 * to push log entries to your fluentd deamon
 * https://github.com/dakatsuka/MonologFluentHandler
 *
 * @author Alex Knol <alex@seretalabs.com>
 */
class MonologFluentdHandler extends AbstractProcessingHandler
{
	/**
	 * @var FluentLogger
	 */
	private $logger;
	/**
	 * @var int
	 */
	private $port;
	/**
	 * @var string
	 */
	private $host;

	/**
	 * Initialize Handler
	 *
	 * @param bool|string $host
	 * @param int $port
	 * @param int $level
	 * @param bool $bubble
	 */
	public function __construct(
		$port   = FluentLogger::DEFAULT_LISTEN_PORT,
		$host   = FluentLogger::DEFAULT_ADDRESS,
		$level = Logger::DEBUG,
		$bubble = true,
		$env = 'dev_ak',
		$tag = 'backend')
	{
		$this->port = $port;
		$this->host = $host;
		$this->env = $env;
		$this->tag = $tag;

		parent::__construct($level, $bubble);

		$this->logger = new FluentLogger($host, $port);
	}

	/**
	 * {@inheritdoc}
	 */
	public function handleBatch(array $records)
	{
		$messages = array();

		foreach ($records as $record) {
			if ($record['level'] < $this->level) {
				continue;
			}
			$messages[] = $this->processRecord($record);
		}

		if (!empty($messages)) {
			foreach($messages as $message) {
				$this->write($message);
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function write(array $record)
	{
		if (isset($record['context']) && isset($record['context']['tag'])) {
			$tag = $record['context']['tag'];
		} else {
			$tag  = $this->tag;
		}
		$tag = $this->env . '.' . $tag;

		$data = $record;
		$data['level'] = Logger::getLevelName($record['level']);

		$this->logger->post($tag, $data);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter()
	{
		return new JsonFormatter;
	}
}
