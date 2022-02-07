# TYPO3 Extension ``spreadsheets``

[![CI](https://github.com/hoogi91/spreadsheets/workflows/CI/badge.svg?event=push)](https://github.com/hoogi91/spreadsheets/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/hoogi91/spreadsheets/branch/develop/graph/badge.svg)](https://codecov.io/gh/hoogi91/spreadsheets)
[![License](https://poser.pugx.org/hoogi91/spreadsheets/license)](https://packagist.org/packages/hoogi91/spreadsheets)

## Features

* Supporting editors & authors by providing
	* Worksheet selection by name (after selecting file)
	* (optional) cell selection after Worksheet has been selected
	* fluid based content element to display worksheet as HTML table in frontend
* Supporting developers by providing
	* TCA renderType to easily add spreadsheet selection
	* option to enable/disable cell selection
	* option to allow definition of interpreting selection (row-/col-based)
	* DataProcessor to extract spreadsheet cell data into usable objects
* [Documentation][1]

## Usage

### Installation

#### Installation using Composer

The recommended way to install the extension is using [Composer][2].

Run the following command within your Composer based TYPO3 project:

```
composer req hoogi91/spreadsheets
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install the [extension][3] with the extension manager module.

## Administration corner

### Versions and support

| Spreadsheets | TYPO3       | PHP       | Support / Development                |
| ------------ | ----------- |-----------|------------------------------------- |
| dev-master   | 11.5        | 8.1       | unstable development branch          |
| 3.x          | 10.4 - 11.5 | 7.3 - 8.1 | features, bugfixes, security updates |
| 2.x          | 10.4        | 7.2 - 7.4 | bugfixes, security update            |
| 1.x          | 8.7 - 9.5   | 7.0 - 7.2 | unsupported                          |

### Release Management

This extension uses [**semantic versioning**][4], which means, that
* **bugfix updates** (e.g. 1.0.0 => 1.0.1) just includes small bugfixes or security relevant stuff without breaking changes,
* **minor updates** (e.g. 1.0.0 => 1.1.0) includes new features and smaller tasks without breaking changes,
* and **major updates** (e.g. 1.0.0 => 2.0.0) breaking changes wich can be refactorings, features or bugfixes.

### Contribution

**Pull Requests** are gladly welcome! Nevertheless please don't forget to add an issue and connect it to your pull requests. This
is very helpful to understand what kind of issue the **PR** is going to solve.

Bugfixes: Please describe what kind of bug your fix solve and give us feedback how to reproduce the issue.

Features: Not every feature is relevant for the bulk of users. It helps to have a discussion about a new feature before you open a pull request.

[1]: https://docs.typo3.org/p/hoogi91/spreadsheets/master/en-us/
[2]: https://getcomposer.org/
[3]: https://extensions.typo3.org/extension/spreadsheets
[4]: https://semver.org/
