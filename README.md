# Laravel GitHub Issues

Automatically monitor Laravel logs and create GitHub issues for errors. This package provides zero-configuration installation and automatic error reporting to help you stay on top of issues in your Laravel applications.

## Features

- **Zero-config installation** - Just install and add environment variables
- **Real-time monitoring** - Watches `laravel.log` using filesystem events
- **Smart deduplication** - Prevents duplicate issues for the same errors
- **Rich error context** - Includes stack traces, request info, and timestamps
- **Rate limiting** - Buffers requests to avoid GitHub API limits
- **Laravel 12.x compatible** - Built for modern Laravel applications

## Installation

Install the package via Composer:

```bash
composer require codexpedite/laravel-github-issues
```

The package will automatically register itself via Laravel's package auto-discovery.

## Configuration

Add the following environment variables to your `.env` file:

```env
GITHUB_TOKEN=your_github_personal_access_token
GITHUB_OWNER=your_username_or_organization
GITHUB_REPO=your_repository_name
```

### GitHub Token Setup

1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Generate a new token with the following permissions:
   - `repo` (Full control of private repositories)
   - `public_repo` (Access public repositories)
3. Copy the token and add it to your `.env` file

### Optional Configuration

Publish the config file for advanced customization:

```bash
php artisan vendor:publish --provider="Codexpedite\LaravelGithubIssues\GitHubIssuesServiceProvider" --tag="config"
```

Available configuration options:

```php
return [
    'enabled' => env('GITHUB_ISSUES_ENABLED', true),
    
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'owner' => env('GITHUB_OWNER'),
        'repository' => env('GITHUB_REPO'),
    ],
    
    'monitoring' => [
        'log_file' => storage_path('logs/laravel.log'),
        'poll_interval' => env('GITHUB_ISSUES_POLL_INTERVAL', 1),
        'buffer_size' => env('GITHUB_ISSUES_BUFFER_SIZE', 10),
        'deduplicate_timeout' => env('GITHUB_ISSUES_DEDUPE_TIMEOUT', 3600),
    ],
    
    'issue' => [
        'labels' => ['bug', 'auto-generated'],
        'assignees' => [],
        'title_prefix' => '[Auto] ',
        'include_stack_trace' => true,
        'include_request_info' => true,
    ],
    
    'filters' => [
        'min_level' => env('GITHUB_ISSUES_MIN_LEVEL', 'error'),
        'exclude_patterns' => [
            '/vendor/',
            'StreamHandler.php',
        ],
    ],
];
```

## Usage

### Automatic Monitoring

The package automatically starts monitoring your Laravel logs once installed and configured. No additional setup required!

### Manual Monitoring

You can also manually control the monitoring process:

```bash
# Test GitHub connection
php artisan github-issues:monitor --test

# Start monitoring manually
php artisan github-issues:monitor

# Stop monitoring
php artisan github-issues:monitor --stop
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `GITHUB_ISSUES_ENABLED` | Enable/disable the package | `true` |
| `GITHUB_ISSUES_MIN_LEVEL` | Minimum log level to process | `error` |
| `GITHUB_ISSUES_POLL_INTERVAL` | Buffer processing interval (seconds) | `1` |
| `GITHUB_ISSUES_BUFFER_SIZE` | Number of errors to buffer before processing | `10` |
| `GITHUB_ISSUES_DEDUPE_TIMEOUT` | Deduplication timeout (seconds) | `3600` |

## How It Works

1. **File Monitoring**: Uses Spatie's File System Watcher to monitor `storage/logs/laravel.log`
2. **Error Processing**: Parses log entries and filters based on level and patterns
3. **Deduplication**: Prevents duplicate issues using error message hashing
4. **Buffering**: Collects errors and processes them in batches to avoid API limits
5. **Issue Creation**: Creates detailed GitHub issues with error context and stack traces

## Issue Format

Created issues include:

- **Error level and environment**
- **Timestamp and error message**
- **Request information** (User Agent, IP, URI)
- **Raw log entry**
- **Automatic labels** (`bug`, `auto-generated`)

## Requirements

- PHP 8.1+
- Laravel 11.0+ or 12.0+
- GitHub repository with appropriate access

## Dependencies

- `knplabs/github-api` - GitHub API integration
- `spatie/file-system-watcher` - Real-time file monitoring
- `http-interop/http-factory-guzzle` - HTTP client

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Contributing

Pull requests are welcome! Please ensure your code follows PSR standards and includes tests.

## Support

If you discover any security vulnerabilities or bugs, please create an issue on GitHub.