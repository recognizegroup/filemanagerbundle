'use strict';

describe('FileTreeView', function() {
    var treeviews;
    var mockeventhandler = {
        trigger: function () {
        },
        register: function () {
        }
    };

    function initializeTreeView( config, fixture ){
        if( typeof fixture == "undefined"){
            fixture = setFixtures('<div class="filetree"><div class="titlebar"></div><div class="directories"></div><div class="mainview"></div></div>');
        }
        var defaults = {
            eventHandler: mockeventhandler
        };
        config = $.extend( defaults, config, true);

        treeviews = new FileTreeView( config );
    }

    it('should be able to find views when initialized', function () {
        initializeTreeView();

        expect( treeviews._directoryElement.length).toBeGreaterThan( 0 );
        expect( treeviews._titlebarElement.length).toBeGreaterThan( 0 );
        expect( treeviews._contentElement.length).toBeGreaterThan( 0 );
    });

    it('should be able to find custom defined views when initialized', function () {
        initializeTreeView({ elements:{ directories: ".mainview", main: ".titlebar", title: ".directories" } } );

        expect( treeviews._directoryElement.get(0) ).toHaveClass( "mainview" );
        expect( treeviews._contentElement.get(0) ).toHaveClass( "titlebar" );
        expect( treeviews._titlebarElement.get(0) ).toHaveClass( "directories" );
    });

    it('should be able to add elements for each file in the content', function () {
        initializeTreeView();

        var files = [
            { name: "one", path: "two" },
            { name: "one", path: "three" },
            { name: "one", path: "one" },
        ];
        treeviews.refreshContent( files );

        expect( treeviews._contentElement.children().length ).toEqual( 3 );
    });

});
