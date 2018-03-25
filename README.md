Ark & Noid (plugin for Omeka)
=============================

[Ark & Noid] is a plugin for [Omeka] that creates and manages [ark identifiers],
that can replace the default [cool URIs] of each record, that corresponds to the
simple number of a row in a table of the database.

Arks are short, opaque, meaningless, universal, unique and persistent ids for
any records. The acronym "ark" means "Archival Resource Key". This is a
reference to the Noah’s ark (digital documents will have a long life) and to the
world of archives (Omeka can be an institutional archive) too. Optionally, the
identifiers can be resolved via a service as [N2T], the Name-to-Thing Resolver.

Arks may be built with the utility [Noid], that creates nice opaque identifiers,
that is integrated too via the library [Noid4Php].

See a living example in the [Bibliothèque patrimoniale] of [Mines ParisTech]
(click on "Notice" to display the record of any document).

If you don’t have an authority number, the plugin will create a non standard
ark, but nevertheless a unique and opaque identifier that can be managed.


Presentation of Ark
-------------------

A full ark looks like (official example):

```
    http://example.org/ark:/12025/654xz321/s3/f8.05v.tiff
    \________________/ \__/ \___/ \______/ \____________/
      (replaceable)     |     |      |       Qualifier
           |       ARK Label  |      |    (NMA-supported)
           |                  |      |
 Name Mapping Authority       |    Name (NAA-assigned)
          (NMA)               |
                   Name Assigning Authority Number (NAAN)
```

In Omeka, by default, the ark of an item looks like:

    http://example.org/ark:/12025/b6KN

The "12025" is the id of the institution, that is assigned for free by the
[California Digital Library] to any institution with historical or archival
purposes. The "b6KN" is the short hash of the id, with a control key. The name
is always short, because four characters are enough to create more than ten
millions of unique names.

In the Ark format, a slash "/" means a sub-resource or a hierarchy and a dot
"." means a variant, so each file gets its ark via the qualifier part (its order
by default, but the original filename or the Omeka hash can be used):

    http://example.org/ark:/12345/b6KN/2

Arks for derivatives files are represented as:

    http://example.org/ark:/12345/b6KN/2.original
    http://example.org/ark:/12345/b6KN/2.fullsize
    http://example.org/ark:/12345/b6KN/2.square_thumbnail
    http://example.org/ark:/12345/b6KN/2.thumbnail

Currently, the links to physical files are created via the standard function
record_url() and the type of derivative, as `record_url($file, 'original')`:

The format of the name can be customized with a prefix (recommended), a suffix
and a control key (recommended too). The qualifier part is not required to be
opaque. Advanced schemas can be added via the filters "ark_format_names" and
"ark_format_qualifiers", associated classes and routes.

So the name can be obtained too from another tool used to create and manage
arks, like [NOID], a generator of "nice opaque identifiers". This is useful too
if other unique ids or permanent urls are already created via free or commercial
systems [PURL], [DOI], [Handle], etc. (see the full [CDL example]):

```
    http://OwlBike.example.org/ark:/13030/tqb3kh97gh8w   <----  Example Key
                                doi:10.30/tqb3kh97gh8w         with parallel
                                hdl:13030/tqb3kh97gh8w        parts in other
                                urn:13030:tqb3kh97gh8w          id schemes.
```

For more informations about persistent identifiers, see this [overview]. The
full [specification] of ark can be checked too.


All arks are saved as Dublin Core Identifier, as recommended. This allows to
make a check to avoid duplicates, that are forbidden. This applies to collection
and items. For files, the qualifier part is managed dynamically.

The policy is available at "http://example.org/ark/policy" and "http://example.org/ark:/12345/policy".

Ark can be displayed by default instead of the default internal ids. This plugin
is fully compatible with [Clean Url]: an ark can be used for machine purposes
and a clean url for true humans and for the natural referencement by search
engines.


Presentation of Noid
--------------------

[NOID] allows to create nice opaque identifier, to bind it to an object (the
url here), and to manage them in the long term. Only the template is explained
here.

- A template is formed by :
    - a prefix that defines a subset, a list of ark minted by a subpart of an
    organization, or a specific set (may be empty, or commonly 1 to 5 characters).
    - a `.`,
    - a mode: `s` for sequential, `z` for unlimited sequential, or `r` for random,
    - a mask: composed from one or more of these character repertoires: `d`, `e`,
    `i`, `x`, `v`, `E`, `w` `c` and `l` (see below).
    - and finally a `k` (key for control), if wanted.

- The character repertoires are:
    - Standard:
        - `d`: `{ 0-9 x }` cardinality 10
        - `e`: `{ 1-9 b-z }` - `{l, vowels}` cardinality 29
    - Proposed:
        - `i`: `{ 0-9 x }` cardinality 11
        - `x`: `{ 0-9 a-f _ }` cardinality 17
        - `v`: `{ 0-9 a-z _ }` cardinality 37
        - `E`: `{ 1-9 b-z B-Z }` - `{l, vowels}` cardinality 47
        - `w`: `{ 0-9 a-z A-Z # * + @ _ }` cardinality 67
    - Proposed, but not accepted for Ark:
        - `c`: Visible ASCII - `{ % - . / \ }` cardinality 89
    - Not proposed in the Perl script, but compatible with Ark and useful
    because the longest with only alphanumeric characters:
        - `l`: `{ 0-9 a-z A-Z }` - `{ l }` cardinality 61

