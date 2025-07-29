<?php

namespace Codexpedite\LaravelGithubIssues\Commands;

use Illuminate\Console\Command;
use Codexpedite\LaravelGithubIssues\Services\LogMonitorService;
use Codexpedite\LaravelGithubIssues\Services\GitHubClient;

class MonitorLogsCommand extends Command
{
    protected $signature = 'github-issues:monitor 
                           {--test : Test GitHub connection without monitoring}
                           {--stop : Stop the monitoring service}';

    protected $description = 'Monitor Laravel logs and automatically create GitHub issues for errors';

    private LogMonitorService $logMonitor;
    private GitHubClient $githubClient;

    public function __construct(LogMonitorService $logMonitor, GitHubClient $githubClient)
    {
        parent::__construct();
        $this->logMonitor = $logMonitor;
        $this->githubClient = $githubClient;
    }

    public function handle(): int
    {
        if ($this->option('test')) {
            return $this->testConnection();
        }

        if ($this->option('stop')) {
            return $this->stopMonitoring();
        }

        return $this->startMonitoring();
    }

    private function testConnection(): int
    {
        $this->info('Testing GitHub connection...');

        if (!$this->isConfigured()) {
            $this->error('GitHub Issues package is not properly configured.');
            $this->line('Please set the following environment variables:');
            $this->line('- GITHUB_TOKEN');
            $this->line('- GITHUB_OWNER');
            $this->line('- GITHUB_REPO');
            return 1;
        }

        try {
            if ($this->githubClient->testConnection()) {
                $this->info('✓ GitHub connection successful!');
                return 0;
            } else {
                $this->error('✗ GitHub connection failed.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("✗ GitHub connection failed: {$e->getMessage()}");
            return 1;
        }
    }

    private function startMonitoring(): int
    {
        if (!$this->isConfigured()) {
            $this->error('GitHub Issues package is not properly configured.');
            return 1;
        }

        $this->info('Starting log monitoring...');
        $this->line('Press Ctrl+C to stop monitoring.');

        try {
            $this->logMonitor->start();
            
            $this->trap(SIGINT, function () {
                $this->info("\nStopping log monitoring...");
                $this->logMonitor->stop();
                exit(0);
            });

            while (true) {
                sleep(1);
            }

        } catch (\Exception $e) {
            $this->error("Failed to start monitoring: {$e->getMessage()}");
            return 1;
        }
    }

    private function stopMonitoring(): int
    {
        $this->info('Stopping log monitoring...');
        
        try {
            $this->logMonitor->stop();
            $this->info('✓ Log monitoring stopped.');
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to stop monitoring: {$e->getMessage()}");
            return 1;
        }
    }

    private function isConfigured(): bool
    {
        return config('github-issues.github.token') &&
               config('github-issues.github.owner') &&
               config('github-issues.github.repository');
    }
}