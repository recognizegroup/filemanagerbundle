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
            fixture = setFixtures('<div class="filetree">' +
            '<div class="titlebar">' +
            '<div data-fm-functionality="directory_up"></div>' +
            '<div data-fm-functionality="create_directory"></div>' +
            '<div data-fm-functionality="sort_filename"></div>' +
            '<input data-fm-functionality="search" />' +
            '<div data-fm-functionality="refresh"></div>' +
            '</div>' +
            '<div class="directories" data-fm-value="directories"></div>' +
            '<div class="mainview" data-fm-value="list_content" data-fm-show="list_view"></div>' +
            '<div class="gridview" data-fm-value="grid_content" data-fm-show="grid_view"></div>' +
            '</div>');
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
        expect( treeviews._contentElement.length).toBeGreaterThan( 0 );
    });

    it('should be able to add elements for each file in the content', function () {
        initializeTreeView();

        var files = [
            { name: "one", path: "two" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );

        expect( treeviews._contentElement.children().length ).toEqual( 3 );
    });

    it('should be able to clear elements if the content is empty', function () {
        initializeTreeView();

        var files = [
            { name: "one", path: "two" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );
        treeviews.refreshContent( [] );

        expect( treeviews._contentElement.children().length ).toEqual( 0 );
    });

    it('should update the content if the file tree content change event has been triggered', function () {
        var eventhandler = new FilemanagerEventHandler();
        var files = [
            { name: "one", path: "two" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];

        initializeTreeView( {
            eventHandler: eventhandler
        });

        eventhandler.trigger( "filemanager:model:content_changed", files );
        expect( treeviews._contentElement.children().length ).toEqual( 3 );
    });

    it('should be able to toggle between grid and list view', function () {
        initializeTreeView();

        var files = [
            { name: "one", path: "two" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews._setOverviewLayout( "grid" );
        treeviews.refreshContent( files );

        expect( $( treeviews._contentElement) ).toEqual( $( "[data-fm-value=grid_content]").get(0) );
        expect( $("[data-fm-show=list_view]").is(":visible") ).toEqual( false );
    });

    it('should hide the list view when the overview layout is set to grid', function () {
        initializeTreeView();

        var files = [
            { name: "one", path: "two" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews._setOverviewLayout( "grid" );
        treeviews.refreshContent( files );

        expect( $("[data-fm-show=list_view]").is(":visible") ).toEqual( false );
    });


    it('should trigger a selection event when a file is clicked', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:select", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        var files = [
            { name: "one", path: "two", type: "file" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );
        treeviews._contentElement.children().eq(0).click();

        expect( triggered ).toEqual( true );
    });

    it('should trigger an open event when a folder is double clicked', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:open", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        var files = [
            { name: "one", path: "two", type: "dir" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );
        treeviews._contentElement.children().eq(0).dblclick();

        expect( triggered ).toEqual( true );
    });

    it('should trigger a sort event when a sort button is clicked', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:sort", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        $("[data-fm-functionality=sort_filename]").click();
        expect( triggered ).toEqual( true );
    });

    it('should trigger a refresh event when a refresh button is clicked', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:refresh", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        $("[data-fm-functionality=refresh]").click();
        expect( triggered ).toEqual( true );
    });

    it('should trigger a directory up event when a directory up button is clicked', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:directory_up", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        $("[data-fm-functionality=directory_up]").click();
        expect( triggered ).toEqual( true );
    });


    it('should trigger a search event when a nonempty search value is sent', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:search", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        var e = jQuery.Event("keyup");
        e.which = 13;
        e.keyCode = 13;
        $("input[data-fm-functionality=search]").val(" search ").trigger( e );

        expect( triggered ).toEqual( true );
    });

    it('should trigger a create directory event when a create directory button is clicked', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:sort", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        $("[data-fm-functionality=sort_filename]").click();
        expect( triggered ).toEqual( true );
    });


    it('should be able to swap a file row in the content with a row containing an input field when a file is being renamed', function () {
        initializeTreeView();

        var files = [
            { name: "one", path: "two", type: "file" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );

        treeviews.createRenamerow( ".file-1", files[0] );
        expect( treeviews._contentElement.eq(0).find("input").length ).toBeGreaterThan( 0 );
    });

    it('should trigger a rename event when the enter key is pressed in a nonempty rename inputfield', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:rename", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        var files = [
            { name: "one", path: "two", type: "file" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );

        treeviews.createRenamerow( ".file-1", files[0] );
        var input = treeviews._contentElement.eq(0).find("input").eq(0);
        input.val("TEST");

        var e = jQuery.Event("keydown");
        e.which = 13;
        e.keyCode = 13;
        input.trigger( e );

        expect( triggered ).toEqual( true );
    });

    it('should call the renamecellFormat function if we are renaming a file in the gridview', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;

        initializeTreeView( {
            eventHandler: eventhandler,
            renamecellFormat: function(){
                triggered = true;
                return "<p></p>";
            }
        });

        var files = [
            { name: "one", path: "two", type: "file" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );
        treeviews._setOverviewLayout( "grid" );
        treeviews.createRenamerow( ".file-1", files[0] );

        expect( triggered ).toEqual( true );
    });

    it('should make a row in the content containing an input field when a create directory button is clicked', function () {
        initializeTreeView();

        var files = [
            { name: "one", path: "two", type: "file" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );

        $("[data-fm-functionality=create_directory]").eq(0).click();
        expect( treeviews._contentElement.eq(0).find("input").length ).toBeGreaterThan( 0 );
    });

    it('should trigger a create event when the enter key is pressed in a nonempty create directory inputfield', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;
        eventhandler.register("filemanager:view:create", function(){
            triggered = true;
        });

        initializeTreeView( {
            eventHandler: eventhandler
        });

        var files = [
            { name: "one", path: "two", type: "file" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );

        $("[data-fm-functionality=create_directory]").click();
        var input = treeviews._contentElement.eq(0).find("input").eq(0).val("asdf");

        var e = jQuery.Event("keydown");
        e.which = 13;
        e.keyCode = 13;
        input.trigger( e );

        expect( triggered ).toEqual( true );
    });

    it('should call the renamecellFormat function if we are creating a directory in the gridview', function () {
        var eventhandler = new FilemanagerEventHandler();
        var triggered = false;

        initializeTreeView( {
            eventHandler: eventhandler,
            renamecellFormat: function(){
                triggered = true;
                return "<p></p>";
            }
        });

        var files = [
            { name: "one", path: "two", type: "file" },
            { name: "one", path: "three" },
            { name: "one", path: "one" }
        ];
        treeviews.refreshContent( files );
        treeviews._setOverviewLayout( "grid" );

        $("[data-fm-functionality=create_directory]").click();
        expect( triggered ).toEqual( true );
    });


});
