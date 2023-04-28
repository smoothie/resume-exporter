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
-
Source: https://stackoverflow.com/questions/7620910/regexp-in-preg-match-function-returning-browser-error/7627962#7627962
[] Support for PHPWord Elements (like links).
[] Support for images (logos).
[] Support for splitting resume into multiple files.
[] Support for other standards besides JSON Resume.
[] Support relative paths (cli).
[] Support for output filters (eg. sort experience in years by [level desc, years desc, skills keyword asc]).
[] Make singular/plural in maps optional.
[] Cleanup
[] map/normalization into domain space (eg. Extract Asterisk)
[] psalm type for canonical array
[] allow null/empty values
```

## Limitations

### Array to Array Mapping

There are some things to note here:

1. At the moment we always expect that an array is mapped to another array.
2. When map into two different arrays, be aware that you might override existent keys.
3. When printing to PDF more than two list depth is not supported (ATM).

Something like this works:

```json
{
    "[from][*][foo]": "[to][*][whatever]",
    "[from][*][some]": "[to][*][some]",
    "[from][*][else]": "[toFoo][*][some]",
    "[from][*][nested][*][foo]": "[toFoo][*][nested][*][foo]",
    "[from][*][nestedTwo][*]": "[toFoo][*][nestedTwo][*]"
}
```

But this would throw hard:

```json
{
    "[from][*][foo]": "[to][*][whatever]",
    "[from][*][some]": "[to][some]", // no list into object
    "[from][*][some][*][nested]": "[to][*][nested][some]", // no list into object
    "[from][*][foo][*][overwrite]": "[to][*][nested][*][some]", // from item is not a list #1
    "[from][*][list][*][overwrite]": "[to][*][whatever][*][overwrite]", // to item is not a list #1
    "[from][*][list][*]": "[to][*][whatever][*][overwrite]" // no list to property (and vice versa)
    "[from][*][list][*][foo][*]": "[to][*][whatever][*][overwrite]" // too deep
}
```

### Absolute Paths Only

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

## Maps

### Available canonical fields

```
[Basic][Email]
[Basic][Label]
[Basic][Name]
[Basic][Phone]
[Basic][Summary]
[Basic][Url]
[Basic][Location][Address]
[Basic][Location][City]
[Basic][Location][CountryCode]
[Basic][Location][PostalCode]
[Basic][Profiles][*][Network]
[Basic][Profiles][*][Url]
[Basic][Profiles][*][Username]
[Basic][Overview][Items][*][Label]
[Basic][Overview][Items][*][Value]

[Education][*][Area]
[Education][*][EndDate]
[Education][*][StartDate]
[Education][*][StudyType]

[Languages][*][Language]
[Languages][*][Fluency]

[Projects][*][Name]
[Projects][*][Description]
[Projects][*][Entity]
[Projects][*][Type]
[Projects][*][StartDate]
[Projects][*][EndDate]
[Projects][*][Highlights][*]
[Projects][*][Keywords][*]
[Projects][*][Roles][*]

[Skills][*][Name]
[Skills][*][Label]
[Skills][*][DetailedKeywords][*][Keyword]
[Skills][*][DetailedKeywords][*][Level]
[Skills][*][DetailedKeywords][*][ExperienceInYears]

