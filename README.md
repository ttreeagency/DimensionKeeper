# DimensionKeeper for the Neos Content Repository

This package sync properties between dimensions automatically.

Installation
------------

    composer require ttree/dimensionkeeper

Configuration
-------------

You can enable the property synching by enabling the speciic properties in the NodeType configuration. Here's an example how to enable the synching of the uri path segment and the title properties:

    'Your.Package:Example':
      options:
        TtreeDimensionKeeper:Properties:
          title: true
          uriPathSegment: true

Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to
sponsoring, support request, ... just contact us.

License
-------

Licensed under MIT, see [LICENSE](LICENSE)
