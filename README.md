# Sitegeist.ZombieHunt 
## Find and destroy undead contents 🧟


### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored by our employer https://www.sitegeist.de.*

## Settings

The zombification and destruction period can be defined via settings. By default hidden contents that have not been touched for
a year will turn to zombie-content and are due to destruction after another year. 

```yaml
Sitegeist:
  ZombieHunt:
    # hidden contents turn to zombie-content after this period
    zombificationPeriod: 31536000

    # zombie-contents have to be destructed after this period
    destructionPeriod: 31536000
```

## Neos Backend

In the document-tree and content-structure zombie nodes are marked with "🧟" resp. "🔥🧟🔥" for zombie-contents that are due to destruction.
In addition zombie-contents are marked also in the backend rendering.

## Cli Commands

The following commands allow to find and destroy zombies.

```shell
./flow zombie:detect
```

```shell
./flow zombie:destroy
```

## Installation

Sitegeist.ZombieHunt is available via packagist. Just run `composer require sitegeist/zombiehunt` to install it. We use semantic-versioning so every breaking change will increase the major-version number.

## Contribution

We will gladly accept contributions. Please send us pull requests.
