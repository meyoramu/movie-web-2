<?php

namespace CineVerse\Core\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use DateTime;
use Exception;

/**
 * Logger Class
 * 
 * PSR-3 compatible logger for CineVerse application
 */
class Logger implements LoggerInterface
{
    private array $config;
    private string $logPath;
    private array $logLevels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->logPath = $config['path'] ?? __DIR__ . '/../../../storage/logs';
        
        // Ensure log directory exists
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * System is unusable.
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action.
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     */
    public function log($level, $message, array $context = []): void
    {
        // Check if log level should be logged
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = new DateTime();
        $formattedMessage = $this->formatMessage($level, $message, $context, $timestamp);
        
        $this->writeToFile($formattedMessage, $timestamp);
    }

    /**
     * Check if the log level should be logged
     */
    private function shouldLog(string $level): bool
    {
        $configLevel = $this->config['level'] ?? LogLevel::INFO;
        
        if (!isset($this->logLevels[$level]) || !isset($this->logLevels[$configLevel])) {
            return true;
        }
        
        return $this->logLevels[$level] <= $this->logLevels[$configLevel];
    }

    /**
     * Format the log message
     */
    private function formatMessage(string $level, string $message, array $context, DateTime $timestamp): string
    {
        $formatted = sprintf(
            "[%s] %s: %s",
            $timestamp->format('Y-m-d H:i:s'),
            strtoupper($level),
            $this->interpolate($message, $context)
        );

        // Add context if present
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        return $formatted . PHP_EOL;
    }

    /**
     * Interpolate context values into message placeholders
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
    }

    /**
     * Write log message to file
     */
    private function writeToFile(string $message, DateTime $timestamp): void
    {
        try {
            $filename = $this->getLogFilename($timestamp);
            $filepath = $this->logPath . '/' . $filename;
            
            file_put_contents($filepath, $message, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Fallback to error_log if file writing fails
            error_log("Logger failed to write to file: " . $e->getMessage());
            error_log($message);
        }
    }

    /**
     * Get log filename based on channel and date
     */
    private function getLogFilename(DateTime $timestamp): string
    {
        $channel = $this->config['channel'] ?? 'daily';
        
        switch ($channel) {
            case 'single':
                return 'cineverse.log';
            case 'daily':
            default:
                return 'cineverse-' . $timestamp->format('Y-m-d') . '.log';
        }
    }

    /**
     * Clear old log files (for daily logs)
     */
    public function clearOldLogs(int $days = 30): void
    {
        if (($this->config['channel'] ?? 'daily') !== 'daily') {
            return;
        }

        $files = glob($this->logPath . '/cineverse-*.log');
        $cutoff = time() - ($days * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
