# Sitegeist.ZombieHunt 
## Find and destroy undead contents 🧟

Often times editors will only hide obsolete content and never actually delete it. Over time this leads to hidden content
that is so old no one remembers who wrote it, why and whether it can finally be removed ... we call this ZOMBIE content.

This package visualizes zombie contents to the editors and allows to automatically destroy contents that have been zombies 
for a while. Of course the time period for hidden contents to turn into a zombie is configurable as well as the period for zombie
destruction.

### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored by our employer https://www.sitegeist.de.*

## Settings

The zombification- and destruction period can be defined via settings. By default, hidden contents that have not been touched for
a year will turn to zombie content and are due to destruction after another year. 

```yaml
Sitegeist:
    ZombieHunt:
        # hidden nodes turn into zombies after this period
        zombificationPeriod: 31536000
        
        # zombie-nodes start are due to destruction after this period
        destructionPeriod: 31536000
        
        # the label for zombie-nodes
        zombieLabel: '🧟'
        
        # label for zombie-nodes that are due to destruction
        zombieToDestroyLabel: '🔥🧟🔥'
```

## Neos Backend

In the document tree and content structure zombie nodes are marked with "🧟" respectively "🔥🧟🔥" for zombie contents that are due to destruction.
In addition, zombie contents are marked in the backend rendering in the content tree and the content area.

## Cli Commands

The following commands allow to find and destroy zombies.

### `./flow zombie:detect`

Detect zombies in the given site. Will return an error code if zombie contents that is due to destruction is detected.

```shell
USAGE:
./flow zombie:detect [<options>]

OPTIONS:
--site-node          node-name of the site to scan, if not defined all sites are used
--dimension-values   json of the dimension values to use, otherwise default. Example '{"language":["de"]}'
```
### `./flow zombie:destroy`

Remove zombie contents that are due to destruction

```shell
USAGE:
  ./flow zombie:destroy [<options>]

OPTIONS:
  --site-node          node-name of the site to scan, if not defined all sites are used
  --dimension-values   json of the dimension values to use, otherwise default. Example '{"language":["de"]}'
  --dry-run            option to output list of nodes to be deleted without actually deleting them
```

## Installation

Sitegeist.ZombieHunt is available via packagist. Just run `composer require sitegeist/zombiehunt` to install it. We use semantic versioning so every breaking change will increase the major version number.

## Contribution

We will gladly accept contributions. Please send us pull requests.
