# DimensionKeeper for the Neos Content Repository

This package sync properties between dimensions automatically.

Installation
------------

    composer require ttree/dimensionkeeper

Configuration
-------------

First, you need to edit your Node Type configuration (NodeTypes.yaml), the example below is for
a course (Workshop) that may contain many sessions (Course Instance in the Schema.org terminology). 
Each Session can have a dedicated Location:

    'Your.Package:Workshop':
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
