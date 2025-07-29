<?php

namespace Codexpedite\LaravelGithubIssues\Services;

use Illuminate\Support\Facades\Http;

class GitHubClient
{
    private string $token;
    private string $owner;
    private string $repository;
    private string $baseUrl = 'https://api.github.com';

    public function __construct(string $token, string $owner, string $repository)
    {
        $this->token = $token;
        $this->owner = $owner;
        $this->repository = $repository;
    }

    public function createIssue(array $issueData): array
    {
        try {
            $response = $this->makeRequest('POST', "/repos/{$this->owner}/{$this->repository}/issues", [
                'title' => $issueData['title'],
                'body' => $issueData['body'],
                'labels' => $issueData['labels'] ?? [],
                'assignees' => $issueData['assignees'] ?? [],
            ]);

            return $response->json();
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to create GitHub issue: " . $e->getMessage());
        }
    }

    public function addCommentToIssue(int $issueNumber, string $comment): array
    {
        try {
            $response = $this->makeRequest('POST', "/repos/{$this->owner}/{$this->repository}/issues/{$issueNumber}/comments", [
                'body' => $comment
            ]);

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("Failed to add comment to GitHub issue: " . $e->getMessage());
        }
    }

    public function searchIssues(string $query): array
    {
        try {
            $response = $this->makeRequest('GET', '/search/issues', [
                'q' => "repo:{$this->owner}/{$this->repository} {$query}"
            ]);

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("Failed to search GitHub issues: " . $e->getMessage());
        }
    }

    public function getIssue(int $issueNumber): array
    {
        try {
            $response = $this->makeRequest('GET', "/repos/{$this->owner}/{$this->repository}/issues/{$issueNumber}");

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("Failed to get GitHub issue: " . $e->getMessage());
        }
    }

    public function closeIssue(int $issueNumber): array
    {
        try {
            $response = $this->makeRequest('PATCH', "/repos/{$this->owner}/{$this->repository}/issues/{$issueNumber}", [
                'state' => 'closed'
            ]);

            return $response->json();
        } catch (\Exception $e) {
            throw new \Exception("Failed to close GitHub issue: " . $e->getMessage());
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/user');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $request = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Laravel-GitHub-Issues/1.0'
        ])->timeout(30);

        $url = $this->baseUrl . $endpoint;

        return match(strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PATCH' => $request->patch($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
        };
    }
}