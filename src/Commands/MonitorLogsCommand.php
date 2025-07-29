<?php

namespace Codexpedite\LaravelGithubIssues\Commands;

use Illuminate\Console\Command;
use Codexpedite\LaravelGithubIssues\Services\LogMonitorService;
use Codexpedite\LaravelGithubIssues\Services\GitHubClient;
use Codexpedite\LaravelGithubIssues\Services\ErrorProcessor;

class MonitorLogsCommand extends Command
{
    protected $signature = 'github-issues:monitor 
                           {--test : Test GitHub connection without monitoring}
                           {--stop : Stop the monitoring service}';

    protected $description = 'Monitor Laravel logs and automatically create GitHub issues for errors';

    public function __construct()
    {
        parent::__construct();
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
            $githubClient = new GitHubClient(
                config('github-issues.github.token'),
                config('github-issues.github.owner'),
                config('github-issues.github.repository')
            );
            
            if ($githubClient->testConnection()) {
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
            $githubClient = new GitHubClient(
                config('github-issues.github.token'),
                config('github-issues.github.owner'),
                config('github-issues.github.repository')
            );
            
            $errorProcessor = new ErrorProcessor(config('github-issues'));
            
            $logMonitor = new LogMonitorService(
                $githubClient,
                $errorProcessor,
                config('github-issues')
            );
            
            $logMonitor->start();
            
            $this->trap(SIGINT, function () use ($logMonitor) {
                $this->info("\nStopping log monitoring...");
                $logMonitor->stop();
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
        $this->info('✓ Log monitoring stopped.');
        return 0;
    }

    private function isConfigured(): bool
    {
        return config('github-issues.github.token') &&
               config('github-issues.github.owner') &&
               config('github-issues.github.repository');
    }
}