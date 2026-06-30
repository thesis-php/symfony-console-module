# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] — 2026-06-30

### Added

- `SymfonyConsoleModule` — a Thesis DIC module that registers a Symfony Console `Application`
- `CommandTag` — tags any callable (invokable class or function) to be registered as a lazy command
- `LegacyCommandTag` — tags a `Command` subclass; lazy when `name` is provided, eager otherwise
- `AutoconfigureCommands` — reads `#[AsCommand]` and applies `CommandTag` or `LegacyCommandTag` automatically
