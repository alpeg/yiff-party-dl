## yiff-party-dl

This is a [yiff.party](https://yiff.party/) parser that extracts links and relevant information.

It is meant to be used for salvaging content that is going to be removed due to [new yiff.party exclusions policy](https://yiff.party/exclusions).

Current excluded creator ID list can be found at https://yiff.party/exclusions.json (updated regularly).

**Fell free to contact me** if you want to cooperate in any way (check my github profile for email).

### Roadmap

- [ ] Parser — converts HTML page into machine-readable format
  - [x] Basic parsing
  - [x] Tests
  - [ ] Running tests against all pages (to make sure parser correctly handles all possible cases)
  - [ ] Interface
- [ ] Crawler — scans through the site to create a list of files to download
  - [x] Basic stuff
  - [ ] ???
  - [ ] Interface
- [ ] Downloader — downloads all or partial links 
- [ ] Verifier — checks that files was downloaded correctly

Project goal is to download everything listed for exclusion before Monday, 1 June.

### Installation

Make sure you have:

- PHP 7.4 with `xml`, `json` and `curl` extensions
- [composer](https://getcomposer.org/download/) installed globally or `composer.phar` in a project directory

Clone project with `git` or download and unpack zip package

Run `composer install` (or `php composer.phar install`)

### Usage

There is currently no way to run anything without editing the code.

## Related projects

- [kemono.party](https://kemono.party/) ([github](https://github.com/OpenYiff/Kemono)) — open source yiff.party reimplementation.
- yiff-dl ([website](https://m-rcus.github.io/yiff-dl/), [github](https://github.com/M-rcus/yiff-dl)) — similar project written in node.js, but with more generic parser.

