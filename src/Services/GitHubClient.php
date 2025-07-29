<?php

namespace Codexpedite\LaravelGithubIssues\Services;

use Github\Client;
use Github\AuthMethod;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use GuzzleHttp\Client as GuzzleClient;

class GitHubClient
{
    private Client $client;
    private string $owner;
    private string $repository;

    public function __construct(string $token, string $owner, string $repository)
    {
        $this->owner = $owner;
        $this->repository = $repository;

        $guzzleClient = new GuzzleClient([
            'timeout' => 30,
        ]);
        
        $this->client = new Client(new GuzzleAdapter($guzzleClient));
        $this->client->authenticate($token, null, AuthMethod::ACCESS_TOKEN);
    }

    public function createIssue(array $issueData): array
    {
        try {
            $response = $this->client->issue()->create(
                $this->owner,
                $this->repository,
                [
                    'title' => $issueData['title'],
                    'body' => $issueData['body'],
                    'labels' => $issueData['labels'] ?? [],
                    'assignees' => $issueData['assignees'] ?? [],
                ]
            );

            return $response;
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to create GitHub issue: " . $e->getMessage());
        }
    }

    public function addCommentToIssue(int $issueNumber, string $comment): array
    {
        try {
            return $this->client->issue()->comments()->create(
                $this->owner,
                $this->repository,
                $issueNumber,
                ['body' => $comment]
            );
        } catch (\Exception $e) {
            throw new \Exception("Failed to add comment to GitHub issue: " . $e->getMessage());
        }
    }

    public function searchIssues(string $query): array
    {
        try {
            return $this->client->search()->issues(
                "repo:{$this->owner}/{$this->repository} {$query}"
            );
        } catch (\Exception $e) {
            throw new \Exception("Failed to search GitHub issues: " . $e->getMessage());
        }
    }

    public function getIssue(int $issueNumber): array
    {
        try {
            return $this->client->issue()->show(
                $this->owner,
                $this->repository,
                $issueNumber
            );
        } catch (\Exception $e) {
            throw new \Exception("Failed to get GitHub issue: " . $e->getMessage());
        }
    }

    public function closeIssue(int $issueNumber): array
    {
        try {
            return $this->client->issue()->update(
                $this->owner,
                $this->repository,
                $issueNumber,
                ['state' => 'closed']
            );
        } catch (\Exception $e) {
            throw new \Exception("Failed to close GitHub issue: " . $e->getMessage());
        }
    }

    public function testConnection(): bool
    {
        try {
            $this->client->currentUser()->show();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}