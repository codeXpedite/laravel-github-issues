<?php

namespace Codexpedite\LaravelGithubIssues;

use Illuminate\Support\ServiceProvider;
use Codexpedite\LaravelGithubIssues\Commands\MonitorLogsCommand;
use Codexpedite\LaravelGithubIssues\Services\LogMonitorService;
use Codexpedite\LaravelGithubIssues\Services\GitHubClient;
use Codexpedite\LaravelGithubIssues\Services\ErrorProcessor;

class GitHubIssuesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/github-issues.php', 'github-issues'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/github-issues.php' => config_path('github-issues.php'),
            ], 'config');

            $this->commands([
                MonitorLogsCommand::class,
            ]);
        }

        if (config('github-issues.enabled') && $this->isConfigured()) {
            $this->app->booted(function () {
                $this->registerServices();
                $this->app[LogMonitorService::class]->start();
            });
        }
    }

    private function registerServices(): void
    {
        $this->app->singleton(GitHubClient::class, function ($app) {
            $token = $app['config']['github-issues.github.token'] ?? '';
            $owner = $app['config']['github-issues.github.owner'] ?? '';
            $repository = $app['config']['github-issues.github.repository'] ?? '';
            
            return new GitHubClient($token, $owner, $repository);
        });

        $this->app->singleton(ErrorProcessor::class, function ($app) {
            return new ErrorProcessor($app['config']['github-issues']);
        });

        $this->app->singleton(LogMonitorService::class, function ($app) {
            return new LogMonitorService(
                $app[GitHubClient::class],
                $app[ErrorProcessor::class],
                $app['config']['github-issues']
            );
        });
    }

    private function isConfigured(): bool
    {
        return config('github-issues.github.token') &&
               config('github-issues.github.owner') &&
               config('github-issues.github.repository');
    }
}