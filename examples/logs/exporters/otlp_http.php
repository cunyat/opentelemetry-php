<?php

declare(strict_types=1);

namespace OpenTelemetry\Example;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\LoggerHolder;
use OpenTelemetry\API\Logs\EventLogger;
use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use Opentelemetry\Proto\Logs\V1\SeverityNumber;
use OpenTelemetry\SDK\Common\Instrumentation\InstrumentationScopeFactory;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\LogRecordLimitsBuilder;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use Psr\Log\LogLevel;

require __DIR__ . '/../../../vendor/autoload.php';

LoggerHolder::set(
    new Logger('otel-php', [new StreamHandler(STDOUT, LogLevel::DEBUG)])
);

$transport = (new OtlpHttpTransportFactory())->create('http://collector:4318/v1/logs', 'application/json');
$exporter = new LogsExporter($transport);

$loggerProvider = new LoggerProvider(
    new SimpleLogRecordProcessor(
        $exporter
    ),
    new InstrumentationScopeFactory(
        (new LogRecordLimitsBuilder())->build()->getAttributeFactory()
    )
);
$logger = $loggerProvider->getLogger('demo', '1.0', 'https://opentelemetry.io/schemas/1.7.1', ['foo' => 'bar']);
$eventLogger = new EventLogger($logger, 'my-domain');

$record = (new LogRecord(['foo' => 'bar', 'baz' => 'bat', 'msg' => 'hello world']))
    ->setSeverityText('INFO')
    ->setTimestamp((new \DateTime())->getTimestamp() * LogRecord::NANOS_PER_SECOND)
    ->setSeverityNumber(SeverityNumber::SEVERITY_NUMBER_INFO);

$eventLogger->logEvent('foo', $record);