The prime cardinality allows to set an efficient check character (`k`) against
a single-character error and a transposition of two single characters.

So, a template like `a.rlllk` allows to create a fixed-size noid for 226981
records and the template `b.rllllk` allows to create 13845841 noids: large
enough in most cases.

The default template in Omeka is `.zek`: unlimited number of sequential names,
with a control key, like `12345/92` and `12345/bkp6` for the NAAN `12345`.

See other [templates] for more explanation and examples.


Installation
------------

Uncompress files and rename plugin folder "Ark".

Then install it like any other Omeka plugin and follow the config instructions.

### Notes

With the format "Omeka Id",  the php extension "BCMath" must be enabled. This is
the case on the majority of the servers (based on Debian or Windows), else
install the  module "php-bcmath", or don’t use this format.

With the format "Noid for php", the plugin requires the php extension "dba",
that uses to be installed by default with Php, and the BerkeleyDB library, that
is installed by default too on standard web servers and any Linux distribution
(package libdb5.3 on Debian), because it is used in many basic tools.

### Perl and php

The output of perl (release 5.20 and greater) and php (before and since release
7.1, where the output of `rand()` and `srand()` were [fixed] in the php
algorithm), are the same for integers at least until 32 bits (perl limit, namely
more than 4 000 000 000 identifiers).

Anyway, it is recommended to use php 7.1 or higher, since previous versions of
php are no more supported.

### Automatic test

For testing and debugging, phpunit 5.7 is required. If you don’t have it:

```
cd tests
wget https://phar.phpunit.de/phpunit-5.7.phar
php phpunit-5.7.phar
```


Usage
-----

Ark ids are automatically added as identifiers when a collection or an item is
saved.

Because an ark should be persistent, if an ark exists already, it will never be
removed or modified automatically. Nevertheless, if it is removed, a new one
will be created according to the specified scheme. Note that the default
internal format create unique ids based on the Omeka id, so the same is created
if the parameters are the same.

To set arks to existing records, simply select them in admin/items/browse and
batch edit them, without any change.

You can run the command line tool too:
```
php -f plugins/Ark/create_arks.php
```

**IMPORTANT**

Even if a check is done after creation of an ark to avoid any duplicate, it’s
not recommended to change parameters once records are public in order to keep
the consistency and the sustainability of the archive.


TODO
----

- Manage the policy statement by item.
- Let the choice to link ark to the file and/or to the record.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.

Don't forget to save the noid database, located in `/files/arkandnoid` by
default.


Troubleshooting
---------------

See online issues on the [plugin issues] page on GitHub.


License
-------

This plugin is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user’s
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.

The original Noid utility was published under BSD. The php version is published
under [CeCILL-B v1], compatible with BSD. See [Noid4Php] for further details.


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM] on GitHub)

Second version of this plugin has been built for [Mines ParisTech].


Copyright
---------

* Copyright Daniel Berthereau, 2015-2018


[Ark & Noid]: https://github.com/Daniel-KM/Omeka-plugin-ArkAndNoid
[Omeka]: https://omeka.org
[ark identifiers]: https://confluence.ucop.edu/display/Curation/ARK
[Cool URIs]: https://www.w3.org/TR/cooluris
[N2T]: http://n2t.org
[Noid]: https://wiki.ucop.edu/display/Curation/NOID
[Noid4Php]: https://github.com/Daniel-KM/Noid4Php
[Bibliothèque patrimoniale]: https://patrimoine.mines-paristech.fr
[Mines ParisTech]: http://mines-paristech.fr
[California Digital Library]: http://www.cdlib.org
[NOID]: https://metacpan.org/pod/distribution/Noid/noid
[PURL]: https://purl.org
[DOI]: http://www.doi.org
[Handle]: http://handle.net
[CDL example]: https://ezid.cdlib.org/learn/id_concepts
[overview]: http://www.metadaten-twr.org/2010/10/13/persistent-identifiers-an-overview
[specification]: https://wiki.ucop.edu/download/attachments/16744455/arkspec.pdf?version=1&modificationDate=1440538826000&api=v2
[Clean Url]: https://github.com/Daniel-KM/Omeka-plugin-CleanUrl
[templates]: https://metacpan.org/pod/distribution/Noid/noid#TEMPLATES
[fixed]: https://secure.php.net/manual/en/migration71.incompatible.php#migration71.incompatible.fixes-to-mt_rand-algorithm
[plugin issues]: https://github.com/Daniel-KM/Omeka-plugin-ArkAndNoid/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[CeCILL-B v1]: https://www.cecill.info/licences/Licence_CeCILL-B_V1-en.txt
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
