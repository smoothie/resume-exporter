# smoothie's Resume Exporter

Export JSON Resume into a PDF or DB.

## Table Of Contents

* [Installation](#installation)
* [Usage](#usage)
* [Testing](#testing)
* [Testing](#wishlist)
* [Limitations](#limitations)

## Installation

**TBD (composer and phive)**

```BASH
composer require smoothie/resume-export

# OR

phive install resume-export

```

## Usage

**TBD**

...

## Testing

This package uses some tools to ensure stuff works.

- PHPUnit for unit testing.
- psalm for static code analysis.
- PHP CS Fixer for enforcing code style (Mainly @PhpCsFixer RuleSet without Yoda and small adjustments).

The dependencies for those are living in the ./infrastructure directory.

**TBD**

...

## Wishlist
```md
[] Full JSONResume support
    [] :construction: Basics
    [] :construction: Education
    [] :construction: Skills
    [] :construction: Projects
    [] :construction: Meta
    [] :construction: Custom Properties
    [] :construction: Languages
    [] Work
    [] Volunteer
    [] Awards
    [] Certificates
    [] Publications
    [] Interests
[] Slim down dependencies (eg. we do not need the complete framework bundle).
[] Investigate if we can get rid of the pcre ini sets by increasing PCRE stacksize.
    - Source: https://stackoverflow.com/questions/7620910/regexp-in-preg-match-function-returning-browser-error/7627962#7627962
[] Support for PHPWord Elements (like links).
[] Support for images (logos).
[] Support for splitting resume into multiple files.
[] Support for other standards besides JSON Resume.
[] Support relative paths (cli).
```

## Limitations

### Absolute paths only

The application expects absolute paths only.

So when you pass an incoming out outputting document/map, be aware of that.

### Cloning Blocks PHPWord

**Error:**

> Can not clone row, template variable not found or variable contains markup.


**Fix:**

Might be related to a limit on PCRE stack size. We can increase those:

```bash
php -dpcre.backtrack_limit=250000000 -dpcre.recursion_limit=250000000 bin/console
```

or

```php
ini_set("pcre.backtrack_limit", "250000000");
ini_set("pcre.recursion_limit", "250000000");
```

**Source:** https://github.com/PHPOffice/PHPWord/issues/2217

*Note.. it might be an option to increase the stacksize, but I haven't digged into that yet.*
