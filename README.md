Ark (plugin for Omeka)
======================

[Ark] is a plugin for [Omeka] that creates and manages [ark identifiers], that
can replace the default [cool URIs] of each record, that corresponds to the
simple number of a row in a table of the database.

Arks are short, opaque, meaningless, universal, unique and persistent ids for
any records. The acronym "ark" means "Archival Resource Key". This is a
reference to the Noah's ark (digital documents will have a long life) and to the
world of archives (Omeka can be an institutional archive) too. Optionally, the
identifiers can be resolved via a service as [N2T], the Name-to-Thing Resolver.

See a living example in the [Bibliothèque patrimoniale] of [Mines ParisTech]
(click on "Notice" to display the record of any document).


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

For more informations about persistent identifiers, see this [overview].

All arks are saved as Dublin Core Identifier, as recommended. This allows to
make a check to avoid duplicates, that are forbidden. This applies to collection
and items. For files, the qualifier part is managed dynamically.

The policy is available at "http://example.org/ark/policy" and "http://example.org/ark:/12345/policy".

Ark can be displayed by default instead of the default internal ids. This plugin
is fully compatible with [Clean Url]: an ark can be used for machine purposes
and a clean url for true humans and for the natural referencement by search
engines.


Installation
------------

Uncompress files and rename plugin folder "Ark".

Then install it like any other Omeka plugin and follow the config instructions.

* Note

With the format "Omeka Id",  the php extension "BCMath" must be enabled. This is
the case on the majority of the servers (based on Debian or Windows), else
install the  module "php-bcmath", or don't use this format.


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

** IMPORTANT **

It's not recommended to change parameters once records are public in order to
keep the consistency and the sustainability of the archive.


Warning
-------

Use it at your own risk.

It's always recommended to backup your files and database regularly so you can
roll back if needed.


Troubleshooting
---------------

See online issues on the [plugin issues] page on GitHub.


License
-------

This plugin is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software's author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user's
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM] on GitHub)

Second version of this plugin has been built for [Mines ParisTech].


Copyright
---------

* Copyright Daniel Berthereau, 2015-2016


[Ark]: https://github.com/Daniel-KM/ArkForOmeka
[Omeka]: https://omeka.org
[ark identifiers]: https://confluence.ucop.edu/display/Curation/ARK
[Cool URIs]: https://www.w3.org/TR/cooluris
[N2T]: http://n2t.org
[Bibliothèque patrimoniale]: https://patrimoine.mines-paristech.fr
[Mines ParisTech]: http://mines-paristech.fr
[California Digital Library]: http://www.cdlib.org
[NOID]: https://metacpan.org/pod/distribution/Noid/noid
[PURL]: https://purl.org
[DOI]: http://www.doi.org
[Handle]: http://handle.net
[CDL example]: https://wiki.ucop.edu/display/DataCite/Identifier+Concepts+and+Practices+at+the+California+Digital+Library
[overview]: http://www.metadaten-twr.org/2010/10/13/persistent-identifiers-an-overview
[Clean Url]: https://github.com/Daniel-KM/CleanUrl
[plugin issues]: https://github.com/Daniel-KM/ArkForOmeka/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
