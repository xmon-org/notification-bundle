# Contributing to Xmon Notification Bundle

Thank you for considering contributing! This document provides guidelines and setup instructions for development.

## Requirements

- PHP 8.2+
- Composer
- Git

## Development Setup

### 1. Clone the Repository

```bash
git clone https://github.com/xmon-org/notification-bundle.git
cd notification-bundle
```

### 2. Install Dependencies

```bash
composer install
```

This will:
- Install all dependencies
- Configure Git hooks automatically (via `post-install-cmd`)

### 3. Verify Setup

```bash
# Run all checks
composer check

# This executes: CS-Fixer (dry-run) + PHPStan + Tests
```

## Code Standards

### PHP-CS-Fixer

We follow Symfony coding standards enforced by PHP-CS-Fixer.

```bash
# Check code style (dry-run)
composer cs-check

# Fix code style automatically
composer cs-fix
```

### PHPStan (Static Analysis)

PHPStan runs at level 5 for strict type checking.

```bash
# Run PHPStan
composer phpstan

# Generate baseline for legacy errors (if needed)
composer phpstan:baseline
```

**Configuration**: See `phpstan.neon` for ignored errors and settings.

### Tests

```bash
# Run tests
composer test

# Run tests with coverage
composer test:coverage
```

## Git Hooks

Git hooks are configured automatically on `composer install`. They ensure code quality before commits.

### Pre-commit Hook

Runs automatically before each commit:

1. **PHP-CS-Fixer**: Auto-formats staged files
2. **PHPStan**: Analyzes modified files only

If PHPStan finds errors, the commit will be rejected.

### Bypassing Hooks (Not Recommended)

```bash
git commit --no-verify -m "message"
```

Only use in emergencies. Hooks exist to prevent problems.

### Manual Hook Setup

If hooks are not working, run:

```bash
composer setup-hooks
```

## Commit Messages

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Code style (no logic change) |
| `refactor` | Code refactoring |
| `perf` | Performance improvement |
| `test` | Adding/fixing tests |
| `chore` | Maintenance tasks |

### Examples

```bash
feat(telegram): add inline keyboard support
fix(email): handle empty recipient list
docs: update installation instructions
```

## Pull Request Process

1. Fork the repository
2. Create a feature branch (`feat/my-feature` or `fix/bug-description`)
3. Make your changes
4. Ensure all checks pass: `composer check`
5. Submit a PR against `main` branch

### PR Checklist

- [ ] Code follows project standards (PHP-CS-Fixer)
- [ ] PHPStan passes without errors
- [ ] Tests pass
- [ ] New features have tests
- [ ] Documentation updated if needed

## Available Composer Scripts

| Command | Description |
|---------|-------------|
| `composer check` | Run all validation (CS + PHPStan + Tests) |
| `composer cs-check` | Check code style (dry-run) |
| `composer cs-fix` | Fix code style automatically |
| `composer phpstan` | Run static analysis |
| `composer phpstan:baseline` | Generate PHPStan baseline |
| `composer test` | Run tests |
| `composer setup-hooks` | Configure Git hooks |

## Project Structure

```
src/
├── Channel/           # Notification channels (Email, Telegram, etc.)
├── DependencyInjection/
├── Event/             # Event classes for hooks
├── Exception/
├── Notification/      # Notification value objects
├── Recipient/
├── Result/            # Send result objects
├── Service/           # Core services
└── XmonNotificationBundle.php

tests/                 # PHPUnit tests
.githooks/             # Git hooks (pre-commit, etc.)
```

## Questions?

Open an issue on GitHub for questions or suggestions.