[Meta][Canonical]
[Meta][Version]
[Meta][LastModified]
[Meta][Content][Labels][Skills]
[Meta][Content][Labels][Languages]
[Meta][Content][Labels][Language]
[Meta][Content][Labels][Overview]
[Meta][Content][Labels][Projects]
[Meta][Content][Labels][Education]
[Meta][Content][Labels][Competences]
[Meta][Content][Labels][MoreCompetences]
[Meta][Content][Labels][ExperienceInYears]
[Meta][Content][Labels][Page]
[Meta][Content][Labels][PageOf]
[Meta][Content][Labels][Years][Singular]
[Meta][Content][Labels][Years][Plural]
```

### Example: Map - JSONResume to Canonical

```json
{
    "map":
    {
        "[basic][email]": "[Basic][Email]",
        "[basic][label]": "[Basic][Label]",
        "[basic][name]": "[Basic][Name]",
        "[basic][phone]": "[Basic][Phone]",
        "[basic][summary]": "[Basic][Summary]",
        "[basic][url]": "[Basic][Url]",
        "[basic][location][address]": "[Basic][Location][Address]",
        "[basic][location][city]": "[Basic][Location][City]",
        "[basic][location][countryCode]": "[Basic][Location][CountryCode]",
        "[basic][location][postalCode]": "[Basic][Location][PostalCode]",
        "[basic][profiles][*][network]": "[Basic][Profiles][*][Network]",
        "[basic][profiles][*][url]": "[Basic][Profiles][*][Url]",
        "[basic][profiles][*][username]": "[Basic][Profiles][*][Username]",
        "[basic][overview][items][*][label]": "[Basic][Overview][Items][*][Label]",
        "[basic][overview][items][*][value]": "[Basic][Overview][Items][*][Value]",
        "[education][*][area]": "[Education][*][Area]",
        "[education][*][endDate]": "[Education][*][EndDate]",
        "[education][*][startDate]": "[Education][*][StartDate]",
        "[education][*][studyType]": "[Education][*][StudyType]",
        "[languages][*][language]": "[Languages][*][Language]",
        "[languages][*][fluency]": "[Languages][*][Fluency]",
        "[projects][*][name]": "[Projects][*][Name]",
        "[projects][*][description]": "[Projects][*][Description]",
        "[projects][*][entity]": "[Projects][*][Entity]",
        "[projects][*][type]": "[Projects][*][Type]",
        "[projects][*][startDate]": "[Projects][*][StartDate]",
        "[projects][*][endDate]": "[Projects][*][EndDate]",
        "[projects][*][highlights][*]": "[Projects][*][Highlights][*]",
        "[projects][*][keywords][*]": "[Projects][*][Keywords][*]",
        "[projects][*][roles][*]": "[Projects][*][Roles][*]",
        "[skills][*][name]": "[Skills][*][Name]",
        "[skills][*][label]": "[Skills][*][Label]",
        "[skills][*][detailedKeywords][*][keyword]": "[Skills][*][DetailedKeywords][*][Keyword]",
        "[skills][*][detailedKeywords][*][level]": "[Skills][*][DetailedKeywords][*][Level]",
        "[skills][*][detailedKeywords][*][experienceInYears]": "[Skills][*][DetailedKeywords][*][ExperienceInYears]",
        "[meta][canonical]": "[Meta][Canonical]",
        "[meta][version]": "[Meta][Version]",
        "[meta][lastModified]": "[Meta][LastModified]",
        "[meta][content][labels][skills]": "[Meta][Content][Labels][Skills]",
        "[meta][content][labels][languages]": "[Meta][Content][Labels][Languages]",
        "[meta][content][labels][language]": "[Meta][Content][Labels][Language]",
        "[meta][content][labels][overview]": "[Meta][Content][Labels][Overview]",
        "[meta][content][labels][projects]": "[Meta][Content][Labels][Projects]",
        "[meta][content][labels][education]": "[Meta][Content][Labels][Education]",
        "[meta][content][labels][competences]": "[Meta][Content][Labels][Competences]",
        "[meta][content][labels][moreCompetences]": "[Meta][Content][Labels][MoreCompetences]",
        "[meta][content][labels][experienceInYears]": "[Meta][Content][Labels][ExperienceInYears]",
        "[meta][content][labels][page]": "[Meta][Content][Labels][Page]",
        "[meta][content][labels][pageOf]": "[Meta][Content][Labels][PageOf]",
        "[meta][content][labels][years][singular]": "[Meta][Content][Labels][Years][Singular]",
        "[meta][content][labels][years][plural": "[Meta][Content][Labels][Years][Plural]"
    }
}
```

### Example: JSONResume to Canonical

```json
{
    "basics":
    {
        "name": "basics.name",
        "label": "basics.label",
        "email": "basics.email",
        "phone": "basics.phone",
        "url": "basics.url",
        "summary": "basics.summary",
        "location":
        {
            "address": "basics.location.address",
            "postalCode": "basics.location.postalCode",
            "city": "basics.location.city",
            "countryCode": "basics.location.countryCode"
        },
        "profiles":
        [
            {
                "network": "basics.profiles.0.network",
                "username": "basics.profiles.0.username",
                "url": "basics.profiles.0.url"
            },
            {
                "network": "basics.profiles.*.network",
                "username": "basics.profiles.*.username",
                "url": "basics.profiles.*.url"
            }
        ],
        "_overview":
        {
            "items":
            [
                {
                    "label": "basics.overview.items.0.label",
                    "value": "basics.overview.items.0.value"
                },
                {
                    "label": "basics.overview.items.1.label",
                    "value": "basics.overview.items.1.value"
                },
                {
                    "label": "basics.overview.items.*.label",
                    "value": "basics.overview.items.*.value"
                }
            ]
        }
    },
    "education":
    [
        {
            "area": "education.0.area",
            "endDate": "education.0.endDate",
            "startDate": "education.0.startDate",
            "studyType": "education.0.studyType"
        },
        {
            "area": "education.1.area",
            "endDate": "education.1.endDate",
            "startDate": "education.1.startDate",
            "studyType": "education.1.studyType"
        }
    ],
    "skills":
    [
        {
            "name": "skills.0.name",
            "_label": "skills.0._label",
            "_detailedKeywords":
            [
                {
                    "keyword": "skills.0._detailedKeywords.0.keyword",
                    "level": "skills.0._detailedKeywords.0.level",
                    "experienceInYears": "skills.0._detailedKeywords.0.experienceInYears"
                },
                {
                    "keyword": "skills.0._detailedKeywords.1.keyword",
                    "level": "skills.0._detailedKeywords.1.level",
                    "experienceInYears": "skills.0._detailedKeywords.1.experienceInYears"
                },
                {
                    "keyword": "skills.0._detailedKeywords.*.keyword",
                    "level": "skills.0._detailedKeywords.*.level",
                    "experienceInYears": "skills.0._detailedKeywords.*.experienceInYears"
                }
            ]
        },
        {
            "name": "skills.*.name",
            "_label": "skills.*._label",
            "_detailedKeywords":
            [
                {
                    "keyword": "skills.*._detailedKeywords.*.keyword",
                    "level": "skills.*._detailedKeywords.*.level",
                    "experienceInYears": "skills.*._detailedKeywords.*.experienceInYears"
                }
            ]
        }
    ],
    "languages":
    [
        {
            "language": "languages.*.language",
            "fluency": "languages.*.fluency"
        }
    ],
    "projects":
    [
        {
            "name": "projects.0.name",
            "description": "projects.0.description",
            "entity": "projects.0.entity",
            "type": "projects.0.type",
            "startDate": "projects.0.startDate",
            "endDate": "projects.0.endDate",
            "highlights":
            [
                "projects.0.highlights.0",
                "projects.0.highlights.1",
                "projects.0.highlights.2",
                "projects.0.highlights.*"
            ],
            "keywords":
            [
                "projects.0.keywords.*"
            ],
            "roles":
            [
                "projects.0.roles.*"
            ]
        },
        {
            "name": "projects.*.name",
            "description": "projects.*.description",
            "entity": "projects.*.entity",
            "type": "projects.*.type",
            "startDate": "projects.*.startDate",
            "endDate": "projects.*.endDate",
            "highlights":
            [
                "projects.*.highlights.*"
            ],
            "keywords":
            [
                "projects.*.keywords.*"
            ],
            "roles":
            [
                "projects.*.roles.*"
            ]
        }
    ],
    "meta":
    {
        "canonical": "meta.canonical",
        "version": "meta.version",
        "lastModified": "meta.lastModified",
        "_content":
        {
            "labels":
            {
                "skills": "meta._content.labels.skills",
                "languages": "meta._content.labels.languages",
                "language": "meta._content.labels.language",
                "overview": "meta._content.labels.overview",
                "projects": "meta._content.labels.projects",
                "education": "meta._content.labels.education",
                "competences": "meta._content.labels.competences",
                "moreCompetences": "meta._content.labels.moreCompetences",
                "experienceInYears": "meta._content.labels.experienceInYears",
                "years":
                {
                    "singular": "meta._content.labels.years.singular",
                    "plural": "meta._content.label.years.plurals"
                },
                "page": "meta._content.labels.page",
                "pageOf": "meta._content.label.pageOfs"
            }
        }
    }
}
```
