<?php

namespace Codexpedite\LaravelGithubIssues\Services;

use Spatie\Watcher\Watch;
use Illuminate\Support\Facades\Log;

class LogMonitorService
{
    private GitHubClient $githubClient;
    private ErrorProcessor $errorProcessor;
    private array $config;
    private array $errorBuffer = [];
    private int $lastPosition = 0;
    private bool $shouldStop = false;

    public function __construct(GitHubClient $githubClient, ErrorProcessor $errorProcessor, array $config)
    {
        $this->githubClient = $githubClient;
        $this->errorProcessor = $errorProcessor;
        $this->config = $config;
    }

    public function start(): void
    {
        if (!$this->isConfigured()) {
            Log::warning('Laravel GitHub Issues: Not properly configured. Skipping log monitoring.');
            return;
        }

        $logFile = $this->config['monitoring']['log_file'];
        
        if (!file_exists($logFile)) {
            Log::warning("Laravel GitHub Issues: Log file not found at {$logFile}");
            return;
        }

        $this->lastPosition = filesize($logFile);
        $this->shouldStop = false;

        try {
            Watch::path(dirname($logFile))
                ->onFileUpdated(function (string $path) use ($logFile) {
                    if ($path === $logFile) {
                        $this->processNewLogEntries($logFile);
                    }
                })
                ->shouldContinue(function () {
                    return !$this->shouldStop;
                })
                ->start();
        } catch (\Exception $e) {
            Log::error('Laravel GitHub Issues: Failed to start file watcher', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function stop(): void
    {
        $this->shouldStop = true;
        $this->flushBuffer();
    }

    private function processNewLogEntries(string $logFile): void
    {
        $currentSize = filesize($logFile);
        
        if ($currentSize <= $this->lastPosition) {
            return;
        }

        $handle = fopen($logFile, 'r');
        if (!$handle) {
            return;
        }

        fseek($handle, $this->lastPosition);
        
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $issueData = $this->errorProcessor->processLogEntry($line);
            
            if ($issueData) {
                $this->addToBuffer($issueData);
            }
        }

        $this->lastPosition = ftell($handle);
        fclose($handle);

        $this->processBuffer();
    }

    private function addToBuffer(array $issueData): void
    {
        $this->errorBuffer[] = $issueData;
        
        $bufferSize = $this->config['monitoring']['buffer_size'] ?? 10;
        
        if (count($this->errorBuffer) >= $bufferSize) {
            $this->flushBuffer();
        }
    }

    private function processBuffer(): void
    {
        if (empty($this->errorBuffer)) {
            return;
        }

        $pollInterval = $this->config['monitoring']['poll_interval'] ?? 1;
        
        static $lastProcessed = 0;
        
        if ((time() - $lastProcessed) >= $pollInterval) {
            $this->flushBuffer();
            $lastProcessed = time();
        }
    }

    private function flushBuffer(): void
    {
        foreach ($this->errorBuffer as $issueData) {
            try {
                $this->githubClient->createIssue($issueData);
                
                Log::info('Laravel GitHub Issues: Created issue', [
                    'title' => $issueData['title']
                ]);
                
            } catch (\Exception $e) {
                Log::error('Laravel GitHub Issues: Failed to create issue', [
                    'error' => $e->getMessage(),
                    'title' => $issueData['title']
                ]);
            }
        }
        
        $this->errorBuffer = [];
    }

    private function isConfigured(): bool
    {
        return !empty($this->config['github']['token']) &&
               !empty($this->config['github']['owner']) &&
               !empty($this->config['github']['repository']);
    }
}